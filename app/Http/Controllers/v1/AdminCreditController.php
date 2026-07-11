<?php

namespace App\Http\Controllers\v1;

use App\Models\SuperAdmin\CreditPolicy;
use App\Models\SuperAdmin\CreditQuery;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * School-admin Credit module for the mobile app.
 *
 * Mirrors app/Livewire/Admin/Credit.php — the org raises credit "queries" to the
 * super-admin, tracks their status, and reads the active credit policies. Only
 * pending queries can be edited or deleted. Org-scoped, role-gated to admin /
 * sub-admin.
 */
class AdminCreditController extends ApiController
{
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

    // ══════════════════════════ STATS ══════════════════════════

    /** GET /admin/credit/stats — status counts + active policies. */
    public function stats()
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        return $this->success([
            'stats' => [
                'total'      => CreditQuery::forOrg($orgId)->count(),
                'pending'    => CreditQuery::forOrg($orgId)->pending()->count(),
                'processing' => CreditQuery::forOrg($orgId)->processing()->count(),
                'approved'   => CreditQuery::forOrg($orgId)->approved()->count(),
                'denied'     => CreditQuery::forOrg($orgId)->denied()->count(),
            ],
            'policies' => CreditPolicy::where('is_active', true)->latest()->get()
                ->map(fn ($p) => [
                    'id'       => $p->id,
                    'title'    => $p->title,
                    'content'  => $p->content,
                    'image'    => $p->image,
                    'link'     => $p->link,
                    'document' => $p->document,
                ])->values(),
        ], 'Credit stats fetched.');
    }

    // ══════════════════════════ LIST ══════════════════════════

    /** GET /admin/credit?search=&status=&per_page=&page= */
    public function index(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $query = CreditQuery::forOrg($user->organization_id);

        if ($s = $request->input('search')) {
            $query->where(fn ($q) => $q->where('heading', 'like', "%{$s}%")
                ->orWhere('reason', 'like', "%{$s}%"));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $paginator = $query->latest()->paginate((int) $request->input('per_page', 10));
        $items = collect($paginator->items())->map(fn ($q) => $this->present($q));

        return $this->paginated($items, $this->paginationMeta($paginator), 'Credit queries fetched.');
    }

    /** GET /admin/credit/{id} */
    public function show($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $query = CreditQuery::forOrg($user->organization_id)->find($id);
        if (!$query) return $this->error('Credit query not found.', 404);

        return $this->success(['query' => $this->present($query)], 'Credit query fetched.');
    }

    private function present(CreditQuery $q): array
    {
        return [
            'id'                => $q->id,
            'amount'            => (float) $q->amount,
            'start_date'        => optional($q->start_date)->format('Y-m-d'),
            'end_date'          => optional($q->end_date)->format('Y-m-d'),
            'start_label'       => optional($q->start_date)->format('d M Y'),
            'end_label'         => optional($q->end_date)->format('d M Y'),
            'heading'           => $q->heading,
            'reason'            => $q->reason,
            'status'            => $q->status,
            'admin_remark'      => $q->admin_remark,
            'penalties_per_day' => $q->penalties_per_day !== null ? (float) $q->penalties_per_day : null,
            'created_at'        => $q->created_at?->toIso8601String(),
            'created_label'     => $q->created_at?->format('d M Y'),
            'editable'          => $q->status === 'pending',
        ];
    }

    // ══════════════════════════ CREATE / UPDATE ══════════════════════════

    /** POST /admin/credit */
    public function store(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        if ($err = $this->validateWith($request, [
            'amount'     => 'required|numeric|min:1',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
            'heading'    => 'required|string|max:255',
            'reason'     => 'required|string|min:10|max:2000',
        ])) return $err;

        $query = CreditQuery::create([
            'organization_id' => $user->organization_id,
            'amount'          => $request->amount,
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
            'heading'         => $request->heading,
            'reason'          => $request->reason,
            'status'          => 'pending',
        ]);

        return $this->success(['query' => $this->present($query)], 'Credit request submitted successfully.', 201);
    }

    /** POST /admin/credit/{id} — only pending queries can be edited. */
    public function update(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $query = CreditQuery::forOrg($user->organization_id)->find($id);
        if (!$query) return $this->error('Credit query not found.', 404);
        if ($query->status !== 'pending') {
            return $this->error('Only pending queries can be edited.', 422);
        }

        if ($err = $this->validateWith($request, [
            'amount'     => 'required|numeric|min:1',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
            'heading'    => 'required|string|max:255',
            'reason'     => 'required|string|min:10|max:2000',
        ])) return $err;

        $query->update([
            'amount'     => $request->amount,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'heading'    => $request->heading,
            'reason'     => $request->reason,
        ]);

        return $this->success(['query' => $this->present($query->fresh())], 'Credit query updated successfully.');
    }

    /** DELETE /admin/credit/{id} */
    public function destroy($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $query = CreditQuery::forOrg($user->organization_id)->find($id);
        if (!$query) return $this->error('Credit query not found.', 404);

        $query->delete();
        return $this->success(null, 'Credit query deleted successfully.');
    }

    /** Convenience: end date defaults to start + 20 days (matches web). */
    public function suggestEndDate(Request $request)
    {
        [, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, ['start_date' => 'required|date'])) return $err;

        return $this->success([
            'end_date' => Carbon::parse($request->start_date)->addDays(20)->format('Y-m-d'),
        ], 'Suggested end date.');
    }
}
