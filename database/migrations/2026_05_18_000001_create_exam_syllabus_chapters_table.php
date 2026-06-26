<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_syllabus_chapters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('exam_id');
            $table->unsignedBigInteger('standard_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('section_id')->nullable();
            $table->unsignedBigInteger('chapter_id');
            $table->timestamps();

            $table->unique(
                ['exam_id', 'standard_id', 'subject_id', 'chapter_id'],
                'exam_syllabus_unique'
            );

            $table->index(
                ['organization_id', 'exam_id', 'standard_id', 'subject_id'],
                'exam_syllabus_lookup_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_syllabus_chapters');
    }
};
