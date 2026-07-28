<?php

namespace App\Livewire\Accounts;

use App\Models\Admin\AdmissionEnquiry;
use App\Models\Admin\AdminEmployee;
use App\Models\Admin\AdminSalaryPayment;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Admin\TransportFeePayment;
use App\Models\Student\StudentDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class Dashboard extends Component
{
    public array $stats = [];
    public $recentPayments = [];

    // Chart datasets (built once on load)
    public array $chartDaily   = ['labels' => [], 'amounts' => []]; // last 14 days
    public array $chartMonthly = ['labels' => [], 'amounts' => []]; // last 6 months
    public array $chartModes   = ['labels' => [], 'amounts' => []]; // payment-mode split

    public function mount(): void
    {
        // Defaults so the view always renders even if a query fails
        $this->stats = [
            'collected' => 0, 'pending' => 0, 'today' => 0, 'month' => 0,
            'transport_collected' => 0, 'students' => 0, 'employees' => 0,
            'salary_month' => 0, 'admissions' => 0, 'admissions_pending' => 0,
            'collection_rate' => 0, 'txn_count' => 0, 'today_count' => 0,
            'avg_txn' => 0,
        ];
        $this->recentPayments = [];

        try {
            $this->loadDashboard();
        } catch (\Throwable $e) {
            logger()->error('Accounts dashboard load failed: ' . $e->getMessage());
        }
    }

    private function loadDashboard(): void
    {
        $orgId = $this->orgId();

        // ── Fees ──────────────────────────────────────────────────────────────
        $totalCollected = (float) FeePayment::where('organization_id', $orgId)->sum('amount');
        $structureTotal = (float) FeeStructure::where('organization_id', $orgId)->where('is_active', true)->sum('amount');
        $todayCollection = (float) FeePayment::where('organization_id', $orgId)->whereDate('payment_date', today())->sum('amount');
        $todayCount      = (int) FeePayment::where('organization_id', $orgId)->whereDate('payment_date', today())->count();
        $monthCollection = (float) FeePayment::where('organization_id', $orgId)
            ->whereMonth('payment_date', now()->month)->whereYear('payment_date', now()->year)->sum('amount');
        $txnCount = (int) FeePayment::where('organization_id', $orgId)->count();

        // ── Transport fees ────────────────────────────────────────────────────
        $transportCollected = 0;
        if (Schema::hasTable('transport_fee_payments')) {
            $transportCollected = (float) TransportFeePayment::where('organization_id', $orgId)->sum('amount');
        }

        // ── Payroll ───────────────────────────────────────────────────────────
        $employees = AdminEmployee::where('organization_id', $orgId)->count();
        $salaryThisMonth = (float) AdminSalaryPayment::where('organization_id', $orgId)
            ->where('month', now()->format('Y-m'))->where('status', 'paid')->sum('amount');

        // ── Admissions ────────────────────────────────────────────────────────
        $admissionsTotal   = AdmissionEnquiry::where('organization_id', $orgId)->count();
        $admissionsPending = AdmissionEnquiry::where('organization_id', $orgId)->where('status', '!=', 'updated')->count();

        // ── Students ──────────────────────────────────────────────────────────
        $totalStudents = StudentDetail::where('organization_id', $orgId)->count();

        $collectionRate = $structureTotal > 0
            ? min(100, round(($totalCollected / $structureTotal) * 100, 1))
            : 0;

        $this->stats = [
            'collected'           => $totalCollected,
            'pending'             => max(0, $structureTotal - $totalCollected),
            'today'               => $todayCollection,
            'today_count'         => $todayCount,
            'month'               => $monthCollection,
            'transport_collected' => $transportCollected,
            'students'            => $totalStudents,
            'employees'           => $employees,
            'salary_month'        => $salaryThisMonth,
            'admissions'          => $admissionsTotal,
            'admissions_pending'  => $admissionsPending,
            'collection_rate'     => $collectionRate,
            'txn_count'           => $txnCount,
            'avg_txn'             => $txnCount > 0 ? round($totalCollected / $txnCount) : 0,
        ];

        $this->buildCharts($orgId);

        $this->recentPayments = FeePayment::with(['studentDetail:id,full_name,admission_no'])
            ->where('organization_id', $orgId)
            ->latest('payment_date')->latest('id')
            ->limit(8)
            ->get()
            ->map(fn($p) => [
                'student'  => $p->studentDetail?->full_name ?? '—',
                'admno'    => $p->studentDetail?->admission_no ?? '',
                'amount'   => $p->amount,
                'mode'     => $p->payment_mode,
                'date'     => $p->payment_date ? \Carbon\Carbon::parse($p->payment_date)->format('d M Y') : '—',
                'receipt'  => $p->receipt_number,
            ])->toArray();
    }

    /**
     * Build the three chart datasets with as few queries as possible:
     * daily (14d) + monthly (6m) collection series, and a payment-mode split.
     */
    private function buildCharts(int $orgId): void
    {
        // ── Daily collections — last 14 days ──
        $dailyRows = FeePayment::where('organization_id', $orgId)
            ->whereDate('payment_date', '>=', today()->subDays(13))
            ->selectRaw('DATE(payment_date) as d, SUM(amount) as total')
            ->groupBy('d')->pluck('total', 'd');

        $dLabels = $dAmounts = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = today()->subDays($i);
            $dLabels[]  = $day->format('d M');
            $dAmounts[] = (float) ($dailyRows[$day->toDateString()] ?? 0);
        }
        $this->chartDaily = ['labels' => $dLabels, 'amounts' => $dAmounts];

        // ── Monthly collections — last 6 months ──
        $monthlyRows = FeePayment::where('organization_id', $orgId)
            ->where('payment_date', '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') as m, SUM(amount) as total")
            ->groupBy('m')->pluck('total', 'm');

        $mLabels = $mAmounts = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $mLabels[]  = $month->format('M');
            $mAmounts[] = (float) ($monthlyRows[$month->format('Y-m')] ?? 0);
        }
        $this->chartMonthly = ['labels' => $mLabels, 'amounts' => $mAmounts];

        // ── Payment-mode split ──
        $modeRows = FeePayment::where('organization_id', $orgId)
            ->selectRaw('payment_mode, SUM(amount) as total')
            ->groupBy('payment_mode')->pluck('total', 'payment_mode');

        $modeMeta = [
            'cash'          => 'Cash',
            'online'        => 'Online',
            'cheque'        => 'Cheque',
            'bank_transfer' => 'Bank Transfer',
        ];
        $modeLabels = $modeAmounts = [];
        foreach ($modeMeta as $key => $label) {
            $val = (float) ($modeRows[$key] ?? 0);
            if ($val > 0) {
                $modeLabels[]  = $label;
                $modeAmounts[] = $val;
            }
        }
        $this->chartModes = ['labels' => $modeLabels, 'amounts' => $modeAmounts];
    }

    private function orgId(): int
    {
        return Auth::user()->organization_id;
    }

    public function render()
    {
        return view('livewire.accounts.dashboard', [
            'menu' => \App\Support\ModuleAccess::filterMenu(
                collect(config('menu.accounts', []))
                    ->reject(fn($m) => ($m['link'] ?? '') === 'accounts.dashboard')
                    ->values()
                    ->all(),
                Auth::user()?->organization
            ),
        ]);
    }
}
