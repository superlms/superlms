<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bring receipts written by older builds up to the shape today's code writes,
 * so an old payment prints exactly like one taken this morning.
 *
 * Three things drifted:
 *  1. fee_payments.submitted_by holds the collector's NAME now; older rows hold
 *     a user id, which printed on the receipt as a bare number.
 *  2. transport_fee_payments.academic_year is filled in now; older rows left it
 *     empty, so the receipt showed no year.
 *  3. transport_fee_payments.receipt_number is generated now; a handful of very
 *     old rows have none, and the receipt footing printed blank.
 *
 * Every step is guarded and idempotent: nothing to fix means nothing runs.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->nameTheFeeCollectors();
        $this->fillTransportAcademicYears();
        $this->fillTransportReceiptNumbers();
    }

    public function down(): void
    {
        // Names cannot be turned back into ids, and a receipt number, once
        // printed, must not change. Nothing to undo.
    }

    /** fee_payments.submitted_by: user id → that user's name. */
    private function nameTheFeeCollectors(): void
    {
        if (! Schema::hasTable('fee_payments') || ! Schema::hasColumn('fee_payments', 'submitted_by')) {
            return;
        }

        $ids = DB::table('fee_payments')
            ->whereNotNull('submitted_by')
            ->where('submitted_by', '!=', '')
            ->whereRaw("submitted_by REGEXP '^[0-9]+$'")
            ->distinct()
            ->pluck('submitted_by');

        if ($ids->isEmpty()) {
            return;
        }

        $names = DB::table('users')->whereIn('id', $ids)->pluck('name', 'id');

        foreach ($names as $id => $name) {
            if (! $name) {
                continue;
            }

            DB::table('fee_payments')
                ->where('submitted_by', (string) $id)
                ->update(['submitted_by' => $name]);
        }
    }

    /**
     * transport_fee_payments.academic_year, derived from the payment date the
     * same way the app derives it when a payment is taken: the year the money
     * came in, paired with the next.
     */
    private function fillTransportAcademicYears(): void
    {
        if (! Schema::hasTable('transport_fee_payments') || ! Schema::hasColumn('transport_fee_payments', 'academic_year')) {
            return;
        }

        DB::table('transport_fee_payments')
            ->where(fn ($q) => $q->whereNull('academic_year')->orWhere('academic_year', ''))
            ->whereNotNull('payment_date')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $year = (int) date('Y', strtotime((string) $row->payment_date));

                    DB::table('transport_fee_payments')
                        ->where('id', $row->id)
                        ->update(['academic_year' => $year . '-' . substr((string) ($year + 1), -2)]);
                }
            });
    }

    /** transport_fee_payments.receipt_number: TRP{org}{year}{00001}, as today. */
    private function fillTransportReceiptNumbers(): void
    {
        if (! Schema::hasTable('transport_fee_payments') || ! Schema::hasColumn('transport_fee_payments', 'receipt_number')) {
            return;
        }

        $blank = DB::table('transport_fee_payments')
            ->where(fn ($q) => $q->whereNull('receipt_number')->orWhere('receipt_number', ''))
            ->orderBy('id')
            ->get(['id', 'organization_id', 'payment_date', 'created_at']);

        foreach ($blank as $row) {
            $date = $row->payment_date ?: $row->created_at;
            $year = $date ? date('y', strtotime((string) $date)) : date('y');
            $base = 'TRP' . $row->organization_id . $year;

            // Scoped to the school as well as the prefix: "TRP" + org + year
            // runs together, so a LIKE alone could reach into another school's
            // series (org 4 / year 26 and org 42 / year 60 both start TRP426).
            $last = DB::table('transport_fee_payments')
                ->where('organization_id', $row->organization_id)
                ->where('receipt_number', 'like', $base . '%')
                ->orderByDesc('receipt_number')
                ->value('receipt_number');

            $serial = $last ? ((int) substr((string) $last, -5) + 1) : 1;

            DB::table('transport_fee_payments')
                ->where('id', $row->id)
                ->update(['receipt_number' => $base . str_pad((string) $serial, 5, '0', STR_PAD_LEFT)]);
        }
    }
};
