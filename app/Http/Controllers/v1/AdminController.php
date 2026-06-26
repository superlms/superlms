<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\Fee\FeePayment;
use App\Models\Organization;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * School-admin entry point for the mobile app (Phase 0).
 *
 * Admins are Users with role 'admin' or 'sub-admin', scoped to an organization.
 * Login is email + password (mirrors the web checks, minus the web OTP step).
 */
class AdminController extends ApiController
{
    private const ADMIN_ROLES = ['admin', 'sub-admin'];

    /**
     * POST /api/v1/admin/login
     */
    public function login(Request $request)
    {
        if ($err = $this->validateWith($request, [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ])) return $err;

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error('Invalid email or password.', 401);
        }

        if (!in_array($user->role, self::ADMIN_ROLES, true)) {
            return $this->error('You do not have school admin access.', 403);
        }

        if (!$user->organization_id) {
            return $this->error('No organization assigned to this account.', 403);
        }

        if ($user->role === 'sub-admin' && !$user->is_active) {
            return $this->error('Your account is inactive. Please contact the administrator.', 403);
        }

        $token = $user->createToken('admin_token')->plainTextToken;
        $parts = explode('|', $token);

        return $this->success([
            'user'       => $this->profile($user),
            'token'      => end($parts),
            'token_type' => 'Bearer',
        ], 'Login successful.');
    }

    /**
     * GET /api/v1/admin/me
     */
    public function me()
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole(self::ADMIN_ROLES)) return $err;

        return $this->success($this->profile($user), 'Admin profile fetched.');
    }

    /**
     * GET /api/v1/admin/dashboard
     *
     * Headline counts for the admin home (Phase 0 shell).
     */
    public function dashboard()
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole(self::ADMIN_ROLES)) return $err;

        $orgId = $user->organization_id;
        $now   = now();

        $feeMonth = (float) FeePayment::where('organization_id', $orgId)
            ->whereMonth('payment_date', $now->month)
            ->whereYear('payment_date', $now->year)
            ->sum('amount');

        return $this->success([
            'students'                  => StudentDetail::where('organization_id', $orgId)->count(),
            'teachers'                  => TeacherDetail::where('organization_id', $orgId)->count(),
            'fees_collected_total'      => (float) FeePayment::where('organization_id', $orgId)->sum('amount'),
            'fees_collected_this_month' => round($feeMonth, 2),
        ], 'Admin dashboard fetched.');
    }

    private function profile(User $user): array
    {
        $org = Organization::find($user->organization_id);

        return [
            'id'           => $user->id,
            'name'         => $user->name,
            'email'        => $user->email,
            'role'         => $user->role,
            'image'        => $user->image,
            'organization' => $org ? [
                'id'          => $org->id,
                'name'        => $org->name,
                'logo'        => $org->logo,
                'school_code' => $org->school_code,
            ] : null,
        ];
    }
}
