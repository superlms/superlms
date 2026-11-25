<?php

namespace Tests\Feature;

use App\Http\Controllers\SchoolBoardsController;
use App\Http\Controllers\SchoolSiteController;
use App\Livewire\Admin\Admissions;
use App\Livewire\Admin\Boards;
use App\Livewire\Admin\Enqueries;
use App\Models\Admin\AdmissionEnquiry;
use App\Models\Boards\BoardSetting;
use App\Models\Boards\BoardStudent;
use App\Models\Organization;
use App\Models\SchoolWebsite;
use App\Models\SchoolWebsiteEnquiry;
use App\Models\User;
use App\Support\BoardClasses;
use App\Support\BoardsStudent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * User's ask of 2026-11-25:
 *  - the school website's last menu item, Boards: a Class 9–12 student signs
 *    in with their name, the first word of their father's name and their date
 *    of birth — one of them wrong and nothing is shown — and sees their
 *    personal details, class details and fee with the school's payment QR;
 *  - the admin's More → Boards lists the Class 9–12 students only;
 *  - the website's Online Admission form has the admin's Add Student fields,
 *    a student photo and a signature of 15–25 KB, and lands in both
 *    Admissions and Website Enquiries.
 */
class SchoolBoardsAdmissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('b', 32))]);
        $this->withoutMiddleware(ThrottleRequests::class);
        Storage::fake('s3');
        // MySQL's YEAR() and MONTH(), which the Admissions figures use.
        DB::connection()->getPdo()->sqliteCreateFunction('YEAR', fn ($d) => $d ? (int) substr((string) $d, 0, 4) : null, 1);
        DB::connection()->getPdo()->sqliteCreateFunction('MONTH', fn ($d) => $d ? (int) substr((string) $d, 5, 2) : null, 1);

        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('logo')->nullable();
            $t->string('mobile_number')->nullable(); $t->string('address')->nullable(); $t->string('education_board')->nullable(); $t->timestamps();
        });
        Schema::create('school_infos', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('organization_id'); $t->timestamps(); });
        Schema::create('school_websites', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id')->unique(); $t->string('domain')->nullable()->unique();
            $t->string('template')->default('classic'); $t->json('theme')->nullable(); $t->json('pages')->nullable();
            $t->json('content')->nullable(); $t->boolean('status')->default(false); $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable(); $t->string('image')->nullable();
            $t->boolean('is_active')->default(true); $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->string('board')->nullable();
            $t->integer('order')->default(0); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id')->nullable(); $t->unsignedBigInteger('standard_id'); $t->string('name');
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
            foreach (['full_name', 'father_name', 'mother_name', 'email', 'gender', 'religion', 'local_address', 'permanent_address', 'city',
                         'state', 'pincode', 'admission_no', 'roll_no', 'board', 'aadhar_no', 'phone', 'image', 'appar_id', 'pen_no', 'registration_number'] as $c) {
                $t->string($c)->nullable();
            }
            $t->date('dob')->nullable(); $t->date('date_of_admission')->nullable(); $t->boolean('transportation_required')->default(false); $t->timestamps();
        });
        Schema::create('fee_structures', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
            $t->unsignedBigInteger('student_detail_id')->nullable(); $t->string('fee_name')->nullable(); $t->string('fee_type')->default('academic');
            $t->decimal('amount', 12, 2)->default(0); $t->string('academic_year')->nullable(); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('fee_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id'); $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable(); $t->string('fee_type')->default('academic'); $t->decimal('amount', 12, 2)->default(0);
            $t->decimal('penalty_amount', 12, 2)->default(0); $t->decimal('waiver_amount', 12, 2)->default(0);
            $t->string('payment_mode')->nullable(); $t->date('payment_date')->nullable(); $t->string('receipt_number')->nullable();
            $t->text('remark')->nullable(); $t->string('submitted_by')->nullable(); $t->timestamps();
        });
        Schema::create('fee_concessions', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id'); $t->string('fee_type')->default('all');
            $t->string('concession_type')->default('amount'); $t->decimal('value', 12, 2)->default(0); $t->boolean('is_penalty')->default(false);
            $t->string('reason')->nullable(); $t->string('academic_year')->nullable(); $t->timestamps();
        });
        Schema::create('transportations', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('route_name')->nullable(); $t->decimal('monthly_fee', 12, 2)->default(0);
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('transportation_students', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('transportation_id'); $t->unsignedBigInteger('student_detail_id');
            $t->text('billable_months')->nullable(); $t->timestamps();
        });
        Schema::create('transport_fee_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('transportation_id')->nullable(); $t->unsignedBigInteger('student_detail_id');
            $t->decimal('amount', 12, 2)->default(0); $t->string('payment_mode')->nullable(); $t->date('payment_date')->nullable();
            $t->string('academic_year')->nullable(); $t->text('remark')->nullable(); $t->unsignedBigInteger('submitted_by')->nullable();
            $t->string('receipt_number')->nullable(); $t->timestamps();
        });
        Schema::create('fee_cycles', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('fee_type')->default('academic'); $t->integer('payment_serial')->default(1);
            $t->date('start_date')->nullable(); $t->date('end_date')->nullable(); $t->date('due_date')->nullable();
            $t->decimal('penalty_per_day', 10, 2)->default(0); $t->decimal('fee_percent', 5, 2)->default(0); $t->decimal('amount', 12, 2)->nullable();
            $t->boolean('is_token')->default(false); $t->string('academic_year', 20)->nullable(); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('payment_qr_codes', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('qr_path')->nullable(); $t->string('upi_id')->nullable();
            $t->string('payee_name')->nullable(); $t->text('instructions')->nullable(); $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('updated_by')->nullable(); $t->timestamps();
        });
        Schema::create('school_website_enquiries', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('type', 20)->nullable(); $t->string('name');
            $t->string('email')->nullable(); $t->string('phone')->nullable(); $t->string('subject')->nullable();
            $t->text('message')->nullable(); $t->json('data')->nullable(); $t->timestamps();
        });
        Schema::create('admission_enquiries', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('student_name'); $t->string('email')->nullable(); $t->string('mobile');
            $t->string('guardian_name'); $t->text('address')->nullable(); $t->unsignedBigInteger('standard_id')->nullable(); $t->string('stream')->nullable();
            $t->decimal('admission_fee', 12, 2)->default(0); $t->decimal('collected_amount', 10, 2)->nullable(); $t->string('payment_mode')->nullable();
            $t->string('collected_by')->nullable(); $t->date('fee_collected_at')->nullable(); $t->decimal('total_marks', 8, 2)->nullable();
            $t->decimal('obtained_marks', 8, 2)->nullable(); $t->text('remarks')->nullable(); $t->string('result_pdf')->nullable();
            $t->json('documents')->nullable(); $t->json('details')->nullable(); $t->string('status')->default('pending'); $t->string('source', 20)->nullable();
            $t->timestamps();
        });
        Schema::create('admission_exam_papers', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('standard_id')->nullable(); $t->string('title')->nullable();
            $t->string('file_path')->nullable(); $t->timestamps();
        });
        foreach (['contact_admin_students' => 'student_query', 'contact_admin_teachers' => 'teacher_query'] as $table => $col) {
            Schema::create($table, function (Blueprint $t) use ($col) {
                $t->id(); $t->unsignedBigInteger('user_id')->nullable(); $t->unsignedBigInteger('organization_id');
                $t->string('topic')->nullable(); $t->text($col)->nullable(); $t->string('image')->nullable();
                $t->text('admin_text')->nullable(); $t->boolean('admin_reply')->default(false); $t->timestamps();
            });
        }
        Schema::create('board_settings', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id')->unique(); $t->decimal('roll_fee_percent', 5, 2)->default(0);
            $t->decimal('admit_fee_percent', 5, 2)->default(0); $t->timestamps();
        });
        Schema::create('board_students', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id')->unique();
            $t->string('roll_number', 40)->nullable(); $t->string('admit_card_path')->nullable(); $t->string('admit_card_name')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable(); $t->timestamps();
        });
        app()->forgetInstance('fee_structures.student_rows');

        DB::table('organizations')->insert([
            ['id' => 1, 'name' => 'Sunrise Public School', 'education_board' => 'CBSE'],
            ['id' => 2, 'name' => 'Another School', 'education_board' => 'CBSE'],
        ]);
        DB::table('school_websites')->insert([
            ['organization_id' => 1, 'domain' => 'sunrise.test', 'template' => 'classic', 'status' => true, 'pages' => null],
            ['organization_id' => 2, 'domain' => 'another.test', 'template' => 'mint', 'status' => true, 'pages' => json_encode(['home', 'about'])],
        ]);
        DB::table('standards')->insert([
            ['id' => 8, 'organization_id' => 1, 'name' => 'Class 8', 'order' => 1],
            ['id' => 9, 'organization_id' => 1, 'name' => 'IX', 'order' => 2],
            ['id' => 10, 'organization_id' => 1, 'name' => 'Class 10', 'order' => 3],
            ['id' => 12, 'organization_id' => 1, 'name' => 'XII Science', 'order' => 4],
            ['id' => 20, 'organization_id' => 2, 'name' => 'Class 10', 'order' => 1],
        ]);
        DB::table('sections')->insert([
            ['id' => 31, 'organization_id' => 1, 'standard_id' => 10, 'name' => 'A'],
            ['id' => 32, 'organization_id' => 1, 'standard_id' => 10, 'name' => 'B'],
        ]);

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 900;
        $admin->role = 'admin';
        $admin->organization_id = 1;
        $this->actingAs($admin);
    }

    /** Aman (Class 10-A, fee 12,000, paid 5,000), a Class 8 student, a switched-off login and another school's twin. */
    private function students(): int
    {
        $user = fn (string $name, int $org, bool $on = true) => DB::table('users')->insertGetId(['name' => $name, 'role' => 'user', 'organization_id' => $org, 'is_active' => $on]);
        $aman = DB::table('student_details')->insertGetId([
            'organization_id' => 1, 'user_id' => $user('Aman Kumar', 1), 'standard_id' => 10, 'section_id' => 31,
            'full_name' => 'Aman Kumar', 'father_name' => 'Mr. Ramesh Kumar Sharma', 'mother_name' => 'Sunita Devi', 'dob' => '2010-05-04',
            'gender' => 'male', 'phone' => '9876543210', 'aadhar_no' => '123456789012', 'appar_id' => 'APAAR-77', 'registration_number' => 'REG-10-5',
            'admission_no' => 'ADM-101', 'roll_no' => '10005', 'local_address' => '12 Civil Lines', 'city' => 'Meerut', 'state' => 'Uttar Pradesh',
        ]);
        DB::table('student_details')->insert([
            ['organization_id' => 1, 'user_id' => $user('Riya Singh', 1), 'standard_id' => 8, 'section_id' => null, 'full_name' => 'Riya Singh',
                'father_name' => 'Mahesh Singh', 'dob' => '2012-01-02', 'admission_no' => 'ADM-80', 'roll_no' => '8001', 'appar_id' => null],
            ['organization_id' => 1, 'user_id' => $user('Left School', 1, false), 'standard_id' => 12, 'section_id' => null, 'full_name' => 'Left School',
                'father_name' => 'Old Father', 'dob' => '2008-03-03', 'admission_no' => 'ADM-12', 'roll_no' => '12001', 'appar_id' => null],
            ['organization_id' => 2, 'user_id' => $user('Aman Kumar', 2), 'standard_id' => 20, 'section_id' => null, 'full_name' => 'Aman Kumar',
                'father_name' => 'Ramesh Kumar', 'dob' => '2010-05-04', 'admission_no' => 'OTHER-1', 'roll_no' => '1', 'appar_id' => null],
        ]);
        DB::table('fee_structures')->insert([
            ['organization_id' => 1, 'standard_id' => 10, 'fee_name' => 'Tuition Fee', 'amount' => 10000, 'academic_year' => '2026-27'],
            ['organization_id' => 1, 'standard_id' => 10, 'fee_name' => 'Board Exam Fee', 'amount' => 2000, 'academic_year' => '2026-27'],
        ]);
        DB::table('fee_payments')->insert(['organization_id' => 1, 'student_detail_id' => $aman, 'amount' => 5000, 'payment_mode' => 'cash',
            'payment_date' => '2026-07-01', 'receipt_number' => 'RCT-1-2026-00009']);
        DB::table('payment_qr_codes')->insert(['organization_id' => 1, 'qr_path' => 'https://cdn.example.com/qr.png', 'upi_id' => 'sunrise@okaxis',
            'payee_name' => 'Sunrise Public School', 'is_active' => true]);

        return $aman;
    }

    /** A page's words, each tag a space. */
    private function text(string $html): string
    {
        $html = preg_replace('#<(style|script)\b.*?</\1>#s', '', $html);

        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(preg_replace('/<[^>]+>/', ' ', $html))));
    }

    private function login(array $data, int $org = 1)
    {
        return $this->postJson('/api/website/school-boards/login', $data + ['organization_id' => $org]);
    }

    private function site(int $org = 1): SchoolWebsite
    {
        $site = SchoolWebsite::where('organization_id', $org)->first();
        $site->setRelation('organization', Organization::find($org)->setRelation('schoolInfo', null));

        return $site;
    }

    public function test_a_class_name_is_read_as_its_class_number(): void
    {
        foreach (['Class 9' => 9, 'IX' => 9, 'ix-A' => 9, '10th' => 10, 'Class X' => 10, 'Class XI Science' => 11, 'XII' => 12,
                     'Twelfth' => 12, 'Class 1' => 1, 'Class 9 (2025-26)' => 9, 'Nursery' => null, 'LKG' => null, '2025-26' => null] as $name => $level) {
            $this->assertSame($level, BoardClasses::level($name), $name);
        }
        $this->assertTrue(BoardClasses::isBoard('Class 12 Commerce'));
        $this->assertFalse(BoardClasses::isBoard('Class 8'));
        $this->assertSame([9, 10, 12], BoardClasses::standardIds(1));
    }

    public function test_a_board_student_signs_in_only_when_name_fathers_first_name_and_birth_date_all_match(): void
    {
        $this->students();

        // Any case, spacing or dots; the father's first word with or without his "Mr.".
        foreach ([['  aman   KUMAR ', 'ramesh'], ['Aman Kumar', 'Mr.'], ['Aman Kumar', 'Ramesh Kumar Sharma']] as [$name, $father]) {
            $res = $this->login(['name' => $name, 'father' => $father, 'dob' => '2010-05-04']);
            $res->assertOk()->assertJson(['success' => true]);
        }
        $html = $res->json('html');
        $text = $this->text($html);
        foreach (['Aman Kumar', 'Class 10 – A', 'Roll No 10005', 'Personal Details', "Father's Name Mr. Ramesh Kumar Sharma",
                     "Mother's Name Sunita Devi", 'Date of Birth 04 May 2010', 'Apaar ID APAAR-77', 'Class Details', 'Section A',
                     'Fee Details', 'Tuition Fee ₹10,000', 'Board Exam Fee ₹2,000', 'Total Fee ₹12,000', 'Paid ₹5,000', 'Balance ₹7,000',
                     'RCT-1-2026-00009', 'Pay Fee', 'UPI ID sunrise@okaxis'] as $see) {
            $this->assertStringContainsString($see, $text, $see);
        }
        $this->assertStringContainsString('src="https://cdn.example.com/qr.png"', $html);
        $this->assertStringContainsString('XXXX XXXX 9012', $text);
        $this->assertStringNotContainsString('123456789012', $html);
        $this->assertStringContainsString('upi://pay?pa=sunrise%40okaxis', $html);

        // One of the three wrong — or not a Class 9–12 student, switched off, another school's — and nothing.
        foreach ([
            ['name' => 'Aman Kumarr', 'father' => 'Ramesh', 'dob' => '2010-05-04'],
            ['name' => 'Aman Kumar', 'father' => 'Suresh', 'dob' => '2010-05-04'],
            ['name' => 'Aman Kumar', 'father' => 'Kumar', 'dob' => '2010-05-04'],
            ['name' => 'Aman Kumar', 'father' => 'Ramesh', 'dob' => '2010-05-05'],
            ['name' => 'Riya Singh', 'father' => 'Mahesh', 'dob' => '2012-01-02'],
            ['name' => 'Left School', 'father' => 'Old', 'dob' => '2008-03-03'],
        ] as $try) {
            $this->login($try)->assertStatus(422)->assertJson(['success' => false, 'message' => SchoolBoardsController::NO_MATCH])->assertJsonMissingPath('html');
        }
        $this->login(['name' => 'Aman Kumar', 'father' => '', 'dob' => '2010-05-04'])->assertStatus(422)->assertJsonValidationErrors('father');

        // A school with the Boards page switched off has no login.
        $this->login(['name' => 'Aman Kumar', 'father' => 'Ramesh', 'dob' => '2010-05-04'], 2)->assertStatus(404);
    }

    public function test_the_tab_stays_signed_in_on_its_token_for_that_school_only(): void
    {
        $this->students();
        $token = $this->login(['name' => 'Aman Kumar', 'father' => 'Ramesh', 'dob' => '2010-05-04'])->json('token');

        $this->postJson('/api/website/school-boards/me', ['organization_id' => 1, 'token' => $token])
            ->assertOk()->assertJson(['success' => true]);
        $this->postJson('/api/website/school-boards/me', ['organization_id' => 1, 'token' => $token . 'x'])->assertStatus(401);

        // Another school's site, or a token run out.
        DB::table('school_websites')->where('organization_id', 2)->update(['pages' => null]);
        $this->postJson('/api/website/school-boards/me', ['organization_id' => 2, 'token' => $token])->assertStatus(401);
        $this->travel(BoardsStudent::MINUTES + 1)->minutes();
        $this->postJson('/api/website/school-boards/me', ['organization_id' => 1, 'token' => $token])->assertStatus(401);
    }

    public function test_boards_is_the_last_item_of_every_templates_menu_and_shows_the_login(): void
    {
        foreach (['classic', 'vibrant', 'breeze', 'mint'] as $tpl) {
            SchoolWebsite::where('organization_id', 1)->update(['template' => $tpl]);
            $res = app(SchoolSiteController::class)->render($this->site(), 'boards');
            $this->assertSame(200, $res->getStatusCode(), $tpl);
            $html = $res->getContent();
            $this->assertStringContainsString('Boards Login', $html, $tpl);
            $this->assertStringContainsString('data-bd data-org="1"', $html, $tpl);
            foreach (['Student Name', "Father's Name (first word)", 'Date of Birth'] as $field) {
                $this->assertStringContainsString($field, html_entity_decode($html), "{$tpl}: {$field}");
            }
            preg_match('#<ul class="menu-list" id="menuList">(.*?)</ul>\s*<div class="menu-panel-foot">#s', $html, $m);
            preg_match_all('#<li class="[^"]*">\s*<(?:a href="[^"]*"|button type="button" aria-haspopup="true")>\s*([^<]+?)\s*<#', $m[1] ?? '', $items);
            $this->assertSame('Boards', end($items[1]), $tpl);
        }

        // Switched off for a site: no page, no menu item.
        $res = app(SchoolSiteController::class)->render($this->site(2), 'boards');
        $this->assertSame(404, $res->getStatusCode());
    }

    public function test_the_online_admission_form_has_the_add_student_fields_with_photo_and_signature(): void
    {
        DB::table('transportations')->insert(['id' => 5, 'organization_id' => 1, 'route_name' => 'Route 1 — Civil Lines', 'monthly_fee' => 800]);

        foreach (['classic', 'mint'] as $tpl) {
            SchoolWebsite::where('organization_id', 1)->update(['template' => $tpl]);
            $html = app(SchoolSiteController::class)->render($this->site(), 'admission')->getContent();
            $text = html_entity_decode(preg_replace('/\s+/', ' ', strip_tags(preg_replace('#<(style|script)\b.*?</\1>#s', '', $html))));
            $at = -1;
            foreach (['Student Profile Photo', 'Full Name', 'Email', 'Mobile', 'Gender', 'Class', 'Section', 'Date of Birth', 'Date of Admission', 'Religion',
                         'Aadhar Number', "Father's Name", "Mother's Name", 'Apaar ID', 'Registration Number', 'State', 'City', 'Pincode',
                         'Transport Required?', 'Select Route', 'Local Address', 'Permanent Address', 'Signature', 'Submit'] as $label) {
                $pos = strpos($text, $label, $at + 1);
                $this->assertNotFalse($pos, "{$tpl}: {$label}");
                $this->assertGreaterThan($at, $pos, "{$tpl}: {$label} in the Add Student order");
                $at = $pos;
            }
            $this->assertStringContainsString('15 KB to 25 KB', $text);
            $this->assertStringContainsString('<option value="10">Class 10</option>', $html);
            $this->assertStringContainsString('<option value="Uttar Pradesh">Uttar Pradesh</option>', $html);
            $this->assertStringContainsString('Route 1 — Civil Lines — ₹800/mo', $html);
            $this->assertStringContainsString('data-af-form data-org="1"', $html);
            $this->assertStringNotContainsString('<form class="sform"', $html);   // not the JSON enquiry form
        }
    }

    private function admission(array $over = [], array $files = []): \Illuminate\Testing\TestResponse
    {
        return $this->post('/api/website/school-admission', array_merge([
            'organization_id' => 1, 'name' => 'Kavya Sharma', 'email' => 'parent@example.com', 'mobile' => '9812345678', 'gender' => 'female',
            'standard_id' => 10, 'section_id' => 32, 'dob' => '2011-08-15', 'religion' => 'Hindu', 'aadhar_no' => '111122223333',
            'father_name' => 'Rajesh Sharma', 'mother_name' => 'Meena Sharma', 'appar_id' => 'AP-1', 'state' => 'Uttar Pradesh', 'city' => 'Meerut',
            'pincode' => '250001', 'transport' => '1', 'route_id' => 5, 'local_address' => '7 Abu Lane', 'permanent_address' => '',
            'photo'     => $files['photo'] ?? UploadedFile::fake()->image('kavya.jpg', 300, 400)->size(20),
            'signature' => $files['signature'] ?? UploadedFile::fake()->image('sign.png', 400, 120)->size(16),
        ], $over), ['Accept' => 'application/json']);
    }

    public function test_an_admission_lands_in_admissions_and_website_enquiries_with_its_photo_and_signature(): void
    {
        DB::table('transportations')->insert(['id' => 5, 'organization_id' => 1, 'route_name' => 'Route 1', 'monthly_fee' => 800]);

        // The photo and signature must each be 15 KB to 25 KB.
        $this->admission([], ['photo' => UploadedFile::fake()->image('small.jpg')->size(10)])
            ->assertStatus(422)->assertJsonValidationErrors(['photo' => 'The student photo must be 15 KB to 25 KB.']);
        $this->admission([], ['signature' => UploadedFile::fake()->image('big.png')->size(30)])
            ->assertStatus(422)->assertJsonValidationErrors(['signature' => 'The signature must be 15 KB to 25 KB.']);
        $this->admission(['signature' => null])->assertStatus(422)->assertJsonValidationErrors('signature');
        // A class with sections needs one; another school's class or route is turned away.
        $this->admission(['section_id' => null])->assertStatus(422)->assertJsonValidationErrors('section_id');
        $this->admission(['standard_id' => 20])->assertStatus(422)->assertJsonValidationErrors('standard_id');
        $this->admission(['route_id' => 99])->assertStatus(422)->assertJsonValidationErrors('route_id');
        $this->assertSame(0, AdmissionEnquiry::count() + SchoolWebsiteEnquiry::count());

        $this->admission()->assertOk()->assertJson(['success' => true]);

        // Website Enquiries: an Online Admission with every answer, the photo and the signature.
        $web = SchoolWebsiteEnquiry::sole();
        $this->assertSame(['admission', 'Kavya Sharma', '9812345678', 'Online Admission — Class 10'], [$web->type, $web->name, $web->phone, $web->subject]);
        $fields = $web->fields();
        $this->assertSame(['Full Name' => 'Kavya Sharma', 'Email' => 'parent@example.com', 'Mobile' => '9812345678', 'Gender' => 'Female',
            'Class' => 'Class 10', 'Section' => 'B', 'Date of Birth' => '15 Aug 2011', 'Religion' => 'Hindu', 'Aadhar Number' => '111122223333',
            "Father's Name" => 'Rajesh Sharma', "Mother's Name" => 'Meena Sharma', 'Apaar ID' => 'AP-1', 'State' => 'Uttar Pradesh', 'City' => 'Meerut',
            'Pincode' => '250001', 'Transport Required' => 'Yes', 'Route' => 'Route 1 — ₹800/mo', 'Local Address' => '7 Abu Lane'], $fields);
        $this->assertSame(['Student Photo', 'Signature'], array_keys($web->files()));

        // Admissions: a pending admission from the website, the father as guardian, the pictures as its documents.
        $adm = AdmissionEnquiry::sole();
        $this->assertSame(['Kavya Sharma', 'Rajesh Sharma', '9812345678', 10, 'pending', 'website'],
            [$adm->student_name, $adm->guardian_name, $adm->mobile, (int) $adm->standard_id, $adm->status, $adm->source]);
        $this->assertSame($fields, $adm->detailRows());
        $this->assertSame(['Student Photo', 'Signature'], array_column($adm->documents, 'name'));
        foreach ($adm->documents as $doc) {
            Storage::disk('s3')->assertExists($doc['path']);
            $this->assertStringStartsWith('admin/admissions/website/1/', $doc['path']);
        }

        // Both admin screens show it.
        $list = Livewire::test(Admissions::class);
        $this->assertStringContainsString('Website</span>', $list->html());
        $view = $list->call('viewEnquiry', $adm->id)->html();
        foreach (['From your website', 'Admission Form', 'Aadhar Number', '111122223333', 'Route 1 — ₹800/mo', 'alt="Student Photo"', 'alt="Signature"'] as $see) {
            $this->assertStringContainsString($see, html_entity_decode($view), $see);
        }
        Livewire::test(Enqueries::class)->call('showTab', 'website')->call('viewEnquiry', $web->id)
            ->assertSee(['Online Admission Enquiry', "Father's Name", 'Rajesh Sharma'])
            ->assertSeeHtml(['alt="Student Photo"', 'alt="Signature"']);
    }

    public function test_more_boards_lists_the_class_9_to_12_students_only(): void
    {
        $aman = $this->students();

        $page = Livewire::test(Boards::class);
        $text = $this->text($page->html());
        $this->assertStringContainsString('IX: 0', $text);
        $this->assertStringContainsString('Class 10: 1', $text);
        $this->assertStringContainsString('XII Science: 1', $text);
        $this->assertStringContainsString('Total: 2', $text);
        foreach (['Aman Kumar', 'ADM-101', 'Mr. Ramesh Kumar Sharma', 'APAAR-77', 'REG-10-5', '04 May 2010', 'Left School'] as $see) {
            $this->assertStringContainsString($see, $text, $see);
        }
        foreach (['Riya Singh', 'OTHER-1', 'Class 8'] as $not) {
            $this->assertStringNotContainsString($not, $text, $not);
        }

        $page->set('filterStandard', '12');
        $this->assertStringNotContainsString('ADM-101', $page->html());
        $page->call('clearFilters')->set('search', 'apaar-77');
        $this->assertStringContainsString('ADM-101', $page->html());
        $this->assertStringNotContainsString('ADM-12<', $page->html());

        // View: the student's details, class and fee, the Aadhaar whole for the school.
        $view = $this->text($page->call('view', $aman)->html());
        foreach (['Personal Details', 'Aadhar Number 123456789012', 'Class Details', 'Section A', 'Total Fee ₹12,000', 'Paid ₹5,000', 'Balance ₹7,000', 'Board Exam Fee ₹2,000'] as $see) {
            $this->assertStringContainsString($see, $view, $see);
        }

        // A class outside 9–12 cannot be picked into the list.
        $page->call('closeView')->call('clearFilters')->set('filterStandard', '8');
        $this->assertStringContainsString('Aman Kumar', $page->html());
        $this->assertStringNotContainsString('Riya Singh', $page->html());
    }

    /**
     * User's ask of 2026-11-25 (later): More → Boards → Settings holds two fee
     * rules — the share of the fee paid before the website shows the board
     * roll number, and before it gives the admit card; each student's arrow
     * opens their details with the roll number and admit card to save; below
     * the rule the website asks the student to pay on the school's QR code.
     */
    public function test_the_roll_number_and_admit_card_show_on_the_website_only_past_the_fee_rules(): void
    {
        $aman = $this->students();   // fee 12,000, paid 5,000 → 41.6%
        $page = fn () => $this->text($this->login(['name' => 'Aman Kumar', 'father' => 'Ramesh', 'dob' => '2010-05-04'])->json('html'));

        // Nothing added and no rules yet: the card says so.
        $this->assertStringContainsString('Board Roll Number & Admit Card Fee paid 41.6% Board Roll Number The school has not added it yet.', $page());

        // Settings: the two rules, 0 to 100.
        $boards = Livewire::test(Boards::class)->call('setTab', 'settings')->assertSee(['Board Roll Number', 'Admit Card'])
            ->set('rollPercent', '120')->call('saveSettings')->assertHasErrors('rollPercent')
            ->set('rollPercent', '40')->set('admitPercent', '100')->call('saveSettings')->assertHasNoErrors();
        $this->assertSame(['roll' => 40.0, 'admit' => 100.0], BoardSetting::rules(1));
        $this->assertSame('40', Livewire::test(Boards::class)->get('rollPercent'));

        // A row's arrow: the student's details and the two fields, saved together.
        $boards->call('setTab', 'students')->call('view', $aman)
            ->assertSee(['Board Roll Number', 'Admit Card', 'Personal Details', 'Fee paid:'])
            ->set('boardRoll', ' CBSE-26-0042 ')
            ->set('admitCard', UploadedFile::fake()->create('aman-admit-card.pdf', 120, 'application/pdf'))
            ->call('saveBoard')->assertHasNoErrors();
        $rec = BoardStudent::sole();
        $this->assertSame(['CBSE-26-0042', 'aman-admit-card.pdf', 1], [$rec->roll_number, $rec->admit_card_name, (int) $rec->organization_id]);
        Storage::disk('s3')->assertExists($rec->admit_card_path);
        $this->assertStringStartsWith('boards/admit-cards/1/', $rec->admit_card_path);
        $list = $this->text($boards->call('closeView')->html());
        $this->assertStringContainsString('CBSE-26-0042 Admit card ✓', $list);
        $boards->call('view', $aman)->set('admitCard', UploadedFile::fake()->create('virus.exe', 10))->call('saveBoard')->assertHasErrors('admitCard');

        // 41.6% paid: the roll number (40%) shows, the admit card (100%) does not — pay on the QR.
        $html = $this->login(['name' => 'Aman Kumar', 'father' => 'Ramesh', 'dob' => '2010-05-04'])->json('html');
        $text = $this->text($html);
        $this->assertStringContainsString('Board Roll Number CBSE-26-0042', $text);
        $this->assertStringContainsString("Please pay your fee to get your admit card. Pay it to your school on the school's QR code below.", $text);
        $this->assertStringContainsString('Admit Card Shows when 100% of the fee is paid', $text);
        $this->assertStringNotContainsString($rec->admit_card_path, $html);
        $this->assertStringContainsString('href="#bd-pay"', $html);

        // A higher roll-number rule: neither shows, and the number is not in the page at all.
        BoardSetting::where('organization_id', 1)->update(['roll_fee_percent' => 50]);
        $html = $this->login(['name' => 'Aman Kumar', 'father' => 'Ramesh', 'dob' => '2010-05-04'])->json('html');
        $this->assertStringNotContainsString('CBSE-26-0042', $html);
        $this->assertStringContainsString('Please pay your fee to get your board roll number and admit card.', $this->text($html));
        $this->assertStringContainsString('Your roll number shows when 50% of the fee is paid and your admit card when 100% is paid; you have paid 41.6%.', $this->text($html));

        // The whole fee paid: both, with the admit card to download, and no message.
        DB::table('fee_payments')->insert(['organization_id' => 1, 'student_detail_id' => $aman, 'amount' => 7000, 'payment_mode' => 'upi', 'payment_date' => '2026-11-20']);
        $html = $this->login(['name' => 'Aman Kumar', 'father' => 'Ramesh', 'dob' => '2010-05-04'])->json('html');
        $this->assertStringContainsString('Board Roll Number CBSE-26-0042', $this->text($html));
        $this->assertStringContainsString('Download Admit Card', $html);
        $this->assertStringContainsString($rec->admit_card_path, $html);
        $this->assertStringNotContainsString('Please pay your fee', $html);

        // Removing the admit card deletes the file.
        $boards->call('view', $aman)->call('removeAdmitCard');
        Storage::disk('s3')->assertMissing($rec->admit_card_path);
        $this->assertNull($rec->fresh()->admit_card_path);

        // Not a Class 9–12 student: nothing opens.
        $riya = (int) DB::table('student_details')->where('full_name', 'Riya Singh')->value('id');
        $boards->call('closeView')->call('view', $riya)->assertSet('viewingId', null);
    }
}
