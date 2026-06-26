<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsAdmin
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
            return $request->route()->named('admin.login')
                ? $next($request)
                : redirect()->route('admin.login');
        }

        // If authenticated but neither an admin nor a sub-admin
        if (!in_array($user->role, ['admin', 'sub-admin'], true)) {
            return redirect()->route('super-admin.dashboard');
        }

        // Both admins and sub-admins must belong to an organization.
        if (!$user->organization_id) {
            Auth::logout();
            return redirect()->route('admin.login')
                ->withErrors(['email' => 'No organization assigned to this account.']);
        }

        // The URL's organization segment must match the signed-in user's org.
        $organization = $request->route('organization');
        if ($organization != $user->organization_id) {
            return redirect()->route('admin.quick-links', ['organization' => $user->organization_id])
                ->withErrors(['organization' => 'Invalid organization access.']);
        }

        // Sub-admin → scoped access by granted permissions.
        if ($user->role === 'sub-admin') {
            $routeName = $request->route()?->getName();

            // Managing other sub-admins is reserved for the full admin only.
            if ($routeName === 'admin.users') {
                return redirect()->route(
                    $this->firstAllowedRoute($user),
                    ['organization' => $user->organization_id]
                );
            }

            if (!$user->canAccessAdminRoute($routeName)) {
                return redirect()->route(
                    $this->firstAllowedRoute($user),
                    ['organization' => $user->organization_id]
                );
            }
        }

        return $next($request);
    }

    /**
     * The route a sub-admin should land on: their first granted permission,
     * falling back to the profile page if none.
     */
    private function firstAllowedRoute($user): string
    {
        $permissions = (array) $user->permissions;

        return $permissions[0] ?? 'admin.profile';
    }
}
