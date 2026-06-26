<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Calendar\TimeTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Services\ResponseService;

class CalendarController extends Controller
{
    protected $responseService;

    public function __construct(ResponseService $responseService)
    {
        $this->responseService = $responseService;
    }

    /**
     * Get calendar events with multiple filters
     */
    public function getEvents(Request $request)
    {
        try {
            $user = Auth::user();
            $organizationId = $user->organization_id;

            // Validate request
            $validator = Validator::make($request->all(), [
                'start_date' => 'nullable|date_format:Y-m-d',
                'end_date' => 'nullable|date_format:Y-m-d',
                'event_type' => 'nullable|in:class,exam,meeting,event,holiday',
                'is_all_day' => 'nullable|boolean',
                'teacher_detail_id' => 'nullable|integer|exists:teacher_details,id',
                'standard_id' => 'nullable|integer|exists:standards,id',
                'section_id' => 'nullable|integer|exists:sections,id',
                'subject_id' => 'nullable|integer|exists:subjects,id',
                'limit' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1'
            ]);

            if ($validator->fails()) {
                return $this->responseService->errorResponse(
                    $validator->errors()->first(),
                    422
                );
            }

            // Get parameters with defaults
            $startDate = $request->input('start_date', date('Y-m-d'));
            $endDate = $request->input('end_date', date('Y-m-d', strtotime('+1 month')));
            $eventType = $request->input('event_type');
            $isAllDay = $request->input('is_all_day');
            $teacherDetailId = $request->input('teacher_detail_id');
            $standardId = $request->input('standard_id');
            $sectionId = $request->input('section_id');
            $subjectId = $request->input('subject_id');
            $limit = $request->input('limit', 50);
            $page = $request->input('page', 1);

            // Build query
            $query = TimeTable::with(['location', 'academic.teacher.user', 'academic.standard', 'academic.section', 'academic.subject'])
                ->where('organization_id', $organizationId)
                ->where('is_cancelled', false);

            // Date range filter
            $query->whereBetween('date', [$startDate, $endDate]);

            // Event type filter
            if ($eventType) {
                $query->where('event_type', $eventType);
            }

            // All day filter
            if (!is_null($isAllDay)) {
                $query->where('is_all_day', $isAllDay);
            }

            // Academic filters
            if ($teacherDetailId || $standardId || $sectionId || $subjectId) {
                $query->whereHas('academic', function ($q) use ($teacherDetailId, $standardId, $sectionId, $subjectId) {
                    if ($teacherDetailId) {
                        $q->where('teacher_detail_id', $teacherDetailId);
                    }
                    if ($standardId) {
                        $q->where('standard_id', $standardId);
                    }
                    if ($sectionId) {
                        $q->where('section_id', $sectionId);
                    }
                    if ($subjectId) {
                        $q->where('subject_id', $subjectId);
                    }
                });
            }

            // Order by date and time
            $query->orderBy('date')
                ->orderBy('start_time')
                ->orderBy('end_time');

            // Paginate results
            $events = $query->paginate($limit, ['*'], 'page', $page);

            // Transform events
            $transformedEvents = $events->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'date' => $event->date->format('Y-m-d'),
                    'start_time' => $event->start_time ? $event->start_time->format('H:i:s') : null,
                    'end_time' => $event->end_time ? $event->end_time->format('H:i:s') : null,
                    'is_all_day' => (bool)$event->is_all_day,
                    'event_type' => $event->event_type,
                    'color' => $event->color,
                    'location' => $event->location ? [
                        'room_number' => $event->location->room_number,
                        'building' => $event->location->building,
                        'location' => $event->location->location,
                    ] : null,
                    'academic_details' => $event->academic ? [
                        'standard' => $event->academic->standard ? [
                            'id' => $event->academic->standard->id,
                            'name' => $event->academic->standard->name,
                        ] : null,
                        'section' => $event->academic->section ? [
                            'id' => $event->academic->section->id,
                            'name' => $event->academic->section->name,
                        ] : null,
                        'subject' => $event->academic->subject ? [
                            'id' => $event->academic->subject->id,
                            'name' => $event->academic->subject->name,
                        ] : null,
                        'teacher' => $event->academic->teacher ? [
                            'id' => $event->academic->teacher->id,
                            'name' => $event->academic->teacher->user->name ?? null,
                        ] : null,
                    ] : null,
                    'created_at' => $event->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $event->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            return $this->responseService->success(
                [
                    'events' => $transformedEvents,
                    'pagination' => [
                        'current_page' => $events->currentPage(),
                        'last_page' => $events->lastPage(),
                        'per_page' => $events->perPage(),
                        'total' => $events->total(),
                    ],
                    'filters' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'event_type' => $eventType,
                    ]
                ],
                'Events retrieved successfully',
                200
            );
        } catch (\Exception $e) {
            return $this->responseService->errorResponse(
                'Failed to retrieve events: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get today's events
     */
    public function getTodayEvents(Request $request)
    {
        try {
            $user = Auth::user();
            $organizationId = $user->organization_id;

            $today = date('Y-m-d');

            // Optional: Add pagination if you expect many events
            $perPage = $request->input('per_page', 20);

            $events = TimeTable::with(['location', 'academic.teacher.user', 'academic.subject'])
                ->where('organization_id', $organizationId)
                ->where('is_cancelled', false)
                ->where('date', $today)
                ->orderBy('start_time')
                ->orderBy('end_time')
                ->paginate($perPage);

            $transformedEvents = $events->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'type' => $event->event_type,
                    'color' => $event->color,
                    'time' => $event->is_all_day ? 'All Day' : ($event->start_time ? $event->start_time->format('h:i A') : '') .
                        ($event->end_time ? ' - ' . $event->end_time->format('h:i A') : ''),
                    'subject' => $event->academic->subject->name ?? null,
                    'teacher' => $event->academic->teacher->user->name ?? null,
                    'location' => $event->location ?
                        ($event->location->room_number ? 'Room ' . $event->location->room_number : '') : null,
                ];
            });

            return $this->responseService->success(
                [
                    'date' => $today,
                    'events' => $transformedEvents,
                    'total' => $events->total(),
                    'current_page' => $events->currentPage(),
                    'per_page' => $events->perPage(),
                    'last_page' => $events->lastPage(),
                ],
                'Today\'s events retrieved successfully',
                200
            );
        } catch (\Exception $e) {
            return $this->responseService->errorResponse(
                'Failed to retrieve today\'s events: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get event by ID
     */
    public function getEvent($id)
    {
        try {
            $user = Auth::user();
            $organizationId = $user->organization_id;

            $event = TimeTable::with([
                'location',
                'academic.teacher.user',
                'academic.standard',
                'academic.section',
                'academic.subject',
                'creator',
                'organization',
            ])
                ->where('organization_id', $organizationId)
                ->find($id);

            if (!$event) {
                return $this->responseService->errorResponse(
                    'Event not found',
                    404
                );
            }

            // Posted By — whoever created the event (admin or user). Legacy
            // events have no created_by, so fall back to the organization's
            // admin so the section never shows "Unknown".
            $creator = $event->creator
                ?? \App\Models\User::where('organization_id', $organizationId)
                    ->where('role', 'admin')
                    ->orderBy('id')
                    ->first();

            $eventData = [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'date' => $event->date->format('Y-m-d'),
                'start_time' => $event->start_time ? $event->start_time->format('H:i:s') : null,
                'end_time' => $event->end_time ? $event->end_time->format('H:i:s') : null,
                'is_all_day' => (bool)$event->is_all_day,
                'event_type' => $event->event_type,
                'color' => $event->color,
                'is_cancelled' => (bool)$event->is_cancelled,
                'cancellation_reason' => $event->cancellation_reason,
                'location' => $event->location ? [
                    'room_number' => $event->location->room_number,
                    'building' => $event->location->building,
                    'location' => $event->location->location,
                    'full_address' => ($event->location->building ? $event->location->building . ', ' : '') .
                        ($event->location->room_number ? 'Room ' . $event->location->room_number . ', ' : '') .
                        ($event->location->location ?: ''),
                ] : null,
                'academic_details' => $event->academic ? [
                    'standard' => $event->academic->standard ? [
                        'id' => $event->academic->standard->id,
                        'name' => $event->academic->standard->name,
                    ] : null,
                    'section' => $event->academic->section ? [
                        'id' => $event->academic->section->id,
                        'name' => $event->academic->section->name,
                    ] : null,
                    'subject' => $event->academic->subject ? [
                        'id' => $event->academic->subject->id,
                        'name' => $event->academic->subject->name,
                    ] : null,
                    'teacher' => $event->academic->teacher ? [
                        'id' => $event->academic->teacher->id,
                        'name' => $event->academic->teacher->user->name ?? null,
                        'email' => $event->academic->teacher->user->email ?? null,
                    ] : null,
                ] : null,
                'timing_display' => $event->is_all_day ?
                    'All Day Event' : ($event->start_time ? $event->start_time->format('h:i A') : '') .
                    ($event->end_time ? ' to ' . $event->end_time->format('h:i A') : ''),
                // Posted By — creator (or org-admin fallback). Avatar falls
                // back to the organization logo. `image` and `logo` are stored
                // as full S3 URLs.
                'creator_name' => $creator->name ?? 'Unknown',
                'creator_email' => $creator->email ?? null,
                'creator_avatar' => ($creator->image ?? null)
                    ?: ($event->organization->logo ?? null),
                'created_at' => $event->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $event->updated_at->format('Y-m-d H:i:s'),
            ];

            return $this->responseService->success(
                $eventData,
                'Event retrieved successfully',
                200
            );
        } catch (\Exception $e) {
            return $this->responseService->errorResponse(
                'Failed to retrieve event: ' . $e->getMessage(),
                500
            );
        }
    }
}
