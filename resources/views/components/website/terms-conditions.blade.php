<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Terms Of Use — SUPERLMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset('website-image/Group 11525.png') }}">

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
            --violet-dark: #5540D4;
            --primary-faint: #F9EDF5;
            --secondary-faint: #F0EDFF;
            --bg: #FFFFFF;
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
            --shadow2: 0 8px 32px rgba(111, 86, 254, 0.12);
            --radius: 16px;
            --radius-sm: 10px;
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
                transform: translateY(32px);
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

        .nav-links a:hover {
            color: var(--violet);
            background: var(--secondary-faint);
        }

        .btn {
            padding: 9px 22px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all .3s;
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
            z-index: 999;
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

        /* ── PAGE HEADER ── */
        .policy-header {
            text-align: center;
            padding: 120px 5% 60px;
            background: linear-gradient(135deg, var(--primary-faint), var(--secondary-faint));
            border-bottom: 1px solid var(--border2);
            position: relative;
            overflow: hidden;
        }

        .policy-header .blob1 {
            position: absolute;
            top: -60px;
            right: -60px;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            background: rgba(111, 86, 254, 0.07);
            filter: blur(60px);
            pointer-events: none;
        }

        .policy-header .blob2 {
            position: absolute;
            bottom: -40px;
            left: -40px;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: rgba(219, 87, 178, 0.07);
            filter: blur(60px);
            pointer-events: none;
        }

        .policy-header-content {
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

        .section-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(2.2rem, 4vw, 3.2rem);
            font-weight: 600;
            line-height: 1.1;
            letter-spacing: -0.5px;
            margin-bottom: 12px;
            color: var(--text);
        }

        .gradient-text {
            background: var(--grad2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .policy-header-sub {
            color: var(--text3);
            font-size: 14px;
            margin-top: 6px;
        }

        .last-updated {
            font-size: 12px;
            color: var(--text4);
            margin-top: 10px;
        }

        /* ── BODY ── */
        .policy-body {
            padding: 60px 6%;
            background: #fff;
        }

        .policy-inner {
            max-width: 820px;
            margin: 0 auto;
        }

        /* ── TOC ── */
        .toc {
            background: var(--bg3);
            border: 1px solid var(--border2);
            border-radius: var(--radius);
            padding: 24px 28px;
            margin-bottom: 48px;
        }

        .toc-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: var(--text3);
            margin-bottom: 14px;
        }

        .toc-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .toc-list a {
            font-size: 13px;
            color: var(--text3);
            text-decoration: none;
            transition: color .2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .toc-list a::before {
            content: '→';
            color: var(--violet);
            font-size: 11px;
        }

        .toc-list a:hover {
            color: var(--violet);
        }

        /* ── SECTIONS ── */
        .policy-section {
            margin-bottom: 40px;
            padding-bottom: 40px;
            border-bottom: 1px solid var(--border2);
            animation: fadeUp .5s ease forwards;
        }

        .policy-section:last-child {
            border-bottom: none;
        }

        .policy-section h2 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 22px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 14px;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid;
            border-image: var(--grad1) 1;
        }

        .policy-section h2 .sec-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            min-width: 28px;
            background: var(--grad1);
            color: #fff;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
        }

        .policy-section p {
            font-size: 14px;
            color: var(--text3);
            line-height: 1.85;
            margin-bottom: 12px;
        }

        .policy-section ul {
            margin: 8px 0 12px 0;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .policy-section ul li {
            font-size: 14px;
            color: var(--text3);
            line-height: 1.75;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .policy-section ul li::before {
            content: '';
            width: 6px;
            height: 6px;
            min-width: 6px;
            border-radius: 50%;
            background: var(--grad1);
            margin-top: 8px;
            flex-shrink: 0;
        }

        .highlight-box {
            background: linear-gradient(135deg, var(--primary-faint), var(--secondary-faint));
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 18px 20px;
            margin: 16px 0;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .highlight-box-icon {
            font-size: 20px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .highlight-box p {
            margin-bottom: 0 !important;
            color: var(--text2) !important;
            font-weight: 500;
        }

        .contact-info-card {
            background: var(--bg3);
            border: 1px solid var(--border2);
            border-radius: var(--radius-sm);
            padding: 18px 22px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 12px;
        }

        .contact-info-row {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: var(--text2);
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
            width: 36px;
            height: 36px;
            background: #fff;
            border: 1px solid var(--border2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all .3s;
            text-decoration: none;
            color: var(--text3);
        }

        .social-btn:hover {
            background: var(--grad1);
            border-color: transparent;
            transform: translateY(-3px);
            color: #fff;
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
        @media (max-width: 768px) {

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

            .policy-header {
                padding: 110px 5% 48px;
            }

            .policy-body {
                padding: 48px 5%;
            }
        }

        @media (max-width: 480px) {
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

            .policy-header {
                padding: 100px 4% 40px;
            }

            .policy-body {
                padding: 40px 4%;
            }

            .section-title {
                font-size: clamp(1.9rem, 7vw, 2.6rem);
            }

            .toc {
                padding: 18px 20px;
            }
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->


    @include('components.website.header')


    <!-- PAGE HEADER -->
    <div class="policy-header">
        <div class="blob1"></div>
        <div class="blob2"></div>
        <div class="policy-header-content">
            <div class="section-tag tag-dual">Legal</div>
            <h1 class="section-title" style="margin-top:16px;">Terms of <span class="gradient-text">Use</span>
            </h1>
            <p class="policy-header-sub" id="tcPlatformName">SUPERLMS — superlms.in</p>
            <div class="last-updated" id="tcLastUpdated">Last updated: —</div>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="policy-body">
        <div class="policy-inner">
            <div id="tcSections"></div>
            <div id="tcFallback">
                <!-- 1 -->
                <div class="policy-section" id="t1">
                    <h2><span class="sec-num">1</span> Acceptance of Terms</h2>
                    <p>By accessing or using the SUPERLMS platform ("Service") available at superlms.in and our
                        mobile
                        applications, you ("User," "School," or "Institution") agree to be bound by these Terms &amp;
                        Conditions ("Terms"). These Terms constitute a legally binding agreement between you and SUPERLMS
                        LMS
                        ("Company," "we," "us," or "our").</p>
                    <div class="highlight-box">
                        <div class="highlight-box-icon">⚠️</div>
                        <p>If you do not agree to these Terms, you must immediately discontinue use of our platform.
                            Continued use of the Service after any updates to these Terms constitutes acceptance of the
                            updated Terms.</p>
                    </div>
                </div>

                <!-- 2 -->
                <div class="policy-section" id="t2">
                    <h2><span class="sec-num">2</span> Description of Service</h2>
                    <p>SUPERLMS is a cloud-based Learning Management System (LMS) designed for schools and educational
                        institutions in India. The Service includes, but is not limited to:</p>
                    <ul>
                        <li>Student and teacher management modules</li>
                        <li>Attendance tracking (manual and biometric)</li>
                        <li>Academic management — timetables, syllabus, homework, quizzes</li>
                        <li>Fee collection, payroll, and financial management</li>
                        <li>Communication tools — announcements, WhatsApp and SMS notifications</li>
                        <li>Document generation — ID cards, admit cards, report cards, TC &amp; certificates</li>
                        <li>Library, transportation, and inventory management</li>
                        <li>Mobile applications for iOS and Android</li>
                    </ul>
                    <p>We reserve the right to modify, suspend, or discontinue any part of the Service at any time, with
                        reasonable notice where possible.</p>
                </div>

                <!-- 3 -->
                <div class="policy-section" id="t3">
                    <h2><span class="sec-num">3</span> User Accounts &amp; Registration</h2>
                    <p>To use the Service, your institution must register an account. You agree to:</p>
                    <ul>
                        <li>Provide accurate, complete, and current registration information</li>
                        <li>Maintain the security and confidentiality of your login credentials</li>
                        <li>Notify us immediately of any unauthorized use of your account</li>
                        <li>Be responsible for all activity that occurs under your account</li>
                        <li>Not share account credentials with unauthorized persons</li>
                        <li>Ensure all users (staff, teachers, parents, students) comply with these Terms</li>
                    </ul>
                    <div class="highlight-box">
                        <div class="highlight-box-icon">🔐</div>
                        <p>Your school administrator is fully responsible for managing user access within your
                            institution.
                            SUPERLMS is not liable for unauthorized access resulting from negligent credential
                            management.
                        </p>
                    </div>
                </div>

                <!-- 4 -->
                <div class="policy-section" id="t4">
                    <h2><span class="sec-num">4</span> Subscription Plans &amp; Payment</h2>
                    <p>SUPERLMS offers the following subscription plans:</p>
                    <ul>
                        <li><strong>School Plan:</strong> ₹250/year (₹20.83/month), billed annually, for up to 500
                            students</li>
                        <li><strong>Enterprise Plan:</strong> Custom pricing for larger institutions — contact us for a
                            quote</li>
                    </ul>
                    <p>Payment terms:</p>
                    <ul>
                        <li>All fees are billed annually in advance in Indian Rupees (INR)</li>
                        <li>Payments are processed via secure, PCI-DSS compliant payment gateways</li>
                        <li>Subscriptions auto-renew unless cancelled at least 15 days before the renewal date</li>
                        <li>No refunds are provided for partial periods after the 7-day free trial</li>
                        <li>Pricing is subject to change with 30 days prior written notice</li>
                        <li>GST and applicable taxes will be added as per Indian tax regulations</li>
                    </ul>
                    <div class="highlight-box">
                        <div class="highlight-box-icon">💳</div>
                        <p>A 7-day free trial is available for all new school registrations with no credit card
                            required.
                            After the trial period, a valid subscription is required to continue accessing the platform.
                        </p>
                    </div>
                </div>

                <!-- 5 -->
                <div class="policy-section" id="t5">
                    <h2><span class="sec-num">5</span> Acceptable Use Policy</h2>
                    <p>You agree to use the SUPERLMS platform only for lawful educational and administrative purposes.
                        You
                        must NOT:</p>
                    <ul>
                        <li>Use the platform to transmit spam, malware, or any harmful content</li>
                        <li>Attempt to gain unauthorized access to any part of our systems</li>
                        <li>Reverse engineer, decompile, or attempt to extract source code</li>
                        <li>Resell, redistribute, or sublicense the Service without written consent</li>
                        <li>Use the platform to store or share illegal, defamatory, or offensive content</li>
                        <li>Upload content that violates third-party intellectual property rights</li>
                        <li>Conduct automated scraping or data harvesting of any kind</li>
                        <li>Impersonate any person or institution or misrepresent your affiliation</li>
                    </ul>
                    <p>Violation of this Acceptable Use Policy may result in immediate account suspension or termination
                        without refund.</p>
                </div>

                <!-- 6 -->
                <div class="policy-section" id="t6">
                    <h2><span class="sec-num">6</span> Intellectual Property Rights</h2>
                    <p>All intellectual property in the SUPERLMS platform — including software, code, design,
                        trademarks,
                        logos, and content — is exclusively owned by SUPERLMS and its licensors. You are granted a
                        limited, non-exclusive, non-transferable license to use the Service during your active
                        subscription
                        period.</p>
                    <p>You retain full ownership of all data and content you upload to the platform ("Your Content"). By
                        using the Service, you grant SUPERLMS a limited license to process, store, and display Your
                        Content solely to provide the Service.</p>
                    <div class="highlight-box">
                        <div class="highlight-box-icon">©️</div>
                        <p>The SUPERLMS name, logo, and all associated marks are registered trademarks. Unauthorized
                            use
                            of our trademarks is strictly prohibited and may result in legal action.</p>
                    </div>
                </div>

                <!-- 7 -->
                <div class="policy-section" id="t7">
                    <h2><span class="sec-num">7</span> Data Ownership &amp; Privacy</h2>
                    <p>You (the school/institution) own all data you input into the platform, including student records,
                        academic data, and financial information. SUPERLMS acts as a Data Processor on your behalf and
                        processes data only as instructed.</p>
                    <p>Our full data handling practices are described in our <a href="{{ url('web/privacy-policy') }}"
                            style="color:var(--violet);text-decoration:none;font-weight:600;">Privacy Policy</a>, which
                        is
                        incorporated into these Terms by reference. Key commitments:</p>
                    <ul>
                        <li>We will never sell your data or student data to third parties</li>
                        <li>Data is encrypted in transit and at rest</li>
                        <li>You may export all your data at any time during your subscription</li>
                        <li>Upon termination, data is available for export for 30 days, then securely deleted</li>
                        <li>We comply with the Indian IT Act 2000 and the Digital Personal Data Protection Act 2023</li>
                    </ul>
                </div>

                <!-- 8 -->
                <div class="policy-section" id="t8">
                    <h2><span class="sec-num">8</span> Service Availability &amp; SLA</h2>
                    <p>SUPERLMS targets a 99.9% uptime Service Level Agreement (SLA) measured monthly, excluding
                        scheduled
                        maintenance windows.</p>
                    <ul>
                        <li>Scheduled maintenance will be communicated at least 24 hours in advance</li>
                        <li>Emergency maintenance may occur without prior notice to protect platform security</li>
                        <li>Uptime credits are available for downtime exceeding the SLA threshold — contact support</li>
                        <li>We do not guarantee uninterrupted access during force majeure events</li>
                    </ul>
                    <div class="highlight-box">
                        <div class="highlight-box-icon">⚡</div>
                        <p>We target 99.9% uptime. In the event of extended downtime beyond our SLA, affected schools
                            may be
                            eligible for prorated service credits. Contact support@superlms.in to submit a claim.</p>
                    </div>
                </div>

                <!-- 9 -->
                <div class="policy-section" id="t9">
                    <h2><span class="sec-num">9</span> Limitation of Liability</h2>
                    <p>To the maximum extent permitted by applicable Indian law, SUPERLMS and its directors, officers,
                        employees, and agents shall not be liable for:</p>
                    <ul>
                        <li>Indirect, incidental, special, consequential, or punitive damages</li>
                        <li>Loss of data, revenue, profits, or business opportunities</li>
                        <li>Damages resulting from unauthorized access to or use of your account</li>
                        <li>Service interruptions caused by third-party infrastructure or force majeure events</li>
                    </ul>
                    <p>Our total cumulative liability to you for any claims arising from your use of the Service shall
                        not
                        exceed the total subscription fees you paid in the 12 months preceding the claim.</p>
                </div>

                <!-- 10 -->
                <div class="policy-section" id="t10">
                    <h2><span class="sec-num">10</span> Termination</h2>
                    <p>Either party may terminate the subscription agreement:</p>
                    <ul>
                        <li><strong>By the School:</strong> Written notice at least 15 days before the renewal date via
                            email to support@superlms.in</li>
                        <li><strong>By SUPERLMS:</strong> Immediately, with or without notice, in cases of Terms
                            violations, non-payment, or fraudulent activity</li>
                        <li><strong>Mutual Agreement:</strong> Both parties may agree to terminate at any time</li>
                    </ul>
                    <p>Upon termination, your access to the platform will be suspended at the end of the current billing
                        cycle. All data remains available for export for 30 days after termination. No refunds are
                        issued
                        for unused portions of the subscription period unless termination is due to our material breach.
                    </p>
                </div>

                <!-- 11 -->
                <div class="policy-section" id="t11">
                    <h2><span class="sec-num">11</span> Governing Law &amp; Disputes</h2>
                    <p>These Terms shall be governed by and construed in accordance with the laws of India. Any disputes
                        arising out of or in connection with these Terms shall be subject to the exclusive jurisdiction
                        of
                        the courts in Aligarh, Uttar Pradesh, India.</p>
                    <p>Before initiating legal proceedings, both parties agree to attempt to resolve disputes through
                        good-faith negotiation for a period of 30 days. If unresolved, disputes may be referred to
                        binding
                        arbitration under the Arbitration and Conciliation Act, 1996 of India.</p>
                </div>

                <!-- 12 -->
                <div class="policy-section" id="t12">
                    <h2><span class="sec-num">12</span> Modifications to Terms</h2>
                    <p>We reserve the right to modify these Terms at any time. Material changes will be communicated
                        via:
                    </p>
                    <ul>
                        <li>Email notification to the registered school administrator at least 30 days in advance</li>
                        <li>A prominent banner notice within the SUPERLMS platform</li>
                        <li>Updated "Last Modified" date on this page</li>
                    </ul>
                    <p>Your continued use of the Service after the effective date of any changes constitutes your
                        acceptance
                        of the revised Terms. If you do not agree with the revised Terms, you must terminate your
                        subscription before the changes take effect.</p>
                    <div class="highlight-box">
                        <div class="highlight-box-icon">📢</div>
                        <p>We encourage you to review these Terms periodically. Minor clarifications and non-material
                            changes may be made without advance notice.</p>
                    </div>
                </div>

                <!-- 13 -->
                <div class="policy-section" id="t13">
                    <h2><span class="sec-num">13</span> Contact Us</h2>
                    <p>For any questions about these Terms &amp; Conditions, billing disputes, or legal enquiries,
                        please
                        contact us at:</p>
                    <div class="contact-info-card">
                        <div class="contact-info-row"><span>📧</span><span>support@superlms.in</span></div>
                        <div class="contact-info-row"><span>📱</span><span>+91 9084748563</span></div>
                        <div class="contact-info-row"><span>📍</span><span>Floor 2, Braj Vihar Colony, Main Road
                                Jattari,
                                Aligarh, Uttar Pradesh 202137, India</span></div>
                    </div>
                </div>

            </div><!-- /tcFallback -->
        </div>
    </div>

    @include('components.website.app-section')

    @include('components.website.footer')

    <script>
        (async () => {
            try {
                const res = await fetch('/api/website/terms-conditions');
                const json = await res.json();
                const data = json.data;

                if (data && data.sections && data.sections.length) {
                    if (data.last_updated) {
                        document.getElementById('tcLastUpdated').textContent = 'Last updated: ' + data.last_updated;
                    }
                    if (data.platform_name) {
                        document.getElementById('tcPlatformName').textContent = data.platform_name;
                    }

                    const container = document.getElementById('tcSections');
                    data.sections.forEach((sec, i) => {
                        const div = document.createElement('div');
                        div.className = 'policy-section';
                        div.id = `tcs${i+1}`;
                        div.innerHTML =
                            `<h2><span class="sec-num">${i+1}</span> ${escHtml(sec.head)}</h2><p>${escHtml(sec.desc)}</p>`;
                        container.appendChild(div);
                    });

                    // Hide static fallback
                    document.getElementById('tcFallback').style.display = 'none';
                } else {
                    document.getElementById('tcLastUpdated').textContent = 'Last updated: January 1, 2025';
                }
            } catch (e) {
                document.getElementById('tcLastUpdated').textContent = 'Last updated: January 1, 2025';
            }
        })();

        function escHtml(str) {
            const d = document.createElement('div');
            d.appendChild(document.createTextNode(str || ''));
            return d.innerHTML;
        }
    </script>
</body>

</html>
