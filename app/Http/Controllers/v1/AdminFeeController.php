<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Admin\FeeReceiptController;
use App\Livewire\Admin\Fee as FeePage;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeePaymentRequest;
use App\Models\Admin\TransportFeePayment;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Support\TransportBilling;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * The admin app's Fees — app/Livewire/Admin/Fee.php over the API.
 *
 * Like Payroll, it runs the panel component's own methods (a student's
 * ledger, what may be collected against it, the Payments listing and its
 * figures, Analytics) instead of a copy of them, so the app, the admin panel
 * and the accounts panel always agree.
 *
 *   Students   classes, students by class / section / name
 *   Ledger     one student's fees net of concession, cycles, payments
 *   Collect    a payment, capped at what is due (transport goes with the
 *              student's other bus payments, as on the panel)
 *   Payments   every payment, both tables, with the panel's filters and figures
 *   Analytics  the panel's Analytics tab
 *   QR         payments students reported on the school's QR — checked,
 *              then approved (booked with a receipt) or rejected (with a reason)
 */
class AdminFeeController extends ApiController
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

    /** The panel's component, with the filters it reads set. */
    private function page(array $props = []): FeePage
    {
        $page = new FeePage();
        foreach ($props as $k => $v) {
            $page->{$k} = $v;
        }
        return $page;
    }

    /** Run one of the panel component's own (protected / private) methods. */
    private function run(FeePage $page, string $method, ...$args)
    {
        return (fn () => $this->{$method}(...$args))->call($page);
    }

    // ══════════════════════════ STUDENTS ══════════════════════════

    /** GET /admin/fees/lookups — classes with their sections, in class order. */
    public function lookups()
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = (int) $user->organization_id;

        $classes = Standard::where('organization_id', $orgId)->where('is_active', true)->inClassOrder()->get();
        $sections = Section::where('organization_id', $orgId)->where('is_active', true)->orderBy('id')->get()->groupBy('standard_id');

        return $this->success([
            'classes' => $classes->map(fn ($c) => [
                'id'       => $c->id,
                'name'     => $c->name,
                'sections' => ($sections[$c->id] ?? collect())->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values(),
            ])->values(),
            'payment_modes' => ['cash', 'online', 'cheque', 'bank_transfer'],
        ], 'Lookups fetched.');
    }

    /**
     * GET /admin/fees/students?standard_id=&section_id=&search=
     * The panel's Fee Submission list: a class, or a name (student or father).
     */
    public function students(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $data = $this->run($this->page([
            'submissionStandardId' => (string) $request->input('standard_id', ''),
            'submissionSectionId'  => (string) $request->input('section_id', ''),
            'submissionSearch'     => (string) $request->input('search', ''),
        ]), 'feeSubmissionViewData');

        $students = collect($data['fsStudents'])
            ->sortBy(fn ($s) => mb_strtolower(trim((string) ($s->full_name ?: $s->user?->name))))
            ->values();

        // With a class picked, each row carries what View Fee's By Class list
        // shows for it: the year's fee, what has come in, what is pending.
        $fees = [];
        if ($request->filled('standard_id')) {
            $page = $this->page([
                'viewClassStandardId' => (string) $request->input('standard_id'),
                'viewClassSectionId'  => (string) $request->input('section_id', ''),
            ]);
            $this->run($page, 'loadClassFeeView');
            $fees = collect($page->classFeeList)->keyBy('id');
        }

        return $this->success([
            'students' => $students->map(fn ($s) => [
                'id'           => $s->id,
                'name'         => $s->full_name ?: ($s->user?->name ?? '—'),
                'father_name'  => $s->father_name,
                'admission_no' => $s->admission_no,
                'roll_no'      => $s->roll_no,
                'class'        => $s->standard?->name,
                'section'      => $s->section?->name,
                'photo'        => $s->user?->image,
                'fee'          => isset($fees[$s->id]) ? [
                    'total'     => (float) $fees[$s->id]['totalFee'],
                    'collected' => (float) $fees[$s->id]['totalCollected'],
                    'pending'   => (float) $fees[$s->id]['pending'],
                ] : null,
            ])->values(),
        ], 'Students fetched.');
    }

    // ══════════════════════════ LEDGER ══════════════════════════

    /**
     * GET /admin/fees/students/{id} — the student's ledger (View Fee), with
     * what may be collected against each fee type now.
     */
    public function ledger($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $student = StudentDetail::with('user')->where('organization_id', $user->organization_id)->find($id);
        if (!$student) return $this->error('Student not found.', 404);

        $page = $this->page(['selectedStudentId' => (string) $student->id]);
        $this->run($page, 'updatedSelectedStudentId');
        $ledger = $page->submissionLedger;

        return $this->success($ledger + [
            'photo'        => $student->user?->image,
            'caps'         => $this->run($page, 'feeTypeCaps'),
            'net_payable'  => (float) $page->netPayable,
            'submitted_by' => $user->name ?? '',
        ], 'Ledger fetched.');
    }

    /**
     * POST /admin/fees/students/{id}/payments {amount, fee_type, payment_mode, date, remark?, submitted_by}
     * The panel's Collect Fee: never more than is due for the fee type.
     */
    public function collect(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = (int) $user->organization_id;

        $student = StudentDetail::where('organization_id', $orgId)->find($id);
        if (!$student) return $this->error('Student not found.', 404);

        if ($err = $this->validateWith($request, [
            'amount'       => 'required|numeric|min:1',
            'fee_type'     => 'required|in:academic,transport,penalty',
            'payment_mode' => 'required|in:cash,online,cheque,bank_transfer',
            'date'         => 'required|date',
            'submitted_by' => 'required|string|max:255',
            'remark'       => 'nullable|string|max:1000',
        ])) return $err;

        // What is due now, read off the same ledger the panel shows.
        $page = $this->page(['selectedStudentId' => (string) $student->id]);
        $this->run($page, 'updatedSelectedStudentId');
        $cap = (float) ($this->run($page, 'feeTypeCaps')[$request->fee_type] ?? 0.0);

        if ($cap <= 0) {
            return $this->error($request->fee_type === 'penalty'
                ? 'This student has no penalty currently due — there is nothing to submit.'
                : ucfirst($request->fee_type) . ' fee for this student is already fully paid for the year.', 422);
        }
        if ((float) $request->amount > $cap + 0.01) {
            return $this->error('Only up to ₹' . number_format($cap, 2) . ' can be submitted — that is what is currently due for ' . $request->fee_type . '.', 422);
        }

        if ($request->fee_type === 'transport') {
            // With the student's other bus payments, as the panel books it.
            [$route] = TransportBilling::forStudent($orgId, (int) $student->id);
            $year    = (int) date('Y', strtotime((string) $request->date));

            $payment = TransportFeePayment::create([
                'organization_id'   => $orgId,
                'transportation_id' => $route?->id,
                'student_detail_id' => $student->id,
                'amount'            => $request->amount,
                'payment_mode'      => $request->payment_mode,
                'payment_date'      => $request->date,
                'academic_year'     => $year . '-' . substr((string) ($year + 1), -2),
                'remark'            => $request->remark ?: null,
                'submitted_by'      => $user->id,
            ]);
            $kind = 'transport';
        } else {
            $payment = FeePayment::create([
                'organization_id'   => $orgId,
                'student_detail_id' => $student->id,
                'standard_id'       => $student->standard_id,
                'section_id'        => $student->section_id,
                'fee_type'          => $request->fee_type,
                'amount'            => $request->amount,
                'payment_mode'      => $request->payment_mode,
                'payment_date'      => $request->date,
                'remark'            => $request->remark,
                'submitted_by'      => $request->submitted_by,
            ]);
            $kind = 'academic';
        }

        return $this->success([
            'id'             => $payment->id,
            'kind'           => $kind,
            'receipt_number' => $payment->fresh()->receipt_number,
        ], 'Fee submitted successfully!');
    }

    /** GET /admin/fees/receipt/{id}/pdf — an academic / penalty receipt, as the panel prints it. */
    public function receipt(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        if (!FeePayment::where('organization_id', $user->organization_id)->whereKey($id)->exists()) {
            return $this->error('Receipt not found.', 404);
        }

        return app(FeeReceiptController::class)->show($request, $user->organization_id, $id);
    }

    // ══════════════════════════ PAYMENTS ══════════════════════════

    /**
     * GET /admin/fees/payments?preset=today|yesterday|7|15|30|this_month|last_month
     *     &date_from=&date_to=&standard_id=&section_id=&search=&mode=&fee_type=&page=
     * The panel's Payments tab: both payment tables in one list, and its figures.
     */
    public function payments(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $preset = $request->has('preset') ? (string) $request->input('preset') : ($request->filled('date_from') || $request->filled('date_to') ? '' : 'this_month');
        $page = $this->page([
            'datePreset'           => $preset,
            'dateFrom'             => (string) $request->input('date_from', ''),
            'dateTo'               => (string) $request->input('date_to', ''),
            'paymentStandardId'    => (string) $request->input('standard_id', ''),
            'paymentSectionId'     => (string) $request->input('section_id', ''),
            'paymentStudentSearch' => (string) $request->input('search', ''),
            'paymentModeFilter'    => (string) $request->input('mode', ''),
            'feeTypeFilter'        => in_array($request->input('fee_type'), ['academic', 'transport', 'penalty'], true) ? $request->input('fee_type') : '',
            'paymentsPerPage'      => max(5, min(50, (int) $request->input('per_page', 20))),
        ]);
        if ($preset !== '') {
            $this->run($page, 'applyDatePreset');
        }

        $list  = $this->run($page, 'paymentsForListing');
        $stats = $this->run($page, 'paymentHeaderStats');

        return $this->success([
            'payments' => collect($list->items())->map(fn ($p) => [
                'id'             => $p->id,
                'kind'           => str_contains($p->receipt_route, 'transport') ? 'transport' : 'academic',
                'student_name'   => $p->student_name,
                'admission_no'   => $p->admission_no,
                'class'          => $p->standard_name,
                'section'        => $p->section_name,
                'fee_type'       => $p->fee_type,
                'payment_mode'   => $p->payment_mode,
                'amount'         => (float) $p->amount,
                'penalty_amount' => (float) $p->penalty_amount,
                'waiver_amount'  => (float) $p->waiver_amount,
                'payment_date'   => $p->payment_date ? \Illuminate\Support\Carbon::parse($p->payment_date)->toDateString() : null,
                'submitted_by'   => is_numeric($p->submitted_by) ? null : $p->submitted_by,
            ])->values(),
            'pagination' => $this->paginationMeta($list),
            'stats'      => $stats,
            'window'     => ['preset' => $page->datePreset, 'date_from' => $page->dateFrom, 'date_to' => $page->dateTo],
        ], 'Payments fetched.');
    }

    // ══════════════════════════ ANALYTICS ══════════════════════════

    /** GET /admin/fees/analytics?standard_id=&section_id= — the panel's Analytics tab. */
    public function analytics(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $page = $this->page([
            'analyticsStandardId' => (string) $request->input('standard_id', ''),
            'analyticsSectionId'  => (string) $request->input('section_id', ''),
        ]);
        $this->run($page, 'loadAnalytics');

        return $this->success([
            'summary'       => $page->analyticsSummary,
            'periods'       => $page->analyticsPeriods,
            'daily'         => $page->analyticsDaily,
            'modes'         => $page->analyticsModes,
            'classes'       => $page->analyticsClassRows,
            'students'      => $page->analyticsStudentRows,
            'student_scope' => $page->analyticsStudentScope,
        ], 'Analytics fetched.');
    }

    // ══════════════════════════ QR PAYMENTS ══════════════════════════

    private function findQrRequest(int $orgId, $id): ?FeePaymentRequest
    {
        return FeePaymentRequest::where('organization_id', $orgId)->find($id);
    }

    private function qrRow(FeePaymentRequest $r): array
    {
        $st = $r->studentDetail;

        return [
            'id'              => $r->id,
            'fee_type'        => $r->fee_type,
            'amount'          => (float) $r->amount,
            'approved_amount' => $r->approved_amount !== null ? (float) $r->approved_amount : null,
            'utr'             => $r->utr,
            'paid_on'         => $r->paid_on?->toDateString(),
            'note'            => $r->note,
            // What it was meant for, as the panel's "for" line reads it.
            'months'          => $r->months(),
            'installment'     => $r->meta['installment'] ?? null,
            'status'          => $r->status,
            'review_note'     => $r->review_note,
            'receipt_number'  => $r->receiptNumber(),
            'fee_payment_id'           => $r->fee_payment_id,
            'transport_fee_payment_id' => $r->transport_fee_payment_id,
            'submitted_at'    => $r->created_at?->toIso8601String(),
            'reviewed_at'     => $r->reviewed_at?->toIso8601String(),
            'student'         => $st ? [
                'id'           => $st->id,
                'name'         => $st->full_name,
                'admission_no' => $st->admission_no,
                'roll_no'      => $st->roll_no,
                'class'        => $st->standard?->name,
                'section'      => $st->section?->name,
                'photo'        => $st->image,
            ] : null,
        ];
    }

    /**
     * GET /admin/fees/qr?status=pending|approved|rejected|&fee_type=&search=&date=&page=
     * The panel's QR Payments: what students paid on the school's QR, waiting
     * ones oldest first, and the header's counts (for the day, or everything).
     */
    public function qrRequests(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = (int) $user->organization_id;

        $status  = $request->has('status') ? (string) $request->input('status') : FeePaymentRequest::STATUS_PENDING;
        $feeType = (string) $request->input('fee_type', '');
        $search  = trim((string) $request->input('search', ''));
        $date    = (string) $request->input('date', '');

        $requests = FeePaymentRequest::where('organization_id', $orgId)
            ->with([
                'studentDetail:id,full_name,admission_no,roll_no,standard_id,section_id,image',
                'studentDetail.standard:id,name',
                'studentDetail.section:id,name',
                'feePayment:id,receipt_number',
                'transportFeePayment:id,receipt_number',
            ])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($feeType !== '', fn ($q) => $q->where('fee_type', $feeType))
            ->when($date !== '', fn ($q) => $q->whereDate('paid_on', $date))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('utr', 'like', '%' . strtoupper(preg_replace('/\s+/', '', $search)) . '%')
                        ->orWhereHas('studentDetail', fn ($s) => $s
                            ->where('full_name', 'like', "%{$search}%")
                            ->orWhere('admission_no', 'like', "%{$search}%"));
                });
            })
            // Waiting ones oldest first — checked in the order they came in.
            ->when(
                $status === FeePaymentRequest::STATUS_PENDING,
                fn ($q) => $q->orderBy('created_at'),
                fn ($q) => $q->orderByDesc('created_at')
            )
            ->paginate(15);

        // What the header counts — the chosen day, or everything.
        $base = fn () => FeePaymentRequest::where('organization_id', $orgId)
            ->when($date !== '', fn ($q) => $q->whereDate('paid_on', $date));

        return $this->success([
            'requests'   => collect($requests->items())->map(fn ($r) => $this->qrRow($r))->values(),
            'pagination' => $this->paginationMeta($requests),
            'stats'      => [
                'total'    => $base()->count(),
                'amount'   => (float) $base()->sum('amount'),
                'pending'  => $base()->where('status', FeePaymentRequest::STATUS_PENDING)->count(),
                'approved' => $base()->where('status', FeePaymentRequest::STATUS_APPROVED)->count(),
                'rejected' => $base()->where('status', FeePaymentRequest::STATUS_REJECTED)->count(),
            ],
        ], 'QR payments fetched.');
    }

    /**
     * GET /admin/fees/qr/{id} — the panel's review panel: the payment, its
     * screenshot, who decided it, and where the student stands on that fee.
     */
    public function qrRequest($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $r = $this->findQrRequest((int) $user->organization_id, $id);
        if (!$r) return $this->error('Payment not found.', 404);

        $r->load([
            'studentDetail.standard:id,name',
            'studentDetail.section:id,name',
            'feePayment:id,receipt_number',
            'transportFeePayment:id,receipt_number',
            'reviewer:id,name',
        ]);

        $ledger = $this->run($this->page(), 'buildStudentFeeView', (int) $r->student_detail_id);
        $side   = $r->fee_type === 'transport' ? ($ledger['transport'] ?? null) : ($ledger['academic'] ?? null);

        return $this->success($this->qrRow($r) + [
            'screenshot_url' => $r->screenshotUrl(),
            'reviewer'       => $r->reviewer?->name,
            'side'           => $side ? [
                'net'       => (float) ($side['net'] ?? 0),
                'paid'      => (float) ($side['paid'] ?? 0),
                'remaining' => (float) ($side['remaining'] ?? 0),
            ] : null,
        ], 'QR payment fetched.');
    }

    /** POST /admin/fees/qr/{id}/approve {amount, note?} — books it with its receipt; the student is told. */
    public function qrApprove(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $r = $this->findQrRequest((int) $user->organization_id, $id);
        if (!$r) return $this->error('Payment not found.', 404);

        if ($err = $this->validateWith($request, [
            'amount' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'note'   => ['nullable', 'string', 'max:300'],
        ], [
            'amount.required' => 'Enter the amount that reached your account.',
            'amount.min'      => 'The amount must be at least ₹1.',
        ])) return $err;

        try {
            $r->approve($user, (float) $request->amount, trim((string) $request->input('note', '')) ?: null);
        } catch (QueryException $e) {
            report($e);
            return $this->error('The payment could not be booked. Please try again.', 500);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $r->load(['feePayment', 'transportFeePayment', 'studentDetail']);
        $r->notifyStudent();

        return $this->success($this->qrRow($r),
            '₹' . number_format((float) $r->approved_amount, 2) . ' booked for '
                . ($r->studentDetail?->full_name ?: 'the student')
                . ($r->receiptNumber() ? ' — receipt ' . $r->receiptNumber() : '') . '.');
    }

    /** POST /admin/fees/qr/{id}/reject {reason} — closed with a reason the student sees. */
    public function qrReject(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $r = $this->findQrRequest((int) $user->organization_id, $id);
        if (!$r) return $this->error('Payment not found.', 404);

        $request->merge(['reason' => trim((string) $request->input('reason', ''))]);
        if ($err = $this->validateWith($request, [
            'reason' => ['required', 'string', 'min:3', 'max:300'],
        ], [
            'reason.required' => 'Tell the student why — they will see this in the app.',
        ])) return $err;

        try {
            $r->reject($user, $request->reason);
        } catch (QueryException $e) {
            report($e);
            return $this->error('Please try again.', 500);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $r->notifyStudent();

        return $this->success($this->qrRow($r->load(['studentDetail'])), 'The student has been told why.');
    }
}
