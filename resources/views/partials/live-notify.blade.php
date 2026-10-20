{{-- A notification that comes in while the school admin or the super-admin is
     working on the panel pops up at the top right — the same card as the
     page's own "Payment approved" (WireUI's notification) — and is read out.

     Only while they are working: this tab is in front and they have clicked,
     typed or scrolled in the last 90 seconds. Otherwise nothing pops up or
     speaks, and coming back starts from then — what came in meanwhile is in
     the bell, as every notification always is (they all stay unread there).

     Every 20 seconds the tab in front asks LiveNotificationController for what
     came in since it last asked. One list of what has been shown is kept for
     all tabs, so a notification pops up once. Param: $liveUrl. --}}
@once
    <script>
        (function () {
            if (window.__lmsLiveNotify) return;
            window.__lmsLiveNotify = 1;

            var URL_LIVE  = @json($liveUrl);
            var KEY       = 'superlms-live-notify:' + @json((string) Auth::id());
            var ACTIVE_MS = 90 * 1000;
            var EVERY_MS  = 20 * 1000;

            var lastInput = Date.now();   // the page was just opened by them
            var skipped   = false;        // an ask let go by while nobody was working
            var busy      = false;
            var audio     = null;

            function touched() {
                lastInput = Date.now();
                // A sound can only start after the person has used the page.
                if (!audio && (window.AudioContext || window.webkitAudioContext)) {
                    try { audio = new (window.AudioContext || window.webkitAudioContext)(); } catch (e) {}
                }
            }
            ['pointerdown', 'keydown', 'wheel', 'touchstart', 'scroll', 'mousemove'].forEach(function (ev) {
                window.addEventListener(ev, touched, { passive: true, capture: true });
            });

            function working() {
                return document.visibilityState === 'visible'
                    && (typeof document.hasFocus !== 'function' || document.hasFocus())
                    && Date.now() - lastInput < ACTIVE_MS;
            }

            function load() {
                try { return JSON.parse(localStorage.getItem(KEY)) || {}; } catch (e) { return {}; }
            }
            function store(s) {
                try { localStorage.setItem(KEY, JSON.stringify(s)); } catch (e) {}
            }

            function chime() {
                if (!audio) return;
                try {
                    if (audio.state === 'suspended') audio.resume();
                    var t = audio.currentTime;
                    [[880, 0], [1320, 0.12]].forEach(function (n) {
                        var o = audio.createOscillator(), g = audio.createGain();
                        o.type = 'sine';
                        o.frequency.value = n[0];
                        g.gain.setValueAtTime(0.0001, t + n[1]);
                        g.gain.exponentialRampToValueAtTime(0.15, t + n[1] + 0.02);
                        g.gain.exponentialRampToValueAtTime(0.0001, t + n[1] + 0.35);
                        o.connect(g); g.connect(audio.destination);
                        o.start(t + n[1]); o.stop(t + n[1] + 0.4);
                    });
                } catch (e) {}
            }

            function speak(items) {
                if (!('speechSynthesis' in window) || !items.length) return;
                var first = items[0];
                var text  = (items.length > 1 ? items.length + ' new notifications. ' : 'New notification. ')
                    + first.title + (first.body ? '. ' + first.body : '');
                try {
                    var say    = new SpeechSynthesisUtterance(text.slice(0, 240));
                    var voices = window.speechSynthesis.getVoices() || [];
                    var voice  = voices.find(function (v) { return /en[-_]IN/i.test(v.lang); })
                        || voices.find(function (v) { return /^en/i.test(v.lang); });
                    if (voice) { say.voice = voice; say.lang = voice.lang; } else { say.lang = 'en-IN'; }
                    say.rate = 1;
                    window.speechSynthesis.cancel();
                    window.speechSynthesis.speak(say);
                } catch (e) {}
            }

            function show(item) {
                var icon = /(reject|fail|absent|overdue|delete|remov|alert|due)/i.test(item.type + ' ' + item.title)
                    ? 'warning' : 'success';
                if (window.$wireui && typeof window.$wireui.notify === 'function') {
                    window.$wireui.notify({ title: item.title, description: item.body || '', icon: icon, timeout: 8000 });
                }
            }

            function ask() {
                if (busy) return;
                if (!working()) { skipped = true; return; }

                // Carry on from the last ask — this page's, or another page's or
                // tab's a moment ago — unless nobody was working since: then
                // start counting from now.
                var state  = load();
                var recent = state.cursor && state.at && Date.now() - state.at < 75 * 1000;
                var since  = !skipped && recent ? state.cursor : '';
                busy = true;

                fetch(URL_LIVE + (since ? '?since=' + encodeURIComponent(since) : ''), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (data) {
                        if (!data || !data.now) return;
                        state = load();
                        var seen = Array.isArray(state.seen) ? state.seen : [];
                        var news = since
                            ? (data.items || []).filter(function (i) { return seen.indexOf(i.id) === -1; })
                            : [];

                        state.cursor = data.now;
                        state.at     = Date.now();
                        state.seen   = seen.concat((data.items || []).map(function (i) { return i.id; })).slice(-100);
                        store(state);
                        skipped = false;

                        if (news.length && working()) {
                            news.slice(0, 3).forEach(show);
                            chime();
                            setTimeout(function () { speak(news); }, 450);
                        }
                    })
                    .catch(function () {})
                    .finally(function () { busy = false; });
            }

            // Voices load late in Chrome; asking once makes them ready.
            try { if ('speechSynthesis' in window) window.speechSynthesis.getVoices(); } catch (e) {}

            setInterval(ask, EVERY_MS);
            document.addEventListener('visibilitychange', function () {
                if (document.visibilityState === 'visible') { touched(); ask(); }
            });
            window.addEventListener('load', function () { setTimeout(ask, 1500); });
        })();
    </script>
@endonce
