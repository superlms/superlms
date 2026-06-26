<?php

namespace App\Models;

use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\TransportFeePayment;
use App\Models\Student\StudentDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * A single online payment attempt through a gateway (currently PhonePe).
 * On success it is linked to the fee_payments row it produced via fee_payment_id.
 */
class PaymentTransaction extends Model
{
    public const STATE_PENDING   = 'PENDING';
    public const STATE_COMPLETED = 'COMPLETED';
    public const STATE_FAILED    = 'FAILED';

    protected $fillable = [
        'organization_id',
        'student_detail_id',
        'user_id',
        'fee_type',
        'gateway',
        'merchant_order_id',
        'phonepe_order_id',
        'amount',
        'state',
        'fee_payment_id',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta'   => 'array',
    ];

    public function isCompleted(): bool
    {
        return $this->state === self::STATE_COMPLETED;
    }

    public function studentDetail(): BelongsTo
    {
        return $this->belongsTo(StudentDetail::class);
    }

    public function feePayment(): BelongsTo
    {
        return $this->belongsTo(FeePayment::class);
    }

    /**
     * Apply a gateway result to this transaction.
     *
     * Idempotent and concurrency-safe: it locks the row, and on the first
     * COMPLETED result creates the matching fee_payments record exactly once.
     * Safe to call from both webhook and status-polling code paths.
     *
     * @param  string  $state  PENDING | COMPLETED | FAILED
     * @param  array   $raw    The raw gateway payload (stored for audit).
     */
    public function settle(string $state, array $raw = []): self
    {
        DB::transaction(function () use ($state, $raw) {
            /** @var self $txn */
            $txn = self::whereKey($this->getKey())->lockForUpdate()->first();
            if (!$txn) {
                return;
            }

            $meta = $txn->meta ?? [];
            $meta['last_result'] = $raw;
            $txn->meta = $meta;

            if (!empty($raw['orderId'])) {
                $txn->phonepe_order_id = $raw['orderId'];
            }

            // Already settled into a payment record — nothing more to do.
            if ($txn->fee_payment_id || !empty($meta['transport_payment_id'])) {
                $txn->save();
                $this->setRawAttributes($txn->getAttributes(), true);
                return;
            }

            if ($state === self::STATE_COMPLETED) {
                if ($txn->fee_type === 'transport') {
                    // Transport online payments live in transport_fee_payments so the
                    // 12-month schedule (and admin) reflect them; oldest-first
                    // allocation in the schedule carries any excess to later months.
                    $tp = TransportFeePayment::create([
                        'organization_id'   => $txn->organization_id,
                        'transportation_id' => $meta['transportation_id'] ?? null,
                        'student_detail_id' => $txn->student_detail_id,
                        'amount'            => $txn->amount,
                        'payment_mode'      => 'online',
                        'payment_date'      => now()->toDateString(),
                        'academic_year'     => self::currentAcademicYear(),
                        'remark'            => $this->transportRemark($txn, $meta),
                    ]);

                    $meta['transport_payment_id'] = $tp->id;
                    $txn->meta = $meta;
                } else {
                    $student = StudentDetail::find($txn->student_detail_id);

                    $fee = FeePayment::create([
                        'organization_id'   => $txn->organization_id,
                        'student_detail_id' => $txn->student_detail_id,
                        'standard_id'       => $student?->standard_id ?? 0,
                        'section_id'        => $student?->section_id,
                        'fee_type'          => $txn->fee_type,
                        'amount'            => $txn->amount,
                        'payment_mode'      => 'online',
                        'payment_date'      => now()->toDateString(),
                        'submitted_by'      => 'PhonePe',
                        'remark'            => 'Online payment via PhonePe (' . $txn->merchant_order_id . ')',
                    ]);

                    $txn->fee_payment_id = $fee->id;
                }

                $txn->state = self::STATE_COMPLETED;
            } elseif ($state === self::STATE_FAILED) {
                $txn->state = self::STATE_FAILED;
            }

            $txn->save();
            $this->setRawAttributes($txn->getAttributes(), true);
        });

        return $this;
    }

    private function transportRemark(self $txn, array $meta): string
    {
        $base = 'Online payment via PhonePe (' . $txn->merchant_order_id . ')';
        $months = $meta['months'] ?? [];
        if (is_array($months) && count($months)) {
            $base .= ' — Months: ' . strtoupper(implode(', ', $months));
        }
        return $base;
    }

    /** Academic-year label, e.g. "2026-27" (April start). */
    public static function currentAcademicYear(): string
    {
        $start = now()->month >= 4 ? now()->year : now()->year - 1;
        return $start . '-' . substr((string) ($start + 1), -2);
    }
}
