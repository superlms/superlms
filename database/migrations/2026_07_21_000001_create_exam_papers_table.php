<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_papers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('exam_id');
            $table->unsignedBigInteger('standard_id');
            $table->unsignedBigInteger('section_id')->nullable();
            $table->string('title');
            $table->string('file_path');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('exam_id')->references('id')->on('exams')->cascadeOnDelete();
            $table->foreign('standard_id')->references('id')->on('standards')->cascadeOnDelete();
            $table->index(['organization_id', 'exam_id', 'standard_id', 'section_id'], 'exam_papers_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_papers');
    }
};
