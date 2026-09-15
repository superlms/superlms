<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Admin\Announcement;
use App\Services\ResponseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AnnouncementController extends Controller
{
    protected ResponseService $responseService;

    public function __construct(ResponseService $responseService)
    {
        $this->responseService = $responseService;
    }

    public function announcementList()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->responseService->errorResponse(
                    'Authentication required',
                    401
                );
            }

            // Get announcements for the user's organization from last 30 days
            $query = Announcement::with(['user', 'organization'])
                ->where('organization_id', $user->organization_id)
                ->where('created_at', '>=', now()->subDays(30))
                ->orderBy('created_at', 'desc');

            // Audience scoping by the logged-in user's role:
            //   teacher  → sees 'teacher' + 'all' (both)
            //   student  → sees 'user' + 'all' (both)
            //   admin/others → sees everything
            $allowed = $this->allowedTypesForUser($user);
            $query->whereIn('type', $allowed);
            $this->scopeToClass($query, $user);

            // Optional explicit narrowing within the allowed set (for app tabs).
            // Accepts friendly aliases: both→all, student→user.
            if (request()->filled('type')) {
                $type = $this->normalizeType((string) request()->input('type'));
                if (in_array($type, $allowed, true)) {
                    $query->where('type', $type);
                }
            }

            // Get the last 30 announcements
            $rows = $query->limit(30)->get();

            // The ones this user has opened — the app's unread dot.
            $readIds = DB::table('announcement_reads')
                ->where('user_id', $user->id)
                ->whereIn('announcement_id', $rows->pluck('id'))
                ->pluck('announcement_id')
                ->flip();

            $announcements = $rows
                ->map(function ($announcement) use ($readIds) {
                    $announcementData = $announcement->toArray();

                    // Add creator details if user exists. Avatar falls back to
                    // the organization logo when the user has no personal image.
                    // Both `image` and `logo` are stored as full S3 URLs.
                    if ($announcement->user) {
                        $announcementData['creator_name'] = $announcement->user->name;
                        $announcementData['creator_email'] = $announcement->user->email;
                        $announcementData['creator_avatar'] = $announcement->user->image
                            ?: ($announcement->organization->logo ?? null);
                    } else {
                        $announcementData['creator_name'] = 'Unknown';
                        $announcementData['creator_email'] = null;
                        $announcementData['creator_avatar'] = $announcement->organization->logo ?? null;
                    }
                    // The school's name, shown under "Posted By" in the app.
                    $announcementData['organization_name'] = $announcement->organization->name ?? null;

                    // Add full URLs for files
                    $announcementData['image_url'] = $announcement->announcement_image
                        ? Storage::disk('s3')->url($announcement->announcement_image)
                        : null;

                    $announcementData['pdf_url'] = $announcement->announcement_pdf
                        ? Storage::disk('s3')->url($announcement->announcement_pdf)
                        : null;

                    $announcementData['is_read'] = $readIds->has($announcement->id);

                    return $announcementData;
                });

            return $this->responseService->success(
                $announcements,
                'Announcement list retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->responseService->errorResponse(
                'An error occurred: ' . $e->getMessage(),
                500
            );
        }
    }

    public function getAnnouncement($id)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->responseService->errorResponse(
                    'Authentication required',
                    401
                );
            }

            $announcement = Announcement::where('organization_id', $user->organization_id)
                ->where('id', $id)
                ->first();

            if (!$announcement) {
                return $this->responseService->errorResponse(
                    'Announcement not found or you dont have access',
                    404
                );
            }

            // Check if user has access to this announcement type
            $allowedTypes = $this->allowedTypesForUser($user);
            if (!in_array($announcement->type, $allowedTypes)) {
                return $this->responseService->errorResponse(
                    'You dont have access to this announcement',
                    403
                );
            }

            // …and, for a student, that it isn't aimed at another class.
            if ($user->role === 'user' && $announcement->standard_id) {
                $standardId = \App\Models\Student\StudentDetail::where('user_id', $user->id)->value('standard_id');
                if ((int) $announcement->standard_id !== (int) $standardId) {
                    return $this->responseService->errorResponse(
                        'You dont have access to this announcement',
                        403
                    );
                }
            }

            $announcementData = $announcement->toArray();

            // Add creator details
            // Avatar falls back to the organization logo when the user has no
            // personal image. Both `image` and `logo` are stored as full S3 URLs.
            if ($announcement->user) {
                $announcementData['creator_name'] = $announcement->user->name;
                $announcementData['creator_email'] = $announcement->user->email;
                $announcementData['creator_avatar'] = $announcement->user->image
                    ?: ($announcement->organization->logo ?? null);
            } else {
                $announcementData['creator_name'] = 'Unknown';
                $announcementData['creator_email'] = null;
                $announcementData['creator_avatar'] = $announcement->organization->logo ?? null;
            }
            // The school's name, shown under "Posted By" in the app.
            $announcementData['organization_name'] = $announcement->organization->name ?? null;

            // Add full URLs for files
            $announcementData['image_url'] = $announcement->announcement_image
                ? Storage::disk('s3')->url($announcement->announcement_image)
                : null;

            $announcementData['pdf_url'] = $announcement->announcement_pdf
                ? Storage::disk('s3')->url($announcement->announcement_pdf)
                : null;

            return $this->responseService->success(
                $announcementData,
                'Announcement retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->responseService->errorResponse(
                'An error occurred: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Record that the user has opened these announcements, so they stay read
     * after the app is reinstalled or used on another phone.
     */
    public function markRead(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->responseService->errorResponse(
                    'Authentication required',
                    401
                );
            }

            $validated = $request->validate([
                'ids'   => 'required|array|max:500',
                'ids.*' => 'integer',
            ]);

            // Only this school's announcements.
            $ids = Announcement::where('organization_id', $user->organization_id)
                ->whereIn('id', $validated['ids'])
                ->pluck('id');

            $now = now();
            DB::table('announcement_reads')->insertOrIgnore(
                $ids->map(fn ($id) => [
                    'announcement_id' => $id,
                    'user_id'         => $user->id,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ])->all()
            );

            return $this->responseService->success(
                ['marked' => $ids->count()],
                'Announcements marked as read'
            );
        } catch (Exception $e) {
            return $this->responseService->errorResponse(
                'An error occurred: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * The announcement audience types a user is allowed to see, based on role.
     * Teachers see teacher-targeted + both; students see student-targeted + both;
     * admins (and any other role) see everything.
     *
     * @return array<int,string>
     */
    /**
     * A student announcement can be aimed at one class. A student sees the
     * school-wide ones (standard_id null) plus the ones for their own class;
     * everyone else sees the lot.
     */
    private function scopeToClass($query, $user): void
    {
        if ($user->role !== 'user') {
            return;
        }

        $standardId = \App\Models\Student\StudentDetail::where('user_id', $user->id)->value('standard_id');

        $query->where(function ($q) use ($standardId) {
            $q->whereNull('standard_id');
            if ($standardId) {
                $q->orWhere('standard_id', $standardId);
            }
        });
    }

    private function allowedTypesForUser($user): array
    {
        return match ($user->role) {
            'teacher' => ['all', 'teacher'],
            'user'    => ['all', 'user'],
            default   => ['all', 'user', 'teacher'],
        };
    }

    /**
     * Normalize an incoming `type` filter to a stored value.
     * Accepts app-friendly aliases (both→all, student→user).
     */
    private function normalizeType(string $type): string
    {
        return match (strtolower(trim($type))) {
            'both', 'all'     => 'all',
            'student', 'user' => 'user',
            'teacher'         => 'teacher',
            default           => strtolower(trim($type)),
        };
    }
}
