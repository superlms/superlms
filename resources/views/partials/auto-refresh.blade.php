{{-- Global live auto-refresh, change-detection edition.

     Every INTERVAL_MS the page is fetched silently in the background (plain GET,
     no Livewire involved) and reduced to a data fingerprint — scripts, CSRF
     tokens, Livewire snapshot attributes and signed image query-strings are
     stripped so only real content is compared. The visible page is NOT touched
     unless that fingerprint changes; only then is a single Livewire $refresh
     morph triggered so the new data appears instantly. Result: no 5-second
     blink — the UI repaints only when there is actually something new.

     Scroll (window + any [data-preserve-scroll] element) is captured before an
     auto-triggered morph and restored right after, so the viewport never jumps.
     User-initiated Livewire actions are never interfered with.

     A page nobody has touched (mouse, keys, touch, scroll) for two minutes is
     checked every 30 seconds instead of every 5; the first touch after that
     checks at once and the 5-second pace resumes. --}}
@once
    <script>
        (function () {
            var INTERVAL_MS = 5000;      // background change-check every 5 seconds
            var AUTO_WINDOW_MS = 1200;   // commits fired this soon after our refresh are "auto" commits
            var IDLE_AFTER_MS = 120000;  // untouched this long = idle
            var IDLE_INTERVAL_MS = 30000; // an idle page is checked this often

            var lastTickAt = 0;
            var lastFingerprint = null;
            var checking = false;
            var lastActivityAt = Date.now();
            var lastCheckAt = 0;

            function isAutoCommit() {
                return (Date.now() - lastTickAt) < AUTO_WINDOW_MS;
            }

            function isIdle() {
                return (Date.now() - lastActivityAt) > IDLE_AFTER_MS;
            }

            // Someone is at the page: coming back from idle checks straight away.
            function onActivity(e) {
                // Our own scroll restore after an auto refresh is not a person.
                if (e && e.type === 'scroll' && isAutoCommit()) return;
                var wasIdle = isIdle();
                lastActivityAt = Date.now();
                if (wasIdle) { tick(); }
            }

            // ── Scroll preservation (unchanged) ─────────────────────────────
            function captureScroll() {
                var saved = [{ el: window, x: window.scrollX, y: window.scrollY }];
                document.querySelectorAll('[data-preserve-scroll]').forEach(function (el) {
                    saved.push({ el: el, x: el.scrollLeft, y: el.scrollTop });
                });
                return saved;
            }

            function restoreScroll(saved) {
                saved.forEach(function (s) {
                    if (s.el === window) {
                        if (window.scrollX !== s.x || window.scrollY !== s.y) {
                            window.scrollTo(s.x, s.y);
                        }
                    } else if (s.el.isConnected) {
                        if (s.el.scrollLeft !== s.x) { s.el.scrollLeft = s.x; }
                        if (s.el.scrollTop !== s.y) { s.el.scrollTop = s.y; }
                    }
                });
            }

            function installScrollPreservation() {
                if (!window.Livewire || typeof window.Livewire.hook !== 'function') return;
                window.Livewire.hook('commit', function (payload) {
                    if (!isAutoCommit()) return;
                    var saved = captureScroll();
                    if (typeof payload.succeed === 'function') {
                        payload.succeed(function () {
                            restoreScroll(saved);
                            requestAnimationFrame(function () { restoreScroll(saved); });
                        });
                    }
                });
            }

            // ── Apply new data: one $refresh morph on every component ──────
            function refreshAll() {
                if (!window.Livewire) return;
                lastTickAt = Date.now();
                try {
                    window.Livewire.all().forEach(function (component) {
                        try {
                            if (component.$wire && typeof component.$wire.$refresh === 'function') {
                                component.$wire.$refresh();
                            } else if (typeof component.call === 'function') {
                                component.call('$refresh');
                            }
                        } catch (e) { /* ignore a single component failure */ }
                    });
                } catch (e) { /* ignore */ }
            }

            // ── Change detection ────────────────────────────────────────────
            // Reduce a fresh server render to just its meaningful content so
            // per-render noise (CSRF tokens, Livewire snapshots, signed URLs)
            // never registers as a "change".
            function normalize(html) {
                var doc;
                try {
                    doc = new DOMParser().parseFromString(html, 'text/html');
                } catch (e) {
                    return html;
                }
                if (!doc || !doc.body) return html;

                doc.body.querySelectorAll('script, noscript, template, meta[name="csrf-token"], input[name="_token"]')
                    .forEach(function (el) { el.remove(); });

                doc.body.querySelectorAll('*').forEach(function (el) {
                    // Livewire snapshot/effect attributes differ on every render.
                    Array.prototype.slice.call(el.attributes).forEach(function (a) {
                        if (a.name.indexOf('wire:') === 0) { el.removeAttribute(a.name); }
                        // Blade's entangle directive writes the component's
                        // id — new on every render — into Alpine's x-data;
                        // without this the Content page read as changed on
                        // every check and refreshed itself every 5 seconds.
                        else if (a.value.indexOf('Livewire.find(') !== -1) {
                            a.value = a.value.replace(/Livewire\.find\((['"])[^'"]*\1\)/g, 'Livewire.find()');
                        }
                    });
                    // Signed/temporary media URLs (e.g. S3) get a fresh signature
                    // per render — compare the path only.
                    if ((el.tagName === 'IMG' || el.tagName === 'SOURCE') && el.getAttribute('src')) {
                        el.setAttribute('src', el.getAttribute('src').split('?')[0]);
                    }
                    if (el.getAttribute && el.getAttribute('srcset')) {
                        el.removeAttribute('srcset');
                    }
                });

                return doc.body.innerHTML;
            }

            function fingerprint(s) {
                // djb2 — cheap and good enough to detect "something changed".
                var h = 5381, i = s.length;
                while (i) { h = ((h * 33) ^ s.charCodeAt(--i)) >>> 0; }
                return h + ':' + s.length;
            }

            function tick() {
                if (document.hidden || checking || !window.Livewire) return;
                if (isIdle() && (Date.now() - lastCheckAt) < IDLE_INTERVAL_MS) return;
                checking = true;
                lastCheckAt = Date.now();
                fetch(window.location.href, {
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: { 'X-SuperLMS-Background-Poll': '1' }
                }).then(function (r) {
                    // Redirect (e.g. session expired) or error → leave the page
                    // alone; a real user action will surface the login screen.
                    if (!r.ok || (r.url && r.url.split('?')[0] !== window.location.href.split('?')[0])) {
                        return null;
                    }
                    return r.text();
                }).then(function (html) {
                    if (html == null) { checking = false; return; }
                    whenIdle(function () {
                        try {
                            var fp = fingerprint(normalize(html));
                            if (lastFingerprint === null) {       // first check = baseline
                                lastFingerprint = fp;
                                return;
                            }
                            if (fp !== lastFingerprint) {
                                lastFingerprint = fp;
                                refreshAll();                     // new data → show it now
                            }
                        } finally {
                            checking = false;
                        }
                    });
                }).catch(function () { checking = false; });
            }

            // Reading a whole page takes a few milliseconds (more on an old
            // computer), so it waits for a moment the browser has nothing else
            // to do — at most a second — instead of landing in the middle of
            // typing or scrolling.
            function whenIdle(fn) {
                if (typeof window.requestIdleCallback === 'function') {
                    window.requestIdleCallback(fn, { timeout: 1000 });
                } else {
                    setTimeout(fn, 0);
                }
            }

            function start() {
                if (window.__superlmsAutoRefresh) return; // never start twice
                installScrollPreservation();
                setTimeout(tick, 1000);                   // establish baseline early
                window.__superlmsAutoRefresh = setInterval(tick, INTERVAL_MS);
                // Coming back to the tab → check immediately instead of waiting.
                document.addEventListener('visibilitychange', function () {
                    if (!document.hidden) { lastActivityAt = Date.now(); tick(); }
                });
                ['mousemove', 'mousedown', 'keydown', 'wheel', 'touchstart', 'scroll'].forEach(function (type) {
                    window.addEventListener(type, onActivity, { capture: true, passive: true });
                });
            }

            if (window.Livewire) {
                start();
            } else {
                document.addEventListener('livewire:init', start);
            }
        })();
    </script>
@endonce
