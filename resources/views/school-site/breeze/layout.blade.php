{{--
    Breeze — the modern, light school-website template (user's ask of
    2026-11-03, after the YARSY / eClaster EdTech design): a quiet strip, a
    white header with the logo and name and an outlined Apply pill, the menu
    as a soft capsule whose open group is a raised white pill, airy white
    cards on a pale page with pastel badges (App\Support\ModernSite::TINTS),
    and a light footer. The school's colour is the accent; everything else
    stays soft.

    Reads exactly what Classic reads — $site, $c, $theme, $nav, $current,
    $lms — and shares its inner pages with Vibrant (school-site/modern).
--}}
@php
    use App\Models\SchoolWebsite;
    use App\Support\ModernSite;
    use Illuminate\Support\Str;

    $link     = fn ($slug) => $slug === 'home' ? url('/') : url($slug);
    $board    = trim((string) ($c['board'] ?? ''));
    $socials  = ModernSite::socials($c);
    $logoUrl  = SchoolWebsite::media($c['logo'] ?? null);
    $boardUrl = SchoolWebsite::media($c['board_logo'] ?? null);
    $medium   = trim((string) ($c['medium'] ?? '')) ?: trim((string) ($c['tagline'] ?? ''));
    $motto    = trim((string) ($c['motto'] ?? ''), " \"“”");
    $tel      = ModernSite::tel($c);
    $apply    = $site->isPageEnabled('admission') ? url('admission') : ($site->isPageEnabled('contact') ? url('contact') : null);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <script>if (window.innerWidth <= 1020) document.documentElement.classList.add('nav-drawer');</script>
    <title>{{ isset($title) ? $title . ' – ' . $c['school_name'] : $c['school_name'] . ($medium ? ' – ' . $medium : '') }}</title>
    <meta name="description" content="{{ Str::limit(strip_tags($c['about_text'] ?? ''), 150) }}">
    <meta name="theme-color" content="#ffffff">
    @if ($logoUrl)<link href="{{ $logoUrl }}" rel="icon">@endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: {{ $theme['primary'] }};
            --dark: {{ $theme['dark'] }};
            --light: {{ $theme['light'] }};
            --navy: {{ $theme['dark'] }};
            --soft: color-mix(in srgb, var(--primary) 8%, #fff);
            --ink: #0F172A; --text: #475569; --muted: #6B7A90; --line: #E6EBF3; --page: #F6F8FC; --card: #fff;
            --r-lg: 24px; --r-md: 20px; --r-sm: 12px;
            --shadow: 0 1px 2px rgba(16,24,40,.04), 0 6px 20px -10px rgba(16,24,40,.10);
            --font: 'Manrope', system-ui, -apple-system, 'Segoe UI', Arial, sans-serif;
            --wrap: 1220px;
        }
    </style>
    @include('school-site.modern.partials.base-css')
    @include('school-site.modern.partials.pages-css')
    <style>
        /* ══════════ BREEZE SKIN ══════════ */
        .hl { color: var(--primary); }

        /* Strip */
        .topbar { background: var(--soft); color: var(--text); font-size: 12.5px; font-weight: 600; border-bottom: 1px solid color-mix(in srgb, var(--primary) 10%, var(--line)); }
        .topbar-in { display: flex; align-items: center; justify-content: space-between; gap: 14px; min-height: 36px; padding-top: 4px; padding-bottom: 4px; }
        .tb-links { display: flex; align-items: center; gap: 18px; flex-wrap: wrap; justify-content: flex-end; }
        .tb-links a { display: inline-flex; align-items: center; gap: 6px; transition: color .2s; }
        .tb-links a:hover { color: var(--primary); }
        .tb-links .ico { width: 14px; height: 14px; color: var(--primary); }
        .tb-soc { display: inline-flex; gap: 10px; }

        /* Header */
        .hd { background: #fff; }
        .hd-in { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding-top: 16px; padding-bottom: 16px; }
        .brand { display: flex; align-items: center; gap: 14px; min-width: 0; }
        .brand-logo { flex: none; width: 58px; height: 58px; display: flex; align-items: center; justify-content: center; }
        .brand-logo img { max-width: 58px; max-height: 58px; width: auto; height: auto; object-fit: contain; }
        .brand-logo svg { width: 56px; height: 56px; }
        .brand-text { min-width: 0; }
        .brand-text b { display: block; font-size: clamp(17px, 2vw, 22px); line-height: 1.15; font-weight: 800; color: var(--ink); letter-spacing: -.02em; }
        .brand-text small { display: block; margin-top: 3px; font-size: 12.5px; line-height: 1.35; color: var(--muted); font-weight: 600; }
        .hd-cta { display: flex; align-items: center; gap: 8px; flex: none; }
        .hd-board { display: flex; align-items: center; gap: 8px; margin-right: 8px; font-size: 12px; color: var(--muted); font-weight: 700; line-height: 1.25; }
        .hd-board img, .hd-board svg { width: 40px; height: 40px; object-fit: contain; }
        .hd-board b { display: block; color: var(--ink); font-size: 13px; }
        .hd-login { padding: 10px 16px; border-radius: 999px; font-weight: 700; font-size: 14px; color: var(--primary); transition: background .2s; }
        .hd-login:hover { background: var(--soft); }
        .btn-outline { background: #fff; color: var(--primary); box-shadow: inset 0 0 0 1.5px var(--primary); }
        .btn-outline:hover { background: var(--primary); color: #fff; }

        /* Menu: a soft capsule */
        .menu { background: rgba(246,248,252,.82); -webkit-backdrop-filter: blur(14px) saturate(1.4); backdrop-filter: blur(14px) saturate(1.4); border-bottom: 1px solid var(--line); }
        .menu-panel { display: flex; justify-content: center; }
        /* A little wider than the page, so a laptop takes every group on one row. */
        .menu > .wrap { max-width: calc(var(--wrap) + 120px); padding: 0 16px; }
        .menu-list { width: fit-content; max-width: 100%; margin: 9px auto; padding: 4px; gap: 1px; border-radius: 999px; background: #EEF2F8; border: 1px solid var(--line); }
        .menu-list > li > a, .menu-list > li > button { gap: 4px; padding: 8px 11px; border-radius: 999px; font-size: 13px; font-weight: 700; color: #475569; transition: background .2s, color .2s, box-shadow .2s; }
        .menu-list .chev { width: 12px; height: 12px; }
        .menu-list > li > a:hover, .menu-list > li > button:hover, .menu-list > li:hover > button { color: var(--ink); background: rgba(255,255,255,.7); }
        .menu-list > li.is-active > a, .menu-list > li.is-active > button { background: #fff; color: var(--primary); box-shadow: 0 1px 3px rgba(16,24,40,.12), 0 1px 2px rgba(16,24,40,.06); }
        .drop { background: #fff; border-radius: 18px; padding: 8px; border: 1px solid var(--line); box-shadow: 0 24px 50px -18px rgba(16,24,40,.22); }
        .drop a { padding: 10px 12px; border-radius: 12px; font-size: 13.5px; font-weight: 600; color: var(--text); line-height: 1.4; }
        .drop a:hover, .drop a.active { background: var(--soft); color: var(--primary); }
        .drop a.active { font-weight: 800; }
        .drop .dot { flex: none; width: 7px; height: 7px; border-radius: 50%; background: color-mix(in srgb, var(--primary) 35%, #CBD5E1); }
        .drop a:hover .dot, .drop a.active .dot { background: var(--primary); }
        .nav-drawer .menu { background: rgba(255,255,255,.9); }
        .nav-drawer .menu-panel { display: flex; }
        .nav-drawer .menu-list { width: auto; margin: 0; padding: 8px 10px 12px; border: 0; border-radius: 0; background: none; }
        .nav-drawer .menu-toggle { background: var(--soft); color: var(--primary); }
        .menu-panel-head { background: var(--soft); }

        /* Buttons, eyebrows, cards */
        .btn { box-shadow: 0 8px 18px -10px color-mix(in srgb, var(--primary) 80%, transparent); }
        .btn-ghost, .btn-white, .btn-soft, .btn-line, .btn-outline { box-shadow: none; }
        .btn-ghost { box-shadow: inset 0 0 0 1.5px var(--line); }
        .btn-outline { box-shadow: inset 0 0 0 1.5px var(--primary); }
        .eyebrow { background: #fff; color: var(--primary); border: 1px solid color-mix(in srgb, var(--primary) 22%, var(--line)); }
        .panel, .tile, .person, .fac-card, .cls article, .info-card, .facts li, .lms-card { border: 1px solid var(--line); box-shadow: var(--shadow); }
        .badge-ico { background: var(--t, var(--soft)); color: var(--c, var(--primary)); }
        .chip { background: var(--t, var(--soft)); color: var(--c, var(--primary)); }
        .person:hover, .fac-card:hover { box-shadow: 0 22px 44px -24px rgba(16,24,40,.25); }
        .quote-mark { background: var(--soft); color: var(--primary); }
        .help-card { background: linear-gradient(165deg, var(--soft) 0%, #fff 70%); border: 1px solid var(--line); box-shadow: var(--shadow); }
        .help-card p { color: var(--text); }
        .help-card .info-card { box-shadow: none; }
        .bars .bar i { background: linear-gradient(180deg, color-mix(in srgb, var(--primary) 55%, #fff), var(--primary)); }
        .nf b { color: var(--primary); }
        body .lms-card { border-radius: var(--r-lg); }
        body .lms-card .lms-form button[type=submit] { border-radius: 999px; padding: 12px 28px; }

        /* Inner-page banner */
        .z-banner { position: relative; overflow: hidden; isolation: isolate; background: linear-gradient(180deg, var(--soft) 0%, var(--page) 100%); border-bottom: 1px solid var(--line); }
        .z-banner::before { content: ''; position: absolute; inset: 0; z-index: -1; background-image: linear-gradient(var(--line) 1px, transparent 1px), linear-gradient(90deg, var(--line) 1px, transparent 1px); background-size: 44px 44px; opacity: .55; mask-image: radial-gradient(70% 90% at 80% 0%, #000, transparent 75%); -webkit-mask-image: radial-gradient(70% 90% at 80% 0%, #000, transparent 75%); }
        .z-banner-in { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 30px; align-items: center; padding-top: 44px; padding-bottom: 44px; }
        .z-banner h1 { font-size: clamp(30px, 4.2vw, 50px); line-height: 1.06; font-weight: 800; letter-spacing: -.035em; color: var(--ink); animation: z-rise .9s cubic-bezier(.16,1,.3,1) both .05s; }
        .z-banner p { margin-top: 10px; color: var(--muted); font-weight: 600; }
        .crumbs { display: inline-flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 14px; padding: 6px 14px; border-radius: 999px; background: #fff; border: 1px solid var(--line); font-size: 12.5px; font-weight: 700; color: var(--muted); }
        .crumbs a:hover { color: var(--primary); }
        .crumbs b { color: var(--primary); }
        .crumbs i { opacity: .5; font-style: normal; }
        .z-banner-photo { position: relative; width: 380px; }
        .z-banner-photo::before { content: ''; position: absolute; inset: 14px -14px -14px 14px; border-radius: 26px; background: color-mix(in srgb, var(--primary) 14%, #fff); z-index: -1; }
        .z-banner-photo .ph { aspect-ratio: 16 / 10; border-radius: 24px; box-shadow: 0 20px 40px -20px rgba(16,24,40,.35); }
        @keyframes z-rise { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: none; } }

        /* Footer */
        .ft { margin-top: 20px; background: #fff; border-top: 1px solid var(--line); color: var(--text); font-size: 14px; }
        .ft-top { display: flex; align-items: center; justify-content: space-between; gap: 24px; flex-wrap: wrap; padding: 52px 0 38px; border-bottom: 1px solid var(--line); }
        .ft-top h2 { text-wrap: balance; font-size: clamp(26px, 3.2vw, 42px); line-height: 1.1; font-weight: 800; color: var(--ink); letter-spacing: -.03em; max-width: 720px; }
        .foot-grid { display: grid; grid-template-columns: 1.35fr 1fr 1fr 1.2fr; gap: 36px; padding: 42px 0 34px; }
        .ft h4 { font-size: 12px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; color: var(--ink); margin-bottom: 16px; }
        .ft-brand { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
        .ft-brand .mb-logo { width: 46px; height: 46px; }
        .ft-brand .mb-logo img, .ft-brand .mb-logo svg { max-width: 46px; max-height: 46px; }
        .ft-brand b { color: var(--ink); font-size: 16px; font-weight: 800; line-height: 1.25; }
        .ft-links { list-style: none; display: grid; gap: 9px; }
        .ft-links a { transition: color .2s; }
        .ft-links a:hover { color: var(--primary); }
        .ft-contact { list-style: none; display: grid; gap: 12px; }
        .ft-contact li { display: flex; gap: 10px; align-items: flex-start; }
        .ft-contact .ico { width: 17px; height: 17px; margin-top: 3px; color: var(--primary); }
        .ft-contact a:hover { color: var(--primary); }
        .ft-soc { display: flex; gap: 8px; margin-top: 18px; }
        .ft-soc a { width: 38px; height: 38px; border-radius: 12px; display: grid; place-items: center; background: var(--soft); color: var(--primary); transition: background .2s, color .2s, transform .2s; }
        .ft-soc a:hover { background: var(--primary); color: #fff; transform: translateY(-2px); }
        .ft-soc .ico { width: 17px; height: 17px; }
        .ft-bottom { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px; padding: 18px 0 22px; border-top: 1px solid var(--line); font-size: 13px; color: var(--muted); }
        .ft-bottom a { color: var(--primary); font-weight: 800; }

        @media (max-width: 1020px) {
            .foot-grid { grid-template-columns: 1fr 1fr; }
            .hd-board { display: none; }
            .z-banner-photo { display: none; }
            .z-banner-in { grid-template-columns: minmax(0, 1fr); }
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
            .z-banner-in { padding-top: 28px; padding-bottom: 28px; }
            .ft-top { padding: 38px 0 28px; }
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
                    <span class="hd-board">
                        @if ($boardUrl)
                            <img src="{{ $boardUrl }}" alt="{{ $board ?: 'Board' }} logo">
                        @else
                            @include('school-site.classic.partials.seal', ['center' => $board, 'ring' => 'Affiliated to ' . $board, 'sealId' => 'board'])
                        @endif
                        <span><b>{{ $board ? 'Affiliated to ' . $board : 'Affiliated school' }}</b>@if (!empty($c['affiliation_no']))Aff. No. {{ $c['affiliation_no'] }}@endif</span>
                    </span>
                @endif
                <a class="hd-login" href="{{ $site->adminLoginUrl() }}" target="_blank" rel="noopener">Login</a>
                @if ($apply)<a class="btn btn-outline" href="{{ $apply }}">Apply Now</a>@endif
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
                    @if ($tel !== '')<a class="btn btn-ghost" href="tel:{{ $tel }}">@include('school-site.modern.partials.icon', ['n' => 'phone']) Call {{ trim(explode(',', $c['phone'])[0]) }}</a>@endif
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
