<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsAccounts
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!Auth::check()) {
            return $request->route()->named('accounts.login')
                ? $next($request)
                : redirect()->route('accounts.login');
        }

        // If authenticated on the accounts guard but not an accounts user,
        // drop this guard's session (other panels stay logged in).
        if ($user->role !== 'accounts') {
            Auth::guard('accounts')->logout();
            return redirect()->route('accounts.login');
        }

        // Check organization for accounts users
        if (!$user->organization_id) {
            Auth::guard('accounts')->logout();
            return redirect()->route('accounts.login')
                ->withErrors(['email' => 'No organization assigned to this account.']);
        }

        $organization = $request->route('organization');
        if ($organization && $organization != $user->organization_id) {
            return redirect()->route('accounts.dashboard', ['organization' => $user->organization_id])
                ->withErrors(['organization' => 'Invalid organization access.']);
        }

        // Check 2FA OTP verification
        if (!session('accounts_otp_verified') && !$request->route()->named('accounts.verify-otp')) {
            return redirect()->route('accounts.verify-otp');
        }

        return $next($request);
    }
}
