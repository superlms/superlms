{{-- The superlms.in look on the Super Admin's Quick Links and Home
     (2026-11-18): white page, teal accent, Plus Jakarta Sans headings, soft
     rounded cards and the website's product-screen style — the mint panel of
     tiles, the analytics screen's search bar and Live pill. Scoped to .sa-skin
     so no other panel page changes. --}}
@once
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
<style>
    .sa-skin {
        --sk-ink: #0c2620; --sk-text: #2b433b; --sk-muted: #6b7c76; --sk-line: #e5eae8; --sk-soft: #f4f6f5;
        --sk-teal: #0d9488; --sk-teal-700: #0f766e; --sk-teal-50: #effaf7; --sk-teal-100: #d8f3ec; --sk-mint: #dff3ee;
        --sk-display: 'Plus Jakarta Sans', 'Geist', system-ui, sans-serif;
        font-family: 'Geist', system-ui, -apple-system, 'Segoe UI', sans-serif;
        color: var(--sk-text); background: #fff;
    }
    @media (min-width: 768px) { .sa-skin.sk-full { height: 100vh; overflow: hidden; } }
    .sa-skin .sk-display { font-family: var(--sk-display); letter-spacing: -.035em; color: var(--sk-ink); }

    /* ── Header row: title · search · Live · actions (the analytics screen's top) ── */
    .sk-head { display: flex; align-items: center; gap: 12px; padding: 14px 24px; background: rgba(255,255,255,.92); backdrop-filter: blur(10px); border-bottom: 1px solid var(--sk-line); position: sticky; top: 0; z-index: 30; flex-wrap: wrap; }
    .sk-title { display: flex; align-items: baseline; gap: 10px; min-width: 0; }
    .sk-title h1 { font-family: var(--sk-display); font-size: 22px; font-weight: 800; letter-spacing: -.035em; color: var(--sk-ink); line-height: 1.1; white-space: nowrap; }
    .sk-title p { font-size: 12.5px; color: var(--sk-muted); white-space: nowrap; }
    .sk-search { position: relative; flex: 1 1 260px; max-width: 440px; }
    .sk-search > svg { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #8b9893; pointer-events: none; }
    .sk-search input { width: 100%; height: 40px; padding: 0 36px 0 40px; border-radius: 12px; border: 1px solid #e3e8e6; background: #fbfcfc; font-size: 14px; color: var(--sk-ink); outline: none; transition: border-color .2s, box-shadow .2s, background .2s; }
    .sk-search input:focus { border-color: var(--sk-teal); background: #fff; box-shadow: 0 0 0 4px rgba(13,148,136,.12); }
    .sk-search > button { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); width: 22px; height: 22px; border-radius: 6px; color: #8b9893; display: grid; place-items: center; }
    .sk-search > button:hover { background: var(--sk-soft); color: var(--sk-ink); }
    .sk-right { margin-left: auto; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .sk-pill { display: inline-flex; align-items: center; gap: 6px; height: 30px; padding: 0 12px; border-radius: 999px; border: 1px solid #e3e8e6; background: #fff; font-size: 12.5px; font-weight: 500; color: #44544f; white-space: nowrap; }
    .sk-pill.live { color: var(--sk-teal-700); border-color: var(--sk-teal-100); background: var(--sk-teal-50); }
    .sk-pill.live::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: var(--sk-teal); animation: skpulse 1.8s infinite; }
    a.sk-pill:hover, button.sk-pill:hover { border-color: var(--sk-teal); color: var(--sk-teal-700); }
    @keyframes skpulse { 0% { box-shadow: 0 0 0 0 rgba(13,148,136,.45); } 70% { box-shadow: 0 0 0 7px rgba(13,148,136,0); } 100% { box-shadow: 0 0 0 0 rgba(13,148,136,0); } }
    .sk-select { height: 30px; padding: 0 28px 0 12px; border-radius: 999px; border: 1px solid #e3e8e6; background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%236b7c76' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E") no-repeat right 10px center; appearance: none; -webkit-appearance: none; font-size: 12.5px; font-weight: 500; color: #44544f; outline: none; cursor: pointer; }
    .sk-select:focus { border-color: var(--sk-teal); box-shadow: 0 0 0 3px rgba(13,148,136,.12); }
    .sk-actions { display: none; align-items: center; gap: 6px; padding-left: 8px; margin-left: 2px; border-left: 1px solid var(--sk-line); }
    @media (min-width: 768px) { .sk-actions { display: flex; } }
    .sk-btn { position: relative; width: 36px; height: 36px; border-radius: 12px; border: 1px solid #e3e8e6; background: #fff; display: grid; place-items: center; color: #44544f; transition: background .2s, border-color .2s, color .2s, transform .2s; }
    .sk-btn:hover { border-color: var(--sk-teal); color: var(--sk-teal-700); background: var(--sk-teal-50); }
    .sk-btn svg { width: 17px; height: 17px; }
    .sk-btn img { width: 19px; height: 19px; object-fit: contain; }
    .sk-btn.out:hover { border-color: #fda4af; color: #e11d48; background: #fff1f2; }
    .sk-badge { position: absolute; top: -5px; right: -5px; min-width: 18px; height: 18px; padding: 0 4px; border-radius: 999px; background: #f0506e; color: #fff; font-size: 10px; font-weight: 700; display: grid; place-items: center; border: 2px solid #fff; }
    .sk-menu { position: absolute; left: 0; top: calc(100% + 8px); width: 260px; background: #fff; border: 1px solid var(--sk-line); border-radius: 16px; box-shadow: 0 24px 50px -24px rgba(12,38,32,.4); z-index: 60; overflow: hidden; }
    .sk-menu-h { padding: 10px 14px 6px; font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: #95a39e; }
    .sk-menu a, .sk-menu div.cur { display: flex; align-items: center; gap: 10px; padding: 9px 14px; font-size: 13.5px; color: var(--sk-ink); }
    .sk-menu a:hover { background: var(--sk-soft); }
    .sk-menu div.cur { background: var(--sk-teal-50); color: var(--sk-teal-700); font-weight: 600; }
    .sk-menu small { display: block; font-size: 11.5px; color: var(--sk-muted); font-weight: 400; }
    .sk-menu .foot { justify-content: space-between; border-top: 1px solid var(--sk-line); font-size: 12.5px; font-weight: 500; color: #44544f; }

    /* ── Quick Links: the mint panel of white tiles ── */
    .sk-panel { position: relative; background: var(--sk-mint); border-radius: 28px; padding: 18px; overflow: hidden; }
    .sk-panel::before { content: ''; position: absolute; right: -10%; bottom: -40%; width: 60%; height: 90%; border-radius: 50%; background: radial-gradient(closest-side, rgba(255,255,255,.55), transparent); pointer-events: none; }
    .sk-tiles { position: relative; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
    @media (min-width: 640px) { .sk-tiles { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    @media (min-width: 768px) { .sk-tiles { grid-template-columns: repeat(6, minmax(0, 1fr)); grid-auto-rows: 1fr; height: 100%; } }
    .sk-tiles.is-filtering { height: auto; grid-auto-rows: minmax(118px, auto); }
    .sk-tile { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; min-height: 92px; padding: 12px 8px; border-radius: 18px; background: #fff; border: 1px solid transparent; text-align: center; box-shadow: 0 10px 24px -16px rgba(12,38,32,.35); transition: transform .3s cubic-bezier(.2,.7,.2,1), box-shadow .3s, border-color .2s; animation: sktile .55s cubic-bezier(.2,.7,.2,1) both; animation-delay: calc(var(--i, 0) * 22ms); }
    .sk-tile:hover { transform: translateY(-4px); border-color: var(--sk-teal-100); box-shadow: 0 22px 40px -22px rgba(12,38,32,.45); }
    .sk-tile-ic { width: 46px; height: 46px; border-radius: 14px; display: grid; place-items: center; color: var(--sk-teal); background: var(--sk-teal-50); transition: background .25s, color .25s, transform .25s; }
    .sk-tile-ic svg { width: 23px; height: 23px; }
    .sk-tile:hover .sk-tile-ic { background: var(--sk-teal); color: #fff; transform: scale(1.06) rotate(-4deg); }
    .sk-tile span { font-size: 12.5px; font-weight: 600; color: #44544f; line-height: 1.25; }
    .sk-tile:hover span { color: var(--sk-ink); }
    @keyframes sktile { from { opacity: 0; transform: translateY(10px) scale(.97); } to { opacity: 1; transform: none; } }
    .sk-chip { position: absolute; z-index: 2; display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 14px; background: #fff; box-shadow: 0 14px 30px -14px rgba(12,38,32,.35); font-size: 12.5px; font-weight: 600; color: var(--sk-ink); }
    .sk-chip svg { width: 16px; height: 16px; color: var(--sk-teal); }
    .sk-empty { position: relative; padding: 48px 16px; text-align: center; color: var(--sk-muted); font-size: 14px; }

    /* ── Quick Links page (sk-ql): the mint is the whole page, edge to edge;
          the header row sits on it with no line under it and none between
          Sort and its buttons; white search, pills and buttons on the mint;
          the tiles straight on the page, lined up with the header. ── */
    .sa-skin.sk-ql { background: radial-gradient(55% 60% at 95% 105%, rgba(255,255,255,.6), transparent 70%), var(--sk-mint); min-height: calc(100vh - var(--lms-nav-h, 0px)); }
    .sk-ql .sk-head { background: rgba(223,243,238,.94); border-bottom: 0; padding: 18px 24px 8px; }
    .sk-ql .sk-actions { border-left: 0; padding-left: 0; margin-left: 2px; }
    .sk-ql .sk-search input { background: #fff; border-color: #fff; box-shadow: 0 1px 2px rgba(12,38,32,.04), 0 10px 24px -18px rgba(12,38,32,.35); }
    .sk-ql .sk-search input:focus { border-color: var(--sk-teal); }
    .sk-ql .sk-pill, .sk-ql .sk-select, .sk-ql .sk-btn { border-color: #fff; box-shadow: 0 1px 2px rgba(12,38,32,.05); }
    .sk-ql .sk-pill.live { background: #fff; border-color: #fff; }
    .sk-ql .sk-body { padding: 10px 24px 24px; }
    .sk-ql .sk-panel { background: transparent; border-radius: 0; padding: 0; overflow: visible; }
    .sk-ql .sk-panel::before { display: none; }
    @media (max-width: 639px) { .sk-ql .sk-head { padding: 14px 14px 6px; } .sk-ql .sk-body { padding: 8px 14px 18px; } }

    /* ── Header's main button ── */
    .sk-cta { display: inline-flex; align-items: center; gap: 6px; height: 36px; padding: 0 16px; border-radius: 999px; background: var(--sk-teal); color: #fff; font-size: 13.5px; font-weight: 600; transition: background .2s, box-shadow .25s, transform .2s; white-space: nowrap; }
    .sk-cta:hover { background: var(--sk-teal-700); box-shadow: 0 10px 22px -10px rgba(13,148,136,.7); transform: translateY(-1px); }

    /* ── Home: the dashboard's cards in the website's look ── */
    .sa-home { background: #f6f8f7; }
    .sa-home .sk-head .sk-menu a { font-size: 13.5px; }
    .sa-home .sa-card { border-radius: 22px; border-color: var(--sk-line); box-shadow: 0 1px 2px rgba(12,38,32,.04), 0 18px 40px -30px rgba(12,38,32,.3); }
    .sa-home a.sa-card:hover, .sa-home button.sa-card:not(:disabled):hover { border-color: var(--sk-teal-100); box-shadow: 0 2px 4px rgba(12,38,32,.05), 0 26px 50px -30px rgba(12,38,32,.4); }
    .sa-home .sa-card-title { font-family: var(--sk-display); font-size: 16px; font-weight: 700; letter-spacing: -.02em; color: var(--sk-ink); }
    .sa-home .sa-card-sub { color: var(--sk-muted); font-size: 12.5px; }
    .sa-home .sa-kpi-value { font-family: var(--sk-display); font-weight: 800; letter-spacing: -.045em; color: var(--sk-ink); font-size: 30px; }
    .sa-home .sa-chip { background: var(--sk-teal-50); color: var(--sk-teal-700); }
    .sa-home .text-gray-900 { color: var(--sk-ink); }
    .sa-home table thead tr { background: #fafbfb; }
    .sa-home .text-\[28px\], .sa-home .text-\[32px\], .sa-home .text-2xl, .sa-home .text-xl { font-family: var(--sk-display); letter-spacing: -.035em; }
    /* The panel's violet becomes the website's teal on this page only. */
    .sa-home .text-violet-600, .sa-home .text-violet-700 { color: var(--sk-teal-700) !important; }
    .sa-home .hover\:text-violet-800:hover { color: var(--sk-ink) !important; }
    .sa-home .bg-violet-600 { background-color: var(--sk-teal) !important; }
    .sa-home .bg-violet-50 { background-color: var(--sk-teal-50) !important; }
    .sa-home a.text-xs.font-medium.text-violet-600 { font-weight: 600; }

    @media (prefers-reduced-motion: reduce) { .sk-tile { animation: none; } .sk-pill.live::before { animation: none; } }
</style>
@endonce
