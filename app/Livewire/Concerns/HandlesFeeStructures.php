<?php

namespace App\Livewire\Concerns;

use App\Models\Admin\Fee\FeeStructure as FeeStructureModel;
use App\Models\Admin\Transportation;
use App\Models\Student\Section;
use App\Models\Student\Standard;

/**
 * The Fee Structure page — shared between Admin\FeeStructure and
 * Accounts\FeeStructure (and, through the admin one, the Fee page's
 * "Fee Structure" tab). The two were hand-copied and had already drifted;
 * this trait plus `livewire.partials.fee-structure-header` and
 * `livewire.partials.fee-structure-panel` are now the one place either
 * needs to change for both to update together.
 *
 * Two tabs only: Academic (class/section fee heads) and Transport (a route's
 * month-by-month fee). Everything is reached from the grey filter band —
 * pick a class (or a route) and its structure appears.
 *
 * The host component must provide `orgId(): int` and `WireUi\Traits\WireUiActions`
 * (for `notification()`).
 */
trait HandlesFeeStructures
{
    // ─── Tabs ────────────────────────────────────────────────────────────────
    public string $structureTab = 'academic'; // academic | transport

    // ─── Academic filter band ────────────────────────────────────────────────
    public $filterStructureStandard = '';
    public $filterStructureSection  = '';
    public $search = '';

    // ─── Transport filter band ───────────────────────────────────────────────
    public $filterStructureRoute = '';

    // ─── Add / Edit slide-in ─────────────────────────────────────────────────
    public bool $structureModalOpen = false;
    public $structureStandardId = '';
    public array $structureSectionIds = [];
    public $academicYear = '';
    /** [['name' => string, 'amount' => string], ...] — the serial is the row's position. */
    public array $feeRows = [];
    // Editing replaces one whole class+section group rather than a single row.
    public bool $editingGroup   = false;
    public $editGroupStandardId = null;
    public $editGroupSectionId  = null; // null = the "All Sections" bucket

    // ─── View slide-in + delete confirm ──────────────────────────────────────
    public bool $viewGroupOpen  = false;
    public array $viewGroupData = [];
    public ?array $pendingDeleteGroup = null; // ['standard_id' => , 'section_id' => ]

    // ─── Transport route view / edit / delete ────────────────────────────────
    public bool $routeViewOpen   = false;
    public bool $routeEditOpen   = false;
    public ?int $editRouteId     = null;
    public string $routeName     = '';
    public $routeMonthlyFee      = '';
    public string $routePickupTime = '';
    public bool $routeIsActive   = true;
    public ?int $pendingDeleteRouteId = null;

    /** Academic year, April → March. June is the free month on transport routes. */
    public array $structureMonths = [
        'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar',
    ];
    public string $structureFreeMonth = 'Jun';

    public function bootHandlesFeeStructures(): void
    {
        if ($this->academicYear === '') {
            $this->academicYear = $this->currentStructureYear();
        }
        if (empty($this->feeRows)) {
            $this->feeRows = [$this->blankFeeRow()];
        }
    }

    /** The academic year running now — April(this year) → March(next), e.g. "2026-27". */
    private function currentStructureYear(): string
    {
        $now       = now();
        $startYear = $now->month >= 4 ? $now->year : $now->year - 1;
        return $startYear . '-' . substr((string) ($startYear + 1), -2);
    }

    /**
     * Route-name prefix for the print / download links — 'admin' or
     * 'accounts'. Hosts override; admin is the safe default.
     */
    protected function structureRoutePrefix(): string
    {
        return 'admin';
    }

    public function setStructureTab(string $tab): void
    {
        $this->structureTab = in_array($tab, ['academic', 'transport'], true) ? $tab : 'academic';
    }

    // ── Filter band ─────────────────────────────────────────────────────────

    public function updatedFilterStructureStandard(): void
    {
        $this->filterStructureSection = '';
    }

    public function clearStructureFilters(): void
    {
        $this->filterStructureStandard = '';
        $this->filterStructureSection  = '';
        $this->search                  = '';
    }

    public function clearRouteFilter(): void
    {
        $this->filterStructureRoute = '';
    }

    // ── Add / Edit slide-in ─────────────────────────────────────────────────

    private function blankFeeRow(): array
    {
        return ['name' => '', 'amount' => ''];
    }

    public function addFeeRow(): void
    {
        $this->feeRows[] = $this->blankFeeRow();
    }

    public function removeFeeRow(int $index): void
    {
        if (count($this->feeRows) <= 1 || !isset($this->feeRows[$index])) {
            return;
        }
        unset($this->feeRows[$index]);
        $this->feeRows = array_values($this->feeRows);
    }

    public function openStructureModal(): void
    {
        $this->resetStructureForm();
        // Opening from a filtered view — carry the chosen class/section in.
        if ($this->filterStructureStandard) {
            $this->structureStandardId = $this->filterStructureStandard;
            $this->structureSectionIds = $this->filterStructureSection ? [(int) $this->filterStructureSection] : [];
        }
        $this->structureModalOpen = true;
    }

    public function closeStructureModal(): void
    {
        $this->structureModalOpen = false;
        $this->resetStructureForm();
    }

    private function resetStructureForm(): void
    {
        $this->reset(['structureStandardId', 'structureSectionIds', 'editingGroup',
                      'editGroupStandardId', 'editGroupSectionId']);
        $this->academicYear = $this->currentStructureYear();
        $this->feeRows      = [$this->blankFeeRow()];
        $this->resetValidation();
    }

    public function updatedStructureStandardId(): void
    {
        $this->structureSectionIds = [];
    }

    /** Live total of the rows being typed — shown in the panel footer. */
    public function getFeeRowsTotalProperty(): float
    {
        return round(collect($this->feeRows)->sum(fn ($r) => (float) ($r['amount'] ?? 0)), 2);
    }

    public function saveStructure(): void
    {
        $this->validate([
            'structureStandardId' => 'required|exists:standards,id',
            'academicYear'        => 'required|string|max:20',
            'feeRows'             => 'required|array|min:1',
            'feeRows.*.name'      => 'required|string|max:255',
            'feeRows.*.amount'    => 'required|numeric|min:0',
        ], [], [
            'structureStandardId' => 'class',
            'feeRows.*.name'      => 'fee name',
            'feeRows.*.amount'    => 'amount',
        ]);

        try {
            // Editing replaces the class+section group wholesale, so rows the
            // user deleted in the panel actually disappear.
            if ($this->editingGroup) {
                FeeStructureModel::where('organization_id', $this->orgId())
                    ->where('fee_type', 'academic')
                    ->where('standard_id', $this->editGroupStandardId)
                    ->when($this->editGroupSectionId,
                        fn ($q) => $q->where('section_id', $this->editGroupSectionId),
                        fn ($q) => $q->whereNull('section_id'))
                    ->delete();

                $this->writeFeeRows([$this->editGroupSectionId]);
                $this->notification()->success('Fee structure updated!');
                $this->closeStructureModal();
                return;
            }

            // Adding: one copy of every row per chosen section (none = All Sections).
            $sectionIds = !empty($this->structureSectionIds) ? $this->structureSectionIds : [null];
            $this->writeFeeRows($sectionIds);

            $this->notification()->success(
                count($this->feeRows) . ' fee head(s) added for ' . count($sectionIds) . ' section(s).'
            );
            $this->closeStructureModal();
        } catch (\Exception $e) {
            $this->notification()->error('Error', $e->getMessage());
        }
    }

    /** Write every row of the form once per section id given. */
    private function writeFeeRows(array $sectionIds): void
    {
        foreach ($sectionIds as $sectionId) {
            foreach ($this->feeRows as $row) {
                FeeStructureModel::create([
                    'organization_id' => $this->orgId(),
                    'standard_id'     => $this->structureStandardId,
                    'section_id'      => $sectionId ?: null,
                    'fee_name'        => $row['name'],
                    'amount'          => $row['amount'],
                    'fee_type'        => 'academic',
                    'academic_year'   => $this->academicYear,
                    'is_active'       => true,
                ]);
            }
        }
    }

    // ── Group view / edit / delete ──────────────────────────────────────────

    private function groupRows($standardId, $sectionId = null)
    {
        return FeeStructureModel::with(['standard', 'section'])
            ->where('organization_id', $this->orgId())
            ->where('fee_type', 'academic')
            ->where('standard_id', $standardId)
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId),
                               fn ($q) => $q->whereNull('section_id'))
            ->orderBy('id')
            ->get();
    }

    public function editGroup($standardId, $sectionId = null): void
    {
        $rows = $this->groupRows($standardId, $sectionId);
        if ($rows->isEmpty()) return;

        $this->resetStructureForm();
        $this->editingGroup        = true;
        $this->editGroupStandardId = $standardId;
        $this->editGroupSectionId  = $sectionId;
        $this->structureStandardId = $standardId;
        $this->structureSectionIds = $sectionId ? [(int) $sectionId] : [];
        $this->academicYear        = $rows->first()->academic_year ?: $this->currentStructureYear();
        $this->feeRows             = $rows->map(fn ($r) => [
            'name'   => $r->fee_name,
            'amount' => (string) $r->amount,
        ])->values()->toArray();
        $this->structureModalOpen  = true;
    }

    public function viewGroup($standardId, $sectionId = null): void
    {
        $rows = $this->groupRows($standardId, $sectionId);
        if ($rows->isEmpty()) return;

        $this->viewGroupData = [
            'standard_id' => $standardId,
            'section_id'  => $sectionId,
            'class'       => $rows->first()->standard->name ?? '—',
            'section'     => $rows->first()->section->name ?? 'All Sections',
            'year'        => $rows->first()->academic_year,
            'rows'        => $rows->map(fn ($r) => ['fee_name' => $r->fee_name, 'amount' => (float) $r->amount])->toArray(),
            'total'       => (float) $rows->sum('amount'),
        ];
        $this->viewGroupOpen = true;
    }

    public function closeViewGroup(): void
    {
        $this->viewGroupOpen = false;
        $this->viewGroupData = [];
    }

    /** Edit button inside the view card's header. */
    public function editViewingGroup(): void
    {
        $standardId = $this->viewGroupData['standard_id'] ?? null;
        $sectionId  = $this->viewGroupData['section_id'] ?? null;
        $this->closeViewGroup();
        if ($standardId) {
            $this->editGroup($standardId, $sectionId);
        }
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
        $this->closeViewGroup();
        $this->notification()->success('Fee structure deleted!');
    }

    // ── Transport route ─────────────────────────────────────────────────────

    private function currentRoute(): ?Transportation
    {
        return $this->filterStructureRoute
            ? Transportation::with('driver.user:id,name')
                ->where('organization_id', $this->orgId())
                ->find($this->filterStructureRoute)
            : null;
    }

    /**
     * A route's month-by-month fee for the academic year — every month costs
     * the route's monthly fee except June, which is free.
     */
    public function routeMonthRows(?Transportation $route): array
    {
        if (!$route) return [];

        $monthly = (float) $route->monthly_fee;
        $rows    = [];
        $running = 0.0;

        foreach ($this->structureMonths as $month) {
            $free    = $month === $this->structureFreeMonth;
            $amount  = $free ? 0.0 : $monthly;
            $running += $amount;
            $rows[]  = ['month' => $month, 'free' => $free, 'amount' => $amount, 'running' => $running];
        }

        return $rows;
    }

    public function openRouteView(): void  { $this->routeViewOpen = true; }
    public function closeRouteView(): void { $this->routeViewOpen = false; }

    public function openRouteEdit(): void
    {
        $route = $this->currentRoute();
        if (!$route) return;

        $this->editRouteId      = $route->id;
        $this->routeName        = (string) $route->route_name;
        $this->routeMonthlyFee  = (string) $route->monthly_fee;
        $this->routePickupTime  = (string) ($route->pickup_time ?? '');
        $this->routeIsActive    = (bool) $route->is_active;
        $this->routeViewOpen    = false;
        $this->routeEditOpen    = true;
        $this->resetValidation();
    }

    public function closeRouteEdit(): void
    {
        $this->routeEditOpen = false;
        $this->editRouteId   = null;
        $this->resetValidation();
    }

    public function saveRoute(): void
    {
        $this->validate([
            'routeName'       => 'required|string|max:255',
            'routeMonthlyFee' => 'required|numeric|min:0',
            'routePickupTime' => 'nullable|string|max:20',
        ], [], ['routeName' => 'route name', 'routeMonthlyFee' => 'monthly fee']);

        Transportation::where('organization_id', $this->orgId())
            ->where('id', $this->editRouteId)
            ->update([
                'route_name'  => $this->routeName,
                'monthly_fee' => $this->routeMonthlyFee,
                'pickup_time' => $this->routePickupTime ?: null,
                'is_active'   => $this->routeIsActive,
            ]);

        $this->notification()->success('Route fee updated!');
        $this->closeRouteEdit();
    }

    public function deleteRoute(): void
    {
        $this->pendingDeleteRouteId = $this->filterStructureRoute ? (int) $this->filterStructureRoute : null;
        $this->routeViewOpen        = false;
    }

    public function cancelDeleteRoute(): void
    {
        $this->pendingDeleteRouteId = null;
    }

    public function doDeleteRoute(): void
    {
        if (!$this->pendingDeleteRouteId) return;

        $route = Transportation::where('organization_id', $this->orgId())
            ->find($this->pendingDeleteRouteId);

        if (!$route) {
            $this->pendingDeleteRouteId = null;
            return;
        }

        // A route with students on it is not ours to drop from here — those
        // assignments live in the Transport section.
        if ($route->students()->count() > 0) {
            $this->pendingDeleteRouteId = null;
            $this->notification()->error(
                'Route still has students',
                'Unassign its students in the Transport section before deleting the route.'
            );
            return;
        }

        $route->delete();
        $this->pendingDeleteRouteId = null;
        $this->filterStructureRoute = '';
        $this->notification()->success('Route deleted!');
    }

    /**
     * Everything the two `livewire.partials.fee-structure-*` views need.
     * Call from render() and merge into the host's own view data.
     */
    protected function feeStructureViewData(): array
    {
        $orgId = $this->orgId();

        $standards = Standard::where('organization_id', $orgId)
            ->where('is_active', true)->orderBy('id')->get();

        $filterSections = $this->filterStructureStandard
            ? Section::where('organization_id', $orgId)
                ->where('standard_id', $this->filterStructureStandard)
                ->where('is_active', true)->orderBy('id')->get()
            : collect();

        $formSections = $this->structureStandardId
            ? Section::where('organization_id', $orgId)
                ->where('standard_id', $this->structureStandardId)
                ->where('is_active', true)->orderBy('id')->get()
            : collect();

        // Academic listing — one card per class+section, gated on a chosen class.
        $structureGroups = collect();
        if ($this->structureTab === 'academic' && $this->filterStructureStandard) {
            $rows = FeeStructureModel::with(['standard', 'section'])
                ->where('organization_id', $orgId)
                ->where('fee_type', 'academic')
                ->where('standard_id', $this->filterStructureStandard)
                ->when($this->filterStructureSection, fn ($q) => $q->where('section_id', $this->filterStructureSection))
                ->when($this->search, fn ($q) => $q->where('fee_name', 'like', "%{$this->search}%"))
                ->orderBy('section_id')->orderBy('id')
                ->get();

            $structureGroups = $rows
                ->groupBy(fn ($r) => $r->standard_id . '-' . ($r->section_id ?? 0))
                ->map(fn ($g) => [
                    'standard_id' => $g->first()->standard_id,
                    'section_id'  => $g->first()->section_id,
                    'class'       => $g->first()->standard->name ?? '—',
                    'section'     => $g->first()->section->name ?? 'All Sections',
                    'year'        => $g->first()->academic_year,
                    'rows'        => $g->values(),
                    'total'       => (float) $g->sum('amount'),
                ])
                ->values();
        }

        $routes = Transportation::where('organization_id', $orgId)
            ->orderBy('route_name')->get();

        $selectedRoute = $this->currentRoute();

        return [
            // Print / download links differ only by guard prefix.
            'pdfPrefix'       => $this->structureRoutePrefix(),
            'pdfOrg'          => $orgId,
            'standards'       => $standards,
            'filterSections'  => $filterSections,
            'formSections'    => $formSections,
            'structureGroups' => $structureGroups,
            'routes'          => $routes,
            'selectedRoute'   => $selectedRoute,
            'routeMonthRows'  => $this->routeMonthRows($selectedRoute),
        ];
    }
}
