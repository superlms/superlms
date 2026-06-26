<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Admin\Library;
use App\Services\ResponseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LibraryController extends Controller
{
    protected $responseService;

    public function __construct(ResponseService $responseService)
    {
        $this->responseService = $responseService;
    }

    /**
     * Get library items list
     */
    public function libraryList()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->responseService->errorResponse(
                    'Authentication required',
                    401
                );
            }

            // Get library items for the user's organization
            $query = Library::where('organization_id', $user->organization_id)
                ->where('availability', 'available') 
                ->orderBy('created_at', 'desc');

            // Filter based on type if provided in request
            if (request()->has('type')) {
                $type = request()->input('type');
                if ($type !== 'all') {
                    $query->where('type', $type);
                }
            }

            // Filter based on category if provided
            if (request()->has('category')) {
                $query->where('category', request()->input('category'));
            }

            // Filter based on search term if provided
            if (request()->has('search')) {
                $searchTerm = request()->input('search');
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('title', 'like', "%$searchTerm%")
                        ->orWhere('author', 'like', "%$searchTerm%")
                        ->orWhere('description', 'like', "%$searchTerm%");
                });
            }

            // Get paginated results (20 per page by default)
            $perPage = request()->has('per_page') ? request()->input('per_page') : 20;
            $libraryItems = $query->paginate($perPage)
                ->through(function ($item) {
                    $itemData = $item->toArray();

                    // Add creator details if user exists
                    if ($item->user) {
                        $itemData['creator_name'] = $item->user->name;
                        $itemData['creator_email'] = $item->user->email;
                    } else {
                        $itemData['creator_name'] = 'Unknown';
                        $itemData['creator_email'] = null;
                    }
                    return $itemData;
                });

            return $this->responseService->success(
                $libraryItems,
                'Library items retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->responseService->errorResponse(
                'An error occurred: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get single library item
     */
    public function getLibraryItem($id)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->responseService->errorResponse(
                    'Authentication required',
                    401
                );
            }

            $libraryItem = Library::where('organization_id', $user->organization_id)
                ->where('id', $id)
                ->where('availability', 'available')
                ->first();

            if (!$libraryItem) {
                return $this->responseService->errorResponse(
                    'Library item not found or you don\'t have access',
                    404
                );
            }

            $libraryData = $libraryItem->toArray();

            // Add creator details
            if ($libraryItem->user) {
                $libraryData['creator_name'] = $libraryItem->user->name;
                $libraryData['creator_email'] = $libraryItem->user->email;
                $libraryData['creator_avatar'] = $libraryItem->user->avatar_url
                    ? Storage::disk('s3')->url($libraryItem->user->avatar_url)
                    : null;
            } else {
                $libraryData['creator_name'] = 'Unknown';
                $libraryData['creator_email'] = null;
                $libraryData['creator_avatar'] = null;
            }

            return $this->responseService->success(
                $libraryData,
                'Library item retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->responseService->errorResponse(
                'An error occurred: ' . $e->getMessage(),
                500
            );
        }
    }
}
