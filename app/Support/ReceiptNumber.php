<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A school's next receipt number in a series, e.g. "RCT-4-2026-00008": one past
 * the highest number the series has used so far, in every table that holds
 * numbers of it.
 *
 * Never a count of the year's rows. A receipt deleted, or moved to another
 * table (the transport payments moved out of fee_payments on 14 Sep 2026 kept
 * their RCT- numbers), made the count hand out a number that was still in use,
 * and the receipt_number unique key then refused that school's every fee.
 *
 * Call it inside the transaction that saves the payment: the first table's
 * rows of the series are locked until it commits.
 */
final class ReceiptNumber
{
    /**
     * @param string   $prefix "RCT-4-2026-": the number follows it
     * @param string[] $tables the payment's own table first, then any other
     *                         table holding numbers of the same series
     */
    public static function next(string $prefix, array $tables, int $digits = 5): string
    {
        // The payment's own table is always there; another one, or its receipt
        // numbers, may not be (a bare install).
        $tables  = array_values(array_filter($tables, fn ($table, $i) => $i === 0 || Schema::hasColumn($table, 'receipt_number'), ARRAY_FILTER_USE_BOTH));
        $highest = 0;

        foreach ($tables as $i => $table) {
            $query = DB::table($table)->where('receipt_number', 'like', $prefix . '%');
            if ($i === 0) {
                $query->lockForUpdate();
            }
            // Longest first, then highest: "00010" after "00009", "100000" after "99999".
            $last = $query->orderByRaw('LENGTH(receipt_number) DESC')
                ->orderByDesc('receipt_number')
                ->value('receipt_number');

            $tail = $last !== null ? substr((string) $last, strlen($prefix)) : '';
            if ($tail !== '' && ctype_digit($tail)) {
                $highest = max($highest, (int) $tail);
            }
        }

        // A number some odd row already carries is skipped, never reused.
        $number = $highest + 1;
        while (self::taken($prefix . str_pad((string) $number, $digits, '0', STR_PAD_LEFT), $tables)) {
            $number++;
        }

        return $prefix . str_pad((string) $number, $digits, '0', STR_PAD_LEFT);
    }

    private static function taken(string $receipt, array $tables): bool
    {
        foreach ($tables as $table) {
            if (DB::table($table)->where('receipt_number', $receipt)->exists()) {
                return true;
            }
        }

        return false;
    }
}
