<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * The panels' download loader (partials.download-loader) follows a download
 * link with `_dl=<token>` added. Whatever comes back — the file, or an error
 * page instead — hands the token back in a short-lived cookie the page can
 * read: that is how the page knows the file has arrived and takes the loader
 * down. A browser cannot otherwise tell a page when a download it started
 * from a link has come.
 */
class DownloadReady
{
    public const PARAM  = '_dl';
    public const COOKIE = 'lms_dl';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $token = $request->query(self::PARAM);
        if (is_string($token) && preg_match('/^[A-Za-z0-9]{8,40}$/', $token)) {
            // Read by the page's script, so not HttpOnly; and not encrypted (bootstrap/app.php).
            $response->headers->setCookie(Cookie::create(
                self::COOKIE, $token, time() + 120, '/', null, $request->isSecure(), false, false, Cookie::SAMESITE_LAX,
            ));
        }

        return $response;
    }
}
