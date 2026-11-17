<?php

namespace Tests\Feature;

use App\Livewire\Admin\Complaints;
use App\Livewire\Admin\Gatepass;
use App\Livewire\Admin\Reception;
use App\Models\Admin\ReceptionVisitor;
use App\Models\Admin\SchoolComplaint;
use App\Models\Admin\StudentGatePass;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Admin → More's front-desk screens:
 * - Complaints: a complaint about a student picked by class, section and
 *   name, with a title, a description and a file if wanted; listed, found,
 *   edited and deleted (with its file).
 * - Reception: the visitor book — who, mobile, email, whom to meet, purpose,
 *   the work, in and out times; marked out with a click.
 * - Gatepass: a student let out — why, when, who took them (the father
 *   filled in), numbered per school, printed as a slip, marked returned.
 * Only ever the admin's own school's students and rows.
 */
class AdminFrontDeskTest extends TestCase
{
    protected array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('f', 32))]);
        Storage::fake('s3');

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable(); $t->boolean('is_active')->default(true);
            $t->text('image')->nullable(); $t->timestamps();
        });
        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('mobile_number')->nullable();
            $t->text('address')->nullable(); $t->text('logo')->nullable(); $t->timestamps();
        });
        Schema::create('school_infos', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('website_url')->nullable(); $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name');
            $t->integer('order')->default(0); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('standard_id'); $t->unsignedBigInteger('organization_id')->nullable();
            $t->string('name'); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id')->nullable(); $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id'); $t->unsignedBigInteger('section_id');
            $t->string('full_name')->nullable(); $t->string('father_name')->nullable(); $t->string('phone')->nullable();
            $t->string('admission_no')->nullable(); $t->string('roll_no')->nullable(); $t->string('image')->nullable();
            $t->date('dob')->nullable(); $t->timestamps();
        });
        (require base_path('database/migrations/2026_11_17_120000_create_front_desk_tables.php'))->up();

        DB::table('organizations')->insert([
            ['id' => 6, 'name' => 'Sunrise Public School', 'mobile_number' => '9876543210', 'address' => 'Station Road, Kota'],
            ['id' => 7, 'name' => 'Other School', 'mobile_number' => null, 'address' => null],
        ]);
        DB::table('users')->insert(['id' => 1, 'name' => 'Admin', 'role' => 'admin', 'organization_id' => 6]);

        $five  = DB::table('standards')->insertGetId(['organization_id' => 6, 'name' => '5', 'order' => 1]);
        $a     = DB::table('sections')->insertGetId(['standard_id' => $five, 'organization_id' => 6, 'name' => 'A']);
        $b     = DB::table('sections')->insertGetId(['standard_id' => $five, 'organization_id' => 6, 'name' => 'B']);
        $other = DB::table('standards')->insertGetId(['organization_id' => 7, 'name' => '5', 'order' => 1]);
        $oSec  = DB::table('sections')->insertGetId(['standard_id' => $other, 'organization_id' => 7, 'name' => 'A']);

        $kid = fn (array $row) => DB::table('student_details')->insertGetId($row + ['organization_id' => 6, 'standard_id' => $five]);
        $this->ids = compact('five', 'a', 'b', 'other', 'oSec') + [
            'aarav'  => $kid(['section_id' => $a, 'full_name' => 'Aarav', 'father_name' => 'Ravi Kumar', 'phone' => '9876500001', 'admission_no' => 'ADM-1', 'roll_no' => '1']),
            'bhavya' => $kid(['section_id' => $a, 'full_name' => 'Bhavya', 'admission_no' => 'ADM-2']),
            'chetan' => $kid(['section_id' => $b, 'full_name' => 'Chetan', 'admission_no' => 'ADM-3']),
            'stranger' => DB::table('student_details')->insertGetId(['organization_id' => 7, 'standard_id' => $other, 'section_id' => $oSec, 'full_name' => 'Stranger']),
        ];

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 1;
        $admin->organization_id = 6;
        $admin->role = 'admin';
        $this->actingAs($admin);
    }

    /** The pickers set on a student of class 5. */
    protected function pick($page, string $section, string $student)
    {
        return $page->set('standardId', (string) $this->ids['five'])
            ->set('sectionId', (string) $this->ids[$section])
            ->set('studentId', (string) $this->ids[$student]);
    }

    // ─── Complaints ─────────────────────────────────────────────────────────

    public function test_a_complaint_is_added_for_a_picked_student_with_a_file_and_listed(): void
    {
        $page = Livewire::test(Complaints::class)->call('openAdd');

        // The pickers: this school's class, its sections, that section's students A to Z.
        $page->set('standardId', (string) $this->ids['five'])
            ->assertSee('Select section')
            ->set('sectionId', (string) $this->ids['a']);
        $html = $page->html();
        $this->assertStringContainsString('Aarav · ADM-1 · Roll 1', $html);
        $this->assertStringContainsString('Bhavya', $html);
        $this->assertStringNotContainsString('Chetan · ADM-3', $html);
        $this->assertStringNotContainsString('Stranger', $html);

        $page->set('studentId', (string) $this->ids['aarav'])
            ->set('title', 'Misbehaved in class')
            ->set('description', 'Pushed a classmate during the lunch break.')
            ->set('attachment', UploadedFile::fake()->create('teacher-note.pdf', 120, 'application/pdf'))
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('formOpen', false)
            ->assertSee('Misbehaved in class')
            ->assertSee('Aarav');

        $c = SchoolComplaint::sole();
        $this->assertSame([6, $this->ids['aarav'], $this->ids['five'], $this->ids['a'], 1],
            [(int) $c->organization_id, (int) $c->student_detail_id, (int) $c->standard_id, (int) $c->section_id, (int) $c->added_by]);
        $this->assertSame('teacher-note.pdf', $c->file_name);
        $this->assertStringStartsWith('admin/complaints/6/', $c->file_path);
        Storage::disk('s3')->assertExists($c->file_path);

        // View shows the whole complaint and its file.
        $page->call('view', $c->id)
            ->assertSee('Pushed a classmate during the lunch break.')
            ->assertSee('teacher-note.pdf');
    }

    public function test_a_complaint_needs_its_fields_and_a_student_of_the_school_and_section(): void
    {
        $page = Livewire::test(Complaints::class)->call('openAdd')->call('save')
            ->assertHasErrors(['standardId', 'sectionId', 'studentId', 'title', 'description']);

        // Another section's student, or another school's, is refused.
        $this->pick($page, 'a', 'chetan')->set('title', 'T')->set('description', 'D')->call('save')->assertHasErrors('studentId');
        $page->set('studentId', (string) $this->ids['stranger'])->call('save')->assertHasErrors('studentId');

        // A file that is not a photo, PDF or Word file.
        $page->set('studentId', (string) $this->ids['aarav'])
            ->set('attachment', UploadedFile::fake()->create('virus.exe', 10))
            ->call('save')->assertHasErrors('attachment');

        // Changing the class clears the section and the student.
        $page->set('standardId', '')->assertSet('sectionId', '')->assertSet('studentId', '');
        $this->assertSame(0, SchoolComplaint::count());
    }

    public function test_a_complaint_is_edited_its_file_replaced_or_removed_and_deleted(): void
    {
        $page = $this->pick(Livewire::test(Complaints::class)->call('openAdd'), 'a', 'aarav')
            ->set('title', 'Late')->set('description', 'Came late')
            ->set('attachment', UploadedFile::fake()->image('first.jpg'))
            ->call('save')->assertHasNoErrors();
        $c = SchoolComplaint::sole();
        $first = $c->file_path;

        // Edit: the form carries the complaint; a new file replaces the old one.
        $page->call('openEdit', $c->id)
            ->assertSet('title', 'Late')->assertSet('studentId', (string) $this->ids['aarav'])
            ->set('title', 'Late again')
            ->set('attachment', UploadedFile::fake()->image('second.png'))
            ->call('save')->assertHasNoErrors();
        $c->refresh();
        $this->assertSame('Late again', $c->title);
        $this->assertSame('second.png', $c->file_name);
        Storage::disk('s3')->assertMissing($first);
        Storage::disk('s3')->assertExists($c->file_path);

        // Remove the file.
        $second = $c->file_path;
        $page->call('openEdit', $c->id)->call('dropAttachment')->call('save')->assertHasNoErrors();
        $this->assertNull($c->fresh()->file_path);
        Storage::disk('s3')->assertMissing($second);

        $page->call('confirmDelete', $c->id)->call('deleteComplaint');
        $this->assertSame(0, SchoolComplaint::count());
    }

    public function test_the_complaints_list_filters_and_never_shows_another_school(): void
    {
        foreach ([['aarav', 'a', 'Broke a window'], ['chetan', 'b', 'Skipped homework']] as [$st, $sec, $title]) {
            SchoolComplaint::create(['organization_id' => 6, 'student_detail_id' => $this->ids[$st], 'standard_id' => $this->ids['five'],
                'section_id' => $this->ids[$sec], 'title' => $title, 'description' => 'x']);
        }
        SchoolComplaint::create(['organization_id' => 7, 'student_detail_id' => $this->ids['stranger'], 'standard_id' => $this->ids['other'],
            'section_id' => $this->ids['oSec'], 'title' => 'Elsewhere complaint', 'description' => 'x']);

        $page = Livewire::test(Complaints::class)
            ->assertSee('Broke a window')->assertSee('Skipped homework')->assertDontSee('Elsewhere complaint');
        $page->set('filterStandard', (string) $this->ids['five'])->set('filterSection', (string) $this->ids['b'])
            ->assertSee('Skipped homework')->assertDontSee('Broke a window');
        $page->call('clearFilters')->set('search', 'Aarav')->assertSee('Broke a window')->assertDontSee('Skipped homework');

        // Another school's complaint cannot be opened or deleted from here.
        $theirs = SchoolComplaint::where('organization_id', 7)->value('id');
        $page->call('openEdit', $theirs)->assertSet('formOpen', false)
            ->call('confirmDelete', $theirs)->call('deleteComplaint');
        $this->assertSame(1, SchoolComplaint::where('organization_id', 7)->count());
    }

    // ─── Reception ──────────────────────────────────────────────────────────

    public function test_a_visitor_is_written_in_and_marked_out(): void
    {
        $page = Livewire::test(Reception::class)->call('openAdd')
            ->assertSet('inDate', now()->toDateString());

        $page->set('name', 'Suresh Verma')->set('mobile', '98765 43210')->set('email', 'suresh@example.com')
            ->set('whomToMeet', 'Principal')->set('purpose', 'Admission Enquiry')->set('details', 'Admission of his son in Class 6')
            ->set('inDate', now()->toDateString())->set('inTime', '00:05')
            ->call('save')->assertHasNoErrors()
            ->assertSee('Suresh Verma')->assertSee('Admission Enquiry')->assertSee('Inside · Mark Out');

        $v = ReceptionVisitor::sole();
        $this->assertSame(['98765 43210', 'suresh@example.com', 'Principal', 'Admission of his son in Class 6', 1],
            [$v->mobile, $v->email, $v->whom_to_meet, $v->details, (int) $v->added_by]);
        $this->assertSame(now()->toDateString() . ' 00:05', $v->in_at->format('Y-m-d H:i'));
        $this->assertNull($v->out_at);

        $page->call('markOut', $v->id)->assertDontSee('Inside · Mark Out');
        $this->assertNotNull($v->fresh()->out_at);
        $this->assertTrue($v->fresh()->out_at->gte($v->in_at));
    }

    public function test_a_visitor_needs_a_name_mobile_purpose_and_sane_times(): void
    {
        $page = Livewire::test(Reception::class)->call('openAdd')->set('inTime', '')
            ->call('save')->assertHasErrors(['name', 'mobile', 'purpose', 'inTime']);

        $page->set('name', 'X')->set('mobile', 'abc')->set('email', 'not-an-email')->set('purpose', 'Other')->set('inTime', '10:00')
            ->call('save')->assertHasErrors(['mobile', 'email', 'details']);

        $page->set('mobile', '9876543210')->set('email', '')->set('details', 'Courier')->set('outTime', '09:30')
            ->call('save')->assertHasErrors('outTime');

        $page->set('outTime', '11:25')->call('save')->assertHasNoErrors();
        $this->assertSame('1 h 25 m', ReceptionVisitor::sole()->stayed());
    }

    public function test_the_visitor_book_filters_and_keeps_to_the_school(): void
    {
        $add = fn (int $org, string $name, string $mobile, ?string $out, string $day = 'today') => ReceptionVisitor::create([
            'organization_id' => $org, 'name' => $name, 'mobile' => $mobile, 'purpose' => 'Fee Payment',
            'in_at' => ($day === 'today' ? now()->startOfDay() : now()->subDays(3)->startOfDay())->addHours(9),
            'out_at' => $out ? ($day === 'today' ? now()->startOfDay() : now()->subDays(3)->startOfDay())->setTimeFromTimeString($out) : null,
        ]);
        $add(6, 'Inside Person', '9000000001', null);
        $add(6, 'Gone Person', '9000000002', '10:00');
        $add(6, 'Old Visitor', '9000000003', '10:00', 'past');
        $add(7, 'Their Visitor', '9000000004', null);

        $page = Livewire::test(Reception::class)
            ->assertSee('Inside Person')->assertSee('Old Visitor')->assertDontSee('Their Visitor');
        $page->set('filterStatus', 'inside')->assertSee('Inside Person')->assertDontSee('Gone Person');
        $page->set('filterStatus', 'left')->assertSee('Gone Person')->assertDontSee('Inside Person');
        $page->call('clearFilters')->call('showToday')->assertSee('Gone Person')->assertDontSee('Old Visitor');
        $page->call('clearFilters')->set('search', '9000000003')->assertSee('Old Visitor')->assertDontSee('Gone Person');

        $theirs = ReceptionVisitor::where('organization_id', 7)->value('id');
        $page->call('markOut', $theirs)->call('confirmDelete', $theirs)->call('deleteVisitor');
        $this->assertNull(ReceptionVisitor::find($theirs)->out_at);

        $mine = ReceptionVisitor::where('name', 'Gone Person')->value('id');
        $page->call('confirmDelete', $mine)->call('deleteVisitor');
        $this->assertNull(ReceptionVisitor::find($mine));
    }

    // ─── Gatepass ───────────────────────────────────────────────────────────

    public function test_a_gate_pass_is_made_numbered_and_the_father_filled_in(): void
    {
        $page = $this->pick(Livewire::test(Gatepass::class)->call('openAdd'), 'a', 'aarav')
            ->assertSet('pickedByName', 'Ravi Kumar')
            ->assertSet('pickedByRelation', 'Father')
            ->assertSet('pickedByMobile', '9876500001');

        $page->set('reason', 'Not feeling well')->set('outTime', '11:30')
            ->call('save')->assertHasNoErrors()
            ->assertSee('GP-0001')->assertSee('Not feeling well')->assertSee('Mark Returned');

        $p = StudentGatePass::sole();
        $this->assertSame([1, 'GP-0001', 6, $this->ids['five'], $this->ids['a'], 'Ravi Kumar', 'Father'],
            [(int) $p->pass_number, $p->number(), (int) $p->organization_id, (int) $p->standard_id, (int) $p->section_id, $p->picked_by_name, $p->picked_by_relation]);
        $this->assertSame('11:30', $p->out_at->format('H:i'));

        // Another school's numbers are its own; ours go on from our last.
        StudentGatePass::create(['organization_id' => 7, 'pass_number' => 40, 'student_detail_id' => $this->ids['stranger'],
            'reason' => 'x', 'out_at' => now(), 'picked_by_name' => 'x']);
        $this->pick($page->call('openAdd'), 'b', 'chetan')
            ->assertSet('pickedByName', '')   // no father on record
            ->set('reason', 'Doctor')->set('pickedByName', 'Uncle Ji')->set('pickedByRelation', 'Uncle')
            ->call('save')->assertHasNoErrors();
        $this->assertSame(2, (int) StudentGatePass::where('organization_id', 6)->max('pass_number'));

        // Found by its number.
        $page->set('search', 'GP-2')->assertSee('Uncle Ji')->assertDontSee('Ravi Kumar');
    }

    public function test_a_gate_pass_needs_why_and_who_and_comes_back_after_it_went(): void
    {
        $page = Livewire::test(Gatepass::class)->call('openAdd')->call('save')
            ->assertHasErrors(['studentId', 'reason', 'pickedByName']);

        $this->pick($page, 'a', 'bhavya')->set('reason', 'Function')->set('pickedByName', 'Mother')
            ->set('outTime', '12:00')->set('returnTime', '11:00')->set('pickedByMobile', 'xyz')
            ->call('save')->assertHasErrors(['pickedByMobile']);
        $page->set('pickedByMobile', '')->call('save')->assertHasErrors('returnTime');

        $page->set('returnTime', '')->call('save')->assertHasNoErrors();
        $p = StudentGatePass::sole();
        $page->call('markReturned', $p->id);
        $this->assertNotNull($p->fresh()->returned_at);
    }

    public function test_the_slip_prints_the_pass_and_only_the_schools_own(): void
    {
        $this->pick(Livewire::test(Gatepass::class)->call('openAdd'), 'a', 'aarav')
            ->set('reason', 'Family function')->set('outTime', '10:45')->call('save')->assertHasNoErrors();
        $p = StudentGatePass::with(\App\Support\GatePassSlip::WITH)->sole();

        $html = view('pdf.gate-pass', ['pass' => $p, 'org' => $p->organization, 'logo' => null, 'contact' => 'Ph: 9876543210', 'fontCss' => ''])->render();
        foreach (['Sunrise Public School', 'GATE PASS', 'GP-0001', 'Aarav', '5 - A', 'ADM-1', 'Family function', '10:45 AM', 'Ravi Kumar (Father)', '9876500001', 'Admin'] as $text) {
            $this->assertStringContainsString(e($text), $html, $text);
        }

        $this->withoutMiddleware();
        $res = $this->get(route('admin.gatepass.pdf', ['organization' => 6, 'id' => $p->id]));
        $res->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('content-type'));
        $this->assertMatchesRegularExpression('/^inline; filename="?gate_pass_GP-0001\.pdf"?$/', (string) $res->headers->get('content-disposition'));

        $theirs = StudentGatePass::create(['organization_id' => 7, 'pass_number' => 1, 'student_detail_id' => $this->ids['stranger'],
            'reason' => 'x', 'out_at' => now(), 'picked_by_name' => 'x']);
        $this->get(route('admin.gatepass.pdf', ['organization' => 6, 'id' => $theirs->id]))->assertNotFound();
    }
}
