{{--
    Empty screens, both panels (user's ask, 2026-11-26: "push notification jaisee
    hr khalee screen p null data user jaisa ui bnao … jha filter krne pr data aata
    h vha dikhao ki filter kro tb data aayega").

    Every list's own "nothing here" message — a table's lone colspan row, or a
    centred block in a card — takes the Users page's empty look
    (<x-empty-state>): a small teal illustration, the first line as a title,
    the rest as plain text under it and a link in it as a teal pill. Which
    picture follows what the message says:
      · "Use the filter… / Pick… / Select… / Choose…"  → a funnel ("filter first")
      · "…match… / for this filter / try another…"      → a magnifier (a filter found nothing)
      · anything else starting "No… / Nothing…"         → the cards

    No page's markup changes: a small script marks the message (data-lms-empty)
    and this CSS draws it. Livewire's morph drops the marks when it re-renders a
    message, so a MutationObserver puts them back before the frame is painted.

    Left alone: the Home-look pages (.sa-skin), the slide-ins and pop-ups
    (.fixed / [role=dialog]), the filter bands, anything already an
    <x-empty-state> (.es), anything under [data-no-empty-art], and small
    messages (less than 56px of padding — a hint in a card, not a screen).
--}}
@php
    $art = function (string $extra): string {
        $svg = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 168 120' fill='none'>"
            . "<circle cx='84' cy='62' r='52' fill='#effaf7'/>"
            . "<circle cx='84' cy='62' r='52' stroke='#d8f3ec' stroke-dasharray='3 6'/>"
            . "<circle cx='28' cy='94' r='3' fill='#b1e6db'/><circle cx='146' cy='30' r='2.5' fill='#b1e6db'/><circle cx='140' cy='98' r='2' fill='#d8f3ec'/>"
            . "<rect x='38' y='30' width='80' height='48' rx='11' fill='#fff' stroke='#d8f3ec' stroke-width='1.5' transform='rotate(-7 78 54)'/>"
            . "<rect x='50' y='44' width='84' height='50' rx='11' fill='#fff' stroke='#b1e6db' stroke-width='1.5'/>"
            . "<rect x='59' y='58' width='22' height='22' rx='6' fill='#d8f3ec'/>"
            . "<path d='M66 63.5h5l3.5 3.5v7.5a1 1 0 0 1-1 1h-7.5a1 1 0 0 1-1-1v-10a1 1 0 0 1 1-1z' fill='#fff' stroke='#0d9488' stroke-width='1.5' stroke-linejoin='round'/>"
            . "<rect x='88' y='61' width='34' height='6' rx='3' fill='#b1e6db'/>"
            . "<rect x='88' y='72' width='22' height='5' rx='2.5' fill='#d8f3ec'/>"
            . $extra
            . '</svg>';

        return 'url("data:image/svg+xml,' . rawurlencode($svg) . '")';
    };
    $artEmpty  = $art("<path d='M132 31l2.6 6.4 6.4 2.6-6.4 2.6-2.6 6.4-2.6-6.4-6.4-2.6 6.4-2.6z' fill='#0d9488'/>");
    $artSearch = $art("<circle cx='126' cy='86' r='13' fill='#fff' stroke='#0d9488' stroke-width='3'/><path d='M135.5 95.5l8 8' stroke='#0d9488' stroke-width='4' stroke-linecap='round'/><path d='M121 86h10' stroke='#b1e6db' stroke-width='2.5' stroke-linecap='round'/>");
    $artFilter = $art("<circle cx='132' cy='44' r='13' fill='#0d9488'/><path d='M125 38.5h14l-5.4 6.4v5.6l-3.2 2v-7.6z' fill='#fff' stroke='#fff' stroke-width='1' stroke-linejoin='round'/>");
@endphp
<style>
    body.lms-ui [data-lms-empty] { text-align: center !important; padding: 44px 20px 50px !important; color: #6b7280 !important;
        font-size: 13.5px !important; line-height: 1.55 !important; }
    body.lms-ui [data-lms-empty]::before { content: ""; display: block; width: 168px; height: 120px; margin: 0 auto 16px;
        background: var(--lms-empty-art) center / contain no-repeat; }
    body.lms-ui [data-lms-empty="empty"]  { --lms-empty-art: {!! $artEmpty !!}; }
    body.lms-ui [data-lms-empty="search"] { --lms-empty-art: {!! $artSearch !!}; }
    body.lms-ui [data-lms-empty="filter"] { --lms-empty-art: {!! $artFilter !!}; }
    /* The message's old icon goes: the picture says it now. */
    body.lms-ui [data-lms-empty-icon] { display: none !important; }
    /* Its first line is the title — the Users page's empty title. */
    body.lms-ui [data-lms-empty-title], body.lms-ui [data-lms-empty][data-lms-empty-raw] {
        font-family: 'Plus Jakarta Sans', 'Geist', ui-sans-serif, system-ui, sans-serif !important; font-size: 17px !important;
        font-weight: 700 !important; color: #0c2620 !important; letter-spacing: -.01em; line-height: 1.4 !important; }
    body.lms-ui [data-lms-empty-title] { margin: 0 auto 4px !important; }
    body.lms-ui [data-lms-empty] :is(p, div):not([data-lms-empty-title], [data-lms-empty-icon]) {
        font-size: 13.5px !important; color: #6b7280 !important; max-width: 440px; margin-left: auto; margin-right: auto; }
    body.lms-ui [data-lms-empty] :is(p, div):not([data-lms-empty-title]) strong { color: #44544f; font-weight: 600; }
    /* A link or plain-text button in it: the teal pill of the Users page. */
    body.lms-ui [data-lms-empty] :is(a, button):not([class*="bg-"]) { display: inline-flex; align-items: center; gap: 6px; height: 38px;
        margin-top: 16px; padding: 0 18px; border-radius: 9999px; background: #0d9488; color: #fff !important; font-family: 'Geist', ui-sans-serif, system-ui, sans-serif;
        font-size: 13.5px !important; font-weight: 600 !important; text-decoration: none !important; letter-spacing: 0; transition: background-color .15s; }
    body.lms-ui [data-lms-empty] :is(a, button):not([class*="bg-"]):hover { background: #0f766e; }
    /* The row holding it does not light up under the pointer. */
    body.lms-ui tr:has(> td[data-lms-empty]), body.lms-ui tr:has(> td[data-lms-empty]):hover { background: transparent !important; }
</style>
<script>
    (function () {
        if (window.__lmsEmptyStates) return;
        window.__lmsEmptyStates = true;

        var START  = /^(no |no-one|nothing|none |use the filter|pick |select |choose |there (are|is) no |you have(n't| not) )/i;
        var FILTER = /^(use the filter|pick |select |choose )|filter (above|first)|from the filter/i;
        var SEARCH = /match|for this (filter|search)|try (another|a different)|no results|for these filters|for the (selected|chosen)/i;
        var SKIP   = '.sa-skin, .es, [data-no-empty-art], .fixed, [role="dialog"], .lms-fbar, .sticky, nav, aside, header, select';
        var ATTRS  = ['data-lms-empty', 'data-lms-empty-title', 'data-lms-empty-icon', 'data-lms-empty-raw'];

        function text(el) { return (el.textContent || '').replace(/\s+/g, ' ').trim(); }
        function set(el, name, value) { if (el.getAttribute(name) !== value) el.setAttribute(name, value); }
        function unset(el, name) { if (el.hasAttribute(name)) el.removeAttribute(name); }

        function qualifies(el) {
            if (el.closest(SKIP) || el.querySelector('.es, input, select, textarea, table, img:not([src^="data:"])')) return null;
            if (el.tagName === 'TD') {
                var row = el.parentElement, body = row && row.parentElement;
                if (!body || body.tagName !== 'TBODY' || body.children.length !== 1 || row.children.length !== 1) return null;
            } else if (!el.classList.contains('text-center')) {
                return null;
            }
            var t = text(el);
            if (!t || t.length > 260 || !START.test(t)) return null;
            if (!el.hasAttribute('data-lms-empty')) {
                var cs = getComputedStyle(el);
                if (parseFloat(cs.paddingTop) + parseFloat(cs.paddingBottom) < 56) return null;
            }
            return FILTER.test(t) ? 'filter' : (SEARCH.test(t) ? 'search' : 'empty');
        }

        // The old icon (leading children with no words), the title (the first
        // line with words), or — words straight in the box — the box itself.
        function markParts(el) {
            var title = null, raw = false, seenText = false;
            for (var n = el.firstChild; n; n = n.nextSibling) {
                if (n.nodeType === 3) {
                    if (!seenText && n.textContent.trim()) { raw = true; seenText = true; }
                    continue;
                }
                if (n.nodeType !== 1) continue;
                var words = text(n);
                if (!seenText && !words && n.querySelector && (n.tagName === 'svg' || n.querySelector('svg'))) {
                    set(n, 'data-lms-empty-icon', '');
                    continue;
                }
                unset(n, 'data-lms-empty-icon');
                if (!seenText && words && !/^(A|BUTTON)$/.test(n.tagName)) {
                    title = n;
                    if (/^(DIV|SECTION)$/.test(n.tagName)) {
                        var first = n.querySelector('p, h2, h3, h4');
                        if (first && text(first)) title = first;
                    }
                }
                if (words) seenText = true;
            }
            el.querySelectorAll('[data-lms-empty-title]').forEach(function (t) { if (t !== title) unset(t, 'data-lms-empty-title'); });
            if (title) set(title, 'data-lms-empty-title', '');
            raw ? set(el, 'data-lms-empty-raw', '') : unset(el, 'data-lms-empty-raw');
        }

        function clear(el) {
            ATTRS.forEach(function (a) { unset(el, a); });
            el.querySelectorAll('[data-lms-empty-title], [data-lms-empty-icon]').forEach(function (c) {
                unset(c, 'data-lms-empty-title'); unset(c, 'data-lms-empty-icon');
            });
        }

        function scan() {
            if (!document.body || !document.body.classList.contains('lms-ui')) return;
            var root = document.getElementById('main-scroll') || document.body;
            var kept = [];
            root.querySelectorAll('td[colspan], .text-center').forEach(function (el) {
                var outer = el.parentElement && el.parentElement.closest('[data-lms-empty]');
                if (outer && kept.indexOf(outer) !== -1) return;   // inside one already marked
                var kind = qualifies(el);
                if (!kind) return;
                set(el, 'data-lms-empty', kind);
                markParts(el);
                kept.push(el);
            });
            root.querySelectorAll('[data-lms-empty]').forEach(function (el) {
                if (kept.indexOf(el) === -1) clear(el);
            });
        }

        var pending = false;
        function schedule() {
            if (pending) return;
            pending = true;
            queueMicrotask(function () { pending = false; try { scan(); } catch (e) {} });
        }

        function start() {
            schedule();
            new MutationObserver(schedule).observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ATTRS });
        }
        document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', start) : start();
        document.addEventListener('livewire:navigated', schedule);
    })();
</script>
