<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\Announcement;
use App\Models\Admin\Exam;
use App\Models\Admin\ExamCopy;
use App\Models\Admin\HomeWork;
use App\Models\Admin\HomeWorkCompletion;
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
            'profile'       => [
                'name'         => $student->full_name ?? $user->name,
                'standard'     => $student->standard->name ?? null,
                'section'      => $student->section->name ?? null,
                'roll_no'      => $student->roll_no,
                'admission_no' => $student->admission_no,
            ],
            'attendance'    => $this->studentAttendance($student, $orgId),
            'performance'   => $this->studentPerformance($student, $orgId),
            'exams'         => ['upcoming' => $this->upcomingExams($orgId)],
            'homework'      => $this->studentHomework($student, $orgId, $user->id),
            'today_classes' => $this->studentToday($student, $orgId),
            'notices'       => $this->notices($orgId, ['user', 'all'], $student->standard_id, true),
        ], 'Student dashboard fetched successfully.');
    }

    /**
     * How one day reads, by the Attendance screen's rules (GET attendance/my):
     * a Sunday is a holiday whatever was recorded, a record of 4 is a holiday,
     * 1 is present and any other record absent; a past day with no record was
     * never marked.
     */
    protected function dayStatus(Carbon $day, ?int $code): string
    {
        if ($day->dayOfWeek === Carbon::SUNDAY) return 'holiday';
        if ($day->toDateString() > now()->toDateString()) return 'upcoming';
        if ($code === null) return 'not_marked';
        if ($code === 4) return 'holiday';
        return $code === 1 ? 'present' : 'absent';
    }

    private function studentAttendance(StudentDetail $student, int $orgId): array
    {
        $month = now()->format('Y-m');
        $start = Carbon::parse($month . '-01')->startOfMonth();
        $end   = (clone $start)->endOfMonth();
        $daysInMonth = $end->day;

        // Six months back for the history, which also covers this month and
        // the last seven days.
        $from = $start->copy()->subMonths(5);
        $records = StudentAttendance::where('student_detail_id', $student->id)
            ->where('organization_id', $orgId)
            ->whereBetween('attendance_date', [$from->toDateString(), $end->toDateString()])
            ->get()
            ->mapWithKeys(fn($r) => [Carbon::parse($r->attendance_date)->toDateString() => (int) $r->status]);

        $count = function (Carbon $first) use ($records) {
            $c = ['present' => 0, 'absent' => 0, 'holiday' => 0, 'not_marked' => 0, 'upcoming' => 0];
            $last = $first->copy()->endOfMonth();
            for ($day = $first->copy(); $day->lte($last); $day->addDay()) {
                $c[$this->dayStatus($day, $records->get($day->toDateString()))]++;
            }
            return $c;
        };

        $c = $count($start);
        $working = $c['present'] + $c['absent'];

        // Last 7 days, oldest → newest, for the "this week" strip.
        $week = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->copy()->subDays($i)->startOfDay();
            $ds   = $date->toDateString();
            $week[] = [
                'label'  => substr($date->format('D'), 0, 1),
                'date'   => $ds,
                'status' => $this->dayStatus($date, $records->get($ds)),
            ];
        }

        // This month and the five before it, oldest first.
        $history = [];
        for ($i = 0; $i < 6; $i++) {
            $first = $from->copy()->addMonths($i);
            $h = $count($first);
            $w = $h['present'] + $h['absent'];
            $history[] = [
                'month'        => $first->format('Y-m'),
                'working_days' => $w,
                'present_days' => $h['present'],
                'percentage'   => $w > 0 ? (int) round($h['present'] / $w * 100) : null,
            ];
        }

        return [
            'month'              => $month,
            'total_days'         => $daysInMonth,
            'working_days'       => $working,
            'present_days'       => $c['present'],
            'absent_days'        => $c['absent'],
            'leave_days'         => 0,
            'holiday_days'       => $c['holiday'],
            'not_marked_days'    => $c['not_marked'],
            'present_percentage' => $working > 0 ? round($c['present'] / $working * 100, 2) : 0,
            'week'               => $week,
            'history'            => $history,
        ];
    }

    /** Everyone in the student's class and section (the student included). */
    protected function classmateIds(StudentDetail $student, int $orgId)
    {
        return StudentDetail::where('organization_id', $orgId)
            ->where('standard_id', $student->standard_id)
            ->when($student->section_id, fn($q) => $q->where('section_id', $student->section_id))
            ->pluck('id');
    }

    /**
     * The class's marks in the exams the student has marks in, as a
     * percentage: overall, per subject and per exam — to set theirs against.
     */
    protected function classAverages(StudentDetail $student, int $orgId, $examIds): array
    {
        $out = ['overall' => null, 'subjects' => [], 'exams' => []];
        if (!$student->standard_id || collect($examIds)->isEmpty()) return $out;

        $rows = ExamCopy::where('organization_id', $orgId)
            ->whereIn('student_detail_id', $this->classmateIds($student, $orgId))
            ->whereIn('exam_id', $examIds)
            ->whereNotNull('marks_obtained')
            ->whereNotNull('max_marks')
            ->selectRaw('exam_id, subject_id, SUM(marks_obtained) as o, SUM(max_marks) as m')
            ->groupBy('exam_id', 'subject_id')
            ->get();

        $pct = fn($o, $m) => $m > 0 ? (int) round($o / $m * 100) : null;
        $out['overall'] = $pct($rows->sum('o'), $rows->sum('m'));
        foreach ($rows->groupBy('subject_id') as $id => $g) $out['subjects'][$id] = $pct($g->sum('o'), $g->sum('m'));
        foreach ($rows->groupBy('exam_id') as $id => $g) $out['exams'][$id] = $pct($g->sum('o'), $g->sum('m'));
        return $out;
    }

    private function studentPerformance(StudentDetail $student, int $orgId): array
    {
        $studentDetailId = $student->id;
        $base = fn() => ExamCopy::where('student_detail_id', $studentDetailId)
            ->where('organization_id', $orgId)
            ->whereNotNull('marks_obtained')
            ->whereNotNull('max_marks');

        $totals = $base()->selectRaw('SUM(marks_obtained) as o, SUM(max_marks) as m')->first();
        $obt = (float) ($totals->o ?? 0);
        $max = (float) ($totals->m ?? 0);

        $class = $this->classAverages($student, $orgId, $base()->distinct()->pluck('exam_id'));

        $subjectWise = $base()
            ->with('subject:id,name')
            ->selectRaw('subject_id, SUM(marks_obtained) as o, SUM(max_marks) as m')
            ->groupBy('subject_id')
            ->get()
            ->map(fn($r) => [
                'subject_name'  => $r->subject?->name,
                'percentage'    => $r->m > 0 ? (int) round($r->o / $r->m * 100) : 0,
                'obtained'      => (float) $r->o,
                'max'           => (float) $r->m,
                'class_average' => $class['subjects'][$r->subject_id] ?? null,
            ])
            ->filter(fn($r) => $r['subject_name'] !== null)
            ->values();

        // Exam by exam — every subject's marks in an exam together, the way the
        // Performance screen totals an exam — the last six, oldest first.
        $byExam = ExamCopy::join('exams', 'exams.id', '=', 'exam_copies.exam_id')
            ->where('exam_copies.student_detail_id', $studentDetailId)
            ->where('exam_copies.organization_id', $orgId)
            ->whereNotNull('exam_copies.marks_obtained')
            ->whereNotNull('exam_copies.max_marks')
            ->selectRaw('exam_copies.exam_id, exams.exam_name, MIN(exams.start_date) as starts, SUM(exam_copies.marks_obtained) as o, SUM(exam_copies.max_marks) as m, COUNT(*) as n')
            ->groupBy('exam_copies.exam_id', 'exams.exam_name')
            ->orderByDesc('starts')
            ->limit(6)
            ->get()
            ->map(fn($r) => [
                'exam_id'       => (int) $r->exam_id,
                'exam_name'     => $r->exam_name,
                'date'          => $r->starts ? Carbon::parse($r->starts)->toDateString() : null,
                'obtained'      => (float) $r->o,
                'max'           => (float) $r->m,
                'percentage'    => $r->m > 0 ? (int) round($r->o / $r->m * 100) : 0,
                'subjects'      => (int) $r->n,
                'class_average' => $class['exams'][$r->exam_id] ?? null,
            ])
            ->reverse()
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
            'class_average'      => $class['overall'],
            'subject_wise'       => $subjectWise,
            'trend'              => $trend,
            'exams'              => $byExam,
        ];
    }

    /** Homework for the student's class, and which of it they have marked done. */
    private function studentHomework(StudentDetail $student, int $orgId, int $userId): array
    {
        $query = fn() => HomeWork::where('organization_id', $orgId)
            ->where('standard_id', $student->standard_id)
            ->where('section_id', $student->section_id);

        $doneIds = HomeWorkCompletion::where('user_id', $userId)
            ->whereIn('home_work_id', $query()->select('id'))
            ->pluck('home_work_id')
            ->flip();

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
                'done'         => $doneIds->has($h->id),
            ]);

        return [
            'total'  => $query()->count(),
            'done'   => $doneIds->count(),
            'recent' => $recent->values(),
        ];
    }

    /** The student's periods today, from their class's timetable. */
    private function studentToday(StudentDetail $student, int $orgId): array
    {
        if (!$student->standard_id) return [];

        return TeacherTimeTable::with(['subject:id,name', 'teacher.user:id,name'])
            ->where('organization_id', $orgId)
            ->where('standard_id', $student->standard_id)
            ->where('section_id', $student->section_id)
            ->where('day_of_week', now()->dayOfWeekIso)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get()
            ->map(fn($p) => [
                'subject'  => $p->subject->name ?? 'Free Period',
                'teacher'  => $p->teacher?->user?->name,
                'time'     => $p->start_time ? Carbon::parse($p->start_time)->format('H:i') : null,
                'end_time' => $p->end_time ? Carbon::parse($p->end_time)->format('H:i') : null,
            ])
            ->values()
            ->all();
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
        $classSize = [];
        $recentHw = $hwQuery()
            ->with(['subject:id,name', 'standard:id,name', 'section:id,name'])
            ->withCount('completions')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($h) use ($orgId, &$classSize) {
                $key = $h->standard_id . '-' . ($h->section_id ?? 0);
                $classSize[$key] ??= StudentDetail::where('organization_id', $orgId)
                    ->where('standard_id', $h->standard_id)
                    ->when($h->section_id, fn($q) => $q->where('section_id', $h->section_id))
                    ->count();
                return [
                    'id'           => $h->id,
                    'title'        => $h->title,
                    'subject_name' => $h->subject?->name,
                    'class'        => trim(($h->standard->name ?? '') . ' ' . ($h->section->name ?? '')),
                    'date'         => $h->created_at?->format('d M'),
                    'done'         => (int) $h->completions_count,
                    'students'     => $classSize[$key],
                ];
            });

        return $this->success([
            'profile'           => [
                'name'        => $teacher->user->name ?? $user->name,
                'employee_id' => $teacher->employee_id,
            ],
            'today_classes'     => $today,
            'totals'            => [
                'total_students'      => $classes['total_students'],
                'total_classes_today' => count($today),
                'homework_count'      => $hwQuery()->count(),
                'upcoming_exams'      => $exams->count(),
            ],
            'class_attendance'  => [
                'overall_percentage' => $classes['overall_percentage'],
                'present'            => $classes['present'],
                'marked'             => $classes['marked'],
                'by_class'           => $classes['by_class'],
                'week'               => $classes['week'],
                'watch'              => $classes['watch'],
            ],
            'class_performance' => $this->teacherClassPerformance($teacher->id, $orgId),
            'homework'          => ['recent' => $recentHw->values()],
            'exams'             => ['upcoming' => $exams],
            'notices'           => $this->notices($orgId, ['teacher', 'all']),
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
                'subject'  => $p->subject->name ?? 'Free Period',
                'class'    => trim(($p->standard->name ?? '') . ' ' . ($p->section->name ?? '')),
                'time'     => $p->start_time ? Carbon::parse($p->start_time)->format('H:i') : null,
                'end_time' => $p->end_time ? Carbon::parse($p->end_time)->format('H:i') : null,
                'room'     => $p->room_number,
            ])
            ->values()
            ->all();
    }

    /** Every (standard, section) the teacher is assigned to — timetable and assigned subjects. */
    protected function teacherClassPairs(int $teacherDetailId)
    {
        return collect()
            ->merge(TeacherTimeTable::where('teacher_detail_id', $teacherDetailId)->get(['standard_id', 'section_id']))
            ->merge(TeacherSubject::where('teacher_detail_id', $teacherDetailId)->get(['standard_id', 'section_id']))
            ->filter(fn($r) => $r->standard_id)
            ->map(fn($r) => ['standard_id' => $r->standard_id, 'section_id' => $r->section_id])
            ->unique(fn($r) => $r['standard_id'] . '-' . ($r['section_id'] ?? '0'))
            ->values();
    }

    protected function className(int $standardId, ?int $sectionId): string
    {
        $std = Standard::find($standardId);
        $sec = $sectionId ? Section::find($sectionId) : null;
        return trim(($std->name ?? 'Class') . ' ' . ($sec->name ?? ''));
    }

    /**
     * The teacher's classes: each one's roster and who is in today (a class
     * whose day is a holiday, or not marked yet, says so), the last seven days
     * across all of them, and the students under three quarters this month.
     * Days are read by the Attendance screen's rules (dayStatus).
     */
    protected function teacherClassAttendance(int $teacherDetailId, int $orgId): array
    {
        $classes = [];
        foreach ($this->teacherClassPairs($teacherDetailId) as $p) {
            $roster = StudentDetail::where('organization_id', $orgId)
                ->where('standard_id', $p['standard_id'])
                ->when($p['section_id'], fn($q) => $q->where('section_id', $p['section_id']))
                ->get(['id', 'full_name', 'roll_no']);
            if ($roster->isEmpty()) continue;
            $classes[] = ['name' => $this->className($p['standard_id'], $p['section_id']), 'roster' => $roster];
        }

        $ids = collect($classes)->flatMap(fn($c) => $c['roster']->pluck('id'))->unique()->values();
        $today = now()->startOfDay();
        $from = $today->copy()->startOfMonth()->min($today->copy()->subDays(6));

        // student id → date → status code
        $records = [];
        if ($ids->isNotEmpty()) {
            StudentAttendance::where('organization_id', $orgId)
                ->whereIn('student_detail_id', $ids)
                ->whereBetween('attendance_date', [$from->toDateString(), $today->toDateString()])
                ->get(['student_detail_id', 'attendance_date', 'status'])
                ->each(function ($r) use (&$records) {
                    $records[$r->student_detail_id][Carbon::parse($r->attendance_date)->toDateString()] = (int) $r->status;
                });
        }
        $status = fn(int $sid, Carbon $day) => $this->dayStatus($day, $records[$sid][$day->toDateString()] ?? null);

        $byClass = [];
        $totalStudents = 0; $totalPresent = 0; $totalMarked = 0;
        foreach ($classes as $c) {
            $n = ['present' => 0, 'absent' => 0, 'holiday' => 0, 'not_marked' => 0, 'upcoming' => 0];
            foreach ($c['roster'] as $st) $n[$status($st->id, $today)]++;
            $total = $c['roster']->count();
            $marked = $n['present'] + $n['absent'];
            $byClass[] = [
                'class'      => $c['name'],
                'present'    => $n['present'],
                'total'      => $total,
                'percentage' => (int) round($n['present'] / $total * 100),
                'absent'     => $n['absent'],
                'marked'     => $marked,
                'holiday'    => $n['holiday'] > 0 && $marked === 0,
            ];
            $totalStudents += $total;
            $totalPresent += $n['present'];
            $totalMarked += $marked;
        }

        // The last seven days across every class, oldest first; null where
        // nothing was marked.
        $week = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = $today->copy()->subDays($i);
            $present = 0; $marked = 0; $holiday = false;
            foreach ($ids as $sid) {
                $s = $status($sid, $day);
                if ($s === 'present') { $present++; $marked++; }
                elseif ($s === 'absent') $marked++;
                elseif ($s === 'holiday') $holiday = true;
            }
            $week[] = [
                'date'       => $day->toDateString(),
                'label'      => substr($day->format('D'), 0, 1),
                'present'    => $present,
                'marked'     => $marked,
                'percentage' => $marked > 0 ? (int) round($present / $marked * 100) : null,
                'holiday'    => $holiday && $marked === 0,
            ];
        }

        // This month, student by student: under 75% with three marked days or more.
        $watch = [];
        $monthStart = $today->copy()->startOfMonth();
        foreach ($classes as $c) {
            foreach ($c['roster'] as $st) {
                $present = 0; $working = 0;
                for ($day = $monthStart->copy(); $day->lte($today); $day->addDay()) {
                    $s = $status($st->id, $day);
                    if ($s === 'present') { $present++; $working++; }
                    elseif ($s === 'absent') $working++;
                }
                if ($working < 3) continue;
                $pct = (int) round($present / $working * 100);
                if ($pct >= 75) continue;
                $watch[$st->id] = [
                    'name'         => $st->full_name,
                    'class'        => $c['name'],
                    'roll_no'      => $st->roll_no,
                    'percentage'   => $pct,
                    'present_days' => $present,
                    'working_days' => $working,
                ];
            }
        }
        $watch = collect($watch)->sortBy('percentage')->values();

        return [
            'total_students'     => $totalStudents,
            'overall_percentage' => $totalStudents > 0 ? (int) round($totalPresent / $totalStudents * 100) : 0,
            'present'            => $totalPresent,
            'marked'             => $totalMarked,
            'by_class'           => $byClass,
            'week'               => $week,
            'watch'              => ['total' => $watch->count(), 'students' => $watch->take(8)->all()],
        ];
    }

    /** Each (class, section, subject) on the teacher's timetable, once. */
    protected function teacherTriples(int $teacherDetailId, int $orgId)
    {
        return TeacherTimeTable::with(['subject:id,name'])
            ->where('teacher_detail_id', $teacherDetailId)
            ->where('organization_id', $orgId)
            ->whereNotNull('subject_id')
            ->get(['standard_id', 'section_id', 'subject_id'])
            ->unique(fn($r) => $r->standard_id . '-' . $r->section_id . '-' . $r->subject_id)
            ->filter(fn($r) => $r->standard_id && $r->subject)
            ->values();
    }

    /** One class-subject's marks rows (marks saved, or absent) — the latest per student. */
    protected function tripleMarks($t, int $orgId, ?int $examId = null)
    {
        return ExamCopy::where('exam_copies.organization_id', $orgId)
            ->where('exam_copies.standard_id', $t->standard_id)
            ->when($t->section_id, fn($q) => $q->where('exam_copies.section_id', $t->section_id))
            ->where('exam_copies.subject_id', $t->subject_id)
            ->where(fn($q) => $q->whereNotNull('exam_copies.marks_obtained')->orWhere('exam_copies.is_absent', true))
            ->when($examId, fn($q) => $q->where('exam_copies.exam_id', $examId));
    }

    /** A student's percentage in a paper — absent, or without a total, is 0. */
    protected function paperPct($r): float
    {
        return $r->is_absent || !$r->max_marks ? 0.0 : (float) $r->marks_obtained / (float) $r->max_marks * 100;
    }

    /**
     * For each class, section and subject on the teacher's timetable, the most
     * recent exam with marks there: the class average, highest and lowest, how
     * many passed and were absent, the grades given — absent counting as 0, as
     * the report card does — and the exam before it, to compare with.
     */
    protected function teacherClassPerformance(int $teacherDetailId, int $orgId): array
    {
        $grading = app(\App\Services\GradingService::class);
        $pass = $grading->passPercentage();

        $out = [];
        foreach ($this->teacherTriples($teacherDetailId, $orgId) as $t) {
            $exams = $this->tripleMarks($t, $orgId)
                ->join('exams', 'exams.id', '=', 'exam_copies.exam_id')
                ->selectRaw('exam_copies.exam_id, exams.exam_name, exams.start_date')
                ->groupBy('exam_copies.exam_id', 'exams.exam_name', 'exams.start_date')
                ->orderByDesc('exams.start_date')
                ->orderByDesc('exam_copies.exam_id')
                ->limit(2)
                ->get();
            $latest = $exams->first();
            if (!$latest) continue;

            $rowsOf = fn($examId) => $this->tripleMarks($t, $orgId, $examId)
                ->latest('exam_copies.updated_at')
                ->get(['student_detail_id', 'marks_obtained', 'max_marks', 'is_absent'])
                ->unique('student_detail_id');

            $rows = $rowsOf($latest->exam_id);
            $pcts = $rows->map(fn($r) => $this->paperPct($r));
            if ($pcts->isEmpty()) continue;

            $grades = $rows->map(fn($r) => $r->is_absent ? 'AB' : $grading->gradeLetter($this->paperPct($r)))
                ->countBy()
                ->all();

            $prev = $exams->get(1);
            $prevPcts = $prev ? $rowsOf($prev->exam_id)->map(fn($r) => $this->paperPct($r)) : collect();

            $out[] = [
                'class'            => $this->className($t->standard_id, $t->section_id),
                'subject'          => $t->subject->name,
                'exam_id'          => (int) $latest->exam_id,
                'exam_name'        => $latest->exam_name,
                'date'             => $latest->start_date ? Carbon::parse($latest->start_date)->toDateString() : null,
                'average'          => (int) round($pcts->avg()),
                'highest'          => (int) round($pcts->max()),
                'lowest'           => (int) round($pcts->min()),
                'students'         => $rows->count(),
                'absent'           => $rows->where('is_absent', true)->count(),
                'passed'           => $pcts->filter(fn($p) => $p >= $pass)->count(),
                'grades'           => $grades,
                'previous_exam'    => $prev?->exam_name,
                'previous_average' => $prevPcts->isNotEmpty() ? (int) round($prevPcts->avg()) : null,
            ];
        }

        return collect($out)->sortBy([['class', 'asc'], ['subject', 'asc']])->values()->all();
    }

    // ── Shared ─────────────────────────────────────────────────────────────────
    protected function upcomingExams(int $orgId)
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
                    'start_date'    => $e->start_date?->toDateString(),
                    'end_date'      => $e->end_date?->toDateString(),
                    'total_marks'   => $e->total_marks !== null ? (float) $e->total_marks : null,
                    'status'        => $status,
                ];
            })
            ->values();
    }

    /**
     * For a student, school-wide notices plus the ones aimed at their own class
     * ($standardId) — a student with no class yet sees only the school-wide
     * ones, never another class's. Teachers see them all.
     */
    private function notices(int $orgId, array $types, ?int $standardId = null, bool $forStudent = false)
    {
        return Announcement::where('organization_id', $orgId)
            ->whereIn('type', $types)
            ->when($forStudent, fn ($q) => $q->where(fn ($w) => $w
                ->whereNull('standard_id')
                ->when($standardId, fn ($c) => $c->orWhere('standard_id', $standardId))))
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
