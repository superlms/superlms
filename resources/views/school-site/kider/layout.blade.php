{{--
    School-website template — master layout.
    Playful redesign matching the SUPERLMS platform website UI: pastel gradient
    heroes, floating blobs, rounded-full gradient buttons, Poppins type. Fully
    self-contained CSS, themed per-school via $theme (gradients derive from the
    school's own primary colour). Receives: $site, $c, $theme, $nav, $current
--}}
@php $asset = fn($p) => asset('school-templates/kider/' . $p); @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>{{ $title ?? $c['school_name'] }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($c['about_text'] ?? ''), 150) }}">
    @if(!empty($c['logo']))<link href="{{ \App\Models\SchoolWebsite::media($c['logo']) }}" rel="icon">@endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Baloo+2:wght@500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: {{ $theme['primary'] }};
            --primary-dark: {{ $theme['dark'] }};
            --faint: {{ $theme['light'] }};
            --bg: #FFFFFF;
            --bg2: #FBFAFF;
            --bg3: #FBF7FF;
            --text: #241C43;
            --text2: #3B345C;
            --text3: #6E6893;
            --text4: #A9A3C4;
            --border: rgba(80,60,120,0.14);
            --border2: rgba(80,60,120,0.09);
            --grad1: linear-gradient(135deg, {{ $theme['primary'] }} 0%, {{ $theme['dark'] }} 100%);
            /* fixed playful pastel accents — the multi-colour "confetti" feel */
            --c-pink: #F9A8D4; --c-yellow: #FCD34D; --c-blue: #93C5FD; --c-purple: #D8B4FE; --c-peach: #FDBA74; --c-mint: #6EE7B7;
            --hero-grad: linear-gradient(135deg, var(--faint) 0%, #F1E6FF 48%, #FFE8D6 100%);
            --shadow: 0 30px 70px rgba(80,60,120,0.18);
            --shadow2: 0 14px 40px rgba(80,60,120,0.14);
            --shadow3: 0 6px 22px rgba(80,60,120,0.10);
            --radius: 20px; --radius-sm: 14px; --radius-lg: 26px; --radius-xl: 34px; --radius-pill: 9999px;
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; overflow-x: hidden; }
        body { font-family: 'Poppins', sans-serif; background: var(--bg); color: var(--text); overflow-x: hidden; }
        img { max-width: 100%; display: block; }
        a { text-decoration: none; }

        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--faint); }
        ::-webkit-scrollbar-thumb { background: var(--grad1); border-radius: 8px; }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes float  { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-18px); } }
        @keyframes floatX { 0%,100% { transform: translate(0,0); } 50% { transform: translate(12px,-14px); } }
        @keyframes spinSlow { from { transform: rotate(0); } to { transform: rotate(360deg); } }
        .float1 { animation: float 5s ease-in-out infinite; }
        .float2 { animation: floatX 7s ease-in-out infinite; }
        .float3 { animation: float 6s ease-in-out infinite .6s; }

        /* decorative confetti blobs */
        .blob { position: absolute; border-radius: 50%; opacity: .75; z-index: 1; pointer-events: none; }
        .blob.sq { border-radius: 26%; }

        /* ══════════ NAVBAR ══════════ */
        .navbar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
            padding: 0 6%; height: 74px;
            display: flex; align-items: center; justify-content: space-between;
            background: rgba(255,255,255,0.86); backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border2); transition: box-shadow .3s, background .3s;
        }
        .navbar.scrolled { box-shadow: 0 8px 40px rgba(80,60,120,0.12); background: rgba(255,255,255,0.95); }
        .nav-logo { display: flex; align-items: center; gap: 12px; cursor: pointer; }
        .logo-icon {
            width: 44px; height: 44px; border-radius: 14px; overflow: hidden;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
            background: var(--grad1); box-shadow: 0 6px 18px rgba(80,60,120,0.28); position: relative;
        }
        .logo-icon img { width: 100%; height: 100%; object-fit: cover; }
        .logo-icon span { font-family: 'Baloo 2', cursive; font-weight: 800; color: #fff; font-size: 22px; }
        .logo-text { font-family: 'Baloo 2', cursive; font-size: 20px; font-weight: 700; letter-spacing: 0.2px; color: var(--text); max-width: 240px; line-height: 1.05; }
        .nav-links { display: flex; align-items: center; gap: 2px; list-style: none; }
        .nav-links a { color: var(--text3); font-size: 14px; font-weight: 500; padding: 9px 16px; border-radius: var(--radius-pill); transition: all .25s; }
        .nav-links a:hover, .nav-links a.active { color: var(--primary); background: var(--faint); }

        .btn {
            padding: 11px 26px; border-radius: var(--radius-pill); font-size: 14px; font-weight: 600;
            cursor: pointer; transition: transform .3s cubic-bezier(0.34,1.56,0.64,1), box-shadow .3s;
            border: none; font-family: 'Poppins', sans-serif;
            display: inline-flex; align-items: center; justify-content: center; gap: 8px; white-space: nowrap;
        }
        .btn-primary { background: var(--grad1); color: #fff; box-shadow: 0 8px 22px rgba(80,60,120,0.28); }
        .btn-primary:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 14px 34px rgba(80,60,120,0.34); }
        .btn-outline { background: #fff; border: 2px solid var(--primary); color: var(--primary); }
        .btn-outline:hover { background: var(--primary); color: #fff; transform: translateY(-3px); }
        .btn-dark { background: var(--text); color: #fff; }
        .btn-dark:hover { transform: translateY(-3px); box-shadow: var(--shadow2); }
        .btn-lg { padding: 15px 36px; font-size: 15px; }

        .hamburger { display: none; flex-direction: column; gap: 5px; cursor: pointer; background: none; border: none; padding: 4px; }
        .hamburger span { display: block; width: 24px; height: 2px; background: var(--primary); border-radius: 2px; }
        .mobile-nav { display: none; position: fixed; inset: 0; background: var(--hero-grad); z-index: 1001; flex-direction: column; align-items: center; justify-content: center; gap: 22px; }
        .mobile-nav.open { display: flex; animation: fadeIn .3s ease; }
        .mobile-nav-link { font-family: 'Baloo 2', cursive; font-size: 28px; font-weight: 700; color: var(--text2); }
        .mobile-nav-link:hover { color: var(--primary); }
        .mobile-nav-close { position: absolute; top: 24px; right: 24px; background: none; border: none; color: var(--text3); font-size: 28px; cursor: pointer; }

        /* ══════════ SHARED TYPOGRAPHY / SECTIONS ══════════ */
        .section { padding: 84px 6%; position: relative; }
        .section-inner { max-width: 1220px; margin: 0 auto; position: relative; z-index: 1; }
        .section-alt { background: linear-gradient(135deg, #FBF6FF 0%, var(--faint) 100%); }
        .section-tag {
            display: inline-flex; align-items: center; gap: 6px; padding: 7px 16px; border-radius: var(--radius-pill);
            font-size: 12px; font-weight: 700; letter-spacing: .4px; margin-bottom: 16px;
            background: #fff; border: 1.5px solid var(--border); color: var(--primary); box-shadow: var(--shadow3);
        }
        .section-title { font-family: 'Baloo 2', cursive; font-size: clamp(2rem, 3.8vw, 3rem); font-weight: 800; line-height: 1.12; letter-spacing: -0.3px; margin-bottom: 16px; color: var(--text); }
        .gradient-text { background: var(--grad1); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .section-subtitle { font-size: 15.5px; color: var(--text3); line-height: 1.85; max-width: 760px; font-weight: 400; }
        .section-head { text-align: center; max-width: 760px; margin: 0 auto 54px; }
        .section-head .section-subtitle { margin: 0 auto; }

        /* ══════════ PAGE HEADER ══════════ */
        .page-header { padding: 148px 6% 84px; background: var(--hero-grad); text-align: center; position: relative; overflow: hidden; }
        .page-header-content { position: relative; z-index: 2; }
        /* .grid-bg now paints soft confetti glows behind heroes & headers */
        .grid-bg { position: absolute; inset: 0; overflow: hidden; z-index: 0; }
        .grid-bg::before, .grid-bg::after { content: ''; position: absolute; border-radius: 50%; filter: blur(64px); opacity: .55; }
        .grid-bg::before { width: 360px; height: 360px; background: var(--c-pink); top: -90px; left: -70px; }
        .grid-bg::after  { width: 320px; height: 320px; background: var(--c-blue); bottom: -110px; right: -60px; }
        .breadcrumb-nav { font-size: 13.5px; color: var(--text3); margin-top: 12px; }
        .breadcrumb-nav a { color: var(--primary); font-weight: 600; }

        /* ══════════ HERO (home) ══════════ */
        .hero { padding: 140px 6% 96px; background: var(--hero-grad); position: relative; overflow: hidden; }
        .hero-inner { max-width: 1220px; margin: 0 auto; display: grid; grid-template-columns: 1.05fr 0.95fr; gap: 56px; align-items: center; position: relative; z-index: 2; }
        .hero-title { font-family: 'Baloo 2', cursive; font-size: clamp(2.6rem, 5.2vw, 4.2rem); font-weight: 800; line-height: 1.04; letter-spacing: -0.5px; color: var(--text); margin-bottom: 20px; }
        .hero-sub { font-size: 16.5px; color: var(--text2); line-height: 1.85; margin-bottom: 32px; max-width: 540px; }
        .hero-actions { display: flex; gap: 14px; flex-wrap: wrap; }
        .hero-visual { position: relative; aspect-ratio: 1/1; border-radius: 44% 56% 58% 42% / 48% 44% 56% 52%; overflow: hidden; box-shadow: var(--shadow); border: 6px solid #fff; z-index: 2; }
        .hero-visual img { width: 100%; height: 100%; object-fit: cover; }
        .hero-visual.is-logo { background: var(--grad1); display: flex; align-items: center; justify-content: center; }
        .hero-visual.is-logo img { width: 62%; height: 62%; object-fit: contain; }
        .hero-media { position: relative; }
        .hero-badge {
            position: absolute; bottom: 18px; left: -14px; z-index: 3;
            background: #fff; border-radius: 18px; padding: 12px 18px 12px 12px;
            box-shadow: var(--shadow2); display: flex; align-items: center; gap: 11px; max-width: 230px;
        }
        .hero-badge .dot { width: 40px; height: 40px; border-radius: 13px; background: var(--grad1); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 19px; flex-shrink: 0; }
        .hero-badge b { font-size: 13.5px; color: var(--text); font-weight: 700; display: block; line-height: 1.2; }
        .hero-badge span { font-size: 11.5px; color: var(--text3); }

        /* ══════════ ABOUT SPLIT ══════════ */
        .split { display: grid; grid-template-columns: 1fr 1fr; gap: 56px; align-items: center; }
        .split-media { border-radius: var(--radius-xl); overflow: hidden; box-shadow: var(--shadow2); border: 6px solid #fff; aspect-ratio: 4/3; position: relative; }
        .split-media img { width: 100%; height: 100%; object-fit: cover; }
        .split-media.is-logo { background: var(--grad1); display: flex; align-items: center; justify-content: center; }
        .split-media.is-logo img { width: 55%; height: 55%; object-fit: contain; }
        .body-text { font-size: 15.5px; color: var(--text3); line-height: 1.9; margin-bottom: 16px; }

        /* ══════════ CARD GRID ══════════ */
        .cards-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 26px; max-width: 1220px; margin: 0 auto; }
        .info-card { background: #fff; border: 1px solid var(--border2); border-radius: var(--radius-lg); padding: 30px; transition: transform .3s cubic-bezier(0.34,1.56,0.64,1), box-shadow .3s; box-shadow: var(--shadow3); }
        .info-card:hover { transform: translateY(-6px); box-shadow: var(--shadow2); }
        .info-icon { width: 58px; height: 58px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 26px; margin-bottom: 18px; background: var(--grad1); color: #fff; box-shadow: 0 8px 20px rgba(80,60,120,0.24); }
        .info-title { font-family: 'Baloo 2', cursive; font-size: 19px; font-weight: 700; color: var(--text); margin-bottom: 8px; }
        .info-desc { font-size: 13.5px; color: var(--text3); line-height: 1.75; }

        /* ══════════ CLASS CARD ══════════ */
        .class-card { background: #fff; border: 1px solid var(--border2); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow3); transition: transform .3s cubic-bezier(0.34,1.56,0.64,1), box-shadow .3s; }
        .class-card:hover { transform: translateY(-6px); box-shadow: var(--shadow2); }
        .class-thumb { aspect-ratio: 16/10; overflow: hidden; background: var(--faint); }
        .class-thumb img { width: 100%; height: 100%; object-fit: cover; transition: transform .5s; }
        .class-card:hover .class-thumb img { transform: scale(1.06); }
        .class-body { padding: 22px 24px 26px; }
        .class-name { font-family: 'Baloo 2', cursive; font-size: 22px; font-weight: 700; color: var(--text); margin-bottom: 16px; text-align: center; }
        .class-meta { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; text-align: center; }
        .class-meta .k { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: var(--primary); margin-bottom: 2px; }
        .class-meta .v { font-size: 12px; color: var(--text3); }
        .class-meta > div { border-top: 2px solid var(--faint); padding-top: 10px; }

        /* ══════════ TEAM CARD ══════════ */
        .team-card { background: #fff; border: 1px solid var(--border2); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow3); transition: transform .3s cubic-bezier(0.34,1.56,0.64,1), box-shadow .3s; text-align: center; }
        .team-card:hover { transform: translateY(-6px); box-shadow: var(--shadow2); }
        .team-photo { aspect-ratio: 1/1; overflow: hidden; background: var(--faint); }
        .team-photo img { width: 100%; height: 100%; object-fit: cover; }
        .team-body { padding: 22px; }
        .team-name { font-family: 'Baloo 2', cursive; font-size: 18px; font-weight: 700; color: var(--text); }
        .team-role { font-size: 13px; color: var(--primary); font-weight: 600; margin-top: 2px; }

        /* ══════════ CONTACT CARD ══════════ */
        .contact-card { display: flex; gap: 16px; align-items: flex-start; background: #fff; border: 1px solid var(--border2); border-radius: var(--radius); padding: 22px; box-shadow: var(--shadow3); margin-bottom: 16px; transition: transform .3s cubic-bezier(0.34,1.56,0.64,1); }
        .contact-card:hover { transform: translateY(-4px); }
        .contact-card .info-icon { margin-bottom: 0; flex-shrink: 0; width: 50px; height: 50px; font-size: 22px; border-radius: 15px; }
        .contact-card h5 { font-size: 14.5px; font-weight: 700; color: var(--text); margin-bottom: 4px; }
        .contact-card p { font-size: 13.5px; color: var(--text3); line-height: 1.6; word-break: break-word; }

        /* ══════════ FORM ══════════ */
        .form-card { background: #fff; border: 1px solid var(--border2); border-radius: var(--radius-xl); padding: 34px; box-shadow: var(--shadow2); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-field { display: flex; flex-direction: column; }
        .form-field.full { grid-column: 1 / -1; }
        .form-field label { font-size: 12.5px; font-weight: 600; color: var(--text2); margin-bottom: 6px; }
        .form-field input, .form-field textarea {
            font-family: 'Poppins', sans-serif; font-size: 14px; color: var(--text);
            background: var(--bg2); border: 1.5px solid var(--border2); border-radius: 14px;
            padding: 12px 16px; transition: all .2s; width: 100%;
        }
        .form-field input:focus, .form-field textarea:focus { outline: none; border-color: var(--primary); background: #fff; box-shadow: 0 0 0 4px var(--faint); }
        .form-note { font-size: 13px; font-weight: 600; }

        /* ══════════ CTA ══════════ */
        .cta-section { padding: 96px 6%; }
        .cta-card { background: var(--grad1); border-radius: var(--radius-xl); padding: 74px 56px; max-width: 940px; margin: 0 auto; position: relative; overflow: hidden; box-shadow: var(--shadow); text-align: center; }
        .cta-card::before { content: ''; position: absolute; top: -90px; right: -60px; width: 300px; height: 300px; background: rgba(255,255,255,0.16); border-radius: 50%; }
        .cta-card::after  { content: ''; position: absolute; bottom: -110px; left: -70px; width: 320px; height: 320px; background: rgba(255,255,255,0.10); border-radius: 50%; }
        .cta-title { font-family: 'Baloo 2', cursive; font-size: clamp(2rem, 4.2vw, 3rem); font-weight: 800; line-height: 1.14; margin-bottom: 18px; position: relative; z-index: 1; color: #fff; }
        .cta-desc { font-size: 15.5px; color: rgba(255,255,255,0.92); margin-bottom: 34px; position: relative; z-index: 1; line-height: 1.8; max-width: 620px; margin-left: auto; margin-right: auto; }
        .cta-actions { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; position: relative; z-index: 1; }
        .cta-card .btn-primary { background: #fff; color: var(--primary); box-shadow: 0 10px 28px rgba(0,0,0,0.18); }
        .cta-card .btn-outline { background: transparent; border-color: rgba(255,255,255,0.8); color: #fff; }
        .cta-card .btn-outline:hover { background: #fff; color: var(--primary); }

        /* ══════════ FOOTER ══════════ */
        footer { background: var(--bg3); border-top: 1px solid var(--border2); padding: 76px 6% 30px; position: relative; overflow: hidden; }
        .footer-inner { max-width: 1220px; margin: 0 auto; position: relative; z-index: 1; }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1.4fr; gap: 48px; margin-bottom: 52px; }
        .footer-brand-desc { font-size: 13.5px; color: var(--text3); line-height: 1.85; margin: 14px 0 20px; max-width: 380px; }
        .footer-socials { display: flex; gap: 10px; }
        .social-btn { width: 38px; height: 38px; border-radius: 50%; background: #fff; border: 1px solid var(--border2); display: flex; align-items: center; justify-content: center; color: var(--text3); transition: all .25s; }
        .social-btn:hover { background: var(--grad1); border-color: transparent; color: #fff; transform: translateY(-3px); }
        .footer-col-title { font-size: 11.5px; font-weight: 700; color: var(--text); text-transform: uppercase; letter-spacing: 1.4px; margin-bottom: 18px; }
        .footer-links { list-style: none; display: flex; flex-direction: column; gap: 10px; }
        .footer-links a { color: var(--text3); font-size: 13.5px; transition: all .2s; }
        .footer-links a:hover { color: var(--primary); }
        .footer-contact p { font-size: 13.5px; color: var(--text3); line-height: 1.7; margin-bottom: 9px; display: flex; gap: 8px; }
        .footer-bottom { border-top: 1px solid var(--border2); padding-top: 22px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
        .footer-bottom-text { font-size: 12.5px; color: var(--text4); }
        .footer-bottom-text a { color: var(--primary); font-weight: 700; }

        /* ══════════ BACK TO TOP ══════════ */
        .back-to-top { position: fixed; right: 26px; bottom: 26px; width: 48px; height: 48px; border-radius: 15px; background: var(--grad1); color: #fff; display: none; align-items: center; justify-content: center; z-index: 900; box-shadow: var(--shadow2); font-size: 20px; transition: transform .3s; }
        .back-to-top:hover { transform: translateY(-3px); }
        .back-to-top.show { display: flex; }

        .reveal { opacity: 0; transform: translateY(28px); transition: opacity .7s cubic-bezier(.16,1,.3,1), transform .7s cubic-bezier(.16,1,.3,1); }
        .reveal.shown { opacity: 1; transform: none; }
        @media (prefers-reduced-motion: reduce) { .reveal { opacity: 1 !important; transform: none !important; } .float1,.float2,.float3 { animation: none !important; } }

        /* ══════════ RESPONSIVE ══════════ */
        @media (max-width: 1024px) { .cards-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 900px) {
            .hero-inner, .split { grid-template-columns: 1fr; gap: 44px; }
            .hero-visual { max-width: 440px; margin: 0 auto; width: 100%; }
            .footer-grid { grid-template-columns: 1fr 1fr; gap: 36px; }
        }
        @media (max-width: 768px) {
            .nav-links, .nav-cta { display: none; }
            .hamburger { display: flex; }
            .cards-grid { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
            .page-header, .hero { padding-left: 5%; padding-right: 5%; }
            .cta-card { padding: 48px 26px; }
        }
        @media (max-width: 480px) {
            .section { padding: 60px 5%; }
            .footer-grid { grid-template-columns: 1fr; }
            .footer-bottom { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>

    {{-- ══════════ NAVBAR ══════════ --}}
    <nav class="navbar" id="navbar">
        <a href="{{ url('/') }}" class="nav-logo">
            <div class="logo-icon">
                @if(!empty($c['logo']))
                    <img src="{{ \App\Models\SchoolWebsite::media($c['logo']) }}" alt="{{ $c['school_name'] }}">
                @else
                    <span>{{ strtoupper(substr($c['school_name'], 0, 1)) }}</span>
                @endif
            </div>
            <div class="logo-text">{{ \Illuminate\Support\Str::limit($c['school_name'], 26) }}</div>
        </a>

        <ul class="nav-links">
            @foreach ($nav as $slug => $label)
                <li><a href="{{ $slug === 'home' ? url('/') : url($slug) }}" class="{{ $current === $slug ? 'active' : '' }}">{{ $label }}</a></li>
            @endforeach
        </ul>

        <div class="nav-cta">
            <a href="{{ $site->adminLoginUrl() }}" target="_blank" rel="noopener" class="btn btn-primary">Login</a>
        </div>

        <button class="hamburger" id="hamburger" aria-label="Open menu"><span></span><span></span><span></span></button>
    </nav>

    {{-- Mobile Nav --}}
    <div class="mobile-nav" id="mobileNav">
        <button class="mobile-nav-close" id="mobileClose">✕</button>
        @foreach ($nav as $slug => $label)
            <a href="{{ $slug === 'home' ? url('/') : url($slug) }}" class="mobile-nav-link">{{ $label }}</a>
        @endforeach
        <a href="{{ $site->adminLoginUrl() }}" target="_blank" rel="noopener" class="btn btn-primary btn-lg">Login</a>
    </div>

    {{-- ══════════ PAGE CONTENT ══════════ --}}
    @yield('content')

    {{-- ══════════ FOOTER ══════════ --}}
    <footer>
        <div class="footer-inner">
            <div class="footer-grid">
                <div>
                    <a href="{{ url('/') }}" class="nav-logo" style="margin-bottom:4px;">
                        <div class="logo-icon">
                            @if(!empty($c['logo']))
                                <img src="{{ \App\Models\SchoolWebsite::media($c['logo']) }}" alt="{{ $c['school_name'] }}">
                            @else
                                <span>{{ strtoupper(substr($c['school_name'], 0, 1)) }}</span>
                            @endif
                        </div>
                        <div class="logo-text">{{ \Illuminate\Support\Str::limit($c['school_name'], 22) }}</div>
                    </a>
                    <p class="footer-brand-desc">{{ \Illuminate\Support\Str::limit(strip_tags($c['about_text'] ?? ''), 170) }}</p>
                    <div class="footer-socials">
                        @if(!empty($c['facebook']))<a class="social-btn" href="{{ $c['facebook'] }}" target="_blank" rel="noopener" title="Facebook"><svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M9 8H6v4h3v12h5V12h3.6l.4-4h-4V6.3c0-1 .2-1.3 1.1-1.3H18V0h-3.6C10.9 0 9 1.6 9 4.7V8z"/></svg></a>@endif
                        @if(!empty($c['instagram']))<a class="social-btn" href="{{ $c['instagram'] }}" target="_blank" rel="noopener" title="Instagram"><svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.64.07-4.85.07s-3.58-.01-4.85-.07c-3.26-.15-4.77-1.7-4.92-4.92C2.17 15.58 2.16 15.2 2.16 12s.01-3.58.07-4.85C2.38 3.92 3.9 2.38 7.15 2.23 8.42 2.17 8.8 2.16 12 2.16zm0 3.68A6.16 6.16 0 1018.16 12 6.16 6.16 0 0012 5.84zm0 10.16A4 4 0 118 12a4 4 0 014 4zm6.41-11.85a1.44 1.44 0 11-1.44-1.44 1.44 1.44 0 011.44 1.44z"/></svg></a>@endif
                        @if(!empty($c['youtube']))<a class="social-btn" href="{{ $c['youtube'] }}" target="_blank" rel="noopener" title="YouTube"><svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M23.5 6.19a3.02 3.02 0 00-2.12-2.14C19.5 3.55 12 3.55 12 3.55s-7.5 0-9.38.5A3.02 3.02 0 00.5 6.19C0 8.07 0 12 0 12s0 3.93.5 5.81a3.02 3.02 0 002.12 2.14c1.88.5 9.38.5 9.38.5s7.5 0 9.38-.5a3.02 3.02 0 002.12-2.14C24 15.93 24 12 24 12s0-3.93-.5-5.81zM9.55 15.57V8.43L15.82 12z"/></svg></a>@endif
                        @if(!empty($c['twitter']))<a class="social-btn" href="{{ $c['twitter'] }}" target="_blank" rel="noopener" title="Twitter"><svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M24 4.56c-.89.39-1.83.65-2.83.77a4.93 4.93 0 002.16-2.72c-.95.56-2 .97-3.13 1.19A4.92 4.92 0 0011.03 8.4a13.96 13.96 0 01-10.13-5.14 4.92 4.92 0 001.52 6.57A4.9 4.9 0 01.96 9.2v.06a4.93 4.93 0 003.95 4.83 4.96 4.96 0 01-2.22.08 4.93 4.93 0 004.6 3.42A9.87 9.87 0 010 19.54a13.94 13.94 0 007.55 2.21c9.06 0 14-7.5 14-14v-.64A9.9 9.9 0 0024 4.56z"/></svg></a>@endif
                        @if(!empty($c['telegram']))<a class="social-btn" href="{{ $c['telegram'] }}" target="_blank" rel="noopener" title="Telegram"><svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M23.07 3.5 1.7 11.74c-1.2.48-1.19 1.16-.22 1.46l5.48 1.71 2.12 6.5c.26.71.13.99.87.99.57 0 .82-.26 1.14-.57l2.73-2.65 5.67 4.19c1.05.58 1.8.28 2.06-.97l3.73-17.56c.38-1.53-.58-2.22-1.61-1.74z"/></svg></a>@endif
                        @if(!empty($c['email']))<a class="social-btn" href="mailto:{{ $c['email'] }}" title="Email"><svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M0 3v18h24V3H0zm21.52 2L12 12.71 2.48 5h19.04zM2 19V6.18l10 8.1 10-8.1V19H2z"/></svg></a>@endif
                    </div>
                </div>

                <div>
                    <div class="footer-col-title">Quick Links</div>
                    <ul class="footer-links">
                        @foreach ($nav as $slug => $label)
                            <li><a href="{{ $slug === 'home' ? url('/') : url($slug) }}">{{ $label }}</a></li>
                        @endforeach
                        <li><a href="{{ $site->adminLoginUrl() }}" target="_blank" rel="noopener">Admin / Staff Login</a></li>
                    </ul>
                </div>

                <div>
                    <div class="footer-col-title">Get In Touch</div>
                    <div class="footer-contact">
                        @if(!empty($c['address']))<p>📍 <span>{{ $c['address'] }}</span></p>@endif
                        @if(!empty($c['phone']))<p>📞 <span>{{ $c['phone'] }}</span></p>@endif
                        @if(!empty($c['email']))<p>✉️ <span>{{ $c['email'] }}</span></p>@endif
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <p class="footer-bottom-text">&copy; {{ date('Y') }} {{ $c['school_name'] }}. All Rights Reserved.</p>
                <p class="footer-bottom-text">Powered by <a href="https://superlms.in" target="_blank" rel="noopener">SUPERLMS</a></p>
            </div>
        </div>
    </footer>

    <a href="#" class="back-to-top" id="backToTop" aria-label="Back to top">↑</a>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var mobileNav = document.getElementById('mobileNav');
            var hamburger = document.getElementById('hamburger');
            var mobileClose = document.getElementById('mobileClose');
            if (hamburger)   hamburger.addEventListener('click', function () { mobileNav && mobileNav.classList.add('open'); });
            if (mobileClose) mobileClose.addEventListener('click', function () { mobileNav && mobileNav.classList.remove('open'); });
            document.querySelectorAll('.mobile-nav-link, .mobile-nav a').forEach(function (l) {
                l.addEventListener('click', function () { mobileNav && mobileNav.classList.remove('open'); });
            });

            var navbar = document.getElementById('navbar');
            var back = document.getElementById('backToTop');
            window.addEventListener('scroll', function () {
                if (navbar) navbar.classList.toggle('scrolled', window.scrollY > 40);
                if (back) back.classList.toggle('show', window.scrollY > 400);
            });
            if (back) back.addEventListener('click', function (e) { e.preventDefault(); window.scrollTo({ top: 0, behavior: 'smooth' }); });

            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('shown'); io.unobserve(en.target); } });
            }, { threshold: 0.12 });
            document.querySelectorAll('.reveal').forEach(function (el) { io.observe(el); });
        });
    </script>
    @stack('scripts')
</body>
</html>
