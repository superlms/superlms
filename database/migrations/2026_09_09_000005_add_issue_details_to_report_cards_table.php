<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Details the admin fills in when issuing a report card.
 *
 * The registration number, the teacher's remark and the pass/fail line were
 * either pulled off the student record or worked out from the percentage, so
 * the school had no way to say what should actually be printed. They are
 * entered on the issue form now and stored with the card.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('report_cards')) {
            return;
        }

        Schema::table('report_cards', function (Blueprint $table) {
            if (!Schema::hasColumn('report_cards', 'regd_no')) {
                $table->string('regd_no')->nullable()->after('academic_year');
            }
            if (!Schema::hasColumn('report_cards', 'remark')) {
                $table->text('remark')->nullable()->after('regd_no');
            }
            if (!Schema::hasColumn('report_cards', 'result')) {
                // 'PASSED' / 'FAILED', or null to keep deriving it from marks.
                $table->string('result', 20)->nullable()->after('remark');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('report_cards')) {
            return;
        }

        Schema::table('report_cards', function (Blueprint $table) {
            foreach (['regd_no', 'remark', 'result'] as $column) {
                if (Schema::hasColumn('report_cards', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
