<?php

namespace App\Http\Middleware;

use App\Models\Student\StudentDetail;
use App\Support\FeeAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A student whose school has them pay the Per Student fee themselves
 * (FeeAccess) gets nothing from the app's API but the payment screen until
 * they have paid: every other v1 request is answered 402 with code
 * "platform_fee_due" and the fee, and the app opens its payment screen.
 * Signing in and out, the push token, switching accounts and the school's
 * name stay open. Anyone else passes straight through.
 */
class PlatformFeeGate
{
    /** What an unpaid student may still reach (paths under api/v1/). */
    private const OPEN = [
        'platform-fee', 'platform-fee/*',
        'login', 'login/*', 'user/login', 'verify-otp', 'resend-otp', 'forgot-password', 'change-password',
        'device-token', 'device-token/*', 'save-fcm-token',
        'switch-account/*',
        'school-info', 'user/profile',
        'terms-and-conditions', 'privacy-policy', 'terms-of-use', 'about-app', 'rules-and-regulation',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->is('api/v1/*') || $request->is(...array_map(fn ($p) => 'api/v1/' . $p, self::OPEN))) {
            return $next($request);
        }

        try {
            $user = $request->user('sanctum');
            if (!FeeAccess::userMustPay($user)) {
                return $next($request);
            }
            $detail = StudentDetail::where('user_id', $user->id)->first();
            $status = $detail ? FeeAccess::status($detail) : null;
        } catch (\Throwable $e) {
            // Never lock the app out over a fault here.
            report($e);
            return $next($request);
        }

        return response()->json([
            'success'     => false,
            'status_code' => 402,
            'code'        => 'platform_fee_due',
            'message'     => FeeAccess::payFirstMessage(),
            'data'        => $status,
        ], 402);
    }
}
