<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_enquiries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->string('student_name');
            $table->string('email')->nullable();
            $table->string('mobile');
            $table->string('guardian_name');
            $table->text('address')->nullable();
            $table->unsignedBigInteger('standard_id')->nullable();
            $table->string('stream')->nullable();
            $table->decimal('admission_fee', 12, 2)->default(0);
            $table->decimal('total_marks', 8, 2)->nullable();
            $table->decimal('obtained_marks', 8, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->string('result_pdf')->nullable();
            $table->enum('status', ['pending', 'updated', 'admitted'])->default('pending');
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('standard_id')->references('id')->on('standards')->nullOnDelete();
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'standard_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_enquiries');
    }
};
