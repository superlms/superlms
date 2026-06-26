<?php

namespace App\Livewire\Accounts;

use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

class FeeSubmission extends Component
{
    use WireUiActions;

    // Student selection
    public $submissionStandardId = '';
    public $submissionSectionId = '';
    public $selectedStudentId = '';

    // Student data
    public $classStructures = [];
    public $studentTransactions = [];
    public $studentInfo = [];
    public $feeBreakdown = [];

    // Payment form
    public $submitAmount = '';
    public $submitFeeType = 'academic';
    public $submitPaymentMode = 'cash';
    public $submitDate = '';
    public $submitRemark = '';
    public $penaltyAmount = '';
    public $waiverAmount = '';
    public $waiverReason = '';

    // Transaction history fullscreen
    public $showTransactionHistory = false;

    // Analytics data
    public $analyticsData = [];

    public function mount(): void
    {
        $this->submitDate = today()->toDateString();
    }

    private function orgId(): int
    {
        return Auth::user()->organization_id;
    }

    public function updatedSubmissionStandardId(): void
    {
        $this->submissionSectionId = '';
        $this->selectedStudentId = '';
        $this->resetStudentData();
    }

    public function updatedSubmissionSectionId(): void
    {
        $this->selectedStudentId = '';
        $this->resetStudentData();
    }

    public function updatedSelectedStudentId(): void
    {
        if (!$this->selectedStudentId) {
            $this->resetStudentData();
            return;
        }

        $this->loadStudentData();
    }

    public function updatedSubmitPaymentMode(): void
    {
        if ($this->submitPaymentMode !== 'waiver') {
            $this->waiverAmount = '';
            $this->waiverReason = '';
        }
    }

    private function resetStudentData(): void
    {
        $this->classStructures = [];
        $this->studentTransactions = [];
        $this->studentInfo = [];
        $this->feeBreakdown = [];
        $this->analyticsData = [];
    }

    private function loadStudentData(): void
    {
        $student = StudentDetail::with(['standard', 'section', 'user'])->find($this->selectedStudentId);
        if (!$student) return;

        $orgId = $this->orgId();

        // Student info
        $this->studentInfo = [
            'name' => $student->user->name ?? $student->full_name ?? '-',
            'admission_no' => $student->admission_no,
            'class' => $student->standard->name ?? '-',
            'section' => $student->section->name ?? '-',
            'phone' => $student->phone,
            'father_name' => $student->father_name ?? '-',
            'roll_no' => $student->roll_no ?? '-',
        ];

        // Fee structures
        $structures = FeeStructure::where('organization_id', $orgId)
            ->where('standard_id', $student->standard_id)
            ->where(function ($q) use ($student) {
                $q->where('section_id', $student->section_id)->orWhereNull('section_id');
            })
            ->where('is_active', true)
            ->get();

        $this->classStructures = $structures->toArray();

        // Transaction history
        $transactions = FeePayment::with(['standard', 'section'])
            ->where('organization_id', $orgId)
            ->where('student_detail_id', $this->selectedStudentId)
            ->orderByDesc('payment_date')
            ->get();

        $this->studentTransactions = $transactions->toArray();

        // Fee breakdown calculation
        $totalAcademicFee = $structures->where('fee_type', 'academic')->sum('amount');
        $totalTransportFee = $structures->where('fee_type', 'transport')->sum('amount');
        $totalFee = $totalAcademicFee + $totalTransportFee;

        $academicPaid = $transactions->where('fee_type', 'academic')->sum('amount');
        $transportPaid = $transactions->where('fee_type', 'transport')->sum('amount');
        $totalPaid = $academicPaid + $transportPaid;
        $totalWaiver = $transactions->sum('waiver_amount');
        $totalPenalty = $transactions->sum('penalty_amount');

        $this->feeBreakdown = [
            'total_academic_fee' => $totalAcademicFee,
            'total_transport_fee' => $totalTransportFee,
            'total_fee' => $totalFee,
            'academic_paid' => $academicPaid,
            'transport_paid' => $transportPaid,
            'total_paid' => $totalPaid,
            'total_waiver' => $totalWaiver,
            'total_penalty' => $totalPenalty,
            'academic_remaining' => max(0, $totalAcademicFee - $academicPaid),
            'transport_remaining' => max(0, $totalTransportFee - $transportPaid),
            'total_remaining' => max(0, $totalFee - $totalPaid),
        ];

        // Analytics data for charts
        $this->loadAnalytics();
    }

    private function loadAnalytics(): void
    {
        $orgId = $this->orgId();

        // Monthly payment trend for this student (last 12 months)
        $monthlyPayments = FeePayment::where('organization_id', $orgId)
            ->where('student_detail_id', $this->selectedStudentId)
            ->where('payment_date', '>=', now()->subMonths(12))
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') as month, SUM(amount) as total, fee_type")
            ->groupBy('month', 'fee_type')
            ->orderBy('month')
            ->get();

        $months = [];
        $academicData = [];
        $transportData = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i)->format('Y-m');
            $label = now()->subMonths($i)->format('M Y');
            $months[] = $label;
            $academicData[] = $monthlyPayments->where('month', $month)->where('fee_type', 'academic')->first()->total ?? 0;
            $transportData[] = $monthlyPayments->where('month', $month)->where('fee_type', 'transport')->first()->total ?? 0;
        }

        // Payment mode distribution
        $modeDistribution = FeePayment::where('organization_id', $orgId)
            ->where('student_detail_id', $this->selectedStudentId)
            ->selectRaw('payment_mode, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('payment_mode')
            ->get();

        $this->analyticsData = [
            'months' => $months,
            'academic_monthly' => $academicData,
            'transport_monthly' => $transportData,
            'mode_labels' => $modeDistribution->pluck('payment_mode')->map(fn($m) => ucfirst(str_replace('_', ' ', $m)))->toArray(),
            'mode_amounts' => $modeDistribution->pluck('total')->toArray(),
            'mode_counts' => $modeDistribution->pluck('count')->toArray(),
        ];
    }

    public function toggleTransactionHistory(): void
    {
        $this->showTransactionHistory = !$this->showTransactionHistory;
    }

    public function submitFeePayment(): void
    {
        $rules = [
            'selectedStudentId' => 'required|exists:student_details,id',
            'submitAmount' => 'required|numeric|min:0.01',
            'submitFeeType' => 'required|in:academic,transport',
            'submitPaymentMode' => 'required|in:cash,online,cheque,bank_transfer,waiver',
            'submitDate' => 'required|date',
            'penaltyAmount' => 'nullable|numeric|min:0',
            'waiverAmount' => 'nullable|numeric|min:0',
        ];

        if ($this->submitPaymentMode === 'waiver') {
            $rules['waiverAmount'] = 'required|numeric|min:0.01';
            $rules['waiverReason'] = 'required|string|max:500';
        }

        $this->validate($rules);

        try {
            DB::beginTransaction();

            $student = StudentDetail::find($this->selectedStudentId);

            $amount = (float) $this->submitAmount;
            $penaltyAmount = (float) ($this->penaltyAmount ?: 0);
            $waiverAmount = (float) ($this->waiverAmount ?: 0);

            // Net amount = base amount + penalty - waiver
            $netAmount = $amount + $penaltyAmount - $waiverAmount;

            FeePayment::create([
                'organization_id' => $this->orgId(),
                'student_detail_id' => $this->selectedStudentId,
                'standard_id' => $student->standard_id,
                'section_id' => $student->section_id,
                'fee_type' => $this->submitFeeType,
                'amount' => max(0, $netAmount),
                'waiver_amount' => $waiverAmount,
                'waiver_reason' => $this->waiverReason ?: null,
                'penalty_amount' => $penaltyAmount,
                'payment_mode' => $this->submitPaymentMode,
                'payment_date' => $this->submitDate,
                'remark' => $this->submitRemark,
                'submitted_by' => Auth::user()->name,
            ]);

            DB::commit();

            $this->notification()->success('Fee payment submitted successfully!');
            $this->reset(['submitAmount', 'submitRemark', 'penaltyAmount', 'waiverAmount', 'waiverReason']);
            $this->submitDate = today()->toDateString();
            $this->submitFeeType = 'academic';
            $this->submitPaymentMode = 'cash';

            // Refresh student data
            $this->loadStudentData();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->notification()->error('Error submitting fee: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $orgId = $this->orgId();

        $standards = Standard::where('organization_id', $orgId)
            ->where('is_active', true)->orderBy('order')->get();

        $sections = collect();
        $students = collect();

        if ($this->submissionStandardId) {
            $sections = Section::where('standard_id', $this->submissionStandardId)
                ->where('organization_id', $orgId)->where('is_active', true)->get();

            $students = StudentDetail::with('user')
                ->where('organization_id', $orgId)
                ->where('standard_id', $this->submissionStandardId)
                ->when($this->submissionSectionId, fn($q) => $q->where('section_id', $this->submissionSectionId))
                ->get();
        }

        return view('livewire.accounts.fee-submission', [
            'standards' => $standards,
            'sections' => $sections,
            'students' => $students,
        ]);
    }
}
