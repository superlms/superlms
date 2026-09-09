<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rebuild every admission number that already exists on the new format.
 *
 * Students added before this were numbered YY + SCHOOL_CODE + class digit +
 * section digit + a serial counted per prefix, so the serial restarted for each
 * class and each birth year — 26TDS110002, 26TDS220002 and 26TDS330002 all
 * carried 0002. The format is now YY + SCHOOL_CODE + DOB_YY + a serial counted
 * across the whole school, handed out one by one.
 *
 * Numbers are reissued in the order the students were added, so the first child
 * on the register keeps 0001. YY is the year that row was created — the year
 * that was running when the student was added, which is what the live generator
 * stamps. A student with no date of birth on file gets "00" in that slot, same
 * as the generator would give them.
 *
 * admission_no lives only on student_details; ID cards, receipts, admit cards
 * and report cards all read it through the relation, so they pick this up on
 * their next render.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_details') || !Schema::hasColumn('student_details', 'admission_no')) {
            return;
        }

        $orgIds = DB::table('student_details')
            ->select('organization_id')
            ->distinct()
            ->pluck('organization_id');

        foreach ($orgIds as $orgId) {
            $schoolCode = (string) (DB::table('organizations')->where('id', $orgId)->value('school_code') ?? '');

            $students = DB::table('student_details')
                ->where('organization_id', $orgId)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(['id', 'dob', 'created_at', 'date_of_admission']);

            $serial = 0;

            DB::transaction(function () use ($students, $schoolCode, &$serial) {
                foreach ($students as $student) {
                    $serial++;

                    $yy    = $this->yearSuffix($student->created_at ?: $student->date_of_admission, (string) now()->year);
                    $dobYy = $this->yearSuffix($student->dob, '0000');

                    DB::table('student_details')
                        ->where('id', $student->id)
                        ->update([
                            'admission_no' => $yy . $schoolCode . $dobYy
                                . str_pad((string) $serial, 4, '0', STR_PAD_LEFT),
                        ]);
                }
            });

            logger()->info('Renumbered student admission numbers', [
                'organization_id' => $orgId,
                'students'        => $serial,
            ]);
        }
    }

    public function down(): void
    {
        // The numbers this replaced are not recoverable, and they were the
        // broken ones. Deliberately a no-op.
    }

    /** Last two digits of a date's year, or of $fallback when it can't be read. */
    private function yearSuffix($value, string $fallback): string
    {
        if (!empty($value)) {
            try {
                return substr(\Carbon\Carbon::parse((string) $value)->format('Y'), -2);
            } catch (\Throwable $e) {
                // fall through to the fallback below
            }
        }

        return substr(str_pad($fallback, 4, '0', STR_PAD_LEFT), -2);
    }
};
