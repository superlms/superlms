<?php

namespace Tests\Feature;

use App\Http\Controllers\SchoolSiteController;
use App\Livewire\Admin\WebsiteData;
use App\Models\Organization;
use App\Models\SchoolWebsite;
use App\Models\User;
use App\Support\SchoolSiteData;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * User's ask of 2026-10-20: the website's More → Transport lists the routes
 * from the admin's Transport — route, pickup and drop time, the fare and the
 * annual fare — and Curriculum → Academic Calendar lists every event on the
 * school's calendar this session.
 */
class SchoolSiteTransportCalendarTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('t', 32))]);
        Carbon::setTestNow('2026-10-20 10:00:00');

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
        Schema::create('transportations', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('route_name'); $t->string('vehicle_type')->nullable();
            $t->string('route_group')->nullable(); $t->unsignedBigInteger('driver_detail_id')->default(0);
            $t->string('pickup_time')->nullable(); $t->string('drop_time')->nullable(); $t->json('stops')->nullable();
            $t->decimal('monthly_fee', 10, 2)->default(0); $t->unsignedSmallInteger('capacity')->default(0);
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('time_tables', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('created_by')->nullable();
            $t->string('title'); $t->text('description')->nullable(); $t->date('date')->nullable();
            $t->time('start_time')->nullable(); $t->time('end_time')->nullable(); $t->string('event_type')->default('class');
            $t->string('color')->nullable(); $t->string('attachment')->nullable(); $t->string('recurrence')->nullable();
            $t->date('recurrence_end_date')->nullable(); $t->boolean('is_all_day')->default(false);
            $t->boolean('is_cancelled')->default(false); $t->text('cancellation_reason')->nullable(); $t->timestamps();
        });

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

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function records(): void
    {
        DB::table('transportations')->insert([
            // One route run by a bus and a van: one row on the site.
            ['organization_id' => 1, 'route_name' => 'Agra Cantt', 'vehicle_type' => 'Bus', 'route_group' => 'g1', 'pickup_time' => '07:30', 'drop_time' => '14:15', 'monthly_fee' => 1200, 'is_active' => true],
            ['organization_id' => 1, 'route_name' => 'Agra Cantt', 'vehicle_type' => 'Van', 'route_group' => 'g1', 'pickup_time' => '07:30', 'drop_time' => '14:15', 'monthly_fee' => 1200, 'is_active' => true],
            ['organization_id' => 1, 'route_name' => 'Kamla Nagar', 'vehicle_type' => 'Bus', 'route_group' => 'g2', 'pickup_time' => '06:50', 'drop_time' => null, 'monthly_fee' => 950.5, 'is_active' => true],
            ['organization_id' => 1, 'route_name' => 'Old Route', 'vehicle_type' => 'Bus', 'route_group' => 'g3', 'pickup_time' => '08:00', 'drop_time' => '15:00', 'monthly_fee' => 500, 'is_active' => false],
            ['organization_id' => 2, 'route_name' => 'Their Route', 'vehicle_type' => 'Bus', 'route_group' => 'g4', 'pickup_time' => '08:00', 'drop_time' => '15:00', 'monthly_fee' => 500, 'is_active' => true],
        ]);
        DB::table('time_tables')->insert([
            ['organization_id' => 1, 'title' => 'Diwali Break', 'description' => 'School closed', 'date' => '2026-11-08', 'start_time' => null, 'end_time' => null, 'event_type' => 'holiday', 'is_all_day' => true, 'is_cancelled' => false],
            ['organization_id' => 1, 'title' => 'Session Begins', 'description' => null, 'date' => '2026-04-01', 'start_time' => '08:00:00', 'end_time' => '09:00:00', 'event_type' => 'event', 'is_all_day' => false, 'is_cancelled' => false],
            ['organization_id' => 1, 'title' => 'Half Yearly Exams', 'description' => null, 'date' => '2026-09-21', 'start_time' => '09:00:00', 'end_time' => null, 'event_type' => 'exam', 'is_all_day' => false, 'is_cancelled' => false],
            ['organization_id' => 1, 'title' => 'Annual Exams', 'description' => null, 'date' => '2027-03-10', 'start_time' => null, 'end_time' => null, 'event_type' => 'exam', 'is_all_day' => true, 'is_cancelled' => false],
            ['organization_id' => 1, 'title' => 'Called Off', 'description' => null, 'date' => '2026-12-01', 'start_time' => null, 'end_time' => null, 'event_type' => 'event', 'is_all_day' => true, 'is_cancelled' => true],
            ['organization_id' => 1, 'title' => 'Last Session', 'description' => null, 'date' => '2026-03-20', 'start_time' => null, 'end_time' => null, 'event_type' => 'event', 'is_all_day' => true, 'is_cancelled' => false],
            ['organization_id' => 1, 'title' => 'Next Session', 'description' => null, 'date' => '2027-04-02', 'start_time' => null, 'end_time' => null, 'event_type' => 'event', 'is_all_day' => true, 'is_cancelled' => false],
            ['organization_id' => 2, 'title' => 'Their Event', 'description' => null, 'date' => '2026-10-25', 'start_time' => null, 'end_time' => null, 'event_type' => 'event', 'is_all_day' => true, 'is_cancelled' => false],
        ]);
    }

    private function site(array $attrs = []): SchoolWebsite
    {
        $site = SchoolWebsite::create(array_merge(['organization_id' => 1, 'status' => false], $attrs));
        $site->setRelation('organization', Organization::find(1)->setRelation('schoolInfo', null));

        return $site;
    }

    private function text(string $html): string
    {
        return html_entity_decode(preg_replace('/\s+/', ' ', strip_tags(preg_replace('#<(style|script)\b.*?</\1>#s', '', $html))));
    }

    private function render(SchoolWebsite $site, string $path): string
    {
        $res = app(SchoolSiteController::class)->render($site, $path);
        $this->assertSame(200, $res->getStatusCode(), $path);

        return $res->getContent();
    }

    public function test_the_routes_and_the_sessions_events_are_read_from_the_records(): void
    {
        $this->records();
        $lms = new SchoolSiteData(1);

        // Active routes, one a group, earliest pickup first; the year is eleven months.
        $this->assertSame([
            ['name' => 'Kamla Nagar', 'pickup' => '6:50 AM', 'drop' => null, 'monthly' => 950.5, 'annual' => 10455.5],
            ['name' => 'Agra Cantt', 'pickup' => '7:30 AM', 'drop' => '2:15 PM', 'monthly' => 1200.0, 'annual' => 13200.0],
        ], $lms->transport());

        // This session's events (1 Apr 2026 – 31 Mar 2027) by date; cancelled ones and other sessions' left out.
        $this->assertSame(['Session Begins', 'Half Yearly Exams', 'Diwali Break', 'Annual Exams'], array_column($lms->events(), 'title'));
        $this->assertSame(['date' => '2026-04-01', 'title' => 'Session Begins', 'description' => '', 'type' => 'Event', 'time' => '8:00 AM – 9:00 AM'], $lms->events()[0]);
        $this->assertSame('9:00 AM', $lms->events()[1]['time']);
        $this->assertSame(['All day', 'Holiday', 'School closed'], [$lms->events()[2]['time'], $lms->events()[2]['type'], $lms->events()[2]['description']]);
        $this->assertSame('2026-27', SchoolSiteData::session()[2]);

        $this->assertSame([], (new SchoolSiteData(3))->transport());
        $this->assertSame([], (new SchoolSiteData(3))->events());
    }

    public function test_transport_and_the_academic_calendar_show_them(): void
    {
        $this->records();
        $site = $this->site();

        $transport = $this->text($this->render($site, 'transport'));
        $this->assertStringContainsString('Transport Routes 2 routes', $transport);
        $this->assertStringContainsString('S.No Route Pickup Time Drop Time Monthly Fare Annual Fare', $transport);
        $this->assertStringContainsString('1 Kamla Nagar 6:50 AM — ₹950.50 ₹10,455.50 2 Agra Cantt 7:30 AM 2:15 PM ₹1,200 ₹13,200', $transport);
        foreach (['Old Route', 'Their Route', 'being updated'] as $not) {
            $this->assertStringNotContainsString($not, $transport, $not);
        }

        $calendar = $this->text($this->render($site, 'academic-calendar'));
        // A plain table as Transport's: a value a cell, no month lines (user's ask of 2026-10-21).
        $this->assertStringContainsString('Academic Calendar Session 2026-27 · 4 events S.No Date Day Event Details Type Time', $calendar);
        $this->assertStringContainsString('1 01 Apr 2026 Wednesday Session Begins — Event 8:00 AM – 9:00 AM', $calendar);
        $this->assertStringContainsString('3 08 Nov 2026 Sunday Diwali Break School closed Holiday All day', $calendar);
        $this->assertStringContainsString('4 10 Mar 2027 Wednesday Annual Exams — Exam All day', $calendar);
        $this->assertStringNotContainsString('April 2026', $calendar);
        foreach (['Called Off', 'Last Session', 'Next Session', 'Their Event', 'published here shortly'] as $not) {
            $this->assertStringNotContainsString($not, $calendar, $not);
        }
    }

    public function test_with_nothing_set_up_each_page_says_so(): void
    {
        $site = $this->site(['content' => ['pages' => ['transport' => ['heading' => 'Transport', 'body' => 'Buses cover the whole city.']]]]);

        $transport = $this->text($this->render($site, 'transport'));
        $this->assertStringContainsString('Buses cover the whole city.', $transport);
        $this->assertStringNotContainsString('Transport Routes', $transport);
        $this->assertStringNotContainsString('published here shortly', $transport);

        $this->assertStringContainsString('The academic calendar will be published here shortly.', $this->text($this->render($site, 'academic-calendar')));

        SchoolWebsite::query()->delete();
        $this->assertStringContainsString('Transport details will be published here shortly.', $this->text($this->render($this->site(), 'transport')));
    }

    public function test_website_data_shows_them_live(): void
    {
        $this->records();
        $this->site();

        Livewire::test(WebsiteData::class)->call('openPage', 'transport')
            ->assertSee(['Transport routes', 'Live', 'Kamla Nagar', '₹13,200', 'Open Transport']);
        Livewire::test(WebsiteData::class)->call('openPage', 'academic-calendar')
            ->assertSee(["This session's events", 'Session 2026-27', 'Diwali Break', 'Open Calendar', 'Calendar documents']);
    }
}
