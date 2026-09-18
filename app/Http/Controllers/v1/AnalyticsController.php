<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\Exam;
use App\Models\Admin\ExamCopy;
use App\Models\Admin\HomeWork;
use App\Models\Admin\HomeWorkCompletion;
use App\Models\Admin\TeacherTimeTable;
use App\Models\Mcq\McqQuestion;
use App\Models\Mcq\McqUserAnswer;
use App\Models\Student\StudentAttendance;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherAttendance;
use App\Models\Teacher\TeacherDetail;
use App\Services\GradingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * The app's Analytics, feature by feature — attendance, exams, homework,
 * quizzes and the timetable — each with the figures its graphs are drawn from
 * and something to compare them with (the class, the month or exam before).
 *
 *   GET /api/v1/student/analytics
 *   GET /api/v1/teacher/analytics
 *
 * It reads things as the dashboards do (DashboardController): a day by the
 * Attendance screen's rules, an exam totalled as the Performance screen totals
 * it, absent counting as 0 as on the report card.
 */
class AnalyticsController extends DashboardController
{
    private const DAY_LABELS = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];

    private function pct($part, $whole): ?int
    {
        return $whole > 0 ? (int) round($part / $whole * 100) : null;
    }

    // ── Student ────────────────────────────────────────────────────────────────
    public function studentAnalytics(Request $request)
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
            'class'      => trim(($student->standard->name ?? '') . ' ' . ($student->section->name ?? '')),
            'attendance' => $this->studentAttendanceAnalytics($student, $orgId),
            'exams'      => $this->studentExamAnalytics($student, $orgId),
            'homework'   => $this->studentHomeworkAnalytics($student, $orgId, $user->id),
            'quiz'       => $this->studentQuizAnalytics($student, $orgId, $user->id),
            'timetable'  => $this->timetableAnalytics(
                TeacherTimeTable::where('organization_id', $orgId)
                    ->where('standard_id', $student->standard_id)
                    ->where('section_id', $student->section_id),
                'subject'
            ),
        ], 'Student analytics fetched successfully.');
    }

    /**
     * Six months of the student's attendance, month by month and weekday by
     * weekday, this month day by day, how long the run of present days is, and
     * the class's own figure each month to compare with.
     */
    private function studentAttendanceAnalytics(StudentDetail $student, int $orgId): array
    {
        $today = now()->startOfDay();
        $from = $today->copy()->startOfMonth()->subMonths(5);

        $records = StudentAttendance::where('student_detail_id', $student->id)
            ->where('organization_id', $orgId)
            ->whereBetween('attendance_date', [$from->toDateString(), $today->toDateString()])
            ->get(['attendance_date', 'status'])
            ->mapWithKeys(fn($r) => [Carbon::parse($r->attendance_date)->toDateString() => (int) $r->status]);

        $months = []; $weekdays = []; $thisMonth = [];
        for ($day = $from->copy(); $day->lte($today); $day->addDay()) {
            $status = $this->dayStatus($day, $records->get($day->toDateString()));
            $m = $day->format('Y-m');
            $months[$m] ??= ['present' => 0, 'absent' => 0, 'holiday' => 0, 'not_marked' => 0];
            if (isset($months[$m][$status])) $months[$m][$status]++;

            if ($status === 'present' || $status === 'absent') {
                $w = $day->dayOfWeekIso;
                $weekdays[$w] ??= ['present' => 0, 'absent' => 0];
                $weekdays[$w][$status]++;
            }
            if ($m === $today->format('Y-m')) {
                $thisMonth[] = ['date' => $day->toDateString(), 'status' => $status];
            }
        }
        // The rest of this month, still to come, so the calendar is whole.
        for ($day = $today->copy()->addDay(); $day->month === $today->month; $day->addDay()) {
            $thisMonth[] = ['date' => $day->toDateString(), 'status' => $this->dayStatus($day, null)];
        }

        // Present days in a row, back from the latest marked day.
        $streak = 0;
        for ($day = $today->copy(); $day->gte($from); $day->subDay()) {
            $status = $this->dayStatus($day, $records->get($day->toDateString()));
            if ($status === 'present') $streak++;
            elseif ($status === 'absent') break;
        }

        // The class, month by month: every classmate's marked days together,
        // Sundays and holidays left out as they are for the student.
        $classMonths = [];
        if ($student->standard_id) {
            $classMonths = StudentAttendance::where('organization_id', $orgId)
                ->whereIn('student_detail_id', $this->classmateIds($student, $orgId))
                ->whereBetween('attendance_date', [$from->toDateString(), $today->toDateString()])
                ->whereRaw('DAYOFWEEK(attendance_date) <> 1')
                ->where('status', '<>', 4)
                ->selectRaw("DATE_FORMAT(attendance_date, '%Y-%m') as m, SUM(status = 1) as p, COUNT(*) as n")
                ->groupBy('m')
                ->get()
                ->mapWithKeys(fn($r) => [$r->m => $this->pct($r->p, $r->n)])
                ->all();
        }

        $monthRows = [];
        foreach ($months as $m => $c) {
            $working = $c['present'] + $c['absent'];
            $monthRows[] = [
                'month'         => $m,
                'present'       => $c['present'],
                'absent'        => $c['absent'],
                'holiday'       => $c['holiday'],
                'not_marked'    => $c['not_marked'],
                'working'       => $working,
                'percentage'    => $this->pct($c['present'], $working),
                'class_average' => $classMonths[$m] ?? null,
            ];
        }

        $weekdayRows = [];
        for ($w = 1; $w <= 6; $w++) {
            $c = $weekdays[$w] ?? ['present' => 0, 'absent' => 0];
            $weekdayRows[] = [
                'day'        => self::DAY_LABELS[$w],
                'present'    => $c['present'],
                'absent'     => $c['absent'],
                'percentage' => $this->pct($c['present'], $c['present'] + $c['absent']),
            ];
        }

        $present = array_sum(array_column($monthRows, 'present'));
        $working = array_sum(array_column($monthRows, 'working'));

        return [
            'months'     => $monthRows,
            'weekdays'   => $weekdayRows,
            'this_month' => $thisMonth,
            'streak'     => $streak,
            'overall'    => ['present' => $present, 'working' => $working, 'percentage' => $this->pct($present, $working)],
        ];
    }

    /**
     * The student's marks: exam by exam and subject by subject against the
     * class, the latest exam paper by paper with the class's average and best,
     * and the grades they have been given.
     */
    private function studentExamAnalytics(StudentDetail $student, int $orgId): array
    {
        $grading = app(GradingService::class);

        // One row per exam and subject — the latest, as the Performance screen reads it.
        $rows = ExamCopy::with(['subject:id,name', 'exam:id,exam_name,start_date'])
            ->where('organization_id', $orgId)
            ->where('student_detail_id', $student->id)
            ->whereNotNull('marks_obtained')
            ->whereNotNull('max_marks')
            ->latest('updated_at')
            ->get()
            ->unique(fn($r) => $r->exam_id . '-' . $r->subject_id)
            ->filter(fn($r) => $r->exam && $r->subject)
            ->values();

        $empty = [
            'overall' => null, 'exams' => [], 'subjects' => [], 'latest' => null,
            'grades' => [], 'pass_percentage' => $grading->passPercentage(),
        ];
        if ($rows->isEmpty()) return $empty;

        $examIds = $rows->pluck('exam_id')->unique()->values();
        $class = $this->classAverages($student, $orgId, $examIds);
        $pctOf = fn($g) => $this->pct($g->sum('marks_obtained'), $g->sum('max_marks'));

        $exams = $rows->groupBy('exam_id')
            ->map(function ($g, $examId) use ($pctOf, $class, $grading) {
                $exam = $g->first()->exam;
                $p = $pctOf($g);
                return [
                    'exam_id'       => (int) $examId,
                    'exam_name'     => $exam->exam_name,
                    'date'          => $exam->start_date?->toDateString(),
                    'obtained'      => (float) $g->sum('marks_obtained'),
                    'max'           => (float) $g->sum('max_marks'),
                    'percentage'    => $p,
                    'grade'         => $grading->gradeLetter($p),
                    'subjects'      => $g->count(),
                    'class_average' => $class['exams'][$examId] ?? null,
                ];
            })
            ->sortBy(fn($e) => ($e['date'] ?? '') . sprintf('%010d', $e['exam_id']))
            ->values();

        $subjects = $rows->groupBy('subject_id')
            ->map(function ($g, $subjectId) use ($pctOf, $class, $grading) {
                $p = $pctOf($g);
                return [
                    'subject'       => $g->first()->subject->name,
                    'obtained'      => (float) $g->sum('marks_obtained'),
                    'max'           => (float) $g->sum('max_marks'),
                    'percentage'    => $p,
                    'grade'         => $grading->gradeLetter($p),
                    'papers'        => $g->count(),
                    'class_average' => $class['subjects'][$subjectId] ?? null,
                ];
            })
            ->sortByDesc('percentage')
            ->values();

        // The latest exam, paper by paper, with the class's average and best.
        $latestExam = $exams->last();
        $latestRows = $rows->where('exam_id', $latestExam['exam_id']);
        $classPapers = ExamCopy::where('organization_id', $orgId)
            ->whereIn('student_detail_id', $this->classmateIds($student, $orgId))
            ->where('exam_id', $latestExam['exam_id'])
            ->whereNotNull('marks_obtained')
            ->where('max_marks', '>', 0)
            ->selectRaw('subject_id, SUM(marks_obtained) as o, SUM(max_marks) as m, MAX(marks_obtained / max_marks) as best')
            ->groupBy('subject_id')
            ->get()
            ->keyBy('subject_id');
        $latest = [
            'exam_id'   => $latestExam['exam_id'],
            'exam_name' => $latestExam['exam_name'],
            'papers'    => $latestRows->map(function ($r) use ($classPapers) {
                $c = $classPapers->get($r->subject_id);
                return [
                    'subject'       => $r->subject->name,
                    'obtained'      => $r->is_absent ? null : (float) $r->marks_obtained,
                    'max'           => (float) $r->max_marks,
                    'percentage'    => $r->is_absent ? null : $this->pct($r->marks_obtained, $r->max_marks),
                    'absent'        => (bool) $r->is_absent,
                    'class_average' => $c ? $this->pct($c->o, $c->m) : null,
                    'class_highest' => $c ? (int) round($c->best * 100) : null,
                ];
            })->sortBy('subject')->values(),
        ];

        // Grades across every paper, in the scale's order, absences last.
        $counts = $rows->map(fn($r) => $r->is_absent ? 'AB' : $grading->gradeLetter($this->pct($r->marks_obtained, $r->max_marks)))
            ->countBy();
        $grades = collect($grading->scale())->pluck('grade')->push('AB')
            ->map(fn($g) => ['grade' => $g, 'count' => (int) ($counts[$g] ?? 0)])
            ->values();

        $overall = $pctOf($rows);
        return [
            'overall'         => [
                'percentage'    => $overall,
                'obtained'      => (float) $rows->sum('marks_obtained'),
                'max'           => (float) $rows->sum('max_marks'),
                'grade'         => $grading->gradeLetter($overall),
                'remark'        => $grading->remarkFor($overall),
                'class_average' => $class['overall'],
                'exams'         => $exams->count(),
                'papers'        => $rows->count(),
                'passed'        => $rows->filter(fn($r) => !$r->is_absent && $grading->isPass($this->pct($r->marks_obtained, $r->max_marks)))->count(),
            ],
            'exams'           => $exams->take(-6)->values(),
            'subjects'        => $subjects,
            'latest'          => $latest,
            'grades'          => $grades,
            'pass_percentage' => $grading->passPercentage(),
        ];
    }

    /** Homework for the student's class: done and pending, subject by subject, week by week. */
    private function studentHomeworkAnalytics(StudentDetail $student, int $orgId, int $userId): array
    {
        $homework = HomeWork::with('subject:id,name')
            ->where('organization_id', $orgId)
            ->where('standard_id', $student->standard_id)
            ->where('section_id', $student->section_id)
            ->get(['id', 'subject_id', 'created_at']);

        $done = HomeWorkCompletion::where('user_id', $userId)
            ->whereIn('home_work_id', $homework->pluck('id'))
            ->pluck('home_work_id')
            ->flip();

        $bySubject = $homework->groupBy(fn($h) => $h->subject?->name ?? 'Other')
            ->map(fn($g, $name) => [
                'subject' => $name,
                'total'   => $g->count(),
                'done'    => $g->filter(fn($h) => $done->has($h->id))->count(),
            ])
            ->sortByDesc('total')
            ->values();

        return [
            'total'      => $homework->count(),
            'done'       => $done->count(),
            'pending'    => max($homework->count() - $done->count(), 0),
            'percentage' => $this->pct($done->count(), $homework->count()),
            'by_subject' => $bySubject,
            'weeks'      => $this->weeks($homework, fn($h) => $done->has($h->id)),
        ];
    }

    /**
     * The last eight weeks, oldest first, Monday to Sunday: how much was set
     * each week and — when $isDone is given — how much of it is done.
     */
    private function weeks($items, ?callable $isDone = null): array
    {
        $start = now()->startOfWeek()->subWeeks(7)->startOfDay();
        $out = [];
        for ($i = 0; $i < 8; $i++) {
            $from = $start->copy()->addWeeks($i);
            $to = $from->copy()->endOfWeek();
            $in = $items->filter(fn($h) => $h->created_at && $h->created_at->between($from, $to));
            $row = ['week' => $from->toDateString(), 'label' => $from->format('j M'), 'set' => $in->count()];
            if ($isDone) $row['done'] = $in->filter($isDone)->count();
            $out[] = $row;
        }
        return $out;
    }

    /** Quiz questions for the student's class, how many they have answered and got right. */
    private function studentQuizAnalytics(StudentDetail $student, int $orgId, int $userId): array
    {
        $available = McqQuestion::where('organization_id', $orgId)
            ->where('is_active', true)
            ->where('standard_id', $student->standard_id)
            ->where(fn($q) => $q->whereNull('section_id')->orWhere('section_id', $student->section_id))
            ->count();

        $answers = McqUserAnswer::where('mcq_user_answers.user_id', $userId)
            ->join('mcq_questions', 'mcq_questions.id', '=', 'mcq_user_answers.mcq_question_id')
            ->leftJoin('chapters', 'chapters.id', '=', 'mcq_questions.chapter_id')
            ->leftJoin('subjects', 'subjects.id', '=', 'chapters.subject_id')
            ->selectRaw("COALESCE(subjects.name, 'Other') as subject, COUNT(*) as attempted, SUM(mcq_user_answers.is_correct = 1) as correct")
            ->groupBy('subject')
            ->get();

        $attempted = (int) $answers->sum('attempted');
        $correct = (int) $answers->sum('correct');

        return [
            'available'  => $available,
            'attempted'  => $attempted,
            'correct'    => $correct,
            'accuracy'   => $this->pct($correct, $attempted),
            'by_subject' => $answers->map(fn($r) => [
                'subject'   => $r->subject,
                'attempted' => (int) $r->attempted,
                'correct'   => (int) $r->correct,
                'accuracy'  => $this->pct($r->correct, $r->attempted),
            ])->sortByDesc('attempted')->values(),
        ];
    }

    /**
     * A week of the timetable: periods per day and per subject (or class), and
     * the hours they come to.
     */
    private function timetableAnalytics($query, string $groupBy): array
    {
        $periods = $query->where('is_active', true)
            ->with(['subject:id,name', 'standard:id,name', 'section:id,name'])
            ->get();

        $minutes = fn($p) => $p->start_time && $p->end_time
            ? max(Carbon::parse($p->start_time)->diffInMinutes(Carbon::parse($p->end_time), false), 0)
            : 0;

        $byDay = [];
        for ($d = 1; $d <= 6; $d++) {
            $day = $periods->where('day_of_week', $d);
            $byDay[] = ['day' => self::DAY_LABELS[$d], 'periods' => $day->count(), 'minutes' => (int) $day->sum($minutes)];
        }
        $sunday = $periods->where('day_of_week', 7)->count();
        if ($sunday > 0) $byDay[] = ['day' => 'Sun', 'periods' => $sunday, 'minutes' => (int) $periods->where('day_of_week', 7)->sum($minutes)];

        $key = $groupBy === 'class'
            ? fn($p) => trim(($p->standard->name ?? '') . ' ' . ($p->section->name ?? ''))
            : fn($p) => $p->subject->name ?? 'Free Period';

        return [
            'periods' => $periods->count(),
            'minutes' => (int) $periods->sum($minutes),
            'by_day'  => $byDay,
            'by_' . $groupBy => $periods->groupBy($key)
                ->map(fn($g, $name) => ['name' => $name, 'periods' => $g->count()])
                ->sortByDesc('periods')
                ->values(),
        ];
    }

    // ── Teacher ────────────────────────────────────────────────────────────────
    public function teacherAnalytics(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        $teacher = TeacherDetail::where('user_id', $user->id)->first();
        if (!$teacher) {
            return $this->error('Teacher profile not found.', 404);
        }

        $orgId = $user->organization_id;
        $attendance = $this->teacherClassAttendance($teacher->id, $orgId);
        $attendance += $this->teacherAttendanceHistory($teacher->id, $orgId);

        return $this->success([
            'attendance'    => $attendance,
            'my_attendance' => $this->teacherOwnAttendance($teacher->id, $orgId),
            'marks'         => $this->teacherMarksAnalytics($teacher->id, $orgId),
            'homework'      => $this->teacherHomeworkAnalytics($user->id, $orgId),
            'quiz'          => $this->teacherQuizAnalytics($user->id, $orgId),
            'timetable'     => $this->timetableAnalytics(
                TeacherTimeTable::where('organization_id', $orgId)->where('teacher_detail_id', $teacher->id),
                'class'
            ),
        ], 'Teacher analytics fetched successfully.');
    }

    /**
     * The teacher's classes over time: six months across all of them, and this
     * month class by class — the share present, and on how many school days
     * the class was marked.
     */
    private function teacherAttendanceHistory(int $teacherDetailId, int $orgId): array
    {
        $today = now()->startOfDay();
        $from = $today->copy()->startOfMonth()->subMonths(5);
        $monthStart = $today->copy()->startOfMonth();

        $schoolDays = 0;
        for ($d = $monthStart->copy(); $d->lte($today); $d->addDay()) {
            if ($d->dayOfWeek !== Carbon::SUNDAY) $schoolDays++;
        }

        $allIds = collect();
        $byClass = [];
        foreach ($this->teacherClassPairs($teacherDetailId) as $p) {
            $ids = StudentDetail::where('organization_id', $orgId)
                ->where('standard_id', $p['standard_id'])
                ->when($p['section_id'], fn($q) => $q->where('section_id', $p['section_id']))
                ->pluck('id');
            if ($ids->isEmpty()) continue;
            $allIds = $allIds->merge($ids);

            $r = StudentAttendance::where('organization_id', $orgId)
                ->whereIn('student_detail_id', $ids)
                ->whereBetween('attendance_date', [$monthStart->toDateString(), $today->toDateString()])
                ->whereRaw('DAYOFWEEK(attendance_date) <> 1')
                ->selectRaw('SUM(status = 1) as p, SUM(status <> 1 AND status <> 4) as a, COUNT(DISTINCT attendance_date) as days')
                ->first();

            $byClass[] = [
                'class'       => $this->className($p['standard_id'], $p['section_id']),
                'students'    => $ids->count(),
                'percentage'  => $this->pct((int) $r->p, (int) $r->p + (int) $r->a),
                'marked_days' => (int) $r->days,
                'school_days' => $schoolDays,
            ];
        }

        $months = [];
        if ($allIds->isNotEmpty()) {
            $agg = StudentAttendance::where('organization_id', $orgId)
                ->whereIn('student_detail_id', $allIds->unique())
                ->whereBetween('attendance_date', [$from->toDateString(), $today->toDateString()])
                ->whereRaw('DAYOFWEEK(attendance_date) <> 1')
                ->where('status', '<>', 4)
                ->selectRaw("DATE_FORMAT(attendance_date, '%Y-%m') as m, SUM(status = 1) as p, COUNT(*) as n")
                ->groupBy('m')
                ->get()
                ->keyBy('m');
            for ($i = 0; $i < 6; $i++) {
                $m = $from->copy()->addMonths($i)->format('Y-m');
                $row = $agg->get($m);
                $months[] = ['month' => $m, 'percentage' => $row ? $this->pct($row->p, $row->n) : null];
            }
        }

        return ['months' => $months, 'month_by_class' => $byClass];
    }

    /** The teacher's own attendance, by the Attendance screen's rules. */
    private function teacherOwnAttendance(int $teacherDetailId, int $orgId): array
    {
        $today = now()->startOfDay();
        $from = $today->copy()->startOfMonth()->subMonths(5);

        $records = TeacherAttendance::where('teacher_detail_id', $teacherDetailId)
            ->where('organization_id', $orgId)
            ->whereBetween('attendance_date', [$from->toDateString(), $today->toDateString()])
            ->get(['attendance_date', 'status'])
            ->mapWithKeys(fn($r) => [Carbon::parse($r->attendance_date)->toDateString() => (int) $r->status]);

        $months = [];
        for ($day = $from->copy(); $day->lte($today); $day->addDay()) {
            $m = $day->format('Y-m');
            $months[$m] ??= ['present' => 0, 'absent' => 0, 'holiday' => 0];
            $status = $this->dayStatus($day, $records->get($day->toDateString()));
            if (isset($months[$m][$status])) $months[$m][$status]++;
        }

        $rows = [];
        foreach ($months as $m => $c) {
            $rows[] = $c + ['month' => $m, 'percentage' => $this->pct($c['present'], $c['present'] + $c['absent'])];
        }
        return ['months' => $rows, 'this_month' => end($rows) ?: null];
    }

    /**
     * Marks in the teacher's subjects: each class-subject's latest exam against
     * the one before, the grades given across them, the exams over time, and how
     * far marks and copies are in for the exam now under way or just finished.
     */
    private function teacherMarksAnalytics(int $teacherDetailId, int $orgId): array
    {
        $performance = $this->teacherClassPerformance($teacherDetailId, $orgId);
        $triples = $this->teacherTriples($teacherDetailId, $orgId);
        $grading = app(GradingService::class);

        // Grades given across every class-subject's latest exam.
        $counts = [];
        foreach ($performance as $p) {
            foreach ($p['grades'] as $g => $n) $counts[$g] = ($counts[$g] ?? 0) + $n;
        }
        $grades = collect($grading->scale())->pluck('grade')->push('AB')
            ->map(fn($g) => ['grade' => $g, 'count' => (int) ($counts[$g] ?? 0)])
            ->values();

        // The exams over time: the average across all the teacher's classes.
        $trend = [];
        if ($triples->isNotEmpty()) {
            $rows = ExamCopy::join('exams', 'exams.id', '=', 'exam_copies.exam_id')
                ->where('exam_copies.organization_id', $orgId)
                ->where(function ($q) use ($triples) {
                    foreach ($triples as $t) {
                        $q->orWhere(fn($w) => $w->where('exam_copies.standard_id', $t->standard_id)
                            ->when($t->section_id, fn($x) => $x->where('exam_copies.section_id', $t->section_id))
                            ->where('exam_copies.subject_id', $t->subject_id));
                    }
                })
                ->where(fn($q) => $q->whereNotNull('exam_copies.marks_obtained')->orWhere('exam_copies.is_absent', true))
                ->where('exam_copies.max_marks', '>', 0)
                ->selectRaw('exam_copies.exam_id, exams.exam_name, exams.start_date, AVG(CASE WHEN exam_copies.is_absent = 1 THEN 0 ELSE exam_copies.marks_obtained / exam_copies.max_marks END) as avg_ratio, COUNT(*) as n')
                ->groupBy('exam_copies.exam_id', 'exams.exam_name', 'exams.start_date')
                ->orderByDesc('exams.start_date')
                ->limit(6)
                ->get();
            $trend = $rows->reverse()->map(fn($r) => [
                'exam_id'   => (int) $r->exam_id,
                'exam_name' => $r->exam_name,
                'date'      => $r->start_date ? Carbon::parse($r->start_date)->toDateString() : null,
                'average'   => (int) round($r->avg_ratio * 100),
                'papers'    => (int) $r->n,
            ])->values()->all();
        }

        // The exam under way, or the one that finished last: whose marks and
        // copies are in, class-subject by class-subject.
        $exam = Exam::where('organization_id', $orgId)
            ->where('is_published', true)
            ->whereDate('start_date', '<=', now()->toDateString())
            ->orderByDesc('start_date')
            ->first();
        $upload = null;
        if ($exam && $triples->isNotEmpty()) {
            $items = $triples->map(function ($t) use ($exam, $orgId) {
                $students = StudentDetail::where('organization_id', $orgId)
                    ->where('standard_id', $t->standard_id)
                    ->when($t->section_id, fn($q) => $q->where('section_id', $t->section_id))
                    ->count();
                $rows = ExamCopy::where('organization_id', $orgId)
                    ->where('exam_id', $exam->id)
                    ->where('standard_id', $t->standard_id)
                    ->when($t->section_id, fn($q) => $q->where('section_id', $t->section_id))
                    ->where('subject_id', $t->subject_id)
                    ->get(['student_detail_id', 'marks_obtained', 'is_absent', 'pdf_path']);
                return [
                    'class'    => $this->className($t->standard_id, $t->section_id),
                    'subject'  => $t->subject->name,
                    'students' => $students,
                    'marks'    => $rows->filter(fn($r) => $r->marks_obtained !== null || $r->is_absent)->unique('student_detail_id')->count(),
                    'copies'   => $rows->filter(fn($r) => !empty($r->pdf_path))->unique('student_detail_id')->count(),
                ];
            })->sortBy([['class', 'asc'], ['subject', 'asc']])->values();

            $upload = [
                'exam_id'   => $exam->id,
                'exam_name' => $exam->exam_name,
                'items'     => $items,
                'done'      => $items->filter(fn($i) => $i['students'] > 0 && $i['marks'] >= $i['students'])->count(),
                'total'     => $items->count(),
            ];
        }

        return [
            'class_performance' => $performance,
            'grades'            => $grades,
            'exams'             => $trend,
            'upload'            => $upload,
            'pass_percentage'   => $grading->passPercentage(),
        ];
    }

    /** Homework the teacher has set: week by week, and class by class with how much is done. */
    private function teacherHomeworkAnalytics(int $userId, int $orgId): array
    {
        $homework = HomeWork::with(['standard:id,name', 'section:id,name'])
            ->withCount('completions')
            ->where('organization_id', $orgId)
            ->where('user_id', $userId)
            ->get(['id', 'standard_id', 'section_id', 'created_at']);

        $sizes = [];
        $byClass = $homework->groupBy(fn($h) => $h->standard_id . '-' . ($h->section_id ?? 0))
            ->map(function ($g) use ($orgId, &$sizes) {
                $h = $g->first();
                $size = StudentDetail::where('organization_id', $orgId)
                    ->where('standard_id', $h->standard_id)
                    ->when($h->section_id, fn($q) => $q->where('section_id', $h->section_id))
                    ->count();
                $possible = $size * $g->count();
                $done = (int) $g->sum('completions_count');
                return [
                    'class'      => trim(($h->standard->name ?? '') . ' ' . ($h->section->name ?? '')),
                    'set'        => $g->count(),
                    'students'   => $size,
                    'done'       => $done,
                    'percentage' => $this->pct(min($done, $possible), $possible),
                ];
            })
            ->sortBy('class')
            ->values();

        $possible = $byClass->sum(fn($c) => $c['students'] * $c['set']);
        $done = $byClass->sum('done');

        return [
            'total'      => $homework->count(),
            'this_week'  => $homework->filter(fn($h) => $h->created_at && $h->created_at->gte(now()->startOfWeek()))->count(),
            'completion' => $this->pct(min($done, $possible), $possible),
            'by_class'   => $byClass,
            'weeks'      => $this->weeks($homework),
        ];
    }

    /** The quiz questions the teacher has written, and how students have done on them. */
    private function teacherQuizAnalytics(int $userId, int $orgId): array
    {
        $questions = McqQuestion::with(['standard:id,name', 'section:id,name'])
            ->where('organization_id', $orgId)
            ->where('created_by', $userId)
            ->get(['id', 'standard_id', 'section_id', 'is_active']);

        $answers = $questions->isEmpty() ? collect() : McqUserAnswer::whereIn('mcq_question_id', $questions->pluck('id'))
            ->selectRaw('mcq_question_id, COUNT(*) as n, SUM(is_correct = 1) as c, COUNT(DISTINCT user_id) as u')
            ->groupBy('mcq_question_id')
            ->get()
            ->keyBy('mcq_question_id');

        $byClass = $questions->groupBy(fn($q) => trim(($q->standard->name ?? '') . ' ' . ($q->section->name ?? '')) ?: 'All classes')
            ->map(function ($g, $name) use ($answers) {
                $n = $g->sum(fn($q) => (int) ($answers->get($q->id)->n ?? 0));
                $c = $g->sum(fn($q) => (int) ($answers->get($q->id)->c ?? 0));
                return ['class' => $name, 'questions' => $g->count(), 'answers' => $n, 'accuracy' => $this->pct($c, $n)];
            })
            ->sortBy('class')
            ->values();

        $attempts = (int) $answers->sum('n');
        $correct = (int) $answers->sum('c');
        $students = $questions->isEmpty() ? 0 : McqUserAnswer::whereIn('mcq_question_id', $questions->pluck('id'))->distinct()->count('user_id');

        return [
            'questions' => $questions->count(),
            'active'    => $questions->where('is_active', true)->count(),
            'answers'   => $attempts,
            'students'  => $students,
            'accuracy'  => $this->pct($correct, $attempts),
            'by_class'  => $byClass,
        ];
    }
}
