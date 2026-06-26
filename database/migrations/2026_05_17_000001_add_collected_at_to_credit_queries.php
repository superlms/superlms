<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_queries', function (Blueprint $table) {
            $table->timestamp('collected_at')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('credit_queries', function (Blueprint $table) {
            $table->dropColumn('collected_at');
        });
    }
};
