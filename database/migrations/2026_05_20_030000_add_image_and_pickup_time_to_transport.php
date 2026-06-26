<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('driver_details', function (Blueprint $table) {
            if (!Schema::hasColumn('driver_details', 'image')) {
                $table->string('image')->nullable()->after('user_id');
            }
        });

        Schema::table('transportations', function (Blueprint $table) {
            if (!Schema::hasColumn('transportations', 'pickup_time')) {
                $table->string('pickup_time')->nullable()->after('driver_detail_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('driver_details', function (Blueprint $table) {
            if (Schema::hasColumn('driver_details', 'image')) {
                $table->dropColumn('image');
            }
        });

        Schema::table('transportations', function (Blueprint $table) {
            if (Schema::hasColumn('transportations', 'pickup_time')) {
                $table->dropColumn('pickup_time');
            }
        });
    }
};
