<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The website "Schedule a Call" flow reuses the website_demos table but needs a
 * preferred date + timeslot. Guarded with hasColumn so it's a no-op where the
 * columns already exist (keeps us safe against schema drift / lms:migrate).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_demos', function (Blueprint $table) {
            if (!Schema::hasColumn('website_demos', 'preferred_date')) {
                $table->date('preferred_date')->nullable()->after('role');
            }
            if (!Schema::hasColumn('website_demos', 'preferred_time')) {
                $table->string('preferred_time')->nullable()->after('preferred_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('website_demos', function (Blueprint $table) {
            foreach (['preferred_date', 'preferred_time'] as $col) {
                if (Schema::hasColumn('website_demos', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
