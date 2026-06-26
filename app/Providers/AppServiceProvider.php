<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // In-app activity feed: notify school admins about every meaningful
        // create/update/delete across the app — including actions taken by
        // accountants, teachers and student/user accounts — with who did what.
        \App\Support\ActivityNotifier::boot();

        // ── Mobile-app push notifications: domain event → student/teacher push ──
        // (Attendance is wired in its controller/Livewire because the bulk path
        //  uses raw inserts that don't fire model events.)
        $this->bootAppPushNotifications();

        // Record last login timestamp on every successful authentication
        Event::listen(Login::class, function (Login $event) {
            $user = $event->user;
            if ($user && \Illuminate\Support\Facades\Schema::hasColumn($user->getTable(), 'last_login_at')) {
                $user->forceFill(['last_login_at' => now()])->saveQuietly();
            }
        });

        // Per-email login throttle (6/min). Stops password brute-force
        // against a single account without locking out unrelated users
        // sharing an IP (e.g. school computer labs).
        RateLimiter::for('login', function (Request $request) {
            $email = strtolower((string) $request->input('email', ''));
            $key = $email !== '' ? 'login:' . sha1($email) : 'login:ip:' . $request->ip();

            return Limit::perMinute(6)->by($key)->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many login attempts. Please try again in a minute.',
                ], 429);
            });
        });

        // OTP / password reset throttle (4/min per email or IP fallback).
        RateLimiter::for('otp', function (Request $request) {
            $email = strtolower((string) $request->input('email', ''));
            $key = $email !== '' ? 'otp:' . sha1($email) : 'otp:ip:' . $request->ip();

            return Limit::perMinute(4)->by($key)->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many OTP requests. Please wait a minute before retrying.',
                ], 429);
            });
        });

        // Online fee payment throttle (5/min per authenticated user, IP fallback).
        // Prevents accidental double-taps and abusive order creation.
        RateLimiter::for('payments', function (Request $request) {
            $key = $request->user()?->id
                ? 'pay:user:' . $request->user()->id
                : 'pay:ip:' . $request->ip();

            return Limit::perMinute(5)->by($key)->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many payment attempts. Please wait a minute and try again.',
                ], 429);
            });
        });
    }

    /**
     * Wire model events to mobile-app push notifications. Centralised here so the
     * triggers stay in one place as the rule list grows.
     */
    private function bootAppPushNotifications(): void
    {
        $push = fn () => app(\App\Services\AppPushNotifier::class);

        // New announcement → its audience (all / students / teachers) of the org.
        \App\Models\Admin\Announcement::created(function ($announcement) use ($push) {
            $push()->announcement($announcement);
        });

        // New homework → students of that class & section. Both the teacher API
        // and the admin panel create via HomeWork::create(), so this covers both.
        \App\Models\Admin\HomeWork::created(function ($homework) use ($push) {
            $push()->homeworkAssigned($homework);
        });

        // Info / policy / legal pages changed → students + teachers, deep-linked
        // to the matching "More" screen. `org` = per-school vs app-wide.
        $settingPages = [
            \App\Models\AboutApp::class                  => ['AboutAppMore',          'About App',          false],
            \App\Models\PrivacyPolicy::class             => ['PrivacyPolicyMore',      'Privacy Policy',     false],
            \App\Models\TermOfUse::class                 => ['TermsOfUseMore',         'Terms of Use',       false],
            \App\Models\Admin\TermAndCondition::class    => ['TermsConditionsMore',    'Terms & Conditions', false],
            \App\Models\Admin\RulesAndRegulation::class  => ['RulesRegulationsMore',   'Rules & Regulations', true],
            \App\Models\Admin\SchoolInfo::class          => ['SchoolInfoMore',         'School Info',        true],
        ];

        foreach ($settingPages as $model => [$screen, $label, $orgScoped]) {
            $model::saved(function ($row) use ($push, $screen, $label, $orgScoped) {
                $push()->settingUpdated(
                    $screen,
                    $label,
                    $orgScoped ? ($row->organization_id ?? null) : null
                );
            });
        }
    }
}
