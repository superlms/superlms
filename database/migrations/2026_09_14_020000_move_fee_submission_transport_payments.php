<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fee Submission used to book a transport fee as a fee_payments row
 * (fee_type = transport). Transport payments live in transport_fee_payments —
 * the table the Transport module, View Fee, the receipts and the app all read —
 * so a bus fee taken there showed on the Payments list and nowhere else: not in
 * the student's transport fees, not in their month statuses, not in the app.
 *
 * Fee Submission now writes transport_fee_payments itself; this moves the rows
 * it booked before. Each keeps its amount, date, mode, remark and the receipt
 * number already printed for it (an RCT- number, which is also how down()
 * finds them again). Left where they are: a row an online checkout is linked to
 * (payment_transactions.fee_payment_id), and a row carrying a penalty or a
 * waiver, which the transport table has no place for.
 *
 * Idempotent: once moved, a row is no longer in fee_payments to move again.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fee_payments') || ! Schema::hasTable('transport_fee_payments')) {
            return;
        }

        $linked = Schema::hasTable('payment_transactions') && Schema::hasColumn('payment_transactions', 'fee_payment_id')
            ? DB::table('payment_transactions')->whereNotNull('fee_payment_id')->pluck('fee_payment_id')->all()
            : [];

        $rows = DB::table('fee_payments')
            ->where('fee_type', 'transport')
            ->when($linked, fn ($q) => $q->whereNotIn('id', $linked))
            ->when(Schema::hasColumn('fee_payments', 'penalty_amount'),
                fn ($q) => $q->where(fn ($w) => $w->whereNull('penalty_amount')->orWhere('penalty_amount', 0)))
            ->when(Schema::hasColumn('fee_payments', 'waiver_amount'),
                fn ($q) => $q->where(fn ($w) => $w->whereNull('waiver_amount')->orWhere('waiver_amount', 0)))
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $receipt = $row->receipt_number ?: 'RCT-' . $row->id;

            // A receipt number is unique in the transport table; if it is already
            // there, this payment was moved before — leave both rows alone.
            if (DB::table('transport_fee_payments')->where('receipt_number', $receipt)->exists()) {
                continue;
            }

            DB::transaction(function () use ($row, $receipt) {
                // The student's route, the active one first.
                $routeId = DB::table('transportation_students as ts')
                    ->join('transportations as t', 't.id', '=', 'ts.transportation_id')
                    ->where('ts.organization_id', $row->organization_id)
                    ->where('ts.student_detail_id', $row->student_detail_id)
                    ->orderByDesc('t.is_active')
                    ->value('t.id');

                $date = $row->payment_date ?: date('Y-m-d', strtotime((string) $row->created_at));
                $year = (int) date('Y', strtotime((string) $date));

                DB::table('transport_fee_payments')->insert([
                    'organization_id'   => $row->organization_id,
                    'transportation_id' => $routeId,
                    'student_detail_id' => $row->student_detail_id,
                    'amount'            => $row->amount,
                    'payment_mode'      => $row->payment_mode ?: 'cash',
                    'payment_date'      => $date,
                    'receipt_number'    => $receipt,
                    // The same year label the app writes: the year the money came in, paired with the next.
                    'academic_year'     => $year . '-' . substr((string) ($year + 1), -2),
                    'remark'            => $row->remark,
                    'submitted_by'      => $this->collectorId($row),
                    'created_at'        => $row->created_at,
                    'updated_at'        => $row->updated_at,
                ]);

                DB::table('fee_payments')->where('id', $row->id)->delete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('fee_payments') || ! Schema::hasTable('transport_fee_payments')) {
            return;
        }

        // Transport's own receipts are TRCT-/TRP numbers; RCT- ones came from here.
        $rows = DB::table('transport_fee_payments')
            ->where('receipt_number', 'like', 'RCT-%')
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            DB::transaction(function () use ($row) {
                $student = DB::table('student_details')->where('id', $row->student_detail_id)->first(['standard_id', 'section_id']);

                $values = [
                    'organization_id'   => $row->organization_id,
                    'student_detail_id' => $row->student_detail_id,
                    'standard_id'       => $student->standard_id ?? 0,
                    'section_id'        => $student->section_id ?? 0,
                    'fee_type'          => 'transport',
                    'amount'            => $row->amount,
                    'payment_mode'      => $row->payment_mode,
                    'payment_date'      => $row->payment_date,
                    'remark'            => $row->remark,
                    // fee_payments keeps the collector's name
                    'submitted_by'      => $row->submitted_by
                        ? (DB::table('users')->where('id', $row->submitted_by)->value('name') ?? (string) $row->submitted_by)
                        : null,
                    'receipt_number'    => $row->receipt_number,
                    'created_at'        => $row->created_at,
                    'updated_at'        => $row->updated_at,
                ];
                foreach (['penalty_amount', 'waiver_amount'] as $column) {
                    if (Schema::hasColumn('fee_payments', $column)) {
                        $values[$column] = 0;
                    }
                }

                DB::table('fee_payments')->insert($values);
                DB::table('transport_fee_payments')->where('id', $row->id)->delete();
            });
        }
    }

    /**
     * fee_payments keeps the collector's name; transport_fee_payments keeps
     * their user id. The name is matched within the school only.
     */
    private function collectorId(object $row): ?int
    {
        $by = trim((string) ($row->submitted_by ?? ''));
        if ($by === '') {
            return null;
        }
        if (ctype_digit($by)) {
            return (int) $by;
        }

        $id = DB::table('users')
            ->where('organization_id', $row->organization_id)
            ->where('name', $by)
            ->value('id');

        return $id ? (int) $id : null;
    }
};
