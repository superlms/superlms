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

    /* ── Quick Links page (sk-ql), user's ask of 2026-11-18 (later): a white
          page; the header as the school admin's — one white row with a line
          under it, 60px tall so that line runs on from the sidebar's divider
          under the logo — holding (since 2026-11-19) "Welcome! <role>", the
          search, Ask AI, the time over the date, the school admin's session,
          then Messages and the bell in circles (Profile and Logout are on the
          sidebar's user card). The tiles: white, a hairline edge; under the
          pointer each lifts and fills with its own pastel (the website's
          panels), its icon turning white with a little bounce. ── */
    .sa-skin.sk-ql { background: #fff; min-height: calc(100vh - var(--lms-nav-h, 0px)); }
    .sk-ql .sk-head { height: 60px; padding: 0 20px; gap: 10px; flex-wrap: nowrap; background: #fff; border-bottom: 1px solid #eef1f0; -webkit-backdrop-filter: none; backdrop-filter: none; }
    .sk-ql .sk-title { align-items: center; gap: 10px; flex: none; }
    .sk-ql .sk-clock, .sk-ql .sk-actions, .sk-ql .sk-session { flex: none; }
    .sk-ql .sk-title h1 { font-size: 17px; font-weight: 700; letter-spacing: -.02em; }
    /* 2026-11-19: the title greets the role — "Welcome!" in the teal. */
    .sk-ql .sk-title .sk-hi { color: var(--sk-teal); }
    .sk-ql .sk-search { flex: 1 1 180px; min-width: 140px; max-width: 300px; }
    .sk-ql .sk-search input { height: 36px; background: #fbfcfc; }
    .sk-ql .sk-pill, .sk-ql .sk-select { height: 32px; }
    .sk-ql .sk-right { flex-wrap: nowrap; gap: 10px; }
    /* The time, and the date under it in small type. */
    .sk-clock { display: inline-flex; flex-direction: column; align-items: flex-end; justify-content: center; gap: 1px; padding: 0 2px; line-height: 1.15; white-space: nowrap; }
    .sk-clock b { font-size: 13px; font-weight: 600; color: var(--sk-ink); letter-spacing: -.005em; }
    .sk-clock small { font-size: 10.5px; font-weight: 500; color: #8b9893; }
    /* Messages and Notifications: a soft mint circle each, the icon in the
       ink; under the pointer the circle fills teal and the icon turns white
       (the bell rings a little). The count sits on the circle's rim. */
    .sk-ql .sk-actions { gap: 8px; border-left: 1px solid var(--sk-line); padding-left: 12px; margin-left: 2px; }
    .sk-ql .sk-btn { width: 38px; height: 38px; border-radius: 50%; border: 1px solid #e3ece9; background: #f3f8f6; color: #1f3a33;
        box-shadow: 0 1px 2px rgba(12,38,32,.05); transition: background .2s, border-color .2s, color .2s, box-shadow .25s, transform .2s; }
    .sk-ql .sk-btn svg { width: 19px; height: 19px; }
    .sk-ql .sk-btn svg * { stroke-width: 1.7px; }
    .sk-ql .sk-btn:hover { background: var(--sk-teal); border-color: var(--sk-teal); color: #fff; transform: translateY(-1px); box-shadow: 0 10px 20px -10px rgba(13,148,136,.7); }
    .sk-ql .sk-btn:focus-visible { outline: 2px solid var(--sk-teal); outline-offset: 2px; }
    .sk-ql .sk-bell:hover svg { animation: sk-ring .7s ease; transform-origin: 50% 4px; }
    @keyframes sk-ring { 0%, 100% { transform: rotate(0); } 20% { transform: rotate(14deg); } 40% { transform: rotate(-12deg); } 60% { transform: rotate(7deg); } 80% { transform: rotate(-4deg); } }
    .sk-ql .sk-badge { top: -3px; right: -4px; min-width: 18px; height: 18px; font-size: 10px; line-height: 1; background: #f43f5e; border: 2px solid #fff; box-shadow: 0 2px 6px -1px rgba(244,63,94,.5); }
    .sk-ql .sk-right { flex: 1 1 auto; min-width: 0; justify-content: flex-end; }
    @media (min-width: 1024px) and (max-width: 1109px) { .sk-ql .sk-session { display: none; } }
    @media (prefers-reduced-motion: reduce) { .sk-ql .sk-bell:hover svg { animation: none; } }
    @media (max-width: 1023px) {
        .sk-ql .sk-head { height: auto; min-height: 60px; flex-wrap: wrap; padding: 10px 14px; }
        .sk-clock { display: none; }
    }

    .sk-ql .sk-body { padding: 18px 20px 22px; }
    .sk-ql .sk-panel { background: transparent; border-radius: 0; padding: 0; overflow: visible; }
    .sk-ql .sk-panel::before { display: none; }
    .sk-ql .sk-tile { border-color: #eef1f0; box-shadow: 0 1px 2px rgba(12,38,32,.04), 0 10px 24px -18px rgba(12,38,32,.3);
        transition: transform .35s cubic-bezier(.2,.7,.2,1), box-shadow .35s, background-color .3s, border-color .3s; }
    .sk-ql .sk-tile:hover { transform: translateY(-6px); background: var(--tint, #dff3ee); border-color: transparent; box-shadow: 0 26px 44px -24px rgba(12,38,32,.45); }
    .sk-ql .sk-tile:hover .sk-tile-ic { background: #fff; color: var(--ink, #0f766e); transform: none; animation: sk-bounce .6s cubic-bezier(.2,.7,.2,1); box-shadow: 0 10px 20px -12px rgba(12,38,32,.45); }
    .sk-ql .sk-tile:hover span { color: var(--ink, #0f766e); }
    @keyframes sk-bounce { 0% { transform: scale(1); } 35% { transform: translateY(-4px) scale(1.1) rotate(-6deg); } 65% { transform: translateY(1px) scale(.97) rotate(3deg); } 100% { transform: none; } }
    @media (max-width: 639px) { .sk-ql .sk-body { padding: 12px 14px 18px; } }
    @media (prefers-reduced-motion: reduce) { .sk-ql .sk-tile:hover .sk-tile-ic { animation: none; } }

    /* ── Ask AI: the website's "Ask anything" pill, in the header; Enter opens
          the assistant as the website's popup with the question asked. ── */
    .sk-ask { display: flex; align-items: center; gap: 8px; flex: 0 1 240px; min-width: 170px; height: 36px; padding: 0 4px 0 12px; border-radius: 999px; background: #fff; border: 1px solid #e3e8e6; transition: border-color .2s, box-shadow .2s; }
    .sk-ask:focus-within { border-color: #b1e6db; box-shadow: 0 0 0 4px rgba(13,148,136,.12); }
    .sk-ask > img { width: 18px; height: 18px; object-fit: contain; flex: none; }
    .sk-ask input, .sk-ask input:focus { flex: 1; min-width: 0; height: 100%; padding: 0; border: 0; outline: 0; box-shadow: none; background: transparent; font-size: 13.5px; color: var(--sk-ink); }
    .sk-ask input::placeholder { color: #8e9b97; }
    .sk-ask button { width: 28px; height: 28px; border-radius: 50%; display: grid; place-items: center; flex: none; background: #eceff0; color: #6b7c76; transition: background .2s, color .2s; }
    .sk-ask button svg { width: 14px; height: 14px; }
    .sk-ask.has-text button { background: #0c2620; color: #fff; }
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

    .sa-home .bg-violet-300 { background-color: #99e0d3 !important; }
    .sa-home .hover\:bg-violet-700:hover { background-color: var(--sk-teal-700) !important; }
    .sa-home .focus\:ring-violet-400:focus, .sa-home .focus\:ring-violet-500:focus { --tw-ring-color: rgba(13,148,136,.35) !important; }
    /* The page's one dark card in the website's deep teal-ink (it was indigo). */
    .sa-home .sa-card-dark { background: radial-gradient(120% 120% at 100% 0%, #13665d 0%, #0c2f28 42%, #0a1f1b 78%); box-shadow: 0 18px 40px -20px rgba(12,38,32,.7); }

    /* ── Analytics: a chip for each functionality under the header ── */
    .an-chips { display: flex; gap: 8px; padding: 16px 24px 2px; overflow-x: auto; scrollbar-width: none; }
    .an-chips::-webkit-scrollbar { display: none; }
    .an-chip { display: inline-flex; align-items: center; gap: 7px; flex: none; height: 38px; padding: 0 16px; border-radius: 999px; border: 1px solid #e3e8e6; background: #fff; font-size: 13.5px; font-weight: 500; color: #44544f; white-space: nowrap; transition: background .2s, border-color .2s, color .2s, box-shadow .25s, transform .2s; }
    .an-chip svg { width: 16px; height: 16px; color: #8b9893; transition: color .2s; }
    .an-chip:hover { border-color: #b1e6db; color: #0c2620; transform: translateY(-1px); }
    .an-chip:hover svg { color: #0d9488; }
    .an-chip.on { background: #0d9488; border-color: #0d9488; color: #fff; box-shadow: 0 10px 22px -12px rgba(13,148,136,.75); }
    .an-chip.on svg { color: #fff; }
    @media (max-width: 639px) { .an-chips { padding: 12px 14px 2px; } }

    /* ── Analytics' extra detail (2026-11-19): a this-month-vs-last table,
          small lists with a figure on the right, a pill per status ── */
    .an-cmp { width: 100%; font-size: 13.5px; }
    .an-cmp th { padding: 8px 0; font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: #95a39e; text-align: right; }
    .an-cmp th:first-child { text-align: left; }
    .an-cmp td { padding: 9px 0; border-top: 1px solid #f0f3f2; text-align: right; color: var(--sk-ink); font-variant-numeric: tabular-nums; }
    .an-cmp td:first-child { text-align: left; color: #44544f; font-weight: 500; }
    .an-delta { display: inline-flex; align-items: center; gap: 2px; padding: 2px 8px; border-radius: 999px; font-size: 11.5px; font-weight: 600; white-space: nowrap; }
    .an-delta.up { background: #ecfdf5; color: #047857; }
    .an-delta.down { background: #fff1f2; color: #be123c; }
    .an-delta.flat { background: #f3f4f6; color: #6b7280; }
    .an-row { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-top: 1px solid #f0f3f2; min-width: 0; }
    .an-row:first-child { border-top: 0; }
    .an-av { width: 34px; height: 34px; border-radius: 11px; flex: none; display: grid; place-items: center; font-size: 12px; font-weight: 700; background: var(--sk-teal-50); color: var(--sk-teal-700); overflow: hidden; }
    .an-av img { width: 100%; height: 100%; object-fit: cover; }
    .an-tag { display: inline-flex; align-items: center; height: 22px; padding: 0 9px; border-radius: 999px; font-size: 11px; font-weight: 600; white-space: nowrap; background: #f3f4f6; color: #4b5563; }
    .an-tag.teal { background: var(--sk-teal-50); color: var(--sk-teal-700); }
    .an-tag.green { background: #ecfdf5; color: #047857; }
    .an-tag.amber { background: #fffbeb; color: #b45309; }
    .an-tag.rose { background: #fff1f2; color: #be123c; }
    .an-tag.blue { background: #eff6ff; color: #1d4ed8; }
    .an-meter { height: 8px; border-radius: 999px; background: #eef2f1; overflow: hidden; display: flex; }
    .an-meter > i { display: block; height: 100%; }

    @media (prefers-reduced-motion: reduce) { .sk-tile { animation: none; } .sk-pill.live::before { animation: none; } }
</style>
@endonce
