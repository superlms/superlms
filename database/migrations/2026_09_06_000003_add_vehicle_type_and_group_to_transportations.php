<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vehicle type moves off the driver and onto the route: one route row per
 * vehicle type, tied together by a shared route_group so the listing can show
 * them as a single route with its types beside it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transportations', function (Blueprint $table) {
            if (!Schema::hasColumn('transportations', 'vehicle_type')) {
                $table->string('vehicle_type', 50)->nullable()->after('route_name');
            }

            if (!Schema::hasColumn('transportations', 'route_group')) {
                $table->string('route_group', 64)->nullable()->after('vehicle_type');
                $table->index(['organization_id', 'route_group'], 'transportations_group_idx');
            }
        });

        // Every existing route becomes a group of one, so grouping has a key
        // to work with from day one.
        DB::table('transportations')
            ->whereNull('route_group')
            ->update(['route_group' => DB::raw("CONCAT('r', id)")]);
    }

    public function down(): void
    {
        Schema::table('transportations', function (Blueprint $table) {
            if (Schema::hasColumn('transportations', 'route_group')) {
                $table->dropIndex('transportations_group_idx');
                $table->dropColumn('route_group');
            }

            if (Schema::hasColumn('transportations', 'vehicle_type')) {
                $table->dropColumn('vehicle_type');
            }
        });
    }
};
