<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A school that runs under two names (say a Public School for Nursery–5 and
 * an Inter College for 6–12): the Super Admin gives it a second name and
 * logo (Schools → the school → Custom Fee), and each class says which of the
 * two it belongs to. A class's ID cards, admit cards, report cards, receipts
 * and certificates then carry that name and logo (App\Support\SchoolBrand);
 * the address and everything else stay the school's own.
 *
 * - organizations.second_name / second_logo: blank = one name only.
 * - standards.wing: 1 = the school's own name (every class so far), 2 = the second.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            if (!Schema::hasColumn('organizations', 'second_name')) {
                $table->string('second_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('organizations', 'second_logo')) {
                $table->string('second_logo')->nullable()->after('logo');
            }
        });

        Schema::table('standards', function (Blueprint $table) {
            if (!Schema::hasColumn('standards', 'wing')) {
                $table->unsignedTinyInteger('wing')->default(1)->after('board');
            }
        });
    }

    public function down(): void
    {
        Schema::table('standards', function (Blueprint $table) {
            if (Schema::hasColumn('standards', 'wing')) {
                $table->dropColumn('wing');
            }
        });

        Schema::table('organizations', function (Blueprint $table) {
            foreach (['second_name', 'second_logo'] as $col) {
                if (Schema::hasColumn('organizations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
