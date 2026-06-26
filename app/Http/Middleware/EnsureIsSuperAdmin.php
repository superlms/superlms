<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // If not authenticated
        if (!Auth::check()) {
            return $request->route()->named('super-admin.login')
                ? $next($request)
                : redirect()->route('super-admin.login');
        }

        // Full super-admin → unrestricted access
        if ($user->role === 'super-admin') {
            return $next($request);
        }

        // Sub-super-admin → scoped access by granted permissions
        if ($user->role === 'sub-super-admin') {
            $routeName = $request->route()?->getName();

            // Managing other sub-admins is reserved for the main super-admin only
            if ($routeName === 'super-admin.users') {
                return redirect()->route($this->firstAllowedRoute($user));
            }

            if ($user->canAccessSuperAdminRoute($routeName)) {
                return $next($request);
            }

            return redirect()->route($this->firstAllowedRoute($user));
        }

        // Any other role → bounce to their admin area
        return redirect()->route('admin.quick-links', ['organization' => $user->organization_id]);
    }

    /**
     * The route a sub-super-admin should land on: their first granted
     * permission, falling back to the profile page if none.
     */
    private function firstAllowedRoute($user): string
    {
        $permissions = (array) $user->permissions;

        return $permissions[0] ?? 'super-admin.profile';
    }
}
