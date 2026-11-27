{{-- The sidebar's look (sa-* classes), shared by the Super Admin's sidebar
     and — in its exact style since 2026-11-19 — the school admin's: the
     website's product-screen panel, plain icons, the open page in teal on a
     soft pill, who is signed in at the foot. 226px, always open on md+.
     Scrollbars in the school panel's style:
     - the menu's scrollbar is a hairline;
     - every box that scrolls inside a page (slide-in panel bodies, lists,
       wide tables) gets the page's own slim 8px bar, darker on hover,
       instead of the browser's wide one. --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
<style>
    @media (min-width: 768px) {
        html.sidebar-expanded { --lms-sb: 226px; }
    }
    /* No shadow beside the menu: its hairline edge is enough, as on the website. */
    .lms-aside { box-shadow: none !important; }

    .sa-side {
        --sa-ink: #0c2620; --sa-text: #44544f; --sa-icon: #5f716b; --sa-line: #eef1f0; --sa-divider: #e3e9e7;
        --sa-teal: #0d9488; --sa-teal-50: #e4f6f2; --sa-on: #edf1f0; --sa-hover: #f3f6f5;
        font-family: 'Geist', 'Segoe UI', system-ui, -apple-system, sans-serif;
        background: #fbfcfc; border-right: 1px solid var(--sa-line); color: var(--sa-text);
    }

    /* Logo · SUPERLMS, centred, a little shorter (4px less); under it a divider
       across the sidebar's full width (user's ask of 2026-11-19). It is the
       pages' header line itself — the same width and colour (--sa-hw /
       --sa-hline, partials.super-admin-theme; thinner since 2026-11-27) and a
       border like theirs, so it is drawn on the same screen pixels. The row is
       exactly 60px, as those headers, so the two lines meet as one. */
    .sa-brand { position: relative; display: flex; align-items: center; justify-content: center; gap: 8px; height: 60px; padding: 0 18px; flex-shrink: 0; border-bottom: var(--sa-hw, 1px) solid var(--sa-hline, var(--sa-line)); }
    .sa-brand-link { display: flex; align-items: center; justify-content: center; gap: 10px; min-width: 0; }
    .sa-brand-link img { width: 28px; height: 28px; object-fit: contain; flex-shrink: 0; }
    .sa-brand-link span { font-size: 16px; font-weight: 700; letter-spacing: .01em; color: var(--sa-ink); white-space: nowrap; }

    /* The menu: plain icons (no tile behind them); the open page's name and
       icon both in teal, on a soft pill. */
    .sa-nav { display: flex; flex-direction: column; gap: 2px; padding: 8px 12px 14px; }
    .sa-link { display: flex; align-items: center; gap: 11px; padding: 8px 10px; border-radius: 10px; font-size: 13.5px; font-weight: 500; color: var(--sa-text); line-height: 1.25; transition: background .2s, color .2s; }
    .sa-ic { width: 18px; height: 18px; display: grid; place-items: center; flex-shrink: 0; color: var(--sa-icon); transition: color .2s; }
    .sa-ic svg { width: 18px; height: 18px; }
    .sa-ic svg * { stroke-width: 1.7px; }
    .sa-link:hover { background: var(--sa-hover); color: var(--sa-ink); }
    .sa-link:hover .sa-ic { color: var(--sa-ink); }
    .sa-link.on { background: var(--sa-teal-50); color: var(--sa-teal); font-weight: 600; }
    .sa-link.on .sa-ic { color: var(--sa-teal); }

    /* Who is signed in */
    .sa-user { display: flex; align-items: center; gap: 10px; margin: 0 12px; padding: 12px 6px 14px; border-top: 1px solid var(--sa-line); flex-shrink: 0; border-radius: 0; transition: color .2s; }
    .sa-user-link { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0; }
    .sa-user-link:hover .sa-user-name { color: var(--sa-teal); }
    /* A thin logout icon at the card's end (opens the panel's logout confirm). */
    .sa-logout { flex-shrink: 0; width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; color: #8b9893; transition: background .2s, color .2s; }
    .sa-logout:hover { color: #e11d48; background: #fff1f2; }
    .sa-logout svg { width: 17px; height: 17px; }
    .sa-av { width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center; background: #fff; border: 1px solid var(--sa-line); box-shadow: 0 1px 2px rgba(12,38,32,.06); flex-shrink: 0; overflow: hidden; }
    .sa-av img { width: 22px; height: 22px; object-fit: contain; }
    .sa-user-text { min-width: 0; line-height: 1.25; }
    .sa-user-name { display: block; font-size: 13px; font-weight: 600; color: var(--sa-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: color .2s; }
    .sa-user-text small { display: block; font-size: 11.5px; color: #8b9893; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    #sidebar-nav::-webkit-scrollbar { width: 3px; }
    #sidebar-nav::-webkit-scrollbar-track { background: transparent; }
    #sidebar-nav::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
    #sidebar-nav::-webkit-scrollbar-button { display: none; }

    #main-scroll :is(.overflow-y-auto, .overflow-x-auto, .overflow-auto)::-webkit-scrollbar { width: 8px; height: 8px; }
    #main-scroll :is(.overflow-y-auto, .overflow-x-auto, .overflow-auto)::-webkit-scrollbar-track { background: transparent; }
    #main-scroll :is(.overflow-y-auto, .overflow-x-auto, .overflow-auto)::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
    #main-scroll :is(.overflow-y-auto, .overflow-x-auto, .overflow-auto)::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
    #main-scroll :is(.overflow-y-auto, .overflow-x-auto, .overflow-auto)::-webkit-scrollbar-button { display: none; }
    #main-scroll :is(.overflow-y-auto, .overflow-x-auto, .overflow-auto)::-webkit-scrollbar-corner { background: transparent; }

    @supports not selector(::-webkit-scrollbar) {
        #sidebar-nav,
        #main-scroll :is(.overflow-y-auto, .overflow-x-auto, .overflow-auto) { scrollbar-width: thin; scrollbar-color: #d1d5db transparent; }
    }
</style>
