<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Real-time (polling) messaging between the School (admin/sub-admin) panel
     * and the Accounts panel. Conversations are scoped to an organization and,
     * for now, are one-to-one (two participants). The schema also supports
     * group conversations (>2 participants) without changes.
     *
     * Guarded with hasTable so it is safe to run on any existing database.
     */
    public function up(): void
    {
        if (!Schema::hasTable('chat_conversations')) {
            Schema::create('chat_conversations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id')->index();
                $table->timestamp('last_message_at')->nullable()->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('chat_conversation_user')) {
            Schema::create('chat_conversation_user', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('conversation_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->timestamp('last_read_at')->nullable();
                $table->timestamps();
                $table->unique(['conversation_id', 'user_id']);
            });
        }

        if (!Schema::hasTable('chat_messages')) {
            Schema::create('chat_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('conversation_id');
                $table->unsignedBigInteger('sender_id')->index();
                $table->text('body')->nullable();
                $table->string('attachment_url')->nullable();
                $table->string('attachment_name')->nullable();
                $table->string('attachment_type', 20)->nullable(); // 'image' | 'file'
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->index(['conversation_id', 'id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversation_user');
        Schema::dropIfExists('chat_conversations');
    }
};
