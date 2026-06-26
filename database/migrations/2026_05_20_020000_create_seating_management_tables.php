<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rooms used for exam seating
        if (!Schema::hasTable('seating_rooms')) {
            Schema::create('seating_rooms', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id');
                $table->string('room_name');
                $table->string('building')->nullable();
                $table->unsignedSmallInteger('rows');
                $table->unsignedSmallInteger('columns');
                $table->unsignedInteger('capacity');
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['organization_id', 'is_active']);
            });
        }

        // Individual seats — auto-generated from room rows × columns
        if (!Schema::hasTable('seating_seats')) {
            Schema::create('seating_seats', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('room_id');
                $table->unsignedSmallInteger('row_no');
                $table->unsignedSmallInteger('col_no');
                $table->string('seat_number');
                $table->timestamps();

                $table->foreign('room_id')->references('id')->on('seating_rooms')->onDelete('cascade');
                $table->unique(['room_id', 'row_no', 'col_no']);
                $table->index(['room_id']);
            });
        }

        // Invigilators
        if (!Schema::hasTable('seating_invigilators')) {
            Schema::create('seating_invigilators', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id');
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->json('available_dates')->nullable(); // ["2026-06-01","2026-06-02",...]
                $table->unsignedSmallInteger('max_rooms')->default(3);
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['organization_id', 'is_active']);
            });
        }

        // Seating plan header (one per exam-date)
        if (!Schema::hasTable('seating_plans')) {
            Schema::create('seating_plans', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id');
                $table->unsignedBigInteger('exam_id');
                $table->string('name');
                $table->date('exam_date');
                $table->string('session')->nullable(); // morning / afternoon
                $table->string('status')->default('draft'); // draft / published
                $table->timestamp('generated_at')->nullable();
                $table->unsignedInteger('total_students')->default(0);
                $table->unsignedInteger('total_seats')->default(0);
                $table->unsignedInteger('conflict_count')->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['organization_id', 'exam_id', 'exam_date']);
            });
        }

        // Per-seat assignment
        if (!Schema::hasTable('seat_assignments')) {
            Schema::create('seat_assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('seating_plan_id');
                $table->unsignedBigInteger('seat_id');
                $table->unsignedBigInteger('room_id'); // denormalized for fast room-wise queries
                $table->unsignedBigInteger('student_id')->nullable();
                $table->string('class_label')->nullable(); // denormalized e.g. "10-A"
                $table->boolean('has_conflict')->default(false);
                $table->boolean('is_locked')->default(false);
                $table->timestamps();

                $table->foreign('seating_plan_id')->references('id')->on('seating_plans')->onDelete('cascade');
                $table->foreign('seat_id')->references('id')->on('seating_seats')->onDelete('cascade');
                $table->index(['seating_plan_id', 'room_id']);
                $table->unique(['seating_plan_id', 'seat_id']);
            });
        }

        // Invigilator → room assignments per plan
        if (!Schema::hasTable('invigilator_assignments')) {
            Schema::create('invigilator_assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('seating_plan_id');
                $table->unsignedBigInteger('room_id');
                $table->unsignedBigInteger('invigilator_id');
                $table->timestamps();

                $table->foreign('seating_plan_id')->references('id')->on('seating_plans')->onDelete('cascade');
                $table->foreign('invigilator_id')->references('id')->on('seating_invigilators')->onDelete('cascade');
                $table->unique(['seating_plan_id', 'room_id', 'invigilator_id'], 'inv_assign_plan_room_inv_unique');
                $table->index(['seating_plan_id', 'room_id'], 'inv_assign_plan_room_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invigilator_assignments');
        Schema::dropIfExists('seat_assignments');
        Schema::dropIfExists('seating_plans');
        Schema::dropIfExists('seating_invigilators');
        Schema::dropIfExists('seating_seats');
        Schema::dropIfExists('seating_rooms');
    }
};
