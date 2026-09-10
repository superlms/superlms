<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Richer chat: delivery receipts, pinned messages, per-user (one-sided)
 * deletes, forwarding, and attachments served through the app instead of a
 * public S3 URL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chat_messages')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                if (!Schema::hasColumn('chat_messages', 'delivered_at')) {
                    $table->timestamp('delivered_at')->nullable()->after('attachment_type');
                }
                if (!Schema::hasColumn('chat_messages', 'pinned_at')) {
                    $table->timestamp('pinned_at')->nullable()->after('read_at');
                }
                if (!Schema::hasColumn('chat_messages', 'attachment_path')) {
                    // S3 object key — lets us hand out short-lived signed URLs
                    // instead of relying on the object being publicly readable.
                    $table->string('attachment_path')->nullable()->after('attachment_url');
                }
                if (!Schema::hasColumn('chat_messages', 'attachment_size')) {
                    $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_type');
                }
                if (!Schema::hasColumn('chat_messages', 'forwarded_from_id')) {
                    $table->unsignedBigInteger('forwarded_from_id')->nullable()->after('sender_id');
                }
            });
        }

        if (Schema::hasTable('chat_conversation_user')) {
            Schema::table('chat_conversation_user', function (Blueprint $table) {
                if (!Schema::hasColumn('chat_conversation_user', 'pinned_at')) {
                    $table->timestamp('pinned_at')->nullable()->after('last_read_at');
                }
                if (!Schema::hasColumn('chat_conversation_user', 'cleared_at')) {
                    // "Delete chat" only hides it for the person who tapped it.
                    $table->timestamp('cleared_at')->nullable()->after('pinned_at');
                }
            });
        }

        // One row per (message, user) that user deleted the message for themselves.
        if (!Schema::hasTable('chat_message_deletes')) {
            Schema::create('chat_message_deletes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('message_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->timestamps();
                $table->unique(['message_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_message_deletes');

        if (Schema::hasTable('chat_conversation_user')) {
            Schema::table('chat_conversation_user', function (Blueprint $table) {
                foreach (['pinned_at', 'cleared_at'] as $col) {
                    if (Schema::hasColumn('chat_conversation_user', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('chat_messages')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                foreach (['delivered_at', 'pinned_at', 'attachment_path', 'attachment_size', 'forwarded_from_id'] as $col) {
                    if (Schema::hasColumn('chat_messages', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
