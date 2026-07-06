{{-- Global live auto-refresh: re-renders every Livewire component on the page
     on a fixed interval so new data (enquiries, payments, notifications, lists…)
     appears without a manual reload. Uses Livewire's $refresh — public property
     state (open panels, typed inputs bound with wire:model) is preserved, and it
     pauses while the browser tab is hidden to save resources.

     To avoid the "blink + jump to top" that a naive $refresh causes, the scroll
     position (window + any element marked [data-preserve-scroll]) is captured
     before each auto-refresh commit and restored right after Livewire morphs the
     DOM. Restoration is scoped to auto-refresh commits so user-initiated actions
     (pagination, intentional scroll-to-top, etc.) are never fought. --}}
@once
    <script>
        (function () {
            var INTERVAL_MS = 5000;      // refresh every 5 seconds
            var AUTO_WINDOW_MS = 1200;   // commits fired this soon after a tick are "auto" commits

            var lastTickAt = 0;

            function isAutoCommit() {
                return (Date.now() - lastTickAt) < AUTO_WINDOW_MS;
            }

            // Capture window scroll + any opted-in scroll containers.
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

            function refreshAll() {
                if (document.hidden || !window.Livewire) return;
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

            // Keep the viewport steady across auto-refresh DOM morphs.
            function installScrollPreservation() {
                if (!window.Livewire || typeof window.Livewire.hook !== 'function') return;
                window.Livewire.hook('commit', function (payload) {
                    if (!isAutoCommit()) return;
                    var saved = captureScroll();
                    if (typeof payload.succeed === 'function') {
                        payload.succeed(function () {
                            // Restore after the morph, then again next frame in case
                            // layout height settles a tick later.
                            restoreScroll(saved);
                            requestAnimationFrame(function () { restoreScroll(saved); });
                        });
                    }
                });
            }

            function start() {
                if (window.__superlmsAutoRefresh) return; // never start twice
                installScrollPreservation();
                window.__superlmsAutoRefresh = setInterval(refreshAll, INTERVAL_MS);
            }

            if (window.Livewire) {
                start();
            } else {
                document.addEventListener('livewire:init', start);
            }
        })();
    </script>
@endonce
