<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TeacherProfileResource;
use App\Http\Resources\UserResource;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class TeacherController extends Controller
{
    protected ResponseService $responseService;

    public function __construct(ResponseService $responseService)
    {
        $this->responseService = $responseService;
    }

    public function teacherLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->responseService->errorResponse(
                implode(', ', $validator->errors()->all()),
                400
            );
        }

        try {
            $user = User::where('email', $request->email)->where('role', 'teacher')->first();

            if (!$user) {
                return $this->responseService->errorResponse(
                    'No teacher account found with this email address.',
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

    public function teacherProfile(Request $request)
    {
        try {
            $user = Auth::user();

            $teacherDetail = TeacherDetail::with([
                'user',
                'assignedSubjects.subject',
                'assignedSubjects.standard',
                'assignedSubjects.section',
                'teacherSections.section.standard',
                'assignedClasses.standard',
                'assignedClasses.section',
                'organization'
            ])
                ->where('user_id', $user->id)
                ->first();

            if (!$teacherDetail) {
                return $this->responseService->errorResponse(
                    'Teacher profile details not found',
                    404
                );
            }

            return $this->responseService->success(
                new TeacherProfileResource($teacherDetail),
                'Teacher profile retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->responseService->errorResponse(
                'Failed to retrieve teacher profile: ' . $e->getMessage(),
                500
            );
        }
    }
}
