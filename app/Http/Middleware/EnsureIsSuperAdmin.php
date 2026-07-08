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
                $this->applyOrganizationScope($user);

                return $next($request);
            }

            return redirect()->route($this->firstAllowedRoute($user));
        }

        // Any other role on the superadmin guard → drop this guard's session
        // (admin/accounts sessions in the same browser stay logged in).
        Auth::guard('superadmin')->logout();
        return redirect()->route('super-admin.login');
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

    /**
     * A sub-super-admin can be limited to a single organization. When set,
     * scope every org-bearing model used by the super-admin screens to that
     * organization for the rest of this request (Livewire updates re-run the
     * page's middleware, so the scope applies there too).
     */
    private function applyOrganizationScope($user): void
    {
        $orgId = $user->allowedOrganizationId();
        if (!$orgId) {
            return; // all organizations — unchanged behaviour
        }

        \App\Models\Organization::addGlobalScope(
            'allowed-org',
            fn($q) => $q->where('organizations.id', $orgId)
        );

        $models = [
            \App\Models\Student\StudentDetail::class,
            \App\Models\Teacher\TeacherDetail::class,
            \App\Models\SuperAdmin\CreditQuery::class,
            \App\Models\SuperAdmin\SuperAdminFeeStructure::class,
            \App\Models\SuperAdmin\SuperAdminFeePayment::class,
            \App\Models\Admin\ContactSuperAdmin::class,
            \App\Models\Admin\RateLms::class,
            \App\Models\Admin\Fee\FeePayment::class,
            \App\Models\SchoolWebsite::class,
        ];

        foreach ($models as $model) {
            $table = (new $model)->getTable();
            $model::addGlobalScope(
                'allowed-org',
                fn($q) => $q->where($table . '.organization_id', $orgId)
            );
        }
    }
}
