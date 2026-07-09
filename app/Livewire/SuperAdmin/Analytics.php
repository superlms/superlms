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

    /** Chart window in months — 6 / 12 / 24, switchable from the header. */
    public int $months = 12;

    public array $overviewStats        = [];
    public array $topByStudents        = [];
    public array $topByTeachers        = [];
    public array $schoolBuckets        = [];
    public array $monthlyRegistrations = [];
    public array $genderSplit          = [];
    public array $ratingStats          = [];

    public array $creditStats       = [];
    public array $creditMonthly     = [];
    public array $topCreditSchools  = [];

    public array $feeStats      = [];
    public array $feeMonthly    = [];
    public array $schoolFeeList = [];
    public array $feeModes      = [];

    public array $payrollStats   = [];
    public array $payrollMonthly = [];
    public array $payrollModes   = [];
    public array $payrollPending = [];
    public array $payrollRecent  = [];

    public array $enquiryStats   = [];
    public array $enquiryMonthly = [];

    public array $supportStats   = [];
    public array $supportMonthly = [];

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        // Charts live inside @if blocks — after the tab's canvases morph in,
        // the frontend re-initialises every visible chart from the payload.
        $this->dispatch('analytics-refresh');
    }

    public function updatedMonths(): void
    {
        $this->months = in_array((int) $this->months, [6, 12, 24], true) ? (int) $this->months : 12;
        $this->loadAll();
        $this->dispatch('analytics-refresh');
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

    // ─── Monthly-series helpers ───────────────────────────────────────────────

    /** Month keys for the selected window, oldest → newest: ['2025-08', …]. */
    private function monthKeys(): array
    {
        $keys = [];
        for ($i = $this->months - 1; $i >= 0; $i--) {
            $keys[] = now()->subMonths($i)->format('Y-m');
        }

        return $keys;
    }

    /** Human labels aligned with monthKeys(): ['Aug 2025', …]. */
    private function monthLabels(): array
    {
        return array_map(
            fn($k) => \Carbon\Carbon::parse($k . '-01')->format('M y'),
            $this->monthKeys()
        );
    }

    /**
     * One aggregate query for a whole monthly series (replaces the old
     * 12-queries-per-line loops). $agg: 'count' or a column to SUM.
     */
    private function monthlySeries($query, string $agg = 'count', string $dateColumn = 'created_at'): array
    {
        $start = now()->subMonths($this->months - 1)->startOfMonth();

        $select = $agg === 'count' ? 'COUNT(*)' : "COALESCE(SUM({$agg}), 0)";

        $rows = $query
            ->where($dateColumn, '>=', $start)
            ->selectRaw("DATE_FORMAT({$dateColumn}, '%Y-%m') as ym, {$select} as v")
            ->groupBy('ym')
            ->pluck('v', 'ym');

        return array_map(fn($k) => (float) ($rows[$k] ?? 0), $this->monthKeys());
    }

    // ─── Overview ─────────────────────────────────────────────────────────────

    private function loadOverview(): void
    {
        $totalSchools    = Organization::count();
        $activeSchools   = Organization::where('status', true)->count();
        $totalStudents   = User::where('role', 'user')->count();
        $activeStudents  = User::where('role', 'user')->where('is_active', 1)->count();
        $totalTeachers   = User::where('role', 'teacher')->count();
        $activeTeachers  = User::where('role', 'teacher')->where('is_active', 1)->count();
        $avgStudents     = $totalSchools > 0 ? round($totalStudents / $totalSchools, 1) : 0;

        $studentsThisMonth = User::where('role', 'user')->where('created_at', '>=', now()->startOfMonth())->count();
        $studentsLastMonth = User::where('role', 'user')
            ->whereBetween('created_at', [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()])
            ->count();
        $growthPct = $studentsLastMonth > 0
            ? round((($studentsThisMonth - $studentsLastMonth) / $studentsLastMonth) * 100, 1)
            : ($studentsThisMonth > 0 ? 100 : 0);

        $this->overviewStats = [
            'totalSchools'         => $totalSchools,
            'activeSchools'        => $activeSchools,
            'inactiveSchools'      => max(0, $totalSchools - $activeSchools),
            'newSchoolsThisMonth'  => Organization::where('created_at', '>=', now()->startOfMonth())->count(),
            'totalStudents'        => $totalStudents,
            'activeStudents'       => $activeStudents,
            'totalTeachers'        => $totalTeachers,
            'activeTeachers'       => $activeTeachers,
            'avgStudentsPerSchool' => $avgStudents,
            'studentsThisMonth'    => $studentsThisMonth,
            'studentsLastMonth'    => $studentsLastMonth,
            'studentGrowthPct'     => $growthPct,
        ];

        // Top 5 by students / teachers — one query, reused for both boards.
        $withCounts = fn() => Organization::select('id', 'name', 'logo')->withCount([
            'users as students_count' => fn($q) => $q->where('role', 'user'),
            'users as teachers_count' => fn($q) => $q->where('role', 'teacher'),
        ]);

        $mapTop = fn($rows) => $rows->values()->map(fn($org, $i) => [
            'rank'     => $i + 1,
            'id'       => $org->id,
            'name'     => $org->name,
            'logo'     => $org->logo,
            'students' => $org->students_count,
            'teachers' => $org->teachers_count,
        ])->toArray();

        $this->topByStudents = $mapTop($withCounts()->orderByDesc('students_count')->limit(5)->get());
        $this->topByTeachers = $mapTop($withCounts()->orderByDesc('teachers_count')->limit(5)->get());

        // School size buckets
        $schoolsWithCounts = Organization::withCount([
            'users as students_count' => fn($q) => $q->where('role', 'user'),
        ])->get();

        $this->schoolBuckets = [
            '<500'  => $schoolsWithCounts->where('students_count', '<', 500)->count(),
            '500+'  => $schoolsWithCounts->where('students_count', '>=', 500)->count(),
            '1000+' => $schoolsWithCounts->where('students_count', '>=', 1000)->count(),
            '1500+' => $schoolsWithCounts->where('students_count', '>=', 1500)->count(),
            '2000+' => $schoolsWithCounts->where('students_count', '>=', 2000)->count(),
        ];

        // Monthly registrations + schools onboarded (aggregate queries)
        $this->monthlyRegistrations = [
            'labels'   => $this->monthLabels(),
            'students' => $this->monthlySeries(User::where('role', 'user')),
            'teachers' => $this->monthlySeries(User::where('role', 'teacher')),
            'schools'  => $this->monthlySeries(Organization::query()),
        ];

        // Student gender split
        $genders = User::where('role', 'user')
            ->selectRaw("COALESCE(NULLIF(gender, ''), 'unknown') as g, COUNT(*) as c")
            ->groupBy('g')
            ->pluck('c', 'g');

        $this->genderSplit = [
            'male'    => (int) ($genders['male'] ?? 0),
            'female'  => (int) ($genders['female'] ?? 0),
            'other'   => (int) ($genders['other'] ?? 0),
            'unknown' => (int) ($genders['unknown'] ?? 0),
        ];

        // Platform ratings (Rate LMS)
        $ratingRows = RateLms::selectRaw('rating, COUNT(*) as c')->groupBy('rating')->pluck('c', 'rating');
        $totalRatings = (int) $ratingRows->sum();
        $dist = [];
        foreach ([5, 4, 3, 2, 1] as $star) {
            $count = (int) ($ratingRows[$star] ?? 0);
            $dist[$star] = [
                'count' => $count,
                'pct'   => $totalRatings > 0 ? round($count / $totalRatings * 100, 1) : 0,
            ];
        }

        $this->ratingStats = [
            'total'        => $totalRatings,
            'avg'          => $totalRatings > 0 ? round((float) RateLms::avg('rating'), 1) : 0,
            'fiveStar'     => (int) ($ratingRows[5] ?? 0),
            'distribution' => $dist,
        ];
    }

    // ─── Credit ───────────────────────────────────────────────────────────────

    private function loadCredit(): void
    {
        $total    = CreditQuery::count();
        $approved = CreditQuery::approved()->count();

        $totalAmountLeased = (float) CreditQuery::approved()->sum('amount');
        $amountCollected   = (float) CreditQuery::approved()->whereNotNull('collected_at')->sum('amount');

        $this->creditStats = [
            'total'             => $total,
            'pending'           => CreditQuery::pending()->count(),
            'processing'        => CreditQuery::processing()->count(),
            'approved'          => $approved,
            'denied'            => CreditQuery::denied()->count(),
            'totalAmountLeased' => $totalAmountLeased,
            'totalPending'      => (float) CreditQuery::pending()->sum('amount'),
            'activeCredits'     => CreditQuery::activeCredit()->count(),
            // Extra detail
            'approvalRate'      => $total > 0 ? round($approved / $total * 100, 1) : 0,
            'avgApproved'       => $approved > 0 ? round($totalAmountLeased / $approved, 0) : 0,
            'collectedCount'    => CreditQuery::approved()->whereNotNull('collected_at')->count(),
            'amountCollected'   => $amountCollected,
            'amountOutstanding' => max(0, $totalAmountLeased - $amountCollected),
        ];

        $this->creditMonthly = [
            'labels'       => $this->monthLabels(),
            'applications' => $this->monthlySeries(CreditQuery::query()),
            'approved'     => $this->monthlySeries(CreditQuery::approved()),
            'amount'       => $this->monthlySeries(CreditQuery::approved(), 'amount'),
        ];

        // Top 5 schools by approved credit amount
        $this->topCreditSchools = CreditQuery::approved()
            ->selectRaw('organization_id, COUNT(*) as queries, SUM(amount) as total_amount')
            ->groupBy('organization_id')
            ->orderByDesc('total_amount')
            ->limit(5)
            ->get()
            ->map(function ($row, $i) {
                return [
                    'rank'    => $i + 1,
                    'name'    => Organization::find($row->organization_id)?->name ?? 'Unknown',
                    'queries' => (int) $row->queries,
                    'amount'  => (float) $row->total_amount,
                ];
            })->values()->toArray();
    }

    // ─── Fee ──────────────────────────────────────────────────────────────────

    private function loadFee(): void
    {
        $totalSchools      = SuperAdminFeeStructure::distinct('organization_id')->count('organization_id');
        $totalRecords      = SuperAdminFeePayment::count();
        $totalFeeToCollect = (float) SuperAdminFeePayment::sum('amount');
        $totalCollected    = (float) SuperAdminFeePayment::paid()->sum('amount');
        $totalRemaining    = $totalFeeToCollect - $totalCollected;

        $this->feeStats = [
            'totalSchools'       => $totalSchools,
            'totalStudents'      => $totalRecords,
            'totalFeeToCollect'  => $totalFeeToCollect,
            'totalCollected'     => $totalCollected,
            'totalRemaining'     => $totalRemaining,
            'avgFeePerStudent'   => $totalRecords > 0 ? round($totalFeeToCollect / $totalRecords, 2) : 0,
            // Extra detail
            'collectionRate'     => $totalFeeToCollect > 0 ? round($totalCollected / $totalFeeToCollect * 100, 1) : 0,
            'collectedThisMonth' => (float) SuperAdminFeePayment::paid()->where('created_at', '>=', now()->startOfMonth())->sum('amount'),
            'collectedLastMonth' => (float) SuperAdminFeePayment::paid()
                ->whereBetween('created_at', [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()])
                ->sum('amount'),
            'paidRecords'        => SuperAdminFeePayment::paid()->count(),
            'unpaidRecords'      => SuperAdminFeePayment::where('is_paid', false)->count(),
        ];

        // School-wise breakdown — one aggregate query, genuinely top 10 by amount
        $this->schoolFeeList = SuperAdminFeePayment::selectRaw('
                organization_id,
                SUM(amount) as to_collect,
                SUM(CASE WHEN is_paid = 1 THEN amount ELSE 0 END) as collected
            ')
            ->groupBy('organization_id')
            ->orderByDesc('to_collect')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $toCollect = (float) $row->to_collect;
                $collected = (float) $row->collected;

                return [
                    'name'      => Organization::find($row->organization_id)?->name ?? 'Unknown',
                    'toCollect' => $toCollect,
                    'collected' => $collected,
                    'remaining' => $toCollect - $collected,
                    'pct'       => $toCollect > 0 ? round($collected / $toCollect * 100, 1) : 0,
                ];
            })->values()->toArray();

        $this->feeMonthly = [
            'labels'    => $this->monthLabels(),
            'collected' => $this->monthlySeries(SuperAdminFeePayment::paid(), 'amount'),
            'payments'  => $this->monthlySeries(SuperAdminFeePayment::paid()),
        ];

        // Collected amount by payment mode
        $modes = SuperAdminFeePayment::paid()
            ->selectRaw("COALESCE(NULLIF(payment_mode, ''), 'other') as mode, SUM(amount) as total")
            ->groupBy('mode')
            ->orderByDesc('total')
            ->pluck('total', 'mode');

        $this->feeModes = [
            'labels' => $modes->keys()->map(fn($m) => ucwords(str_replace('_', ' ', $m)))->toArray(),
            'data'   => $modes->values()->map(fn($v) => (float) $v)->toArray(),
        ];
    }

    // ─── Payroll ──────────────────────────────────────────────────────────────

    private function loadPayroll(): void
    {
        $totalEmployees         = SuperAdminEmployee::count();
        $totalMonthlySalaryBill = (float) SuperAdminEmployee::where('is_active', true)->sum('salary');

        // Salary is payable for the PREVIOUS month (current month is locked in payroll).
        $payableMonth     = now()->subMonthNoOverflow()->format('Y-m');
        $paidThisMonth    = SuperAdminSalaryPayment::forMonth($payableMonth)->paid()->count();
        $totalPaidAmount  = (float) SuperAdminSalaryPayment::forMonth($payableMonth)->paid()->sum('amount');

        // Employee types come from the payroll module so new types show up automatically.
        $typeRows   = SuperAdminEmployee::selectRaw('type, COUNT(*) as c')->groupBy('type')->pluck('c', 'type');
        $salaryRows = SuperAdminEmployee::selectRaw('type, SUM(salary) as s')->groupBy('type')->pluck('s', 'type');
        $byType     = [];
        foreach (Payroll::EMP_TYPES as $type) {
            $byType[$type] = [
                'count'  => (int) ($typeRows[$type] ?? 0),
                'salary' => (float) ($salaryRows[$type] ?? 0),
            ];
        }
        // Legacy/unexpected types still show instead of silently disappearing.
        foreach ($typeRows as $type => $count) {
            if (!isset($byType[$type])) {
                $byType[$type] = ['count' => (int) $count, 'salary' => (float) ($salaryRows[$type] ?? 0)];
            }
        }

        // Payment-mode split (mirrors the Fee tab's mode breakdown)
        $modes = SuperAdminSalaryPayment::paid()
            ->selectRaw("COALESCE(NULLIF(payment_mode, ''), 'other') as mode, SUM(amount) as total")
            ->groupBy('mode')
            ->orderByDesc('total')
            ->pluck('total', 'mode');

        $this->payrollModes = [
            'labels' => $modes->keys()->map(fn($m) => ucwords(str_replace('_', ' ', $m)))->toArray(),
            'data'   => $modes->values()->map(fn($v) => (float) $v)->toArray(),
        ];

        // Employees still unpaid for the payable month — who needs paying, right now.
        $paidEmployeeIds = SuperAdminSalaryPayment::forMonth($payableMonth)->paid()->pluck('super_admin_employee_id');
        $this->payrollPending = SuperAdminEmployee::where('is_active', true)
            ->whereNotIn('id', $paidEmployeeIds)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'designation', 'salary'])
            ->map(fn($e) => [
                'name'        => $e->name,
                'type'        => $e->type,
                'designation' => $e->designation,
                'salary'      => (float) $e->salary,
            ])->toArray();

        // Most recent payouts, whatever month they were for.
        $this->payrollRecent = SuperAdminSalaryPayment::paid()
            ->with('employee:id,name,type')
            ->orderByDesc('payment_date')
            ->limit(8)
            ->get()
            ->map(fn($p) => [
                'name'   => $p->employee?->name ?? 'Unknown',
                'type'   => $p->employee?->type ?? '—',
                'month'  => $p->month ? \Carbon\Carbon::parse($p->month . '-01')->format('M Y') : '—',
                'amount' => (float) $p->amount,
                'mode'   => $p->payment_mode ? ucwords(str_replace('_', ' ', $p->payment_mode)) : '—',
                'date'   => $p->payment_date?->format('d M Y') ?? '—',
            ])->toArray();

        $this->payrollStats = [
            'totalEmployees'         => $totalEmployees,
            'totalMonthlySalaryBill' => $totalMonthlySalaryBill,
            'payableMonth'           => \Carbon\Carbon::parse($payableMonth . '-01')->format('M Y'),
            'paidThisMonth'          => $paidThisMonth,
            'pendingThisMonth'       => max(0, $totalEmployees - $paidThisMonth),
            'totalPaidAmount'        => $totalPaidAmount,
            'byType'                 => $byType,
            // Extra detail
            'avgSalary'              => $totalEmployees > 0 ? round((float) SuperAdminEmployee::avg('salary'), 0) : 0,
            'highestSalary'          => (float) (SuperAdminEmployee::max('salary') ?? 0),
            'lowestSalary'           => (float) (SuperAdminEmployee::where('is_active', true)->min('salary') ?? 0),
            'paidThisYear'           => (float) SuperAdminSalaryPayment::paid()
                ->where('month', 'like', now()->format('Y') . '-%')
                ->sum('amount'),
        ];

        // Salary paid per payment_date month (payment_date reflects when money moved)
        $this->payrollMonthly = [
            'labels' => $this->monthLabels(),
            'paid'   => $this->monthlySeries(
                SuperAdminSalaryPayment::paid()->whereNotNull('payment_date'),
                'amount',
                'payment_date'
            ),
        ];
    }

    // ─── Enquiries ────────────────────────────────────────────────────────────

    private function loadEnquiries(): void
    {
        // NB: the pending check groups the OR properly — a bare orWhere here used
        // to leak every row with remark = '' into the count regardless of role.
        $pendingScope = fn($q) => $q->where(fn($q) => $q->whereNull('remark')->orWhere('remark', ''));
        $repliedScope = fn($q) => $q->whereNotNull('remark')->where('remark', '!=', '');

        $demoTotal    = WebsiteDemo::count();
        $demoReplied  = $repliedScope(WebsiteDemo::query())->count();
        $demoPending  = $pendingScope(WebsiteDemo::query())->count();

        $contactTotal   = WebsiteContact::count();
        $contactReplied = $repliedScope(WebsiteContact::query())->count();
        $contactPending = $pendingScope(WebsiteContact::query())->count();

        $this->enquiryStats = [
            'demo' => [
                'total'     => $demoTotal,
                'pending'   => $demoPending,
                'replied'   => $demoReplied,
                'thisMonth' => WebsiteDemo::where('created_at', '>=', now()->startOfMonth())->count(),
                'thisWeek'  => WebsiteDemo::where('created_at', '>=', now()->startOfWeek())->count(),
                'replyRate' => $demoTotal > 0 ? round($demoReplied / $demoTotal * 100, 1) : 0,
            ],
            'contact' => [
                'total'     => $contactTotal,
                'pending'   => $contactPending,
                'replied'   => $contactReplied,
                'thisMonth' => WebsiteContact::where('created_at', '>=', now()->startOfMonth())->count(),
                'thisWeek'  => WebsiteContact::where('created_at', '>=', now()->startOfWeek())->count(),
                'replyRate' => $contactTotal > 0 ? round($contactReplied / $contactTotal * 100, 1) : 0,
            ],
            'combined' => [
                'total'   => $demoTotal + $contactTotal,
                'pending' => $demoPending + $contactPending,
                'replied' => $demoReplied + $contactReplied,
            ],
        ];

        $this->enquiryMonthly = [
            'labels'  => $this->monthLabels(),
            'demo'    => $this->monthlySeries(WebsiteDemo::query()),
            'contact' => $this->monthlySeries(WebsiteContact::query()),
        ];
    }

    // ─── Support ──────────────────────────────────────────────────────────────

    private function loadSupport(): void
    {
        $total   = ContactSuperAdmin::count();
        $replied = ContactSuperAdmin::whereNotNull('super_admin_reply')
            ->where('super_admin_reply', '!=', '')
            ->count();

        $monthsActive = max(1, min(
            $this->months,
            (int) ceil(now()->diffInMonths(ContactSuperAdmin::min('created_at') ?? now()) + 1)
        ));

        $this->supportStats = [
            'total'       => $total,
            'pending'     => $total - $replied,
            'replied'     => $replied,
            'thisMonth'   => ContactSuperAdmin::where('created_at', '>=', now()->startOfMonth())->count(),
            'thisWeek'    => ContactSuperAdmin::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'avgPerMonth' => round($total / $monthsActive, 1),
        ];

        $this->supportMonthly = [
            'labels'  => $this->monthLabels(),
            'total'   => $this->monthlySeries(ContactSuperAdmin::query()),
            'replied' => $this->monthlySeries(
                ContactSuperAdmin::whereNotNull('super_admin_reply')->where('super_admin_reply', '!=', '')
            ),
        ];
    }

    /**
     * Everything the charts need, serialised into one payload the blade drops
     * into a data attribute. The frontend re-reads it after every Livewire
     * morph, so charts stay in sync when the tab or month window changes.
     */
    public function chartPayload(): array
    {
        return [
            'labels'   => $this->monthLabels(),
            'overview' => [
                'students' => $this->monthlyRegistrations['students'] ?? [],
                'teachers' => $this->monthlyRegistrations['teachers'] ?? [],
                'schools'  => $this->monthlyRegistrations['schools'] ?? [],
            ],
            'gender'   => $this->genderSplit,
            'credit'   => [
                'applications' => $this->creditMonthly['applications'] ?? [],
                'approved'     => $this->creditMonthly['approved'] ?? [],
                'amount'       => $this->creditMonthly['amount'] ?? [],
            ],
            'fee'      => [
                'collected' => $this->feeMonthly['collected'] ?? [],
                'payments'  => $this->feeMonthly['payments'] ?? [],
                'modes'     => $this->feeModes,
            ],
            'payroll'  => [
                'paid'  => $this->payrollMonthly['paid'] ?? [],
                'modes' => $this->payrollModes,
            ],
            'enquiry'  => [
                'demo'    => $this->enquiryMonthly['demo'] ?? [],
                'contact' => $this->enquiryMonthly['contact'] ?? [],
            ],
            'support'  => [
                'total'   => $this->supportMonthly['total'] ?? [],
                'replied' => $this->supportMonthly['replied'] ?? [],
            ],
        ];
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.super-admin.analytics', [
            'chartPayload' => $this->chartPayload(),
        ]);
    }
}
