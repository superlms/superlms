<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the columns needed for sub-super-admin profiles + scoped access.
     * Each column is guarded with hasColumn so this is safe to run on any
     * existing database (dob/gender may already exist via lms:migrate).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'dob')) {
                $table->date('dob')->nullable();
            }
            if (!Schema::hasColumn('users', 'gender')) {
                $table->string('gender')->nullable();
            }
            if (!Schema::hasColumn('users', 'date_of_joining')) {
                $table->date('date_of_joining')->nullable();
            }
            if (!Schema::hasColumn('users', 'alternative_mobile')) {
                $table->string('alternative_mobile')->nullable();
            }
            if (!Schema::hasColumn('users', 'permissions')) {
                // null = full access (main super-admin). Array of allowed
                // super-admin route names for sub-super-admins.
                $table->json('permissions')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['date_of_joining', 'alternative_mobile', 'permissions'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
            // dob/gender intentionally left in place — they may be owned by lms:migrate
        });
    }
};
