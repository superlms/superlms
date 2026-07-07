<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="google-site-verification" content="GdMracC0ZkQ_UWHR922wYADBk2AS9KO5yALR8BFNFdY" />
    <title>SUPERLMS — India's #1 School Management Platform</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-7S4FD1GMPK"></script>
    <link rel="icon" type="image/png" href="{{ asset('website-image/Group 11525.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('website-image/Group 11525.png') }}">
    @include('partials.pwa-head')
    <meta property="og:title" content="SUPERLMS — India's #1 School Management Platform" />
    <meta property="og:description" content="Smart attendance, automated timetables, fee management, and real-time progress tracking — all in one powerful platform trusted by schools across India." />
    <meta property="og:image" content="{{ asset('website-image/Group 11525.png') }}" />
    <meta property="og:image:width" content="512" />
    <meta property="og:image:height" content="512" />
    <meta property="og:url" content="https://superlms.in/" />
    <meta property="og:type" content="website" />
    <meta name="twitter:card" content="summary" />
    <meta name="twitter:image" content="{{ asset('website-image/Group 11525.png') }}" />
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "Organization",
      "name": "SUPERLMS",
      "url": "https://superlms.in",
      "logo": "{{ asset('website-image/Group 11525.png') }}",
      "description": "India's #1 School Management Platform",
      "sameAs": ["https://www.instagram.com/superlms"]
    }
    </script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'G-7S4FD1GMPK');
    </script>
    <meta name="description"
        content="SUPERLMS is a complete academic ecosystem for schools and institutions. Smart attendance, automated timetables, fee management, and real-time progress tracking — all in one powerful platform trusted by many schools across India." />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600;700&display=swap');

        :root {
            --pink: #DB57B2;
            --pink-dark: #B83D92;
            --pink-light: #E878C4;
            --violet: #6F56FE;
            --violet-dark: #5540D4;
            --violet-light: #8B74FF;
            --primary: #DB57B2;
            --primary-dark: #B83D92;
            --primary-light: #E878C4;
            --primary-soft: #C44A9E;
            --primary-faint: #F9EDF5;
            --secondary-faint: #F0EDFF;
            --dual-faint: #FAF0FF;
            --bg: #FFFFFF;
            --bg2: #FAFAFA;
            --bg3: #F7F4FF;
            --bg4: #EDE8FF;
            --text: #1A0F2E;
            --text2: #2D1B4E;
            --text3: #6B5B8A;
            --text4: #A99CC0;
            --border: rgba(111, 86, 254, 0.15);
            --border2: rgba(111, 86, 254, 0.08);
            --border-pink: rgba(219, 87, 178, 0.18);
            --grad1: linear-gradient(135deg, #DB57B2 0%, #6F56FE 100%);
            --grad2: linear-gradient(135deg, #E878C4 0%, #DB57B2 40%, #6F56FE 100%);
            --grad3: linear-gradient(180deg, rgba(111, 86, 254, 0.07) 0%, transparent 100%);
            --grad-pink: linear-gradient(135deg, #DB57B2 0%, #B83D92 100%);
            --grad-violet: linear-gradient(135deg, #6F56FE 0%, #5540D4 100%);
            --shadow: 0 24px 64px rgba(111, 86, 254, 0.18);
            --shadow2: 0 8px 32px rgba(111, 86, 254, 0.12);
            --shadow3: 0 2px 12px rgba(111, 86, 254, 0.08);
            --shadow-pink: 0 8px 32px rgba(219, 87, 178, 0.2);
            --radius: 16px;
            --radius-sm: 10px;
            --radius-lg: 24px;
            --radius-xl: 32px;
        }

        /* ── Design tokens ── */
        :root {
            --pink: #DB57B2;
            --pink-dark: #B83D92;
            --violet: #6F56FE;
            --violet-dark: #5540D4;
            --primary-faint: #F9EDF5;
            --secondary-faint: #F0EDFF;
            --bg2: #FAFAFA;
            --bg3: #F7F4FF;
            --bg4: #EDE8FF;
            --text: #1A0F2E;
            --text2: #2D1B4E;
            --text3: #6B5B8A;
            --text4: #A99CC0;
            --border: rgba(111, 86, 254, 0.15);
            --border2: rgba(111, 86, 254, 0.08);
            --grad1: linear-gradient(135deg, #DB57B2 0%, #6F56FE 100%);
            --shadow2: 0 8px 32px rgba(111, 86, 254, 0.12);
            --shadow3: 0 2px 12px rgba(111, 86, 254, 0.08);
            --radius: 16px;
            --radius-sm: 10px;
        }

        * {
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

        /* ─── Animations ─── */
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

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideLeft {
            from {
                opacity: 0;
                transform: translateX(-60px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideRight {
            from {
                opacity: 0;
                transform: translateX(60px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-14px);
            }
        }

        @keyframes floatSlow {

            0%,
            100% {
                transform: translateY(0) rotate(0deg);
            }

            50% {
                transform: translateY(-10px) rotate(3deg);
            }
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: .5;
                transform: scale(.9);
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

        @keyframes barGrow {
            from {
                width: 0;
            }
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        @keyframes gradShift {

            0%,
            100% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
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

        @keyframes blobMove {

            0%,
            100% {
                border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
            }

            50% {
                border-radius: 30% 60% 70% 40% / 50% 60% 30% 60%;
            }
        }

        .blob {
            position: absolute;
            border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
            animation: blobMove 8s ease-in-out infinite;
            filter: blur(60px);
            pointer-events: none;
        }

        /* ─── Navbar ─── */
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
            transition: all .4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(24px);
            border-bottom: 1px solid var(--border2);
            box-shadow: 0 4px 40px rgba(111, 86, 254, 0.1);
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

        .nav-cta {
            display: flex;
            align-items: center;
            gap: 10px;
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

        .btn-xl {
            padding: 15px 40px;
            font-size: 16px;
            border-radius: 13px;
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
            transition: all .3s;
        }

        /* ─── Section Utilities ─── */
        .section {
            padding: 100px 6%;
            position: relative;
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
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        .tag-violet {
            background: var(--secondary-faint);
            border: 1px solid var(--border);
            color: var(--violet);
        }

        .tag-pink {
            background: var(--primary-faint);
            border: 1px solid var(--border-pink);
            color: var(--pink-dark);
        }

        .tag-dual {
            background: linear-gradient(135deg, var(--primary-faint), var(--secondary-faint));
            border: 1px solid var(--border);
            color: var(--violet);
        }

        .section-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(2.2rem, 4vw, 3.6rem);
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

        .gradient-text-violet {
            background: var(--grad-violet);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .section-subtitle {
            font-size: 16px;
            color: var(--text3);
            line-height: 1.75;
            max-width: 560px;
            font-weight: 400;
        }

        .section-center {
            text-align: center;
        }

        .section-center .section-subtitle {
            margin: 0 auto;
        }

        /* ─── Hero ─── */
        .hero-section {
            position: relative;
            min-height: 100vh;
            padding-top: 72px;
            background: linear-gradient(135deg, #faf8ff 0%, #fff0fa 50%, #f5f0ff 100%);
            overflow: hidden;
            overflow-x: hidden;
            /* slide fix */
        }

        .hero-blob-1 {
            position: absolute;
            top: -100px;
            right: -100px;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: rgba(111, 86, 254, 0.07);
            filter: blur(80px);
            pointer-events: none;
        }

        .hero-blob-2 {
            position: absolute;
            bottom: -80px;
            left: -80px;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: rgba(219, 87, 178, 0.07);
            filter: blur(80px);
            pointer-events: none;
        }

        .hero-inner {
            max-width: 1280px;
            margin: 0 auto;
            padding: 60px 5% 80px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            background: rgba(111, 86, 254, 0.08);
            border: 1px solid rgba(111, 86, 254, 0.18);
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            color: #6F56FE;
            margin-bottom: 24px;
        }

        .hero-badge-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #6F56FE;
            animation: pulse 2s infinite;
        }

        .hero-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(36px, 4vw, 58px);
            font-weight: 700;
            color: #1a1a2e;
            line-height: 1.15;
            margin-bottom: 20px;
        }

        .hero-title .highlight {
            background: linear-gradient(135deg, #6F56FE, #DB57B2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-desc {
            font-size: 15px;
            color: #555;
            line-height: 1.8;
            margin-bottom: 32px;
            max-width: 520px;
        }

        .hero-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 40px;
        }

        .hero-stats {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }

        .hero-stat-divider {
            width: 1px;
            height: 32px;
            background: rgba(111, 86, 254, 0.2);
            margin: 0 16px;
        }

        .hero-stat {
            text-align: center;
        }

        .hero-stat-num {
            font-size: 20px;
            font-weight: 700;
            background: linear-gradient(135deg, #6F56FE, #DB57B2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
        }

        .hero-stat-label {
            font-size: 11px;
            color: #888;
            margin-top: 2px;
        }

        .dash-top-bar {
            background: linear-gradient(135deg, #6F56FE 0%, #DB57B2 100%);
            padding: 14px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .dash-top-logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dash-top-icon {
            width: 30px;
            height: 30px;
            background: rgba(255, 255, 255, 0.22);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .dash-top-title {
            color: #fff;
            font-weight: 700;
            font-size: 12px;
        }

        .dash-top-sub {
            color: rgba(255, 255, 255, 0.65);
            font-size: 10px;
        }

        .dash-top-dots {
            display: flex;
            gap: 5px;
        }

        .dash-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
        }

        .dash-kpi-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            border-bottom: 1px solid #f3f3f3;
        }

        .dash-kpi-cell {
            padding: 11px 4px;
            text-align: center;
            border-right: 1px solid #f3f3f3;
        }

        .dash-kpi-cell:last-child {
            border-right: none;
        }

        .dash-kpi-icon {
            font-size: 13px;
            margin-bottom: 2px;
        }

        .dash-kpi-num {
            font-size: 12px;
            font-weight: 700;
            background: linear-gradient(135deg, #6F56FE, #DB57B2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
        }

        .dash-kpi-label {
            font-size: 8px;
            color: #bbb;
            margin-top: 1px;
        }

        .dash-body {
            padding: 14px 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .dash-section-title {
            font-size: 11px;
            font-weight: 600;
            color: #333;
            margin-bottom: 7px;
        }

        .dash-week-tag {
            font-size: 9px;
            color: #6F56FE;
            font-weight: 700;
            background: rgba(111, 86, 254, 0.1);
            padding: 2px 7px;
            border-radius: 20px;
        }

        .dash-chart-row {
            display: flex;
            align-items: flex-end;
            gap: 4px;
            height: 48px;
        }

        .dash-bar-wrap {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
        }

        .dash-bar {
            width: 100%;
            border-radius: 3px 3px 0 0;
        }

        .dash-bar-label {
            font-size: 8px;
            color: #bbb;
        }

        .dash-activity-title {
            font-size: 11px;
            font-weight: 600;
            color: #333;
            margin-bottom: 6px;
        }

        .dash-activity-row {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 5px 8px;
            background: #fafafa;
            border-radius: 7px;
            border: 1px solid #f0f0f0;
            margin-bottom: 4px;
        }

        .dash-activity-icon {
            width: 22px;
            height: 22px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            flex-shrink: 0;
        }

        .dash-activity-text {
            flex: 1;
            font-size: 10px;
            font-weight: 500;
            color: #444;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dash-activity-time {
            font-size: 9px;
            color: #ccc;
            flex-shrink: 0;
        }

        .dash-module-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }

        .dash-module-pill {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 3px 9px;
            background: linear-gradient(135deg, rgba(111, 86, 254, 0.07), rgba(219, 87, 178, 0.06));
            border: 1px solid rgba(111, 86, 254, 0.12);
            border-radius: 20px;
            font-size: 9px;
            font-weight: 600;
            color: #6F56FE;
        }

        .dash-footer {
            padding: 7px 16px;
            background: #fafafa;
            border-top: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .dash-footer-sync {
            font-size: 9px;
            color: #ccc;
        }

        .dash-footer-status {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 9px;
            color: #16A34A;
            font-weight: 700;
        }

        .dash-status-dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: #16A34A;
            animation: pulse 1.8s infinite;
        }

        /* ─── Hero animated dashboard illustration ─── */
        .hero-visual {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .hero-dash {
            width: 100%;
            max-width: 380px;
            background: #fff;
            border-radius: 18px;
            border: 1px solid var(--border2);
            box-shadow: 0 30px 70px rgba(111, 86, 254, 0.18);
            overflow: hidden;
            animation: floatSlow 6s ease-in-out infinite;
        }

        .hero-dash-glow {
            position: absolute;
            inset: -40px;
            background: radial-gradient(circle at 70% 30%, rgba(111, 86, 254, 0.18), transparent 60%),
                radial-gradient(circle at 30% 80%, rgba(219, 87, 178, 0.16), transparent 60%);
            filter: blur(20px);
            pointer-events: none;
            z-index: -1;
        }

        /* Floating mini badges around the dashboard */
        .hero-float-badge {
            position: absolute;
            display: flex;
            align-items: center;
            gap: 7px;
            background: #fff;
            border: 1px solid var(--border2);
            border-radius: 12px;
            padding: 8px 12px;
            box-shadow: 0 10px 28px rgba(31, 31, 60, 0.12);
            font-size: 11px;
            font-weight: 700;
            color: var(--text);
            z-index: 2;
            white-space: nowrap;
        }

        .hero-float-badge .hfb-icon {
            width: 24px;
            height: 24px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .hero-float-badge .hfb-sub {
            font-size: 9px;
            font-weight: 500;
            color: var(--text3);
        }

        .hero-float-1 {
            top: 6%;
            left: -28px;
            animation: float 5s ease-in-out infinite;
        }

        .hero-float-2 {
            bottom: 10%;
            right: -24px;
            animation: float 6.5s ease-in-out infinite;
            animation-delay: .8s;
        }

        /* Bars rise on load + breathe */
        .hero-dash .dash-bar {
            transform-origin: bottom;
            animation: barRise .9s cubic-bezier(0.34, 1.56, 0.64, 1) both;
        }

        @keyframes barRise {
            from {
                transform: scaleY(0);
            }

            to {
                transform: scaleY(1);
            }
        }

        /* ─── Marquee (school logos — 5 visible, steps one logo right→left every
               second; the centre logo is largest, its neighbours smaller) ─── */
        .marquee-section {
            padding: 44px 0;
            background: var(--bg3);
            border-top: 1px solid var(--border2);
            border-bottom: 1px solid var(--border2);
            overflow: hidden;
        }

        /* Centered window that fits exactly 5 slots. */
        .marquee-viewport {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            overflow: hidden;
            -webkit-mask-image: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent);
                    mask-image: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent);
        }

        .marquee-track {
            display: flex;
            gap: 0;
            width: max-content;
            will-change: transform;
        }

        /* Each slot is 1/5 of the visible window, so exactly 5 logos show. */
        .marquee-item {
            width: calc(min(100vw, 1200px) / 5);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .marquee-logo {
            width: clamp(50px, 9vw, 90px);
            height: clamp(50px, 9vw, 90px);
            border-radius: 50%;
            object-fit: contain;
            background: #fff;
            border: 1px solid var(--border2);
            box-shadow: 0 4px 14px rgba(0, 0, 0, .06);
            padding: 10px;
            flex-shrink: 0;
            transition: transform .5s ease, box-shadow .5s ease;
        }

        /* Positional emphasis: centre biggest, the pair next to it in between. */
        .marquee-item.pos-near .marquee-logo {
            transform: scale(1.12);
        }

        .marquee-item.pos-center .marquee-logo {
            transform: scale(1.35);
            box-shadow: 0 10px 26px rgba(111, 86, 254, .22);
            border-color: var(--violet, #6F56FE);
        }

        /* When fewer than 5 schools exist: no sliding — just center exactly
           that many logos in a static grid. */
        .marquee-track.marquee-static {
            animation: none;
            width: 100%;
            justify-content: center;
            flex-wrap: wrap;
            gap: clamp(16px, 4vw, 56px);
        }

        .marquee-track.marquee-static .marquee-item {
            width: auto;
        }

        /* ─── How It Works ─── */
        .hiw-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1px;
            margin-top: 60px;
            background: var(--border2);
            border-radius: var(--radius-lg);
            overflow: hidden;
            border: 1px solid var(--border2);
        }

        .hiw-card {
            background: #fff;
            padding: 40px 36px;
            position: relative;
            transition: all .35s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .hiw-card:hover {
            background: var(--bg3);
            transform: translateY(-4px);
            z-index: 2;
            box-shadow: var(--shadow2);
        }

        .hiw-step {
            font-family: 'Cormorant Garamond', serif;
            font-size: 72px;
            font-weight: 700;
            background: var(--grad1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            opacity: .08;
            position: absolute;
            top: 20px;
            right: 24px;
            line-height: 1;
        }

        .hiw-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }

        .hiw-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 22px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 12px;
        }

        .hiw-desc {
            font-size: 14px;
            color: var(--text3);
            line-height: 1.75;
        }

        /* ─── Stats ─── */
        .stats-section {
            padding: 72px 6%;
            background: linear-gradient(135deg, var(--primary-faint) 0%, var(--secondary-faint) 100%);
            border-top: 1px solid var(--border2);
            border-bottom: 1px solid var(--border2);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1px;
            background: var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            max-width: 1100px;
            margin: 0 auto;
            border: 1px solid var(--border);
        }

        .stat-card {
            text-align: center;
            padding: 40px 20px;
            background: #fff;
            transition: all .3s;
        }

        .stat-card:hover {
            background: var(--bg3);
        }

        .stat-icon {
            font-size: 28px;
            margin-bottom: 14px;
        }

        .stat-num {
            font-family: 'Cormorant Garamond', serif;
            font-size: 44px;
            font-weight: 700;
            background: var(--grad1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 13px;
            color: var(--text3);
            font-weight: 500;
        }

        /* ─── Analytics Section ─── */
        .illus-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            max-width: 1100px;
            margin: 0 auto;
        }

        .analytics-bullet {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            background: var(--secondary-faint);
            border-radius: 10px;
            border: 1px solid var(--border);
            margin-bottom: 12px;
        }

        .analytics-bullet-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--grad1);
            min-width: 8px;
        }

        .analytics-bullet-text {
            font-size: 14px;
            color: var(--text2);
            font-weight: 500;
        }

        /* ─── Features ─── */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1px;
            background: var(--border2);
            border-radius: var(--radius-lg);
            overflow: hidden;
            margin-top: 56px;
            border: 1px solid var(--border2);
        }

        .feature-card {
            background: #fff;
            padding: 32px 28px;
            transition: all .3s;
            cursor: default;
            position: relative;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--grad1);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform .35s;
        }

        .feature-card:hover::before {
            transform: scaleX(1);
        }

        .feature-card:hover {
            background: var(--bg3);
        }

        .feature-icon-wrap {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 18px;
        }

        .feature-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 10px;
        }

        .feature-desc {
            font-size: 13px;
            color: var(--text3);
            line-height: 1.75;
        }

        .feature-tag {
            display: inline-block;
            margin-top: 14px;
            padding: 3px 10px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .tag-v {
            background: var(--secondary-faint);
            color: var(--violet);
        }

        .tag-p {
            background: var(--primary-faint);
            color: var(--pink-dark);
        }

        /* ─── Modules Mini ─── */
        .modules-mini-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 40px;
        }

        .module-mini {
            background: #fff;
            border: 1px solid var(--border2);
            border-radius: var(--radius-sm);
            padding: 16px 14px;
            text-align: center;
            transition: all .3s;
            cursor: default;
        }

        .module-mini:hover {
            border-color: var(--border);
            transform: translateY(-4px);
            box-shadow: var(--shadow3);
            background: var(--bg3);
        }

        .module-mini-icon {
            font-size: 22px;
            margin-bottom: 8px;
        }

        .module-mini-name {
            font-size: 11px;
            font-weight: 600;
            color: var(--text);
        }

        /* ─── Role Cards ─── */
        .role-cards-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-top: 56px;
            max-width: 1060px;
            margin-left: auto;
            margin-right: auto;
        }

        .role-card {
            border-radius: var(--radius);
            padding: 28px 32px;
            transition: all .35s;
        }

        .role-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow2);
        }

        .role-card-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 20px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 20px;
        }

        .role-point {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 13px;
            color: var(--text2);
            margin-bottom: 9px;
        }

        .role-point-check {
            width: 16px;
            height: 16px;
            min-width: 16px;
            background: rgba(34, 197, 94, .12);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            color: #16A34A;
            margin-top: 1px;
        }

        /* ─── Apps Section ─── */
        .apps-download-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 52px;
            max-width: 920px;
            margin-left: auto;
            margin-right: auto;
        }

        .app-card {
            background: #fff;
            border: 1px solid var(--border2);
            border-radius: var(--radius-lg);
            padding: 36px 28px;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: all .4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .app-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--grad1);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform .35s;
        }

        .app-card:hover::before {
            transform: scaleX(1);
        }

        .app-card:hover {
            transform: translateY(-8px);
            border-color: var(--border);
            box-shadow: var(--shadow2);
        }

        .app-card-icon {
            width: 72px;
            height: 72px;
            border-radius: 18px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            position: relative;
        }

        .app-card-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 22px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
        }

        .app-card-desc {
            font-size: 13px;
            color: var(--text3);
            line-height: 1.7;
            margin-bottom: 24px;
        }

        .app-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 16px;
        }

        .app-dl-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all .3s;
            border: none;
            font-family: 'DM Sans', sans-serif;
            text-decoration: none;
            margin-bottom: 10px;
        }

        .app-dl-btn-primary {
            background: var(--grad1);
            color: #fff;
            box-shadow: 0 4px 16px rgba(111, 86, 254, 0.28);
        }

        .app-dl-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(111, 86, 254, 0.4);
        }

        .app-dl-btn-secondary {
            background: var(--bg3);
            color: var(--text2);
            border: 1px solid var(--border2);
        }

        .app-dl-btn-secondary:hover {
            background: var(--secondary-faint);
            border-color: var(--border);
        }

        /* ─── Pricing ─── */
        .pricing-snap-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            max-width: 740px;
            margin: 48px auto 0;
        }

        .home-price-school {
            background: linear-gradient(160deg, var(--secondary-faint), #fff);
            border: 2px solid var(--violet);
            border-radius: var(--radius-lg);
            padding: 40px 32px;
            position: relative;
            box-shadow: var(--shadow);
            transition: all .35s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .home-price-school:hover {
            transform: translateY(-6px);
            box-shadow: 0 28px 64px rgba(111, 86, 254, 0.22);
        }

        .home-price-enterprise {
            background: #fff;
            border: 1px solid var(--border2);
            border-radius: var(--radius-lg);
            padding: 40px 32px;
            transition: all .35s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .home-price-enterprise:hover {
            border-color: var(--pink);
            transform: translateY(-6px);
        }

        .price-plan-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }

        .price-amount {
            font-family: 'Cormorant Garamond', serif;
            font-size: 52px;
            font-weight: 700;
            color: var(--text);
            line-height: 1;
            margin-bottom: 2px;
        }

        .price-amount-cents {
            font-size: 18px;
            font-weight: 500;
            color: var(--text3);
            font-family: 'DM Sans', sans-serif;
        }

        .price-sub {
            font-size: 12px;
            color: var(--text3);
            margin-bottom: 4px;
        }

        .price-note {
            font-size: 11px;
            color: var(--text4);
            margin-bottom: 24px;
        }

        .price-badge {
            position: absolute;
            top: 16px;
            right: 16px;
            background: var(--grad1);
            color: #fff;
            font-size: 9px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 50px;
            letter-spacing: 1px;
        }

        /* ─── Testimonials ─── */
        .testimonials-section {
            background: linear-gradient(135deg, var(--primary-faint) 0%, var(--secondary-faint) 100%);
            border-top: 1px solid var(--border2);
            border-bottom: 1px solid var(--border2);
        }

        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 56px;
        }

        .testimonial-card {
            background: #fff;
            border: 1px solid var(--border2);
            border-radius: var(--radius);
            padding: 32px;
            transition: all .35s;
            position: relative;
            overflow: hidden;
        }

        .testimonial-card::after {
            content: '"';
            position: absolute;
            top: 16px;
            right: 20px;
            font-family: 'Cormorant Garamond', serif;
            font-size: 80px;
            line-height: 1;
            background: var(--grad1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            opacity: .1;
        }

        .testimonial-card:hover {
            border-color: var(--border);
            background: var(--bg3);
            transform: translateY(-6px);
            box-shadow: var(--shadow2);
        }

        .testimonial-stars {
            font-size: 13px;
            margin-bottom: 16px;
            letter-spacing: 3px;
            background: var(--grad1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .testimonial-text {
            font-size: 14px;
            color: var(--text2);
            line-height: 1.8;
            margin-bottom: 24px;
            font-style: italic;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 4;
            -webkit-box-orient: vertical;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .testimonial-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            color: #fff;
            overflow: hidden;
            flex: 0 0 40px;
            border: 1px solid rgba(111, 86, 254, 0.12);
        }

        .testimonial-avatar img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
        }

        .testimonial-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
        }

        .testimonial-role {
            font-size: 12px;
            color: var(--text3);
            margin-top: 2px;
        }

        /* ─── Team ─── */
        .team-grid-home {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 48px;
        }

        .team-card-leader {
            background: #fff;
            border: 1px solid var(--border2);
            border-radius: var(--radius);
            padding: 32px 24px;
            text-align: center;
            transition: all .35s cubic-bezier(0.34, 1.56, 0.64, 1);
            cursor: default;
            position: relative;
            overflow: hidden;
        }

        .team-card-leader::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--grad1);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform .4s;
        }

        .team-card-leader:hover::before {
            transform: scaleX(1);
        }

        .team-card-leader:hover {
            transform: translateY(-8px);
            border-color: var(--border);
            box-shadow: var(--shadow2);
        }

        .team-photo {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            margin: 0 auto 18px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(111, 86, 254, 0.2);
            border: 3px solid rgba(111, 86, 254, 0.2);
        }

        .team-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            display: block;
        }

        .team-photo svg {
            width: 90px;
            height: 90px;
            display: block;
        }

        .team-name {
            font-weight: 700;
            font-size: 16px;
            color: var(--text);
            margin-bottom: 5px;
        }

        .team-role {
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .team-bio {
            font-size: 13px;
            color: var(--text3);
            line-height: 1.7;
            margin-bottom: 14px;
        }

        .team-dept-badge {
            position: absolute;
            top: 16px;
            right: 16px;
            border-radius: 50px;
            padding: 2px 10px;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }

        .team-profile-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 600;
            color: var(--violet);
            background: var(--primary-faint);
            border: 1px solid rgba(111, 86, 254, 0.2);
            border-radius: 20px;
            padding: 4px 13px;
        }

        /* ─── Founder Spotlight ─── */
        .founder-spotlight {
            position: relative;
            margin: 56px auto 0;
            max-width: 920px;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 44px;
            align-items: center;
            background: linear-gradient(135deg, var(--secondary-faint), var(--primary-faint));
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 48px 52px;
            overflow: hidden;
        }

        .founder-spotlight-blob {
            position: absolute;
            top: -90px;
            right: -90px;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(111, 86, 254, 0.18), transparent 70%);
            pointer-events: none;
        }

        .founder-photo-link {
            display: block;
            justify-self: center;
            text-decoration: none;
            position: relative;
            z-index: 1;
        }

        .founder-photo-ring {
            width: 220px;
            height: 220px;
            border-radius: 50%;
            padding: 6px;
            background: var(--grad1);
            box-shadow: 0 18px 44px rgba(111, 86, 254, 0.28);
            transition: transform .35s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
        }

        .founder-photo-link:hover .founder-photo-ring {
            transform: translateY(-6px) scale(1.02);
        }

        .founder-photo-ring img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            display: block;
            border: 4px solid #fff;
        }

        .founder-photo-fallback {
            display: none;
            position: absolute;
            inset: 6px;
            background: linear-gradient(135deg, #6F56FE, #5540D4);
            border-radius: 50%;
            align-items: center;
            justify-content: center;
            font-size: 64px;
            border: 4px solid #fff;
        }

        .founder-content {
            position: relative;
            z-index: 1;
        }

        .founder-badge {
            display: inline-block;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            color: #fff;
            background: var(--grad1);
            padding: 5px 14px;
            border-radius: 50px;
            margin-bottom: 14px;
        }

        .founder-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(30px, 3.5vw, 42px);
            font-weight: 700;
            color: var(--text);
            line-height: 1.1;
            margin-bottom: 14px;
        }

        .founder-bio {
            font-size: 15px;
            color: var(--text2);
            line-height: 1.8;
            margin-bottom: 18px;
        }

        .founder-quote {
            font-family: 'Cormorant Garamond', serif;
            font-size: 19px;
            font-style: italic;
            color: var(--violet);
            line-height: 1.5;
            border-left: 3px solid var(--violet);
            padding-left: 16px;
            margin-bottom: 22px;
        }

        .founder-profile-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            background: var(--grad1);
            border-radius: 50px;
            padding: 11px 24px;
            text-decoration: none;
            box-shadow: 0 8px 22px rgba(111, 86, 254, 0.28);
            transition: transform .25s, box-shadow .25s;
        }

        .founder-profile-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(111, 86, 254, 0.38);
        }

        @media (max-width: 760px) {
            .founder-spotlight {
                grid-template-columns: 1fr;
                gap: 28px;
                padding: 36px 26px;
                text-align: center;
            }

            .founder-photo-ring {
                width: 170px;
                height: 170px;
            }

            .founder-quote {
                text-align: left;
            }
        }

        /* ─── Security ─── */
        .security-strip-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
        }

        .card-feature {
            background: var(--bg3);
            border: 1px solid var(--border2);
            border-radius: var(--radius);
            padding: 24px 20px;
            text-align: center;
            transition: all .3s;
            cursor: default;
        }

        .card-feature:hover {
            background: #fff;
            border-color: var(--border);
            transform: translateY(-4px);
            box-shadow: var(--shadow3);
        }

        .card-feature-icon {
            font-size: 28px;
            margin-bottom: 12px;
        }

        .card-feature-title {
            font-weight: 600;
            color: var(--text);
            font-size: 14px;
            margin-bottom: 8px;
        }

        .card-feature-desc {
            font-size: 12px;
            color: var(--text3);
            line-height: 1.65;
        }

        /* ─── FAQ (matches /web/faqs page) ─── */
        .faq-chips {
            max-width: 1000px;
            margin: 36px auto 0;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
        }

        .faq-chip {
            font-size: 13px;
            font-weight: 600;
            padding: 8px 13px;
            border-radius: 50px;
            border: 1px solid var(--border);
            background: #fff;
            color: var(--text2);
            cursor: pointer;
            transition: all .2s;
            white-space: nowrap;
        }

        .faq-chip:hover {
            border-color: var(--violet);
            color: var(--violet);
        }

        .faq-chip.active {
            background: var(--grad1);
            border-color: transparent;
            color: #fff;
            box-shadow: 0 6px 16px rgba(111, 86, 254, .25);
        }

        .faq-wrap {
            max-width: 820px;
            margin: 56px auto 0;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .faq-item {
            background: #fff;
            border: 1px solid var(--border2);
            border-radius: var(--radius);
            overflow: hidden;
            transition: border-color .2s, box-shadow .2s;
        }

        .faq-item[open] {
            border-color: var(--border);
            box-shadow: var(--shadow3);
        }

        .faq-q {
            list-style: none;
            cursor: pointer;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            font-size: 15px;
            font-weight: 600;
            color: var(--text);
        }

        .faq-q::-webkit-details-marker {
            display: none;
        }

        .faq-icon {
            flex-shrink: 0;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: var(--secondary-faint);
            color: var(--violet);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            transition: transform .25s;
        }

        .faq-item[open] .faq-icon {
            transform: rotate(45deg);
            background: var(--grad1);
            color: #fff;
        }

        .faq-a {
            padding: 0 24px 22px;
            font-size: 14px;
            color: var(--text3);
            line-height: 1.8;
        }

        /* ─── CTA ─── */
        .cta-section {
            padding: 100px 6%;
            text-align: center;
            position: relative;
            overflow: hidden;
            background: #fff;
        }

        .cta-bg {
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 30% 50%, rgba(219, 87, 178, 0.07) 0%, transparent 55%), radial-gradient(ellipse at 70% 50%, rgba(111, 86, 254, 0.07) 0%, transparent 55%);
        }

        .cta-card {
            background: linear-gradient(160deg, rgba(219, 87, 178, 0.06) 0%, rgba(111, 86, 254, 0.06) 100%);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 80px 60px;
            max-width: 860px;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .cta-card::before {
            content: '';
            position: absolute;
            top: -80px;
            right: -60px;
            width: 280px;
            height: 280px;
            background: rgba(111, 86, 254, 0.07);
            border-radius: 50%;
            filter: blur(60px);
        }

        .cta-card::after {
            content: '';
            position: absolute;
            bottom: -60px;
            left: -60px;
            width: 240px;
            height: 240px;
            background: rgba(219, 87, 178, 0.07);
            border-radius: 50%;
            filter: blur(60px);
        }

        .cta-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(2rem, 4vw, 3.2rem);
            font-weight: 600;
            line-height: 1.15;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
            color: var(--text);
        }

        .cta-desc {
            font-size: 16px;
            color: var(--text3);
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
        }

        .cta-actions {
            display: flex;
            gap: 14px;
            justify-content: center;
            flex-wrap: wrap;
            position: relative;
            z-index: 1;
        }

        /* ─── Mobile Nav ─── */
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
            animation: fadeIn .3s ease;
        }

        .mobile-nav.open {
            display: flex;
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
            background-clip: text;
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

        /* ─── Toast ─── */
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
            max-width: 300px;
        }

        /* ─── Scroll Reveal (3D depth — blocks tilt up out of the page) ─── */
        .reveal {
            opacity: 0;
            transform: perspective(1400px) rotateX(14deg) translateY(44px) translateZ(-60px);
            transform-origin: center top;
            transform-style: preserve-3d;
            backface-visibility: hidden;
            transition: opacity .85s cubic-bezier(0.16, 1, 0.3, 1), transform .85s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .reveal.visible {
            opacity: 1;
            transform: perspective(1400px) rotateX(0) translateY(0) translateZ(0);
        }

        .reveal-left {
            opacity: 0;
            transform: perspective(1400px) rotateY(-18deg) translateX(-58px) translateZ(-60px);
            transform-origin: left center;
            transform-style: preserve-3d;
            transition: opacity .85s cubic-bezier(0.16, 1, 0.3, 1), transform .85s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .reveal-left.visible {
            opacity: 1;
            transform: perspective(1400px) rotateY(0) translateX(0) translateZ(0);
        }

        .reveal-right {
            opacity: 0;
            transform: perspective(1400px) rotateY(18deg) translateX(58px) translateZ(-60px);
            transform-origin: right center;
            transform-style: preserve-3d;
            transition: opacity .85s cubic-bezier(0.16, 1, 0.3, 1), transform .85s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .reveal-right.visible {
            opacity: 1;
            transform: perspective(1400px) rotateY(0) translateX(0) translateZ(0);
        }

        .reveal-scale {
            opacity: 0;
            transform: perspective(1400px) rotateX(16deg) scale(0.9) translateY(28px);
            transform-origin: center top;
            transform-style: preserve-3d;
            transition: opacity .8s cubic-bezier(0.16, 1, 0.3, 1), transform .8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .reveal-scale.visible {
            opacity: 1;
            transform: perspective(1400px) rotateX(0) scale(1) translateY(0);
        }

        .stagger-children {
            perspective: 1400px;
        }

        .stagger-children>* {
            opacity: 0;
            transform: rotateX(18deg) translateY(34px) translateZ(-40px);
            transform-origin: center top;
            backface-visibility: hidden;
            transition: opacity .7s cubic-bezier(0.16, 1, 0.3, 1), transform .7s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .stagger-children.visible>* {
            opacity: 1;
            transform: rotateX(0) translateY(0) translateZ(0);
        }

        .stagger-children.visible>*:nth-child(1) {
            transition-delay: .04s;
        }

        .stagger-children.visible>*:nth-child(2) {
            transition-delay: .08s;
        }

        .stagger-children.visible>*:nth-child(3) {
            transition-delay: .12s;
        }

        .stagger-children.visible>*:nth-child(4) {
            transition-delay: .16s;
        }

        .stagger-children.visible>*:nth-child(5) {
            transition-delay: .20s;
        }

        .stagger-children.visible>*:nth-child(6) {
            transition-delay: .24s;
        }

        .stagger-children.visible>*:nth-child(7) {
            transition-delay: .28s;
        }

        .stagger-children.visible>*:nth-child(8) {
            transition-delay: .32s;
        }

        .stagger-children.visible>*:nth-child(9) {
            transition-delay: .36s;
        }

        .stagger-children.visible>*:nth-child(10) {
            transition-delay: .40s;
        }

        .stagger-children.visible>*:nth-child(11) {
            transition-delay: .44s;
        }

        .stagger-children.visible>*:nth-child(12) {
            transition-delay: .48s;
        }

        /* ─── Footer ─── */
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
            font-size: 15px;
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

        .footer-contact-item {
            display: flex;
            gap: 8px;
            align-items: flex-start;
            color: var(--text3);
            font-size: 12px;
            margin-bottom: 10px;
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

        /* ─── Responsive ─── */
        @media (max-width: 1100px) {
            .hero-inner {
                padding: 28px 4% 40px;
                /* excess padding fix */
                grid-template-columns: 1fr;
            }

            .hero-section {
                min-height: auto;
            }

            .hero-blob-1,
            .hero-blob-2 {
                width: 250px;
                height: 250px;
            }

            .hero-visual {
                display: none;
            }

            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .footer-grid {
                grid-template-columns: 1fr 1fr;
                gap: 36px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .illus-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .hiw-grid {
                grid-template-columns: 1fr;
            }

            .testimonials-grid {
                grid-template-columns: 1fr 1fr;
            }

            .modules-mini-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .role-cards-grid {
                grid-template-columns: 1fr;
            }

            .security-strip-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .team-grid-home {
                grid-template-columns: repeat(2, 1fr);
            }

            .pricing-snap-grid {
                grid-template-columns: 1fr;
                max-width: 460px;
            }

            .apps-download-grid {
                grid-template-columns: 1fr;
                max-width: 400px;
            }
        }

        @media (max-width: 768px) {

            .nav-links,
            .nav-cta {
                display: none;
            }

            .hamburger {
                display: flex;
            }

            .section {
                padding: 64px 5%;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .footer-grid {
                grid-template-columns: 1fr;
            }

            .cta-card {
                padding: 40px 24px;
            }

            .testimonials-grid {
                grid-template-columns: 1fr;
            }

            .modules-mini-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .security-strip-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .team-grid-home {
                grid-template-columns: repeat(2, 1fr);
            }

            .stats-section {
                padding: 48px 5%;
            }

            .stat-card {
                padding: 28px 14px;
            }

            .stat-num {
                font-size: 36px;
            }

            .hiw-card {
                padding: 28px 24px;
            }

            .hiw-step {
                font-size: 56px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .cta-section {
                padding: 60px 4%;
            }

            .section-title {
                font-size: clamp(1.9rem, 7vw, 2.8rem);
            }
        }

        @media (max-width: 480px) {
            .section {
                padding: 52px 4%;
            }

            .cta-card {
                padding: 32px 18px;
            }

            .btn-xl {
                width: 100%;
                justify-content: center;
                text-align: center;
                display: flex;
                align-items: center;
            }

            .hero-actions {
                flex-direction: column;
                width: 100%;
            }

            .hero-actions .btn {
                width: 100%;
                text-align: center;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .footer-grid {
                gap: 24px;
            }

            .security-strip-grid {
                grid-template-columns: 1fr;
            }

            .team-grid-home {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
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

    <!-- ═══════════════════════════════════════════
     NAVBAR
═══════════════════════════════════════════ -->


    @include('components.website.header')



    <!-- ═══════════════════════════════════════════
     HERO SECTION
═══════════════════════════════════════════ -->
    <section class="hero-section">
        <div class="hero-blob-1"></div>
        <div class="hero-blob-2"></div>

        <div class="hero-inner">
            <!-- LEFT: Text -->
            <div>
                <div class="hero-badge">
                    <div class="hero-badge-dot"></div>
                    India's #1 School Management Platform
                </div>

                <h1 class="hero-title">
                    Engaging, Accessible &amp;
                    <span class="highlight">Affordable</span>
                    Learning Management System.
                </h1>

                <p class="hero-desc">
                    SUPERLMS is a complete academic ecosystem for schools and institutions.
                    Smart attendance, automated timetables, fee management, and real-time progress
                    tracking — all in one powerful platform trusted by many schools across India.
                </p>

                <div class="hero-actions">
                    <a href="{{ url('web/demo') }}" class="btn btn-primary btn-xl">Request Free Demo</a>
                    <a href="{{ url('web/features') }}" class="btn btn-outline btn-xl">Explore Features →</a>
                </div>

                <div class="hero-stats">
                    <div class="hero-stat">
                        <div class="hero-stat-num" id="heroSchools">many</div>
                        <div class="hero-stat-label">Schools</div>
                    </div>
                    <div class="hero-stat-divider"></div>
                    <div class="hero-stat">
                        <div class="hero-stat-num" id="heroStudents">100K+</div>
                        <div class="hero-stat-label">Students</div>
                    </div>
                    <div class="hero-stat-divider"></div>
                    <div class="hero-stat">
                        <div class="hero-stat-num">50+</div>
                        <div class="hero-stat-label">Modules</div>
                    </div>
                    <div class="hero-stat-divider"></div>
                    <div class="hero-stat">
                        <div class="hero-stat-num" id="heroRating">4.9★</div>
                        <div class="hero-stat-label">Rating</div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Animated live dashboard illustration -->
            <div class="hero-visual">
                <div class="hero-dash-glow"></div>

                <!-- Floating badges -->
                <div class="hero-float-badge hero-float-1">
                    <div class="hfb-icon" style="background:rgba(22,163,74,.12);color:#16A34A;">✓</div>
                    <div>
                        <div>Attendance Synced</div>
                        <div class="hfb-sub">Just now</div>
                    </div>
                </div>
                <div class="hero-float-badge hero-float-2">
                    <div class="hfb-icon" style="background:rgba(219,87,178,.12);color:#DB57B2;">₹</div>
                    <div>
                        <div>Fees Collected</div>
                        <div class="hfb-sub">+18% this month</div>
                    </div>
                </div>

                <div class="hero-dash">
                    <!-- Top bar -->
                    <div class="dash-top-bar">
                        <div class="dash-top-logo">
                            <div class="dash-top-icon">🎓</div>
                            <div>
                                <div class="dash-top-title">SUPERLMS Dashboard</div>
                                <div class="dash-top-sub">Live school overview</div>
                            </div>
                        </div>
                        <div class="dash-top-dots">
                            <div class="dash-dot" style="background:rgba(255,255,255,.45);"></div>
                            <div class="dash-dot" style="background:rgba(255,255,255,.45);"></div>
                            <div class="dash-dot" style="background:rgba(255,255,255,.75);"></div>
                        </div>
                    </div>

                    <!-- KPI row (driven by live stats) -->
                    <div class="dash-kpi-row">
                        <div class="dash-kpi-cell">
                            <div class="dash-kpi-icon">🏫</div>
                            <div class="dash-kpi-num" id="dashSchools">many</div>
                            <div class="dash-kpi-label">Schools</div>
                        </div>
                        <div class="dash-kpi-cell">
                            <div class="dash-kpi-icon">🎓</div>
                            <div class="dash-kpi-num" id="dashStudents">100K+</div>
                            <div class="dash-kpi-label">Students</div>
                        </div>
                        <div class="dash-kpi-cell">
                            <div class="dash-kpi-icon">🧩</div>
                            <div class="dash-kpi-num">50+</div>
                            <div class="dash-kpi-label">Modules</div>
                        </div>
                        <div class="dash-kpi-cell">
                            <div class="dash-kpi-icon">⭐</div>
                            <div class="dash-kpi-num" id="dashRating">4.9★</div>
                            <div class="dash-kpi-label">Rating</div>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="dash-body">
                        <!-- Attendance chart -->
                        <div>
                            <div style="display:flex;align-items:center;justify-content:space-between;">
                                <div class="dash-section-title">Weekly Attendance</div>
                                <div class="dash-week-tag">This week</div>
                            </div>
                            <div class="dash-chart-row">
                                <div class="dash-bar-wrap">
                                    <div class="dash-bar" style="height:62%;background:var(--grad1);animation-delay:.05s;"></div>
                                    <div class="dash-bar-label">M</div>
                                </div>
                                <div class="dash-bar-wrap">
                                    <div class="dash-bar" style="height:80%;background:var(--grad1);animation-delay:.15s;"></div>
                                    <div class="dash-bar-label">T</div>
                                </div>
                                <div class="dash-bar-wrap">
                                    <div class="dash-bar" style="height:55%;background:var(--grad1);animation-delay:.25s;"></div>
                                    <div class="dash-bar-label">W</div>
                                </div>
                                <div class="dash-bar-wrap">
                                    <div class="dash-bar" style="height:92%;background:var(--grad1);animation-delay:.35s;"></div>
                                    <div class="dash-bar-label">T</div>
                                </div>
                                <div class="dash-bar-wrap">
                                    <div class="dash-bar" style="height:74%;background:var(--grad1);animation-delay:.45s;"></div>
                                    <div class="dash-bar-label">F</div>
                                </div>
                                <div class="dash-bar-wrap">
                                    <div class="dash-bar" style="height:48%;background:var(--grad1);animation-delay:.55s;"></div>
                                    <div class="dash-bar-label">S</div>
                                </div>
                            </div>
                        </div>

                        <!-- Activity feed -->
                        <div>
                            <div class="dash-activity-title">Recent Activity</div>
                            <div class="dash-activity-row">
                                <div class="dash-activity-icon" style="background:rgba(111,86,254,.12);">📝</div>
                                <div class="dash-activity-text">Class 8-A attendance marked</div>
                                <div class="dash-activity-time">2m</div>
                            </div>
                            <div class="dash-activity-row">
                                <div class="dash-activity-icon" style="background:rgba(219,87,178,.12);">💳</div>
                                <div class="dash-activity-text">Fee receipt #4821 generated</div>
                                <div class="dash-activity-time">9m</div>
                            </div>
                            <div class="dash-activity-row">
                                <div class="dash-activity-icon" style="background:rgba(22,163,74,.12);">📊</div>
                                <div class="dash-activity-text">Term-1 results published</div>
                                <div class="dash-activity-time">1h</div>
                            </div>
                        </div>

                        <!-- Module pills -->
                        <div class="dash-module-pills">
                            <div class="dash-module-pill">📚 Syllabus</div>
                            <div class="dash-module-pill">🗓️ Timetable</div>
                            <div class="dash-module-pill">🚌 Transport</div>
                            <div class="dash-module-pill">📋 Exams</div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="dash-footer">
                        <div class="dash-footer-sync">Synced moments ago</div>
                        <div class="dash-footer-status">
                            <div class="dash-status-dot"></div> All systems live
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- ═══════════════════════════════════════════
     MARQUEE — Partner Schools
═══════════════════════════════════════════ -->
    <div class="marquee-section">
        <div class="marquee-viewport">
            <div class="marquee-track" id="marqueeTrack">
                <!-- JS populates school logos dynamically -->
            </div>
        </div>
    </div>


    <!-- ═══════════════════════════════════════════
     HOW IT WORKS
═══════════════════════════════════════════ -->
    <section class="section" style="background:#fff;">
        <div style="max-width:1200px;margin:0 auto;">
            <div class="section-center reveal">
                <div class="section-tag tag-violet">Process</div>
                <h2 class="section-title">How It <span class="gradient-text">Works</span></h2>
                <p class="section-subtitle" style="max-width:780px; margin:18px auto 0;">Getting your school onto
                    SUPERLMS is easier than you think. You share your school's data once, our team sets everything up
                    for you, and every student, teacher and administrator gets their own secure account — so classes,
                    attendance, fees and homework start running online from day one. No technical knowledge needed at
                    your end; here's how a school goes live in 3 simple steps.</p>
            </div>

            <div class="hiw-grid stagger-children">
                <div class="hiw-card">
                    <div class="hiw-step">01</div>
                    <div class="hiw-icon" style="background:rgba(111,86,254,.1);border:1px solid #6F56FE;">🏫</div>
                    <div class="hiw-title">Integrate with School</div>
                    <div class="hiw-desc">Connect your school data, configure departments, classes, and subjects. Our
                        team handles the entire migration and setup within 3–5 business days at no extra cost.</div>
                </div>
                <div class="hiw-card">
                    <div class="hiw-step">02</div>
                    <div class="hiw-icon" style="background:rgba(219,87,178,.1);border:1px solid #DB57B2;">🔐</div>
                    <div class="hiw-title">Get LMS Account</div>
                    <div class="hiw-desc">Every student, teacher, and administrator gets a personalized secure account
                        with role-based access, tailored views, and an intuitive onboarding workflow.</div>
                </div>
                <div class="hiw-card">
                    <div class="hiw-step">03</div>
                    <div class="hiw-icon"
                        style="background:linear-gradient(135deg,rgba(219,87,178,.1),rgba(111,86,254,.1));border:1px solid transparent;">
                        🎯</div>
                    <div class="hiw-title">Start to Learn</div>
                    <div class="hiw-desc">Begin learning instantly with engaging multimedia content, real-time progress
                        tracking, interactive assignments, online quizzes, and expert teacher guidance.</div>
                </div>
            </div>
        </div>
    </section>


    <!-- ═══════════════════════════════════════════
     STATS
═══════════════════════════════════════════ -->
    <div class="stats-section">
        <div class="stats-grid stagger-children">
            <div class="stat-card">
                <div class="stat-icon">🏫</div>
                <div class="stat-num" id="statSchools">many</div>
                <div class="stat-label">Partnered Schools</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👨‍🏫</div>
                <div class="stat-num" id="statTeachers">500+</div>
                <div class="stat-label">Subject Experts</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🎓</div>
                <div class="stat-num" id="statStudents">100K+</div>
                <div class="stat-label">Online Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-num" id="statRating">4.9/5</div>
                <div class="stat-label">Average Rating</div>
            </div>
        </div>
    </div>


    <!-- ═══════════════════════════════════════════
     DATA-DRIVEN ANALYTICS
═══════════════════════════════════════════ -->
    <section class="section" style="background:#fff;">
        <div class="illus-grid" style="max-width:1100px;margin:0 auto;">
            <!-- Left: Text -->
            <div class="reveal-left">
                <div class="section-tag tag-pink">Live Insights</div>
                <h2 class="section-title">Data-Driven <span class="gradient-text">Decisions</span></h2>
                <p style="color:var(--text3);line-height:1.8;margin-bottom:28px;font-size:15px;">Get real-time
                    analytics on attendance, academic performance, fee collection, and more. Administrators see the full
                    picture; teachers see their class; parents see their child — all with a single login.</p>
                <div>
                    <div class="analytics-bullet">
                        <div class="analytics-bullet-dot"></div><span class="analytics-bullet-text">Attendance
                            heatmaps &amp; trend analysis</span>
                    </div>
                    <div class="analytics-bullet">
                        <div class="analytics-bullet-dot"></div><span class="analytics-bullet-text">Subject-wise
                            performance &amp; rank comparisons</span>
                    </div>
                    <div class="analytics-bullet">
                        <div class="analytics-bullet-dot"></div><span class="analytics-bullet-text">Fee collection
                            dashboards &amp; monthly forecasting</span>
                    </div>
                    <div class="analytics-bullet">
                        <div class="analytics-bullet-dot"></div><span class="analytics-bullet-text">Automated report
                            card generation in one click</span>
                    </div>
                </div>
            </div>
            <!-- Right: SVG Illustration -->
            <div class="reveal-right" style="display:flex;justify-content:center;align-items:center;">
                <svg viewBox="0 0 300 240" fill="none" xmlns="http://www.w3.org/2000/svg"
                    style="width:100%;max-width:300px;animation:float 5s ease-in-out infinite;">
                    <rect x="20" y="20" width="260" height="200" rx="16" fill="white"
                        stroke="#EDE8FF" stroke-width="1.5" />
                    <rect x="36" y="36" width="228" height="36" rx="8" fill="#F0EDFF" />
                    <text x="52" y="59" font-size="13" fill="#6F56FE" font-weight="600"
                        font-family="DM Sans,sans-serif">Analytics Dashboard</text>
                    <circle cx="247" cy="54" r="8" fill="url(#ag1)" opacity="0.8" />
                    <rect x="36" y="84" width="100" height="60" rx="8" fill="#F9EDF5" />
                    <text x="86" y="107" text-anchor="middle" font-size="20" font-weight="700" fill="#DB57B2"
                        font-family="Cormorant Garamond,serif">89%</text>
                    <text x="86" y="122" text-anchor="middle" font-size="9" fill="#B083A0"
                        font-family="DM Sans,sans-serif">Attendance</text>
                    <rect x="36" y="84" width="100" height="60" rx="8" stroke="#DB57B2"
                        stroke-width="1" opacity="0.3" />
                    <rect x="148" y="84" width="116" height="60" rx="8" fill="#F0EDFF" />
                    <rect x="148" y="84" width="116" height="60" rx="8" stroke="#6F56FE"
                        stroke-width="1" opacity="0.3" />
                    <rect x="160" y="100" width="18" height="32" rx="4" fill="#6F56FE"
                        opacity="0.25" />
                    <rect x="182" y="110" width="18" height="22" rx="4" fill="#6F56FE"
                        opacity="0.4" />
                    <rect x="204" y="96" width="18" height="36" rx="4" fill="#6F56FE"
                        opacity="0.6" />
                    <rect x="226" y="104" width="18" height="28" rx="4" fill="#6F56FE"
                        opacity="0.85" />
                    <text x="206" y="157" text-anchor="middle" font-size="9" fill="#7A6EA0"
                        font-family="DM Sans,sans-serif">Monthly Progress</text>
                    <rect x="36" y="158" width="228" height="8" rx="4" fill="#EDE8FF" />
                    <rect x="36" y="158" width="160" height="8" rx="4" fill="url(#ag1)" />
                    <text x="36" y="182" font-size="9" fill="#7A6EA0" font-family="DM Sans,sans-serif">Goal: 95%
                        average this semester</text>
                    <text x="248" y="182" text-anchor="end" font-size="9" fill="#6F56FE" font-weight="600"
                        font-family="DM Sans,sans-serif">78%</text>
                    <defs>
                        <linearGradient id="ag1" x1="0" y1="0" x2="1" y2="0">
                            <stop stop-color="#DB57B2" />
                            <stop offset="1" stop-color="#6F56FE" />
                        </linearGradient>
                    </defs>
                </svg>
            </div>
        </div>
    </section>


    <!-- ═══════════════════════════════════════════
     FEATURES
═══════════════════════════════════════════ -->
    <section class="section" style="background:var(--bg3);">
        <div style="max-width:1280px;margin:0 auto;">
            <div class="section-center reveal">
                <div class="section-tag tag-dual">Features</div>
                <h2 class="section-title">Everything You Need to <span class="gradient-text">Succeed</span></h2>
                <p class="section-subtitle" style="max-width:780px; margin:0 auto;">A complete suite of 50+ modules
                    designed to streamline learning and administration for every role — admissions, attendance, fees,
                    exams, homework, timetables, transport, payroll and much more. One login for administrators,
                    teachers, students and parents, so your whole school runs from a single platform instead of five
                    different apps.</p>
            </div>

            <div class="features-grid stagger-children">
                <div class="feature-card">
                    <div class="feature-icon-wrap" style="background:rgba(111,86,254,.1);">📊</div>
                    <div class="feature-title">Smart Analytics</div>
                    <div class="feature-desc">Real-time performance tracking, subject-wise trend analysis, attendance
                        heatmaps and detailed admin, teacher &amp; student reports with exportable dashboards.</div>
                    <div class="feature-tag tag-v">Analytics</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrap" style="background:rgba(219,87,178,.1);">✅</div>
                    <div class="feature-title">Attendance Management</div>
                    <div class="feature-desc">Mark attendance via mobile, QR code or biometric. Instant SMS &amp;
                        WhatsApp alerts sent automatically to parents the moment a student is absent.</div>
                    <div class="feature-tag tag-p">Automation</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrap" style="background:rgba(111,86,254,.08);">🗓️</div>
                    <div class="feature-title">Timetable Scheduling</div>
                    <div class="feature-desc">AI-assisted, conflict-free timetable generation with drag-and-drop
                        editing. Handles substitute teacher assignments and distributes schedules instantly.</div>
                    <div class="feature-tag tag-v">Scheduling</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrap" style="background:rgba(219,87,178,.08);">💰</div>
                    <div class="feature-title">Fee Management</div>
                    <div class="feature-desc">Online &amp; offline fee collection, late fine automation, scholarship
                        management, instant receipt generation and comprehensive financial reporting.</div>
                    <div class="feature-tag tag-p">Finance</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrap" style="background:rgba(111,86,254,.1);">📝</div>
                    <div class="feature-title">Assignments &amp; Quizzes</div>
                    <div class="feature-desc">Create, distribute, and auto-grade assignments and online tests (MCQ,
                        true/false, short answers). Instant results, leaderboards, and performance analytics.</div>
                    <div class="feature-tag tag-v">Assessment</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrap" style="background:rgba(219,87,178,.1);">🏫</div>
                    <div class="feature-title">School Administration</div>
                    <div class="feature-desc">Complete school profile, student &amp; staff management, ID card/admit
                        card bulk generation, library, payroll, transport routes and rules &amp; regulations — all in
                        one place.</div>
                    <div class="feature-tag tag-p">Administration</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrap" style="background:rgba(111,86,254,.08);">📚</div>
                    <div class="feature-title">Digital Content &amp; Library</div>
                    <div class="feature-desc">Upload chapters, notes, videos and PDFs subject-wise. Students access a
                        rich digital library anytime, anywhere, on any device — online or offline.</div>
                    <div class="feature-tag tag-v">Learning</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrap" style="background:rgba(219,87,178,.08);">💬</div>
                    <div class="feature-title">Communication &amp; Alerts</div>
                    <div class="feature-desc">Keep parents, teachers and students connected with announcements, push
                        notifications, homework alerts and instant SMS &amp; WhatsApp updates.</div>
                    <div class="feature-tag tag-p">Engagement</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrap" style="background:rgba(111,86,254,.1);">🎓</div>
                    <div class="feature-title">Report Cards &amp; Certificates</div>
                    <div class="feature-desc">Auto-generated report cards, term results, transfer &amp; bonafide
                        certificates and ID cards — designed, branded and ready to print in one click.</div>
                    <div class="feature-tag tag-v">Records</div>
                </div>
            </div>

            <!-- 44+ more modules note -->
            <div class="reveal" style="margin-top:40px;">
                <p style="text-align:center;font-size:13px;color:var(--text3);font-weight:500;">And
                    44+ more modules — fully included in every plan</p>
            </div>

            <div style="text-align:center;margin-top:36px;">
                <a href="{{ url('web/features') }}" class="btn btn-outline btn-lg">View All 50+ Features →</a>
            </div>
        </div>
    </section>


    <!-- ═══════════════════════════════════════════
     WHO IT'S FOR
═══════════════════════════════════════════ -->
    <section class="section" style="background:#fff;">
        <div style="max-width:1100px;margin:0 auto;">
            <div class="section-center reveal">
                <div class="section-tag tag-violet">Built for Everyone</div>
                <h2 class="section-title">One Platform, <span class="gradient-text">Every Role</span></h2>
                <p class="section-subtitle" style="max-width:780px;">SUPERLMS is built to serve the unique needs of every stakeholder in your
                    school ecosystem — from administrators and teachers to students, parents and the accounts &amp;
                    examination teams. Each role gets a dedicated, role-based dashboard with exactly the tools they need,
                    so everyone works from a single source of truth without juggling spreadsheets, paperwork or
                    disconnected apps.</p>
            </div>

            <div class="role-cards-grid stagger-children">
                <!-- Administrators -->
                <div class="role-card" style="background:var(--secondary-faint);border:1px solid var(--border);">
                    <div class="role-card-title">🏛️ For Administrators</div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Complete school dashboard with live KPIs
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Student &amp; teacher record management
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Fee collection, payroll &amp; financial reports
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Timetable generation &amp; substitute management
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Bulk ID card, admit card &amp; report card generation
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Transport route &amp; library management
                    </div>
                </div>
                <!-- Teachers -->
                <div class="role-card" style="background:var(--primary-faint);border:1px solid var(--border-pink);">
                    <div class="role-card-title">👩‍🏫 For Teachers</div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Mark attendance via mobile in seconds
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Upload notes, videos &amp; study materials
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Create quizzes, assignments &amp; grade them
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Track student performance per subject
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Communicate with parents via announcements
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Manage class-wise seating plans for exams
                    </div>
                </div>
                <!-- Students -->
                <div class="role-card" style="background:var(--secondary-faint);border:1px solid var(--border);">
                    <div class="role-card-title">🎓 For Students</div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Access subject notes, books &amp; content anytime
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>View assignments, deadlines &amp; submit digitally
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Attempt online quizzes with instant results
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Check own attendance &amp; academic performance
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Download admit cards &amp; report cards
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Receive fee receipts &amp; timetable updates
                    </div>
                </div>
                <!-- Parents -->
                <div class="role-card" style="background:var(--primary-faint);border:1px solid var(--border-pink);">
                    <div class="role-card-title">👨‍👩‍👧 For Parents</div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Real-time child attendance notifications (SMS/WhatsApp)
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Track academic progress &amp; grade trends
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Pay school fees online &amp; download receipts
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>View homework, test scores &amp; report cards
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Communicate directly with teachers
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Receive school announcements instantly
                    </div>
                </div>
                <!-- Accounts -->
                <div class="role-card" style="background:var(--secondary-faint);border:1px solid var(--border);">
                    <div class="role-card-title">🧾 For Accounts</div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Automated fee invoicing, dues tracking &amp; online collection
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Payroll, salary slips &amp; staff expense management
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Income, expense &amp; fee ledgers with financial reports
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Online payment reconciliation &amp; instant receipts
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Export GST-ready accounting &amp; audit data
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Real-time dues, defaulter &amp; collection dashboards
                    </div>
                </div>
                <!-- Exams Management -->
                <div class="role-card" style="background:var(--primary-faint);border:1px solid var(--border-pink);">
                    <div class="role-card-title">📝 For Exams Management</div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Build exam schedules, datesheets &amp; seating arrangements
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Configure grading schemes &amp; marks entry workflows
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Automated result processing &amp; rank calculation
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Auto-generate admit cards, marksheets &amp; report cards in bulk
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Subject &amp; class-wise performance analysis
                    </div>
                    <div class="role-point">
                        <div class="role-point-check">✓</div>Publish results instantly to students &amp; parents
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- ═══════════════════════════════════════════
     THREE APPS
═══════════════════════════════════════════ -->
    <section class="section"
        style="background:var(--bg3);border-top:1px solid var(--border2);border-bottom:1px solid var(--border2);">
        <div style="max-width:1100px;margin:0 auto;">
            <div class="section-center reveal">
                <div class="section-tag tag-dual">Available On All Platforms</div>
                <h2 class="section-title">One Company, <span class="gradient-text">Three Platforms</span></h2>
                <p class="section-subtitle">One company, three powerful platforms — Edyone, SuperLMS, and Super Safe
                    — built to keep your entire school ecosystem connected. Access everything from any device, anytime,
                    anywhere.</p>
            </div>

            <div class="apps-download-grid stagger-children">
                <!-- SuperLMS App -->
                <div class="app-card">
                    <div class="app-badge" style="background:rgba(34,197,94,0.12);color:#15803d;">🤖 Android</div>
                    <div class="app-card-icon"
                        style="background:linear-gradient(135deg,rgba(34,197,94,0.12),rgba(34,197,94,0.05));">
                        <img src="{{ asset('admin-image/c8cd2e7a3c40476d15ae54f52a8565b83bfc20b3.png') }}"
                            alt="Logo" class="w-12 h-12 object-contain mb-2" style="width:48px;height:48px;object-fit:contain;">
                    </div>
                    <div class="app-card-name">Edyone</div>
                    <div class="app-card-desc">Edyone is a comprehensive edtech platform offering academic and
                        competitive exam courses, interactive lessons, practice tests, performance tracking, and smart
                        learning tools for students.</div>
                    <a href="https://superlms.in" target="_blank" rel="noopener noreferrer" class="app-dl-btn app-dl-btn-primary">
                        <span style="font-size:16px;">▶</span> Coming Soon
                    </a>
                </div>

                <!-- SuperLMS App -->
                <div class="app-card" style="border:2px solid var(--border);box-shadow:var(--shadow);">
                    <div
                        style="position:absolute;top:16px;right:16px;background:var(--grad1);color:#fff;font-size:9px;font-weight:700;padding:3px 10px;border-radius:50px;letter-spacing:1px;">
                        DOWNLOAD</div>
                    <div class="app-badge" style="background:rgba(111,86,254,0.1);color:var(--violet);">🤖 Android
                    </div>
                    <div class="app-card-icon"
                        style="background:linear-gradient(135deg,rgba(111,86,254,0.12),rgba(219,87,178,0.08));">
                        <img src="{{ asset('website-image/Group 11525.png') }}" alt="Logo"
                            class="w-12 h-12 object-contain mb-2" style="width:48px;height:48px;object-fit:contain;">
                    </div>
                    <div class="app-card-name">SuperLMS</div>
                    <div class="app-card-desc">SuperLMS is a complete school management system enabling attendance
                        tracking, fee management, timetables, academic reports, and seamless communication between
                        administrators, teachers, students, and parents.</div>
                    <a href="https://play.google.com/store/apps/details?id=com.edyoneapp&pcampaignid=web_share"
                        target="_blank" rel="noopener noreferrer" class="app-dl-btn app-dl-btn-primary">
                        <span style="font-size:16px;">⬇</span> Get it on Google Play
                    </a>
                    <div style="display:flex;gap:8px;justify-content:center;font-size:11px;color:var(--text3);">
                        <span>⭐ 4.9</span><span style="color:var(--border);">·</span><span>5K+ Downloads</span>
                    </div>
                </div>

                <!-- SuperLMS Safe App -->
                <div class="app-card">
                    <div class="app-badge" style="background:rgba(219,87,178,0.1);color:var(--pink-dark);">🤖 Android
                    </div>
                    <div class="app-card-icon"
                        style="background:linear-gradient(135deg,rgba(219,87,178,0.12),rgba(219,87,178,0.05));">
                        <img src="{{ asset('admin-image/Group 33079.png') }}" alt="Logo"
                            class="w-12 h-12 object-contain mb-2" style="width:48px;height:48px;object-fit:contain;">
                    </div>
                    <div class="app-card-name">Super Safe</div>
                    <div class="app-card-desc">Super Safe is a powerful parental control app that monitors screen
                        time, filters inappropriate content, tracks online activity, and helps parents ensure their
                        children's digital safety.</div>
                    <a href="https://superlmssafe.in" target="_blank" rel="noopener noreferrer"
                        class="app-dl-btn app-dl-btn-primary">
                        <span style="font-size:16px;">🚀</span> Coming Soon.
                    </a>
                </div>
            </div>
        </div>
    </section>


    <!-- ═══════════════════════════════════════════
     PRICING SNAPSHOT
═══════════════════════════════════════════ -->
    <section class="section" style="background:#fff;border-top:1px solid var(--border2);">
        <div style="max-width:900px;margin:0 auto;">
            <div class="section-center reveal">
                <div class="section-tag tag-pink">Pricing</div>
                <h2 class="section-title">Simple, <span class="gradient-text">Transparent</span> Pricing</h2>
                <p class="section-subtitle" style="max-width:860px;margin:0 auto;">One simple plan that unlocks all 50+ modules — no per-feature add-ons, no
                    setup charges and absolutely no hidden fees. You pay just ₹250 per user a year, billed annually, with
                    free onboarding, data migration and support included. Need something bigger? Our Enterprise plan is
                    custom-built for multi-campus groups with white-label branding and API access — pricing that finally
                    makes sense for every school, big or small.</p>
            </div>

            <div class="pricing-snap-grid stagger-children">
                <!-- School Plan -->
                <div class="home-price-school">
                    <div class="price-badge">BEST VALUE</div>
                    <div class="price-plan-label"
                        style="background:var(--grad1);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">
                        School Plan</div>
                    <div class="price-amount">₹20<span class="price-amount-cents">.83</span></div>
                    <div class="price-sub">per month/user <span style="color:var(--violet);font-weight:600;">(billed
                            annually)</span></div>
                    <div class="price-note">= ₹250/year · All 50+ modules</div>
                    <a href="{{ url('web/pricing') }}" class="btn btn-primary"
                        style="width:100%;display:block;text-align:center;">See Full Plan →</a>
                </div>
                <!-- Enterprise -->
                <div class="home-price-enterprise">
                    <div class="price-plan-label"
                        style="background:var(--grad-pink);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">
                        Enterprise</div>
                    <div
                        style="font-family:'Cormorant Garamond',serif;font-size:38px;font-weight:700;color:var(--text);line-height:1;margin-bottom:2px;">
                        Custom</div>
                    <div class="price-sub">tailored for large institutions</div>
                    <div class="price-note">Multi-campus · White-label · API</div>
                    <a href="{{ url('web/contact') }}" class="btn btn-outline-pink"
                        style="width:100%;display:block;text-align:center;">Contact Sales →</a>
                </div>
            </div>

            <div style="text-align:center;margin-top:56px;">
                <a href="{{ url('web/pricing') }}" class="btn btn-outline btn-lg">View Full Pricing &amp; All Modules
                    →</a>
            </div>
        </div>
    </section>


    <!-- ═══════════════════════════════════════════
     TESTIMONIALS
═══════════════════════════════════════════ -->
    <section class="section testimonials-section">
        <div style="max-width:1200px;margin:0 auto;">
            <div class="section-center reveal">
                <div class="section-tag tag-pink">Testimonials</div>
                <h2 class="section-title">Why Schools <span class="gradient-text">Love</span> SUPERLMS</h2>
                <p class="section-subtitle" style="max-width:780px;">From small private schools to large multi-campus institutions, principals,
                    teachers and parents trust SUPERLMS to run their day-to-day work. Here's what they have to say about
                    saving hours of paperwork, faster fee collection and happier classrooms.</p>
            </div>

            <!-- Slider wrapper -->
            <div style="position:relative;margin-top:52px;">
                <div id="testimonialsSlider" style="overflow:hidden;">
                    <div id="testimonialsTrack"
                        style="display:flex;transition:transform 0.45s cubic-bezier(.4,0,.2,1);">
                        <!-- JS populated -->
                    </div>
                </div>

                <!-- Prev / Next buttons -->
                <button id="tPrev" onclick="testimonialSlide(-1)" aria-label="Previous"
                    style="position:absolute;top:50%;left:-18px;transform:translateY(-50%);width:42px;height:42px;border-radius:50%;border:1.5px solid var(--border);background:#fff;box-shadow:var(--shadow3);cursor:pointer;display:flex;align-items:center;justify-content:center;z-index:10;font-size:18px;color:var(--violet);transition:box-shadow .2s;">&#8249;</button>
                <button id="tNext" onclick="testimonialSlide(1)" aria-label="Next"
                    style="position:absolute;top:50%;right:-18px;transform:translateY(-50%);width:42px;height:42px;border-radius:50%;border:1.5px solid var(--border);background:#fff;box-shadow:var(--shadow3);cursor:pointer;display:flex;align-items:center;justify-content:center;z-index:10;font-size:18px;color:var(--violet);transition:box-shadow .2s;">&#8250;</button>
            </div>

            <!-- Dots -->
            <div id="testimonialDots" style="display:flex;justify-content:center;gap:8px;margin-top:28px;"></div>
        </div>
    </section>


    <!-- ═══════════════════════════════════════════
     TEAM
═══════════════════════════════════════════ -->
    <section class="section" style="background:#fff;">
        <div style="max-width:1060px;margin:0 auto;">
            <div class="section-center reveal">
                <div class="section-tag tag-violet">Our Team</div>
                <h2 class="section-title">Meet the <span class="gradient-text">Minds Behind</span> SUPERLMS</h2>
                <p class="section-subtitle" style="max-width:720px;">Founded by Annant Dagur with one mission — to put
                    quality, affordable school-management technology within reach of every institution in India, from
                    big city campuses to small-town classrooms.</p>
            </div>

            <!-- Founder Spotlight (single, wide feature card) -->
            <div class="founder-spotlight reveal">
                <div class="founder-spotlight-blob"></div>
                <a href="https://www.instagram.com/annantdagur?igsh=OTMyZnIzaGR2aDVs" target="_blank"
                    rel="noopener noreferrer" class="founder-photo-link">
                    <div class="founder-photo-ring">
                        <img src="{{ asset('website-image/annant.jpg') }}"
                            alt="Annant Dagur"
                            onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                        <div class="founder-photo-fallback">👨‍💼</div>
                    </div>
                </a>
                <div class="founder-content">
                    <span class="founder-badge">Founder &amp; CEO</span>
                    <h3 class="founder-name">Annant Dagur</h3>
                    <p class="founder-bio">The visionary behind SUPERLMS. Annant started SUPERLMS with one mission — to
                        make quality, affordable school-management technology accessible to every institution in India,
                        from large city schools to small towns. He works closely with educators on the ground, turning
                        their everyday challenges into simple, powerful features that thousands of students, teachers and
                        parents now rely on.</p>
                    <div class="founder-quote">“Great technology should empower every teacher and reach every child —
                        not just the privileged few.”</div>
                    <a href="https://www.instagram.com/annantdagur?igsh=OTMyZnIzaGR2aDVs" target="_blank"
                        rel="noopener noreferrer" class="founder-profile-btn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                            <rect x="2" y="2" width="20" height="20" rx="5" stroke="currentColor" stroke-width="2" />
                            <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="2" />
                            <circle cx="17.5" cy="6.5" r="1.2" fill="currentColor" />
                        </svg>
                        View Profile
                    </a>
                </div>
            </div>

            <div class="reveal" style="text-align:center;margin-top:40px;">
                <div
                    style="background:linear-gradient(135deg,var(--primary-faint),var(--secondary-faint));border:1px solid var(--border);border-radius:var(--radius);padding:24px 32px;display:inline-block;max-width:560px;text-align:center;">
                    <div style="font-size:13px;color:var(--text3);line-height:1.75;">We're a team of <strong
                            style="color:var(--text);">educators, engineers, and designers</strong> based in Aligarh,
                        UP. Every feature we build is shaped by direct feedback from real schools.</div>
                    <a href="{{ url('web/about') }}" class="btn btn-outline btn-lg"
                        style="margin-top:16px;display:inline-block;">Learn Our Story →</a>
                </div>
            </div>
        </div>
    </section>


    <!-- ═══════════════════════════════════════════
     WHY US — conclusion of the /web/why-us page
═══════════════════════════════════════════ -->
    <section class="section" style="background:var(--bg3);">
        <div style="max-width:1060px;margin:0 auto;">
            <div class="section-center reveal">
                <div class="section-tag tag-violet">Why Us</div>
                <h2 class="section-title">Why Schools <span class="gradient-text">Choose</span> SUPERLMS</h2>
                <p class="section-subtitle" style="max-width:780px;">The bottom line: SUPERLMS is the one partner your
                    school needs. You get every module in one place at genuinely affordable, transparent per-student
                    pricing — full power on the web and dedicated apps on every phone. Our team handles the entire
                    setup and data upload, real humans support you on call, chat and WhatsApp, and everything is
                    designed around Indian boards, fees and school workflows — not adapted from somewhere else.</p>
            </div>
            <div class="reveal" style="display:flex;flex-wrap:wrap;justify-content:center;gap:12px;margin-top:36px;">
                <span style="display:inline-flex;align-items:center;gap:8px;padding:9px 16px;border-radius:50px;background:#fff;border:1px solid var(--border);font-size:13.5px;font-weight:600;color:var(--text2);">🧩 All-in-One</span>
                <span style="display:inline-flex;align-items:center;gap:8px;padding:9px 16px;border-radius:50px;background:#fff;border:1px solid var(--border);font-size:13.5px;font-weight:600;color:var(--text2);">💰 Genuinely Affordable</span>
                <span style="display:inline-flex;align-items:center;gap:8px;padding:9px 16px;border-radius:50px;background:#fff;border:1px solid var(--border);font-size:13.5px;font-weight:600;color:var(--text2);">🔁 Hybrid Web + App</span>
                <span style="display:inline-flex;align-items:center;gap:8px;padding:9px 16px;border-radius:50px;background:#fff;border:1px solid var(--border);font-size:13.5px;font-weight:600;color:var(--text2);">🛠️ We Set It Up</span>
                <span style="display:inline-flex;align-items:center;gap:8px;padding:9px 16px;border-radius:50px;background:#fff;border:1px solid var(--border);font-size:13.5px;font-weight:600;color:var(--text2);">🛟 Real Human Support</span>
                <span style="display:inline-flex;align-items:center;gap:8px;padding:9px 16px;border-radius:50px;background:#fff;border:1px solid var(--border);font-size:13.5px;font-weight:600;color:var(--text2);">🇮🇳 Built for India</span>
            </div>
            <div class="reveal" style="text-align:center;margin-top:36px;">
                <a href="{{ url('web/why-us') }}" class="btn btn-outline btn-lg">See Why Schools Switch →</a>
            </div>
        </div>
    </section>


    <!-- ═══════════════════════════════════════════
     SERVICES — conclusion of the /web/services page
═══════════════════════════════════════════ -->
    <section class="section" style="background:#fff;">
        <div style="max-width:1060px;margin:0 auto;">
            <div class="section-center reveal">
                <div class="section-tag tag-pink">Our Services</div>
                <h2 class="section-title">Beyond Software — <span class="gradient-text">Complete School
                        Solutions</span></h2>
                <p class="section-subtitle" style="max-width:780px;">In short: SUPERLMS goes far beyond an LMS. From
                    running your classes online to printing ID cards, arranging school loans, setting up labs and smart
                    classrooms, managing transport, supplying uniforms &amp; books, and taking your teaching fully
                    online — everything your campus needs comes from one trusted partner, so you never juggle multiple
                    vendors again.</p>
            </div>
            <div class="reveal" style="display:flex;flex-wrap:wrap;justify-content:center;gap:12px;margin-top:36px;">
                <span style="display:inline-flex;align-items:center;gap:8px;padding:9px 16px;border-radius:50px;background:var(--bg3);border:1px solid var(--border);font-size:13.5px;font-weight:600;color:var(--text2);">🎓 School LMS</span>
                <span style="display:inline-flex;align-items:center;gap:8px;padding:9px 16px;border-radius:50px;background:var(--bg3);border:1px solid var(--border);font-size:13.5px;font-weight:600;color:var(--text2);">🆔 ID Cards</span>
                <span style="display:inline-flex;align-items:center;gap:8px;padding:9px 16px;border-radius:50px;background:var(--bg3);border:1px solid var(--border);font-size:13.5px;font-weight:600;color:var(--text2);">🏦 School Loans</span>
                <span style="display:inline-flex;align-items:center;gap:8px;padding:9px 16px;border-radius:50px;background:var(--bg3);border:1px solid var(--border);font-size:13.5px;font-weight:600;color:var(--text2);">🔬 Labs Setup</span>
                <span style="display:inline-flex;align-items:center;gap:8px;padding:9px 16px;border-radius:50px;background:var(--bg3);border:1px solid var(--border);font-size:13.5px;font-weight:600;color:var(--text2);">🖥️ Smart Classrooms</span>
                <span style="display:inline-flex;align-items:center;gap:8px;padding:9px 16px;border-radius:50px;background:var(--bg3);border:1px solid var(--border);font-size:13.5px;font-weight:600;color:var(--text2);">🚌 Smart Transport</span>
                <span style="display:inline-flex;align-items:center;gap:8px;padding:9px 16px;border-radius:50px;background:var(--bg3);border:1px solid var(--border);font-size:13.5px;font-weight:600;color:var(--text2);">👕 Uniforms &amp; Books</span>
                <span style="display:inline-flex;align-items:center;gap:8px;padding:9px 16px;border-radius:50px;background:var(--bg3);border:1px solid var(--border);font-size:13.5px;font-weight:600;color:var(--text2);">💻 Online Education</span>
            </div>
            <div class="reveal" style="text-align:center;margin-top:36px;">
                <a href="{{ url('web/services') }}" class="btn btn-outline btn-lg">Explore All Services →</a>
            </div>
        </div>
    </section>


    <!-- ═══════════════════════════════════════════
     SECURITY
═══════════════════════════════════════════ -->
    <section class="section" style="background:var(--bg3);padding:60px 6%;">
        <div style="max-width:1060px;margin:0 auto;">
            <div class="section-center reveal" style="margin-bottom:40px;">
                <div class="section-tag tag-dual">Security &amp; Reliability</div>
                <h2 class="section-title">Enterprise-Grade <span class="gradient-text">Protection</span></h2>
                <p class="section-subtitle">Your data and your students' data is protected with industry-leading
                    standards — always.</p>
            </div>

            <div class="security-strip-grid stagger-children">
                <div class="card-feature">
                    <div class="card-feature-icon">🔒</div>
                    <div class="card-feature-title">256-bit SSL</div>
                    <div class="card-feature-desc">Bank-grade encryption for all data in transit</div>
                </div>
                <div class="card-feature">
                    <div class="card-feature-icon">🛡️</div>
                    <div class="card-feature-title">Role-Based Access</div>
                    <div class="card-feature-desc">Granular permissions — right data to right people only</div>
                </div>
                <div class="card-feature">
                    <div class="card-feature-icon">☁️</div>
                    <div class="card-feature-title">Daily Backups</div>
                    <div class="card-feature-desc">Geo-redundant cloud backups, recoverable in minutes</div>
                </div>
                <div class="card-feature">
                    <div class="card-feature-icon">✅</div>
                    <div class="card-feature-title">GDPR Compliant</div>
                    <div class="card-feature-desc">Fully compliant with Indian IT Act &amp; DPDP Act 2023</div>
                </div>
            </div>
        </div>
    </section>


    <!-- ═══════════════════════════════════════════
     FAQ
═══════════════════════════════════════════ -->
    <section class="section" style="background:#fff;padding-top:72px;padding-bottom:72px;">
        <div style="max-width:860px;margin:0 auto;">
            <div class="section-center reveal">
                <div class="section-tag tag-violet">FAQ</div>
                <h2 class="section-title">Frequently Asked <span class="gradient-text">Questions</span></h2>
            </div>

            {{-- Category chips (no "All" — the first category is selected by default) --}}
            <div class="faq-chips reveal" id="faqChips">
                <button type="button" class="faq-chip active" data-cat="General">General</button>
                <button type="button" class="faq-chip" data-cat="Getting Started">Getting Started</button>
                <button type="button" class="faq-chip" data-cat="Features">Features</button>
                <button type="button" class="faq-chip" data-cat="Security">Security</button>
            </div>

            <div class="faq-wrap reveal" id="faqWrap" style="margin-top:28px;">
                <details class="faq-item" data-cat="General" open>
                    <summary class="faq-q">What is SUPERLMS, and who is it designed for?<span class="faq-icon">+</span></summary>
                    <div class="faq-a">SUPERLMS is a comprehensive Learning Management System for schools and
                        educational institutions that streamlines teaching, learning, and administrative tasks like
                        attendance, fees, timetables, and assessments. It's designed for students, teachers, and school
                        administrators.</div>
                </details>
                <details class="faq-item" data-cat="Getting Started">
                    <summary class="faq-q">How easy is it to get started with SUPERLMS?<span class="faq-icon">+</span></summary>
                    <div class="faq-a">Getting started with SUPERLMS is straightforward — schools can begin by signing
                        up or requesting a demo, then set up user roles, dashboards, and key modules without steep
                        technical barriers, making onboarding quick and manageable.</div>
                </details>
                <details class="faq-item" data-cat="General">
                    <summary class="faq-q">Can SUPERLMS be accessed on mobile devices?<span class="faq-icon">+</span></summary>
                    <div class="faq-a">Yes — SUPERLMS can be accessed on mobile devices. It offers a mobile-friendly
                        app (including an Android APK) so students and educators can log in, view syllabus and content,
                        submit &amp; view homework, and all school related features and interact with the system
                        conveniently from smartphones and tablets.</div>
                </details>
                <details class="faq-item" data-cat="Features">
                    <summary class="faq-q">Does SUPERLMS support attendance and fee management?<span class="faq-icon">+</span></summary>
                    <div class="faq-a">Yes — SUPERLMS includes both attendance tracking and fee management as core
                        features. It lets schools record and monitor student attendance easily and handle tuition fees,
                        payment records, and related administrative tasks within the same platform.</div>
                </details>
                <details class="faq-item" data-cat="Features">
                    <summary class="faq-q">Is SUPERLMS customizable to suit specific institutional needs?<span class="faq-icon">+</span></summary>
                    <div class="faq-a">Yes — SUPERLMS offers customizable options so institutions can adapt the system
                        to their specific academic structure, workflows, permissions, and feature use cases to better
                        match their operational needs and preferences.</div>
                </details>
                <details class="faq-item" data-cat="Security">
                    <summary class="faq-q">What security measures does SUPERLMS have in place?<span class="faq-icon">+</span></summary>
                    <div class="faq-a">SUPERLMS uses secure cloud hosting and encrypted data storage to protect
                        personal and academic information. It enforces secure user authentication, role-based access
                        controls, and privacy safeguards so only authorised school administrators and users can access
                        sensitive data.</div>
                </details>
                <details class="faq-item" data-cat="Features">
                    <summary class="faq-q">Can teachers easily share study materials through SUPERLMS?<span class="faq-icon">+</span></summary>
                    <div class="faq-a">Yes! SUPERLMS allows teachers to easily share study materials like PDFs, videos,
                        presentations, and more directly through the platform. Once uploaded, students receive instant
                        notifications, ensuring timely access. This seamless process enhances learning, keeps students
                        engaged, and simplifies resource management for educators, all within a single centralized
                        system.</div>
                </details>
            </div>
        </div>
    </section>


    <!-- ═══════════════════════════════════════════
     CTA
═══════════════════════════════════════════ -->
    <section class="cta-section">
        <div class="cta-bg"></div>
        <div class="cta-card reveal-scale">
            <!-- CTA Illustration -->
            <div style="display:flex;justify-content:center;gap:20px;margin-bottom:32px;">
                <span style="font-size:28px;">🏫</span>
                <span style="font-size:28px;">📚</span>
                <span style="font-size:28px;">🎓</span>
                <span style="font-size:28px;">✅</span>
                <span style="font-size:28px;">🚀</span>
            </div>
            <div class="section-tag tag-dual" style="margin:0 auto 20px;display:inline-flex;">Get Started</div>
            <h2 class="cta-title">Ready to Transform Your <span class="gradient-text">Institution?</span></h2>
            <p class="cta-desc">Join many schools already using SUPERLMS. Get started with a free personalized demo
                today — no commitment, no credit card required.</p>
            <div class="cta-actions">
                <a href="{{ url('web/demo') }}" class="btn btn-primary btn-xl">Request Free Demo</a>
                <a href="{{ url('web/pricing') }}" class="btn btn-outline btn-xl">View Pricing →</a>
            </div>
        </div>
    </section>


    @include('components.website.app-section')

    @include('components.website.footer')


    <script>
        /* ─────────────────────────────────────────────
                                   SUPERLMS — Homepage JavaScript
                                   ───────────────────────────────────────────── */

        document.addEventListener('DOMContentLoaded', () => {

            /* ── Navbar scroll effect ── */
            const navbar = document.getElementById('navbar');
            window.addEventListener('scroll', () => {
                navbar.classList.toggle('scrolled', window.scrollY > 40);
            });

            /* ── Mobile nav ── */
            const mobileNav = document.getElementById('mobileNav');
            const hamburger = document.getElementById('hamburger');
            const mobileClose = document.getElementById('mobileClose');
            if (hamburger) hamburger.addEventListener('click', () => mobileNav && mobileNav.classList.add('open'));
            if (mobileClose) mobileClose.addEventListener('click', () => mobileNav && mobileNav.classList.remove(
                'open'));
            document.querySelectorAll('.mobile-nav-link').forEach(l => {
                l.addEventListener('click', () => mobileNav && mobileNav.classList.remove('open'));
            });

            if (hamburger && mobileNav) {
                hamburger.addEventListener('click', () => mobileNav.classList.add('open'));
            }
            if (mobileClose && mobileNav) {
                mobileClose.addEventListener('click', () => mobileNav.classList.remove('open'));
            }

            /* ── FAQ accordion ──
               Now uses native <details>/<summary> (matches /web/faqs page).
               Keep only one item open at a time. */
            document.querySelectorAll('.faq-wrap .faq-item').forEach(item => {
                item.addEventListener('toggle', () => {
                    if (item.open) {
                        document.querySelectorAll('.faq-wrap .faq-item').forEach(other => {
                            if (other !== item) other.open = false;
                        });
                    }
                });
            });

            /* ── FAQ category chips (matches /web/faqs page) ── */
            const faqChips = Array.prototype.slice.call(document.querySelectorAll('#faqChips .faq-chip'));
            const faqItems = Array.prototype.slice.call(document.querySelectorAll('#faqWrap .faq-item'));

            const applyFaqFilter = (cat) => {
                faqItems.forEach(item => {
                    const show = (cat === 'all') || (item.getAttribute('data-cat') === cat);
                    item.style.display = show ? '' : 'none';
                    if (!show) item.open = false;
                });
            };

            faqChips.forEach(chip => {
                chip.addEventListener('click', () => {
                    faqChips.forEach(c => c.classList.remove('active'));
                    chip.classList.add('active');
                    faqItems.forEach(item => { item.open = false; }); // close any open FAQ when switching
                    applyFaqFilter(chip.getAttribute('data-cat'));
                });
            });

            // No "All" tab — start filtered to the default (first) active chip.
            const activeChip = faqChips.find(c => c.classList.contains('active'));
            if (activeChip) applyFaqFilter(activeChip.getAttribute('data-cat'));

            /* ── Scroll-reveal (IntersectionObserver) ── */
            const observer = new IntersectionObserver(entries => {
                entries.forEach(e => {
                    if (e.isIntersecting) {
                        e.target.classList.add('visible');
                        observer.unobserve(e.target);
                    }
                });
            }, {
                threshold: 0.05,
                rootMargin: '0px 0px -20px 0px'
            });

            document.querySelectorAll(
                '.reveal, .reveal-left, .reveal-right, .reveal-scale, .stagger-children'
            ).forEach(el => observer.observe(el));

            /* ── Smooth anchor scroll for nav links ── */
            document.querySelectorAll('a[href^="#"]').forEach(a => {
                a.addEventListener('click', e => {
                    const target = document.querySelector(a.getAttribute('href'));
                    if (target) {
                        e.preventDefault();
                        target.scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                });
            });

        });
    </script>

    <script>
        /* ─────────────────────────────────────────────
               WEBSITE DYNAMIC DATA — API-driven
            ───────────────────────────────────────────── */

        // ── Format numbers ──────────────────────────
        function fmtNum(n) {
            if (!n || n === 0) return '0';
            if (n >= 100000) return Math.floor(n / 1000) + 'K+';
            if (n >= 1000) return Math.floor(n / 1000) + 'K+';
            return n + '+';
        }

        // ── Stats + Hero Stats ───────────────────────
        fetch('/api/website/stats')
            .then(r => r.json())
            .then(({
                data
            }) => {
                if (!data) return;
                const s = data.schools || 0;
                const u = data.students || 0;
                const t = data.teachers || 0;
                const r = data.rating || 4.9;

                const schoolsLabel = s > 0 ? s + '+' : 'many';
                const studentsLabel = u > 0 ? fmtNum(u) : '100K+';
                const teachersLabel = t > 0 ? t + '+' : '500+';
                const ratingLabel = r + '★';

                // Hero
                const hs = document.getElementById('heroSchools');
                const hu = document.getElementById('heroStudents');
                const hr = document.getElementById('heroRating');
                if (hs && s > 0) hs.textContent = schoolsLabel;
                if (hu && u > 0) hu.textContent = studentsLabel;
                if (hr) hr.textContent = ratingLabel;

                // Hero dashboard illustration KPIs
                const ds = document.getElementById('dashSchools');
                const du = document.getElementById('dashStudents');
                const dr = document.getElementById('dashRating');
                if (ds && s > 0) ds.textContent = schoolsLabel;
                if (du && u > 0) du.textContent = studentsLabel;
                if (dr) dr.textContent = ratingLabel;

                // Stats section
                const ss = document.getElementById('statSchools');
                const su = document.getElementById('statStudents');
                const st = document.getElementById('statTeachers');
                const sr = document.getElementById('statRating');
                if (ss && s > 0) ss.textContent = schoolsLabel;
                if (su && u > 0) su.textContent = studentsLabel;
                if (st && t > 0) st.textContent = teachersLabel;
                if (sr) sr.textContent = r + '/5';
            })
            .catch(() => {});

        // ── Schools marquee ──────────────────────────
        fetch('/api/website/schools')
            .then(r => r.json())
            .then(({
                data
            }) => {
                const track = document.getElementById('marqueeTrack');
                if (!track || !data || data.length === 0) return;

                const fallbackLogo = "{{ asset('website-image/Vector 215.png') }}";

                const itemHtml = (school) => {
                    const name = typeof school === 'string' ? school : school.name;
                    const logo = (typeof school === 'object' && school.logo_url) ? school.logo_url : fallbackLogo;
                    return `<div class="marquee-item">` +
                        `<img class="marquee-logo" src="${escText(logo)}" alt="${escText(name)} logo" loading="lazy" onerror="this.onerror=null;this.src='${fallbackLogo}'"/>` +
                        `</div>`;
                };

                // Fewer than 5 schools → show exactly that many in a static, centered
                // grid (no sliding).
                if (data.length < 5) {
                    track.classList.add('marquee-static');
                    track.innerHTML = data.map(itemHtml).join('');
                    return;
                }

                // 5 or more → stepped carousel: every second the row slides one
                // logo right→left; the logo in the centre slot is emphasised.
                // Repeat the list so there is always a logo waiting off-screen.
                let base = data.slice();
                while (base.length < 7) base = base.concat(data);
                track.innerHTML = base.map(itemHtml).join('');

                // `shift` = how many slots the row is about to move; child i sits
                // in visible slot (i - shift). Slot 2 is the centre of the 5.
                const paintSizes = (shift) => {
                    Array.prototype.forEach.call(track.children, (el, i) => {
                        const slot = i - shift;
                        el.classList.remove('pos-center', 'pos-near');
                        if (slot === 2) el.classList.add('pos-center');
                        else if (slot === 1 || slot === 3) el.classList.add('pos-near');
                    });
                };

                paintSizes(0);
                let sliding = false;

                setInterval(() => {
                    if (document.hidden || sliding || !track.children.length) return;
                    sliding = true;

                    const slotW = track.children[0].getBoundingClientRect().width;
                    paintSizes(1); // sizes morph towards their post-slide slots
                    track.style.transition = 'transform .55s cubic-bezier(.4, 0, .2, 1)';
                    track.style.transform = 'translateX(-' + slotW + 'px)';

                    setTimeout(() => {
                        // Move the logo that just left the window to the end and
                        // snap back — invisible reset, loop runs forever.
                        track.style.transition = 'none';
                        track.appendChild(track.children[0]);
                        track.style.transform = 'translateX(0)';
                        void track.offsetWidth; // flush so the next slide animates
                        paintSizes(0);
                        sliding = false;
                    }, 600);
                }, 1000);
            })
            .catch(() => {});

        // ── Testimonials slider ──────────────────────
        let tCurrent = 0;
        let tTotal = 0;
        let tItems = [];
        let tAuto = null;

        function getVisible() {
            return window.innerWidth >= 900 ? 3 : (window.innerWidth >= 600 ? 2 : 1);
        }

        function escText(s) {
            if (s == null) return '';
            return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function starsHtml(n) {
            return '★'.repeat(Math.max(1, Math.min(5, n || 5)));
        }

        function getSliderWidth() {
            const slider = document.getElementById('testimonialsSlider');
            return slider ? slider.offsetWidth : 0;
        }

        function setCardWidths() {
            const slider = document.getElementById('testimonialsSlider');
            if (!slider || tTotal === 0) return;
            const vis = Math.min(getVisible(), tTotal);
            const cardW = Math.floor(slider.offsetWidth / vis) - 16;
            slider.querySelectorAll('.testimonial-card').forEach(card => {
                card.style.width = cardW + 'px';
                card.style.minWidth = cardW + 'px';
            });
        }

        function buildTestimonials(items) {
            const track = document.getElementById('testimonialsTrack');
            const dots = document.getElementById('testimonialDots');
            if (!track) return;
            tItems = items;
            tTotal = items.length;
            tCurrent = 0;

            const gradients = [
                'linear-gradient(135deg,#DB57B2,#B83D92)',
                'linear-gradient(135deg,#6F56FE,#5540D4)',
                'linear-gradient(135deg,#DB57B2,#6F56FE)',
                'linear-gradient(135deg,#5540D4,#DB57B2)',
            ];



            track.innerHTML = items.map((t, i) => `
            <div class="testimonial-card" style="flex-shrink:0;margin:0 8px;box-sizing:border-box;">
                <div class="testimonial-stars">${starsHtml(t.rating)}</div>
                <p class="testimonial-text">"${escText(t.feedback)}"</p>
                <div class="testimonial-author">
                    ${t.logo_url
                        ? `<div class="testimonial-avatar" style="background:#fff;min-width:40px;"><img src="${escText(t.logo_url)}" alt="${escText(t.school_name)} logo" loading="lazy" onerror="this.closest('.testimonial-avatar').outerHTML='<div class=&quot;testimonial-avatar&quot; style=&quot;background:${gradients[i % gradients.length]};min-width:40px;&quot;>${escText(t.initials)}</div>'"></div>`
                        : `<div class="testimonial-avatar" style="background:${gradients[i % gradients.length]};min-width:40px;">${escText(t.initials)}</div>`}
                    <div>
                        <div class="testimonial-name">${escText(t.school_name)}</div>
                        <div class="testimonial-role">Verified School</div>
                    </div>
                </div>
            </div>`).join('');

            setCardWidths();

            // Dots — one per slide position
            const vis = Math.min(getVisible(), tTotal);
            const positions = Math.max(1, tTotal - vis + 1);
            if (dots) {
                dots.innerHTML = Array.from({
                        length: positions
                    }, (_, i) =>
                    `
                <button onclick="goTestimonial(${i})" style="width:8px;height:8px;border-radius:4px;border:none;padding:0;cursor:pointer;background:${i===0?'var(--violet)':'var(--border)'};transition:all .3s;"></button>`
                ).join('');
            }

            startAutoSlide();
            updateSlider();
        }

        function updateSlider() {
            const track = document.getElementById('testimonialsTrack');
            const slider = document.getElementById('testimonialsSlider');
            if (!track || !slider || tTotal === 0) return;

            const vis = Math.min(getVisible(), tTotal);
            const maxPos = Math.max(0, tTotal - vis);
            tCurrent = Math.min(tCurrent, maxPos);

            const cardW = Math.floor(slider.offsetWidth / vis);
            track.style.transform = `translateX(-${tCurrent * cardW}px)`;

            // Update dots
            const dots = document.getElementById('testimonialDots');
            if (dots) {
                dots.querySelectorAll('button').forEach((d, i) => {
                    const active = i === tCurrent;
                    d.style.background = active ? 'var(--violet)' : 'var(--border)';
                    d.style.width = active ? '22px' : '8px';
                    d.style.borderRadius = '4px';
                });
            }
        }

        function testimonialSlide(dir) {
            if (tTotal === 0) return;
            const vis = Math.min(getVisible(), tTotal);
            const maxPos = Math.max(0, tTotal - vis);
            tCurrent = (tCurrent + dir + maxPos + 1) % (maxPos + 1);
            updateSlider();
            resetAutoSlide();
        }

        function goTestimonial(idx) {
            tCurrent = idx;
            updateSlider();
            resetAutoSlide();
        }

        function startAutoSlide() {
            if (tAuto) clearInterval(tAuto);
            tAuto = setInterval(() => testimonialSlide(1), 3500);
        }

        function resetAutoSlide() {
            if (tAuto) clearInterval(tAuto);
            startAutoSlide();
        }

        window.addEventListener('resize', () => {
            setCardWidths();
            updateSlider();
        });

        const staticTestimonials = [{
                feedback: 'SUPERLMS transformed how we manage our institution. Attendance tracking and fee collection became completely seamless.',
                rating: 5,
                school_name: 'Delhi Public School',
                logo_url: null,
                initials: 'DP'
            },
            {
                feedback: 'The setup was incredibly smooth and support was always available. Student engagement increased by over 60%.',
                rating: 5,
                school_name: 'Sunrise Academy',
                logo_url: null,
                initials: 'SA'
            },
            {
                feedback: 'Sharing notes and assignments is so easy now. Parents can track their child\'s progress in real time.',
                rating: 5,
                school_name: "St. Mary's School",
                logo_url: null,
                initials: 'SM'
            },
        ];

        fetch('/api/website/testimonials')
            .then(r => r.json())
            .then(({
                data
            }) => {
                buildTestimonials(data && data.length ? data : staticTestimonials);
            })
            .catch(() => buildTestimonials(staticTestimonials));

        window.testimonialSlide = testimonialSlide;
        window.goTestimonial = goTestimonial;
    </script>
</body>

</html>
