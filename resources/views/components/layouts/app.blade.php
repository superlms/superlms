<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Restore the collapsed/expanded sidebar preference before first paint (no FOUC).
         Default is EXPANDED (the width every functionality screen is laid out for);
         collapse to the icon rail only when the user explicitly chose it. --}}
    <script>try{ if(localStorage.getItem('lmsSidebar')!=='closed'){ document.documentElement.classList.add('sidebar-expanded'); } }catch(e){}</script>
    {{-- A page the browser brings back with the Back button (from its cache, or
         a plain back/forward hit) still carries the Livewire snapshot it was
         serialised with. The server has moved on, so the restored page looks
         fine but every filter/typing update silently does nothing until the
         user reloads by hand. Reload once when we arrive that way — screens
         keep their filters in the URL, so the list comes back as it was. --}}
    <script>
        window.addEventListener('pageshow', function (e) {
            var nav = (window.performance && performance.getEntriesByType)
                ? performance.getEntriesByType('navigation')[0] : null;
            if (e.persisted || (nav && nav.type === 'back_forward')) {
                window.location.reload();
            }
        });
    </script>
    <link rel="icon" type="image/png" href="{{ url('website-image/Group 11525.png') }}" />
    @include('partials.pwa-head')
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
        /* The top bar's real height: the 4rem row plus the 1px bottom border it
           draws. Overlays that must sit UNDER the top bar anchor to this, not to
           `top-16` — 64px lands on the border itself, and an overlay with a
           background or a backdrop-blur then smears that line across the page. */
        :root { --lms-nav-h: 65px; }

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

        /* ─── Page header: title/tabs slide away, filter bar stays pinned ───
           Every admin/accounts page uses the same `sticky top-0` header: some
           title/stats/tab rows, then a gray (`bg-gray-50`) filter bar. We pull
           the header's sticky top UP by the height of the rows above the filter
           bar (measured into `--lms-hdr-top`), so scrolling down slides those
           rows out of the scrollport and parks the filter bar at the top, and
           scrolling back to the very top brings them in again with the content.
           It is plain CSS stickiness — nothing is toggled per scroll event, so a
           fast flick or a Livewire re-render can never strand the header
           half-drawn or leave it hidden at the top of the list.
           The `:has()` rule keeps working even in the frame right after a
           Livewire DOM morph wipes the JS-added class. */
        #main-scroll div.lms-pagehead,
        #main-scroll div.sticky.top-0:has(> div.bg-gray-50) {
            top: var(--lms-hdr-top, 0px);
        }

        /* Sidebar logo, expanded state. The collapsed rail overrides it below. */
        .lms-logo-img { width: 4.5rem !important; height: 4.5rem !important; }

        /* ─── Collapsible desktop sidebar ───
           Default (no `.sidebar-expanded` on <html>) = icon-only rail; clicking
           the three-dot toggle expands it to the full labelled sidebar and the
           content area shrinks to match. Widths are driven by a CSS variable so
           we never depend on Tailwind classes that aren't in the compiled build. */
        @media (min-width: 768px) {
            :root { --lms-sb: 4.75rem; }
            html.sidebar-expanded { --lms-sb: 253px; }

            .lms-aside, .lms-rail { width: var(--lms-sb) !important; transition: width .2s ease; }
            .lms-navbar { left: var(--lms-sb) !important; padding-left: 0 !important; transition: left .2s ease; }
            .lms-main   { left: var(--lms-sb) !important; transition: left .2s ease; }

            /* Collapsed state: hide labels/section titles/logo text, center icons. */
            html:not(.sidebar-expanded) .lms-label,
            html:not(.sidebar-expanded) .lms-section-title,
            html:not(.sidebar-expanded) .lms-logo-name,
            html:not(.sidebar-expanded) .lms-logo-sub { display: none !important; }

            html:not(.sidebar-expanded) .lms-nav-link { justify-content: center; padding-left: .5rem; padding-right: .5rem; }
            html:not(.sidebar-expanded) .lms-ico { margin-right: 0 !important; }
            html:not(.sidebar-expanded) .lms-topbar { justify-content: center !important; }
            html:not(.sidebar-expanded) .lms-logo-wrap { padding-left: .25rem !important; padding-right: .25rem !important; }
            html:not(.sidebar-expanded) .lms-logo-img { width: 2.5rem !important; height: 2.5rem !important; }
            html:not(.sidebar-expanded) .lms-nav { padding-left: .5rem !important; padding-right: .5rem !important; }
        }
    </style>
</head>

{{-- offcanvas drives the mobile sidebar drawer (the navbar hamburger flips it).
     Kept at body level so every page inherits the same state. --}}
<body class="bg-gray-50 h-screen w-screen overflow-hidden"
      x-data="{ offcanvas: false, sidebarOpen: document.documentElement.classList.contains('sidebar-expanded') }"
      x-effect="document.body.classList.toggle('drawer-open', offcanvas);
                document.documentElement.classList.toggle('sidebar-expanded', sidebarOpen);
                try { localStorage.setItem('lmsSidebar', sidebarOpen ? 'open' : 'closed'); } catch (e) {}"
      x-on:keydown.escape.window="offcanvas = false">

    <x-notifications position="top-end" />
    <x-dialog z-index="z-50" blur="md" align="center" />

    @if (Auth::user())
        {{-- ─── SIDEBAR — invisible/non-blocking on mobile, fixed rail on md+
             (each sidebar partial includes BOTH a `md:hidden` off-canvas
              drawer and a `hidden md:flex` static rail). ─── --}}
        <aside class="lms-aside z-50 w-0 md:fixed md:inset-y-0 md:left-0
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
    <header class="lms-navbar fixed top-0 left-0 right-0 z-40">
        @livewire('components.nav-bar')
    </header>

    {{-- ─── MAIN CONTENT WRAPPER ─── --}}
    {{-- Offset left by sidebar width, offset top by navbar height --}}
    {{-- Only THIS div scrolls --}}
    <div id="main-scroll" class="lms-main fixed inset-0 top-16 overflow-y-auto overflow-x-hidden">

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

    {{-- ─── Super LMS assistant panel ───
         The launcher lives in the top bar (next to the bell); this is only the
         panel it opens. Renders nothing unless the role is allowed AND an API
         key is set, so a deployment without one has no button. --}}
    @if (Auth::user() && in_array(Auth::user()->role, (array) config('gemini.roles', [])))
        @livewire('components.gemini-assistant')
    @endif

    @livewireScripts
    @livewireCalendarScripts
    @include('partials.auto-refresh')

    {{-- ─── Global toast for Livewire `notify` events ───
         Many components dispatch `$this->dispatch('notify', ['type'=>.., 'message'=>..])`.
         Render them as a lightweight top-end toast so success/error feedback is
         actually visible (WireUI's <x-notifications> only listens to its own API). --}}
    <div id="lms-toasts" style="position:fixed;top:16px;right:16px;z-index:9999;display:flex;flex-direction:column;gap:8px;pointer-events:none"></div>
    <script>
        (function () {
            if (window.__lmsNotify) return;
            window.__lmsNotify = 1;

            // Shared notification sound — used by the chat notifier and by
            // foreground web-push (FCM) so incoming notifications sound the same
            // everywhere. Browsers block audio until the first user gesture; we
            // ignore the rejected promise silently in that case.
            var __notifUrl = '{{ asset('sounds/notification.mp3') }}';
            var __notifAudio = null;
            window.lmsPlayNotifSound = function () {
                try {
                    var a = __notifAudio || new Audio(__notifUrl);
                    a.currentTime = 0;
                    a.volume = 0.6;
                    var p = a.play();
                    if (p && p.catch) p.catch(function () {});
                } catch (e) {}
            };

            // Autoplay policies only let a page make noise after the user has
            // interacted with it, so prime one Audio element on the very first
            // gesture — from then on incoming messages can chime on their own.
            (function () {
                function unlock() {
                    document.removeEventListener('pointerdown', unlock);
                    document.removeEventListener('keydown', unlock);
                    try {
                        var a = new Audio(__notifUrl);
                        a.volume = 0;
                        var p = a.play();
                        if (p && p.then) {
                            p.then(function () {
                                a.pause();
                                a.currentTime = 0;
                                __notifAudio = a;
                            }).catch(function () {});
                        }
                    } catch (e) {}
                }
                document.addEventListener('pointerdown', unlock, { once: true });
                document.addEventListener('keydown', unlock, { once: true });
            })();
            function toast(p) {
                p = Array.isArray(p) ? p[0] : p;
                if (!p || !p.message) return;
                var wrap = document.getElementById('lms-toasts');
                if (!wrap) return;
                var ok = (p.type || 'success') === 'success';
                var el = document.createElement('div');
                el.setAttribute('role', 'status');
                el.style.cssText = 'pointer-events:auto;max-width:340px;padding:12px 16px;border-radius:12px;font:500 13px/1.5 system-ui,sans-serif;color:#fff;box-shadow:0 10px 30px rgba(0,0,0,.18);opacity:0;transform:translateY(-8px);transition:opacity .25s,transform .25s;background:' + (ok ? '#059669' : '#dc2626');
                el.textContent = p.message;
                wrap.appendChild(el);
                requestAnimationFrame(function () { el.style.opacity = '1'; el.style.transform = 'none'; });
                setTimeout(function () {
                    el.style.opacity = '0'; el.style.transform = 'translateY(-8px)';
                    setTimeout(function () { el.remove(); }, 300);
                }, 4500);
            }
            // Also reachable from plain JS (e.g. the chat "Copied" confirmation).
            window.lmsToast = toast;

            document.addEventListener('livewire:init', function () {
                if (window.Livewire && typeof window.Livewire.on === 'function') {
                    window.Livewire.on('notify', toast);
                }
            });
        })();
    </script>

    {{-- ─── Page header offset ───
         Measures the header rows above the filter bar into `--lms-hdr-top` so
         the CSS above can slide them out of view on scroll. Re-measured when the
         header changes size (tab switch, wrapping row) and after Livewire
         re-renders; there is no per-scroll state to get wrong. --}}
    @if (Auth::user() && in_array(Auth::user()->role, ['admin', 'sub-admin', 'accounts', 'super-admin', 'sub-super-admin']))
        <script>
            (function () {
                if (window.__lmsHdr) return;
                window.__lmsHdr = 1;

                function getHeader() {
                    var c = document.getElementById('main-scroll');
                    return c ? c.querySelector('div.sticky.top-0') : null;
                }

                // Height of the rows sitting ABOVE the gray filter bar — how far
                // the header may slide up before the filter bar hits the top.
                // Filter bar first (it IS the header) → pin it as is; no filter
                // bar at all → let the whole header scroll away, as it used to.
                function offsetFor(header) {
                    var kids = header.children, filterIdx = -1;
                    for (var i = kids.length - 1; i >= 0; i--) {
                        if (kids[i].classList.contains('bg-gray-50')) { filterIdx = i; break; }
                    }
                    if (filterIdx === 0) return 0;
                    var end = filterIdx < 0 ? kids.length : filterIdx;
                    var h = 0;
                    for (var j = 0; j < end; j++) { h += kids[j].offsetHeight; }
                    return h;
                }

                var ro = null, roTarget = null;
                function observe(header) {
                    if (typeof ResizeObserver === 'undefined' || roTarget === header) return;
                    if (!ro) { ro = new ResizeObserver(function () { apply(); }); }
                    ro.disconnect();
                    ro.observe(header);
                    roTarget = header;
                }

                function apply() {
                    var container = document.getElementById('main-scroll');
                    if (!container) return;
                    var header = getHeader();
                    if (!header) { container.style.removeProperty('--lms-hdr-top'); return; }
                    header.classList.add('lms-pagehead');
                    observe(header);
                    container.style.setProperty('--lms-hdr-top', '-' + offsetFor(header) + 'px');
                }

                // A Livewire morph re-renders the header from server HTML, which
                // drops the JS-added class; re-add it (and re-measure) in the same
                // task, before paint.
                function registerMorphGuard() {
                    if (window.__lmsHdrHook) return;
                    if (!window.Livewire || typeof window.Livewire.hook !== 'function') return;
                    window.__lmsHdrHook = 1;
                    window.Livewire.hook('commit', function (payload) {
                        if (typeof payload.succeed === 'function') {
                            payload.succeed(function () { apply(); });
                        }
                    });
                }

                var rzT;
                window.addEventListener('resize', function () {
                    clearTimeout(rzT);
                    rzT = setTimeout(apply, 150);
                });

                // Late layout shifts (web fonts, a logo image) change the header
                // height after the first measurement — re-measure once they land.
                if (document.fonts && document.fonts.ready) {
                    document.fonts.ready.then(function () { apply(); });
                }

                document.addEventListener('DOMContentLoaded', function () { apply(); setTimeout(apply, 300); });
                document.addEventListener('livewire:init', function () { registerMorphGuard(); apply(); });
                document.addEventListener('livewire:navigated', function () { roTarget = null; apply(); });
                if (window.Livewire) registerMorphGuard();
                if (document.readyState !== 'loading') apply();
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
                        apiKey: 'AIzaSyBmS5hLvwYWXVvnAQBsCsvMeT73kJZ0Hzg',
                        authDomain: 'super-lms-48c90.firebaseapp.com',
                        projectId: 'super-lms-48c90',
                        storageBucket: 'super-lms-48c90.firebasestorage.app',
                        messagingSenderId: '1003028261382',
                        appId: '1:1003028261382:web:26be364e5bb6792d933187',
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

                    // Foreground messages → show a native notification + play the sound.
                    messaging.onMessage(function (payload) {
                        const n = payload.notification || {};
                        if (window.lmsPlayNotifSound) window.lmsPlayNotifSound();
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
