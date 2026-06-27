<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pricing — SUPERLMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="icon" type="image/png" href="{{ asset('website-image/Group 11525.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
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
            --text: #1A0F2E;
            --text2: #2D1B4E;
            --text3: #6B5B8A;
            --text4: #A99CC0;
            --border: rgba(111, 86, 254, 0.15);
            --border2: rgba(111, 86, 254, 0.08);
            --border-pink: rgba(219, 87, 178, 0.18);
            --grad1: linear-gradient(135deg, #DB57B2 0%, #6F56FE 100%);
            --grad2: linear-gradient(135deg, #E878C4 0%, #DB57B2 40%, #6F56FE 100%);
            --grad-pink: linear-gradient(135deg, #DB57B2 0%, #B83D92 100%);
            --grad-violet: linear-gradient(135deg, #6F56FE 0%, #5540D4 100%);
            --shadow: 0 24px 64px rgba(111, 86, 254, 0.18);
            --shadow2: 0 8px 32px rgba(111, 86, 254, 0.12);
            --shadow3: 0 2px 12px rgba(111, 86, 254, 0.08);
            --radius: 16px;
            --radius-sm: 10px;
            --radius-lg: 24px;
            --radius-xl: 32px;
        }

        *,
        *::before,
        *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
            overflow-x: hidden;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            overflow-x: hidden;
        }

        ::-webkit-scrollbar {
            width: 4px;
        }

        ::-webkit-scrollbar-track {
            background: #f0eaff;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--grad1);
            border-radius: 2px;
        }

        @keyframes shimmer {
            0% {
                background-position: -200% center;
            }

            100% {
                background-position: 200% center;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(28px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-40px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(40px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes popCard {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-6px);
            }
        }

        /* ══════════════════ NAVBAR ══════════════════ */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 0 6%;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(24px);
            border-bottom: 1px solid var(--border2);
            box-shadow: 0 4px 40px rgba(111, 86, 254, 0.08);
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: var(--grad1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 16px rgba(111, 86, 254, 0.35);
            position: relative;
            overflow: hidden;
        }

        .logo-icon::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.25) 50%, transparent 70%);
            animation: shimmer 3s infinite;
        }

        .logo-text {
            font-family: 'Cormorant Garamond', serif;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: var(--text);
        }

        .logo-text span {
            background: var(--grad1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 2px;
            list-style: none;
        }

        .nav-links a {
            color: var(--text3);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all .25s;
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: var(--violet);
            background: var(--secondary-faint);
        }

        .btn {
            padding: 9px 22px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all .3s cubic-bezier(0.34, 1.56, 0.64, 1);
            border: none;
            font-family: 'DM Sans', sans-serif;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-primary {
            background: var(--grad1);
            color: #fff;
            box-shadow: 0 4px 16px rgba(111, 86, 254, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(111, 86, 254, 0.45);
        }

        .btn-outline {
            background: transparent;
            border: 1.5px solid var(--violet);
            color: var(--violet);
        }

        .btn-outline:hover {
            background: var(--violet);
            color: #fff;
            transform: translateY(-1px);
        }

        .btn-outline-pink {
            background: transparent;
            border: 1.5px solid var(--pink);
            color: var(--pink);
        }

        .btn-outline-pink:hover {
            background: var(--pink);
            color: #fff;
            transform: translateY(-1px);
        }

        .btn-lg {
            padding: 13px 32px;
            font-size: 15px;
            border-radius: 11px;
        }

        .hamburger {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            background: none;
            border: none;
            padding: 4px;
        }

        .hamburger span {
            display: block;
            width: 22px;
            height: 1.5px;
            background: var(--text3);
            border-radius: 2px;
        }

        /* ══════════════════ MOBILE NAV ══════════════════ */
        .mobile-nav {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, 0.98);
            z-index: 1001;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 24px;
        }

        .mobile-nav.open {
            display: flex;
            animation: fadeIn .3s ease;
        }

        .mobile-nav-link {
            font-family: 'Cormorant Garamond', serif;
            font-size: 28px;
            font-weight: 600;
            color: var(--text2);
            text-decoration: none;
        }

        .mobile-nav-close {
            position: absolute;
            top: 24px;
            right: 24px;
            background: none;
            border: none;
            color: var(--text3);
            font-size: 24px;
            cursor: pointer;
        }

        /* ══════════════════ PAGE HEADER ══════════════════ */
        .page-header {
            padding: 140px 6% 80px;
            background: linear-gradient(135deg, var(--primary-faint) 0%, var(--secondary-faint) 100%);
            border-bottom: 1px solid var(--border2);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .page-header .grid-bg {
            position: absolute;
            inset: 0;
            background-image: linear-gradient(rgba(111, 86, 254, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(111, 86, 254, 0.04) 1px, transparent 1px);
            background-size: 48px 48px;
        }

        .page-header-content {
            position: relative;
            z-index: 1;
        }

        .section-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 14px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.5px;
            margin-bottom: 16px;
            text-transform: uppercase;
        }

        .tag-dual {
            background: linear-gradient(135deg, var(--primary-faint), var(--secondary-faint));
            border: 1px solid var(--border);
            color: var(--violet);
        }

        .tag-violet {
            background: var(--secondary-faint);
            border: 1px solid var(--border);
            color: var(--violet);
        }

        .section-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(2.2rem, 4vw, 3.4rem);
            font-weight: 600;
            line-height: 1.1;
            letter-spacing: -0.5px;
            margin-bottom: 16px;
            color: var(--text);
        }

        .gradient-text {
            background: var(--grad2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .section-subtitle {
            font-size: 15px;
            color: var(--text3);
            line-height: 1.8;
            max-width: 820px;
            margin: 0 auto;
        }

        /* ══════════════════ PRICING CARDS ══════════════════ */
        .section {
            padding: 80px 6%;
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 28px;
            max-width: 860px;
            margin: 0 auto 80px;
        }

        /* School Plan card */
        .price-card-school {
            background: linear-gradient(160deg, var(--secondary-faint) 0%, #fff 100%);
            border: 2px solid var(--violet);
            border-radius: var(--radius-lg);
            padding: 44px 36px;
            position: relative;
            box-shadow: var(--shadow);
            transition: all .35s cubic-bezier(0.34, 1.56, 0.64, 1);
            animation: slideInLeft .6s ease forwards;
            opacity: 0;
        }

        .price-card-school:hover {
            transform: translateY(-8px);
            box-shadow: 0 32px 80px rgba(111, 86, 254, 0.22);
        }

        /* Enterprise card */
        .price-card-enterprise {
            background: #fff;
            border: 1px solid var(--border2);
            border-radius: var(--radius-lg);
            padding: 44px 36px;
            position: relative;
            transition: all .35s cubic-bezier(0.34, 1.56, 0.64, 1);
            animation: slideInRight .6s ease forwards;
            opacity: 0;
        }

        .price-card-enterprise:hover {
            transform: translateY(-8px);
            border-color: var(--border-pink);
            box-shadow: var(--shadow2);
        }

        .price-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--grad1);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 50px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .price-plan-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 14px;
        }

        .price-amount {
            font-family: 'Cormorant Garamond', serif;
            font-size: 56px;
            font-weight: 700;
            color: var(--text);
            line-height: 1;
            margin-bottom: 4px;
        }

        .price-amount .decimal {
            font-size: 20px;
            font-weight: 500;
            color: var(--text3);
            font-family: 'DM Sans', sans-serif;
        }

        .price-period {
            font-size: 13px;
            color: var(--text3);
            margin-bottom: 6px;
        }

        .price-period strong {
            color: var(--violet);
            font-weight: 600;
        }

        .price-note {
            font-size: 12px;
            color: var(--text4);
            margin-bottom: 28px;
        }

        .price-divider {
            height: 1px;
            background: var(--border2);
            margin-bottom: 24px;
        }

        .price-features {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 32px;
        }

        .price-features li {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: var(--text2);
        }

        .check-green {
            width: 18px;
            height: 18px;
            min-width: 18px;
            background: rgba(34, 197, 94, .12);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #16A34A;
        }

        .check-pink {
            width: 18px;
            height: 18px;
            min-width: 18px;
            background: rgba(219, 87, 178, .12);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: var(--pink-dark);
        }

        .price-cta {
            width: 100%;
            padding: 13px 28px;
            border-radius: 11px;
            font-size: 15px;
        }

        .price-sub {
            text-align: center;
            font-size: 11px;
            color: var(--text4);
            margin-top: 10px;
        }

        /* ══════════════════ MODULES SECTION ══════════════════ */
        .modules-header {
            text-align: center;
            margin-bottom: 48px;
        }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 12px;
        }

        .module-card {
            background: #fff;
            border: 1px solid var(--border2);
            border-radius: var(--radius-sm);
            padding: 16px 18px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            transition: all .3s;
            cursor: default;
            position: relative;
            overflow: hidden;
        }

        .module-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--grad1);
            border-radius: 3px 0 0 3px;
            opacity: 0.5;
        }

        .module-card:hover {
            transform: translateY(-3px);
            border-color: var(--border);
            box-shadow: var(--shadow3);
            background: var(--bg3);
        }

        .module-icon {
            font-size: 20px;
            min-width: 28px;
            text-align: center;
            margin-top: 2px;
            padding-left: 4px;
        }

        .module-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 4px;
            line-height: 1.2;
        }

        .module-desc {
            font-size: 11.5px;
            color: var(--text3);
            line-height: 1.65;
        }

        .module-check {
            margin-left: auto;
            flex-shrink: 0;
            width: 18px;
            height: 18px;
            background: rgba(34, 197, 94, 0.12);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            color: #16A34A;
            margin-top: 2px;
        }

        /* ══════════════════ BOTTOM CTA ══════════════════ */
        .bottom-cta {
            background: linear-gradient(135deg, var(--primary-faint), var(--secondary-faint));
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 48px 40px;
            max-width: 680px;
            margin: 72px auto 0;
            text-align: center;
        }

        .bottom-cta-icon {
            font-size: 36px;
            margin-bottom: 14px;
        }

        .bottom-cta-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 26px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 12px;
        }

        .bottom-cta-desc {
            color: var(--text3);
            font-size: 14px;
            line-height: 1.75;
            margin-bottom: 28px;
        }

        .bottom-cta-actions {
            display: flex;
            gap: 14px;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* ══════════════════ FOOTER ══════════════════ */
        footer {
            background: var(--bg3);
            border-top: 1px solid var(--border2);
            padding: 80px 6% 32px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 48px;
            margin-bottom: 60px;
        }

        .footer-brand-desc {
            font-size: 13px;
            color: var(--text3);
            line-height: 1.8;
            margin: 14px 0 22px;
        }

        .footer-socials {
            display: flex;
            gap: 8px;
        }

        .social-btn {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--bg2);
            border: 1px solid var(--border2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text3);
            text-decoration: none;
            transition: all .2s;
        }

        .social-btn:hover {
            background: var(--violet);
            border-color: var(--violet);
            color: white;
        }

        .footer-col-title {
            font-size: 11px;
            font-weight: 700;
            color: var(--text);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 20px;
        }

        .footer-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 9px;
        }

        .footer-links a {
            color: var(--text3);
            text-decoration: none;
            font-size: 13px;
            transition: all .2s;
        }

        .footer-links a:hover {
            color: var(--violet);
        }

        .footer-bottom {
            border-top: 1px solid var(--border2);
            padding-top: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
        }

        .footer-bottom-text {
            font-size: 12px;
            color: var(--text4);
        }

        .footer-bottom-links {
            display: flex;
            gap: 20px;
        }

        .footer-bottom-links a {
            font-size: 12px;
            color: var(--text4);
            text-decoration: none;
            transition: color .2s;
        }

        .footer-bottom-links a:hover {
            color: var(--violet);
        }

        /* ══════════════════ RESPONSIVE ══════════════════ */
        @media (max-width: 768px) {

            .nav-links,
            .nav-cta {
                display: none;
            }

            .hamburger {
                display: flex;
            }

            .pricing-grid {
                grid-template-columns: 1fr;
                max-width: 460px;
            }

            .page-header {
                padding: 110px 5% 60px;
            }

            .bottom-cta {
                padding: 32px 20px;
            }

            .footer-grid {
                grid-template-columns: 1fr 1fr;
                gap: 36px;
            }
        }

        @media (max-width: 480px) {
            .section {
                padding: 60px 4%;
            }

            .price-card-school,
            .price-card-enterprise {
                padding: 28px 20px;
            }

            .pricing-grid {
                max-width: 100%;
            }

            .section-title {
                font-size: clamp(1.9rem, 7vw, 2.8rem);
            }

            .footer-grid {
                grid-template-columns: 1fr;
            }

            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }

            .footer-bottom-links {
                justify-content: center;
                flex-wrap: wrap;
            }

            .bottom-cta-actions {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->


    @include('components.website.header')


    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="grid-bg"></div>
        <div class="page-header-content">
            <div class="section-tag tag-dual">Pricing</div>
            <h1 class="section-title" style="margin-top:16px;">Simple, <span class="gradient-text">Transparent</span>
                Pricing</h1>
            <p class="section-subtitle">Enjoy simple, transparent pricing with one comprehensive plan built specifically
                for schools. Get clear, upfront costs with no surprises, no add-ons, and absolutely no hidden fees.
                Everything you need is included in a straightforward package designed to make budgeting easy and
                predictable from day one.</p>
        </div>
    </div>

    <!-- PRICING CARDS -->
    <section class="section" style="background:#fff;">
        <div style="max-width:1100px; margin:0 auto;">

            <div class="pricing-grid">

                <!-- School Plan -->
                <div class="price-card-school">
                    <div class="price-badge">STANDARD PLAN</div>
                    <div class="price-plan-label"
                        style="background:var(--grad1);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">
                        School Plan</div>
                    <div class="price-amount">₹20<span class="decimal">.83</span></div>
                    <div class="price-period">per month/user <strong>(billed annually)</strong></div>
                    <div class="price-note">= ₹250 / year &nbsp;·&nbsp; All features included</div>
                    <div class="price-divider"></div>
                    <ul class="price-features">
                        <li>
                            <div class="check-green">✓</div> All LMS modules included
                        </li>
                        <li>
                            <div class="check-green">✓</div> Unlimited teachers &amp; students
                        </li>
                        <li>
                            <div class="check-green">✓</div> Attendance, Fee, Exam &amp; more
                        </li>
                        <li>
                            <div class="check-green">✓</div> Mobile apps (iOS &amp; Android)
                        </li>
                        <li>
                            <div class="check-green">✓</div> Dedicated onboarding support
                        </li>
                        <li>
                            <div class="check-green">✓</div> Free updates forever
                        </li>
                        <li>
                            <div class="check-green">✓</div> 99.9% uptime SLA
                        </li>
                    </ul>
                    <a href="{{ url('web/demo') }}" class="btn btn-primary price-cta">Get Started →</a>
                    <div class="price-sub">No credit card required to start</div>
                </div>

                <!-- Enterprise -->
                <div class="price-card-enterprise">
                    <div class="price-plan-label"
                        style="background:var(--grad-pink);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">
                        Enterprise</div>
                    <div class="price-amount" style="font-size:42px;">Custom</div>
                    <div class="price-period">tailored pricing for your institution</div>
                    <div class="price-note">Multi-campus &nbsp;·&nbsp; Large schools &nbsp;·&nbsp; Special needs</div>
                    <div class="price-divider"></div>
                    <ul class="price-features">
                        <li>
                            <div class="check-pink">✓</div> Everything in School Plan
                        </li>
                        <li>
                            <div class="check-pink">✓</div> Multi-campus management
                        </li>
                        <li>
                            <div class="check-pink">✓</div> Custom branding &amp; white-label
                        </li>
                        <li>
                            <div class="check-pink">✓</div> Dedicated account manager
                        </li>
                        <li>
                            <div class="check-pink">✓</div> On-site training &amp; onboarding
                        </li>
                        <li>
                            <div class="check-pink">✓</div> API integrations
                        </li>
                        <li>
                            <div class="check-pink">✓</div> Custom SLA &amp; data agreements
                        </li>
                    </ul>
                    <a href="{{ url('web/contact') }}" class="btn btn-outline-pink price-cta">Contact Sales →</a>
                    <div class="price-sub">We'll respond within 24 hours</div>
                </div>
            </div>

            <!-- ALL MODULES -->
            <div>
                <div class="modules-header">
                    <div class="section-tag tag-dual">All Modules</div>
                    <h2 class="section-title" style="margin-top:16px;">Everything Included in <span
                            class="gradient-text">Your Plan</span></h2>
                    <p class="section-subtitle" style="margin:12px auto 0;">Everything listed below comes fully included
                        in your School Plan — no hidden charges, no add-ons, and no feature restrictions. Enjoy
                        unlimited access to all powerful modules, designed to streamline academics, administration,
                        communication, and reporting.</p>
                </div>

                <div class="modules-grid">

                    <div class="module-card">
                        <div class="module-icon">📊</div>
                        <div>
                            <div class="module-name">Dashboard</div>
                            <div class="module-desc">Central overview of school activity — attendance summary, fee
                                status, upcoming exams, notices, and live stats at a glance.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">🔗</div>
                        <div>
                            <div class="module-name">Quick Links</div>
                            <div class="module-desc">One-tap shortcuts to the most-used modules. Customize links for
                                admins, teachers, and students separately.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">🏠</div>
                        <div>
                            <div class="module-name">Home Analytics</div>
                            <div class="module-desc">Visual charts and KPIs — enrollment trends, monthly fee collection,
                                attendance rates, and exam scores.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">🏫</div>
                        <div>
                            <div class="module-name">School Profile</div>
                            <div class="module-desc">Complete institutional profile management — branding, departments,
                                academic year config, affiliation board, and contact info.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">👨‍🎓</div>
                        <div>
                            <div class="module-name">Students</div>
                            <div class="module-desc">Complete student profiles — personal info, academic history,
                                attendance record, fee status, documents, and parent contact.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">👨‍🏫</div>
                        <div>
                            <div class="module-name">Teachers</div>
                            <div class="module-desc">Staff directory with subject mappings, class assignments,
                                attendance, payroll links, and performance tracking.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">💰</div>
                        <div>
                            <div class="module-name">Fee</div>
                            <div class="module-desc">Collect fees online or offline, generate receipts, set due dates,
                                apply late fines, manage scholarships, and export reports.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">📞</div>
                        <div>
                            <div class="module-name">Enquiries</div>
                            <div class="module-desc">Track admission enquiries from first contact to enrolment — assign
                                follow-ups, log calls, and monitor conversion rates.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">📚</div>
                        <div>
                            <div class="module-name">Content</div>
                            <div class="module-desc">Upload and share notes, PDFs, videos, and presentations. Students
                                access subject-wise content anytime, anywhere.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">📖</div>
                        <div>
                            <div class="module-name">Syllabus</div>
                            <div class="module-desc">Define and publish the syllabus for each class and subject. Track
                                completion progress chapter by chapter.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">🗓️</div>
                        <div>
                            <div class="module-name">Time Table</div>
                            <div class="module-desc">Auto-generate conflict-free timetables. Drag-and-drop editor,
                                substitute teacher assignment, and instant sharing.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">📦</div>
                        <div>
                            <div class="module-name">Homework</div>
                            <div class="module-desc">Assign homework with due dates and attachments. Students submit
                                digitally; teachers review and grade in one place.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">✅</div>
                        <div>
                            <div class="module-name">Attendance</div>
                            <div class="module-desc">Mark attendance via mobile, QR code, or biometric. Instant
                                SMS/WhatsApp alerts to parents for absences.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">🧠</div>
                        <div>
                            <div class="module-name">Quiz</div>
                            <div class="module-desc">Create auto-graded MCQ, true/false, and short-answer quizzes.
                                Timed tests with instant results and leaderboards.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">📈</div>
                        <div>
                            <div class="module-name">Performance</div>
                            <div class="module-desc">Subject-wise marks, grade trends, class rank, and comparative
                                analytics for students, teachers, and parents.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">🏛️</div>
                        <div>
                            <div class="module-name">Library</div>
                            <div class="module-desc">Manage physical and digital library — catalog books, track
                                issue/return, set due dates, and send overdue reminders.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">📗</div>
                        <div>
                            <div class="module-name">Book</div>
                            <div class="module-desc">Digital book repository for students. Browse, borrow, and read
                                e-books subject-wise directly within the app.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">📢</div>
                        <div>
                            <div class="module-name">Announcement</div>
                            <div class="module-desc">Broadcast notices to specific classes, roles, or the entire school
                                via app notification, SMS, or email instantly.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">🔄</div>
                        <div>
                            <div class="module-name">Arrangement</div>
                            <div class="module-desc">Manage substitute teacher arrangements when staff are absent —
                                auto-suggest replacements and notify affected classes.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">🪑</div>
                        <div>
                            <div class="module-name">Seating Plan</div>
                            <div class="module-desc">Design and publish exam hall seating arrangements. Print or share
                                digitally with students and invigilators.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">📃</div>
                        <div>
                            <div class="module-name">Exam Copy</div>
                            <div class="module-desc">Upload scanned or digital answer sheets. Teachers annotate, grade,
                                and return copies to students within the platform.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">📋</div>
                        <div>
                            <div class="module-name">Report Card</div>
                            <div class="module-desc">Auto-generate formatted report cards with grades, remarks, and
                                attendance. Print or share as PDF with parents.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">📅</div>
                        <div>
                            <div class="module-name">Calendar</div>
                            <div class="module-desc">School-wide academic calendar with holidays, exams, events, and
                                PTMs. Sync to personal calendars with one click.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">🪪</div>
                        <div>
                            <div class="module-name">ID Card</div>
                            <div class="module-desc">Design and bulk-generate student and staff ID cards with photo,
                                barcode, and school branding. Print-ready PDF export.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">📄</div>
                        <div>
                            <div class="module-name">Admit Card</div>
                            <div class="module-desc">Auto-generate exam admit cards with student details, roll number,
                                exam schedule, and school seal. Distribute digitally.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">📜</div>
                        <div>
                            <div class="module-name">Rules &amp; Regulation</div>
                            <div class="module-desc">Publish and manage school rules, code of conduct, and disciplinary
                                policies. Accessible to all users within the app.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">💼</div>
                        <div>
                            <div class="module-name">Payroll</div>
                            <div class="module-desc">Manage staff salaries, allowances, deductions, and PF/ESI.
                                Generate salary slips and export payroll reports monthly.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">🚌</div>
                        <div>
                            <div class="module-name">Transportation</div>
                            <div class="module-desc">Manage bus routes, stops, and assigned students. Track vehicles,
                                collect transport fees, and notify parents of delays.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">🎓</div>
                        <div>
                            <div class="module-name">TC &amp; Certificate</div>
                            <div class="module-desc">Issue Transfer Certificates, bonafide letters, and custom
                                certificates digitally. QR-code verified and tamper-proof.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">⚙️</div>
                        <div>
                            <div class="module-name">Account Settings</div>
                            <div class="module-desc">Manage profile, notification preferences, password, and linked
                                devices. Role-specific settings for every user type.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">📞</div>
                        <div>
                            <div class="module-name">Contact Admin</div>
                            <div class="module-desc">Direct in-app messaging channel between users and school admin.
                                Raise queries, get support, and track resolution.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">ℹ️</div>
                        <div>
                            <div class="module-name">About App</div>
                            <div class="module-desc">Version info, release notes, developer contact, and platform
                                documentation — all accessible within the app.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">⭐</div>
                        <div>
                            <div class="module-name">Rate LMS</div>
                            <div class="module-desc">Students, teachers, and parents can rate their experience.
                                Feedback is collected to continuously improve the platform.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                </div>

                <!-- Bottom CTA -->
                <div class="bottom-cta">
                    <div class="bottom-cta-icon">🏫</div>
                    <div class="bottom-cta-title">Ready to Get Started?</div>
                    <p class="bottom-cta-desc">Book your free personalized demo today and explore all modules in
                        action. Our team will guide you through every feature and handle the complete setup for you.</p>
                    <div class="bottom-cta-actions">
                        <a href="{{ url('web/demo') }}" class="btn btn-primary btn-lg">Book Free Demo</a>
                        <a href="{{ url('web/contact') }}" class="btn btn-outline btn-lg">Talk to Sales →</a>
                    </div>
                </div>

            </div>
        </div>
    </section>



    @include('components.website.app-section')

    @include('components.website.footer')


</body>

</html>
