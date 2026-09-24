<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * A teacher's username — the name they sign in with, and the one thing about
 * them that is theirs alone. Emails are shared now (a family, a department,
 * one school address for everyone), so the username is what tells two accounts
 * apart at the login screen and when a password is forgotten.
 *
 * Shape: 4 to 50 characters, a letter first, then lowercase letters, numbers,
 * dots and underscores, and at most one @ — the school's own form is the
 * teacher's first name at the school code, meera@tds, and a second Meera in
 * that school is meera2@tds. Unique across every school.
 */
class Usernames
{
    public const MIN = 4;
    public const MAX = 50;

    /** The rule every username follows, for validation. */
    public const REGEX = '/^(?=.{4,50}$)[a-z][a-z0-9._]*(@[a-z0-9._]+)?$/';

    /** Said in words, for when one is taken or malformed. */
    public const RULES = [
        '4 to 50 characters long',
        'starts with a letter',
        'only lowercase letters, numbers, dots, underscores and one @ (like meera@tds)',
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
        if (preg_match('/[^a-z0-9._@]/', $username)) {
            $problems[] = 'It can only hold lowercase letters, numbers, dots, underscores and one @.';
        }
        if (substr_count($username, '@') > 1 || str_ends_with($username, '@')) {
            $problems[] = 'It can hold one @, with the school code after it (like meera@tds).';
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
    public static function suggest(?string $name, ?string $email = null, ?int $organizationId = null): string
    {
        // Within a school, the school's own form: first name at the school code.
        if ($organizationId) {
            return self::forSchool($name, $organizationId);
        }

        $base = self::base((string) $name);

        if (mb_strlen($base) < self::MIN && $email) {
            $base = self::base((string) Str::before($email, '@'));
        }
        if (mb_strlen($base) < self::MIN) {
            $base = 'teacher';
        }

        return self::makeUnique($base);
    }

    /**
     * The school's form of a username: the teacher's first name at the school
     * code, meera@tds. The next Meera there is meera2@tds, then meera3@tds.
     */
    public static function forSchool(?string $name, int $organizationId, ?int $ignoreUserId = null): string
    {
        $first = preg_replace('/[^a-z]/', '', strtolower((string) Str::before(trim((string) $name) . ' ', ' ')));
        $first = mb_substr($first ?: 'teacher', 0, 30);

        $code = self::schoolCode($organizationId);

        $candidate = $first . '@' . $code;
        for ($n = 2; self::taken($candidate, $ignoreUserId); $n++) {
            $candidate = $first . $n . '@' . $code;
        }

        return $candidate;
    }

    /**
     * A school's teachers whose username ends in a code that is not the
     * school's — the one it had before, meera@006 — take the code it has now:
     * meera@asic. The part before the @ stays (meera2@006 → meera2@asic) unless
     * that is taken, when the next free number is used. The old username is
     * kept in previous_username, where it still signs them in.
     *
     * @return int how many were renamed
     */
    public static function followSchoolCode(int $organizationId): int
    {
        $code    = self::schoolCode($organizationId);
        $keepOld = Schema::hasColumn('users', 'previous_username');

        $teachers = DB::table('users')
            ->where('role', 'teacher')
            ->where('organization_id', $organizationId)
            ->where('username', 'like', '%@%')
            ->orderBy('id')
            ->get(['id', 'username'])
            ->filter(fn ($t) => Str::after((string) $t->username, '@') !== $code);

        foreach ($teachers as $t) {
            $local     = Str::before((string) $t->username, '@');
            $base      = rtrim($local, '0123456789') ?: 'teacher';
            $candidate = $local . '@' . $code;
            for ($n = 2; self::taken($candidate, $t->id); $n++) {
                $candidate = $base . $n . '@' . $code;
            }

            DB::table('users')->where('id', $t->id)->update(
                ['username' => $candidate] + ($keepOld ? ['previous_username' => $t->username] : [])
            );
        }

        return $teachers->count();
    }

    /** The school code as it goes after the @ — "TDS" is tds. */
    public static function schoolCode(int $organizationId): string
    {
        $code = (string) \App\Models\Organization::whereKey($organizationId)->value('school_code');
        $code = preg_replace('/[^a-z0-9]/', '', strtolower($code));

        return mb_substr($code ?: 'school' . $organizationId, 0, 18);
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
        // meera@tds taken → meera2@tds, meera3@tds: the number goes before the @.
        if (str_contains($username, '@')) {
            [$local, $code] = explode('@', self::normalize($username), 2);
            $local = rtrim($local, '0123456789') ?: 'teacher';
            $found = [];
            for ($n = 2; count($found) < $count && $n < 200; $n++) {
                if (!self::taken($local . $n . '@' . $code)) {
                    $found[] = $local . $n . '@' . $code;
                }
            }

            return $found ?: [$local . '2@' . $code];
        }

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
