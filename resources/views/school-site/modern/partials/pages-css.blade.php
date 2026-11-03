{{--
    The inner pages the modern templates share (school-site/modern/*): their
    structure only. Colours, the cards' edges and the icon badges come from
    the layout's skin (see vibrant/ and breeze/ layout.blade.php), which also
    sets --c and --t (colour, tint) on each badge.
--}}
<style>
    .pg { padding: 40px 0 84px; }
    .pg-narrow { max-width: 900px; }
    .panel { background: var(--card); border-radius: var(--r-lg); padding: 36px; }
    .panel + .panel, .panel + .lms-card, .lms-card + .panel { margin-top: 24px; }
    .panel-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 10px 20px; margin-bottom: 22px; }
    .eyebrow { display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; border-radius: 999px; font-size: 11.5px; font-weight: 800; letter-spacing: .09em; text-transform: uppercase; }
    .eyebrow::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
    .h2 { text-wrap: balance; font-size: clamp(25px, 3vw, 38px); line-height: 1.16; font-weight: 800; color: var(--ink); letter-spacing: -.025em; margin-top: 12px; }
    .h3 { font-size: 19px; line-height: 1.3; font-weight: 800; color: var(--ink); letter-spacing: -.01em; }
    .lead-text { font-size: 16.5px; color: var(--muted); max-width: 640px; }
    .prose { color: var(--text); }
    .prose p { margin-bottom: 14px; }
    .prose p:last-child { margin-bottom: 0; }
    .prose h3 { font-size: 18px; font-weight: 800; color: var(--ink); margin: 24px 0 8px; }
    .sec-head { margin-bottom: 30px; }
    .sec-head.center { text-align: center; display: flex; flex-direction: column; align-items: center; }
    .sec-head.center .lead-text { margin: 10px auto 0; }

    .split { display: grid; grid-template-columns: minmax(0, 1.55fr) minmax(0, 1fr); gap: 24px; align-items: start; }
    .grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 22px; }
    .tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; }
    .tile { position: relative; overflow: hidden; background: var(--card); border-radius: var(--r-md); padding: 26px; }
    .tile .h3 { margin: 16px 0 8px; }
    .tile p { color: var(--text); }
    .badge-ico { width: 50px; height: 50px; border-radius: 16px; display: grid; place-items: center; flex: none; font-size: 24px; line-height: 1; }
    .badge-ico .ico { width: 24px; height: 24px; }
    .chips { display: flex; flex-wrap: wrap; gap: 8px; }
    .chip { display: inline-flex; align-items: center; gap: 6px; padding: 6px 13px; border-radius: 999px; font-size: 12.5px; font-weight: 700; }

    /* About */
    .about-side { display: flex; flex-direction: column; gap: 18px; }
    .about-side .ph { aspect-ratio: 4 / 3; border-radius: var(--r-lg); }
    .facts { list-style: none; display: grid; gap: 10px; }
    .facts li { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: var(--r-sm); background: var(--card); }
    .facts li div > span { color: var(--muted); font-size: 12.5px; font-weight: 600; display: block; line-height: 1.2; }
    .facts li b { color: var(--ink); font-size: 14.5px; display: block; line-height: 1.35; overflow-wrap: anywhere; }
    .facts .badge-ico { width: 40px; height: 40px; border-radius: 12px; }
    .facts .badge-ico .ico { width: 19px; height: 19px; }

    /* Leaders and desks */
    .leader { display: grid; grid-template-columns: 240px minmax(0, 1fr); gap: 34px; align-items: start; }
    .leader + .leader { margin-top: 24px; }
    .leader .ph, .desk .ph { aspect-ratio: 4 / 5; border-radius: var(--r-md); }
    .leader-role { font-size: 12px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: var(--primary); }
    .leader .h3 { font-size: 24px; margin: 4px 0 14px; }
    .quote-mark { width: 42px; height: 42px; border-radius: 14px; display: grid; place-items: center; margin-bottom: 12px; }
    .desk { display: grid; grid-template-columns: 260px minmax(0, 1fr); gap: 34px; align-items: start; }
    .side { display: grid; grid-template-columns: minmax(0, 1fr) 380px; gap: 32px; align-items: start; }
    .side .ph { aspect-ratio: 4 / 3; border-radius: var(--r-md); }
    .photos-head { font-size: 16px; font-weight: 800; color: var(--ink); margin: 30px 0 14px; }

    /* Team */
    .people { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 18px; }
    .person { background: var(--card); border-radius: var(--r-md); padding: 26px 16px 22px; text-align: center; transition: transform .25s, box-shadow .25s; }
    .person:hover { transform: translateY(-4px); }
    .person .ph { width: 118px; height: 118px; margin: 0 auto 16px; border-radius: 50%; }
    .person b { display: block; font-size: 15.5px; font-weight: 800; color: var(--ink); line-height: 1.35; overflow-wrap: anywhere; }
    .person .chip { margin-top: 8px; font-size: 11.5px; overflow-wrap: anywhere; }

    /* Facilities */
    .fac-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 22px; }
    .fac-card { display: flex; flex-direction: column; background: var(--card); border-radius: var(--r-lg); overflow: hidden; transition: transform .3s, box-shadow .3s; }
    .fac-card:hover { transform: translateY(-5px); }
    .fac-card:hover .ph > img { transform: scale(1.05); }
    .fac-card .ph { aspect-ratio: 16 / 10; }
    .fac-card .wm small { font-size: 44px; letter-spacing: 0; opacity: 1; }
    .fac-body { position: relative; padding: 0 24px 24px; flex: 1; }
    .fac-body .badge-ico { margin-top: -25px; position: relative; box-shadow: 0 0 0 5px var(--card); }
    .fac-body .h3 { margin: 14px 0 6px; }
    .fac-body p { color: var(--text); font-size: 14.5px; }

    /* Classes (Website Data's class cards) */
    .cls { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
    .cls article { background: var(--card); border-radius: var(--r-md); overflow: hidden; }
    .cls .ph { aspect-ratio: 16 / 10; }
    .cls .h3 { padding: 16px 18px 8px; font-size: 17px; }
    .cls dl { display: grid; grid-template-columns: auto 1fr; gap: 4px 12px; padding: 0 18px 18px; font-size: 13.5px; }
    .cls dt { color: var(--muted); }
    .cls dd { color: var(--ink); font-weight: 700; }

    /* Contact */
    .contact-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.35fr); gap: 24px; align-items: stretch; }
    .info-list { display: grid; gap: 14px; align-content: start; }
    .info-card { display: flex; gap: 14px; align-items: flex-start; padding: 18px; border-radius: var(--r-md); background: var(--card); }
    .info-card > div > span { display: block; font-size: 12px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); }
    .info-card b, .info-card a { display: block; color: var(--ink); font-size: 15px; font-weight: 700; line-height: 1.45; overflow-wrap: anywhere; }
    .info-card a:hover { color: var(--primary); }
    .map-card { border-radius: var(--r-lg); overflow: hidden; min-height: 320px; }
    .map-card .map { height: 100% !important; min-height: 320px; border: 0 !important; }
    .form-split { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr); gap: 24px; align-items: start; }
    .help-card { border-radius: var(--r-lg); padding: 30px; position: relative; overflow: hidden; }
    .help-card .h3 { margin: 14px 0 8px; }
    .help-card .info-list { margin-top: 18px; }

    /* Result */
    .bars { display: flex; align-items: flex-end; gap: 14px; height: 220px; padding: 8px 4px 0; margin-bottom: 26px; }
    .bars .bar { flex: 1; min-width: 0; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; gap: 8px; height: 100%; }
    .bars .bar i { display: block; width: 100%; max-width: 66px; border-radius: 16px 16px 8px 8px; min-height: 8px; }
    .bars .bar b { font-size: 13px; color: var(--ink); }
    .bars .bar span { font-size: 12px; color: var(--muted); font-weight: 700; }

    .nf { text-align: center; padding: 60px 24px; }
    .nf b { display: block; font-size: clamp(70px, 12vw, 130px); line-height: 1; font-weight: 800; letter-spacing: -.05em; }

    @media (max-width: 1020px) {
        .fac-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .split, .contact-grid, .form-split { grid-template-columns: minmax(0, 1fr); }
    }
    @media (max-width: 820px) {
        .leader, .desk, .side { grid-template-columns: minmax(0, 1fr); gap: 20px; }
        .leader .ph, .desk .ph { width: 100%; max-width: 260px; }
    }
    @media (max-width: 720px) {
        .pg { padding: 24px 0 56px; }
        .panel { padding: 22px 18px; }
        .grid-2 { grid-template-columns: minmax(0, 1fr); }
        .fac-grid { grid-template-columns: minmax(0, 1fr); gap: 16px; }
        .people { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .person { padding: 18px 10px 16px; }
        .person .ph { width: 86px; height: 86px; margin-bottom: 10px; }
        .person b { font-size: 14px; }
        .bars { height: 170px; gap: 8px; }
        .help-card { padding: 22px 18px; }
    }
</style>
