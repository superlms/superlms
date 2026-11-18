{{--
    Mint — the school-website template in the superlms.in website's own look
    (user's ask of 2026-11-18): an announcement strip in the school's dark
    colour, a white header with the logo and the name in the website's
    display face (its last word in the school's colour, as SUPER·LMS), a
    frosted menu bar of pill links, the website's pill buttons, borderless
    white cards with a soft shadow, pastel panels (App\Support\ModernSite::
    PANELS), gradient words, a mint call-to-action band and a light footer.
    The school's colour leads; on its own preset (Teal) it is the website's.

    Reads exactly what Classic reads — $site, $c, $theme, $nav, $current,
    $lms — and shares its inner pages with Vibrant and Breeze (school-site/modern).
--}}
@php
    use App\Models\SchoolWebsite;
    use App\Support\ModernSite;
    use Illuminate\Support\Str;

    $link     = fn ($slug) => $slug === 'home' ? url('/') : url($slug);
    $board    = trim((string) ($c['board'] ?? ''));
    $socials  = ModernSite::socials($c);
    $logoUrl  = SchoolWebsite::media($c['logo'] ?? null);
    $medium   = trim((string) ($c['medium'] ?? '')) ?: trim((string) ($c['tagline'] ?? ''));
    $motto    = trim((string) ($c['motto'] ?? ''), " \"“”");
    $tel      = ModernSite::tel($c);
    $apply    = $site->isPageEnabled('admission') ? url('admission') : ($site->isPageEnabled('contact') ? url('contact') : null);
    $session  = trim((string) ($c['admission_session'] ?? ''));

    // The name as the website writes SUPER·LMS: its last word in the school's colour.
    $nameWords = preg_split('/\s+/u', trim((string) $c['school_name']));
    $nameTail  = count($nameWords) > 1 ? array_pop($nameWords) : '';
    $nameHead  = implode(' ', $nameWords);

    // The strip's announcement: the admission season, else the call to action.
    $stripLead = trim((string) ($c['cta_heading'] ?? '')) ?: 'Admissions Open';
    $stripText = $session !== '' ? 'Session ' . $session : Str::limit(trim((string) ($c['cta_text'] ?? '')), 70);
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
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: {{ $theme['primary'] }};
            --dark: {{ $theme['dark'] }};
            --light: {{ $theme['light'] }};
            --navy: {{ $theme['dark'] }};
            --soft: color-mix(in srgb, var(--primary) 8%, #fff);
            --soft-2: color-mix(in srgb, var(--primary) 16%, #fff);
            --deep: color-mix(in srgb, var(--primary) 82%, #000);
            --ink: #0c2620; --text: #2b433b; --muted: #6b7c76; --faint: #c3ccc8;
            --line: #e5eae8; --line-2: #d6dedb; --page: #fff; --card: #fff; --grey: #f4f6f5; --grey-2: #eceff0;
            --r-lg: 26px; --r-md: 20px; --r-sm: 12px;
            --shadow: 0 1px 2px rgba(12,38,32,.05), 0 18px 40px -26px rgba(12,38,32,.28);
            --shadow-lg: 0 44px 90px -30px rgba(12,38,32,.32), 0 14px 28px -14px rgba(12,38,32,.14);
            --grad: linear-gradient(92deg, var(--primary) 0%, color-mix(in srgb, var(--primary) 78%, #23a083) 55%, color-mix(in srgb, var(--primary) 62%, #86a83a) 110%);
            --font: 'Geist', 'Segoe UI', system-ui, -apple-system, Arial, sans-serif;
            --display: 'Plus Jakarta Sans', 'Geist', system-ui, -apple-system, 'Segoe UI', sans-serif;
            --ease: cubic-bezier(.2,.7,.2,1);
            --wrap: 1200px;
        }
    </style>
    @include('school-site.modern.partials.base-css')
    @include('school-site.modern.partials.pages-css')
    <style>
        /* ══════════ MINT SKIN — the superlms.in look ══════════ */
        body { font-size: 15.5px; line-height: 1.6; background: #fff; }
        ::selection { background: var(--soft-2); color: var(--ink); }
        .hl, .grad { background: var(--grad); -webkit-background-clip: text; background-clip: text; color: transparent; padding-bottom: .06em; }
        .h2, .h3, .nf b { font-family: var(--display); letter-spacing: -.04em; }
        .h2 { font-size: clamp(30px, 3.8vw, 48px); line-height: 1.08; margin-top: 0; }
        .h3 { letter-spacing: -.02em; }
        .lead-text { font-size: clamp(16px, 1.4vw, 18px); line-height: 1.62; }
        .eyebrow { padding: 0; border-radius: 0; background: none; border: 0; font-size: 14.5px; font-weight: 600; letter-spacing: -.005em; text-transform: none; color: var(--primary); margin-bottom: 12px; }
        .eyebrow::before { display: none; }

        /* Buttons: the website's pills */
        .btn { height: 48px; padding: 0 24px; font-size: 15.5px; font-weight: 500; letter-spacing: -.01em; border: 1px solid transparent; transition: background .2s, color .2s, box-shadow .25s, transform .25s var(--ease), border-color .2s; }
        .btn:hover { filter: none; background: var(--deep); transform: translateY(-1px); box-shadow: 0 12px 26px -10px color-mix(in srgb, var(--primary) 70%, transparent); }
        .btn .ico { width: 17px; height: 17px; transition: transform .25s var(--ease); }
        .btn:hover .ico { transform: translateX(3px); }
        .btn-sm { height: 40px; padding: 0 18px; font-size: 14.5px; }
        .btn-lg { height: 56px; padding: 0 32px; font-size: 17px; }
        .btn-ghost, .btn-white { background: #fff; color: var(--ink); border-color: var(--line); box-shadow: none; }
        .btn-ghost:hover, .btn-white:hover { background: #fff; color: var(--ink); border-color: var(--line-2); box-shadow: 0 1px 2px rgba(12,38,32,.06), 0 1px 3px rgba(12,38,32,.05); }
        .btn-outline { background: #fff; color: var(--deep); border-color: var(--primary); }
        .btn-outline:hover { background: var(--soft); color: var(--deep); box-shadow: none; }
        .btn-soft { background: var(--soft); color: var(--deep); }
        .btn-soft:hover { background: var(--soft-2); box-shadow: none; }

        /* Cards: white, borderless, a soft shadow */
        .panel, .tile, .person, .fac-card, .cls article, .info-card, .facts li, .lms-card { border: 1px solid #edf1ef; box-shadow: var(--shadow); }
        .tile, .fac-card, .person, .cls article { transition: transform .4s var(--ease), box-shadow .4s var(--ease); }
        .tile:hover, .fac-card:hover, .person:hover { transform: translateY(-6px); box-shadow: 0 2px 4px rgba(12,38,32,.05), 0 30px 60px -30px rgba(12,38,32,.38); }
        .badge-ico { background: var(--t, var(--soft)); color: var(--c, var(--primary)); }
        .chip { background: var(--t, var(--soft)); color: var(--c, var(--deep)); font-weight: 600; }
        .quote-mark { background: var(--soft); color: var(--primary); }
        .help-card { background: radial-gradient(120% 140% at 0% 0%, color-mix(in srgb, var(--primary) 16%, #fff) 0%, transparent 55%), radial-gradient(90% 120% at 100% 100%, #f4f3d6 0%, transparent 55%), color-mix(in srgb, var(--primary) 6%, #fff); border: 0; }
        .help-card .info-card { box-shadow: none; border-color: transparent; }
        .bars .bar i { background: linear-gradient(180deg, color-mix(in srgb, var(--primary) 55%, #fff), var(--primary)); }
        .nf b { background: var(--grad); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .tbl-wrap { border-radius: 18px; }
        body .lms-card { border-radius: var(--r-lg); }
        body .lms-card .lms-form button[type=submit] { border-radius: 999px; padding: 12px 28px; }
        .sform input, .sform select, .sform textarea { border-radius: 14px; border-color: var(--line); background: #fff; }
        .pg { background: linear-gradient(180deg, #fff 0, var(--grey) 260px, var(--grey) 100%); }

        /* Strip: the website's announcement bar */
        .topbar { background: var(--dark); color: rgba(255,255,255,.88); font-size: 13.5px; }
        .topbar-in { display: flex; align-items: center; justify-content: center; gap: 10px; min-height: 40px; padding-top: 6px; padding-bottom: 6px; text-align: center; flex-wrap: wrap; }
        .topbar-ico { width: 22px; height: 22px; border-radius: 6px; display: grid; place-items: center; background: rgba(255,255,255,.1); color: #fcd77f; flex: none; }
        .topbar-ico .ico { width: 13px; height: 13px; }
        .topbar b { color: #fcd77f; font-weight: 600; }
        .topbar a { color: #fff; font-weight: 600; display: inline-flex; align-items: center; gap: 2px; }
        .topbar a:hover { text-decoration: underline; text-underline-offset: 3px; }
        .topbar a .ico { width: 14px; height: 14px; }

        /* Header */
        .hd { background: #fff; }
        .hd-in { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding-top: 16px; padding-bottom: 16px; }
        .brand { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .brand-logo { flex: none; width: 52px; height: 52px; display: flex; align-items: center; justify-content: center; }
        .brand-logo img { max-width: 52px; max-height: 52px; width: auto; height: auto; object-fit: contain; }
        .brand-logo svg { width: 50px; height: 50px; }
        .brand-text { min-width: 0; }
        .brand-text b { display: block; font-family: var(--display); font-size: clamp(18px, 2vw, 23px); line-height: 1.12; font-weight: 800; letter-spacing: -.035em; color: var(--ink); }
        .brand-text b span { color: var(--primary); }
        .brand-text small { display: block; margin-top: 3px; font-size: 12.5px; line-height: 1.35; color: var(--muted); font-weight: 500; }
        .hd-cta { display: flex; align-items: center; gap: 6px; flex: none; }
        .hd-phone { display: inline-flex; align-items: center; gap: 7px; padding: 0 12px; height: 40px; border-radius: 999px; font-size: 14.5px; font-weight: 500; color: var(--text); transition: background .2s, color .2s; }
        .hd-phone .ico { width: 16px; height: 16px; color: var(--primary); }
        .hd-phone:hover, .hd-login:hover { background: var(--grey); color: var(--ink); }
        .hd-login { display: inline-flex; align-items: center; height: 40px; padding: 0 14px; border-radius: 999px; font-size: 14.5px; font-weight: 500; color: var(--text); }

        /* Menu: a frosted bar of pill links */
        .menu { background: rgba(255,255,255,.92); -webkit-backdrop-filter: saturate(180%) blur(14px); backdrop-filter: saturate(180%) blur(14px); border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); }
        .menu > .wrap { max-width: calc(var(--wrap) + 140px); padding: 0 16px; }
        .menu-list { gap: 2px; padding: 8px 0; }
        .menu-list > li > a, .menu-list > li > button { gap: 4px; padding: 8px 11px; border-radius: 999px; font-size: 14px; font-weight: 500; color: #3c4e48; transition: background .2s, color .2s; }
        .menu-list .chev { width: 12px; height: 12px; }
        .menu-list > li > a:hover, .menu-list > li > button:hover, .menu-list > li:hover > button { color: var(--ink); background: var(--grey); }
        .menu-list > li.is-active > a, .menu-list > li.is-active > button { color: var(--ink); background: var(--grey-2); font-weight: 600; }
        .drop { background: #fff; border-radius: 18px; padding: 8px; border: 1px solid var(--line); box-shadow: var(--shadow-lg); }
        .drop a { padding: 10px 12px; border-radius: 12px; font-size: 14px; font-weight: 500; color: var(--text); line-height: 1.4; }
        .drop a:hover, .drop a.active { background: var(--soft); color: var(--deep); }
        .drop a.active { font-weight: 600; }
        .drop .dot { flex: none; width: 7px; height: 7px; border-radius: 50%; background: var(--faint); }
        .drop a:hover .dot, .drop a.active .dot { background: var(--primary); }
        .nav-drawer .menu-list { padding: 8px 10px 12px; }
        .nav-drawer .menu-toggle { background: var(--grey); color: var(--ink); font-weight: 600; }
        .menu-panel-head { background: #fff; }

        /* The call-to-action band over the footer (inner pages) */
        .m-cta-band { padding: 30px 0 10px; }
        .m-cta { position: relative; overflow: hidden; isolation: isolate; border-radius: 40px; padding: 70px 32px; text-align: center;
            background: radial-gradient(120% 140% at 0% 0%, color-mix(in srgb, var(--primary) 20%, #fff) 0%, transparent 55%), radial-gradient(90% 120% at 100% 100%, #f4f3d6 0%, transparent 55%), color-mix(in srgb, var(--primary) 8%, #fff); }
        .m-cta::after { content: ''; position: absolute; z-index: -1; width: 380px; height: 380px; border-radius: 50%; right: -120px; top: -160px; background: radial-gradient(closest-side, rgba(255,255,255,.7), transparent); }
        .m-cta .h2 { max-width: 820px; margin: 0 auto; }
        .m-cta .lead-text { max-width: 660px; margin: 18px auto 0; }
        .m-cta .btns { margin-top: 30px; justify-content: center; }
        .m-cta-chip { display: inline-flex; align-items: center; gap: 8px; height: 40px; padding: 0 18px; margin-bottom: 22px; border-radius: 999px; background: rgba(255,255,255,.8); border: 1px solid rgba(12,38,32,.08); font-size: 14px; color: var(--muted); }
        .m-cta-chip b { color: #9a6a07; font-weight: 600; }

        /* Inner-page title (school-site/mint/partials/banner) */
        .m-banner { position: relative; isolation: isolate; overflow: hidden; padding: 64px 0 46px; text-align: center; background: #fff; }
        .m-banner::before, .m-banner::after { content: ''; position: absolute; z-index: -1; border-radius: 50%; filter: blur(70px); pointer-events: none; }
        .m-banner::before { width: 520px; height: 420px; right: -60px; top: -80px; background: radial-gradient(circle, color-mix(in srgb, var(--primary) 18%, transparent), transparent 70%); }
        .m-banner::after { width: 420px; height: 360px; left: -80px; top: 80px; background: radial-gradient(circle, rgba(134,168,58,.12), transparent 70%); }
        .crumbs { display: inline-flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: 8px; margin-bottom: 18px; padding: 6px 14px 6px 8px; border-radius: 999px; background: #fff; border: 1px solid var(--line); box-shadow: 0 1px 2px rgba(12,38,32,.06), 0 1px 3px rgba(12,38,32,.05); font-size: 13.5px; font-weight: 500; color: var(--muted); }
        .crumbs .c-dot { width: 22px; height: 22px; border-radius: 50%; background: var(--soft); display: grid; place-items: center; }
        .crumbs .c-dot::after { content: ''; width: 8px; height: 8px; border-radius: 50%; background: var(--primary); }
        .crumbs a:hover { color: var(--deep); }
        .crumbs b { color: var(--ink); font-weight: 600; }
        .crumbs i { opacity: .5; font-style: normal; }
        .m-banner h1 { max-width: 940px; margin: 0 auto; font-family: var(--display); font-size: clamp(34px, 5vw, 60px); line-height: 1.05; font-weight: 800; letter-spacing: -.045em; color: var(--ink); text-wrap: balance; animation: m-rise .9s var(--ease) both .05s; }
        .m-meta { display: inline-flex; align-items: center; gap: 8px; margin-top: 20px; padding: 7px 14px; border-radius: 999px; background: var(--grey); color: var(--muted); font-size: 13.5px; font-weight: 500; }
        .m-meta .ico { width: 15px; height: 15px; color: var(--primary); }
        .m-banner-photo { max-width: 1040px; margin: 34px auto 0; aspect-ratio: 21 / 7; border-radius: 28px; box-shadow: var(--shadow-lg); }
        @keyframes m-rise { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: none; } }

        /* Footer: the website's light footer */
        .ft { margin-top: 30px; padding: 64px 0 26px; background: #f6f7f7; border-top: 1px solid var(--line); color: var(--muted); font-size: 14.5px; }
        .foot-grid { display: grid; grid-template-columns: 1.5fr 1fr 1fr 1.3fr; gap: 40px; }
        .ft h4 { font-family: var(--display); font-size: 15px; font-weight: 700; letter-spacing: -.01em; color: var(--ink); margin-bottom: 16px; }
        .ft-brand { display: flex; align-items: center; gap: 12px; }
        .ft-brand .mb-logo { width: 44px; height: 44px; }
        .ft-brand .mb-logo img, .ft-brand .mb-logo svg { max-width: 44px; max-height: 44px; }
        .ft-brand b { font-family: var(--display); color: var(--ink); font-size: 17px; font-weight: 800; letter-spacing: -.03em; line-height: 1.2; }
        .ft-desc { margin: 16px 0 20px; line-height: 1.7; max-width: 320px; }
        .ft-links { list-style: none; display: grid; gap: 10px; }
        .ft-links a { transition: color .2s; }
        .ft-links a:hover { color: var(--deep); }
        .ft-contact { list-style: none; display: grid; gap: 12px; }
        .ft-contact li { display: flex; gap: 10px; align-items: flex-start; }
        .ft-contact .ico { width: 17px; height: 17px; margin-top: 3px; color: var(--primary); }
        .ft-contact a:hover { color: var(--deep); }
        .ft-soc { display: flex; gap: 8px; }
        .ft-soc a { width: 40px; height: 40px; border-radius: 50%; display: grid; place-items: center; background: #fff; border: 1px solid var(--line); color: var(--text); transition: background .2s, color .2s, border-color .2s, transform .2s; }
        .ft-soc a:hover { background: var(--primary); border-color: var(--primary); color: #fff; transform: translateY(-2px); }
        .ft-soc .ico { width: 17px; height: 17px; }
        .ft-bottom { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-top: 48px; padding-top: 22px; border-top: 1px solid var(--line); font-size: 13.5px; }
        .ft-bottom a { color: var(--deep); font-weight: 600; }

        @media (max-width: 1020px) {
            .foot-grid { grid-template-columns: 1fr 1fr; }
            .hd-phone { display: none; }
            .m-banner-photo { aspect-ratio: 16 / 7; }
        }
        @media (max-width: 720px) {
            .topbar-in { font-size: 12.5px; gap: 8px; }
            .topbar-ico, .topbar .tb-text { display: none; }
            .hd-in { padding-top: 12px; padding-bottom: 12px; }
            .brand-logo { width: 44px; height: 44px; }
            .brand-logo img { max-width: 44px; max-height: 44px; }
            .brand-logo svg { width: 42px; height: 42px; }
            .brand-text small { font-size: 11.5px; }
            .hd-cta { display: none; }
            .btn-lg { height: 52px; padding: 0 24px; font-size: 16px; }
            .m-banner { padding: 40px 0 30px; }
            .m-banner-photo { aspect-ratio: 16 / 9; border-radius: 20px; margin-top: 24px; }
            .m-cta { padding: 46px 20px; border-radius: 28px; }
            .m-cta .btns .btn { flex: 1; }
            .ft { margin-top: 40px; padding-top: 44px; }
            .foot-grid { grid-template-columns: 1fr; gap: 28px; }
            .ft-bottom { flex-direction: column; text-align: center; margin-top: 34px; padding-bottom: 60px; }
        }
    </style>
    @stack('styles')
</head>
<body>

    {{-- ══════════ STRIP ══════════ --}}
    <div class="topbar" role="region" aria-label="Announcement">
        <div class="wrap topbar-in">
            <span class="topbar-ico">@include('school-site.modern.partials.icon', ['n' => 'sparkle'])</span>
            <span><b>{{ $stripLead }}</b>@if ($stripText !== '')<span class="tb-text">: {{ $stripText }}</span>@endif</span>
            @if ($apply)<a href="{{ $apply }}">Apply Now @include('school-site.modern.partials.icon', ['n' => 'right'])</a>
            @elseif ($tel !== '')<a href="tel:{{ $tel }}">Call {{ trim(explode(',', $c['phone'])[0]) }}</a>@endif
        </div>
    </div>

    {{-- ══════════ HEADER ══════════ --}}
    <header class="hd">
        <div class="wrap hd-in">
            <a href="{{ url('/') }}" class="brand" aria-label="{{ $c['school_name'] }} — Home">
                <span class="brand-logo">@include('school-site.classic.partials.logo', ['sealId' => 'logo'])</span>
                <span class="brand-text">
                    <b>{{ $nameHead }}@if ($nameTail !== '') <span>{{ $nameTail }}</span>@endif</b>
                    @if ($motto !== '' || $medium !== '')<small>{{ $motto !== '' ? '“' . $motto . '”' : '' }}{{ $motto !== '' && $medium !== '' ? ' · ' : '' }}{{ $medium }}</small>@endif
                </span>
            </a>
            <div class="hd-cta">
                @if ($tel !== '')<a class="hd-phone" href="tel:{{ $tel }}">@include('school-site.modern.partials.icon', ['n' => 'phone']){{ trim(explode(',', $c['phone'])[0]) }}</a>@endif
                <a class="hd-login" href="{{ $site->adminLoginUrl() }}" target="_blank" rel="noopener">Login</a>
                @if ($apply)<a class="btn btn-sm" href="{{ $apply }}">Apply Now</a>@endif
            </div>
        </div>
    </header>

    @include('school-site.modern.partials.menu')

    <main>
        @yield('content')
    </main>

    {{-- ══════════ CALL TO ACTION (the home page has its own) ══════════ --}}
    @if (($current ?? '') !== 'home' && ($apply || $tel !== ''))
        <section class="m-cta-band">
            <div class="wrap">
                <div class="m-cta">
                    <span class="m-cta-chip">@include('school-site.modern.partials.icon', ['n' => 'calendar'])<b>{{ $session !== '' ? $session : $stripLead }}</b></span>
                    <h2 class="h2">{!! ModernSite::highlight($stripLead, '') !!}</h2>
                    @if (!empty($c['cta_text']))<p class="lead-text">{{ $c['cta_text'] }}</p>@endif
                    <div class="btns">
                        @if ($apply)<a class="btn btn-lg" href="{{ $apply }}">Apply Now @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a>@endif
                        @if ($tel !== '')<a class="btn btn-outline btn-lg" href="tel:{{ $tel }}">@include('school-site.modern.partials.icon', ['n' => 'phone']) Call Us</a>@endif
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- ══════════ FOOTER ══════════ --}}
    <footer class="ft">
        <div class="wrap">
            <div class="foot-grid">
                <div>
                    <div class="ft-brand">
                        <span class="mb-logo">@include('school-site.classic.partials.logo', ['sealId' => 'foot'])</span>
                        <b>{{ $c['school_name'] }}</b>
                    </div>
                    <p class="ft-desc">{{ Str::limit(strip_tags($c['about_text'] ?? ''), 200) }}</p>
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
                        @if (!empty($c['affiliation_no']))<li>@include('school-site.modern.partials.icon', ['n' => 'award'])<span>{{ $board ?: 'CBSE' }} Aff. No. {{ $c['affiliation_no'] }}</span></li>@endif
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
