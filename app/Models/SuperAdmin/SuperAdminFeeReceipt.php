<?php

namespace App\Models\SuperAdmin;

use App\Models\Organization;
use App\Models\Student\StudentDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Money received from a school on its Super Admin fee, one row per time it
 * came in, on the day it came in (payment_date).
 *
 * A SuperAdminFeePayment row holds everything collected so far for one
 * student / period, so it cannot say on which day each part came: a part
 * paid student topped up later moves the whole amount to the later date.
 * Reports reads these rows instead — an "Amount Received" is one row for the
 * whole sum, and a single Record / Edit / Mark Paid / clear writes what it
 * changed (SuperAdminFeePayment's saved / deleted events), so the rows always
 * add up to the payments' amounts.
 */
class SuperAdminFeeReceipt extends Model
{
    protected $fillable = [
        'organization_id',
        'super_admin_fee_structure_id',
        'amount',
        'academic_year',
        'payment_mode',
        'payment_date',
        'receipt_number',
        'label',
        'items',
        'remark',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'payment_date' => 'date',
        'items'        => 'integer',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(SuperAdminFeeStructure::class, 'super_admin_fee_structure_id');
    }

    /** "2026-04" → "Apr 2026", "2026-Q1" → "Q1 2026", a year key stays as it is. */
    public static function periodLabel(?string $key): string
    {
        $key = (string) $key;
        if (preg_match('/^(\d{4})-(\d{2})$/', $key, $m)) {
            return \Illuminate\Support\Carbon::create((int) $m[1], (int) $m[2], 1)->format('M Y');
        }
        if (preg_match('/^(\d{4})-(Q\d)$/', $key, $m)) {
            return $m[2] . ' ' . $m[1];
        }

        return $key !== '' ? 'FY ' . $key : '—';
    }

    /** What a set of payment rows covered: "215 students", "Apr 2026 – Jun 2026", a student's name. */
    public static function describe(array $studentIds, array $periodKeys): string
    {
        $studentIds = array_values(array_filter($studentIds));
        $periodKeys = array_values(array_filter($periodKeys, fn ($k) => $k !== null && $k !== ''));

        if ($studentIds) {
            if (count($studentIds) === 1) {
                return StudentDetail::whereKey($studentIds[0])->value('full_name') ?: '1 student';
            }

            return count($studentIds) . ' students';
        }
        if ($periodKeys) {
            sort($periodKeys);
            $first = self::periodLabel($periodKeys[0]);
            $last  = self::periodLabel(end($periodKeys));

            return $first === $last ? $first : $first . ' – ' . $last;
        }

        return '';
    }

    /**
     * Note what one payment row's save changed. The amount moved on its own
     * date; a new date takes the old amount off the old day and puts the new
     * one on the new day.
     */
    public static function recordChange(SuperAdminFeePayment $payment, float $oldAmount, ?string $oldDate, bool $created = false): void
    {
        $newAmount = round((float) $payment->amount, 2);
        $oldAmount = round($oldAmount, 2);
        $newDate   = $payment->payment_date?->toDateString();

        if ($created || $oldDate === $newDate) {
            self::note($payment, round($newAmount - $oldAmount, 2), $newDate);
            return;
        }

        self::note($payment, -$oldAmount, $oldDate);
        self::note($payment, $newAmount, $newDate);
    }

    /** One receipt row for a single payment row's change (nothing for a zero change). */
    public static function note(SuperAdminFeePayment $payment, float $amount, ?string $date): void
    {
        if (abs($amount) < 0.005) {
            return;
        }

        try {
            self::create([
                'organization_id'              => $payment->organization_id,
                'super_admin_fee_structure_id' => $payment->super_admin_fee_structure_id,
                'amount'                       => $amount,
                'academic_year'                => $payment->academic_year,
                'payment_mode'                 => $payment->payment_mode,
                'payment_date'                 => $date,
                'receipt_number'               => $payment->receipt_number,
                'label'                        => self::describe([$payment->student_detail_id], [$payment->installment_period]),
                'items'                        => 1,
                'remark'                       => $payment->remark,
            ]);
        } catch (\Throwable $e) {
            // The payment itself is saved; a missing receipts table never stops it.
            report($e);
        }
    }
}
