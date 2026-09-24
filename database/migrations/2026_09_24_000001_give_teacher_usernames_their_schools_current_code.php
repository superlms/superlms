<?php

use App\Support\Usernames;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Teachers whose username was made in the school's form while the school had
 * another code — jitendra@006, from before the school became ASIC — take the
 * code it has now: jitendra@asic. From here on the Organization model does the
 * same whenever a school's code is changed. The old username is kept in
 * previous_username and still signs its teacher in.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('organizations')->orderBy('id')->pluck('id')
            ->each(fn ($id) => Usernames::followSchoolCode((int) $id));
    }

    public function down(): void
    {
        // The earlier usernames are in previous_username, which the migration
        // that added it puts back when it is rolled back.
    }
};
