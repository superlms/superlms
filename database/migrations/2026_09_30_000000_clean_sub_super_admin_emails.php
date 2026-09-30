<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Sub super-admins saved with a space or an invisible character in their email
 * (a pasted non-breaking space passes the email rule) could not be found at the
 * Super Admin login ("Email does not exist.") and got no mail. Their addresses
 * lose those characters here, as the Users screen now does on every save — so
 * they can sign in and use Forgot password. An address that would then match
 * another account is left as it is.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'sub-super-admin')
            ->orderBy('id')
            ->get(['id', 'email'])
            ->each(function ($row) {
                $clean = (string) preg_replace('/[\s\p{Z}\p{Cf}]+/u', '', (string) $row->email);

                if ($clean === '' || $clean === $row->email) {
                    return;
                }

                $taken = DB::table('users')
                    ->where('email', $clean)
                    ->where('id', '!=', $row->id)
                    ->exists();

                if (!$taken) {
                    DB::table('users')->where('id', $row->id)->update(['email' => $clean]);
                }
            });
    }

    public function down(): void
    {
        // The characters taken out were never part of an address.
    }
};
