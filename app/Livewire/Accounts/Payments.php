<?php

namespace App\Livewire\Accounts;

use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Payments extends Component
{
    use WithPagination;

    // Filters
    public $dateFrom = '';
    public $dateTo = '';
    public $paymentStandardId = '';
    public $paymentSectionId = '';
    public $paymentStudentSearch = '';
    public $paymentModeFilter = '';
    public $feeTypeFilter = '';

    public $perPage = 15;

    protected $queryString = [
        'paymentModeFilter' => ['except' => ''],
        'paymentStudentSearch' => ['except' => ''],
        'feeTypeFilter' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    private function orgId(): int
    {
        return Auth::user()->organization_id;
    }

    private function getHeaderStats(): array
    {
        $orgId = $this->orgId();

        // Total fee from fee structures (for filtered class/section or all)
        $structureQuery = FeeStructure::where('organization_id', $orgId)->where('is_active', true);

        if ($this->paymentStandardId) {
            $structureQuery->where('standard_id', $this->paymentStandardId);
            if ($this->paymentSectionId) {
                $structureQuery->where(function ($q) {
                    $q->where('section_id', $this->paymentSectionId)->orWhereNull('section_id');
                });
            }
        }

        $structures = $structureQuery->get();

        $totalAcademicFee = $structures->where('fee_type', 'academic')->sum('amount');
        $totalTransportFee = $structures->where('fee_type', 'transport')->sum('amount');

        // If viewing all classes, multiply by student count per class
        // For simplicity, compute total scheduled fee across all applicable students
        if (!$this->paymentStandardId) {
            // Sum fee structures per standard * student count
            $standardIds = $structures->pluck('standard_id')->unique();
            $totalAcademicFee = 0;
            $totalTransportFee = 0;

            foreach ($standardIds as $stdId) {
                $studentCount = StudentDetail::where('organization_id', $orgId)
                    ->where('standard_id', $stdId)->count();

                $stdStructures = $structures->where('standard_id', $stdId);
                $totalAcademicFee += $stdStructures->where('fee_type', 'academic')->sum('amount') * $studentCount;
                $totalTransportFee += $stdStructures->where('fee_type', 'transport')->sum('amount') * $studentCount;
            }
        } else {
            $studentQuery = StudentDetail::where('organization_id', $orgId)
                ->where('standard_id', $this->paymentStandardId);
            if ($this->paymentSectionId) {
                $studentQuery->where('section_id', $this->paymentSectionId);
            }
            $studentCount = $studentQuery->count();
            $totalAcademicFee = $totalAcademicFee * $studentCount;
            $totalTransportFee = $totalTransportFee * $studentCount;
        }

        $totalFee = $totalAcademicFee + $totalTransportFee;

        // Collected amounts (based on filters)
        $paymentBase = FeePayment::where('organization_id', $orgId);
        if ($this->paymentStandardId) {
            $paymentBase->where('standard_id', $this->paymentStandardId);
        }
        if ($this->paymentSectionId) {
            $paymentBase->where('section_id', $this->paymentSectionId);
        }

        $academicCollected = (clone $paymentBase)->where('fee_type', 'academic')->sum('amount');
        $transportCollected = (clone $paymentBase)->where('fee_type', 'transport')->sum('amount');
        $totalCollected = $academicCollected + $transportCollected;

        return [
            'total_fee' => $totalFee,
            'total_academic_fee' => $totalAcademicFee,
            'total_transport_fee' => $totalTransportFee,
            'academic_collected' => $academicCollected,
            'transport_collected' => $transportCollected,
            'total_collected' => $totalCollected,
            'remaining_fee' => max(0, $totalFee - $totalCollected),
        ];
    }

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
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['paymentStandardId', 'paymentSectionId', 'paymentStudentSearch', 'paymentModeFilter', 'feeTypeFilter']);
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
        $this->resetPage();
    }

    public function render()
    {
        $orgId = $this->orgId();

        $standards = Standard::where('organization_id', $orgId)
            ->where('is_active', true)->orderBy('id')->get();

        $sections = collect();
        if ($this->paymentStandardId) {
            $sections = Section::where('standard_id', $this->paymentStandardId)
                ->where('organization_id', $orgId)->where('is_active', true)->get();
        }

        $headerStats = $this->getHeaderStats();

        $payments = FeePayment::with(['studentDetail.user', 'standard', 'section'])
            ->where('organization_id', $orgId)
            ->when($this->paymentStandardId, fn($q) => $q->where('standard_id', $this->paymentStandardId))
            ->when($this->paymentSectionId, fn($q) => $q->where('section_id', $this->paymentSectionId))
            ->when($this->paymentModeFilter, fn($q) => $q->where('payment_mode', $this->paymentModeFilter))
            ->when($this->feeTypeFilter, fn($q) => $q->where('fee_type', $this->feeTypeFilter))
            ->when($this->dateFrom, fn($q) => $q->whereDate('payment_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('payment_date', '<=', $this->dateTo))
            ->when($this->paymentStudentSearch, fn($q) => $q->whereHas('studentDetail', function ($sq) {
                $sq->where('full_name', 'like', "%{$this->paymentStudentSearch}%")
                    ->orWhere('admission_no', 'like', "%{$this->paymentStudentSearch}%")
                    ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$this->paymentStudentSearch}%"));
            }))
            ->orderByDesc('payment_date')
            ->paginate($this->perPage);

        return view('livewire.accounts.payments', [
            'standards' => $standards,
            'sections' => $sections,
            'payments' => $payments,
            'headerStats' => $headerStats,
        ]);
    }
}
