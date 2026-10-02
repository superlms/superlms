<?php

namespace Tests\Feature;

use App\Livewire\Accounts\ReportCard as AccountsReportCardPage;
use App\Livewire\Admin\ReportCard as AdminReportCardPage;
use App\Models\Admin\ReportCard;
use App\Models\User;
use App\Services\ReportCardService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Report Card: the listing in the Students list's style with Active / Inactive
 * in place of Revoke, View in a slide-in (as a transfer certificate is viewed),
 * Download that only downloads, and issuing in a slide-in of two steps — pick
 * the class, section and students, then enter each one's remark and choose
 * their co-scholastic grades.
 */
class ReportCardPanelTest extends TestCase
{
    private int $org = 8;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-02 12:00:00');

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
            $t->string('roll_no')->nullable();
            $t->string('registration_number')->nullable();
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
        Schema::create('exams', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->string('exam_name')->nullable();
            $t->boolean('is_published')->default(false);
            $t->timestamps();
        });
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
            $t->text('co_scholastic')->nullable();
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

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
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
            'full_name' => $name, 'admission_no' => 'ADM-' . strtoupper(substr($name, 0, 3)), 'registration_number' => 'REG-' . strtoupper(substr($name, 0, 3)),
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

    private function text(string $html): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags($html)));
    }

    private function row(string $table, int $id): string
    {
        $row = substr($table, strpos($table, 'wire:key="rc-' . $id . '"'));

        return substr($row, strpos($row, '>') + 1, strpos($row, '</tr>') - strpos($row, '>') - 1);
    }

    /** The slide-in panel under a heading. */
    private function panel(string $html, string $heading): string
    {
        $at = strpos($html, $heading);
        $this->assertNotFalse($at, "No panel headed '{$heading}'.");

        return substr($html, $at);
    }

    // ───────────────────────── listing ─────────────────────────

    #[DataProvider('pages')]
    public function test_the_listing_reads_as_the_students_list(string $pageClass, string $panel): void
    {
        $asha = $this->student('Asha', 'https://cdn.test/asha.jpg');
        $bina = $this->student('Bina');
        $on = $this->card($asha);
        $off = $this->card($bina, 'revoked');

        $html = Livewire::test($pageClass)->set('filterStandard', '1')->html();
        $table = $this->table($html);

        preg_match_all('/<th\s[^>]*>(.*?)<\/th>/s', $table, $m);
        $this->assertSame(['S.No', 'Student Name', 'Class', 'Academic Year', 'Status', 'Issued On', 'Actions'], array_map('trim', $m[1]));

        // Name over admission number, class over section, the status as plain text; then the two buttons.
        $this->assertSame('1 Asha ADM-ASH Class 5 Section A 2026-2027 Active 16 Sep 2026 Active Inactive', $this->text($this->row($table, $on)));
        $this->assertSame('2 B Bina ADM-BIN Class 5 Section A 2026-2027 Inactive 16 Sep 2026 Active Inactive', $this->text($this->row($table, $off)));
        $this->assertMatchesRegularExpression('/text-red-600 font-medium">Inactive</', $table);
        $this->assertStringContainsString('wire:click="showStudentPhoto(' . $asha . ')"', $table);

        // An active card: View, Download, then Active (lit, not to be clicked) and Inactive.
        $row = $this->row($table, $on);
        $view = strpos($row, 'wire:click="openCardView(' . $on . ')"');
        $download = strpos($row, '/report-card/' . $on . '/download" download title="Download"');
        $buttons = strpos($row, 'wire:click="setCardStatus(' . $on . ', \'issued\')"');
        $this->assertTrue($view !== false && $download !== false && $buttons !== false && $view < $download && $download < $buttons);
        $this->assertMatchesRegularExpression('/setCardStatus\(' . $on . ', \'issued\'\)" disabled[^>]*class="[^"]*bg-emerald-50 text-emerald-700/s', $row);
        $this->assertMatchesRegularExpression('/setCardStatus\(' . $on . ', \'revoked\'\)"\s+title="Make inactive"/', $row);

        // An inactive card: nothing to view or download, Inactive lit, Active to click.
        $row = $this->row($table, $off);
        $this->assertStringNotContainsString('openCardView', $row);
        $this->assertStringNotContainsString('/download', $row);
        $this->assertMatchesRegularExpression('/setCardStatus\(' . $off . ', \'issued\'\)"\s+title="Make active"/', $row);
        $this->assertMatchesRegularExpression('/setCardStatus\(' . $off . ', \'revoked\'\)" disabled[^>]*class="[^"]*bg-red-50 text-red-600/s', $row);

        // No Revoke, no Print, no new tab; the filter speaks of active and inactive.
        $this->assertStringNotContainsString('revokeReportCard', $table);
        $this->assertStringNotContainsString('/print', $table);
        $this->assertStringNotContainsString('target="_blank"', $html);
        $this->assertMatchesRegularExpression('/<option value="issued">Active<\/option>\s*<option value="revoked">Inactive<\/option>/', $html);
    }

    #[DataProvider('pages')]
    public function test_active_and_inactive_switch_the_card_whatever_else_the_student_holds(string $pageClass, string $panel): void
    {
        $asha = $this->student('Asha');
        $older = $this->card($asha, 'revoked');
        $newer = $this->card($asha);           // the student already has an active card for the class

        $page = Livewire::test($pageClass)->set('filterStandard', '1');

        // It used to answer "Already issued" and leave the card as it was.
        $page->call('setCardStatus', $older, 'issued');
        $row = DB::table('report_cards')->find($older);
        $this->assertSame(['issued', 'R-1', 'Good', '2026-09-16 10:00:00'], [$row->status, $row->regd_no, $row->remark, $row->issued_at]);
        $this->assertSame('issued', DB::table('report_cards')->where('id', $newer)->value('status'));
        $this->assertSame(2, DB::table('report_cards')->count());

        $page->call('setCardStatus', $older, 'revoked');
        $this->assertSame('revoked', DB::table('report_cards')->where('id', $older)->value('status'));

        // The earlier Issue button's method no longer refuses either.
        $page->call('reissueReportCard', $older);
        $this->assertSame('issued', DB::table('report_cards')->where('id', $older)->value('status'));

        // Anything but the two statuses, or another school's card, changes nothing.
        $foreign = $this->card($this->student('Other', null, 99), 'revoked', 99);
        $page->call('setCardStatus', $foreign, 'issued')->call('setCardStatus', $newer, 'deleted');
        $this->assertSame('revoked', DB::table('report_cards')->where('id', $foreign)->value('status'));
        $this->assertSame('issued', DB::table('report_cards')->where('id', $newer)->value('status'));
    }

    #[DataProvider('pages')]
    public function test_view_opens_the_card_in_a_slide_in_as_a_tc_does(string $pageClass, string $panel): void
    {
        $asha = $this->student('Asha');
        $on = $this->card($asha);
        $off = $this->card($this->student('Bina'), 'revoked');
        $foreign = $this->card($this->student('Other', null, 99), 'issued', 99);

        $page = Livewire::test($pageClass)->set('filterStandard', '1');
        $html = $page->call('openCardView', $on)->assertSet('viewCardId', $on)->html();

        // A panel on the right, not a screen over the whole window.
        $this->assertStringNotContainsString('lms-cover', $html);
        $view = substr($html, strrpos($html, '<div class="fixed inset-x-0 bottom-0 top-16 z-[9999] overflow-hidden">'));
        $this->assertStringContainsString('max-w-3xl bg-white shadow-2xl flex flex-col', $view);
        $this->assertStringContainsString('Asha', $view);
        $this->assertMatchesRegularExpression('/<iframe src="[^"]*\/report-card\/' . $on . '\/view#toolbar=0/', $view);
        $this->assertMatchesRegularExpression('/\/report-card\/' . $on . '\/download" download\s+class="px-4 py-2[^"]*"[^>]*>.*?Download PDF/s', $view);
        $this->assertMatchesRegularExpression('/wire:click="closeCardView"[^>]*>Close<\/button>/s', $view);

        $page->call('closeCardView')->assertSet('viewCardId', null);

        // An inactive card, or another school's, does not open; making the open one inactive closes it.
        $page->call('openCardView', $off)->assertSet('viewCardId', null);
        $page->call('openCardView', $foreign)->assertSet('viewCardId', null);
        $page->call('openCardView', $on)->call('setCardStatus', $on, 'revoked')->assertSet('viewCardId', null);

        $this->assertTrue(Route::has('admin.report-card.view'));
        $this->assertTrue(Route::has('accounts.report-card.view'));
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

    // ───────────────────────── issuing ─────────────────────────

    #[DataProvider('pages')]
    public function test_issuing_is_a_slide_in_over_the_list(string $pageClass, string $panel): void
    {
        $this->student('Asha');
        $this->student('Bina');

        $page = Livewire::test($pageClass)->set('filterStandard', '1');
        $this->assertStringNotContainsString('Issue Report Cards</h2>', $page->html());

        // The list stays behind the panel; nothing is listed until class and section are picked.
        $html = $page->call('openIssueScreen')->assertSet('showIssuePanel', true)->assertSet('viewMode', 'list')->html();
        $this->assertStringContainsString('Choose a filter', Livewire::test($pageClass)->call('openIssueScreen')->html());   // the list's home is still there
        $issue = $this->panel($html, 'Issue Report Cards</h2>');
        $this->assertStringContainsString('max-w-5xl bg-white shadow-2xl flex flex-col', $html);
        $this->assertStringContainsString('wire:model.live="issueStandard"', $issue);
        $this->assertStringContainsString('wire:model.live="issueSection"', $issue);
        $this->assertStringContainsString('Select a class and section', $issue);
        $this->assertStringNotContainsString('Load Students', $issue);

        // Class then section: the students come up by themselves, the admission number under the name.
        $issue = $this->panel($page->set('issueStandard', '1')->set('issueSection', '1')->assertSet('issueStudentsLoaded', true)->html(), 'Issue Report Cards</h2>');
        $list = $this->table($issue);
        preg_match_all('/<th\s[^>]*>(.*?)<\/th>/s', $list, $m);
        $this->assertSame(['', 'S.No', 'Student Name', 'Roll No.', 'Marks Status', 'Report Card'], array_map('trim', $m[1]));
        $this->assertMatchesRegularExpression('/Asha<\/p>\s*<p class="text-xs text-gray-400">ADM-ASH<\/p>/', $list);
        $this->assertStringContainsString('wire:click="openIssueForm"', $issue);

        // Closing puts everything back.
        $page->call('backToList')->assertSet('showIssuePanel', false)->assertSet('issueStandard', '')->assertSet('selectedStudents', []);
    }

    #[DataProvider('pages')]
    public function test_continue_lists_each_student_for_a_remark_and_co_scholastic_grades(string $pageClass, string $panel): void
    {
        $asha = $this->student('Asha');
        $bina = $this->student('Bina');

        $page = Livewire::test($pageClass)->call('openIssueScreen')->set('issueStandard', '1')->set('issueSection', '1')
            ->set('selectedStudents', [$asha, $bina])->call('openIssueForm')->assertSet('showIssueForm', true);

        // Number, name over admission number, the remark, and a grade to choose for each area in each term.
        $issue = $this->panel($page->html(), 'Issue Report Cards</h2>');
        $first = substr($issue, strpos($issue, 'wire:key="issue-row-' . $asha . '"'));
        $first = substr($first, 0, strpos($first, 'wire:key="issue-row-' . $bina . '"'));
        $this->assertStringStartsWith('1 Asha ADM-ASH Remark *', $this->text(substr($first, strpos($first, '>') + 1)));
        $this->assertStringContainsString('wire:model.defer="issueRows.' . $asha . '.remark"', $first);
        $this->assertSame(6, substr_count($first, 'wire:model.defer="issueRows.' . $asha . '.co.term'));
        foreach (ReportCardService::CO_SCHOLASTIC_AREAS as $area) {
            $this->assertStringContainsString(e($area), $first);
        }
        $this->assertStringContainsString('Submit — Issue 2 Report Card(s)', $this->text($issue));

        // Nothing is chosen to begin with, and nothing is issued until it is.
        $page->call('issueReportCards')->assertHasErrors([
            'issueRows.' . $asha . '.remark', 'issueRows.' . $asha . '.co.term1.0', 'issueRows.' . $bina . '.co.term2.2',
        ]);
        $this->assertSame(0, ReportCard::count());
        $issue = $this->panel($page->html(), 'Issue Report Cards</h2>');
        $this->assertStringContainsString('Enter a remark.', $issue);
        $this->assertStringContainsString('Choose every co-scholastic grade.', $issue);

        // A grade set for everyone fills that box for each student; each can still differ.
        $page->call('setAllCoGrade', 'term1', 0, 'B')->call('setAllCoGrade', 'term9', 0, 'A')->call('setAllCoGrade', 'term1', 0, 'Z')
            ->assertSet('issueRows.' . $asha . '.co.term1.0', 'B')->assertSet('issueRows.' . $bina . '.co.term1.0', 'B')
            ->assertSet('issueRows.' . $asha . '.co.term1.1', '');
        foreach ([$asha, $bina] as $id) {
            foreach (['term1', 'term2'] as $term) {
                foreach ([0, 1, 2] as $area) {
                    $page->set("issueRows.{$id}.co.{$term}.{$area}", 'A');
                }
            }
        }
        $page->set("issueRows.{$asha}.co.term2.2", 'C')->set("issueRows.{$asha}.remark", 'Very good')
            ->call('issueReportCards')->assertHasErrors(['issueRows.' . $bina . '.remark']);   // one remark still missing
        $this->assertSame(0, ReportCard::count());

        $page->set("issueRows.{$bina}.remark", 'Keep it up')->call('issueReportCards')
            ->assertHasNoErrors()->assertSet('showIssuePanel', false)->assertSet('showIssueForm', false);

        $card = ReportCard::where('student_detail_id', $asha)->first();
        $this->assertSame(['issued', 'Very good', 'REG-ASH', '2026-10-02'], [$card->status, $card->remark, $card->regd_no, $card->issued_at->toDateString()]);
        $this->assertSame([
            'term1' => ['General Studies' => 'A', 'Health & Physical Education' => 'A', 'Work Behaviour' => 'A'],
            'term2' => ['General Studies' => 'A', 'Health & Physical Education' => 'A', 'Work Behaviour' => 'C'],
        ], $card->co_scholastic);
        $this->assertSame(2, ReportCard::count());
    }

    public function test_the_card_prints_the_grades_chosen_and_a_for_an_older_card(): void
    {
        $chosen = new ReportCard(['co_scholastic' => [
            'term1' => ['General Studies' => 'B', 'Health & Physical Education' => 'a', 'Work Behaviour' => 'E'],
            'term2' => ['General Studies' => 'C', 'Work Behaviour' => 'Z'],
        ]]);

        $this->assertSame([
            'term1' => [
                ['subject' => 'General Studies', 'grade' => 'B'],
                ['subject' => 'Health & Physical Education', 'grade' => 'A'],
                ['subject' => 'Work Behaviour', 'grade' => 'E'],
            ],
            'term2' => [
                ['subject' => 'General Studies', 'grade' => 'C'],
                ['subject' => 'Health & Physical Education', 'grade' => 'A'],   // none saved
                ['subject' => 'Work Behaviour', 'grade' => 'A'],                // not a grade
            ],
        ], ReportCardService::coScholasticFor($chosen));

        // A card issued before the grades could be chosen prints "A" throughout, as it did.
        $older = ReportCardService::coScholasticFor(new ReportCard());
        $this->assertSame(['A', 'A', 'A', 'A', 'A', 'A'], array_merge(array_column($older['term1'], 'grade'), array_column($older['term2'], 'grade')));
    }
}
