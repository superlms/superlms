<?php

namespace App\Models\Admin\Fee;

use App\Models\Admin\TransportFeePayment;
use App\Models\Student\StudentDetail;
use App\Models\User;
use App\Support\TransportBilling;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * A fee a student paid on the school's QR and reported from the app — with the
 * UTR, a screenshot, or both. Nothing is booked until the school checks it:
 * approve() records it as an ordinary payment with its own receipt, in
 * fee_payments (academic) or transport_fee_payments (the bus), exactly once;
 * reject() closes it with a reason the student sees.
 */
class FeePaymentRequest extends Model
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'organization_id',
        'student_detail_id',
        'user_id',
        'fee_type',
        'amount',
        'utr',
        'screenshot_path',
        'paid_on',
        'note',
        'meta',
        'status',
        'approved_amount',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'fee_payment_id',
        'transport_fee_payment_id',
    ];

    protected $casts = [
        'amount'          => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'paid_on'         => 'date',
        'reviewed_at'     => 'datetime',
        'meta'            => 'array',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function studentDetail(): BelongsTo
    {
        return $this->belongsTo(StudentDetail::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function feePayment(): BelongsTo
    {
        return $this->belongsTo(FeePayment::class);
    }

    public function transportFeePayment(): BelongsTo
    {
        return $this->belongsTo(TransportFeePayment::class);
    }

    // ── Reading ──────────────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /** "412345678901" — a UTR as the banks print it: no spaces, capitals. */
    public static function normaliseUtr(?string $utr): ?string
    {
        $utr = strtoupper(preg_replace('/\s+/', '', (string) $utr));
        return $utr === '' ? null : $utr;
    }

    /** Has this school already been sent this UTR (and not turned it down)? */
    public static function utrTaken(int $orgId, string $utr, ?int $exceptId = null): bool
    {
        return self::where('organization_id', $orgId)
            ->where('utr', $utr)
            ->where('status', '!=', self::STATUS_REJECTED)
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->exists();
    }

    /**
     * A short-lived link to the screenshot. The file is private on S3 — it can
     * carry a bank name or account — so it is only ever handed out signed.
     */
    public function screenshotUrl(int $minutes = 30): ?string
    {
        if (!$this->screenshot_path) {
            return null;
        }

        $disk = Storage::disk('s3');
        try {
            return $disk->temporaryUrl($this->screenshot_path, now()->addMinutes($minutes));
        } catch (\Throwable $e) {
            return $disk->url($this->screenshot_path);
        }
    }

    public function receiptNumber(): ?string
    {
        return $this->feePayment?->receipt_number ?? $this->transportFeePayment?->receipt_number;
    }

    /** The months a transport payment was meant for, as the student picked them. */
    public function months(): array
    {
        return array_values(array_filter((array) ($this->meta['months'] ?? [])));
    }

    // ── Deciding ─────────────────────────────────────────────────────────────

    /**
     * Book the payment. $amount is what actually reached the school's account
     * (defaults to what the student reported). Row-locked, so two people
     * approving at once still make one payment.
     *
     * @throws RuntimeException when it is no longer pending or the amount is not positive
     */
    public function approve(User $reviewer, ?float $amount = null, ?string $note = null): self
    {
        DB::transaction(function () use ($reviewer, $amount, $note) {
            /** @var self|null $req */
            $req = self::whereKey($this->getKey())->lockForUpdate()->first();
            if (!$req || !$req->isPending()) {
                throw new RuntimeException('This payment has already been ' . ($req->status ?? 'removed') . '.');
            }

            $amount = round($amount ?? (float) $req->amount, 2);
            if ($amount <= 0) {
                throw new RuntimeException('The amount must be more than zero.');
            }

            $student = StudentDetail::find($req->student_detail_id);
            if (!$student) {
                throw new RuntimeException('The student on this payment no longer exists.');
            }

            $paidOn = $req->paid_on ?? now();

            if ($req->fee_type === 'transport') {
                $routeId = $req->meta['transportation_id'] ?? null;
                if (!$routeId) {
                    [$route] = TransportBilling::forStudent((int) $req->organization_id, (int) $student->id);
                    $routeId = $route?->id;
                }

                $tp = TransportFeePayment::create([
                    'organization_id'   => $req->organization_id,
                    'transportation_id' => $routeId,
                    'student_detail_id' => $student->id,
                    'amount'            => $amount,
                    'payment_mode'      => 'upi',
                    'payment_date'      => $paidOn->toDateString(),
                    'academic_year'     => self::academicYearOf($paidOn),
                    'remark'            => $req->bookingRemark(),
                    // transport_fee_payments keeps the collector as a user id
                    'submitted_by'      => $reviewer->id,
                ]);
                $req->transport_fee_payment_id = $tp->id;
            } else {
                $fp = FeePayment::create([
                    'organization_id'   => $req->organization_id,
                    'student_detail_id' => $student->id,
                    'standard_id'       => $student->standard_id ?? 0,
                    'section_id'        => $student->section_id,
                    'fee_type'          => 'academic',
                    'amount'            => $amount,
                    // fee_payments.payment_mode is an enum without 'upi'; the
                    // remark says it came on the school's QR.
                    'payment_mode'      => 'online',
                    'payment_date'      => $paidOn->toDateString(),
                    'remark'            => $req->bookingRemark(),
                    'submitted_by'      => $reviewer->name ?: 'QR payment',
                ]);
                $req->fee_payment_id = $fp->id;
            }

            $req->status          = self::STATUS_APPROVED;
            $req->approved_amount = $amount;
            $req->reviewed_by     = $reviewer->id;
            $req->reviewed_at     = now();
            $req->review_note     = $note ?: null;
            $req->save();

            $this->setRawAttributes($req->getAttributes(), true);
        });

        return $this;
    }

    /** @throws RuntimeException when it is no longer pending */
    public function reject(User $reviewer, string $reason): self
    {
        DB::transaction(function () use ($reviewer, $reason) {
            /** @var self|null $req */
            $req = self::whereKey($this->getKey())->lockForUpdate()->first();
            if (!$req || !$req->isPending()) {
                throw new RuntimeException('This payment has already been ' . ($req->status ?? 'removed') . '.');
            }

            $req->status      = self::STATUS_REJECTED;
            $req->reviewed_by = $reviewer->id;
            $req->reviewed_at = now();
            $req->review_note = $reason;
            $req->save();

            $this->setRawAttributes($req->getAttributes(), true);
        });

        return $this;
    }

    /**
     * Tell the student how the school decided — a push to the login that sent
     * it, opening Fees. Never lets a push failure undo the decision.
     */
    public function notifyStudent(): void
    {
        try {
            $user = User::find($this->user_id);
            if (!$user || $this->isPending()) {
                return;
            }

            $amount = '₹' . number_format((float) ($this->approved_amount ?? $this->amount), 0);
            $kind   = $this->fee_type === 'transport' ? 'transport fee' : 'fee';

            [$type, $title, $body] = $this->status === self::STATUS_APPROVED
                ? ['fee_paid', 'Payment approved', "Your {$kind} payment of {$amount} has been received"
                    . ($this->receiptNumber() ? ' · Receipt ' . $this->receiptNumber() : '') . '.']
                : ['fee_due', 'Payment not accepted', "The school could not accept your {$kind} payment of {$amount}"
                    . ($this->review_note ? ': ' . $this->review_note : '.')];

            app(\App\Services\FirebaseNotificationService::class)->notifyUser($user, $type, [
                'title'  => $title,
                'body'   => $body,
                'screen' => 'Fees',
            ]);
        } catch (\Throwable $e) {
            logger()->warning('QR payment push failed: ' . $e->getMessage());
        }
    }

    /** "Paid on school QR · UTR 412345678901 — Months: APR, MAY" */
    private function bookingRemark(): string
    {
        $remark = 'Paid on school QR' . ($this->utr ? ' · UTR ' . $this->utr : '');
        if ($months = $this->months()) {
            $remark .= ' — Months: ' . strtoupper(implode(', ', $months));
        }
        return $remark;
    }

    /** "2026-27" for any day from April 2026 to March 2027. */
    private static function academicYearOf(Carbon $date): string
    {
        $start = $date->month >= 4 ? $date->year : $date->year - 1;
        return $start . '-' . substr((string) ($start + 1), -2);
    }
}
