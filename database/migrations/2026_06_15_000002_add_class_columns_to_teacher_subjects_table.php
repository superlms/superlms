<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The original create_teacher_subjects_table migration only created
 * teacher_detail_id, subject_id and organization_id — but the TeacherSubject
 * model and several controllers (ExamController, FilterController,
 * DashboardController, SubjectController) read `standard_id` and `section_id`
 * from this table. Selecting those non-existent columns threw a 500
 * ("Unknown column 'standard_id'"), which is why the teacher "Exams" screen
 * (and the teacher class/section/subject filters) errored.
 *
 * This migration adds the two missing columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('teacher_subjects', 'standard_id')) {
                $table->unsignedBigInteger('standard_id')->nullable()->after('teacher_detail_id');
            }
            if (!Schema::hasColumn('teacher_subjects', 'section_id')) {
                $table->unsignedBigInteger('section_id')->nullable()->after('subject_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('teacher_subjects', function (Blueprint $table) {
            foreach (['standard_id', 'section_id'] as $col) {
                if (Schema::hasColumn('teacher_subjects', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
