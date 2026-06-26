<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\Announcement;
use App\Models\Admin\Exam;
use App\Models\Admin\ExamCopy;
use App\Models\Admin\HomeWork;
use App\Models\Admin\TeacherTimeTable;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentAttendance;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use App\Models\Teacher\TeacherSubject;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Aggregated home-screen + analytics data for the student and teacher apps.
 *
 *   GET /api/v1/student/dashboard   → student home + analytics
 *   GET /api/v1/teacher/dashboard   → teacher home + analytics
 *
 * Each endpoint composes the same data the existing per-feature endpoints
 * expose (attendance, marks, exams, homework) into a single call so the home
 * screen needs one request and one loading state.
 */
class DashboardController extends ApiController
{
    // ── Student ────────────────────────────────────────────────────────────────
    public function studentDashboard(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        $student = StudentDetail::with(['standard:id,name', 'section:id,name'])
            ->where('user_id', $user->id)
            ->first();
        if (!$student) {
            return $this->error('Student profile not found.', 404);
        }

        $orgId = $student->organization_id;

        return $this->success([
            'profile'     => [
                'name'         => $student->full_name ?? $user->name,
                'standard'     => $student->standard->name ?? null,
                'section'      => $student->section->name ?? null,
                'roll_no'      => $student->roll_no,
                'admission_no' => $student->admission_no,
            ],
            'attendance'  => $this->studentAttendance($student, $orgId),
            'performance' => $this->studentPerformance($student->id, $orgId),
            'exams'       => ['upcoming' => $this->upcomingExams($orgId)],
            'homework'    => $this->studentHomework($student, $orgId),
            'notices'     => $this->notices($orgId, ['user', 'all']),
        ], 'Student dashboard fetched successfully.');
    }

    private function studentAttendance(StudentDetail $student, int $orgId): array
    {
        $month = now()->format('Y-m');
        $start = Carbon::parse($month . '-01')->startOfMonth();
        $end   = (clone $start)->endOfMonth();
        $daysInMonth = $end->day;

        $records = StudentAttendance::where('student_detail_id', $student->id)
            ->where('organization_id', $orgId)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->mapWithKeys(fn($r) => [Carbon::parse($r->attendance_date)->toDateString() => (int) $r->status]);

        $present = 0; $absent = 0; $working = 0;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = $start->copy()->day($d)->toDateString();
            if ($records->has($date)) {
                $working++;
                $records->get($date) === 1 ? $present++ : $absent++;
            }
        }

        // Last 7 days, oldest → newest, for the "this week" strip.
        $week = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->copy()->subDays($i);
            $ds   = $date->toDateString();
            $status = $records->has($ds)
                ? ($records->get($ds) === 1 ? 'present' : 'absent')
                : 'holiday';
            $week[] = ['label' => substr($date->format('D'), 0, 1), 'date' => $ds, 'status' => $status];
        }

        return [
            'month'              => $month,
            'total_days'         => $daysInMonth,
            'working_days'       => $working,
            'present_days'       => $present,
            'absent_days'        => $absent,
            'leave_days'         => 0,
            'present_percentage' => $working > 0 ? round($present / $working * 100, 2) : 0,
            'week'               => $week,
        ];
    }

    private function studentPerformance(int $studentDetailId, int $orgId): array
    {
        $base = fn() => ExamCopy::where('student_detail_id', $studentDetailId)
            ->where('organization_id', $orgId)
            ->whereNotNull('marks_obtained')
            ->whereNotNull('max_marks');

        $totals = $base()->selectRaw('SUM(marks_obtained) as o, SUM(max_marks) as m')->first();
        $obt = (float) ($totals->o ?? 0);
        $max = (float) ($totals->m ?? 0);

        $subjectWise = $base()
            ->with('subject:id,name')
            ->selectRaw('subject_id, SUM(marks_obtained) as o, SUM(max_marks) as m')
            ->groupBy('subject_id')
            ->get()
            ->map(fn($r) => [
                'subject_name' => $r->subject?->name,
                'percentage'   => $r->m > 0 ? (int) round($r->o / $r->m * 100) : 0,
            ])
            ->filter(fn($r) => $r['subject_name'] !== null)
            ->values();

        $trend = $base()
            ->with('exam:id,exam_name')
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn($e) => [
                'exam_name'  => $e->exam?->exam_name,
                'percentage' => $e->max_marks > 0 ? (int) round($e->marks_obtained / $e->max_marks * 100) : 0,
            ])
            ->reverse()
            ->values();

        return [
            'overall_percentage' => $max > 0 ? (int) round($obt / $max * 100) : 0,
            'total_obtained'     => $obt,
            'total_max'          => $max,
            'subject_wise'       => $subjectWise,
            'trend'              => $trend,
        ];
    }

    private function studentHomework(StudentDetail $student, int $orgId): array
    {
        $query = fn() => HomeWork::where('organization_id', $orgId)
            ->where('standard_id', $student->standard_id)
            ->where('section_id', $student->section_id);

        $recent = $query()
            ->with('subject:id,name')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($h) => [
                'id'           => $h->id,
                'title'        => $h->title,
                'subject_name' => $h->subject?->name,
                'date'         => $h->created_at?->format('d M'),
            ]);

        return ['total' => $query()->count(), 'recent' => $recent->values()];
    }

    // ── Teacher ────────────────────────────────────────────────────────────────
    public function teacherDashboard(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        $teacher = TeacherDetail::with('user')->where('user_id', $user->id)->first();
        if (!$teacher) {
            return $this->error('Teacher profile not found.', 404);
        }

        $orgId = $user->organization_id;

        $today   = $this->teacherToday($teacher->id);
        $classes = $this->teacherClassAttendance($teacher->id, $orgId);
        $exams   = $this->upcomingExams($orgId);

        $hwQuery = fn() => HomeWork::where('organization_id', $orgId)->where('user_id', $user->id);
        $recentHw = $hwQuery()
            ->with(['subject:id,name', 'standard:id,name', 'section:id,name'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($h) => [
                'id'           => $h->id,
                'title'        => $h->title,
                'subject_name' => $h->subject?->name,
                'class'        => trim(($h->standard->name ?? '') . ' ' . ($h->section->name ?? '')),
                'date'         => $h->created_at?->format('d M'),
            ]);

        return $this->success([
            'profile'          => [
                'name'        => $teacher->user->name ?? $user->name,
                'employee_id' => $teacher->employee_id,
            ],
            'today_classes'    => $today,
            'totals'           => [
                'total_students'      => $classes['total_students'],
                'total_classes_today' => count($today),
                'homework_count'      => $hwQuery()->count(),
                'upcoming_exams'      => $exams->count(),
            ],
            'class_attendance' => [
                'overall_percentage' => $classes['overall_percentage'],
                'by_class'           => $classes['by_class'],
            ],
            'homework'         => ['recent' => $recentHw->values()],
            'exams'            => ['upcoming' => $exams],
            'notices'          => $this->notices($orgId, ['teacher', 'all']),
        ], 'Teacher dashboard fetched successfully.');
    }

    private function teacherToday(int $teacherDetailId): array
    {
        return TeacherTimeTable::with(['subject:id,name', 'standard:id,name', 'section:id,name'])
            ->where('teacher_detail_id', $teacherDetailId)
            ->where('day_of_week', now()->dayOfWeekIso)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get()
            ->map(fn($p) => [
                'subject' => $p->subject->name ?? 'Free Period',
                'class'   => trim(($p->standard->name ?? '') . ' ' . ($p->section->name ?? '')),
                'time'    => $p->start_time ? Carbon::parse($p->start_time)->format('H:i') : null,
                'room'    => $p->room_number,
            ])
            ->values()
            ->all();
    }

    /**
     * Per-class roster + today's present count for every (standard, section) the
     * teacher is assigned to (timetable + assigned subjects).
     */
    private function teacherClassAttendance(int $teacherDetailId, int $orgId): array
    {
        $pairs = collect()
            ->merge(TeacherTimeTable::where('teacher_detail_id', $teacherDetailId)->get(['standard_id', 'section_id']))
            ->merge(TeacherSubject::where('teacher_detail_id', $teacherDetailId)->get(['standard_id', 'section_id']))
            ->filter(fn($r) => $r->standard_id)
            ->map(fn($r) => ['standard_id' => $r->standard_id, 'section_id' => $r->section_id])
            ->unique(fn($r) => $r['standard_id'] . '-' . ($r['section_id'] ?? '0'))
            ->values();

        $todayDate = now()->toDateString();
        $byClass = [];
        $totalStudents = 0;
        $totalPresent = 0;

        foreach ($pairs as $p) {
            $rosterQ = StudentDetail::where('organization_id', $orgId)
                ->where('standard_id', $p['standard_id']);
            if ($p['section_id']) {
                $rosterQ->where('section_id', $p['section_id']);
            }
            $studentIds = $rosterQ->pluck('id');
            $total = $studentIds->count();
            if ($total === 0) continue;

            $present = StudentAttendance::where('organization_id', $orgId)
                ->whereIn('student_detail_id', $studentIds)
                ->whereDate('attendance_date', $todayDate)
                ->where('status', 1)
                ->count();

            $std = Standard::find($p['standard_id']);
            $sec = $p['section_id'] ? Section::find($p['section_id']) : null;
            $className = trim(($std->name ?? 'Class') . ' ' . ($sec->name ?? ''));

            $byClass[] = [
                'class'      => $className,
                'present'    => $present,
                'total'      => $total,
                'percentage' => $total > 0 ? (int) round($present / $total * 100) : 0,
            ];

            $totalStudents += $total;
            $totalPresent += $present;
        }

        return [
            'total_students'     => $totalStudents,
            'overall_percentage' => $totalStudents > 0 ? (int) round($totalPresent / $totalStudents * 100) : 0,
            'by_class'           => $byClass,
        ];
    }

    // ── Shared ─────────────────────────────────────────────────────────────────
    private function upcomingExams(int $orgId)
    {
        return Exam::where('organization_id', $orgId)
            ->where('is_published', true)
            ->whereDate('end_date', '>=', now()->toDateString())
            ->orderBy('start_date')
            ->limit(5)
            ->get()
            ->map(function ($e) {
                $now = now();
                $status = match (true) {
                    $e->start_date > $now => 'upcoming',
                    $e->end_date   < $now => 'completed',
                    default               => 'ongoing',
                };
                return [
                    'id'            => $e->id,
                    'name'          => $e->exam_name,
                    'type'          => $e->exam_type,
                    'academic_year' => $e->academic_year,
                    'date_range'    => $this->dateRange($e->start_date, $e->end_date),
                    'status'        => $status,
                ];
            })
            ->values();
    }

    private function notices(int $orgId, array $types)
    {
        return Announcement::where('organization_id', $orgId)
            ->whereIn('type', $types)
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn($a) => [
                'id'    => $a->id,
                'title' => $a->announcement_name,
                'type'  => $a->type,
                'time'  => $a->created_at?->diffForHumans(),
            ])
            ->values();
    }

    private function dateRange($start, $end): string
    {
        $s = $start ? Carbon::parse($start)->format('d M') : null;
        $e = $end ? Carbon::parse($end)->format('d M Y') : null;
        if ($s && $e) return "$s - $e";
        return $s ?? $e ?? 'TBA';
    }
}
