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

        return view('livewire.super-admin.dashboard', compact(
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
