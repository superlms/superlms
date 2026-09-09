<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Login credentials that can actually be sent to someone.
 *
 * A password is stored hashed, plus a Crypt-encrypted copy in
 * users.password_plain so a later credential email can carry the SAME password
 * the person already knows. Accounts created before that column existed have no
 * copy — for them the only way to send a usable password is to set a new one.
 */
class Credentials
{
    /** A fresh, readable-but-random password. */
    public static function generate(): string
    {
        return substr(
            str_shuffle('abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789@#$!'),
            0,
            10,
        );
    }

    /**
     * The password to put in a credential email for this user.
     *
     * Returns the one they already have when it can be recovered. When it
     * can't, sets a new password on the account and returns that — an email
     * saying "use your existing password" is no use to someone who is being
     * told their login has moved to a new address.
     *
     * Saves the user when it resets, so call this after the row is committed.
     */
    public static function sendablePassword(User $user): string
    {
        $known = $user->plainPassword();
        if ($known !== null && $known !== '') {
            return $known;
        }

        $fresh = self::generate();

        $user->password = Hash::make($fresh);
        $user->rememberPlainPassword($fresh);
        $user->save();

        logger()->info('Credentials: no recoverable password, issued a new one', [
            'user_id' => $user->id,
        ]);

        return $fresh;
    }
}
