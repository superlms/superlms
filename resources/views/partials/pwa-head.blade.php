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
<link rel="manifest" href="{{ route('pwa.manifest', ['role' => $pwaRole]) }}">
<meta name="theme-color" content="#4f46e5">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="SuperLMS">
<link rel="apple-touch-icon" href="/website-image/Logo.png">
@once
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js', { scope: '/' })
                    .catch(function (err) { console.warn('SW registration failed:', err); });
            });
        }

        /* Custom install control. The browser fires `beforeinstallprompt` only
           when the app is NOT installed — so this "Install app" button shows on
           first visit and reappears automatically after the user uninstalls the
           app. Hidden once installed or when already running standalone. */
        (function () {
            var deferred = null;

            function isStandalone() {
                return window.matchMedia('(display-mode: standalone)').matches ||
                    window.navigator.standalone === true;
            }

            function showButton() {
                if (isStandalone() || document.getElementById('pwa-install-btn')) return;
                var b = document.createElement('button');
                b.id = 'pwa-install-btn';
                b.type = 'button';
                b.textContent = 'Install app';
                b.setAttribute('style', [
                    'position:fixed', 'z-index:2147483647', 'right:16px', 'bottom:16px',
                    'padding:10px 16px', 'border:0', 'border-radius:9999px',
                    'background:#4f46e5', 'color:#fff', 'font:600 14px system-ui,-apple-system,sans-serif',
                    'box-shadow:0 6px 20px rgba(79,70,229,.4)', 'cursor:pointer'
                ].join(';'));
                b.addEventListener('click', function () {
                    if (!deferred) return;
                    deferred.prompt();
                    deferred.userChoice.finally(function () {
                        deferred = null;
                        b.remove();
                    });
                });
                (document.body || document.documentElement).appendChild(b);
            }

            window.addEventListener('beforeinstallprompt', function (e) {
                e.preventDefault();
                deferred = e;
                showButton();
            });

            window.addEventListener('appinstalled', function () {
                deferred = null;
                var b = document.getElementById('pwa-install-btn');
                if (b) b.remove();
            });
        })();
    </script>
@endonce
