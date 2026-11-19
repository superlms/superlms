<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The platform's super admin logins were all named "Super Admin", so Quick
 * Links greeted "Welcome! Super Admin" and the sidebar's card read the same.
 * The super admin is Amit Dagur (user's ask, 2026-11-19): the logins with the
 * role super-admin still on that placeholder name (or none) take his name.
 * A name set on purpose is left alone, and from now on it can be changed on
 * Super Admin → Profile (the pencil by the name).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        DB::table('users')
            ->where('role', 'super-admin')
            ->where(fn ($q) => $q->whereNull('name')->orWhere('name', '')->orWhere('name', 'Super Admin'))
            ->update(['name' => 'Amit Dagur'] + (Schema::hasColumn('users', 'updated_at') ? ['updated_at' => now()] : []));
    }

    public function down(): void
    {
        // The earlier name was only the placeholder; nothing to bring back.
    }
};
