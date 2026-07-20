<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('super_admin_documents')) {
            Schema::create('super_admin_documents', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('file_path');                       // S3 key
                $table->string('file_name');                       // original filename
                $table->unsignedBigInteger('file_size')->default(0); // bytes
                $table->string('mime_type')->nullable();
                $table->string('audience_scope')->default('all');  // all | selected
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamps();
                $table->index('created_at');
            });
        }

        if (!Schema::hasTable('super_admin_document_organization')) {
            Schema::create('super_admin_document_organization', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')
                    ->constrained('super_admin_documents')
                    ->cascadeOnDelete();
                $table->unsignedBigInteger('organization_id');
                $table->timestamps();
                $table->unique(['document_id', 'organization_id'], 'sad_doc_org_unique');
                $table->index('organization_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('super_admin_document_organization');
        Schema::dropIfExists('super_admin_documents');
    }
};
