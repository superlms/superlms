<?php

namespace App\Livewire\Concerns;

use App\Models\Admin\Fee\FeeConcession;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use Illuminate\Support\Facades\Auth;

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
            ->get()->toArray();

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
        $discount = 0.0;
        $concessionRows = [];
        foreach ($this->studentConcessions as $c) {
            $amt = $c['concession_type'] === 'percent'
                ? round($totalStructure * ((float) $c['value']) / 100, 2)
                : min((float) $c['value'], $totalStructure);
            $discount += $amt;
            $concessionRows[] = [
                'id'             => null,
                'receipt_number' => 'CONCESSION',
                'amount'         => $amt,
                'fee_type'       => $c['fee_type'] === 'all' ? 'academic' : $c['fee_type'],
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
        $this->submitFeeType     = $caps['academic'] > 0 ? 'academic' : ($caps['transport'] > 0 ? 'transport' : ($caps['penalty'] > 0 ? 'penalty' : 'academic'));
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
            'submitFeeType'     => 'required|in:academic,transport,penalty',
            'submitPaymentMode' => 'required|in:cash,online,cheque,bank_transfer',
            'submitDate'        => 'required|date',
            'submittedBy'       => 'required|string|max:255',
        ]);

        // Can't collect more than what's actually due — the remaining
        // academic/transport balance for the year, or the total penalty
        // currently accrued (net of any waiver/payment already applied).
        $cap = $this->submitFeeTypeCap();
        if ($cap <= 0) {
            $this->notification()->error(
                $this->submitFeeType === 'penalty' ? 'No penalty due' : 'Nothing due',
                $this->submitFeeType === 'penalty'
                    ? 'This student has no penalty currently due — there is nothing to submit.'
                    : ucfirst($this->submitFeeType) . ' fee for this student is already fully paid for the year.'
            );
            return;
        }
        if ((float) $this->submitAmount > $cap + 0.01) {
            $this->notification()->error(
                'Amount too high',
                'Only up to ₹' . number_format($cap, 2) . ' can be submitted — that is what is currently due for ' . $this->submitFeeType . '.'
            );
            return;
        }

        try {
            $student = StudentDetail::find($this->selectedStudentId);

            FeePayment::create([
                'organization_id'   => $this->orgId(),
                'student_detail_id' => $this->selectedStudentId,
                'standard_id'       => $student->standard_id,
                'section_id'        => $student->section_id,
                'fee_type'          => $this->submitFeeType,
                'amount'            => $this->submitAmount,
                'payment_mode'      => $this->submitPaymentMode,
                'payment_date'      => $this->submitDate,
                'remark'            => $this->submitRemark,
                'submitted_by'      => $this->submittedBy,
            ]);

            $this->notification()->success('Fee submitted successfully!');
            $this->reset(['submitAmount', 'submitFeeType', 'submitPaymentMode', 'submitRemark', 'submittedBy']);
            $this->submitDate = today()->toDateString();
            $this->showSubmitPanel = false;

            // Refresh transactions + net payable
            $this->updatedSelectedStudentId();
        } catch (\Exception $e) {
            $this->notification()->error('Error submitting fee', $e->getMessage());
        }
    }

    /** How much can currently be submitted for the selected fee type. */
    private function submitFeeTypeCap(): float
    {
        return $this->feeTypeCaps()[$this->submitFeeType] ?? 0.0;
    }

    /**
     * Per fee type, how much can currently be submitted — the remaining
     * academic/transport balance for the year, or the total penalty still
     * due (net of any waiver/payment). Both read straight off
     * $submissionLedger so they match what's on screen, and the Collect Fee
     * panel uses this to grey out Penalties when nothing is due and to hint
     * the cap under the Amount field.
     */
    protected function feeTypeCaps(): array
    {
        return [
            'academic'  => round((float) ($this->submissionLedger['academic']['remaining'] ?? 0), 2),
            'transport' => round((float) ($this->submissionLedger['transport']['remaining'] ?? 0), 2),
            'penalty'   => round(collect($this->submissionLedger['cycles'] ?? [])->sum('penalty_net'), 2),
        ];
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
            ->where('is_active', true)->orderBy('id')->get();

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
                ->orderBy('roll_no')
                ->limit(200)
                ->get()
            : collect();

        return [
            'fsStandards'  => $fsStandards,
            'fsSections'   => $fsSections,
            'fsStudents'   => $fsStudents,
            'feeTypeCaps'  => $this->feeTypeCaps(),
        ];
    }
}
