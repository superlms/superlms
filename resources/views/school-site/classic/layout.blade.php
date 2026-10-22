{{--
    Classic CBSE school-website template — master layout.

    Modelled on the plain, information-first sites CBSE schools publish: a
    green strip with the affiliation number and school code, a tricolour
    masthead (logo · school name, motto and medium · board seal), a white
    menu bar listing every page, white content cards on a grey page and a
    navy footer. Self-contained CSS; the school's theme colours the name, the
    strip and the accents. Receives: $site, $c, $theme, $nav, $current.
--}}
@php
    use App\Models\SchoolWebsite;
    use Illuminate\Support\Str;

    $link = fn ($slug) => $slug === 'home' ? url('/') : url($slug);
    // Groups that are one page link straight to it under the group's name
    // (HOME, FEATURES, MANDATORY DISCLOSURE); the rest open a dropdown.
    $single = array_keys(array_filter(SchoolWebsite::classicNavGroups(), fn ($s) => count($s) === 1));
    $board  = trim((string) ($c['board'] ?? ''));
    $socials = array_filter([
        'Facebook'  => $c['facebook'] ?? '',
        'YouTube'   => $c['youtube'] ?? '',
        'Instagram' => $c['instagram'] ?? '',
        'Twitter'   => $c['twitter'] ?? '',
        'Telegram'  => $c['telegram'] ?? '',
    ]);
    $logoUrl  = SchoolWebsite::media($c['logo'] ?? null);
    $boardUrl = SchoolWebsite::media($c['board_logo'] ?? null);
    $medium   = trim((string) ($c['medium'] ?? '')) ?: trim((string) ($c['tagline'] ?? ''));
    $tel      = preg_replace('/[^0-9+]/', '', explode(',', (string) ($c['phone'] ?? ''))[0]);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    {{-- A phone or tablet has the menu as a drawer from the first paint, not after the page's scripts. --}}
    <script>if (window.innerWidth <= 1020) document.documentElement.classList.add('nav-drawer');</script>
    <title>{{ isset($title) ? $title . ' – ' . $c['school_name'] : $c['school_name'] . ($medium ? ' – ' . $medium : '') }}</title>
    <meta name="description" content="{{ Str::limit(strip_tags($c['about_text'] ?? ''), 150) }}">
    @if($logoUrl)<link href="{{ $logoUrl }}" rel="icon">@endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: {{ $theme['primary'] }};
            --dark: {{ $theme['dark'] }};
            --light: {{ $theme['light'] }};
            --navy: #2D3C69;
            --navy2: #1E2A4D;
            --lime: #ADFF2F;
            --magenta: #E6007E;
            --orange: #FF8C00;
            --page: #F2F3F5;
            --line: #E4E5E8;
            --text: #333;
            --muted: #666;
            --wrap: 1240px;
        }
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { font-family: 'Poppins', Calibri, Arial, sans-serif; font-size: 14px; line-height: 1.75; color: var(--text); background: var(--page); overflow-x: hidden; }
        /* Nothing can make the page wider than the screen, whatever a school
           puts in it: clip (not hidden) cuts it off at the body, so a phone has
           nothing to slide sideways to, and the menu still sticks to the top.
           A browser without clip keeps the hidden above. */
        html, body { overflow-x: clip; }
        img { max-width: 100%; display: block; }
        iframe, video, embed, object { max-width: 100%; }
        a { color: inherit; text-decoration: none; }
        .wrap { max-width: var(--wrap); margin: 0 auto; padding: 0 20px; }

        /* ══════════ GREEN STRIP ══════════ */
        .strip { background: var(--dark); background: linear-gradient(90deg, var(--dark) 60%, color-mix(in srgb, var(--dark) 70%, #6b6b00) 100%); color: #fff; font-size: 12.5px; line-height: 1.4; }
        .strip-in { display: flex; justify-content: space-between; align-items: center; gap: 12px; min-height: 30px; padding-top: 5px; padding-bottom: 5px; }
        .strip-facts { font-weight: 600; letter-spacing: .2px; }
        .strip-links { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 0; font-weight: 700; }
        .strip-links a, .strip-links span { white-space: nowrap; }
        .strip-links > * + *::before { content: '|'; margin: 0 7px; font-weight: 400; opacity: .8; }
        .strip-links a:hover { text-decoration: underline; }

        /* ══════════ TRICOLOUR MASTHEAD ══════════ */
        .mast {
            position: relative; overflow: hidden;
            background:
                radial-gradient(ellipse 48% 130% at 0% 0%, rgba(255,153,51,.55), rgba(255,153,51,0) 70%),
                radial-gradient(ellipse 30% 90% at 22% 100%, rgba(255,196,0,.22), rgba(255,196,0,0) 70%),
                radial-gradient(ellipse 34% 110% at 100% 0%, rgba(255,153,51,.30), rgba(255,153,51,0) 70%),
                radial-gradient(ellipse 40% 120% at 100% 100%, rgba(19,136,8,.26), rgba(19,136,8,0) 70%),
                #FFFDF7;
        }
        .mast-wheel { position: absolute; left: 50%; top: 50%; width: 330px; height: 330px; transform: translate(-50%, -50%); color: var(--navy); opacity: .06; pointer-events: none; }
        .mast-in { position: relative; display: grid; grid-template-columns: 110px 1fr 110px; align-items: center; gap: 20px; padding-top: 14px; padding-bottom: 14px; }
        .mast-logo, .mast-board { display: flex; align-items: center; justify-content: center; height: 104px; }
        .mast-logo img, .mast-board img { max-height: 104px; width: auto; object-fit: contain; }
        .mast-logo svg, .mast-board svg { width: 100px; height: 100px; }
        .mast-text { text-align: center; min-width: 0; }
        /* The school's name on one line: a long one is made smaller to fit
           (the script below), and wraps only on a phone too narrow for it. */
        .mast-name {
            display: block; color: var(--primary); font-weight: 800; text-transform: uppercase;
            font-size: clamp(24px, 4.3vw, 58px); line-height: 1.08; letter-spacing: .06em;
            text-shadow: 0 1px 0 rgba(255,255,255,.6); white-space: nowrap;
        }
        .mast-motto { margin-top: 8px; font-weight: 700; font-size: clamp(12px, 1.25vw, 15px); color: #222; line-height: 1.35; }
        .mast-medium { font-weight: 600; font-size: clamp(11px, 1.05vw, 13.5px); color: #444; line-height: 1.35; }

        /* ══════════ MENU BAR ══════════ */
        .menu { position: sticky; top: 0; z-index: 60; background: #fff; border-top: 1px solid var(--line); box-shadow: 0 2px 10px rgba(0,0,0,.07); }
        .menu-bar { display: flex; align-items: center; }
        .menu-panel { flex: 1; min-width: 0; }
        .menu-brand, .menu-toggle, .menu-shade, .menu-panel-head, .menu-panel-foot { display: none; }
        .menu-list { list-style: none; display: flex; flex-wrap: wrap; justify-content: center; }
        .menu-list > li { position: relative; }
        .menu-list > li > a, .menu-list > li > button {
            display: flex; align-items: center; gap: 5px; padding: 11px 11px; background: none; border: 0; cursor: pointer;
            font: 400 12px/1.2 'Poppins', sans-serif; text-transform: uppercase; color: #222; letter-spacing: .02em; white-space: nowrap;
        }
        .menu-list > li > a:hover, .menu-list > li > button:hover, .menu-list > li:hover > button { color: var(--primary); }
        .menu-list > li.is-active > a, .menu-list > li.is-active > button { color: #8D8D8D; }
        .menu-list .chev { width: 9px; height: 9px; transition: transform .2s; }
        .drop {
            list-style: none; position: absolute; top: 100%; left: 0; min-width: 240px; max-height: 72vh; overflow-y: auto;
            background: #fff; border-top: 3px solid var(--primary); box-shadow: 0 10px 28px rgba(0,0,0,.14);
            opacity: 0; visibility: hidden; transform: translateY(6px); transition: opacity .18s, transform .18s, visibility .18s; z-index: 70;
        }
        .menu-list > li:hover > .drop, .menu-list > li:focus-within > .drop { opacity: 1; visibility: visible; transform: none; }
        .menu-list > li:nth-last-child(-n+3) > .drop { left: auto; right: 0; }
        .drop a { display: block; padding: 9px 16px; font-size: 12.5px; color: #333; border-bottom: 1px solid #F1F1F1; line-height: 1.45; }
        .drop li:last-child a { border-bottom: 0; }
        .drop a:hover, .drop a.active { background: var(--light); color: var(--primary); }
        @media (max-width: 1300px) { .menu-list > li > a, .menu-list > li > button { padding-left: 8px; padding-right: 8px; } }

        /* ── The menu as a drawer: on a phone or tablet, and on any screen its
           groups do not fit on one row of (the script after the menu decides,
           html.nav-drawer). The bar shows the school and a Menu button; the
           menu slides in from the right over a dimmed page, with the school
           at its head, each group opening under itself (the current page's
           group already open) and Call / Login at its foot. ── */
        .nav-drawer .menu-bar { justify-content: space-between; gap: 12px; min-height: 54px; }
        .nav-drawer .menu-brand { display: flex; align-items: center; gap: 10px; min-width: 0; font-weight: 700; font-size: 13px; line-height: 1.2; letter-spacing: .04em; text-transform: uppercase; color: var(--primary); }
        .mb-logo { flex: none; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; }
        .mb-logo img, .mb-logo svg { max-width: 36px; max-height: 36px; width: auto; height: auto; object-fit: contain; }
        .mb-logo svg { width: 36px; height: 36px; }
        .nav-drawer .menu-brand > span:last-child { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        /* The school in the bar only once the masthead above has scrolled away. */
        .nav-drawer .menu-brand { opacity: 0; visibility: hidden; transform: translateY(-4px); transition: opacity .25s, transform .25s, visibility .25s; }
        .nav-drawer .menu.stuck .menu-brand { opacity: 1; visibility: visible; transform: none; }
        .nav-drawer .menu-toggle { display: inline-flex; flex: none; align-items: center; gap: 10px; padding: 10px 2px; background: none; border: 0; font: 600 12px 'Poppins', sans-serif; letter-spacing: .12em; text-transform: uppercase; color: #222; cursor: pointer; }
        .menu-toggle .bars { position: relative; width: 24px; height: 16px; }
        .menu-toggle .bars i { position: absolute; left: 0; width: 100%; height: 2px; border-radius: 2px; background: var(--primary); transition: top .3s, transform .3s, opacity .2s; }
        .menu-toggle .bars i:nth-child(1) { top: 0; }
        .menu-toggle .bars i:nth-child(2) { top: 7px; }
        .menu-toggle .bars i:nth-child(3) { top: 14px; }
        .menu.open .menu-toggle .bars i:nth-child(1) { top: 7px; transform: rotate(45deg); }
        .menu.open .menu-toggle .bars i:nth-child(2) { opacity: 0; }
        .menu.open .menu-toggle .bars i:nth-child(3) { top: 7px; transform: rotate(-45deg); }
        .nav-drawer .menu.open { z-index: 150; }
        /* The drawer and its shade live in a layer the size of the window that
           clips them, so the drawer waiting off the right edge never makes the
           page wider; on a wide screen the layer is no box at all. Slides only
           once the page has opened (html.nav-anim), never while it loads. */
        .menu-layer { display: contents; }
        .nav-drawer .menu-layer { display: block; position: fixed; inset: 0; overflow: hidden; pointer-events: none; visibility: hidden; }
        .nav-drawer .menu.open .menu-layer { pointer-events: auto; visibility: visible; }
        .nav-drawer .menu-shade { display: block; position: absolute; inset: 0; background: rgba(15,18,30,.5); -webkit-backdrop-filter: blur(2px); backdrop-filter: blur(2px); opacity: 0; }
        .nav-drawer .menu.open .menu-shade { opacity: 1; }
        .nav-drawer .menu-panel {
            position: absolute; top: 0; right: 0; bottom: 0; width: min(86vw, 360px); display: flex; flex-direction: column;
            background: #fff; box-shadow: -14px 0 40px rgba(0,0,0,.18); transform: translateX(105%);
        }
        .nav-drawer .menu.open .menu-panel { transform: none; }
        .nav-anim.nav-drawer .menu-layer { transition: visibility .38s; }
        .nav-anim.nav-drawer .menu-shade { transition: opacity .3s; }
        .nav-anim.nav-drawer .menu-panel { transition: transform .38s cubic-bezier(.16,1,.3,1); }
        .nav-drawer .menu-panel-head { display: flex; align-items: center; gap: 12px; padding: 14px 12px 14px 18px; background: var(--light); border-bottom: 3px solid var(--primary); }
        .menu-panel-head b { flex: 1; min-width: 0; font-size: 13.5px; line-height: 1.3; letter-spacing: .03em; text-transform: uppercase; color: var(--primary); }
        .menu-close { flex: none; width: 38px; height: 38px; border: 0; border-radius: 50%; background: #fff; color: #333; font-size: 24px; line-height: 1; cursor: pointer; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
        .nav-drawer .menu-list { display: block; flex: 1; overflow-y: auto; overscroll-behavior: contain; padding: 4px 0 10px; }
        .nav-drawer .menu-list > li > a, .nav-drawer .menu-list > li > button { width: 100%; justify-content: space-between; padding: 14px 18px; border-bottom: 1px solid #F1F1F3; font-size: 13px; font-weight: 500; letter-spacing: .03em; }
        .nav-drawer .menu-list > li.is-active > a, .nav-drawer .menu-list > li.is-active > button { color: var(--primary); box-shadow: inset 3px 0 0 var(--primary); }
        .nav-drawer .menu-list .chev { width: 12px; height: 12px; }
        .nav-drawer .drop { position: static; display: none; min-width: 0; max-height: none; opacity: 1; visibility: visible; transform: none; box-shadow: none; border-top: 0; background: #FAFAFB; }
        .nav-drawer .menu-list > li.open > .drop { display: block; animation: drop-in .25s ease; }
        .nav-drawer .menu-list > li.open .chev { transform: rotate(180deg); }
        .nav-drawer .drop a { padding: 12px 18px 12px 32px; font-size: 13px; }
        .nav-drawer .drop a.active { font-weight: 600; }
        .nav-drawer .menu-panel-foot { display: flex; gap: 10px; padding: 14px 16px calc(14px + env(safe-area-inset-bottom)); border-top: 1px solid var(--line); }
        .menu-panel-foot .btn { flex: 1; padding: 11px 12px; }
        @keyframes drop-in { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }
        html.menu-lock, html.menu-lock body { overflow: hidden; }

        /* ══════════ PAGE + CARDS ══════════ */
        main { min-height: 40vh; }
        .page { padding: 34px 0 56px; }
        .card { background: #fff; border: 1px solid var(--line); box-shadow: 0 1px 3px rgba(0,0,0,.04); padding: 30px 32px; }
        .card + .card { margin-top: 24px; }
        .card h2.title { color: var(--magenta); font-size: clamp(18px, 2vw, 23px); font-weight: 700; line-height: 1.3; }
        .card .lead { font-weight: 700; font-size: 13px; color: #222; margin-top: 4px; }
        .sec-head { text-align: center; margin-bottom: 22px; }
        .sec-head h2 { color: var(--muted); font-size: clamp(19px, 2vw, 23px); font-weight: 600; }
        .sec-head p { font-weight: 700; font-size: 13px; color: #222; }
        .prose p { margin-bottom: 14px; color: #444; }
        .prose p:last-child { margin-bottom: 0; }
        .prose h3 { font-size: 16px; color: #222; margin: 18px 0 8px; }
        .prose ul, .prose ol { padding-left: 22px; margin-bottom: 14px; color: #444; }
        .muted-note { text-align: center; color: var(--muted); padding: 26px 10px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 9px 20px; border: 0; cursor: pointer; font: 600 13px 'Poppins', sans-serif; color: #fff; background: var(--primary); border-radius: 3px; transition: filter .2s, transform .2s; }
        .btn:hover { filter: brightness(.92); transform: translateY(-1px); }
        .btn-dark { background: #111; }
        .btn-ghost { background: #fff; color: var(--primary); border: 1px solid currentColor; }

        /* ══════════ PHOTO PLACEHOLDER / WATERMARK ══════════ */
        .ph { position: relative; overflow: hidden; background: #EDEEF1; }
        .ph > img { width: 100%; height: 100%; object-fit: cover; }
        .ph .wm { display: none; }
        .ph.is-mark .wm { display: flex; }
        .wm {
            position: absolute; inset: 0; align-items: center; justify-content: center; flex-direction: column; gap: 8px;
            background: repeating-linear-gradient(45deg, #F5F5F7 0 14px, #EFEFF2 14px 28px);
            color: #9AA0AE; text-align: center; padding: 12px;
        }
        .wm::before { content: ''; position: absolute; inset: 0; background-image: var(--wm-tile, none); background-size: 70px; background-repeat: repeat; opacity: .06; }
        .wm img, .wm svg { position: relative; max-width: 46%; max-height: 62%; width: auto; height: auto; opacity: .2; filter: grayscale(.3); }
        .wm svg { width: 46%; aspect-ratio: 1; color: var(--navy); }
        .wm small { position: relative; font-size: 11px; font-weight: 600; letter-spacing: .14em; text-transform: uppercase; opacity: .8; }

        /* ══════════ INNER-PAGE BANNER ══════════ */
        /* A slim strip — under half its old height (it was up to 300px). */
        .banner { position: relative; height: clamp(84px, 9vw, 130px); overflow: hidden; background: var(--navy); }
        .banner > img { width: 100%; height: 100%; object-fit: cover; }
        .banner::after { content: ''; position: absolute; inset: 0; background: rgba(0,0,0,.28); }
        .banner .wm { background: linear-gradient(135deg, var(--dark) 0%, var(--navy2) 100%); color: rgba(255,255,255,.5); }
        .banner .wm::before { opacity: .07; }
        .banner .wm img, .banner .wm svg { opacity: .16; max-height: 80%; color: #fff; }
        .banner h1 { position: absolute; z-index: 2; inset: 0; display: flex; align-items: center; justify-content: center; text-align: center; padding: 0 20px; color: #fff; text-transform: uppercase; font-weight: 700; font-size: clamp(17px, 2vw, 24px); letter-spacing: .02em; text-shadow: 2px 2px 4px rgba(0,0,0,.55); }

        /* ══════════ FORMS ══════════ */
        .sform-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 18px; }
        .sform label { display: flex; flex-direction: column; gap: 5px; font-size: 12.5px; font-weight: 600; color: #333; }
        .sform label.wide { grid-column: 1 / -1; }
        .sform label b { color: var(--primary); }
        .sform input, .sform select, .sform textarea { width: 100%; padding: 9px 11px; border: 1px solid #CFD2D8; border-radius: 3px; font: 400 13.5px 'Poppins', sans-serif; color: #222; background: #fff; }
        .sform input:focus, .sform select:focus, .sform textarea:focus { outline: 2px solid color-mix(in srgb, var(--primary) 35%, transparent); border-color: var(--primary); }
        .sform .hp { display: none; }
        .sform-foot { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; margin-top: 16px; }
        .sform-msg { font-size: 13px; font-weight: 600; }

        /* ══════════ TABLES ══════════ */
        .tbl-wrap { overflow-x: auto; }
        .tbl { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        .tbl th { background: var(--navy); color: #fff; text-align: left; font-weight: 600; padding: 10px 14px; border: 1px solid var(--navy); white-space: nowrap; }
        .tbl td { padding: 10px 14px; border: 1px solid var(--line); vertical-align: middle; }
        .tbl tbody tr:nth-child(even) td { background: #FAFAFB; }
        .tbl tbody tr:hover td { background: var(--light); }

        /* A PDF on a phone: a tile that opens it (see document.blade.php). */
        .doc-tap { display: none; flex-direction: column; align-items: center; gap: 6px; padding: 26px 16px; border: 1px dashed color-mix(in srgb, var(--primary) 45%, var(--line)); border-radius: 10px; background: var(--light); color: var(--primary); text-align: center; }
        .doc-tap b { color: #222; font-size: 15px; line-height: 1.35; }
        .doc-tap span { font-size: 12.5px; color: var(--muted); }

        /* ══════════ PHOTO GRID + LIGHTBOX ══════════ */
        .photos { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; }
        .photos figure { background: #fff; border: 1px solid var(--line); }
        .photos .ph { aspect-ratio: 4 / 3; cursor: zoom-in; }
        .photos figcaption { padding: 7px 10px; font-size: 12.5px; color: #444; text-align: center; }
        .lb { position: fixed; inset: 0; z-index: 200; background: rgba(10,12,20,.92); display: none; align-items: center; justify-content: center; padding: 50px 60px; }
        .lb.open { display: flex; }
        .lb img { max-width: 100%; max-height: 100%; object-fit: contain; box-shadow: 0 10px 40px rgba(0,0,0,.5); }
        .lb button { position: absolute; background: rgba(255,255,255,.12); color: #fff; border: 0; width: 44px; height: 44px; border-radius: 50%; font-size: 22px; cursor: pointer; }
        .lb button:hover { background: rgba(255,255,255,.25); }
        .lb .lb-x { top: 14px; right: 14px; }
        .lb .lb-p { left: 12px; top: 50%; transform: translateY(-50%); }
        .lb .lb-n { right: 12px; top: 50%; transform: translateY(-50%); }

        /* ══════════ FOOTER ══════════ */
        .foot {
            color: rgba(255,255,255,.88); font-size: 13px;
            background-color: var(--navy);
            background-image:
                linear-gradient(rgba(255,255,255,.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.035) 1px, transparent 1px),
                linear-gradient(90deg, var(--navy) 60%, var(--navy2) 100%);
            background-size: 26px 26px, 26px 26px, 100% 100%;
        }
        .foot-grid { display: grid; grid-template-columns: 1.15fr 1.25fr .8fr; gap: 40px; padding-top: 48px; padding-bottom: 36px; }
        .foot h2 { font-size: 11px; font-weight: 700; letter-spacing: .18em; text-transform: uppercase; color: #fff; margin-bottom: 16px; }
        .foot p { margin-bottom: 6px; line-height: 1.7; }
        .foot b { color: #fff; }
        .foot-social { margin-top: 14px; font-weight: 700; color: #fff; }
        .foot-social > * + *::before { content: '|'; margin: 0 7px; font-weight: 400; opacity: .7; }
        .foot-social a:hover, .foot-links a:hover { text-decoration: underline; }
        .foot-links { list-style: none; columns: 2; column-gap: 18px; }
        .foot-links li { margin-bottom: 6px; break-inside: avoid; }
        .foot-bottom { border-top: 1px solid rgba(255,255,255,.12); padding-top: 16px; padding-bottom: 18px; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px; font-size: 12.5px; }
        .foot-bottom a { font-weight: 700; color: #fff; }

        .to-top { position: fixed; right: 18px; bottom: 18px; width: 42px; height: 42px; border-radius: 4px; background: var(--primary); color: #fff; display: none; align-items: center; justify-content: center; z-index: 80; box-shadow: 0 6px 18px rgba(0,0,0,.2); font-size: 18px; }
        .to-top.show { display: flex; }

        /* ══════════ SCROLL ANIMATIONS ══════════
           As on the SUPERLMS site: blocks tilt up out of the page as they
           scroll into view, a row of them one after another (the script at
           the foot tags them; a block already on screen is left as it is).
           On a phone or tablet a block only fades in with a short rise
           (ed-soft) — nothing tilts, and a block that fills the screen is
           not moved at all, so the page never seems to slide under a finger.
           The transition is on ed-shown alone: a block being tagged goes
           out of sight at once instead of fading away first. */
        .ed-anim-on { opacity: 0; }
        .ed-anim-on.ed-up    { transform: perspective(1400px) rotateX(14deg) translateY(46px) translateZ(-60px); transform-origin: center top; }
        .ed-anim-on.ed-left  { transform: perspective(1400px) rotateY(-18deg) translateX(-60px) translateZ(-60px); transform-origin: left center; }
        .ed-anim-on.ed-right { transform: perspective(1400px) rotateY(18deg) translateX(60px) translateZ(-60px); transform-origin: right center; }
        .ed-anim-on.ed-zoom  { transform: perspective(1400px) rotateX(16deg) scale(.9) translateY(34px); transform-origin: center top; }
        .ed-anim-on.ed-flip  { transform: perspective(1400px) rotateX(34deg) translateY(40px); transform-origin: center top; }
        .ed-anim-on.ed-soft  { transform: translateY(18px); }
        .ed-anim-on.ed-shown {
            opacity: 1; transform: perspective(1400px) rotateX(0) rotateY(0) translate3d(0,0,0) scale(1);
            transition: opacity .85s cubic-bezier(.16,1,.3,1), transform .85s cubic-bezier(.16,1,.3,1);
            transition-delay: var(--ed-delay, 0s);   /* a row's cards one after another */
        }
        .ed-anim-on.ed-soft.ed-shown { transform: none; transition-duration: .5s; }
        /* The page's title rises in as the page opens. */
        .banner h1 { animation: title-in .9s cubic-bezier(.16,1,.3,1) both .1s; }
        @keyframes title-in { from { opacity: 0; transform: perspective(900px) rotateX(40deg) translateY(24px); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) {
            .ed-anim-on { opacity: 1 !important; transform: none !important; transition: none !important; }
            .banner h1 { animation: none; }
        }

        /* ══════════ RESPONSIVE ══════════ */
        @media (max-width: 1020px) {
            .foot-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 720px) {
            .strip { font-size: 12px; }
            .strip-in { flex-direction: column; justify-content: center; text-align: center; gap: 1px; }
            .strip-links { justify-content: center; gap: 0 16px; }
            .strip-links > * + *::before { display: none; }
            .strip-links .s-soc { display: none; }   /* in the footer — the strip keeps the phone and Login */
            .mast-in { grid-template-columns: 64px 1fr 56px; gap: 10px; padding-top: 10px; padding-bottom: 10px; }
            .mast-logo, .mast-board { height: 64px; }
            .mast-logo img { max-height: 64px; }
            .mast-board img { max-height: 56px; }
            .mast-logo svg { width: 62px; height: 62px; }
            .mast-board svg { width: 54px; height: 54px; }
            .mast-wheel { width: 200px; height: 200px; }
            .mast-motto { margin-top: 4px; }
            .mast-name { white-space: normal; }   /* until the script fits it: wrapped, never cut off */
            .card { padding: 22px 18px; }
            .card + .card { margin-top: 16px; }
            .page { padding: 20px 0 40px; }
            .sform-grid { grid-template-columns: 1fr; }
            /* 16px: a phone zooms in on a smaller box when it is tapped. */
            .sform input, .sform select, .sform textarea, .lms-card .lms-form input, .lms-card .lms-form select, .lms-card .lms-form textarea { font-size: 16px; }
            .sform-foot .btn { flex: 1; padding: 12px 20px; }
            .foot-grid { grid-template-columns: 1fr; gap: 26px; padding-top: 36px; padding-bottom: 26px; }
            .foot-bottom { flex-direction: column; text-align: center; padding-bottom: 76px; }
            .wrap { padding: 0 16px; }
            .photos { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
            .photos figcaption { padding: 5px 6px; font-size: 11.5px; line-height: 1.35; }
            .lb { padding: 60px 8px; }
            .lb button { width: 38px; height: 38px; font-size: 19px; }
            .lb .lb-p, .lb .lb-n { top: auto; bottom: 12px; transform: none; }
            .lb .lb-p { left: calc(50% - 46px); }
            .lb .lb-n { right: calc(50% - 46px); }
            .map { height: 300px !important; }
            .doc-embed { display: none; }
            .doc-tap { display: flex; }
            .to-top { right: 14px; bottom: 14px; width: 40px; height: 40px; }
        }

        /* ══════════ TABLES ON A PHONE ══════════
           A row becomes a card: its title (the bold column) on top, then each
           value under the column's name (data-label, set by the script from
           the table's head). Nothing to swipe sideways. */
        @media (max-width: 640px) {
            .tbl-wrap, .lms-scroll { overflow: visible; }
            .tbl thead, .lms-tbl thead { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
            .tbl, .tbl tbody, .lms-tbl, .lms-tbl tbody { display: block; width: 100%; }
            .tbl tr, .lms-tbl tr { display: flex; flex-direction: column; background: #fff; border: 1px solid var(--line); border-radius: 10px; padding: 8px 14px; margin-bottom: 10px; box-shadow: 0 1px 2px rgba(16,24,40,.04); }
            .lms-tbl tr { margin: 10px 12px; }
            .lms-tbl tbody tr:first-child { margin-top: 12px; }
            .tbl td, .lms-tbl td {
                display: flex; justify-content: space-between; align-items: baseline; gap: 14px; width: auto !important; min-width: 0 !important;
                padding: 6px 0 !important; border: 0 !important; background: none !important; text-align: right !important; white-space: normal !important; overflow-wrap: anywhere;
            }
            .tbl td::before, .lms-tbl td::before { content: attr(data-label); flex: none; max-width: 48%; text-align: left; font-size: 10.5px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: #9CA3AF; }
            .tbl td:not([data-label]), .lms-tbl td:not([data-label]), .tbl td[data-label=""], .lms-tbl td[data-label=""] { justify-content: flex-start; text-align: left !important; }
            .tbl td:not([data-label])::before, .lms-tbl td:not([data-label])::before { content: none; }
            .tbl td.sno, .lms-tbl td.sno { display: none; }
            .tbl td.strong, .lms-tbl td.strong { order: -1; justify-content: flex-start; text-align: left !important; font-size: 15px; font-weight: 600; color: #111827; padding: 6px 0 9px !important; margin-bottom: 3px; border-bottom: 1px solid #F1F2F4 !important; }
            .tbl td.strong::before, .lms-tbl td.strong::before { content: none; }
            .tbl td.desc, .lms-tbl td.desc { flex-direction: column; align-items: flex-start; gap: 2px; text-align: left !important; }
            .tbl td.desc::before, .lms-tbl td.desc::before { max-width: none; }
            .tbl td.muted-note { justify-content: center; text-align: center !important; }
            .tbl td.muted-note::before { content: none; }
        }
    </style>
    @stack('styles')
</head>
<body>

    {{-- ══════════ GREEN STRIP ══════════ --}}
    <div class="strip">
        <div class="wrap strip-in">
            <div class="strip-facts">
                @if (!empty($c['affiliation_no'])){{ $board ?: 'CBSE' }} Aff. No. {{ $c['affiliation_no'] }}@endif
                @if (!empty($c['affiliation_no']) && !empty($c['school_code'])) | @endif
                @if (!empty($c['school_code']))School Code: {{ $c['school_code'] }}@endif
                @if (empty($c['affiliation_no']) && empty($c['school_code'])){{ $c['address'] ?: $c['school_name'] }}@endif
            </div>
            <div class="strip-links">
                @foreach ($socials as $label => $url)
                    <a href="{{ $url }}" target="_blank" rel="noopener" class="s-soc">{{ $label }}</a>
                @endforeach
                @if (!empty($c['phone']))
                    <a href="tel:{{ $tel }}">{{ $c['phone'] }}</a>
                @endif
                <a href="{{ $site->adminLoginUrl() }}" target="_blank" rel="noopener">Login</a>
            </div>
        </div>
    </div>

    {{-- ══════════ MASTHEAD ══════════ --}}
    <header class="mast">
        <svg class="mast-wheel" viewBox="0 0 100 100" aria-hidden="true">
            <circle cx="50" cy="50" r="46" fill="none" stroke="currentColor" stroke-width="3"/>
            <circle cx="50" cy="50" r="7" fill="currentColor"/>
            @for ($i = 0; $i < 24; $i++)
                <line x1="50" y1="50" x2="{{ 50 + 44 * cos(deg2rad($i * 15)) }}" y2="{{ 50 + 44 * sin(deg2rad($i * 15)) }}" stroke="currentColor" stroke-width="1.4"/>
            @endfor
        </svg>
        <div class="wrap mast-in">
            <a href="{{ url('/') }}" class="mast-logo" aria-label="{{ $c['school_name'] }} — Home">
                @include('school-site.classic.partials.logo', ['sealId' => 'logo'])
            </a>
            <div class="mast-text">
                <a href="{{ url('/') }}" class="mast-name">{{ $c['school_name'] }}</a>
                @if (!empty($c['motto']))<p class="mast-motto">"{{ trim($c['motto'], " \"“”") }}"</p>@endif
                @if ($medium)<p class="mast-medium">{{ $medium }}</p>@endif
            </div>
            <div class="mast-board">
                @if ($boardUrl)
                    <img src="{{ $boardUrl }}" alt="{{ $board ?: 'Board' }} logo">
                @elseif ($board)
                    @include('school-site.classic.partials.seal', ['center' => $board, 'ring' => 'Affiliated to ' . $board, 'sealId' => 'board'])
                @endif
            </div>
        </div>
    </header>

    {{-- ══════════ MENU ══════════ --}}
    <nav class="menu" id="menu" aria-label="Main">
        <div class="wrap menu-bar">
            <a href="{{ url('/') }}" class="menu-brand" aria-label="{{ $c['school_name'] }} — Home">
                <span class="mb-logo">@include('school-site.classic.partials.logo', ['sealId' => 'bar'])</span>
                <span>{{ $c['school_name'] }}</span>
            </a>
            <button class="menu-toggle" type="button" id="menuToggle" aria-expanded="false" aria-controls="menuPanel">
                Menu <span class="bars" aria-hidden="true"><i></i><i></i><i></i></span>
            </button>
            <div class="menu-layer">
            <div class="menu-shade" data-menu-close></div>
            <div class="menu-panel" id="menuPanel">
            <div class="menu-panel-head">
                <span class="mb-logo">@include('school-site.classic.partials.logo', ['sealId' => 'drawer'])</span>
                <b>{{ $c['school_name'] }}</b>
                <button type="button" class="menu-close" data-menu-close aria-label="Close menu">&times;</button>
            </div>
            <ul class="menu-list" id="menuList">
                @foreach ($nav as $group => $items)
                    @php $isActive = array_key_exists($current, $items); @endphp
                    @if (count($items) === 1 && in_array($group, $single, true))
                        @php $slug = array_key_first($items); @endphp
                        <li class="{{ $isActive ? 'is-active' : '' }}"><a href="{{ $link($slug) }}">{{ $group }}</a></li>
                    @else
                        <li class="has-drop {{ $isActive ? 'is-active' : '' }}">
                            <button type="button" aria-haspopup="true">
                                {{ $group }}
                                <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            <ul class="drop">
                                @foreach ($items as $slug => $label)
                                    <li><a href="{{ $link($slug) }}" class="{{ $current === $slug ? 'active' : '' }}">{{ $label }}</a></li>
                                @endforeach
                            </ul>
                        </li>
                    @endif
                @endforeach
            </ul>
            <div class="menu-panel-foot">
                @if ($tel !== '')<a class="btn" href="tel:{{ $tel }}">Call Us</a>@endif
                <a class="btn btn-ghost" href="{{ $site->adminLoginUrl() }}" target="_blank" rel="noopener">Login</a>
            </div>
            </div>
            </div>
        </div>
    </nav>
    <script>
    // The menu is a drawer on a phone or tablet, and on any screen its groups
    // do not fit on one row of (html.nav-drawer) — decided as soon as the
    // menu is drawn, and again when the window or the fonts change it.
    (function () {
        var html = document.documentElement, list = document.getElementById('menuList');
        window.__fitMenu = function () {
            var drawer = window.innerWidth <= 1020;
            if (!drawer && list && list.children.length > 1) {
                // Measured as one row; a phone never needs to be, so its menu
                // is never taken out of the drawer, even for a moment.
                html.classList.remove('nav-drawer');
                var k = list.children;
                drawer = k[k.length - 1].offsetTop > k[0].offsetTop + 4;
            }
            if (html.classList.contains('nav-drawer') !== drawer) html.classList.toggle('nav-drawer', drawer);
            return drawer;
        };
        window.__fitMenu();
    })();
    </script>

    <main>
        @yield('content')
    </main>

    {{-- ══════════ FOOTER ══════════ --}}
    <footer class="foot">
        <div class="wrap foot-grid">
            <div>
                <h2>Contact Details</h2>
                @if (!empty($c['address']))<p><b>Address:</b> {{ $c['address'] }}</p>@endif
                @if (!empty($c['email']))<p><b>Email ID:</b> <a href="mailto:{{ $c['email'] }}">{{ $c['email'] }}</a></p>@endif
                @if (!empty($c['phone']))<p><b>Contact:</b> {{ $c['phone'] }}</p>@endif
                @if ($socials)
                    <div class="foot-social">
                        @foreach ($socials as $label => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener">{{ $label }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
            <div>
                <h2>{{ $c['school_name'] }}</h2>
                <p>{{ Str::limit(strip_tags($c['about_text'] ?? ''), 300) }}</p>
            </div>
            <div>
                <h2>Quick Links</h2>
                <ul class="foot-links">
                    @foreach (['home', 'about', 'admission', 'facilities', 'gallery', 'disclosures', 'result', 'contact'] as $slug)
                        @if ($site->isPageEnabled($slug))
                            <li><a href="{{ $link($slug) }}">{{ SchoolWebsite::allPages()[$slug] }}</a></li>
                        @endif
                    @endforeach
                    <li><a href="{{ $site->adminLoginUrl() }}" target="_blank" rel="noopener">Staff Login</a></li>
                </ul>
            </div>
        </div>
        <div class="wrap foot-bottom">
            <span>&copy; {{ date('Y') }} {{ $c['school_name'] }}. All rights reserved.</span>
            <span>Website by <a href="https://superlms.in" target="_blank" rel="noopener">SUPERLMS</a></span>
        </div>
    </footer>

    <a href="#" class="to-top" id="toTop" aria-label="Back to top">&#8593;</a>

    {{-- Lightbox for every [data-lb] photo; photos of one .photos grid page through together. --}}
    <div class="lb" id="lb" role="dialog" aria-modal="true" aria-label="Photo">
        <button class="lb-x" type="button" aria-label="Close">&times;</button>
        <button class="lb-p" type="button" aria-label="Previous">&#8249;</button>
        <img alt="">
        <button class="lb-n" type="button" aria-label="Next">&#8250;</button>
    </div>

    <script>
    (function () {
        // The school's name on one line: a name too long for its space at
        // the full size is made smaller until it fits; below the smallest
        // size worth reading (a narrow phone) it wraps instead.
        // It is measured on an unseen copy, so the name on the page only ever
        // changes to its final size: the page never shifts while it is fitted.
        var nameEl = document.querySelector('.mast-name');
        function fitName() {
            if (!nameEl) return;
            var box = nameEl.parentNode, room = box.clientWidth;
            if (!room) return;
            var probe = nameEl.cloneNode(true);
            probe.removeAttribute('style');
            probe.setAttribute('aria-hidden', 'true');
            probe.style.cssText = 'position:absolute;left:0;top:0;visibility:hidden;pointer-events:none;width:auto;white-space:nowrap;';
            box.appendChild(probe);
            var size = '', wrap = 'nowrap', need = probe.scrollWidth;
            if (need > room) {
                var full = parseFloat(getComputedStyle(probe).fontSize), least = 14;
                size = Math.max(least, Math.floor(full * room / need * 10) / 10 - .3) + 'px';
                probe.style.fontSize = size;
                if (probe.scrollWidth > room) wrap = 'normal';
            }
            box.removeChild(probe);
            if (nameEl.style.fontSize !== size) nameEl.style.fontSize = size;
            if (nameEl.style.whiteSpace !== wrap) nameEl.style.whiteSpace = wrap;
        }
        // The menu drawer: the Menu button opens it, its ×, the dimmed page
        // and Escape close it; the page underneath stays put while it is open.
        var menu = document.getElementById('menu');
        var toggle = document.getElementById('menuToggle');
        function setMenu(open) {
            menu.classList.toggle('open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            document.documentElement.classList.toggle('menu-lock', open);
            if (open && !document.querySelector('.menu-list > li.open')) {
                var here = document.querySelector('.menu-list > li.has-drop.is-active');
                if (here) here.classList.add('open');
            }
        }
        if (toggle) toggle.addEventListener('click', function () { setMenu(!menu.classList.contains('open')); });
        document.querySelectorAll('[data-menu-close]').forEach(function (b) { b.addEventListener('click', function () { setMenu(false); }); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && menu.classList.contains('open')) setMenu(false); });
        // A group opens under itself; another one open closes.
        document.querySelectorAll('.menu-list > li.has-drop > button').forEach(function (b) {
            b.addEventListener('click', function () {
                var li = b.parentElement, was = li.classList.contains('open');
                document.querySelectorAll('.menu-list > li.open').forEach(function (o) { o.classList.remove('open'); });
                if (!was) li.classList.add('open');
            });
        });

        function fitAll() {
            fitName();
            if (window.__fitMenu && !window.__fitMenu() && menu.classList.contains('open')) setMenu(false);
        }
        fitAll();
        if (document.fonts && document.fonts.ready) document.fonts.ready.then(fitAll);
        window.addEventListener('load', fitAll);
        requestAnimationFrame(function () { requestAnimationFrame(function () { document.documentElement.classList.add('nav-anim'); }); });
        var mast = document.querySelector('.mast');
        if (mast && 'IntersectionObserver' in window) {
            new IntersectionObserver(function (e) { menu.classList.toggle('stuck', !e[0].isIntersecting); }).observe(mast);
        } else {
            menu.classList.add('stuck');
        }
        // Fitted again only when the window's width changes. On a phone the
        // address bar hides and shows as the page scrolls, resizing the
        // window's height over and over; nothing here depends on it, and
        // fitting then moved the page under the reader's finger.
        var fitTimer, fitWidth = window.innerWidth;
        window.addEventListener('resize', function () {
            if (window.innerWidth === fitWidth) return;
            fitWidth = window.innerWidth;
            clearTimeout(fitTimer); fitTimer = setTimeout(fitAll, 100);
        });

        // Tables on a phone: each value carries its column's name (shown
        // beside it when a row becomes a card).
        document.querySelectorAll('table.tbl, table.lms-tbl').forEach(function (t) {
            var heads = Array.prototype.map.call(t.querySelectorAll('thead th'), function (th) { return th.textContent.trim(); });
            if (!heads.length) return;
            t.querySelectorAll('tbody tr').forEach(function (tr) {
                if (tr.children.length !== heads.length) return;
                Array.prototype.forEach.call(tr.children, function (td, i) { if (heads[i]) td.setAttribute('data-label', heads[i]); });
            });
        });

        // Scroll animations, as on the SUPERLMS site: a block tilts up out of
        // the page as it scrolls into view — the cards of a row one after
        // another; a text-and-photo band from its two sides. A block already
        // on screen when the page opens is left as it is, and every block
        // animates once, then is plain again (so hovers keep working).
        (function () {
            if (!('IntersectionObserver' in window)) return;
            if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            var ITEMS = '.sec-head, .feat, .band-text, .band .ph, .enroll .wrap > *, .photos figure, .team figure, .fac, .cls article, '
                + '.vm > div, .vg > *, .leader, .lms-card, .desk > *, .side > *, .contact > *, .loc .map, .foot-grid > div';
            var BLOCKS = 'main .card';
            var small = window.innerWidth <= 1020;   // a phone or tablet
            var vh = window.innerHeight || document.documentElement.clientHeight;
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) {
                    if (!e.isIntersecting) return;
                    var el = e.target, cleaned = false;
                    io.unobserve(el);
                    el.classList.add('ed-shown');
                    var done = function (ev) {
                        if ((ev && ev.target !== el) || cleaned) return;
                        cleaned = true;
                        el.classList.remove('ed-anim-on', 'ed-up', 'ed-left', 'ed-right', 'ed-zoom', 'ed-flip', 'ed-soft', 'ed-shown');
                        el.style.removeProperty('--ed-delay');
                        el.removeEventListener('transitionend', done);
                    };
                    el.addEventListener('transitionend', done);
                    setTimeout(done, 1900);
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });

            function variant(el) {
                if (small) return 'ed-soft';
                if (el.classList.contains('sec-head')) return 'ed-flip';
                if (el.matches('.enroll .wrap > *')) return 'ed-zoom';
                var rev = !!el.closest('.band.rev');
                if (el.classList.contains('band-text')) return rev ? 'ed-right' : 'ed-left';
                if (el.matches('.band .ph')) return rev ? 'ed-left' : 'ed-right';
                if (el.matches('.side > .ph, .contact > :last-child')) return 'ed-right';
                return 'ed-up';
            }
            // A row that scrolls sideways (the facility cards on a phone):
            // its cards come into view by a swipe, not by the page's scroll.
            function inSwipeRow(el) {
                for (var p = el.parentElement; p && p !== document.body; p = p.parentElement) {
                    var o = getComputedStyle(p).overflowX;
                    if (o === 'auto' || o === 'scroll') return true;
                }
                return false;
            }
            function tag(el) {
                if (el.classList.contains('ed-anim-on') || (el.parentElement && el.parentElement.closest('.ed-anim-on'))) return;
                if (el.getBoundingClientRect().top < vh * 0.88) return;   // already on screen: no flash
                // On a phone or tablet a block that fills most of the screen is
                // the page itself: moving it would slide the page under a finger.
                if (small && (el.offsetHeight > vh * 0.6 || inSwipeRow(el))) return;
                var idx = 0, sib = el.previousElementSibling;
                while (sib) { idx++; sib = sib.previousElementSibling; }
                el.classList.add('ed-anim-on', variant(el));
                if (idx) el.style.setProperty('--ed-delay', Math.min(idx, 7) * 70 + 'ms');
                io.observe(el);
            }
            function init() {
                document.querySelectorAll(ITEMS).forEach(tag);
                // A card animates whole only when nothing in it does.
                document.querySelectorAll(BLOCKS).forEach(function (el) { if (!el.querySelector('.ed-anim-on')) tag(el); });
            }
            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
        })();

        var top = document.getElementById('toTop');
        window.addEventListener('scroll', function () { top.classList.toggle('show', window.scrollY > 500); }, { passive: true });
        top.addEventListener('click', function (e) { e.preventDefault(); window.scrollTo({ top: 0, behavior: 'smooth' }); });

        // Lightbox.
        var lb = document.getElementById('lb'), lbImg = lb.querySelector('img'), set = [], at = 0;
        function show(i) { at = (i + set.length) % set.length; lbImg.src = set[at]; }
        document.addEventListener('click', function (e) {
            var img = e.target.closest('[data-lb] img');
            if (!img) return;
            var grid = img.closest('.photos') || document;
            set = Array.prototype.map.call(grid.querySelectorAll('[data-lb] img'), function (i) { return i.src; });
            show(set.indexOf(img.src));
            lb.classList.add('open');
        });
        lb.querySelector('.lb-x').onclick = function () { lb.classList.remove('open'); };
        lb.querySelector('.lb-p').onclick = function () { show(at - 1); };
        lb.querySelector('.lb-n').onclick = function () { show(at + 1); };
        lb.addEventListener('click', function (e) { if (e.target === lb) lb.classList.remove('open'); });
        document.addEventListener('keydown', function (e) {
            if (!lb.classList.contains('open')) return;
            if (e.key === 'Escape') lb.classList.remove('open');
            if (e.key === 'ArrowLeft') show(at - 1);
            if (e.key === 'ArrowRight') show(at + 1);
        });

        // Enquiry-style forms (Admission, Query, Career, Contact, Appointment)
        // all post to the school's enquiry inbox: the form's type, every
        // field filled in as its label and answer, name, email and phone as
        // they are, the form's kind (and class, if asked) as the subject, and
        // every other field written into the message too.
        document.querySelectorAll('form.sform').forEach(function (form) {
            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                var btn = form.querySelector('button[type=submit]'), msg = form.querySelector('.sform-msg');
                var say = function (t, ok) { msg.textContent = t; msg.style.color = ok ? '#1A8A3A' : '#C0392B'; };
                var missing = Array.prototype.find.call(form.querySelectorAll('[required]'), function (f) { return !f.value.trim(); });
                if (missing) { say('Please fill in ' + missing.dataset.label + '.'); missing.focus(); return; }

                var trap = form.querySelector('[name=sf_check]');
                var data = { organization_id: Number(form.dataset.org), company: trap ? trap.value : '', subject: form.dataset.kind,
                             type: form.dataset.type || null, fields: [] }, extra = [];
                form.querySelectorAll('[data-label]').forEach(function (f) {
                    var v = f.value.trim(), n = f.name;
                    if (v && f.type === 'date') { v = new Date(v + 'T00:00:00').toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }); }
                    if (v) { data.fields.push({ label: f.dataset.label, value: v }); }
                    if (n === 'name' || n === 'email' || n === 'phone') { data[n] = v; }
                    else if (n === 'message') { data.message_body = v; }
                    else if (v) { extra.push(f.dataset.label + ': ' + v); if (f.dataset.subject) data.subject += ' — ' + v; }
                });
                data.message = extra.join('\n') + (extra.length && data.message_body ? '\n\n' : '') + (data.message_body || '');
                delete data.message_body;

                btn.disabled = true; var label = btn.textContent; btn.textContent = 'Sending…'; msg.textContent = '';
                try {
                    var res = await fetch('/api/website/school-contact', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify(data),
                    });
                    var json = await res.json().catch(function () { return {}; });
                    if (res.ok && json.success) { form.reset(); say(json.message || 'Thank you! We will get back to you soon.', true); }
                    else {
                        var first = json.errors ? Object.values(json.errors)[0][0] : null;
                        say(first || json.message || 'Something went wrong. Please try again.');
                    }
                } catch (err) {
                    say('Network error. Please try again.');
                } finally {
                    btn.disabled = false; btn.textContent = label;
                }
            });
        });
    })();
    </script>
    @stack('scripts')
</body>
</html>
