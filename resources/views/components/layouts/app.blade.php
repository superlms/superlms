<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Restore the collapsed/expanded sidebar preference before first paint (no FOUC).
         Default is EXPANDED (the width every functionality screen is laid out for);
         collapse to the icon rail only when the user explicitly chose it. --}}
    <script>try{ if(localStorage.getItem('lmsSidebar')!=='closed'){ document.documentElement.classList.add('sidebar-expanded'); } }catch(e){}</script>
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
           full screen. The header comes back only when you scroll all the way
           to the top (not on an upward scroll mid-list). Driven by JS that
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

        /* When re-applying the collapse state right after a Livewire DOM morph
           (the 5s auto-refresh), transitions are suspended for one frame so the
           header snaps to its previous state instead of animating/blinking. */
        #main-scroll .lms-header-noanim,
        #main-scroll .lms-header-noanim .lms-collapsible {
            transition: none !important;
        }

        /* ─── Collapsible desktop sidebar ───
           Default (no `.sidebar-expanded` on <html>) = icon-only rail; clicking
           the three-dot toggle expands it to the full labelled sidebar and the
           content area shrinks to match. Widths are driven by a CSS variable so
           we never depend on Tailwind classes that aren't in the compiled build. */
        @media (min-width: 768px) {
            :root { --lms-sb: 4.75rem; }
            html.sidebar-expanded { --lms-sb: 16rem; }

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
            window.lmsPlayNotifSound = function () {
                try {
                    var a = new Audio(__notifUrl);
                    a.volume = 0.6;
                    var p = a.play();
                    if (p && p.catch) p.catch(function () {});
                } catch (e) {}
            };
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
            document.addEventListener('livewire:init', function () {
                if (window.Livewire && typeof window.Livewire.on === 'function') {
                    window.Livewire.on('notify', toast);
                }
            });
        })();
    </script>

    {{-- ─── Scroll-aware collapsing page header (admin & accounts) ───
         Hides the title/stats/tabs once you scroll down past them (keeping only
         the sticky filter bar pinned), and restores them ONLY when scrolled back
         to the very top. Works generically across every admin page that uses the
         shared `sticky top-0` header + gray (`bg-gray-50`) filter bar pattern. --}}
    @if (Auth::user() && in_array(Auth::user()->role, ['admin', 'sub-admin', 'accounts', 'super-admin', 'sub-super-admin']))
        <script>
            (function () {
                if (window.__lmsHdr) return;
                window.__lmsHdr = 1;

                // While a collapse/expand animates, the content height changes and
                // the browser nudges scrollTop. We ignore scroll for a short window
                // after each toggle so it can settle (prevents flicker). Hoisted to
                // IIFE scope so the morph guard can also arm it.
                var lockUntil = 0;
                function now() {
                    return (window.performance && performance.now) ? performance.now() : Date.now();
                }

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

                    kids.forEach(function (k) { k.classList.remove('lms-collapsible', 'lms-filterbar'); k.style.maxHeight = ''; });

                    // No filter bar (or it's the first child) → collapse the whole header.
                    var collapseEnd = filterIdx <= 0 ? kids.length : filterIdx;
                    for (var j = 0; j < collapseEnd; j++) {
                        kids[j].classList.add('lms-collapsible');
                        // Pin an accurate max-height so the collapse animates from the
                        // element's REAL height instead of a fixed 50rem — otherwise most
                        // of the transition is spent on invisible range and the filter bar
                        // slides up with a lag.
                        kids[j].style.maxHeight = (kids[j].scrollHeight + 16) + 'px';
                    }
                    if (filterIdx > 0) { kids[filterIdx].classList.add('lms-filterbar'); }
                }

                // Height of the parts that collapse away — the collapse trigger
                // must sit ABOVE this, otherwise the scrollTop drop from
                // collapsing lands us back at the top and re-shows the header.
                function collapsibleHeight(header) {
                    var h = 0, els = header.querySelectorAll('.lms-collapsible');
                    for (var i = 0; i < els.length; i++) { h += els[i].offsetHeight; }
                    return h;
                }

                // A Livewire DOM morph (e.g. the 5s auto-refresh) re-renders the
                // header from server HTML, which does NOT carry the client-only
                // collapse classes/inline max-heights — so the header pops back in
                // and the filter/rows re-animate (a blink). We capture the collapsed
                // state before the morph and re-apply it synchronously afterwards,
                // in the same JS task (before paint) with transitions suspended, so
                // the header snaps back to exactly where it was — no flash.
                function reapplyHeaderState(wasCollapsed) {
                    var container = document.getElementById('main-scroll');
                    if (!container) return;
                    var header = getHeader(container);
                    if (!header) return;
                    header.classList.add('lms-header-noanim');
                    header.removeAttribute('data-lms-sig'); // force re-mark: morph reset children
                    mark(header);                            // measured while expanded → correct max-heights
                    if (wasCollapsed) {
                        header.classList.add('lms-header-collapsed');
                    } else {
                        header.classList.remove('lms-header-collapsed');
                    }
                    void header.offsetHeight;                // flush styles before re-enabling transitions
                    header.classList.remove('lms-header-noanim');
                    lockUntil = now() + 400;                 // ignore the reflow nudge from scroll restore
                }

                function registerMorphGuard() {
                    if (window.__lmsHdrHook) return;
                    if (!window.Livewire || typeof window.Livewire.hook !== 'function') return;
                    window.__lmsHdrHook = 1;
                    window.Livewire.hook('commit', function (payload) {
                        var container = document.getElementById('main-scroll');
                        var header = container && getHeader(container);
                        var wasCollapsed = !!(header && header.classList.contains('lms-header-collapsed'));
                        if (typeof payload.succeed === 'function') {
                            payload.succeed(function () { reapplyHeaderState(wasCollapsed); });
                        }
                    });
                }

                function init() {
                    registerMorphGuard();

                    var container = document.getElementById('main-scroll');
                    if (!container || container.dataset.lmsScroll === '1') return;
                    container.dataset.lmsScroll = '1';

                    container.addEventListener('scroll', function () {
                        var header = getHeader(container);
                        if (!header) return;
                        mark(header);

                        // Ignore the reflow nudge while a toggle is animating.
                        if (now() < lockUntil) return;

                        var y = container.scrollTop;
                        var collapsed = header.classList.contains('lms-header-collapsed');

                        if (collapsed) {
                            // Reappear ONLY when scrolled all the way back to the top.
                            if (y <= 1) {
                                header.classList.remove('lms-header-collapsed');
                                lockUntil = now() + 400;
                            }
                        } else {
                            // Position-based (not delta) so it also hides on a slow
                            // scroll. Threshold clears the collapsible height so the
                            // collapse doesn't bounce back to the top.
                            var threshold = Math.max(60, collapsibleHeight(header) + 24);
                            if (y > threshold) {
                                header.classList.add('lms-header-collapsed');
                                lockUntil = now() + 400;
                            }
                        }
                    }, { passive: true });

                    // Responsive rows change height across breakpoints — re-measure the
                    // pinned max-heights on resize so the header never gets clipped.
                    var rzT;
                    window.addEventListener('resize', function () {
                        clearTimeout(rzT);
                        rzT = setTimeout(function () {
                            var header = getHeader(container);
                            if (header) { header.removeAttribute('data-lms-sig'); mark(header); }
                        }, 150);
                    });
                }

                document.addEventListener('DOMContentLoaded', init);
                document.addEventListener('livewire:init', registerMorphGuard);
                document.addEventListener('livewire:navigated', function () {
                    var c = document.getElementById('main-scroll');
                    if (c) {
                        var h = getHeader(c);
                        if (h) { h.classList.remove('lms-header-collapsed'); h.removeAttribute('data-lms-sig'); }
                    }
                    init();
                });
                if (window.Livewire) registerMorphGuard();
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
