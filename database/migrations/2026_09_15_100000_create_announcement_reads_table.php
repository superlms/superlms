<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who has opened which announcement. The app kept this only on the phone, so
 * reinstalling it (or signing in on another phone) showed every announcement
 * as unread again.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('announcement_reads')) {
            return;
        }

        Schema::create('announcement_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('announcement_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->timestamps();
            $table->unique(['announcement_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_reads');
    }
};
