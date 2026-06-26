<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Student\{StudentAttendance, StudentDetail};
use App\Models\Teacher\{AssignTeacherStandard, TeacherAttendance, TeacherDetail};
use App\Services\AppPushNotifier;
use App\Services\ResponseService;
use App\Services\StudentAttendanceService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    protected $responseService;
    protected $attendanceService;

    /** Teachers may mark/edit attendance for the last 3 working days (Sundays excluded). */
    private const MARKABLE_DAYS = 3;

    public function __construct(StudentAttendanceService $attendanceService, ResponseService $responseService)
    {
        $this->responseService = $responseService;
        $this->attendanceService = $attendanceService;
    }

    /**
     * The dates a teacher may currently mark: the last MARKABLE_DAYS working days
     * (Sundays are auto-holidays and excluded), most recent first.
     */
    private function markableDates(): array
    {
        $dates  = [];
        $cursor = now()->startOfDay();
        while (count($dates) < self::MARKABLE_DAYS) {
            if ($cursor->dayOfWeek !== Carbon::SUNDAY) {
                $dates[] = $cursor->toDateString();
            }
            $cursor->subDay();
        }
        return $dates;
    }

    /**
     * Validate that $date is one of the markable working days.
     * Returns an error string when not allowed, or null when allowed.
     */
    private function outsideEditWindow(string $date): ?string
    {
        $target = Carbon::parse($date);
        if ($target->dayOfWeek === Carbon::SUNDAY) {
            return 'Sunday is a holiday — attendance cannot be marked.';
        }
        if ($target->isFuture() && !$target->isToday()) {
            return 'You cannot mark attendance for a future date.';
        }
        if (!in_array($target->toDateString(), $this->markableDates(), true)) {
            return 'Attendance can only be marked for the last ' . self::MARKABLE_DAYS . ' working days (Sundays excluded).';
        }
        return null;
    }

    /**
     * Get students for attendance marking
     */
    public function getStudentsForAttendance(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->responseService->errorResponse('Authentication required', 401);
            }

            // Get teacher detail with eager loading
            $teacherDetail = TeacherDetail::with(['assignedClasses.standard', 'assignedClasses.section'])
                ->where('user_id', $user->id)
                ->first();

            if (!$teacherDetail) {
                return $this->responseService->errorResponse('Teacher profile not found', 404);
            }

            // Check if teacher has any assigned classes
            if ($teacherDetail->assignedClasses->isEmpty()) {
                return $this->responseService->errorResponse(
                    'No classes assigned to this teacher. Please contact administrator.',
                    404
                );
            }

            $date = $request->date ?? now()->toDateString();

            // Get students for each assigned class
            $studentsByClass = collect();

            foreach ($teacherDetail->assignedClasses as $assignment) {
                $students = StudentDetail::with(['user', 'standard', 'section'])
                    ->where('organization_id', $user->organization_id)
                    ->where('standard_id', $assignment->standard_id);

                if ($assignment->section_id) {
                    $students->where('section_id', $assignment->section_id);
                }

                $students = $students->orderBy('roll_no')->get();

                // Get attendance for each student for the date
                $studentIds = $students->pluck('id')->toArray();
                $attendances = StudentAttendance::whereIn('student_detail_id', $studentIds)
                    ->where('attendance_date', $date)
                    ->get()
                    ->keyBy('student_detail_id');

                $classStudents = $students->map(function ($student) use ($attendances, $assignment) {
                    $attendance = $attendances->get($student->id);

                    return [
                        'student_id' => $student->id,
                        'user_id' => $student->user_id,
                        'roll_no' => $student->roll_no,
                        'full_name' => $student->full_name,
                        'photo' => $student->user->image ?? null,
                        'standard_id' => $student->standard_id,
                        'section_id' => $student->section_id,
                        'standard_name' => $student->standard->name ?? null,
                        'section_name' => $student->section->name ?? null,
                        'attendance' => $attendance ? [
                            'attendance_id' => $attendance->id,
                            'status' => $this->getAttendanceStatus($attendance->status),
                            'db_status' => $attendance->status,
                            'remarks' => $attendance->remarks,
                            'marked_by' => $attendance->marked_by,
                            'marked_at' => $attendance->created_at
                        ] : [
                            'status' => 'not_marked',
                            'db_status' => null,
                            'remarks' => null
                        ]
                    ];
                });

                $studentsByClass->push([
                    'assignment_id' => $assignment->id,
                    'class_info' => [
                        'standard_id' => $assignment->standard_id,
                        'standard_name' => $assignment->standard->name ?? null,
                        'section_id' => $assignment->section_id,
                        'section_name' => $assignment->section->name ?? null,
                        'class_display' => ($assignment->standard->name ?? '') .
                            ($assignment->section ? ' - ' . $assignment->section->name : '')
                    ],
                    'total_students' => $classStudents->count(),
                    'students' => $classStudents
                ]);
            }

            $response = [
                'teacher_info' => [
                    'teacher_id' => $teacherDetail->id,
                    'name' => $teacherDetail->user->name,
                    'employee_id' => $teacherDetail->employee_id
                ],
                'date' => $date,
                'classes' => $studentsByClass,
                'summary' => [
                    'total_classes' => $studentsByClass->count(),
                    'total_students' => $studentsByClass->sum('total_students'),
                    'attendance_date' => $date
                ]
            ];

            return $this->responseService->success(
                $response,
                'Students retrieved successfully for attendance marking'
            );
        } catch (\Exception $e) {
            return $this->responseService->errorResponse(
                'Failed to retrieve students: ' . $e->getMessage(),
                500
            );
        }
    }

    private function getAttendanceStatus($statusCode)
    {
        $statusMap = [
            0 => 'absent',
            1 => 'present',
            2 => 'late',
            3 => 'half_day',
            4 => 'holiday'
        ];

        return $statusMap[$statusCode] ?? 'not_marked';
    }

    /**
     * Bulk submit attendance
     */
    public function bulkSubmitAttendance(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->responseService->errorResponse('Authentication required', 401);
            }

            $validated = $request->validate([
                'attendance_date' => 'required|date',
                'attendances' => 'required|array|min:1',
                'attendances.*.student_detail_id' => 'required|exists:student_details,id',
                // Status code: 0=absent, 1=present, 2=late, 3=half_day, 4=holiday.
                // Legacy booleans (true=present/false=absent) are also accepted.
                'attendances.*.status' => 'required',
                'attendances.*.remarks' => 'nullable|string'
            ]);

            // Normalise each status into its tinyint code (handles bool / "true" / int).
            foreach ($validated['attendances'] as $i => $a) {
                $s = $a['status'];
                $code = ($s === true || $s === 'true')
                    ? 1
                    : (($s === false || $s === 'false') ? 0 : (int) $s);
                if (!in_array($code, [0, 1, 2, 3, 4], true)) {
                    return $this->responseService->errorResponse('Invalid attendance status.', 422);
                }
                $validated['attendances'][$i]['status'] = $code;
            }

            if ($windowError = $this->outsideEditWindow($validated['attendance_date'])) {
                return $this->responseService->errorResponse($windowError, 403);
            }

            $results = $this->attendanceService->bulkSubmitAttendance(
                $validated,
                $user->id,
                $user->organization_id
            );

            // Push a notification to each student whose attendance was marked.
            $userIdByDetail = StudentDetail::whereIn(
                'id',
                collect($validated['attendances'])->pluck('student_detail_id')
            )->pluck('user_id', 'id');

            $notifyRows = [];
            foreach ($validated['attendances'] as $a) {
                $uid = $userIdByDetail[$a['student_detail_id']] ?? null;
                if ($uid) {
                    $notifyRows[] = ['user_id' => $uid, 'status' => $a['status']];
                }
            }
            app(AppPushNotifier::class)->attendanceMarked($notifyRows);

            // Get summary for the day
            $firstStudent = StudentDetail::find($validated['attendances'][0]['student_detail_id']);
            $summary = $this->attendanceService->getDailyAttendanceSummary(
                $user->organization_id,
                $firstStudent->standard_id,
                $firstStudent->section_id,
                $validated['attendance_date']
            );

            return $this->responseService->success([
                'processed_count' => count($results),
                'summary' => $summary,
                'details' => $results
            ], 'Attendance submitted successfully');
        } catch (Exception $e) {
            return $this->responseService->errorResponse(
                'An error occurred: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Mark a whole class+section as holiday for a date.
     * Holiday is stored as status code 4 on every student's attendance row.
     */
    public function markHoliday(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->responseService->errorResponse('Authentication required', 401);
            }

            $validated = $request->validate([
                'date'        => 'required|date',
                'standard_id' => 'required|exists:standards,id',
                'section_id'  => 'nullable|exists:sections,id',
            ]);

            if ($windowError = $this->outsideEditWindow($validated['date'])) {
                return $this->responseService->errorResponse($windowError, 403);
            }

            $teacherDetail = TeacherDetail::where('user_id', $user->id)->first();
            if (!$teacherDetail) {
                return $this->responseService->errorResponse('Teacher profile not found', 404);
            }

            // Teacher must be assigned to this class/section.
            $isAssigned = AssignTeacherStandard::where('teacher_detail_id', $teacherDetail->id)
                ->where('organization_id', $user->organization_id)
                ->where('standard_id', $validated['standard_id'])
                ->when(!empty($validated['section_id']), fn($q) => $q->where('section_id', $validated['section_id']))
                ->exists();

            if (!$isAssigned) {
                return $this->responseService->errorResponse('You are not assigned to this class', 403);
            }

            $students = StudentDetail::where('organization_id', $user->organization_id)
                ->where('standard_id', $validated['standard_id'])
                ->when(!empty($validated['section_id']), fn($q) => $q->where('section_id', $validated['section_id']))
                ->get();

            if ($students->isEmpty()) {
                return $this->responseService->errorResponse('No students found for this class', 404);
            }

            $date  = Carbon::parse($validated['date'])->toDateString();
            $count = 0;

            DB::transaction(function () use ($students, $date, $user, &$count) {
                foreach ($students as $student) {
                    StudentAttendance::updateOrCreate(
                        [
                            'student_detail_id' => $student->id,
                            'attendance_date'   => $date,
                        ],
                        [
                            'user_id'         => $student->user_id,
                            'organization_id' => $user->organization_id,
                            'status'          => 4, // holiday
                            'remarks'         => 'Holiday',
                            'marked_by'       => $user->id,
                        ]
                    );
                    $count++;
                }
            });

            return $this->responseService->success([
                'date'            => $date,
                'standard_id'     => (int) $validated['standard_id'],
                'section_id'      => isset($validated['section_id']) ? (int) $validated['section_id'] : null,
                'marked_students' => $count,
            ], 'Holiday marked successfully');
        } catch (Exception $e) {
            return $this->responseService->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get attendance summary for a class
     */
    public function getAttendanceSummary(Request $request)
    {
        try {
            $user = Auth::user();
            $validated = $request->validate([
                'date' => 'required|date',
                'standard_id' => 'sometimes|exists:standards,id',
                'section_id' => 'sometimes|exists:sections,id'
            ]);

            $teacherDetail = TeacherDetail::where('user_id', $user->id)->first();

            if (!$teacherDetail) {
                return $this->responseService->errorResponse('Teacher profile not found', 404);
            }

            $teacherAssignments = AssignTeacherStandard::where('teacher_detail_id', $teacherDetail->id)
                ->where('organization_id', $user->organization_id);

            // Agar specific class request ki hai
            if (isset($validated['standard_id']) && isset($validated['section_id'])) {
                // Verify teacher is assigned to this class
                $isAssigned = $teacherAssignments->where('standard_id', $validated['standard_id'])
                    ->where('section_id', $validated['section_id'])
                    ->exists();

                if (!$isAssigned) {
                    return $this->responseService->errorResponse('You are not assigned to this class', 403);
                }

                $summary = $this->attendanceService->getDailyAttendanceSummary(
                    $user->organization_id,
                    $validated['standard_id'],
                    $validated['section_id'],
                    $validated['date']
                );

                return $this->responseService->success([$summary], 'Attendance summary retrieved successfully');
            }

            $assignedClasses = $teacherAssignments->get();
            $summaries = [];

            foreach ($assignedClasses as $class) {
                $summary = $this->attendanceService->getDailyAttendanceSummary(
                    $user->organization_id,
                    $class->standard_id,
                    $class->section_id,
                    $validated['date']
                );

                $summaries[] = [
                    'standard_id' => $class->standard_id,
                    'section_id' => $class->section_id,
                    'standard_name' => $class->standard->name ?? null,
                    'section_name' => $class->section->name ?? null,
                    ...$summary
                ];
            }

            return $this->responseService->success($summaries, 'All classes attendance summary retrieved successfully');
        } catch (Exception $e) {
            return $this->responseService->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    public function teacherAttendance(Request $request)
    {
        try {
            $user = Auth::user();

            // Get teacher detail
            $teacherDetail = TeacherDetail::where('user_id', $user->id)->first();

            if (!$teacherDetail) {
                return $this->responseService->errorResponse(
                    'Teacher profile not found',
                    404
                );
            }

            // Validate request parameters
            $validator = Validator::make($request->all(), [
                'month' => 'nullable|integer|min:1|max:12',
                'year' => 'nullable|integer|min:2000|max:' . date('Y'),
                'date' => 'nullable|date',
                'status' => 'nullable|in:present,absent,late,half_day',
                'per_page' => 'nullable|integer|min:5|max:100',
                'page' => 'nullable|integer|min:1'
            ]);

            if ($validator->fails()) {
                return $this->responseService->errorResponse(
                    'Validation failed',
                    422,
                );
            }

            // Build query
            $query = TeacherAttendance::with(['recordedBy'])
                ->where('teacher_detail_id', $teacherDetail->id)
                ->where('organization_id', $user->organization_id)
                ->orderBy('attendance_date', 'desc');

            // Apply filters
            if ($request->filled('date')) {
                $query->whereDate('attendance_date', $request->date);
            }

            if ($request->filled('month') && $request->filled('year')) {
                $query->whereMonth('attendance_date', $request->month)
                    ->whereYear('attendance_date', $request->year);
            } elseif ($request->filled('month')) {
                $query->whereMonth('attendance_date', $request->month)
                    ->whereYear('attendance_date', date('Y'));
            } elseif ($request->filled('year')) {
                $query->whereYear('attendance_date', $request->year);
            }

            if ($request->filled('status')) {
                $statusMap = [
                    'present' => 1,
                    'absent' => 0,
                    'late' => 2,
                    'half_day' => 3
                ];
                $query->where('status', $statusMap[$request->status] ?? 1);
            }

            // Get paginated results
            $perPage = $request->per_page ?? 20;
            $attendance = $query->paginate($perPage);

            // Format response
            $formattedData = $attendance->map(function ($record) {
                return [
                    'id' => $record->id,
                    'attendance_date' => $record->attendance_date->format('Y-m-d'),
                    'day_name' => $record->attendance_date->format('l'),
                    'status' => $record->getStatusLabelAttribute(),
                    'status_code' => $record->status,
                    'remarks' => $record->remarks,
                    'marked_by' => $record->recordedBy ? [
                        'id' => $record->recordedBy->id,
                        'name' => $record->recordedBy->name,
                        'role' => $record->recordedBy->role
                    ] : null,
                    'marked_at' => $record->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $record->updated_at->format('Y-m-d H:i:s')
                ];
            });

            // Calculate statistics
            $statistics = $this->getTeacherAttendanceStatistics($teacherDetail->id, $user->organization_id);

            // Prepare pagination metadata
            $paginationData = [
                'current_page' => $attendance->currentPage(),
                'last_page' => $attendance->lastPage(),
                'per_page' => $attendance->perPage(),
                'total' => $attendance->total(),
                'from' => $attendance->firstItem(),
                'to' => $attendance->lastItem(),
                'has_more_pages' => $attendance->hasMorePages(),
                'next_page_url' => $attendance->nextPageUrl(),
                'prev_page_url' => $attendance->previousPageUrl()
            ];

            $response = [
                'teacher_info' => [
                    'teacher_id' => $teacherDetail->id,
                    'name' => $teacherDetail->user->name,
                    'employee_id' => $teacherDetail->employee_id,
                    'joining_date' => $teacherDetail->date_of_joining
                ],
                'attendance_records' => $formattedData,
                'statistics' => $statistics,
                'pagination' => $paginationData,
                'filters' => [
                    'applied_filters' => $request->only(['month', 'year', 'date', 'status']),
                    'available_filters' => [
                        'status_options' => ['present', 'absent', 'late', 'half_day'],
                        'month_range' => [
                            '1' => 'January',
                            '2' => 'February',
                            '3' => 'March',
                            '4' => 'April',
                            '5' => 'May',
                            '6' => 'June',
                            '7' => 'July',
                            '8' => 'August',
                            '9' => 'September',
                            '10' => 'October',
                            '11' => 'November',
                            '12' => 'December'
                        ]
                    ]
                ]
            ];

            return $this->responseService->success(
                $response,
                'Teacher attendance records retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->responseService->errorResponse(
                'An error occurred: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get teacher attendance statistics
     */
    private function getTeacherAttendanceStatistics($teacherId, $organizationId)
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // Current month statistics
        $currentMonthStats = TeacherAttendance::where('teacher_detail_id', $teacherId)
            ->where('organization_id', $organizationId)
            ->whereMonth('attendance_date', $currentMonth)
            ->whereYear('attendance_date', $currentYear)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Overall statistics
        $overallStats = TeacherAttendance::where('teacher_detail_id', $teacherId)
            ->where('organization_id', $organizationId)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Last 30 days
        $last30Days = TeacherAttendance::where('teacher_detail_id', $teacherId)
            ->where('organization_id', $organizationId)
            ->where('attendance_date', '>=', now()->subDays(30))
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Calculate percentages
        $totalCurrentMonth = array_sum($currentMonthStats->toArray());
        $totalOverall = array_sum($overallStats->toArray());

        return [
            'current_month' => [
                'present' => $currentMonthStats[1] ?? 0,
                'absent' => $currentMonthStats[0] ?? 0,
                'late' => $currentMonthStats[2] ?? 0,
                'half_day' => $currentMonthStats[3] ?? 0,
                'total' => $totalCurrentMonth,
                'present_percentage' => $totalCurrentMonth > 0 ? round(($currentMonthStats[1] ?? 0) / $totalCurrentMonth * 100, 2) : 0
            ],
            'last_30_days' => [
                'present' => $last30Days[1] ?? 0,
                'absent' => $last30Days[0] ?? 0,
                'late' => $last30Days[2] ?? 0,
                'half_day' => $last30Days[3] ?? 0,
                'total' => array_sum($last30Days->toArray())
            ],
            'overall' => [
                'present' => $overallStats[1] ?? 0,
                'absent' => $overallStats[0] ?? 0,
                'late' => $overallStats[2] ?? 0,
                'half_day' => $overallStats[3] ?? 0,
                'total' => $totalOverall,
                'present_percentage' => $totalOverall > 0 ? round(($overallStats[1] ?? 0) / $totalOverall * 100, 2) : 0
            ]
        ];
    }

    public function todaysAttendance(Request $request)
    {
        try {
            $user = Auth::user();

            $teacherDetail = TeacherDetail::where('user_id', $user->id)->first();

            if (!$teacherDetail) {
                return $this->responseService->errorResponse('Teacher not found', 404);
            }

            $today = now()->toDateString();

            $attendance = TeacherAttendance::with(['recordedBy'])
                ->where('teacher_detail_id', $teacherDetail->id)
                ->where('organization_id', $user->organization_id)
                ->whereDate('attendance_date', $today)
                ->first();

            $response = [
                'date' => $today,
                'day_name' => now()->format('l'),
                'attendance' => $attendance ? [
                    'status' => $attendance->getStatusLabelAttribute(),
                    'status_code' => $attendance->status,
                    'remarks' => $attendance->remarks,
                    'marked_by' => $attendance->recordedBy ? [
                        'name' => $attendance->recordedBy->name,
                        'role' => $attendance->recordedBy->role
                    ] : null,
                    'marked_at' => $attendance->created_at->format('h:i A'),
                    'can_edit' => $this->canEditAttendance($attendance)
                ] : [
                    'status' => 'not_marked',
                    'status_code' => null,
                    'remarks' => null,
                    'marked_by' => null,
                    'marked_at' => null,
                    'can_edit' => true
                ]
            ];

            return $this->responseService->success(
                $response,
                'Today\'s attendance retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->responseService->errorResponse(
                'An error occurred: ' . $e->getMessage(),
                500
            );
        }
    }

    private function canEditAttendance($attendance)
    {
        return $attendance->created_at->diffInHours(now()) < 24;
    }

    /**
     * GET /api/v1/attendance/my
     *
     * Self attendance view for BOTH students and teachers.
     * Optional ?month=YYYY-MM (defaults to current month).
     * Returns day-wise records + monthly summary.
     * Days with no record are treated as holidays.
     */
    public function myAttendance(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->responseService->errorResponse('Authentication required', 401);
            }

            $month = $request->get('month', now()->format('Y-m'));
            if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                $month = now()->format('Y-m');
            }
            $start = \Carbon\Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();
            $end   = (clone $start)->endOfMonth();
            $daysInMonth = $end->day;

            if ($user->role === 'teacher') {
                $teacher = TeacherDetail::where('user_id', $user->id)->first();
                if (!$teacher) {
                    return $this->responseService->errorResponse('Teacher profile not found', 404);
                }
                $records = TeacherAttendance::where('teacher_detail_id', $teacher->id)
                    ->where('organization_id', $user->organization_id)
                    ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
                    ->get()
                    ->mapWithKeys(fn($r) => [\Carbon\Carbon::parse($r->attendance_date)->toDateString() => (int) $r->status]);
            } else {
                $student = StudentDetail::where('user_id', $user->id)->first();
                if (!$student) {
                    return $this->responseService->errorResponse('Student profile not found', 404);
                }
                $records = StudentAttendance::where('student_detail_id', $student->id)
                    ->where('organization_id', $user->organization_id)
                    ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
                    ->get()
                    ->mapWithKeys(fn($r) => [\Carbon\Carbon::parse($r->attendance_date)->toDateString() => (int) $r->status]);
            }

            $today = now()->toDateString();

            $days = [];
            $present = 0; $absent = 0; $working = 0; $holiday = 0; $notMarked = 0;
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $dayCarbon = $start->copy()->day($d);
                $date = $dayCarbon->toDateString();

                if ($dayCarbon->dayOfWeek === \Carbon\Carbon::SUNDAY) {
                    // Sundays are always holidays, regardless of any record.
                    $status = 'holiday';
                    $holiday++;
                } elseif ($date > $today) {
                    // Future days in the (current) month — nothing to show yet.
                    $status = 'upcoming';
                } elseif ($records->has($date)) {
                    $code = $records->get($date);
                    if ($code === 4) {
                        $status = 'holiday';
                        $holiday++;
                    } elseif ($code === 1) {
                        $status = 'present';
                        $present++;
                        $working++;
                    } else {
                        $status = 'absent';
                        $absent++;
                        $working++;
                    }
                } else {
                    // Past/today date with no record — attendance was never taken.
                    $status = 'not_marked';
                    $notMarked++;
                }

                $days[] = ['date' => $date, 'day' => $d, 'status' => $status];
            }

            return $this->responseService->success([
                'month'   => $month,
                'role'    => $user->role,
                'days'    => $days,
                'summary' => [
                    'total_days'      => $daysInMonth,
                    'working_days'    => $working,
                    'present_days'    => $present,
                    'absent_days'     => $absent,
                    'holiday_days'    => $holiday,
                    'not_marked_days' => $notMarked,
                    'present_percentage' => $working > 0 ? round($present / $working * 100, 2) : 0,
                ],
            ], 'Attendance fetched successfully');
        } catch (Exception $e) {
            return $this->responseService->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }
}
