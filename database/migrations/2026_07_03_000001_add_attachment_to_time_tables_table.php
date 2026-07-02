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
        if (! Schema::hasColumn('time_tables', 'attachment')) {
            Schema::table('time_tables', function (Blueprint $table) {
                // Public S3 URL of an optional image/PDF attached to the event
                // (≤ 1 MB). Nullable so existing rows and attachment-less events
                // are unaffected.
                $table->string('attachment')->nullable()->after('color');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('time_tables', 'attachment')) {
            Schema::table('time_tables', function (Blueprint $table) {
                $table->dropColumn('attachment');
            });
        }
    }
};
