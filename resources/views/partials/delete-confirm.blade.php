{{-- Every delete pop-up of the Super Admin's and the school admin's panels
     (body.lms-ui) in the logout pop-up's look (user's ask of 2026-11-22:
     "admin superadmin ke sare delete popups ko logout style ui m bnao").

     The logout card (livewire/components/nav-bar, .lo-*): a soft blurred
     shade, a white card with a peach stage whose white panel holds the
     question and its line(s), then a white "Cancel" pill and a rose action
     pill. Here the same card is drawn for:

       1. Each delete / remove confirmation pop-up (`.lms-cover` — the class
          the pages' delete confirmations already carry) that has a heading
          and a Cancel (or a backdrop that cancels). The page's own pop-up
          stays in the page, unseen and unclickable while the card shows;
          the card takes its words from it (heading, lines, error lines,
          loading labels) and every click on the card is passed to the pop-up's
          own buttons, boxes and ticks — so what a delete does, and when the
          pop-up closes, is exactly as before. A pop-up with a control the card
          does not draw (a select, a radio, a file box) is left as it was.
       2. `wire:confirm` questions about deleting / removing, which the
          browser asked in its own small box: Livewire's own confirm is swapped
          for the card at the click, and the action runs on "Yes, …".

     Nothing on the pages changes; the accounts panel (no lms-ui) keeps its
     pop-ups. To undo it: remove its @include in components.layouts.app. --}}
<style>
    body.lms-ui.lms-dc-on .lms-cover:not(.lo-shade) { opacity: 0 !important; pointer-events: none !important; }

    body.lms-ui .ldc-shade { position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 16px;
        background: rgba(12, 38, 32, .32); -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px); animation: ldc-fade .2s ease both; }
    body.lms-ui .ldc-card { width: 100%; max-width: 380px; max-height: calc(100dvh - 32px); overflow-y: auto; border-radius: 26px; background: #fff;
        font-family: 'Geist', 'Segoe UI', system-ui, sans-serif; text-align: left;
        box-shadow: 0 2px 4px rgba(12,38,32,.05), 0 44px 90px -30px rgba(12,38,32,.45); animation: ldc-pop .35s cubic-bezier(.2,.7,.2,1) both; }
    body.lms-ui .ldc-stage { margin: 10px 10px 0; padding: 26px 22px 0; border-radius: 20px; overflow: hidden; background: linear-gradient(160deg, #fde6cf 0%, #f7c9a0 100%); }
    body.lms-ui .ldc-panel { margin-bottom: -16px; padding: 26px 22px 40px; border-radius: 16px 16px 0 0; background: #fff; text-align: center; box-shadow: 0 12px 30px -16px rgba(12,38,32,.35); }
    body.lms-ui .ldc-panel h3 { margin: 0; font-family: 'Plus Jakarta Sans', 'Geist', system-ui, sans-serif; font-size: 20px; font-weight: 800; letter-spacing: -.03em; line-height: 1.25; color: #0c2620; text-wrap: balance; overflow-wrap: anywhere; }
    body.lms-ui .ldc-panel p { margin: 8px 0 0; font-size: 14px; line-height: 1.6; color: #6b7c76; text-wrap: balance; overflow-wrap: anywhere; }
    body.lms-ui .ldc-panel p.ldc-small { font-size: 12.5px; line-height: 1.5; color: #8a9893; }
    body.lms-ui .ldc-panel p.ldc-err { font-size: 13px; color: #e11d48; }
    body.lms-ui .ldc-check { display: flex; align-items: flex-start; gap: 10px; margin-top: 14px; text-align: left; font-size: 14px; line-height: 1.5; color: #0c2620; cursor: pointer; }
    body.lms-ui .ldc-check input { flex: none; width: 16px; height: 16px; margin: 3px 0 0; accent-color: #f0506e; cursor: pointer; }
    body.lms-ui .ldc-input { display: block; box-sizing: border-box; width: 100%; height: 46px; margin-top: 16px; padding: 0 18px; border-radius: 999px; border: 1px solid #d6dedb;
        background: #fff; font: inherit; font-size: 15px; color: #0c2620; text-align: center; letter-spacing: .06em; outline: none; transition: border-color .2s, box-shadow .2s; }
    body.lms-ui textarea.ldc-input { height: auto; min-height: 92px; padding: 12px 16px; border-radius: 18px; text-align: left; letter-spacing: 0; resize: vertical; }
    body.lms-ui .ldc-input::placeholder { color: #9aa8a3; letter-spacing: 0; }
    body.lms-ui .ldc-input:focus { border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13,148,136,.12); }
    body.lms-ui .ldc-link { display: inline-block; margin-top: 12px; padding: 0; border: 0; background: none; font: inherit; font-size: 13px; color: #6b7c76; text-decoration: underline; text-underline-offset: 3px; cursor: pointer; }
    body.lms-ui .ldc-link:hover { color: #0c2620; }
    body.lms-ui .ldc-link:disabled { opacity: .6; cursor: wait; }
    body.lms-ui .ldc-btns { display: flex; gap: 10px; padding: 20px 22px 22px; }
    body.lms-ui .ldc-btns.is-col { flex-direction: column; }
    body.lms-ui .ldc-btn { flex: 1; position: relative; height: 46px; padding: 0 16px; border-radius: 999px; border: 1px solid transparent; font: inherit; font-size: 15px; font-weight: 500; letter-spacing: -.01em;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer; transition: background .2s, border-color .2s, box-shadow .25s, transform .25s cubic-bezier(.2,.7,.2,1); }
    body.lms-ui .ldc-btns.is-col .ldc-btn { flex: none; width: 100%; }
    body.lms-ui .ldc-btn.ghost { background: #fff; color: #0c2620; border-color: #e5eae8; }
    body.lms-ui .ldc-btn.ghost:hover { border-color: #d6dedb; box-shadow: 0 1px 2px rgba(12,38,32,.06), 0 1px 3px rgba(12,38,32,.05); }
    body.lms-ui .ldc-btn.danger { background: #f0506e; color: #fff; }
    body.lms-ui .ldc-btn.danger:hover:not(:disabled) { background: #e11d48; box-shadow: 0 12px 26px -10px rgba(240,80,110,.7); transform: translateY(-1px); }
    body.lms-ui .ldc-btn:disabled { opacity: .7; cursor: wait; }
    body.lms-ui .ldc-btn:focus-visible, body.lms-ui .ldc-link:focus-visible { outline: 2px solid #99e2d6; outline-offset: 2px; }
    @keyframes ldc-fade { from { opacity: 0; } to { opacity: 1; } }
    @keyframes ldc-pop { from { opacity: 0; transform: translateY(14px) scale(.96); } to { opacity: 1; transform: none; } }
    @media (prefers-reduced-motion: reduce) { body.lms-ui .ldc-shade, body.lms-ui .ldc-card { animation: none; } }
</style>
<script>
    (function () {
        if (window.__lmsDeleteCard) return;
        window.__lmsDeleteCard = true;

        var WORD   = /\b(delete|deleting|remove|removing)\b/i;
        var CANCEL = /^(cancel|no|keep|close|not now|go back|back)\b/i;
        var TEXTY  = 'input:not([type]), input[type="text"], input[type="number"], input[type="email"], input[type="tel"], input[type="password"], input[type="search"], textarea';
        var st = null;   // the card on screen: { mode, cover, shade, card, panel, btns, p, psig, bsig }

        function on() { return !!document.body && document.body.classList.contains('lms-ui'); }
        function shown(el) { return !!el && el.isConnected && el.getClientRects().length > 0; }
        function txt(el) { return String(el.innerText || el.textContent || '').replace(/\s+/g, ' ').trim(); }
        function make(tag, cls) { var el = document.createElement(tag); if (cls) el.className = cls; return el; }
        function cls(el) { return typeof el.className === 'string' ? el.className : ''; }

        // ── What a page's pop-up holds, in its own order ──────────────────────
        function read(cover) {
            if (!shown(cover) || cover.classList.contains('lo-shade')) return null;
            var head = Array.prototype.find.call(cover.querySelectorAll('h1, h2, h3, h4'), function (h) {
                return shown(h) && !h.closest('button, a');
            });
            if (!head) return null;
            var odd = Array.prototype.some.call(cover.querySelectorAll('select, input[type="radio"], input[type="file"], input[type="date"], input[type="time"], input[type="datetime-local"], input[type="range"]'), shown);
            if (odd) return null;

            var items = [], acts = [], cancel = null;
            var all = cover.querySelectorAll('p, li, div, span, small, label, input, textarea, button, a[wire\\:click]');
            for (var i = 0; i < all.length; i++) {
                var el = all[i];
                if (el === head || head.contains(el) || el.contains(head) || !shown(el)) continue;
                var tag = el.tagName;
                if (tag === 'BUTTON' || tag === 'A') {
                    var t = txt(el);
                    if (!t) continue;                                   // an icon-only button
                    if (t.length <= 2 && !/[a-z0-9]/i.test(t)) { if (!cancel) cancel = el; continue; }   // the × in a corner
                    if (CANCEL.test(t)) { if (!cancel || !CANCEL.test(txt(cancel))) cancel = el; acts.push({ el: el, kind: 'ghost', t: t }); }
                    else if (/\bunderline\b/.test(cls(el)) || (/\btext-xs\b/.test(cls(el)) && !/\bbg-/.test(cls(el)))) items.push({ type: 'link', el: el, t: t });
                    else acts.push({ el: el, kind: 'danger', t: t });
                    continue;
                }
                if (el.closest('button, a[wire\\:click]')) continue;
                if (tag === 'INPUT' && el.type === 'checkbox') {
                    var lab = el.closest('label');
                    items.push({ type: 'check', el: el, t: lab ? txt(lab) : '' });
                    continue;
                }
                if (el.matches(TEXTY)) { items.push({ type: 'input', el: el, t: '' }); continue; }
                if (tag === 'INPUT') continue;
                var chk = el.closest('label');
                if (chk && chk.querySelector('input[type="checkbox"]')) continue;
                if (el.querySelector('div, p, ul, ol, li, table, button, input, textarea, select, label, h1, h2, h3, h4')) continue;
                var words = txt(el);
                if (!words) continue;
                var inside = false;
                for (var k = 0; k < items.length; k++) if (items[k].type === 'line' && items[k].el.contains(el)) { inside = true; break; }
                if (inside) continue;
                var c = cls(el);
                items.push({ type: 'line', el: el, t: words, tone: /\btext-red-/.test(c) ? 'err' : (/\btext-xs\b/.test(c) ? 'small' : '') });
            }

            var danger = acts.filter(function (a) { return a.kind === 'danger'; });
            if (!danger.length) return null;
            if (!WORD.test(txt(head)) && !danger.some(function (a) { return WORD.test(a.t); })) return null;
            var backdrop = cover.querySelector(':scope > [wire\\:click]:not(button)');
            if (!cancel && !backdrop) return null;

            // Cancel on the left, the action(s) on the right — as the logout card.
            var ghosts = acts.filter(function (a) { return a.kind === 'ghost'; });
            acts = acts.length > 2 ? danger.concat(ghosts) : ghosts.concat(danger);
            return { title: txt(head), items: items, acts: acts, cancel: cancel, backdrop: backdrop };
        }

        // ── The card ──────────────────────────────────────────────────────────
        function open(mode, cover) {
            var shade = make('div', 'ldc-shade');
            var card  = make('div', 'ldc-card');
            card.setAttribute('role', 'alertdialog');
            card.setAttribute('aria-modal', 'true');
            card.setAttribute('aria-labelledby', 'ldc-title');
            var stage = make('div', 'ldc-stage');
            var panel = make('div', 'ldc-panel');
            var btns  = make('div', 'ldc-btns');
            stage.appendChild(panel);
            card.appendChild(stage);
            card.appendChild(btns);
            shade.appendChild(card);
            shade.addEventListener('click', function (e) { if (e.target === shade) dismiss(); });
            document.body.appendChild(shade);
            if (mode === 'cover') document.body.classList.add('lms-dc-on');
            st = { mode: mode, cover: cover, shade: shade, card: card, panel: panel, btns: btns, p: null, psig: null, bsig: null, first: true };
        }

        function close() {
            if (!st) return;
            var s = st;
            st = null;
            if (s.cover && s.cover.isConnected) s.cover.removeAttribute('aria-hidden');
            if (s.shade.isConnected) s.shade.remove();
            document.body.classList.remove('lms-dc-on');
        }

        function dismiss() {
            if (!st) return;
            if (st.mode === 'ask') { var no = st.no; close(); if (no) no(); return; }
            var p = st.p;
            if (p && p.cancel && p.cancel.isConnected) p.cancel.click();
            else if (p && p.backdrop && p.backdrop.isConnected) p.backdrop.click();
        }

        function focusFirst() {
            if (!st) return;
            var f = st.panel.querySelector('input:not([type="checkbox"]), textarea') || st.btns.querySelector('.ldc-btn.ghost') || st.btns.querySelector('.ldc-btn');
            if (f) try { f.focus({ preventScroll: true }); } catch (e) { f.focus(); }
        }

        function drawPanel(p) {
            var panel = st.panel;
            var was = document.activeElement, at = -1, caret = null;
            if (was && panel.contains(was) && was.hasAttribute('data-i')) {
                at = +was.getAttribute('data-i');
                try { caret = [was.selectionStart, was.selectionEnd]; } catch (e) {}
            }
            panel.textContent = '';
            var h = make('h3');
            h.id = 'ldc-title';
            h.textContent = p.title;
            panel.appendChild(h);
            p.items.forEach(function (it, i) {
                if (it.type === 'line') {
                    var q = make('p', it.tone ? 'ldc-' + it.tone : '');
                    q.textContent = it.t;
                    panel.appendChild(q);
                } else if (it.type === 'check') {
                    var lab = make('label', 'ldc-check');
                    var box = make('input');
                    box.type = 'checkbox';
                    box.checked = !!it.el.checked;
                    box.setAttribute('data-i', i);
                    box.addEventListener('change', function () {
                        var o = st && st.p && st.p.items[i];
                        if (o && o.el.isConnected && o.el.checked !== box.checked) o.el.click();
                    });
                    var span = make('span');
                    span.textContent = it.t;
                    lab.appendChild(box);
                    lab.appendChild(span);
                    panel.appendChild(lab);
                } else if (it.type === 'input') {
                    var area = it.el.tagName === 'TEXTAREA';
                    var inp = make(area ? 'textarea' : 'input', 'ldc-input');
                    if (!area) inp.type = it.el.type === 'password' ? 'password' : 'text';
                    ['inputmode', 'maxlength', 'placeholder', 'autocomplete', 'rows'].forEach(function (a) {
                        if (it.el.hasAttribute(a)) inp.setAttribute(a, it.el.getAttribute(a));
                    });
                    inp.value = it.el.value || '';
                    inp.setAttribute('data-i', i);
                    inp.addEventListener('input', function () {
                        var o = st && st.p && st.p.items[i];
                        if (!o || !o.el.isConnected) return;
                        o.el.value = inp.value;
                        o.el.dispatchEvent(new Event('input', { bubbles: true }));
                    });
                    inp.addEventListener('change', function () {
                        var o = st && st.p && st.p.items[i];
                        if (o && o.el.isConnected) o.el.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                    if (!area) inp.addEventListener('keydown', function (e) {
                        if (e.key !== 'Enter') return;
                        e.preventDefault();
                        var o = st && st.p && st.p.items[i];
                        if (o && o.el.isConnected) o.el.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', code: 'Enter', keyCode: 13, which: 13, bubbles: true }));
                    });
                    panel.appendChild(inp);
                } else if (it.type === 'link') {
                    var a = make('button', 'ldc-link');
                    a.type = 'button';
                    a.textContent = it.t;
                    a.disabled = !!it.el.disabled;
                    a.addEventListener('click', function () {
                        var o = st && st.p && st.p.items[i];
                        if (o && o.el.isConnected && !o.el.disabled) o.el.click();
                    });
                    panel.appendChild(a);
                }
            });
            if (at > -1) {
                var back = panel.querySelector('[data-i="' + at + '"]');
                if (back) { back.focus({ preventScroll: true }); if (caret) try { back.setSelectionRange(caret[0], caret[1]); } catch (e) {} }
            }
        }

        function drawButtons(acts) {
            var row = st.btns;
            row.textContent = '';
            row.className = 'ldc-btns' + (acts.length > 2 ? ' is-col' : '');
            acts.forEach(function (a, i) {
                var b = make('button', 'ldc-btn ' + a.kind);
                b.type = 'button';
                b.textContent = a.t;
                b.disabled = !!a.disabled;
                if (a.busy) { b.setAttribute('data-sa-busy', ''); b.style.setProperty('--sa-spin', a.kind === 'danger' ? '#fff' : '#0c2620'); }
                b.addEventListener('click', function () {
                    if (!st) return;
                    if (st.mode === 'ask') { var yes = st.yes, no = st.no; close(); if (a.kind === 'danger') { if (yes) yes(); } else if (no) no(); return; }
                    var o = st.p && st.p.acts[i];
                    if (!o || !o.el.isConnected || o.el.disabled) return;
                    if (a.kind === 'danger') b.disabled = true;   // one press; the pop-up's own state takes over
                    o.el.click();
                });
                row.appendChild(b);
            });
        }

        // Keeps the card in step with the page's pop-up (its words, a loading
        // label, a step that follows) and closes it when the pop-up goes.
        function sync() {
            if (!on() || (st && st.mode === 'ask')) return;
            if (st && !st.shade.isConnected) { st = null; document.body.classList.remove('lms-dc-on'); }
            var covers = document.getElementsByClassName('lms-cover');
            var target = null, p = null;
            if (st && st.cover) { p = read(st.cover); if (p) target = st.cover; }
            for (var i = covers.length - 1; !target && i >= 0; i--) {
                var c = covers[i];
                if (c.closest('.ldc-shade')) continue;
                var r = read(c);
                if (r) { target = c; p = r; }
            }
            if (!target) { close(); return; }
            if (st && st.cover !== target) close();
            if (!st) open('cover', target);

            st.p = p;
            var psig = JSON.stringify([p.title, p.items.map(function (it) {
                return [it.type, it.t, it.tone || '', it.type === 'input' ? (it.el.getAttribute('placeholder') || '') : '', it.type === 'link' ? !!it.el.disabled : ''];
            })]);
            if (psig !== st.psig) { st.psig = psig; drawPanel(p); }
            else {
                p.items.forEach(function (it, i) {
                    var mine = st.panel.querySelector('[data-i="' + i + '"]');
                    if (!mine) return;
                    if (it.type === 'check') mine.checked = !!it.el.checked;
                    else if (it.type === 'input' && mine !== document.activeElement && mine.value !== (it.el.value || '')) mine.value = it.el.value || '';
                });
            }
            var acts = p.acts.map(function (a) {
                return { kind: a.kind, t: a.t, disabled: !!a.el.disabled, busy: a.el.hasAttribute('data-sa-busy') };
            });
            var bsig = JSON.stringify(acts);
            if (bsig !== st.bsig) { st.bsig = bsig; drawButtons(acts); }
            // The page's pop-up is there but unseen: kept from screen readers too.
            if (target.getAttribute('aria-hidden') !== 'true') target.setAttribute('aria-hidden', 'true');
            // First draw, or a redraw took the focus away (a step that follows): back into the card.
            if (st.first || !st.card.contains(document.activeElement)) { st.first = false; focusFirst(); }
        }

        // ── wire:confirm: the card instead of the browser's own box ───────────
        function ask(message, yes) {
            if (st) close();
            var msg = String(message).replace(/\\n/g, '\n').trim() || 'Are you sure?';
            var m = msg.match(/^([\s\S]+?[?!.])\s+([\s\S]+)$/);
            var title = m ? m[1] : msg, line = m ? m[2] : '';
            var w = (msg.match(WORD) || ['delete'])[0].toLowerCase();
            var verb = /^remov/.test(w) ? 'Remove' : 'Delete';
            open('ask', null);
            st.yes = yes;
            st.p = { title: title, items: line ? line.split(/\n+/).map(function (t) { return { type: 'line', t: t.trim(), el: null }; }).filter(function (it) { return it.t; }) : [], acts: [] };
            drawPanel(st.p);
            drawButtons([{ kind: 'ghost', t: 'Cancel' }, { kind: 'danger', t: 'Yes, ' + verb }]);
            focusFirst();
        }

        function confirmOf(el) {
            if (!el.getAttributeNames) return null;
            var names = el.getAttributeNames();
            for (var i = 0; i < names.length; i++) {
                if (names[i].indexOf('wire:confirm') === 0) return names[i].indexOf('.prompt') > -1 ? null : el.getAttribute(names[i]);
            }
            return null;
        }

        document.addEventListener('click', function (e) {
            if (!on()) return;
            for (var n = e.target; n && n.nodeType === 1; n = n.parentElement) {
                var msg = confirmOf(n);
                if (msg === null) continue;
                if (!WORD.test(msg) || typeof n.__livewire_confirm !== 'function') return;
                if (!n.__livewire_confirm.__ldc) {
                    var mine = function (action, instead) {
                        try { instead(); } catch (x) {}
                        ask(confirmOf(n) || msg, action);
                    };
                    mine.__ldc = true;
                    n.__livewire_confirm = mine;
                }
                return;
            }
        }, true);

        // ── Keys: Esc cancels, Tab stays in the card ─────────────────────────
        document.addEventListener('keydown', function (e) {
            if (!st) return;
            if (e.key === 'Escape') { e.preventDefault(); dismiss(); return; }
            if (e.key !== 'Tab') return;
            var f = Array.prototype.filter.call(st.card.querySelectorAll('button, input, textarea'), function (x) { return !x.disabled; });
            if (!f.length) return;
            var first = f[0], last = f[f.length - 1];
            if (!st.card.contains(document.activeElement)) { e.preventDefault(); first.focus(); }
            else if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        }, true);

        // ── Watch the page: a mutation is answered before the browser paints ─
        var queued = false;
        function soon(list) {
            if (!st && !document.getElementsByClassName('lms-cover').length) return;
            if (list && st && list.every(function (m) { return st.shade.contains(m.target); })) return;
            if (queued) return;
            queued = true;
            Promise.resolve().then(function () { queued = false; sync(); });
        }
        var start = function () {
            if (!on()) return;
            sync();
            // The whole document, not <body>: a wire:navigate visit swaps the body.
            if (window.MutationObserver) new MutationObserver(soon).observe(document.documentElement, {
                childList: true, subtree: true, characterData: true,
                attributes: true, attributeFilter: ['style', 'class', 'disabled', 'hidden', 'data-sa-busy'],
            });
        };
        document.addEventListener('livewire:navigated', function () { soon(); });
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
    })();
</script>
