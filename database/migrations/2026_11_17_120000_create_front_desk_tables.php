<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin → More's front-desk screens, each a school's own:
 *
 * - school_complaints: a complaint about a student — the class, section and
 *   student, a title, a description and a file attached if wanted.
 * - reception_visitors: who came to the school, when, to meet whom and for
 *   what, their mobile and email, and when they left.
 * - student_gate_passes: a student let out of school — why, when, who took
 *   them (name, relation, mobile) and, if they came back, when; numbered per
 *   school (GP-0001 …) and printed as a slip.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('school_complaints')) {
            Schema::create('school_complaints', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id')->index();
                $table->unsignedBigInteger('student_detail_id')->index();
                $table->unsignedBigInteger('standard_id')->nullable();
                $table->unsignedBigInteger('section_id')->nullable();
                $table->string('title', 150);
                $table->text('description');
                $table->string('file_path')->nullable();   // on S3
                $table->string('file_name')->nullable();   // as it was uploaded
                $table->unsignedBigInteger('added_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('reception_visitors')) {
            Schema::create('reception_visitors', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id')->index();
                $table->string('name', 120);
                $table->string('mobile', 20);
                $table->string('email', 150)->nullable();
                $table->string('whom_to_meet', 120)->nullable();
                $table->string('purpose', 60);
                $table->text('details')->nullable();
                $table->dateTime('in_at')->index();
                $table->dateTime('out_at')->nullable();
                $table->unsignedBigInteger('added_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('student_gate_passes')) {
            Schema::create('student_gate_passes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id')->index();
                $table->unsignedInteger('pass_number');
                $table->unsignedBigInteger('student_detail_id')->index();
                $table->unsignedBigInteger('standard_id')->nullable();
                $table->unsignedBigInteger('section_id')->nullable();
                $table->string('reason', 255);
                $table->dateTime('out_at')->index();
                $table->dateTime('returned_at')->nullable();
                $table->string('picked_by_name', 120);
                $table->string('picked_by_relation', 40)->nullable();
                $table->string('picked_by_mobile', 20)->nullable();
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('added_by')->nullable();
                $table->timestamps();
                $table->unique(['organization_id', 'pass_number']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_gate_passes');
        Schema::dropIfExists('reception_visitors');
        Schema::dropIfExists('school_complaints');
    }
};
