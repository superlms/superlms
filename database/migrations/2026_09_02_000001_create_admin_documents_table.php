<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * School-admin owned documents. Unlike super_admin_documents (which the
 * super-admin pushes down to schools), these are files an admin uploads and
 * manages for their own organization — add / edit / download / delete.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admin_documents')) {
            return;
        }

        Schema::create('admin_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');                         // S3 key
            $table->string('file_name');                         // original filename
            $table->unsignedBigInteger('file_size')->default(0); // bytes
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_documents');
    }
};
