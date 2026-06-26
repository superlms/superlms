<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Block MIME-sniff-based XSS
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        // Disallow framing — clickjacking protection
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        // Limit referrer leakage to other origins
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // Drop powerful APIs we never use
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=(), payment=()');

        return $response;
    }
}
