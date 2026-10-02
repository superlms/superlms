<?php

namespace Tests\Feature;

use App\Livewire\Accounts\TcCertificate as AccountsTcCertificatePage;
use App\Livewire\Admin\TcCertificate as AdminTcCertificatePage;
use App\Models\Admin\Certificate;
use App\Models\Admin\TransferCertificate;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Certificates & TC: the listings in the Students list's style, the issue
 * form with every field needed, and the class → student picker without a
 * section, the father's name under each student's.
 */
class TcCertificatePanelTest extends TestCase
{
    private int $org = 8;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-02 12:00:00');

        // The page keeps the signed-in school on itself.
        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('image')->nullable();
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
            $t->string('father_name')->nullable();
            $t->string('admission_no')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->string('name');
            $t->integer('order')->default(0);
            $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id');
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('certificates', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('student_detail_id');
            $t->string('type');
            $t->string('event_name');
            $t->string('issued_by');
            $t->string('issued_by_designation')->nullable();
            $t->text('description')->nullable();
            $t->date('issued_date');
            $t->string('certificate_no')->nullable();
            $t->timestamps();
        });
        Schema::create('transfer_certificates', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('student_detail_id');
            $t->string('tc_no')->nullable();
            $t->string('book_no')->nullable();
            $t->string('nationality')->nullable();
            $t->boolean('is_sc_st')->default(false);
            $t->string('last_class_studied')->nullable();
            $t->string('general_conduct')->nullable();
            $t->date('application_date')->nullable();
            $t->date('issue_date');
            $t->timestamps();
        });

        config(['app.key' => 'base64:' . base64_encode(str_repeat('g', 32))]);

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 500;
        $admin->organization_id = $this->org;
        $this->actingAs($admin);

        DB::table('organizations')->insert(['id' => $this->org, 'name' => 'Test School']);
        DB::table('standards')->insert(['id' => 1, 'organization_id' => $this->org, 'name' => 'Class 5']);
        DB::table('sections')->insert([
            ['id' => 1, 'organization_id' => $this->org, 'standard_id' => 1, 'name' => 'A'],
            ['id' => 2, 'organization_id' => $this->org, 'standard_id' => 1, 'name' => 'B'],
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public static function pages(): array
    {
        return ['admin' => [AdminTcCertificatePage::class], 'accounts' => [AccountsTcCertificatePage::class]];
    }

    private function student(string $name, string $father, int $section = 1, ?string $image = null, ?int $org = null): int
    {
        $org ??= $this->org;
        $user = DB::table('users')->insertGetId(['name' => $name, 'image' => $image, 'organization_id' => $org]);

        return DB::table('student_details')->insertGetId([
            'user_id' => $user, 'organization_id' => $org, 'standard_id' => 1, 'section_id' => $section,
            'full_name' => $name, 'father_name' => $father, 'admission_no' => 'ADM-' . strtoupper(substr($name, 0, 3)),
        ]);
    }

    private function certificate(int $student, array $more = []): Certificate
    {
        return Certificate::create($more + [
            'organization_id' => $this->org, 'student_detail_id' => $student, 'type' => 'achievement',
            'event_name' => 'Science Olympiad', 'issued_by' => 'Mr Rao', 'issued_by_designation' => 'Principal',
            'description' => 'First position', 'issued_date' => '2026-09-16',
        ]);
    }

    /** The listing's table, cut out of the page. */
    private function table(string $html): string
    {
        $from = strpos($html, '<table');

        return substr($html, $from, strpos($html, '</table>') - $from);
    }

    private function text(string $html): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags($html)));
    }

    /** A listing row, cut after its wire:key, as plain text. */
    private function rowText(string $row): string
    {
        return $this->text(substr($row, strpos($row, '>') + 1));
    }

    #[DataProvider('pages')]
    public function test_the_certificate_listing_reads_as_the_students_list(string $pageClass): void
    {
        $asha = $this->student('Asha', 'Ramesh', 1, 'https://cdn.test/asha.jpg');
        $bina = $this->student('Bina', 'Suresh');
        $first = $this->certificate($asha);
        $this->certificate($bina, ['issued_date' => '2026-09-10', 'event_name' => 'Maths Quiz']);

        $table = $this->table(Livewire::test($pageClass)->html());

        // Columns: no Type, Certificate No or Date of their own.
        preg_match_all('/<th\s[^>]*>(.*?)<\/th>/s', $table, $m);
        $this->assertSame(['S.No', 'Student', 'Event / Activity', 'Issued By', 'Actions'], array_map('trim', $m[1]));

        // Number, name over admission number, event over its type, issuer over the date.
        $rows = array_slice(explode('<tr wire:key="cert-', $table), 1);
        $this->assertCount(2, $rows);
        $this->assertSame('1 Asha ADM-ASH Science Olympiad Achievement Mr Rao 16 Sep 2026', $this->rowText($rows[0]));
        $this->assertSame('2 B Bina ADM-BIN Maths Quiz Achievement Mr Rao 10 Sep 2026', $this->rowText($rows[1]));
        $this->assertStringNotContainsString($first->certificate_no, $table);
        $this->assertStringNotContainsString('Adm:', $table);

        // The photo opens large; the type is plain text, not a tag.
        $this->assertStringContainsString('wire:click="showStudentPhoto(' . $asha . ')"', $table);
        $this->assertStringNotContainsString('rounded-full bg-amber-100', $table);

        // The Students list's plain action buttons.
        foreach (['previewCert(' . $first->id . ')" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg',
            'editCert(' . $first->id . ')" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg',
            'deleteCert(' . $first->id . ')" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg',
            'class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" title="Download PDF"'] as $button) {
            $this->assertStringContainsString($button, $table);
        }
        $this->assertStringNotContainsString('rounded-md border border-gray-200 text-gray-500', $table);
    }

    #[DataProvider('pages')]
    public function test_the_tc_listing_has_the_same_student_and_buttons(string $pageClass): void
    {
        $asha = $this->student('Asha', 'Ramesh', 1, 'https://cdn.test/asha.jpg');
        $tc = TransferCertificate::create([
            'organization_id' => $this->org, 'student_detail_id' => $asha, 'book_no' => '096', 'nationality' => 'Indian',
            'last_class_studied' => '5th', 'general_conduct' => 'Good', 'application_date' => '2026-09-10', 'issue_date' => '2026-09-16',
        ]);

        $table = $this->table(Livewire::test($pageClass)->set('activeTab', 'tc')->html());

        preg_match_all('/<th\s[^>]*>(.*?)<\/th>/s', $table, $m);
        $this->assertSame(['S.No', 'Student', 'TC No', 'Book No', 'Last Class', 'Conduct', 'Issue Date', 'Actions'], array_map('trim', $m[1]));
        $this->assertSame('1 Asha ADM-ASH ' . $tc->tc_no . ' 096 5th Good 16 Sep 2026', $this->rowText(explode('<tr wire:key="tc-', $table)[1]));
        $this->assertStringContainsString('wire:click="showStudentPhoto(' . $asha . ')"', $table);
        $this->assertStringContainsString('previewTc(' . $tc->id . ')" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg', $table);
        $this->assertStringNotContainsString('rounded-md border border-gray-200 text-gray-500', $table);
    }

    #[DataProvider('pages')]
    public function test_a_click_on_the_photo_shows_it_large(string $pageClass): void
    {
        $asha = $this->student('Asha', 'Ramesh', 1, 'https://cdn.test/asha.jpg');
        $other = $this->student('Other', 'X', 1, 'https://cdn.test/other.jpg', 99);
        $this->certificate($asha);

        $page = Livewire::test($pageClass);
        $this->assertStringNotContainsString('lms-cover', $page->html());

        $page->call('showStudentPhoto', $asha)->assertSet('studentPhoto', 'https://cdn.test/asha.jpg');
        $this->assertStringContainsString('lms-cover', $page->html());

        $page->call('closeStudentPhoto')->assertSet('studentPhoto', null);
        $page->call('showStudentPhoto', $other)->assertSet('studentPhoto', null);   // another school's student
    }

    #[DataProvider('pages')]
    public function test_every_field_of_a_certificate_is_needed(string $pageClass): void
    {
        $asha = $this->student('Asha', 'Ramesh');

        $page = Livewire::test($pageClass)->call('createCert')->call('saveCert')
            ->assertHasErrors(['student_detail_id', 'event_name', 'issued_by', 'issued_by_designation', 'description']);
        $this->assertSame(0, Certificate::count());

        // Both are marked as needed on the form.
        $html = $page->html();
        $this->assertStringNotContainsString('(optional)', substr($html, strpos($html, 'Issue Certificate</h2>')));
        $this->assertMatchesRegularExpression('/Description <span class="text-red-500">\*<\/span>/', $html);
        $this->assertMatchesRegularExpression('/Designation <span class="text-red-500">\*<\/span>/', $html);

        $page->call('selectCertStudent', $asha)
            ->set('event_name', 'Science Olympiad')->set('issued_by', 'Mr Rao')
            ->set('issued_by_designation', 'Principal')->set('description', 'First position')
            ->call('saveCert')->assertHasNoErrors()->assertSet('certModal', false);

        $saved = Certificate::first();
        $this->assertSame([$asha, 'Science Olympiad', 'Principal', 'First position', '2026-10-02'],
            [$saved->student_detail_id, $saved->event_name, $saved->issued_by_designation, $saved->description, $saved->issued_date->toDateString()]);
    }

    #[DataProvider('pages')]
    public function test_the_class_lists_its_students_with_the_fathers_name_and_no_section(string $pageClass): void
    {
        $this->student('Asha', 'Ramesh Kumar', 1);
        $this->student('Bina', 'Suresh Pal', 2);

        foreach ([['createCert', 'certClass', 'certSection', 'Issue Certificate</h2>'], ['createTc', 'tcClass', 'tcSection', 'Transfer Certificate</h2>']] as [$open, $class, $section, $heading]) {
            $page = Livewire::test($pageClass);
            if ($open === 'createTc') {
                $page->set('activeTab', 'tc');
            }
            $html = $page->call($open)->set($class, '1')->html();
            $panel = substr($html, strpos($html, $heading));
            $picker = substr($panel, 0, strpos($panel, 'max-h-56') + 4000);

            // Both sections' students at once, each with the father's name under it.
            $text = $this->text($picker);
            $this->assertStringContainsString('A Asha Ramesh Kumar ADM-ASH', $text);
            $this->assertStringContainsString('B Bina Suresh Pal ADM-BIN', $text);
            $this->assertStringContainsString('<span class="block text-xs text-gray-400 truncate">Ramesh Kumar</span>', $picker);

            // No section to pick.
            $this->assertStringNotContainsString('wire:model.live="' . $section . '"', $panel);
            $this->assertStringNotContainsString('All Sections', $picker);
        }
    }

    #[DataProvider('pages')]
    public function test_clear_empties_every_filter(string $pageClass): void
    {
        $page = Livewire::test($pageClass)
            ->set('search', 'ash')->set('filterMonth', '2026-09')->set('filterClass', '1')->set('filterSection', '2');

        $this->assertStringContainsString('wire:click="clearFilters"', $page->html());

        $page->call('clearFilters')
            ->assertSet('search', '')->assertSet('filterMonth', '')->assertSet('filterClass', '')->assertSet('filterSection', '');
    }
}
