<?php

use App\Support\StudentNumbers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Roll numbers are now the class's whole code + a 3-digit serial from 001
 * (code "05" → 05001, 05002 …) instead of the code's last digit + a 2-digit
 * serial (501, 502 … 5118). Every class is numbered again here: its students
 * keep the order their rolls stood in (by the roll's number, then — no roll,
 * or one that is not a number — the order they were added), from 001 with no
 * gaps, and each admit card that showed a student's old roll shows the new.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_details') || !Schema::hasTable('standards')
            || !Schema::hasColumn('student_details', 'roll_no') || !Schema::hasColumn('standards', 'code')) {
            return;
        }

        $classes = DB::table('standards')
            ->whereIn('id', DB::table('student_details')->whereNotNull('standard_id')->select('standard_id'))
            ->orderBy('id')
            ->get(['id', 'code']);

        foreach ($classes as $class) {
            DB::transaction(fn () => StudentNumbers::renumberClass((int) $class->id, $class->code));
        }
    }

    public function down(): void
    {
        // The old rolls are not kept anywhere to go back to.
    }
};
