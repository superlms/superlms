<?php

use App\Models\Organization;
use App\Models\Student\StudentDetail;
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
        Schema::create('transfer_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(StudentDetail::class)->default(0);
            $table->foreignIdFor(Organization::class)->default(0);
            $table->string('tc_no')->nullable();
            $table->string('book_no')->nullable();
            $table->string('nationality')->default('Indian');
            $table->boolean('is_sc_st')->default(false);
            $table->string('last_class_studied')->nullable();
            $table->string('exam_last_taken')->nullable();
            $table->string('whether_failed')->default('No');            // No / Once / Twice
            $table->text('subjects_studied')->nullable();
            $table->string('qualified_for_promotion')->default('Yes');  // Yes / No
            $table->string('fees_paid_upto')->nullable();               // e.g. "March 2026"
            $table->string('fee_concession')->nullable();
            $table->unsignedSmallInteger('total_working_days')->default(0);
            $table->unsignedSmallInteger('days_present')->default(0);
            $table->string('is_ncc_scout')->default('No');              // No / NCC Cadet / Boy Scout / Girl Guide
            $table->string('extra_activities')->nullable();             // e.g. "Cricket"
            $table->string('general_conduct')->default('Good');         // Good / Excellent / Satisfactory
            $table->date('application_date');
            $table->date('issue_date');
            $table->string('reason_for_leaving')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_certificates');
    }
};
