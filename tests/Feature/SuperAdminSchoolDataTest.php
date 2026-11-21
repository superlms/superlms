<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\SchoolData;
use App\Models\User;
use App\Support\SchoolFeeTotals;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Super Admin → School Data: one school's School Detail and the analytics of
 * everything it keeps — the fee as the school's own Analytics counts it — and
 * only that school's, then every functionality with its PDF; no lists of
 * records on the page, an Excel download of each list from the header.
 */
class SuperAdminSchoolDataTest extends TestCase
{
    private const SCHOOL = 7;
    private const OTHER  = 8;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('s', 32))]);
        \Carbon\Carbon::setTestNow('2026-11-03 10:00:00');

        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name')->nullable(); $t->string('logo')->nullable(); $t->boolean('status')->default(true);
            foreach (['email', 'mobile_number', 'state', 'education_board', 'medium', 'school_code'] as $c) {
                $t->string($c)->nullable();
            }
            $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable();
            $t->string('mobile_number')->nullable(); $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('organization_id')->nullable(); $t->unsignedBigInteger('allowed_organization_id')->nullable();
            $t->text('permissions')->nullable(); $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->string('board')->nullable();
            $t->integer('order')->default(0); $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('standard_id'); $t->unsignedBigInteger('organization_id')->nullable(); $t->string('name'); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id')->nullable(); $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
            foreach (['full_name', 'admission_no', 'roll_no', 'phone', 'father_name'] as $c) {
                $t->string($c)->nullable();
            }
            $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id')->nullable(); $t->unsignedBigInteger('organization_id');
            $t->string('employee_id')->nullable(); $t->string('phone')->nullable(); $t->string('qualification')->nullable();
            $t->date('date_of_joining')->nullable(); $t->timestamps();
        });
        Schema::create('admin_employees', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('teacher_detail_id')->nullable();
            $t->string('email')->nullable(); $t->string('mobile')->nullable(); $t->string('designation')->nullable(); $t->string('type')->nullable();
            $t->decimal('salary', 10, 2)->nullable(); $t->boolean('is_active')->default(true); $t->date('joining_date')->nullable(); $t->timestamps();
        });
        Schema::create('student_attendances', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('student_detail_id'); $t->unsignedBigInteger('organization_id');
            $t->date('attendance_date'); $t->integer('status'); $t->timestamps();
        });
        Schema::create('fee_structures', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable(); $t->string('fee_type')->nullable();
            $t->decimal('amount', 10, 2)->default(0); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('fee_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id');
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
            $t->string('fee_type')->nullable(); $t->decimal('amount', 10, 2)->default(0); $t->string('payment_mode')->nullable();
            $t->date('payment_date')->nullable(); $t->string('receipt_number')->nullable(); $t->timestamps();
        });
        Schema::create('transportations', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->decimal('monthly_fee', 10, 2)->default(0); $t->timestamps();
        });
        Schema::create('transportation_students', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('transportation_id'); $t->unsignedBigInteger('student_detail_id');
            $t->unsignedBigInteger('organization_id')->nullable(); $t->text('billable_months')->nullable(); $t->timestamps();
        });
        Schema::create('transport_fee_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id');
            $t->decimal('amount', 10, 2)->default(0); $t->date('payment_date')->nullable(); $t->timestamps();
        });

        $now = now();
        DB::table('organizations')->insert([
            ['id' => self::SCHOOL, 'name' => 'Adarsh Public School', 'education_board' => 'CBSE', 'created_at' => $now],
            ['id' => self::OTHER, 'name' => 'Sunrise Academy', 'education_board' => 'State', 'created_at' => $now],
        ]);
        DB::table('standards')->insert([
            ['id' => 1, 'organization_id' => self::SCHOOL, 'name' => 'Class 8', 'order' => 8],
            ['id' => 2, 'organization_id' => self::OTHER, 'name' => 'Class 9', 'order' => 9],
        ]);
        DB::table('sections')->insert([
            ['id' => 1, 'standard_id' => 1, 'organization_id' => self::SCHOOL, 'name' => 'A'],
            ['id' => 2, 'standard_id' => 1, 'organization_id' => self::SCHOOL, 'name' => 'B'],
        ]);
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Aarav', 'role' => 'user', 'organization_id' => self::SCHOOL, 'is_active' => true],
            ['id' => 2, 'name' => 'Bela', 'role' => 'user', 'organization_id' => self::SCHOOL, 'is_active' => false],
            ['id' => 3, 'name' => 'Zed', 'role' => 'user', 'organization_id' => self::OTHER, 'is_active' => true],
            ['id' => 4, 'name' => 'Meena Rao', 'role' => 'teacher', 'organization_id' => self::SCHOOL, 'is_active' => true],
        ]);
        DB::table('student_details')->insert([
            ['id' => 1, 'user_id' => 1, 'organization_id' => self::SCHOOL, 'standard_id' => 1, 'section_id' => 1, 'full_name' => 'Aarav Sharma', 'admission_no' => '2024/118', 'created_at' => $now],
            ['id' => 2, 'user_id' => 2, 'organization_id' => self::SCHOOL, 'standard_id' => 1, 'section_id' => 2, 'full_name' => 'Bela Khan', 'admission_no' => '2024/119', 'created_at' => $now],
            ['id' => 3, 'user_id' => 3, 'organization_id' => self::OTHER, 'standard_id' => 2, 'section_id' => null, 'full_name' => 'Zed Other', 'admission_no' => 'X1', 'created_at' => $now],
        ]);
        DB::table('teacher_details')->insert(['user_id' => 4, 'organization_id' => self::SCHOOL, 'employee_id' => 'T-01', 'qualification' => 'M.Sc.', 'created_at' => $now]);
        DB::table('admin_employees')->insert([
            ['name' => 'Ramesh Driver', 'organization_id' => self::SCHOOL, 'type' => 'driver', 'designation' => 'Bus driver', 'salary' => 15000],
            ['name' => 'Meena Rao', 'organization_id' => self::SCHOOL, 'type' => 'teacher', 'designation' => 'Teacher', 'salary' => 30000],
            ['name' => 'Elsewhere Clerk', 'organization_id' => self::OTHER, 'type' => 'employee', 'designation' => 'Clerk', 'salary' => 9000],
        ]);
        DB::table('student_attendances')->insert([
            ['student_detail_id' => 1, 'organization_id' => self::SCHOOL, 'attendance_date' => '2026-11-03', 'status' => 1],
            ['student_detail_id' => 2, 'organization_id' => self::SCHOOL, 'attendance_date' => '2026-11-03', 'status' => 0],
        ]);
        // Class 8's head: ₹1,000 a student → ₹2,000 for the school; ₹500 paid this session.
        DB::table('fee_structures')->insert(['organization_id' => self::SCHOOL, 'standard_id' => 1, 'fee_type' => 'academic', 'amount' => 1000]);
        DB::table('fee_payments')->insert([
            ['organization_id' => self::SCHOOL, 'student_detail_id' => 1, 'standard_id' => 1, 'section_id' => 1, 'fee_type' => 'academic', 'amount' => 500, 'payment_mode' => 'cash', 'payment_date' => '2026-10-10', 'receipt_number' => 'RCT-7-1'],
            ['organization_id' => self::OTHER, 'student_detail_id' => 3, 'standard_id' => 2, 'section_id' => null, 'fee_type' => 'academic', 'amount' => 999, 'payment_mode' => 'upi', 'payment_date' => '2026-10-11', 'receipt_number' => 'RCT-8-1'],
        ]);
    }

    protected function tearDown(): void
    {
        \Carbon\Carbon::setTestNow();
        parent::tearDown();
    }

    private function signIn(string $role = 'super-admin', ?int $onlySchool = null): void
    {
        $user = User::forceCreate([
            'name' => 'Boss', 'email' => 'boss@example.com', 'role' => $role,
            'permissions' => ['super-admin.school-data'], 'allowed_organization_id' => $onlySchool,
        ]);
        $this->actingAs($user);
    }

    public function test_the_fee_is_the_one_the_schools_own_analytics_counts(): void
    {
        $fee = SchoolFeeTotals::summary(self::SCHOOL);

        $this->assertEquals(2000, $fee['academicFee']);
        $this->assertEquals(500, $fee['collected']);
        $this->assertEquals(1500, $fee['remaining']);
    }

    public function test_one_schools_detail_and_analytics_and_no_lists_of_records(): void
    {
        $this->signIn();
        DB::table('organizations')->where('id', self::SCHOOL)->update(['mobile_number' => '9876543210', 'school_code' => 'ASIC']);

        $page = Livewire::test(SchoolData::class, ['school' => self::SCHOOL])
            ->assertSee('School Detail')
            ->assertSee('Adarsh Public School')
            ->assertSee('9876543210')
            ->assertSee('ASIC')
            ->assertSee('Present Today')
            ->assertSee('50%')            // 1 of 2 marked present
            ->assertSee('₹1,500')         // pending
            ->assertSee('25% collected')  // ₹500 of ₹2,000
            ->assertSee('Students by Class')
            ->assertSee('Class 8')
            ->assertSee('Driver')         // staff by kind
            ->assertSee('Fee by Payment Mode')
            ->assertSee('Functionality Usage')
            ->assertDontSee('Class 9')
            // The lists of records are gone from the page.
            ->assertDontSee('Aarav Sharma')
            ->assertDontSee('Bela Khan')
            ->assertDontSee('RCT-7-1');

        $an = $page->viewData('an');
        $this->assertSame(['total' => 2, 'active' => 1, 'inactive' => 1], array_intersect_key($an['students'], array_flip(['total', 'active', 'inactive'])));
        $this->assertSame(1, $an['teachers']['total']);
        $this->assertSame(1, $an['staff']['total']);          // teachers are not staff
        $this->assertEquals(15000, $an['staff']['bill']);
        $this->assertSame([['label' => 'Class 8', 'count' => 2]], $an['classes']['rows']);
        $this->assertSame(['Cash'], array_column($an['fee']['modes'], 'label')); // not the other school's UPI
        $this->assertEquals(500, array_sum($an['fee']['byMonth']));

        // Another school, and the figures follow it.
        $page->set('school', self::OTHER)->assertSee('Sunrise Academy')->assertDontSee('Class 8')->assertSee('Class 9');
    }

    public function test_a_sub_super_admin_limited_to_one_school_sees_only_it(): void
    {
        $this->signIn('sub-super-admin', self::SCHOOL);

        Livewire::test(SchoolData::class, ['school' => self::OTHER])
            ->assertSet('school', self::SCHOOL)
            ->assertDontSee('Sunrise Academy')
            ->assertSee('Adarsh Public School')
            ->set('school', self::OTHER)
            ->assertSet('school', self::SCHOOL)
            ->assertDontSee('Class 9');
    }

    public function test_each_list_downloads_as_excel_from_the_header(): void
    {
        $this->signIn();

        $page = Livewire::test(SchoolData::class, ['school' => self::SCHOOL])->assertSee('Export Excel');
        foreach (array_keys(SchoolData::EXPORTS) as $list) {
            $page->assertSeeHtml("export('{$list}')");
        }

        foreach (['staff', 'classes', 'fees'] as $list) {
            Livewire::test(SchoolData::class, ['school' => self::SCHOOL])
                ->call('export', $list)
                ->assertFileDownloaded('adarsh-public-school-' . $list . '-2026-11-03.xlsx');
        }

        Livewire::test(SchoolData::class, ['school' => self::SCHOOL])->call('export', 'passwords')->assertNoFileDownloaded();
    }

    public function test_school_data_is_the_last_screen_in_the_menu(): void
    {
        $links = array_column(config('menu.super-admin'), 'link');
        $this->assertSame('super-admin.school-data', end($links));
    }

    // ─── Functionalities as PDFs, and Send to Mail ────────────────────────────

    /** The columns the Students and Fees PDFs read beyond what the lists above need. */
    private function fullSchema(): void
    {
        Schema::table('student_details', function (Blueprint $t) {
            foreach (['email', 'gender', 'religion', 'mother_name', 'local_address', 'permanent_address', 'city', 'state', 'pincode',
                      'board', 'aadhar_no', 'appar_id', 'pen_no', 'registration_number'] as $c) {
                $t->string($c)->nullable();
            }
            $t->date('dob')->nullable();
            $t->date('date_of_admission')->nullable();
            $t->boolean('transportation_required')->default(false);
        });
        Schema::table('users', fn (Blueprint $t) => $t->string('username')->nullable());
        Schema::table('fee_payments', function (Blueprint $t) {
            $t->string('applies_to')->nullable(); $t->decimal('waiver_amount', 10, 2)->default(0); $t->string('waiver_reason')->nullable();
            $t->decimal('penalty_amount', 10, 2)->default(0); $t->text('remark')->nullable(); $t->string('submitted_by')->nullable();
        });
        Schema::table('transportations', function (Blueprint $t) {
            $t->string('route_name')->nullable(); $t->boolean('is_active')->default(true);
        });
        Schema::table('transport_fee_payments', function (Blueprint $t) {
            $t->string('payment_mode')->nullable(); $t->string('receipt_number')->nullable(); $t->text('remark')->nullable();
            $t->unsignedBigInteger('submitted_by')->nullable(); $t->unsignedBigInteger('transportation_id')->nullable();
        });
        Schema::create('fee_concessions', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id')->nullable();
            $t->string('concession_type')->default('amount'); $t->decimal('value', 10, 2)->default(0); $t->string('fee_type')->nullable();
            $t->boolean('is_penalty')->default(false); $t->string('reason')->nullable(); $t->timestamps();
        });

        DB::table('fee_payments')->where('receipt_number', 'RCT-7-1')->update(['submitted_by' => 'Office Clerk', 'remark' => 'October', 'waiver_amount' => 50]);
        DB::table('fee_concessions')->insert(['organization_id' => self::SCHOOL, 'student_detail_id' => 1, 'concession_type' => 'amount',
            'value' => 100, 'fee_type' => 'academic', 'reason' => 'Sibling']);
        DB::table('student_details')->where('id', 1)->update(['gender' => 'male', 'father_name' => 'Ravi Sharma', 'city' => 'Aligarh']);
    }

    public function test_every_functionality_is_listed_with_its_export_pdf_and_send_to_mail_is_in_the_header(): void
    {
        $this->signIn();

        $page = Livewire::test(SchoolData::class, ['school' => self::SCHOOL])
            ->assertSee('Functionalities')
            ->assertSee('Send to Mail')
            ->assertSee('1 payment')    // Fees counts its payments: RCT-7-1, not the other school's
            // Two tabs as on Analytics: the analytics in one, the functionalities in the other.
            ->assertSeeHtml('data-sd-chip="analytics"')
            ->assertSeeHtml('data-sd-chip="functionalities"')
            ->assertSeeHtmlInOrder(['data-sd="analytics"', 'School Detail', 'Functionality Usage', '</section>', 'data-sd="functionalities"', "exportPdf('students')"]);

        foreach (\App\Support\SchoolPdf\Exporter::SECTIONS as $key => [$label]) {
            $page->assertSee($label)->assertSeeHtml("exportPdf('{$key}')");
        }
        $this->assertSame(count(\App\Support\SchoolPdf\Exporter::SECTIONS), substr_count($page->html(), 'wire:click="exportPdf('));
    }

    public function test_the_fees_pdf_has_each_students_fee_and_every_payment_with_who_took_it(): void
    {
        $this->fullSchema();

        $doc = \App\Support\SchoolPdf\Exporter::document(self::SCHOOL, 'fees');
        $fee = collect($doc['blocks'])->where('type', 'fee')->keyBy('title');

        // Only this school's students; Aarav: ₹1,000 less the ₹100 concession, ₹500 paid.
        $this->assertSame(['Aarav Sharma', 'Bela Khan'], $fee->keys()->all());
        $this->assertSame([['Fee', '₹900'], ['Paid', '₹500'], ['Due', '₹400', true]], $fee['Aarav Sharma']['figures']);
        $this->assertStringContainsString('Concession ₹100', $fee['Aarav Sharma']['note']);
        $this->assertSame(['10 Oct 2026', 'RCT-7-1', 'Academic', '₹500', '₹50', '—', 'Cash', 'Counter', 'Office Clerk', 'October'],
            $fee['Aarav Sharma']['rows'][0]);
        $this->assertSame([], $fee['Bela Khan']['rows']);
        $this->assertSame('No payment yet', $fee['Bela Khan']['empty']);
        $this->assertContains(['Collected', '₹500'], $doc['summary']);
    }

    public function test_the_students_pdf_has_a_card_with_every_field_and_the_pdf_downloads(): void
    {
        $this->fullSchema();
        $this->signIn();

        $doc   = \App\Support\SchoolPdf\Exporter::document(self::SCHOOL, 'students');
        $cards = collect($doc['blocks'])->where('type', 'cards')->flatMap(fn ($b) => $b['items'])->keyBy('title');
        $this->assertSame(['Aarav Sharma', 'Bela Khan'], $cards->keys()->sort()->values()->all());
        $fields = collect($cards['Aarav Sharma']['fields'])->mapWithKeys(fn ($f) => [$f[0] => $f[1]]);
        $this->assertSame('Ravi Sharma', $fields['Father']);
        $this->assertSame('Male', $fields['Gender']);
        $this->assertSame('Aligarh', $fields['City']);
        $this->assertSame('1 present, 0 absent', $fields['Attendance']);
        $this->assertSame('₹500', $fields['Fee Paid']);
        $this->assertSame(['Class 8 - A', 'Class 8 - B'], collect($doc['blocks'])->where('type', 'heading')->pluck('text')->all());

        $bytes = \App\Support\SchoolPdf\Exporter::pdf(self::SCHOOL, 'students');
        $this->assertStringStartsWith('%PDF', $bytes);

        Livewire::test(SchoolData::class, ['school' => self::SCHOOL])
            ->call('exportPdf', 'students')
            ->assertFileDownloaded('adarsh-public-school-students-2026-11-03.pdf');
    }

    public function test_an_empty_functionality_still_makes_a_pdf(): void
    {
        $this->signIn();
        Schema::create('ledger_transactions', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('type'); $t->decimal('amount', 12, 2); $t->date('txn_date');
            $t->string('party')->nullable(); $t->string('party_to')->nullable(); $t->string('mode')->nullable(); $t->text('reason')->nullable();
            $t->unsignedBigInteger('created_by')->nullable(); $t->timestamps();
        });

        $this->assertStringStartsWith('%PDF', \App\Support\SchoolPdf\Exporter::pdf(self::SCHOOL, 'ledger'));
        Livewire::test(SchoolData::class, ['school' => self::SCHOOL])
            ->call('exportPdf', 'passwords')
            ->assertNoFileDownloaded();
    }

    public function test_send_to_mail_makes_every_pdf_in_the_background_and_mails_them_to_the_school(): void
    {
        $this->fullSchema();
        $this->signIn();
        \Illuminate\Support\Facades\Bus::fake();

        // No registered email: nothing goes.
        Livewire::test(SchoolData::class, ['school' => self::SCHOOL])->call('sendToMail');
        \Illuminate\Support\Facades\Bus::assertNothingDispatched();

        DB::table('organizations')->where('id', self::SCHOOL)->update(['email' => 'office@adarsh.test']);
        Livewire::test(SchoolData::class, ['school' => self::SCHOOL])->call('sendToMail')
            ->assertSee('Sending');

        \Illuminate\Support\Facades\Bus::assertChained([
            \App\Jobs\SchoolDataMailPdf::class,   // students
            \App\Jobs\SchoolDataMailPdf::class,   // teachers
            \App\Jobs\SchoolDataMailPdf::class,   // staff
            \App\Jobs\SchoolDataMailPdf::class,   // classes
            \App\Jobs\SchoolDataMailPdf::class,   // fees
            \App\Jobs\SchoolDataMailPdf::class,   // attendance
            \App\Jobs\SchoolDataMailSend::class,
        ]);

        // A second click while it is on its way starts nothing new.
        $this->assertFalse(\App\Jobs\SchoolDataMailSend::start(\App\Models\Organization::find(self::SCHOOL), 'office@adarsh.test'));
    }

    public function test_the_last_step_mails_the_pdfs_as_attachments_and_clears_up(): void
    {
        $this->fullSchema();
        config(['services.zeptomail.api_token' => 'test-token', 'services.zeptomail.api_url' => 'https://zepto.test']);
        \Illuminate\Support\Facades\Http::fake(['zepto.test/*' => \Illuminate\Support\Facades\Http::response(['request_id' => 'r1'])]);

        $dir = storage_path('framework/testing/school-data-mail-' . uniqid());
        (new \App\Jobs\SchoolDataMailPdf(self::SCHOOL, 'students', $dir))->handle();
        (new \App\Jobs\SchoolDataMailPdf(self::SCHOOL, 'fees', $dir))->handle();
        file_put_contents($dir . '/ledger.failed', 'boom');
        (new \App\Jobs\SchoolDataMailSend(self::SCHOOL, $dir, 'office@adarsh.test', ['students' => 2, 'fees' => 1]))->handle();

        \Illuminate\Support\Facades\Http::assertSentCount(1);
        \Illuminate\Support\Facades\Http::assertSent(function ($request) {
            $body = $request->data();
            $names = array_column($body['attachments'], 'name');

            return $body['to'][0]['email_address']['address'] === 'office@adarsh.test'
                && $names === ['adarsh-public-school-students-2026-11-03.pdf', 'adarsh-public-school-fees-2026-11-03.pdf']
                && str_starts_with(base64_decode($body['attachments'][0]['content']), '%PDF')
                && str_contains($body['htmlbody'], 'Could not be made this time: Ledger')
                && str_contains($body['subject'], 'Adarsh Public School');
        });
        $this->assertDirectoryDoesNotExist($dir);
        $this->assertSame('sent', \App\Jobs\SchoolDataMailSend::status(self::SCHOOL)['state']);
        $this->assertSame(2, \App\Jobs\SchoolDataMailSend::status(self::SCHOOL)['files']);
    }
}
