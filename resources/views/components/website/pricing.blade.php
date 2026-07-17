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

        /* ══════════════════ ZERO COST TO SCHOOLS ══════════════════ */
        .zero-cost {
            background: linear-gradient(160deg, var(--secondary-faint) 0%, #fff 60%);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 48px 40px;
            box-shadow: var(--shadow3);
        }

        .zero-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-top: 8px;
        }

        .zero-card {
            background: #fff;
            border: 1px solid var(--border2);
            border-radius: var(--radius);
            padding: 26px 22px;
            text-align: center;
            transition: all .3s;
        }

        .zero-card:hover {
            transform: translateY(-4px);
            border-color: var(--border);
            box-shadow: var(--shadow2);
        }

        .zero-icon {
            width: 56px;
            height: 56px;
            margin: 0 auto 16px;
            border-radius: 16px;
            background: var(--grad1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            box-shadow: 0 8px 20px rgba(111, 86, 254, .25);
        }

        .zero-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 8px;
        }

        .zero-desc {
            font-size: 13px;
            color: var(--text3);
            line-height: 1.7;
        }

        @media (max-width: 768px) {
            .zero-grid { grid-template-columns: 1fr; }
            .zero-cost { padding: 32px 20px; }
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

            <!-- ZERO COST TO SCHOOLS -->
            <div class="zero-cost" style="margin-bottom:80px;">
                <div class="modules-header" style="margin-bottom:32px;">
                    <div class="section-tag tag-dual">Zero Cost to Schools</div>
                    <h2 class="section-title" style="margin-top:16px;">The School Pays <span
                            class="gradient-text">Nothing</span></h2>
                    <p class="section-subtitle" style="margin:12px auto 0;">Here's the best part — whatever the pricing
                        is, we charge it directly to the students. The school never pays us a single rupee. And on top of
                        that, every school gets its very own website, completely free of cost.</p>
                </div>

                <div class="zero-grid">
                    <div class="zero-card">
                        <div class="zero-icon">🎓</div>
                        <div class="zero-title">Charged to Students, Not the School</div>
                        <div class="zero-desc">Whatever the plan costs is collected straight from the students. The
                            school carries zero financial burden — ever.</div>
                    </div>
                    <div class="zero-card">
                        <div class="zero-icon">🆓</div>
                        <div class="zero-title">₹0 Payable by the School</div>
                        <div class="zero-desc">No licence fees, no setup charges, no hidden costs. The school pays
                            absolutely nothing to go live with SUPERLMS.</div>
                    </div>
                    <div class="zero-card">
                        <div class="zero-icon">🌐</div>
                        <div class="zero-title">Free School Website</div>
                        <div class="zero-desc">Every school gets its own professional website — designed, hosted and
                            maintained by us — free of cost.</div>
                    </div>
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
                        <div class="module-icon">🔗</div>
                        <div>
                            <div class="module-name">Quick Links</div>
                            <div class="module-desc">Pin your most-used tools up front. One tap, zero digging — every
                                day starts faster.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">🏠</div>
                        <div>
                            <div class="module-name">Home</div>
                            <div class="module-desc">Your school's command centre — today's attendance, fees, notices and
                                live stats the second you log in.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">📊</div>
                        <div>
                            <div class="module-name">Analytics</div>
                            <div class="module-desc">Turn raw numbers into decisions — enrollment, collections,
                                attendance and results in living charts.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">🏷️</div>
                        <div>
                            <div class="module-name">Standard</div>
                            <div class="module-desc">Set up classes, sections and streams once, and the whole platform
                                organises itself around them.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">👨‍🎓</div>
                        <div>
                            <div class="module-name">Students</div>
                            <div class="module-desc">Every learner's full story in one profile — academics, attendance,
                                fees, documents and parent contacts.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">👨‍🏫</div>
                        <div>
                            <div class="module-name">Teachers</div>
                            <div class="module-desc">A complete staff directory with subjects, classes, attendance and
                                performance, all linked together.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">💰</div>
                        <div>
                            <div class="module-name">Fees</div>
                            <div class="module-desc">Collect online or offline, auto-generate receipts, apply fines and
                                track dues — money made effortless.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">📒</div>
                        <div>
                            <div class="module-name">Ledger</div>
                            <div class="module-desc">A crystal-clear book of every rupee in and out — balanced,
                                searchable and always audit-ready.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">💼</div>
                        <div>
                            <div class="module-name">Payroll</div>
                            <div class="module-desc">Salaries, allowances, deductions and slips — staff get paid right,
                                on time, every single month.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">💳</div>
                        <div>
                            <div class="module-name">Credit</div>
                            <div class="module-desc">Manage wallet balances and top-ups that keep your premium services
                                running without a hitch.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">✅</div>
                        <div>
                            <div class="module-name">Attendance</div>
                            <div class="module-desc">Mark in seconds by app, QR or biometric — parents get an instant
                                ping the moment a child is absent.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">🚌</div>
                        <div>
                            <div class="module-name">Transportation</div>
                            <div class="module-desc">Routes, stops, vehicles and live alerts — every child's ride home,
                                tracked and safe.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">📦</div>
                        <div>
                            <div class="module-name">Homework</div>
                            <div class="module-desc">Assign with due dates and files; students submit digitally and
                                teachers grade it all in one place.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">🗓️</div>
                        <div>
                            <div class="module-name">Time Table</div>
                            <div class="module-desc">Conflict-free schedules built in minutes, with substitutes and
                                instant sharing to every phone.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">🔄</div>
                        <div>
                            <div class="module-name">Arrangement</div>
                            <div class="module-desc">Teacher absent? Auto-suggest a substitute and notify the class
                                before the bell even rings.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">📢</div>
                        <div>
                            <div class="module-name">Announcement</div>
                            <div class="module-desc">Reach the whole school or a single class instantly — push, SMS and
                                email all in one shot.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">📅</div>
                        <div>
                            <div class="module-name">Calendar</div>
                            <div class="module-desc">Holidays, exams, events and PTMs in one shared calendar everyone
                                can sync in a single click.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">📖</div>
                        <div>
                            <div class="module-name">Syllabus</div>
                            <div class="module-desc">Publish the course plan and track completion chapter by chapter,
                                subject by subject.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">📚</div>
                        <div>
                            <div class="module-name">Content</div>
                            <div class="module-desc">Share notes, PDFs, videos and slides so students learn anytime,
                                anywhere, on any device.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">🧠</div>
                        <div>
                            <div class="module-name">Quiz</div>
                            <div class="module-desc">Auto-graded quizzes with timers, instant results and leaderboards
                                that make revision genuinely fun.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">📗</div>
                        <div>
                            <div class="module-name">Book</div>
                            <div class="module-desc">A digital library in every pocket — browse, borrow and read
                                e-books subject-wise, right in the app.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">📞</div>
                        <div>
                            <div class="module-name">Enquiries</div>
                            <div class="module-desc">Capture every admission lead and nurture it from the first hello to
                                the final enrolment.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">🪪</div>
                        <div>
                            <div class="module-name">ID Card</div>
                            <div class="module-desc">Design once, generate in bulk — photo, barcode and branding,
                                print-ready in seconds.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">📝</div>
                        <div>
                            <div class="module-name">Exam</div>
                            <div class="module-desc">Plan exams end to end — schedules, subjects, marks and grades, all
                                beautifully organised.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">🎫</div>
                        <div>
                            <div class="module-name">Admit Card</div>
                            <div class="module-desc">Auto-build admit cards with roll numbers, schedules and school
                                seal, shared digitally in a click.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">🪑</div>
                        <div>
                            <div class="module-name">Seating Plan</div>
                            <div class="module-desc">Fair, tidy exam-hall seating designed in a click and shared with
                                students and invigilators.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">📈</div>
                        <div>
                            <div class="module-name">Performance</div>
                            <div class="module-desc">Spot every rising star and every gap with marks, trends and class
                                rankings at a glance.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">📃</div>
                        <div>
                            <div class="module-name">Exam Copy</div>
                            <div class="module-desc">Upload answer sheets, annotate, grade and return copies — the whole
                                paper trail goes paperless.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">📋</div>
                        <div>
                            <div class="module-name">Report Card</div>
                            <div class="module-desc">Polished report cards with grades, remarks and attendance, exported
                                to crisp PDFs for parents.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">🎓</div>
                        <div>
                            <div class="module-name">TC &amp; Certificate</div>
                            <div class="module-desc">Issue transfer certificates and bonafide letters — QR-verified and
                                completely tamper-proof.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">👥</div>
                        <div>
                            <div class="module-name">Users</div>
                            <div class="module-desc">Add staff and roles with pinpoint permissions so everyone sees
                                exactly what they should.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">🆕</div>
                        <div>
                            <div class="module-name">Admissions</div>
                            <div class="module-desc">Run the whole admission journey online, from application to
                                enrolment, in one smooth flow.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">🗂️</div>
                        <div>
                            <div class="module-name">Lists</div>
                            <div class="module-desc">Generate any student or staff list you need and export it, filtered
                                and ready, in a click.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">📜</div>
                        <div>
                            <div class="module-name">Rules &amp; Regulation</div>
                            <div class="module-desc">Publish the code of conduct and policies where every student and
                                parent can find them.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">💬</div>
                        <div>
                            <div class="module-name">Contact Admin</div>
                            <div class="module-desc">A direct line to the office — raise queries, get answers and track
                                every resolution.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">ℹ️</div>
                        <div>
                            <div class="module-name">About App</div>
                            <div class="module-desc">Version info, release notes and support contacts, always just a tap
                                away.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">⭐</div>
                        <div>
                            <div class="module-name">Rate LMS</div>
                            <div class="module-desc">Students, teachers and parents rate their experience so the
                                platform keeps getting better.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">📑</div>
                        <div>
                            <div class="module-name">Terms &amp; Conditions</div>
                            <div class="module-desc">The full terms of service, kept transparent and always up to
                                date.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">🔒</div>
                        <div>
                            <div class="module-name">Privacy Policy</div>
                            <div class="module-desc">Clear, honest data practices your whole school community can
                                trust.</div>
                        </div>
                        <div class="module-check">✓</div>
                    </div>

                    <div class="module-card">
                        <div class="module-icon">📘</div>
                        <div>
                            <div class="module-name">Terms Of Use</div>
                            <div class="module-desc">Simple, plain-English guidelines on using the platform the right
                                way.</div>
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
