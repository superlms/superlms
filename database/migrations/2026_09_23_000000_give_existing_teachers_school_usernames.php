<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Every teacher who was on the rolls before usernames came in signs in as
 * their first name at the school code: meera@tds. A second Meera in the same
 * school is meera2@tds, a third meera3@tds, in the order they were added.
 *
 * Only those teachers — the ones 2026_09_21 gave a username from their full
 * name. Anyone added since, whose username a school admin typed in, keeps it.
 * Nobody's password or session changes; only what they type at login.
 */
return new class extends Migration
{
    /** When usernames went live; teachers added before it are renamed. */
    private const CUTOFF_IST = '2026-09-23 10:10:00';

    public function up(): void
    {
        // Room for a first name, an @ and a school code.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `users` MODIFY `username` VARCHAR(50) NULL');
        }

        $cutoff = Carbon::parse(self::CUTOFF_IST, 'Asia/Kolkata')
            ->setTimezone(config('app.timezone'))
            ->toDateTimeString();

        $codes = DB::table('organizations')->pluck('school_code', 'id');

        $teachers = DB::table('users')
            ->where('role', 'teacher')
            ->where(fn ($q) => $q->where('created_at', '<', $cutoff)->orWhereNull('created_at'))
            ->orderBy('organization_id')
            ->orderBy('id')
            ->get(['id', 'name', 'organization_id']);

        // Clear theirs first, so a new name can never collide with an old one
        // that is about to go.
        DB::table('users')->whereIn('id', $teachers->pluck('id'))->update(['username' => null]);

        foreach ($teachers as $t) {
            $first = preg_replace('/[^a-z]/', '', strtolower(strtok(trim((string) $t->name) . ' ', ' ')));
            $first = substr($first ?: 'teacher', 0, 30);

            $code = preg_replace('/[^a-z0-9]/', '', strtolower((string) ($codes[$t->organization_id] ?? '')));
            $code = substr($code ?: 'school' . (int) $t->organization_id, 0, 18);

            $candidate = $first . '@' . $code;
            for ($n = 2; DB::table('users')->where('username', $candidate)->exists(); $n++) {
                $candidate = $first . $n . '@' . $code;
            }

            DB::table('users')->where('id', $t->id)->update(['username' => $candidate]);
        }
    }

    public function down(): void
    {
        // The earlier names are not kept; the new ones stay.
    }
};
