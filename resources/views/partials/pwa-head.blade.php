{{-- Progressive Web App: installable "add to home screen" web app.
     A role-specific manifest is served so installing from the admin / super-admin
     / accounts area produces a separate app that opens straight into that role
     (and shows that role's login when the session has expired). --}}
@php
    $pwaRole = 'site';
    if (request()->routeIs('super-admin.*') || request()->routeIs('pwa.superadmin')) {
        $pwaRole = 'superadmin';
    } elseif (request()->routeIs('accounts.*') || request()->routeIs('pwa.accounts')) {
        $pwaRole = 'accounts';
    } elseif (request()->routeIs('admin.*') || request()->routeIs('pwa.admin')) {
        $pwaRole = 'admin';
    }
@endphp
<link rel="manifest" href="{{ route('pwa.manifest', ['role' => $pwaRole]) }}" crossorigin="use-credentials">
<meta name="theme-color" content="#4f46e5">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="SuperLMS">
<link rel="apple-touch-icon" href="/website-image/Logo.png">
@once
    <script>
        /* No in-page "Install app" button — installing is left to the browser's
           own UI (the install icon in the address bar / "Install app" in the
           browser menu), which appears because a manifest + service worker are
           registered here. */
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js', { scope: '/' })
                    .catch(function (err) { console.warn('SW registration failed:', err); });
            });
        }
    </script>
@endonce
