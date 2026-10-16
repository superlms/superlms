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
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
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
        img { max-width: 100%; display: block; }
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
        .mast-name {
            display: block; color: var(--primary); font-weight: 800; text-transform: uppercase;
            font-size: clamp(24px, 4.3vw, 58px); line-height: 1.08; letter-spacing: .06em;
            text-shadow: 0 1px 0 rgba(255,255,255,.6);
        }
        .mast-motto { margin-top: 8px; font-weight: 700; font-size: clamp(12px, 1.25vw, 15px); color: #222; line-height: 1.35; }
        .mast-medium { font-weight: 600; font-size: clamp(11px, 1.05vw, 13.5px); color: #444; line-height: 1.35; }

        /* ══════════ MENU BAR ══════════ */
        .menu { position: sticky; top: 0; z-index: 60; background: #fff; border-top: 1px solid var(--line); box-shadow: 0 2px 10px rgba(0,0,0,.07); }
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
        .menu-toggle { display: none; width: 100%; align-items: center; justify-content: space-between; padding: 12px 4px; background: none; border: 0; font: 600 13px 'Poppins', sans-serif; letter-spacing: .12em; text-transform: uppercase; color: #222; cursor: pointer; }
        .menu-toggle span { display: grid; gap: 4px; }
        .menu-toggle span i { display: block; width: 22px; height: 2px; background: var(--primary); border-radius: 2px; }

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
        .banner { position: relative; height: clamp(150px, 22vw, 300px); overflow: hidden; background: var(--navy); }
        .banner > img { width: 100%; height: 100%; object-fit: cover; }
        .banner::after { content: ''; position: absolute; inset: 0; background: rgba(0,0,0,.28); }
        .banner .wm { background: linear-gradient(135deg, var(--dark) 0%, var(--navy2) 100%); color: rgba(255,255,255,.5); }
        .banner .wm::before { opacity: .07; }
        .banner .wm img, .banner .wm svg { opacity: .16; max-height: 80%; color: #fff; }
        .banner h1 { position: absolute; z-index: 2; inset: 0; display: flex; align-items: center; justify-content: center; text-align: center; padding: 0 20px; color: #fff; text-transform: uppercase; font-weight: 700; font-size: clamp(18px, 2.4vw, 28px); letter-spacing: .02em; text-shadow: 2px 2px 4px rgba(0,0,0,.55); }

        /* ══════════ FORMS ══════════ */
        .sform-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 18px; }
        .sform label { display: flex; flex-direction: column; gap: 5px; font-size: 12.5px; font-weight: 600; color: #333; }
        .sform label.wide { grid-column: 1 / -1; }
        .sform label b { color: var(--primary); }
        .sform input, .sform select, .sform textarea { width: 100%; padding: 9px 11px; border: 1px solid #CFD2D8; border-radius: 3px; font: 400 13.5px 'Poppins', sans-serif; color: #222; background: #fff; }
        .sform input:focus, .sform select:focus, .sform textarea:focus { outline: 2px solid color-mix(in srgb, var(--primary) 35%, transparent); border-color: var(--primary); }
        .sform .hp { position: absolute; left: -9999px; width: 1px; height: 1px; opacity: 0; }
        .sform-foot { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; margin-top: 16px; }
        .sform-msg { font-size: 13px; font-weight: 600; }

        /* ══════════ TABLES ══════════ */
        .tbl-wrap { overflow-x: auto; }
        .tbl { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        .tbl th { background: var(--navy); color: #fff; text-align: left; font-weight: 600; padding: 10px 14px; border: 1px solid var(--navy); white-space: nowrap; }
        .tbl td { padding: 10px 14px; border: 1px solid var(--line); vertical-align: middle; }
        .tbl tbody tr:nth-child(even) td { background: #FAFAFB; }
        .tbl tbody tr:hover td { background: var(--light); }

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

        /* ══════════ RESPONSIVE ══════════ */
        @media (max-width: 1020px) {
            .menu-toggle { display: flex; }
            .menu-list { display: none; flex-direction: column; padding-bottom: 8px; }
            .menu.open .menu-list { display: flex; }
            .menu-list > li > a, .menu-list > li > button { width: 100%; justify-content: space-between; padding: 11px 4px; border-top: 1px solid #F0F0F0; font-size: 12.5px; }
            .drop { position: static; opacity: 1; visibility: visible; transform: none; display: none; box-shadow: none; border-top: 0; max-height: none; background: #FAFAFA; }
            .menu-list > li.open > .drop { display: block; }
            .menu-list > li.open .chev { transform: rotate(180deg); }
            .drop a { padding-left: 18px; }
            .menu { max-height: 100vh; overflow-y: auto; }
            .foot-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 720px) {
            .strip-in { flex-direction: column; justify-content: center; text-align: center; gap: 2px; }
            .strip-links { justify-content: center; gap: 0 14px; }
            .strip-links > * + *::before { display: none; }
            .mast-in { grid-template-columns: 64px 1fr 56px; gap: 10px; padding-top: 10px; padding-bottom: 10px; }
            .mast-logo, .mast-board { height: 64px; }
            .mast-logo img { max-height: 64px; }
            .mast-board img { max-height: 56px; }
            .mast-logo svg { width: 62px; height: 62px; }
            .mast-board svg { width: 54px; height: 54px; }
            .mast-wheel { width: 200px; height: 200px; }
            .mast-motto { margin-top: 4px; }
            .card { padding: 22px 18px; }
            .page { padding: 20px 0 40px; }
            .sform-grid { grid-template-columns: 1fr; }
            .foot-grid { grid-template-columns: 1fr; gap: 26px; }
            .wrap { padding: 0 16px; }
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
                    <a href="{{ $url }}" target="_blank" rel="noopener">{{ $label }}</a>
                @endforeach
                @if (!empty($c['phone']))
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', explode(',', $c['phone'])[0]) }}">{{ $c['phone'] }}</a>
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
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $c['school_name'] }} logo">
                @else
                    @include('school-site.classic.partials.seal', ['center' => null, 'ring' => $c['school_name'], 'sealId' => 'logo'])
                @endif
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
        <div class="wrap">
            <button class="menu-toggle" type="button" id="menuToggle" aria-expanded="false" aria-controls="menuList">
                Menu <span aria-hidden="true"><i></i><i></i><i></i></span>
            </button>
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
        </div>
    </nav>

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
        // Mobile menu and its dropdowns.
        var menu = document.getElementById('menu');
        var toggle = document.getElementById('menuToggle');
        if (toggle) toggle.addEventListener('click', function () {
            var open = menu.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        document.querySelectorAll('.menu-list > li.has-drop > button').forEach(function (b) {
            b.addEventListener('click', function () {
                var li = b.parentElement, was = li.classList.contains('open');
                document.querySelectorAll('.menu-list > li.open').forEach(function (o) { o.classList.remove('open'); });
                if (!was) li.classList.add('open');
            });
        });

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
        // all post to the school's enquiry inbox: name, email and phone as
        // they are, the form's kind (and class, if asked) as the subject, and
        // every other field written into the message.
        document.querySelectorAll('form.sform').forEach(function (form) {
            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                var btn = form.querySelector('button[type=submit]'), msg = form.querySelector('.sform-msg');
                var say = function (t, ok) { msg.textContent = t; msg.style.color = ok ? '#1A8A3A' : '#C0392B'; };
                var missing = Array.prototype.find.call(form.querySelectorAll('[required]'), function (f) { return !f.value.trim(); });
                if (missing) { say('Please fill in ' + missing.dataset.label + '.'); missing.focus(); return; }

                var data = { organization_id: Number(form.dataset.org), company: form.company.value, subject: form.dataset.kind }, extra = [];
                form.querySelectorAll('[data-label]').forEach(function (f) {
                    var v = f.value.trim(), n = f.name;
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
