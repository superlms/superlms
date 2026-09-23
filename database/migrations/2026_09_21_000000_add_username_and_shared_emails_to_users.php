<?php

use App\Support\Usernames;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One email, many people — and a username for every teacher.
 *
 * A family shares an address, a school gives one address to a whole
 * department, so an email can no longer be the thing that tells two accounts
 * apart: the unique index comes off (a plain index takes its place, since
 * every login still looks accounts up by it). What tells them apart instead is
 * the admission number for a student and the username for a teacher, which is
 * added here and filled in for every teacher already on the rolls so none of
 * them is locked out.
 *
 * Admins, sub-admins and accounts users still sign in with their email; that
 * one address stays theirs, enforced in the code that creates them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username', 30)->nullable()->unique()->after('email');
            }
        });

        // The unique index on email, dropped by whichever name this database
        // knows it by. MySQL names it users_email_unique by default.
        foreach (['users_email_unique', 'email'] as $index) {
            try {
                DB::statement("ALTER TABLE `users` DROP INDEX `{$index}`");
                break;
            } catch (\Throwable $e) {
                // Not there under that name — try the next.
            }
        }

        // Logins still find accounts by email, so it stays indexed.
        try {
            DB::statement('CREATE INDEX `users_email_index` ON `users` (`email`)');
        } catch (\Throwable $e) {
            // Already indexed.
        }

        $this->fillTeacherUsernames();
    }

    /**
     * Every teacher without one gets a username from their name (else their
     * email), numbered when two land on the same. They keep signing in.
     */
    private function fillTeacherUsernames(): void
    {
        DB::table('users')
            ->where('role', 'teacher')
            ->where(fn ($q) => $q->whereNull('username')->orWhere('username', ''))
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('users')
                        ->where('id', $row->id)
                        ->update(['username' => Usernames::suggest($row->name, $row->email)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'username')) {
                $table->dropUnique(['username']);
                $table->dropColumn('username');
            }
        });

        try {
            DB::statement('DROP INDEX `users_email_index` ON `users`');
        } catch (\Throwable $e) {
            // Never created.
        }

        // Only restorable while no two accounts share an address.
        try {
            DB::statement('ALTER TABLE `users` ADD UNIQUE `users_email_unique` (`email`)');
        } catch (\Throwable $e) {
            // Duplicates exist — left as it is.
        }
    }
};
