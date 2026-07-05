{{-- ══════════════════════════════════════════════════════════════
     SHARED WEBSITE <head> + COMMON CSS
     Usage: @include('components.website.partials.head', ['title' => '...'])
     Pages may add their own <style> block right after this include.
══════════════════════════════════════════════════════════════ --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="google-site-verification" content="GdMracC0ZkQ_UWHR922wYADBk2AS9KO5yALR8BFNFdY" />
  <title>{{ $title ?? 'SUPERLMS' }} — SUPERLMS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link rel="icon" type="image/png" href="{{ asset('website-image/Group 11525.png') }}">
  @include('partials.pwa-head')

  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <!-- Google tag (gtag.js) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-7S4FD1GMPK"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-7S4FD1GMPK');
  </script>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <style>
    :root {
      --pink: #DB57B2;
      --pink-dark: #B83D92;
      --violet: #6F56FE;
      --violet-dark: #5540D4;
      --primary-faint: #F9EDF5;
      --secondary-faint: #F0EDFF;
      --bg: #FFFFFF;
      --bg2: #FAFAFA;
      --bg3: #F7F4FF;
      --bg4: #EDE8FF;
      --text: #1A0F2E;
      --text2: #2D1B4E;
      --text3: #6B5B8A;
      --text4: #A99CC0;
      --border: rgba(111,86,254,0.15);
      --border2: rgba(111,86,254,0.08);
      --border-pink: rgba(219,87,178,0.18);
      --grad1: linear-gradient(135deg, #DB57B2 0%, #6F56FE 100%);
      --grad2: linear-gradient(135deg, #E878C4 0%, #DB57B2 40%, #6F56FE 100%);
      --grad-pink: linear-gradient(135deg, #DB57B2 0%, #B83D92 100%);
      --grad-violet: linear-gradient(135deg, #6F56FE 0%, #5540D4 100%);
      --shadow: 0 24px 64px rgba(111,86,254,0.18);
      --shadow2: 0 8px 32px rgba(111,86,254,0.12);
      --shadow3: 0 2px 12px rgba(111,86,254,0.08);
      --radius: 16px;
      --radius-sm: 10px;
      --radius-lg: 24px;
      --radius-xl: 32px;
    }

    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
    html { scroll-behavior: smooth; overflow-x: hidden; }
    body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); overflow-x: hidden; }

    ::-webkit-scrollbar { width: 4px; }
    ::-webkit-scrollbar-track { background: #f0eaff; }
    ::-webkit-scrollbar-thumb { background: var(--grad1); border-radius: 2px; }

    @keyframes shimmer { 0% { background-position: -200% center; } 100% { background-position: 200% center; } }
    @keyframes fadeUp { from { opacity: 0; transform: translateY(32px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

    /* ══════════════════ NAVBAR ══════════════════ */
    .navbar {
      position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
      padding: 0 6%; height: 72px;
      display: flex; align-items: center; justify-content: space-between;
      background: rgba(255,255,255,0.95); backdrop-filter: blur(24px);
      border-bottom: 1px solid var(--border2);
      box-shadow: 0 4px 40px rgba(111,86,254,0.08);
    }
    .nav-logo { display: flex; align-items: center; gap: 12px; cursor: pointer; text-decoration: none; }
    .logo-icon {
      width: 40px; height: 40px; background: var(--grad1);
      border-radius: 10px; display: flex; align-items: center; justify-content: center;
      box-shadow: 0 4px 16px rgba(111,86,254,0.35); position: relative; overflow: hidden;
    }
    .logo-icon::after {
      content: ''; position: absolute; top: -50%; left: -50%;
      width: 200%; height: 200%;
      background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.25) 50%, transparent 70%);
      animation: shimmer 3s infinite;
    }
    .logo-text { font-family: 'Cormorant Garamond', serif; font-size: 20px; font-weight: 700; letter-spacing: 0.5px; color: var(--text); }
    .logo-text span { background: var(--grad1); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    .nav-links { display: flex; align-items: center; gap: 2px; list-style: none; }
    .nav-links a {
      color: var(--text3); text-decoration: none; font-size: 13px; font-weight: 500;
      padding: 8px 16px; border-radius: 8px; transition: all .25s;
    }
    .nav-links a:hover, .nav-links a.active { color: var(--violet); background: var(--secondary-faint); }
    .btn {
      padding: 9px 22px; border-radius: 9px; font-size: 13px; font-weight: 600;
      cursor: pointer; transition: all .3s cubic-bezier(0.34,1.56,0.64,1);
      border: none; font-family: 'DM Sans', sans-serif; text-decoration: none;
      display: inline-flex; align-items: center; justify-content: center;
    }
    .btn-primary { background: var(--grad1); color: #fff; box-shadow: 0 4px 16px rgba(111,86,254,0.3); }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(111,86,254,0.45); }
    .btn-outline {
      background: transparent; border: 1.5px solid var(--violet); color: var(--violet);
    }
    .btn-outline:hover { background: var(--violet); color: #fff; transform: translateY(-1px); }
    .btn-lg { padding: 13px 32px; font-size: 15px; border-radius: 11px; }
    .btn-xl { padding: 15px 40px; font-size: 16px; border-radius: 13px; }
    .hamburger { display: none; flex-direction: column; gap: 5px; cursor: pointer; background: none; border: none; padding: 4px; }
    .hamburger span { display: block; width: 22px; height: 1.5px; background: var(--text3); border-radius: 2px; }

    /* ══════════════════ MOBILE NAV ══════════════════ */
    .mobile-nav {
      display: none; position: fixed; inset: 0;
      background: rgba(255,255,255,0.98); z-index: 1001;
      flex-direction: column; align-items: center; justify-content: center; gap: 24px;
    }
    .mobile-nav.open { display: flex; animation: fadeIn .3s ease; }
    .mobile-nav-link {
      font-family: 'Cormorant Garamond', serif; font-size: 28px; font-weight: 600;
      color: var(--text2); text-decoration: none; transition: all .2s;
    }
    .mobile-nav-close { position: absolute; top: 24px; right: 24px; background: none; border: none; color: var(--text3); font-size: 24px; cursor: pointer; }

    /* ══════════════════ PAGE HEADER ══════════════════ */
    .page-header {
      padding: 140px 6% 80px;
      background: linear-gradient(135deg, var(--primary-faint) 0%, var(--secondary-faint) 100%);
      border-bottom: 1px solid var(--border2);
      text-align: center; position: relative; overflow: hidden;
    }
    .page-header .grid-bg {
      position: absolute; inset: 0;
      background-image: linear-gradient(rgba(111,86,254,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(111,86,254,0.04) 1px, transparent 1px);
      background-size: 48px 48px;
    }
    .page-header-content { position: relative; z-index: 1; }
    .section-tag {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 5px 14px; border-radius: 50px;
      font-size: 11px; font-weight: 700; letter-spacing: 1.5px;
      margin-bottom: 16px; text-transform: uppercase;
    }
    .tag-dual { background: linear-gradient(135deg, var(--primary-faint), var(--secondary-faint)); border: 1px solid var(--border); color: var(--violet); }
    .tag-violet { background: var(--secondary-faint); border: 1px solid var(--border); color: var(--violet); }
    .tag-pink { background: var(--primary-faint); border: 1px solid var(--border-pink); color: var(--pink-dark); }
    .section-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(2.2rem, 4vw, 3.4rem);
      font-weight: 600; line-height: 1.1; letter-spacing: -0.5px;
      margin-bottom: 16px; color: var(--text);
    }
    .gradient-text {
      background: var(--grad2); -webkit-background-clip: text;
      -webkit-text-fill-color: transparent; background-clip: text;
    }
    .section-subtitle {
      font-size: 15px; color: var(--text3); line-height: 1.8;
      max-width: 820px; margin: 0 auto; font-weight: 400;
    }

    /* ══════════════════ SECTIONS / CARD GRID ══════════════════ */
    .section { padding: 80px 6%; }
    /* Alternating tinted band — gives sub-pages the same gradient/white rhythm as the homepage */
    .section-alt {
      background: linear-gradient(135deg, var(--primary-faint) 0%, var(--secondary-faint) 100%);
      border-top: 1px solid var(--border2);
      border-bottom: 1px solid var(--border2);
    }
    .section-head { text-align: center; max-width: 760px; margin: 0 auto 56px; }
    .section-head .section-title { font-size: clamp(1.9rem, 3.4vw, 2.8rem); }

    .cards-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1px;
      background: var(--border2);
      border-radius: var(--radius-lg);
      overflow: hidden;
      border: 1px solid var(--border2);
      max-width: 1280px;
      margin: 0 auto;
    }
    .feature-card {
      background: #fff;
      padding: 32px 28px;
      transition: all .3s;
      position: relative;
    }
    .feature-card::before {
      content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
      background: var(--grad1); transform: scaleX(0); transform-origin: left; transition: transform .35s;
    }
    .feature-card:hover::before { transform: scaleX(1); }
    .feature-card:hover { background: var(--bg3); }
    .feature-icon-wrap {
      width: 48px; height: 48px; border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 22px; margin-bottom: 18px;
      background: var(--secondary-faint);
    }
    .feature-title { font-size: 16px; font-weight: 600; color: var(--text); margin-bottom: 10px; }
    .feature-desc { font-size: 13px; color: var(--text3); line-height: 1.75; }
    .feature-tag {
      display: inline-block; margin-top: 14px;
      padding: 3px 10px; border-radius: 50px;
      font-size: 11px; font-weight: 600; letter-spacing: 0.5px;
    }
    .tag-v { background: var(--secondary-faint); color: var(--violet); }
    .tag-p { background: var(--primary-faint); color: var(--pink-dark); }

    /* ══════════════════ CTA SECTION ══════════════════ */
    .cta-section {
      padding: 100px 6%; text-align: center;
      position: relative; overflow: hidden; background: #fff;
    }
    .cta-bg {
      position: absolute; inset: 0;
      background: radial-gradient(ellipse at 30% 50%, rgba(219,87,178,0.07) 0%, transparent 55%),
        radial-gradient(ellipse at 70% 50%, rgba(111,86,254,0.07) 0%, transparent 55%);
    }
    .cta-card {
      background: linear-gradient(160deg, rgba(219,87,178,0.06) 0%, rgba(111,86,254,0.06) 100%);
      border: 1px solid var(--border); border-radius: var(--radius-xl);
      padding: 80px 60px; max-width: 860px; margin: 0 auto;
      position: relative; overflow: hidden; box-shadow: var(--shadow);
    }
    .cta-card::before {
      content: ''; position: absolute; top: -80px; right: -60px;
      width: 280px; height: 280px; background: rgba(111,86,254,0.07);
      border-radius: 50%; filter: blur(60px);
    }
    .cta-card::after {
      content: ''; position: absolute; bottom: -60px; left: -60px;
      width: 240px; height: 240px; background: rgba(219,87,178,0.07);
      border-radius: 50%; filter: blur(60px);
    }
    .cta-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(2rem, 4vw, 3rem);
      font-weight: 600; line-height: 1.15; margin-bottom: 20px;
      position: relative; z-index: 1; color: var(--text);
    }
    .cta-desc { font-size: 15px; color: var(--text3); margin-bottom: 40px; position: relative; z-index: 1; line-height: 1.75; }
    .cta-actions {
      display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; position: relative; z-index: 1;
    }

    /* ══════════════════ FOOTER ══════════════════ */
    footer { background: var(--bg3); border-top: 1px solid var(--border2); padding: 80px 6% 32px; }
    .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 48px; margin-bottom: 60px; }
    .footer-brand-desc { font-size: 13px; color: var(--text3); line-height: 1.8; margin: 14px 0 22px; }
    .footer-socials { display: flex; gap: 8px; }
    .social-btn { width: 34px; height: 34px; border-radius: 50%; background: var(--bg2); border: 1px solid var(--border2); display: flex; align-items: center; justify-content: center; color: var(--text3); text-decoration: none; transition: all .2s; }
    .social-btn:hover { background: var(--violet); border-color: var(--violet); color: white; }
    .footer-col-title { font-size: 11px; font-weight: 700; color: var(--text); text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 20px; }
    .footer-links { list-style: none; display: flex; flex-direction: column; gap: 9px; }
    .footer-links a { color: var(--text3); text-decoration: none; font-size: 13px; transition: all .2s; }
    .footer-links a:hover { color: var(--violet); }
    .footer-bottom { border-top: 1px solid var(--border2); padding-top: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; }
    .footer-bottom-text { font-size: 12px; color: var(--text4); }
    .footer-bottom-links { display: flex; gap: 20px; }
    .footer-bottom-links a { font-size: 12px; color: var(--text4); text-decoration: none; transition: color .2s; }
    .footer-bottom-links a:hover { color: var(--violet); }

    /* ══════════════════ RESPONSIVE ══════════════════ */
    @media (max-width: 1024px) { .cards-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 768px) {
      .nav-links, .nav-cta { display: none; }
      .hamburger { display: flex; }
      .cards-grid { grid-template-columns: 1fr; }
      .cta-card { padding: 40px 24px; }
      .page-header { padding: 110px 5% 60px; }
      .footer-grid { grid-template-columns: 1fr 1fr; gap: 36px; }
    }
    @media (max-width: 480px) {
      .section { padding: 60px 4%; }
      .cta-card { padding: 32px 18px; }
      .section-title { font-size: clamp(1.9rem, 7vw, 2.8rem); }
      .footer-grid { grid-template-columns: 1fr; }
      .footer-bottom { flex-direction: column; text-align: center; }
      .footer-bottom-links { justify-content: center; flex-wrap: wrap; }
    }
  </style>
</head>
<body>
