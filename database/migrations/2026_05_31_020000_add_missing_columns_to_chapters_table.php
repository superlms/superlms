<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            if (!Schema::hasColumn('chapters', 'order')) {
                $table->unsignedInteger('order')->default(0)->after('description');
            }
            if (!Schema::hasColumn('chapters', 'image_path')) {
                $table->string('image_path')->nullable();
            }
            if (!Schema::hasColumn('chapters', 'pdf_path')) {
                $table->string('pdf_path')->nullable();
            }
            if (!Schema::hasColumn('chapters', 'content_type')) {
                $table->string('content_type', 32)->nullable();
            }
            if (!Schema::hasColumn('chapters', 'file_path')) {
                $table->string('file_path')->nullable();
            }
            if (!Schema::hasColumn('chapters', 'thumbnail')) {
                $table->string('thumbnail')->nullable();
            }
            if (!Schema::hasColumn('chapters', 'duration')) {
                $table->string('duration', 32)->nullable();
            }
            if (!Schema::hasColumn('chapters', 'is_published')) {
                $table->boolean('is_published')->default(true);
            }
            if (!Schema::hasColumn('chapters', 'is_free')) {
                $table->boolean('is_free')->default(false);
            }
            if (!Schema::hasColumn('chapters', 'metadata')) {
                $table->json('metadata')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            foreach ([
                'order', 'image_path', 'pdf_path', 'content_type', 'file_path',
                'thumbnail', 'duration', 'is_published', 'is_free', 'metadata'
            ] as $col) {
                if (Schema::hasColumn('chapters', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
