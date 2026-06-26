<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Admin\TeacherTimeTable;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TimeTableController extends Controller
{
    protected $responseService;

    public function __construct(ResponseService $responseService)
    {
        $this->responseService = $responseService;
    }

    /**
     * Get teacher timetable with multiple filters
     */
    public function getTimeTable(Request $request)
    {
        try {
            $user = Auth::user();

            // Get teacher detail
            $teacherDetail = TeacherDetail::where('user_id', $user->id)->first();

            if (!$teacherDetail) {
                return $this->responseService->errorResponse(
                    'Teacher details not found',
                    404
                );
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'day_of_week' => 'nullable|integer|min:1|max:7',
                'date' => 'nullable|date_format:Y-m-d',
                'type' => 'nullable|in:today,week,all',
                'limit' => 'nullable|integer|min:1|max:100'
            ]);

            if ($validator->fails()) {
                return $this->responseService->errorResponse(
                    $validator->errors()->first(),
                    422
                );
            }

            // Get parameters
            $dayOfWeek = $request->input('day_of_week');
            $date = $request->input('date');
            $type = $request->input('type', 'week');
            $limit = $request->input('limit', 50);

            // Get all timetables for the teacher
            $allTimeTables = TeacherTimeTable::with([
                'teacher.user',
                'standard',
                'section',
                'subject',
                'todaysArrangement.substituteTeacher.user'
            ])
                ->where('teacher_detail_id', $teacherDetail->id)
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get();

            // Handle different types
            if ($type === 'today') {
                return $this->getTodayTimetable($teacherDetail, $allTimeTables);
            }

            if ($type === 'all') {
                return $this->getAllTimetable($teacherDetail, $allTimeTables, $limit);
            }

            // Handle date parameter
            if ($date) {
                return $this->getTimetableForDate($teacherDetail, $date, $allTimeTables);
            }

            // Handle day_of_week parameter
            if ($dayOfWeek) {
                return $this->getTimetableForDay($teacherDetail, $dayOfWeek, $allTimeTables);
            }

            // Default: get timetable for next 7 days
            return $this->getWeeklyTimetable($teacherDetail, $allTimeTables);
        } catch (\Exception $e) {
            return $this->responseService->errorResponse(
                'Failed to retrieve timetable: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get the weekly timetable for the authenticated STUDENT.
     *
     * Derives the schedule from the teacher timetables that target the
     * student's class (standard + section), grouped by day of week.
     */
    public function studentTimeTable(Request $request)
    {
        try {
            $user = Auth::user();

            $student = StudentDetail::with(['standard', 'section'])
                ->where('user_id', $user->id)
                ->where('organization_id', $user->organization_id)
                ->first();

            if (!$student) {
                return $this->responseService->errorResponse('Student profile not found', 404);
            }

            $rows = TeacherTimeTable::with([
                'teacher.user',
                'standard',
                'section',
                'subject',
                'todaysArrangement.substituteTeacher.user',
            ])
                ->where('organization_id', $user->organization_id)
                ->where('standard_id', $student->standard_id)
                ->where('section_id', $student->section_id)
                ->where('is_active', true)
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get();

            $groupedTimeTables = [];
            for ($day = 1; $day <= 7; $day++) {
                $dayRows = $rows->where('day_of_week', $day)->values();
                if ($dayRows->isEmpty()) {
                    continue;
                }
                $groupedTimeTables[] = [
                    'day_of_week'   => $day,
                    'day_name'      => $this->getDayName($day),
                    'total_classes' => $dayRows->count(),
                    'timetable'     => $dayRows->map(fn($t) => $this->formatTimeTable($t))->values(),
                ];
            }

            $response = [
                'student_id'       => $student->id,
                'standard'         => $student->standard->name ?? 'N/A',
                'standard_id'      => $student->standard_id,
                'section'          => $student->section->name ?? 'N/A',
                'section_id'       => $student->section_id,
                'type'             => 'weekly',
                'total_classes'    => $rows->count(),
                'timetable_by_day' => $groupedTimeTables,
            ];

            return $this->responseService->success(
                $response,
                'Student timetable retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->responseService->errorResponse(
                'Failed to retrieve timetable: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get today's timetable
     */
    private function getTodayTimetable($teacherDetail, $allTimeTables)
    {
        $today = Carbon::today();
        $dayOfWeek = $today->dayOfWeekIso;

        // Filter for today's day
        $todayTimeTables = $allTimeTables->filter(function ($timetable) use ($dayOfWeek) {
            return $timetable->day_of_week == $dayOfWeek;
        });

        $formattedResponse = [
            'teacher_id' => $teacherDetail->id,
            'teacher_name' => $teacherDetail->user->name ?? 'N/A',
            'day_of_week' => $dayOfWeek,
            'day_name' => $this->getDayName($dayOfWeek),
            'date' => $today->format('Y-m-d'),
            'type' => 'today',
            'total_classes' => $todayTimeTables->count(),
            'timetable' => $todayTimeTables->map(function ($timetable) {
                return $this->formatTimeTable($timetable);
            })
        ];

        return $this->responseService->success(
            $formattedResponse,
            'Today\'s timetable retrieved successfully'
        );
    }

    /**
     * Get all timetable records
     */
    private function getAllTimetable($teacherDetail, $allTimeTables, $limit = 50)
    {
        // Apply limit
        $limitedTimeTables = $allTimeTables->take($limit);

        $response = [
            'teacher_id' => $teacherDetail->id,
            'teacher_name' => $teacherDetail->user->name ?? 'N/A',
            'type' => 'all',
            'total_records' => $limitedTimeTables->count(),
            'timetable' => $limitedTimeTables->map(function ($timetable) {
                return $this->formatTimeTable($timetable);
            })
        ];

        return $this->responseService->success(
            $response,
            'Timetable records retrieved successfully'
        );
    }

    /**
     * Get timetable for specific day of week
     */
    private function getTimetableForDay($teacherDetail, $dayOfWeek, $allTimeTables)
    {
        $nextDay = $this->getNextDayOfWeek($dayOfWeek);

        // Filter for specific day
        $dayTimeTables = $allTimeTables->filter(function ($timetable) use ($dayOfWeek) {
            return $timetable->day_of_week == $dayOfWeek;
        });

        $formattedResponse = [
            'teacher_id' => $teacherDetail->id,
            'teacher_name' => $teacherDetail->user->name ?? 'N/A',
            'day_of_week' => $dayOfWeek,
            'day_name' => $this->getDayName($dayOfWeek),
            'date' => $nextDay->format('Y-m-d'),
            'type' => 'specific_day',
            'total_classes' => $dayTimeTables->count(),
            'timetable' => $dayTimeTables->map(function ($timetable) {
                return $this->formatTimeTable($timetable);
            })
        ];

        return $this->responseService->success(
            $formattedResponse,
            'Timetable for ' . $this->getDayName($dayOfWeek) . ' retrieved successfully'
        );
    }

    /**
     * Get timetable for specific date
     */
    private function getTimetableForDate($teacherDetail, $date, $allTimeTables)
    {
        $dateObj = Carbon::parse($date);
        $dayOfWeek = $dateObj->dayOfWeekIso;

        // Filter for specific date's day
        $dateTimeTables = $allTimeTables->filter(function ($timetable) use ($dayOfWeek) {
            return $timetable->day_of_week == $dayOfWeek;
        });

        $formattedResponse = [
            'teacher_id' => $teacherDetail->id,
            'teacher_name' => $teacherDetail->user->name ?? 'N/A',
            'day_of_week' => $dayOfWeek,
            'day_name' => $this->getDayName($dayOfWeek),
            'date' => $dateObj->format('Y-m-d'),
            'type' => 'specific_date',
            'total_classes' => $dateTimeTables->count(),
            'timetable' => $dateTimeTables->map(function ($timetable) {
                return $this->formatTimeTable($timetable);
            })
        ];

        return $this->responseService->success(
            $formattedResponse,
            'Timetable for ' . $dateObj->format('Y-m-d') . ' retrieved successfully'
        );
    }

    /**
     * Get weekly timetable (next 7 days)
     */
    private function getWeeklyTimetable($teacherDetail, $allTimeTables)
    {
        $today = Carbon::today();
        $endDate = $today->copy()->addDays(7);

        // Group by day of week for next 7 days
        $groupedTimeTables = [];

        for ($day = 1; $day <= 7; $day++) {
            $date = $this->getNextDayOfWeek($day);

            // Filter timetables for this day
            $dayTimeTables = $allTimeTables->filter(function ($timetable) use ($day) {
                return $timetable->day_of_week == $day;
            });

            if ($dayTimeTables->isNotEmpty()) {
                $groupedTimeTables[] = [
                    'day_of_week' => $day,
                    'day_name' => $this->getDayName($day),
                    'date' => $date->format('Y-m-d'),
                    'total_classes' => $dayTimeTables->count(),
                    'timetable' => $dayTimeTables->map(function ($timetable) {
                        return $this->formatTimeTable($timetable);
                    })
                ];
            }
        }

        $response = [
            'teacher_id' => $teacherDetail->id,
            'teacher_name' => $teacherDetail->user->name ?? 'N/A',
            'week_start' => $today->format('Y-m-d'),
            'week_end' => $endDate->format('Y-m-d'),
            'type' => 'weekly',
            'total_classes' => $allTimeTables->count(),
            'timetable_by_day' => $groupedTimeTables
        ];

        return $this->responseService->success(
            $response,
            'Timetable for next 7 days retrieved successfully'
        );
    }

    /**
     * Format timetable record
     */
    private function formatTimeTable($timetable)
    {
        // Check if there's a substitute arrangement for today
        $hasSubstitute = !is_null($timetable->todaysArrangement);
        $substituteDetails = null;

        if ($hasSubstitute && $timetable->todaysArrangement->substituteTeacher) {
            $substituteDetails = [
                'substitute_teacher_id' => $timetable->todaysArrangement->substitute_teacher_id,
                'substitute_teacher_name' => $timetable->todaysArrangement->substituteTeacher->user->name ?? 'N/A',
                'reason' => $timetable->todaysArrangement->reason
            ];
        }

        return [
            'id' => $timetable->id,
            'period_id' => $timetable->id,
            'standard' => $timetable->standard->name ?? 'N/A',
            'standard_id' => $timetable->standard_id,
            'section' => $timetable->section->name ?? 'N/A',
            'section_id' => $timetable->section_id,
            'subject' => $timetable->subject->name ?? 'N/A',
            'subject_id' => $timetable->subject_id,
            'teacher' => $timetable->teacher->user->name ?? 'N/A',
            'teacher_id' => $timetable->teacher_detail_id,
            'day_of_week' => $timetable->day_of_week,
            'day_name' => $this->getDayName($timetable->day_of_week),
            'start_time' => $timetable->start_time,
            'end_time' => $timetable->end_time,
            'time_slot' => date('h:i A', strtotime($timetable->start_time)) . ' - ' . date('h:i A', strtotime($timetable->end_time)),
            'has_substitute' => $hasSubstitute,
            'substitute_details' => $substituteDetails,
            'assigned_by' => $timetable->assignedBy->name ?? 'N/A',
            'assigned_by_id' => $timetable->assigned_by,
            'is_active' => (bool) $timetable->is_active,
            'created_at' => $timetable->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $timetable->updated_at->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Get the next occurrence of a specific day of week
     */
    private function getNextDayOfWeek($dayOfWeek)
    {
        $today = Carbon::today();
        $currentDayOfWeek = $today->dayOfWeekIso;

        if ($currentDayOfWeek <= $dayOfWeek) {
            return $today->addDays($dayOfWeek - $currentDayOfWeek);
        } else {
            return $today->addDays(7 - ($currentDayOfWeek - $dayOfWeek));
        }
    }

    /**
     * Get day name from day number
     */
    private function getDayName($dayOfWeek)
    {
        $days = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday'
        ];

        return $days[$dayOfWeek] ?? 'Unknown';
    }
}
