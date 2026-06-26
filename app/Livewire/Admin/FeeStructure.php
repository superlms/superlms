<?php

namespace App\Livewire\Admin;

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

    // ─── Group (class+section) edit / view / delete ───────────────────────────────
    public bool $editingGroup       = false;
    public $editGroupStandardId     = null;
    public $editGroupSectionId      = null; // null = "All Sections" bucket
    public ?array $pendingDeleteGroup = null; // ['standard_id'=>, 'section_id'=>]
    public bool $viewGroupOpen      = false;
    public array $viewGroupData     = [];

    // ─── Tabs: academic | transport_routes | transport_fees ──────────────────────
    public string $activeTab = 'academic';

    /** True when rendered inside the Fee page's "Fee Structure" tab. */
    public bool $embedded = false;

    protected function queryString(): array
    {
        // Skip URL sync when embedded — avoids activeTab/search clashes with the parent Fee page.
        if ($this->embedded) {
            return [];
        }

        return [
            'activeTab' => ['except' => 'academic'],
            'search'    => ['except' => ''],
        ];
    }

    public function mount($embedded = false): void
    {
        $this->embedded = $embedded;
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
            if ($this->editingGroup) {
                // Replace the whole class+section group with the edited rows.
                FeeStructureModel::where('organization_id', $this->orgId())
                    ->where('fee_type', 'academic')
                    ->where('standard_id', $this->editGroupStandardId)
                    ->when($this->editGroupSectionId,
                        fn ($q) => $q->where('section_id', $this->editGroupSectionId),
                        fn ($q) => $q->whereNull('section_id'))
                    ->delete();

                $sectionId = $this->editGroupSectionId;
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
                $this->notification()->success('Fee structure updated successfully!');
                $this->closeStructureModal();
                return;
            }

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

    // ─── Group operations (the listing groups one card per class+section) ─────────

    /** Open the structure modal pre-loaded with every fee row of a class+section. */
    public function editGroup($standardId, $sectionId = null): void
    {
        $this->resetStructureForm();

        $rows = FeeStructureModel::where('organization_id', $this->orgId())
            ->where('fee_type', 'academic')
            ->where('standard_id', $standardId)
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId),
                              fn ($q) => $q->whereNull('section_id'))
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) return;

        $this->editingGroup        = true;
        $this->editGroupStandardId = $standardId;
        $this->editGroupSectionId  = $sectionId;
        $this->structureStandardId = $standardId;
        $this->structureSectionIds = $sectionId ? [$sectionId] : [];
        $this->academicYear        = $rows->first()->academic_year ?: '2026-27';
        $this->feeRows             = $rows->map(fn ($r) => ['name' => $r->fee_name, 'amount' => $r->amount])->toArray();
        $this->editStructureId     = null;
        $this->structureModalOpen  = true;
    }

    public function viewGroup($standardId, $sectionId = null): void
    {
        $rows = FeeStructureModel::with(['standard', 'section'])
            ->where('organization_id', $this->orgId())
            ->where('fee_type', 'academic')
            ->where('standard_id', $standardId)
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId),
                              fn ($q) => $q->whereNull('section_id'))
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) return;

        $this->viewGroupData = [
            'class'   => $rows->first()->standard->name ?? '—',
            'section' => $rows->first()->section->name ?? 'All Sections',
            'year'    => $rows->first()->academic_year,
            'rows'    => $rows->map(fn ($r) => ['fee_name' => $r->fee_name, 'amount' => (float) $r->amount])->toArray(),
            'total'   => (float) $rows->sum('amount'),
        ];
        $this->viewGroupOpen = true;
    }

    public function closeViewGroup(): void
    {
        $this->viewGroupOpen = false;
        $this->viewGroupData = [];
    }

    public function deleteGroup($standardId, $sectionId = null): void
    {
        $this->pendingDeleteGroup = ['standard_id' => $standardId, 'section_id' => $sectionId];
    }

    public function cancelDeleteGroup(): void
    {
        $this->pendingDeleteGroup = null;
    }

    public function doDeleteGroup(): void
    {
        if (!$this->pendingDeleteGroup) return;

        FeeStructureModel::where('organization_id', $this->orgId())
            ->where('fee_type', 'academic')
            ->where('standard_id', $this->pendingDeleteGroup['standard_id'])
            ->when($this->pendingDeleteGroup['section_id'],
                fn ($q) => $q->where('section_id', $this->pendingDeleteGroup['section_id']),
                fn ($q) => $q->whereNull('section_id'))
            ->delete();

        $this->pendingDeleteGroup = null;
        $this->notification()->success('Fee structure deleted!');
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
        $this->reset(['editStructureId', 'structureStandardId', 'structureSectionIds',
                      'editingGroup', 'editGroupStandardId', 'editGroupSectionId']);
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
            ->where('is_active', true)->orderBy('order')->get();

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

        // Academic structures (academic tab) — grouped one card per class+section.
        // The school's fee lines are few, so grouping in PHP is cheap and lets
        // us show per-group totals + a grand total the way the listing needs.
        $rows = FeeStructureModel::with(['standard', 'section'])
            ->where('organization_id', $orgId)
            ->where('fee_type', 'academic')
            ->when($this->filterStructureStandard, fn($q) => $q->where('standard_id', $this->filterStructureStandard))
            ->when($this->filterStructureSection, fn($q) => $q->where('section_id', $this->filterStructureSection))
            ->when($this->search, fn($q) => $q->where('fee_name', 'like', "%{$this->search}%"))
            ->orderBy('standard_id')->orderBy('section_id')->orderBy('id')
            ->get();

        $structureGroups = $rows
            ->groupBy(fn($r) => $r->standard_id . '-' . ($r->section_id ?? 0))
            ->map(fn($g) => [
                'standard_id' => $g->first()->standard_id,
                'section_id'  => $g->first()->section_id,
                'class'       => $g->first()->standard->name ?? '—',
                'section'     => $g->first()->section->name ?? 'All Sections',
                'year'        => $g->first()->academic_year,
                'rows'        => $g->values(),
                'total'       => (float) $g->sum('amount'),
            ])
            ->values();

        $structureGrandTotal = (float) $rows->sum('amount');

        // Transport routes (route-wise tab)
        $routes = collect();
        if ($this->activeTab === 'transport_routes') {
            $routes = Transportation::with('driver.user:id,name')
                ->where('organization_id', $orgId)
                ->when($this->search, fn($q) => $q->where('route_name', 'like', "%{$this->search}%"))
                ->orderBy('route_name')->get();
        }

        return view('livewire.admin.fee-structure', [
            'standards'           => $standards,
            'sections'            => $sections,
            'formSections'        => $formSections,
            'structureGroups'     => $structureGroups,
            'structureGrandTotal' => $structureGrandTotal,
            'routes'              => $routes,
            'academicCount'       => $academicCount,
            'totalAcademicAmt'    => $totalAcademicAmt,
            'routeCount'          => $routeCount,
        ]);
    }
}
