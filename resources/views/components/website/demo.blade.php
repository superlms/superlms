<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Request Demo — EDYONE LMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="icon" type="image/png" href="{{ asset('website-image/Group 11525.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-7S4FD1GMPK"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'G-7S4FD1GMPK');
    </script>
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
    <style>
        :root {
            --pink: #DB57B2;
            --pink-dark: #B83D92;
            --violet: #6F56FE;
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
            --grad1: linear-gradient(135deg, #DB57B2 0%, #6F56FE 100%);
            --grad-violet: linear-gradient(135deg, #6F56FE 0%, #5540D4 100%);
            --shadow: 0 24px 64px rgba(111, 86, 254, 0.18);
            --shadow2: 0 8px 32px rgba(111, 86, 254, 0.12);
            --shadow3: 0 2px 12px rgba(111, 86, 254, 0.08);
            --radius: 16px;
            --radius-sm: 10px;
            --radius-lg: 24px;
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

        @keyframes shimmer {
            0% {
                background-position: -200% center;
            }

            100% {
                background-position: 200% center;
            }
        }

        @keyframes popIn {
            from {
                opacity: 0;
                transform: scale(0.8) translateY(20px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(60px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ── NAVBAR ── */
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
            cursor: pointer;
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

        /* ── MOBILE NAV ── */
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
            animation: fadeUp .3s ease;
        }

        .mobile-nav-link {
            font-family: 'Cormorant Garamond', serif;
            font-size: 28px;
            font-weight: 600;
            color: var(--text2);
            text-decoration: none;
            transition: all .2s;
        }

        .mobile-nav-link:hover {
            background: var(--grad1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
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

        /* ── PAGE HEADER ── */
        .page-header {
            padding: 140px 6% 80px;
            background: linear-gradient(135deg, var(--primary-faint), var(--secondary-faint));
            border-bottom: 1px solid var(--border2);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            background: rgba(111, 86, 254, 0.07);
            filter: blur(60px);
        }

        .page-header::after {
            content: '';
            position: absolute;
            bottom: -40px;
            left: -40px;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: rgba(219, 87, 178, 0.07);
            filter: blur(60px);
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
            background: linear-gradient(135deg, #E878C4 0%, #DB57B2 40%, #6F56FE 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .section-subtitle {
            font-size: 15px;
            color: var(--text3);
            line-height: 1.8;
            max-width: 720px;
            margin: 0 auto;
            font-weight: 400;
        }

        /* ── DEMO SECTION ── */
        .section {
            padding: 80px 6%;
        }

        .demo-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: start;
            max-width: 1060px;
            margin: 0 auto;
        }

        /* ── WHAT YOU'LL SEE LIST ── */
        .left-col {
            animation: slideInLeft .65s ease .05s forwards;
            opacity: 0;
        }

        .left-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(22px, 3vw, 28px);
            font-weight: 600;
            color: var(--text);
            margin: 16px 0 24px;
            line-height: 1.25;
        }

        .feature-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 18px;
            background: #fff;
            border: 1px solid var(--border2);
            border-radius: var(--radius-sm);
            transition: all .3s;
            margin-bottom: 10px;
        }

        .feature-row:hover {
            border-color: var(--border);
            background: var(--bg3);
            transform: translateX(6px);
        }

        .feature-icon {
            font-size: 18px;
            width: 32px;
            text-align: center;
            flex-shrink: 0;
        }

        .feature-text {
            font-size: 14px;
            font-weight: 500;
            color: var(--text);
            flex: 1;
        }

        .feature-check {
            margin-left: auto;
            flex-shrink: 0;
            background: var(--grad1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 14px;
            font-weight: 700;
        }

        /* ── FORM CARD ── */
        .form-card {
            background: #fff;
            border: 1px solid var(--border2);
            border-radius: var(--radius-lg);
            padding: clamp(20px, 3vw, 40px);
            box-shadow: var(--shadow3);
            animation: slideInRight .7s ease .1s forwards;
            opacity: 0;
        }

        .form-card-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(20px, 2.5vw, 24px);
            font-weight: 600;
            color: var(--text);
            margin-bottom: 6px;
        }

        .form-card-sub {
            color: var(--text3);
            font-size: 13px;
            margin-bottom: 28px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: var(--text3);
            margin-bottom: 7px;
            text-transform: uppercase;
            letter-spacing: .8px;
        }

        .form-input,
        .form-select {
            width: 100%;
            padding: 12px 16px;
            background: var(--bg2);
            border: 1px solid var(--border2);
            border-radius: 9px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            transition: all .25s;
            outline: none;
        }

        .form-input::placeholder {
            color: var(--text4);
        }

        .form-input:focus,
        .form-select:focus {
            border-color: var(--violet);
            box-shadow: 0 0 0 3px rgba(111, 86, 254, 0.1);
            background: #fff;
        }

        .form-select {
            -webkit-appearance: none;
            cursor: pointer;
        }

        .btn-submit {
            width: 100%;
            padding: 15px 32px;
            font-size: 16px;
            border-radius: 13px;
            background: var(--grad1);
            color: #fff;
            border: none;
            font-family: 'DM Sans', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: all .3s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 4px 16px rgba(111, 86, 254, 0.3);
            margin-top: 6px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(111, 86, 254, 0.45);
        }

        .form-disclaimer {
            text-align: center;
            font-size: 11px;
            color: var(--text4);
            margin-top: 12px;
        }

        /* ── TRUST BADGES ── */
        .trust-strip {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 20px;
            max-width: 860px;
            margin: 52px auto 0;
            animation: fadeUp .7s ease .4s forwards;
            opacity: 0;
        }

        .trust-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: #fff;
            border: 1px solid var(--border2);
            border-radius: var(--radius-sm);
            font-size: 13px;
            color: var(--text2);
            font-weight: 500;
            transition: all .3s;
        }

        .trust-badge:hover {
            border-color: var(--border);
            transform: translateY(-3px);
            box-shadow: var(--shadow3);
        }

        .trust-badge-icon {
            font-size: 18px;
        }

        /* ── TOAST ── */
        .toast {
            position: fixed;
            bottom: 28px;
            right: 28px;
            background: #fff;
            border: 1px solid rgba(34, 197, 94, .3);
            border-radius: var(--radius);
            padding: 16px 22px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: var(--shadow2);
            z-index: 9999;
            animation: popIn .4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
            max-width: 320px;
        }

        .toast.hidden {
            display: none;
        }

        .toast-icon {
            font-size: 22px;
        }

        .toast-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
        }

        .toast-msg {
            font-size: 12px;
            color: var(--text3);
            margin-top: 2px;
        }

        /* ── FOOTER ── */
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

        /* ── RESPONSIVE ── */
        @media (max-width: 900px) {
            .demo-grid {
                grid-template-columns: 1fr;
                gap: 32px;
            }

            .nav-links,
            .nav-cta {
                display: none;
            }

            .hamburger {
                display: flex;
            }

            .footer-grid {
                grid-template-columns: 1fr 1fr;
                gap: 36px;
            }
        }

        @media (max-width: 600px) {
            .section {
                padding: 60px 5%;
            }

            .form-card {
                padding: 22px 18px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .page-header {
                padding: 110px 5% 60px;
            }

            .trust-strip {
                gap: 12px;
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
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->


    @include('components.website.header')


    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="section-tag tag-dual">Free Demo</div>
        <h1 class="section-title">Request a Free <span class="gradient-text">Demo</span></h1>
        <p class="section-subtitle">Discover how EDYONE LMS streamlines teaching, enhances learning, and simplifies
            school management—all within one powerful platform. In your personalized 30-minute demo, our education
            specialist will guide you through key features and show how EDYONE LMS can transform your institution's
            daily operations and academic experience.</p>
    </div>

    <!-- DEMO SECTION -->
    <section class="section" style="background:#fff;">
        <div class="demo-grid">

            <!-- LEFT: What You'll See -->
            <div class="left-col">
                <div class="section-tag tag-violet">What You'll See</div>
                <h2 class="left-title">Your Personalized <span class="gradient-text">Live Demo</span></h2>

                <div class="feature-row">
                    <div class="feature-icon">🎓</div>
                    <div class="feature-text">Complete Student Dashboard</div>
                    <div class="feature-check">✓</div>
                </div>
                <div class="feature-row">
                    <div class="feature-icon">✅</div>
                    <div class="feature-text">Live Attendance Marking</div>
                    <div class="feature-check">✓</div>
                </div>
                <div class="feature-row">
                    <div class="feature-icon">📊</div>
                    <div class="feature-text">Real-time Analytics &amp; Reports</div>
                    <div class="feature-check">✓</div>
                </div>
                <div class="feature-row">
                    <div class="feature-icon">💰</div>
                    <div class="feature-text">Fee Collection &amp; Receipts</div>
                    <div class="feature-check">✓</div>
                </div>
                <div class="feature-row">
                    <div class="feature-icon">📝</div>
                    <div class="feature-text">Assignment &amp; Quiz Creator</div>
                    <div class="feature-check">✓</div>
                </div>
                <div class="feature-row">
                    <div class="feature-icon">📱</div>
                    <div class="feature-text">Mobile App Showcase</div>
                    <div class="feature-check">✓</div>
                </div>
                <div class="feature-row">
                    <div class="feature-icon">💬</div>
                    <div class="feature-text">Parent-Teacher Communication</div>
                    <div class="feature-check">✓</div>
                </div>
                <div class="feature-row">
                    <div class="feature-icon">🔐</div>
                    <div class="feature-text">Admin Control Panel</div>
                    <div class="feature-check">✓</div>
                </div>
            </div>

            <!-- RIGHT: Form -->
            <div>
                <div class="form-card">
                    <div class="form-card-title">Book Your Free Demo</div>
                    <p class="form-card-sub">Fill in the form and our team will reach out within 3 business days.</p>
                    <form id="demoForm" onsubmit="handleDemoSubmit(event)">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Your Name *</label>
                                <input class="form-input" type="text" name="name" placeholder="Full name"
                                    required />
                            </div>
                            <div class="form-group">
                                <label class="form-label">School Name *</label>
                                <input class="form-input" type="text" name="school"
                                    placeholder="School / Institution" required />
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Phone *</label>
                                <input class="form-input" type="tel" name="phone" placeholder="10-digit mobile number"
                                    required inputmode="numeric" maxlength="10" pattern="[6-9][0-9]{9}"
                                    title="Enter a valid 10-digit Indian mobile number starting with 6-9"
                                    oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email *</label>
                                <input class="form-input" type="email" name="email" placeholder="Email address"
                                    required pattern="[^@\s]+@[^@\s]+\.[^@\s]+"
                                    title="Enter a valid email address" />
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">City *</label>
                                <input class="form-input" type="text" name="city" placeholder="Your city" required />
                            </div>
                            <div class="form-group">
                                <label class="form-label">No. of Students *</label>
                                <select class="form-select" name="students" required>
                                    <option value="">Select range</option>
                                    <option>Under 200</option>
                                    <option>200–500</option>
                                    <option>500–1000</option>
                                    <option>1000–2000</option>
                                    <option>2000+</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Your Role *</label>
                            <select class="form-select" name="role" required>
                                <option value="">Select role</option>
                                <option>Principal</option>
                                <option>Administrator</option>
                                <option>Director</option>
                                <option>Manager</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-submit">Book My Free Demo</button>
                        <p class="form-disclaimer">No credit card required &nbsp;·&nbsp; No commitment &nbsp;·&nbsp;
                            Completely free</p>
                    </form>
                </div>
            </div>
        </div>

        <!-- Trust Badges -->
        <div class="trust-strip">
            <div class="trust-badge"><span class="trust-badge-icon">🏫</span> <span id="trustSchools">many</span> Schools Trust Us</div>
            <div class="trust-badge"><span class="trust-badge-icon">⭐</span> <span id="trustRating">4.9</span>/5 Average Rating</div>
            <div class="trust-badge"><span class="trust-badge-icon">🔒</span> 256-bit SSL Encrypted</div>
            <div class="trust-badge"><span class="trust-badge-icon">🎓</span> <span id="trustStudents">100K+</span> Active Students</div>
            <div class="trust-badge"><span class="trust-badge-icon">📱</span> iOS &amp; Android Apps</div>
        </div>
    </section>

    <!-- TOAST -->
    <div class="toast hidden" id="toast">
        <div class="toast-icon">✅</div>
        <div>
            <div class="toast-title">Demo Requested!</div>
            <div class="toast-msg">Our team will contact you within 3 business days.</div>
        </div>
    </div>



    @include('components.website.app-section')

    @include('components.website.footer')


    <script>
        // ── Dynamic trust badges (schools / rating / students) ──
        function fmtNum(n) {
            if (!n || n === 0) return '0';
            if (n >= 1000) return Math.floor(n / 1000) + 'K+';
            return n + '+';
        }
        fetch('/api/website/stats')
            .then(r => r.json())
            .then(({ data }) => {
                if (!data) return;
                const sc = document.getElementById('trustSchools');
                const ra = document.getElementById('trustRating');
                const st = document.getElementById('trustStudents');
                if (sc && data.schools > 0) sc.textContent = data.schools + '+';
                if (ra && data.rating) ra.textContent = data.rating;
                if (st && data.students > 0) st.textContent = fmtNum(data.students);
            })
            .catch(() => {});

        async function handleDemoSubmit(e) {
            e.preventDefault();
            const form = e.target;
            const btn = form.querySelector('.btn-submit');
            const orig = btn.textContent;
            btn.textContent = 'Submitting…';
            btn.disabled = true;

            try {
                const res = await fetch('/api/website/demo', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        full_name: form.name.value,
                        school_name: form.school.value,
                        phone: form.phone.value,
                        email: form.email.value,
                        city: form.city.value,
                        no_of_students: form.students.value,
                        role: form.role.value,
                    }),
                });
                const json = await res.json();
                if (json.success) {
                    form.reset();
                    showToast(json.message || 'Demo requested!', true);
                } else {
                    showToast(Object.values(json.errors || {}).flat()[0] || 'Something went wrong.', false);
                }
            } catch (err) {
                showToast('Network error. Please try again.', false);
            } finally {
                btn.textContent = orig;
                btn.disabled = false;
            }
        }

        function showToast(msg, success) {
            const toast = document.getElementById('toast');
            const titleEl = toast.querySelector('.toast-title');
            const msgEl = toast.querySelector('.toast-msg');
            const iconEl = toast.querySelector('.toast-icon');
            if (titleEl) titleEl.textContent = success ? 'Demo Requested!' : 'Error';
            if (msgEl) msgEl.textContent = msg;
            if (iconEl) iconEl.textContent = success ? '✅' : '❌';
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 4500);
        }
    </script>
</body>

</html>
