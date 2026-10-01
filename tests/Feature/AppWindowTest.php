<?php

namespace Tests\Feature;

use App\Support\AppWindow;
use Tests\TestCase;

/**
 * An installed panel app and the browser keep their own sign-ins: the app's
 * pages live under its mount, which the application never sees, and it has a
 * session cookie of its own.
 */
class AppWindowTest extends TestCase
{
    private const NAV = ['Sec-Fetch-Mode' => 'navigate', 'Sec-Fetch-Dest' => 'document'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        config(['app.key' => 'base64:' . base64_encode(str_repeat('k', 32)), 'session.driver' => 'array']);
    }

    private function cookieNames($response): array
    {
        return array_map(fn ($c) => $c->getName(), $response->headers->getCookies());
    }

    public function test_a_page_under_the_mount_is_the_apps_and_has_its_own_session_cookie(): void
    {
        $base    = config('session.cookie');
        $browser = $this->get('/login');
        $app     = $this->get('/9/~/login');

        $browser->assertOk();
        $app->assertOk();
        $this->assertSame('/9/~', AppWindow::mount());
        $this->assertContains($base, $this->cookieNames($browser));
        $this->assertNotContains($base, $this->cookieNames($app));
        $this->assertContains(AppWindow::sessionCookie('/9/~'), $this->cookieNames($app));
        $this->assertStringContainsString('var mount = "\/9\/~"', $app->getContent());
        $this->assertStringNotContainsString('var mount', $browser->getContent());
    }

    public function test_the_app_of_each_school_and_the_accounts_app_have_their_own_cookies(): void
    {
        $names = array_unique([
            AppWindow::sessionCookie(null),
            AppWindow::sessionCookie('/~'),
            AppWindow::sessionCookie('/superadmin/~'),
            AppWindow::sessionCookie('/9/~'),
            AppWindow::sessionCookie('/10/~'),
            AppWindow::sessionCookie('/accounts/~'),
        ]);

        $this->assertCount(6, $names);
    }

    public function test_a_navigation_from_a_page_of_the_app_is_sent_to_its_mounted_address(): void
    {
        $this->get('/login?x=1', self::NAV + ['Referer' => 'http://localhost/9/~/9/home'])
            ->assertRedirect('/9/~/login?x=1');

        // A fetch is not moved, nor is a file, nor a page of the browser.
        $this->get('/login', ['Referer' => 'http://localhost/9/~/9/home'])->assertOk();
        $this->get('/login', self::NAV)->assertOk();
        $this->assertNull(AppWindow::mount());
    }

    public function test_an_apps_opening_page_is_mounted_and_its_redirects_stay_in_the_app(): void
    {
        $this->get('/9/launch', self::NAV)->assertRedirect('/9/~/9/launch');
        $this->get('/accounts/launch', self::NAV)->assertRedirect('/accounts/~/accounts/launch');
        $this->get('/app/superadmin', self::NAV)->assertRedirect('/superadmin/~/app/superadmin');

        // The opening page, under the mount, sends a device nobody signed in on to the login — inside the app.
        $this->get('/accounts/~/accounts/launch', self::NAV)
            ->assertRedirect('/accounts/~/accounts?password=1');
    }
}
