<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\AdmissionEnquiry;
use App\Models\Admin\Announcement;
use App\Models\Admin\Exam;
use App\Models\Admin\ExamCopy;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeePaymentRequest;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Admin\HomeWork;
use App\Models\Admin\LedgerTransaction;
use App\Models\Admin\TeacherArrangement;
use App\Models\Admin\TeacherTimeTable;
use App\Models\Admin\TransportFeePayment;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentAttendance;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use App\Models\Teacher\TeacherAttendance;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use App\Services\GradingService;
use App\Support\AcademicYear;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The admin app's Dashboard and Analytics, laid out as the student and teacher
 * ones are: the whole school's attendance (students and teachers), results,
 * fees, homework and admissions, each with something to compare it with — the
 * school day or month before, the exam before, the class beside it.
 *
 *   GET /api/v1/admin/dashboard          → the admin home
 *   GET /api/v1/admin/analytics/school   → Analytics, tab by tab
 *
 * A day is read as the panel's Attendance marks it: 1 present, 2 half day (in
 * school, so counted present), 3 or 4 a holiday, anything else absent, and a
 * Sunday is a holiday whatever was recorded. Fees are counted as the panel's
 * Fee → Analytics counts them (HandlesFeeAnalytics); a paper as the teacher's
 * Analytics reads it, absent counting as 0.
 */
class AdminAnalyticsController extends DashboardController
{
    private const ADMIN_ROLES = ['admin', 'sub-admin'];

    // Attendance under three quarters is worth a look.
    private const LOW = 75;

    private const WEEKDAYS = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat'];

    // SQL for a record's reading (see the class comment).
    private const IS_PRESENT = 'IN (1, 2)';
    private const IS_HOLIDAY = 'IN (3, 4)';
    private const IS_ABSENT  = 'NOT IN (1, 2, 3, 4)';

    private function pct($part, $whole): ?int
    {
        return $whole > 0 ? (int) round($part / $whole * 100) : null;
    }

    /**
     * Months a rider is billed for, from their billable_months flags: an unset
     * month is billed, except June, which is free unless the school says so.
     */
    private function billedMonths($raw): int
    {
        if (is_string($raw)) $raw = json_decode($raw, true) ?: [];
        $raw = (array) $raw;
        $n = 0;
        foreach (['apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec', 'jan', 'feb', 'mar'] as $m) {
            if (array_key_exists($m, $raw) ? (bool) $raw[$m] : $m !== 'jun') $n++;
        }
        return $n;
    }

    // ── Endpoints ──────────────────────────────────────────────────────────────
    public function dashboard(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole(self::ADMIN_ROLES)) return $err;

        $orgId = $user->organization_id;
        $classes = $this->classSections($orgId);
        $attendance = $this->studentAttendance($orgId, $classes);
        $staff = $this->staffAttendance($orgId);
        $fees = $this->fees($orgId);
        $results = $this->results($orgId, $classes, false);
        $admissions = $this->admissions($orgId, $classes);
        $now = now();

        return $this->success([
            // What the first admin home read, for builds that still read it.
            'students'                  => $admissions['students'],
            'teachers'                  => $staff['total'],
            'fees_collected_total'      => (float) FeePayment::where('organization_id', $orgId)->sum('amount'),
            'fees_collected_this_month' => round((float) FeePayment::where('organization_id', $orgId)
                ->whereMonth('payment_date', $now->month)
                ->whereYear('payment_date', $now->year)
                ->sum('amount'), 2),

            'school'     => $this->school($orgId, $classes, $staff['total']),
            'attendance' => [
                'today'    => $attendance['today'],
                'previous' => $attendance['previous'],
                'week'     => $attendance['week'],
            ],
            'staff' => [
                'today'        => $staff['today'],
                'absent'       => $staff['absent'],
                'arrangements' => $staff['arrangements'],
            ],
            'fees' => [
                'summary'    => $fees['summary'],
                'periods'    => $fees['periods'],
                'days'       => array_slice($fees['days'], -7),
                'qr_pending' => $fees['qr_pending'],
            ],
            'results' => $results['latest'] ? [
                'exam_id'          => $results['latest']['exam_id'],
                'exam_name'        => $results['latest']['exam_name'],
                'average'          => $results['latest']['average'],
                'previous_exam'    => $results['latest']['previous_exam'],
                'previous_average' => $results['latest']['previous_average'],
                'students'         => $results['latest']['students'],
                'passed'           => $results['latest']['passed'],
                'by_class'         => $results['latest']['by_class'],
                'pass_percentage'  => $results['pass_percentage'],
            ] : null,
            'exams'      => $this->upcomingExams($orgId),
            'admissions' => [
                'this_month'        => $admissions['this_month'],
                'last_month'        => $admissions['last_month'],
                'session_total'     => $admissions['session_total'],
                'enquiries_pending' => $admissions['enquiries']['pending'],
            ],
            'notices'  => $this->adminNotices($orgId),
            'activity' => $this->activity($orgId),
        ], 'Admin dashboard fetched.');
    }

    public function analytics(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole(self::ADMIN_ROLES)) return $err;

        $orgId = $user->organization_id;
        $classes = $this->classSections($orgId);
        $staff = $this->staffAttendance($orgId);

        return $this->success([
            'school'     => $this->school($orgId, $classes, $staff['total']),
            'attendance' => $this->studentAttendance($orgId, $classes),
            'staff'      => $staff,
            'results'    => $this->results($orgId, $classes),
            'fees'       => $this->fees($orgId),
            'homework'   => $this->homework($orgId, $classes),
            'admissions' => $this->admissions($orgId, $classes),
        ], 'Admin analytics fetched.');
    }

    // ── The school ─────────────────────────────────────────────────────────────
    /**
     * Every class and section that has students, in class order:
     * "standard-section" → standard, section, name ("5 A") and how many.
     */
    private function classSections(int $orgId): array
    {
        $standards = Standard::where('organization_id', $orgId)->inClassOrder()->get(['id', 'name']);
        $sections = Section::where('organization_id', $orgId)->pluck('name', 'id');
        $counts = StudentDetail::where('organization_id', $orgId)
            ->whereNotNull('standard_id')
            ->selectRaw('standard_id, section_id, COUNT(*) as n')
            ->groupBy('standard_id', 'section_id')
            ->get()
            ->groupBy('standard_id');

        $out = [];
        foreach ($standards as $std) {
            $rows = ($counts[$std->id] ?? collect())
                ->sortBy(fn($r) => $r->section_id ? ($sections[$r->section_id] ?? '') : '');
            foreach ($rows as $r) {
                $out[$std->id . '-' . ($r->section_id ?? 0)] = [
                    'standard_id' => (int) $std->id,
                    'section_id'  => $r->section_id ? (int) $r->section_id : null,
                    'standard'    => $std->name,
                    'name'        => trim($std->name . ' ' . ($r->section_id ? ($sections[$r->section_id] ?? '') : '')),
                    'students'    => (int) $r->n,
                ];
            }
        }
        return $out;
    }

    private function school(int $orgId, array $classes, int $teachers): array
    {
        $students = array_sum(array_column($classes, 'students'));
        return [
            'session'   => AcademicYear::label(),
            'students'  => StudentDetail::where('organization_id', $orgId)->count(),
            'teachers'  => $teachers,
            'classes'   => Standard::where('organization_id', $orgId)->count(),
            'sections'  => Section::where('organization_id', $orgId)->count(),
            'subjects'  => Subject::where('organization_id', $orgId)->count(),
            'per_teacher' => $teachers > 0 ? (int) round($students / $teachers) : null,
        ];
    }

    // ── Students' attendance ───────────────────────────────────────────────────
    /**
     * Today class by class (a class with nobody marked, or on holiday, says
     * so), the school day before that was marked, the last seven days, six
     * months month by month and weekday by weekday, this month class by class
     * with the school days each was marked, and the students under three
     * quarters this month.
     */
    private function studentAttendance(int $orgId, array $classes): array
    {
        $today = now()->startOfDay();
        $todayStr = $today->toDateString();
        $from = $today->copy()->startOfMonth()->subMonths(5);
        $monthStart = $today->copy()->startOfMonth();
        $sunday = $today->dayOfWeek === Carbon::SUNDAY;

        $base = fn() => StudentAttendance::join('student_details as sd', 'sd.id', '=', 'student_attendances.student_detail_id')
            ->where('sd.organization_id', $orgId)
            ->whereRaw('DAYOFWEEK(student_attendances.attendance_date) <> 1');
        $counts = 'COUNT(DISTINCT CASE WHEN student_attendances.status ' . self::IS_PRESENT . ' THEN sd.id END) as p, '
            . 'COUNT(DISTINCT CASE WHEN student_attendances.status ' . self::IS_ABSENT . ' THEN sd.id END) as a, '
            . 'COUNT(DISTINCT CASE WHEN student_attendances.status ' . self::IS_HOLIDAY . ' THEN sd.id END) as h';

        // Six months, day by day: students present, absent and on holiday.
        $days = $base()
            ->whereBetween('student_attendances.attendance_date', [$from->toDateString(), $todayStr])
            ->selectRaw('student_attendances.attendance_date as d, ' . $counts)
            ->groupBy('student_attendances.attendance_date')
            ->get()
            ->keyBy(fn($r) => Carbon::parse($r->d)->toDateString());

        // A day the whole school spent on holiday, as marked.
        $schoolHoliday = fn(string $d) => ($r = $days->get($d)) && (int) $r->h > 0 && (int) $r->p + (int) $r->a === 0;

        $week = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = $today->copy()->subDays($i);
            $r = $days->get($day->toDateString());
            $p = (int) ($r->p ?? 0);
            $a = (int) ($r->a ?? 0);
            $week[] = [
                'date'       => $day->toDateString(),
                'present'    => $p,
                'marked'     => $p + $a,
                'percentage' => $this->pct($p, $p + $a),
                'holiday'    => $day->dayOfWeek === Carbon::SUNDAY || $schoolHoliday($day->toDateString()),
            ];
        }

        $months = [];
        for ($i = 0; $i < 6; $i++) {
            $key = $from->copy()->addMonths($i)->format('Y-m');
            $in = $days->filter(fn($r, $d) => str_starts_with($d, $key));
            $p = (int) $in->sum('p');
            $a = (int) $in->sum('a');
            $months[] = ['month' => $key, 'present' => $p, 'absent' => $a, 'percentage' => $this->pct($p, $p + $a)];
        }

        $weekdays = [];
        foreach (self::WEEKDAYS as $n => $label) {
            $in = $days->filter(fn($r, $d) => Carbon::parse($d)->dayOfWeekIso === $n);
            $p = (int) $in->sum('p');
            $a = (int) $in->sum('a');
            $weekdays[] = ['day' => $label, 'absent' => $a, 'percentage' => $this->pct($p, $p + $a)];
        }

        // The last school day before today that was marked, to set today against.
        $prevDay = $days->keys()
            ->filter(fn($d) => $d < $todayStr && (int) $days[$d]->p + (int) $days[$d]->a > 0)
            ->sort()
            ->last();
        $previous = $prevDay ? [
            'date'       => $prevDay,
            'percentage' => $this->pct((int) $days[$prevDay]->p, (int) $days[$prevDay]->p + (int) $days[$prevDay]->a),
        ] : null;

        // This month, class by class and day by day.
        $rows = $base()
            ->whereBetween('student_attendances.attendance_date', [$monthStart->toDateString(), $todayStr])
            ->selectRaw('sd.standard_id, sd.section_id, student_attendances.attendance_date as d, ' . $counts)
            ->groupBy('sd.standard_id', 'sd.section_id', 'student_attendances.attendance_date')
            ->get()
            ->groupBy(fn($r) => $r->standard_id . '-' . ($r->section_id ?? 0));

        $schoolDays = 0;
        for ($d = $monthStart->copy(); $d->lte($today); $d->addDay()) {
            if ($d->dayOfWeek !== Carbon::SUNDAY && !$schoolHoliday($d->toDateString())) $schoolDays++;
        }

        $byClass = [];
        $monthByClass = [];
        $notMarked = [];
        $tp = 0; $ta = 0; $th = 0;
        foreach ($classes as $key => $c) {
            $classRows = $rows->get($key, collect());
            $t = $classRows->first(fn($r) => Carbon::parse($r->d)->toDateString() === $todayStr);
            $p = (int) ($t->p ?? 0);
            $a = (int) ($t->a ?? 0);
            $h = (int) ($t->h ?? 0);
            $holiday = $sunday || ($h > 0 && $p + $a === 0);
            $byClass[] = [
                'class'      => $c['name'],
                'present'    => $p,
                'total'      => $c['students'],
                'percentage' => (int) round($p / max($c['students'], 1) * 100),
                'absent'     => $a,
                'marked'     => $p + $a,
                'holiday'    => $holiday,
            ];
            if (!$holiday && $p + $a === 0) $notMarked[] = $c['name'];
            $tp += $p; $ta += $a; $th += $h;

            $mp = (int) $classRows->sum('p');
            $ma = (int) $classRows->sum('a');
            $monthByClass[] = [
                'class'       => $c['name'],
                'students'    => $c['students'],
                'percentage'  => $this->pct($mp, $mp + $ma),
                'marked_days' => $classRows->filter(fn($r) => (int) $r->p + (int) $r->a + (int) $r->h > 0)->count(),
                'school_days' => $schoolDays,
            ];
        }

        // This month, student by student: under three quarters with three marked days or more.
        $watch = $base()
            ->whereBetween('student_attendances.attendance_date', [$monthStart->toDateString(), $todayStr])
            ->selectRaw('sd.id, sd.full_name, sd.roll_no, sd.standard_id, sd.section_id, '
                . 'COUNT(DISTINCT CASE WHEN student_attendances.status ' . self::IS_PRESENT . ' THEN student_attendances.attendance_date END) as p, '
                . 'COUNT(DISTINCT CASE WHEN student_attendances.status ' . self::IS_ABSENT . ' THEN student_attendances.attendance_date END) as a')
            ->groupBy('sd.id', 'sd.full_name', 'sd.roll_no', 'sd.standard_id', 'sd.section_id')
            ->get()
            ->filter(fn($r) => (int) $r->p + (int) $r->a >= 3 && $this->pct((int) $r->p, (int) $r->p + (int) $r->a) < self::LOW)
            ->map(fn($r) => [
                'name'         => $r->full_name,
                'class'        => $classes[$r->standard_id . '-' . ($r->section_id ?? 0)]['name'] ?? null,
                'roll_no'      => $r->roll_no,
                'percentage'   => $this->pct((int) $r->p, (int) $r->p + (int) $r->a),
                'present_days' => (int) $r->p,
                'working_days' => (int) $r->p + (int) $r->a,
            ])
            ->sortBy([['percentage', 'asc'], ['name', 'asc']])
            ->values();

        $students = array_sum(array_column($classes, 'students'));
        $n = count($months);

        return [
            'today' => [
                'date'       => $todayStr,
                'holiday'    => $sunday || ($th > 0 && $tp + $ta === 0),
                'students'   => $students,
                'present'    => $tp,
                'absent'     => $ta,
                'marked'     => $tp + $ta,
                'percentage' => $this->pct($tp, $tp + $ta),
                'not_marked' => $notMarked,
            ],
            'previous'       => $previous,
            'by_class'       => $byClass,
            'week'           => $week,
            'months'         => $months,
            'this_month'     => $months[$n - 1]['percentage'] ?? null,
            'last_month'     => $months[$n - 2]['percentage'] ?? null,
            'weekdays'       => $weekdays,
            'month_by_class' => $monthByClass,
            'watch'          => ['total' => $watch->count(), 'students' => $watch->take(10)->all()],
        ];
    }

    // ── Teachers' attendance ───────────────────────────────────────────────────
    /**
     * The teachers: who is in today, and who is absent, on a half day or not
     * marked; the last seven days and six months; this month teacher by
     * teacher with the days they were away; today's arrangements; and the
     * week of periods each one teaches.
     */
    private function staffAttendance(int $orgId): array
    {
        $today = now()->startOfDay();
        $todayStr = $today->toDateString();
        $from = $today->copy()->startOfMonth()->subMonths(5);
        $monthStart = $today->copy()->startOfMonth();
        $sunday = $today->dayOfWeek === Carbon::SUNDAY;

        $teachers = TeacherDetail::with('user:id,name')->where('organization_id', $orgId)->get(['id', 'user_id', 'employee_id']);
        $names = $teachers->mapWithKeys(fn($t) => [$t->id => $t->user->name ?? 'Teacher']);
        $total = $teachers->count();

        $base = fn() => TeacherAttendance::join('teacher_details as td', 'td.id', '=', 'teacher_attendances.teacher_detail_id')
            ->where('td.organization_id', $orgId)
            ->whereRaw('DAYOFWEEK(teacher_attendances.attendance_date) <> 1');

        // Today, teacher by teacher (the last record wins).
        $todayCodes = TeacherAttendance::where('organization_id', $orgId)
            ->whereDate('attendance_date', $todayStr)
            ->whereIn('teacher_detail_id', $names->keys())
            ->orderBy('id')
            ->get(['teacher_detail_id', 'status'])
            ->mapWithKeys(fn($r) => [$r->teacher_detail_id => (int) $r->status]);

        $present = $todayCodes->filter(fn($s) => $s === 1)->count();
        $half = $todayCodes->filter(fn($s) => $s === 2)->count();
        $holidays = $todayCodes->filter(fn($s) => $s === 3 || $s === 4)->count();
        $absent = $todayCodes->count() - $present - $half - $holidays;
        $marked = $present + $half + $absent;
        $holiday = $sunday || ($holidays > 0 && $marked === 0);

        $away = $todayCodes
            ->filter(fn($s) => !in_array($s, [1, 3, 4], true))
            ->map(fn($s, $id) => ['name' => $names[$id], 'status' => $s === 2 ? 'half_day' : 'absent'])
            ->sortBy('name')
            ->values();
        $unmarked = $holiday ? collect() : $names->keys()->diff($todayCodes->keys())->map(fn($id) => $names[$id])->sort()->values();

        // Six months, day by day.
        $days = $base()
            ->whereBetween('teacher_attendances.attendance_date', [$from->toDateString(), $todayStr])
            ->selectRaw('teacher_attendances.attendance_date as d, '
                . 'COUNT(DISTINCT CASE WHEN teacher_attendances.status ' . self::IS_PRESENT . ' THEN td.id END) as p, '
                . 'COUNT(DISTINCT CASE WHEN teacher_attendances.status ' . self::IS_ABSENT . ' THEN td.id END) as a, '
                . 'COUNT(DISTINCT CASE WHEN teacher_attendances.status ' . self::IS_HOLIDAY . ' THEN td.id END) as h')
            ->groupBy('teacher_attendances.attendance_date')
            ->get()
            ->keyBy(fn($r) => Carbon::parse($r->d)->toDateString());

        $week = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = $today->copy()->subDays($i);
            $r = $days->get($day->toDateString());
            $p = (int) ($r->p ?? 0);
            $a = (int) ($r->a ?? 0);
            $week[] = [
                'date'       => $day->toDateString(),
                'present'    => $p,
                'marked'     => $p + $a,
                'percentage' => $this->pct($p, $p + $a),
                'holiday'    => $day->dayOfWeek === Carbon::SUNDAY || ((int) ($r->h ?? 0) > 0 && $p + $a === 0),
            ];
        }

        $months = [];
        for ($i = 0; $i < 6; $i++) {
            $key = $from->copy()->addMonths($i)->format('Y-m');
            $in = $days->filter(fn($r, $d) => str_starts_with($d, $key));
            $p = (int) $in->sum('p');
            $a = (int) $in->sum('a');
            $months[] = ['month' => $key, 'percentage' => $this->pct($p, $p + $a)];
        }

        // This month, teacher by teacher: those who were away, most days first.
        $byTeacher = $base()
            ->whereBetween('teacher_attendances.attendance_date', [$monthStart->toDateString(), $todayStr])
            ->selectRaw('td.id, '
                . 'COUNT(DISTINCT CASE WHEN teacher_attendances.status = 1 THEN teacher_attendances.attendance_date END) as p, '
                . 'COUNT(DISTINCT CASE WHEN teacher_attendances.status = 2 THEN teacher_attendances.attendance_date END) as hd, '
                . 'COUNT(DISTINCT CASE WHEN teacher_attendances.status ' . self::IS_ABSENT . ' THEN teacher_attendances.attendance_date END) as a')
            ->groupBy('td.id')
            ->get()
            ->filter(fn($r) => (int) $r->a + (int) $r->hd > 0)
            ->map(fn($r) => [
                'name'       => $names[$r->id] ?? 'Teacher',
                'present'    => (int) $r->p,
                'half_day'   => (int) $r->hd,
                'absent'     => (int) $r->a,
                'working'    => (int) $r->p + (int) $r->hd + (int) $r->a,
                'percentage' => $this->pct((int) $r->p + (int) $r->hd, (int) $r->p + (int) $r->hd + (int) $r->a),
            ])
            ->sortBy([['absent', 'desc'], ['half_day', 'desc'], ['name', 'asc']])
            ->values();

        // Today's arrangements: periods of absent teachers, and how many have a substitute.
        $arrangements = TeacherArrangement::where('organization_id', $orgId)
            ->whereDate('date', $todayStr)
            ->get(['id', 'substitute_teacher_id']);

        // The week of periods each teacher teaches.
        $periods = TeacherTimeTable::where('organization_id', $orgId)
            ->where('is_active', true)
            ->whereNotNull('teacher_detail_id')
            ->get(['teacher_detail_id', 'start_time', 'end_time'])
            ->groupBy('teacher_detail_id');
        $load = $periods->map(fn($g, $id) => [
            'name'    => $names[$id] ?? null,
            'periods' => $g->count(),
            'minutes' => (int) $g->sum(fn($p) => $p->start_time && $p->end_time
                ? max(Carbon::parse($p->start_time)->diffInMinutes(Carbon::parse($p->end_time), false), 0)
                : 0),
        ])->filter(fn($t) => $t['name'] !== null)->sortByDesc('periods')->values();

        return [
            'total' => $total,
            'today' => [
                'date'       => $todayStr,
                'holiday'    => $holiday,
                'present'    => $present,
                'half_day'   => $half,
                'absent'     => $absent,
                'marked'     => $marked,
                'not_marked' => $unmarked->count(),
                'percentage' => $this->pct($present + $half, $marked),
            ],
            'absent'       => $away->all(),
            'not_marked'   => $unmarked->take(20)->all(),
            'week'         => $week,
            'months'       => $months,
            'month_by_teacher' => ['total' => $byTeacher->count(), 'teachers' => $byTeacher->take(8)->all()],
            'arrangements' => [
                'total'   => $arrangements->count(),
                'covered' => $arrangements->whereNotNull('substitute_teacher_id')->count(),
            ],
            'workload' => [
                'periods'  => $load->sum('periods'),
                'teachers' => $load->count(),
                'idle'     => max($total - $load->count(), 0),
                'by_teacher' => $load->take(10)->all(),
            ],
        ];
    }

    // ── Results ────────────────────────────────────────────────────────────────
    /**
     * The school's exams over time (the average paper across every class), and
     * the latest exam class by class against the exam before, subject by
     * subject, the grades given and the toppers; and how far marks are in for
     * the exam under way, class by class.
     */
    private function results(int $orgId, array $classes, bool $withUpload = true): array
    {
        $grading = app(GradingService::class);
        $pass = $grading->passPercentage();
        $ratio = 'CASE WHEN exam_copies.is_absent = 1 THEN 0 ELSE exam_copies.marks_obtained / exam_copies.max_marks END';
        $papers = fn() => ExamCopy::where('exam_copies.organization_id', $orgId)
            ->where(fn($q) => $q->whereNotNull('exam_copies.marks_obtained')->orWhere('exam_copies.is_absent', true))
            ->where('exam_copies.max_marks', '>', 0);

        $trend = $papers()
            ->join('exams', 'exams.id', '=', 'exam_copies.exam_id')
            ->selectRaw("exam_copies.exam_id, exams.exam_name, exams.start_date, AVG($ratio) as avg_ratio, COUNT(*) as n")
            ->groupBy('exam_copies.exam_id', 'exams.exam_name', 'exams.start_date')
            ->orderByDesc('exams.start_date')
            ->orderByDesc('exam_copies.exam_id')
            ->limit(6)
            ->get()
            ->reverse()
            ->values();

        $exams = $trend->map(fn($r) => [
            'exam_id'   => (int) $r->exam_id,
            'exam_name' => $r->exam_name,
            'date'      => $r->start_date ? Carbon::parse($r->start_date)->toDateString() : null,
            'average'   => (int) round($r->avg_ratio * 100),
            'papers'    => (int) $r->n,
        ])->all();

        $latest = null;
        if ($last = $trend->last()) {
            $prev = $trend->count() > 1 ? $trend[$trend->count() - 2] : null;
            $key = fn($r) => $r->standard_id . '-' . ($r->section_id ?? 0);

            $classAvg = fn(int $examId) => $papers()
                ->where('exam_copies.exam_id', $examId)
                ->selectRaw("exam_copies.standard_id, exam_copies.section_id, AVG($ratio) as avg_ratio, MAX($ratio) as hi, MIN($ratio) as lo")
                ->groupBy('exam_copies.standard_id', 'exam_copies.section_id')
                ->get()
                ->keyBy($key);
            $now = $classAvg((int) $last->exam_id);
            $before = $prev ? $classAvg((int) $prev->exam_id) : collect();

            // Each student's whole exam: marks over what the papers were out of.
            $students = $papers()
                ->where('exam_copies.exam_id', $last->exam_id)
                ->join('student_details as sd', 'sd.id', '=', 'exam_copies.student_detail_id')
                ->selectRaw('sd.id, sd.full_name, exam_copies.standard_id, exam_copies.section_id, '
                    . 'SUM(CASE WHEN exam_copies.is_absent = 1 THEN 0 ELSE exam_copies.marks_obtained END) / SUM(exam_copies.max_marks) as ratio')
                ->groupBy('sd.id', 'sd.full_name', 'exam_copies.standard_id', 'exam_copies.section_id')
                ->get()
                ->map(fn($r) => [
                    'name'  => $r->full_name,
                    'key'   => $key($r),
                    'class' => $classes[$key($r)]['name'] ?? null,
                    'pct'   => round((float) $r->ratio * 100, 1),
                ]);

            $byClass = [];
            foreach ($classes as $k => $c) {
                if (!$now->has($k)) continue;
                $mine = $students->where('key', $k);
                $byClass[] = [
                    'class'            => $c['name'],
                    'average'          => (int) round($now[$k]->avg_ratio * 100),
                    'previous_average' => $before->has($k) ? (int) round($before[$k]->avg_ratio * 100) : null,
                    'highest'          => (int) round($now[$k]->hi * 100),
                    'lowest'           => (int) round($now[$k]->lo * 100),
                    'students'         => $mine->count(),
                    'passed'           => $mine->filter(fn($s) => $s['pct'] >= $pass)->count(),
                ];
            }

            $subjectNames = Subject::where('organization_id', $orgId)->pluck('name', 'id');
            $bySubject = $papers()
                ->where('exam_copies.exam_id', $last->exam_id)
                ->selectRaw("exam_copies.subject_id, AVG($ratio) as avg_ratio, MAX($ratio) as hi, MIN($ratio) as lo, COUNT(*) as n")
                ->groupBy('exam_copies.subject_id')
                ->get()
                ->map(fn($r) => [
                    'subject' => $subjectNames[$r->subject_id] ?? 'Subject',
                    'average' => (int) round($r->avg_ratio * 100),
                    'highest' => (int) round($r->hi * 100),
                    'lowest'  => (int) round($r->lo * 100),
                    'papers'  => (int) $r->n,
                ])
                ->sortByDesc('average')
                ->values();

            // Grades given, paper by paper, as on the report card.
            $counts = $papers()
                ->where('exam_copies.exam_id', $last->exam_id)
                ->get(['exam_copies.is_absent', 'exam_copies.marks_obtained', 'exam_copies.max_marks'])
                ->map(fn($r) => $r->is_absent ? 'AB' : $grading->gradeLetter($this->paperPct($r)))
                ->countBy();
            $grades = collect($grading->scale())->pluck('grade')->push('AB')
                ->map(fn($g) => ['grade' => $g, 'count' => (int) ($counts[$g] ?? 0)])
                ->values();

            $latest = [
                'exam_id'          => (int) $last->exam_id,
                'exam_name'        => $last->exam_name,
                'date'             => $last->start_date ? Carbon::parse($last->start_date)->toDateString() : null,
                'average'          => (int) round($last->avg_ratio * 100),
                'previous_exam'    => $prev?->exam_name,
                'previous_average' => $prev ? (int) round($prev->avg_ratio * 100) : null,
                'students'         => $students->count(),
                'passed'           => $students->filter(fn($s) => $s['pct'] >= $pass)->count(),
                'absent'           => $papers()->where('exam_copies.exam_id', $last->exam_id)->where('exam_copies.is_absent', true)->count(),
                'papers'           => (int) $last->n,
                'by_class'         => $byClass,
                'by_subject'       => $bySubject,
                'grades'           => $grades,
                'toppers'          => $students->sortByDesc('pct')->take(5)
                    ->map(fn($s) => ['name' => $s['name'], 'class' => $s['class'], 'percentage' => (int) round($s['pct'])])
                    ->values(),
            ];
        }

        return [
            'pass_percentage' => $pass,
            'exams'           => $exams,
            'latest'          => $latest,
            'upload'          => $withUpload ? $this->marksEntry($orgId, $classes) : null,
        ];
    }

    /**
     * The exam under way, or the one that started last: for each class, how
     * many of its subjects (as the timetable has them) have marks for every
     * student.
     */
    private function marksEntry(int $orgId, array $classes): ?array
    {
        $exam = Exam::where('organization_id', $orgId)
            ->where('is_published', true)
            ->whereDate('start_date', '<=', now()->toDateString())
            ->orderByDesc('start_date')
            ->first();
        if (!$exam) return null;

        $triples = TeacherTimeTable::where('organization_id', $orgId)
            ->whereNotNull('subject_id')
            ->whereNotNull('standard_id')
            ->get(['standard_id', 'section_id', 'subject_id'])
            ->unique(fn($r) => $r->standard_id . '-' . ($r->section_id ?? 0) . '-' . $r->subject_id);
        if ($triples->isEmpty()) return null;

        $marks = ExamCopy::where('organization_id', $orgId)
            ->where('exam_id', $exam->id)
            ->where(fn($q) => $q->whereNotNull('marks_obtained')->orWhere('is_absent', true))
            ->selectRaw('standard_id, section_id, subject_id, COUNT(DISTINCT student_detail_id) as n')
            ->groupBy('standard_id', 'section_id', 'subject_id')
            ->get()
            ->keyBy(fn($r) => $r->standard_id . '-' . ($r->section_id ?? 0) . '-' . $r->subject_id);

        $byClass = [];
        $done = 0;
        $total = 0;
        foreach ($classes as $k => $c) {
            $subjects = $triples->filter(fn($t) => $t->standard_id . '-' . ($t->section_id ?? 0) === $k);
            if ($subjects->isEmpty()) continue;
            $complete = $subjects->filter(fn($t) => (int) ($marks[$k . '-' . $t->subject_id]->n ?? 0) >= $c['students'])->count();
            $entered = (int) $subjects->sum(fn($t) => min((int) ($marks[$k . '-' . $t->subject_id]->n ?? 0), $c['students']));
            $byClass[] = [
                'class'    => $c['name'],
                'subjects' => $subjects->count(),
                'complete' => $complete,
                'marks'    => $entered,
                'expected' => $subjects->count() * $c['students'],
            ];
            $done += $complete;
            $total += $subjects->count();
        }
        if ($total === 0) return null;

        return [
            'exam_id'   => $exam->id,
            'exam_name' => $exam->exam_name,
            'done'      => $done,
            'total'     => $total,
            'by_class'  => $byClass,
        ];
    }

    // ── Fees ───────────────────────────────────────────────────────────────────
    /**
     * Fees as the panel's Fee → Analytics counts them: academic is each
     * class's active fee heads for every student in it, transport each rider's
     * route fee for the months they are billed, and collections come from both
     * payment tables. Then what came in day by day and month by month, class
     * by class, by mode, the biggest dues, QR payments to check and the ledger.
     */
    private function fees(int $orgId): array
    {
        $heads = FeeStructure::where('organization_id', $orgId)
            ->where('is_active', true)
            ->where('fee_type', 'academic')
            ->get(['standard_id', 'section_id', 'amount'])
            ->groupBy('standard_id');
        $academicFor = fn($std, $sec) => (float) ($heads[$std] ?? collect())
            ->filter(fn($h) => $h->section_id === null || (int) $h->section_id === (int) $sec)
            ->sum('amount');

        $students = StudentDetail::with('user:id,name')
            ->where('organization_id', $orgId)
            ->get(['id', 'user_id', 'full_name', 'admission_no', 'standard_id', 'section_id']);
        // Each student's own academic rows — Last Year Dues.
        $ownFees = FeeStructure::ownTotals($orgId, $students->pluck('id')->all());
        // Each rider's bus for the year: the route's monthly fee for the months they are billed.
        $transport = [];
        $riders = DB::table('transportation_students as ts')
            ->join('transportations as t', 't.id', '=', 'ts.transportation_id')
            ->join('student_details as sd', 'sd.id', '=', 'ts.student_detail_id')
            ->where('ts.organization_id', $orgId)
            ->get(['ts.student_detail_id', 'ts.billable_months', 't.monthly_fee']);
        foreach ($riders as $r) {
            $transport[$r->student_detail_id] = ($transport[$r->student_detail_id] ?? 0)
                + (float) $r->monthly_fee * $this->billedMonths($r->billable_months);
        }

        // What each student has paid, academic and bus, from both payment tables.
        $paid = [];
        $paidQueries = [
            FeePayment::where('organization_id', $orgId)->whereIn('fee_type', ['academic', 'transport']),
            TransportFeePayment::where('organization_id', $orgId),
        ];
        foreach ($paidQueries as $q) {
            $sums = $q->selectRaw('student_detail_id, SUM(amount) as total')->groupBy('student_detail_id')->pluck('total', 'student_detail_id');
            foreach ($sums as $id => $sum) $paid[$id] = ($paid[$id] ?? 0) + (float) $sum;
        }

        $standardNames = Standard::where('organization_id', $orgId)->pluck('name', 'id');
        $order = Standard::where('organization_id', $orgId)->inClassOrder()->pluck('id')->flip();
        $sectionNames = Section::where('organization_id', $orgId)->pluck('name', 'id');

        $academicBill = 0.0;
        $transportBill = 0.0;
        $byClass = [];
        $rows = [];
        foreach ($students as $s) {
            $a = $academicFor($s->standard_id, $s->section_id) + (float) ($ownFees[$s->id] ?? 0);
            $t = (float) ($transport[$s->id] ?? 0);
            $in = (float) ($paid[$s->id] ?? 0);
            $academicBill += $a;
            $transportBill += $t;

            // A student not yet in a class counts in the totals only.
            $std = (int) $s->standard_id;
            if (isset($standardNames[$std])) {
                $byClass[$std] ??= ['class' => $standardNames[$std], 'students' => 0, 'billable' => 0.0, 'collected' => 0.0];
                $byClass[$std]['students']++;
                $byClass[$std]['billable'] += $a + $t;
                $byClass[$std]['collected'] += $in;
            }

            $rows[] = [
                'name'         => $s->user->name ?? $s->full_name ?? '—',
                'admission_no' => $s->admission_no,
                'class'        => trim(($standardNames[$std] ?? '') . ' ' . ($s->section_id ? ($sectionNames[$s->section_id] ?? '') : '')),
                'billable'     => round($a + $t, 2),
                'collected'    => round($in, 2),
                'due'          => round(max(0, $a + $t - $in), 2),
            ];
        }

        $byType = FeePayment::where('organization_id', $orgId)
            ->selectRaw('fee_type, SUM(amount) as total')
            ->groupBy('fee_type')
            ->pluck('total', 'fee_type');
        $academicIn = (float) ($byType['academic'] ?? 0);
        $penaltyIn = (float) ($byType['penalty'] ?? 0);
        $transportIn = (float) ($byType['transport'] ?? 0)
            + (float) TransportFeePayment::where('organization_id', $orgId)->sum('amount');
        $billable = $academicBill + $transportBill;
        $collected = $academicIn + $transportIn;

        $summary = [
            'students'            => $students->count(),
            'riders'              => count($transport),
            'academic_billable'   => round($academicBill, 2),
            'transport_billable'  => round($transportBill, 2),
            'total_billable'      => round($billable, 2),
            'academic_collected'  => round($academicIn, 2),
            'transport_collected' => round($transportIn, 2),
            'penalty_collected'   => round($penaltyIn, 2),
            'total_collected'     => round($collected, 2),
            'academic_due'        => round(max(0, $academicBill - $academicIn), 2),
            'transport_due'       => round(max(0, $transportBill - $transportIn), 2),
            'total_due'           => round(max(0, $billable - $collected), 2),
            'rate'                => $this->pct($collected, $billable),
            'fully_paid'          => count(array_filter($rows, fn($r) => $r['billable'] > 0 && $r['due'] <= 0)),
            'with_dues'           => count(array_filter($rows, fn($r) => $r['due'] > 0)),
        ];

        // What came in, day by day, over six months — both tables.
        $today = now()->startOfDay();
        $from = $today->copy()->startOfMonth()->subMonths(5);
        $byDay = [];
        foreach ([FeePayment::where('organization_id', $orgId), TransportFeePayment::where('organization_id', $orgId)] as $q) {
            $q->whereBetween('payment_date', [$from->toDateString(), $today->toDateString()])
                ->selectRaw('payment_date, SUM(amount) as amount, COUNT(*) as n')
                ->groupBy('payment_date')
                ->get()
                ->each(function ($r) use (&$byDay) {
                    $d = Carbon::parse($r->payment_date)->toDateString();
                    $byDay[$d]['amount'] = ($byDay[$d]['amount'] ?? 0) + (float) $r->amount;
                    $byDay[$d]['count'] = ($byDay[$d]['count'] ?? 0) + (int) $r->n;
                });
        }
        $sum = function (Carbon $a, Carbon $b) use ($byDay) {
            $amount = 0.0;
            $count = 0;
            foreach ($byDay as $d => $v) {
                if ($d >= $a->toDateString() && $d <= $b->toDateString()) {
                    $amount += $v['amount'];
                    $count += $v['count'];
                }
            }
            return ['amount' => round($amount, 2), 'count' => $count];
        };
        $lastMonth = $today->copy()->subMonthNoOverflow();

        $days = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = $today->copy()->subDays($i)->toDateString();
            $days[] = ['date' => $d, 'amount' => round($byDay[$d]['amount'] ?? 0, 2), 'count' => $byDay[$d]['count'] ?? 0];
        }
        $months = [];
        for ($i = 0; $i < 6; $i++) {
            $m = $from->copy()->addMonths($i);
            $months[] = ['month' => $m->format('Y-m')] + $sum($m->copy()->startOfMonth(), $m->copy()->endOfMonth());
        }

        // Payment modes, from both tables.
        $modes = [];
        foreach ([FeePayment::where('organization_id', $orgId), TransportFeePayment::where('organization_id', $orgId)] as $q) {
            foreach ($q->selectRaw('payment_mode, SUM(amount) as amount, COUNT(*) as n')->groupBy('payment_mode')->get() as $r) {
                $k = $r->payment_mode ?: 'other';
                $modes[$k]['amount'] = ($modes[$k]['amount'] ?? 0) + (float) $r->amount;
                $modes[$k]['count'] = ($modes[$k]['count'] ?? 0) + (int) $r->n;
            }
        }
        $modeTotal = array_sum(array_column($modes, 'amount'));
        $modeRows = collect($modes)->map(fn($m, $k) => [
            'mode'   => ucwords(str_replace('_', ' ', $k)),
            'amount' => round($m['amount'], 2),
            'count'  => $m['count'],
            'share'  => $this->pct($m['amount'], $modeTotal),
        ])->sortByDesc('amount')->values();

        $classRows = collect($byClass)
            ->sortBy(fn($c, $std) => $order[$std] ?? PHP_INT_MAX)
            ->map(fn($c) => $c + [
                'due'  => round(max(0, $c['billable'] - $c['collected']), 2),
                'rate' => $this->pct($c['collected'], $c['billable']),
            ])
            ->map(fn($c) => array_merge($c, ['billable' => round($c['billable'], 2), 'collected' => round($c['collected'], 2)]))
            ->values();

        $credit = (float) LedgerTransaction::forOrg($orgId)->credit()->sum('amount');
        $expense = (float) LedgerTransaction::forOrg($orgId)->expense()->sum('amount');

        return [
            'summary' => $summary,
            'periods' => [
                'today'      => $sum($today, $today),
                'yesterday'  => $sum($today->copy()->subDay(), $today->copy()->subDay()),
                'this_week'  => $sum($today->copy()->startOfWeek(), $today),
                'this_month' => $sum($today->copy()->startOfMonth(), $today),
                'last_month' => $sum($lastMonth->copy()->startOfMonth(), $lastMonth->copy()->endOfMonth()),
            ],
            'days'       => $days,
            'months'     => $months,
            'by_class'   => $classRows,
            'modes'      => $modeRows,
            'top_due'    => collect($rows)->where('due', '>', 0)->sortByDesc('due')->take(8)->values(),
            'qr_pending' => FeePaymentRequest::where('organization_id', $orgId)
                ->where('status', FeePaymentRequest::STATUS_PENDING)
                ->count(),
            'ledger' => ['credit' => round($credit, 2), 'expense' => round($expense, 2), 'balance' => round($credit - $expense, 2)],
        ];
    }

    // ── Homework ───────────────────────────────────────────────────────────────
    /**
     * Homework set this session: week by week, and how much students have
     * marked done, class by class and teacher by teacher.
     */
    private function homework(int $orgId, array $classes): array
    {
        $homework = HomeWork::withCount('completions')
            ->where('organization_id', $orgId)
            ->where('created_at', '>=', AcademicYear::start())
            ->get(['id', 'user_id', 'standard_id', 'section_id', 'created_at']);

        // How many students a piece of homework is for: its section, or the whole class.
        $sizeOf = function ($h) use ($classes) {
            if ($h->section_id) return (int) ($classes[$h->standard_id . '-' . $h->section_id]['students'] ?? 0);
            return (int) collect($classes)->where('standard_id', (int) $h->standard_id)->sum('students');
        };

        $done = fn($g) => (int) $g->sum(fn($h) => min((int) $h->completions_count, $sizeOf($h)));
        $possible = fn($g) => (int) $g->sum($sizeOf);

        $byClass = [];
        foreach ($classes as $k => $c) {
            $g = $homework->filter(fn($h) => (int) $h->standard_id === $c['standard_id']
                && ((int) ($h->section_id ?? 0) === (int) ($c['section_id'] ?? 0) || !$h->section_id));
            if ($g->isEmpty()) continue;
            $d = (int) $g->sum(fn($h) => min((int) $h->completions_count, $c['students']));
            $byClass[] = [
                'class'      => $c['name'],
                'set'        => $g->count(),
                'students'   => $c['students'],
                'done'       => $d,
                'percentage' => $this->pct($d, $g->count() * $c['students']),
            ];
        }

        $teacherNames = User::whereIn('id', $homework->pluck('user_id')->filter()->unique())->pluck('name', 'id');
        $byTeacher = $homework->groupBy('user_id')
            ->map(fn($g, $id) => [
                'name'       => $teacherNames[$id] ?? 'Teacher',
                'set'        => $g->count(),
                'percentage' => $this->pct($done($g), $possible($g)),
            ])
            ->sortByDesc('set')
            ->take(8)
            ->values();

        $start = now()->startOfWeek()->subWeeks(7)->startOfDay();
        $weeks = [];
        for ($i = 0; $i < 8; $i++) {
            $a = $start->copy()->addWeeks($i);
            $b = $a->copy()->endOfWeek();
            $weeks[] = [
                'week'  => $a->toDateString(),
                'label' => $a->format('j M'),
                'set'   => $homework->filter(fn($h) => $h->created_at && $h->created_at->between($a, $b))->count(),
            ];
        }

        return [
            'total'      => $homework->count(),
            'this_week'  => $weeks[7]['set'],
            'last_week'  => $weeks[6]['set'],
            'teachers'   => $homework->pluck('user_id')->filter()->unique()->count(),
            'completion' => $this->pct($done($homework), $possible($homework)),
            'weeks'      => $weeks,
            'by_class'   => $byClass,
            'by_teacher' => $byTeacher,
        ];
    }

    // ── Admissions ─────────────────────────────────────────────────────────────
    /**
     * Admissions this session month by month (on the admission date the school
     * recorded, else when the student was added), the school class by class
     * with boys and girls, and the enquiries.
     */
    private function admissions(int $orgId, array $classes): array
    {
        $on = 'COALESCE(date_of_admission, created_at)';
        $start = AcademicYear::start();
        $end = AcademicYear::end();
        $perMonth = StudentDetail::where('organization_id', $orgId)
            ->whereRaw("$on BETWEEN ? AND ?", [$start, $end])
            ->selectRaw("DATE_FORMAT($on, '%Y-%m') as m, COUNT(*) as n")
            ->groupBy('m')
            ->pluck('n', 'm');
        $months = collect(AcademicYear::months())
            ->filter(fn($m) => $m['key'] <= now()->format('Y-m'))
            ->map(fn($m) => ['month' => $m['key'], 'count' => (int) ($perMonth[$m['key']] ?? 0)])
            ->values();

        $count = fn(Carbon $a, Carbon $b) => StudentDetail::where('organization_id', $orgId)
            ->whereRaw("$on BETWEEN ? AND ?", [$a, $b])
            ->count();
        $lastMonth = now()->subMonthNoOverflow();

        // Boys and girls, class by class.
        $gender = StudentDetail::where('organization_id', $orgId)
            ->selectRaw('standard_id, LOWER(TRIM(gender)) as g, COUNT(*) as n')
            ->groupBy('standard_id', 'g')
            ->get();
        $kind = fn($g) => in_array($g, ['male', 'm', 'boy'], true) ? 'boys' : (in_array($g, ['female', 'f', 'girl'], true) ? 'girls' : 'other');
        $byClass = collect($classes)->groupBy('standard_id')->map(function ($g, $std) use ($gender, $kind) {
            $mix = ['boys' => 0, 'girls' => 0, 'other' => 0];
            foreach ($gender->where('standard_id', $std) as $r) $mix[$kind($r->g)] += (int) $r->n;
            return [
                'class'    => $g->first()['standard'],
                'students' => (int) $g->sum('students'),
                'sections' => $g->whereNotNull('section_id')->count(),
            ] + $mix;
        })->values();
        $mix = ['boys' => 0, 'girls' => 0, 'other' => 0];
        foreach ($gender as $r) $mix[$kind($r->g)] += (int) $r->n;

        $enquiries = [
            'total'    => AdmissionEnquiry::where('organization_id', $orgId)->count(),
            'pending'  => AdmissionEnquiry::where('organization_id', $orgId)->pending()->count(),
            'admitted' => AdmissionEnquiry::where('organization_id', $orgId)->admitted()->count(),
        ];
        $enquiries['other'] = max(0, $enquiries['total'] - $enquiries['pending'] - $enquiries['admitted']);
        $enquiries['recent'] = AdmissionEnquiry::with('standard:id,name')
            ->where('organization_id', $orgId)
            ->pending()
            ->latest()
            ->limit(4)
            ->get()
            ->map(fn($e) => [
                'id'    => $e->id,
                'name'  => $e->student_name,
                'class' => $e->standard->name ?? null,
                'date'  => $e->created_at?->toDateString(),
            ])
            ->values();

        return [
            'students'      => StudentDetail::where('organization_id', $orgId)->count(),
            'this_month'    => $count(now()->startOfMonth(), now()->endOfMonth()),
            'last_month'    => $count($lastMonth->copy()->startOfMonth(), $lastMonth->copy()->endOfMonth()),
            'session_total' => (int) $months->sum('count'),
            'months'        => $months,
            'by_class'      => $byClass,
            'gender'        => $mix,
            'enquiries'     => $enquiries,
        ];
    }

    // ── The home's lists ───────────────────────────────────────────────────────
    private function adminNotices(int $orgId)
    {
        return Announcement::where('organization_id', $orgId)
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

    /** The latest admissions and fee payments, newest first. */
    private function activity(int $orgId): array
    {
        $items = [];
        foreach (StudentDetail::with('user:id,name')->where('organization_id', $orgId)->latest()->take(4)->get() as $s) {
            $items[] = [
                'kind'  => 'admission',
                'title' => $s->user->name ?? $s->full_name ?? 'Student',
                'text'  => 'Admitted',
                'time'  => $s->created_at?->diffForHumans(),
                'ts'    => $s->created_at?->timestamp ?? 0,
            ];
        }
        foreach (FeePayment::with('studentDetail.user:id,name')->where('organization_id', $orgId)->latest()->take(4)->get() as $f) {
            $items[] = [
                'kind'   => 'fee',
                'title'  => $f->studentDetail->user->name ?? $f->studentDetail->full_name ?? 'Student',
                'text'   => ucfirst($f->fee_type ?: 'fee') . ' fee paid',
                'amount' => (float) $f->amount,
                'time'   => $f->created_at?->diffForHumans(),
                'ts'     => $f->created_at?->timestamp ?? 0,
            ];
        }
        usort($items, fn($a, $b) => $b['ts'] <=> $a['ts']);
        return array_map(function ($i) {
            unset($i['ts']);
            return $i;
        }, array_slice($items, 0, 5));
    }
}
