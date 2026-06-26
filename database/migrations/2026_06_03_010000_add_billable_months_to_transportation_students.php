<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transportation_students', function (Blueprint $table) {
            if (!Schema::hasColumn('transportation_students', 'billable_months')) {
                // JSON-encoded { "apr": true, "may": true, "jun": false, ... }
                // Null => fall back to default (June excluded, all others on).
                $table->json('billable_months')->nullable()->after('organization_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transportation_students', function (Blueprint $table) {
            if (Schema::hasColumn('transportation_students', 'billable_months')) {
                $table->dropColumn('billable_months');
            }
        });
    }
};
