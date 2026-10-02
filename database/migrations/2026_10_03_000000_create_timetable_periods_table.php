<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A school's day, as set on Timetable → Periods: its periods in order, each
 * with its start and end, and the lunch break. A class's timetable is then
 * filled in period by period; what it stores (teacher_time_tables) is as it was.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->index();
            $table->string('type', 10)->default('period');   // period | lunch
            $table->unsignedTinyInteger('period_no')->nullable(); // 1, 2, 3 … (null for the lunch break)
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_periods');
    }
};
