<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-student fee concession (discount). A concession reduces what a student
 * owes — either a flat amount or a percentage of their class fee structure —
 * and is surfaced on the Fee Submission screen so the net payable reflects it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fee_concessions')) {
            return;
        }

        Schema::create('fee_concessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->default(0)->index();
            $table->unsignedBigInteger('student_detail_id')->index();
            $table->unsignedBigInteger('standard_id')->nullable();
            $table->unsignedBigInteger('section_id')->nullable();
            $table->enum('concession_type', ['amount', 'percent'])->default('amount');
            $table->decimal('value', 10, 2)->default(0);
            $table->enum('fee_type', ['academic', 'transport', 'all'])->default('all');
            $table->string('reason')->nullable();
            $table->string('academic_year')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'student_detail_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_concessions');
    }
};
