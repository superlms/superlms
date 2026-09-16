<?php

namespace App\Http\Controllers\v1;

use App\Models\Organization;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use App\Services\OtpMailService;
use App\Support\AdminAppOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SwitchAccountController extends ApiController
{
    /**
     * POST /api/v1/switch-account/add
     *
     * Instagram-style "Add Account" flow. The app stores { token, account }
     * locally; the user can switch between stored accounts without re-login,
     * and can remove any one of them.
     *
     * Body (preferred, unified):
     *   - identifier: string (required) — admission number (student) OR email (any other role)
     *   - password:   string (required)
     *
     * The role is auto-detected from the identifier — the app no longer asks the
     * user to pick a user type.
     *
     * Body (legacy, still accepted):
     *   - admission_number: string  (with login_type=student)
     *   - email:            string  (with login_type=teacher)
     *   - login_type:       'student' | 'teacher'
     *   - password:         string
     *
     * Returns: { account: <snapshot>, token, token_type }
     *   where snapshot includes user_type ('student'|'teacher'|'admin'|'accounts').
     */
    public function add(Request $request)
    {
        // ── Normalize input (unified `identifier` OR legacy fields) ─────
        $identifier = trim((string) $request->input('identifier', ''));
        $loginType  = $request->input('login_type');
        $admission  = $request->input('admission_number');
        $email      = $request->input('email');

        if ($identifier === '') {
            // Fall back to legacy fields
            $identifier = $loginType === 'teacher' ? (string) $email : (string) $admission;
            $identifier = trim($identifier);
        }

        $validationErr = $this->validateWith($request, [
            'password' => 'required|string',
        ]);
        if ($validationErr) return $validationErr;

        if ($identifier === '') {
            return $this->error('Please provide an admission number or email.', 422);
        }

        // ── Resolve user (auto-detect: email = any staff/teacher role, else student) ──
        $isEmail = (bool) filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $user    = null;

        if ($isEmail) {
            $user = User::where('email', $identifier)
                ->whereIn('role', ['teacher', 'admin', 'sub-admin', 'accounts'])
                ->first();
            if (!$user) {
                return $this->error('No account found with this email address.', 401);
            }
        } else {
            $studentDetail = StudentDetail::where('admission_no', $identifier)->first();
            if (!$studentDetail) {
                return $this->error('No student account found with this admission number.', 401);
            }
            $user = $studentDetail->user()->where('role', 'user')->first();
            if (!$user) {
                return $this->error('No valid student account for this admission number.', 401);
            }
        }

        if (!Hash::check($request->password, $user->password)) {
            return $this->error('The provided password is incorrect.', 401);
        }
        if (!$user->is_active && $user->role !== 'admin') {
            return $this->error('This account has been deactivated.', 403);
        }

        // ── A school admin is added only after the code mailed to them, as at
        //    login: the reply carries { otp_required, user_id, otp_token … }
        //    for POST /switch-account/add/verify-otp. ──
        if (AdminAppOtp::required($user)) {
            if (!$request->boolean('otp_supported')) {
                return $this->error(AdminAppOtp::UPDATE_APP_MESSAGE, 426);
            }
            if ($refusal = AdminAppOtp::refusal($user)) {
                return $this->error($refusal, 403);
            }

            try {
                return $this->success(
                    AdminAppOtp::challenge($user, 'switch'),
                    'We sent a code to your email address.'
                );
            } catch (\RuntimeException $e) {
                return $this->error($e->getMessage(), OtpMailService::lockedUntil($user) ? 429 : 503);
            }
        }

        return $this->added($user);
    }

    /**
     * POST /api/v1/switch-account/add/verify-otp
     *
     * A school admin's account, added once the mailed code is entered.
     * Body: user_id, otp_token, otp. Returns what /switch-account/add returns
     * for everyone else.
     */
    public function verifyAddOtp(Request $request)
    {
        if ($err = $this->validateWith($request, [
            'user_id'   => 'required|integer',
            'otp_token' => 'required|string|max:100',
            'otp'       => 'required|digits:6',
        ])) return $err;

        $user = User::whereIn('role', ['admin', 'sub-admin'])->find($request->user_id);
        if (!$user) {
            return $this->error(AdminAppOtp::EXPIRED_MESSAGE, 401);
        }

        try {
            AdminAppOtp::verify($user, 'switch', (string) $request->otp_token, (string) $request->otp);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 401);
        }

        if ($refusal = AdminAppOtp::refusal($user)) {
            return $this->error($refusal, 403);
        }

        return $this->added($user);
    }

    /**
     * A fresh token for this account (each added account has its own token;
     * removing one does not log out the others).
     */
    private function added(User $user)
    {
        $tokenName = $user->role . '_switch_' . now()->timestamp;
        $token     = $user->createToken($tokenName)->plainTextToken;
        $snapshot  = $this->buildSnapshot($user);

        return $this->success(
            [
                'account'    => $snapshot,
                'token'      => explode('|', $token)[1] ?? $token,
                'token_type' => 'Bearer',
            ],
            'Account added successfully.'
        );
    }

    /**
     * GET /api/v1/switch-account/me
     *
     * Returns a compact "card" snapshot for the currently authenticated user.
     * The app calls this periodically to refresh locally-stored snapshots
     * (so the switcher shows the latest name / image / class).
     */
    public function me()
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        return $this->success(
            $this->buildSnapshot($user),
            'Profile snapshot fetched successfully.'
        );
    }

    /**
     * POST /api/v1/switch-account/remove
     *
     * Revokes the **current** access token (the one in the Authorization header).
     * Use case: user removes an account from the device's switcher list.
     * The app sends this with the token-to-remove in Authorization, server
     * revokes only that token (other linked accounts keep working).
     */
    public function remove()
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        $currentToken = $user->currentAccessToken();
        if ($currentToken) {
            $currentToken->delete();
        }

        return $this->success(
            ['user_id' => $user->id],
            'Account removed successfully. Token revoked.'
        );
    }

    /**
     * GET /api/v1/switch-account/schools
     *
     * Auto-discovery: returns all accounts sharing the current user's email + role
     * across organizations. Useful for users enrolled in multiple schools with the
     * same email.
     */
    public function schools()
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        $accounts = User::with('organization:id,name,logo,status')
            ->where('email', $user->email)
            ->where('role', $user->role)
            ->where('is_active', true)
            ->get()
            ->map(fn($u) => [
                'user_id'         => $u->id,
                'organization_id' => $u->organization_id,
                'is_current'      => $u->id === $user->id,
                'school'          => $u->organization ? [
                    'id'     => $u->organization->id,
                    'name'   => $u->organization->name,
                    'logo'   => $u->organization->logo,
                    'active' => (bool) $u->organization->status,
                ] : null,
            ])
            ->filter(fn($a) => $a['school'] !== null)
            ->values();

        return $this->success($accounts, 'Accounts fetched successfully.');
    }

    /**
     * POST /api/v1/switch-account/switch
     *
     * Revokes the current token, issues a new one for the same email+role in a
     * different organization. Used for the auto-discovery flow (above).
     *
     * Body: { "organization_id": 5 }
     */
    public function switch(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        $validationErr = $this->validateWith($request, [
            'organization_id' => 'required|integer|exists:organizations,id',
        ]);
        if ($validationErr) return $validationErr;

        $targetOrgId = (int) $request->organization_id;

        if ($user->organization_id === $targetOrgId) {
            return $this->error('You are already logged in to this school.', 400);
        }

        $targetUser = User::where('email', $user->email)
            ->where('role', $user->role)
            ->where('organization_id', $targetOrgId)
            ->where('is_active', true)
            ->first();

        if (!$targetUser) {
            return $this->error('No account found for your email in the selected school.', 404);
        }

        $user->currentAccessToken()->delete();
        $token = $targetUser->createToken('auth_token')->plainTextToken;

        return $this->response->authResponse(
            [
                'id'              => $targetUser->id,
                'name'            => $targetUser->name,
                'email'           => $targetUser->email,
                'role'            => $targetUser->role,
                'organization_id' => $targetUser->organization_id,
                'image'           => $targetUser->image,
            ],
            $token,
            'Switched to ' . (Organization::find($targetOrgId)?->name ?? 'school') . ' successfully.'
        );
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    /**
     * Compact profile card for the switcher UI:
     * { user_id, name, email, role, image, organization{id,name,logo}, class_info{...}|null }
     */
    protected function buildSnapshot(User $user): array
    {
        $organization = $user->organization;

        $classInfo = null;
        $displayName = $user->name;
        $displayImage = $user->image;

        if ($user->role === 'user') {
            $student = StudentDetail::with(['standard:id,name,code', 'section:id,name'])
                ->where('user_id', $user->id)
                ->first();

            if ($student) {
                $classInfo = [
                    'admission_no'  => $student->admission_no,
                    'roll_no'       => $student->roll_no,
                    'standard_id'   => $student->standard_id,
                    'standard_name' => $student->standard->name ?? null,
                    'section_id'    => $student->section_id,
                    'section_name'  => $student->section->name ?? null,
                    'class_display' => trim(
                        ($student->standard->name ?? '') .
                        ($student->section ? ' - ' . $student->section->name : '')
                    ) ?: null,
                ];
                $displayName  = $student->full_name ?? $user->name;
                $displayImage = $student->image ?? $user->image;
            }
        } elseif ($user->role === 'teacher') {
            $teacher = TeacherDetail::where('user_id', $user->id)->first();
            if ($teacher) {
                $classInfo = [
                    'employee_id'   => $teacher->employee_id,
                    'qualification' => $teacher->qualification,
                    'class_display' => 'Teacher' . ($teacher->employee_id ? ' · ' . $teacher->employee_id : ''),
                ];
            }
        }

        // Friendly account type for the switcher card.
        $userType = match ($user->role) {
            'teacher'            => 'teacher',
            'admin', 'sub-admin' => 'admin',
            'accounts'           => 'accounts',
            default              => 'student',
        };
        $roleLabel = match ($user->role) {
            'user'      => 'Student',
            'sub-admin' => 'Sub-admin',
            'accounts'  => 'Accounts',
            default     => ucfirst($user->role),
        };

        return [
            'user_id'      => $user->id,
            'name'         => $displayName,
            'username'     => $displayName,
            'email'        => $user->email,
            'role'         => $user->role,
            'role_label'   => $roleLabel,
            // Friendly account type: 'student' | 'teacher' | 'admin' | 'accounts'
            'user_type'    => $userType,
            'image'        => $displayImage,
            'organization' => $organization ? [
                'id'   => $organization->id,
                'name' => $organization->name,
                'logo' => $organization->logo,
            ] : null,
            'class_info'   => $classInfo,
        ];
    }
}
