<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Base controller for all v1 API controllers.
 *
 * Provides:
 *  - Auto-injected ResponseService (no boilerplate in each controller)
 *  - Shorthand helpers: success(), error(), paginated()
 *  - authUser()  — returns authenticated user (or 401 response)
 *  - orgId()     — returns authenticated user's organization_id
 *  - validateWith() — validates a request and returns error response on failure
 */
abstract class ApiController extends Controller
{
    protected ResponseService $response;

    public function __construct(ResponseService $response)
    {
        $this->response = $response;
    }

    // ── Response shortcuts ────────────────────────────────────────────────────

    protected function success(mixed $data = null, string $message = '', int $status = 200): JsonResponse
    {
        return $this->response->success($data, $message, $status);
    }

    protected function error(string $message, int $status = 400, mixed $errors = null): JsonResponse
    {
        return $this->response->error($message, $status, $errors);
    }

    protected function paginated(mixed $collection, array $pagination, string $message = ''): JsonResponse
    {
        return $this->response->paginated($collection, $pagination, $message);
    }

    // ── Auth helpers ──────────────────────────────────────────────────────────

    /**
     * Returns the authenticated user, or a 401 JSON response if not authenticated.
     * Usage:  [$user, $err] = $this->authUser(); if ($err) return $err;
     */
    protected function authUser(): array
    {
        $user = Auth::user();
        if (!$user) {
            return [null, $this->error('Authentication required.', 401)];
        }
        return [$user, null];
    }

    /**
     * Returns the authenticated user's organization_id.
     */
    protected function orgId(): ?int
    {
        return Auth::user()?->organization_id;
    }

    /**
     * Ensures the authenticated user has the given role(s).
     * Usage:  $err = $this->requireRole('user'); if ($err) return $err;
     *
     * @param  string|array $roles
     */
    protected function requireRole(string|array $roles): ?JsonResponse
    {
        $user = Auth::user();
        $roles = (array) $roles;

        if (!$user || !in_array($user->role, $roles, true)) {
            return $this->error('Access denied for your role.', 403);
        }
        return null;
    }

    // ── Validation helper ─────────────────────────────────────────────────────

    /**
     * Validates the request. Returns null on pass, or a 422 error response on fail.
     * Usage:  $err = $this->validateWith($request, [...rules...]); if ($err) return $err;
     */
    protected function validateWith(Request $request, array $rules, array $messages = []): ?JsonResponse
    {
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return $this->error(
                implode(' ', $validator->errors()->all()),
                422,
                $validator->errors()->toArray()
            );
        }
        return null;
    }

    // ── Pagination helper ─────────────────────────────────────────────────────

    /**
     * Builds a standard pagination meta array from a LengthAwarePaginator.
     */
    protected function paginationMeta(\Illuminate\Pagination\LengthAwarePaginator $paginator): array
    {
        return [
            'total'        => $paginator->total(),
            'per_page'     => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'from'         => $paginator->firstItem(),
            'to'           => $paginator->lastItem(),
        ];
    }
}
