<?php

namespace Tests\Feature;

use App\Livewire\Accounts\TcCertificate as AccountsTcCertificatePage;
use App\Livewire\Admin\TcCertificate as AdminTcCertificatePage;
use App\Models\Admin\Certificate;
use App\Models\Admin\TransferCertificate;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Certificates & TC: the listings in the Students list's style, the filter bar,
 * the issue forms (every certificate field needed; class then student, two
 * selects in a row; a TC's last school and what the student's record lacks),
 * delete asked by the page's own popup, and the TC's printed page.
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
            $t->string('mother_name')->nullable();
            $t->date('dob')->nullable();
            $t->date('date_of_admission')->nullable();
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
            $t->string('previous_school_name')->nullable();
            $t->string('previous_school_class')->nullable();
            $t->string('exam_last_taken')->nullable();
            $t->string('whether_failed')->nullable();
            $t->text('subjects_studied')->nullable();
            $t->string('qualified_for_promotion')->nullable();
            $t->string('fees_paid_upto')->nullable();
            $t->string('fee_concession')->nullable();
            $t->integer('total_working_days')->default(0);
            $t->integer('days_present')->default(0);
            $t->string('is_ncc_scout')->nullable();
            $t->string('extra_activities')->nullable();
            $t->string('general_conduct')->nullable();
            $t->date('application_date')->nullable();
            $t->date('issue_date');
            $t->string('reason_for_leaving')->nullable();
            $t->text('remarks')->nullable();
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

    /** A student of Class 5 whose record is complete unless $more blanks something. */
    private function student(string $name, string $father, int $section = 1, ?string $image = null, ?int $org = null, array $more = []): int
    {
        $org ??= $this->org;
        $user = DB::table('users')->insertGetId(['name' => $name, 'image' => $image, 'organization_id' => $org]);

        return DB::table('student_details')->insertGetId($more + [
            'user_id' => $user, 'organization_id' => $org, 'standard_id' => 1, 'section_id' => $section,
            'full_name' => $name, 'father_name' => $father, 'mother_name' => 'Mother of ' . $name,
            'dob' => '2015-04-10', 'date_of_admission' => '2021-04-05',
            'admission_no' => 'ADM-' . strtoupper(substr($name, 0, 3)),
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

    private function tc(int $student, array $more = []): TransferCertificate
    {
        return TransferCertificate::create($more + [
            'organization_id' => $this->org, 'student_detail_id' => $student, 'book_no' => '096', 'nationality' => 'Indian',
            'last_class_studied' => '5th', 'whether_failed' => 'No', 'qualified_for_promotion' => 'Yes', 'is_ncc_scout' => 'No',
            'general_conduct' => 'Good', 'application_date' => '2026-09-10', 'issue_date' => '2026-09-16',
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

    /** The slide-in panel under a heading. */
    private function panel(string $html, string $heading): string
    {
        $at = strpos($html, $heading);
        $this->assertNotFalse($at, "No panel headed '{$heading}'.");

        return substr($html, $at);
    }

    // ───────────────────────── listings ─────────────────────────

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

        // The photo opens large; the type is plain text, not a tag.
        $this->assertStringContainsString('wire:click="showStudentPhoto(' . $asha . ')"', $table);
        $this->assertStringNotContainsString('rounded-full bg-amber-100', $table);

        // The Students list's plain action buttons.
        foreach (['previewCert(' . $first->id . ')" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg',
            'editCert(' . $first->id . ')" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg',
            'deleteCert(' . $first->id . ')" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg'] as $button) {
            $this->assertStringContainsString($button, $table);
        }
        $this->assertStringNotContainsString('rounded-md border border-gray-200 text-gray-500', $table);
    }

    #[DataProvider('pages')]
    public function test_the_tc_listing_has_the_class_after_the_tc_number(string $pageClass): void
    {
        $asha = $this->student('Asha', 'Ramesh', 2, 'https://cdn.test/asha.jpg');
        $tc = $this->tc($asha);

        $table = $this->table(Livewire::test($pageClass)->set('activeTab', 'tc')->html());

        preg_match_all('/<th\s[^>]*>(.*?)<\/th>/s', $table, $m);
        $this->assertSame(['S.No', 'Student', 'TC No', 'Class', 'Book No', 'Last Class', 'Conduct', 'Issue Date', 'Actions'], array_map('trim', $m[1]));

        // The student's class with the section small under it.
        $this->assertSame('1 Asha ADM-ASH ' . $tc->tc_no . ' Class 5 B 096 5th Good 16 Sep 2026', $this->rowText(explode('<tr wire:key="tc-', $table)[1]));
        $this->assertMatchesRegularExpression('/<p class="text-gray-700">Class 5<\/p>(?:\s|<!--.*?-->)*<p class="text-xs text-gray-400">B<\/p>/s', $table);

        $this->assertStringContainsString('wire:click="showStudentPhoto(' . $asha . ')"', $table);
        $this->assertStringContainsString('previewTc(' . $tc->id . ')" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg', $table);
        $this->assertStringContainsString('editTc(' . $tc->id . ')"', $table);

        // An issued TC has no Delete.
        $this->assertStringNotContainsString('deleteTc', $table);
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
    public function test_download_saves_the_file_without_opening_anything(string $pageClass): void
    {
        $asha = $this->student('Asha', 'Ramesh');
        $cert = $this->certificate($asha);
        $tc = $this->tc($asha);

        $certs = Livewire::test($pageClass);
        $this->assertMatchesRegularExpression('/certificates\/' . $cert->id . '\/download" download\s+class="p-1\.5 text-emerald-600/', $this->table($certs->html()));

        $tcs = Livewire::test($pageClass)->set('activeTab', 'tc');
        $this->assertMatchesRegularExpression('/tc\/' . $tc->id . '\/download" download\s+class="p-1\.5 text-emerald-600/', $this->table($tcs->html()));

        // Nothing on the page opens a new tab (which the installed app turned into its viewer).
        foreach ([$certs->html(), $tcs->html(), $certs->call('previewCert', $cert->id)->html()] as $html) {
            $this->assertStringNotContainsString('target="_blank"', $html);
        }
        $this->assertMatchesRegularExpression('/certificates\/' . $cert->id . '\/download" download\s+class="px-4 py-2/', $certs->html());
    }

    // ───────────────────────── filter bar ─────────────────────────

    #[DataProvider('pages')]
    public function test_fifty_a_page_and_no_page_size_box(string $pageClass): void
    {
        $asha = $this->student('Asha', 'Ramesh');
        foreach (range(1, 55) as $i) {
            $this->certificate($asha, ['event_name' => 'Event ' . $i]);
        }

        $page = Livewire::test($pageClass)->assertSet('perPage', 50);
        $html = $page->html();

        $this->assertSame(50, substr_count($html, '<tr wire:key="cert-'));
        $this->assertStringNotContainsString('wire:model.live="perPage"', $html);
        $this->assertStringNotContainsString('/ page', $html);
    }

    #[DataProvider('pages')]
    public function test_the_month_box_reads_select_month_until_one_is_picked(string $pageClass): void
    {
        $page = Livewire::test($pageClass);
        $html = $page->html();
        $this->assertMatchesRegularExpression('/peer-focus:hidden">Select month<\/span>/', $html);
        $this->assertStringContainsString('text-transparent focus:text-gray-700', $html);

        $html = $page->set('filterMonth', '2026-09')->html();
        $this->assertStringNotContainsString('Select month', $html);
        $this->assertStringNotContainsString('text-transparent', $html);
    }

    #[DataProvider('pages')]
    public function test_search_finds_by_name_admission_number_or_certificate_number(string $pageClass): void
    {
        $asha = $this->student('Asha', 'Ramesh');
        $bina = $this->student('Bina', 'Suresh');
        $a = $this->certificate($asha, ['event_name' => 'Science Olympiad']);
        $b = $this->certificate($bina, ['event_name' => 'Maths Quiz']);

        $found = fn (string $term) => collect(Livewire::test($pageClass)->set('search', $term)->viewData('certificates')->items())->pluck('id')->all();

        $this->assertSame([$b->id], $found('Bina'));
        $this->assertSame([$b->id], $found('ADM-BIN'));           // it used to bring back every certificate
        $this->assertSame([$a->id], $found($a->certificate_no));
        $this->assertSame([], $found('nobody'));

        // Transfer certificates: name, admission number or TC number.
        $tcA = $this->tc($asha);
        $tcB = $this->tc($bina);
        $foundTc = fn (string $term) => collect(Livewire::test($pageClass)->set('activeTab', 'tc')->set('search', $term)->viewData('tcList')->items())->pluck('id')->all();

        $this->assertSame([$tcA->id], $foundTc('ADM-ASH'));
        $this->assertSame([$tcB->id], $foundTc('Bina'));
        $this->assertSame([$tcB->id], $foundTc($tcB->tc_no));

        $html = Livewire::test($pageClass)->html();
        $this->assertStringContainsString('placeholder="Name / admission no / certificate no…"', $html);
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

    // ───────────────────────── delete ─────────────────────────

    #[DataProvider('pages')]
    public function test_delete_is_asked_by_the_pages_own_popup(string $pageClass): void
    {
        $asha = $this->student('Asha', 'Ramesh');
        $cert = $this->certificate($asha);
        $tc = $this->tc($asha);

        $page = Livewire::test($pageClass);
        $this->assertStringNotContainsString('lms-cover', $page->html());

        // The Logout popup's card: nothing is deleted until Yes.
        $html = $page->call('deleteCert', $cert->id)->assertSet('pendingDeleteCertId', $cert->id)->html();
        $this->assertStringContainsString('lms-cover fixed inset-0 flex items-center justify-center bg-black/30 backdrop-blur-sm', $html);
        $this->assertStringContainsString('Delete Certificate', $html);
        $this->assertStringContainsString('wire:click="confirmDeleteCert(' . $cert->id . ')"', $html);
        $this->assertNotNull(Certificate::find($cert->id));

        $page->call('cancelDelete')->assertSet('pendingDeleteCertId', null);
        $this->assertNotNull(Certificate::find($cert->id));
        $this->assertStringNotContainsString('lms-cover', $page->html());

        $page->call('deleteCert', $cert->id)->call('confirmDeleteCert', $cert->id)->assertSet('pendingDeleteCertId', null);
        $this->assertNull(Certificate::find($cert->id));

        // An issued transfer certificate is not deleted, whatever asks for it.
        $html = $page->set('activeTab', 'tc')->call('deleteTc', $tc->id)->assertSet('pendingDeleteTcId', null)->html();
        $this->assertStringNotContainsString('lms-cover', $html);
        $page->call('confirmDeleteTc', $tc->id);
        $this->assertNotNull(TransferCertificate::find($tc->id));
    }

    // ───────────────────────── issue certificate ─────────────────────────

    #[DataProvider('pages')]
    public function test_every_field_of_a_certificate_is_needed(string $pageClass): void
    {
        $asha = $this->student('Asha', 'Ramesh');

        $page = Livewire::test($pageClass)->call('createCert')->call('saveCert')
            ->assertHasErrors(['student_detail_id', 'event_name', 'issued_by', 'issued_by_designation', 'description']);
        $this->assertSame(0, Certificate::count());

        // Both are marked as needed on the form.
        $panel = $this->panel($page->html(), 'Issue Certificate</h2>');
        $this->assertStringNotContainsString('(optional)', $panel);
        $this->assertMatchesRegularExpression('/Description <span class="text-red-500">\*<\/span>/', $panel);
        $this->assertMatchesRegularExpression('/Designation <span class="text-red-500">\*<\/span>/', $panel);

        $page->set('certClass', '1')->set('student_detail_id', $asha)
            ->set('event_name', 'Science Olympiad')->set('issued_by', 'Mr Rao')
            ->set('issued_by_designation', 'Principal')->set('description', 'First position')
            ->call('saveCert')->assertHasNoErrors()->assertSet('certModal', false);

        $saved = Certificate::first();
        $this->assertSame([$asha, 'Science Olympiad', 'Principal', 'First position', '2026-10-02'],
            [$saved->student_detail_id, $saved->event_name, $saved->issued_by_designation, $saved->description, $saved->issued_date->toDateString()]);
    }

    #[DataProvider('pages')]
    public function test_the_class_then_the_student_with_the_fathers_name_small_beside(string $pageClass): void
    {
        $this->student('Bina', 'Suresh Pal', 2);
        $asha = $this->student('Asha', 'Ramesh Kumar', 1);

        foreach ([['createCert', 'certClass', 'student_detail_id', 'Issue Certificate</h2>'], ['createTc', 'tcClass', 'tc_student_id', 'Transfer Certificate</h2>']] as [$open, $class, $studentProp, $heading]) {
            $page = Livewire::test($pageClass);
            if ($open === 'createTc') {
                $page->set('activeTab', 'tc');
            }

            // Before a class is picked the student box waits for one.
            $panel = $this->panel($page->call($open)->html(), $heading);
            $this->assertStringContainsString('wire:model.live="' . $class . '"', $panel);
            $this->assertStringContainsString('Select a class first', $panel);

            // The class's students of every section, A to Z: the name, and the
            // father's name small beside it — no admission number.
            $panel = $this->panel($page->set($class, '1')->html(), $heading);
            $picker = substr($panel, 0, strpos($panel, 'Event / Activity Name') ?: strpos($panel, 'Student &amp; Academic'));
            preg_match_all('/wire:click="\$set\(\'' . $studentProp . '\', (\d+)\)".*?<\/button>/s', $picker, $m);
            $this->assertSame(['Asha Ramesh Kumar', 'Bina Suresh Pal'], array_map(fn ($b) => $this->text(substr($b, strpos($b, '>') + 1)), $m[0]));
            $this->assertStringContainsString('<span class="text-sm text-gray-800">Asha</span>', $picker);
            $this->assertStringContainsString('<span class="text-xs text-gray-400">Ramesh Kumar</span>', $picker);
            $this->assertStringNotContainsString('ADM-', $picker);

            // No section, no search box.
            $this->assertStringNotContainsString('All Sections', $picker);
            $this->assertStringNotContainsString('Search by name or admission no.', $picker);

            // Picking sets the student and shows them in the box; another class starts with nobody picked.
            $panel = $this->panel($page->set($studentProp, $asha)->assertSet($studentProp, $asha)->html(), $heading);
            $this->assertMatchesRegularExpression('/<span class="text-gray-900">Asha<\/span>\s*<span class="text-xs text-gray-400">Ramesh Kumar<\/span>/', $panel);
            $page->set($class, '')->assertSet($studentProp, null);
        }
    }

    // ───────────────────────── issue TC ─────────────────────────

    /** Every field of the Issue TC form, filled. */
    private function filledTc($page, int $student, array $more = [])
    {
        $fields = $more + [
            'tcClass' => '1', 'tc_student_id' => $student,
            'nationality' => 'Indian', 'book_no' => '096',
            'previous_school_name' => 'ABC Public School', 'previous_school_class' => '4th',
            'last_class_studied' => '5th', 'exam_last_taken' => '5th Passed', 'subjects_studied' => 'Hindi, English, Maths',
            'total_working_days' => 220, 'days_present' => 201, 'fees_paid_upto' => 'March 2026', 'fee_concession' => 'None',
            'extra_activities' => 'Cricket', 'reason_for_leaving' => 'No Further Classes', 'tc_remarks' => 'No',
        ];
        foreach ($fields as $key => $value) {
            $page->set($key, $value);
        }

        return $page;
    }

    #[DataProvider('pages')]
    public function test_every_field_of_a_tc_is_needed(string $pageClass): void
    {
        $asha = $this->student('Asha', 'Ramesh');

        $page = Livewire::test($pageClass)->set('activeTab', 'tc')->call('createTc')->set('nationality', '')->call('saveTc')
            ->assertHasErrors([
                'tc_student_id', 'nationality', 'book_no', 'previous_school_name', 'previous_school_class', 'last_class_studied',
                'exam_last_taken', 'subjects_studied', 'fees_paid_upto', 'fee_concession', 'extra_activities', 'reason_for_leaving', 'tc_remarks',
            ])
            ->assertHasNoErrors(['whether_failed', 'qualified_for_promotion', 'is_ncc_scout', 'general_conduct', 'application_date', 'tc_issue_date', 'total_working_days', 'days_present']);
        $this->assertSame(0, TransferCertificate::count());

        // Every label carries the star and every field shows its error.
        $panel = $this->panel($page->html(), 'Transfer Certificate</h2>');
        foreach (['Nationality', 'Book No.', 'Last School Name', 'Class in Last School', 'Class Last Studied', 'Exam Last Taken with Result', 'Whether Failed',
            'Qualified for Promotion', 'Subjects Studied', 'Total Working Days', 'Days Present', 'Fees Paid Upto', 'Fee Concession (if any)',
            'NCC / Scout / Guide', 'Games / Extra-Curricular Activities', 'Reason for Leaving', 'Any Other Remark'] as $label) {
            $this->assertStringContainsString($label . ' <span class="text-red-500">*</span>', $panel, $label);
        }
        $this->assertStringContainsString('The book no. field is required.', $panel);
        $this->assertStringContainsString('The remark field is required.', $panel);

        $this->filledTc($page, $asha)->call('saveTc')->assertHasNoErrors()->assertSet('tcModal', false);

        $tc = TransferCertificate::first();
        $this->assertSame([$asha, '096', 'ABC Public School', '4th', 'None', 'No'],
            [$tc->student_detail_id, $tc->book_no, $tc->previous_school_name, $tc->previous_school_class, $tc->fee_concession, $tc->remarks]);

        // Edit brings them back.
        $page->call('editTc', $tc->id)->assertSet('previous_school_name', 'ABC Public School')->assertSet('previous_school_class', '4th')
            ->set('previous_school_name', 'XYZ School')->call('saveTc')->assertHasNoErrors();
        $this->assertSame('XYZ School', $tc->fresh()->previous_school_name);
    }

    #[DataProvider('pages')]
    public function test_what_the_students_record_lacks_is_asked_for_and_saved_to_it(string $pageClass): void
    {
        $whole = $this->student('Asha', 'Ramesh');
        $gaps = $this->student('Bina', 'Suresh', 1, null, null, ['mother_name' => null, 'dob' => null]);

        $page = Livewire::test($pageClass)->set('activeTab', 'tc')->call('createTc')->set('tcClass', '1');

        // A complete record: nothing more is asked.
        $panel = $this->panel($page->set('tc_student_id', $whole)->html(), 'Transfer Certificate</h2>');
        $this->assertStringNotContainsString("Missing in the Student's Record", html_entity_decode($panel, ENT_QUOTES));
        $this->assertSame([], $page->instance()->tcMissingFields);

        // Blanks in the record: just those are asked for.
        $panel = $this->panel($page->set('tc_student_id', $gaps)->html(), 'Transfer Certificate</h2>');
        $this->assertStringContainsString("Missing in the Student's Record", html_entity_decode($panel, ENT_QUOTES));
        $this->assertStringContainsString('wire:model.defer="tcStudentFill.mother_name"', $panel);
        $this->assertStringContainsString('type="date" wire:model.defer="tcStudentFill.dob"', $panel);
        $this->assertStringNotContainsString('tcStudentFill.father_name', $panel);
        $this->assertStringNotContainsString('tcStudentFill.date_of_admission', $panel);

        // They are needed too: left blank, or with a date that is not one, nothing is issued.
        $this->filledTc($page, $gaps)->call('saveTc')
            ->assertHasErrors(['tcStudentFill.mother_name', 'tcStudentFill.dob'])
            ->assertHasNoErrors(['tcStudentFill.father_name', 'tcStudentFill.date_of_admission']);
        $page->set('tcStudentFill.mother_name', 'Sunita Devi')->set('tcStudentFill.dob', 'not a date')->call('saveTc')
            ->assertHasErrors(['tcStudentFill.dob']);
        $this->assertSame(0, TransferCertificate::count());

        // Filled, they go onto the student's record; a value already there is never touched.
        $page->set('tcStudentFill.dob', '2014-08-15')->set('tcStudentFill.father_name', 'Somebody Else')
            ->call('saveTc')->assertHasNoErrors()->assertSet('tcModal', false);

        $record = DB::table('student_details')->where('id', $gaps)->first();
        $this->assertSame(['Sunita Devi', '2014-08-15', 'Suresh'], [$record->mother_name, substr((string) $record->dob, 0, 10), $record->father_name]);
        $this->assertSame(1, TransferCertificate::count());
    }

    // ───────────────────────── the printed TC ─────────────────────────

    public function test_the_printed_tc_has_the_last_school_and_a_line_under_its_heading(): void
    {
        $asha = $this->student('Asha', 'Ramesh');
        $tc = $this->tc($asha, ['previous_school_name' => 'ABC Public School', 'previous_school_class' => '4th'])->load('student.standard');
        $tc->setRelation('organization', Organization::find($this->org));

        $html = view('pdf.admin.tc-certificate', ['tc' => $tc, 'contact' => []])->render();
        $text = $this->text(substr($html, strpos($html, '<body')));

        // The heading sits between two rules.
        $this->assertMatchesRegularExpression('/<div class="rule"><\/div>\s*<div class="title">Transfer Certificate<\/div>\s*<div class="rule"><\/div>/', $html);

        // The two new lines, after the admission line; the numbering runs on.
        $this->assertStringContainsString('6 Date of first admission in the school with class 05/04/2021 · Class Class 5 7 Name of the school last attended before this ABC Public School 8 Class studied in that school 4th 9 Date of birth', $text);
        $this->assertStringContainsString('26 Any other remark No', $text);

        // A certificate issued before these were asked for prints a dash for each.
        $old = $this->tc($asha)->load('student.standard');
        $old->setRelation('organization', Organization::find($this->org));
        $text = $this->text(view('pdf.admin.tc-certificate', ['tc' => $old, 'contact' => []])->render());
        $this->assertStringContainsString('7 Name of the school last attended before this — 8 Class studied in that school — 9', $text);
    }
}
