<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Terms of Use — SUPERLMS</title>
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
            /* Section bodies are stored as plain text — keep their line breaks
               and bullet lines instead of collapsing them into one paragraph. */
            white-space: pre-line;
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

    @include('components.website.header')

    <!-- PAGE HEADER -->
    <div class="policy-header">
        <div class="blob1"></div>
        <div class="blob2"></div>
        <div class="policy-header-content">
            <div class="section-tag tag-dual">Legal</div>
            <h1 class="section-title" style="margin-top:16px;">Terms of <span class="gradient-text">Use</span></h1>
            <p class="policy-header-sub" id="touSubtitle">SUPERLMS — superlms.in</p>
            <div class="last-updated" id="touLastUpdated">Last updated: —</div>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="policy-body">
        <div class="policy-inner">
            <div id="touSections"></div>
            <div id="touFallback">
                <!-- Static fallback content -->
                <div class="policy-section" id="u1">
                    <h2><span class="sec-num">1</span> Acceptance of Terms of Use</h2>
                    <p>By accessing or using the SUPERLMS platform, you agree to comply with and be bound by these
                        Terms of Use. These Terms govern your day-to-day use of the platform and its features.</p>
                </div>

                <div class="policy-section" id="u2">
                    <h2><span class="sec-num">2</span> Permitted Use</h2>
                    <p>You may use the SUPERLMS platform only for lawful educational and institutional management
                        purposes. Any use outside the scope of your subscription plan or these terms is strictly
                        prohibited.</p>
                </div>

                <div class="policy-section" id="u3">
                    <h2><span class="sec-num">3</span> Contact Us</h2>
                    <p>For any questions about these Terms of Use, please contact us at:</p>
                    <div class="contact-info-card">
                        <div class="contact-info-row"><span>📧</span><span>support@superlms.in</span></div>
                        <div class="contact-info-row"><span>📱</span><span>+91 9084748563</span></div>
                        <div class="contact-info-row"><span>📍</span><span>Office No. 02, Braj Vihar Colony, Jattari,
                                Khair, Aligarh, UP 202137</span></div>
                    </div>
                </div>
            </div><!-- /touFallback -->
        </div>
    </div>

    @include('components.website.app-section')

    @include('components.website.footer')

    <script>
        (async () => {
            try {
                const res = await fetch('/api/website/terms-of-use');
                const json = await res.json();
                const data = json.data;

                if (data && data.sections && data.sections.length) {
                    if (data.last_updated) {
                        document.getElementById('touLastUpdated').textContent = 'Last updated: ' + data
                        .last_updated;
                    }

                    const container = document.getElementById('touSections');
                    data.sections.forEach((sec, i) => {
                        const div = document.createElement('div');
                        div.className = 'policy-section';
                        div.id = `tous${i+1}`;
                        div.innerHTML =
                            `<h2><span class="sec-num">${i+1}</span> ${escHtml(sec.head)}</h2><p>${escHtml(sec.desc)}</p>`;
                        container.appendChild(div);
                    });

                    // Hide static fallback
                    document.getElementById('touFallback').style.display = 'none';
                } else {
                    document.getElementById('touLastUpdated').textContent = 'Last updated: January 1, 2025';
                }
            } catch (e) {
                document.getElementById('touLastUpdated').textContent = 'Last updated: January 1, 2025';
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
