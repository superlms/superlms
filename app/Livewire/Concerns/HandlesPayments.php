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
 * (`livewire.partials.payments-header`, `-analytics`, `-table`), so the two
 * screens stay identical without hand-copying.
 *
 * The host component must provide `orgId(): int`, use `WithPagination`, and
 * override `paymentsRoutePrefix()` ('admin' or 'accounts') so receipt links
 * point at its own routes.
 */
trait HandlesPayments
{
    use CountsBillableMonths;

    // ─── Filters ──────────────────────────────────────────────────────────────
    public $dateFrom = '';
    public $dateTo = '';
    public $datePreset = 'this_month';
    public $paymentStandardId = '';
    public $paymentSectionId = '';
    public $paymentStudentSearch = '';
    public $paymentModeFilter = '';
    public $feeTypeFilter = '';

    // 50 a page (2026-10-14); the app's API asks for its own.
    public $paymentsPerPage = 50;

    /** The host component calls this from its own mount(). */
    public function initPaymentFilters(): void
    {
        $this->datePreset = 'this_month';
        $this->applyDatePreset();
    }

    /** The range dropdown's options, in order. '' is the manual from/to pair. */
    private function datePresetOptions(): array
    {
        return [
            'today'      => 'Today',
            'yesterday'  => 'Yesterday',
            '7'          => 'Last 7 Days',
            '15'         => 'Last 15 Days',
            '30'         => 'Last 30 Days',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            ''           => 'Custom Range',
        ];
    }

    /** 'admin' or 'accounts' — which route group the receipt links belong to. */
    protected function paymentsRoutePrefix(): string
    {
        return 'accounts';
    }

    /**
     * Fee-type tabs above the filter band: '' (all) | academic | transport |
     * dues (Last Year Dues — academic payments taken as Dues, FeeDues) | penalty.
     */
    public function setFeeTypeFilter(string $type): void
    {
        $this->feeTypeFilter = in_array($type, ['academic', 'transport', 'dues', 'penalty'], true) ? $type : '';
        $this->resetPage();
    }

    /**
     * One fee-type tab on fee_payments. Last Year Dues are academic payments
     * marked for the dues, so they have their own tab and stay out of
     * Academic's; every other tab is its fee_type.
     */
    private function applyFeeTypeTab($query)
    {
        $tracked = \App\Support\FeeDues::tracked();

        return match ($this->feeTypeFilter) {
            ''       => $query,
            'dues'   => $tracked
                ? $query->where('fee_type', 'academic')->where('applies_to', 'dues')
                : $query->whereRaw('1 = 0'),
            'academic' => $query->where('fee_type', 'academic')
                ->when($tracked, fn ($q) => $q->where(fn ($w) => $w->whereNull('applies_to')->orWhere('applies_to', '!=', 'dues'))),
            default  => $query->where('fee_type', $this->feeTypeFilter),
        };
    }

    /** The range dropdown moved the date span; recompute from/to. */
    public function updatedDatePreset(): void
    {
        $this->applyDatePreset();
        $this->resetPage();
    }

    /** Turn the chosen preset into a concrete from/to pair. */
    private function applyDatePreset(): void
    {
        $today = now();

        switch ($this->datePreset) {
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
            case 'this_month':
                $this->dateFrom = $today->copy()->startOfMonth()->toDateString();
                $this->dateTo = $today->toDateString();
                break;
            case 'last_month':
                $lastMonth = $today->copy()->subMonthNoOverflow();
                $this->dateFrom = $lastMonth->copy()->startOfMonth()->toDateString();
                $this->dateTo = $lastMonth->copy()->endOfMonth()->toDateString();
                break;
        }
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
    /**
     * A school on Custom Fee (App\Support\CustomFee): its fee is each
     * student's own particulars and is paid into custom_fee_payments, so the
     * screen lists and counts those (with the bus fee) in place of the
     * ordinary fee's. Its Fee tab is the Academic one.
     */
    private function paymentsCustomFee(): bool
    {
        return \App\Support\CustomFee::enabled($this->orgId());
    }

    private function customFeePaymentQuery()
    {
        return \App\Models\Admin\Fee\CustomFeePayment::where('organization_id', $this->orgId())
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

    private function paymentHeaderStats(): array
    {
        $orgId  = $this->orgId();
        $custom = $this->paymentsCustomFee();

        $studentQuery = StudentDetail::where('organization_id', $orgId)
            ->when($this->paymentStandardId, fn ($q) => $q->where('standard_id', $this->paymentStandardId))
            ->when($this->paymentSectionId, fn ($q) => $q->where('section_id', $this->paymentSectionId));

        if ($custom) {
            // Every student's own particulars, added up.
            $totalAcademicFee = (float) \App\Models\Admin\Fee\CustomFeeItem::where('organization_id', $orgId)
                ->whereIn('student_detail_id', (clone $studentQuery)->select('id'))
                ->sum('amount');
        } else {
        // ── Scheduled academic fee: what each student is billed — their
        //    class's whole-class heads and their own section's, as View Fee and
        //    Fee → Analytics read it. A head written for one section is not
        //    charged to the class's other sections (Add writes a copy per
        //    section picked, so counting every copy for every student counted
        //    the class's fee once per section). ──
        $heads = FeeStructure::where('organization_id', $orgId)
            ->where('is_active', true)->where('fee_type', 'academic')
            ->when($this->paymentStandardId, fn ($q) => $q->where('standard_id', $this->paymentStandardId))
            ->get(['standard_id', 'section_id', 'amount'])
            ->groupBy('standard_id');

        // Students per class and section, so each pair's fee is worked out once.
        $pairs = (clone $studentQuery)
            ->selectRaw('standard_id, section_id, COUNT(*) as students')
            ->groupBy('standard_id', 'section_id')
            ->get();

        $totalAcademicFee = 0.0;
        foreach ($pairs as $pair) {
            $fee = ($heads[$pair->standard_id] ?? collect())
                ->filter(fn ($h) => $h->section_id === null || (int) $h->section_id === (int) $pair->section_id)
                ->sum('amount');
            $totalAcademicFee += (float) $fee * (int) $pair->students;
        }

        // …and the students' own Last Year Dues.
        $totalAcademicFee += $this->paymentStandardId || $this->paymentSectionId
            ? array_sum(FeeStructure::ownTotals($orgId, (clone $studentQuery)->pluck('id')->all()))
            : FeeStructure::ownTotalForSchool($orgId);
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
        $academicCollected = $custom
            ? $this->customFeePaymentQuery()->sum('amount')
            : (clone $feeBase)->where('fee_type', 'academic')->sum('amount');
        // On Custom Fee the ordinary fee table is not read at all.
        $transportFromFeeTable = $custom ? 0 : (clone $feeBase)->where('fee_type', 'transport')->sum('amount');
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
        $custom = $this->paymentsCustomFee();

        // On Custom Fee: its own payments in the ordinary fee's place.
        $feePayments = $custom
            ? collect()
            : $this->applyFeeTypeTab($this->feePaymentQuery())
                ->with(['studentDetail.user', 'standard', 'section'])
                ->get();
        $customPayments = $custom && in_array($this->feeTypeFilter, ['', 'academic'], true)
            ? $this->customFeePaymentQuery()->with(['studentDetail.user', 'standard', 'section'])->get()
            : collect();

        $transportPayments = $includeTransportTable
            ? $this->transportPaymentQuery()->with(['studentDetail.user', 'studentDetail.standard', 'studentDetail.section'])->get()
            : collect();

        $merged = collect();
        $collectorType = $this->paymentCollectorTypes($feePayments->concat($customPayments), $transportPayments);

        foreach ($customPayments as $p) {
            $merged->push((object) [
                'id' => $p->id,
                'student_name' => $p->studentDetail?->user?->name ?? $p->studentDetail?->full_name ?? '—',
                'father_name' => $p->studentDetail?->father_name,
                'collected_by_type' => $collectorType($p->submitted_by, $p->payment_mode),
                'is_dues' => false,
                'admission_no' => $p->studentDetail?->admission_no,
                'standard_name' => $p->standard?->name,
                'section_name' => $p->section?->name,
                'fee_type' => 'fee',
                'payment_mode' => $p->payment_mode,
                'amount' => (float) $p->amount,
                'penalty_amount' => 0.0,
                'waiver_amount' => 0.0,
                'waiver_reason' => null,
                'payment_date' => $p->payment_date,
                'submitted_by' => $p->submitted_by,
                'receipt_route' => "{$prefix}.fee.custom-receipt",
            ]);
        }

        foreach ($feePayments as $p) {
            $merged->push((object) [
                'id' => $p->id,
                'student_name' => $p->studentDetail?->user?->name ?? $p->studentDetail?->full_name ?? '—',
                'father_name' => $p->studentDetail?->father_name,
                'collected_by_type' => $collectorType($p->submitted_by, $p->payment_mode),
                // Taken as Last Year Dues (FeeDues) — the Type column says Dues.
                'is_dues' => \App\Support\FeeDues::isDues($p),
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
                'father_name' => $p->studentDetail?->father_name,
                'collected_by_type' => $collectorType($p->submitted_by, $p->payment_mode),
                'is_dues' => false,
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

    /**
     * Who took each payment, as a kind of person rather than a name — the
     * listing's Collected By says "Admin", "Sub Admin", "Accounts"…
     * (2026-10-14). submitted_by holds a staff member's name (the panel and
     * the admin app), a user id (bus fees, older rows), "PhonePe" (paid in the
     * app) or nothing; a name is matched to the school's staff by name.
     * Returns fn (submitted_by, payment_mode) => label.
     */
    private function paymentCollectorTypes($feePayments, $transportPayments): \Closure
    {
        $label = fn (?string $role) => match ($role) {
            'admin'                            => 'Admin',
            'sub-admin'                        => 'Sub Admin',
            'accounts'                         => 'Accounts',
            'super-admin', 'sub-super-admin'   => 'Super Admin',
            'teacher'                          => 'Teacher',
            'driver'                           => 'Driver',
            'user'                             => 'Student',
            default                            => null,
        };

        $values = collect($feePayments)->pluck('submitted_by')
            ->merge(collect($transportPayments)->pluck('submitted_by'))
            ->filter(fn ($v) => $v !== null && $v !== '')->unique();

        $ids    = $values->filter(fn ($v) => ctype_digit((string) $v))->map(fn ($v) => (int) $v)->all();
        $byId   = $ids ? \App\Models\User::whereIn('id', $ids)->pluck('role', 'id')->all() : [];

        // The school's staff by name — admin before sub-admin before accounts
        // should two share a name.
        $byName = [];
        foreach (\App\Models\User::where('organization_id', $this->orgId())
            ->whereIn('role', ['admin', 'sub-admin', 'accounts'])
            ->get(['name', 'role'])
            ->sortBy(fn ($u) => array_search($u->role, ['admin', 'sub-admin', 'accounts'], true)) as $u) {
            $byName[mb_strtolower(trim((string) $u->name))] ??= $u->role;
        }

        return function ($value, $mode) use ($label, $byId, $byName): string {
            $value = trim((string) $value);
            if ($value === '' || strcasecmp($value, 'PhonePe') === 0) {
                // Nobody at the counter: the student paid in the app.
                return strtolower((string) $mode) === 'online' || $value !== '' ? 'Online' : '—';
            }
            $role = ctype_digit($value) ? ($byId[(int) $value] ?? null) : ($byName[mb_strtolower($value)] ?? null);

            return $label($role) ?? '—';
        };
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
            'datePresets' => $this->datePresetOptions(),
            // The tabs over the filters: a Custom Fee school has no dues or penalty.
            'paymentTabs' => $this->paymentsCustomFee()
                ? ['' => 'All', 'academic' => 'Fee', 'transport' => 'Transport']
                : ['' => 'All', 'academic' => 'Academic', 'transport' => 'Transport', 'dues' => 'Last Year Dues', 'penalty' => 'Penalty'],
        ];
    }
}
