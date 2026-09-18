<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\Fee\FeePaymentRequest;
use App\Models\Admin\Fee\PaymentQrCode;
use App\Models\OrganizationPaymentSetting;
use App\Models\Student\StudentDetail;
use App\Support\TransportBilling;
use Illuminate\Http\Request;

/**
 * Paying fees on the school's own UPI QR (student only).
 *
 *   GET  /fees/qr         the school's QR and this student's reported payments
 *   POST /fees/qr/submit  report a payment made on it — UTR and/or screenshot
 *
 * The money goes straight to the school; nothing here moves it. A reported
 * payment waits for the school to check it in the admin panel (Fees → QR
 * Payments), where approving it books the receipt.
 */
class QrPaymentController extends ApiController
{
    public function show()
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole('user')) return $err;

        $student = $this->studentOf($user);
        if (!$student) {
            return $this->error('Student profile not found.', 404);
        }

        $qr = PaymentQrCode::activeForOrg((int) $user->organization_id);

        $requests = FeePaymentRequest::with(['feePayment:id,receipt_number', 'transportFeePayment:id,receipt_number'])
            ->where('organization_id', $user->organization_id)
            ->where('student_detail_id', $student->id)
            ->latest()
            ->limit(50)
            ->get();

        return $this->success([
            'qr'             => $qr ? $this->qrPayload($qr) : null,
            // The school's own PhonePe merchant is set up — online payments
            // reach its account too. Without it, a school on QR takes fees
            // only there.
            'gateway_ready'  => (bool) OrganizationPaymentSetting::forOrg((int) $user->organization_id)?->collectionReady(),
            'requests'       => $requests->map(fn ($r) => $this->requestPayload($r))->values(),
            'pending_count'  => $requests->where('status', FeePaymentRequest::STATUS_PENDING)->count(),
            'pending_amount' => round((float) $requests->where('status', FeePaymentRequest::STATUS_PENDING)->sum('amount'), 2),
        ], 'QR payment details fetched.');
    }

    public function submit(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole('user')) return $err;

        if ($err = $this->validateWith($request, [
            'fee_type'          => ['required', 'in:academic,transport'],
            'amount'            => ['required', 'numeric', 'min:1', 'max:1000000'],
            'utr'               => ['nullable', 'string', 'max:40'],
            'screenshot'        => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'paid_on'           => ['nullable', 'date', 'before_or_equal:today'],
            'note'              => ['nullable', 'string', 'max:300'],
            'months'            => ['nullable', 'array'],
            'months.*'          => ['string', 'max:20'],
            'transportation_id' => ['nullable', 'integer'],
            'installment'       => ['nullable', 'string', 'max:100'],
        ], [
            'screenshot.mimes' => 'The screenshot must be a JPG, PNG or WebP image.',
            'screenshot.max'   => 'The screenshot must be 5 MB or smaller.',
            'paid_on.before_or_equal' => 'The payment date cannot be in the future.',
        ])) return $err;

        $orgId   = (int) $user->organization_id;
        $student = $this->studentOf($user);
        if (!$student) {
            return $this->error('Student profile not found.', 404);
        }

        if (!PaymentQrCode::activeForOrg($orgId)) {
            return $this->error('Your school is not taking fees on QR right now.', 422);
        }

        $utr = FeePaymentRequest::normaliseUtr($request->input('utr'));
        if (!$utr && !$request->hasFile('screenshot')) {
            return $this->error('Add the UTR or a screenshot of the payment.', 422);
        }
        if ($utr && !preg_match('/^[A-Z0-9]{6,35}$/', $utr)) {
            return $this->error('Enter the UTR as your UPI app shows it — letters and numbers only.', 422);
        }
        if ($utr && FeePaymentRequest::utrTaken($orgId, $utr)) {
            return $this->error('A payment with this UTR has already been sent to the school.', 422);
        }

        $feeType = $request->input('fee_type');
        $meta    = [];

        if ($feeType === 'transport') {
            // The route the student is actually on; a route id from the app is
            // taken only when it is one of theirs.
            $routeId = (int) $request->input('transportation_id');
            if (!$routeId || !$student->transportations()->where('transportations.id', $routeId)->exists()) {
                [$route] = TransportBilling::forStudent($orgId, (int) $student->id);
                $routeId = $route?->id;
            }
            if (!$routeId) {
                return $this->error('No transport route is assigned to you.', 422);
            }
            $meta['transportation_id'] = $routeId;
            $meta['months'] = array_values(array_filter((array) $request->input('months', [])));
        } elseif ($request->filled('installment')) {
            $meta['installment'] = $request->input('installment');
        }

        $path = null;
        if ($request->hasFile('screenshot')) {
            // Private: shown only through signed links (see screenshotUrl()).
            $path = $request->file('screenshot')->store("fees/qr-payments/{$orgId}", 's3');
            if (!$path) {
                return $this->error('Could not upload the screenshot. Please try again.', 500);
            }
        }

        $req = FeePaymentRequest::create([
            'organization_id'   => $orgId,
            'student_detail_id' => $student->id,
            'user_id'           => $user->id,
            'fee_type'          => $feeType,
            'amount'            => round((float) $request->input('amount'), 2),
            'utr'               => $utr,
            'screenshot_path'   => $path,
            'paid_on'           => $request->input('paid_on') ?: today()->toDateString(),
            'note'              => $request->input('note') ?: null,
            'meta'              => $meta ?: null,
            'status'            => FeePaymentRequest::STATUS_PENDING,
        ]);

        return $this->success($this->requestPayload($req), 'Sent to the school for checking.', 201);
    }

    private function studentOf($user): ?StudentDetail
    {
        return StudentDetail::where('user_id', $user->id)
            ->where('organization_id', $user->organization_id)
            ->first();
    }

    private function qrPayload(PaymentQrCode $qr): array
    {
        return [
            'image_url'    => $qr->imageUrl(),
            'upi_id'       => $qr->upi_id,
            'payee_name'   => $qr->payee_name,
            'instructions' => $qr->instructions,
            'updated_at'   => $qr->updated_at?->toIso8601String(),
        ];
    }

    private function requestPayload(FeePaymentRequest $r): array
    {
        return [
            'id'              => $r->id,
            'fee_type'        => $r->fee_type,
            'amount'          => (float) $r->amount,
            'approved_amount' => $r->approved_amount !== null ? (float) $r->approved_amount : null,
            'utr'             => $r->utr,
            'paid_on'         => $r->paid_on?->toDateString(),
            'note'            => $r->note,
            'months'          => $r->months(),
            'installment'     => $r->meta['installment'] ?? null,
            'status'          => $r->status,
            'review_note'     => $r->review_note,
            'receipt_number'  => $r->receiptNumber(),
            'screenshot_url'  => $r->screenshotUrl(),
            'submitted_at'    => $r->created_at?->toIso8601String(),
            'reviewed_at'     => $r->reviewed_at?->toIso8601String(),
        ];
    }
}
