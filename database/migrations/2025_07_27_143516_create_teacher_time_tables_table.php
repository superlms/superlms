<?php

use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\Subject;
use App\Models\Teacher\TeacherDetail;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('teacher_time_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TeacherDetail::class)->default(0);
            $table->unsignedTinyInteger('assigned_by')->default(0);
            $table->foreignIdFor(Organization::class)->default(0);
            $table->foreignIdFor(Standard::class)->default(0);
            $table->foreignIdFor(Section::class)->default(0);
            $table->foreignIdFor(Subject::class)->default(0);
            $table->integer('day_of_week')->default(0); // 1-7 (Monday-Sunday)
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->dateTime('effective_from')->nullable();
            $table->dateTime('effective_to')->nullable();
            // $table->unique(['teacher_detail_id', 'day_of_week', 'start_time']);
            // $table->unique(['standard_id', 'section_id', 'day_of_week', 'start_time']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_time_tables');
    }
};
