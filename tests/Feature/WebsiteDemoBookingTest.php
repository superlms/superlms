<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\Calendar;
use App\Models\SuperAdmin\SuperAdminCalendarEvent;
use App\Models\User;
use App\Models\WebsiteDemo;
use App\Support\DemoBooking;
use App\Support\Platform;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A website demo request: thanked by email and WhatsApp with its own booking
 * link; the booking page offers the free times; picking one makes the meeting
 * link at once, puts it on the super admin's calendar and sends it. Super
 * Admin → Calendar → Queries lists the requests with their status.
 */
class WebsiteDemoBookingTest extends TestCase
{
    private User $main;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config([
            'app.key' => 'base64:' . base64_encode(str_repeat('d', 32)),
            'app.url' => 'https://superlms.in',
            'services.zeptomail.api_token' => 'zepto-test',
            'services.whatsapp.token' => 'wa-test',
            'services.whatsapp.phone_number_id' => '123',
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
            'services.google.refresh_token' => null,
        ]);
        Carbon::setTestNow('2026-11-12 10:00:00'); // a Thursday
        (new \ReflectionProperty(Platform::class, 'settings'))->setValue(null, null);

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name')->nullable(); $t->string('email')->nullable(); $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable(); $t->text('permissions')->nullable();
            $t->unsignedBigInteger('allowed_organization_id')->nullable(); $t->timestamps();
        });
        foreach ([
            '2026_03_07_200536_create_website_demos_table.php',
            '2026_06_29_000002_add_schedule_call_fields_to_website_demos_table.php',
            '2026_11_13_100000_add_booking_fields_to_website_demos_table.php',
            '2026_11_12_120000_create_super_admin_calendar_events_table.php',
            '2026_11_01_160100_create_platform_settings_table.php',
        ] as $migration) {
            (require base_path('database/migrations/' . $migration))->up();
        }

        $this->main = User::forceCreate(['name' => 'Main', 'email' => 'main@superlms.in', 'role' => 'super-admin']);

        Http::fake([
            'api.zeptomail.in/*'  => Http::response(['request_id' => 'zm-1'], 200),
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]], 200),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        (new \ReflectionProperty(Platform::class, 'settings'))->setValue(null, null);
        parent::tearDown();
    }

    private function demo(array $over = []): WebsiteDemo
    {
        $demo = WebsiteDemo::create($over + [
            'full_name' => 'Rahul Sharma', 'school_name' => 'Doon Public School', 'phone' => '9876543210',
            'email' => 'rahul@doonschool.in', 'city' => 'Dehradun', 'no_of_students' => '500-1000', 'role' => 'Principal',
            'status' => 'pending',
        ]);
        $demo->ensureToken();

        return $demo->fresh();
    }

    private function sentWhatsApp(string $template): array
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), 'graph.facebook.com') && ($r['template']['name'] ?? null) === $template)
            ->map(fn ($pair) => $pair[0])->values()->all();
    }

    private function sentEmails(): array
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), 'zeptomail'))->map(fn ($pair) => $pair[0])->values()->all();
    }

    public function test_a_demo_request_is_thanked_by_email_and_whatsapp_with_its_own_booking_link(): void
    {
        $res = $this->postJson('/api/website/demo', [
            'full_name' => 'Rahul Sharma', 'school_name' => 'Doon Public School', 'phone' => '9876543210',
            'email' => 'rahul@doonschool.in', 'city' => 'Dehradun', 'no_of_students' => '500-1000', 'role' => 'Principal',
        ])->assertOk()->assertJson(['success' => true]);

        $demo = WebsiteDemo::sole();
        $this->assertSame('pending', $demo->status);
        $this->assertSame(40, strlen((string) $demo->booking_token));
        $url = $res->json('booking_url');
        $this->assertStringEndsWith('/web/demo/book/' . $demo->booking_token, $url);

        $emails = $this->sentEmails();
        $this->assertCount(1, $emails);
        $this->assertStringContainsString('pick your time', $emails[0]['subject']);
        $this->assertStringContainsString($url, $emails[0]['htmlbody']);
        $this->assertSame('rahul@doonschool.in', $emails[0]['to'][0]['email_address']['address']);

        $wa = $this->sentWhatsApp('demo_request_thanks');
        $this->assertCount(1, $wa);
        $this->assertSame('919876543210', $wa[0]['to']);
        $components = collect($wa[0]['template']['components']);
        $this->assertSame(['Rahul', 'Doon Public School'], array_column($components->firstWhere('type', 'body')['parameters'], 'text'));
        $this->assertSame($demo->booking_token, $components->firstWhere('type', 'button')['parameters'][0]['text']);

        $log = $demo->fresh()->messages;
        $this->assertSame('thanks', $log[0]['type']);
        $this->assertSame('ok', $log[0]['email']);
        $this->assertSame('ok', $log[0]['whatsapp']);
    }

    public function test_a_bot_caught_by_the_honeypot_gets_no_booking_link(): void
    {
        $this->postJson('/api/website/demo', [
            'full_name' => 'Bot', 'school_name' => 'X', 'phone' => '9876543210', 'email' => 'bot@spam.in',
            'city' => 'X', 'no_of_students' => '1', 'role' => 'X', 'company' => 'filled',
        ])->assertOk()->assertJsonMissing(['booking_url']);

        $this->assertSame(0, WebsiteDemo::count());
        $this->assertSame([], $this->sentEmails());
    }

    public function test_the_booking_page_offers_the_free_times_and_picking_one_makes_the_meeting_at_once(): void
    {
        $demo = $this->demo();

        // Today from 1 PM (3 hours' notice), Mon–Sat, 10 AM – 6 PM in 30 minutes.
        $slots = DemoBooking::slots($demo);
        $this->assertSame('13:00', $slots['2026-11-12'][0]);
        $this->assertSame('10:00', $slots['2026-11-13'][0]);
        $this->assertSame('17:30', end($slots['2026-11-13']));
        $this->assertArrayNotHasKey('2026-11-15', $slots); // a Sunday

        $this->get('/web/demo/book/' . $demo->booking_token)
            ->assertOk()
            ->assertSee('Hi Rahul, pick a time for your', false)
            ->assertSee('data-date="2026-11-13"', false)
            ->assertSee('Confirm &amp; get meeting link', false);

        $this->post('/web/demo/book/' . $demo->booking_token, ['date' => '2026-11-13', 'time' => '11:00'])
            ->assertRedirect('/web/demo/book/' . $demo->booking_token);

        $demo->refresh();
        $this->assertSame('booked', $demo->status);
        $this->assertSame('2026-11-13 11:00:00', $demo->meeting_at->toDateTimeString());
        $this->assertSame(30, (int) $demo->meeting_minutes);
        $this->assertSame('jitsi', $demo->meet_provider);
        $this->assertStringStartsWith('https://meet.jit.si/SuperLMS-Demo-', $demo->meet_link);

        // On the main super admin's calendar.
        $event = SuperAdminCalendarEvent::sole();
        $this->assertSame($this->main->id, (int) $event->user_id);
        $this->assertSame($event->id, (int) $demo->calendar_event_id);
        $this->assertSame('meeting', $event->type);
        $this->assertSame('Demo — Doon Public School', $event->title);
        $this->assertSame('2026-11-13', $event->date->toDateString());
        $this->assertSame('11:00', SuperAdminCalendarEvent::hm($event->start_time));
        $this->assertSame('11:30', SuperAdminCalendarEvent::hm($event->end_time));
        $this->assertSame($demo->meet_link, $event->location);

        // The link went at once, by email (with an invite) and WhatsApp.
        $mail = collect($this->sentEmails())->last();
        $this->assertStringContainsString('Your demo is booked', $mail['subject']);
        $this->assertStringContainsString($demo->meet_link, $mail['htmlbody']);
        $this->assertSame('text/calendar', $mail['attachments'][0]['mime_type']);
        $this->assertStringContainsString('DTSTART:20261113T053000Z', base64_decode($mail['attachments'][0]['content']));

        $wa = $this->sentWhatsApp('demo_meeting_confirmed');
        $this->assertCount(1, $wa);
        $body = array_column(collect($wa[0]['template']['components'])->firstWhere('type', 'body')['parameters'], 'text');
        $this->assertSame(['Rahul', 'Fri, 13 Nov 2026 at 11:00 AM (IST)', $demo->meet_link], $body);

        // The page now shows the meeting and its link.
        $this->get('/web/demo/book/' . $demo->booking_token)
            ->assertOk()
            ->assertSee("You're booked, Rahul!", false)
            ->assertSee($demo->meet_link)
            ->assertSee('Friday, 13 November 2026');
    }

    public function test_a_time_already_taken_or_busy_on_the_calendar_is_not_offered_or_booked(): void
    {
        $first  = $this->demo();
        $second = $this->demo(['full_name' => 'Priya', 'email' => 'priya@school.in']);
        DemoBooking::book($first, '2026-11-13', '11:00');

        // The super admin's own meeting 2–3 PM blocks it; a task or an all-day event does not.
        SuperAdminCalendarEvent::create(['user_id' => $this->main->id, 'type' => 'meeting', 'title' => 'Bank', 'date' => '2026-11-13', 'start_time' => '14:00:00', 'end_time' => '15:00:00']);
        SuperAdminCalendarEvent::create(['user_id' => $this->main->id, 'type' => 'task', 'title' => 'Call', 'date' => '2026-11-13', 'start_time' => '16:00:00']);
        SuperAdminCalendarEvent::create(['user_id' => $this->main->id, 'type' => 'event', 'title' => 'Diwali', 'date' => '2026-11-13']);

        $times = DemoBooking::slots($second)['2026-11-13'];
        $this->assertNotContains('11:00', $times);
        $this->assertNotContains('14:00', $times);
        $this->assertNotContains('14:30', $times);
        $this->assertContains('15:00', $times);
        $this->assertContains('16:00', $times);
        $this->assertContains('11:30', $times);

        $this->post('/web/demo/book/' . $second->booking_token, ['date' => '2026-11-13', 'time' => '11:00'])
            ->assertRedirect('/web/demo/book/' . $second->booking_token)
            ->assertSessionHasErrors('time');
        $this->assertSame('pending', $second->fresh()->status);

        // Outside the hours, or not on a slot, is refused too.
        $this->post('/web/demo/book/' . $second->booking_token, ['date' => '2026-11-13', 'time' => '19:00'])->assertSessionHasErrors('time');
        $this->post('/web/demo/book/' . $second->booking_token, ['date' => '2026-11-15', 'time' => '11:00'])->assertSessionHasErrors('time');
        $this->assertSame('pending', $second->fresh()->status);

        // An unknown token is a 404.
        $this->get('/web/demo/book/' . str_repeat('x', 40))->assertNotFound();
    }

    public function test_a_booked_demo_moves_to_another_time_and_keeps_its_link(): void
    {
        $demo = $this->demo();
        DemoBooking::book($demo, '2026-11-13', '11:00');
        $demo->refresh();
        $link  = $demo->meet_link;
        $event = $demo->calendar_event_id;

        $this->post('/web/demo/book/' . $demo->booking_token, ['date' => '2026-11-14', 'time' => '15:30'])->assertRedirect();

        $demo->refresh();
        $this->assertSame('2026-11-14 15:30:00', $demo->meeting_at->toDateTimeString());
        $this->assertSame($link, $demo->meet_link);
        $this->assertSame($event, $demo->calendar_event_id);
        $this->assertSame(1, SuperAdminCalendarEvent::count());
        $this->assertSame('2026-11-14', SuperAdminCalendarEvent::sole()->date->toDateString());
        $this->assertSame('moved', collect($demo->messages)->last()['type']);
    }

    public function test_with_google_connected_a_booking_gets_a_google_meet_link(): void
    {
        config([
            'services.google.client_id' => 'cid.apps.googleusercontent.com',
            'services.google.client_secret' => 'secret',
            'services.google.refresh_token' => 'refresh-1',
        ]);
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'at-1', 'expires_in' => 3599], 200),
            'www.googleapis.com/calendar/v3/*' => Http::response(['id' => 'ev-1', 'hangoutLink' => 'https://meet.google.com/abc-defg-hij'], 200),
            'api.zeptomail.in/*'  => Http::response(['request_id' => 'zm-1'], 200),
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]], 200),
        ]);

        $demo = $this->demo();
        $this->assertTrue(DemoBooking::book($demo, '2026-11-13', '12:00')['ok']);
        $demo->refresh();

        $this->assertSame('google', $demo->meet_provider);
        $this->assertSame('https://meet.google.com/abc-defg-hij', $demo->meet_link);
        $this->assertSame('ev-1', $demo->google_event_id);

        $create = Http::recorded(fn (Request $r) => str_contains($r->url(), 'calendar/v3/calendars/primary/events'))->first()[0];
        $this->assertStringContainsString('conferenceDataVersion=1', $create->url());
        $this->assertStringContainsString('sendUpdates=all', $create->url());
        $this->assertSame('hangoutsMeet', $create['conferenceData']['createRequest']['conferenceSolutionKey']['type']);
        $this->assertSame([['email' => 'rahul@doonschool.in']], $create['attendees']);
        $this->assertSame('Bearer at-1', $create->header('Authorization')[0]);
    }

    public function test_if_google_fails_the_booking_still_goes_through_with_a_jitsi_link(): void
    {
        config([
            'services.google.client_id' => 'cid', 'services.google.client_secret' => 'secret', 'services.google.refresh_token' => 'refresh-1',
        ]);
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant'], 400),
            'api.zeptomail.in/*'  => Http::response(['request_id' => 'zm-1'], 200),
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]], 200),
        ]);

        $demo = $this->demo();
        $this->assertTrue(DemoBooking::book($demo, '2026-11-13', '12:00')['ok']);
        $this->assertSame('jitsi', $demo->fresh()->meet_provider);
    }

    public function test_queries_tab_lists_requests_with_created_at_and_status_and_cancel_takes_the_demo_off_the_calendar(): void
    {
        $this->actingAs($this->main);
        $booked  = $this->demo(['full_name' => 'Anita Rao', 'school_name' => 'Green Valley School']);
        $pending = $this->demo(['full_name' => 'Vikram Singh', 'school_name' => 'Sunrise Academy']);
        DemoBooking::book($booked, '2026-11-13', '11:00');
        $booked->refresh();

        $page = Livewire::test(Calendar::class)
            ->assertSee('Queries')
            ->call('switchTab', 'queries')
            ->assertSet('tab', 'queries')
            ->assertSee('Anita Rao')
            ->assertSee('Green Valley School')
            ->assertSee('Vikram Singh')
            ->assertSee('Created At')
            ->assertSee('12 Nov 2026')
            ->assertSee('data-query-status="booked"', false)
            ->assertSee('data-query-status="pending"', false)
            ->assertSee('Booking Settings')
            ->set('qStatus', 'booked')
            ->assertSee('Anita Rao')
            ->assertDontSee('Vikram Singh')
            ->set('qStatus', '')
            ->set('qSearch', 'Sunrise')
            ->assertSee('Vikram Singh')
            ->assertDontSee('Anita Rao')
            ->call('clearFilters')
            ->call('viewQuery', $booked->id)
            ->assertSee($booked->meet_link)
            ->assertSee('Booking link')
            ->call('setQueryStatus', $booked->id, 'cancel');

        $this->assertSame('cancelled', $booked->fresh()->status);
        $this->assertSame(0, SuperAdminCalendarEvent::count());

        // A cancelled demo's link is closed; reopened, it works again.
        $this->get('/web/demo/book/' . $booked->booking_token)->assertOk()->assertSee('This booking link is closed');
        $page->call('setQueryStatus', $booked->id, 'reopen');
        $this->assertSame('pending', $booked->fresh()->status);
        $this->assertNull($booked->fresh()->meeting_at);
        $this->get('/web/demo/book/' . $booked->booking_token)->assertOk()->assertSee('pick a time for your', false);

        $page->call('setQueryStatus', $pending->id, 'complete');
        $this->assertSame('completed', $pending->fresh()->status);

        $page->call('confirmDeleteQuery', $pending->id)->call('deleteQuery');
        $this->assertNull($pending->fresh());
    }

    public function test_the_booking_link_can_be_sent_again_and_settings_change_the_free_times(): void
    {
        $this->actingAs($this->main);
        $demo = $this->demo();

        Livewire::test(Calendar::class)
            ->call('switchTab', 'queries')
            ->call('resendBookingLink', $demo->id)
            ->call('openSettings')
            ->assertSet('showSettings', true)
            ->assertSee('demo_request_thanks')
            ->assertSee('demo_meeting_confirmed')
            ->set('sDays', ['5'])          // Fridays only
            ->set('sStart', '15:00')
            ->set('sEnd', '17:00')
            ->set('sMinutes', '60')
            ->call('saveSettings')
            ->assertHasNoErrors()
            ->assertSet('showSettings', false)
            ->call('showEmailPreview', 'confirmed')
            ->assertSee('Your demo is booked!');

        $this->assertCount(1, $this->sentWhatsApp('demo_request_thanks'));
        $this->assertSame(['2026-11-13' => ['15:00', '16:00'], '2026-11-20' => ['15:00', '16:00']], DemoBooking::slots($demo));
    }

    public function test_a_sub_super_admin_without_website_enquiries_does_not_get_the_queries_tab(): void
    {
        $sub = User::forceCreate(['name' => 'Sub', 'email' => 'sub@superlms.in', 'role' => 'sub-super-admin', 'permissions' => ['super-admin.calendar']]);
        $demo = $this->demo(['full_name' => 'Hidden Person']);
        $this->actingAs($sub->fresh());

        Livewire::test(Calendar::class)
            ->assertDontSee('Queries')
            ->call('switchTab', 'queries')
            ->assertSet('tab', 'calendar')
            ->assertDontSee('Hidden Person')
            ->call('setQueryStatus', $demo->id, 'cancel');

        $this->assertSame('pending', $demo->fresh()->status);
    }
}
