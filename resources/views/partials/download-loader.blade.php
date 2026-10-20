{{-- One download loader for the whole panel.

     While a file is being made and sent — an export, a PDF, a certificate, a
     document — a dark card at the foot of the window says so, with a spinner;
     it turns to a tick (or a cross) when the file comes, then goes. It never
     blocks the page, and × hides it.

     It notices a download by itself, wherever it starts:
     - a Livewire action whose name starts with download… or export… (every
       one that sends a file: exportStudents, downloadDocument, downloadCycle…)
       — the card stays until the action's answer is back;
     - a click on a download link of this site: one with `download` or
       `data-download`, or whose address ends in /download, /export, /pdf,
       -pdf or /statement. The link is followed with `_dl=<token>` added, and
       the answer hands the token back in a cookie (DownloadReady middleware)
       — the only way a page can know a file it did not fetch itself has come.
       `data-no-download-loader` leaves a link alone;
     - a script's own download: window.lmsDownload.track(promise, name), or
       lmsDownload.open(url, '_blank') for an address opened by a script. --}}
<div id="lms-dl" role="status" aria-live="polite" hidden
     style="position:fixed;left:50%;bottom:24px;transform:translate(-50%,12px);z-index:10050;opacity:0;transition:opacity .2s,transform .2s;
            width:max-content;min-width:260px;max-width:calc(100vw - 32px);background:#111827;color:#fff;border-radius:14px;
            box-shadow:0 12px 32px rgba(0,0,0,.28);font:500 13px/1.4 system-ui,-apple-system,'Segoe UI',sans-serif;overflow:hidden">
    <div style="display:flex;align-items:center;gap:12px;padding:12px 12px 12px 16px">
        <span data-dl-icon style="flex:none;width:22px;height:22px;display:flex;align-items:center;justify-content:center"></span>
        <span style="flex:1;min-width:0">
            <span data-dl-title style="display:block;font-weight:600"></span>
            <span data-dl-sub style="display:block;font-size:12px;color:#9ca3af;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:60vw"></span>
        </span>
        <button type="button" data-dl-close aria-label="Hide"
                style="flex:none;width:26px;height:26px;border:0;border-radius:8px;background:transparent;color:#9ca3af;cursor:pointer;font-size:18px;line-height:1">&times;</button>
    </div>
    <span data-dl-bar style="display:block;height:3px;background:rgba(255,255,255,.12);position:relative;overflow:hidden">
        <span style="position:absolute;top:0;bottom:0;width:40%;background:#8b5cf6;border-radius:3px;animation:lms-dl-bar 1.1s ease-in-out infinite"></span>
    </span>
</div>
<style>
    @keyframes lms-dl-spin { to { transform: rotate(360deg); } }
    @keyframes lms-dl-bar { 0% { left: -40%; } 100% { left: 100%; } }
    #lms-dl [data-dl-close]:hover { background: rgba(255,255,255,.1); color: #fff; }
</style>
<script>
    (function () {
        if (window.lmsDownload) return;

        var PARAM = '_dl', COOKIE = 'lms_dl';
        var LINK_WAIT = 5 * 60 * 1000;   // a link's file that never says it came: give up after this
        var box = document.getElementById('lms-dl');
        if (!box) return;

        var icon = box.querySelector('[data-dl-icon]');
        var title = box.querySelector('[data-dl-title]');
        var sub = box.querySelector('[data-dl-sub]');
        var bar = box.querySelector('[data-dl-bar]');

        var ICONS = {
            busy: '<span style="width:18px;height:18px;border-radius:50%;border:2.5px solid rgba(255,255,255,.25);border-top-color:#fff;animation:lms-dl-spin .8s linear infinite"></span>',
            ok: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>',
            fail: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f87171" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 18L18 6M6 6l12 12"/></svg>'
        };

        var jobs = {};        // id => { name, token, timer }
        var seq = 0;
        var hideTimer = null;
        var dismissed = false;

        function count() { return Object.keys(jobs).length; }

        function paint(state, head, line) {
            clearTimeout(hideTimer);
            icon.innerHTML = ICONS[state];
            title.textContent = head;
            sub.textContent = line || '';
            sub.style.display = line ? 'block' : 'none';
            bar.style.visibility = state === 'busy' ? 'visible' : 'hidden';
            if (dismissed && state === 'busy') return;
            box.hidden = false;
            void box.offsetWidth;   // laid out first, so it fades in
            box.style.opacity = '1';
            box.style.transform = 'translate(-50%,0)';
        }

        function hide() {
            clearTimeout(hideTimer);
            box.style.opacity = '0';
            box.style.transform = 'translate(-50%,12px)';
            hideTimer = setTimeout(function () { box.hidden = true; }, 220);
        }

        function busy() {
            var n = count();
            if (!n) return;
            var names = Object.keys(jobs).map(function (k) { return jobs[k].name; }).filter(Boolean);
            paint('busy',
                n > 1 ? 'Preparing ' + n + ' downloads…' : 'Preparing your download…',
                n > 1 ? names.join(', ') : (names[0] || 'This can take a few seconds'));
        }

        function end(id, state, name, message) {
            var job = jobs[id];
            if (!job) return;
            clearTimeout(job.timer);
            delete jobs[id];
            if (count()) { busy(); return; }
            if (dismissed) { dismissed = false; hide(); return; }
            if (state === 'quiet') { hide(); return; }
            if (state === 'ok') {
                paint('ok', 'Downloaded', name || job.name);
                hideTimer = setTimeout(hide, 1800);
            } else {
                paint('fail', 'Download failed', message || 'Please try again.');
                hideTimer = setTimeout(hide, 4000);
            }
        }

        function start(name, wait) {
            var id = ++seq;
            dismissed = false;   // a new download shows again, though × hid the last one
            jobs[id] = { name: name || '', token: null, timer: null };
            if (wait) jobs[id].timer = setTimeout(function () { end(id, 'quiet'); }, wait);
            busy();
            return id;
        }

        box.querySelector('[data-dl-close]').addEventListener('click', function () {
            dismissed = count() > 0;
            hide();
        });

        // ── Links: the answer's cookie says the file has come ─────────────
        var poll = null;
        function token() {
            var s = '';
            while (s.length < 16) s += Math.random().toString(36).slice(2);
            return s.slice(0, 16);
        }
        function readCookie() {
            var m = document.cookie.match(new RegExp('(?:^|;\\s*)' + COOKIE + '=([^;]*)'));
            return m ? decodeURIComponent(m[1]) : '';
        }
        function watch(id, tok) {
            jobs[id].token = tok;
            if (poll) return;
            poll = setInterval(function () {
                var got = readCookie();
                var left = 0;
                Object.keys(jobs).forEach(function (k) {
                    if (!jobs[k].token) return;
                    if (jobs[k].token === got) {
                        document.cookie = COOKIE + '=; Max-Age=0; path=/';
                        end(+k, 'ok');
                    } else {
                        left++;
                    }
                });
                if (!left) { clearInterval(poll); poll = null; }
            }, 300);
        }
        function withToken(href, tok) {
            var u = new URL(href, location.href);
            u.searchParams.set(PARAM, tok);
            return u.toString();
        }
        /** What the link fetches, for the card's second line: "Certificate" of "Download Certificate". */
        function nameOf(a) {
            var d = a.getAttribute('download');
            if (d) return d;
            var t = (a.getAttribute('title') || a.textContent || '').replace(/\s+/g, ' ').trim()
                .replace(/^(download|export)\b\s*/i, '');
            return /^(pdf|file|excel)?$/i.test(t) ? '' : t.slice(0, 60);
        }
        function isDownloadLink(a) {
            if (a.hasAttribute('data-no-download-loader')) return false;
            var raw = a.getAttribute('href') || '';
            if (!raw || raw.charAt(0) === '#' || /^(javascript|mailto|tel|blob|data):/i.test(raw)) return false;
            var u;
            try { u = new URL(a.href, location.href); } catch (e) { return false; }
            if (u.origin !== location.origin) return false;
            if (a.hasAttribute('download') || a.hasAttribute('data-download')) return true;
            return /\/(download|export|statement)\/?$|[\/-]pdf\/?$/i.test(u.pathname);
        }

        // After the page's own click handlers: a link they took over is left alone.
        document.addEventListener('click', function (e) {
            if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            var a = e.target && e.target.closest ? e.target.closest('a[href]') : null;
            if (!a || !isDownloadLink(a)) return;

            // A second click while its file is still coming does nothing.
            if (a.__lmsDl && jobs[a.__lmsDl]) { e.preventDefault(); return; }

            var tok = token();
            var id = start(nameOf(a), LINK_WAIT);
            a.__lmsDl = id;
            watch(id, tok);

            // The browser follows the link the way it always does (its target,
            // its download name) — only with the token; the address goes back
            // straight after, so the page's own markup is untouched.
            var original = a.getAttribute('href');
            a.setAttribute('href', withToken(a.href, tok));
            setTimeout(function () { a.setAttribute('href', original); }, 0);
        });

        // ── Livewire: download… / export… actions ─────────────────────────
        var hooked = false;
        function hook() {
            if (hooked || !window.Livewire || typeof window.Livewire.hook !== 'function') return;
            hooked = true;
            window.Livewire.hook('commit', function (o) {
                var calls = (o.commit && o.commit.calls) || [];
                var hit = calls.some(function (c) { return /^(download|export)/i.test(c.method || ''); });
                if (!hit) return;

                var id = start('', LINK_WAIT);
                o.succeed(function (r) {
                    var fx = (r && r.effects) || {};
                    if (fx.download) end(id, 'ok', fx.download.name);
                    else if (fx.redirect) end(id, 'ok');      // a stored file, sent from where it is kept
                    else end(id, 'quiet');                   // nothing to send (the page says why)
                });
                o.fail(function () { end(id, 'fail'); });
            });
        }
        if (window.Livewire) hook();
        document.addEventListener('livewire:init', hook);

        window.lmsDownload = {
            start: start,
            done: function (id, name) { end(id, 'ok', name); },
            fail: function (id, message) { end(id, 'fail', null, message); },
            stop: function (id) { end(id, 'quiet'); },
            /** Show the loader while the promise runs; it settles as the promise does. */
            track: function (promise, name) {
                var id = start(name || '', LINK_WAIT);
                return Promise.resolve(promise).then(function (v) {
                    end(id, 'ok', name);
                    return v;
                }, function (err) { end(id, 'fail'); throw err; });
            },
            /** Open a download address from a script (a new tab, or here) with the loader. */
            open: function (url, target) {
                var tok = token();
                var id = start('', LINK_WAIT);
                var sameSite = false;
                try { sameSite = new URL(url, location.href).origin === location.origin; } catch (e) {}
                if (sameSite) { watch(id, tok); url = withToken(url, tok); }
                else { setTimeout(function () { end(id, 'quiet'); }, 1500); }
                if (target === '_blank') window.open(url, '_blank'); else window.location.href = url;
            }
        };
    })();
</script>
