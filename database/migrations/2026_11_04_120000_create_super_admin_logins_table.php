<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every sign-in to the Super Admin panel — who, with which email, when,
     * how, from which browser and IP (App\Support\SuperAdminLogins). Shown on
     * Profile → Login Activity for the last 15 days; kept for 90.
     */
    public function up(): void
    {
        if (Schema::hasTable('super_admin_logins')) {
            return;
        }

        Schema::create('super_admin_logins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('name', 150)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('role', 32)->nullable();
            $table->string('method', 32)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('logged_in_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('super_admin_logins');
    }
};
