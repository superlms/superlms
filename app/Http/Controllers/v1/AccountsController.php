<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Admin\TransportFeePayment;
use App\Models\Organization;
use App\Models\Student\StudentDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Accounts-staff entry point for the mobile app (Phase 0).
 *
 * Accounts users are Users with role 'accounts', scoped to an organization.
 * Login is email + password (mirrors the web checks, minus the web OTP step).
 */
class AccountsController extends ApiController
{
    private const ROLE = 'accounts';

    /**
     * POST /api/v1/accounts/login
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

        if ($user->role !== self::ROLE) {
            return $this->error('You do not have accounts access.', 403);
        }

        if (!$user->organization_id) {
            return $this->error('No organization assigned to this account.', 403);
        }

        if (!$user->is_active) {
            return $this->error('Your account is inactive. Please contact the administrator.', 403);
        }

        $token = $user->createToken('accounts_token')->plainTextToken;
        $parts = explode('|', $token);

        return $this->success([
            'user'       => $this->profile($user),
            'token'      => end($parts),
            'token_type' => 'Bearer',
        ], 'Login successful.');
    }

    /**
     * GET /api/v1/accounts/me
     */
    public function me()
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole(self::ROLE)) return $err;

        return $this->success($this->profile($user), 'Accounts profile fetched.');
    }

    /**
     * GET /api/v1/accounts/dashboard
     *
     * Finance headline numbers for the accounts home (Phase 0 shell).
     */
    public function dashboard()
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole(self::ROLE)) return $err;

        $orgId = $user->organization_id;
        $now   = now();

        $collected = (float) FeePayment::where('organization_id', $orgId)->sum('amount');
        $structure = (float) FeeStructure::where('organization_id', $orgId)->where('is_active', true)->sum('amount');

        $today = (float) FeePayment::where('organization_id', $orgId)
            ->whereDate('payment_date', $now->toDateString())->sum('amount');

        $month = (float) FeePayment::where('organization_id', $orgId)
            ->whereMonth('payment_date', $now->month)
            ->whereYear('payment_date', $now->year)
            ->sum('amount');

        $transport = (float) TransportFeePayment::where('organization_id', $orgId)->sum('amount');

        return $this->success([
            'fees_collected_total'      => round($collected, 2),
            'fees_collected_today'      => round($today, 2),
            'fees_collected_this_month' => round($month, 2),
            'transport_collected'       => round($transport, 2),
            'pending'                   => round(max(0, $structure - $collected), 2),
            'students'                  => StudentDetail::where('organization_id', $orgId)->count(),
        ], 'Accounts dashboard fetched.');
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
