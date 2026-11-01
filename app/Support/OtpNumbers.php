<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A super admin's numbers for the sign-in code (Profile → OTP Numbers).
 *
 * The primary number is the account's own mobile_number — so it is also what
 * the Users screen edits for a sub super admin — and the sign-in and
 * forgot-password codes go on WhatsApp to it alone, beside the email. The
 * other numbers wait in user_otp_numbers until one is made primary: then the
 * two swap places.
 *
 * Every change returns null when done, or a message saying what is wrong.
 */
class OtpNumbers
{
    /** Numbers an account can have, the primary included. */
    public const MAX = 5;

    /** Whether the table is there yet — asked once a request. */
    public static function tracked(): bool
    {
        $key = 'otp_numbers.tracked';
        if (!app()->bound($key)) {
            try {
                $has = Schema::hasTable('user_otp_numbers');
            } catch (\Throwable $e) {
                $has = false;
            }
            app()->instance($key, $has);
        }

        return app($key);
    }

    /**
     * The account's numbers, primary first.
     *
     * @return list<array{id: ?int, phone: string, primary: bool}>
     */
    public static function forUser(User $user): array
    {
        $list = [];

        if ($primary = self::tenDigits($user->mobile_number)) {
            $list[] = ['id' => null, 'phone' => $primary, 'primary' => true];
        }

        foreach (self::rows($user) as $row) {
            $list[] = ['id' => (int) $row->id, 'phone' => $row->phone, 'primary' => false];
        }

        return $list;
    }

    /** The number the code goes to on WhatsApp, or null. */
    public static function primary(User $user): ?string
    {
        return self::tenDigits($user->mobile_number);
    }

    public static function add(User $user, string $input): ?string
    {
        [$phone, $error] = self::check($input);
        if ($error) {
            return $error;
        }

        $current = self::forUser($user);

        if (in_array($phone, array_column($current, 'phone'), true)) {
            return self::format($phone) . ' is already added.';
        }

        if (count($current) >= self::MAX) {
            return 'Up to ' . self::MAX . ' numbers can be added. Delete one first.';
        }

        // The first number is the primary one.
        if (!self::primary($user)) {
            self::setPrimary($user, $phone);
            return null;
        }

        if (!self::tracked()) {
            return 'Numbers can\'t be saved yet — the server is still being updated. Try again in a minute.';
        }

        DB::table('user_otp_numbers')->insert([
            'user_id' => $user->id, 'phone' => $phone, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return null;
    }

    /** Change a number; $id null is the primary one. */
    public static function update(User $user, ?int $id, string $input): ?string
    {
        [$phone, $error] = self::check($input);
        if ($error) {
            return $error;
        }

        foreach (self::forUser($user) as $n) {
            if ($n['phone'] === $phone && $n['id'] !== $id) {
                return self::format($phone) . ' is already added.';
            }
        }

        if ($id === null) {
            self::setPrimary($user, $phone);
            return null;
        }

        if (!self::row($user, $id)) {
            return 'This number is no longer there. Refresh the page.';
        }

        DB::table('user_otp_numbers')->where('id', $id)->update(['phone' => $phone, 'updated_at' => now()]);

        return null;
    }

    /**
     * Delete a number; $id null is the primary one — then the oldest other
     * number becomes primary, or there is none.
     */
    public static function delete(User $user, ?int $id): ?string
    {
        if ($id !== null) {
            if (!self::row($user, $id)) {
                return 'This number is no longer there. Refresh the page.';
            }
            DB::table('user_otp_numbers')->where('id', $id)->delete();
            return null;
        }

        $next = self::rows($user)->first();
        if ($next) {
            DB::table('user_otp_numbers')->where('id', $next->id)->delete();
        }
        self::setPrimary($user, $next?->phone);

        return null;
    }

    /** Make another number the primary one; the old primary takes its place. */
    public static function makePrimary(User $user, int $id): ?string
    {
        $row = self::row($user, $id);
        if (!$row) {
            return 'This number is no longer there. Refresh the page.';
        }

        $old = self::primary($user);
        if ($old) {
            DB::table('user_otp_numbers')->where('id', $id)->update(['phone' => $old, 'updated_at' => now()]);
        } else {
            DB::table('user_otp_numbers')->where('id', $id)->delete();
        }
        self::setPrimary($user, $row->phone);

        return null;
    }

    /** "+91 88649 85914" */
    public static function format(string $phone): string
    {
        return '+91 ' . substr($phone, 0, 5) . ' ' . substr($phone, 5);
    }

    /**
     * [ten digits, null] or [null, what is wrong with it].
     *
     * @return array{0: ?string, 1: ?string}
     */
    public static function check(string $input): array
    {
        $input = trim($input);
        if ($input === '') {
            return [null, 'Enter a mobile number.'];
        }
        if (preg_match('/[^\d\s+\-()]/', $input)) {
            return [null, 'A mobile number has digits only — remove letters or other signs.'];
        }

        $digits = preg_replace('/\D+/', '', $input);
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        } elseif (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) !== 10) {
            return [null, 'Enter a 10-digit mobile number — ' . strlen($digits) . ' digits entered.'];
        }
        if (!preg_match('/^[6-9]/', $digits)) {
            return [null, 'An Indian mobile number starts with 6, 7, 8 or 9.'];
        }

        return [$digits, null];
    }

    private static function tenDigits(?string $phone): ?string
    {
        [$digits] = $phone === null ? [null] : self::check($phone);

        return $digits;
    }

    private static function rows(User $user)
    {
        if (!self::tracked() || !$user->id) {
            return collect();
        }

        return DB::table('user_otp_numbers')->where('user_id', $user->id)->orderBy('id')->get(['id', 'phone']);
    }

    private static function row(User $user, int $id): ?object
    {
        if (!self::tracked()) {
            return null;
        }

        return DB::table('user_otp_numbers')->where('user_id', $user->id)->where('id', $id)->first(['id', 'phone']);
    }

    /** The primary number lives on the account; saved without model events. */
    private static function setPrimary(User $user, ?string $phone): void
    {
        $user->forceFill(['mobile_number' => $phone])->saveQuietly();
    }
}
