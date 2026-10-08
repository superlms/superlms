<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The circle a student's or teacher's photo shows in the lists, set with the
 * photo's Profile button (web panel and the class teacher's app): where it sits
 * on the photo and how big, kept with a mark of the photo it was set on
 * (App\Support\PhotoCircle). The photo itself is not changed.
 *
 * A new column only — no row is changed; with none set the lists show the top
 * of the photo, as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'photo_circle')) {
            Schema::table('users', fn (Blueprint $t) => $t->string('photo_circle')->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'photo_circle')) {
            Schema::table('users', fn (Blueprint $t) => $t->dropColumn('photo_circle'));
        }
    }
};
