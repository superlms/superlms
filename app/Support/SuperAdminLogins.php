<?php

namespace App\Support;

use App\Models\User;
use App\Services\OtpMailService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Who signed in to the Super Admin panel, when, with which email and how
 * (table super_admin_logins). Written on every Login event of the
 * superadmin guard (AppServiceProvider); shown on Profile → Login Activity:
 * the main super admin sees every account, a sub super admin their own.
 */
class SuperAdminLogins
{
    /** How far back Profile shows. */
    public const DAYS = 15;

    /** How long a sign-in is kept. */
    private const KEEP_DAYS = 90;

    private const TABLE = 'super_admin_logins';

    public const METHODS = [
        'otp'        => 'Password + OTP',
        'password'   => 'Password',
        'remembered' => 'Remembered',
    ];

    public static function record(User $user, bool $remembered = false): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        $request = request();

        DB::table(self::TABLE)->insert([
            'user_id'      => $user->getKey(),
            'name'         => mb_substr((string) $user->name, 0, 150) ?: null,
            'email'        => mb_substr(User::cleanEmail((string) $user->email), 0, 191) ?: null,
            'role'         => $user->role,
            'method'       => $remembered ? 'remembered' : (OtpMailService::loginOtpEnabled() ? 'otp' : 'password'),
            'ip'           => $request?->ip(),
            'user_agent'   => mb_substr((string) $request?->userAgent(), 0, 500) ?: null,
            'logged_in_at' => now(),
        ]);

        DB::table(self::TABLE)->where('logged_in_at', '<', now()->subDays(self::KEEP_DAYS))->delete();
    }

    /**
     * The last $days days of sign-ins the viewer may see: the rows (newest
     * first), a count per day, per account, and in all.
     */
    public static function recent(User $viewer, int $days = self::DAYS): array
    {
        $since = now()->startOfDay()->subDays($days - 1);
        $all   = $viewer->role === 'super-admin';

        $rows = collect();
        if (Schema::hasTable(self::TABLE)) {
            $rows = DB::table(self::TABLE)
                ->where('logged_in_at', '>=', $since)
                ->when(!$all, fn ($q) => $q->where('user_id', $viewer->getKey()))
                ->orderByDesc('logged_in_at')->orderByDesc('id')
                ->limit(500)
                ->get();
        }

        $list = $rows->map(function ($r) {
            $at = Carbon::parse($r->logged_in_at);
            [$browser, $os, $mobile] = self::device($r->user_agent);

            return [
                'id'      => $r->id,
                'name'    => $r->name ?: '—',
                'email'   => $r->email ?: '—',
                'role'    => $r->role,
                'method'  => $r->method,
                'ip'      => $r->ip ?: '—',
                'browser' => $browser,
                'os'      => $os,
                'mobile'  => $mobile,
                'day'     => $at->toDateString(),
                'date'    => $at->format('d M Y'),
                'time'    => $at->format('h:i A'),
                'ago'     => $at->diffForHumans(),
            ];
        })->all();

        $perDay = collect($list)->countBy('day');
        $daysOut = [];
        for ($i = 0; $i < $days; $i++) {
            $d = $since->copy()->addDays($i);
            $daysOut[] = [
                'label' => $d->format('d M'),
                'short' => $d->format('j'),
                'count' => (int) ($perDay[$d->toDateString()] ?? 0),
                'today' => $d->isToday(),
            ];
        }

        $accounts = collect($list)->groupBy('email')->map(fn ($g) => [
            'name'  => $g->first()['name'],
            'email' => $g->first()['email'],
            'role'  => $g->first()['role'],
            'count' => $g->count(),
            'last'  => $g->first()['ago'],
        ])->sortByDesc('count')->values()->all();

        return [
            'rows'     => $list,
            'days'     => $daysOut,
            'accounts' => $accounts,
            'total'    => count($list),
            'today'    => (int) ($perDay[now()->toDateString()] ?? 0),
            'since'    => $since->format('d M Y'),
            'all'      => $all,
        ];
    }

    /** [browser, system, on a phone?] from a user agent — enough to tell devices apart. */
    public static function device(?string $ua): array
    {
        $ua = (string) $ua;
        if ($ua === '') {
            return ['Unknown', '', false];
        }

        $browser = match (true) {
            str_contains($ua, 'Edg/') || str_contains($ua, 'EdgA/') || str_contains($ua, 'EdgiOS/') => 'Edge',
            str_contains($ua, 'OPR/') || str_contains($ua, 'Opera')                                  => 'Opera',
            str_contains($ua, 'SamsungBrowser/')                                                     => 'Samsung Internet',
            str_contains($ua, 'Firefox/') || str_contains($ua, 'FxiOS/')                             => 'Firefox',
            str_contains($ua, '; wv)')                                                               => 'Android app',
            str_contains($ua, 'CriOS/') || str_contains($ua, 'Chrome/')                              => 'Chrome',
            str_contains($ua, 'Safari/')                                                             => 'Safari',
            default                                                                                  => 'Other',
        };

        $os = match (true) {
            str_contains($ua, 'Windows')                                     => 'Windows',
            str_contains($ua, 'Android')                                     => 'Android',
            str_contains($ua, 'iPhone')                                      => 'iPhone',
            str_contains($ua, 'iPad')                                        => 'iPad',
            str_contains($ua, 'CrOS')                                        => 'ChromeOS',
            str_contains($ua, 'Macintosh') || str_contains($ua, 'Mac OS X') => 'macOS',
            str_contains($ua, 'Linux')                                       => 'Linux',
            default                                                          => '',
        };

        $mobile = !str_contains($ua, 'iPad') && (bool) preg_match('/Mobile|iPhone|Android/', $ua);

        return [$browser, $os, $mobile];
    }
}
