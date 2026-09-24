<?php

namespace App\Support;

use App\Models\Student\StudentDetail;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

/**
 * Who is signing in, from the one thing they typed.
 *
 * Since an email can belong to several people — a family, a department — it is
 * no longer what identifies anyone but the school's own staff. What does:
 *
 *   a student  their admission number
 *   a teacher  their username
 *   an admin, sub-admin or accounts user   their email, theirs alone
 *
 * A teacher signs in to the app with their username alone: login and adding an
 * account to the switcher refuse a teacher's email. A forgotten password still
 * finds a teacher by their email while it points at exactly one of them.
 *
 * Login, adding an account to the switcher and forgetting a password all ask
 * here, so all three take the same thing.
 */
class LoginIdentifier
{
    public const STAFF_ROLES = ['admin', 'sub-admin', 'accounts'];

    /** The only accounts that may share an address with one another. */
    public const SHARED_ROLES = ['user', 'teacher'];

    /**
     * Whether this address already belongs to an account that keeps it to
     * itself — an admin, sub-admin, accounts user, super-admin or driver, who
     * all still sign in by email — so no student or teacher may take it.
     */
    public static function emailReserved(?string $email, ?int $ignoreUserId = null): bool
    {
        if (!$email) {
            return false;
        }

        return User::where('email', $email)
            ->where(fn ($q) => $q->whereNotIn('role', self::SHARED_ROLES)->orWhereNull('role'))
            ->when($ignoreUserId, fn ($q) => $q->where('id', '!=', $ignoreUserId))
            ->exists();
    }

    /**
     * @param  bool  $teacherEmail  whether a teacher's email may find them — false
     *                              when signing in, where a teacher gives their username
     * @return array{0: ?User, 1: ?string}  the account, or why there is none
     */
    public static function resolve(string $identifier, bool $teacherEmail = true): array
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return [null, 'Enter your admission number, username or email.'];
        }

        // A username can carry an @ (meera@tds), so a teacher's username is
        // looked for before the text is taken for an email.
        if (str_contains($identifier, '@')) {
            $teacher = User::where('username', Usernames::normalize($identifier))
                ->where('role', 'teacher')
                ->first();
            if ($teacher) {
                return [$teacher, null];
            }
        }

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return self::byEmail($identifier, $teacherEmail);
        }

        // An admission number first — students are the many.
        $student = StudentDetail::where('admission_no', $identifier)->first();
        $user    = $student?->user()->where('role', 'user')->first();
        if ($user) {
            return [$user, null];
        }

        $teacher = User::where('username', Usernames::normalize($identifier))
            ->where('role', 'teacher')
            ->first();
        if ($teacher) {
            return [$teacher, null];
        }

        // The username a teacher had before it took the school code
        // (meera.sharma1, now meera@tds) still signs them in.
        if (Schema::hasColumn('users', 'previous_username')) {
            $teacher = User::where('previous_username', Usernames::normalize($identifier))
                ->where('role', 'teacher')
                ->first();
            if ($teacher) {
                return [$teacher, null];
            }
        }

        return [null, 'No account found with this admission number or username.'];
    }

    /**
     * An email belongs to the school's staff. It also still finds a teacher
     * (unless $teacherEmail is false), or a student, while it points at one of
     * them alone.
     */
    private static function byEmail(string $email, bool $teacherEmail = true): array
    {
        $staff = User::where('email', $email)->whereIn('role', self::STAFF_ROLES)->first();
        if ($staff) {
            return [$staff, null];
        }

        $teachers = User::where('email', $email)->where('role', 'teacher')->limit(2)->get();
        if (!$teacherEmail && $teachers->isNotEmpty()) {
            return [null, 'Teachers sign in with their username. Please use your username instead of your email.'];
        }
        if ($teachers->count() === 1) {
            return [$teachers->first(), null];
        }
        if ($teachers->count() > 1) {
            return [null, 'More than one teacher uses this email. Please sign in with your username.'];
        }

        $students = User::where('email', $email)->where('role', 'user')->limit(2)->get();
        if ($students->count() === 1) {
            return [$students->first(), null];
        }
        if ($students->count() > 1) {
            return [null, 'More than one student uses this email. Please sign in with the admission number.'];
        }

        return [null, 'No account found with this email address.'];
    }

    /** What the app should call the box: the same words everywhere. */
    public const LABEL = 'Admission number, username or email';

    /**
     * An address said without giving it away: ra****ta@gmail.com. Enough for
     * whoever forgot their password to know which inbox to open, and not
     * enough for anyone else.
     */
    public static function maskEmail(?string $email): ?string
    {
        if (!$email || !str_contains($email, '@')) {
            return $email;
        }

        [$name, $domain] = explode('@', $email, 2);
        $len = mb_strlen($name);

        // Always the same run of stars, so the length is not given away either.
        if ($len <= 2) {
            return mb_substr($name, 0, 1) . '**@' . $domain;
        }

        $keep = $len <= 4 ? 1 : 2;

        return mb_substr($name, 0, $keep) . '****' . mb_substr($name, -$keep) . '@' . $domain;
    }
}
