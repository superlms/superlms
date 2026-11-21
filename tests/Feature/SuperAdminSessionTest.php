<?php

namespace Tests\Feature;

use App\Livewire\Components\NavBar;
use App\Livewire\SuperAdmin\QuickLinks;
use App\Livewire\SuperAdmin\Session as SessionPage;
use App\Models\AcademicSession;
use App\Models\User;
use App\Support\SessionChangeOtp;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Super Admin → Session: academic sessions (2026–27 …) added, renamed by
 * their dates, never overlapping, one of them current — the one every top
 * bar shows. Making one current takes two different codes, one sent to the
 * Super Admin's email and one on WhatsApp, entered on one screen. Each
 * session's › lists its schools, and each school's › its data in it. It
 * sits right after Documents in the menu and on Quick Links.
 */
class SuperAdminSessionTest extends TestCase
{
    private int $year;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:' . base64_encode(str_repeat('k', 32))]);
        $this->year = (int) now()->year;

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable();
            $t->string('mobile_number')->nullable(); $t->string('password')->nullable(); $t->text('permissions')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable(); $t->unsignedBigInteger('allowed_organization_id')->nullable();
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name')->nullable(); $t->string('logo')->nullable(); $t->boolean('status')->default(true);
            foreach (['email', 'mobile_number', 'state', 'education_board', 'medium', 'school_code', 'affiliation_no'] as $c) {
                $t->string($c)->nullable();
            }
            $t->timestamps();
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
    }

    private function migrate(): void
    {
        (require base_path('database/migrations/2026_11_03_100000_create_academic_sessions_table.php'))->up();
    }

    private function label(int $from): string
    {
        return $from . '–' . substr((string) ($from + 1), -2);
    }

    private function mainSuperAdmin(): User
    {
        return User::forceCreate([
            'name' => 'Main', 'email' => 'superlms.india@gmail.com', 'role' => 'super-admin', 'mobile_number' => '9876505914',
        ]);
    }

    /**
     * Mail and WhatsApp both "deliver" (faked): the codes are read off the
     * requests — the email's from ZeptoMail's merge_info, the WhatsApp's from
     * the template's body.
     */
    private function fakeDelivery(): void
    {
        config([
            'services.zeptomail.api_token'        => 'test-token',
            'services.zeptomail.api_url'          => 'https://zepto.test/v1.1',
            'services.zeptomail.otp_template_key' => 'otp-template',
            'services.whatsapp.enabled'           => true,
            'services.whatsapp.token'             => 'wa-token',
            'services.whatsapp.phone_number_id'   => '123',
            'mail.default'                        => 'array',
        ]);
        Http::fake(['*' => Http::response(['request_id' => 'r1', 'messages' => [['id' => 'wamid.1']]], 200)]);
    }

    /** The last code sent by email and on WhatsApp. */
    private function codes(): array
    {
        $email = $whatsapp = null;
        foreach (Http::recorded() as [$request]) {
            /** @var Request $request */
            if (str_contains($request->url(), 'zepto.test')) {
                $email = $request['merge_info']['OTP'] ?? $email;
            } elseif (str_contains($request->url(), 'graph.facebook.com')) {
                $whatsapp = $request['template']['components'][0]['parameters'][0]['text'] ?? $whatsapp;
            }
        }

        return [$email, $whatsapp];
    }

    private function wrong(string $code): string
    {
        return str_pad((string) ((((int) $code) + 1) % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private function nextSession(): AcademicSession
    {
        return AcademicSession::create(['name' => $this->label($this->year + 1), 'starts_on' => ($this->year + 1) . '-04-01', 'ends_on' => ($this->year + 2) . '-03-31']);
    }

    // ─── Sessions ────────────────────────────────────────────────────────────

    public function test_without_sessions_the_top_bar_keeps_this_years_label(): void
    {
        // Before the migration has run, nothing breaks.
        $this->assertSame($this->label($this->year), AcademicSession::currentLabel());
    }

    public function test_the_migration_starts_with_the_session_the_top_bar_showed(): void
    {
        $this->migrate();
        $this->migrate(); // run twice: still one

        $session = AcademicSession::sole();
        $this->assertSame($this->label($this->year), $session->name);
        $this->assertTrue($session->is_current);
        $this->assertSame("{$this->year}-04-01", $session->starts_on->toDateString());
        $this->assertSame(($this->year + 1) . '-03-31', $session->ends_on->toDateString());
        $this->assertSame(12, $session->months());
        $this->assertSame($this->label($this->year), AcademicSession::currentLabel());
    }

    public function test_a_new_session_follows_the_latest_and_is_named_by_its_dates(): void
    {
        $this->migrate();
        $next = $this->year + 1;

        Livewire::test(SessionPage::class)
            ->assertSee('Current:')
            ->assertSee($this->label($this->year))
            ->call('openCreate')
            ->assertSet('startsOn', "{$next}-04-01")
            ->assertSet('endsOn', ($next + 1) . '-03-31')
            ->assertSet('name', $this->label($next))
            ->assertSet('makeCurrent', false)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showPanel', false)
            ->assertSet('switchId', null)
            ->assertSee($this->label($next));

        $this->assertSame(2, AcademicSession::count());
        // Adding a session doesn't move the current one.
        $this->assertSame($this->label($this->year), AcademicSession::currentLabel());
        $this->assertSame('upcoming', AcademicSession::where('name', $this->label($next))->sole()->status());
    }

    public function test_the_dates_rename_it_until_a_name_is_typed(): void
    {
        $this->migrate();

        $page = Livewire::test(SessionPage::class)->call('openCreate')
            ->set('startsOn', '2030-06-01')
            ->assertSet('endsOn', '2031-05-31')
            ->assertSet('name', '2030–31')
            ->set('endsOn', '2030-12-31')
            ->assertSet('name', '2030');

        $page->set('name', 'Batch A')
            ->set('endsOn', '2031-04-30')
            ->assertSet('name', 'Batch A')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(AcademicSession::where('name', 'Batch A')->exists());
    }

    public function test_overlapping_backwards_too_long_or_duplicate_sessions_are_refused(): void
    {
        $this->migrate();

        $page = Livewire::test(SessionPage::class)->call('openCreate');

        $page->set('startsOn', "{$this->year}-10-01")->set('endsOn', ($this->year + 1) . '-09-30')->call('save')
            ->assertHasErrors('startsOn')
            ->assertSee('These dates overlap ' . $this->label($this->year));

        $page->set('startsOn', '2035-04-01')->set('endsOn', '2035-03-01')->call('save')
            ->assertHasErrors(['endsOn' => 'after']);

        $page->set('startsOn', '2035-04-01')->set('endsOn', '2037-04-01')->call('save')
            ->assertSee('A session can be at most 2 years long.');

        $page->set('startsOn', '2035-04-01')->set('endsOn', '2036-03-31')->set('name', $this->label($this->year))->call('save')
            ->assertHasErrors(['name' => 'unique']);

        $this->assertSame(1, AcademicSession::count());
    }

    public function test_the_current_session_cannot_be_deleted_others_can(): void
    {
        $this->migrate();
        $current = AcademicSession::sole();
        $old = AcademicSession::create(['name' => $this->label($this->year - 1), 'starts_on' => ($this->year - 1) . '-04-01', 'ends_on' => "{$this->year}-03-31"]);

        Livewire::test(SessionPage::class)
            ->call('confirmDelete', $current->id)->assertSet('deleteId', null)
            ->call('confirmDelete', $old->id)->assertSet('deleteId', $old->id)
            ->call('delete')->assertSet('deleteId', null);

        $this->assertSame([$current->id], AcademicSession::pluck('id')->all());
        $this->assertSame('completed', $old->status());
    }

    // ─── Making a session current: two codes ────────────────────────────────

    public function test_making_a_session_current_takes_the_email_code_and_the_mobile_code_each_verified_on_its_own(): void
    {
        $this->migrate();
        $this->fakeDelivery();
        $first = AcademicSession::sole();
        $next  = $this->nextSession();
        $this->actingAs($this->mainSuperAdmin());

        $page = Livewire::test(SessionPage::class)
            ->call('askMakeCurrent', $next->id)
            ->assertHasNoErrors()
            ->assertSet('switchId', $next->id)
            ->assertSee('Make ' . $next->name . ' the current session')
            ->assertSee('Email code')
            ->assertSee('Mobile code')
            ->assertSee('su•••') // the email, masked
            ->assertSee('+91 ••••••5914')
            ->assertSee('0 of 2 verified');

        [$email, $whatsapp] = $this->codes();
        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $email);
        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $whatsapp);
        $this->assertNotSame($email, $whatsapp, 'Two different codes.');

        // Asking alone changes nothing.
        $this->assertTrue($first->fresh()->is_current);

        // The mobile's code is not the email's: refused.
        $page->set('emailCode', $whatsapp)->call('verifyCode', 'email')
            ->assertHasErrors('emailCode')->assertSet('verified', ['email' => false, 'whatsapp' => false]);

        // The email's code, verified on its own: the session is NOT current yet.
        $page->set('emailCode', $email)->call('verifyCode', 'email')
            ->assertHasNoErrors()
            ->assertSet('verified', ['email' => true, 'whatsapp' => false])
            ->assertSet('switchId', $next->id)
            ->assertSee('1 of 2 verified')
            ->assertNotDispatched('academic-session-changed');
        $this->assertTrue($first->fresh()->is_current);
        $this->assertFalse(SessionChangeOtp::complete(User::first(), $page->get('switchToken'), $next->id), 'One code is not enough.');

        // The email's code again for the mobile: refused.
        $page->set('whatsappCode', $email)->call('verifyCode', 'whatsapp')->assertHasErrors('whatsappCode');
        $this->assertTrue($first->fresh()->is_current);

        // The mobile's code, verified on its own: now both are, and the session is current.
        $page->set('whatsappCode', $whatsapp)->call('verifyCode', 'whatsapp')
            ->assertHasNoErrors()
            ->assertSet('switchId', null)
            ->assertDispatched('academic-session-changed');

        $this->assertSame([$next->id], AcademicSession::where('is_current', true)->pluck('id')->all());
        $this->assertSame($next->name, AcademicSession::currentLabel());

        // The codes are used up.
        $this->assertSame(
            ['request' => 'These codes have expired. Close this and click Make current again.'],
            SessionChangeOtp::verifyChannel(User::first(), $page->get('switchToken'), $next->id, 'email', $email),
        );
    }

    public function test_the_mobile_code_can_come_first_too(): void
    {
        $this->migrate();
        $this->fakeDelivery();
        $next = $this->nextSession();
        $this->actingAs($this->mainSuperAdmin());

        $page = Livewire::test(SessionPage::class)->call('askMakeCurrent', $next->id);
        [$email, $whatsapp] = $this->codes();

        $page->set('whatsappCode', $whatsapp)->call('verifyCode', 'whatsapp')
            ->assertSet('verified', ['email' => false, 'whatsapp' => true])
            ->call('resendCode', 'whatsapp')                 // a verified code is not sent again
            ->assertSet('verified', ['email' => false, 'whatsapp' => true]);
        $this->assertFalse($next->fresh()->is_current);

        $page->set('emailCode', $email)->call('verifyCode', 'email')->assertSet('switchId', null);
        $this->assertTrue($next->fresh()->is_current);
    }

    public function test_too_many_wrong_codes_end_the_request(): void
    {
        $this->migrate();
        $this->fakeDelivery();
        $first = AcademicSession::sole();
        $next  = $this->nextSession();
        $this->actingAs($this->mainSuperAdmin());

        $page = Livewire::test(SessionPage::class)->call('askMakeCurrent', $next->id);
        [$email, $whatsapp] = $this->codes();

        $page->set('emailCode', $email)->call('verifyCode', 'email')->assertHasNoErrors();
        for ($i = 1; $i < SessionChangeOtp::MAX_WRONG; $i++) {
            $page->set('whatsappCode', $this->wrong($whatsapp))->call('verifyCode', 'whatsapp')
                ->assertHasErrors('whatsappCode');
        }
        $page->set('whatsappCode', $this->wrong($whatsapp))->call('verifyCode', 'whatsapp')
            ->assertHasErrors('request')
            ->assertSee('Too many wrong codes');

        // Even the right code no longer works.
        $page->set('whatsappCode', $whatsapp)->call('verifyCode', 'whatsapp')
            ->assertHasErrors('request');
        $this->assertTrue($first->fresh()->is_current);
    }

    public function test_the_codes_go_to_the_main_super_admin_even_when_a_sub_super_admin_asks(): void
    {
        $this->migrate();
        $this->fakeDelivery();
        $next = $this->nextSession();
        $this->mainSuperAdmin();
        $this->actingAs(User::forceCreate([
            'name' => 'Sub', 'email' => 'sub@example.com', 'role' => 'sub-super-admin', 'mobile_number' => '9000000001',
            'permissions' => ['super-admin.sessions'],
        ]));

        Livewire::test(SessionPage::class)->call('askMakeCurrent', $next->id)
            ->assertSet('switchId', $next->id)
            ->assertSee('+91 ••••••5914');

        $to = collect(Http::recorded())->map(fn ($pair) => $pair[0])->map(fn (Request $r) => str_contains($r->url(), 'zepto')
            ? $r['to'][0]['email_address']['address'] ?? null
            : $r['to'] ?? null)->filter()->values()->all();

        $this->assertContains('superlms.india@gmail.com', $to);
        $this->assertContains('919876505914', $to);
        $this->assertNotContains('sub@example.com', $to);
    }

    public function test_without_a_whatsapp_number_no_codes_go_and_nothing_changes(): void
    {
        $this->migrate();
        $this->fakeDelivery();
        $next = $this->nextSession();
        $this->actingAs(User::forceCreate(['name' => 'Main', 'email' => 'superlms.india@gmail.com', 'role' => 'super-admin']));

        Livewire::test(SessionPage::class)->call('askMakeCurrent', $next->id)
            ->assertSet('switchId', null);

        Http::assertNothingSent();
        $this->assertFalse($next->fresh()->is_current);
    }

    public function test_a_code_that_could_not_be_sent_says_why_and_can_be_resent(): void
    {
        $this->migrate();
        $this->fakeDelivery();
        config(['services.whatsapp.enabled' => false]);
        $next = $this->nextSession();
        $this->actingAs($this->mainSuperAdmin());

        Livewire::test(SessionPage::class)->call('askMakeCurrent', $next->id)
            ->assertSet('switchId', $next->id)
            ->assertHasErrors('whatsappCode')
            ->assertSee('WhatsApp is not set up on the server')
            // Too soon to send it again: the page counts down instead.
            ->call('resendCode', 'whatsapp')
            ->assertHasNoErrors('whatsappCode');
    }

    public function test_saving_a_session_marked_current_asks_for_the_codes(): void
    {
        $this->migrate();
        $this->fakeDelivery();
        DB::table('academic_sessions')->delete();
        $this->actingAs($this->mainSuperAdmin());

        // With no current session, a new one is offered as current — still behind the codes.
        $page = Livewire::test(SessionPage::class)->assertSee('No sessions yet')
            ->call('openCreate')->assertSet('makeCurrent', true)
            ->call('save')
            ->assertSet('showPanel', false);

        $session = AcademicSession::sole();
        $page->assertSet('switchId', $session->id);
        $this->assertFalse($session->is_current);

        [$email, $whatsapp] = $this->codes();
        $page->set('emailCode', $email)->call('verifyCode', 'email')->assertHasNoErrors();
        $this->assertFalse($session->fresh()->is_current);
        $page->set('whatsappCode', $whatsapp)->call('verifyCode', 'whatsapp')->assertHasNoErrors();
        $this->assertTrue($session->fresh()->is_current);
    }

    public function test_the_top_bar_switcher_opens_the_codes_instead_of_switching(): void
    {
        $this->migrate();
        $first = AcademicSession::sole();
        $this->actingAs($this->mainSuperAdmin());

        // One session: the badge only, no switcher.
        Livewire::test(NavBar::class)->assertSee($first->name)->assertDontSee('Manage sessions');

        $next = $this->nextSession();

        Livewire::test(NavBar::class)
            ->assertSee('Manage sessions')
            ->assertSeeHtml(e(route('super-admin.sessions', ['switch' => $next->id])))
            ->assertDontSeeHtml('switchSession');
        $this->assertSame($first->name, AcademicSession::currentLabel());

        // The link lands on Session with the codes asked for.
        $this->fakeDelivery();
        Livewire::withQueryParams(['switch' => $next->id])->test(SessionPage::class)
            ->assertSet('switchId', $next->id);
        $this->assertSame($first->name, AcademicSession::currentLabel());

        // A sub super-admin without Session granted sees no switcher.
        $this->actingAs(User::forceCreate(['name' => 'Sub', 'email' => 'sub@example.com', 'role' => 'sub-super-admin', 'permissions' => ['super-admin.documents']]));
        Livewire::test(NavBar::class)->assertDontSee('Manage sessions');
    }

    // ─── A session's schools, and a school's data in it ─────────────────────

    public function test_a_sessions_schools_are_those_on_superlms_by_its_end_new_ones_marked(): void
    {
        $this->migrate();
        $current = AcademicSession::sole();
        $old = AcademicSession::create(['name' => $this->label($this->year - 1), 'starts_on' => ($this->year - 1) . '-04-01', 'ends_on' => "{$this->year}-03-31"]);
        $this->actingAs($this->mainSuperAdmin());

        DB::table('organizations')->insert([
            ['id' => 1, 'name' => 'Old Oak School', 'created_at' => ($this->year - 1) . '-06-10 10:00:00'],
            ['id' => 2, 'name' => 'New Leaf Academy', 'created_at' => "{$this->year}-05-02 10:00:00"],
        ]);
        DB::table('student_details')->insert([
            ['organization_id' => 1, 'full_name' => 'Early Kid', 'created_at' => ($this->year - 1) . '-07-01 10:00:00'],
            ['organization_id' => 1, 'full_name' => 'Late Kid', 'created_at' => "{$this->year}-06-01 10:00:00"],
        ]);

        $page = Livewire::test(SessionPage::class)
            ->call('openSchools', $old->id)
            ->assertSet('schoolsOf', $old->id)
            ->assertSee('Schools · ' . $old->name)
            ->assertSee('Old Oak School')
            ->assertDontSee('New Leaf Academy'); // registered after that session ended

        // Only one student was on Old Oak's roll by the end of the old session.
        $this->assertSame(1, $page->viewData('schools')->firstWhere('id', 1)->total_students);

        $page->call('backToSessions')->call('openSchools', $current->id)
            ->assertSee('Old Oak School')
            ->assertSee('New Leaf Academy')
            ->set('schoolJoined', 'new');
        // The list follows the filter (the Biggest Schools card above it shows the whole session).
        $this->assertSame(['New Leaf Academy'], $page->viewData('schools')->pluck('name')->all());
        $this->assertSame(1, $page->viewData('schoolTotals')['new']);
    }

    public function test_a_schools_data_in_a_session_is_that_sessions(): void
    {
        $this->migrate();
        $current = AcademicSession::sole();
        $this->actingAs($this->mainSuperAdmin());

        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->string('board')->nullable(); $t->integer('order')->default(0); $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('standard_id'); $t->unsignedBigInteger('organization_id')->nullable(); $t->string('name'); $t->timestamps();
        });
        Schema::create('admin_employees', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->unsignedBigInteger('organization_id'); $t->string('email')->nullable(); $t->string('mobile')->nullable();
            $t->string('designation')->nullable(); $t->string('type')->nullable(); $t->decimal('salary', 10, 2)->nullable();
            $t->boolean('is_active')->default(true); $t->date('joining_date')->nullable(); $t->timestamps();
        });
        Schema::create('student_attendances', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('student_detail_id'); $t->unsignedBigInteger('organization_id'); $t->date('attendance_date'); $t->integer('status'); $t->timestamps();
        });
        Schema::create('fee_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id');
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
            $t->string('fee_type')->nullable(); $t->decimal('amount', 10, 2)->default(0); $t->string('payment_mode')->nullable();
            $t->date('payment_date')->nullable(); $t->string('receipt_number')->nullable(); $t->timestamps();
        });
        Schema::create('transport_fee_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id');
            $t->decimal('amount', 10, 2)->default(0); $t->date('payment_date')->nullable(); $t->timestamps();
        });

        $y = $this->year;
        DB::table('organizations')->insert(['id' => 1, 'name' => 'Old Oak School', 'created_at' => ($y - 1) . '-06-10 10:00:00']);
        DB::table('standards')->insert(['id' => 1, 'organization_id' => 1, 'name' => 'Class 8', 'order' => 8]);
        DB::table('sections')->insert(['id' => 1, 'standard_id' => 1, 'organization_id' => 1, 'name' => 'A']);
        DB::table('student_details')->insert([
            ['id' => 1, 'organization_id' => 1, 'standard_id' => 1, 'section_id' => 1, 'full_name' => 'Aarav Sharma', 'admission_no' => 'A-1', 'created_at' => ($y - 1) . '-07-01 10:00:00'],
            ['id' => 2, 'organization_id' => 1, 'standard_id' => 1, 'section_id' => 1, 'full_name' => 'Bela Khan', 'admission_no' => 'B-2', 'created_at' => "{$y}-04-15 10:00:00"],
            // Added after this session's end: not in it.
            ['id' => 3, 'organization_id' => 1, 'standard_id' => 1, 'section_id' => 1, 'full_name' => 'Future Kid', 'admission_no' => 'F-3', 'created_at' => ($y + 1) . '-05-01 10:00:00'],
        ]);
        DB::table('fee_payments')->insert([
            ['organization_id' => 1, 'student_detail_id' => 1, 'standard_id' => 1, 'section_id' => 1, 'fee_type' => 'academic', 'amount' => 700, 'payment_mode' => 'cash', 'payment_date' => "{$y}-05-10", 'receipt_number' => 'IN-SESSION'],
            ['organization_id' => 1, 'student_detail_id' => 1, 'standard_id' => 1, 'section_id' => 1, 'fee_type' => 'academic', 'amount' => 900, 'payment_mode' => 'cash', 'payment_date' => ($y - 1) . '-05-10', 'receipt_number' => 'LAST-YEAR'],
        ]);
        DB::table('transport_fee_payments')->insert(['organization_id' => 1, 'student_detail_id' => 1, 'amount' => 300, 'payment_date' => "{$y}-06-01"]);
        DB::table('student_attendances')->insert([
            ['student_detail_id' => 1, 'organization_id' => 1, 'attendance_date' => "{$y}-04-20", 'status' => 1],
            ['student_detail_id' => 2, 'organization_id' => 1, 'attendance_date' => "{$y}-04-20", 'status' => 0],
            ['student_detail_id' => 1, 'organization_id' => 1, 'attendance_date' => ($y - 1) . '-12-01', 'status' => 0],
        ]);

        $page = Livewire::test(SessionPage::class)
            ->call('openSchools', $current->id)
            ->call('openSchoolData', 1)
            ->assertSet('schoolId', 1)
            ->assertSet('dataTab', 'overview')   // the school's analytics in the session first
            ->assertSee('School Data · ' . $current->name)
            ->assertSee('School Detail')
            ->assertSee('Students by Class')
            ->assertSee('₹1,000') // 700 academic + 300 bus, in the session only
            ->assertSee('50%');   // 1 of 2 present in the session

        $page->call('setDataTab', 'students')
            ->assertSee('Aarav Sharma')
            ->assertSee('Bela Khan')
            ->assertDontSee('Future Kid');

        $summary = $page->viewData('school')['summary'];
        $this->assertSame(2, $summary['students']);
        $this->assertSame(1, $summary['newStudents']);
        $this->assertEquals(700, $summary['academic']);
        $this->assertEquals(300, $summary['transport']);
        $this->assertSame(1, $summary['payments']);

        $page->call('setDataTab', 'fees')->assertSee('IN-SESSION')->assertDontSee('LAST-YEAR');
        $page->call('setDataTab', 'classes')->assertSee('Class 8');
        $page->call('setDataTab', 'attendance')->assertSee(\Carbon\Carbon::create($y, 4, 1)->format('F Y'));
        $page->call('setDataTab', 'students')->call('exportData')
            ->assertFileDownloaded('old-oak-school-' . \Illuminate\Support\Str::slug(str_replace('–', '-', $current->name)) . '-students-' . now()->format('Y-m-d') . '.xlsx');

        $page->call('backToSchools')->assertSet('schoolId', null)->assertSee('Schools · ' . $current->name);
    }

    public function test_a_school_outside_the_session_or_the_sub_admins_reach_is_not_opened(): void
    {
        $this->migrate();
        $old = AcademicSession::create(['name' => $this->label($this->year - 1), 'starts_on' => ($this->year - 1) . '-04-01', 'ends_on' => "{$this->year}-03-31"]);
        DB::table('organizations')->insert([
            ['id' => 1, 'name' => 'Old Oak School', 'created_at' => ($this->year - 1) . '-06-10 10:00:00'],
            ['id' => 2, 'name' => 'New Leaf Academy', 'created_at' => "{$this->year}-05-02 10:00:00"],
        ]);

        $this->actingAs(User::forceCreate(['name' => 'Sub', 'email' => 'sub@example.com', 'role' => 'sub-super-admin',
            'permissions' => ['super-admin.sessions'], 'allowed_organization_id' => 2]));

        Livewire::test(SessionPage::class)
            ->call('openSchools', $old->id)
            ->assertDontSee('Old Oak School') // not theirs
            ->call('openSchoolData', 1)->assertSet('schoolId', null)
            ->call('openSchoolData', 2)->assertSet('schoolId', null); // theirs, but registered after the session
    }

    public function test_session_comes_right_after_documents_in_the_menu_and_on_quick_links(): void
    {
        $this->migrate();
        $links = array_column(config('menu.super-admin'), 'link');
        $this->assertSame('super-admin.sessions', $links[array_search('super-admin.documents', $links, true) + 1]);

        $this->actingAs($this->mainSuperAdmin());

        $tiles = Livewire::test(QuickLinks::class)->get('links');
        $routes = array_column($tiles, 'route');

        $this->assertSame('super-admin.sessions', $routes[array_search('super-admin.documents', $routes, true) + 1]);
        // School Data (2026-11-03) comes last, after Session.
        $this->assertSame('super-admin.school-data', end($routes));
        $this->assertLessThanOrEqual(30, count($tiles), 'Quick Links is a 6 × 5 grid.');
    }
}
