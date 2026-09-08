<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A student announcement can be aimed at one class instead of the whole school.
 *
 * NULL means "every class" — that is what every existing row means, and what a
 * new announcement means unless a class is picked. Kept nullable on purpose:
 * a 0 default would make "all classes" indistinguishable from a real class id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            if (!Schema::hasColumn('announcements', 'standard_id')) {
                $table->unsignedBigInteger('standard_id')->nullable()->after('type')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            if (Schema::hasColumn('announcements', 'standard_id')) {
                $table->dropColumn('standard_id');
            }
        });
    }
};
