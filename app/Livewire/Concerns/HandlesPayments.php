<?php

namespace App\Livewire\Concerns;

use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Admin\TransportFeePayment;
use App\Models\Student\Section;
use App\Models\Student\StudentDetail;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;

/**
 * The Payments screen — filters, the one-line analytics strip and the merged
 * payment listing — shared verbatim between Accounts\Payments (its own page)
 * and Admin\Fee (the "Payments" tab). Both render the same three partials
 * (`livewire.partials.payments-filters`, `-analytics`, `-table`), so the two
 * screens stay identical without hand-copying.
 *
 * The host component must provide `orgId(): int`, use `WithPagination`, and
 * override `paymentsRoutePrefix()` ('admin' or 'accounts') so receipt links
 * point at its own routes.
 */
trait HandlesPayments
{
    // ─── Filters ──────────────────────────────────────────────────────────────
    public $dateFrom = '';
    public $dateTo = '';
    public $datePreset = '';
    public $paymentStandardId = '';
    public $paymentSectionId = '';
    public $paymentStudentSearch = '';
    public $paymentModeFilter = '';
    public $feeTypeFilter = '';

    public $paymentsPerPage = 15;

    /** Academic-year month keys, April first — June is off by default. */
    private const PAYMENT_MONTH_KEYS = ['apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec', 'jan', 'feb', 'mar'];

    /** The host component calls this from its own mount(). */
    public function initPaymentFilters(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    /** 'admin' or 'accounts' — which route group the receipt links belong to. */
    protected function paymentsRoutePrefix(): string
    {
        return 'accounts';
    }

    /** How many of the year's months a transport pivot row is billed for. */
    private function billableMonthsCount($raw): int
    {
        $months = is_string($raw) ? (json_decode($raw, true) ?: []) : (array) ($raw ?? []);

        $count = 0;
        foreach (self::PAYMENT_MONTH_KEYS as $key) {
            $on = array_key_exists($key, $months) ? (bool) $months[$key] : ($key !== 'jun');
            if ($on) {
                $count++;
            }
        }

        return $count;
    }

    /** Quick date-range presets for the filter bar. */
    public function setDatePreset(string $preset): void
    {
        $this->datePreset = $preset;
        $today = now();

        switch ($preset) {
            case 'today':
                $this->dateFrom = $today->toDateString();
                $this->dateTo = $today->toDateString();
                break;
            case 'yesterday':
                $y = $today->copy()->subDay();
                $this->dateFrom = $y->toDateString();
                $this->dateTo = $y->toDateString();
                break;
            case '7':
                $this->dateFrom = $today->copy()->subDays(6)->toDateString();
                $this->dateTo = $today->toDateString();
                break;
            case '15':
                $this->dateFrom = $today->copy()->subDays(14)->toDateString();
                $this->dateTo = $today->toDateString();
                break;
            case '30':
                $this->dateFrom = $today->copy()->subDays(29)->toDateString();
                $this->dateTo = $today->toDateString();
                break;
            case 'last_month':
                $lastMonth = $today->copy()->subMonthNoOverflow();
                $this->dateFrom = $lastMonth->copy()->startOfMonth()->toDateString();
                $this->dateTo = $lastMonth->copy()->endOfMonth()->toDateString();
                break;
        }

        $this->resetPage();
    }

    /** Filters shared by the header analytics and the payments listing below. */
    private function feePaymentQuery()
    {
        return FeePayment::where('organization_id', $this->orgId())
            ->when($this->paymentStandardId, fn ($q) => $q->where('standard_id', $this->paymentStandardId))
            ->when($this->paymentSectionId, fn ($q) => $q->where('section_id', $this->paymentSectionId))
            ->when($this->paymentModeFilter, fn ($q) => $q->where('payment_mode', $this->paymentModeFilter))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('payment_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('payment_date', '<=', $this->dateTo))
            ->when($this->paymentStudentSearch, fn ($q) => $q->whereHas('studentDetail', function ($sq) {
                $sq->where('full_name', 'like', "%{$this->paymentStudentSearch}%")
                    ->orWhere('admission_no', 'like', "%{$this->paymentStudentSearch}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$this->paymentStudentSearch}%"));
            }));
    }

    private function transportPaymentQuery()
    {
        return TransportFeePayment::where('organization_id', $this->orgId())
            ->when($this->paymentStandardId, fn ($q) => $q->whereHas('studentDetail', fn ($sq) => $sq->where('standard_id', $this->paymentStandardId)))
            ->when($this->paymentSectionId, fn ($q) => $q->whereHas('studentDetail', fn ($sq) => $sq->where('section_id', $this->paymentSectionId)))
            ->when($this->paymentModeFilter, fn ($q) => $q->where('payment_mode', $this->paymentModeFilter))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('payment_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('payment_date', '<=', $this->dateTo))
            ->when($this->paymentStudentSearch, fn ($q) => $q->whereHas('studentDetail', function ($sq) {
                $sq->where('full_name', 'like', "%{$this->paymentStudentSearch}%")
                    ->orWhere('admission_no', 'like', "%{$this->paymentStudentSearch}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$this->paymentStudentSearch}%"));
            }));
    }

    /**
     * The analytics strip — total fee to collect and what has actually come
     * in, split by academic and transport. Collected/remaining figures track
     * every active filter (date range, mode, search) exactly like the
     * listing below, so the header always matches what's on screen. Fee
     * Type is deliberately excluded here since the strip already breaks
     * academic and transport out side by side.
     */
    private function paymentHeaderStats(): array
    {
        $orgId = $this->orgId();

        // ── Scheduled academic fee: fee-structure amount × matching student count ──
        $structureQuery = FeeStructure::where('organization_id', $orgId)
            ->where('is_active', true)->where('fee_type', 'academic');

        if ($this->paymentStandardId) {
            $structureQuery->where('standard_id', $this->paymentStandardId);
            if ($this->paymentSectionId) {
                $structureQuery->where(function ($q) {
                    $q->where('section_id', $this->paymentSectionId)->orWhereNull('section_id');
                });
            }
        }

        $structures = $structureQuery->get();

        if (!$this->paymentStandardId) {
            $totalAcademicFee = 0.0;
            foreach ($structures->pluck('standard_id')->unique() as $stdId) {
                $studentCount = StudentDetail::where('organization_id', $orgId)
                    ->where('standard_id', $stdId)->count();
                $totalAcademicFee += $structures->where('standard_id', $stdId)->sum('amount') * $studentCount;
            }
        } else {
            $studentQuery = StudentDetail::where('organization_id', $orgId)
                ->where('standard_id', $this->paymentStandardId);
            if ($this->paymentSectionId) {
                $studentQuery->where('section_id', $this->paymentSectionId);
            }
            $totalAcademicFee = $structures->sum('amount') * $studentQuery->count();
        }

        // ── Scheduled transport fee: route.monthly_fee × each student's billed months ──
        $txRows = DB::table('transportation_students as ts')
            ->join('transportations as t', 'ts.transportation_id', '=', 't.id')
            ->join('student_details as sd', 'ts.student_detail_id', '=', 'sd.id')
            ->where('ts.organization_id', $orgId)
            ->when($this->paymentStandardId, fn ($q) => $q->where('sd.standard_id', $this->paymentStandardId))
            ->when($this->paymentSectionId, fn ($q) => $q->where('sd.section_id', $this->paymentSectionId))
            ->select('ts.billable_months', 't.monthly_fee')
            ->get();

        $totalTransportFee = 0.0;
        foreach ($txRows as $row) {
            $totalTransportFee += (float) $row->monthly_fee * $this->billableMonthsCount($row->billable_months);
        }

        $totalFee = $totalAcademicFee + $totalTransportFee;

        // ── Collected, filtered the same way as the listing ──
        $feeBase = $this->feePaymentQuery();
        $academicCollected = (clone $feeBase)->where('fee_type', 'academic')->sum('amount');
        $transportFromFeeTable = (clone $feeBase)->where('fee_type', 'transport')->sum('amount');
        $transportFromTxTable = $this->transportPaymentQuery()->sum('amount');
        $transportCollected = (float) $transportFromFeeTable + (float) $transportFromTxTable;

        $totalCollected = $academicCollected + $transportCollected;

        return [
            'total_fee' => $totalFee,
            'total_academic_fee' => $totalAcademicFee,
            'total_transport_fee' => $totalTransportFee,
            'academic_collected' => (float) $academicCollected,
            'transport_collected' => $transportCollected,
            'total_collected' => $totalCollected,
            'academic_remaining' => max(0, $totalAcademicFee - $academicCollected),
            'transport_remaining' => max(0, $totalTransportFee - $transportCollected),
            'remaining_fee' => max(0, $totalFee - $totalCollected),
        ];
    }

    // ─── Filter hooks — the header analytics re-runs on every render anyway ───

    public function updatedPaymentStandardId(): void
    {
        $this->paymentSectionId = '';
        $this->resetPage();
    }

    public function updatedPaymentSectionId(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentModeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedFeeTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentStudentSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->datePreset = '';
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->datePreset = '';
        $this->resetPage();
    }

    public function resetPaymentFilters(): void
    {
        $this->reset(['paymentStandardId', 'paymentSectionId', 'paymentStudentSearch', 'paymentModeFilter', 'feeTypeFilter', 'datePreset']);
        $this->initPaymentFilters();
        $this->resetPage();
    }

    /**
     * Every fee payment, whichever table it actually lives in — regular
     * FeePayment rows (academic, transport, penalty) and TransportFeePayment
     * rows from the dedicated transport-collection flow — merged into one
     * normalized, sorted, paginated list.
     */
    private function paymentsForListing(): LengthAwarePaginator
    {
        $prefix = $this->paymentsRoutePrefix();
        $includeTransportTable = in_array($this->feeTypeFilter, ['', 'transport'], true);

        $feePayments = $this->feePaymentQuery()
            ->when($this->feeTypeFilter, fn ($q) => $q->where('fee_type', $this->feeTypeFilter))
            ->with(['studentDetail.user', 'standard', 'section'])
            ->get();

        $transportPayments = $includeTransportTable
            ? $this->transportPaymentQuery()->with(['studentDetail.user', 'studentDetail.standard', 'studentDetail.section'])->get()
            : collect();

        $merged = collect();

        foreach ($feePayments as $p) {
            $merged->push((object) [
                'id' => $p->id,
                'student_name' => $p->studentDetail?->user?->name ?? $p->studentDetail?->full_name ?? '—',
                'admission_no' => $p->studentDetail?->admission_no,
                'standard_name' => $p->standard?->name,
                'section_name' => $p->section?->name,
                'fee_type' => $p->fee_type,
                'payment_mode' => $p->payment_mode,
                'amount' => (float) $p->amount,
                'penalty_amount' => (float) ($p->penalty_amount ?? 0),
                'waiver_amount' => (float) ($p->waiver_amount ?? 0),
                'waiver_reason' => $p->waiver_reason,
                'payment_date' => $p->payment_date,
                'submitted_by' => $p->submitted_by,
                'receipt_route' => "{$prefix}.fee.receipt",
            ]);
        }

        foreach ($transportPayments as $p) {
            $merged->push((object) [
                'id' => $p->id,
                'student_name' => $p->studentDetail?->user?->name ?? $p->studentDetail?->full_name ?? '—',
                'admission_no' => $p->studentDetail?->admission_no,
                'standard_name' => $p->studentDetail?->standard?->name,
                'section_name' => $p->studentDetail?->section?->name,
                'fee_type' => 'transport',
                'payment_mode' => $p->payment_mode,
                'amount' => (float) $p->amount,
                'penalty_amount' => 0.0,
                'waiver_amount' => 0.0,
                'waiver_reason' => null,
                'payment_date' => $p->payment_date,
                'submitted_by' => $p->submitted_by,
                'receipt_route' => "{$prefix}.transport.receipt",
            ]);
        }

        $sorted = $merged->sortBy([
            ['payment_date', 'desc'],
            ['id', 'desc'],
        ])->values();

        $page = Paginator::resolveCurrentPage('page');
        $items = $sorted->slice(($page - 1) * $this->paymentsPerPage, $this->paymentsPerPage)->values();

        return new LengthAwarePaginator($items, $sorted->count(), $this->paymentsPerPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);
    }

    /** Everything the three shared payment partials need. */
    private function paymentsViewData(): array
    {
        $orgId = $this->orgId();

        return [
            'payments' => $this->paymentsForListing(),
            'headerStats' => $this->paymentHeaderStats(),
            'paymentSections' => $this->paymentStandardId
                ? Section::where('organization_id', $orgId)
                    ->where('standard_id', $this->paymentStandardId)
                    ->where('is_active', true)->get()
                : collect(),
            'paymentsOrgId' => $orgId,
        ];
    }
}
