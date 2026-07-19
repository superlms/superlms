<?php

use App\Http\Middleware\EnsureIsAccounts;
use App\Http\Middleware\EnsureIsAdmin;
use App\Http\Middleware\EnsureIsSuperAdmin;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\ServeSchoolSite;
use App\Http\Middleware\TrackWebsiteVisit;
use App\Http\Middleware\VerifyOrganizationAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global: serve a school's site on its custom domain. Appended so it runs
        // after TrustProxies — getHost() then reflects the real forwarded host.
        $middleware->append(ServeSchoolSite::class);

        $middleware->alias([
            'admin' => EnsureIsAdmin::class,
            'super-admin' => EnsureIsSuperAdmin::class,
            'accounts' => EnsureIsAccounts::class,
            'verify.organization' => VerifyOrganizationAccess::class,
            'module' => EnsureModuleEnabled::class,
            'track.website' => TrackWebsiteVisit::class,
        ]);

        // Trust the upstream proxy (host nginx) so Laravel knows the original request was HTTPS.
        // This makes asset() / url() generate https:// URLs and fixes mixed-content blocks.
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        // Always-on security response headers (clickjacking, MIME-sniff, referrer leakage).
        $middleware->web(append: [SecurityHeaders::class]);
        $middleware->api(append: [SecurityHeaders::class]);

        // Guests: web panels go to their own login screen; API keeps its JSON 403.
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->routeIs('super-admin.*')) {
                return route('super-admin.login');
            }
            if ($request->routeIs('accounts.*')) {
                return route('accounts.login');
            }
            if ($request->routeIs('admin.*')) {
                return route('admin.login');
            }
            return '/api/unauthenticate';
        });

        // Already-authenticated users hitting a login page go back into their panel.
        $middleware->redirectUsersTo(function (Request $request) {
            if ($request->routeIs('super-admin.*')) {
                return route('super-admin.quick-links');
            }
            if ($request->routeIs('accounts.*')) {
                $u = auth('accounts')->user();
                return $u && $u->organization_id
                    ? route('accounts.dashboard', ['organization' => $u->organization_id])
                    : '/';
            }
            $u = auth('admin')->user();
            return $u && $u->organization_id
                ? route('admin.quick-links', ['organization' => $u->organization_id])
                : '/';
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();