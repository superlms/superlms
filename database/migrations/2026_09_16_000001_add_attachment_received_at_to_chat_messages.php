<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chat files for the mobile app, the WhatsApp way: the server only carries a
 * file across to the other phone. Once that phone has saved it,
 * attachment_received_at is set; when every message holding the same file
 * (forwarded copies share it) has reached its phone, the file is deleted from S3.
 *
 * Guarded with hasColumn so it is safe to run on any existing database.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chat_messages') && !Schema::hasColumn('chat_messages', 'attachment_received_at')) {
            $after = Schema::hasColumn('chat_messages', 'attachment_size') ? 'attachment_size' : 'attachment_type';

            Schema::table('chat_messages', function (Blueprint $table) use ($after) {
                $table->timestamp('attachment_received_at')->nullable()->after($after);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('chat_messages') && Schema::hasColumn('chat_messages', 'attachment_received_at')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                $table->dropColumn('attachment_received_at');
            });
        }
    }
};
