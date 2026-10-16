<?php

namespace App\Livewire\Concerns;

use App\Models\Admin\Fee\FeeConcession;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Admin\TransportFeePayment;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Services\SchoolWhatsApp;
use App\Support\AcademicYear;
use App\Support\FeeDues;
use App\Support\TransportBilling;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Support\NameOrder;

/**
 * Fee Submission — shared verbatim between Admin\Fee's "Fee Submission" tab and
 * Accounts\FeeSubmission (its own page). This trait, `livewire.partials.
 * fee-submission-header` (the filter band) and `livewire.partials.
 * fee-submission-panel` (the ledger + the slide-in Collect Fee panel) are now
 * the one place either needs to change for both to update together — same
 * split as View Fee's HandlesViewFee / view-fee-header / view-fee-panel.
 *
 * The per-student ledger itself comes from HandlesStudentFeeView, which this
 * trait requires the host to use as well.
 *
 * The host must provide `orgId(): int` and use WireUiActions (for
 * `notification()`), and should set `$submitDate = today()->toDateString()`
 * in its own mount().
 */
trait HandlesFeeSubmission
{
    public $submissionStandardId = '';
    public $submissionSectionId  = '';
    public $selectedStudentId    = '';
    public $submissionSearch     = '';

    public $classStructures     = [];
    public $studentTransactions = [];
    public array $selectedStudentInfo = [];
    public $studentConcessions  = [];
    public float $netPayable    = 0.0;
    /** The selected student's ledger, rendered by livewire.partials.student-fee-view. */
    public array $submissionLedger = [];

    // Collect Fee slide-in panel
    public bool $showSubmitPanel = false;
    public $submitAmount      = '';
    public $submitFeeType     = 'academic';
    public $submitPaymentMode = 'cash';
    public $submitDate        = '';
    public $submitRemark      = '';
    public $submittedBy       = '';

    // Edit a payment's date — the popup the ledger's Payments list opens
    public bool $showPaymentDateEdit = false;
    public string $editPaymentKind   = '';   // 'academic' (fee_payments) | 'transport' (transport_fee_payments)
    public $editPaymentId            = null;
    public $editPaymentDate          = '';
    public array $editPaymentInfo    = [];

    public function updatedSubmissionStandardId(): void
    {
        $this->submissionSectionId = '';
        $this->selectedStudentId   = '';
        $this->resetSubmissionStudentData();
    }

    public function updatedSubmissionSectionId(): void
    {
        $this->selectedStudentId = '';
        $this->resetSubmissionStudentData();
    }

    /** Search button — list students by student/father name (class optional). */
    public function searchSubmissionStudents(): void
    {
        $this->selectedStudentId = '';
        $this->resetSubmissionStudentData();
    }

    private function resetSubmissionStudentData(): void
    {
        $this->classStructures     = [];
        $this->studentTransactions = [];
        $this->selectedStudentInfo = [];
        $this->studentConcessions  = [];
        $this->netPayable          = 0.0;
        $this->submissionLedger    = [];
    }

    public function updatedSelectedStudentId(): void
    {
        if (!$this->selectedStudentId) {
            $this->resetSubmissionStudentData();
            return;
        }

        $student = StudentDetail::with(['user', 'standard', 'section'])->find($this->selectedStudentId);
        if (!$student) {
            return;
        }

        $orgId = $this->orgId();

        $this->selectedStudentInfo = [
            'name'         => $student->full_name ?? ($student->user->name ?? '—'),
            'father_name'  => $student->father_name ?? '—',
            'admission_no' => $student->admission_no ?? '—',
            'roll_no'      => $student->roll_no ?? '—',
            'class'        => $student->standard->name ?? '—',
            'section'      => $student->section->name ?? '—',
            'phone'        => $student->phone ?? '—',
        ];

        // Load fee structures for this student's class
        $this->classStructures = FeeStructure::where('organization_id', $orgId)
            ->where('standard_id', $student->standard_id)
            ->where(function ($q) use ($student) {
                $q->where('section_id', $student->section_id)->orWhereNull('section_id');
            })
            ->where('is_active', true)
            ->get()
            // …and the student's own — Last Year Dues.
            ->concat(FeeStructure::ownRows($orgId, [$student->id], null))
            ->toArray();

        // Concessions for this student — penalty waivers are a different pool
        // (netted by FeeCycleBreakdown), kept out of the base-fee discount here.
        $this->studentConcessions = FeeConcession::where('organization_id', $orgId)
            ->where('student_detail_id', $this->selectedStudentId)
            ->where('is_penalty', false)
            ->get()->toArray();

        // Net payable = total structure − concessions − amount already paid.
        // Each concession is also surfaced as a "concession" entry in the ledger
        // below (as a payment of type concession), but it reduces the fee via
        // this discount — NOT via the paid sum — so there's no double counting.
        $totalStructure = collect($this->classStructures)->sum(fn ($s) => (float) $s['amount']);
        // A concession on the Last Year Dues is worked out on them alone, and
        // never comes to more than they do.
        $duesTotal = collect($this->classStructures)
            ->filter(fn ($s) => !empty($s['student_detail_id']) && $s['fee_type'] === 'academic')
            ->sum(fn ($s) => (float) $s['amount']);
        $duesDiscount = 0.0;
        $discount = 0.0;
        $concessionRows = [];
        foreach ($this->studentConcessions as $c) {
            $onDues = $c['fee_type'] === 'dues';
            $base   = $onDues ? $duesTotal : $totalStructure;
            $amt = $c['concession_type'] === 'percent'
                ? round($base * ((float) $c['value']) / 100, 2)
                : min((float) $c['value'], $base);
            if ($onDues) {
                $amt = round(min($amt, max(0, $duesTotal - $duesDiscount)), 2);
                $duesDiscount += $amt;
            }
            $discount += $amt;
            $concessionRows[] = [
                'id'             => null,
                'receipt_number' => 'CONCESSION',
                'amount'         => $amt,
                'fee_type'       => in_array($c['fee_type'], ['all', 'dues'], true) ? 'academic' : $c['fee_type'],
                'payment_mode'   => 'concession',
                'submitted_by'   => !empty($c['reason']) ? $c['reason'] : 'Concession',
                'payment_date'   => $c['created_at'] ?? now()->toDateString(),
                'is_concession'  => true,
            ];
        }

        // Load real payment history for this student (admin/accounts + app payments)
        $realTransactions = FeePayment::with(['standard', 'section'])
            ->where('organization_id', $orgId)
            ->where('student_detail_id', $this->selectedStudentId)
            ->orderByDesc('payment_date')
            ->get()->toArray();

        // Penalty payments settle accrued late fees, not the academic/transport
        // structure itself — excluded here so they never shrink Net Payable.
        $paid = collect($realTransactions)
            ->where('fee_type', '!=', 'penalty')
            ->sum(fn ($t) => (float) $t['amount']);
        $this->netPayable = max(0, round($totalStructure - $discount - $paid, 2));

        // Ledger = real payments + concessions shown as concession-type entries.
        $this->studentTransactions = array_merge($realTransactions, $concessionRows);

        // The summary on screen is the same ledger View Fee renders.
        $this->submissionLedger = $this->buildStudentFeeView((int) $this->selectedStudentId);
    }

    public function openSubmitPanel(): void
    {
        if (!$this->selectedStudentId) {
            $this->notification()->error('Select a student first.');
            return;
        }
        $this->submitAmount      = '';
        // Default to whichever type actually has something due, so the form
        // doesn't open on a disabled option.
        $caps = $this->feeTypeCaps();
        $this->submitFeeType     = collect(['academic', 'transport', 'dues', 'penalty'])->first(fn ($t) => $caps[$t] > 0) ?? 'academic';
        $this->submitPaymentMode = 'cash';
        $this->submitDate        = today()->toDateString();
        $this->submitRemark      = '';
        $this->submittedBy       = Auth::user()->name ?? '';
        $this->resetValidation();
        $this->showSubmitPanel   = true;
    }

    public function closeSubmitPanel(): void
    {
        $this->showSubmitPanel = false;
    }

    public function submitFeePayment(): void
    {
        $this->validate([
            'selectedStudentId' => 'required|exists:student_details,id',
            'submitAmount'      => 'required|numeric|min:1',
            // 'dues' — an academic payment against the Last Year Dues only (FeeDues).
            'submitFeeType'     => 'required|in:academic,transport,penalty,dues',
            'submitPaymentMode' => 'required|in:cash,online,cheque,bank_transfer',
            // Never before the running session — 1 April.
            'submitDate'        => 'required|date|after_or_equal:' . $this->feeMinDate(),
            'submittedBy'       => 'required|string|max:255',
        ], [
            'submitDate.after_or_equal' => $this->feeMinDateMessage(),
        ]);

        // The fee type picked must have something due; the amount may then be
        // up to all the student owes (academic, transport and Last Year Dues),
        // what is over the type picked going to the others. A penalty is only
        // ever up to the penalty currently accrued.
        if ($error = $this->feeCollectionError($this->submitFeeType, (float) $this->submitAmount)) {
            [$title, $message] = $error;
            if ($title === 'Amount too high') {
                $this->addError('submitAmount', $message);
            }
            $this->notification()->error($title, $message);
            return;
        }

        try {
            $student = StudentDetail::find($this->selectedStudentId);
            $parts   = $this->feeCollectionSplit($this->submitFeeType, (float) $this->submitAmount);

            $this->bookFeeCollection($student, $parts, [
                'payment_mode' => $this->submitPaymentMode,
                'payment_date' => $this->submitDate,
                'remark'       => $this->submitRemark,
                'submitted_by' => $this->submittedBy,
                'user_id'      => Auth::id(),
            ]);

            $this->notification()->success(
                'Fee submitted successfully!',
                count($parts) > 1 ? self::describeFeeCollection($parts) : null
            );
            $this->reset(['submitAmount', 'submitFeeType', 'submitPaymentMode', 'submitRemark', 'submittedBy']);
            $this->submitDate = today()->toDateString();
            $this->showSubmitPanel = false;

            // Refresh transactions + net payable
            $this->updatedSelectedStudentId();
        } catch (\Exception $e) {
            $this->notification()->error('Error submitting fee', $e->getMessage());
        }
    }

    // ── A payment's date, corrected from the ledger's Payments list ───────────

    /** The earliest date a fee may carry: 1 April of the running session. */
    protected function feeMinDate(): string
    {
        return AcademicYear::start()->toDateString();
    }

    private function feeMinDateMessage(): string
    {
        return 'The date cannot be before ' . Carbon::parse($this->feeMinDate())->format('d M Y') . '.';
    }

    /**
     * The selected student's payment behind a ledger row — a bus fee lives in
     * transport_fee_payments, everything else (academic, penalty) in fee_payments.
     */
    private function ledgerPayment(string $kind, $id): FeePayment|TransportFeePayment|null
    {
        if (!$this->selectedStudentId || !$id) {
            return null;
        }

        $model = $kind === 'transport' ? TransportFeePayment::class : FeePayment::class;

        return $model::where('organization_id', $this->orgId())
            ->where('student_detail_id', $this->selectedStudentId)
            ->find($id);
    }

    public function openPaymentDateEdit(string $kind, $id): void
    {
        $payment = $this->ledgerPayment($kind, $id);
        if (!$payment) {
            $this->notification()->error('Payment not found.');
            return;
        }

        $this->editPaymentKind = $kind === 'transport' ? 'transport' : 'academic';
        $this->editPaymentId   = $payment->id;
        $this->editPaymentDate = $payment->payment_date?->toDateString() ?? '';
        $this->editPaymentInfo = [
            'receipt_number' => $payment->receipt_number,
            'amount'         => (float) $payment->amount,
            'fee_type'       => $kind === 'transport' ? 'transport' : $payment->fee_type,
        ];
        $this->resetValidation('editPaymentDate');
        $this->showPaymentDateEdit = true;
    }

    public function closePaymentDateEdit(): void
    {
        $this->showPaymentDateEdit = false;
        $this->resetValidation('editPaymentDate');
    }

    /** Only the date moves — amount, mode, receipt number and the rest stay as booked. */
    public function savePaymentDate(): void
    {
        $this->validate([
            'editPaymentDate' => 'required|date|after_or_equal:' . $this->feeMinDate(),
        ], [
            'editPaymentDate.required'       => 'Choose a date.',
            'editPaymentDate.date'           => 'Choose a valid date.',
            'editPaymentDate.after_or_equal' => $this->feeMinDateMessage(),
        ]);

        $payment = $this->ledgerPayment($this->editPaymentKind, $this->editPaymentId);
        if (!$payment) {
            $this->showPaymentDateEdit = false;
            $this->notification()->error('Payment not found.');
            return;
        }

        try {
            $payment->update(['payment_date' => $this->editPaymentDate]);

            $this->notification()->success('Payment date updated.');
            $this->showPaymentDateEdit = false;
            $this->reset(['editPaymentKind', 'editPaymentId', 'editPaymentDate', 'editPaymentInfo']);

            // Refresh the ledger — the Payments list is ordered by date.
            $this->updatedSelectedStudentId();
        } catch (\Exception $e) {
            $this->notification()->error('Error updating the date', $e->getMessage());
        }
    }

    /**
     * What is left of each fee — this year's academic fee (the installments),
     * transport, the Last Year Dues and the penalty accrued (net of any waiver
     * or payment) — and 'overall', the academic, transport and dues together:
     * the most one collection can take. All read straight off
     * $submissionLedger so they match what's on screen; the Collect Fee panel
     * greys out a fee with nothing left and hints the cap under the Amount.
     */
    protected function feeTypeCaps(): array
    {
        $ledger = $this->submissionLedger;
        $dues   = $ledger['dues'] ?? [];

        // With dues, the academic side is this year's fee and the dues (FeeDues).
        $academic = !empty($dues['has'])
            ? max(0, (float) ($dues['cycle_total'] ?? 0) - (float) ($dues['cycle_paid'] ?? 0))
            : (float) ($ledger['academic']['remaining'] ?? 0);
        $transport = (float) ($ledger['transport']['remaining'] ?? 0);
        $duesLeft  = (float) ($dues['dues_remaining'] ?? 0);

        return [
            'academic'  => round($academic, 2),
            'transport' => round($transport, 2),
            'penalty'   => round(collect($ledger['cycles'] ?? [])->sum('penalty_net'), 2),
            'dues'      => round($duesLeft, 2),
            'overall'   => round($academic + $transport + $duesLeft, 2),
        ];
    }

    /**
     * Why a collection of $amount for $type cannot be taken, as [title,
     * message], or null when it can: the type picked must have something left,
     * and the amount be no more than the student owes in all (a penalty, no
     * more than the penalty).
     */
    protected function feeCollectionError(string $type, float $amount): ?array
    {
        $caps = $this->feeTypeCaps();

        if (($caps[$type] ?? 0) <= 0) {
            return match ($type) {
                'penalty' => ['No penalty due', 'This student has no penalty currently due — there is nothing to submit.'],
                'dues'    => ['No dues', 'This student has no Last Year Dues left to pay.'],
                default   => ['Nothing due', ucfirst($type) . ' fee for this student is already fully paid for the year.'],
            };
        }

        $cap = $type === 'penalty' ? $caps['penalty'] : $caps['overall'];
        if ($amount > $cap + 0.01) {
            return ['Amount too high', $type === 'penalty'
                ? 'You cannot collect more than ₹' . number_format($cap, 2) . ' — that is the penalty currently due.'
                : 'You cannot collect more than ₹' . number_format($cap, 2) . ' — that is all this student still owes.'];
        }

        return null;
    }

    /**
     * How one collection is booked: the fee type picked takes what is left of
     * it first; anything over goes to transport, then the Last Year Dues, then
     * this year's academic fee. A penalty stands alone. [type => amount], in
     * booking order, the fees it does not reach left out.
     *
     * @return array<string, float>
     */
    protected function feeCollectionSplit(string $type, float $amount): array
    {
        $caps   = $this->feeTypeCaps();
        $amount = round($amount, 2);
        $order  = $type === 'penalty' ? ['penalty'] : array_unique([$type, 'transport', 'dues', 'academic']);

        $parts = [];
        foreach ($order as $fee) {
            $take = round(min($amount, max(0, (float) ($caps[$fee] ?? 0))), 2);
            if ($take > 0) {
                $parts[$fee] = $take;
                $amount      = round($amount - $take, 2);
            }
            if ($amount <= 0) {
                break;
            }
        }

        return $parts;
    }

    /**
     * Books a collection as feeCollectionSplit() lays it out — a payment, and
     * a receipt, for each fee it reaches, all or none of them — with one
     * WhatsApp "Fee received" for the whole of it. A bus fee goes with the
     * student's other transport payments (transport_fee_payments, which the
     * Transport module, View Fee, the receipts and the app all read); a dues
     * part is an academic payment marked for the Last Year Dues (FeeDues).
     *
     * @param  array{payment_mode: string, payment_date: string, remark: ?string, submitted_by: string, user_id: ?int}  $meta
     * @return array<int, FeePayment|TransportFeePayment>  the payments, in booking order
     */
    protected function bookFeeCollection(StudentDetail $student, array $parts, array $meta): array
    {
        $orgId = $this->orgId();

        return SchoolWhatsApp::oneFeeMessage(fn () => DB::transaction(function () use ($orgId, $student, $parts, $meta) {
            $made = [];
            foreach ($parts as $fee => $amount) {
                if ($fee === 'transport') {
                    [$route] = TransportBilling::forStudent($orgId, (int) $student->id);
                    $year    = (int) date('Y', strtotime((string) $meta['payment_date']));

                    $made[] = TransportFeePayment::create([
                        'organization_id'   => $orgId,
                        'transportation_id' => $route?->id,
                        'student_detail_id' => $student->id,
                        'amount'            => $amount,
                        'payment_mode'      => $meta['payment_mode'],
                        'payment_date'      => $meta['payment_date'],
                        'academic_year'     => $year . '-' . substr((string) ($year + 1), -2),
                        'remark'            => $meta['remark'] ?: null,
                        // transport_fee_payments keeps the collector as a user id
                        'submitted_by'      => $meta['user_id'],
                    ]);
                    continue;
                }

                $isDues = $fee === 'dues';
                $made[] = FeePayment::create([
                    'organization_id'   => $orgId,
                    'student_detail_id' => $student->id,
                    'standard_id'       => $student->standard_id,
                    'section_id'        => $student->section_id,
                    'fee_type'          => $isDues ? 'academic' : $fee,
                    'amount'            => $amount,
                    'payment_mode'      => $meta['payment_mode'],
                    'payment_date'      => $meta['payment_date'],
                    'remark'            => $meta['remark'],
                    'submitted_by'      => $meta['submitted_by'],
                ] + ($isDues && FeeDues::tracked() ? ['applies_to' => 'dues'] : []));
            }

            return $made;
        }));
    }

    /** "Academic ₹2,000.00 · Transport ₹1,000.00 · Last Year Dues ₹1,000.00" */
    protected static function describeFeeCollection(array $parts): string
    {
        $names = ['academic' => 'Academic', 'transport' => 'Transport', 'dues' => 'Last Year Dues', 'penalty' => 'Penalty'];

        return collect($parts)
            ->map(fn ($amount, $fee) => ($names[$fee] ?? ucfirst($fee)) . ' ₹' . number_format($amount, 2))
            ->implode(' · ');
    }

    /**
     * The dropdowns the filter band needs, computed fresh each render rather
     * than kept as separate mutable state — class/section/search already
     * drive it, so there's nothing here that can go stale.
     */
    protected function feeSubmissionViewData(): array
    {
        $orgId = $this->orgId();

        $fsStandards = Standard::where('organization_id', $orgId)
            ->where('is_active', true)->inClassOrder()->get();

        $fsSections = $this->submissionStandardId
            ? Section::where('organization_id', $orgId)
                ->where('standard_id', $this->submissionStandardId)
                ->where('is_active', true)->orderBy('id')->get()
            : collect();

        // Need either a class or a search term to list students.
        $term = trim((string) $this->submissionSearch);
        $fsStudents = ($this->submissionStandardId || $term !== '')
            ? StudentDetail::with(['user', 'standard', 'section'])
                ->where('organization_id', $orgId)
                ->when($this->submissionStandardId, fn ($q) => $q->where('standard_id', $this->submissionStandardId))
                ->when($this->submissionSectionId, fn ($q) => $q->where('section_id', $this->submissionSectionId))
                ->when($term !== '', function ($q) use ($term) {
                    $q->where(function ($w) use ($term) {
                        $w->where('full_name', 'like', "%{$term}%")
                          ->orWhere('father_name', 'like', "%{$term}%")
                          ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$term}%"));
                    });
                })
                ->tap(fn ($q) => NameOrder::students($q))
                ->limit(200)
                ->get()
            : collect();

        return [
            'fsStandards'  => $fsStandards,
            'fsSections'   => $fsSections,
            'fsStudents'   => $fsStudents,
            'feeTypeCaps'  => $this->feeTypeCaps(),
            // Where the amount typed in Collect Fee will go, fee by fee.
            'collectionSplit' => $this->showSubmitPanel && is_numeric($this->submitAmount) && (float) $this->submitAmount > 0
                ? $this->feeCollectionSplit((string) $this->submitFeeType, (float) $this->submitAmount)
                : [],
            // The date fields' `min` — Collect Fee and the payment date edit.
            'feeMinDate'   => $this->feeMinDate(),
        ];
    }
}
