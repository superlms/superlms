<?php

namespace App\Livewire\SuperAdmin;

use Livewire\Component;
use App\Models\Organization;
use App\Models\User;
use App\Models\Admin\RateLms;
use App\Models\Admin\ContactSuperAdmin;
use App\Models\WebsiteContact;
use App\Models\WebsiteDemo;
use App\Models\SuperAdmin\CreditQuery;
use App\Models\SuperAdmin\SuperAdminFeePayment;
use App\Models\Student\StudentAttendance;
use App\Models\Teacher\TeacherAttendance;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\TransportFeePayment;
use App\Support\AcademicYear;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Dashboard extends Component
{
    public $stats         = [];
    public $recentActivities = [];
    public $loading       = true;
    public $selectedPeriod = '7days';
    public $chartData     = [];
    public $topSchools    = [];
    public $systemHealth  = [];

    protected $listeners = ['refreshDashboard' => 'refreshData'];

    public function mount(): void
    {
        $this->loading = false;
    }

    public function updatedSelectedPeriod(): void
    {
        // Period change is handled reactively via render()
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    protected function calculateMonthlyGrowth(): float|int
    {
        $now          = Carbon::now();
        $currentMonth = Organization::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->count();

        $lastMonthDate = $now->copy()->subMonth();
        $lastMonth     = Organization::whereMonth('created_at', $lastMonthDate->month)
            ->whereYear('created_at', $lastMonthDate->year)
            ->count();

        return $lastMonth > 0
            ? round((($currentMonth - $lastMonth) / $lastMonth) * 100, 2)
            : ($currentMonth > 0 ? 100 : 0);
    }

    protected function getAverageStudentsPerSchool(): int
    {
        $totalStudents = DB::table('users')->where('role', 'user')->count();
        $totalSchools  = DB::table('organizations')->where('status', true)->count();

        return $totalSchools > 0 ? (int) round($totalStudents / $totalSchools) : 0;
    }

    protected function formatBytes(int|float $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow   = min($pow, count($units) - 1);
        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }

    protected function measureResponseTime(): float
    {
        $start = microtime(true);
        DB::table('organizations')->count();
        return round((microtime(true) - $start) * 1000, 2);
    }

    // ── System Health ─────────────────────────────────────────────────────────

    protected function checkDatabaseHealth(): array
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'healthy', 'message' => 'Database connected', 'color' => 'green'];
        } catch (\Exception $e) {
            return ['status' => 'critical', 'message' => 'Connection failed', 'color' => 'red'];
        }
    }

    protected function checkStorageHealth(): array
    {
        $free       = disk_free_space(base_path());
        $total      = disk_total_space(base_path());
        $percentage = $total > 0 ? round(($free / $total) * 100, 2) : 0;

        return [
            'status'     => $percentage > 20 ? 'healthy' : ($percentage > 10 ? 'warning' : 'critical'),
            'message'    => 'Storage check',
            'free_space' => $this->formatBytes($free),
            'percentage' => $percentage,
            'color'      => $percentage > 20 ? 'green' : ($percentage > 10 ? 'yellow' : 'red'),
        ];
    }

    protected function checkSystemHealth(): array
    {
        $responseTime = $this->measureResponseTime();
        return [
            'database'    => $this->checkDatabaseHealth(),
            'storage'     => $this->checkStorageHealth(),
            'performance' => [
                'status'        => $responseTime < 100 ? 'excellent' : ($responseTime < 500 ? 'good' : 'slow'),
                'response_time' => $responseTime . 'ms',
                'color'         => $responseTime < 100 ? 'green' : ($responseTime < 500 ? 'yellow' : 'red'),
            ],
        ];
    }

    // ── Home in the school dashboard's look ───────────────────────────────────

    /** Days on the Attendance Trend and Fee Collection charts, today included. */
    private const TREND_DAYS = 30;

    /** Cache keys of the figures below; they are for one day, so the date is part of the key. */
    private function homeCacheKey(string $what): string
    {
        return 'sa_home_' . $what . '_' . now()->toDateString();
    }

    /**
     * Every school's student and teacher attendance over the last 30 days,
     * counted as each school's own dashboard counts it (1 present, 0 absent,
     * 2 half day; holidays left out): [kind => [org id => [date => [status => count]]]].
     */
    protected function attendanceBySchool(): array
    {
        return cache()->remember($this->homeCacheKey('attendance'), 300, function () {
            $from   = now()->subDays(self::TREND_DAYS - 1)->toDateString();
            $to     = now()->toDateString();
            $orgIds = Organization::pluck('id')->all();

            $read = function ($query) use ($from, $to, $orgIds): array {
                $out  = [];
                $rows = $query->whereIn('organization_id', $orgIds)
                    ->where('attendance_date', '>=', $from)
                    ->where('attendance_date', '<=', $to)
                    ->whereIn('status', [0, 1, 2])
                    ->selectRaw('organization_id as o, attendance_date as d, status, COUNT(*) as c')
                    ->groupBy('o', 'd', 'status')
                    ->get();
                foreach ($rows as $r) {
                    $out[(int) $r->o][substr((string) $r->d, 0, 10)][(int) $r->status] = (int) $r->c;
                }
                return $out;
            };

            return [
                'students' => $read(StudentAttendance::query()),
                'teachers' => $read(TeacherAttendance::query()),
            ];
        });
    }

    /** The 30 days across all schools, oldest first, in the school dashboard's row shape. */
    protected function attendanceTrend(array $attendance): array
    {
        $sum = function (array $bySchool, string $date): array {
            $present = $absent = $half = 0;
            foreach ($bySchool as $days) {
                $present += $days[$date][1] ?? 0;
                $absent  += $days[$date][0] ?? 0;
                $half    += $days[$date][2] ?? 0;
            }
            return [$present, $absent, $half];
        };

        $rows = [];
        for ($i = self::TREND_DAYS - 1; $i >= 0; $i--) {
            $day  = now()->subDays($i);
            $date = $day->toDateString();

            [$sPresent, $sAbsent, $sHalf] = $sum($attendance['students'], $date);
            [$tPresent, $tAbsent, $tHalf] = $sum($attendance['teachers'], $date);
            $sMarked = $sPresent + $sAbsent + $sHalf;
            $tMarked = $tPresent + $tAbsent + $tHalf;

            $rows[] = [
                'date'            => $date,
                'label'           => $day->format('d M'),
                'student_present' => $sPresent,
                'student_absent'  => $sAbsent,
                'student_half'    => $sHalf,
                'student_pct'     => $sMarked > 0 ? round(($sPresent + 0.5 * $sHalf) / $sMarked * 100, 1) : 0,
                'teacher_present' => $tPresent,
                'teacher_absent'  => $tAbsent,
                'teacher_half'    => $tHalf,
                'teacher_pct'     => $tMarked > 0 ? round(($tPresent + 0.5 * $tHalf) / $tMarked * 100, 1) : 0,
            ];
        }

        return $rows;
    }

    /**
     * Fee the schools have taken, academic and transport from both payment
     * tables (as each school's dashboard counts it), from the start of this
     * session (or 30 days back, if that is earlier) up to today:
     * [org id => [date => amount]].
     */
    protected function schoolFees(): array
    {
        return cache()->remember($this->homeCacheKey('fee'), 300, function () {
            $from = min(
                AcademicYear::start()->toDateString(),
                now()->subDays(self::TREND_DAYS - 1)->toDateString()
            );
            $to     = now()->toDateString();
            $orgIds = Organization::pluck('id')->all();

            $byDay   = [];
            $queries = [
                FeePayment::query()->whereIn('fee_type', ['academic', 'transport']),
                TransportFeePayment::query(),
            ];
            foreach ($queries as $query) {
                $rows = $query->whereIn('organization_id', $orgIds)
                    ->whereDate('payment_date', '>=', $from)
                    ->whereDate('payment_date', '<=', $to)
                    ->selectRaw('organization_id as o, DATE(payment_date) as d, SUM(amount) as total')
                    ->groupBy('o', 'd')
                    ->get();
                foreach ($rows as $r) {
                    $o = (int) $r->o;
                    $d = substr((string) $r->d, 0, 10);
                    $byDay[$o][$d] = ($byDay[$o][$d] ?? 0) + (float) $r->total;
                }
            }

            return $byDay;
        });
    }

    /** All schools' fee: the 30-day series, today, this month and this session. */
    protected function feeSummary(array $byDay): array
    {
        $today       = now()->toDateString();
        $sessionFrom = AcademicYear::start()->toDateString();
        $monthFrom   = now()->startOfMonth()->toDateString();

        $totals = [];
        foreach ($byDay as $days) {
            foreach ($days as $date => $amount) {
                $totals[$date] = ($totals[$date] ?? 0) + $amount;
            }
        }

        $labels = $data = [];
        for ($i = self::TREND_DAYS - 1; $i >= 0; $i--) {
            $day      = now()->subDays($i);
            $labels[] = $day->format('d M');
            $data[]   = round($totals[$day->toDateString()] ?? 0, 2);
        }

        $since = fn (string $from) => array_sum(array_filter(
            $totals,
            fn ($amount, $date) => $date >= $from && $date <= $today,
            ARRAY_FILTER_USE_BOTH
        ));

        return [
            'labels'        => $labels,
            'data'          => $data,
            'range_total'   => array_sum($data),
            'today'         => $totals[$today] ?? 0,
            'month'         => $since($monthFrom),
            'session'       => $since($sessionFrom),
            'session_from'  => AcademicYear::start()->format('d M Y'),
            'schools_today' => count(array_filter($byDay, fn ($days) => ($days[$today] ?? 0) > 0)),
        ];
    }

    /** One row a school, A–Z: its people, today's attendance and its fee. */
    protected function schoolsToday(array $attendance, array $fees): array
    {
        $today       = now()->toDateString();
        $sessionFrom = AcademicYear::start()->toDateString();

        return Organization::select(['id', 'name', 'logo', 'status'])
            ->withCount([
                'users as students_count' => fn($q) => $q->where('role', 'user'),
                'users as teachers_count' => fn($q) => $q->where('role', 'teacher'),
            ])
            ->get()
            ->map(function ($s) use ($attendance, $fees, $today, $sessionFrom) {
                $stu      = $attendance['students'][$s->id][$today] ?? [];
                $tch      = $attendance['teachers'][$s->id][$today] ?? [];
                $students = (int) $s->students_count;
                $teachers = (int) $s->teachers_count;

                $session = 0;
                foreach ($fees[$s->id] ?? [] as $date => $amount) {
                    if ($date >= $sessionFrom && $date <= $today) {
                        $session += $amount;
                    }
                }

                return [
                    'id'              => $s->id,
                    'name'            => $s->name,
                    'logo'            => $s->logo,
                    'status'          => (bool) $s->status,
                    'students'        => $students,
                    'teachers'        => $teachers,
                    'student_marked'  => ! empty($stu),
                    'student_present' => $stu[1] ?? 0,
                    'student_pct'     => $students > 0 ? round(($stu[1] ?? 0) / $students * 100) : 0,
                    'teacher_marked'  => ! empty($tch),
                    'teacher_present' => $tch[1] ?? 0,
                    'teacher_pct'     => $teachers > 0 ? round(($tch[1] ?? 0) / $teachers * 100) : 0,
                    'fee_today'       => $fees[$s->id][$today] ?? 0,
                    'fee_session'     => $session,
                ];
            })
            ->sortBy(fn ($r) => mb_strtolower(trim((string) $r['name'])))
            ->values()
            ->toArray();
    }

    /** The Super Admin's own fee from schools, counted as Analytics → Fee counts it. */
    protected function superAdminFee(): array
    {
        $total     = (float) SuperAdminFeePayment::sum('amount');
        $collected = (float) SuperAdminFeePayment::paid()->sum('amount');

        return [
            'total'      => $total,
            'collected'  => $collected,
            'pending'    => max(0, $total - $collected),
            'this_month' => (float) SuperAdminFeePayment::paid()->where('created_at', '>=', now()->startOfMonth())->sum('amount'),
            'pct'        => $total > 0 ? round($collected / $total * 100, 1) : 0,
        ];
    }

    /**
     * Quick Access tiles. A sub-super-admin sees only the screens granted to
     * them and never Users, as on Quick Links and the sidebar.
     */
    protected function quickTiles(): array
    {
        $tiles = [
            ['label' => 'Schools',           'route' => 'super-admin.schools',           'icon' => 'building-library'],
            ['label' => 'Students',          'route' => 'super-admin.students',          'icon' => 'users'],
            ['label' => 'Teachers',          'route' => 'super-admin.teachers',          'icon' => 'academic-cap'],
            ['label' => 'Fees',              'route' => 'super-admin.fees',              'icon' => 'currency-rupee'],
            ['label' => 'Payroll',           'route' => 'super-admin.payroll',           'icon' => 'banknotes'],
            ['label' => 'Analytics',         'route' => 'super-admin.analytics',         'icon' => 'chart-bar'],
            ['label' => 'Reports',           'route' => 'super-admin.reports',           'icon' => 'document-chart-bar'],
            ['label' => 'Push Notification', 'route' => 'super-admin.push-notification', 'icon' => 'megaphone'],
            ['label' => 'Support',           'route' => 'super-admin.support',           'icon' => 'lifebuoy'],
            ['label' => 'Enquiries',         'route' => 'super-admin.enquiries',         'icon' => 'chat-bubble-oval-left-ellipsis'],
            ['label' => 'Users',             'route' => 'super-admin.users',             'icon' => 'user-group'],
            ['label' => 'Documents',         'route' => 'super-admin.documents',         'icon' => 'folder-open'],
        ];

        $user = auth()->user();

        return array_values(array_filter($tiles, function ($t) use ($user) {
            if (! Route::has($t['route'])) {
                return false;
            }
            if ($user && $user->isSubSuperAdmin()) {
                return $t['route'] !== 'super-admin.users'
                    && in_array($t['route'], (array) $user->permissions, true);
            }
            return true;
        }));
    }

    // ── Public Actions ────────────────────────────────────────────────────────

    public function loginAsSchool(int $schoolId): void
    {
        $school = Organization::find($schoolId);
        if ($school) {
            Session::put([
                'impersonated_school_id'   => $schoolId,
                'impersonated_school_name' => $school->name,
                'is_impersonating'         => true,
                'impersonation_time'       => now(),
            ]);

            $this->dispatch('openSchoolDashboard', [
                'url'         => route('admin.quick-links', ['organization' => $school->slug ?? $school->id]),
                'school_name' => $school->name,
            ]);
            return;
        }

        $this->addError('school', 'School not found');
    }

    public function refreshData(): void
    {
        cache()->forget('super_admin_dashboard_stats');
        cache()->forget('sa_top_schools_students');
        cache()->forget('sa_top_schools_teachers');
        cache()->forget('sa_monthly_registrations');
        cache()->forget($this->homeCacheKey('attendance'));
        cache()->forget($this->homeCacheKey('fee'));

        $this->dispatch('notify', ['message' => 'Dashboard refreshed successfully', 'type' => 'success']);
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        // ── Core counts ──────────────────────────────────────────────────────
        $totalSchools  = Organization::count();
        $totalStudents = User::where('role', 'user')->count();
        $totalTeachers = User::where('role', 'teacher')->count();
        $avgStudentsPerSchool = $this->getAverageStudentsPerSchool();

        // ── Top 5 schools by students ────────────────────────────────────────
        $topSchoolsByStudents = cache()->remember('sa_top_schools_students', 300, function () {
            return Organization::select(['id', 'name', 'logo'])
                ->withCount(['users as students_count' => fn($q) => $q->where('role', 'user')])
                ->orderByDesc('students_count')
                ->take(5)
                ->get()
                ->values()
                ->map(fn($s, $i) => [
                    'rank'           => $i + 1,
                    'id'             => $s->id,
                    'name'           => $s->name,
                    'logo'           => $s->logo,
                    'students_count' => $s->students_count,
                ])
                ->toArray();
        });

        // ── Top 5 schools by teachers ────────────────────────────────────────
        $topSchoolsByTeachers = cache()->remember('sa_top_schools_teachers', 300, function () {
            return Organization::select(['id', 'name', 'logo'])
                ->withCount(['users as teachers_count' => fn($q) => $q->where('role', 'teacher')])
                ->orderByDesc('teachers_count')
                ->take(5)
                ->get()
                ->values()
                ->map(fn($s, $i) => [
                    'rank'           => $i + 1,
                    'id'             => $s->id,
                    'name'           => $s->name,
                    'logo'           => $s->logo,
                    'teachers_count' => $s->teachers_count,
                ])
                ->toArray();
        });

        // ── Recent 6 schools ─────────────────────────────────────────────────
        $recentSchools = Organization::select(['id', 'name', 'logo', 'status', 'created_at'])
            ->withCount([
                'users as students_count' => fn($q) => $q->where('role', 'user'),
                'users as teachers_count' => fn($q) => $q->where('role', 'teacher'),
            ])
            ->latest()
            ->take(6)
            ->get()
            ->map(fn($s) => [
                'id'             => $s->id,
                'name'           => $s->name,
                'logo'           => $s->logo,
                'status'         => $s->status,          // boolean: true=active
                'students_count' => $s->students_count,
                'teachers_count' => $s->teachers_count,
                'created_at'     => $s->created_at->format('M d, Y'),
            ])
            ->toArray();

        // ── Recent Activities (mixed: orgs + students + teachers) ────────────
        $recentOrgs = Organization::select(['id', 'name', 'logo', 'created_at'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($o) => [
                'type'        => 'school',
                'title'       => 'New school registered',
                'description' => $o->name,
                'icon'        => 'building-office-2',
                'color'       => 'emerald',
                'created_at'  => $o->created_at,
                'time'        => $o->created_at->diffForHumans(),
            ]);

        $recentStudents = User::select(['id', 'name', 'organization_id', 'created_at'])
            ->where('role', 'user')
            ->with('organization:id,name')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($u) => [
                'type'        => 'student',
                'title'       => 'New student enrolled',
                'description' => $u->name . ($u->organization ? ' · ' . $u->organization->name : ''),
                'icon'        => 'user',
                'color'       => 'blue',
                'created_at'  => $u->created_at,
                'time'        => $u->created_at->diffForHumans(),
            ]);

        $recentTeachers = User::select(['id', 'name', 'organization_id', 'created_at'])
            ->where('role', 'teacher')
            ->with('organization:id,name')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($u) => [
                'type'        => 'teacher',
                'title'       => 'New teacher joined',
                'description' => $u->name . ($u->organization ? ' · ' . $u->organization->name : ''),
                'icon'        => 'academic-cap',
                'color'       => 'violet',
                'created_at'  => $u->created_at,
                'time'        => $u->created_at->diffForHumans(),
            ]);

        $recentActivities = $recentOrgs
            ->concat($recentStudents)
            ->concat($recentTeachers)
            ->sortByDesc('created_at')
            ->take(10)
            ->values()
            ->toArray();

        // ── Monthly Registrations (last 12 months) ───────────────────────────
        $monthlyRegistrations = cache()->remember('sa_monthly_registrations', 600, function () {
            $labels   = [];
            $students = [];
            $teachers = [];

            for ($i = 11; $i >= 0; $i--) {
                $month     = Carbon::now()->subMonths($i);
                $labels[]  = $month->format('M Y');

                $students[] = User::where('role', 'user')
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->count();

                $teachers[] = User::where('role', 'teacher')
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->count();
            }

            return compact('labels', 'students', 'teachers');
        });

        // ── Rating Stats ─────────────────────────────────────────────────────
        $totalSchoolsCount = $totalSchools;
        $schoolsRated      = RateLms::distinct('organization_id')->count('organization_id');
        $avgRating         = RateLms::avg('rating') ?? 0;

        $ratingStats = [
            'total_schools'  => $totalSchoolsCount,
            'schools_rated'  => $schoolsRated,
            'remaining'      => max(0, $totalSchoolsCount - $schoolsRated),
            'avg_rating'     => round($avgRating, 1),
        ];

        // ── Support Stats ────────────────────────────────────────────────────
        $supportStats = [
            'total'      => ContactSuperAdmin::count(),
            'pending'    => ContactSuperAdmin::where('super_admin_reply', false)->count(),
            'replied'    => ContactSuperAdmin::where('super_admin_reply', true)->count(),
            'this_month' => ContactSuperAdmin::whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->count(),
        ];

        // ── Enquiry Stats ────────────────────────────────────────────────────
        $enquiryStats = [
            'demo_total'      => WebsiteDemo::count(),
            'demo_pending'    => WebsiteDemo::whereNull('remark')->count(),
            'demo_replied'    => WebsiteDemo::whereNotNull('remark')->count(),
            'contact_total'   => WebsiteContact::count(),
            'contact_pending' => WebsiteContact::whereNull('remark')->count(),
            'contact_replied' => WebsiteContact::whereNotNull('remark')->count(),
        ];

        // ── Credit Stats ─────────────────────────────────────────────────────
        $creditStats = [
            'total'               => CreditQuery::count(),
            'pending'             => CreditQuery::where('status', 'pending')->count(),
            'approved'            => CreditQuery::where('status', 'approved')->count(),
            'denied'              => CreditQuery::where('status', 'denied')->count(),
            'total_amount_leased' => (float) CreditQuery::where('status', 'approved')->sum('amount'),
        ];

        // ── Home in the school dashboard's look ──────────────────────────────
        $activeSchools       = Organization::where('status', true)->count();
        $activeStudents      = User::where('role', 'user')->where('is_active', 1)->count();
        $activeTeachers      = User::where('role', 'teacher')->where('is_active', 1)->count();
        $newSchoolsThisMonth = Organization::where('created_at', '>=', now()->startOfMonth())->count();

        $attendance      = $this->attendanceBySchool();
        $attendanceTrend = $this->attendanceTrend($attendance);
        $todayAttendance = end($attendanceTrend) ?: [];
        $schoolFees      = $this->schoolFees();
        $feeSummary      = $this->feeSummary($schoolFees);
        $schoolsToday    = $this->schoolsToday($attendance, $schoolFees);
        $superAdminFee   = $this->superAdminFee();
        $quickTiles      = $this->quickTiles();

        // Active schools that have marked their students' attendance today.
        $schoolsMarkedToday = count(array_filter($schoolsToday, fn ($s) => $s['status'] && $s['student_marked']));

        // The empty public $recentActivities shadows a view variable of that
        // name (the feed always showed "No recent activity"), so the view
        // reads it under another name.
        $activityFeed = $recentActivities;

        return view('livewire.super-admin.dashboard', compact(
            'activeSchools',
            'activeStudents',
            'activeTeachers',
            'newSchoolsThisMonth',
            'attendanceTrend',
            'todayAttendance',
            'feeSummary',
            'schoolsToday',
            'schoolsMarkedToday',
            'superAdminFee',
            'quickTiles',
            'activityFeed',
            'totalSchools',
            'totalStudents',
            'totalTeachers',
            'avgStudentsPerSchool',
            'topSchoolsByStudents',
            'topSchoolsByTeachers',
            'recentSchools',
            'recentActivities',
            'monthlyRegistrations',
            'ratingStats',
            'supportStats',
            'enquiryStats',
            'creditStats',
        ));
    }
}
