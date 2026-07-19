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
        Schema::table('contact_super_admins', function (Blueprint $table) {
            if (!Schema::hasColumn('contact_super_admins', 'super_admin_attachment')) {
                $table->string('super_admin_attachment')->nullable()->after('super_admin_text');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_super_admins', function (Blueprint $table) {
            if (Schema::hasColumn('contact_super_admins', 'super_admin_attachment')) {
                $table->dropColumn('super_admin_attachment');
            }
        });
    }
};
