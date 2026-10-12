<?php

use App\Models\SuperAdmin\SuperAdminFeeReceipt;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Money received from a school on its Super Admin fee, one row each time
     * it came (SuperAdminFeeReceipt). The payments recorded so far become
     * their first rows: rows saved together (same school, fee, date, mode and
     * remark within the same minute — one "Amount Received") make one row.
     */
    public function up(): void
    {
        if (!Schema::hasTable('super_admin_fee_receipts')) {
            Schema::create('super_admin_fee_receipts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->foreignId('super_admin_fee_structure_id')->nullable()
                    ->constrained('super_admin_fee_structures')->cascadeOnDelete();
                $table->decimal('amount', 12, 2);
                $table->string('academic_year', 10)->nullable();
                $table->string('payment_mode')->nullable();
                $table->date('payment_date')->nullable();
                $table->string('receipt_number')->nullable();
                $table->string('label')->nullable();
                $table->unsignedInteger('items')->default(1);
                $table->text('remark')->nullable();
                $table->timestamps();

                $table->index('payment_date');
                $table->index(['organization_id', 'payment_date']);
            });
        }

        if (!Schema::hasTable('super_admin_fee_payments') || DB::table('super_admin_fee_receipts')->exists()) {
            return;
        }

        $groups = [];
        foreach (DB::table('super_admin_fee_payments')->where('amount', '!=', 0)->orderBy('id')->get() as $p) {
            $date = $p->payment_date ?: ($p->created_at ? substr((string) $p->created_at, 0, 10) : null);
            $key  = implode('|', [
                $p->organization_id, $p->super_admin_fee_structure_id, $date, $p->payment_mode,
                (string) $p->remark, substr((string) $p->created_at, 0, 16),
            ]);
            $groups[$key]['date']   = $date;
            $groups[$key]['rows'][] = $p;
        }

        foreach ($groups as ['date' => $date, 'rows' => $rows]) {
            $first = $rows[0];
            $last  = end($rows);

            $numbers = array_values(array_filter(array_map(fn ($r) => $r->receipt_number, $rows)));
            $number  = count($numbers) > 1 ? $numbers[0] . ' – ' . end($numbers) : ($numbers[0] ?? null);

            DB::table('super_admin_fee_receipts')->insert([
                'organization_id'              => $first->organization_id,
                'super_admin_fee_structure_id' => $first->super_admin_fee_structure_id,
                'amount'                       => round(array_sum(array_map(fn ($r) => (float) $r->amount, $rows)), 2),
                'academic_year'                => $first->academic_year,
                'payment_mode'                 => $first->payment_mode,
                'payment_date'                 => $date,
                'receipt_number'               => $number,
                'label'                        => SuperAdminFeeReceipt::describe(
                    array_map(fn ($r) => $r->student_detail_id, $rows),
                    array_map(fn ($r) => $r->installment_period ?? null, $rows)
                ),
                'items'                        => count($rows),
                'remark'                       => $first->remark,
                'created_at'                   => $last->created_at ?? now(),
                'updated_at'                   => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('super_admin_fee_receipts');
    }
};
