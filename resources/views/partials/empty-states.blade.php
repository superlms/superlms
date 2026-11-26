{{--
    Empty screens, both panels — exactly the Users page's (user's asks,
    2026-11-26: "admin aur superadmin ki saree null screens is type ki ui m
    dikhao exact … jo bhi screen m data na ho use aise dikhao").

    Every list's own "nothing here" message — a table's lone colspan row, or a
    centred block — becomes the Users page's <x-empty-state>: the same picture
    (App\Support\EmptyStateArt), its first line as the title, the rest as the
    grey line under it, and a teal pill button:
      · "No… / Nothing…"                       → the cards with a +; the button is
                                                 the page's own Add/New/Upload… from
                                                 its header ("+ Add Notification")
      · "Use the filter… / Pick… / Select…"    → the cards with a funnel; "Open filter"
      · "…match… / for this filter / try…"     → the cards under a magnifier; "Clear filters"
    A message with one line gets the grey line too ("Nothing here yet — use
    “Add Doc” to add the first one."). A table showing only the message loses its
    column heads, as the Users page's does.

    No page's markup changes: a small script marks the message (data-lms-empty),
    adds the grey line and the button in one box at its end, and this CSS draws
    it. Livewire's morph drops all that when it re-renders the message, so a
    MutationObserver puts it back before the frame is painted.

    Left alone: pages marked [data-no-empty-art] (Home, Analytics, Reports, Quick
    Links, More, Messages), the slide-ins and pop-ups, the filter bands, anything
    already an <x-empty-state> (.es), and small messages — narrower than 60% of
    the page with less than 56px of padding: a hint in a card, not a screen.
--}}
<style>
    body.lms-ui [data-lms-empty] { text-align: center !important; padding: 56px 20px 60px !important; color: #6b7280 !important;
        font-size: 13.5px !important; line-height: 1.55 !important; }
    body.lms-ui [data-lms-empty]:not(td) { display: block; }
    body.lms-ui [data-lms-empty]::before { content: ""; display: block; width: 168px; height: 120px; margin: 0 auto 18px;
        background: var(--lms-empty-art) center / contain no-repeat; }
    body.lms-ui [data-lms-empty="empty"]  { --lms-empty-art: {!! \App\Support\EmptyStateArt::cssUrl('add') !!}; }
    body.lms-ui [data-lms-empty="search"] { --lms-empty-art: {!! \App\Support\EmptyStateArt::cssUrl('search') !!}; }
    body.lms-ui [data-lms-empty="filter"] { --lms-empty-art: {!! \App\Support\EmptyStateArt::cssUrl('filter') !!}; }
    /* The message's old icon goes: the picture says it now. */
    body.lms-ui [data-lms-empty-icon] { display: none !important; }
    /* The title — .es-title. */
    body.lms-ui [data-lms-empty-title], body.lms-ui [data-lms-empty][data-lms-empty-raw] {
        font-family: 'Plus Jakarta Sans', 'Geist', ui-sans-serif, system-ui, sans-serif !important; font-size: 17px !important;
        font-weight: 700 !important; color: #0c2620 !important; letter-spacing: -.01em; line-height: 1.4 !important; }
    body.lms-ui [data-lms-empty-title] { margin: 0 auto !important; }
    /* The grey line(s) — .es-text. */
    body.lms-ui [data-lms-empty] :is(p, div):not([data-lms-empty-title], [data-lms-empty-icon], [data-lms-empty-extra], .lms-empty-acts),
    body.lms-ui [data-lms-empty] .lms-empty-hint {
        font-family: 'Geist', ui-sans-serif, system-ui, sans-serif; font-size: 13.5px !important; font-weight: 400 !important; line-height: 1.55 !important;
        color: #6b7280 !important; max-width: 360px; margin: 6px auto 0 !important; letter-spacing: 0; }
    body.lms-ui [data-lms-empty] :is(p, div):not([data-lms-empty-title]) strong { color: #44544f; font-weight: 600; }
    body.lms-ui [data-lms-empty] [data-lms-empty-extra] { display: block; margin: 0; padding: 0; }
    body.lms-ui [data-lms-empty] .lms-empty-acts { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; margin: 20px 0 0; max-width: none; }
    /* The button — .es-btn; a link or plain-text button of the page's own the same. */
    body.lms-ui [data-lms-empty] .lms-empty-btn,
    body.lms-ui [data-lms-empty] :is(a, button):not([class*="bg-"], .lms-empty-btn) {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 40px; padding: 0 20px; margin-top: 20px;
        border-radius: 9999px; border: 1px solid #0d9488; background: #0d9488; color: #fff !important;
        font-family: 'Geist', ui-sans-serif, system-ui, sans-serif; font-size: 14px !important; font-weight: 600 !important; line-height: 1;
        text-decoration: none !important; letter-spacing: 0; cursor: pointer; transition: background-color .15s, border-color .15s, color .15s; }
    body.lms-ui [data-lms-empty] .lms-empty-acts .lms-empty-btn { margin-top: 0; }
    body.lms-ui [data-lms-empty] .lms-empty-btn:hover,
    body.lms-ui [data-lms-empty] :is(a, button):not([class*="bg-"], .lms-empty-btn):hover { background: #0f766e; border-color: #0f766e; }
    body.lms-ui [data-lms-empty] .lms-empty-btn:focus-visible { outline: 2px solid #b1e6db; outline-offset: 2px; }
    body.lms-ui [data-lms-empty] .lms-empty-btn svg { width: 16px; height: 16px; flex-shrink: 0; }
    body.lms-ui [data-lms-empty] .lms-empty-btn.is-light { background: #fff; color: #0f766e !important; border-color: #d6dedb; }
    body.lms-ui [data-lms-empty] .lms-empty-btn.is-light:hover { background: #effaf7; border-color: #b1e6db; color: #0c2620 !important; }
    /* The row holding it does not light up; a table showing only it has no column heads. */
    body.lms-ui tr:has(> td[data-lms-empty]), body.lms-ui tr:has(> td[data-lms-empty]):hover { background: transparent !important; }
    body.lms-ui table:has(> tbody > tr:only-child > td[data-lms-empty]) > thead { display: none; }
    body.lms-ui table:has(> tbody > tr:only-child > td[data-lms-empty]) { min-width: 0 !important; }
</style>
<script>
    (function () {
        if (window.__lmsEmptyStates) return;
        window.__lmsEmptyStates = true;

        var START  = /^(no |no-one|nothing|none |use the filter|pick |select |choose |there (are|is) no |you have(n't| not) |not any )/i;
        var FILTER = /^(use the filter|pick |select |choose )|filter (above|first)|from the filter/i;
        var SEARCH = /match|for this (filter|search)|try (another|a different)|no results|for these filters|for the (selected|chosen)/i;
        var ACTION = /^(\+\s*)?(add|new|create|upload|record|generate|import|invite|book|schedule|write|post|compose)\b/i;
        // (#main-scroll — the page's own scroller — is itself .fixed: only a .fixed INSIDE it is a pop-up.)
        var SKIP   = '[data-no-empty-art], .es, .fixed:not(#main-scroll), [role="dialog"], .lms-fbar, .sticky, nav, aside, header, select, .absolute[class*="z-"]';
        var ATTRS  = ['data-lms-empty', 'data-lms-empty-title', 'data-lms-empty-icon', 'data-lms-empty-raw', 'style', 'hidden'];
        var PLUS   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>';
        var FUNNEL = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16l-6.2 7.4V18l-3.6 2v-7.6z"/></svg>';
        var CROSS  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>';

        function text(el) { return (el.textContent || '').replace(/\s+/g, ' ').trim(); }
        function label(el) { return (el.innerText || el.textContent || '').replace(/\s+/g, ' ').trim(); }
        function shown(el) { return !!el && el.offsetParent !== null && el.getClientRects().length > 0; }
        function set(el, name, value) { if (el.getAttribute(name) !== value) el.setAttribute(name, value); }
        function unset(el, name) { if (el.hasAttribute(name)) el.removeAttribute(name); }
        function main() { return document.getElementById('main-scroll') || document.body; }

        // ── What the page offers ─────────────────────────────────────────────
        // The header's own Add / New / Upload … (its primary one first).
        function headerAction() {
            var h1 = Array.prototype.find.call(main().querySelectorAll('h1'), shown);
            var head = h1 && (h1.closest('.sk-head, .bg-white.border-b, .sticky') || h1.parentElement && h1.parentElement.parentElement);
            if (!head) return null;
            var found = null;
            head.querySelectorAll('button, a[href]').forEach(function (b) {
                if (found && /text-white/.test(found.className)) return;
                if (!shown(b) || b.closest('.lms-fbar') || b.hasAttribute('data-lms-fh-btn') || b.hasAttribute('data-lms-fh-x')) return;
                var t = label(b);
                if (t && t.length <= 40 && ACTION.test(t)) found = b;
            });
            return found ? { el: found, label: label(found).replace(/^\+\s*/, '') } : null;
        }
        function filterOpener() {
            var b = document.querySelector('[data-lms-fh-btn]');
            return shown(b) ? b : null;
        }
        function filterClearer() {
            var band = Array.prototype.find.call(document.querySelectorAll('.lms-fbar'), shown);
            var clear = band && Array.prototype.find.call(band.querySelectorAll('button'), function (b) { return shown(b) && /^\s*(×\s*)?clear/i.test(label(b)); });
            if (clear) return clear;
            var x = document.querySelector('[data-lms-fh-x]');
            return shown(x) ? x : null;
        }

        // ── Is this a list's empty message? ──────────────────────────────────
        function qualifies(el) {
            if (el.closest(SKIP) || el.querySelector('.es, input, select, textarea, table, img:not([src^="data:"])')) return null;
            if (el.tagName === 'TD') {
                var row = el.parentElement, body = row && row.parentElement;
                if (!body || body.tagName !== 'TBODY' || body.children.length !== 1 || row.children.length !== 1) return null;
            } else if (!el.classList.contains('text-center')) {
                return null;
            }
            var extra = el.querySelector(':scope > [data-lms-empty-extra]');
            var t = text(el);
            if (extra) t = t.slice(0, Math.max(0, t.length - text(extra).length)).trim();
            if (!t || t.length > 260 || !START.test(t)) return null;
            if (!el.hasAttribute('data-lms-empty')) {
                var w = el.getBoundingClientRect().width;
                if (!w) return null;
                var cs = getComputedStyle(el);
                var pad = parseFloat(cs.paddingTop) + parseFloat(cs.paddingBottom);
                if (w < main().clientWidth * 0.6 && !(pad >= 56 && w >= 280)) return null;
            }
            if (FILTER.test(t)) return 'filter';
            return SEARCH.test(t) ? 'search' : 'empty';
        }

        // The old icon (leading children with no words), the title (the first
        // line with words) — or, words straight in the box, the box itself.
        // Returns how many lines of words it has and whether it has a button.
        function markParts(el) {
            var title = null, raw = false, seenText = false, lines = 0, own = false;
            for (var n = el.firstChild; n; n = n.nextSibling) {
                if (n.nodeType === 3) {
                    if (n.textContent.trim()) { if (!seenText) raw = true; seenText = true; lines = Math.max(lines, 1); }
                    continue;
                }
                if (n.nodeType !== 1 || n.hasAttribute('data-lms-empty-extra')) continue;
                var words = text(n);
                if (!seenText && !words && (n.tagName.toLowerCase() === 'svg' || n.querySelector('svg'))) {
                    set(n, 'data-lms-empty-icon', '');
                    continue;
                }
                unset(n, 'data-lms-empty-icon');
                if (/^(A|BUTTON)$/.test(n.tagName) || n.querySelector('a, button')) own = true;
                if (!words) continue;
                if (/^(A|BUTTON)$/.test(n.tagName)) continue;
                if (!seenText) {
                    title = n;
                    if (/^(DIV|SECTION)$/.test(n.tagName)) {
                        var ps = n.querySelectorAll('p, h2, h3, h4');
                        if (ps.length && text(ps[0])) { title = ps[0]; lines += ps.length; } else { lines += 1; }
                    } else { lines += 1; }
                } else {
                    lines += Math.max(1, n.querySelectorAll('p').length);
                }
                seenText = true;
            }
            el.querySelectorAll('[data-lms-empty-title]').forEach(function (t) { if (t !== title) unset(t, 'data-lms-empty-title'); });
            if (title) set(title, 'data-lms-empty-title', '');
            raw ? set(el, 'data-lms-empty-raw', '') : unset(el, 'data-lms-empty-raw');
            // "No routes yet. Create them first." already says what to do: no grey line of ours.
            var first = title ? text(title) : (raw ? text(el).replace(text(el.querySelector(':scope > [data-lms-empty-extra]') || document.createElement('i')), '') : '');
            if (/[.!?—–]\s+\S/.test(first.trim().replace(/[.!?]+$/, ''))) lines = Math.max(lines, 2);
            return { lines: lines, own: own };
        }

        // The grey line (when the message has one line only) and the button,
        // in one box at the message's end — rebuilt only when they change.
        function extras(el, kind, parts) {
            var hint = '', btn = null;
            if (kind === 'filter') {
                var opener = filterOpener();
                if (parts.lines < 2) hint = 'Use the filter above — the data shows up here.';
                if (opener && !parts.own) btn = { act: 'filter', text: 'Open filter', icon: FUNNEL, light: false };
            } else if (kind === 'search') {
                if (parts.lines < 2) hint = 'Nothing matches — try another search or filter, or clear them.';
                if (filterClearer() && !parts.own) btn = { act: 'clear', text: 'Clear filters', icon: CROSS, light: true };
            } else {
                var action = parts.own ? null : headerAction();
                if (parts.lines < 2) hint = action
                    ? 'Nothing here yet — use “' + action.label + '” to add the first one.'
                    : 'Nothing here yet — it shows up here as soon as there is some.';
                if (action) btn = { act: 'add', text: action.label, icon: PLUS, light: false };
            }

            var sig = [hint, btn ? btn.act + ':' + btn.text : ''].join('|');
            var box = el.querySelector(':scope > [data-lms-empty-extra]');
            if (box && box.getAttribute('data-sig') === sig) return;
            if (box) box.remove();
            if (!hint && !btn) return;

            box = document.createElement('div');
            box.setAttribute('data-lms-empty-extra', '');
            box.setAttribute('data-sig', sig);
            box.setAttribute('wire:ignore', '');
            if (hint) {
                var p = document.createElement('p');
                p.className = 'lms-empty-hint';
                p.textContent = hint;
                box.appendChild(p);
            }
            if (btn) {
                var acts = document.createElement('div');
                acts.className = 'lms-empty-acts';
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'lms-empty-btn' + (btn.light ? ' is-light' : '');
                b.setAttribute('data-act', btn.act);
                b.innerHTML = btn.icon;
                b.appendChild(document.createTextNode(btn.text));
                acts.appendChild(b);
                box.appendChild(acts);
            }
            el.appendChild(box);
        }

        // A click goes to what the page itself has (found again: a re-render may have replaced it).
        document.addEventListener('click', function (e) {
            var b = e.target.closest && e.target.closest('.lms-empty-btn[data-act]');
            if (!b) return;
            e.preventDefault();
            var act = b.getAttribute('data-act');
            var target = act === 'filter' ? filterOpener() : act === 'clear' ? filterClearer() : (headerAction() || {}).el;
            if (target) target.click();
        });

        function clear(el) {
            ['data-lms-empty', 'data-lms-empty-raw'].forEach(function (a) { unset(el, a); });
            el.querySelectorAll('[data-lms-empty-title], [data-lms-empty-icon]').forEach(function (c) {
                unset(c, 'data-lms-empty-title'); unset(c, 'data-lms-empty-icon');
            });
            var box = el.querySelector(':scope > [data-lms-empty-extra]');
            if (box) box.remove();
        }

        function scan() {
            if (!document.body || !document.body.classList.contains('lms-ui')) return;
            var root = main();
            var kept = [];
            root.querySelectorAll('td[colspan], .text-center').forEach(function (el) {
                if (el.closest('[data-lms-empty-extra]')) return;
                var outer = el.parentElement && el.parentElement.closest('[data-lms-empty]');
                if (outer && kept.indexOf(outer) !== -1) return;   // inside one already marked
                var kind = qualifies(el);
                if (!kind) return;
                set(el, 'data-lms-empty', kind);
                extras(el, kind, markParts(el));
                kept.push(el);
            });
            root.querySelectorAll('[data-lms-empty]').forEach(function (el) {
                if (kept.indexOf(el) === -1) clear(el);
            });
        }

        // A re-render (nodes, our own marks) is looked at again at once, before
        // the frame is painted; a tab shown or hidden (style / class / hidden)
        // on the next frame, at most every 120 ms — some pages change styles often.
        var pending = false, later = null, lastScan = 0;
        function run() { pending = false; lastScan = Date.now(); try { scan(); } catch (e) {} }
        function schedule() {
            if (pending) return;
            pending = true;
            queueMicrotask(run);
        }
        function scheduleSoon() {
            if (pending || later) return;
            var wait = Math.max(0, 120 - (Date.now() - lastScan));
            later = setTimeout(function () { later = null; requestAnimationFrame(run); }, wait);
        }

        function start() {
            schedule();
            new MutationObserver(function (list) {
                var soon = false;
                for (var i = 0; i < list.length; i++) {
                    var m = list[i], t = m.target;
                    if (t.closest && t.closest('[data-lms-empty-extra]')) continue;   // our own box
                    if (m.type === 'childList' || /^data-lms-empty/.test(m.attributeName || '')) { schedule(); return; }
                    soon = true;
                }
                if (soon) scheduleSoon();
            }).observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ATTRS.concat(['class']) });
        }
        document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', start) : start();
        document.addEventListener('livewire:navigated', schedule);
        window.addEventListener('resize', schedule);
    })();
</script>
