<?php

use App\Http\Middleware\EnsureIsAccounts;
use App\Http\Middleware\EnsureIsAdmin;
use App\Http\Middleware\EnsureIsSuperAdmin;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\ServeSchoolSite;
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

        $middleware->redirectGuestsTo('/api/unauthenticate');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();