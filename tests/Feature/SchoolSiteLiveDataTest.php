<?php

namespace Tests\Feature;

use App\Http\Controllers\SchoolSiteController;
use App\Http\Controllers\WebsiteController;
use App\Livewire\Admin\Enqueries;
use App\Livewire\Admin\WebsiteData;
use App\Models\Organization;
use App\Models\SchoolWebsite;
use App\Models\SchoolWebsiteEnquiry;
use App\Models\User;
use App\Support\SchoolSiteData;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * User's ask of 2026-10-20: the website's Online Admission pages read the
 * fee structure, the rules and the classes with their sections and subjects
 * from the admin's own records, and the website's forms — the Contact menu's
 * four and Online Admission — arrive in the admin's Website Enquiries with
 * their type and every field filled in.
 */
class SchoolSiteLiveDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('l', 32))]);

        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('logo')->nullable();
            $t->string('mobile_number')->nullable(); $t->string('address')->nullable(); $t->string('education_board')->nullable();
            $t->timestamps();
        });
        Schema::create('school_infos', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->timestamps();
        });
        Schema::create('school_websites', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id')->unique(); $t->string('domain')->nullable()->unique();
            $t->string('template')->default('classic'); $t->json('theme')->nullable(); $t->json('pages')->nullable();
            $t->json('content')->nullable(); $t->boolean('status')->default(false); $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->integer('order')->default(0);
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('standard_id'); $t->string('name');
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('subjects', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('standard_subjects', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('standard_id'); $t->unsignedBigInteger('subject_id'); $t->timestamps();
        });
        Schema::create('section_subjects', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('section_id'); $t->unsignedBigInteger('subject_id');
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('fee_structures', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('standard_id');
            $t->unsignedBigInteger('section_id')->nullable(); $t->unsignedBigInteger('student_detail_id')->nullable();
            $t->string('fee_name'); $t->decimal('amount', 10, 2); $t->string('fee_type')->default('academic');
            $t->string('academic_year')->nullable(); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('rules_and_regulations', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->json('content')->nullable(); $t->timestamps();
        });
        Schema::create('school_website_enquiries', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('type', 20)->nullable(); $t->string('name');
            $t->string('email')->nullable(); $t->string('phone')->nullable(); $t->string('subject')->nullable();
            $t->text('message')->nullable(); $t->json('data')->nullable(); $t->timestamps();
        });
        foreach (['contact_admin_students' => 'student_query', 'contact_admin_teachers' => 'teacher_query'] as $table => $col) {
            Schema::create($table, function (Blueprint $t) use ($col) {
                $t->id(); $t->unsignedBigInteger('user_id')->nullable(); $t->unsignedBigInteger('organization_id');
                $t->string('topic')->nullable(); $t->text($col)->nullable(); $t->string('image')->nullable();
                $t->text('admin_text')->nullable(); $t->boolean('admin_reply')->default(false); $t->timestamps();
            });
        }

        DB::table('organizations')->insert([
            ['id' => 1, 'name' => 'Sunrise Public School', 'education_board' => 'CBSE', 'mobile_number' => '9457293157'],
            ['id' => 2, 'name' => 'Another School', 'education_board' => 'CBSE', 'mobile_number' => null],
        ]);

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 901;
        $admin->role = 'admin';
        $admin->organization_id = 1;
        $this->actingAs($admin);
    }

    /** Nursery and Class I (an old class switched off), their sections, subjects, fees and the rules. */
    private function records(): void
    {
        DB::table('standards')->insert([
            ['id' => 10, 'organization_id' => 1, 'name' => 'Class I', 'order' => 2, 'is_active' => true],
            ['id' => 11, 'organization_id' => 1, 'name' => 'Nursery', 'order' => 1, 'is_active' => true],
            ['id' => 12, 'organization_id' => 1, 'name' => 'Old Class', 'order' => 3, 'is_active' => false],
            ['id' => 13, 'organization_id' => 2, 'name' => 'Their Class', 'order' => 1, 'is_active' => true],
        ]);
        DB::table('sections')->insert([
            ['id' => 20, 'organization_id' => 1, 'standard_id' => 10, 'name' => 'A', 'is_active' => true],
            ['id' => 21, 'organization_id' => 1, 'standard_id' => 10, 'name' => 'B', 'is_active' => true],
            ['id' => 22, 'organization_id' => 1, 'standard_id' => 11, 'name' => 'Rose', 'is_active' => true],
            ['id' => 23, 'organization_id' => 1, 'standard_id' => 10, 'name' => 'Closed', 'is_active' => false],
        ]);
        DB::table('subjects')->insert([
            ['id' => 30, 'organization_id' => 1, 'name' => 'English', 'is_active' => true],
            ['id' => 31, 'organization_id' => 1, 'name' => 'Maths', 'is_active' => true],
            ['id' => 32, 'organization_id' => 1, 'name' => 'Hindi', 'is_active' => true],
            ['id' => 33, 'organization_id' => 1, 'name' => 'Latin', 'is_active' => false],
        ]);
        DB::table('standard_subjects')->insert([
            ['standard_id' => 10, 'subject_id' => 30], ['standard_id' => 11, 'subject_id' => 30], ['standard_id' => 10, 'subject_id' => 33],
        ]);
        DB::table('section_subjects')->insert([
            ['section_id' => 20, 'subject_id' => 31, 'standard_id' => 10], ['section_id' => 21, 'subject_id' => 32, 'standard_id' => 10],
            ['section_id' => 21, 'subject_id' => 30, 'standard_id' => 10],
        ]);
        DB::table('fee_structures')->insert([
            ['organization_id' => 1, 'standard_id' => 10, 'section_id' => null, 'fee_name' => 'Tuition Fee', 'amount' => 1200, 'fee_type' => 'academic', 'academic_year' => '2026-27', 'is_active' => true],
            ['organization_id' => 1, 'standard_id' => 10, 'section_id' => null, 'fee_name' => 'Admission Fee', 'amount' => 5000, 'fee_type' => 'academic', 'academic_year' => '2026-27', 'is_active' => true],
            ['organization_id' => 1, 'standard_id' => 10, 'section_id' => null, 'fee_name' => 'Old Tuition', 'amount' => 999, 'fee_type' => 'academic', 'academic_year' => '2025-26', 'is_active' => true],
            ['organization_id' => 1, 'standard_id' => 10, 'section_id' => null, 'fee_name' => 'Bus', 'amount' => 700, 'fee_type' => 'transport', 'academic_year' => '2026-27', 'is_active' => true],
            ['organization_id' => 1, 'standard_id' => 11, 'section_id' => 22, 'fee_name' => 'Tuition Fee', 'amount' => 900.5, 'fee_type' => 'academic', 'academic_year' => '2026-27', 'is_active' => true],
            ['organization_id' => 1, 'standard_id' => 11, 'section_id' => null, 'fee_name' => 'Switched off', 'amount' => 50, 'fee_type' => 'academic', 'academic_year' => '2026-27', 'is_active' => false],
        ]);
        DB::table('rules_and_regulations')->insert(['organization_id' => 1, 'content' => json_encode([
            'sections'        => [['head' => 'Attendance', 'desc' => "75% attendance is a must.\nLeave needs a note."], ['head' => '', 'desc' => '']],
            'additional_info' => [['key' => 'School timing', 'value' => '8 AM – 2 PM']],
            'files'           => [['title' => 'Handbook', 'file_path' => 'https://cdn.example.com/handbook.pdf']],
        ])]);
    }

    private function site(string $template = 'classic', array $attrs = []): SchoolWebsite
    {
        // Unpublished: going live tells the super admins, and there is no users table here.
        $site = SchoolWebsite::create(array_merge(['organization_id' => 1, 'template' => $template, 'status' => false], $attrs));
        $site->setRelation('organization', Organization::find(1)->setRelation('schoolInfo', null));

        return $site;
    }

    private function render(SchoolWebsite $site, string $path): string
    {
        $res = app(SchoolSiteController::class)->render($site, $path);
        $this->assertSame(200, $res->getStatusCode(), $path);

        return $res->getContent();
    }

    private function text(string $html): string
    {
        return html_entity_decode(preg_replace('/\s+/', ' ', strip_tags(preg_replace('#<(style|script)\b.*?</\1>#s', '', $html))));
    }

    public function test_the_schools_records_are_read_as_the_site_shows_them(): void
    {
        $this->records();
        $lms = new SchoolSiteData(1);

        // Classes in the Standard page's order, active sections, active subjects (the class's and its sections'), A to Z.
        $this->assertSame([
            ['name' => 'Nursery', 'sections' => ['Rose'], 'subjects' => ['English']],
            ['name' => 'Class I', 'sections' => ['A', 'B'], 'subjects' => ['English', 'Hindi', 'Maths']],
        ], $lms->classes());
        $this->assertSame(['Nursery', 'Class I'], $lms->classNames());

        // Fees: academic and active, the latest year a group has, in the classes' order.
        $this->assertSame([
            ['class' => 'Nursery', 'section' => 'Rose', 'year' => '2026-27', 'rows' => [['name' => 'Tuition Fee', 'amount' => 900.5]], 'total' => 900.5],
            ['class' => 'Class I', 'section' => null, 'year' => '2026-27', 'rows' => [['name' => 'Tuition Fee', 'amount' => 1200.0], ['name' => 'Admission Fee', 'amount' => 5000.0]], 'total' => 6200.0],
        ], $lms->fees());

        // Rules as saved, empty ones left out.
        $this->assertTrue($lms->hasRules());
        $this->assertSame([['head' => 'Attendance', 'desc' => "75% attendance is a must.\nLeave needs a note."]], $lms->rules()['sections']);
        $this->assertSame([['title' => 'Handbook', 'url' => 'https://cdn.example.com/handbook.pdf']], $lms->rules()['files']);

        // Another school's, and a school with nothing set up.
        $this->assertSame([['name' => 'Their Class', 'sections' => [], 'subjects' => []]], (new SchoolSiteData(2))->classes());
        $this->assertSame([], (new SchoolSiteData(2))->fees());
        $this->assertFalse((new SchoolSiteData(2))->hasRules());
        $this->assertSame('₹6,200', SchoolSiteData::money(6200));
        $this->assertSame('₹900.50', SchoolSiteData::money(900.5));
    }

    public function test_the_online_admission_pages_show_the_schools_records(): void
    {
        $this->records();
        $site = $this->site();

        // Admission: the online admission form alone (user's ask of 2026-10-20). Its
        // Class list is the school's classes; it arrives as an Online Admission.
        $admission = $this->render($site, 'admission');
        $text = $this->text($admission);
        $this->assertStringContainsString('Online Admission Form', $text);
        foreach (['₹', 'Attendance', 'English', 'Subjects'] as $not) {
            $this->assertStringNotContainsString($not, $text, $not);
        }
        $this->assertStringContainsString('<option value="Nursery">Nursery</option>', $admission);
        $this->assertStringContainsString('<option value="Class I">Class I</option>', $admission);
        $this->assertStringNotContainsString('<option value="Play Group">', $admission);
        $this->assertStringContainsString('data-type="admission"', $admission);

        // Classes: a table as Mark Attendance's — sections and subjects as plain text.
        $classes = $this->render($site, 'classes');
        $text = $this->text($classes);
        $this->assertStringContainsString('Classes, Sections & Subjects 2 classes · 3 sections S.No Class Sections Subjects', $text);
        $this->assertStringContainsString('1 Nursery Rose English 2 Class I A, B English, Hindi, Maths', $text);
        $this->assertStringNotContainsString('lms-chip', $classes);
        $this->assertStringNotContainsString('will be published here shortly', $text);
        foreach (['Old Class', 'Their Class', 'Latin', 'Closed'] as $not) {
            $this->assertStringNotContainsString($not, $text, $not);
        }

        // Fee Structure: a row a class, a column a fee, the total; a section column as one class's fees are a section's.
        $fees = $this->text($this->render($site, 'fee-structure'));
        $this->assertStringContainsString('Fee Structure Session 2026-27 S.No Class Section Tuition Fee Admission Fee Total', $fees);
        $this->assertStringContainsString('1 Nursery Rose ₹900.50 — ₹900.50 2 Class I All ₹1,200 ₹5,000 ₹6,200', $fees);
        foreach (['Old Tuition', 'Bus', 'Switched off', 'published here shortly'] as $not) {
            $this->assertStringNotContainsString($not, $fees, $not);
        }

        // Rules & Regulations: the rules, the additional information and the PDFs, a table each.
        $rules = $this->render($site, 'rules');
        $text = $this->text($rules);
        $this->assertStringContainsString('Rules & Regulations 1 rule S.No Rule Details 1 Attendance 75% attendance is a must. Leave needs a note.', $text);
        $this->assertStringContainsString('Additional Information School timing 8 AM – 2 PM', $text);
        $this->assertStringContainsString('Documents 1 document S.No Document 1 Handbook Download', $text);
        $this->assertStringContainsString('href="https://cdn.example.com/handbook.pdf"', $rules);
        $this->assertStringNotContainsString('This page is being updated', $text);
    }

    public function test_many_fees_read_as_a_line_and_the_columns_follow_the_data(): void
    {
        $this->records();
        foreach (['Lab', 'Library', 'Sports', 'Exam'] as $fee) {
            DB::table('fee_structures')->insert(['organization_id' => 1, 'standard_id' => 10, 'fee_name' => "{$fee} Fee", 'amount' => 100, 'academic_year' => '2026-27']);
        }
        DB::table('fee_structures')->where('section_id', 22)->update(['academic_year' => '2025-26']);
        $site = $this->site();

        // Six fees: the particulars as a line; two sessions: a session column.
        $fees = $this->text($this->render($site, 'fee-structure'));
        $this->assertStringContainsString('S.No Class Section Session Particulars Total', $fees);
        $this->assertStringContainsString('Class I All 2026-27 Tuition Fee ₹1,200 · Admission Fee ₹5,000 · Lab Fee ₹100', $fees);
        $this->assertStringNotContainsString('Session 2026-27 S.No', $fees);

        // A long section list wraps instead of stretching its column.
        DB::table('sections')->insert(['organization_id' => 1, 'standard_id' => 10, 'name' => 'Section Sunflower Morning']);
        $classes = $this->render($site, 'classes');
        $this->assertMatchesRegularExpression('#<th class="fit">Class</th>\s*<th class="">Sections</th>#', $classes);
    }

    public function test_a_school_with_nothing_set_up_keeps_what_website_data_holds(): void
    {
        $site = $this->site('classic', ['content' => [
            'classes' => [['title' => 'Pre-Primary']], 'admission_rules' => [['text' => 'Bring two photos']], 'fee_note' => 'Fees are payable quarterly.',
        ]]);

        $admission = $this->render($site, 'admission');
        $this->assertStringContainsString('<option value="Pre-Primary">', $admission);
        $classes = $this->text($this->render($site, 'classes'));
        $this->assertStringNotContainsString('Classes, Sections & Subjects', $classes);
        $this->assertStringContainsString('Pre-Primary', $classes);
        $this->assertStringContainsString('Rules & Regulations 1 rule 1 Bring two photos', $this->text($this->render($site, 'rules')));
        $fees = $this->text($this->render($site, 'fee-structure'));
        $this->assertStringContainsString('Fees are payable quarterly.', $fees);
        $this->assertStringNotContainsString('S.No', $fees);

        // Nothing at all: each page says so.
        SchoolWebsite::query()->delete();
        $empty = $this->site();
        $this->assertStringContainsString('Details of our classes will be published here shortly.', $this->text($this->render($empty, 'classes')));
        $this->assertStringContainsString('The fee structure will be published here shortly.', $this->text($this->render($empty, 'fee-structure')));
        $this->assertStringContainsString('Our rules & regulations will be published here shortly.', $this->text($this->render($empty, 'rules')));
    }

    public function test_website_data_shows_what_the_site_reads_live(): void
    {
        $this->records();
        $this->site();

        // Admission is the form; what is written there shows on Classic's home page.
        $admission = Livewire::test(WebsiteData::class)->call('openPage', 'admission')
            ->assertSee(['This page is the online admission form', 'On your home page, under the admission band', 'Introduction']);
        foreach (['Fee note', 'Admission rules', 'Open Standard'] as $not) {
            $this->assertStringNotContainsString($not, $this->text($admission->html()), $not);   // the page, not the chips' tooltips
        }

        // Each of the other pages shows what it reads from the school's records.
        Livewire::test(WebsiteData::class)->call('openPage', 'classes')
            ->assertSee(['Classes, sections & subjects', 'Live', 'Nursery', 'Rose', 'Open Standard', 'Class cards']);
        $fee = Livewire::test(WebsiteData::class)->call('openPage', 'fee-structure')
            ->assertSee(['Fee structure', 'Live', '₹6,200', 'Open Fee Structure', 'Fee note', 'Fee documents']);
        $this->assertStringContainsString('/1/fee-structure', $fee->html());
        Livewire::test(WebsiteData::class)->call('openPage', 'rules')
            ->assertSee(['Rules & Regulations', 'Attendance', 'Open Rules & Regulations', 'Admission rules']);

        // The Online Admission menu's pages count as filled from the school's records.
        $wd = Livewire::test(WebsiteData::class)->call('openGroup', 'Online Admission');
        $text = html_entity_decode(preg_replace('/\s+/', ' ', strip_tags($wd->html())));
        $this->assertStringContainsString('Fee Structure Filled Rules & Regulations Filled Classes Filled', $text);

        // Saving a page leaves the records alone; the fee note shows on the site.
        $fee->set('data.fee_note', 'Fees are payable quarterly.')->call('save');
        $content = SchoolWebsite::first()->content;
        $this->assertSame('Fees are payable quarterly.', $content['fee_note']);
        $this->assertArrayNotHasKey('live_fees', $content);
        $site = SchoolWebsite::first()->setRelation('organization', Organization::find(1)->setRelation('schoolInfo', null));
        $this->assertStringContainsString('Fee Structure Session 2026-27 Fees are payable quarterly.', $this->text($this->render($site, 'fee-structure')));
    }

    public function test_the_websites_forms_arrive_with_their_type_and_fields(): void
    {
        $site = $this->site();
        foreach (['contact' => 'contact', 'query' => 'query', 'appointment' => 'appointment', 'career' => 'career'] as $path => $type) {
            $this->assertStringContainsString('data-type="' . $type . '"', $this->render($site, $path), $path);
        }
        $send = fn (array $data) => app(WebsiteController::class)->schoolContact(new Request($data));
        $send([
            'organization_id' => 1, 'type' => 'appointment', 'name' => 'Ravi', 'phone' => '9876543210',
            'subject' => 'Appointment — 25 Oct 2026', 'message' => "Purpose: Campus visit\n\nAfter 11, please.",
            'fields' => [
                ['label' => 'Name', 'value' => 'Ravi'], ['label' => 'Mobile No.', 'value' => '9876543210'],
                ['label' => 'Preferred Date', 'value' => '25 Oct 2026'], ['label' => 'Purpose', 'value' => 'Campus visit'],
                ['label' => 'Email ID', 'value' => '  '], ['label' => 'Message', 'value' => 'After 11, please.'],
            ],
        ]);
        $send(['organization_id' => 1, 'type' => 'query', 'name' => 'Meena', 'message' => 'Is there a bus?']);
        $send(['organization_id' => 1, 'name' => 'Old form', 'subject' => 'Hello']);
        $send(['organization_id' => 1, 'type' => 'contact', 'name' => 'Bot', 'company' => 'Spam Inc']);   // the honeypot

        $appt = SchoolWebsiteEnquiry::where('type', 'appointment')->first();
        $this->assertSame('Appointment', $appt->typeLabel());
        $this->assertSame(['Name' => 'Ravi', 'Mobile No.' => '9876543210', 'Preferred Date' => '25 Oct 2026',
            'Purpose' => 'Campus visit', 'Message' => 'After 11, please.'], $appt->fields());
        $query = SchoolWebsiteEnquiry::where('type', 'query')->first();
        $this->assertSame('Query', $query->subject);   // no subject: the form's name
        $this->assertSame(['Name' => 'Meena', 'Subject' => 'Query', 'Message' => 'Is there a bus?'], $query->fields());
        $this->assertSame('Website', SchoolWebsiteEnquiry::whereNull('type')->first()->typeLabel());
        $this->assertSame(3, SchoolWebsiteEnquiry::count());

        try {
            $send(['organization_id' => 1, 'type' => 'spam', 'name' => 'X']);
            $this->fail('A type that is not a form is turned away.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('type', $e->errors());
        }

        // The admin's Website Enquiries: a chip a form with its count, a tag a row, and the enquiry's own fields.
        $inbox = Livewire::test(Enqueries::class)->call('showTab', 'website');
        $text = html_entity_decode(preg_replace('/\s+/', ' ', strip_tags($inbox->html())));
        $this->assertStringContainsString('All 3 Contact Us 0 Query 1 Appointment 1 Career 0 Online Admission 0', $text);
        $this->assertStringContainsString('Appointment Appointment — 25 Oct 2026', $text);

        $inbox->call('applyTypeFilter', 'appointment')->assertSet('typeFilter', 'appointment')
            ->assertSee('Appointment — 25 Oct 2026')->assertDontSee('Is there a bus?');
        $inbox->call('viewEnquiry', $appt->id)
            ->assertSee(['Appointment Enquiry', 'Preferred Date', '25 Oct 2026', 'Purpose', 'Campus visit', 'href="tel:9876543210"'], false);
        $inbox->call('applyTypeFilter', 'appointment')->assertSet('typeFilter', '');   // again: all of them
        $inbox->call('applyTypeFilter', 'query')->call('showTab', 'student')->assertSet('typeFilter', '');
    }
}
