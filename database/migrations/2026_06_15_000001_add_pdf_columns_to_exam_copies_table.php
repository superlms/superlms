<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The original create_exam_copies_table migration never created the
 * `pdf_path`, `file` and `uploaded_by` columns — yet the ExamCopy model
 * lists them as fillable and the teacher controllers write to them on every
 * marks / exam-copy upload. The result was a 500 ("Unknown column") on both
 * "Upload Marks" (writes uploaded_by) and "Upload Copy" (writes uploaded_by
 * + pdf_path), and on listing copies (whereNotNull('pdf_path')).
 *
 * This migration adds the missing columns so those flows work.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_copies', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_copies', 'pdf_path')) {
                $table->string('pdf_path')->nullable()->after('remarks');
            }
            if (!Schema::hasColumn('exam_copies', 'file')) {
                $table->string('file')->nullable()->after('pdf_path');
            }
            if (!Schema::hasColumn('exam_copies', 'uploaded_by')) {
                $table->unsignedBigInteger('uploaded_by')->nullable()->after('file');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exam_copies', function (Blueprint $table) {
            foreach (['pdf_path', 'file', 'uploaded_by'] as $col) {
                if (Schema::hasColumn('exam_copies', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
