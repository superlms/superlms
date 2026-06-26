<?php

use App\Models\Admin\ExamCopy;
use App\Models\Organization;
use App\Models\Student\Subject;
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
        Schema::create('exam_subject_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ExamCopy::class)->default(0);
            $table->foreignIdFor(Organization::class)->default(0);
            $table->foreignIdFor(Subject::class)->default(0);
            $table->decimal('marks_obtained', 8, 2)->default(0);
            $table->decimal('max_marks', 8, 2)->default(0);
            $table->decimal('percentage', 5, 2)->nullable();
            $table->string('grade')->nullable();
            $table->string('evaluation_type'); // e.g., 'term1', 'term2', 'final', 'practical'
            $table->string('academic_year'); // e.g., '2023-2024'
            $table->boolean('counts_towards_yearly')->default(true);
            $table->decimal('weightage', 5, 2)->default(1.00); // For weighted average calculations
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_subject_marks');
    }
};
