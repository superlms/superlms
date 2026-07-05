<?php

use App\Http\Controllers\PhonePeController;
use Illuminate\Support\Facades\Route;

// PhonePe browser redirect target (registered early so the website's
// {organization} wildcard doesn't swallow it).
Route::get('/payment/return/{merchantOrderId}', [PhonePeController::class, 'paymentReturn'])
    ->name('phonepe.return');

// ── Installable PWA entry points ──────────────────────────────────────────
// Each installed app (admin / super-admin / accounts) opens at its own
// start_url below. Logged in → straight to that role's area; session expired
// → that role's login screen. Registered before website.php so the
// {organization} wildcard doesn't swallow them.
Route::get('/app/admin', function () {
    $u = auth('web')->user();
    return ($u && $u->organization_id)
        ? redirect()->route('admin.home', ['organization' => $u->organization_id])
        : redirect()->route('admin.login');
})->name('pwa.admin');

Route::get('/app/superadmin', function () {
    return auth('web')->check()
        ? redirect()->route('super-admin.dashboard')
        : redirect()->route('super-admin.login');
})->name('pwa.superadmin');

Route::get('/app/accounts', function () {
    return auth('web')->check()
        ? redirect()->route('accounts.dashboard')
        : redirect()->route('accounts.login');
})->name('pwa.accounts');

// Role-specific web-app manifests, served with the correct MIME type so each
// role installs as its own separate app (distinct id + start_url).
Route::get('/pwa/manifest/{role}', function (string $role) {
    $apps = [
        'admin'      => ['SuperLMS Admin',       'Admin',    route('pwa.admin', [], false)],
        'superadmin' => ['SuperLMS Super Admin', 'Super',    route('pwa.superadmin', [], false)],
        'accounts'   => ['SuperLMS Accounts',    'Accounts', route('pwa.accounts', [], false)],
        'site'       => ['SuperLMS',             'SuperLMS', '/'],
    ];
    $key = array_key_exists($role, $apps) ? $role : 'site';
    [$name, $short, $start] = $apps[$key];

    // Bump this when an install gets "stuck" (browser shows Open-in-app after the
    // icon was removed) — a new id makes the browser offer Install again.
    $idVersion = 'v2';

    $manifest = [
        'name'             => $name,
        'short_name'       => $short,
        'id'               => '/pwa/' . $key . '/' . $idVersion,
        'start_url'        => $start,
        'scope'            => '/',
        'display'          => 'standalone',
        'display_override' => ['standalone', 'minimal-ui'],
        'orientation'      => 'any',
        'background_color' => '#ffffff',
        'theme_color'      => '#4f46e5',
        'icons'            => [
            ['src' => '/website-image/Logo.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => '/website-image/Logo.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
        ],
    ];

    return response(json_encode($manifest, JSON_UNESCAPED_SLASHES))
        ->header('Content-Type', 'application/manifest+json');
})->name('pwa.manifest');

//SuperLMS Website (must be before admin — avoids {organization} wildcard swallowing /web/* routes)
require __DIR__.'/website.php';

// Super Admin
require __DIR__.'/super-admin.php';

//Admin School
require __DIR__.'/admin.php';

//Accounts Panel
require __DIR__.'/accounts.php';



