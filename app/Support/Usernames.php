<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * A teacher's username — the name they sign in with, and the one thing about
 * them that is theirs alone. Emails are shared now (a family, a department,
 * one school address for everyone), so the username is what tells two accounts
 * apart at the login screen and when a password is forgotten.
 *
 * Shape: 4 to 30 characters, a letter first, then lowercase letters, numbers,
 * dots and underscores. Unique across every school.
 */
class Usernames
{
    public const MIN = 4;
    public const MAX = 30;

    /** The rule every username follows, for validation. */
    public const REGEX = '/^[a-z][a-z0-9._]{3,29}$/';

    /** Said in words, for when one is taken or malformed. */
    public const RULES = [
        '4 to 30 characters long',
        'starts with a letter',
        'only lowercase letters, numbers, dots and underscores',
        'not already taken by another account',
    ];

    /** One line of the rules, for a form's hint or an error. */
    public static function rulesLine(): string
    {
        return 'A username is ' . self::RULES[0] . ', ' . self::RULES[1]
            . ', and uses ' . self::RULES[2] . '.';
    }

    /** Everything wrong with this username, or an empty list when it is fine. */
    public static function problems(?string $username, ?int $ignoreUserId = null): array
    {
        $username = self::normalize($username);
        $problems = [];

        if (mb_strlen($username) < self::MIN || mb_strlen($username) > self::MAX) {
            $problems[] = 'It must be ' . self::MIN . ' to ' . self::MAX . ' characters long.';
        }
        if ($username !== '' && !preg_match('/^[a-z]/', $username)) {
            $problems[] = 'It must start with a letter.';
        }
        if (preg_match('/[^a-z0-9._]/', $username)) {
            $problems[] = 'It can only hold lowercase letters, numbers, dots and underscores.';
        }
        if ($username !== '' && self::taken($username, $ignoreUserId)) {
            $problems[] = 'Someone already has this username — try ' . implode(' or ', self::alternatives($username)) . '.';
        }

        return $problems;
    }

    /** Trimmed and lowercased, the way it is stored. */
    public static function normalize(?string $username): string
    {
        return strtolower(trim((string) $username));
    }

    public static function taken(string $username, ?int $ignoreUserId = null): bool
    {
        return User::where('username', self::normalize($username))
            ->when($ignoreUserId, fn ($q) => $q->where('id', '!=', $ignoreUserId))
            ->exists();
    }

    /**
     * A username from what is known about the person — the name, else the part
     * of their email before the @ — made unique with a number on the end.
     */
    public static function suggest(?string $name, ?string $email = null): string
    {
        $base = self::base((string) $name);

        if (mb_strlen($base) < self::MIN && $email) {
            $base = self::base((string) Str::before($email, '@'));
        }
        if (mb_strlen($base) < self::MIN) {
            $base = 'teacher';
        }

        return self::makeUnique($base);
    }

    /** The same name with a number on the end, until no one has it. */
    public static function makeUnique(string $base, ?int $ignoreUserId = null): string
    {
        $base = self::base($base) ?: 'teacher';
        $candidate = $base;

        for ($n = 1; self::taken($candidate, $ignoreUserId); $n++) {
            $suffix = (string) $n;
            $candidate = mb_substr($base, 0, self::MAX - mb_strlen($suffix)) . $suffix;
        }

        return $candidate;
    }

    /** Three free usernames near the one that was taken. */
    public static function alternatives(string $username, int $count = 2): array
    {
        $base  = self::base($username) ?: 'teacher';
        $found = [];

        for ($n = 1; count($found) < $count && $n < 200; $n++) {
            $suffix    = (string) $n;
            $candidate = mb_substr($base, 0, self::MAX - mb_strlen($suffix)) . $suffix;
            if (!self::taken($candidate)) {
                $found[] = $candidate;
            }
        }

        return $found ?: [$base . '1'];
    }

    /** "Meera K. Sharma" → "meera.k.sharma", cut to length. */
    private static function base(string $raw): string
    {
        $slug = strtolower(trim($raw));
        $slug = preg_replace('/[^a-z0-9]+/', '.', $slug);
        $slug = trim((string) $slug, '.');
        $slug = preg_replace('/\.{2,}/', '.', (string) $slug);
        $slug = ltrim((string) $slug, '0123456789._');

        return mb_substr((string) $slug, 0, self::MAX);
    }
}
