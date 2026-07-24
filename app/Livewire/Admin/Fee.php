<?php

namespace App\Livewire\Admin;

use App\Models\Admin\Fee\FeeConcession;
use App\Models\Admin\Fee\FeeCycle;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeSettings;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class Fee extends Component
{
    use WireUiActions, WithPagination;

    public string $activeTab = ''; // '' = card menu (landing); otherwise the open tab

    // ─── Fee Structure ─────────────────────────────────────────────────────────
    public $structureStandardId = '';
    public $structureSectionId  = '';
    public $feeName             = '';
    public $feeAmount           = '';
    public $structureFeeType    = 'academic';
    public $academicYear        = '';
    public $editStructureId     = null;
    public $structureModalOpen  = false;

    // Filters for structure list
    public $filterStructureStandard  = '';
    public $filterStructureSection   = '';
    public $filterStructureYear      = '';

    // ─── Fee Submission ────────────────────────────────────────────────────────
    public $submissionStandardId = '';
    public $submissionSectionId  = '';
    public $selectedStudentId    = '';
    public $classStructures      = [];
    public $studentTransactions  = [];

    // Payment form
    public $submitAmount      = '';
    public $submitFeeType     = 'academic';
    public $submitPaymentMode = 'cash';
    public $submitDate        = '';
    public $submitRemark      = '';
    public $submittedBy       = '';

    // Submission search + slide-in update panel
    public $submissionSearch  = '';
    public bool $showSubmitPanel = false;
    public array $selectedStudentInfo = [];
    public $studentConcessions = [];
    public float $netPayable    = 0.0;

    // ─── Concession (per-student fee discount) ──────────────────────────────────
    public $concFilterStandard = '';
    public $concFilterSection  = '';
    public $concStudentId      = '';
    public $concType           = 'amount'; // amount | percent
    public $concValue          = '';
    public $concFeeType        = 'all';    // academic | transport | all
    public $concReason         = '';
    public $concYear           = '2026-27';
    public $editConcessionId   = null;
    public bool $concModalOpen = false;
    public ?int $pendingDeleteConcessionId = null;
    public $concStudents       = [];

    // ─── View Fee ─────────────────────────────────────────────────────────────
    public string $viewSubTab          = 'by_student';
    public $viewStudentStandardId      = '';
    public $viewStudentSectionId       = '';
    public $viewStudentId              = '';
    public $studentFeeView             = [];

    public $viewClassStandardId        = '';
    public $viewClassSectionId         = '';
    public $classFeeList               = [];

    // ─── Analytics ────────────────────────────────────────────────────────────
    public $analyticsStandardId  = '';
    public $analyticsSectionId   = '';
    public $analyticsData        = [];
    public $analyticsStudentList = [];
    public $analyticsDaily         = [];   // last 14 days collection series
    public $analyticsModeBreakdown = [];   // cash / online / cheque / bank_transfer
    public $analyticsPeriodStats   = [];   // today / week / month totals & counts
    public $analyticsRecentPayments = [];  // latest payments feed

    // ─── Payments ─────────────────────────────────────────────────────────────
    public $paymentModeFilter    = '';
    public $paymentStandardId    = '';
    public $paymentSectionId     = '';
    public $paymentStudentId     = '';
    public $paymentDateFrom      = '';
    public $paymentDateTo        = '';
    public $paymentStudents      = [];
    public $paymentPeriodStats   = [];
    public float $paymentFilteredTotal = 0.0;

    // ─── Penalties (per-student) ────────────────────────────────────────────────
    public $penaltyPerDay    = '0';
    public $cycleType        = 'monthly';
    public $dueDayOfMonth    = '10';

    public $penaltyFilterStandard = '';
    public $penaltyFilterSection  = '';
    public $penaltyStudentId      = '';
    public $penaltyStudents       = [];
    public array $penaltyStudentInfo  = [];
    public array $penaltyStructures   = [];
    public array $penaltyPayments     = [];
    public array $penaltyWaivers      = [];
    public float $penaltyGross        = 0.0;
    public float $penaltyWaivedTotal  = 0.0;
    public float $penaltyNet          = 0.0;
    public int   $penaltyDaysOverdue  = 0;
    // Waive-penalty form
    public $waiveValue  = '';
    public $waiveReason = '';

    // ─── Fee Cycle (installments) ───────────────────────────────────────────────
    // An installment is defined only by its % of the fee — the rupee amount is
    // computed per class from each class's own fee structure (see the calculator).
    public $cycleFeeType    = 'academic';
    public $cycleSerial     = 1;        // installment no. 1–8
    public $cycleDueDate     = '';
    public $cyclePenaltyPerDay = '0';
    public $cycleFeePercent  = '';
    public $cycleYear        = '2026-27';
    public $editCycleId      = null;
    public bool $cycleModalOpen = false;
    public ?int $pendingDeleteCycleId = null;
    // How the installments are generated when adding: '' (chooser) | monthly | quarterly | custom.
    public string $cycleMode = '';
    public $cycleMonthlyDueDay = 10;    // day-of-month each monthly installment is due

    // Installment calculator (per-class breakdown)
    public $calcStandardId = '';
    public $calcSectionId  = '';
    public $calcSerial     = '';   // optional: focus a single installment

    // ─── Account Users (nested component — filters proxied via events) ──────────
    public string $acctSearch = '';
    public string $acctStatus = '';

    // ─── Shared ───────────────────────────────────────────────────────────────
    public $search      = '';
    public $perPage     = 10;
    public $standards   = [];
    public $sections    = [];
    public $students    = [];

    protected $queryString = [
        'activeTab'  => ['except' => ''],
        'search'     => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->standards  = Standard::where('organization_id', $this->orgId())
            ->where('is_active', true)->orderBy('id')->get();
        $this->submitDate = today()->toDateString();
        $this->loadPenaltySettings();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────
    private function orgId(): int
    {
        return Auth::user()->organization_id;
    }

    /** Return to the card menu (landing). */
    public function backToMenu(): void
    {
        $this->activeTab = '';
        $this->resetPage();
        $this->search = '';
    }

    /** Fired by the embedded fee-structure component's Analytics button. */
    #[On('fee-open-analytics')]
    public function openAnalyticsTab(): void
    {
        $this->showTab('analytics');
    }

    // ─── Account Users proxy (button + filters live in the fee header) ──────────
    public function acctAdd(): void
    {
        $this->dispatch('acct-add')->to(AccountUsers::class);
    }

    public function updatedAcctSearch(): void
    {
        $this->dispatch('acct-filter', search: $this->acctSearch, status: $this->acctStatus)->to(AccountUsers::class);
    }

    public function updatedAcctStatus(): void
    {
        $this->dispatch('acct-filter', search: $this->acctSearch, status: $this->acctStatus)->to(AccountUsers::class);
    }

    public function acctClearFilters(): void
    {
        $this->acctSearch = '';
        $this->acctStatus = '';
        $this->dispatch('acct-filter', search: '', status: '')->to(AccountUsers::class);
    }

    public function showTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
        $this->search = '';
        // Account-users filters live in this header; reset them on every switch
        // so they match the freshly-mounted nested component.
        $this->acctSearch = '';
        $this->acctStatus = '';

        if ($tab === 'analytics') {
            $this->loadAnalytics();
        } elseif ($tab === 'payments') {
            $this->loadPaymentPeriodStats();
        } elseif ($tab === 'penalties') {
            $this->loadPenaltySettings();
        } elseif ($tab === 'cycle') {
            $this->loadPenaltySettings();
        }
    }

    // ─── Concession (per-student fee discount) ──────────────────────────────────

    public function updatedConcFilterStandard(): void
    {
        $this->concFilterSection = '';
        $this->concStudentId     = '';
        $this->sections = $this->concFilterStandard
            ? Section::where('standard_id', $this->concFilterStandard)->where('is_active', true)->get()
            : [];
        $this->loadConcessionStudents();
    }

    public function updatedConcFilterSection(): void
    {
        $this->concStudentId = '';
        $this->loadConcessionStudents();
    }

    private function loadConcessionStudents(): void
    {
        if (!$this->concFilterStandard) {
            $this->concStudents = [];
            return;
        }
        $this->concStudents = StudentDetail::with('user')
            ->where('organization_id', $this->orgId())
            ->where('standard_id', $this->concFilterStandard)
            ->when($this->concFilterSection, fn ($q) => $q->where('section_id', $this->concFilterSection))
            ->orderBy('roll_no')->get();
    }

    public function openConcessionModal(?int $id = null): void
    {
        $this->resetConcessionForm();
        $this->editConcessionId = $id;

        if ($id) {
            $c = FeeConcession::where('organization_id', $this->orgId())->find($id);
            if (!$c) return;
            $this->concFilterStandard = $c->standard_id;
            $this->loadConcessionStudents();
            $this->concFilterSection = $c->section_id;
            $this->concStudentId     = $c->student_detail_id;
            $this->concType          = $c->concession_type;
            $this->concValue         = $c->value;
            $this->concFeeType       = $c->fee_type;
            $this->concReason        = $c->reason;
            $this->concYear          = $c->academic_year;
        }
        $this->concModalOpen = true;
    }

    public function closeConcessionModal(): void
    {
        $this->concModalOpen = false;
        $this->resetConcessionForm();
    }

    private function resetConcessionForm(): void
    {
        $this->reset(['editConcessionId', 'concStudentId', 'concType', 'concValue', 'concReason']);
        $this->concType   = 'amount';
        $this->concFeeType = 'all';
        $this->concYear   = '2026-27';
        $this->resetValidation();
    }

    public function saveConcession(): void
    {
        $this->validate([
            'concStudentId' => 'required|exists:student_details,id',
            'concType'      => 'required|in:amount,percent',
            'concValue'     => 'required|numeric|min:0.01' . ($this->concType === 'percent' ? '|max:100' : ''),
            'concFeeType'   => 'required|in:academic,transport,all',
            'concReason'    => 'nullable|string|max:255',
            'concYear'      => 'required|string|max:20',
        ]);

        $student = StudentDetail::find($this->concStudentId);

        $payload = [
            'organization_id'   => $this->orgId(),
            'student_detail_id' => $this->concStudentId,
            'standard_id'       => $student->standard_id,
            'section_id'        => $student->section_id,
            'concession_type'   => $this->concType,
            'value'             => $this->concValue,
            'fee_type'          => $this->concFeeType,
            'reason'            => $this->concReason,
            'academic_year'     => $this->concYear,
            'created_by'        => Auth::id(),
        ];

        if ($this->editConcessionId) {
            FeeConcession::where('organization_id', $this->orgId())
                ->where('id', $this->editConcessionId)->update($payload);
            $this->notification()->success('Concession updated successfully!');
        } else {
            FeeConcession::create($payload);
            $this->notification()->success('Concession added successfully!');
        }

        $this->closeConcessionModal();
    }

    public function deleteConcession(int $id): void { $this->pendingDeleteConcessionId = $id; }
    public function cancelDeleteConcession(): void { $this->pendingDeleteConcessionId = null; }
    public function doDeleteConcession(): void
    {
        FeeConcession::where('organization_id', $this->orgId())
            ->where('id', $this->pendingDeleteConcessionId)->delete();
        $this->pendingDeleteConcessionId = null;
        $this->notification()->success('Concession deleted!');
    }

    // ─── Fee Structure ─────────────────────────────────────────────────────────

    public function openStructureModal(int $id = null): void
    {
        $this->resetStructureForm();
        $this->editStructureId   = $id;
        $this->structureModalOpen = true;

        if ($id) {
            $s = FeeStructure::find($id);
            $this->structureStandardId = $s->standard_id;
            $this->structureSectionId  = $s->section_id;
            $this->feeName             = $s->fee_name;
            $this->feeAmount           = $s->amount;
            $this->structureFeeType    = $s->fee_type;
            $this->academicYear        = $s->academic_year;
        }
    }

    public function saveStructure(): void
    {
        $this->validate([
            'structureStandardId' => 'required|exists:standards,id',
            'feeName'             => 'required|string|max:255',
            'feeAmount'           => 'required|numeric|min:0',
            'structureFeeType'    => 'required|in:academic,transport',
            'academicYear'        => 'required|string|max:20',
        ]);

        $data = [
            'organization_id' => $this->orgId(),
            'standard_id'     => $this->structureStandardId,
            'section_id'      => $this->structureSectionId ?: null,
            'fee_name'        => $this->feeName,
            'amount'          => $this->feeAmount,
            'fee_type'        => $this->structureFeeType,
            'academic_year'   => $this->academicYear,
            'is_active'       => true,
        ];

        try {
            if ($this->editStructureId) {
                FeeStructure::find($this->editStructureId)->update($data);
                $this->notification()->success('Fee structure updated successfully!');
            } else {
                FeeStructure::create($data);
                $this->notification()->success('Fee structure added successfully!');
            }
            $this->resetStructureForm();
        } catch (\Exception $e) {
            $this->notification()->error('Error', $e->getMessage());
        }
    }

    public function deleteStructure(int $id): void
    {
        $this->dialog()->confirm([
            'title'       => 'Delete Fee Structure?',
            'description' => 'This action cannot be undone.',
            'icon'        => 'error',
            'accept'      => ['label' => 'Yes, delete', 'method' => 'doDeleteStructure', 'params' => $id],
            'reject'      => ['label' => 'Cancel'],
        ]);
    }

    public function doDeleteStructure(int $id): void
    {
        FeeStructure::find($id)?->delete();
        $this->notification()->success('Fee structure deleted!');
    }

    private function resetStructureForm(): void
    {
        $this->reset([
            'editStructureId', 'structureModalOpen',
            'structureStandardId', 'structureSectionId',
            'feeName', 'feeAmount', 'structureFeeType', 'academicYear',
        ]);
    }

    public function updatedFilterStructureStandard(): void
    {
        $this->filterStructureSection = '';
        $this->sections = $this->filterStructureStandard
            ? Section::where('standard_id', $this->filterStructureStandard)->where('is_active', true)->get()
            : [];
        $this->resetPage();
    }

    // ─── Fee Submission ────────────────────────────────────────────────────────

    public function updatedSubmissionStandardId(): void
    {
        $this->submissionSectionId = '';
        $this->selectedStudentId   = '';
        $this->classStructures     = [];
        $this->studentTransactions = [];
        $this->students            = [];

        if ($this->submissionStandardId) {
            $this->sections = Section::where('standard_id', $this->submissionStandardId)
                ->where('is_active', true)->get();
            $this->loadSubmissionStudents();
        }
    }

    public function updatedSubmissionSectionId(): void
    {
        $this->selectedStudentId   = '';
        $this->classStructures     = [];
        $this->studentTransactions = [];
        $this->loadSubmissionStudents();
    }

    private function loadSubmissionStudents(): void
    {
        // Need either a class or a search term to list students.
        if (!$this->submissionStandardId && !trim((string) $this->submissionSearch)) {
            $this->students = [];
            return;
        }

        $term = trim((string) $this->submissionSearch);

        $this->students = StudentDetail::with(['user', 'standard', 'section'])
            ->where('organization_id', $this->orgId())
            ->when($this->submissionStandardId, fn($q) => $q->where('standard_id', $this->submissionStandardId))
            ->when($this->submissionSectionId, fn($q) => $q->where('section_id', $this->submissionSectionId))
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($w) use ($term) {
                    $w->where('full_name', 'like', "%{$term}%")
                      ->orWhere('father_name', 'like', "%{$term}%")
                      ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$term}%"));
                });
            })
            ->orderBy('roll_no')
            ->limit(200)
            ->get();
    }

    /** Search button — list students by student/father name (class optional). */
    public function searchSubmissionStudents(): void
    {
        $this->selectedStudentId   = '';
        $this->classStructures     = [];
        $this->studentTransactions = [];
        $this->loadSubmissionStudents();
    }

    public function updatedSelectedStudentId(): void
    {
        if (!$this->selectedStudentId) {
            $this->classStructures     = [];
            $this->studentTransactions = [];
            $this->selectedStudentInfo = [];
            $this->studentConcessions  = [];
            $this->netPayable          = 0.0;
            return;
        }

        $student = StudentDetail::with(['user', 'standard', 'section'])->find($this->selectedStudentId);
        if (!$student) return;

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
        $this->classStructures = FeeStructure::where('organization_id', $this->orgId())
            ->where('standard_id', $student->standard_id)
            ->where(function ($q) use ($student) {
                $q->where('section_id', $student->section_id)->orWhereNull('section_id');
            })
            ->where('is_active', true)
            ->get()->toArray();

        // Concessions for this student
        $this->studentConcessions = FeeConcession::where('organization_id', $this->orgId())
            ->where('student_detail_id', $this->selectedStudentId)
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
            ->where('organization_id', $this->orgId())
            ->where('student_detail_id', $this->selectedStudentId)
            ->orderByDesc('payment_date')
            ->get()->toArray();

        $paid = collect($realTransactions)->sum(fn ($t) => (float) $t['amount']);
        $this->netPayable = max(0, round($totalStructure - $discount - $paid, 2));

        // Ledger = real payments + concessions shown as concession-type entries.
        $this->studentTransactions = array_merge($realTransactions, $concessionRows);
    }

    public function openSubmitPanel(): void
    {
        if (!$this->selectedStudentId) {
            $this->notification()->error('Select a student first.');
            return;
        }
        $this->submitAmount      = '';
        $this->submitFeeType     = 'academic';
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
            'submitFeeType'     => 'required|in:academic,transport',
            'submitPaymentMode' => 'required|in:cash,online,cheque,bank_transfer',
            'submitDate'        => 'required|date',
            'submittedBy'       => 'required|string|max:255',
        ]);

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

    // ─── View Fee ─────────────────────────────────────────────────────────────

    public function setViewSubTab(string $tab): void
    {
        $this->viewSubTab = $tab;
    }

    public function updatedViewStudentStandardId(): void
    {
        $this->viewStudentSectionId = '';
        $this->viewStudentId        = '';
        $this->studentFeeView       = [];
        $this->sections = $this->viewStudentStandardId
            ? Section::where('standard_id', $this->viewStudentStandardId)->where('is_active', true)->get()
            : [];
        // Load the class's students right away so the Student picker is usable
        // even before a section is chosen (section is optional).
        $this->students = $this->viewStudentStandardId
            ? StudentDetail::with('user')
                ->where('organization_id', $this->orgId())
                ->where('standard_id', $this->viewStudentStandardId)
                ->get()
            : [];
    }

    public function updatedViewStudentSectionId(): void
    {
        $this->viewStudentId  = '';
        $this->studentFeeView = [];
        if ($this->viewStudentStandardId) {
            $this->students = StudentDetail::with('user')
                ->where('organization_id', $this->orgId())
                ->where('standard_id', $this->viewStudentStandardId)
                ->when($this->viewStudentSectionId, fn($q) => $q->where('section_id', $this->viewStudentSectionId))
                ->get();
        }
    }

    public function updatedViewStudentId(): void
    {
        // Auto-load the ledger the moment a student is picked.
        $this->studentFeeView = [];
        if ($this->viewStudentId) {
            $this->loadStudentFeeView();
        }
    }

    public function loadStudentFeeView(): void
    {
        if (!$this->viewStudentId) return;

        $student = StudentDetail::with(['standard', 'section', 'user'])->find($this->viewStudentId);
        if (!$student) return;

        $structures = FeeStructure::where('organization_id', $this->orgId())
            ->where('standard_id', $student->standard_id)
            ->where(function ($q) use ($student) {
                $q->where('section_id', $student->section_id)->orWhereNull('section_id');
            })
            ->where('is_active', true)
            ->get();

        $payments = FeePayment::where('organization_id', $this->orgId())
            ->where('student_detail_id', $this->viewStudentId)
            ->orderByDesc('payment_date')
            ->get();

        $academicTotal    = $structures->where('fee_type', 'academic')->sum('amount');
        $transportTotal   = $student->transportation_required
            ? $structures->where('fee_type', 'transport')->sum('amount')
            : 0;
        $academicPaid     = $payments->where('fee_type', 'academic')->sum('amount');
        $transportPaid    = $payments->where('fee_type', 'transport')->sum('amount');
        $totalFee         = $academicTotal + $transportTotal;
        $totalPaid        = $academicPaid + $transportPaid;

        $this->studentFeeView = [
            'student'          => $student,
            'structures'       => $structures,
            'payments'         => $payments,
            'academicTotal'    => $academicTotal,
            'transportTotal'   => $transportTotal,
            'totalFee'         => $totalFee,
            'academicPaid'     => $academicPaid,
            'transportPaid'    => $transportPaid,
            'totalPaid'        => $totalPaid,
            'remaining'        => max(0, $totalFee - $totalPaid),
            'hasTransport'     => (bool) $student->transportation_required,
        ];
    }

    public function updatedViewClassStandardId(): void
    {
        $this->viewClassSectionId = '';
        $this->classFeeList       = [];
        $this->sections = $this->viewClassStandardId
            ? Section::where('standard_id', $this->viewClassStandardId)->where('is_active', true)->get()
            : [];
    }

    public function loadClassFeeView(): void
    {
        if (!$this->viewClassStandardId) return;

        $students = StudentDetail::with(['user', 'standard', 'section'])
            ->where('organization_id', $this->orgId())
            ->where('standard_id', $this->viewClassStandardId)
            ->when($this->viewClassSectionId, fn($q) => $q->where('section_id', $this->viewClassSectionId))
            ->get();

        $structures = FeeStructure::where('organization_id', $this->orgId())
            ->where('standard_id', $this->viewClassStandardId)
            ->where('is_active', true)
            ->get();

        $this->classFeeList = $students->map(function ($student) use ($structures) {
            $studentStructures = $structures->filter(function ($s) use ($student) {
                return is_null($s->section_id) || $s->section_id == $student->section_id;
            });

            $academicFee   = $studentStructures->where('fee_type', 'academic')->sum('amount');
            $transportFee  = $student->transportation_required
                ? $studentStructures->where('fee_type', 'transport')->sum('amount')
                : 0;

            $collected = FeePayment::where('organization_id', $this->orgId())
                ->where('student_detail_id', $student->id)
                ->sum('amount');

            return [
                'id'           => $student->id,
                'name'         => $student->user->name ?? '-',
                'admission_no' => $student->admission_no,
                'class'        => $student->standard->name ?? '-',
                'section'      => $student->section->name ?? '-',
                'academicFee'  => $academicFee,
                'transportFee' => $transportFee,
                'totalFee'     => $academicFee + $transportFee,
                'collected'    => $collected,
            ];
        })->values()->toArray();
    }

    // ─── Analytics ────────────────────────────────────────────────────────────

    public function updatedAnalyticsStandardId(): void
    {
        $this->analyticsSectionId = '';
        $this->sections = $this->analyticsStandardId
            ? Section::where('standard_id', $this->analyticsStandardId)->where('is_active', true)->get()
            : [];
        $this->loadAnalytics();
    }

    public function updatedAnalyticsSectionId(): void
    {
        $this->loadAnalytics();
    }

    public function loadAnalytics(): void
    {
        $orgId = $this->orgId();

        $structureQuery = FeeStructure::where('organization_id', $orgId)->where('is_active', true);
        $paymentQuery   = FeePayment::where('organization_id', $orgId);

        if ($this->analyticsStandardId) {
            $structureQuery->where('standard_id', $this->analyticsStandardId);
            $paymentQuery->where('standard_id', $this->analyticsStandardId);
        }
        if ($this->analyticsSectionId) {
            $structureQuery->where(function ($q) {
                $q->where('section_id', $this->analyticsSectionId)->orWhereNull('section_id');
            });
            $paymentQuery->where('section_id', $this->analyticsSectionId);
        }

        $academicTotal   = (clone $structureQuery)->where('fee_type', 'academic')->sum('amount');
        $transportTotal  = (clone $structureQuery)->where('fee_type', 'transport')->sum('amount');
        $totalCollected  = (clone $paymentQuery)->sum('amount');
        $academicPaid    = (clone $paymentQuery)->where('fee_type', 'academic')->sum('amount');
        $transportPaid   = (clone $paymentQuery)->where('fee_type', 'transport')->sum('amount');

        $this->analyticsData = [
            'totalFee'       => $academicTotal + $transportTotal,
            'academicTotal'  => $academicTotal,
            'transportTotal' => $transportTotal,
            'collected'      => $totalCollected,
            'academicPaid'   => $academicPaid,
            'transportPaid'  => $transportPaid,
            'remaining'      => max(0, ($academicTotal + $transportTotal) - $totalCollected),
        ];

        // Student list
        $studentQuery = StudentDetail::with(['user', 'standard', 'section'])
            ->where('organization_id', $orgId);
        if ($this->analyticsStandardId) {
            $studentQuery->where('standard_id', $this->analyticsStandardId);
        }
        if ($this->analyticsSectionId) {
            $studentQuery->where('section_id', $this->analyticsSectionId);
        }

        $structures = FeeStructure::where('organization_id', $orgId)->where('is_active', true)
            ->when($this->analyticsStandardId, fn($q) => $q->where('standard_id', $this->analyticsStandardId))
            ->get();

        $this->analyticsStudentList = $studentQuery->get()->map(function ($student) use ($structures, $orgId) {
            $studentStructures = $structures->filter(function ($s) use ($student) {
                return $s->standard_id == $student->standard_id &&
                    (is_null($s->section_id) || $s->section_id == $student->section_id);
            });

            $academicFee  = $studentStructures->where('fee_type', 'academic')->sum('amount');
            $transportFee = $student->transportation_required
                ? $studentStructures->where('fee_type', 'transport')->sum('amount')
                : 0;
            $collected    = FeePayment::where('organization_id', $orgId)
                ->where('student_detail_id', $student->id)->sum('amount');

            return [
                'id'           => $student->id,
                'name'         => $student->user->name ?? '-',
                'admission_no' => $student->admission_no,
                'class'        => $student->standard->name ?? '-',
                'section'      => $student->section->name ?? '-',
                'totalFee'     => $academicFee + $transportFee,
                'collected'    => $collected,
            ];
        })->values()->toArray();

        $this->loadAnalyticsDaily();
    }

    /**
     * Daily collection series (last 14 days), payment-mode split, period
     * totals and a recent-payments feed — powers the redesigned analytics
     * dashboard so admins can watch day-by-day cash flow.
     */
    private function loadAnalyticsDaily(): void
    {
        $orgId = $this->orgId();

        // Fresh base query honouring the class/section filters.
        $base = fn () => FeePayment::where('organization_id', $orgId)
            ->when($this->analyticsStandardId, fn ($q) => $q->where('standard_id', $this->analyticsStandardId))
            ->when($this->analyticsSectionId, fn ($q) => $q->where('section_id', $this->analyticsSectionId));

        // ── Last 14 days series ──
        $labels = $amounts = $counts = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = today()->subDays($i);
            $labels[]  = $day->format('d M');
            $amounts[] = (float) $base()->whereDate('payment_date', $day)->sum('amount');
            $counts[]  = (int)   $base()->whereDate('payment_date', $day)->count();
        }
        $this->analyticsDaily = ['labels' => $labels, 'amounts' => $amounts, 'counts' => $counts];

        // ── Payment-mode breakdown (this financial context = all matching) ──
        $modes = ['cash', 'online', 'cheque', 'bank_transfer'];
        $this->analyticsModeBreakdown = [];
        foreach ($modes as $mode) {
            $this->analyticsModeBreakdown[$mode] = (float) $base()->where('payment_mode', $mode)->sum('amount');
        }

        // ── Period stats ──
        $today = today();
        $this->analyticsPeriodStats = [
            'today_amt'   => (float) $base()->whereDate('payment_date', $today)->sum('amount'),
            'today_cnt'   => (int)   $base()->whereDate('payment_date', $today)->count(),
            'week_amt'    => (float) $base()->whereBetween('payment_date', [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()])->sum('amount'),
            'month_amt'   => (float) $base()->whereMonth('payment_date', $today->month)->whereYear('payment_date', $today->year)->sum('amount'),
            'month_cnt'   => (int)   $base()->whereMonth('payment_date', $today->month)->whereYear('payment_date', $today->year)->count(),
            'avg_txn'     => (float) $base()->avg('amount'),
        ];

        // ── Recent payments feed ──
        $this->analyticsRecentPayments = $base()
            ->with(['studentDetail.user', 'standard', 'section'])
            ->orderByDesc('payment_date')->orderByDesc('id')
            ->take(8)->get()
            ->map(fn ($p) => [
                'name'    => $p->studentDetail->user->name ?? '-',
                'class'   => trim(($p->standard->name ?? '') . ($p->section ? ' / ' . $p->section->name : '')) ?: '-',
                'amount'  => (float) $p->amount,
                'mode'    => str_replace('_', ' ', $p->payment_mode),
                'date'    => \Carbon\Carbon::parse($p->payment_date)->format('d M Y'),
                'receipt' => $p->receipt_number,
            ])->toArray();
    }

    // ─── Payments ─────────────────────────────────────────────────────────────

    public function updatedPaymentStandardId(): void
    {
        $this->paymentSectionId = '';
        $this->paymentStudentId = '';
        $this->sections = $this->paymentStandardId
            ? Section::where('standard_id', $this->paymentStandardId)->where('is_active', true)->get()
            : [];
        $this->loadPaymentStudents();
        $this->loadPaymentPeriodStats();
        $this->resetPage();
    }

    public function updatedPaymentSectionId(): void
    {
        $this->paymentStudentId = '';
        $this->loadPaymentStudents();
        $this->loadPaymentPeriodStats();
        $this->resetPage();
    }

    public function updatedPaymentStudentId(): void
    {
        $this->loadPaymentPeriodStats();
        $this->resetPage();
    }

    public function updatedPaymentModeFilter(): void
    {
        $this->loadPaymentPeriodStats();
        $this->resetPage();
    }

    public function updatedPaymentDateFrom(): void
    {
        $this->loadPaymentPeriodStats();
        $this->resetPage();
    }

    public function updatedPaymentDateTo(): void
    {
        $this->loadPaymentPeriodStats();
        $this->resetPage();
    }

    public function clearPaymentFilters(): void
    {
        $this->reset(['paymentStandardId', 'paymentSectionId', 'paymentStudentId', 'paymentModeFilter', 'paymentDateFrom', 'paymentDateTo', 'search']);
        $this->paymentStudents = [];
        $this->loadPaymentPeriodStats();
        $this->resetPage();
    }

    private function loadPaymentStudents(): void
    {
        if (!$this->paymentStandardId) {
            $this->paymentStudents = [];
            return;
        }
        $this->paymentStudents = StudentDetail::with('user')
            ->where('organization_id', $this->orgId())
            ->where('standard_id', $this->paymentStandardId)
            ->when($this->paymentSectionId, fn($q) => $q->where('section_id', $this->paymentSectionId))
            ->orderBy('roll_no')->get();
    }

    public function loadPaymentPeriodStats(): void
    {
        $orgId = $this->orgId();
        $base  = FeePayment::where('organization_id', $orgId)
            ->when($this->paymentStandardId, fn($q) => $q->where('standard_id', $this->paymentStandardId))
            ->when($this->paymentSectionId, fn($q) => $q->where('section_id', $this->paymentSectionId));

        $today     = today();
        $yesterday = today()->subDay();

        $this->paymentPeriodStats = [
            'today'      => (clone $base)->whereDate('payment_date', $today)->sum('amount'),
            'yesterday'  => (clone $base)->whereDate('payment_date', $yesterday)->sum('amount'),
            'this_week'  => (clone $base)->whereBetween('payment_date', [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()])->sum('amount'),
            'this_month' => (clone $base)->whereMonth('payment_date', $today->month)->whereYear('payment_date', $today->year)->sum('amount'),
            'last_month' => (clone $base)->whereMonth('payment_date', $today->copy()->subMonth()->month)->whereYear('payment_date', $today->copy()->subMonth()->year)->sum('amount'),
        ];

        // Total matching the currently applied filters (student / date / mode / search).
        $this->paymentFilteredTotal = (float) $this->getPaymentsQuery()->sum('amount');
    }

    private function getPaymentsQuery()
    {
        return FeePayment::with(['studentDetail.user', 'standard', 'section'])
            ->where('organization_id', $this->orgId())
            ->when($this->paymentStandardId, fn($q) => $q->where('standard_id', $this->paymentStandardId))
            ->when($this->paymentSectionId, fn($q) => $q->where('section_id', $this->paymentSectionId))
            ->when($this->paymentStudentId, fn($q) => $q->where('student_detail_id', $this->paymentStudentId))
            ->when($this->paymentModeFilter, fn($q) => $q->where('payment_mode', $this->paymentModeFilter))
            ->when($this->paymentDateFrom, fn($q) => $q->whereDate('payment_date', '>=', $this->paymentDateFrom))
            ->when($this->paymentDateTo, fn($q) => $q->whereDate('payment_date', '<=', $this->paymentDateTo))
            ->when($this->search, fn($q) => $q->whereHas('studentDetail.user', fn($q) => $q->where('name', 'like', "%{$this->search}%")));
    }

    // ─── Penalties ────────────────────────────────────────────────────────────

    public function loadPenaltySettings(): void
    {
        $settings            = FeeSettings::getForOrg($this->orgId());
        $this->penaltyPerDay  = $settings->penalty_per_day;
        $this->cycleType      = $settings->cycle_type;
        $this->dueDayOfMonth  = $settings->due_day_of_month;
    }

    public function saveSettings(): void
    {
        $this->validate([
            'penaltyPerDay'   => 'required|numeric|min:0',
            'cycleType'       => 'required|in:monthly,quarterly',
            'dueDayOfMonth'   => 'required|integer|min:1|max:31',
        ]);

        FeeSettings::updateOrCreate(
            ['organization_id' => $this->orgId()],
            [
                'penalty_per_day'  => $this->penaltyPerDay,
                'cycle_type'       => $this->cycleType,
                'due_day_of_month' => $this->dueDayOfMonth,
                'is_active'        => true,
            ]
        );

        $this->notification()->success('Fee settings saved successfully!');
        if ($this->penaltyStudentId) {
            $this->loadPenaltyForStudent();
        }
    }

    // ── Penalty: student filter & per-student view ──────────────────────────────

    public function updatedPenaltyFilterStandard(): void
    {
        $this->penaltyFilterSection = '';
        $this->penaltyStudentId     = '';
        $this->resetPenaltyView();
        $this->sections = $this->penaltyFilterStandard
            ? Section::where('standard_id', $this->penaltyFilterStandard)->where('is_active', true)->get()
            : [];
        $this->loadPenaltyStudents();
    }

    public function updatedPenaltyFilterSection(): void
    {
        $this->penaltyStudentId = '';
        $this->resetPenaltyView();
        $this->loadPenaltyStudents();
    }

    public function updatedPenaltyStudentId(): void
    {
        $this->resetPenaltyView();
        if ($this->penaltyStudentId) {
            $this->loadPenaltyForStudent();
        }
    }

    private function loadPenaltyStudents(): void
    {
        if (!$this->penaltyFilterStandard) {
            $this->penaltyStudents = [];
            return;
        }
        $this->penaltyStudents = StudentDetail::with('user')
            ->where('organization_id', $this->orgId())
            ->where('standard_id', $this->penaltyFilterStandard)
            ->when($this->penaltyFilterSection, fn($q) => $q->where('section_id', $this->penaltyFilterSection))
            ->orderBy('roll_no')->get();
    }

    private function resetPenaltyView(): void
    {
        $this->penaltyStudentInfo = [];
        $this->penaltyStructures  = [];
        $this->penaltyPayments    = [];
        $this->penaltyWaivers     = [];
        $this->penaltyGross       = 0.0;
        $this->penaltyWaivedTotal = 0.0;
        $this->penaltyNet         = 0.0;
        $this->penaltyDaysOverdue = 0;
        $this->waiveValue         = '';
        $this->waiveReason        = '';
    }

    public function loadPenaltyForStudent(): void
    {
        if (!$this->penaltyStudentId) return;

        $orgId   = $this->orgId();
        $student = StudentDetail::with(['user', 'standard', 'section'])->find($this->penaltyStudentId);
        if (!$student) return;

        $this->penaltyStudentInfo = [
            'name'         => $student->full_name ?? ($student->user->name ?? '—'),
            'father_name'  => $student->father_name ?? '—',
            'admission_no' => $student->admission_no ?? '—',
            'class'        => $student->standard->name ?? '—',
            'section'      => $student->section->name ?? '—',
        ];

        // Fee structure for the student's class
        $this->penaltyStructures = FeeStructure::where('organization_id', $orgId)
            ->where('standard_id', $student->standard_id)
            ->where(fn($q) => $q->where('section_id', $student->section_id)->orWhereNull('section_id'))
            ->where('is_active', true)
            ->get()->toArray();

        // Payment history
        $this->penaltyPayments = FeePayment::where('organization_id', $orgId)
            ->where('student_detail_id', $this->penaltyStudentId)
            ->orderByDesc('payment_date')
            ->get()->toArray();

        // Estimate penalty: overdue days × per-day rate when no payment was made this month
        $settings = FeeSettings::getForOrg($orgId);
        $perDay   = (float) $settings->penalty_per_day;
        $dueDay   = (int) $settings->due_day_of_month;

        $today   = Carbon::today();
        $dueDate = Carbon::createFromDate($today->year, $today->month, min($dueDay, $today->daysInMonth));
        if ($today->day <= $dueDay) {
            $dueDate = $dueDate->subMonth();
        }

        $paidThisMonth = FeePayment::where('organization_id', $orgId)
            ->where('student_detail_id', $this->penaltyStudentId)
            ->whereMonth('payment_date', $today->month)
            ->whereYear('payment_date', $today->year)
            ->exists();

        $this->penaltyDaysOverdue = $paidThisMonth ? 0 : max(0, (int) $today->diffInDays($dueDate));
        $this->penaltyGross       = round($this->penaltyDaysOverdue * $perDay, 2);

        // Penalty waivers = concessions scoped to fee_type = 'penalty'
        $this->penaltyWaivers = FeeConcession::where('organization_id', $orgId)
            ->where('student_detail_id', $this->penaltyStudentId)
            ->where('fee_type', 'penalty')
            ->orderByDesc('created_at')
            ->get()->toArray();

        $this->penaltyWaivedTotal = collect($this->penaltyWaivers)->sum(function ($w) {
            return $w['concession_type'] === 'percent'
                ? round($this->penaltyGross * ((float) $w['value']) / 100, 2)
                : (float) $w['value'];
        });

        $this->penaltyNet = max(0, round($this->penaltyGross - $this->penaltyWaivedTotal, 2));
    }

    public function waivePenalty(): void
    {
        $this->validate([
            'penaltyStudentId' => 'required|exists:student_details,id',
            'waiveValue'       => 'required|numeric|min:0.01',
            'waiveReason'      => 'nullable|string|max:255',
        ]);

        $student = StudentDetail::find($this->penaltyStudentId);

        FeeConcession::create([
            'organization_id'   => $this->orgId(),
            'student_detail_id' => $this->penaltyStudentId,
            'standard_id'       => $student->standard_id,
            'section_id'        => $student->section_id,
            'concession_type'   => 'amount',
            'value'             => $this->waiveValue,
            'fee_type'          => 'penalty',
            'reason'            => $this->waiveReason ?: 'Penalty waiver',
            'academic_year'     => '2026-27',
            'created_by'        => Auth::id(),
        ]);

        $this->waiveValue  = '';
        $this->waiveReason = '';
        $this->notification()->success('Penalty waiver applied!');
        $this->loadPenaltyForStudent();
    }

    public function removeWaiver(int $id): void
    {
        FeeConcession::where('organization_id', $this->orgId())
            ->where('fee_type', 'penalty')
            ->where('id', $id)->delete();
        $this->notification()->success('Waiver removed.');
        $this->loadPenaltyForStudent();
    }

    // ── Fee Cycle (installments) ────────────────────────────────────────────────

    public function openCycleModal(?int $id = null): void
    {
        $this->resetCycleForm();
        $this->editCycleId = $id;

        if ($id) {
            $c = FeeCycle::forOrg($this->orgId())->find($id);
            if (!$c) return;
            // Editing an existing installment is always a single-row (custom) edit.
            $this->cycleMode          = 'custom';
            $this->cycleFeeType       = $c->fee_type;
            $this->cycleSerial        = $c->payment_serial;
            $this->cycleDueDate       = optional($c->due_date)->toDateString();
            $this->cyclePenaltyPerDay = $c->penalty_per_day;
            $this->cycleFeePercent    = $c->fee_percent;
            $this->cycleYear          = $c->academic_year;
        }
        $this->cycleModalOpen = true;
    }

    /** Pick how the cycle is built (monthly | quarterly | custom) from the chooser. */
    public function setCycleMode(string $mode): void
    {
        $this->cycleMode = in_array($mode, ['monthly', 'quarterly', 'custom'], true) ? $mode : '';
        $this->resetValidation();
    }

    public function closeCycleModal(): void
    {
        $this->cycleModalOpen = false;
        $this->resetCycleForm();
    }

    private function resetCycleForm(): void
    {
        $this->reset([
            'editCycleId', 'cycleSerial', 'cycleDueDate',
            'cyclePenaltyPerDay', 'cycleFeePercent',
        ]);
        $this->cycleMode          = '';
        $this->cycleFeeType       = 'academic';
        $this->cycleSerial        = 1;
        $this->cyclePenaltyPerDay = '0';
        $this->cycleMonthlyDueDay = 10;
        $this->cycleYear          = '2026-27';
        $this->resetValidation();
    }

    /** First 4-digit year in an academic-year string, e.g. "2026-27" → 2026. */
    private function cycleStartYear(): int
    {
        return (int) (preg_match('/\d{4}/', (string) $this->cycleYear, $m) ? $m[0] : now()->year);
    }

    public function saveCycle(): void
    {
        // Monthly / Quarterly auto-split (add mode only) — generate the whole set.
        if (!$this->editCycleId && $this->cycleMode === 'monthly') {
            $this->generateMonthlyCycles();
            return;
        }
        if (!$this->editCycleId && $this->cycleMode === 'quarterly') {
            $this->generateQuarterlyCycles();
            return;
        }

        // Custom (single installment) — add or edit.
        $this->validate([
            'cycleFeeType'    => 'required|string|max:20',
            'cycleSerial'     => 'required|integer|min:1|max:12',
            'cycleDueDate'    => 'required|date',
            'cycleFeePercent' => 'required|numeric|min:0|max:100',
            'cycleYear'       => 'required|string|max:20',
        ]);

        $payload = [
            'organization_id' => $this->orgId(),
            'fee_type'        => $this->cycleFeeType,
            'payment_serial'  => $this->cycleSerial,
            'start_date'      => null,
            'end_date'        => null,
            'due_date'        => $this->cycleDueDate,
            'penalty_per_day' => $this->cyclePenaltyPerDay ?: 0,
            'fee_percent'     => $this->cycleFeePercent,
            // Amount is computed per class from fee_percent × the class's own fee
            // (see FeeController::upcomingInstallments); no single stored amount.
            'amount'          => 0,
            'academic_year'   => $this->cycleYear,
            'is_active'       => true,
        ];

        if ($this->editCycleId) {
            FeeCycle::forOrg($this->orgId())->where('id', $this->editCycleId)->update($payload);
            $this->notification()->success('Installment updated!');
        } else {
            FeeCycle::create($payload);
            $this->notification()->success('Installment added!');
        }

        $this->closeCycleModal();
    }

    /**
     * Split the full fee into 12 equal monthly installments for the academic
     * year (April → March). Each is due on the chosen day-of-month. Replaces any
     * existing installments for this fee type + year.
     */
    private function generateMonthlyCycles(): void
    {
        $this->validate([
            'cycleFeeType'       => 'required|string|max:20',
            'cycleYear'          => 'required|string|max:20',
            'cycleMonthlyDueDay' => 'required|integer|min:1|max:28',
        ]);

        $startYear = $this->cycleStartYear();
        $dueDay    = (int) $this->cycleMonthlyDueDay;
        $per       = round(100 / 12, 2);

        $this->replaceCycles(function () use ($startYear, $dueDay, $per) {
            for ($i = 0; $i < 12; $i++) {
                // Academic year runs April(startYear) → March(startYear+1).
                $month = \Carbon\Carbon::create($startYear, 4, 1)->addMonths($i);
                $due   = $month->copy()->day(min($dueDay, $month->daysInMonth));
                // Load the rounding remainder onto the last installment so the set sums to 100%.
                $percent = $i === 11 ? round(100 - $per * 11, 2) : $per;

                FeeCycle::create([
                    'organization_id' => $this->orgId(),
                    'fee_type'        => $this->cycleFeeType,
                    'payment_serial'  => $i + 1,
                    'start_date'      => $month->copy()->startOfMonth()->toDateString(),
                    'end_date'        => $month->copy()->endOfMonth()->toDateString(),
                    'due_date'        => $due->toDateString(),
                    'penalty_per_day' => $this->cyclePenaltyPerDay ?: 0,
                    'fee_percent'     => $percent,
                    'amount'          => 0,
                    'academic_year'   => $this->cycleYear,
                    'is_active'       => true,
                ]);
            }
        });

        $this->notification()->success('12 monthly installments created!');
        $this->closeCycleModal();
    }

    /**
     * Split the full fee into 4 equal quarterly installments (Apr–Jun, Jul–Sep,
     * Oct–Dec, Jan–Mar). Each is due on the last day of its quarter's last month.
     * Replaces any existing installments for this fee type + year.
     */
    private function generateQuarterlyCycles(): void
    {
        $this->validate([
            'cycleFeeType' => 'required|string|max:20',
            'cycleYear'    => 'required|string|max:20',
        ]);

        $y = $this->cycleStartYear();
        // [startMonth, startYear, endMonth, endYear]
        $quarters = [
            [4, $y, 6, $y],
            [7, $y, 9, $y],
            [10, $y, 12, $y],
            [1, $y + 1, 3, $y + 1],
        ];

        $this->replaceCycles(function () use ($quarters) {
            foreach ($quarters as $i => [$sM, $sY, $eM, $eY]) {
                $start = \Carbon\Carbon::create($sY, $sM, 1)->startOfMonth();
                $end   = \Carbon\Carbon::create($eY, $eM, 1)->endOfMonth();

                FeeCycle::create([
                    'organization_id' => $this->orgId(),
                    'fee_type'        => $this->cycleFeeType,
                    'payment_serial'  => $i + 1,
                    'start_date'      => $start->toDateString(),
                    'end_date'        => $end->toDateString(),
                    'due_date'        => $end->toDateString(),
                    'penalty_per_day' => $this->cyclePenaltyPerDay ?: 0,
                    'fee_percent'     => 25,
                    'amount'          => 0,
                    'academic_year'   => $this->cycleYear,
                    'is_active'       => true,
                ]);
            }
        });

        $this->notification()->success('4 quarterly installments created!');
        $this->closeCycleModal();
    }

    /** Clear existing installments for the current fee type + year, then run $build. */
    private function replaceCycles(callable $build): void
    {
        FeeCycle::forOrg($this->orgId())
            ->where('fee_type', $this->cycleFeeType)
            ->where('academic_year', $this->cycleYear)
            ->delete();

        $build();
    }

    public function deleteCycle(int $id): void { $this->pendingDeleteCycleId = $id; }
    public function cancelDeleteCycle(): void  { $this->pendingDeleteCycleId = null; }
    public function doDeleteCycle(): void
    {
        FeeCycle::forOrg($this->orgId())->where('id', $this->pendingDeleteCycleId)->delete();
        $this->pendingDeleteCycleId = null;
        $this->notification()->success('Installment deleted!');
    }

    // ─── Shared ───────────────────────────────────────────────────────────────

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $orgId = $this->orgId();
        $data  = ['standards' => $this->standards, 'sections' => $this->sections, 'students' => $this->students];

        if ($this->activeTab === 'fee_structure') {
            $data['structures'] = FeeStructure::with(['standard', 'section'])
                ->where('organization_id', $orgId)
                ->when($this->filterStructureStandard, fn($q) => $q->where('standard_id', $this->filterStructureStandard))
                ->when($this->filterStructureSection, fn($q) => $q->where('section_id', $this->filterStructureSection))
                ->when($this->filterStructureYear, fn($q) => $q->where('academic_year', $this->filterStructureYear))
                ->when($this->search, fn($q) => $q->where('fee_name', 'like', "%{$this->search}%"))
                ->orderByDesc('created_at')
                ->paginate($this->perPage);
        }

        if ($this->activeTab === 'payments') {
            $data['payments'] = $this->getPaymentsQuery()
                ->orderByDesc('payment_date')
                ->paginate($this->perPage);
        }

        if ($this->activeTab === 'cycle') {
            $data['cycles'] = FeeCycle::forOrg($orgId)
                ->orderBy('fee_type')
                ->orderBy('payment_serial')
                ->get();

            // Existing installments of the fee type being added/edited — shown in
            // the form so the user can see previous installments' % and due dates.
            $data['cycleExisting'] = FeeCycle::forOrg($orgId)
                ->where('fee_type', $this->cycleFeeType ?: 'academic')
                ->where('academic_year', $this->cycleYear ?: '')
                ->orderBy('payment_serial')
                ->get();

            // ── Per-class installment calculator ──
            $data['calcSections'] = $this->calcStandardId
                ? Section::where('standard_id', $this->calcStandardId)->where('is_active', true)->orderBy('id')->get()
                : collect();

            $calcTotalFee = 0.0;
            $calcRows     = [];
            if ($this->calcStandardId) {
                $calcTotalFee = (float) FeeStructure::where('organization_id', $orgId)
                    ->academic()->active()
                    ->forClass((int) $this->calcStandardId, $this->calcSectionId ? (int) $this->calcSectionId : null)
                    ->sum('amount');

                $acadCycles = FeeCycle::forOrg($orgId)->active()
                    ->where('fee_type', 'academic')
                    ->orderBy('payment_serial')->get();

                $cum = 0.0;
                foreach ($acadCycles as $cy) {
                    $pct = (float) $cy->fee_percent;
                    $amt = round($calcTotalFee * $pct / 100, 2);
                    $cum += $amt;
                    $calcRows[] = [
                        'serial'     => (int) $cy->payment_serial,
                        'percent'    => $pct,
                        'due_date'   => optional($cy->due_date)->format('d M Y'),
                        'amount'     => $amt,
                        'cumulative' => round($cum, 2),
                        'remaining'  => round(max(0, $calcTotalFee - $cum), 2),
                    ];
                }
            }
            $data['calcTotalFee'] = $calcTotalFee;
            $data['calcRows']     = $calcRows;
        }

        if ($this->activeTab === 'account_users') {
            $accBase = User::where('organization_id', $orgId)->where('role', 'accounts');
            $data['acctTotal']    = (clone $accBase)->count();
            $data['acctActive']   = (clone $accBase)->where('is_active', true)->count();
            $data['acctInactive'] = (clone $accBase)->where('is_active', false)->count();
        }

        if ($this->activeTab === 'concession') {
            $data['concessions'] = FeeConcession::with(['studentDetail.user', 'standard', 'section'])
                ->where('organization_id', $orgId)
                ->when($this->concFilterStandard, fn($q) => $q->where('standard_id', $this->concFilterStandard))
                ->when($this->concFilterSection, fn($q) => $q->where('section_id', $this->concFilterSection))
                ->when($this->search, fn($q) => $q->whereHas('studentDetail', fn($s) =>
                    $s->where('full_name', 'like', "%{$this->search}%")->orWhere('father_name', 'like', "%{$this->search}%")))
                ->orderByDesc('created_at')
                ->paginate($this->perPage);
            $data['concStudents'] = $this->concStudents;
        }

        return view('livewire.admin.fee', $data);
    }
}
