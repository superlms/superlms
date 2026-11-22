{{-- Every list of the Super Admin's and the school admin's panels (body.lms-ui)
     in the look of the Super Admin's Home / Analytics (user's ask of
     2026-11-22: "saree listing ko superadmin home analytics ui theme m bdl
     do"; the figures and data exactly as they were — only the look changes).

     Only CSS, laid over partials.super-admin-theme: no page's markup, query
     or figure changes, so a list shows the same rows, columns and counts as
     before. The pages already drawn in Home's look (.sa-skin: Home,
     Analytics, Reports, Session, School Data, Profile, Messaging, Quick
     Links) are left alone, and so are pop-ups and slide-over forms (.fixed,
     role="dialog") and the grey "Filter by" band.

       1. The page: Home's light mint-grey ground (#f6f8f7).
       2. The header: the title in Home's heading (Plus Jakarta Sans, 22px,
          800). Its figures ("Total: 2,185", "Active: …") and everything else
          in it stay as they were (they were Home's round pills for a day —
          the user had them put back, 2026-11-22).
       3. Cards: Home's white cards — soft rounded corners, a hairline edge
          and a quiet shadow (a card inside a card: smaller corners, no
          shadow); a card's big figures in the heading face.
       4. Tables: Home's list — a quiet head in small grey capitals on a
          faint ground, hairline rows, a soft row under the pointer, more
          room at the card's edges; the panel's greys in Home's green-greys.
       5. In the rows: a name's letter avatar in Home's teal; a status badge
          as Home's pill (green / amber / red / blue / teal / grey); the
          panel's blue links and View buttons teal.

     To undo it: remove its @include in components.layouts.app (or revert
     its commit) — nothing else depends on it. --}}
@php
    $pg = 'body.lms-ui #main-scroll';
    // Not on a page already in Home's look, nor in a pop-up / slide-over.
    $no = ':not(.sa-skin, .sa-skin *, #main-scroll .fixed *, [role="dialog"] *)';
    // Nor in a page's header (its figures, pills and buttons stay exactly as
    // they were — user's ask, 2026-11-22; only the title takes Home's heading).
    $nh = ':not(.sticky *, .bg-white.border-b:has(h1) *)';

    // A card: a white box with rounded corners and an edge or a shadow (not a
    // small tile, a menu, something in the header or the filter band).
    $small = collect(['w-5', 'w-6', 'w-7', 'w-8', 'w-9', 'w-10', 'w-11', 'w-12', 'w-14', 'h-5', 'h-6', 'h-7', 'h-8', 'h-9', 'h-10', 'h-11', 'h-12', 'h-14'])
        ->map(fn ($c) => "[class~=\"$c\"]")->implode(', ');
    $cardBase = ':is(div, section, article, form, li, aside).bg-white:is(.rounded-lg, .rounded-xl, .rounded-2xl):is(.border, .shadow, .shadow-sm, .shadow-md)'
              . ':not(.absolute, ' . $small . ')';
    $card = "$pg $cardBase$no:not(.sticky *, .lms-fbar *, td *)";

    // A list's table (not Home's own, nor a grid drawn with a table).
    $tbl = "$pg table$no:not(.sk-table, .an-cmp, .border-separate)";

    // A status badge: a small pill with a light ground, per colour family.
    $badge = "$pg :is(td, .bg-white) span.rounded-full:is([class~=\"px-1.5\"], [class~=\"px-2\"], [class~=\"px-2.5\"], [class~=\"px-3\"])$no$nh";
    $families = [
        'green' => [['green', 'emerald', 'lime'], '#ecfdf5', '#047857'],
        'amber' => [['amber', 'yellow', 'orange'], '#fffbeb', '#b45309'],
        'rose'  => [['red', 'rose', 'pink'], '#fff1f2', '#be123c'],
        'blue'  => [['blue', 'sky', 'cyan'], '#eff6ff', '#1d4ed8'],
        'teal'  => [['teal', 'indigo', 'violet', 'purple', 'fuchsia'], '#effaf7', '#0f766e'],
        'grey'  => [['gray', 'slate', 'zinc', 'neutral', 'stone'], '#f3f4f6', '#4b5563'],
    ];
    $tok = fn (array $colours, array $shades) => collect($colours)
        ->flatMap(fn ($c) => collect($shades)->map(fn ($s) => "[class~=\"bg-$c-$s\"]"))->implode(', ');
    $anyLight = $tok(array_merge(...array_map(fn ($f) => $f[0], array_values($families))), [50, 100, 200]);

    // The panel's blue / violet text → Home's teal.
    $blueText = '[class~="text-blue-600"], [class~="text-blue-700"], [class~="text-indigo-600"], [class~="text-indigo-700"], [class~="text-violet-600"], [class~="text-purple-600"]';

    // A letter avatar: a small circle on a light (or gradient) ground.
    $avSize = collect([6, 7, 8, 9, 10, 11, 12])->map(fn ($n) => "[class~=\"w-$n\"][class~=\"h-$n\"]")->implode(', ');
    $avBg   = $tok(['indigo', 'blue', 'violet', 'purple', 'gray', 'sky'], [50, 100, 200]) . ', [class*="bg-gradient-to"]';
@endphp
<style>
    /* ─── 1. The page ─── */
    {!! $pg !!} :is(.min-h-screen, [class*="min-h-"]).bg-gray-50:not(.sa-skin) { background-color: #f6f8f7; }

    /* ─── 2. The header ─── */
    {!! $pg !!} :is(.sticky, .bg-white.border-b) h1{!! $no !!}:not(.lms-fbar h1) {
        font-family: 'Plus Jakarta Sans', 'Geist', system-ui, sans-serif;
        font-size: 22px; font-weight: 800; letter-spacing: -.035em; line-height: 1.15; color: #0c2620;
    }
    @media (max-width: 639px) { {!! $pg !!} :is(.sticky, .bg-white.border-b) h1{!! $no !!}:not(.lms-fbar h1) { font-size: 20px; } }

    /* ─── 3. Cards ─── */
    {!! $card !!} {
        border-color: #e5eae8;
        box-shadow: 0 1px 2px rgba(12,38,32,.04), 0 18px 40px -30px rgba(12,38,32,.3);
    }
    {!! $card !!}:is(.rounded-xl, .rounded-2xl) { border-radius: 22px; }
    {!! $card !!}.rounded-lg { border-radius: 16px; }
    {!! $card !!}:is(a, [wire\:click]):hover { border-color: #d8f3ec; box-shadow: 0 2px 4px rgba(12,38,32,.05), 0 26px 50px -30px rgba(12,38,32,.4); }
    /* A card inside a card: smaller corners, no shadow of its own. */
    {!! $pg !!} {!! $cardBase !!} {!! $cardBase !!}{!! $no !!}:not(.sticky *, .lms-fbar *, td *) { border-radius: 16px; box-shadow: none; border-color: #eef1f0; }
    /* The rounded box a table scrolls in, and a head cell's own corner: the card's corners. */
    {!! $card !!} :is(.overflow-x-auto, .overflow-auto, [class*="overflow-hidden"]):is(.rounded-lg, .rounded-xl, .rounded-2xl) { border-radius: inherit; }
    {!! $card !!}:is(.rounded-xl, .rounded-2xl) th.rounded-tl-xl { border-top-left-radius: 22px; }
    {!! $card !!}:is(.rounded-xl, .rounded-2xl) th.rounded-tr-xl { border-top-right-radius: 22px; }
    /* Its lines: Home's hairlines. */
    {!! $card !!} :is(div, header, footer, ul, li, thead, tbody, tr, th, td):is([class~="border-gray-100"], [class~="border-gray-200"]) { border-color: #eef1f0; }
    {!! $card !!} :is([class~="divide-gray-100"], [class~="divide-gray-200"]) > * { border-color: #f0f3f2; }
    /* Its titles and big figures in Home's heading face. */
    {!! $card !!} :is(h2, h3) { color: #0c2620; letter-spacing: -.02em; }
    {!! $card !!} :is(.text-xl, .text-2xl, .text-3xl):is(.font-bold, .font-semibold, .font-extrabold) {
        font-family: 'Plus Jakarta Sans', 'Geist', system-ui, sans-serif; letter-spacing: -.035em;
    }
    {!! $card !!} :is(.text-xl, .text-2xl, .text-3xl):is(.font-bold, .font-semibold, .font-extrabold).text-gray-900 { color: #0c2620; }

    /* ─── 4. Tables ─── */
    {!! $tbl !!} thead, {!! $tbl !!} thead > tr { background-color: #fafbfb; }
    {!! $tbl !!} thead th { border-bottom: 1px solid #eef1f0; }
    {!! $tbl !!} thead th[class*="bg-gray-"], {!! $tbl !!} thead th[class*="bg-slate-"], {!! $tbl !!} thead th.bg-white { background-color: #fafbfb; }
    {!! $tbl !!} thead th:not([class*="text-white"]) {
        font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: #95a39e;
    }
    /* A table whose columns have set widths (Super Admin's Students, Teachers …):
       the heads keep their own case — in capitals they would no longer fit
       their columns and be cut short with "…". */
    {!! $tbl !!}.table-fixed thead th:not([class*="text-white"]) { text-transform: none; letter-spacing: 0; font-weight: 500; }
    {!! $tbl !!} thead th:not([class*="text-white"]) :is(button, a) { font: inherit; letter-spacing: inherit; text-transform: inherit; color: inherit; }
    {!! $tbl !!} thead th :is(button, a):hover { color: #0c2620; }
    {!! $tbl !!} :is(th, td):first-child { padding-left: 20px; }
    {!! $tbl !!} :is(th, td):last-child { padding-right: 20px; }
    {!! $tbl !!} :is(th, td):only-child { padding-left: 20px; padding-right: 20px; }
    {!! $tbl !!} > tbody > tr, {!! $tbl !!} > tbody > tr > td { border-color: #f0f3f2; }
    {!! $tbl !!} > tbody > tr { transition: background-color .15s; }
    {!! $tbl !!} > tbody > tr:hover { background-color: #fbfdfc; }
    {!! $tbl !!} .text-gray-900 { color: #0c2620; }
    {!! $tbl !!} .text-gray-800 { color: #16302a; }
    {!! $tbl !!} .text-gray-700 { color: #2b433b; }
    {!! $tbl !!} .text-gray-600 { color: #44544f; }
    {!! $tbl !!} .text-gray-500 { color: #6b7c76; }
    {!! $tbl !!} .text-gray-400 { color: #95a39e; }
    {!! $tbl !!} img.rounded-full { border-color: #e5eae8; }
    /* The panel's blue figures in a row (an amount, a count): Home's teal. */
    {!! $tbl !!} td :is({!! $blueText !!}):not([class~="text-white"]) { color: #0f766e; }
    /* A list's empty state ("No students found"): its round icon in Home's teal. */
    {!! $pg !!} :is(td, .bg-white) :is(div, span).rounded-full:is([class~="w-12"][class~="h-12"][class~="mx-auto"], [class~="w-14"][class~="h-14"][class~="mx-auto"], [class~="w-16"][class~="h-16"]):is([class~="bg-gray-100"], [class~="bg-gray-50"], [class~="bg-blue-50"], [class~="bg-indigo-50"]):has(> svg){!! $no !!}{!! $nh !!} { background-color: #effaf7; }
    {!! $pg !!} :is(td, .bg-white) :is(div, span).rounded-full:is([class~="w-12"][class~="h-12"][class~="mx-auto"], [class~="w-14"][class~="h-14"][class~="mx-auto"], [class~="w-16"][class~="h-16"]):is([class~="bg-gray-100"], [class~="bg-gray-50"], [class~="bg-blue-50"], [class~="bg-indigo-50"]){!! $no !!}{!! $nh !!} > svg { color: #0d9488; }

    /* ─── 5. In the rows ─── */
    /* A name's letter avatar (no photo): Home's teal circle. */
    {!! $pg !!} :is(td, .bg-white) :is(div, span).rounded-full:is({!! $avSize !!}):is({!! $avBg !!}){!! $no !!}{!! $nh !!}:not(:has(img, svg)) {
        background-color: #effaf7; background-image: none; color: #0f766e;
    }
    {!! $pg !!} :is(td, .bg-white) :is(div, span).rounded-full:is({!! $avSize !!}):is({!! $avBg !!}){!! $no !!}{!! $nh !!}:not(:has(img, svg)) > * { color: #0f766e; }
    /* A status badge: Home's pill. */
    {!! $badge !!}:is({!! $anyLight !!}) {
        display: inline-flex; align-items: center; gap: 5px; min-height: 22px; padding: 2px 9px;
        font-size: 11px; font-weight: 600; line-height: 1.2; letter-spacing: 0; border-color: transparent; white-space: nowrap;
    }
@foreach ($families as [$colours, $bg, $ink])
    {!! $badge !!}:is({!! $tok($colours, [50, 100, 200]) !!}) { background-color: {{ $bg }}; color: {{ $ink }}; }
@endforeach
    /* A progress bar (Fees' collection, a fee's share): Home's teal on its soft track. */
    {!! $pg !!} :is(td, .bg-white) div.rounded-full[style*="width"]:is([class~="bg-blue-500"], [class~="bg-blue-600"], [class~="bg-indigo-500"], [class~="bg-indigo-600"], [class~="bg-violet-500"], [class~="bg-violet-600"]){!! $no !!}{!! $nh !!} { background-color: #0d9488; }
    {!! $pg !!} :is(td, .bg-white) div.rounded-full:is([class~="bg-gray-100"], [class~="bg-gray-200"]):has(> div.rounded-full[style*="width"]){!! $no !!}{!! $nh !!} { background-color: #eef2f1; }
    /* The panel's blue links and View buttons in a list: teal. */
    {!! $pg !!} :is(td, .bg-white) :is(a, button):is({!! $blueText !!}){!! $no !!}{!! $nh !!}:not(.sticky *, .lms-fbar *, [class~="text-white"]) { color: #0f766e; }
    {!! $pg !!} :is(td, .bg-white) :is(a, button):is({!! $blueText !!}){!! $no !!}{!! $nh !!}:not(.sticky *, .lms-fbar *, [class~="text-white"]):hover { color: #0c2620; }
    {!! $pg !!} td :is(a, button):is({!! $blueText !!}):is([class~="p-1"], [class~="p-1.5"], [class~="p-2"]){!! $no !!}{!! $nh !!}:hover { background-color: #effaf7; color: #0f766e; }
</style>
