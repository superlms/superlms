<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A seat in a room is a desk, and a desk can hold more than one candidate.
 *
 * `seating_rooms.seat_capacity` is how many sit at one desk, so a room holds
 * rows × columns × seat_capacity candidates; `capacity` keeps holding that
 * total, which is what the planner shares work out against.
 *
 * An assignment is therefore one candidate in one *place* at a desk, not one
 * candidate per desk: `seat_position` says which place, and the uniqueness
 * that used to be (plan, seat) is now (plan, seat, position).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seating_rooms') && !Schema::hasColumn('seating_rooms', 'seat_capacity')) {
            Schema::table('seating_rooms', function (Blueprint $table) {
                $table->unsignedSmallInteger('seat_capacity')->default(1)->after('columns');
            });
        }

        if (Schema::hasTable('seat_assignments') && !Schema::hasColumn('seat_assignments', 'seat_position')) {
            Schema::table('seat_assignments', function (Blueprint $table) {
                $table->unsignedSmallInteger('seat_position')->default(1)->after('seat_id');
            });

            // Swap the old one-student-per-desk unique for one per place at it.
            // Wrapped because the index name differs on installs that predate
            // the convention, and a missing index must not fail the migration.
            try {
                Schema::table('seat_assignments', function (Blueprint $table) {
                    $table->dropUnique('seat_assignments_seating_plan_id_seat_id_unique');
                });
            } catch (\Throwable $e) {
                // already gone — nothing to drop
            }

            try {
                Schema::table('seat_assignments', function (Blueprint $table) {
                    $table->unique(['seating_plan_id', 'seat_id', 'seat_position'], 'seat_assign_plan_seat_pos_unique');
                });
            } catch (\Throwable $e) {
                // already there
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('seat_assignments') && Schema::hasColumn('seat_assignments', 'seat_position')) {
            try {
                Schema::table('seat_assignments', function (Blueprint $table) {
                    $table->dropUnique('seat_assign_plan_seat_pos_unique');
                });
            } catch (\Throwable $e) {
            }

            Schema::table('seat_assignments', function (Blueprint $table) {
                $table->dropColumn('seat_position');
                $table->unique(['seating_plan_id', 'seat_id']);
            });
        }

        if (Schema::hasTable('seating_rooms') && Schema::hasColumn('seating_rooms', 'seat_capacity')) {
            Schema::table('seating_rooms', function (Blueprint $table) {
                $table->dropColumn('seat_capacity');
            });
        }
    }
};
