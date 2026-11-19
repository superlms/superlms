{{-- The Super Admin's panel, on every page of it (body.lms-sa), in the
     superlms.in website's look (user's ask, 2026-11-19). Laid over
     partials.panel-ui, which both panels share; the school admin's panel is
     left as it is.

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
        edge while empty, teal while being typed in, and once filled the edge
        AND the typed words teal (the name on the border teal too); red while
        wrong. The words are in the login's face (Geist, 15px). --}}
@php
    $in   = 'body.lms-ui.lms-sa #main-scroll';
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
    $btn    = 'body.lms-sa #main-scroll :is(button, a, [role="button"], input[type="submit"], input[type="button"])'
            . ':not([role="switch"], .sa-btn, .sk-cta, .sk-btn, .sk-tile, .an-chip, .sa-card, [class*="border-dashed"])';
@endphp
<style>
    body.lms-sa {
        --sa-t: #0d9488; --sa-t7: #0f766e; --sa-t50: #effaf7; --sa-t100: #d8f3ec; --sa-t200: #b1e6db;
        --sa-ink: #0c2620; --sa-line2: #d6dedb; --sa-hline: #eef1f0;
    }

    /* ─── 1. Header rows: 60px, the line level with the sidebar's divider ─── */
    @media (min-width: 768px) {
        /* The padded row that holds the page's title (…sm:min-h-9): no
           padding of its own above or below; the title row 59px and the
           line 1px — 60px, as .sa-brand. */
        body.lms-sa #main-scroll .py-3:has(> .sm\:min-h-9) {
            padding-top: 0 !important; padding-bottom: 0 !important;
            border-bottom: 1px solid var(--sa-hline) !important;
        }
        body.lms-sa #main-scroll .py-3:has(> .sm\:min-h-9) > .sm\:min-h-9 { min-height: 59px; }
        body.lms-sa #main-scroll .sm\:min-h-9.py-3 {
            padding-top: 0 !important; padding-bottom: 0 !important; min-height: 60px;
            border-bottom: 1px solid var(--sa-hline) !important;
        }
        /* The row of filters or tabs under it starts on that line (no second one). */
        body.lms-sa #main-scroll :is(.py-3:has(> .sm\:min-h-9), .sm\:min-h-9.py-3) + .border-t { border-top-width: 0 !important; }
        /* The sticky white header around them: its own bottom edge in the same soft line. */
        body.lms-sa #main-scroll .bg-white.border-b:has(.sm\:min-h-9) { border-bottom-color: var(--sa-hline); }
        /* A header whose title row is all it holds: that row's line is its edge. */
        body.lms-sa #main-scroll .bg-white.border-b:has(> .py-3:last-child > .sm\:min-h-9),
        body.lms-sa #main-scroll .bg-white.border-b:has(> .sm\:min-h-9.py-3:last-child) { border-bottom-width: 0 !important; }
    }
    @media (min-width: 1024px) {
        /* Home and Analytics: the same 60px row (Quick Links has it already). */
        body.lms-sa .sa-home .sk-head { height: 60px; padding-top: 0; padding-bottom: 0; flex-wrap: nowrap; border-bottom-color: var(--sa-hline); }
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
    /* The panel's blue / violet text buttons and ticks in teal. */
    body.lms-sa #main-scroll :is(input[type="checkbox"], input[type="radio"]) { accent-color: var(--sa-t); }
    body.lms-sa #main-scroll :is(input[type="checkbox"], input[type="radio"]):is([class*="text-blue-"], [class*="text-indigo-"], [class*="text-violet-"]) { color: var(--sa-t); }

    /* ─── 3. Fields: the login's look ─── */
    /* Every box: the login's face, and teal (not blue) while in use. */
    body.lms-sa #main-scroll {!! $any !!} { font-family: 'Geist', 'Segoe UI', system-ui, -apple-system, sans-serif; }
    body.lms-sa #main-scroll {!! $any !!}:focus { --tw-ring-color: rgba(13,148,136,.28) !important; border-color: var(--sa-t) !important; }
    /* A field with its name on the border (partials.panel-ui): grey edge while empty … */
    {!! $in !!} {!! $name !!} + {!! $box !!} {
        border-color: var(--sa-line2) !important; color: #111827;
        font-size: .9375rem; letter-spacing: normal;
    }
    {!! $in !!} {!! $name !!} + {!! $box !!}::placeholder { color: #9ca3af; }
    /* … teal while typing … */
    {!! $in !!} {!! $name !!} + {!! $box !!}:focus { border-color: var(--sa-t) !important; }
    {!! $in !!} {!! $name !!}:has(+ {!! $box !!}:focus) { color: var(--sa-t7) !important; }
    /* … and once filled, the edge and the words teal, as the login. */
    {!! $in !!} {!! $name !!} + {!! $box !!}.sa-filled { border-color: var(--sa-t) !important; color: var(--sa-t); -webkit-text-fill-color: var(--sa-t); }
    {!! $in !!} {!! $name !!}:has(+ {!! $box !!}.sa-filled) { color: var(--sa-t7) !important; }
    {!! $in !!} {!! $name !!} + {!! $box !!}:-webkit-autofill { border-color: var(--sa-t) !important; -webkit-text-fill-color: var(--sa-t); -webkit-box-shadow: 0 0 0 1000px #fff inset !important; }
    /* Red while wrong (as before). */
    {!! $in !!} {!! $name !!} + {!! $box !!}[class*="border-red"] { border-color: #dc2626 !important; color: #dc2626; -webkit-text-fill-color: #dc2626; }
    {!! $in !!} {!! $name !!}:has(+ {!! $box !!}[class*="border-red"]) { color: #dc2626 !important; }
    {!! $in !!} {!! $name !!} + {!! $box !!}:disabled { border-color: #e5e7eb !important; color: #6b7280; -webkit-text-fill-color: #6b7280; }
    /* Other boxes (search, filters): once something is typed, the words teal too. */
    body.lms-sa #main-scroll {!! $any !!}.sa-filled:not(:disabled) { color: var(--sa-t7); }
</style>
<script>
    // Marks every box that holds something (.sa-filled) — as the login does —
    // when typed in, when Livewire redraws a part of the page, and when a
    // form opens with its values.
    (function () {
        if (window.__saTheme) return;
        window.__saTheme = true;
        var SEL = '#main-scroll :is(input:not([type="checkbox"], [type="radio"], [type="file"], [type="hidden"], [type="range"], [type="color"], [type="submit"], [type="button"]), select, textarea)';
        function mark(el) {
            var v = el.value == null ? '' : String(el.value);
            el.classList.toggle('sa-filled', v.trim() !== '');
        }
        function scan() { document.querySelectorAll(SEL).forEach(mark); }
        var queued = false;
        function soon() { if (queued) return; queued = true; requestAnimationFrame(function () { queued = false; scan(); }); }
        ['input', 'change'].forEach(function (t) {
            document.addEventListener(t, function (e) { if (e.target && e.target.matches && e.target.matches(SEL)) mark(e.target); }, true);
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
</script>
