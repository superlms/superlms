{{--
    What both modern templates (Vibrant, Breeze) work the same way: the reset,
    photo slots and their watermark, the menu and its drawer, forms, tables
    (a card a row on a phone), the lightbox and the scroll reveal. Each
    layout dresses it with its own variables and skin: --primary, --dark,
    --light (the school's theme), --ink, --text, --muted, --line, --page,
    --card, --r-lg, --r-md, --r-sm, --shadow, --font, --wrap.

    As on Classic, nothing a school puts on a page can make it wider than the
    screen (html and body clip), and a phone's page never moves under the
    reader's finger: on a phone blocks only fade in, and no tall one moves.
--}}
<style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
    html { scroll-behavior: smooth; -webkit-text-size-adjust: 100%; }
    body { font-family: var(--font); font-size: 15px; line-height: 1.7; color: var(--text); background: var(--page); overflow-x: hidden; -webkit-font-smoothing: antialiased; }
    html, body { overflow-x: clip; }
    img { max-width: 100%; display: block; }
    iframe, video, embed, object { max-width: 100%; }
    a { color: inherit; text-decoration: none; }
    button, input, select, textarea { font: inherit; color: inherit; }
    .wrap { max-width: var(--wrap); margin: 0 auto; padding: 0 24px; }
    .ico { width: 1.15em; height: 1.15em; flex: none; }
    .hl { color: var(--primary); }
    .muted-note { text-align: center; color: var(--muted); padding: 30px 12px; }

    /* ══════════ PHOTO SLOT + WATERMARK ══════════ */
    .ph { position: relative; overflow: hidden; background: color-mix(in srgb, var(--primary) 7%, #EEF1F6); }
    .ph > img { width: 100%; height: 100%; object-fit: cover; transition: transform .6s cubic-bezier(.16,1,.3,1); }
    .ph .wm { display: none; }
    .ph.is-mark .wm { display: flex; }
    .wm {
        position: absolute; inset: 0; align-items: center; justify-content: center; flex-direction: column; gap: 8px; text-align: center; padding: 12px;
        background: linear-gradient(135deg, color-mix(in srgb, var(--primary) 12%, #fff) 0%, color-mix(in srgb, var(--primary) 4%, #F5F7FB) 100%);
        color: color-mix(in srgb, var(--primary) 60%, #64748B);
    }
    .wm::before { content: ''; position: absolute; inset: 0; background-image: var(--wm-tile, none); background-size: 64px; background-repeat: repeat; opacity: .05; }
    .wm img, .wm svg { position: relative; max-width: 40%; max-height: 56%; width: auto; height: auto; opacity: .3; }
    .wm svg { width: 40%; aspect-ratio: 1; color: var(--dark); }
    .wm small { position: relative; font-size: 11px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; opacity: .85; }

    /* ══════════ MENU (a row on a wide screen, a drawer otherwise) ══════════ */
    .menu { position: sticky; top: 0; z-index: 60; }
    .menu-bar { display: flex; align-items: center; }
    .menu-panel { flex: 1; min-width: 0; }
    .menu-brand, .menu-toggle, .menu-shade, .menu-panel-head, .menu-panel-foot { display: none; }
    .menu-list { list-style: none; display: flex; flex-wrap: wrap; justify-content: center; align-items: center; }
    .menu-list > li { position: relative; }
    .menu-list > li > a, .menu-list > li > button { display: flex; align-items: center; gap: 5px; background: none; border: 0; cursor: pointer; white-space: nowrap; }
    .menu-list .chev { width: 13px; height: 13px; transition: transform .2s; opacity: .7; }
    .drop {
        list-style: none; position: absolute; top: calc(100% + 8px); left: 0; min-width: 250px; max-height: 70vh; overflow-y: auto; z-index: 70;
        opacity: 0; visibility: hidden; transform: translateY(8px); transition: opacity .18s, transform .18s, visibility .18s;
    }
    .drop::before { content: ''; position: absolute; left: 0; right: 0; top: -10px; height: 10px; }
    .menu-list > li:hover > .drop, .menu-list > li:focus-within > .drop { opacity: 1; visibility: visible; transform: none; }
    .menu-list > li:nth-last-child(-n+3) > .drop { left: auto; right: 0; }
    .drop a { display: flex; align-items: center; gap: 10px; }

    .nav-drawer .menu-bar { justify-content: space-between; gap: 12px; min-height: 58px; }
    .nav-drawer .menu-brand { display: flex; align-items: center; gap: 10px; min-width: 0; font-weight: 800; font-size: 14px; line-height: 1.2; color: var(--ink); opacity: 0; visibility: hidden; transform: translateY(-4px); transition: opacity .25s, transform .25s, visibility .25s; }
    .nav-drawer .menu.stuck .menu-brand { opacity: 1; visibility: visible; transform: none; }
    .mb-logo { flex: none; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; }
    .mb-logo img, .mb-logo svg { max-width: 36px; max-height: 36px; width: auto; height: auto; object-fit: contain; }
    .mb-logo svg { width: 36px; height: 36px; }
    .nav-drawer .menu-brand > span:last-child { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .nav-drawer .menu-toggle { display: inline-flex; flex: none; align-items: center; gap: 10px; height: 40px; line-height: 1; padding: 0 16px; border: 0; border-radius: 999px; font-weight: 700; font-size: 13px; cursor: pointer; }
    .menu-toggle .bars { position: relative; width: 18px; height: 12px; }
    .menu-toggle .bars i { position: absolute; left: 0; width: 100%; height: 2px; border-radius: 2px; background: currentColor; transition: top .3s, transform .3s, opacity .2s; }
    .menu-toggle .bars i:nth-child(1) { top: 0; } .menu-toggle .bars i:nth-child(2) { top: 5px; } .menu-toggle .bars i:nth-child(3) { top: 10px; }
    .menu.open .menu-toggle .bars i:nth-child(1) { top: 5px; transform: rotate(45deg); }
    .menu.open .menu-toggle .bars i:nth-child(2) { opacity: 0; }
    .menu.open .menu-toggle .bars i:nth-child(3) { top: 5px; transform: rotate(-45deg); }
    .nav-drawer .menu.open { z-index: 150; }
    /* A blurred backdrop would hold the fixed drawer inside the bar: none in drawer mode. */
    .nav-drawer .menu { -webkit-backdrop-filter: none; backdrop-filter: none; background: rgba(255,255,255,.97); }
    .menu-layer { display: contents; }
    .nav-drawer .menu-layer { display: block; position: fixed; inset: 0; overflow: hidden; pointer-events: none; visibility: hidden; }
    .nav-drawer .menu.open .menu-layer { pointer-events: auto; visibility: visible; }
    .nav-drawer .menu-shade { display: block; position: absolute; inset: 0; background: rgba(15, 23, 42, .45); -webkit-backdrop-filter: blur(3px); backdrop-filter: blur(3px); opacity: 0; }
    .nav-drawer .menu.open .menu-shade { opacity: 1; }
    .nav-drawer .menu-panel { position: absolute; top: 10px; right: 10px; bottom: 10px; width: min(calc(100vw - 20px), 380px); display: flex; flex-direction: column; background: var(--card); border-radius: 26px; box-shadow: 0 30px 80px rgba(15, 23, 42, .25); transform: translateX(110%); overflow: hidden; }
    .nav-drawer .menu.open .menu-panel { transform: none; }
    .nav-anim.nav-drawer .menu-layer { transition: visibility .4s; }
    .nav-anim.nav-drawer .menu-shade { transition: opacity .3s; }
    .nav-anim.nav-drawer .menu-panel { transition: transform .42s cubic-bezier(.16, 1, .3, 1); }
    .nav-drawer .menu-panel-head { display: flex; align-items: center; gap: 12px; padding: 16px 14px 16px 18px; border-bottom: 1px solid var(--line); }
    .menu-panel-head b { flex: 1; min-width: 0; font-size: 14.5px; line-height: 1.3; color: var(--ink); }
    .menu-close { flex: none; width: 40px; height: 40px; border: 0; border-radius: 50%; background: var(--page); color: var(--ink); font-size: 24px; line-height: 1; cursor: pointer; }
    .nav-drawer .menu-list { display: block; flex: 1; overflow-y: auto; overscroll-behavior: contain; padding: 8px 10px 12px; }
    .nav-drawer .menu-list > li > a, .nav-drawer .menu-list > li > button { width: 100%; justify-content: space-between; padding: 13px 14px; border-radius: 14px; font-size: 14.5px; font-weight: 600; color: var(--ink); background: none; box-shadow: none; }
    .nav-drawer .menu-list > li.is-active > a, .nav-drawer .menu-list > li.is-active > button { background: color-mix(in srgb, var(--primary) 9%, #fff); color: var(--primary); }
    .nav-drawer .drop { position: static; display: none; min-width: 0; max-height: none; opacity: 1; visibility: visible; transform: none; box-shadow: none; border: 0; background: none; padding: 2px 0 6px 10px; }
    .nav-drawer .drop::before { content: none; }
    .nav-drawer .menu-list > li.open > .drop { display: block; animation: drop-in .25s ease; }
    .nav-drawer .menu-list > li.open .chev { transform: rotate(180deg); }
    .nav-drawer .drop a { padding: 10px 14px; font-size: 14px; border-radius: 12px; }
    .nav-drawer .menu-panel-foot { display: flex; gap: 10px; padding: 14px 16px calc(14px + env(safe-area-inset-bottom)); border-top: 1px solid var(--line); }
    .menu-panel-foot .btn { flex: 1; }
    @keyframes drop-in { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }
    html.menu-lock, html.menu-lock body { overflow: hidden; }

    /* ══════════ BUTTONS ══════════ */
    .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 22px; border: 0; border-radius: 999px; cursor: pointer; font-weight: 700; font-size: 14px; line-height: 1.2; color: #fff; background: var(--primary); transition: transform .2s, box-shadow .2s, filter .2s, background .2s; white-space: nowrap; }
    .btn:hover { transform: translateY(-2px); filter: brightness(1.05); }
    .btn .ico { width: 16px; height: 16px; }
    .btn-ghost { background: var(--card); color: var(--ink); box-shadow: inset 0 0 0 1.5px var(--line); }
    .btn-ghost:hover { box-shadow: inset 0 0 0 1.5px var(--primary); color: var(--primary); }
    .btn-soft { background: color-mix(in srgb, var(--primary) 10%, #fff); color: var(--primary); }
    .btn-white { background: #fff; color: var(--ink); }
    .btn-line { background: transparent; color: #fff; box-shadow: inset 0 0 0 1.5px rgba(255,255,255,.7); }
    .btn-sm { padding: 8px 15px; font-size: 13px; }
    .btns { display: flex; flex-wrap: wrap; gap: 10px; }

    /* ══════════ FORMS ══════════ */
    .sform-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 16px 18px; }
    .sform label { display: flex; flex-direction: column; gap: 7px; font-size: 13px; font-weight: 700; color: var(--ink); }
    .sform label.wide { grid-column: 1 / -1; }
    .sform label b { color: #EF4444; }
    .sform input, .sform select, .sform textarea { width: 100%; padding: 12px 15px; border: 1.5px solid var(--line); border-radius: var(--r-sm); background: var(--field, #fff); font-size: 14.5px; font-weight: 500; color: var(--ink); transition: border-color .15s, box-shadow .15s; }
    .sform textarea { resize: vertical; min-height: 110px; }
    .sform input:focus, .sform select:focus, .sform textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 4px color-mix(in srgb, var(--primary) 14%, transparent); }
    .sform .hp { display: none; }
    .sform-foot { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; margin-top: 20px; }
    .sform-msg { font-size: 13.5px; font-weight: 700; }

    /* ══════════ TABLES ══════════ */
    .tbl-wrap { overflow-x: auto; border-radius: var(--r-md); border: 1px solid var(--line); }
    .tbl { width: 100%; border-collapse: collapse; font-size: 14px; }
    .tbl th { text-align: left; font-size: 11.5px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); padding: 13px 16px; background: color-mix(in srgb, var(--primary) 4%, #F8FAFC); border-bottom: 1px solid var(--line); white-space: nowrap; }
    .tbl td { padding: 13px 16px; border-top: 1px solid var(--line); vertical-align: middle; }
    .tbl tbody tr:first-child td { border-top: 0; }
    .tbl tbody tr:hover td { background: color-mix(in srgb, var(--primary) 3%, #fff); }
    .tbl td.sno { color: var(--muted); font-weight: 600; width: 1%; white-space: nowrap; }
    .tbl td.strong { font-weight: 600; color: var(--ink); }
    .doc-tap { display: none; flex-direction: column; align-items: center; gap: 6px; padding: 28px 16px; border: 1.5px dashed color-mix(in srgb, var(--primary) 45%, var(--line)); border-radius: var(--r-md); background: color-mix(in srgb, var(--primary) 5%, #fff); color: var(--primary); text-align: center; }
    .doc-tap b { color: var(--ink); font-size: 15px; line-height: 1.35; }
    .doc-tap span { font-size: 12.5px; color: var(--muted); }

    /* ══════════ PHOTO GRID + LIGHTBOX ══════════ */
    .photos { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 14px; }
    .photos figure { position: relative; border-radius: var(--r-md); overflow: hidden; background: var(--card); }
    .photos .ph { aspect-ratio: 4 / 3; cursor: zoom-in; }
    .photos figure:hover .ph > img { transform: scale(1.06); }
    .photos figcaption { position: absolute; left: 10px; right: 10px; bottom: 10px; padding: 7px 12px; border-radius: 12px; font-size: 12.5px; font-weight: 600; color: var(--ink); background: rgba(255,255,255,.88); -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px); pointer-events: none; }
    .lb { position: fixed; inset: 0; z-index: 200; background: rgba(8, 11, 22, .92); display: none; align-items: center; justify-content: center; padding: 50px 64px; }
    .lb.open { display: flex; }
    .lb img { max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 14px; box-shadow: 0 20px 60px rgba(0,0,0,.5); }
    .lb button { position: absolute; background: rgba(255,255,255,.14); color: #fff; border: 0; width: 46px; height: 46px; border-radius: 50%; font-size: 22px; cursor: pointer; }
    .lb button:hover { background: rgba(255,255,255,.26); }
    .lb .lb-x { top: 14px; right: 14px; }
    .lb .lb-p { left: 14px; top: 50%; transform: translateY(-50%); }
    .lb .lb-n { right: 14px; top: 50%; transform: translateY(-50%); }

    .to-top { position: fixed; right: 18px; bottom: 18px; width: 46px; height: 46px; border-radius: 50%; color: #fff; display: none; align-items: center; justify-content: center; z-index: 80; font-size: 18px; background: var(--primary); box-shadow: 0 10px 24px color-mix(in srgb, var(--primary) 35%, transparent); }
    .to-top.show { display: flex; }

    /* ══════════ SCROLL REVEAL ══════════ */
    .rv-on { opacity: 0; transform: translateY(34px) scale(.98); }
    .rv-on.rv-soft { transform: translateY(16px); }
    .rv-on.rv-in { opacity: 1; transform: none; transition: opacity .8s cubic-bezier(.16,1,.3,1), transform .8s cubic-bezier(.16,1,.3,1); transition-delay: var(--rv-delay, 0s); }
    .rv-on.rv-soft.rv-in { transition-duration: .5s; }
    @media (prefers-reduced-motion: reduce) {
        .rv-on { opacity: 1 !important; transform: none !important; transition: none !important; }
        *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; }
    }

    /* ══════════ PHONE ══════════ */
    @media (max-width: 720px) {
        body { font-size: 14.5px; }
        .wrap { padding: 0 16px; }
        .sform-grid { grid-template-columns: minmax(0, 1fr); }
        /* 16px: a phone zooms in on a smaller box when it is tapped. */
        .sform input, .sform select, .sform textarea, .lms-card .lms-form input, .lms-card .lms-form select, .lms-card .lms-form textarea { font-size: 16px; }
        .sform-foot .btn { flex: 1; }
        .photos { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .photos figcaption { left: 6px; right: 6px; bottom: 6px; padding: 4px 8px; font-size: 11px; line-height: 1.35; }
        .lb { padding: 64px 8px; }
        .lb button { width: 40px; height: 40px; font-size: 19px; }
        .lb .lb-p, .lb .lb-n { top: auto; bottom: 12px; transform: none; }
        .lb .lb-p { left: calc(50% - 48px); }
        .lb .lb-n { right: calc(50% - 48px); }
        .map { height: 300px !important; }
        .doc-embed { display: none; }
        .doc-tap { display: flex; }
        .to-top { right: 14px; bottom: 14px; width: 42px; height: 42px; }
    }
    @media (max-width: 640px) {
        .tbl-wrap, .lms-scroll { overflow: visible; border: 0; }
        .tbl thead, .lms-tbl thead { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
        .tbl, .tbl tbody, .lms-tbl, .lms-tbl tbody { display: block; width: 100%; }
        .tbl tr, .lms-tbl tr { display: flex; flex-direction: column; background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 8px 14px; margin-bottom: 10px; }
        .lms-tbl tr { margin: 10px 12px; }
        .lms-tbl tbody tr:first-child { margin-top: 12px; }
        .tbl td, .lms-tbl td {
            display: flex; justify-content: space-between; align-items: baseline; gap: 14px; width: auto !important; min-width: 0 !important;
            padding: 6px 0 !important; border: 0 !important; background: none !important; text-align: right !important; white-space: normal !important; overflow-wrap: anywhere;
        }
        .tbl td::before, .lms-tbl td::before { content: attr(data-label); flex: none; max-width: 48%; text-align: left; font-size: 10.5px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #94A3B8; }
        .tbl td:not([data-label]), .lms-tbl td:not([data-label]), .tbl td[data-label=""], .lms-tbl td[data-label=""] { justify-content: flex-start; text-align: left !important; }
        .tbl td:not([data-label])::before, .lms-tbl td:not([data-label])::before { content: none; }
        .tbl td.sno, .lms-tbl td.sno { display: none; }
        .tbl td.strong, .lms-tbl td.strong { order: -1; justify-content: flex-start; text-align: left !important; font-size: 15px; font-weight: 700; color: var(--ink); padding: 6px 0 9px !important; margin-bottom: 3px; border-bottom: 1px solid var(--line) !important; }
        .tbl td.strong::before, .lms-tbl td.strong::before { content: none; }
        .tbl td.desc, .lms-tbl td.desc { flex-direction: column; align-items: flex-start; gap: 2px; text-align: left !important; }
        .tbl td.desc::before, .lms-tbl td.desc::before { max-width: none; }
        .tbl td.muted-note { justify-content: center; text-align: center !important; }
        .tbl td.muted-note::before { content: none; }
    }
</style>
