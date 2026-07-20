<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\UserRequest;
use App\Http\Resources\StudentProfileResource;
use App\Http\Resources\UserResource;
use App\Models\Student\StudentDetail;
use App\Models\User;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    protected ResponseService $responseService;

    public function __construct(ResponseService $responseService)
    {
        $this->responseService = $responseService;
    }

    public function studentLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'admission_number' => 'required|string',
            'password'         => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->responseService->errorResponse(
                implode(', ', $validator->errors()->all()),
                400
            );
        }

        try {
            $studentDetail = StudentDetail::where('admission_no', $request->admission_number)->first();

            if (!$studentDetail) {
                return $this->responseService->errorResponse(
                    'The provided admission number does not exist in our records.',
                    401
                );
            }

            $user = $studentDetail->user()->where('role', 'user')->first();

            if (!$user) {
                return $this->responseService->errorResponse(
                    'No valid student account found for this admission number.',
                    401
                );
            }

            if (!Hash::check($request->password, $user->password)) {
                return $this->responseService->errorResponse(
                    'The provided password is incorrect.',
                    401
                );
            }

            if (!$user->is_active) {
                return $this->responseService->errorResponse(
                    'Your account has been deactivated. Please contact support.',
                    403
                );
            }

            // Per-student fee plan: an unpaid student can't sign in.
            if (\App\Support\FeeAccess::studentLoginBlocked($user)) {
                return $this->responseService->errorResponse(
                    \App\Support\FeeAccess::blockedMessage(),
                    403
                );
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->responseService->authResponse(
                new UserResource($user),
                $token,
                'Login successful'
            );
        } catch (\Exception $e) {
            return $this->responseService->errorResponse(
                'Login failed: ' . $e->getMessage(),
                500
            );
        }
    }

    public function studentProfile(Request $request)
    {
        try {
            $user = Auth::user();

            $studentDetail = StudentDetail::with([
                'user',
                'standard',
                'section',
                'organization',
                'transportations',
            ])->where('user_id', $user->id)->first();

            if (!$studentDetail) {
                return $this->responseService->errorResponse(
                    'Student profile not found',
                    404
                );
            }

            return $this->responseService->success(
                new StudentProfileResource($studentDetail),
                'Student profile retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->responseService->errorResponse(
                'Failed to retrieve student profile: ' . $e->getMessage(),
                500
            );
        }
    }
}
