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
    $u = auth('web')->user();
    return ($u && $u->organization_id)
        ? redirect()->route('accounts.dashboard', ['organization' => $u->organization_id])
        : redirect()->route('accounts.login');
})->name('pwa.accounts');

// Role-specific web-app manifests (correct MIME). Each role installs as its own
// separate app with its OWN scope, so installing one (e.g. admin) doesn't make
// the browser treat the whole site as that app — the other roles stay usable in
// the browser and installable on their own.
//   - admin    → scope /{org}/  (needs the logged-in org; manifest is fetched
//                 with credentials so we can read it). Falls back to a broad
//                 scope only while logged out.
//   - accounts → scope /accounts
//   - superadmin/site → scope / (their routes live at the site root)
// Bump $idVersion if an install ever gets "stuck" on a device.
Route::get('/pwa/manifest/{role}', function (string $role) {
    $idVersion = 'v3';
    $u = auth('web')->user();

    if ($role === 'admin') {
        if ($u && $u->organization_id) {
            $org      = $u->organization_id;
            $name     = 'SuperLMS Admin';
            $short    = 'Admin';
            $id       = '/pwa/admin-' . $org;
            $start    = route('admin.launch', ['organization' => $org], false);
            $scope    = '/' . $org . '/';
        } else {
            $name  = 'SuperLMS Admin';
            $short = 'Admin';
            $id    = '/pwa/admin';
            $start = route('pwa.admin', [], false);
            $scope = '/';
        }
    } elseif ($role === 'accounts') {
        $name  = 'SuperLMS Accounts';
        $short = 'Accounts';
        $id    = '/pwa/accounts';
        $start = route('accounts.launch', [], false);
        $scope = '/accounts';
    } elseif ($role === 'superadmin') {
        $name  = 'SuperLMS Super Admin';
        $short = 'Super';
        $id    = '/pwa/superadmin';
        $start = route('pwa.superadmin', [], false);
        $scope = '/';
    } else {
        $name  = 'SuperLMS';
        $short = 'SuperLMS';
        $id    = '/pwa/site';
        $start = '/';
        $scope = '/';
    }

    $manifest = [
        'name'             => $name,
        'short_name'       => $short,
        'id'               => $id . '/' . $idVersion,
        'start_url'        => $start,
        'scope'            => $scope,
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
        ->header('Content-Type', 'application/manifest+json')
        // Per-user (admin org) — never let a shared cache serve one org's manifest to another.
        ->header('Cache-Control', 'private, no-store');
})->name('pwa.manifest');

//SuperLMS Website (must be before admin — avoids {organization} wildcard swallowing /web/* routes)
require __DIR__.'/website.php';

// Super Admin
require __DIR__.'/super-admin.php';

//Admin School
require __DIR__.'/admin.php';

//Accounts Panel
require __DIR__.'/accounts.php';



