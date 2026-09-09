<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Assignments — the module that replaced the old chapter/topic "Quiz" screen.
 *
 * An assignment is handed to one class + section + subject and lives inside a
 * start → end window. It comes in two flavours:
 *
 *   type = 'written'  the teacher writes instructions (and may attach a file);
 *                     the student answers with text, a file, or both — which
 *                     one is required is `submission_mode`.
 *   type = 'mcq'      the teacher adds questions with four options each; the
 *                     student picks answers and the score is worked out here.
 *
 * Every attempt lands in assignment_submissions (one row per student per
 * assignment), which is what the admin "Responses" tab reads, marks and moves
 * through its status.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('assignments')) {
            Schema::create('assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();   // creator (admin / teacher)
                $table->unsignedBigInteger('standard_id')->nullable()->index();
                $table->unsignedBigInteger('section_id')->nullable()->index();
                $table->unsignedBigInteger('subject_id')->nullable()->index();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('type', 20)->default('written');               // written | mcq
                $table->string('submission_mode', 20)->default('both');       // text | file | both  (written only)
                $table->string('file')->nullable();                           // teacher's attachment (public S3 url)
                $table->dateTime('start_date')->nullable();
                $table->dateTime('end_date')->nullable();
                $table->unsignedInteger('total_marks')->default(0);           // 0 = derived from MCQ marks
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['organization_id', 'standard_id', 'section_id', 'subject_id'], 'assignments_class_idx');
            });
        }

        if (!Schema::hasTable('assignment_questions')) {
            Schema::create('assignment_questions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id')->index();
                $table->unsignedBigInteger('assignment_id')->index();
                $table->text('question_text');
                $table->unsignedInteger('marks')->default(1);
                $table->unsignedInteger('order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('assignment_question_options')) {
            Schema::create('assignment_question_options', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id')->index();
                $table->unsignedBigInteger('assignment_question_id')->index();
                $table->text('option_text');
                $table->boolean('is_correct')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('assignment_submissions')) {
            Schema::create('assignment_submissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id')->index();
                $table->unsignedBigInteger('assignment_id')->index();
                $table->unsignedBigInteger('user_id')->index();               // the student's user account
                $table->unsignedBigInteger('student_detail_id')->nullable()->index();
                $table->text('answer_text')->nullable();
                $table->string('file')->nullable();                           // student's uploaded file (public S3 url)
                $table->string('file_name')->nullable();
                $table->string('status', 20)->default('submitted');           // submitted | reviewed | approved | rejected
                $table->decimal('marks', 8, 2)->nullable();
                $table->text('remarks')->nullable();
                $table->unsignedInteger('mcq_score')->nullable();             // auto-graded MCQ marks
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamps();

                $table->unique(['assignment_id', 'user_id'], 'assignment_submissions_unique');
            });
        }

        if (!Schema::hasTable('assignment_submission_answers')) {
            Schema::create('assignment_submission_answers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id')->index();
                $table->unsignedBigInteger('assignment_submission_id')->index();
                $table->unsignedBigInteger('assignment_question_id')->index();
                $table->unsignedBigInteger('assignment_question_option_id')->nullable();
                $table->boolean('is_correct')->default(false);
                $table->timestamps();

                $table->unique(['assignment_submission_id', 'assignment_question_id'], 'assignment_answer_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_submission_answers');
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('assignment_question_options');
        Schema::dropIfExists('assignment_questions');
        Schema::dropIfExists('assignments');
    }
};
