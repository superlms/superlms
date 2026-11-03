{{--
    Vibrant — the modern, colourful school-website template (user's ask of
    2026-11-03, after the YARSY / eClaster EdTech design): a gradient strip,
    a white header with the logo, name and an Apply pill, a frosted menu
    whose open group is a gradient pill, rounded white cards on a soft page
    and a dark footer lit by colour. The school's colour leads a gradient
    into violet and pink; cards, icons and tags take turns in a bright
    palette (App\Support\ModernSite::PALETTE).

    Reads exactly what Classic reads — $site, $c, $theme, $nav, $current,
    $lms — and shares its inner pages with Breeze (school-site/modern).
--}}
@php
    use App\Models\SchoolWebsite;
    use App\Support\ModernSite;
    use Illuminate\Support\Str;

    $link    = fn ($slug) => $slug === 'home' ? url('/') : url($slug);
    $board   = trim((string) ($c['board'] ?? ''));
    $socials = ModernSite::socials($c);
    $logoUrl = SchoolWebsite::media($c['logo'] ?? null);
    $boardUrl = SchoolWebsite::media($c['board_logo'] ?? null);
    $medium  = trim((string) ($c['medium'] ?? '')) ?: trim((string) ($c['tagline'] ?? ''));
    $motto   = trim((string) ($c['motto'] ?? ''), " \"“”");
    $tel     = ModernSite::tel($c);
    $apply   = $site->isPageEnabled('admission') ? url('admission') : ($site->isPageEnabled('contact') ? url('contact') : null);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <script>if (window.innerWidth <= 1020) document.documentElement.classList.add('nav-drawer');</script>
    <title>{{ isset($title) ? $title . ' – ' . $c['school_name'] : $c['school_name'] . ($medium ? ' – ' . $medium : '') }}</title>
    <meta name="description" content="{{ Str::limit(strip_tags($c['about_text'] ?? ''), 150) }}">
    <meta name="theme-color" content="{{ $theme['primary'] }}">
    @if ($logoUrl)<link href="{{ $logoUrl }}" rel="icon">@endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: {{ $theme['primary'] }};
            --dark: {{ $theme['dark'] }};
            --light: {{ $theme['light'] }};
            --navy: {{ $theme['dark'] }};
            --violet: #7C3AED; --pink: #EC4899; --amber: #F59E0B; --green: #10B981; --cyan: #06B6D4; --orange: #F97316;
            --grad: linear-gradient(120deg, var(--primary) 0%, var(--violet) 58%, var(--pink) 100%);
            --ink: #0B1324; --text: #3B4658; --muted: #69768C; --line: #E8EBF3; --page: #F5F6FB; --card: #fff;
            --r-lg: 28px; --r-md: 22px; --r-sm: 14px;
            --shadow: 0 1px 2px rgba(15,23,42,.04), 0 14px 34px -14px rgba(15,23,42,.16);
            --font: 'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', Arial, sans-serif;
            --wrap: 1240px;
        }
    </style>
    @include('school-site.modern.partials.base-css')
    @include('school-site.modern.partials.pages-css')
    <style>
        /* ══════════ VIBRANT SKIN ══════════ */
        .hl { background: var(--grad); -webkit-background-clip: text; background-clip: text; color: transparent; }

        /* Strip */
        .topbar { background: var(--grad); color: #fff; font-size: 12.5px; font-weight: 600; }
        .topbar-in { display: flex; align-items: center; justify-content: space-between; gap: 14px; min-height: 36px; padding-top: 4px; padding-bottom: 4px; }
        .tb-facts { opacity: .95; letter-spacing: .01em; }
        .tb-links { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; justify-content: flex-end; }
        .tb-links a { display: inline-flex; align-items: center; gap: 6px; opacity: .92; transition: opacity .2s; }
        .tb-links a:hover { opacity: 1; }
        .tb-links .ico { width: 14px; height: 14px; }
        .tb-soc { display: inline-flex; gap: 4px; }
        .tb-soc a { width: 24px; height: 24px; border-radius: 50%; justify-content: center; background: rgba(255,255,255,.18); }
        .tb-soc .ico { width: 12px; height: 12px; }
        .tb-login { padding: 3px 12px; border-radius: 999px; background: rgba(255,255,255,.2); }

        /* Header */
        .hd { background: #fff; }
        .hd-in { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding-top: 14px; padding-bottom: 14px; }
        .brand { display: flex; align-items: center; gap: 14px; min-width: 0; }
        .brand-logo { flex: none; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; }
        .brand-logo img { max-width: 60px; max-height: 60px; width: auto; height: auto; object-fit: contain; }
        .brand-logo svg { width: 58px; height: 58px; }
        .brand-text { min-width: 0; }
        .brand-text b { display: block; font-size: clamp(17px, 2vw, 23px); line-height: 1.15; font-weight: 800; color: var(--ink); letter-spacing: -.02em; }
        .brand-text small { display: block; margin-top: 3px; font-size: 12.5px; line-height: 1.35; color: var(--muted); font-weight: 600; }
        .hd-cta { display: flex; align-items: center; gap: 12px; flex: none; }
        .board-badge { display: flex; align-items: center; gap: 10px; padding: 5px 14px 5px 5px; border-radius: 999px; background: var(--page); border: 1px solid var(--line); }
        .board-badge img { width: 38px; height: 38px; object-fit: contain; border-radius: 50%; background: #fff; }
        .board-badge svg { width: 38px; height: 38px; }
        .board-badge span { font-size: 11.5px; line-height: 1.25; color: var(--muted); font-weight: 600; }
        .board-badge span b { display: block; font-size: 13px; color: var(--ink); }
        .hd .btn { background: var(--grad); box-shadow: 0 10px 22px -10px color-mix(in srgb, var(--violet) 70%, transparent); }

        /* Menu */
        .menu { background: rgba(255,255,255,.86); -webkit-backdrop-filter: blur(14px) saturate(1.5); backdrop-filter: blur(14px) saturate(1.5); border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); }
        /* A little wider than the page, so a laptop takes every group on one row. */
        .menu > .wrap { max-width: calc(var(--wrap) + 100px); padding: 0 16px; }
        .menu-list { padding: 7px 0; gap: 1px; }
        .menu-list > li > a, .menu-list > li > button { gap: 4px; padding: 9px 11px; border-radius: 999px; font-size: 13px; font-weight: 700; color: var(--ink); transition: background .2s, color .2s; }
        .menu-list .chev { width: 12px; height: 12px; }
        .menu-list > li > a:hover, .menu-list > li > button:hover, .menu-list > li:hover > button { background: color-mix(in srgb, var(--primary) 8%, #fff); color: var(--primary); }
        .menu-list > li.is-active > a, .menu-list > li.is-active > button { background: var(--grad); color: #fff; box-shadow: 0 8px 18px -8px color-mix(in srgb, var(--violet) 75%, transparent); }
        .drop { background: #fff; border-radius: 20px; padding: 8px; border: 1px solid var(--line); box-shadow: 0 28px 60px -18px rgba(15,23,42,.3); }
        .drop a { padding: 10px 12px; border-radius: 12px; font-size: 13.5px; font-weight: 600; color: var(--text); line-height: 1.4; }
        .drop a:hover, .drop a.active { background: color-mix(in srgb, var(--primary) 7%, #fff); color: var(--ink); }
        .drop a.active { font-weight: 800; }
        .drop .dot { flex: none; width: 8px; height: 8px; border-radius: 50%; background: var(--primary); }
        .drop li:nth-child(6n+2) .dot { background: var(--violet); } .drop li:nth-child(6n+3) .dot { background: var(--pink); }
        .drop li:nth-child(6n+4) .dot { background: var(--amber); } .drop li:nth-child(6n+5) .dot { background: var(--green); }
        .drop li:nth-child(6n+6) .dot { background: var(--cyan); }
        .nav-drawer .menu-toggle { background: var(--grad); color: #fff; }
        .nav-drawer .menu-list > li.is-active > a, .nav-drawer .menu-list > li.is-active > button { background: color-mix(in srgb, var(--primary) 9%, #fff); color: var(--primary); box-shadow: none; }
        .menu-panel-head { background: linear-gradient(120deg, color-mix(in srgb, var(--primary) 10%, #fff), color-mix(in srgb, var(--pink) 8%, #fff)); }
        .menu-panel-foot .btn:first-child { background: var(--grad); }

        /* Buttons, eyebrows, cards */
        .btn { box-shadow: 0 10px 22px -12px color-mix(in srgb, var(--primary) 80%, transparent); }
        .btn-grad { background: var(--grad); }
        .btn-ghost, .btn-white, .btn-soft, .btn-line { box-shadow: none; }
        .btn-ghost { box-shadow: inset 0 0 0 1.5px var(--line); }
        .eyebrow { background: color-mix(in srgb, var(--primary) 9%, #fff); color: var(--primary); }
        .eyebrow::before { background: var(--grad); }
        .panel, .tile, .person, .fac-card, .cls article, .info-card, .facts li, .lms-card { border: 1px solid var(--line); box-shadow: var(--shadow); }
        .tile::after { content: ''; position: absolute; right: -40px; top: -40px; width: 120px; height: 120px; border-radius: 50%; background: var(--c, var(--primary)); opacity: .08; }
        .badge-ico { background: var(--c, var(--primary)); color: #fff; box-shadow: 0 12px 22px -12px var(--c, var(--primary)); }
        .chip { background: color-mix(in srgb, var(--c, var(--primary)) 11%, #fff); color: var(--c, var(--primary)); }
        .person:hover, .fac-card:hover { box-shadow: 0 26px 50px -22px rgba(15,23,42,.3); }
        .quote-mark { background: var(--grad); color: #fff; }
        .help-card { background: var(--grad); color: #fff; }
        .help-card .h3, .help-card .info-card b, .help-card .info-card a { color: #fff; }
        .help-card p { color: rgba(255,255,255,.85); }
        .help-card .info-card { background: rgba(255,255,255,.14); border-color: rgba(255,255,255,.2); box-shadow: none; }
        .help-card .info-card > div > span { color: rgba(255,255,255,.75); }
        .help-card .badge-ico { background: #fff; color: var(--violet); box-shadow: none; }
        .bars .bar i { background: var(--grad); }
        .nf b { background: var(--grad); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .tbl .btn { padding: 8px 16px; font-size: 12.5px; }
        body .lms-card { border-radius: var(--r-lg); }
        body .lms-head h2::before { background: var(--grad); width: 5px; }
        body .lms-card .lms-form button[type=submit] { background: var(--grad); border-radius: 999px; padding: 12px 28px; }

        /* Inner-page banner */
        .v-banner { padding-top: 22px; }
        .v-banner-card { position: relative; overflow: hidden; border-radius: 32px; min-height: 220px; display: flex; align-items: flex-end; padding: 44px; background: var(--grad); color: #fff; isolation: isolate; }
        .v-banner-card > img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: .32; mix-blend-mode: luminosity; z-index: -1; }
        .v-banner-card::before { content: ''; position: absolute; inset: 0; z-index: -1; background-image: radial-gradient(rgba(255,255,255,.22) 1.2px, transparent 1.4px); background-size: 22px 22px; mask-image: linear-gradient(90deg, transparent 30%, #000); -webkit-mask-image: linear-gradient(90deg, transparent 30%, #000); }
        .v-blob { position: absolute; border-radius: 50%; filter: blur(46px); opacity: .55; z-index: -1; }
        .v-blob.b1 { width: 260px; height: 260px; right: 6%; top: -90px; background: var(--amber); }
        .v-blob.b2 { width: 220px; height: 220px; right: 30%; bottom: -120px; background: var(--cyan); }
        .v-banner h1 { font-size: clamp(30px, 4.4vw, 54px); line-height: 1.05; font-weight: 800; letter-spacing: -.035em; text-shadow: 0 2px 20px rgba(0,0,0,.12); animation: v-rise .9s cubic-bezier(.16,1,.3,1) both .05s; }
        .crumbs { display: inline-flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 14px; padding: 6px 14px; border-radius: 999px; background: rgba(255,255,255,.18); -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px); font-size: 12.5px; font-weight: 600; }
        .crumbs a:hover { text-decoration: underline; }
        .crumbs i { opacity: .6; font-style: normal; }
        @keyframes v-rise { from { opacity: 0; transform: translateY(22px); } to { opacity: 1; transform: none; } }

        /* Footer */
        .ft { position: relative; overflow: hidden; margin-top: 30px; color: rgba(255,255,255,.72); font-size: 14px; background: #0A0F21; isolation: isolate; }
        .ft::before { content: ''; position: absolute; inset: 0; z-index: -1; background: radial-gradient(600px 300px at 8% 0%, color-mix(in srgb, var(--primary) 45%, transparent), transparent 70%), radial-gradient(520px 280px at 92% 6%, rgba(236,72,153,.32), transparent 70%), radial-gradient(500px 260px at 55% 110%, rgba(124,58,237,.3), transparent 70%); }
        .ft-top { display: flex; align-items: center; justify-content: space-between; gap: 24px; flex-wrap: wrap; padding: 56px 0 40px; border-bottom: 1px solid rgba(255,255,255,.1); }
        .ft-top h2 { text-wrap: balance; font-size: clamp(26px, 3.4vw, 44px); line-height: 1.1; font-weight: 800; color: #fff; letter-spacing: -.03em; max-width: 720px; }
        .ft-top h2 .hl { background: linear-gradient(90deg, #fff, color-mix(in srgb, var(--pink) 60%, #fff), var(--amber)); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .ft-top .btn:not(.btn-line) { background: #fff; color: var(--ink); }
        .foot-grid { display: grid; grid-template-columns: 1.35fr 1fr 1fr 1.2fr; gap: 36px; padding: 44px 0 36px; }
        .ft h4 { font-size: 12px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; color: #fff; margin-bottom: 16px; }
        .ft-brand { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
        .ft-brand .mb-logo { width: 46px; height: 46px; padding: 4px; border-radius: 14px; background: #fff; }
        .ft-brand .mb-logo img, .ft-brand .mb-logo svg { max-width: 38px; max-height: 38px; }
        .ft-brand b { color: #fff; font-size: 16px; font-weight: 800; line-height: 1.25; }
        .ft-links { list-style: none; display: grid; gap: 9px; }
        .ft-links a { transition: color .2s, padding .2s; }
        .ft-links a:hover { color: #fff; padding-left: 4px; }
        .ft-contact { list-style: none; display: grid; gap: 12px; }
        .ft-contact li { display: flex; gap: 10px; align-items: flex-start; }
        .ft-contact .ico { width: 17px; height: 17px; margin-top: 3px; color: color-mix(in srgb, var(--pink) 70%, #fff); }
        .ft-contact a:hover { color: #fff; }
        .ft-soc { display: flex; gap: 8px; margin-top: 18px; }
        .ft-soc a { width: 38px; height: 38px; border-radius: 12px; display: grid; place-items: center; background: rgba(255,255,255,.08); color: #fff; transition: background .2s, transform .2s; }
        .ft-soc a:hover { background: var(--grad); transform: translateY(-2px); }
        .ft-soc .ico { width: 17px; height: 17px; }
        .ft-bottom { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px; padding: 18px 0 22px; border-top: 1px solid rgba(255,255,255,.1); font-size: 13px; }
        .ft-bottom a { color: #fff; font-weight: 700; }

        @media (max-width: 1020px) {
            .foot-grid { grid-template-columns: 1fr 1fr; }
            .board-badge { display: none; }
        }
        @media (max-width: 720px) {
            .topbar-in { justify-content: center; text-align: center; }
            .tb-facts, .tb-soc, .tb-mail { display: none; }
            .hd-in { padding-top: 12px; padding-bottom: 12px; }
            .brand-logo { width: 48px; height: 48px; }
            .brand-logo img { max-width: 48px; max-height: 48px; }
            .brand-logo svg { width: 46px; height: 46px; }
            .brand-text small { font-size: 11.5px; }
            .hd-cta { display: none; }
            .v-banner { padding-top: 14px; }
            .v-banner-card { min-height: 160px; padding: 26px 22px; border-radius: 24px; }
            .ft-top { padding: 40px 0 30px; }
            .foot-grid { grid-template-columns: 1fr; gap: 26px; padding: 32px 0 26px; }
            .ft-bottom { flex-direction: column; text-align: center; padding-bottom: 76px; }
        }
    </style>
    @stack('styles')
</head>
<body>

    {{-- ══════════ STRIP ══════════ --}}
    <div class="topbar">
        <div class="wrap topbar-in">
            <div class="tb-facts">
                @if (!empty($c['affiliation_no'])){{ $board ?: 'CBSE' }} Aff. No. {{ $c['affiliation_no'] }}@endif
                @if (!empty($c['affiliation_no']) && !empty($c['school_code'])) &nbsp;·&nbsp; @endif
                @if (!empty($c['school_code']))School Code: {{ $c['school_code'] }}@endif
                @if (empty($c['affiliation_no']) && empty($c['school_code'])){{ $c['address'] ?: $c['school_name'] }}@endif
            </div>
            <div class="tb-links">
                @if (!empty($c['phone']))<a href="tel:{{ $tel }}">@include('school-site.modern.partials.icon', ['n' => 'phone']){{ $c['phone'] }}</a>@endif
                @if (!empty($c['email']))<a href="mailto:{{ $c['email'] }}" class="tb-mail">@include('school-site.modern.partials.icon', ['n' => 'mail']){{ $c['email'] }}</a>@endif
                @if ($socials)
                    <span class="tb-soc">
                        @foreach ($socials as $network => [$url, $icon])
                            <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $network }}">@include('school-site.modern.partials.icon', ['n' => $icon])</a>
                        @endforeach
                    </span>
                @endif
                <a href="{{ $site->adminLoginUrl() }}" target="_blank" rel="noopener" class="tb-login">Login</a>
            </div>
        </div>
    </div>

    {{-- ══════════ HEADER ══════════ --}}
    <header class="hd">
        <div class="wrap hd-in">
            <a href="{{ url('/') }}" class="brand" aria-label="{{ $c['school_name'] }} — Home">
                <span class="brand-logo">@include('school-site.classic.partials.logo', ['sealId' => 'logo'])</span>
                <span class="brand-text">
                    <b>{{ $c['school_name'] }}</b>
                    @if ($motto !== '' || $medium !== '')<small>{{ $motto !== '' ? '“' . $motto . '”' : '' }}{{ $motto !== '' && $medium !== '' ? ' · ' : '' }}{{ $medium }}</small>@endif
                </span>
            </a>
            <div class="hd-cta">
                @if ($boardUrl || $board)
                    <span class="board-badge">
                        @if ($boardUrl)
                            <img src="{{ $boardUrl }}" alt="{{ $board ?: 'Board' }} logo">
                        @else
                            @include('school-site.classic.partials.seal', ['center' => $board, 'ring' => 'Affiliated to ' . $board, 'sealId' => 'board'])
                        @endif
                        <span><b>{{ $board ? 'Affiliated to ' . $board : 'Affiliated school' }}</b>@if (!empty($c['affiliation_no']))Aff. No. {{ $c['affiliation_no'] }}@endif</span>
                    </span>
                @endif
                @if ($apply)<a class="btn" href="{{ $apply }}">Apply Now @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a>@endif
            </div>
        </div>
    </header>

    @include('school-site.modern.partials.menu')

    <main>
        @yield('content')
    </main>

    {{-- ══════════ FOOTER ══════════ --}}
    <footer class="ft">
        <div class="wrap">
            <div class="ft-top">
                <h2>{!! ModernSite::highlight(trim((string) ($c['cta_heading'] ?? '')) ?: 'Admissions Open', '') !!}</h2>
                <div class="btns">
                    @if ($apply)<a class="btn" href="{{ $apply }}">Apply Now @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a>@endif
                    @if ($tel !== '')<a class="btn btn-line" href="tel:{{ $tel }}">@include('school-site.modern.partials.icon', ['n' => 'phone']) Call {{ trim(explode(',', $c['phone'])[0]) }}</a>@endif
                </div>
            </div>
            <div class="foot-grid">
                <div>
                    <div class="ft-brand">
                        <span class="mb-logo">@include('school-site.classic.partials.logo', ['sealId' => 'foot'])</span>
                        <b>{{ $c['school_name'] }}</b>
                    </div>
                    <p>{{ Str::limit(strip_tags($c['about_text'] ?? ''), 210) }}</p>
                    @if ($socials)
                        <div class="ft-soc">
                            @foreach ($socials as $network => [$url, $icon])
                                <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $network }}">@include('school-site.modern.partials.icon', ['n' => $icon])</a>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div>
                    <h4>Quick Links</h4>
                    <ul class="ft-links">
                        @foreach (['home', 'about', 'facilities', 'gallery', 'disclosures', 'result', 'contact'] as $slug)
                            @if ($site->isPageEnabled($slug))<li><a href="{{ $link($slug) }}">{{ SchoolWebsite::allPages()[$slug] }}</a></li>@endif
                        @endforeach
                        <li><a href="{{ $site->adminLoginUrl() }}" target="_blank" rel="noopener">Staff Login</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Admissions</h4>
                    <ul class="ft-links">
                        @foreach (['admission', 'classes', 'fee-structure', 'rules', 'academic-calendar', 'transport', 'career'] as $slug)
                            @if ($site->isPageEnabled($slug))<li><a href="{{ $link($slug) }}">{{ SchoolWebsite::allPages()[$slug] }}</a></li>@endif
                        @endforeach
                    </ul>
                </div>
                <div>
                    <h4>Contact</h4>
                    <ul class="ft-contact">
                        @if (!empty($c['address']))<li>@include('school-site.modern.partials.icon', ['n' => 'pin'])<span>{{ $c['address'] }}</span></li>@endif
                        @if (!empty($c['phone']))<li>@include('school-site.modern.partials.icon', ['n' => 'phone'])<a href="tel:{{ $tel }}">{{ $c['phone'] }}</a></li>@endif
                        @if (!empty($c['email']))<li>@include('school-site.modern.partials.icon', ['n' => 'mail'])<a href="mailto:{{ $c['email'] }}">{{ $c['email'] }}</a></li>@endif
                    </ul>
                </div>
            </div>
            <div class="ft-bottom">
                <span>&copy; {{ date('Y') }} {{ $c['school_name'] }}. All rights reserved.</span>
                <span>Website by <a href="https://superlms.in" target="_blank" rel="noopener">SUPERLMS</a></span>
            </div>
        </div>
    </footer>

    @include('school-site.modern.partials.scripts')
    @stack('scripts')
</body>
</html>
