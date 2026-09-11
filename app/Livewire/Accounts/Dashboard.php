<?php

namespace App\Livewire\Accounts;

use App\Livewire\Concerns\HandlesFeeAnalytics;
use App\Models\Admin\AdmissionEnquiry;
use App\Models\Admin\AdminEmployee;
use App\Models\Admin\AdminSalaryPayment;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\TransportFeePayment;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

/**
 * The accounts dashboard.
 *
 * The fee half of it is the admin Fee > Analytics screen verbatim — same
 * HandlesFeeAnalytics trait, same fee-analytics-panel partial — so the
 * billable / collected / outstanding figures here are the ones the Fee and
 * Payments screens show, not a second set computed another way. On top of
 * that it adds the roll, payroll, admissions and transport counts that only
 * matter to the accounts desk, plus the latest receipts.
 */
class Dashboard extends Component
{
    use HandlesFeeAnalytics;

    /** Roll, payroll, admissions — the non-fee half of the dashboard. */
    public array $officeStats = [];

    /** The latest receipts, whichever table they came from. */
    public array $recentPayments = [];

    public function mount(): void
    {
        // Defaults, so a failing query leaves a readable page rather than a 500.
        $this->officeStats = [
            'students' => 0, 'classes' => 0, 'riders' => 0, 'new_admissions' => 0,
            'employees' => 0, 'salary_month' => 0, 'salary_pending' => 0,
            'enquiries' => 0, 'enquiries_pending' => 0,
        ];

        try {
            $this->loadAnalytics();
            $this->loadOfficeStats();
            $this->loadRecentPayments();
        } catch (\Throwable $e) {
            logger()->error('Accounts dashboard load failed: ' . $e->getMessage());
        }
    }

    private function orgId(): int
    {
        return (int) Auth::user()->organization_id;
    }

    /** Analytics rows link out to the accounts View Fee page. */
    public function openStudentLedger(int $studentId)
    {
        return redirect()->route('accounts.view-fee', [
            'organization'  => $this->orgId(),
            'viewStudentId' => $studentId,
        ]);
    }

    private function loadOfficeStats(): void
    {
        $orgId = $this->orgId();

        $riders = DB::table('transportation_students')
            ->where('organization_id', $orgId)
            ->distinct()->count('student_detail_id');

        $employees = AdminEmployee::where('organization_id', $orgId)->count();

        $salaryMonth = (float) AdminSalaryPayment::where('organization_id', $orgId)
            ->where('month', now()->format('Y-m'))->where('status', 'paid')->sum('amount');
        $salaryPending = (float) AdminSalaryPayment::where('organization_id', $orgId)
            ->where('month', now()->format('Y-m'))->where('status', '!=', 'paid')->sum('amount');

        $this->officeStats = [
            'students' => StudentDetail::where('organization_id', $orgId)->count(),
            'classes'  => Standard::where('organization_id', $orgId)->where('is_active', true)->count(),
            'riders'   => $riders,
            'new_admissions' => StudentDetail::where('organization_id', $orgId)
                ->whereDate('created_at', '>=', now()->startOfMonth())->count(),
            'employees'      => $employees,
            'salary_month'   => $salaryMonth,
            'salary_pending' => $salaryPending,
            'enquiries'         => AdmissionEnquiry::where('organization_id', $orgId)->count(),
            'enquiries_pending' => AdmissionEnquiry::where('organization_id', $orgId)
                ->where('status', '!=', 'updated')->count(),
        ];
    }

    /**
     * The last eight receipts across both payment tables, so a transport
     * collection shows up here the same as an academic one.
     */
    private function loadRecentPayments(): void
    {
        $orgId = $this->orgId();

        $fee = FeePayment::with('studentDetail:id,full_name,admission_no')
            ->where('organization_id', $orgId)
            ->latest('payment_date')->latest('id')->limit(8)->get()
            ->map(fn ($p) => [
                'student' => $p->studentDetail?->full_name ?? '—',
                'admno'   => $p->studentDetail?->admission_no ?? '',
                'amount'  => (float) $p->amount,
                'mode'    => $p->payment_mode,
                'type'    => $p->fee_type,
                'date'    => $p->payment_date?->format('d M Y') ?? '—',
                'sort'    => $p->payment_date?->timestamp ?? 0,
                'receipt' => $p->receipt_number,
                'route'   => 'accounts.fee.receipt',
                'id'      => $p->id,
            ]);

        $transport = Schema::hasTable('transport_fee_payments')
            ? TransportFeePayment::with('studentDetail:id,full_name,admission_no')
                ->where('organization_id', $orgId)
                ->latest('payment_date')->latest('id')->limit(8)->get()
                ->map(fn ($p) => [
                    'student' => $p->studentDetail?->full_name ?? '—',
                    'admno'   => $p->studentDetail?->admission_no ?? '',
                    'amount'  => (float) $p->amount,
                    'mode'    => $p->payment_mode,
                    'type'    => 'transport',
                    'date'    => $p->payment_date?->format('d M Y') ?? '—',
                    'sort'    => $p->payment_date?->timestamp ?? 0,
                    'receipt' => $p->receipt_number,
                    'route'   => 'accounts.transport.receipt',
                    'id'      => $p->id,
                ])
            : collect();

        $this->recentPayments = $fee->concat($transport)
            ->sortByDesc('sort')->take(8)->values()->all();
    }

    public function render()
    {
        return view('livewire.accounts.dashboard', array_merge($this->analyticsViewData(), [
            'standards' => Standard::where('organization_id', $this->orgId())
                ->where('is_active', true)->orderBy('id')->get(),
            'orgId' => $this->orgId(),
            'menu'  => \App\Support\ModuleAccess::filterMenu(
                collect(config('menu.accounts', []))
                    ->reject(fn ($m) => ($m['link'] ?? '') === 'accounts.dashboard')
                    ->values()
                    ->all(),
                Auth::user()?->organization
            ),
        ]));
    }
}
