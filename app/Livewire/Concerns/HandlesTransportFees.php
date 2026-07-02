<?php

namespace App\Livewire\Concerns;

use App\Models\Admin\Transportation;
use App\Models\Admin\TransportFeePayment;
use App\Models\Student\StudentDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Shared transport-fee logic for the Admin & Accounts Transport components.
 *
 * Annual transport fee = route.monthly_fee × 11  (June is excluded).
 *
 * Requires the host component to define:
 *   - protected function txOrgId(): int   (organization id)
 *   - WireUiActions trait (for $this->notification())
 */
trait HandlesTransportFees
{
    // Default number of billable months in the year (June excluded by default)
    public int $billableMonths = 11;

    // Academic year month order (Apr first, Mar last)
    public array $monthsOrder = [
        'apr' => 'April',  'may' => 'May',  'jun' => 'June',
        'jul' => 'July',   'aug' => 'August', 'sep' => 'September',
        'oct' => 'October','nov' => 'November','dec' => 'December',
        'jan' => 'January','feb' => 'February','mar' => 'March',
    ];

    // ── Fee Summary tab state ────────────────────────────────────────────────
    public string $feeStudentSearch = '';
    public ?int   $feeStudentId     = null;

    // Payment form
    public bool   $showPaymentPanel = false;
    public $payAmount;
    public string $payMode = 'cash';
    public string $payDate = '';
    public string $payRemark = '';

    // Delete payment confirm
    public ?int $pendingDeletePaymentId = null;

    // ── Transport student edit / delete state ────────────────────────────────
    public bool $editTxStudentModal       = false;
    public ?int $editTxStudentId          = null;   // student_detail_id
    public ?int $editTxStudentRouteId     = null;   // transportation_id
    public string $editTxStudentName      = '';
    public array $editTxBillableMonths    = [];     // ['apr' => true, 'may' => true, ...]
    public float $editTxMonthly           = 0;      // route monthly fee (live calc in modal)

    public ?int $pendingDeleteTxStudentId = null;   // student_detail_id
    public ?int $pendingDeleteTxRouteId   = null;   // transportation_id
    public string $pendingDeleteTxStudentName = '';

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Default month flags — June off, all other 11 months on. */
    public function defaultBillableMonths(): array
    {
        $flags = [];
        foreach (array_keys($this->monthsOrder) as $key) {
            $flags[$key] = ($key !== 'jun');
        }
        return $flags;
    }

    /** Normalize stored billable_months (may be null, JSON string, or array). */
    public function normalizeBillableMonths($raw): array
    {
        if (empty($raw)) {
            return $this->defaultBillableMonths();
        }
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?: [];
        }
        $defaults = $this->defaultBillableMonths();
        $merged = [];
        foreach (array_keys($this->monthsOrder) as $key) {
            $merged[$key] = array_key_exists($key, (array) $raw) ? (bool) $raw[$key] : $defaults[$key];
        }
        return $merged;
    }

    /** Count of months a student is billed for. */
    public function billableMonthsCount(array $months): int
    {
        return count(array_filter($months));
    }

    /** Annual transport fee for a route at the default 11 months (route-level display). */
    public function annualFee($monthlyFee): float
    {
        return round((float) $monthlyFee * $this->billableMonths, 2);
    }

    /** Annual transport fee for a specific student (uses their pivot months). */
    public function studentAnnualFee($monthlyFee, array $months): float
    {
        return round((float) $monthlyFee * $this->billableMonthsCount($months), 2);
    }

    /** The route a student is assigned to (first active match). */
    protected function studentRoute(int $studentDetailId): ?Transportation
    {
        return Transportation::where('organization_id', $this->txOrgId())
            ->whereHas('students', fn($q) => $q->where('student_details.id', $studentDetailId))
            ->orderByDesc('is_active')
            ->first();
    }

    // ── Tab 3: Transport Students list ────────────────────────────────────────

    /**
     * Students for the Transport Students tab.
     *
     * - If $filterRoute is set: students of that specific route, monthly fee
     *   pulled from that route, driver from that route, per-student billable
     *   months from the pivot row tying THAT student to THAT route.
     * - If $filterRoute is empty (legacy / Accounts behavior): all students
     *   with any transport assignment, first route used for figures.
     *
     * Each row gets _route, _driverName, _monthly, _months, _monthsCount,
     * _annual, _paid, _remaining.
     */
    public function transportStudents()
    {
        $orgId = $this->txOrgId();

        $routeId = !empty($this->filterRoute) ? (int) $this->filterRoute : null;
        $route   = $routeId
            ? Transportation::with('driver.user')->where('organization_id', $orgId)->find($routeId)
            : null;

        if ($routeId && !$route) {
            return new \Illuminate\Pagination\LengthAwarePaginator(
                collect(), 0, $this->perPage ?? 15, 1,
                ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
            );
        }

        $query = StudentDetail::with(['user:id,name,image', 'standard:id,name', 'section:id,name', 'transportations.driver.user'])
            ->where('student_details.organization_id', $orgId)
            ->whereHas('transportations', function ($q) use ($routeId) {
                if ($routeId) $q->where('transportations.id', $routeId);
            })
            ->when($this->search ?? '', function ($q) {
                $q->where(function ($qq) {
                    $qq->where('full_name', 'like', '%' . $this->search . '%')
                       ->orWhere('admission_no', 'like', '%' . $this->search . '%');
                });
            });

        $students = $query->orderBy('full_name')->paginate($this->perPage ?? 15);

        $ids = $students->getCollection()->pluck('id')->all();

        // Pull pivot rows (scoped to selected route if any) to read billable_months
        $pivotQuery = DB::table('transportation_students')
            ->where('organization_id', $orgId)
            ->whereIn('student_detail_id', $ids);
        if ($routeId) $pivotQuery->where('transportation_id', $routeId);
        $pivotRows = $pivotQuery->get()->groupBy('student_detail_id');

        // Paid totals (scoped to route if any)
        $paidQuery = TransportFeePayment::where('organization_id', $orgId)
            ->whereIn('student_detail_id', $ids);
        if ($routeId) $paidQuery->where('transportation_id', $routeId);
        $paidMap = $paidQuery
            ->selectRaw('student_detail_id, SUM(amount) as paid')
            ->groupBy('student_detail_id')
            ->pluck('paid', 'student_detail_id');

        $students->getCollection()->transform(function ($s) use ($route, $pivotRows, $paidMap) {
            // Resolve the route + monthly fee + driver for this student
            $rowRoute = $route ?: $s->transportations->first();
            $monthly  = (float) ($rowRoute?->monthly_fee ?? 0);
            $driver   = $rowRoute?->driver?->user?->name ?? '—';

            // Pivot for THIS student / THIS route
            $pivot = $pivotRows->get($s->id, collect())
                ->first(fn($p) => $rowRoute ? $p->transportation_id == $rowRoute->id : true);

            $months  = $this->normalizeBillableMonths($pivot->billable_months ?? null);
            $annual  = $this->studentAnnualFee($monthly, $months);
            $paid    = (float) ($paidMap[$s->id] ?? 0);

            $s->_route       = $rowRoute;
            $s->_driverName  = $driver;
            $s->_monthly     = $monthly;
            $s->_months      = $months;
            $s->_monthsCount = $this->billableMonthsCount($months);
            $s->_annual      = $annual;
            $s->_paid        = $paid;
            $s->_remaining   = max(0, $annual - $paid);
            return $s;
        });

        return $students;
    }

    // ── Edit transport student (per-month toggles) ──────────────────────────

    public function editTransportStudent(int $studentDetailId, int $routeId): void
    {
        $orgId = $this->txOrgId();

        $student = StudentDetail::where('organization_id', $orgId)->find($studentDetailId);
        if (!$student) {
            $this->notification()->error('Error', 'Student not found.');
            return;
        }

        $pivot = DB::table('transportation_students')
            ->where('organization_id', $orgId)
            ->where('transportation_id', $routeId)
            ->where('student_detail_id', $studentDetailId)
            ->first();

        $route = Transportation::where('organization_id', $orgId)->find($routeId);

        $this->editTxStudentId       = $studentDetailId;
        $this->editTxStudentRouteId  = $routeId;
        $this->editTxStudentName     = $student->full_name ?? '';
        $this->editTxMonthly         = (float) ($route?->monthly_fee ?? 0);
        $this->editTxBillableMonths  = $this->normalizeBillableMonths($pivot->billable_months ?? null);
        $this->editTxStudentModal    = true;
    }

    public function closeEditTransportStudent(): void
    {
        $this->editTxStudentModal   = false;
        $this->editTxStudentId      = null;
        $this->editTxStudentRouteId = null;
        $this->editTxStudentName    = '';
        $this->editTxMonthly        = 0;
        $this->editTxBillableMonths = [];
    }

    public function toggleTxMonth(string $monthKey): void
    {
        if (!array_key_exists($monthKey, $this->editTxBillableMonths)) return;
        $this->editTxBillableMonths[$monthKey] = !$this->editTxBillableMonths[$monthKey];
    }

    public function saveTransportStudentMonths(): void
    {
        if (!$this->editTxStudentId || !$this->editTxStudentRouteId) return;

        try {
            DB::table('transportation_students')
                ->where('organization_id', $this->txOrgId())
                ->where('transportation_id', $this->editTxStudentRouteId)
                ->where('student_detail_id', $this->editTxStudentId)
                ->update([
                    'billable_months' => json_encode($this->editTxBillableMonths),
                    'updated_at'      => now(),
                ]);

            $this->notification()->success('Saved', 'Monthly fee schedule updated.');
            $this->closeEditTransportStudent();
        } catch (\Exception $e) {
            $this->notification()->error('Error', 'Failed to save: ' . $e->getMessage());
        }
    }

    // ── Delete (remove student from transport) ──────────────────────────────

    public function confirmDeleteTransportStudent(int $studentDetailId, int $routeId, string $name = ''): void
    {
        $this->pendingDeleteTxStudentId   = $studentDetailId;
        $this->pendingDeleteTxRouteId     = $routeId;
        $this->pendingDeleteTxStudentName = $name;
    }

    public function cancelDeleteTransportStudent(): void
    {
        $this->pendingDeleteTxStudentId   = null;
        $this->pendingDeleteTxRouteId     = null;
        $this->pendingDeleteTxStudentName = '';
    }

    public function executeDeleteTransportStudent(): void
    {
        if (!$this->pendingDeleteTxStudentId || !$this->pendingDeleteTxRouteId) {
            $this->cancelDeleteTransportStudent();
            return;
        }

        try {
            DB::table('transportation_students')
                ->where('organization_id', $this->txOrgId())
                ->where('transportation_id', $this->pendingDeleteTxRouteId)
                ->where('student_detail_id', $this->pendingDeleteTxStudentId)
                ->delete();

            $this->notification()->success('Removed', 'Student removed from this route. Transport fee no longer applies.');
        } catch (\Exception $e) {
            $this->notification()->error('Error', 'Failed to remove: ' . $e->getMessage());
        }

        $this->cancelDeleteTransportStudent();
    }

    // ── Tab 4: Fee Summary for a single student ─────────────────────────────────

    public function selectFeeStudent(int $id): void
    {
        $this->feeStudentId = $id;
        $this->feeStudentSearch = '';
    }

    public function clearFeeStudent(): void
    {
        $this->feeStudentId = null;
        $this->feeStudentSearch = '';
    }

    /** Search results for the fee-summary student picker. */
    public function feeStudentResults()
    {
        if (strlen($this->feeStudentSearch) < 2) {
            return collect();
        }
        return StudentDetail::with(['standard:id,name', 'section:id,name'])
            ->where('organization_id', $this->txOrgId())
            ->whereHas('transportations')
            ->where(function ($q) {
                $q->where('full_name', 'like', '%' . $this->feeStudentSearch . '%')
                  ->orWhere('admission_no', 'like', '%' . $this->feeStudentSearch . '%');
            })
            ->limit(10)->get();
    }

    /** Full fee summary + transactions for the selected student. */
    public function feeSummary(): ?array
    {
        if (!$this->feeStudentId) return null;

        $orgId   = $this->txOrgId();
        $student = StudentDetail::with(['user:id,name,image', 'standard:id,name', 'section:id,name'])
            ->where('organization_id', $orgId)->find($this->feeStudentId);
        if (!$student) return null;

        $route   = $this->studentRoute($student->id);
        $monthly = (float) ($route?->monthly_fee ?? 0);

        // Per-student months (from pivot)
        $pivot = $route ? DB::table('transportation_students')
            ->where('organization_id', $orgId)
            ->where('transportation_id', $route->id)
            ->where('student_detail_id', $student->id)
            ->first() : null;
        $months      = $this->normalizeBillableMonths($pivot->billable_months ?? null);
        $monthsCount = $this->billableMonthsCount($months);
        $annual      = $this->studentAnnualFee($monthly, $months);

        $payments = TransportFeePayment::with('transportation:id,route_name')
            ->where('organization_id', $orgId)
            ->where('student_detail_id', $student->id)
            ->orderByDesc('payment_date')->orderByDesc('id')
            ->get();

        $paid = (float) $payments->sum('amount');

        return [
            'student'      => $student,
            'route'        => $route,
            'monthly'      => $monthly,
            'months'       => $months,
            'months_count' => $monthsCount,
            'annual'       => $annual,
            'paid'         => $paid,
            'remaining'    => max(0, $annual - $paid),
            'payments'     => $payments,
            'month_status' => $this->monthStatuses($monthly, $months, $paid),
        ];
    }

    /**
     * Month-by-month fee status for the fee summary.
     *
     * The total paid amount is applied sequentially across the student's
     * enabled billable months in academic order (Apr→Mar). Each month costs
     * `monthly`; a month is Paid once fully covered, Partial while partially
     * covered, else Unpaid — so ₹5,400 at ₹1,000/mo marks 5 months Paid and the
     * 6th Partial (₹400). Only months up to the current month are returned, and
     * disabled (non-billable) months are skipped entirely.
     *
     * @return array<int, array{key:string,label:string,amount:float,paid:float,status:string}>
     */
    public function monthStatuses(float $monthly, array $months, float $paid): array
    {
        $monthNum = [
            'apr' => 4, 'may' => 5, 'jun' => 6, 'jul' => 7, 'aug' => 8, 'sep' => 9,
            'oct' => 10, 'nov' => 11, 'dec' => 12, 'jan' => 1, 'feb' => 2, 'mar' => 3,
        ];

        $now           = now();
        $curMonthStart = $now->copy()->startOfMonth();
        // Academic year starts in April.
        $startYear     = $now->month >= 4 ? $now->year : $now->year - 1;

        $remaining = $paid;
        $rows = [];

        foreach (array_keys($this->monthsOrder) as $key) {
            if (empty($months[$key])) {
                continue; // disabled month → not billed, not shown
            }
            $mnum       = $monthNum[$key];
            $year       = $mnum >= 4 ? $startYear : $startYear + 1;
            $monthStart = \Carbon\Carbon::create($year, $mnum, 1)->startOfMonth();

            if ($monthStart->gt($curMonthStart)) {
                continue; // only show months up to the current month
            }

            $alloc     = $monthly > 0 ? min($remaining, $monthly) : 0;
            $alloc     = max(0, $alloc);
            $remaining = max(0, $remaining - $alloc);

            $status = ($monthly > 0 && $alloc >= $monthly)
                ? 'paid'
                : ($alloc > 0 ? 'partial' : 'unpaid');

            $rows[] = [
                'key'    => $key,
                'label'  => $this->monthsOrder[$key],
                'amount' => $monthly,
                'paid'   => round($alloc, 2),
                'status' => $status,
            ];
        }

        return $rows;
    }

    // ── Add payment ──────────────────────────────────────────────────────────

    public function openPaymentPanel(): void
    {
        $this->resetErrorBag();
        $summary = $this->feeSummary();
        $this->payAmount = $summary ? max(0, $summary['remaining']) : null;
        $this->payMode   = 'cash';
        $this->payDate   = now()->toDateString();
        $this->payRemark = '';
        $this->showPaymentPanel = true;
    }

    public function closePaymentPanel(): void
    {
        $this->showPaymentPanel = false;
        $this->payAmount = null;
        $this->payRemark = '';
    }

    public function savePayment(): void
    {
        $this->validate([
            'payAmount' => 'required|numeric|min:1',
            'payMode'   => 'required|in:cash,online,cheque,upi',
            'payDate'   => 'required|date',
            'payRemark' => 'nullable|string|max:255',
        ]);

        if (!$this->feeStudentId) {
            $this->notification()->error('Error', 'Select a student first.');
            return;
        }

        $orgId   = $this->txOrgId();
        $student = StudentDetail::where('organization_id', $orgId)->find($this->feeStudentId);
        if (!$student) {
            $this->notification()->error('Error', 'Student not found.');
            return;
        }
        $route = $this->studentRoute($student->id);

        DB::transaction(function () use ($orgId, $student, $route) {
            TransportFeePayment::create([
                'organization_id'   => $orgId,
                'transportation_id' => $route?->id,
                'student_detail_id' => $student->id,
                'amount'            => $this->payAmount,
                'payment_mode'      => $this->payMode,
                'payment_date'      => $this->payDate,
                'receipt_number'    => $this->generateTransportReceiptNumber($orgId),
                'academic_year'     => now()->format('Y') . '-' . substr((string) (now()->year + 1), -2),
                'remark'            => $this->payRemark ?: null,
                'submitted_by'      => Auth::id(),
            ]);
        });

        $this->notification()->success('Success', 'Transport fee payment recorded.');
        $this->closePaymentPanel();
    }

    private function generateTransportReceiptNumber(int $orgId): string
    {
        $year = now()->format('y');
        $base = "TRP{$orgId}{$year}";
        $last = TransportFeePayment::where('organization_id', $orgId)
            ->where('receipt_number', 'like', "{$base}%")
            ->orderByDesc('id')->first();
        $serial = $last ? ((int) substr($last->receipt_number, -5) + 1) : 1;
        return $base . str_pad((string) $serial, 5, '0', STR_PAD_LEFT);
    }

    public function confirmDeletePayment(int $id): void { $this->pendingDeletePaymentId = $id; }
    public function cancelDeletePayment(): void { $this->pendingDeletePaymentId = null; }
    public function executeDeletePayment(): void
    {
        if ($this->pendingDeletePaymentId) {
            TransportFeePayment::where('id', $this->pendingDeletePaymentId)
                ->where('organization_id', $this->txOrgId())->delete();
            $this->notification()->success('Deleted', 'Payment removed.');
        }
        $this->pendingDeletePaymentId = null;
    }
}
