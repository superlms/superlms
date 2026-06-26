<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class ResponseService
{
    /**
     * Success Response
     * 
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    public function success($data = null, string $message = '', int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'status_code' => $statusCode,
            'message' => $message,
            'data' => $data,
        ];

        return response()->json($response, $statusCode);
    }

    /**
     * Error Response
     * 
     * @param string $message
     * @param int $statusCode
     * @param mixed $errors
     * @return JsonResponse
     */
    public function error(string $message = '', int $statusCode = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'status_code' => $statusCode,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Authentication Response with Token
     * 
     * @param mixed $user
     * @param string $token
     * @param string $message
     * @return JsonResponse
     */
    public function authResponse($user, string $token, string $message = ''): JsonResponse
    {
        $tokenParts = explode('|', $token);
        $plainTextToken = end($tokenParts);

        return $this->success([
            'user' => $user,
            'token' => $plainTextToken,
            'token_type' => 'Bearer',
        ], $message);
    }

    /**
     * Paginated Response
     * 
     * @param mixed $resourceCollection
     * @param array $pagination
     * @param string $message
     * @return JsonResponse
     */
    public function paginated($resourceCollection, array $pagination, string $message = ''): JsonResponse
    {
        return $this->success([
            'items' => $resourceCollection,
            'pagination' => $pagination
        ], $message);
    }

    /**
     * Unauthenticate Response
     * 
     * @param mixed $resourceCollection
     * @param array $pagination
     * @param string $message
     * @return JsonResponse
     */
    public function unAuthenticate(): JsonResponse
    {
        $responseArray = [
            'status' => false,
            'message' => 'Your are not logged in',
            'status_code' => 403,
        ];
        return response()->json($responseArray, 403);
    }

    /**
     * Return error response with dynamic status code
     * 
     * @param string $message
     * @param int $statusCode
     * @param array $additionalData
     * @return \Illuminate\Http\JsonResponse
     */
    public function errorResponse(string $message, int $statusCode = 400, array $additionalData = [])
    {
        $response = [
            'message' => $message,
            'status' => false
        ];

        if (!empty($additionalData)) {
            $response = array_merge($response, $additionalData);
        }

        return response()->json($response, $statusCode);
    }
}
