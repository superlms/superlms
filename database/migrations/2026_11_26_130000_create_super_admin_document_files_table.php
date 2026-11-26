<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A Super Admin document holds one file or many (user's ask, 2026-11-26):
 * the document has the title and the description, and each file in it has a
 * name of its own. The files live here; the document's own file columns keep
 * its first file, so anything that reads them still works.
 *
 * Every document so far had one file: it becomes that document's first file,
 * named as the document is.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('super_admin_document_files')) {
            Schema::create('super_admin_document_files', function (Blueprint $table) {
                $table->id();
                $table->foreignId('super_admin_document_id')
                    ->constrained('super_admin_documents')->cascadeOnDelete();
                $table->string('name');                               // the name given to this file
                $table->string('file_path');                          // S3 key
                $table->string('file_name');                          // original filename
                $table->unsignedBigInteger('file_size')->default(0);  // bytes
                $table->string('mime_type')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['super_admin_document_id', 'sort_order'], 'sadf_doc_sort');
            });
        }

        if (!Schema::hasTable('super_admin_documents')) {
            return;
        }

        $have = DB::table('super_admin_document_files')->pluck('super_admin_document_id')->flip();
        DB::table('super_admin_documents')->orderBy('id')->get()->each(function ($doc) use ($have) {
            if (isset($have[$doc->id]) || !$doc->file_path) {
                return;
            }
            DB::table('super_admin_document_files')->insert([
                'super_admin_document_id' => $doc->id,
                'name'                    => $doc->title ?: $doc->file_name,
                'file_path'               => $doc->file_path,
                'file_name'               => $doc->file_name ?: basename($doc->file_path),
                'file_size'               => (int) $doc->file_size,
                'mime_type'               => $doc->mime_type,
                'sort_order'              => 0,
                'created_at'              => $doc->created_at ?? now(),
                'updated_at'              => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('super_admin_document_files');
    }
};
