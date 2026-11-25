{{-- The Super Admin's panel and, since 2026-11-20, the school admin's too
     (user's ask: "the admin's headers, font, forms and buttons as the super
     admin's"), on every page of them (body.lms-ui), in the superlms.in
     website's look (2026-11-19). Laid over partials.panel-ui, which both
     panels share. (The file keeps its name: it began as the Super Admin's.)

     1. Headers: every functionality's header row is exactly as tall as the
        sidebar's logo band (60px), its line running on from the sidebar's
        divider at the same height. A filter or tab row under it keeps its
        place below that line. (Quick Links, Home and Analytics have the
        same 60px row of their own, .sk-head.)
     2. Buttons: every button a full pill. The page's main buttons (the
        panel's blue, violet, indigo, green and near-black ones) take the
        website header's "Request Demo" button: teal, white words, a soft
        teal glow and a little lift under the pointer. Red ones stay red,
        light ones stay light with a teal edge under the pointer.
     3. Forms: the fields take the Super Admin login's look — a light grey
        edge, teal while being typed in, and what the person typed or picked
        teal, edge and words (the name on the border too); red while wrong.
        Every empty field reads the same light grey (hints, "Select …", empty
        dates) and a value the form came with reads ink. The words are in the
        login's face (Geist, 15px).
     4. Filters behind a button (2026-11-21, user's ask): the grey "Filter by"
        band under a page's header is hidden; the header has a round filter
        button instead, and clicking it shows the band under the header —
        the same band, look and size as before, the header and its figures
        staying as they are (they follow the filters as before) — with a
        small round × at its right end that hides it and brings the filter
        button back. A dot on the filter button says a filter is on (the band
        has its Clear button). Since 2026-11-22 (user's ask) the filter button
        stands before the header's buttons (Export, Add …), and the × also
        takes the filters off (it presses the band's own Clear).
        One script for every page of both panels (no page's own markup
        changes); it re-marks the header after every Livewire update.
     Included in the page's <head> (components.layouts.app), so a page never
     shows the old buttons or fields while it loads. --}}
@php
    $in   = 'html body.lms-ui #main-scroll';
    // As partials.panel-ui: a box to fill (not a tick, a file, …) that is not
    // a small one of a filter bar or toolbar, right after its name.
    $box  = ':is(input:not([type="checkbox"], [type="radio"], [type="file"], [type="hidden"], [type="range"], [type="color"], [type="submit"], [type="button"]), select, textarea)'
          . ':not(.text-xs, [class~="py-1"], [class~="py-0.5"], [class~="h-7"], [class~="h-8"])';
    $name = 'label:not(.sr-only):not(:is(.flex, .inline-flex, .grid):not(.flex-col) > *):not(.sticky *)';
    // Any box to type in or pick from.
    $any  = ':is(input:not([type="checkbox"], [type="radio"], [type="file"], [type="hidden"], [type="range"], [type="color"], [type="submit"], [type="button"]), select, textarea)';

    // The panel's main-button colours → the website's teal.
    $mainBg = collect(['blue', 'indigo', 'violet', 'purple', 'emerald', 'green', 'teal', 'sky', 'cyan'])
        ->flatMap(fn ($c) => ["[class~=\"bg-$c-500\"]", "[class~=\"bg-$c-600\"]", "[class~=\"bg-$c-700\"]"])
        ->merge(['[class~="bg-gray-700"]', '[class~="bg-gray-800"]', '[class~="bg-gray-900"]', '[class~="bg-slate-800"]',
                 '[class~="bg-slate-900"]', '[class~="bg-black"]', '[class~="bg-gradient-to-r"]', '[class~="bg-gradient-to-br"]'])
        ->implode(', ');
    $redBg  = '[class~="bg-red-500"], [class~="bg-red-600"], [class~="bg-red-700"], [class~="bg-rose-500"], [class~="bg-rose-600"], [class~="bg-rose-700"]';
    // A button-sized thing: a padded word button, an icon button or a fixed small height.
    $sized  = collect(['py-0.5', 'py-1', 'py-1.5', 'py-2', 'py-2.5', 'py-3', 'py-[7px]', 'py-[9px]', 'p-1', 'p-1.5', 'p-2', 'p-2.5',
                       'h-6', 'h-7', 'h-8', 'h-9', 'h-10', 'h-11'])->map(fn ($c) => "[class~=\"$c\"]")->implode(', ');
    $btn    = 'body.lms-ui #main-scroll :is(button, a, [role="button"], input[type="submit"], input[type="button"])'
            . ':not([role="switch"], .sa-btn, .sk-cta, .sk-btn, .sk-tile, .an-chip, .sa-card, [class*="border-dashed"])';
@endphp
<style>
    body.lms-ui {
        --sa-t: #0d9488; --sa-t7: #0f766e; --sa-t50: #effaf7; --sa-t100: #d8f3ec; --sa-t200: #b1e6db;
        --sa-ink: #0c2620; --sa-line2: #d6dedb; --sa-hline: #eef1f0;
    }

    /* ─── 1. Header rows: 60px, the line level with the sidebar's divider ─── */
    @media (min-width: 768px) {
        /* The padded row (…py-3) of a page's white header that holds its
           title (the <h1>; the innermost such row): no padding of its own
           above or below, at least 60px with a 1px line at its foot — as the
           sidebar's .sa-brand — and the title row in it 59px, its parts
           centred in that height. */
        body.lms-ui #main-scroll :is(.bg-white.border-b .py-3, .bg-white.border-b.py-3):has(h1):not(:has(.py-3 h1)) {
            padding-top: 0 !important; padding-bottom: 0 !important; min-height: 60px;
            border-bottom: 1px solid var(--sa-hline) !important;
        }
        body.lms-ui #main-scroll :is(.bg-white.border-b .py-3, .bg-white.border-b.py-3):has(h1):not(:has(.py-3 h1)):not(.flex) > :first-child:has(h1) { min-height: 59px; }
        /* (only a row already laid out across at this width is centred) */
        body.lms-ui #main-scroll :is(.bg-white.border-b .py-3, .bg-white.border-b.py-3):has(h1):not(:has(.py-3 h1)):not(.flex) > :first-child:has(h1):is(.flex:not(.flex-col), [class~="sm:flex-row"], [class~="md:flex-row"]),
        body.lms-ui #main-scroll :is(.bg-white.border-b .py-3, .bg-white.border-b.py-3):has(h1):not(:has(.py-3 h1)):is(.flex:not(.flex-col), [class~="sm:flex-row"], [class~="md:flex-row"]) { align-items: center; }
        /* The row of filters or tabs under it starts on that line (no second one). */
        body.lms-ui #main-scroll .bg-white.border-b .py-3:has(h1):not(:has(.py-3 h1)) + .border-t { border-top-width: 0 !important; }
        /* The sticky white header around them: its own bottom edge in the same soft line. */
        body.lms-ui #main-scroll .bg-white.border-b:has(h1) { border-bottom-color: var(--sa-hline); }
        /* A header whose title row is all it holds: that row's line is its edge. */
        body.lms-ui #main-scroll .bg-white.border-b:has(> .py-3:last-child h1) { border-bottom-width: 0 !important; }
        body.lms-ui #main-scroll .bg-white.border-b.py-3:has(h1) { border-bottom-width: 1px !important; }
    }
    @media (min-width: 1024px) {
        /* Home and Analytics: the same 60px row (Quick Links has it already). */
        body.lms-ui .sa-home .sk-head { height: 60px; padding-top: 0; padding-bottom: 0; flex-wrap: nowrap; border-bottom-color: var(--sa-hline); }
    }

    /* ─── 2. Buttons: full pills; the main ones the website's teal ─── */
    {!! $btn !!}:is(.rounded, .rounded-sm, .rounded-md, .rounded-lg, .rounded-xl, .rounded-2xl):is({!! $sized !!}) { border-radius: 9999px !important; }
    {!! $btn !!}[class~="text-white"]:is({!! $mainBg !!}) {
        border-radius: 9999px !important;
        background-color: var(--sa-t) !important; background-image: none !important;
        border-color: transparent !important; color: #fff !important;
        --sa-spin: #fff;
        transition: background-color .2s, box-shadow .25s, transform .25s cubic-bezier(.2,.7,.2,1), opacity .2s;
    }
    {!! $btn !!}[class~="text-white"]:is({!! $mainBg !!}):not(:disabled):hover {
        background-color: var(--sa-t7) !important;
        box-shadow: 0 12px 26px -10px rgba(13,148,136,.7) !important;
        transform: translateY(-1px);
    }
    {!! $btn !!}[class~="text-white"]:is({!! $mainBg !!}):focus-visible { outline: 2px solid var(--sa-t200); outline-offset: 2px; }
    {!! $btn !!}[class~="text-white"]:is({!! $redBg !!}) { border-radius: 9999px !important; --sa-spin: #fff; }
    {!! $btn !!}[class~="text-white"]:is({!! $redBg !!}):not(:disabled):hover { box-shadow: 0 12px 26px -12px rgba(220,38,38,.6) !important; transform: translateY(-1px); }
    /* Light buttons with an edge (Cancel, Back, View Live …): a teal edge under the pointer. */
    {!! $btn !!}[class~="border"]:not([class~="text-white"]):is({!! $sized !!}):not(:disabled):hover {
        border-color: var(--sa-t200) !important; color: var(--sa-t7) !important;
    }
    /* A tab's underline and an outline button in the panel's blue / violet: teal. */
    body.lms-ui #main-scroll :is(button, a):is([class~="border-blue-500"], [class~="border-blue-600"], [class~="border-indigo-600"], [class~="border-violet-600"], [class~="border-purple-600"]):not([class~="text-white"]) {
        border-color: var(--sa-t) !important; color: var(--sa-t7) !important;
    }
    /* Every box to type in or pick from with rounded corners — a toolbar's
       dropdown, a filter's search too — a pill like the form's fields. */
    body.lms-ui #main-scroll :is(input:not([type="checkbox"], [type="radio"], [type="file"], [type="hidden"], [type="range"], [type="color"]), select):is(.rounded, .rounded-md, .rounded-lg, .rounded-xl) { border-radius: 9999px !important; }
    /* The panel's blue / violet text buttons and ticks in teal. */
    body.lms-ui #main-scroll :is(input[type="checkbox"], input[type="radio"]) { accent-color: var(--sa-t); }
    body.lms-ui #main-scroll :is(input[type="checkbox"], input[type="radio"]):is([class*="text-blue-"], [class*="text-indigo-"], [class*="text-violet-"]) { color: var(--sa-t); }

    /* ─── 3. Fields: the login's look ─── */
    /* Every box: the login's face, and teal (not blue) while in use. */
    body.lms-ui #main-scroll {!! $any !!} { font-family: 'Geist', 'Segoe UI', system-ui, -apple-system, sans-serif; }
    body.lms-ui #main-scroll {!! $any !!}:focus { --tw-ring-color: rgba(13,148,136,.28) !important; border-color: var(--sa-t) !important; }
    /* A field with its name on the border (partials.panel-ui), every one in
       the same three colours (user's ask, 2026-11-19 — some read dark, some
       light, some teal before anything was typed):
         · nothing in it yet — a typed box's hint, a dropdown still on its
           "Select …", an empty date, a locked box: all the hint's light grey;
         · a value it came with (an edit form, a default such as "No"): ink;
         · what the person typed or picked here: teal, edge and words, as the
           login. */
    {!! $in !!} {!! $name !!} + {!! $box !!} {
        border-color: var(--sa-line2) !important; color: #111827; -webkit-text-fill-color: #111827;
        font-size: .9375rem; font-weight: 400; letter-spacing: normal;
    }
    {!! $in !!} {!! $name !!} + {!! $box !!}::placeholder { color: #9ca3af; -webkit-text-fill-color: #9ca3af; opacity: 1; }
    {!! $in !!} {!! $name !!} + {!! $box !!}:is(select):has(option:checked:is([value=""], :disabled)),
    {!! $in !!} {!! $name !!} + {!! $box !!}.sa-empty { color: #9ca3af; -webkit-text-fill-color: #9ca3af; }
    {!! $in !!} {!! $name !!} + {!! $box !!}.sa-empty::-webkit-datetime-edit { color: #9ca3af; }
    {!! $in !!} {!! $name !!} + select option { color: #111827; }
    /* Teal while typing … */
    {!! $in !!} {!! $name !!} + {!! $box !!}:focus { border-color: var(--sa-t) !important; }
    {!! $in !!} {!! $name !!}:has(+ {!! $box !!}:focus) { color: var(--sa-t7) !important; }
    /* … and what was typed or picked here, the edge and the words teal. */
    {!! $in !!} {!! $name !!} + {!! $box !!}.sa-filled { border-color: var(--sa-t) !important; color: var(--sa-t); -webkit-text-fill-color: var(--sa-t); }
    {!! $in !!} {!! $name !!}:has(+ {!! $box !!}.sa-filled) { color: var(--sa-t7) !important; }
    {!! $in !!} {!! $name !!} + {!! $box !!}:-webkit-autofill { border-color: var(--sa-t) !important; -webkit-text-fill-color: var(--sa-t); -webkit-box-shadow: 0 0 0 1000px #fff inset !important; }
    /* Red while wrong (as before). */
    {!! $in !!} {!! $name !!} + {!! $box !!}[class*="border-red"] { border-color: #dc2626 !important; color: #dc2626; -webkit-text-fill-color: #dc2626; }
    {!! $in !!} {!! $name !!}:has(+ {!! $box !!}[class*="border-red"]) { color: #dc2626 !important; }
    {!! $in !!} {!! $name !!} + {!! $box !!}:disabled { border-color: #e5e7eb !important; color: #9ca3af; -webkit-text-fill-color: #9ca3af; }
    /* Other boxes (search, filters): what is typed there in teal too. */
    body.lms-ui #main-scroll {!! $any !!}.sa-filled:not(:disabled) { color: var(--sa-t7); }

    /* A list's skeleton while its page loads: its buttons already pills. */
    body.lms-sa .lms-skeleton .sa-skel:is(.h-7, .h-8, .h-9, .h-10) { border-radius: 9999px !important; }

    /* ─── 4. Filters in the header ─── */
    /* Before the script has looked at the page: a header's grey "Filter by"
       band (it has the funnel icon) is already out of sight, so it never
       shows for a moment while the page loads. */
    body.lms-ui #main-scroll :is(.sticky, .bg-white.border-b):has(h1):not(:has(table)):not(.lms-fopen) > :is(.border-t, .border-b).bg-gray-50:has(path[d^="M3 4a1 1 0 011-1h16"]) { display: none !important; }
    /* Closed: the band hidden, the filter button in the header. Open (user's
       ask, 2026-11-21 later): the header stays as it is — its figures follow
       the filters as before — and the band shows under it in its own look and
       size, with a small round × at its right end; the filter button is out
       of the header until the × closes the band again. */
    body.lms-ui #main-scroll .lms-fh:not(.lms-fopen) .lms-fbar { display: none !important; }
    body.lms-ui #main-scroll .lms-fh.lms-fopen [data-lms-fh-btn] { display: none !important; }
    body.lms-ui #main-scroll .lms-fh.lms-fopen .lms-fbar {
        position: relative; padding-right: 52px !important;
        animation: lms-fh-in .2s cubic-bezier(.2,.7,.2,1);
    }
    /* Thinner (user's ask, 2026-11-25): 6px over and under its fields, so the
       band is 44px tall, not 56px; the fields and the × keep their size. */
    body.lms-ui #main-scroll .lms-fh.lms-fopen .lms-fbar { padding-top: 6px !important; padding-bottom: 6px !important; }
    @keyframes lms-fh-in { from { opacity: 0; transform: translateY(-3px); } to { opacity: 1; transform: none; } }
    /* Where the filter button sits: in the header's row of buttons ("in"), at
       the end of the title's row ("row"), or at the header's right edge ("abs"). */
    body.lms-ui #main-scroll .lms-fh-row { justify-content: flex-start !important; }
    body.lms-ui #main-scroll .lms-fh-row > [data-lms-fh-btn] { margin-left: 8px; }
    body.lms-ui #main-scroll .lms-fh-row > :nth-last-child(2) { margin-left: auto; }
    /* Before the header's buttons (Export, Add …; user's ask, 2026-11-22):
       the button stays last in the page and is drawn before them. */
    body.lms-ui #main-scroll .lms-fh-before > [data-lms-fh-btn] { order: 1; }
    body.lms-ui #main-scroll .lms-fh-before > .lms-fh-act { order: 2; }
    body.lms-ui #main-scroll .lms-fh-rowb { justify-content: flex-start !important; }
    body.lms-ui #main-scroll .lms-fh-rowb > [data-lms-fh-btn] { margin-left: auto; }
    body.lms-ui #main-scroll .lms-fh-abs { position: relative; padding-right: 64px !important; }
    body.lms-ui #main-scroll .lms-fh-abs > [data-lms-fh-btn] { position: absolute; right: 16px; top: 50%; transform: translateY(-50%); }
    /* The round filter button and the round ×: Quick Links' header circles. */
    body.lms-ui #main-scroll :is([data-lms-fh-btn], [data-lms-fh-x]) {
        position: relative; width: 38px; height: 38px; flex: none; padding: 0; border-radius: 50%;
        border: 1px solid #e3ece9; background: #f3f8f6; color: #1f3a33; display: inline-grid; place-items: center; cursor: pointer;
        box-shadow: 0 1px 2px rgba(12,38,32,.05); transition: background .2s, border-color .2s, color .2s, box-shadow .25s, transform .2s;
    }
    body.lms-ui #main-scroll :is([data-lms-fh-btn], [data-lms-fh-x]) svg { width: 18px; height: 18px; }
    /* The band's ×: small, at its right end, so the band keeps its height. */
    body.lms-ui #main-scroll .lms-fbar > [data-lms-fh-x] {
        position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
        width: 28px; height: 28px; background: #fff; border-color: #e5e7eb;
    }
    body.lms-ui #main-scroll .lms-fbar > [data-lms-fh-x] svg { width: 14px; height: 14px; }
    body.lms-ui #main-scroll [data-lms-fh-btn]:hover { background: var(--sa-t); border-color: var(--sa-t); color: #fff; transform: translateY(-1px); box-shadow: 0 10px 20px -10px rgba(13,148,136,.7); }
    body.lms-ui #main-scroll [data-lms-fh-x]:hover { background: #fff1f2; border-color: #fda4af; color: #e11d48; }
    body.lms-ui #main-scroll :is([data-lms-fh-btn], [data-lms-fh-x]):focus-visible { outline: 2px solid var(--sa-t200); outline-offset: 2px; }
    body.lms-ui #main-scroll [data-lms-fh-btn].is-on { color: var(--sa-t7); border-color: var(--sa-t200); background: var(--sa-t50); }
    body.lms-ui #main-scroll [data-lms-fh-btn].is-on::after { content: ''; position: absolute; top: 0; right: 0; width: 10px; height: 10px; border-radius: 50%; background: var(--sa-t); border: 2px solid #fff; }
    @media (prefers-reduced-motion: reduce) { body.lms-ui #main-scroll .lms-fh.lms-fopen .lms-fbar { animation: none; } }
</style>
<script>
    // Marks every box: .sa-empty while nothing is in it, and .sa-filled once
    // the person has typed or picked something there themselves (a value the
    // form came with stays ink) — kept when Livewire redraws a part of the
    // page, dropped when the page puts another value in the box.
    // (In the <head>: it waits for the page before it looks at anything.)
    (function () {
        if (window.__saTheme) return;
        window.__saTheme = true;
        var SEL = '#main-scroll :is(input:not([type="checkbox"], [type="radio"], [type="file"], [type="hidden"], [type="range"], [type="color"], [type="submit"], [type="button"]), select, textarea)';
        var typed = new WeakMap();   // box → the value the person last gave it
        function mark(el) {
            var v = el.value == null ? '' : String(el.value);
            var empty = v.trim() === '';
            el.classList.toggle('sa-empty', empty);
            el.classList.toggle('sa-filled', !empty && typed.has(el) && typed.get(el) === v);
        }
        function scan() { document.querySelectorAll(SEL).forEach(mark); }
        var queued = false;
        function soon() { if (queued) return; queued = true; requestAnimationFrame(function () { queued = false; scan(); }); }
        ['input', 'change'].forEach(function (t) {
            document.addEventListener(t, function (e) {
                var el = e.target;
                if (!el || !el.matches || !el.matches(SEL)) return;
                if (e.isTrusted) typed.set(el, el.value == null ? '' : String(el.value));
                mark(el);
            }, true);
        });
        document.addEventListener('livewire:init', function () {
            Livewire.hook('morph.updated', function (p) { if (p.el && p.el.matches && p.el.matches(SEL)) mark(p.el); });
            Livewire.hook('commit', function (p) { p.succeed(function () { setTimeout(scan, 0); }); });
        });
        document.addEventListener('livewire:navigated', soon);
        var start = function () {
            scan();
            var main = document.getElementById('main-scroll');
            if (main && window.MutationObserver) new MutationObserver(soon).observe(main, { childList: true, subtree: true });
        };
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
    })();

    // 4. Filters in the header. Each page's grey "Filter by" band under its
    // header is found (the band with the funnel icon / "Filter by" / data-
    // filter-bar, in the white or sticky header that holds the page's <h1>),
    // hidden, and a round filter button added to the header's row; clicking it
    // shows the band under the header (the button hides) with a small round ×
    // at its end, which hides it again. The page's own markup is untouched: the
    // marks and buttons are put back after every Livewire update ("morphed"
    // and a MutationObserver, both before the browser paints), and which
    // headers are open is kept here, not on the page.
    (function () {
        if (window.__lmsFilterHead) return;
        window.__lmsFilterHead = true;
        var FUNNEL = 'M3 4a1 1 0 011-1h16';
        var MARKS  = ['lms-fh', 'lms-fh-flex', 'lms-fopen', 'lms-ftitle', 'lms-fbar', 'lms-fh-row', 'lms-fh-rowb', 'lms-fh-abs', 'lms-fh-before', 'lms-fh-act'];
        var CTL    = 'button, a, input, select, textarea, label, [role="button"]';
        var ICON_F = '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h18l-7 8.25V19l-4 1.5v-7.75L3 4.5z"/></svg>';
        var ICON_X = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>';
        var open = new WeakSet();   // headers whose filters are showing

        function main() { return document.getElementById('main-scroll'); }
        function visible(el) { return getComputedStyle(el).display !== 'none'; }
        function injected(el) { return el.hasAttribute('data-lms-fh-btn') || el.hasAttribute('data-lms-fh-x'); }
        // In an overlay, a skeleton or a card's own filter row — below #main-scroll
        // (which is itself .fixed), so the page's frame never counts.
        var AWAY = '.lms-skeleton, [data-page-skeleton], .lms-cover, .fixed, [role="dialog"], .sk-filter';
        function away(el) {
            var root = main(), c = el.closest(AWAY);
            return !!c && c !== root && !!root && root.contains(c);
        }

        function isBar(el) {
            if (away(el)) return false;
            if (el.hasAttribute('data-filter-bar')) return true;
            if (!el.classList.contains('bg-gray-50') || !(el.classList.contains('border-t') || el.classList.contains('border-b'))) return false;
            return el.textContent.indexOf('Filter by') !== -1 || !!el.querySelector('path[d^="' + FUNNEL + '"]');
        }

        // The page's <h1> in a box, outside the band.
        function titleIn(box, bar) {
            var all = box.querySelectorAll('h1');
            for (var i = 0; i < all.length; i++) {
                if (!bar.contains(all[i]) && !away(all[i])) return all[i];
            }
            return null;
        }
        function childHolding(box, el) {
            while (el && el.parentElement !== box) el = el.parentElement;
            return el;
        }

        // A band and its header: { head, title (row), bar, flex (the band is the header's own child) }.
        function headerOf(bar) {
            var root = main(), box = bar.parentElement, h1 = null;
            while (box && box !== root) {
                if ((h1 = titleIn(box, bar))) break;
                box = box.parentElement;
            }
            if (!h1 || !box || box === root) return null;
            if (!box.matches('.sticky, .bg-white.border-b') || box.querySelector('table')) return null;
            var title = childHolding(box, h1), top = childHolding(box, bar);
            if (!title || !top || title === top) return null;
            if (!(title.compareDocumentPosition(top) & Node.DOCUMENT_POSITION_FOLLOWING)) return null;
            return { head: box, title: title, bar: bar, h1: h1, flex: top === bar };
        }

        // Where the filter button goes: the header's row of buttons, else the
        // end of the title's row, else the header's right edge.
        function spot(title, h1) {
            var node = h1;
            while (node && node !== title) {
                var row = node.parentElement, kids = [];
                for (var i = 0; i < row.children.length; i++) {
                    var k = row.children[i];
                    if (!injected(k) && visible(k)) kids.push(k);
                }
                var last = kids[kids.length - 1];
                if (last && last !== node && !last.contains(h1)) {
                    // A button or link is never a box to sit in (Attendance's
                    // "Mark Attendance" is a flex button on its own): the
                    // filter button goes beside it in the row instead.
                    var control = last.matches(CTL);
                    if (!control && getComputedStyle(last).display.indexOf('flex') !== -1) return { el: last, mode: 'in' };
                    if (getComputedStyle(row).display.indexOf('flex') !== -1) return { el: row, mode: 'row' };
                    break;
                }
                node = row;
            }
            return { el: title, mode: 'abs' };
        }

        // The header's buttons in the filter button's box (Export, Add …): the
        // run of buttons at its end — a figure ("Total: 877", with its <strong>)
        // or the title is not one. The filter button shows right before them
        // (user's ask, 2026-11-22). It stays the box's last child in the page
        // and is moved with CSS order only, so a Livewire redraw of the box
        // never mistakes it for one of the page's own buttons.
        function actionsIn(box) {
            var acts = [];
            for (var i = box.children.length - 1; i >= 0; i--) {
                var k = box.children[i];
                if (injected(k)) continue;
                if (k.matches('h1') || k.querySelector('h1')) break;
                var isCtl = k.matches(CTL) || !!k.querySelector(CTL);
                if (!isCtl && !visible(k)) continue;
                if (!isCtl || k.querySelector('strong')) break;
                acts.unshift(k);
            }
            return acts;
        }
        function arrange(btn) {
            var box = btn.parentElement, mode = btn.dataset.lmsFhMode;
            if (mode === 'abs') { box.classList.add('lms-fh-abs'); return; }
            var acts = actionsIn(box);
            Array.prototype.forEach.call(box.children, function (k) { k.classList.toggle('lms-fh-act', acts.indexOf(k) !== -1); });
            box.classList.toggle('lms-fh-before', acts.length > 0);
            if (mode === 'row') {
                box.classList.toggle('lms-fh-rowb', acts.length > 0);
                box.classList.toggle('lms-fh-row', acts.length === 0);
            }
        }

        // The band's own way back to no filters: its "Clear" button (by its
        // words, or its title for an icon one). A band without one: its
        // search box emptied.
        function clearBands(head) {
            head.querySelectorAll('.lms-fbar').forEach(function (bar) {
                var clear = Array.prototype.find.call(bar.querySelectorAll('button, a'), function (c) {
                    if (injected(c)) return false;
                    return /^\s*clear/i.test(c.textContent || '') || /^\s*clear/i.test(c.getAttribute('title') || c.getAttribute('aria-label') || '');
                });
                if (clear) { clear.click(); return; }
                bar.querySelectorAll('input[type="search"], input[type="text"], input:not([type])').forEach(function (inp) {
                    if (inp.value === '') return;
                    inp.value = '';
                    inp.dispatchEvent(new Event('input', { bubbles: true }));
                    inp.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });
        }

        function make(kind) {
            var b = document.createElement('button');
            b.type = 'button';
            b.setAttribute(kind === 'x' ? 'data-lms-fh-x' : 'data-lms-fh-btn', '');
            b.setAttribute('aria-label', kind === 'x' ? 'Close filters' : 'Show filters');
            b.title = kind === 'x' ? 'Close filters' : 'Filters';
            b.innerHTML = kind === 'x' ? ICON_X : ICON_F;
            return b;
        }

        var busy = false;
        function scan() {
            var root = main();
            if (!root || busy) return;
            busy = true;
            try {
                var found = [], seen = new Set();
                root.querySelectorAll('[data-filter-bar], .bg-gray-50.border-t, .bg-gray-50.border-b').forEach(function (el) {
                    if (!seen.has(el) && isBar(el)) { seen.add(el); found.push(el); }
                });
                // The innermost bands only.
                found = found.filter(function (el) { return !found.some(function (o) { return o !== el && el.contains(o); }); });

                var heads = new Map();   // head → its setup
                found.forEach(function (bar) {
                    var h = headerOf(bar);
                    if (!h) return;
                    var got = heads.get(h.head);
                    if (got) { got.bars.push(bar); got.flex = got.flex && h.flex; return; }
                    heads.set(h.head, { head: h.head, title: h.title, h1: h.h1, bars: [bar], flex: h.flex });
                });

                // Marks and buttons from before that no longer belong.
                var list = Array.from(heads.values());
                root.querySelectorAll('.' + MARKS.join(', .')).forEach(function (el) {
                    var keep = list.some(function (h) { return h.head === el || h.title === el || h.bars.indexOf(el) !== -1 || (h.head.contains(el) && el.contains(h.h1)); });
                    if (!keep) MARKS.forEach(function (m) { el.classList.remove(m); });
                });
                root.querySelectorAll('[data-lms-fh-btn], [data-lms-fh-x]').forEach(function (b) {
                    var ok = list.some(function (h) { return b.hasAttribute('data-lms-fh-btn') ? h.title.contains(b) : b.parentElement === h.bars[0]; });
                    if (!ok) b.remove();
                });

                heads.forEach(function (h) {
                    var isOpen = open.has(h.head);
                    h.head.classList.add('lms-fh');
                    h.head.classList.toggle('lms-fh-flex', h.flex);
                    h.head.classList.toggle('lms-fopen', isOpen);
                    h.title.classList.add('lms-ftitle');
                    h.bars.forEach(function (b) { b.classList.add('lms-fbar'); });

                    // The filter button, once, in its spot.
                    var btn = h.title.querySelector('[data-lms-fh-btn]');
                    if (!btn) {
                        var s = spot(h.title, h.h1);
                        btn = make('filter');
                        s.el.appendChild(btn);
                        btn.dataset.lmsFhMode = s.mode;
                    }
                    // (Its spot's marks, again after a redraw took them off:
                    // before the header's buttons.)
                    arrange(btn);
                    h.title.querySelectorAll('[data-lms-fh-btn]').forEach(function (b, i) { if (i > 0) b.remove(); });
                    // A filter is on when the band shows its Clear button.
                    var on = h.bars.some(function (bar) {
                        return Array.prototype.some.call(bar.querySelectorAll('button, a'), function (c) { return !injected(c) && /^\s*clear/i.test(c.textContent || ''); });
                    });
                    btn.classList.toggle('is-on', on);
                    btn.title = on ? 'Filters (a filter is on)' : 'Filters';
                    btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

                    // The × at the end of the (first) band.
                    var first = h.bars[0];
                    if (!first.querySelector(':scope > [data-lms-fh-x]')) first.appendChild(make('x'));
                });
            } finally {
                busy = false;
            }
        }

        function setOpen(head, on) {
            if (on) open.add(head); else open.delete(head);
            head.classList.toggle('lms-fopen', on);
            var btn = head.querySelector('[data-lms-fh-btn]');
            if (btn) btn.setAttribute('aria-expanded', on ? 'true' : 'false');
            // Focus goes to the band's × (the button it came from is hidden now),
            // not into a field: the band opens looking exactly as it did.
            if (on) {
                var x = head.querySelector('.lms-fbar > [data-lms-fh-x]');
                if (x) try { x.focus({ preventScroll: true, focusVisible: false }); } catch (e) {}
            } else if (btn) {
                try { btn.focus({ preventScroll: true }); } catch (e) {}
            }
        }

        document.addEventListener('click', function (e) {
            var b = e.target && e.target.closest ? e.target.closest('[data-lms-fh-btn], [data-lms-fh-x]') : null;
            if (!b) return;
            var head = b.closest('.lms-fh');
            if (!head) return;
            e.preventDefault();
            e.stopPropagation();
            // The × takes the filters off as well (user's ask, 2026-11-22):
            // closing the band means no filter.
            if (b.hasAttribute('data-lms-fh-x')) clearBands(head);
            setOpen(head, b.hasAttribute('data-lms-fh-btn'));
        }, true);
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape' || !e.target || !e.target.closest) return;
            var head = e.target.closest('.lms-fh.lms-fopen');
            if (head && e.target.closest('.lms-fbar') && !e.target.matches('select')) setOpen(head, false);
        });

        var queued = false;
        function soon() { if (queued) return; queued = true; queueMicrotask(function () { queued = false; scan(); }); }
        document.addEventListener('livewire:init', function () {
            Livewire.hook('morphed', function () { scan(); });
        });
        document.addEventListener('livewire:navigated', soon);
        var start = function () {
            scan();
            var root = main();
            if (root && window.MutationObserver) new MutationObserver(soon).observe(root, { childList: true, subtree: true });
        };
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
    })();
</script>
