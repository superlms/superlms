<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_papers', function (Blueprint $table) {
            // Nullable: a NULL subject means the paper was filed under "Other",
            // i.e. it is deliberately not tied to one of the class's subjects.
            if (!Schema::hasColumn('exam_papers', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable()->after('section_id');
                $table->index(['organization_id', 'subject_id'], 'exam_papers_subject_idx');
            }

            if (!Schema::hasColumn('exam_papers', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exam_papers', function (Blueprint $table) {
            if (Schema::hasColumn('exam_papers', 'subject_id')) {
                $table->dropIndex('exam_papers_subject_idx');
                $table->dropColumn('subject_id');
            }

            if (Schema::hasColumn('exam_papers', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
