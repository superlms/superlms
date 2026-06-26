<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_fcm_tokens', function (Blueprint $table) {
            if (!Schema::hasColumn('user_fcm_tokens', 'platform')) {
                $table->string('platform', 16)->nullable()->after('token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_fcm_tokens', function (Blueprint $table) {
            if (Schema::hasColumn('user_fcm_tokens', 'platform')) {
                $table->dropColumn('platform');
            }
        });
    }
};
