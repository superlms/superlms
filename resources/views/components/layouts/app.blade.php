<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ url('website-image/Group 11525.png') }}" />
    @wireUiScripts
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>{{ $title ?? 'SuperLMS' }}</title>

    {{-- Rich Text --}}
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/trix@2.0.8/dist/trix.css">
    <script type="text/javascript" src="https://unpkg.com/trix@2.0.8/dist/trix.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>

    <style>
        /* Prevent any horizontal scroll at root level */
        html,
        body {
            overflow: hidden;
            height: 100%;
            width: 100%;
        }

        /* Lock body scroll when the mobile drawer is open */
        body.drawer-open {
            overflow: hidden;
        }

        /* ─── Scroll-aware collapsing page header (admin) ───
           On scroll-down the title/stats/tabs collapse away and only the
           sticky filter bar stays pinned at the top, giving the content the
           full screen. Scroll-up brings the header back. Driven by JS that
           toggles `.lms-header-collapsed` on the page header. */
        #main-scroll .lms-collapsible {
            overflow: hidden;
            max-height: 50rem;
            opacity: 1;
            transition: max-height .3s ease, opacity .2s ease, margin .3s ease, padding .3s ease;
        }

        #main-scroll .lms-header-collapsed {
            padding-top: 0 !important;
            padding-bottom: 0 !important;
        }

        #main-scroll .lms-header-collapsed .lms-collapsible {
            max-height: 0 !important;
            opacity: 0;
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            pointer-events: none;
        }

        #main-scroll .lms-header-collapsed .lms-filterbar {
            margin-top: 0 !important;
        }
    </style>
</head>

{{-- offcanvas drives the mobile sidebar drawer (the navbar hamburger flips it).
     Kept at body level so every page inherits the same state. --}}
<body class="bg-gray-50 h-screen w-screen overflow-hidden"
      x-data="{ offcanvas: false }"
      x-effect="document.body.classList.toggle('drawer-open', offcanvas)"
      x-on:keydown.escape.window="offcanvas = false">

    <x-notifications position="top-end" />
    <x-dialog z-index="z-50" blur="md" align="center" />

    @if (Auth::user())
        {{-- ─── SIDEBAR — invisible/non-blocking on mobile, fixed rail on md+
             (each sidebar partial includes BOTH a `md:hidden` off-canvas
              drawer and a `hidden md:flex` static rail). ─── --}}
        <aside class="z-50 w-0 md:w-64 md:fixed md:inset-y-0 md:left-0
                      md:shadow-md md:overflow-y-auto md:overflow-x-hidden">
            @if (in_array(Auth::user()->role, ['super-admin', 'sub-super-admin']))
                @include('admin-components.super-admin-sidebar')
            @elseif (in_array(Auth::user()->role, ['admin', 'sub-admin']))
                @include('admin-components.admin-sidebar')
            @elseif (Auth::user()->role === 'accounts')
                @include('admin-components.accounts-sidebar')
            @endif
        </aside>
    @endif

    {{-- ─── NAVBAR (fixed, sits above everything; offset by sidebar on md+) ─── --}}
    <header class="fixed top-0 left-0 right-0 z-40 md:pl-64">
        @livewire('components.nav-bar')
    </header>

    {{-- ─── MAIN CONTENT WRAPPER ─── --}}
    {{-- Offset left by sidebar width, offset top by navbar height --}}
    {{-- Only THIS div scrolls --}}
    <div id="main-scroll" class="fixed inset-0 md:left-64 top-16 overflow-y-auto overflow-x-hidden">

        {{-- Background decorative blobs (contained inside this wrapper) --}}
        <div class="relative min-h-full w-full">
            <div
                class="pointer-events-none absolute w-[400px] h-[400px] bg-pink-200 rounded-full opacity-30 blur-3xl top-[-50px] left-[-100px]">
            </div>
            <div
                class="pointer-events-none absolute w-[500px] h-[500px] bg-purple-200 rounded-full opacity-30 blur-3xl top-0 right-[50px]">
            </div>
            <div
                class="pointer-events-none absolute w-[300px] h-[300px] bg-orange-200 rounded-full opacity-30 blur-3xl top-[100px] right-[150px]">
            </div>

            {{-- Slot content --}}
            <div class="relative z-10">
                {{ $slot }}
            </div>
        </div>

    </div>

    {{-- ─── Live chat notifications (toast preview + badge sync) ─── --}}
    @if (Auth::user() && in_array(Auth::user()->role, ['admin', 'sub-admin', 'accounts']))
        @livewire('chat.notifier')
    @endif

    @livewireScripts
    @livewireCalendarScripts

    {{-- ─── Scroll-aware collapsing page header (admin & accounts) ───
         Hides the title/stats/tabs while scrolling down (keeping only the
         sticky filter bar pinned), and restores them on scroll up. Works
         generically across every admin page that uses the shared
         `sticky top-0` header + gray (`bg-gray-50`) filter bar pattern. --}}
    @if (Auth::user() && in_array(Auth::user()->role, ['admin', 'sub-admin', 'accounts', 'super-admin', 'sub-super-admin']))
        <script>
            (function () {
                if (window.__lmsHdr) return;
                window.__lmsHdr = 1;

                function getHeader(container) {
                    return container.querySelector('.sticky.top-0');
                }

                // Tag the rows above the filter bar as collapsible, and the
                // filter bar itself so it can stay pinned. Recomputed only when
                // the header's child structure changes (e.g. tab switches).
                function mark(header) {
                    var kids = Array.prototype.slice.call(header.children);
                    var filterIdx = -1;
                    for (var i = kids.length - 1; i >= 0; i--) {
                        if (kids[i].classList.contains('bg-gray-50')) { filterIdx = i; break; }
                    }
                    var sig = kids.length + ':' + filterIdx;
                    if (header.dataset.lmsSig === sig) return;
                    header.dataset.lmsSig = sig;

                    kids.forEach(function (k) { k.classList.remove('lms-collapsible', 'lms-filterbar'); });

                    // No filter bar (or it's the first child) → collapse the whole header.
                    var collapseEnd = filterIdx <= 0 ? kids.length : filterIdx;
                    for (var j = 0; j < collapseEnd; j++) { kids[j].classList.add('lms-collapsible'); }
                    if (filterIdx > 0) { kids[filterIdx].classList.add('lms-filterbar'); }
                }

                function init() {
                    var container = document.getElementById('main-scroll');
                    if (!container || container.dataset.lmsScroll === '1') return;
                    container.dataset.lmsScroll = '1';

                    var lastY = container.scrollTop;
                    // While a collapse/expand is animating, the content height
                    // changes and the browser nudges scrollTop. Without a lock
                    // that nudge reads as a direction change and the header
                    // flickers. We ignore scroll for a short window after each
                    // toggle so it can settle.
                    var lockUntil = 0;
                    function now() {
                        return (window.performance && performance.now) ? performance.now() : Date.now();
                    }
                    container.addEventListener('scroll', function () {
                        var header = getHeader(container);
                        var y = container.scrollTop;
                        if (!header) { lastY = y; return; }
                        mark(header);

                        var collapsed = header.classList.contains('lms-header-collapsed');

                        // At the very top, always show the full header.
                        if (y <= 8) {
                            if (collapsed) { header.classList.remove('lms-header-collapsed'); lockUntil = now() + 400; }
                            lastY = y;
                            return;
                        }

                        // Ignore reflow-induced scrolls during the animation.
                        if (now() < lockUntil) { lastY = y; return; }

                        var dy = y - lastY;
                        if (dy > 6 && y > 90 && !collapsed) {
                            header.classList.add('lms-header-collapsed');
                            lockUntil = now() + 400;
                        } else if (dy < -6 && collapsed) {
                            header.classList.remove('lms-header-collapsed');
                            lockUntil = now() + 400;
                        }
                        lastY = y;
                    }, { passive: true });
                }

                document.addEventListener('DOMContentLoaded', init);
                document.addEventListener('livewire:navigated', function () {
                    var c = document.getElementById('main-scroll');
                    if (c) {
                        var h = getHeader(c);
                        if (h) { h.classList.remove('lms-header-collapsed'); h.removeAttribute('data-lms-sig'); }
                    }
                    init();
                });
                if (document.readyState !== 'loading') init();
            })();
        </script>
    @endif

    {{-- ─── Web Push (FCM) registration — super-admin only ─── --}}
    @if (Auth::user() && in_array(Auth::user()->role, ['super-admin', 'sub-super-admin']))
        <script src="https://www.gstatic.com/firebasejs/10.12.2/firebase-app-compat.js"></script>
        <script src="https://www.gstatic.com/firebasejs/10.12.2/firebase-messaging-compat.js"></script>
        <script>
            (function () {
                if (typeof firebase === 'undefined' || !('serviceWorker' in navigator)) return;
                try {
                    firebase.initializeApp({
                        apiKey: 'AIzaSyBRZcETdNS1gcdedGB_IW8KwOSyUUXTa6w',
                        authDomain: 'superlms-lms-57e8c.firebaseapp.com',
                        projectId: 'superlms-lms-57e8c',
                        storageBucket: 'superlms-lms-57e8c.firebasestorage.app',
                        messagingSenderId: '682389969874',
                        appId: '1:682389969874:web:f9e4948399cdc52cc5c60b',
                    });

                    const messaging = firebase.messaging();
                    const VAPID_KEY = 'BGrupAUEMUzVBLV9lPd4DGYo5_9AKltHbcTOKWiFMiHlpixwoP9_qfHu_OnVBqyGbUFduqdOuCADp7sjRmPSqhY';

                    navigator.serviceWorker.register('/firebase-messaging-sw.js').then(function (registration) {
                        Notification.requestPermission().then(function (permission) {
                            if (permission !== 'granted') return;
                            messaging.getToken({ vapidKey: VAPID_KEY, serviceWorkerRegistration: registration })
                                .then(function (token) {
                                    if (!token) return;
                                    fetch('{{ route('super-admin.fcm-token') }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'Accept': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        },
                                        body: JSON.stringify({ token: token }),
                                    }).catch(function () {});
                                })
                                .catch(function (e) { console.warn('FCM getToken failed', e); });
                        });
                    }).catch(function (e) { console.warn('FCM SW registration failed', e); });

                    // Foreground messages → show a native notification.
                    messaging.onMessage(function (payload) {
                        const n = payload.notification || {};
                        try {
                            new Notification(n.title || 'SuperLMS', { body: n.body || '' });
                        } catch (e) {}
                    });
                } catch (e) {
                    console.warn('FCM init failed', e);
                }
            })();
        </script>
    @endif
</body>

</html>
