<?php

namespace Tests\Feature;

use App\Http\Middleware\MarkPanelBrowser;
use App\Http\Middleware\TrackWebsiteVisit;
use App\Models\User;
use App\Models\WebsiteVisit;
use App\Support\Platform;
use App\Support\WebsiteDesign;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The website's visitors on Super Admin → Analytics → Website (2026-11-19):
 * counted exactly — people reading superlms.in itself, each page once per
 * half hour per person (a cookie of the site's own) — and never the
 * platform's own users: anyone signed in to a panel, or a browser that has
 * opened a panel's login page (whose visits on the way there are taken back).
 */
class WebsiteVisitTrackingTest extends TestCase
{
    private const BROWSER = [
        'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language' => 'en-IN,en;q=0.9,hi;q=0.8',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('a', 32)), 'app.url' => 'https://superlms.in']);

        Schema::create('platform_settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->timestamps();
        });
        Schema::create('website_pages', function (Blueprint $t) {
            $t->id();
            $t->string('slug');
            $t->json('metadata')->nullable();
            $t->date('last_updated')->nullable();
            $t->timestamps();
        });
        Schema::create('website_visits', function (Blueprint $t) {
            $t->id();
            $t->string('path')->index();
            $t->string('page')->nullable();
            $t->string('visitor_id', 64)->index();
            $t->timestamp('created_at')->nullable()->index();
        });

        Platform::set(WebsiteDesign::SETTING_KEY, null);
        Carbon::setTestNow('2026-11-19 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function visit(string $path, array $headers = self::BROWSER, ?string $cookie = null, string $host = 'superlms.in')
    {
        // Headers set on the test case stay for its next requests: start clean.
        $req = $this->flushHeaders()->withHeaders($headers);
        if ($cookie !== null) {
            $req = $req->withUnencryptedCookie(TrackWebsiteVisit::VISITOR_COOKIE, $cookie);
        }

        return $req->get('https://' . $host . $path);
    }

    public function test_a_person_is_counted_once_per_page_and_gets_the_sites_own_cookie(): void
    {
        $first = $this->visit('/')->assertOk();
        $cookie = $first->getCookie(TrackWebsiteVisit::VISITOR_COOKIE, false);
        $this->assertNotNull($cookie, 'the first visit gives the browser its visitor id');
        $id = $cookie->getValue();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $id);
        $this->assertSame(1, WebsiteVisit::count());
        $this->assertSame(hash('sha256', 'c|' . $id), WebsiteVisit::first()->visitor_id);
        $this->assertSame('Home', WebsiteVisit::first()->page);

        // A reload, and coming back within half an hour: still one visit.
        $this->visit('/', cookie: $id)->assertOk();
        Carbon::setTestNow('2026-11-19 10:20:00');
        $this->visit('/', cookie: $id)->assertOk();
        $this->assertSame(1, WebsiteVisit::count());

        // Another page is another visit; the home page after the half hour too.
        $this->visit('/web/pricing', cookie: $id)->assertOk();
        Carbon::setTestNow('2026-11-19 10:45:00');
        $this->visit('/', cookie: $id)->assertOk();
        $this->assertSame(3, WebsiteVisit::count());
        $this->assertSame(1, WebsiteVisit::distinct('visitor_id')->count('visitor_id'));
    }

    public function test_bots_previews_and_scripts_are_not_people(): void
    {
        $bots = [
            ['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'Accept-Language' => 'en'],
            ['User-Agent' => 'WhatsApp/2.23.20.0 A', 'Accept-Language' => 'en'],
            ['User-Agent' => 'curl/8.4.0', 'Accept-Language' => 'en'],
            ['User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) HeadlessChrome/120.0 Safari/537.36', 'Accept-Language' => 'en'],
            // A browser's name but no language: a script. (A test request
            // carries Symfony's default language unless it is blanked.)
            ['User-Agent' => self::BROWSER['User-Agent'], 'Accept-Language' => ''],
            // The browser fetching ahead of a click.
            self::BROWSER + ['Sec-Purpose' => 'prefetch'],
        ];
        foreach ($bots as $headers) {
            $this->visit('/', $headers)->assertOk();
        }

        $this->assertSame(0, WebsiteVisit::count());
    }

    public function test_only_superlms_in_itself_is_counted(): void
    {
        $this->visit('/', host: '13.234.10.20')->assertOk();
        $this->visit('/', host: 'ec2-13-234-10-20.ap-south-1.compute.amazonaws.com')->assertOk();
        $this->assertSame(0, WebsiteVisit::count());

        $this->visit('/', host: 'www.superlms.in')->assertOk();
        $this->assertSame(1, WebsiteVisit::count());
    }

    public function test_someone_signed_in_to_a_panel_is_not_a_visitor(): void
    {
        $user = new User(['name' => 'Amit Dagur', 'email' => 'amit@example.com']);
        $user->id = 7;
        $user->role = 'super-admin';

        $this->actingAs($user, 'superadmin');
        $this->visit('/')->assertOk();

        $this->assertSame(0, WebsiteVisit::count());
    }

    public function test_a_browser_that_has_opened_a_login_page_is_left_out(): void
    {
        $this->withUnencryptedCookie(MarkPanelBrowser::COOKIE, '1');
        $this->visit('/')->assertOk();

        $this->assertSame(0, WebsiteVisit::count());
    }

    public function test_opening_a_login_page_marks_the_browser_and_takes_back_the_visits_on_the_way(): void
    {
        $id = str_repeat('ab', 16);
        $other = str_repeat('cd', 16);

        // Yesterday's visit stays (older than the look-back); this morning's
        // home and pricing were the way to the login page; someone else's stay.
        Carbon::setTestNow('2026-11-18 09:00:00');
        $this->visit('/', cookie: $id);
        Carbon::setTestNow('2026-11-19 09:40:00');
        $this->visit('/', cookie: $id);
        $this->visit('/web/pricing', cookie: $id);
        $this->visit('/', cookie: $other);
        $this->assertSame(4, WebsiteVisit::count());

        Carbon::setTestNow('2026-11-19 10:00:00');
        $login = $this->withHeaders(self::BROWSER)
            ->withUnencryptedCookie(TrackWebsiteVisit::VISITOR_COOKIE, $id)
            ->get('https://superlms.in/admin')
            ->assertOk();

        $mark = $login->getCookie(MarkPanelBrowser::COOKIE, false);
        $this->assertNotNull($mark, 'the login page marks the browser');
        $this->assertSame('1', $mark->getValue());

        $mine = hash('sha256', 'c|' . $id);
        $this->assertSame(1, WebsiteVisit::where('visitor_id', $mine)->count(), "yesterday's visit is all that is left");
        $this->assertSame(1, WebsiteVisit::where('visitor_id', hash('sha256', 'c|' . $other))->count());
    }
}
