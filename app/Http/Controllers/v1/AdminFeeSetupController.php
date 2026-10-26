<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Admin\FeeStructurePdfController;
use App\Livewire\Admin\AccountUsers as AccountUsersPage;
use App\Livewire\Admin\Fee as FeePage;
use App\Livewire\Admin\FeeStructure as FeeStructurePage;
use App\Livewire\Admin\PaymentQr as PaymentQrPage;
use App\Models\Admin\Fee\FeeConcession;
use App\Models\Admin\Fee\FeeCycle;
use App\Models\Admin\Fee\PaymentQrCode;
use App\Models\Admin\Transportation;
use App\Models\Student\Section;
use App\Models\Student\StudentDetail;
use App\Models\User;
use App\Http\Controllers\v1\Concerns\RunsPanelActions;
use Illuminate\Http\Request;
use Livewire\Component;

/**
 * The admin app's Fees, the sections the panel's Fee page has beside the ones
 * AdminFeeController serves: Fee Structure (with Add Dues and a route's
 * transport fee), Penalties, Fee Cycle (with its Calculator), Concession,
 * Payment QR and Accounts user.
 *
 * Like AdminFeeController it runs the panel components' own methods — their
 * reads, their saves, their checks — instead of a copy of them, so the app and
 * the panel can never disagree. An action answers as the panel's toast does:
 * its success line, or what stopped it (a field's validation message, an
 * error added to a field, or an error toast), with the field named the app's
 * way.
 *
 *   Structure    a class's fee heads by section; add, edit or delete a group;
 *                its PDF; a route's month-by-month transport fee, edited or
 *                deleted
 *   Dues         each student's Last Year Dues and earlier years' fees
 *   Penalties    a class's students with what they owe in penalty; a waiver
 *   Cycles       the installments by fee type + year; add (monthly, quarterly,
 *                custom), edit one, edit the whole cycle, delete; Calculator
 *   Concessions  the list with its filters; add and edit
 *   Payment QR   the school's QR and UPI ID; save and remove
 *   Accounts     the accounts logins: list, add, edit, switch on / off, delete
 */
class AdminFeeSetupController extends ApiController
{
    use RunsPanelActions;

    private const ADMIN_ROLES = ['admin', 'sub-admin'];

    private function guard(): array
    {
        [$user, $err] = $this->authUser();
        if ($err) return [null, $err];
        if ($err = $this->requireRole(self::ADMIN_ROLES)) return [null, $err];
        if (!$user->organization_id) {
            return [null, $this->error('No organization assigned to this account.', 403)];
        }
        return [$user, null];
    }

    /** A panel component, with the properties its method reads set. */
    private function page(string $class, array $props = []): Component
    {
        $page = new $class();
        foreach ($props as $k => $v) {
            $page->{$k} = $v;
        }
        return $page;
    }

    private function money($v): float
    {
        return round((float) $v, 2);
    }

    /** The academic year running now — April → March, e.g. "2026-27". */
    private function currentYear(): string
    {
        $now   = now();
        $start = $now->month >= 4 ? $now->year : $now->year - 1;
        return $start . '-' . substr((string) ($start + 1), -2);
    }

    private function student(int $orgId, $id): ?StudentDetail
    {
        return StudentDetail::where('organization_id', $orgId)->find($id);
    }

    // ══════════════════════════ FEE STRUCTURE ══════════════════════════

    /**
     * GET /admin/fees/structure?standard_id=&section_id=&search=&route_id=
     * Academic: a class's fee heads, one group per section (or All Sections).
     * Transport: the routes, and the one picked month by month — every month
     * at the route's fee, June free.
     */
    public function structure(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $page = $this->page(FeeStructurePage::class, [
            'structureTab'            => 'academic',
            'filterStructureStandard' => (string) $request->input('standard_id', ''),
            'filterStructureSection'  => (string) $request->input('section_id', ''),
            'search'                  => (string) $request->input('search', ''),
            'filterStructureRoute'    => (string) $request->input('route_id', ''),
        ]);
        $data = $this->callOn($page, 'feeStructureViewData');

        $route  = $data['selectedRoute'];
        $months = $data['routeMonthRows'];

        return $this->success([
            'groups' => collect($data['structureGroups'])->map(fn ($g) => [
                'standard_id' => (int) $g['standard_id'],
                'section_id'  => $g['section_id'] ? (int) $g['section_id'] : null,
                'class'       => $g['class'],
                'section'     => $g['section'],
                'year'        => $g['year'],
                'rows'        => collect($g['rows'])->map(fn ($r) => [
                    'id'       => $r->id,
                    'fee_name' => $r->fee_name,
                    'amount'   => $this->money($r->amount),
                ])->values(),
                'total'       => $this->money($g['total']),
            ])->values(),
            'routes' => collect($data['routes'])->map(fn ($r) => [
                'id'          => $r->id,
                'name'        => $r->route_name,
                'monthly_fee' => $this->money($r->monthly_fee),
                'is_active'   => (bool) $r->is_active,
            ])->values(),
            'route'  => $route ? [
                'id'          => $route->id,
                'name'        => $route->route_name,
                'monthly_fee' => $this->money($route->monthly_fee),
                'pickup_time' => $route->pickup_time,
                'is_active'   => (bool) $route->is_active,
                'driver'      => $route->driver?->user?->name,
                'months'      => array_map(fn ($m) => [
                    'month'   => $m['month'],
                    'free'    => (bool) $m['free'],
                    'amount'  => $this->money($m['amount']),
                    'running' => $this->money($m['running']),
                ], $months),
                'annual'          => $this->money(collect($months)->sum('amount')),
                'billable_months' => collect($months)->where('free', false)->count(),
            ] : null,
            'current_year' => $this->currentYear(),
        ], 'Fee structure fetched.');
    }

    /** The form's rows, as the panel holds them. */
    private function feeRows(Request $request): array
    {
        return collect((array) $request->input('rows', []))
            ->map(fn ($r) => ['name' => trim((string) ($r['name'] ?? '')), 'amount' => trim((string) ($r['amount'] ?? ''))])
            ->values()->all();
    }

    private const STRUCTURE_FIELDS = [
        'structureStandardId' => 'standard_id',
        'academicYear'        => 'academic_year',
        'feeRows'             => 'rows',
        'feeRows.'            => 'rows.',
    ];

    /** POST /admin/fees/structure {standard_id, section_ids[], academic_year, rows:[{name, amount}]} — Add Fee Structure. */
    public function addStructure(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = (int) $user->organization_id;

        // Only this school's sections of the class picked.
        $sectionIds = Section::where('organization_id', $orgId)
            ->where('standard_id', (int) $request->input('standard_id'))
            ->whereIn('id', array_map('intval', (array) $request->input('section_ids', [])))
            ->pluck('id')->map(fn ($id) => (int) $id)->all();

        $page = $this->page(FeeStructurePage::class, [
            'structureStandardId' => (string) $request->input('standard_id', ''),
            'structureSectionIds' => $sectionIds,
            'academicYear'        => trim((string) $request->input('academic_year', '')),
            'feeRows'             => $this->feeRows($request),
        ]);

        return $this->act($page, 'saveStructure', [], self::STRUCTURE_FIELDS, 'Fee structure added!');
    }

    /**
     * POST /admin/fees/structure/group {standard_id, section_id?, academic_year, rows}
     * Edit: the class + section group is replaced wholesale, as the panel's Edit does.
     */
    public function editStructure(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = (int) $user->organization_id;

        $standardId = (int) $request->input('standard_id');
        $sectionId  = $request->filled('section_id') ? (int) $request->input('section_id') : null;

        $exists = \App\Models\Admin\Fee\FeeStructure::where('organization_id', $orgId)
            ->where('fee_type', 'academic')->where('standard_id', $standardId)
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId), fn ($q) => $q->whereNull('section_id'))
            ->exists();
        if (!$exists) return $this->error('Fee structure not found.', 404);

        $page = $this->page(FeeStructurePage::class, [
            'editingGroup'        => true,
            'editGroupStandardId' => $standardId,
            'editGroupSectionId'  => $sectionId,
            'structureStandardId' => (string) $standardId,
            'structureSectionIds' => $sectionId ? [$sectionId] : [],
            'academicYear'        => trim((string) $request->input('academic_year', '')),
            'feeRows'             => $this->feeRows($request),
        ]);

        return $this->act($page, 'saveStructure', [], self::STRUCTURE_FIELDS, 'Fee structure updated!');
    }

    /** DELETE /admin/fees/structure/group?standard_id=&section_id= — every head of the group. */
    public function deleteStructure(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $page = $this->page(FeeStructurePage::class, [
            'pendingDeleteGroup' => [
                'standard_id' => (int) $request->input('standard_id'),
                'section_id'  => $request->filled('section_id') ? (int) $request->input('section_id') : null,
            ],
        ]);

        return $this->act($page, 'doDeleteGroup', [], [], 'Fee structure deleted!');
    }

    /** GET /admin/fees/structure/pdf?standard_id=&section_id= — the panel's Download. */
    public function structurePdf(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $request->merge([
            'standard' => $request->input('standard_id'),
            'section'  => $request->input('section_id'),
            'download' => 1,
        ]);

        return app(FeeStructurePdfController::class)->show($request, $user->organization_id);
    }

    /** GET /admin/fees/structure/transport-pdf?route_id= — a route's month-by-month fee. */
    public function transportPdf(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        if (!Transportation::where('organization_id', $user->organization_id)->whereKey($request->input('route_id'))->exists()) {
            return $this->error('Route not found.', 404);
        }
        $request->merge(['route' => $request->input('route_id'), 'download' => 1]);

        return app(FeeStructurePdfController::class)->transport($request, $user->organization_id);
    }

    /** POST /admin/fees/routes/{id} {name, monthly_fee, pickup_time?, is_active} — a route's fee. */
    public function saveRoute(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        if (!Transportation::where('organization_id', $user->organization_id)->whereKey($id)->exists()) {
            return $this->error('Route not found.', 404);
        }

        $page = $this->page(FeeStructurePage::class, [
            'editRouteId'     => (int) $id,
            'routeName'       => trim((string) $request->input('name', '')),
            'routeMonthlyFee' => trim((string) $request->input('monthly_fee', '')),
            'routePickupTime' => trim((string) $request->input('pickup_time', '')),
            'routeIsActive'   => $request->boolean('is_active', true),
        ]);

        return $this->act($page, 'saveRoute', [], [
            'routeName'       => 'name',
            'routeMonthlyFee' => 'monthly_fee',
            'routePickupTime' => 'pickup_time',
        ], 'Route fee updated!');
    }

    /** DELETE /admin/fees/routes/{id} — refused while students ride it. */
    public function deleteRoute($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        if (!Transportation::where('organization_id', $user->organization_id)->whereKey($id)->exists()) {
            return $this->error('Route not found.', 404);
        }

        $page = $this->page(FeeStructurePage::class, ['pendingDeleteRouteId' => (int) $id]);

        return $this->act($page, 'doDeleteRoute', [], [], 'Route deleted!');
    }

    // ══════════════════════════ ADD DUES ══════════════════════════

    /**
     * GET /admin/fees/dues?standard_id=&section_id=
     * The class's students (or a section's), each with their Last Year Dues and
     * the earlier years' fees saved for them.
     */
    public function dues(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $page = $this->page(FeeStructurePage::class, ['duesStandardId' => (string) $request->input('standard_id', '')]);
        $this->callOn($page, 'updatedDuesStandardId');
        $page->duesSectionId = (string) $request->input('section_id', '');

        $students = $page->duesStandardId ? $page->duesStudents : collect();
        $sections = $page->duesStandardId ? $page->duesSections : collect();
        $names    = Section::whereIn('id', $students->pluck('section_id')->filter()->unique())->pluck('name', 'id');

        return $this->success([
            'sections' => $sections->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values(),
            'students' => $students->map(fn ($s) => [
                'id'           => $s->id,
                'name'         => $s->full_name ?: ($s->user?->name ?? '—'),
                'admission_no' => $s->admission_no,
                'roll_no'      => $s->roll_no,
                'section'      => $names[$s->section_id] ?? null,
                'amount'       => (string) ($page->duesAmounts['s' . $s->id] ?? ''),
                'extras'       => array_values(array_map(fn ($e) => [
                    'id'     => $e['id'],
                    'name'   => (string) $e['name'],
                    'amount' => (string) $e['amount'],
                ], $page->duesExtras['s' . $s->id] ?? [])),
            ])->values(),
        ], 'Dues fetched.');
    }

    /**
     * POST /admin/fees/dues {standard_id, amounts:{student id: amount}, extras:{student id: [{id?, name, amount}]}}
     * The panel's Save: the class's dues as they stand, with what was sent laid
     * over them — a box emptied clears that student's dues.
     */
    public function saveDues(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $page = $this->page(FeeStructurePage::class, ['duesStandardId' => (string) $request->input('standard_id', '')]);
        if ($page->duesStandardId !== '') {
            $this->callOn($page, 'updatedDuesStandardId');
        }

        $amounts = $page->duesAmounts;
        foreach ((array) $request->input('amounts', []) as $id => $amount) {
            $amounts['s' . (int) $id] = trim((string) $amount);
        }
        $extras = $page->duesExtras;
        foreach ((array) $request->input('extras', []) as $id => $rows) {
            $extras['s' . (int) $id] = array_values(array_map(fn ($e) => [
                'id'     => !empty($e['id']) ? (int) $e['id'] : null,
                'name'   => trim((string) ($e['name'] ?? '')),
                'amount' => trim((string) ($e['amount'] ?? '')),
            ], (array) $rows));
        }
        $page->duesAmounts = $amounts;
        $page->duesExtras  = $extras;

        return $this->act($page, 'saveDues', [], [
            'duesStandardId' => 'standard_id',
            'duesAmounts.s'  => 'amounts.',
            'duesExtras.s'   => 'extras.',
        ], 'Dues saved.');
    }

    // ══════════════════════════ PENALTIES ══════════════════════════

    /**
     * GET /admin/fees/penalties/class?standard_id=&section_id=
     * By Class: each student with what they still owe in penalty, academic and
     * transport. (By Student is the student's ledger: /admin/fees/students/{id}.)
     */
    public function penaltyClass(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $page = $this->page(FeePage::class, [
            'penaltyClassStandardId' => (string) $request->input('standard_id', ''),
            'penaltyClassSectionId'  => (string) $request->input('section_id', ''),
        ]);
        if ($page->penaltyClassStandardId !== '') {
            $this->callOn($page, 'loadPenaltyClassList');
        }

        $rows = collect($page->penaltyClassList);

        return $this->success([
            'students' => $rows->values(),
            'total'    => $this->money($rows->sum('total_penalty')),
        ], 'Penalties fetched.');
    }

    /** POST /admin/fees/penalties/{studentId}/waiver {fee_type, cycle_id, amount, mode, collected_by} — the Waiver panel. */
    public function waivePenalty(Request $request, $studentId)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = (int) $user->organization_id;

        if (!$this->student($orgId, $studentId)) return $this->error('Student not found.', 404);
        if ($request->filled('cycle_id') && !FeeCycle::forOrg($orgId)->whereKey($request->input('cycle_id'))->exists()) {
            return $this->error('Pick the installment the waiver is for.', 422, ['cycle_id' => ['Pick the installment the waiver is for.']]);
        }

        $page = $this->page(FeePage::class, [
            'penaltyViewStudentId' => (string) $studentId,
            'waiverAmount'         => trim((string) $request->input('amount', '')),
            'waiverFeeType'        => (string) $request->input('fee_type', 'academic'),
            'waiverCycleId'        => (string) $request->input('cycle_id', ''),
            'waiverMode'           => (string) $request->input('mode', 'cash'),
            'waiverCollectedBy'    => trim((string) $request->input('collected_by', '')),
        ]);

        return $this->act($page, 'savePenaltyWaiver', [], [
            'waiverAmount'      => 'amount',
            'waiverFeeType'     => 'fee_type',
            'waiverCycleId'     => 'cycle_id',
            'waiverMode'        => 'mode',
            'waiverCollectedBy' => 'collected_by',
        ], 'Penalty waived successfully!');
    }

    // ══════════════════════════ FEE CYCLE ══════════════════════════

    private function cycleRow(FeePage $page, FeeCycle $c, string $kind): array
    {
        return [
            'id'              => $c->id,
            'is_token'        => (bool) $c->is_token,
            'serial'          => (int) $c->payment_serial,
            'label'           => $this->callOn($page, 'cycleRowLabel', $c, $kind),
            'span'            => $this->callOn($page, 'cycleSpanLabel', $c),
            'fee_type'        => $c->fee_type,
            'year'            => $c->academic_year,
            'due_date'        => optional($c->due_date)->toDateString(),
            'start_date'      => optional($c->start_date)->toDateString(),
            'end_date'        => optional($c->end_date)->toDateString(),
            'fee_percent'     => (float) $c->fee_percent,
            'amount'          => $this->money($c->amount),
            'penalty_per_day' => $this->money($c->penalty_per_day),
        ];
    }

    /** GET /admin/fees/cycles — one group per fee type + academic year, the registration fee first. */
    public function cycles()
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $page = $this->page(FeePage::class);
        $data = $this->callOn($page, 'feeCycleViewData');

        return $this->success([
            'groups' => collect($data['cycleGroups'])->map(fn ($g) => [
                'key'      => $g['key'],
                'fee_type' => $g['fee_type'],
                'year'     => $g['year'],
                'kind'     => $g['kind'],
                'count'    => (int) $g['count'],
                'percent'  => (float) $g['percent'],
                'token'    => $g['token'] ? $this->cycleRow($page, $g['token'], $g['kind']) : null,
                'rows'     => collect($g['rows'])->map(fn ($c) => $this->cycleRow($page, $c, $g['kind']))->values(),
            ])->values(),
            'counts' => [
                'total'     => (int) $data['totalCycles'],
                'academic'  => (int) $data['academicCycles'],
                'transport' => (int) $data['transportCycles'],
            ],
            'current_year' => $this->currentYear(),
        ], 'Fee cycles fetched.');
    }

    /** GET /admin/fees/cycles/{id} — one installment (or the registration fee), and the registration fee of its cycle. */
    public function cycle($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = (int) $user->organization_id;

        $c = FeeCycle::forOrg($orgId)->find($id);
        if (!$c) return $this->error('Installment not found.', 404);

        $page  = $this->page(FeePage::class);
        $siblings = FeeCycle::forOrg($orgId)->where('fee_type', $c->fee_type)->where('academic_year', $c->academic_year)->get();
        $kind  = $this->callOn($page, 'detectCycleKind', $siblings);
        $token = $siblings->firstWhere('is_token', true);

        return $this->success([
            'cycle'    => $this->cycleRow($page, $c, $kind),
            'token'    => $token ? $this->cycleRow($page, $token, $kind) : null,
            'siblings' => $siblings->where('is_token', false)->sortBy('payment_serial')
                ->map(fn ($s) => $this->cycleRow($page, $s, $kind))->values(),
        ], 'Installment fetched.');
    }

    /** GET /admin/fees/cycles/{id}/preview?percent= — what the other installments become once this one's % changes. */
    public function cyclePreview(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $c = FeeCycle::forOrg($user->organization_id)->find($id);
        if (!$c) return $this->error('Installment not found.', 404);

        $page = $this->page(FeePage::class, [
            'editCycleId'     => $c->id,
            'cycleFeeType'    => $c->fee_type,
            'cycleYear'       => $c->academic_year,
            'cycleFeePercent' => (string) $request->input('percent', ''),
        ]);
        $this->callOn($page, 'buildCycleSiblingPreview');

        return $this->success(['siblings' => $page->cycleSiblingPreview], 'Preview fetched.');
    }

    private const CYCLE_FIELDS = [
        'cycleFeeType'       => 'fee_type',
        'cycleYear'          => 'year',
        'cycleMonthlyDueDay' => 'monthly_due_day',
        'cycleSerial'        => 'serial',
        'cycleDueDate'       => 'due_date',
        'cycleStartDate'     => 'start_date',
        'cycleEndDate'       => 'end_date',
        'cycleFeePercent'    => 'fee_percent',
        'cyclePenaltyPerDay' => 'penalty_per_day',
    ];

    /** The registration fee asks for a due date with its amount, as the panel does. */
    private function tokenError(Request $request)
    {
        if ((float) $request->input('token_amount', 0) > 0 && !$request->filled('token_due_date')) {
            return $this->error('Set a due date for the registration fee, or leave its amount blank.', 422, [
                'token_due_date' => ['Set a due date for the registration fee, or leave its amount blank.'],
            ]);
        }
        return null;
    }

    /**
     * POST /admin/fees/cycles {mode: monthly|quarterly|custom, monthly_due_day?, penalty_per_day?,
     *   rows?:[{serial, start_date, end_date, due_date, fee_percent, penalty_per_day}],
     *   token_amount?, token_due_date?, token_penalty_per_day?}
     * Add Fee Cycle: this year's academic fee, split as picked.
     */
    public function addCycles(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        if (!in_array($request->input('mode'), ['monthly', 'quarterly', 'custom'], true)) {
            return $this->error('Pick the cycle type.', 422, ['mode' => ['Pick the cycle type.']]);
        }
        if ($err = $this->tokenError($request)) return $err;

        $rows = array_values(array_map(fn ($r) => [
            'serial'          => (int) ($r['serial'] ?? 0),
            'start_date'      => (string) ($r['start_date'] ?? ''),
            'end_date'        => (string) ($r['end_date'] ?? ''),
            'due_date'        => (string) ($r['due_date'] ?? ''),
            'fee_percent'     => trim((string) ($r['fee_percent'] ?? '')),
            'penalty_per_day' => trim((string) ($r['penalty_per_day'] ?? '0')),
        ], (array) $request->input('rows', [])));

        $page = $this->page(FeePage::class, [
            'cycleMode'          => (string) $request->input('mode'),
            'cycleFeeType'       => 'academic',
            'cycleYear'          => $this->currentYear(),
            'cycleMonthlyDueDay' => (int) $request->input('monthly_due_day', 10),
            'cyclePenaltyPerDay' => trim((string) $request->input('penalty_per_day', '0')) ?: '0',
            'customRows'         => $rows,
            'tokenFeeAmount'     => trim((string) $request->input('token_amount', '')),
            'tokenDueDate'       => (string) $request->input('token_due_date', ''),
            'tokenPenaltyPerDay' => trim((string) $request->input('token_penalty_per_day', '0')) ?: '0',
        ]);

        return $this->act($page, 'saveCycle', [], self::CYCLE_FIELDS, 'Fee cycle saved!');
    }

    /**
     * POST /admin/fees/cycles/{id}
     *   an installment: {serial, due_date, start_date?, end_date?, fee_percent, penalty_per_day, token_*?}
     *   the registration fee: {amount, due_date, penalty_per_day}
     * Editing one installment re-balances the others to 100%, as the panel does.
     */
    public function saveCycle(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = (int) $user->organization_id;

        $c = FeeCycle::forOrg($orgId)->find($id);
        if (!$c) return $this->error('Installment not found.', 404);

        if ($c->is_token) {
            $page = $this->page(FeePage::class, [
                'editingToken'       => true,
                'editCycleId'        => $c->id,
                'cycleFeeType'       => $c->fee_type,
                'cycleYear'          => $c->academic_year,
                'tokenFeeAmount'     => trim((string) $request->input('amount', '')),
                'tokenDueDate'       => (string) $request->input('due_date', ''),
                'tokenPenaltyPerDay' => trim((string) $request->input('penalty_per_day', '0')) ?: '0',
            ]);

            return $this->act($page, 'saveCycle', [], [
                'tokenFeeAmount'     => 'amount',
                'tokenDueDate'       => 'due_date',
                'tokenPenaltyPerDay' => 'penalty_per_day',
            ], 'Registration fee updated!');
        }

        if ($err = $this->tokenError($request)) return $err;

        $page = $this->page(FeePage::class, [
            'cycleMode'          => 'custom',
            'editCycleId'        => $c->id,
            'cycleFeeType'       => $c->fee_type,
            'cycleYear'          => $c->academic_year,
            'cycleSerial'        => (int) $request->input('serial', $c->payment_serial),
            'cycleDueDate'       => (string) $request->input('due_date', ''),
            'cycleStartDate'     => (string) $request->input('start_date', ''),
            'cycleEndDate'       => (string) $request->input('end_date', ''),
            'cycleFeePercent'    => trim((string) $request->input('fee_percent', '')),
            'cyclePenaltyPerDay' => trim((string) $request->input('penalty_per_day', '0')) ?: '0',
        ]);
        // The registration fee rides along, as in the panel's form: what was
        // sent, else what is saved.
        $this->callOn($page, 'loadTokenForCurrentCycle');
        if ($request->has('token_amount')) {
            $page->tokenFeeAmount     = trim((string) $request->input('token_amount', ''));
            $page->tokenDueDate       = (string) $request->input('token_due_date', '');
            $page->tokenPenaltyPerDay = trim((string) $request->input('token_penalty_per_day', '0')) ?: '0';
        }
        $this->callOn($page, 'buildCycleSiblingPreview');

        return $this->act($page, 'saveCycle', [], self::CYCLE_FIELDS, 'Installment updated!');
    }

    /** DELETE /admin/fees/cycles/{id} */
    public function deleteCycle($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        if (!FeeCycle::forOrg($user->organization_id)->whereKey($id)->exists()) {
            return $this->error('Installment not found.', 404);
        }

        $page = $this->page(FeePage::class, ['pendingDeleteCycleId' => (int) $id]);

        return $this->act($page, 'doDeleteCycle', [], [], 'Installment deleted!');
    }

    /** GET /admin/fees/cycles/bulk?fee_type=&year= — the whole cycle's editor: every installment, labelled as built. */
    public function cycleBulk(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $page = $this->page(FeePage::class);
        $this->callOn($page, 'openCycleEdit', (string) $request->input('fee_type', ''), (string) $request->input('year', ''));
        [, $bad] = $this->toasts($page);
        if ($bad !== null) return $this->error($bad, 422);

        return $this->success([
            'fee_type' => $page->editCycleFeeType,
            'year'     => $page->editCycleYear,
            'kind'     => $page->editCycleKind,
            'rows'     => array_values($page->editRows),
        ], 'Fee cycle fetched.');
    }

    /** POST /admin/fees/cycles/bulk {fee_type, year, rows:[{id, start_date, end_date, due_date, fee_percent, penalty_per_day}]} */
    public function saveCycleBulk(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $page = $this->page(FeePage::class);
        $this->callOn($page, 'openCycleEdit', (string) $request->input('fee_type', ''), (string) $request->input('year', ''));
        [, $bad] = $this->toasts($page);
        if ($bad !== null) return $this->error($bad, 422);

        $sent = collect((array) $request->input('rows', []))->keyBy(fn ($r) => (int) ($r['id'] ?? 0));
        $page->editRows = array_map(function ($row) use ($sent) {
            $r = $sent->get((int) $row['id']);
            if (!$r) return $row;
            foreach (['start_date', 'end_date', 'due_date', 'fee_percent', 'penalty_per_day'] as $k) {
                if (array_key_exists($k, $r)) $row[$k] = trim((string) ($r[$k] ?? ''));
            }
            return $row;
        }, $page->editRows);

        return $this->act($page, 'saveCycleEdit', [], [], 'Installments updated.');
    }

    /** GET /admin/fees/cycles/csv?fee_type=&year= — the panel's Download (CSV). */
    public function cycleCsv(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        return $this->callOn($this->page(FeePage::class), 'downloadCycle', (string) $request->input('fee_type', ''), (string) $request->input('year', ''));
    }

    /**
     * GET /admin/fees/cycles/calculator?standard_id=&section_id=
     * Every student of the class (or section) as their View Fee works it out,
     * each installment's total for the class, and the summary.
     */
    public function calculator(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = (int) $user->organization_id;

        $page = $this->page(FeePage::class, [
            'calcStandardId' => (string) $request->input('standard_id', ''),
            'calcSectionId'  => (string) $request->input('section_id', ''),
        ]);
        [$rows, $students, $summary] = $this->callOn($page, 'calculatorFor', $orgId);

        $installments = FeeCycle::forOrg($orgId)->where('fee_type', 'academic')->where('is_token', false)
            ->orderBy('payment_serial')->pluck('payment_serial')->unique()->values();

        return $this->success([
            'rows'         => $rows,
            'students'     => $students,
            'summary'      => $summary ?: null,
            'installments' => $installments,
        ], 'Calculator fetched.');
    }

    // ══════════════════════════ CONCESSION ══════════════════════════

    private function concessionRow(FeeConcession $c): array
    {
        $st = $c->studentDetail;

        return [
            'id'          => $c->id,
            'student'     => $st ? [
                'id'           => $st->id,
                'name'         => $st->full_name ?: ($st->user?->name ?? '—'),
                'father_name'  => $st->father_name,
                'admission_no' => $st->admission_no,
            ] : null,
            'standard_id' => $c->standard_id,
            'section_id'  => $c->section_id,
            'class'       => $c->standard?->name,
            'section'     => $c->section?->name,
            'fee_type'    => $c->fee_type,
            'applies_to'  => FeeConcession::appliesToLabel($c->fee_type),
            'type'        => $c->concession_type,
            'value'       => (float) $c->value,
            'reason'      => $c->reason,
            'year'        => $c->academic_year,
            'granted'     => $c->created_at?->toDateString(),
        ];
    }

    /** GET /admin/fees/concessions?standard_id=&section_id=&student_id=&date=&page= */
    public function concessions(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $request->merge(['concPage' => max(1, (int) $request->input('page', 1))]);
        $page = $this->page(FeePage::class, [
            'filterConcStandardId' => (string) $request->input('standard_id', ''),
            'filterConcSectionId'  => (string) $request->input('section_id', ''),
            'filterConcStudentId'  => (string) $request->input('student_id', ''),
            'filterConcDate'       => (string) $request->input('date', ''),
            'concPerPage'          => 20,
        ]);
        $data = $this->callOn($page, 'feeConcessionViewData');
        $list = $data['concessions'];

        return $this->success([
            'concessions' => collect($list->items())->map(fn ($c) => $this->concessionRow($c))->values(),
            'pagination'  => $this->paginationMeta($list),
            'applies_to'  => collect(FeeConcession::APPLIES_TO)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
            'current_year' => $this->currentYear(),
        ], 'Concessions fetched.');
    }

    private const CONCESSION_FIELDS = [
        'concStudentId' => 'student_id',
        'concType'      => 'type',
        'concValue'     => 'value',
        'concFeeType'   => 'fee_type',
        'concReason'    => 'reason',
        'concYear'      => 'year',
    ];

    /** POST /admin/fees/concessions and /admin/fees/concessions/{id} {student_id, type, value, fee_type, reason?, year} */
    public function saveConcession(Request $request, $id = null)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = (int) $user->organization_id;

        if ($id && !FeeConcession::where('organization_id', $orgId)->whereKey($id)->exists()) {
            return $this->error('Concession not found.', 404);
        }
        if ($request->filled('student_id') && !$this->student($orgId, $request->input('student_id'))) {
            return $this->error('Select a student.', 422, ['student_id' => ['Select a student.']]);
        }

        $page = $this->page(FeePage::class, [
            'editConcessionId' => $id ? (int) $id : null,
            'concStudentId'    => (string) $request->input('student_id', ''),
            'concType'         => (string) $request->input('type', 'amount'),
            'concValue'        => trim((string) $request->input('value', '')),
            'concFeeType'      => (string) $request->input('fee_type', 'academic'),
            'concReason'       => trim((string) $request->input('reason', '')),
            'concYear'         => trim((string) $request->input('year', '')) ?: $this->currentYear(),
        ]);

        return $this->act($page, 'saveConcession', [], self::CONCESSION_FIELDS, $id ? 'Concession updated successfully!' : 'Concession added successfully!');
    }

    // ══════════════════════════ PAYMENT QR ══════════════════════════

    private function qrShape(int $orgId): array
    {
        $qr = PaymentQrCode::forOrg($orgId);

        return [
            'qr' => $qr ? [
                'image_url'  => $qr->imageUrl(),
                'upi_id'     => $qr->upi_id,
                'payee_name' => $qr->payee_name,
                'updated_at' => $qr->updated_at?->toIso8601String(),
            ] : null,
            'school_name' => optional(\App\Models\Organization::find($orgId))->name,
        ];
    }

    /** GET /admin/fees/payment-qr — the school's QR and UPI ID, as students see them in the app. */
    public function paymentQr()
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        return $this->success($this->qrShape((int) $user->organization_id), 'Payment QR fetched.');
    }

    /** POST /admin/fees/payment-qr (multipart) {image?, upi_id?, payee_name?} — add or change it. */
    public function savePaymentQr(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $page = $this->page(PaymentQrPage::class, [
            'upiId'     => trim((string) $request->input('upi_id', '')),
            'payeeName' => trim((string) $request->input('payee_name', '')),
        ]);
        $page->qrImage = $request->file('image');

        return $this->act($page, 'save', [], [
            'qrImage'   => 'image',
            'upiId'     => 'upi_id',
            'payeeName' => 'payee_name',
        ], 'Payment QR saved.', fn () => $this->qrShape((int) $user->organization_id));
    }

    /** DELETE /admin/fees/payment-qr */
    public function deletePaymentQr()
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        return $this->act($this->page(PaymentQrPage::class), 'removeQr', [], [], 'Payment QR removed');
    }

    // ══════════════════════════ ACCOUNTS USER ══════════════════════════

    private function accountRow(User $u): array
    {
        $su = $u->schoolUser;

        return [
            'id'               => $u->id,
            'name'             => $u->name,
            'email'            => $u->email,
            'mobile_number'    => $u->mobile_number,
            'alternate_mobile' => $su?->alternate_mobile,
            'designation'      => $su?->designation,
            'department'       => $su?->department,
            'employee_id'      => $su?->employee_id,
            'address'          => $su?->address,
            'image'            => $su?->image,
            'is_active'        => (bool) $u->is_active,
            'created_at'       => $u->created_at?->toDateString(),
        ];
    }

    private function accountUser(int $orgId, $id): ?User
    {
        return User::with('schoolUser')->where('organization_id', $orgId)->where('role', 'accounts')->find($id);
    }

    /** GET /admin/fees/account-users?search=&status=1|0&page= — the accounts logins, with the header's counts. */
    public function accountUsers(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = (int) $user->organization_id;

        $base   = User::where('organization_id', $orgId)->where('role', 'accounts');
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', '');

        $users = (clone $base)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile_number', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($q) => $q->where('is_active', (bool) $status))
            ->with('schoolUser')
            ->latest()
            ->paginate(20);

        return $this->success([
            'users'      => collect($users->items())->map(fn ($u) => $this->accountRow($u))->values(),
            'pagination' => $this->paginationMeta($users),
            'stats'      => [
                'total'    => (clone $base)->count(),
                'active'   => (clone $base)->where('is_active', true)->count(),
                'inactive' => (clone $base)->where('is_active', false)->count(),
            ],
        ], 'Account users fetched.');
    }

    /** GET /admin/fees/account-users/{id} */
    public function accountUserShow($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $u = $this->accountUser((int) $user->organization_id, $id);
        if (!$u) return $this->error('User not found.', 404);

        return $this->success($this->accountRow($u), 'User fetched.');
    }

    /**
     * POST /admin/fees/account-users and /admin/fees/account-users/{id} (multipart)
     * {name, email, password?, mobile_number?, alternate_mobile?, designation?, department?, employee_id?, address?, image?}
     * The panel's Add / Edit: a new login is emailed its credentials.
     */
    public function saveAccountUser(Request $request, $id = null)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = (int) $user->organization_id;

        $editing = $id ? $this->accountUser($orgId, $id) : null;
        if ($id && !$editing) return $this->error('User not found.', 404);

        $page = $this->page(AccountUsersPage::class, [
            'isEditing'        => (bool) $editing,
            'editUserId'       => $editing?->id,
            'existingImage'    => $editing?->schoolUser?->image,
            'name'             => trim((string) $request->input('name', '')),
            'email'            => trim((string) $request->input('email', '')),
            'password'         => (string) $request->input('password', ''),
            'mobile_number'    => trim((string) $request->input('mobile_number', '')),
            'alternate_mobile' => trim((string) $request->input('alternate_mobile', '')),
            'designation'      => trim((string) $request->input('designation', '')),
            'department'       => trim((string) $request->input('department', '')) ?: 'accounts',
            'employee_id'      => trim((string) $request->input('employee_id', '')),
            'address'          => trim((string) $request->input('address', '')),
        ]);
        $page->userImage = $request->file('image');

        return $this->act($page, 'saveUser', [], ['userImage' => 'image'], $editing ? 'User Updated' : 'User Created');
    }

    /** POST /admin/fees/account-users/{id}/toggle — switch the login on or off. */
    public function toggleAccountUser($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $u = $this->accountUser((int) $user->organization_id, $id);
        if (!$u) return $this->error('User not found.', 404);

        return $this->act($this->page(AccountUsersPage::class), 'toggleActive', [$u->id], [], 'Status Updated',
            fn () => ['is_active' => (bool) $u->fresh()->is_active]);
    }

    /** POST /admin/fees/account-users/{id}/remove-image */
    public function removeAccountUserImage($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $u = $this->accountUser((int) $user->organization_id, $id);
        if (!$u) return $this->error('User not found.', 404);

        $page = $this->page(AccountUsersPage::class, [
            'isEditing'     => true,
            'editUserId'    => $u->id,
            'existingImage' => $u->schoolUser?->image,
        ]);

        return $this->act($page, 'removeImage', [], [], 'Photo removed.');
    }

    /** DELETE /admin/fees/account-users/{id} */
    public function deleteAccountUser($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $u = $this->accountUser((int) $user->organization_id, $id);
        if (!$u) return $this->error('User not found.', 404);

        return $this->act($this->page(AccountUsersPage::class, ['deleteTargetId' => $u->id]), 'executeDelete', [], [], 'User Deleted');
    }
}
