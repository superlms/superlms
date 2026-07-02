<?php

namespace App\Livewire\Accounts;

use App\Livewire\Concerns\HandlesTransportFees;
use App\Models\Admin\Fee\FeeStructure as FeeStructureModel;
use App\Models\Admin\Transportation;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class FeeStructure extends Component
{
    use WireUiActions, WithPagination, HandlesTransportFees;

    // ─── Academic structure form ─────────────────────────────────────────────
    public $structureStandardId = '';
    public $structureSectionIds = [];
    public $academicYear        = '';
    public $editStructureId     = null;
    public bool $structureModalOpen = false;
    public $feeRows = [];

    // ─── View slide-in ────────────────────────────────────────────────────────
    public bool $viewModalOpen = false;
    public array $viewStructureData = [];

    // ─── Filters ────────────────────────────────────────────────────────────────
    public $filterStructureStandard = '';
    public $filterStructureSection  = '';
    public $search = '';
    public $perPage = 10;

    // Required by HandlesTransportFees trait
    public string $filterRoute = '';

    // ─── Delete confirm ─────────────────────────────────────────────────────────
    public ?int $pendingDeleteStructureId = null;

    // ─── Tabs: academic | transport_routes | transport_fees ──────────────────────
    public string $activeTab = 'academic';

    protected $queryString = [
        'activeTab' => ['except' => 'academic'],
        'search'    => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->academicYear = '2026-27';
        $this->feeRows = [['name' => '', 'amount' => '']];
    }

    private function orgId(): int
    {
        return Auth::user()->organization_id;
    }

    protected function txOrgId(): int
    {
        return (int) Auth::user()->organization_id;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    // ─── Fee row management ──────────────────────────────────────────────────────
    public function addFeeRow(): void
    {
        $this->feeRows[] = ['name' => '', 'amount' => ''];
    }

    public function removeFeeRow(int $index): void
    {
        if (count($this->feeRows) > 1) {
            unset($this->feeRows[$index]);
            $this->feeRows = array_values($this->feeRows);
        }
    }

    // ─── Academic structure modal ────────────────────────────────────────────────
    public function openStructureModal(?int $id = null): void
    {
        $this->resetStructureForm();
        $this->editStructureId = $id;

        if ($id) {
            $s = FeeStructureModel::where('organization_id', $this->orgId())->find($id);
            if (!$s) return;
            $this->structureStandardId = $s->standard_id;
            $this->structureSectionIds = $s->section_id ? [$s->section_id] : [];
            $this->academicYear        = $s->academic_year;
            $this->feeRows             = [['name' => $s->fee_name, 'amount' => $s->amount]];
        }

        $this->structureModalOpen = true;
    }

    public function closeStructureModal(): void
    {
        $this->structureModalOpen = false;
        $this->resetStructureForm();
    }

    public function editStructure(int $id): void
    {
        $this->openStructureModal($id);
    }

    public function saveStructure(): void
    {
        $this->validate([
            'structureStandardId' => 'required|exists:standards,id',
            'academicYear'        => 'required|string|max:20',
            'feeRows'             => 'required|array|min:1',
            'feeRows.*.name'      => 'required|string|max:255',
            'feeRows.*.amount'    => 'required|numeric|min:0',
        ]);

        try {
            if ($this->editStructureId) {
                $row = $this->feeRows[0] ?? ['name' => '', 'amount' => 0];
                FeeStructureModel::where('organization_id', $this->orgId())
                    ->where('id', $this->editStructureId)
                    ->update([
                        'standard_id'   => $this->structureStandardId,
                        'section_id'    => !empty($this->structureSectionIds) ? $this->structureSectionIds[0] : null,
                        'fee_name'      => $row['name'],
                        'amount'        => $row['amount'],
                        'fee_type'      => 'academic',
                        'academic_year' => $this->academicYear,
                    ]);
                $this->notification()->success('Fee structure updated successfully!');
            } else {
                $sectionIds = !empty($this->structureSectionIds) ? $this->structureSectionIds : [null];
                foreach ($sectionIds as $sectionId) {
                    foreach ($this->feeRows as $row) {
                        FeeStructureModel::create([
                            'organization_id' => $this->orgId(),
                            'standard_id'     => $this->structureStandardId,
                            'section_id'      => $sectionId,
                            'fee_name'        => $row['name'],
                            'amount'          => $row['amount'],
                            'fee_type'        => 'academic',
                            'academic_year'   => $this->academicYear,
                            'is_active'       => true,
                        ]);
                    }
                }
                $this->notification()->success('Fee structure added successfully!');
            }

            $this->closeStructureModal();
        } catch (\Exception $e) {
            $this->notification()->error('Error', $e->getMessage());
        }
    }

    public function viewStructure(int $id): void
    {
        $s = FeeStructureModel::with(['standard', 'section'])
            ->where('organization_id', $this->orgId())->find($id);
        if (!$s) return;

        $this->viewStructureData = [
            'id'            => $s->id,
            'class'         => $s->standard->name ?? '-',
            'section'       => $s->section->name ?? 'All Sections',
            'fee_name'      => $s->fee_name,
            'amount'        => $s->amount,
            'academic_year' => $s->academic_year,
        ];
        $this->viewModalOpen = true;
    }

    public function closeViewModal(): void
    {
        $this->viewModalOpen = false;
        $this->viewStructureData = [];
    }

    // ─── Delete ───────────────────────────────────────────────────────────────────
    public function deleteStructure(int $id): void { $this->pendingDeleteStructureId = $id; }
    public function cancelDeleteStructure(): void { $this->pendingDeleteStructureId = null; }
    public function doDeleteStructure(): void
    {
        FeeStructureModel::where('id', $this->pendingDeleteStructureId)
            ->where('organization_id', $this->orgId())->delete();
        $this->pendingDeleteStructureId = null;
        $this->notification()->success('Fee structure deleted!');
    }

    private function resetStructureForm(): void
    {
        $this->reset(['editStructureId', 'structureStandardId', 'structureSectionIds']);
        $this->academicYear = '2026-27';
        $this->feeRows = [['name' => '', 'amount' => '']];
        $this->resetValidation();
    }

    // ─── Watchers ─────────────────────────────────────────────────────────────────
    public function updatedStructureStandardId(): void { $this->structureSectionIds = []; }
    public function updatedFilterStructureStandard(): void { $this->filterStructureSection = ''; $this->resetPage(); }
    public function updatedFilterStructureSection(): void { $this->resetPage(); }
    public function updatedSearch(): void { $this->resetPage(); }

    public function render()
    {
        $orgId = $this->orgId();

        $standards = Standard::where('organization_id', $orgId)
            ->where('is_active', true)->orderBy('id')->get();

        $sections = $this->filterStructureStandard
            ? Section::where('standard_id', $this->filterStructureStandard)
                ->where('organization_id', $orgId)->where('is_active', true)->get()
            : collect();

        $formSections = $this->structureStandardId
            ? Section::where('standard_id', $this->structureStandardId)
                ->where('organization_id', $orgId)->where('is_active', true)->get()
            : collect();

        // Analytics
        $academicCount    = FeeStructureModel::where('organization_id', $orgId)->where('fee_type', 'academic')->count();
        $totalAcademicAmt = FeeStructureModel::where('organization_id', $orgId)->where('fee_type', 'academic')->sum('amount');
        $routeCount       = Transportation::where('organization_id', $orgId)->count();

        // Academic structures (academic tab)
        $structures = FeeStructureModel::with(['standard', 'section'])
            ->where('organization_id', $orgId)
            ->where('fee_type', 'academic')
            ->when($this->filterStructureStandard, fn($q) => $q->where('standard_id', $this->filterStructureStandard))
            ->when($this->filterStructureSection, fn($q) => $q->where('section_id', $this->filterStructureSection))
            ->when($this->search, fn($q) => $q->where('fee_name', 'like', "%{$this->search}%"))
            ->orderBy('standard_id')->orderBy('section_id')
            ->paginate($this->perPage);

        // Transport routes (route-wise tab)
        $routes = collect();
        if ($this->activeTab === 'transport_routes') {
            $routes = Transportation::with('driver.user:id,name')
                ->where('organization_id', $orgId)
                ->when($this->search, fn($q) => $q->where('route_name', 'like', "%{$this->search}%"))
                ->orderBy('route_name')->get();
        }

        return view('livewire.accounts.fee-structure', [
            'standards'        => $standards,
            'sections'         => $sections,
            'formSections'     => $formSections,
            'structures'       => $structures,
            'routes'           => $routes,
            'academicCount'    => $academicCount,
            'totalAcademicAmt' => $totalAcademicAmt,
            'routeCount'       => $routeCount,
        ]);
    }
}
