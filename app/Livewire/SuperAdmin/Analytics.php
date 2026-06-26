<?php

namespace App\Livewire\SuperAdmin;

use Livewire\Component;
use App\Models\Organization;
use App\Models\User;
use App\Models\SuperAdmin\CreditQuery;
use App\Models\SuperAdmin\SuperAdminFeeStructure;
use App\Models\SuperAdmin\SuperAdminFeePayment;
use App\Models\SuperAdmin\SuperAdminEmployee;
use App\Models\SuperAdmin\SuperAdminSalaryPayment;
use App\Models\Admin\RateLms;
use App\Models\Admin\ContactSuperAdmin;
use App\Models\WebsiteContact;
use App\Models\WebsiteDemo;

class Analytics extends Component
{
    public string $activeTab = 'overview';

    public array $overviewStats      = [];
    public array $topByStudents      = [];
    public array $topByTeachers      = [];
    public array $schoolBuckets      = [];
    public array $monthlyRegistrations = [];

    public array $creditStats        = [];
    public array $creditMonthly      = [];

    public array $feeStats           = [];
    public array $feeMonthly         = [];
    public array $schoolFeeList      = [];

    public array $payrollStats       = [];

    public array $enquiryStats       = [];
    public array $enquiryMonthly     = [];

    public array $supportStats       = [];
    public array $supportMonthly     = [];

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function mount(): void
    {
        $this->loadAll();
    }

    private function loadAll(): void
    {
        $this->loadOverview();
        $this->loadCredit();
        $this->loadFee();
        $this->loadPayroll();
        $this->loadEnquiries();
        $this->loadSupport();
    }

    private function loadOverview(): void
    {
        $totalSchools  = Organization::count();
        $totalStudents = User::where('role', 'user')->count();
        $totalTeachers = User::where('role', 'teacher')->count();
        $avgStudents   = $totalSchools > 0 ? round($totalStudents / $totalSchools, 1) : 0;

        $this->overviewStats = [
            'totalSchools'         => $totalSchools,
            'totalStudents'        => $totalStudents,
            'totalTeachers'        => $totalTeachers,
            'avgStudentsPerSchool' => $avgStudents,
        ];

        // Top 5 by students
        $topStudents = Organization::select('id', 'name', 'logo')
            ->withCount([
                'users as students_count' => fn($q) => $q->where('role', 'user'),
                'users as teachers_count' => fn($q) => $q->where('role', 'teacher'),
            ])
            ->orderByDesc('students_count')
            ->limit(5)
            ->get();

        $this->topByStudents = $topStudents->map(fn($org, $i) => [
            'rank'     => $i + 1,
            'id'       => $org->id,
            'name'     => $org->name,
            'logo'     => $org->logo,
            'students' => $org->students_count,
            'teachers' => $org->teachers_count,
        ])->values()->toArray();

        // Top 5 by teachers
        $topTeachers = Organization::select('id', 'name', 'logo')
            ->withCount([
                'users as students_count' => fn($q) => $q->where('role', 'user'),
                'users as teachers_count' => fn($q) => $q->where('role', 'teacher'),
            ])
            ->orderByDesc('teachers_count')
            ->limit(5)
            ->get();

        $this->topByTeachers = $topTeachers->map(fn($org, $i) => [
            'rank'     => $i + 1,
            'id'       => $org->id,
            'name'     => $org->name,
            'logo'     => $org->logo,
            'students' => $org->students_count,
            'teachers' => $org->teachers_count,
        ])->values()->toArray();

        // School size buckets
        $schoolsWithCounts = Organization::withCount([
            'users as students_count' => fn($q) => $q->where('role', 'user'),
        ])->get();

        $this->schoolBuckets = [
            '500+'  => $schoolsWithCounts->where('students_count', '>=', 500)->count(),
            '1000+' => $schoolsWithCounts->where('students_count', '>=', 1000)->count(),
            '1500+' => $schoolsWithCounts->where('students_count', '>=', 1500)->count(),
            '2000+' => $schoolsWithCounts->where('students_count', '>=', 2000)->count(),
        ];

        // Monthly registrations (last 12 months)
        $labels   = [];
        $students = [];
        $teachers = [];
        for ($i = 11; $i >= 0; $i--) {
            $month    = now()->subMonths($i);
            $labels[] = $month->format('M Y');
            $students[] = User::where('role', 'user')
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
            $teachers[] = User::where('role', 'teacher')
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
        }

        $this->monthlyRegistrations = [
            'labels'   => $labels,
            'students' => $students,
            'teachers' => $teachers,
        ];
    }

    private function loadCredit(): void
    {
        $total      = CreditQuery::count();
        $pending    = CreditQuery::pending()->count();
        $processing = CreditQuery::processing()->count();
        $approved   = CreditQuery::approved()->count();
        $denied     = CreditQuery::denied()->count();

        $totalAmountLeased = CreditQuery::approved()->sum('amount');
        $totalPending      = CreditQuery::pending()->sum('amount');
        $activeCredits     = CreditQuery::activeCredit()->count();

        $this->creditStats = [
            'total'             => $total,
            'pending'           => $pending,
            'processing'        => $processing,
            'approved'          => $approved,
            'denied'            => $denied,
            'totalAmountLeased' => $totalAmountLeased,
            'totalPending'      => $totalPending,
            'activeCredits'     => $activeCredits,
        ];

        // Monthly credit applications (last 12 months)
        $labels       = [];
        $applications = [];
        $approvedData = [];
        for ($i = 11; $i >= 0; $i--) {
            $month          = now()->subMonths($i);
            $labels[]       = $month->format('M Y');
            $applications[] = CreditQuery::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
            $approvedData[] = CreditQuery::approved()
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
        }

        $this->creditMonthly = [
            'labels'       => $labels,
            'applications' => $applications,
            'approved'     => $approvedData,
        ];
    }

    private function loadFee(): void
    {
        $totalSchools    = SuperAdminFeeStructure::distinct('organization_id')->count('organization_id');
        $totalStudents   = SuperAdminFeePayment::count();
        $totalFeeToCollect = SuperAdminFeePayment::sum('amount');
        $totalCollected  = SuperAdminFeePayment::paid()->sum('amount');
        $totalRemaining  = $totalFeeToCollect - $totalCollected;
        $avgFeePerStudent = $totalStudents > 0 ? round($totalFeeToCollect / $totalStudents, 2) : 0;

        $this->feeStats = [
            'totalSchools'      => $totalSchools,
            'totalStudents'     => $totalStudents,
            'totalFeeToCollect' => $totalFeeToCollect,
            'totalCollected'    => $totalCollected,
            'totalRemaining'    => $totalRemaining,
            'avgFeePerStudent'  => $avgFeePerStudent,
        ];

        // Top 10 schools by fee
        $orgIds = SuperAdminFeePayment::distinct('organization_id')
            ->pluck('organization_id');

        $schoolFeeList = [];
        foreach ($orgIds->take(10) as $orgId) {
            $org       = Organization::select('id', 'name')->find($orgId);
            $toCollect = SuperAdminFeePayment::where('organization_id', $orgId)->sum('amount');
            $collected = SuperAdminFeePayment::where('organization_id', $orgId)->paid()->sum('amount');
            $remaining = $toCollect - $collected;
            $pct       = $toCollect > 0 ? round(($collected / $toCollect) * 100, 1) : 0;

            $schoolFeeList[] = [
                'name'      => $org?->name ?? 'Unknown',
                'toCollect' => $toCollect,
                'collected' => $collected,
                'remaining' => $remaining,
                'pct'       => $pct,
            ];
        }

        usort($schoolFeeList, fn($a, $b) => $b['toCollect'] <=> $a['toCollect']);
        $this->schoolFeeList = array_slice($schoolFeeList, 0, 10);

        // Monthly fee collection (last 12 months)
        $labels    = [];
        $collected = [];
        for ($i = 11; $i >= 0; $i--) {
            $month      = now()->subMonths($i);
            $labels[]   = $month->format('M Y');
            $collected[] = SuperAdminFeePayment::paid()
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('amount');
        }

        $this->feeMonthly = [
            'labels'    => $labels,
            'collected' => $collected,
        ];
    }

    private function loadPayroll(): void
    {
        $totalEmployees = SuperAdminEmployee::count();
        $totalMonthlySalaryBill = SuperAdminEmployee::where('is_active', true)->sum('salary');

        $currentMonth = now()->format('Y-m');
        $paidThisMonth    = SuperAdminSalaryPayment::forMonth($currentMonth)->paid()->count();
        $pendingThisMonth = SuperAdminSalaryPayment::forMonth($currentMonth)->pending()->count();
        $totalPaidAmount  = SuperAdminSalaryPayment::forMonth($currentMonth)->paid()->sum('amount');

        $byType = [];
        foreach (['user', 'teacher', 'management', 'driver'] as $type) {
            $byType[$type] = SuperAdminEmployee::where('type', $type)->count();
        }

        $this->payrollStats = [
            'totalEmployees'         => $totalEmployees,
            'totalMonthlySalaryBill' => $totalMonthlySalaryBill,
            'paidThisMonth'          => $paidThisMonth,
            'pendingThisMonth'       => $pendingThisMonth,
            'totalPaidAmount'        => $totalPaidAmount,
            'byType'                 => $byType,
        ];
    }

    private function loadEnquiries(): void
    {
        // Demo enquiries
        $demoTotal     = WebsiteDemo::count();
        $demoPending   = WebsiteDemo::whereNull('remark')->orWhere('remark', '')->count();
        $demoReplied   = WebsiteDemo::whereNotNull('remark')->where('remark', '!=', '')->count();
        $demoThisMonth = WebsiteDemo::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        // Contact enquiries (WebsiteContact)
        $contactTotal     = WebsiteContact::count();
        $contactPending   = WebsiteContact::whereNull('remark')->orWhere('remark', '')->count();
        $contactReplied   = WebsiteContact::whereNotNull('remark')->where('remark', '!=', '')->count();
        $contactThisMonth = WebsiteContact::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        $this->enquiryStats = [
            'demo' => [
                'total'     => $demoTotal,
                'pending'   => $demoPending,
                'replied'   => $demoReplied,
                'thisMonth' => $demoThisMonth,
            ],
            'contact' => [
                'total'     => $contactTotal,
                'pending'   => $contactPending,
                'replied'   => $contactReplied,
                'thisMonth' => $contactThisMonth,
            ],
            'combined' => [
                'total'   => $demoTotal + $contactTotal,
                'pending' => $demoPending + $contactPending,
                'replied' => $demoReplied + $contactReplied,
            ],
        ];

        // Monthly enquiry chart (last 12 months)
        $labels  = [];
        $demo    = [];
        $contact = [];
        for ($i = 11; $i >= 0; $i--) {
            $month     = now()->subMonths($i);
            $labels[]  = $month->format('M Y');
            $demo[]    = WebsiteDemo::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
            $contact[] = WebsiteContact::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
        }

        $this->enquiryMonthly = [
            'labels'  => $labels,
            'demo'    => $demo,
            'contact' => $contact,
        ];
    }

    private function loadSupport(): void
    {
        $total     = ContactSuperAdmin::count();
        $replied   = ContactSuperAdmin::whereNotNull('super_admin_reply')
            ->where('super_admin_reply', '!=', '')
            ->count();
        $pending   = $total - $replied;
        $thisMonth = ContactSuperAdmin::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();
        $thisWeek  = ContactSuperAdmin::whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ])->count();

        $this->supportStats = [
            'total'     => $total,
            'pending'   => $pending,
            'replied'   => $replied,
            'thisMonth' => $thisMonth,
            'thisWeek'  => $thisWeek,
        ];

        // Monthly support chart (last 12 months)
        $labels  = [];
        $totals  = [];
        $replies = [];
        for ($i = 11; $i >= 0; $i--) {
            $month     = now()->subMonths($i);
            $labels[]  = $month->format('M Y');
            $totals[]  = ContactSuperAdmin::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
            $replies[] = ContactSuperAdmin::whereNotNull('super_admin_reply')
                ->where('super_admin_reply', '!=', '')
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
        }

        $this->supportMonthly = [
            'labels'  => $labels,
            'total'   => $totals,
            'replied' => $replies,
        ];
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.super-admin.analytics');
    }
}
