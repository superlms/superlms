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

    public function mount(): void
    {
        // Defaults so the view always renders even if a query fails
        $this->stats = [
            'collected' => 0, 'pending' => 0, 'today' => 0, 'month' => 0,
            'transport_collected' => 0, 'students' => 0, 'employees' => 0,
            'salary_month' => 0, 'admissions' => 0, 'admissions_pending' => 0,
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
        $monthCollection = (float) FeePayment::where('organization_id', $orgId)
            ->whereMonth('payment_date', now()->month)->whereYear('payment_date', now()->year)->sum('amount');

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

        $this->stats = [
            'collected'           => $totalCollected,
            'pending'             => max(0, $structureTotal - $totalCollected),
            'today'               => $todayCollection,
            'month'               => $monthCollection,
            'transport_collected' => $transportCollected,
            'students'            => $totalStudents,
            'employees'           => $employees,
            'salary_month'        => $salaryThisMonth,
            'admissions'          => $admissionsTotal,
            'admissions_pending'  => $admissionsPending,
        ];

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
