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
// use App\Models\Admin\Homework;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Admin\TeacherArrangement;
use App\Models\Admin\TransportFeePayment;
use App\Models\Teacher\TeacherAttendance;
use App\Models\Teacher\TeacherDetail;
use App\Models\WebsiteContact;
use Illuminate\Support\Facades\DB;

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
    public $classAttendanceRank  = [];   // per-class attendance % ranking (30 days)
    public $admissionsTrend      = [];   // new admissions per month (Apr–Mar)
    public $feeClassRate         = [];   // per-class collection % with defaulters
    public $transportClassData   = [];   // transport fee: collected vs remaining per class
    public $transportRouteData   = [];   // transport fee: collected vs remaining per route
    public $transportFeeStats    = [];   // transport totals: expected / collected / remaining
    public $lowPerformers        = [];   // lowest-attendance students (needs attention)
    public $enquiryStats         = [];   // enquiry funnel counts

    protected $queryString = ['attendanceFilter', 'performerClass', 'performerSection'];

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
            ->orderBy('id')->get();

        $now = Carbon::now();
        $thisAy = $now->month >= 4 ? (int) $now->year : (int) $now->year - 1;
        $this->admissionYears = range($thisAy, $thisAy - 5);
        $this->admissionYear  = (string) $thisAy;

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
        $this->loadTopStudents();
        $this->loadLowPerformers();
    }

    public function updatedPerformerSection(): void
    {
        $this->loadTopStudents();
        $this->loadLowPerformers();
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
        $this->loadTeacherDaily();
        $this->loadSections();
        $this->loadTopStudents();
        $this->loadAdminEnquiries();
        $this->loadArrangements();
        $this->loadAnnouncements();
        $this->loadFeeStatsStatic();
        $this->loadFeeClassDataStatic();

        // Deeper, analytics-only widgets
        $this->loadTeacherMonthly();
        $this->loadAttendanceTrendPct();
        $this->loadClassAttendanceRank();
        $this->loadAdmissionsTrend();
        $this->loadFeeClassRate();
        $this->loadTransportFeeData();
        $this->loadLowPerformers();
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
            'teacher_rate'     => $rate($tchPresentToday, $tchTotalToday),
            'collect_rate'     => $collectRate,
            'avg_daily'        => round($last30Sum / 30, 0),
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

    // ─── Class-wise attendance % ranking (last 30 days) ─────────────────────────

    protected function loadClassAttendanceRank(): void
    {
        $orgId = $this->orgId();
        $from  = Carbon::today()->subDays(29);
        $rows  = [];

        foreach (Standard::where('organization_id', $orgId)->orderBy('id')->get() as $std) {
            $studentIds = StudentDetail::where('organization_id', $orgId)
                ->where('standard_id', $std->id)->pluck('id');
            if ($studentIds->isEmpty()) continue;

            $present = StudentAttendance::whereIn('student_detail_id', $studentIds)
                ->where('attendance_date', '>=', $from)->where('status', self::STU_PRESENT)->count();
            $total = StudentAttendance::whereIn('student_detail_id', $studentIds)
                ->where('attendance_date', '>=', $from)->count();

            $rows[] = [
                'name' => $std->name,
                'pct'  => $total > 0 ? round(($present / $total) * 100, 1) : 0,
                'present' => $present,
                'total'   => $total,
            ];
        }

        usort($rows, fn ($a, $b) => $b['pct'] <=> $a['pct']);
        $this->classAttendanceRank = $rows;
    }

    // ─── Admissions trend (new students per month, Apr–Mar) ─────────────────────

    /**
     * New admissions per month across the selected school year (April → March).
     *
     * Counts on the admission date the school actually recorded, falling back to
     * when the record was created. Counting created_at alone read as empty for
     * any school whose students were imported in one go — every admission
     * landed in a single month and the other eleven showed nothing.
     */
    protected function loadAdmissionsTrend(): void
    {
        $orgId     = $this->orgId();
        $year      = (int) ($this->admissionYear ?: Carbon::now()->year);
        $yearStart = Carbon::create($year, 4, 1)->startOfDay();
        $yearEnd   = Carbon::create($year + 1, 3, 31)->endOfDay();

        // One grouped query rather than twelve counts.
        $rows = StudentDetail::where('organization_id', $orgId)
            ->whereRaw('COALESCE(date_of_admission, created_at) BETWEEN ? AND ?', [$yearStart, $yearEnd])
            ->selectRaw("DATE_FORMAT(COALESCE(date_of_admission, created_at), '%Y-%m') as ym, COUNT(*) as c")
            ->groupBy('ym')->pluck('c', 'ym');

        $labels = $data = [];
        for ($i = 0; $i < 12; $i++) {
            $month    = $yearStart->copy()->addMonths($i);
            $labels[] = $month->format('M y');
            $data[]   = (int) ($rows[$month->format('Y-m')] ?? 0);
        }

        $this->admissionsTrend  = ['labels' => $labels, 'data' => $data];
        $this->admissionsTotal  = array_sum($data);
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
     * their route's monthly fee times the months they are billed for (June is
     * off by default, and the pivot row can turn any month on or off). So the
     * expected side is built student by student here, then folded up two ways:
     * by class, to sit under the main fee chart, and by route.
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

        $paid = TransportFeePayment::where('organization_id', $orgId)
            ->selectRaw('student_detail_id, SUM(amount) as paid')
            ->groupBy('student_detail_id')
            ->pluck('paid', 'student_detail_id');

        // A student on two routes pays once; the first route seen owns their
        // payments so the collected side is never counted twice.
        $paidClaimed = [];

        $byClass = [];   // standard_id => ['expected' => , 'collected' => ]
        $byRoute = [];   // route_id    => ['name' =>, 'expected' =>, 'collected' => ]
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

            $route = (int) $r->route_id;
            $byRoute[$route]['name']      = $r->route_name ?: 'Route #' . $route;
            $byRoute[$route]['expected']  = ($byRoute[$route]['expected']  ?? 0) + $expected;
            $byRoute[$route]['collected'] = ($byRoute[$route]['collected'] ?? 0) + $collected;

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

        // Routes, heaviest expected first, capped so the axis stays readable.
        uasort($byRoute, fn ($a, $b) => $b['expected'] <=> $a['expected']);
        $byRoute = array_slice($byRoute, 0, 12, true);

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

    protected function loadStudentPie(): void
    {
        $orgId = $this->orgId();
        $from  = Carbon::now()->subDays((int) $this->attendanceFilter - 1)->startOfDay();

        $present = StudentAttendance::where('organization_id', $orgId)
            ->where('attendance_date', '>=', $from)
            ->where('status', self::STU_PRESENT)
            ->count();

        $absent = StudentAttendance::where('organization_id', $orgId)
            ->where('attendance_date', '>=', $from)
            ->where('status', self::STU_ABSENT)
            ->count();

        $this->studentPieData = compact('present', 'absent');
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
        $standards = Standard::where('organization_id', $orgId)->orderBy('id')->get();

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

    // ─── Fee – STATIC (dynamic implementation pending) ───────────────────────

    protected function loadFeeStatsStatic(): void
    {
        $orgId         = $this->orgId();
        $totalFee      = FeeStructure::where('organization_id', $orgId)->where('is_active', true)->sum('amount');
        $collected     = FeePayment::where('organization_id', $orgId)->sum('amount');
        $transportFee  = FeeStructure::where('organization_id', $orgId)->where('is_active', true)->where('fee_type', 'transport')->sum('amount');

        $this->feeStats = [
            'totalFee'     => $totalFee,
            'collected'    => $collected,
            'remaining'    => max(0, $totalFee - $collected),
            'transportFee' => $transportFee,
        ];
    }

    protected function loadFeeClassDataStatic(): void
    {
        $orgId     = $this->orgId();
        $standards = Standard::where('organization_id', $orgId)->orderBy('id')->get();
        $labels    = $standards->pluck('name')->toArray();

        $collected = [];
        $remaining = [];

        foreach ($standards as $std) {
            $classFeeTotal = FeeStructure::where('organization_id', $orgId)
                ->where('standard_id', $std->id)->where('is_active', true)->sum('amount');
            $classCollected = FeePayment::where('organization_id', $orgId)
                ->where('standard_id', $std->id)->sum('amount');

            $collected[] = (float) $classCollected;
            $remaining[] = (float) max(0, $classFeeTotal - $classCollected);
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
