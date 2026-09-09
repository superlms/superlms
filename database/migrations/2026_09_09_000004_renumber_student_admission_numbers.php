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
 *
 * The school is read from users.organization_id, joined in. student_details has
 * an organization_id of its own in production, but that column is added by the
 * `lms:migrate` command rather than a migration, so it does not exist yet when
 * this file runs against a freshly migrated database. It is preferred when
 * present and the join is the fallback.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_details') || !Schema::hasTable('users')) {
            return;
        }
        if (!Schema::hasColumn('student_details', 'admission_no')) {
            return;
        }

        // NULLIF because these FK columns are `default(0)` rather than nullable,
        // so an unset school reads as 0, not NULL.
        $orgExpr = Schema::hasColumn('student_details', 'organization_id')
            ? 'COALESCE(NULLIF(sd.organization_id, 0), u.organization_id)'
            : 'u.organization_id';

        $students = DB::table('student_details as sd')
            ->leftJoin('users as u', 'u.id', '=', 'sd.user_id')
            ->selectRaw("sd.id, sd.dob, sd.created_at, sd.date_of_admission, {$orgExpr} as org_id")
            ->orderByRaw($orgExpr)
            ->orderBy('sd.created_at')
            ->orderBy('sd.id')
            ->get();

        if ($students->isEmpty()) {
            return;
        }

        $schoolCodes = DB::table('organizations')->pluck('school_code', 'id');

        // One running counter per school, spent in the order students joined.
        $serials = [];

        DB::transaction(function () use ($students, $schoolCodes, &$serials) {
            foreach ($students as $student) {
                $orgId = (int) ($student->org_id ?? 0);

                $serials[$orgId] = ($serials[$orgId] ?? 0) + 1;

                $yy    = $this->yearSuffix($student->created_at ?: $student->date_of_admission, (string) now()->year);
                $dobYy = $this->yearSuffix($student->dob, '0000');
                $code  = (string) ($schoolCodes[$orgId] ?? '');

                DB::table('student_details')
                    ->where('id', $student->id)
                    ->update([
                        'admission_no' => $yy . $code . $dobYy
                            . str_pad((string) $serials[$orgId], 4, '0', STR_PAD_LEFT),
                    ]);
            }
        });

        foreach ($serials as $orgId => $count) {
            logger()->info('Renumbered student admission numbers', [
                'organization_id' => $orgId,
                'students'        => $count,
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
