<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Student\StudentDetail;
use App\Models\Student\Standard;
use App\Models\Student\Section;
use App\Models\Student\StudentAttendance;
use App\Models\Admin\Announcement;
use App\Models\Admin\ExamCopy;
// use App\Models\Admin\Homework;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Admin\TeacherArrangement;
use App\Models\Admin\TransportFeePayment;
use App\Models\Teacher\TeacherAttendance;
use App\Models\Teacher\TeacherDetail;
use App\Models\WebsiteContact;
use App\Support\AcademicYear;
use Illuminate\Support\Facades\DB;
use App\Support\NameOrder;

class Analytics extends Component
{
    // ─── Filters ─────────────────────────────────────────────────────────────
    public $attendanceFilter = '7';
    public $teacherAttFilter = '7';
    public $performerClass   = '';
    public $performerSection = '';

    // Teacher volume reads day by day now, not month by month: the window above
    // sets the bars, and this picks one of those days to report on its own.
    public $teacherAttDate = '';
    public $teacherDailyAttendance = [];

    // Admissions are counted per school year (April → March); this is its
    // starting year, so 2026 means Apr 2026 – Mar 2027.
    public $admissionYear = '';
    public $admissionYears = [];
    public $admissionsTotal = 0;

    /** [date => admissions], read once a request. */
    private ?array $admissionDates = null;

    // ─── Arrangement ─────────────────────────────────────────────────────────
    public $selectedTeachers  = [];
    public $availableTeachers = [];

    // ─── Data ─────────────────────────────────────────────────────────────────
    public $statsData                = [];
    public $studentMonthlyAttendance = [];
    public $teacherMonthlyAttendance = [];   // feeds the monthly rate trend only
    public $studentPieData           = [];
    public $attendanceMonths         = [];
    public $topStudents              = [];
    public $classDistribution        = [];
    public $adminEnquiries           = [];
    public $arrangements             = [];
    public $announcements            = [];
    public $recentActivities         = [];
    public $todayHomework            = [];
    public $feeStats                 = [];
    public $feeClassData             = [];
    public $standards                = [];
    public $sections                 = [];

    // ─── Deeper analytics (distinct from the home dashboard) ───────────────────
    public $kpis                 = [];   // headline metrics with period deltas
    public $attendanceTrendPct   = [];   // monthly attendance % (student vs teacher)
    public $attendanceDailyTrend = [];   // last 30 days, day by day: % with present / absent behind it
    public $monthlyAttendancePct = [];   // the session so far, month by month: average % (students, teachers)
    public $classAttendanceRank  = [];   // per-class attendance % ranking (30 days)
    public $admissionsTrend      = [];   // new admissions per month (Apr–Mar)
    public $feeClassRate         = [];   // per-class collection % with defaulters
    public $transportClassData   = [];   // transport fee: collected vs remaining per class
    public $transportRouteData   = [];   // transport fee: collected vs remaining per route
    public $transportFeeStats    = [];   // transport totals: expected / collected / remaining
    public $lowPerformers        = [];   // lowest-attendance students (needs attention)
    public $topRankers           = [];   // Student Performance: the 10 best by exam marks
    public $bottomRankers        = [];   // …and the 10 at the foot of the same ranking
    public $enquiryStats         = [];   // enquiry funnel counts

    // Filters only — they change the URL without adding a Back step.
    protected $queryString = [
        'attendanceFilter' => ['history' => false],
        'performerClass'   => ['history' => false],
        'performerSection' => ['history' => false],
    ];

    // ─── DB status values ─────────────────────────────────────────────────────
    // student_attendances.status  → tinyint:  1 = present, 0 = absent
    // teacher_attendances.status  → boolean:  1 = present, 0 = absent
    const STU_PRESENT = 1;
    const STU_ABSENT  = 0;
    const TCH_PRESENT = 1;
    const TCH_ABSENT  = 0;

    public function mount(): void
    {
        $this->standards = Standard::where('organization_id', $this->orgId())
            ->inClassOrder()->get();

        $now = Carbon::now();
        $thisAy = $now->month >= 4 ? (int) $now->year : (int) $now->year - 1;
        $this->admissionYears = range($thisAy, $thisAy - 5);
        $this->admissionYear  = (string) $thisAy;
        // The school years that hold admissions, and the latest of them when
        // the running one holds none (see pickAdmissionYear()).
        $this->pickAdmissionYear($thisAy);

        $this->teacherAttDate = $now->toDateString();

        $this->loadAll();
    }

    public function updatedAttendanceFilter(): void
    {
        $this->loadStudentAttendance();
        $this->loadStudentPie();
    }

    public function updatedTeacherAttFilter(): void
    {
        $this->loadTeacherDaily();

        // Keep the picked day inside the window the bars now cover.
        $dates = array_column($this->teacherDailyAttendance['days'] ?? [], 'date');
        if ($dates && !in_array($this->teacherAttDate, $dates, true)) {
            $this->teacherAttDate = end($dates);
        }
    }

    public function updatedAdmissionYear(): void
    {
        $this->loadAdmissionsTrend();
    }

    public function updatedPerformerClass(): void
    {
        $this->loadSections();
        $this->performerSection = '';
        $this->loadExamRankers();
    }

    public function updatedPerformerSection(): void
    {
        $this->loadExamRankers();
    }

    public function saveArrangement(int $arrangementId): void
    {
        $teacherId = $this->selectedTeachers[$arrangementId] ?? null;
        if ($teacherId) {
            TeacherArrangement::where('id', $arrangementId)
                ->update(['substitute_teacher_id' => $teacherId]);
            $this->loadArrangements();
            session()->flash('arrangement_saved', true);
        }
    }

    // ─── Master load ──────────────────────────────────────────────────────────

    protected function loadAll(): void
    {
        $this->loadStats();
        $this->buildAttendanceMonths();
        $this->loadStudentAttendance();
        $this->loadStudentPie();
        // The teacher card's window, date dropdown and day figures are off the
        // page; loadTeacherDaily() / teacherDay() are kept below, uncalled.
        $this->loadSections();
        // Student Performance ranks on exam marks now. The attendance-based
        // loadTopStudents() / loadLowPerformers() are kept below, uncalled.
        $this->loadExamRankers();
        $this->loadAdminEnquiries();
        $this->loadArrangements();
        // The Recent Announcements card is off the page; loadAnnouncements() is kept below, uncalled.
        $this->loadFeeStatsStatic();
        $this->loadFeeClassDataStatic();

        // Deeper, analytics-only widgets
        $this->loadTeacherMonthly();
        $this->loadAttendanceTrendPct();
        $this->loadAttendanceDailyTrend();
        $this->loadMonthlyAttendancePct();
        $this->loadClassAttendanceRank();
        $this->loadAdmissionsTrend();
        $this->loadFeeClassRate();
        $this->loadTransportFeeData();
        $this->loadEnquiryStats();
        $this->loadKpis();
    }

    // ─── KPIs with period deltas ────────────────────────────────────────────────

    protected function loadKpis(): void
    {
        $orgId = $this->orgId();
        $today = Carbon::today();
        $yest  = Carbon::yesterday();

        $rate = function ($present, $total) {
            return $total > 0 ? round(($present / $total) * 100, 1) : 0.0;
        };

        // Student attendance rate today vs yesterday
        $stuPresentToday = StudentAttendance::where('organization_id', $orgId)->whereDate('attendance_date', $today)->where('status', self::STU_PRESENT)->count();
        $stuTotalToday   = StudentAttendance::where('organization_id', $orgId)->whereDate('attendance_date', $today)->count();
        $stuPresentYest  = StudentAttendance::where('organization_id', $orgId)->whereDate('attendance_date', $yest)->where('status', self::STU_PRESENT)->count();
        $stuTotalYest    = StudentAttendance::where('organization_id', $orgId)->whereDate('attendance_date', $yest)->count();
        $stuRateToday    = $rate($stuPresentToday, $stuTotalToday);
        $stuRateYest     = $rate($stuPresentYest, $stuTotalYest);

        // Teacher attendance rate today
        $tchPresentToday = TeacherAttendance::where('organization_id', $orgId)->whereDate('attendance_date', $today)->where('status', self::TCH_PRESENT)->count();
        $tchTotalToday   = TeacherAttendance::where('organization_id', $orgId)->whereDate('attendance_date', $today)->count();

        // Fee collection rate + avg daily collection (30 days)
        $totalFee   = (float) ($this->feeStats['totalFee'] ?? 0);
        $collected  = (float) ($this->feeStats['collected'] ?? 0);
        $collectRate = $totalFee > 0 ? round(($collected / $totalFee) * 100, 1) : 0.0;
        $last30Sum  = (float) FeePayment::where('organization_id', $orgId)
            ->where('payment_date', '>=', $today->copy()->subDays(29))->sum('amount');

        // Students who have never paid (defaulters proxy)
        $paidIds = FeePayment::where('organization_id', $orgId)
            ->whereNotNull('student_detail_id')->distinct()->pluck('student_detail_id');
        $unpaidStudents = StudentDetail::where('organization_id', $orgId)
            ->whereNotIn('id', $paidIds)->count();

        $this->kpis = [
            'student_rate'     => $stuRateToday,
            'student_delta'    => round($stuRateToday - $stuRateYest, 1),
            // Until today is marked there is nothing to set against yesterday:
            // the page leaves the "vs yesterday" line out.
            'student_marked'   => $stuTotalToday > 0,
            'teacher_rate'     => $rate($tchPresentToday, $tchTotalToday),
            'collect_rate'     => $collectRate,
            'avg_daily'        => round($last30Sum / 30, 0),
            // What the whole fee is made of (in the Avg / Day tile's place on the page).
            'academic_fee'     => (float) ($this->feeStats['academicFee'] ?? 0),
            'transport_fee'    => (float) ($this->feeStats['transportFee'] ?? 0),
            'last_year_dues'   => (float) ($this->feeStats['lastYearDues'] ?? 0),
            'unpaid_students'  => $unpaidStudents,
            'new_admissions'   => (int) ($this->statsData['newAdmissions'] ?? 0),
        ];
    }

    /**
     * Teacher present/absent per month across the school year. The volume chart
     * reads by date now, but the rate trend above it is still monthly, so this
     * fills that series in one grouped query.
     */
    protected function loadTeacherMonthly(): void
    {
        $now       = Carbon::now();
        $yearStart = $now->month >= 4
            ? Carbon::create($now->year, 4, 1)
            : Carbon::create($now->year - 1, 4, 1);
        $yearEnd   = $yearStart->copy()->addMonths(12)->subDay()->endOfDay();

        $rows = TeacherAttendance::where('organization_id', $this->orgId())
            ->whereBetween('attendance_date', [$yearStart, $yearEnd])
            ->selectRaw("DATE_FORMAT(attendance_date, '%Y-%m') as ym, status, COUNT(*) as c")
            ->groupBy('ym', 'status')->get();

        $by = [];
        foreach ($rows as $r) {
            $by[(string) $r->ym][(int) $r->status] = (int) $r->c;
        }

        $present = $absent = [];
        for ($i = 0; $i < 12; $i++) {
            $ym = $yearStart->copy()->addMonths($i)->format('Y-m');
            $present[] = $by[$ym][self::TCH_PRESENT] ?? 0;
            $absent[]  = $by[$ym][self::TCH_ABSENT] ?? 0;
        }

        $this->teacherMonthlyAttendance = compact('present', 'absent');
    }

    // ─── Monthly attendance % trend (Apr–Mar) ───────────────────────────────────

    protected function loadAttendanceTrendPct(): void
    {
        $pct = function ($present, $absent) {
            $t = $present + $absent;
            return $t > 0 ? round(($present / $t) * 100, 1) : 0;
        };

        $student = $teacher = [];
        $sp = $this->studentMonthlyAttendance['present'] ?? [];
        $sa = $this->studentMonthlyAttendance['absent']  ?? [];
        $tp = $this->teacherMonthlyAttendance['present'] ?? [];
        $ta = $this->teacherMonthlyAttendance['absent']  ?? [];

        for ($i = 0; $i < 12; $i++) {
            $student[] = $pct($sp[$i] ?? 0, $sa[$i] ?? 0);
            $teacher[] = $pct($tp[$i] ?? 0, $ta[$i] ?? 0);
        }

        $this->attendanceTrendPct = [
            'labels'  => $this->attendanceMonths,
            'student' => $student,
            'teacher' => $teacher,
        ];
    }

    // ─── The last 30 days, day by day ────────────────────────────────────────────

    /**
     * The Attendance Rate Trend: each of the last 30 days, the share of
     * students and of teachers present, with the present and absent counts
     * behind every point. A day nobody was marked on (a Sunday, a holiday)
     * has no point; the line runs on to the next day that has one.
     */
    protected function loadAttendanceDailyTrend(): void
    {
        $orgId = $this->orgId();
        $days  = 30;
        $from  = Carbon::today()->subDays($days - 1)->toDateString();

        $read = function (string $model, int $present, int $absent) use ($orgId, $from) {
            $by = [];
            $rows = $model::where('organization_id', $orgId)
                ->where('attendance_date', '>=', $from)
                ->whereIn('status', [$present, $absent])
                ->selectRaw('DATE(attendance_date) as d, status, COUNT(*) as c')
                ->groupBy('d', 'status')->get();
            foreach ($rows as $r) {
                $by[(string) $r->d][(int) $r->status] = (int) $r->c;
            }

            return $by;
        };

        $students = $read(StudentAttendance::class, self::STU_PRESENT, self::STU_ABSENT);
        $teachers = $read(TeacherAttendance::class, self::TCH_PRESENT, self::TCH_ABSENT);

        $pct = fn (int $p, int $a) => $p + $a > 0 ? round($p / ($p + $a) * 100, 1) : null;

        $out = [
            'labels' => [], 'student' => [], 'teacher' => [],
            'studentPresent' => [], 'studentAbsent' => [], 'teacherPresent' => [], 'teacherAbsent' => [],
        ];

        for ($i = $days - 1; $i >= 0; $i--) {
            $day  = Carbon::today()->subDays($i);
            $date = $day->toDateString();

            $sp = $students[$date][self::STU_PRESENT] ?? 0;
            $sa = $students[$date][self::STU_ABSENT] ?? 0;
            $tp = $teachers[$date][self::TCH_PRESENT] ?? 0;
            $ta = $teachers[$date][self::TCH_ABSENT] ?? 0;

            $out['labels'][]         = $day->format('d M');
            $out['student'][]        = $pct($sp, $sa);
            $out['teacher'][]        = $pct($tp, $ta);
            $out['studentPresent'][] = $sp;
            $out['studentAbsent'][]  = $sa;
            $out['teacherPresent'][] = $tp;
            $out['teacherAbsent'][]  = $ta;
        }

        $this->attendanceDailyTrend = $out;
    }

    // ─── The session so far, month by month ─────────────────────────────────────

    /**
     * [Y-m-d => % present] for every day from $from to today that has marks:
     * present / (present + absent) of that one day.
     */
    private function dailyAttendancePct(string $model, int $present, int $absent, Carbon $from): array
    {
        $counts = [];
        $rows = $model::where('organization_id', $this->orgId())
            ->where('attendance_date', '>=', $from->toDateString())
            ->where('attendance_date', '<=', Carbon::today()->endOfDay())
            ->whereIn('status', [$present, $absent])
            ->selectRaw('DATE(attendance_date) as d, status, COUNT(*) as c')
            ->groupBy('d', 'status')->get();
        foreach ($rows as $r) {
            $counts[(string) $r->d][(int) $r->status] = (int) $r->c;
        }

        $pct = [];
        foreach ($counts as $date => $byStatus) {
            $p = $byStatus[$present] ?? 0;
            $a = $byStatus[$absent] ?? 0;
            if ($p + $a > 0) {
                $pct[$date] = $p / ($p + $a) * 100;
            }
        }

        return $pct;
    }

    /**
     * The two Attendance Volume charts: from 1 April of the running session to
     * this month, each month's average attendance % — the students', and the
     * teachers'. A month's figure is the average of its days: every marked
     * day's own % (68% on the 1st, 70% on the 2nd, 50% on the 3rd …) added up
     * and divided by the number of those days. It used to be the month's
     * present over its present + absent, which lets a full-strength day weigh
     * more than a thin one. A day nobody was marked on is not a day of 0%: it
     * is left out. The months still to come are left out too, and a month
     * nobody was marked in has no bar.
     */
    protected function loadMonthlyAttendancePct(): void
    {
        $start = AcademicYear::start();
        $now   = Carbon::now();

        $students = $this->dailyAttendancePct(StudentAttendance::class, self::STU_PRESENT, self::STU_ABSENT, $start);
        $teachers = $this->dailyAttendancePct(TeacherAttendance::class, self::TCH_PRESENT, self::TCH_ABSENT, $start);

        // [Y-m => [the days' percentages]]
        $byMonth = function (array $daily): array {
            $months = [];
            foreach ($daily as $date => $pct) {
                $months[substr((string) $date, 0, 7)][] = $pct;
            }

            return $months;
        };
        $studentMonths = $byMonth($students);
        $teacherMonths = $byMonth($teachers);

        $avg = fn (array $days) => $days ? round(array_sum($days) / count($days), 1) : null;

        $out = [
            'from'   => $start->format('j M Y'),
            'labels' => [], 'student' => [], 'teacher' => [],
            // How many days each average is taken over.
            'studentDays' => [], 'teacherDays' => [],
        ];

        for ($i = 0; $i < 12; $i++) {
            $month = $start->copy()->addMonths($i);
            if ($month->greaterThan($now)) {
                break;
            }
            $ym = $month->format('Y-m');

            $out['labels'][]      = $month->format('M Y');
            $out['student'][]     = $avg($studentMonths[$ym] ?? []);
            $out['teacher'][]     = $avg($teacherMonths[$ym] ?? []);
            $out['studentDays'][] = count($studentMonths[$ym] ?? []);
            $out['teacherDays'][] = count($teacherMonths[$ym] ?? []);
        }

        $this->monthlyAttendancePct = $out;
    }

    // ─── Class-wise attendance % ranking (overall) ──────────────────────────────

    /**
     * Each class's attendance % over its students' whole record, counted the
     * way the volume charts are: the class's own % on each day it was marked
     * (present / present + absent), averaged over those days. It used to be
     * all the class's presents over all its marks, holidays included.
     */
    protected function loadClassAttendanceRank(): void
    {
        $orgId = $this->orgId();

        $strength = StudentDetail::where('organization_id', $orgId)
            ->whereNotNull('standard_id')
            ->selectRaw('standard_id, COUNT(*) as c')
            ->groupBy('standard_id')->pluck('c', 'standard_id');

        // One row per class per day: how many were present, how many marked.
        $days = [];
        $present = [];
        $total = [];
        $marks = StudentAttendance::query()
            ->join('student_details', 'student_details.id', '=', 'student_attendances.student_detail_id')
            ->where('student_details.organization_id', $orgId)
            ->whereIn('student_attendances.status', [self::STU_PRESENT, self::STU_ABSENT])
            ->selectRaw('student_details.standard_id as sid, DATE(student_attendances.attendance_date) as d, '
                . 'SUM(CASE WHEN student_attendances.status = ' . self::STU_PRESENT . ' THEN 1 ELSE 0 END) as p, COUNT(*) as t')
            ->groupBy('sid', 'd')->get();
        foreach ($marks as $m) {
            if ((int) $m->t === 0) {
                continue;
            }
            $days[(int) $m->sid][]  = (int) $m->p / (int) $m->t * 100;
            $present[(int) $m->sid] = ($present[(int) $m->sid] ?? 0) + (int) $m->p;
            $total[(int) $m->sid]   = ($total[(int) $m->sid] ?? 0) + (int) $m->t;
        }

        $rows = [];
        foreach (Standard::where('organization_id', $orgId)->inClassOrder()->get() as $std) {
            if (empty($strength[$std->id])) continue;

            $classDays = $days[$std->id] ?? [];
            $rows[] = [
                'name'    => $std->name,
                'pct'     => $classDays ? round(array_sum($classDays) / count($classDays), 1) : 0,
                'days'    => count($classDays),
                'present' => $present[$std->id] ?? 0,
                'total'   => $total[$std->id] ?? 0,
            ];
        }

        usort($rows, fn ($a, $b) => $b['pct'] <=> $a['pct']);
        $this->classAttendanceRank = $rows;
    }

    // ─── Admissions, date by date ─────────────────────────────────────────────────

    /** [date => admissions] for the school: the admission date, else the day the record was made. */
    private function admissionsByDate(): array
    {
        return $this->admissionDates ??= StudentDetail::where('organization_id', $this->orgId())
            ->selectRaw('DATE(COALESCE(date_of_admission, created_at)) as d, COUNT(*) as c')
            ->groupBy('d')->orderBy('d')
            ->pluck('c', 'd')
            ->filter(fn ($count, $date) => $date)
            ->all();
    }

    /** The school year (its starting year: 2026 = Apr 2026 – Mar 2027) a date falls in. */
    private function schoolYearOf(string $date): int
    {
        $day = Carbon::parse($date);

        return $day->month >= 4 ? (int) $day->year : (int) $day->year - 1;
    }

    /**
     * The year dropdown lists the school years that hold admissions (and the
     * running one), the latest first. It used to list the last six years
     * whatever they held, and opened on the running year: a school whose
     * students were all admitted in earlier years saw an empty graph.
     */
    private function pickAdmissionYear(int $thisAy): void
    {
        $held = [];
        foreach (array_keys($this->admissionsByDate()) as $date) {
            $held[$this->schoolYearOf((string) $date)] = true;
        }

        $years = array_keys($held + [$thisAy => true]);
        rsort($years);
        $this->admissionYears = $years;

        // Open on the running year when it has admissions, else the latest that has.
        $this->admissionYear = (string) (isset($held[$thisAy]) || !$held ? $thisAy : max(array_keys($held)));
    }

    /** How many admission dates the Admissions Trend draws: the latest ones, so the graph never scrolls sideways. */
    public const ADMISSION_DATES_SHOWN = 15;

    /**
     * The Admissions Trend of the selected school year (April → March): one bar
     * for each date students were admitted on, with how many — the last
     * fifteen such dates, so the bars always fit the card (a year with many
     * dates used to scroll sideways). The total over the graph is still the
     * whole year's.
     *
     * Counts on the admission date the school recorded, falling back to when
     * the record was created.
     */
    protected function loadAdmissionsTrend(): void
    {
        $year = (int) ($this->admissionYear ?: Carbon::now()->year);

        $labels = $data = [];
        foreach ($this->admissionsByDate() as $date => $count) {
            if ($this->schoolYearOf((string) $date) !== $year) {
                continue;
            }
            $labels[] = Carbon::parse($date)->format('d M Y');
            $data[]   = (int) $count;
        }

        $this->admissionsTotal  = array_sum($data);
        $this->admissionsTrend  = [
            'labels' => array_slice($labels, -self::ADMISSION_DATES_SHOWN),
            'data'   => array_slice($data, -self::ADMISSION_DATES_SHOWN),
            // How many dates the year holds in all (the graph shows the latest of them).
            'dates'  => count($labels),
        ];
    }

    // ─── Teacher attendance, day by day ─────────────────────────────────────────

    /**
     * Present/absent per day across the selected window, so the volume chart is
     * read by date rather than by month.
     */
    protected function loadTeacherDaily(): void
    {
        $orgId = $this->orgId();
        $days  = max(1, (int) $this->teacherAttFilter);
        $from  = Carbon::today()->subDays($days - 1);

        $rows = TeacherAttendance::where('organization_id', $orgId)
            ->whereDate('attendance_date', '>=', $from->toDateString())
            ->selectRaw('DATE(attendance_date) as d, status, COUNT(*) as c')
            ->groupBy('d', 'status')->get();

        $byDate = [];
        foreach ($rows as $r) {
            $byDate[(string) $r->d][(int) $r->status] = (int) $r->c;
        }

        $labels = $present = $absent = $buckets = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day  = Carbon::today()->subDays($i);
            $date = $day->toDateString();
            $s    = $byDate[$date] ?? [];

            $p = $s[self::TCH_PRESENT] ?? 0;
            $a = $s[self::TCH_ABSENT] ?? 0;
            $h = $s[2] ?? 0;   // half day
            $o = $s[3] ?? 0;   // holiday

            $labels[]  = $day->format('d M');
            $present[] = $p;
            $absent[]  = $a;

            $marked = $p + $a + $h;
            $buckets[] = [
                'date'    => $date,
                'label'   => $day->format('D, d M Y'),
                'present' => $p,
                'absent'  => $a,
                'half'    => $h,
                'holiday' => $o,
                'marked'  => $marked,
                'pct'     => $marked > 0 ? round(($p + 0.5 * $h) / $marked * 100, 1) : 0,
            ];
        }

        $this->teacherDailyAttendance = [
            'labels'  => $labels,
            'present' => $present,
            'absent'  => $absent,
            'days'    => $buckets,
        ];
    }

    /** The day the teacher-volume dropdown is pointing at. */
    public function teacherDay(): array
    {
        $days = $this->teacherDailyAttendance['days'] ?? [];
        foreach ($days as $d) {
            if ($d['date'] === $this->teacherAttDate) {
                return $d;
            }
        }

        return $days ? end($days) : [];
    }

    // ─── Per-class collection rate (%) with defaulters ──────────────────────────

    protected function loadFeeClassRate(): void
    {
        $labels    = $this->feeClassData['labels'] ?? [];
        $collected = $this->feeClassData['collected'] ?? [];
        $remaining = $this->feeClassData['remaining'] ?? [];

        $rows = [];
        foreach ($labels as $i => $name) {
            $c = (float) ($collected[$i] ?? 0);
            $r = (float) ($remaining[$i] ?? 0);
            $t = $c + $r;
            $rows[] = [
                'name'      => $name,
                'collected' => $c,
                'total'     => $t,
                'pct'       => $t > 0 ? round(($c / $t) * 100, 1) : 0,
            ];
        }

        usort($rows, fn ($a, $b) => $b['pct'] <=> $a['pct']);
        $this->feeClassRate = $rows;
    }

    // ─── Transport fee: expected vs collected, by class and by route ────────────

    /**
     * Transport fee has no FeeStructure rows behind it — what a student owes is
     * their route's monthly fee times the months they use the bus, i.e. the
     * months they are billed for (June is off by default, and the pivot row
     * can turn any month on or off). So the expected side is built student by
     * student here — a route's fee is its fare × each of its students' months,
     * added up — then folded two ways: by class, to sit under the main fee
     * chart, and by route.
     *
     * Every route is on the route chart, one with no riders too (it used to
     * stop at the twelve heaviest). A route the school runs with several
     * vehicle types is several rows underneath and one route here, as the
     * Transport page lists it.
     */
    protected function loadTransportFeeData(): void
    {
        $orgId = $this->orgId();

        $riders = DB::table('transportation_students as ts')
            ->join('transportations as t', 't.id', '=', 'ts.transportation_id')
            ->join('student_details as sd', 'sd.id', '=', 'ts.student_detail_id')
            ->where('ts.organization_id', $orgId)
            ->get([
                'ts.student_detail_id',
                'ts.billable_months',
                't.id as route_id',
                't.route_name',
                't.monthly_fee',
                'sd.standard_id',
            ]);

        // Every route of the school, riders or not; a route's vehicle-type rows
        // share one key (their group), so they read as the one route they are.
        $grouped  = \Illuminate\Support\Facades\Schema::hasColumn('transportations', 'route_group');
        $routeKey = [];   // transportation id => the route's key
        $byRoute  = [];   // route key => ['name' =>, 'expected' =>, 'collected' =>, 'riders' => ]
        foreach (DB::table('transportations')->where('organization_id', $orgId)->orderBy('route_name')->orderBy('id')->get() as $t) {
            $key = $grouped && filled($t->route_group) ? 'g:' . $t->route_group : 'r:' . $t->id;
            $routeKey[(int) $t->id] = $key;
            $byRoute[$key] ??= ['name' => $t->route_name ?: 'Route #' . $t->id, 'expected' => 0.0, 'collected' => 0.0, 'riders' => 0];
        }

        $paid = TransportFeePayment::where('organization_id', $orgId)
            ->selectRaw('student_detail_id, SUM(amount) as paid')
            ->groupBy('student_detail_id')
            ->pluck('paid', 'student_detail_id');

        // A student on two routes pays once; the first route seen owns their
        // payments so the collected side is never counted twice.
        $paidClaimed = [];

        $byClass = [];   // standard_id => ['expected' => , 'collected' => ]
        $totalExpected = 0.0;

        foreach ($riders as $r) {
            $expected = (float) $r->monthly_fee * $this->billableMonthCount($r->billable_months);

            $collected = 0.0;
            if (!isset($paidClaimed[$r->student_detail_id])) {
                $collected = (float) ($paid[$r->student_detail_id] ?? 0);
                $paidClaimed[$r->student_detail_id] = true;
            }

            $std = (int) $r->standard_id;
            $byClass[$std]['expected']  = ($byClass[$std]['expected']  ?? 0) + $expected;
            $byClass[$std]['collected'] = ($byClass[$std]['collected'] ?? 0) + $collected;

            $route = $routeKey[(int) $r->route_id] ?? 'r:' . (int) $r->route_id;
            $byRoute[$route] ??= ['name' => $r->route_name ?: 'Route #' . (int) $r->route_id, 'expected' => 0.0, 'collected' => 0.0, 'riders' => 0];
            $byRoute[$route]['expected']  += $expected;
            $byRoute[$route]['collected'] += $collected;
            $byRoute[$route]['riders']++;

            $totalExpected += $expected;
        }

        // Classes follow the same order as the main fee chart, so the two read
        // against each other; classes with no riders are left out.
        $labels = $collectedSeries = $remainingSeries = [];
        foreach ($this->standards as $std) {
            if (!isset($byClass[$std->id])) {
                continue;
            }
            $row = $byClass[$std->id];
            $labels[]          = $std->name;
            $collectedSeries[] = round($row['collected'], 2);
            $remainingSeries[] = round(max(0, $row['expected'] - $row['collected']), 2);
        }

        $this->transportClassData = [
            'labels'    => $labels,
            'collected' => $collectedSeries,
            'remaining' => $remainingSeries,
        ];

        // Routes, heaviest expected first (then by name) — all of them: the card
        // keeps its size and the chart scrolls inside it.
        uasort($byRoute, fn ($a, $b) => [$b['expected'], $a['name']] <=> [$a['expected'], $b['name']]);

        $rLabels = $rCollected = $rRemaining = [];
        foreach ($byRoute as $row) {
            $rLabels[]    = $row['name'];
            $rCollected[] = round($row['collected'], 2);
            $rRemaining[] = round(max(0, $row['expected'] - $row['collected']), 2);
        }

        $this->transportRouteData = [
            'labels'    => $rLabels,
            'collected' => $rCollected,
            'remaining' => $rRemaining,
        ];

        $totalCollected = array_sum(array_map(
            fn ($id) => (float) ($paid[$id] ?? 0),
            array_keys($paidClaimed)
        ));

        $this->transportFeeStats = [
            'expected'  => round($totalExpected, 2),
            'collected' => round($totalCollected, 2),
            'remaining' => round(max(0, $totalExpected - $totalCollected), 2),
            'rate'      => $totalExpected > 0 ? round($totalCollected / $totalExpected * 100, 1) : 0,
            'riders'    => count($paidClaimed),
            'routes'    => count($byRoute),
        ];
    }

    /**
     * How many months a rider is billed for. Null or empty means the default —
     * every month except June — matching HandlesTransportFees.
     */
    private function billableMonthCount($raw): int
    {
        $months = ['apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec', 'jan', 'feb', 'mar'];

        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        if (empty($raw) || !is_array($raw)) {
            return 11;
        }

        $count = 0;
        foreach ($months as $m) {
            $on = array_key_exists($m, $raw) ? (bool) $raw[$m] : ($m !== 'jun');
            if ($on) {
                $count++;
            }
        }

        return $count;
    }

    // ─── Student Performance: the exam ranking ──────────────────────────────────

    /**
     * Students ranked on their exam marks — every paper they have marks for,
     * added up, as a percentage — for the whole school or the class (and
     * section) picked: the first ten, and the last ten of the same ranking.
     * A student in the top ten is not listed again at the foot.
     */
    protected function loadExamRankers(): void
    {
        $orgId = $this->orgId();

        $rows = ExamCopy::join('student_details as sd', 'sd.id', '=', 'exam_copies.student_detail_id')
            ->where('exam_copies.organization_id', $orgId)
            ->where('sd.organization_id', $orgId)
            ->when($this->performerClass, fn ($q) => $q->where('sd.standard_id', $this->performerClass))
            ->when($this->performerSection, fn ($q) => $q->where('sd.section_id', $this->performerSection))
            ->selectRaw('exam_copies.student_detail_id as id, SUM(exam_copies.marks_obtained) as obtained, SUM(exam_copies.max_marks) as max_marks')
            ->groupBy('exam_copies.student_detail_id')
            ->havingRaw('SUM(exam_copies.max_marks) > 0')
            ->get()
            ->map(fn ($r) => [
                'id'       => (int) $r->id,
                'obtained' => (float) $r->obtained,
                'max'      => (float) $r->max_marks,
                'score'    => round((float) $r->obtained / (float) $r->max_marks * 100, 1),
            ])
            // Best percentage first; more marks breaks a tie.
            ->sort(fn ($a, $b) => [$b['score'], $b['obtained']] <=> [$a['score'], $a['obtained']])
            ->values();

        $ranked = $rows->map(fn ($r, $i) => $r + ['rank' => $i + 1]);

        $top    = $ranked->take(10);
        $bottom = $ranked->slice(10)->reverse()->take(10)->values();

        $students = StudentDetail::with(['user', 'standard', 'section'])
            ->whereIn('id', $top->pluck('id')->merge($bottom->pluck('id'))->all())
            ->get()->keyBy('id');

        $card = function (array $r) use ($students) {
            $s = $students[$r['id']] ?? null;

            return [
                'rank'     => $r['rank'],
                'name'     => $s?->user?->name ?? $s?->full_name ?? 'N/A',
                'class'    => $s?->standard?->name ?? '—',
                'section'  => $s?->section?->name ?? '—',
                'photo'    => $s?->user?->profile_photo_url ?? null,
                'score'    => $r['score'],
                'obtained' => $r['obtained'],
                'max'      => $r['max'],
            ];
        };

        $this->topRankers    = $top->map($card)->values()->toArray();
        $this->bottomRankers = $bottom->map($card)->values()->toArray();
    }

    // ─── Lowest-attendance students (needs attention) ───────────────────────────

    protected function loadLowPerformers(): void
    {
        $query = StudentDetail::with(['user', 'standard', 'section'])
            ->where('organization_id', $this->orgId())
            ->whereHas('studentAttendances')   // only students with attendance records
            ->withCount([
                'studentAttendances as present_count' => fn ($q) => $q->where('status', self::STU_PRESENT),
                'studentAttendances as total_count',
            ]);

        if ($this->performerClass)   $query->where('standard_id', $this->performerClass);
        if ($this->performerSection) $query->where('section_id', $this->performerSection);

        // Pull the lowest-present set, then rank precisely by percentage in PHP.
        $this->lowPerformers = $query->orderBy('present_count')
            ->take(15)->get()
            ->map(fn ($s) => [
                'name'    => $s->user?->name ?? $s->full_name ?? 'N/A',
                'class'   => $s->standard?->name ?? '—',
                'section' => $s->section?->name ?? '—',
                'score'   => $s->total_count > 0 ? round(($s->present_count / $s->total_count) * 100, 1) : 0,
            ])
            ->sortBy('score')->take(3)->values()->toArray();
    }

    // ─── Enquiry funnel ─────────────────────────────────────────────────────────

    protected function loadEnquiryStats(): void
    {
        $total     = WebsiteContact::count();
        $responded = WebsiteContact::whereNotNull('remark')->where('remark', '!=', '')->count();

        $this->enquiryStats = [
            'total'     => $total,
            'responded' => $responded,
            'pending'   => max(0, $total - $responded),
            'rate'      => $total > 0 ? round(($responded / $total) * 100, 1) : 0,
        ];
    }

    // ─── Stats Cards ──────────────────────────────────────────────────────────

    protected function loadStats(): void
    {
        $orgId = $this->orgId();
        $today = Carbon::today();

        $totalStudents  = StudentDetail::where('organization_id', $orgId)->count();
        $activeStudents = StudentDetail::where('organization_id', $orgId)->count();

        // Column: attendance_date  |  status tinyint 1=present 0=absent
        $presentToday = StudentAttendance::where('organization_id', $orgId)
            ->whereDate('attendance_date', $today)
            ->where('status', self::STU_PRESENT)
            ->count();

        $absentToday = StudentAttendance::where('organization_id', $orgId)
            ->whereDate('attendance_date', $today)
            ->where('status', self::STU_ABSENT)
            ->count();

        $newAdmissions = StudentDetail::where('organization_id', $orgId)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->count();

        $this->statsData = compact(
            'totalStudents',
            'activeStudents',
            'presentToday',
            'absentToday',
            'newAdmissions'
        );
    }

    // ─── Academic-year months (Apr – Mar) ─────────────────────────────────────

    protected function buildAttendanceMonths(): void
    {
        $now       = Carbon::now();
        $yearStart = $now->month >= 4
            ? Carbon::create($now->year, 4, 1)
            : Carbon::create($now->year - 1, 4, 1);

        $this->attendanceMonths = [];
        for ($i = 0; $i < 12; $i++) {
            $this->attendanceMonths[] = $yearStart->copy()->addMonths($i)->format('M Y');
        }
    }

    // ─── Student Attendance Bar ───────────────────────────────────────────────

    protected function loadStudentAttendance(): void
    {
        $orgId     = $this->orgId();
        $now       = Carbon::now();
        $yearStart = $now->month >= 4
            ? Carbon::create($now->year, 4, 1)
            : Carbon::create($now->year - 1, 4, 1);

        $present = [];
        $absent  = [];

        for ($i = 0; $i < 12; $i++) {
            $mStart = $yearStart->copy()->addMonths($i)->startOfMonth();
            $mEnd   = $yearStart->copy()->addMonths($i)->endOfMonth();

            $present[] = StudentAttendance::where('organization_id', $orgId)
                ->whereBetween('attendance_date', [$mStart, $mEnd])
                ->where('status', self::STU_PRESENT)
                ->count();

            $absent[] = StudentAttendance::where('organization_id', $orgId)
                ->whereBetween('attendance_date', [$mStart, $mEnd])
                ->where('status', self::STU_ABSENT)
                ->count();
        }

        $this->studentMonthlyAttendance = compact('present', 'absent');
    }

    // ─── Student Pie ──────────────────────────────────────────────────────────

    /**
     * The Student Split: today's students, present against absent. It used to
     * cover a window picked from a dropdown (today, or the last 7, 14 or 30
     * days — $attendanceFilter, which the page no longer offers).
     */
    protected function loadStudentPie(): void
    {
        $orgId = $this->orgId();
        $today = Carbon::today();

        $present = StudentAttendance::where('organization_id', $orgId)
            ->whereDate('attendance_date', $today)
            ->where('status', self::STU_PRESENT)
            ->count();

        $absent = StudentAttendance::where('organization_id', $orgId)
            ->whereDate('attendance_date', $today)
            ->where('status', self::STU_ABSENT)
            ->count();

        // The same split as a share of the students marked today.
        $marked     = $present + $absent;
        $presentPct = $marked > 0 ? round($present / $marked * 100, 1) : 0;
        $absentPct  = $marked > 0 ? round(100 - $presentPct, 1) : 0;

        $this->studentPieData = compact('present', 'absent', 'presentPct', 'absentPct');
    }

    // ─── Sections ─────────────────────────────────────────────────────────────

    protected function loadSections(): void
    {
        $this->sections = $this->performerClass
            ? Section::where('standard_id', $this->performerClass)->get()
            : [];
    }

    // ─── Top 3 Students ───────────────────────────────────────────────────────

    protected function loadTopStudents(): void
    {
        $query = StudentDetail::with(['user', 'standard', 'section'])
            ->where('organization_id', $this->orgId())
            ->withCount([
                'studentAttendances as present_count' => fn($q) =>
                $q->where('status', self::STU_PRESENT),
                'studentAttendances as total_count',
            ]);

        if ($this->performerClass)   $query->where('standard_id', $this->performerClass);
        if ($this->performerSection) $query->where('section_id', $this->performerSection);

        $this->topStudents = $query
            ->orderByDesc('present_count')
            ->take(3)
            ->get()
            ->map(fn($s, $i) => [
                'rank'    => $i + 1,
                'name'    => $s->user?->name ?? $s->full_name ?? 'N/A',
                'class'   => $s->standard?->name ?? '—',
                'section' => $s->section?->name ?? '—',
                'photo'   => $s->user?->profile_photo_url ?? null,
                'score'   => $s->total_count > 0
                    ? round(($s->present_count / $s->total_count) * 100, 1)
                    : 0,
            ])
            ->toArray();
    }

    // ─── Class Distribution ───────────────────────────────────────────────────

    protected function loadClassDistribution(): void
    {
        $orgId     = $this->orgId();
        $today     = Carbon::today();
        $standards = Standard::where('organization_id', $orgId)->inClassOrder()->get();

        $labels = $present = $absent = [];

        foreach ($standards as $std) {
            $studentIds = StudentDetail::where('organization_id', $orgId)
                ->where('standard_id', $std->id)->pluck('id');

            $labels[] = $std->name;

            $present[] = StudentAttendance::whereIn('student_detail_id', $studentIds)
                ->whereDate('attendance_date', $today)
                ->where('status', self::STU_PRESENT)
                ->count();

            $absent[] = StudentAttendance::whereIn('student_detail_id', $studentIds)
                ->whereDate('attendance_date', $today)
                ->where('status', self::STU_ABSENT)
                ->count();
        }

        $this->classDistribution = compact('labels', 'present', 'absent');
    }

    // ─── Admin Enquiries ──────────────────────────────────────────────────────

    protected function loadAdminEnquiries(): void
    {
        $this->adminEnquiries = WebsiteContact::latest()->take(3)->get()
            ->map(fn($e) => [
                'id'     => $e->id,
                'name'   => $e->full_name,
                'email'  => $e->email,
                'time'   => $e->created_at->diffForHumans(),
                'status' => $e->remark ? 'Responded' : 'Pending',
            ])->toArray();
    }

    // ─── Arrangements ─────────────────────────────────────────────────────────
    // Schema: original_teacher_id, substitute_teacher_id, teacher_time_table_id, date, reason
    // Class/section/time info resolved through TeacherTimeTable relationship

    protected function loadArrangements(): void
    {
        $orgId = $this->orgId();
        $today = Carbon::today();

        // Find teachers absent today
        $absentTeacherIds = TeacherAttendance::where('organization_id', $orgId)
            ->whereDate('attendance_date', $today)
            ->where('status', self::TCH_ABSENT)
            ->pluck('teacher_detail_id');

        // Load arrangements for absent teachers today
        $this->arrangements = TeacherArrangement::with([
            'originalTeacher',
            'substituteTeacher',
            'teacherTimeTable.standard',
            'teacherTimeTable.section',
        ])
            ->whereDate('date', $today)
            ->whereIn('original_teacher_id', $absentTeacherIds)
            ->get()
            ->map(fn($a) => [
                'id'                    => $a->id,
                'class'                 => $a->teacherTimeTable?->standard?->name ?? '—',
                'section'               => $a->teacherTimeTable?->section?->name  ?? '—',
                'absent_teacher'        => $a->originalTeacher?->name ?? '—',
                'time'                  => $a->teacherTimeTable?->time
                    ?? $a->teacherTimeTable?->period
                    ?? '—',
                'substitute_teacher_id' => $a->substitute_teacher_id,
                'status'                => $a->substitute_teacher_id ? 'assigned' : 'pending',
            ])->toArray();

        // Available teachers = not absent today
        $this->availableTeachers = TeacherDetail::where('organization_id', $orgId)
            ->whereNotIn('id', $absentTeacherIds)
            ->tap(fn ($q) => NameOrder::teachers($q))
            ->get()
            ->map(fn($t) => ['id' => $t->id, 'name' => $t->name])
            ->toArray();
    }

    // ─── Announcements ────────────────────────────────────────────────────────

    protected function loadAnnouncements(): void
    {
        $this->announcements = Announcement::where('organization_id', $this->orgId())
            ->latest()->take(5)->get()
            ->map(fn($a) => [
                'title'  => $a->title,
                'body'   => $a->description ?? $a->body ?? '',
                'type'   => $a->type ?? 'general',
                'pinned' => (bool) ($a->is_pinned ?? false),
                'time'   => $a->created_at->diffForHumans(),
            ])->toArray();
    }

    // ─── Recent Activities ────────────────────────────────────────────────────

    protected function loadRecentActivities(): void
    {
        $orgId      = $this->orgId();
        $activities = [];

        foreach (StudentDetail::with('user')->where('organization_id', $orgId)->latest()->take(3)->get() as $s) {
            $activities[] = [
                'title'       => 'New Admission',
                'description' => ($s->user?->name ?? $s->full_name ?? 'Student') . ' enrolled',
                'time'        => $s->created_at->diffForHumans(),
                'color'       => 'green',
                'ts'          => $s->created_at->timestamp,
            ];
        }

        foreach (FeePayment::with('studentDetail.user')->where('organization_id', $orgId)->latest()->take(3)->get() as $f) {
            $name = $f->studentDetail?->user?->name ?? 'Student';
            $activities[] = [
                'title'       => 'Fee Paid',
                'description' => '₹' . number_format($f->amount ?? 0) . " from {$name}",
                'time'        => $f->created_at->diffForHumans(),
                'color'       => 'blue',
                'ts'          => $f->created_at->timestamp,
            ];
        }

        usort($activities, fn($a, $b) => $b['ts'] - $a['ts']);
        $this->recentActivities = array_slice($activities, 0, 6);
    }

    // ─── Today's Homework ─────────────────────────────────────────────────────

    protected function loadTodayHomework(): void
    {
        $this->todayHomework = [];
    }

    // ─── Fee totals ───────────────────────────────────────────────────────────

    /**
     * The school's whole fee and what has come in, counted as the dashboard
     * (Livewire\Admin\Home::loadFeeOverview) counts them: change one, change
     * both. The fee is every student's academic fee (the active heads of their
     * class: the whole class's and their own section's), their own Last Year
     * Dues, and every rider's transport fee (route fee x the months they are
     * billed for). Collected is academic and transport, from both payment
     * tables, taken this session (1 April) up to today.
     *
     * It used to add up the fee heads themselves, not what the students owe
     * on them, so the collection % read far too high.
     */
    protected function loadFeeStatsStatic(): void
    {
        $orgId = $this->orgId();

        $heads = FeeStructure::where('organization_id', $orgId)
            ->where('is_active', true)->where('fee_type', 'academic')
            ->get(['standard_id', 'section_id', 'amount'])
            ->groupBy('standard_id');

        $pairs = StudentDetail::where('organization_id', $orgId)
            ->selectRaw('standard_id, section_id, COUNT(*) as students')
            ->groupBy('standard_id', 'section_id')
            ->get();

        $academic = 0.0;
        foreach ($pairs as $pair) {
            $fee = ($heads[$pair->standard_id] ?? collect())
                ->filter(fn ($h) => $h->section_id === null || (int) $h->section_id === (int) $pair->section_id)
                ->sum('amount');
            $academic += (float) $fee * (int) $pair->students;
        }

        $lastYearDues = FeeStructure::ownTotalForSchool($orgId); // students' own Last Year Dues

        $transport = 0.0;
        $riders = DB::table('transportation_students as ts')
            ->join('transportations as t', 'ts.transportation_id', '=', 't.id')
            ->join('student_details as sd', 'ts.student_detail_id', '=', 'sd.id')
            ->where('ts.organization_id', $orgId)
            ->get(['ts.billable_months', 't.monthly_fee']);
        foreach ($riders as $row) {
            $transport += (float) $row->monthly_fee * $this->billableMonthCount($row->billable_months);
        }

        $from  = AcademicYear::start()->toDateString();
        $today = Carbon::today()->toDateString();

        $collected = (float) FeePayment::where('organization_id', $orgId)
                ->whereIn('fee_type', ['academic', 'transport'])
                ->whereDate('payment_date', '>=', $from)
                ->whereDate('payment_date', '<=', $today)
                ->sum('amount')
            + (float) TransportFeePayment::where('organization_id', $orgId)
                ->whereDate('payment_date', '>=', $from)
                ->whereDate('payment_date', '<=', $today)
                ->sum('amount');

        $totalFee = $academic + $lastYearDues + $transport;

        $this->feeStats = [
            'totalFee'     => $totalFee,
            'collected'    => round($collected, 2),
            'remaining'    => max(0, $totalFee - $collected),
            'transportFee' => $transport,
            'academicFee'  => $academic,
            'lastYearDues' => $lastYearDues,
        ];
    }

    /**
     * Fee Collection by Class (and, out of it, Recovery Rate by Class): each
     * class's academic fee is what its students owe — the class's active heads
     * (the whole class's and each student's own section's) for every student
     * in it, plus those students' own Last Year Dues — and collected is the
     * academic fee those students have paid this session, up to today.
     *
     * It used to set the fee heads of the class, counted once, against every
     * payment made in the class.
     */
    protected function loadFeeClassDataStatic(): void
    {
        $orgId     = $this->orgId();
        $standards = Standard::where('organization_id', $orgId)->inClassOrder()->get();
        $labels    = $standards->pluck('name')->toArray();

        $heads = FeeStructure::where('organization_id', $orgId)
            ->where('is_active', true)->where('fee_type', 'academic')
            ->get(['standard_id', 'section_id', 'amount'])
            ->groupBy('standard_id');

        $students = StudentDetail::where('organization_id', $orgId)
            ->get(['id', 'standard_id', 'section_id']);

        // Each class's bill: its heads for every student in it…
        $billable = [];
        foreach ($students->groupBy(fn ($s) => $s->standard_id . ':' . $s->section_id) as $group) {
            $first = $group->first();
            $fee = ($heads[$first->standard_id] ?? collect())
                ->filter(fn ($h) => $h->section_id === null || (int) $h->section_id === (int) $first->section_id)
                ->sum('amount');
            $billable[$first->standard_id] = ($billable[$first->standard_id] ?? 0) + (float) $fee * $group->count();
        }

        // …and the students' own Last Year Dues, with the class they are in now.
        $classOf = $students->pluck('standard_id', 'id');
        foreach (FeeStructure::ownRows($orgId, [], 'academic', true) as $row) {
            $class = $classOf[$row->student_detail_id] ?? null;
            if ($class !== null) {
                $billable[$class] = ($billable[$class] ?? 0) + (float) $row->amount;
            }
        }

        // Academic fee paid this session, by the class the student is in now.
        $paid = [];
        $payments = FeePayment::where('organization_id', $orgId)
            ->where('fee_type', 'academic')
            ->whereDate('payment_date', '>=', AcademicYear::start()->toDateString())
            ->whereDate('payment_date', '<=', Carbon::today()->toDateString())
            ->selectRaw('student_detail_id, SUM(amount) as total')
            ->groupBy('student_detail_id')
            ->pluck('total', 'student_detail_id');
        foreach ($payments as $studentId => $total) {
            $class = $classOf[$studentId] ?? null;
            if ($class !== null) {
                $paid[$class] = ($paid[$class] ?? 0) + (float) $total;
            }
        }

        $collected = [];
        $remaining = [];

        foreach ($standards as $std) {
            $classFeeTotal  = (float) ($billable[$std->id] ?? 0);
            $classCollected = (float) ($paid[$std->id] ?? 0);

            $collected[] = round($classCollected, 2);
            $remaining[] = round(max(0, $classFeeTotal - $classCollected), 2);
        }

        $this->feeClassData = [
            'labels'    => $labels,
            'collected' => $collected,
            'remaining' => $remaining,
        ];
    }

    // ─── Helper ───────────────────────────────────────────────────────────────

    protected function orgId(): int
    {
        return Auth::user()->organization_id;
    }

    public function render()
    {
        return view('livewire.admin.analytics');
    }
}
