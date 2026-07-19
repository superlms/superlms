<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Broadens super-admin push campaigns:
 *   - audience_roles : the multi-select of who it went to (students/teachers/admins)
 *   - web_count      : how many recipients also got the in-app (web) notification
 *   - device_breakdown : per-platform device reach captured at send time
 *
 * The legacy `audience_role` string stays for backward compatibility (older rows).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('push_notification_campaigns', function (Blueprint $table) {
            if (!Schema::hasColumn('push_notification_campaigns', 'audience_roles')) {
                $table->json('audience_roles')->nullable()->after('audience_role');
            }
            if (!Schema::hasColumn('push_notification_campaigns', 'web_count')) {
                $table->unsignedInteger('web_count')->default(0)->after('device_count');
            }
            if (!Schema::hasColumn('push_notification_campaigns', 'device_breakdown')) {
                $table->json('device_breakdown')->nullable()->after('web_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('push_notification_campaigns', function (Blueprint $table) {
            foreach (['audience_roles', 'web_count', 'device_breakdown'] as $col) {
                if (Schema::hasColumn('push_notification_campaigns', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
