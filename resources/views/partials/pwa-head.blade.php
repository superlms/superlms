{{-- Progressive Web App: installable "add to home screen" web app.
     Root-relative URLs keep the manifest + service worker same-origin even
     when static assets are served from a CDN. --}}
<link rel="manifest" href="/manifest.webmanifest">
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
    </script>
@endonce
