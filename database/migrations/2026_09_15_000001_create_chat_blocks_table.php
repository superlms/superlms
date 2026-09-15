<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chat blocks, for the mobile app: once a user blocks someone, that person can
 * no longer message them (so no notification of theirs arrives), nor can the
 * user message the person, until they unblock.
 *
 * Guarded with hasTable so it is safe to run on any existing database.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('chat_blocks')) {
            Schema::create('chat_blocks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();         // who blocked
                $table->unsignedBigInteger('blocked_user_id')->index(); // who is blocked
                $table->timestamps();
                $table->unique(['user_id', 'blocked_user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_blocks');
    }
};
