<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_works', function (Blueprint $table) {
            if (!Schema::hasColumn('home_works', 'file')) {
                // S3 path of an optional attachment (image / pdf / doc).
                $table->string('file')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('home_works', function (Blueprint $table) {
            if (Schema::hasColumn('home_works', 'file')) {
                $table->dropColumn('file');
            }
        });
    }
};
