<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Takes off the admission dates that were never entered.
 *
 * A student saved without an admission date — from the panel, the admin or
 * teacher app, or by the Super Admin — was given the day of saving as one. So
 * the "admission date" of such a student is simply the day they were added to
 * the software (or, for a record that had none, the day it was next edited).
 *
 * Those are removed here: an admission date that is the very day the record
 * was created, or — being later than that day — the day it was last edited. A
 * date entered by the school is earlier than the day of entry, and is kept. A
 * student who really was admitted on the day they were entered cannot be told
 * apart from the rest and loses the date too; so every date removed is first
 * copied to `student_admission_dates_removed`, from where it can be put back.
 *
 * The school enters the real date on the student's form (and the Issue TC
 * form asks for it when it is missing).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('student_details') || ! Schema::hasColumn('student_details', 'date_of_admission')) {
            return;
        }

        if (! Schema::hasTable('student_admission_dates_removed')) {
            Schema::create('student_admission_dates_removed', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_detail_id')->index();
                $table->date('date_of_admission');
                $table->timestamp('removed_at')->nullable();
            });
        }

        $assumed = fn () => DB::table('student_details')
            ->whereNotNull('date_of_admission')
            ->where(function ($q) {
                $q->whereRaw('DATE(date_of_admission) = DATE(created_at)')
                    ->orWhere(function ($later) {
                        $later->whereRaw('DATE(date_of_admission) > DATE(created_at)')
                            ->whereRaw('DATE(date_of_admission) = DATE(updated_at)');
                    });
            });

        $now = now();
        $assumed()->orderBy('id')->select(['id', 'date_of_admission'])->chunkById(500, function ($rows) use ($now) {
            DB::table('student_admission_dates_removed')->insert(
                $rows->map(fn ($r) => [
                    'student_detail_id' => $r->id,
                    'date_of_admission' => substr((string) $r->date_of_admission, 0, 10),
                    'removed_at'        => $now,
                ])->all()
            );
        });

        // Only the rows just copied — and without touching updated_at, so the
        // students do not all read as edited today.
        DB::table('student_details')
            ->whereIn('id', DB::table('student_admission_dates_removed')->where('removed_at', $now)->select('student_detail_id'))
            ->update(['date_of_admission' => null, 'updated_at' => DB::raw('updated_at')]);
    }

    /** Puts back every date this migration removed. */
    public function down(): void
    {
        if (! Schema::hasTable('student_admission_dates_removed')) {
            return;
        }

        DB::table('student_admission_dates_removed')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('student_details')
                    ->where('id', $row->student_detail_id)
                    ->whereNull('date_of_admission')
                    ->update(['date_of_admission' => $row->date_of_admission, 'updated_at' => DB::raw('updated_at')]);
            }
        });

        Schema::dropIfExists('student_admission_dates_removed');
    }
};
