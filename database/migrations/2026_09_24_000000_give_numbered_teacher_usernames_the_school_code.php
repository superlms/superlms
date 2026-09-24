<?php

use App\Support\Usernames;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Every teacher's username ends in the school code. Those still made from
 * their name with a number on the end — meera.sharma, meera.sharma1, anything
 * without an @ — take the school's form: their first name at the school code,
 * meera@tds, and meera2@tds for a second Meera there, in the order they were
 * added. A teacher with no username yet gets one the same way. Usernames that
 * already carry an @ are left as they are.
 *
 * The old username is kept in previous_username and still signs its teacher
 * in, so nobody who knew only that is locked out. Passwords and sessions do
 * not change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'previous_username')) {
                $table->string('previous_username', 50)->nullable()->index()->after('username');
            }
        });

        DB::table('users')
            ->where('role', 'teacher')
            ->whereNotNull('organization_id')
            ->where(fn ($q) => $q->whereNull('username')->orWhere('username', 'not like', '%@%'))
            ->orderBy('id')
            ->select(['id', 'name', 'username', 'organization_id'])
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $old = trim((string) $row->username);

                    DB::table('users')->where('id', $row->id)->update([
                        'username'          => Usernames::forSchool($row->name, (int) $row->organization_id, $row->id),
                        'previous_username' => $old !== '' ? $old : null,
                    ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('users')
            ->whereNotNull('previous_username')
            ->update(['username' => DB::raw('previous_username')]);

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'previous_username')) {
                $table->dropIndex(['previous_username']);
                $table->dropColumn('previous_username');
            }
        });
    }
};
