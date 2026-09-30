<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Super Admin → Schools → Login: signs this browser's admin guard into a
 * school and lands on its home, in the school tab.
 *
 * The list's button submits a small form straight into the named tab
 * ("superlms-school") from the click itself, so the tab opens on this request
 * at once. It used to open an empty tab first and fill it in once a Livewire
 * call answered — when that answer never came, the tab stayed on about:blank.
 *
 * The session keeps its id: logging in with Auth::login() gives the session a
 * new id, and a request another admin tab had in flight then came back with
 * the old cookie and put the browser back on the previous school — the
 * "didn't switch" the super-admin saw. The super-admin is already signed in on
 * this session, so there is nothing to fix a session against here.
 *
 * The landing page tells the browser's other admin tabs (school-login-sync)
 * so they follow to the new school.
 */
class SchoolLoginController extends Controller
{
    public function __invoke(Request $request, int $organization)
    {
        $user = Auth::guard('superadmin')->user();

        // Who may: the super-admin, or a sub super-admin with Schools — and
        // only their own school when they are limited to one.
        if ($user?->role === 'sub-super-admin') {
            $allowed = $user->allowedOrganizationId();
            if (!$user->canAccessSuperAdminRoute('super-admin.schools') || ($allowed && (int) $allowed !== $organization)) {
                abort(403);
            }
        } elseif ($user?->role !== 'super-admin') {
            abort(403);
        }

        $admin = Organization::whereKey($organization)->exists()
            ? User::where('organization_id', $organization)->where('role', 'admin')->first()
            : null;

        if (!$admin) {
            return redirect()->route('super-admin.schools')
                ->with('school-login-error', 'No admin account found for this school.');
        }

        // Onto the admin guard only — the super-admin stays signed in.
        $guard = Auth::guard('admin');
        $request->session()->put($guard->getName(), $admin->getAuthIdentifier());
        $guard->setUser($admin);
        event(new Login('admin', $admin, false));

        $home = route('admin.home', ['organization' => $organization]);

        return redirect($home)->with('school-login', [
            'org' => (string) $organization,
            'url' => $home,
        ]);
    }
}
