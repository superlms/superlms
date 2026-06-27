<?php

namespace App\Http\Middleware;

use App\Http\Controllers\SchoolSiteController;
use App\Models\SchoolWebsite;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * Global middleware: when a request arrives on a school's custom domain, serve
 * that school's website (Kider template) instead of the main app. API calls
 * and static assets are allowed to fall through to normal routing.
 */
class ServeSchoolSite
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower(preg_replace('/:\d+$/', '', $request->getHost()));

        // Fast path: the main app's own host(s) never serve a school site.
        if (in_array($host, $this->mainHosts(), true)) {
            return $next($request);
        }

        // Table may not exist yet (before migration) — never break the app.
        try {
            if (! Schema::hasTable('school_websites')) {
                return $next($request);
            }
        } catch (\Throwable $e) {
            return $next($request);
        }

        $site = SchoolWebsite::forHost($host);
        if (! $site) {
            return $next($request);
        }

        $path = trim($request->path(), '/');

        // Let the framework handle API, assets and storage on this host.
        if ($path !== '' && preg_match('#^(api|school-templates|build|storage|livewire|vendor)/#', $path)) {
            return $next($request);
        }

        return app(SchoolSiteController::class)->render($site, $path);
    }

    /** Hosts that belong to the main SUPERLMS app itself. */
    private function mainHosts(): array
    {
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';

        return array_unique(array_filter(array_merge(
            [strtolower($appHost), 'www.' . strtolower($appHost), 'localhost', '127.0.0.1'],
            config('school_site.main_hosts', [])
        )));
    }
}
