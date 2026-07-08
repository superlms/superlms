<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sub-super-admin profile address + optional single-organization scope.
     * null allowed_organization_id = access to all organizations (as before).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'address')) {
                $table->string('address', 500)->nullable();
            }
            if (!Schema::hasColumn('users', 'allowed_organization_id')) {
                $table->unsignedBigInteger('allowed_organization_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['allowed_organization_id'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
            // address intentionally left in place — it may be owned by lms:migrate
        });
    }
};
