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
use App\Models\SuperAdmin\SuperAdminFeeReceipt;
use App\Models\SuperAdmin\SuperAdminEmployee;
use App\Models\SuperAdmin\SuperAdminSalaryPayment;
use App\Models\SuperAdmin\SuperAdminAttendance;
use App\Models\SchoolListing;
use App\Models\CareerApplication;
use App\Models\ExecutiveApplication;
use App\Models\PushNotificationCampaign;
use App\Models\WebsiteVisit;
use App\Support\SuperAdminFeeTotals;
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

    // ── Home: the Super Admin's own figures, in the school panel's look ───────

    /** Months on the fee chart, this month included. */
    private const FEE_MONTHS = 12;

    /**
     * Whether this user may open a screen: a sub-super-admin only the screens
     * granted to them and never Users, as on Quick Links and the sidebar.
     */
    protected function canOpen(string $route): bool
    {
        if (! Route::has($route)) {
            return false;
        }

        $user = auth()->user();
        if ($user && $user->isSubSuperAdmin()) {
            return $route !== 'super-admin.users'
                && in_array($route, (array) $user->permissions, true);
        }

        return true;
    }

    /**
     * The Super Admin's fee from the schools: the year's total from the fee
     * structure set for each school on the Fees screen against the money
     * recorded (`SuperAdminFeeTotals`, as Fees and Analytics count it), and
     * what was collected month by month (by payment date, as the Fees
     * screen's own chart reads it) over the last 12 months.
     */
    protected function superAdminFee(): array
    {
        $year      = SuperAdminFeeTotals::summary();
        $total     = $year['expected'];
        $collected = $year['collected'];

        $from  = now()->subMonthsNoOverflow(self::FEE_MONTHS - 1)->startOfMonth();
        // Money received on each day (SuperAdminFeeReceipt, as Reports reads it).
        $byDay = SuperAdminFeeReceipt::query()
            ->whereNotNull('payment_date')
            ->whereDate('payment_date', '>=', $from->toDateString())
            ->whereDate('payment_date', '<=', now()->toDateString())
            ->selectRaw('DATE(payment_date) as d, SUM(amount) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        $byMonth = [];
        foreach ($byDay as $date => $amount) {
            $key = substr((string) $date, 0, 7);
            $byMonth[$key] = ($byMonth[$key] ?? 0) + (float) $amount;
        }

        $labels = $data = [];
        for ($i = self::FEE_MONTHS - 1; $i >= 0; $i--) {
            $month    = now()->subMonthsNoOverflow($i);
            $labels[] = $month->format('M y');
            $data[]   = round($byMonth[$month->format('Y-m')] ?? 0, 2);
        }

        return [
            'total'          => $total,
            'collected'      => $collected,
            'pending'        => max(0, $total - $collected),
            'pct'            => $total > 0 ? round($collected / $total * 100, 1) : 0,
            'this_month'     => $byMonth[now()->format('Y-m')] ?? 0,
            'last_month'     => $byMonth[now()->subMonthNoOverflow()->format('Y-m')] ?? 0,
            'schools'        => $year['schools'],
            'schools_due'    => $year['schools_due'],
            'year'           => $year['year'],
            'unpaid_records' => SuperAdminFeePayment::where('is_paid', false)->count(),
            'labels'         => $labels,
            'data'           => $data,
            'year_total'     => array_sum($data),
        ];
    }

    /**
     * The Super Admin's staff: salary for the month now payable (the one before
     * this, as Payroll and Analytics take it) and today's attendance.
     */
    protected function staffSummary(): array
    {
        $payable = now()->subMonthNoOverflow()->format('Y-m');
        $active  = SuperAdminEmployee::where('is_active', true);

        $paidIds = SuperAdminSalaryPayment::forMonth($payable)->paid()
            ->pluck('super_admin_employee_id')->unique()->values();

        $unpaid = (clone $active)->whereNotIn('id', $paidIds)
            ->orderBy('name')
            ->get(['id', 'name', 'designation', 'type', 'salary']);

        $today = SuperAdminAttendance::whereDate('date', now()->toDateString())
            ->whereIn('super_admin_employee_id', (clone $active)->select('id'))
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $activeCount = (clone $active)->count();
        $present     = (int) ($today['present'] ?? 0);
        $half        = (int) ($today['half_day'] ?? 0);
        $marked      = (int) $today->sum();

        return [
            'employees'    => SuperAdminEmployee::count(),
            'active'       => $activeCount,
            'bill'         => (float) (clone $active)->sum('salary'),
            'month'        => Carbon::parse($payable . '-01')->format('M Y'),
            'paid_count'   => $activeCount - $unpaid->count(),
            'paid_amount'  => (float) SuperAdminSalaryPayment::forMonth($payable)->paid()->sum('amount'),
            'paid_pct'     => $activeCount > 0 ? round(($activeCount - $unpaid->count()) / $activeCount * 100, 1) : 0,
            'unpaid_count' => $unpaid->count(),
            'unpaid'       => $unpaid->take(6)->map(fn ($e) => [
                'name'   => $e->name,
                'role'   => $e->designation ?: ucfirst((string) $e->type),
                'salary' => (float) $e->salary,
            ])->values()->toArray(),
            'today' => [
                'present'  => $present,
                'absent'   => (int) ($today['absent'] ?? 0),
                'half_day' => $half,
                'leave'    => (int) ($today['leave'] ?? 0),
                'holiday'  => (int) ($today['holiday'] ?? 0),
                'marked'   => $marked,
                'pct'      => $activeCount > 0 ? round(($present + 0.5 * $half) / $activeCount * 100, 1) : 0,
            ],
        ];
    }

    /**
     * What is waiting on the Super Admin: unanswered support tickets and
     * enquiries, school listings to approve and new applications. A screen
     * this user cannot open is left out.
     */
    protected function waitingOnYou(): array
    {
        $unanswered = fn ($query) => $query->where(fn ($q) => $q->whereNull('remark')->orWhere('remark', ''));
        $isNew      = fn ($query, string $status) => $query->where(fn ($q) => $q->whereNull('status')->orWhere('status', $status));

        $rows = [
            ['Support Tickets',   'not replied',  'super-admin.support',                 fn () => ContactSuperAdmin::where('super_admin_reply', false)->count()],
            ['Demo Requests',     'not answered', 'super-admin.enquiries',               fn () => $unanswered(WebsiteDemo::query())->count()],
            ['Contact Messages',  'not answered', 'super-admin.enquiries',               fn () => $unanswered(WebsiteContact::query())->count()],
            ['School Listings',   'to approve',   'super-admin.listing',                 fn () => $isNew(SchoolListing::query(), 'pending')->count()],
            ['Career Applications', 'new',          'super-admin.website.careers',         fn () => $isNew(CareerApplication::query(), 'new')->count()],
            ['Become Executive',  'new applications', 'super-admin.website.become-executive', fn () => $isNew(ExecutiveApplication::query(), 'new')->count()],
        ];

        $out = [];
        foreach ($rows as [$label, $sub, $route, $count]) {
            if ($this->canOpen($route)) {
                $out[] = ['label' => $label, 'sub' => $sub, 'route' => $route, 'count' => (int) $count()];
            }
        }

        return $out;
    }

    /** The latest support tickets from schools, newest first. */
    protected function latestTickets(): array
    {
        return ContactSuperAdmin::with('organization:id,name')
            ->latest()
            ->take(5)
            ->get(['id', 'organization_id', 'topic', 'admin_query', 'super_admin_reply', 'created_at'])
            ->map(fn ($t) => [
                'school'  => $t->organization?->name ?? '—',
                'topic'   => $t->topic ?: \Illuminate\Support\Str::limit((string) $t->admin_query, 60),
                'replied' => (bool) $t->super_admin_reply,
                'time'    => $t->created_at?->diffForHumans() ?? '—',
            ])
            ->toArray();
    }

    /** The latest demo requests and contact messages together, newest first. */
    protected function latestEnquiries(): array
    {
        $demo = WebsiteDemo::latest()->take(6)->get(['full_name', 'school_name', 'remark', 'created_at'])
            ->map(fn ($e) => ['kind' => 'Demo', 'name' => $e->full_name, 'school' => $e->school_name, 'answered' => filled($e->remark), 'at' => $e->created_at]);
        $contact = WebsiteContact::latest()->take(6)->get(['full_name', 'school_name', 'subject', 'remark', 'created_at'])
            ->map(fn ($e) => ['kind' => 'Contact', 'name' => $e->full_name, 'school' => $e->school_name ?: $e->subject, 'answered' => filled($e->remark), 'at' => $e->created_at]);

        return $demo->concat($contact)
            ->sortByDesc(fn ($e) => $e['at']?->timestamp ?? 0)
            ->take(6)
            ->map(fn ($e) => array_merge($e, ['time' => $e['at']?->diffForHumans() ?? '—', 'at' => null]))
            ->values()
            ->toArray();
    }

    /** Visits to the SuperLMS website over the last 7 days. */
    protected function websiteWeek(): array
    {
        $from = now()->subDays(6)->startOfDay();
        $base = fn () => WebsiteVisit::where('created_at', '>=', $from);

        return [
            'visits'  => $base()->count(),
            'unique'  => $base()->distinct('visitor_id')->count('visitor_id'),
            'today'   => WebsiteVisit::where('created_at', '>=', now()->startOfDay())->count(),
            'top'     => $base()->selectRaw("COALESCE(NULLIF(page, ''), path) as pg, COUNT(*) as c")
                ->groupBy('pg')->orderByDesc('c')->value('pg') ?? '—',
        ];
    }

    /** Push notifications sent from the panel. */
    protected function pushSummary(): array
    {
        $latest = PushNotificationCampaign::latest()->first(['title', 'recipient_count', 'created_at']);

        return [
            'this_month' => PushNotificationCampaign::where('created_at', '>=', now()->startOfMonth())->count(),
            'total'      => PushNotificationCampaign::count(),
            'latest'     => $latest ? [
                'title'      => $latest->title,
                'recipients' => (int) $latest->recipient_count,
                'time'       => $latest->created_at?->diffForHumans() ?? '—',
            ] : null,
        ];
    }

    /** The latest Rate LMS review, with its school. */
    protected function latestReview(): ?array
    {
        $r = RateLms::with('organization:id,name')->latest()->first();

        return $r ? [
            'school'   => $r->organization?->name ?? '—',
            'rating'   => (int) $r->rating,
            'feedback' => $r->feedback,
            'time'     => $r->created_at?->diffForHumans() ?? '—',
        ] : null;
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
                'created_at'     => $s->created_at->format('d M Y'),
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

        // ── Home: the Super Admin's own figures ──────────────────────────────
        $activeSchools       = Organization::where('status', true)->count();
        $activeStudents      = User::where('role', 'user')->where('is_active', 1)->count();
        $activeTeachers      = User::where('role', 'teacher')->where('is_active', 1)->count();
        $newSchoolsThisMonth = Organization::where('created_at', '>=', now()->startOfMonth())->count();

        $superAdminFee   = $this->superAdminFee();
        $staff           = $this->staffSummary();
        $waiting         = $this->waitingOnYou();
        $latestTickets   = $this->latestTickets();
        $latestEnquiries = $this->latestEnquiries();
        $websiteWeek     = $this->websiteWeek();
        $pushSummary     = $this->pushSummary();
        $latestReview    = $this->latestReview();

        // The empty public $recentActivities shadows a view variable of that
        // name (the feed always showed "No recent activity"), so the view
        // reads it under another name.
        $activityFeed = $recentActivities;

        return view('livewire.super-admin.dashboard', compact(
            'activeSchools',
            'activeStudents',
            'activeTeachers',
            'newSchoolsThisMonth',
            'superAdminFee',
            'staff',
            'waiting',
            'latestTickets',
            'latestEnquiries',
            'websiteWeek',
            'pushSummary',
            'latestReview',
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
