<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FirebaseNotificationService;
use App\Services\ResponseService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SendNotificationController extends Controller
{
    protected $firebaseService;
    protected $responseService;

    public function __construct(
        FirebaseNotificationService $firebaseService,
        ResponseService $responseService
    ) {
        $this->firebaseService = $firebaseService;
        $this->responseService = $responseService;
    }

    public function sendToMe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'body' => 'required|string',
            'data' => 'nullable'
        ]);

        if ($validator->fails()) {
            return $this->responseService->errorResponse(
                implode(', ', $validator->errors()->all()),
                400
            );
        }

        try {
            $data = $this->parseNotificationData($request->input('data', []));
            
            $success = $this->firebaseService->sendToUser(
                Auth::user(),
                $request->input('title'),
                $request->input('body'),
                $data
            );

            return $this->responseService->success(
                ['success' => $success],
                $success ? 'Notification sent' : 'Failed to send notification'
            );

        } catch (\InvalidArgumentException $e) {
            return $this->responseService->errorResponse(
                'Invalid data format: ' . $e->getMessage(),
                400
            );
        } catch (Exception $e) {
            return $this->responseService->errorResponse(
                'Failed to send notification: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Register / upsert this device's FCM token for the logged-in user.
     * Called by the app on every login and on token refresh.
     * Body: token (required), platform (android|ios, optional).
     */
    public function registerDeviceToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token'    => 'required|string',
            'platform' => 'nullable|in:android,ios',
        ]);

        if ($validator->fails()) {
            return $this->responseService->errorResponse(
                implode(', ', $validator->errors()->all()),
                400
            );
        }

        try {
            $this->firebaseService->saveToken(
                Auth::user(),
                $request->input('token'),
                $request->input('platform')
            );

            return $this->responseService->success(null, 'Device token registered');
        } catch (Exception $e) {
            return $this->responseService->errorResponse(
                'Failed to register device token: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Remove this device's FCM token (called on logout).
     * Body: token (required).
     */
    public function removeDeviceToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->responseService->errorResponse(
                implode(', ', $validator->errors()->all()),
                400
            );
        }

        try {
            $this->firebaseService->removeToken(Auth::user(), $request->input('token'));

            return $this->responseService->success(null, 'Device token removed');
        } catch (Exception $e) {
            return $this->responseService->errorResponse(
                'Failed to remove device token: ' . $e->getMessage(),
                500
            );
        }
    }

    public function saveFcmToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->responseService->errorResponse(
                implode(', ', $validator->errors()->all()),
                400
            );
        }

        try {
            $user = Auth::user();
            $success = $this->firebaseService->saveFcmToken($user, $request->fcm_token);

            return $this->responseService->success(
                null,
                $success ? 'FCM token saved successfully' : 'FCM token already exists'
            );

        } catch (Exception $e) {
            return $this->responseService->errorResponse(
                'Failed to save FCM token: ' . $e->getMessage(),
                500
            );
        }
    }

    protected function parseNotificationData($data): array
    {
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Invalid JSON data format');
            }
            return $decoded;
        }

        return (array)$data;
    }
}