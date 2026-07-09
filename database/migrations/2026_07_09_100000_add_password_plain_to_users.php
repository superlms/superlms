<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Encrypted copy of the user's current password (Crypt::encryptString).
     * Needed so credential emails re-sent after an email change can carry the
     * SAME password instead of resetting it. null = unknown (legacy account).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'password_plain')) {
                $table->text('password_plain')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'password_plain')) {
                $table->dropColumn('password_plain');
            }
        });
    }
};
