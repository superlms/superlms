<?php

namespace Tests\Feature;

use App\Livewire\Accounts\ReportCard as AccountsReportCardPage;
use App\Livewire\Admin\ReportCard as AdminReportCardPage;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Report Card listing: in the Students list's style, with View on a screen of
 * its own, Download that only downloads, no Print, and Issue on a revoked card.
 */
class ReportCardPanelTest extends TestCase
{
    private int $org = 8;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('image')->nullable();
            $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable();
            $t->string('full_name')->nullable();
            $t->string('admission_no')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        foreach (['standards', 'sections'] as $table) {
            Schema::create($table, function (Blueprint $t) use ($table) {
                $t->id();
                $t->unsignedBigInteger('organization_id');
                if ($table === 'sections') {
                    $t->unsignedBigInteger('standard_id');
                }
                $t->string('name');
                $t->integer('order')->default(0);
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }
        Schema::create('report_cards', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('student_detail_id');
            $t->unsignedBigInteger('standard_id');
            $t->unsignedBigInteger('section_id');
            $t->string('academic_year')->nullable();
            $t->string('regd_no')->nullable();
            $t->string('remark')->nullable();
            $t->string('result')->nullable();
            $t->dateTime('issued_at')->nullable();
            $t->unsignedBigInteger('issued_by')->nullable();
            $t->string('status')->default('issued');
            $t->timestamps();
        });

        config(['app.key' => 'base64:' . base64_encode(str_repeat('g', 32))]);

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 500;
        $admin->organization_id = $this->org;
        $this->actingAs($admin);

        DB::table('standards')->insert(['id' => 1, 'organization_id' => $this->org, 'name' => 'Class 5']);
        DB::table('sections')->insert(['id' => 1, 'organization_id' => $this->org, 'standard_id' => 1, 'name' => 'Section A']);
    }

    public static function pages(): array
    {
        return ['admin' => [AdminReportCardPage::class, 'admin'], 'accounts' => [AccountsReportCardPage::class, 'accounts']];
    }

    private function student(string $name, ?string $image = null, ?int $org = null): int
    {
        $org ??= $this->org;
        $user = DB::table('users')->insertGetId(['name' => $name, 'image' => $image, 'organization_id' => $org]);

        return DB::table('student_details')->insertGetId([
            'user_id' => $user, 'organization_id' => $org, 'standard_id' => 1, 'section_id' => 1,
            'full_name' => $name, 'admission_no' => 'ADM-' . strtoupper(substr($name, 0, 3)),
        ]);
    }

    /** A report card, written straight to the table (no "issued" push). */
    private function card(int $student, string $status = 'issued', ?int $org = null): int
    {
        return DB::table('report_cards')->insertGetId([
            'organization_id' => $org ?? $this->org, 'student_detail_id' => $student, 'standard_id' => 1, 'section_id' => 1,
            'academic_year' => '2026-2027', 'regd_no' => 'R-1', 'remark' => 'Good', 'issued_at' => '2026-09-16 10:00:00',
            'issued_by' => 500, 'status' => $status,
        ]);
    }

    private function table(string $html): string
    {
        $from = strpos($html, '<table');

        return substr($html, $from, strpos($html, '</table>') - $from);
    }

    private function rowText(string $table, int $id): string
    {
        $row = substr($table, strpos($table, 'wire:key="rc-' . $id . '"'));
        $row = substr($row, strpos($row, '>') + 1, strpos($row, '</tr>') - strpos($row, '>') - 1);

        return trim(preg_replace('/\s+/', ' ', strip_tags($row)));
    }

    #[DataProvider('pages')]
    public function test_the_listing_reads_as_the_students_list(string $pageClass, string $panel): void
    {
        $asha = $this->student('Asha', 'https://cdn.test/asha.jpg');
        $bina = $this->student('Bina');
        $issued = $this->card($asha);
        $revoked = $this->card($bina, 'revoked');

        $table = $this->table(Livewire::test($pageClass)->set('filterStandard', '1')->html());

        // Columns: "Class" (the section under it); no Admission No. of its own.
        preg_match_all('/<th\s[^>]*>(.*?)<\/th>/s', $table, $m);
        $this->assertSame(['S.No', 'Student Name', 'Class', 'Academic Year', 'Status', 'Issued On', 'Actions'], array_map('trim', $m[1]));

        // Name over admission number, class over section, the status as plain text.
        $this->assertSame('1 Asha ADM-ASH Class 5 Section A 2026-2027 Issued 16 Sep 2026', $this->rowText($table, $issued));
        $this->assertSame('2 B Bina ADM-BIN Class 5 Section A 2026-2027 Revoked 16 Sep 2026 Issue', $this->rowText($table, $revoked));
        $this->assertStringNotContainsString('rounded-full uppercase tracking-wide bg-emerald-100', $table);
        $this->assertMatchesRegularExpression('/text-red-600 font-medium">Revoked</', $table);

        // The photo opens large.
        $this->assertStringContainsString('wire:click="showStudentPhoto(' . $asha . ')"', $table);

        // The Students list's plain buttons: View, then Download, then Revoke — no Print.
        $view = strpos($table, 'wire:click="openCardView(' . $issued . ')"');
        $download = strpos($table, '/report-card/' . $issued . '/download" download title="Download"');
        $revoke = strpos($table, 'wire:click="revokeReportCard(' . $issued . ')"');
        $this->assertTrue($view !== false && $download !== false && $revoke !== false && $view < $download && $download < $revoke);
        $this->assertStringContainsString('class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"', $table);
        $this->assertStringContainsString('class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors"', $table);
        $this->assertStringNotContainsString('/print', $table);
        $this->assertStringNotContainsString('target="_blank"', $table);
        $this->assertStringNotContainsString('rounded-md border border-gray-200 text-gray-500', $table);
        $this->assertStringContainsString($panel === 'accounts' ? '/accounts/' : '/' . $this->org . '/', $table);

        // A revoked card has Issue, and nothing to view or download.
        $this->assertStringContainsString('wire:click="reissueReportCard(' . $revoked . ')"', $table);
        $this->assertStringNotContainsString('openCardView(' . $revoked . ')', $table);
        $this->assertStringNotContainsString('/report-card/' . $revoked . '/download', $table);
    }

    #[DataProvider('pages')]
    public function test_view_opens_the_card_on_a_screen_of_its_own(string $pageClass, string $panel): void
    {
        $asha = $this->student('Asha');
        $issued = $this->card($asha);
        $revoked = $this->card($this->student('Bina'), 'revoked');
        $foreign = $this->card($this->student('Other', null, 99), 'issued', 99);

        $page = Livewire::test($pageClass)->set('filterStandard', '1');
        $this->assertStringNotContainsString('lms-cover', $page->html());

        $html = $page->call('openCardView', $issued)->assertSet('viewCardId', $issued)->html();
        $screen = substr($html, strpos($html, 'lms-cover fixed inset-0 z-[9999] bg-white flex flex-col'));

        // Over the whole window, with Back and Exit, and the card in a frame — no toolbar on it.
        $this->assertSame(2, substr_count($screen, 'wire:click="closeCardView"'));
        $this->assertMatchesRegularExpression('/>\s*Back\s*<\/button>/', $screen);
        $this->assertMatchesRegularExpression('/>\s*Exit\s*</', $screen);
        $this->assertStringContainsString('Asha', $screen);
        $this->assertMatchesRegularExpression('/<iframe src="[^"]*\/report-card\/' . $issued . '\/view#toolbar=0/', $screen);
        $this->assertStringNotContainsString('/download', $screen);
        $this->assertStringNotContainsString('/print', $screen);

        $page->call('closeCardView')->assertSet('viewCardId', null);
        $this->assertStringNotContainsString('lms-cover', $page->html());

        // A revoked card, or another school's, does not open.
        $page->call('openCardView', $revoked)->assertSet('viewCardId', null);
        $page->call('openCardView', $foreign)->assertSet('viewCardId', null);

        // The frame's address is a real route in both panels.
        $this->assertTrue(Route::has('admin.report-card.view'));
        $this->assertTrue(Route::has('accounts.report-card.view'));
    }

    #[DataProvider('pages')]
    public function test_issue_on_a_revoked_card_issues_it_again(string $pageClass, string $panel): void
    {
        $asha = $this->student('Asha');
        $card = $this->card($asha, 'revoked');

        $page = Livewire::test($pageClass)->set('filterStandard', '1')->call('reissueReportCard', $card);

        $row = DB::table('report_cards')->find($card);
        $this->assertSame(['issued', 'R-1', 'Good', '2026-09-16 10:00:00'], [$row->status, $row->regd_no, $row->remark, $row->issued_at]);
        $this->assertSame(1, DB::table('report_cards')->count());   // the same card, not a new one
        $this->assertStringContainsString('wire:click="openCardView(' . $card . ')"', $page->html());

        // Revoke still works on it.
        $page->call('revokeReportCard', $card);
        $this->assertSame('revoked', DB::table('report_cards')->where('id', $card)->value('status'));

        // If another was issued for the student and class since, the revoked one stays revoked.
        $this->card($asha);
        $page->call('reissueReportCard', $card);
        $this->assertSame('revoked', DB::table('report_cards')->where('id', $card)->value('status'));

        // Another school's card is not touched.
        $foreign = $this->card($this->student('Other', null, 99), 'revoked', 99);
        $page->call('reissueReportCard', $foreign);
        $this->assertSame('revoked', DB::table('report_cards')->where('id', $foreign)->value('status'));
    }

    #[DataProvider('pages')]
    public function test_a_click_on_the_photo_shows_it_large(string $pageClass, string $panel): void
    {
        $asha = $this->student('Asha', 'https://cdn.test/asha.jpg');
        $other = $this->student('Other', 'https://cdn.test/other.jpg', 99);
        $this->card($asha);

        Livewire::test($pageClass)->set('filterStandard', '1')
            ->call('showStudentPhoto', $asha)->assertSet('studentPhoto', 'https://cdn.test/asha.jpg')
            ->call('closeStudentPhoto')->assertSet('studentPhoto', null)
            ->call('showStudentPhoto', $other)->assertSet('studentPhoto', null);
    }
}
