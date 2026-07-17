<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>About Us — SUPERLMS</title>
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
        /* ══════════════════ CSS VARIABLES ══════════════════ */
        :root {
            --pink: #DB57B2;
            --pink-dark: #B83D92;
            --pink-light: #E878C4;
            --violet: #6F56FE;
            --violet-dark: #5540D4;
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

        /* ══════════════════ RESET & BASE ══════════════════ */
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

        /* ══════════════════ KEYFRAMES ══════════════════ */
        @keyframes shimmer {
            0% {
                background-position: -200% center;
            }

            100% {
                background-position: 200% center;
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

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
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

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-60px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
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

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
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

        @keyframes staggerIn {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
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
            transition: all .2s;
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

        /* ══════════════════ SHARED TYPOGRAPHY ══════════════════ */
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

        .tag-pink {
            background: var(--primary-faint);
            border: 1px solid var(--border-pink);
            color: var(--pink-dark);
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

        .gradient-text-violet {
            background: var(--grad-violet);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .section-subtitle {
            font-size: 15px;
            color: var(--text3);
            line-height: 1.8;
            max-width: 560px;
            font-weight: 400;
        }

        /* ══════════════════ PAGE HEADER ══════════════════ */
        .page-header {
            padding: 140px 6% 80px;
            background: linear-gradient(135deg, var(--primary-faint), var(--secondary-faint));
            border-bottom: 1px solid var(--border2);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .page-header .blob1 {
            position: absolute;
            top: -60px;
            right: -60px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(111, 86, 254, 0.07);
            filter: blur(60px);
            pointer-events: none;
        }

        .page-header .blob2 {
            position: absolute;
            bottom: -40px;
            left: -40px;
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: rgba(219, 87, 178, 0.07);
            filter: blur(60px);
            pointer-events: none;
        }

        .page-header-content {
            position: relative;
            z-index: 1;
        }

        .page-header-content .section-subtitle {
            max-width: 820px;
            margin: 16px auto 0;
        }

        /* ══════════════════ SECTIONS ══════════════════ */
        .section {
            padding: 100px 6%;
            position: relative;
        }

        /* ══════════════════ MISSION SECTION ══════════════════ */
        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .about-grid .left {
            animation: slideInLeft .75s ease forwards;
            opacity: 0;
        }

        .about-grid .right {
            animation: slideInRight .9s ease .1s forwards;
            opacity: 0;
        }

        .illus-float {
            animation: float 5s ease-in-out infinite;
        }

        .illus-float-slow {
            animation: floatSlow 7s ease-in-out infinite;
        }

        /* ══════════════════ STORY SECTION ══════════════════ */
        .story-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 72px;
            align-items: center;
            max-width: 1100px;
            margin: 0 auto;
        }

        .story-illus-wrap {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .story-stat-bubble {
            position: absolute;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 12px 16px;
            box-shadow: var(--shadow2);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text);
            white-space: nowrap;
        }

        .story-stat-bubble.b1 {
            top: 16px;
            right: -8px;
            animation: float 4s ease-in-out infinite;
        }

        .story-stat-bubble.b2 {
            bottom: 60px;
            left: -8px;
            animation: float 5s ease-in-out 1s infinite;
        }

        .story-stat-bubble.b3 {
            bottom: 0;
            right: 24px;
            animation: float 4.5s ease-in-out .5s infinite;
        }

        .story-para {
            font-size: 15px;
            color: var(--text3);
            line-height: 1.9;
            margin-bottom: 16px;
        }

        .story-highlights {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 28px;
        }

        .story-highlight {
            background: linear-gradient(135deg, var(--primary-faint), var(--secondary-faint));
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px 18px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .sh-icon {
            font-size: 20px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .sh-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 3px;
        }

        .sh-desc {
            font-size: 11px;
            color: var(--text3);
            line-height: 1.6;
        }

        /* ══════════════════ NUMBERS SECTION ══════════════════ */
        .numbers-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-top: 48px;
        }

        .card-num {
            background: var(--bg3);
            border: 1px solid var(--border2);
            border-radius: var(--radius-sm);
            padding: 18px 16px;
            text-align: center;
            transition: all .3s;
            cursor: default;
        }

        .card-num:hover {
            background: #fff;
            border-color: var(--border);
            transform: translateY(-4px);
            box-shadow: var(--shadow3);
        }

        /* ══════════════════ WHAT WE DO SECTION ══════════════════ */
        .about-list {
            margin-top: 32px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .card-about-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 18px 20px;
            background: #fff;
            border: 1px solid var(--border2);
            border-radius: var(--radius-sm);
            transition: all .35s;
        }

        .card-about-item:hover {
            border-color: var(--border);
            background: var(--bg3);
            transform: translateX(8px);
        }

        .about-item-icon {
            width: 40px;
            height: 40px;
            min-width: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .about-item-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 4px;
        }

        .about-item-desc {
            font-size: 13px;
            color: var(--text3);
            line-height: 1.65;
        }

        /* ══════════════════ WHAT WE DO (redesigned card grid) ══════════════════ */
        .wd-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-top: 44px;
        }

        .wd-card {
            background: #fff;
            border: 1px solid var(--border2);
            border-radius: var(--radius);
            padding: 28px 24px;
            text-align: left;
            position: relative;
            overflow: hidden;
            transition: all .35s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .wd-card::after {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(111, 86, 254, 0.08), transparent 70%);
            opacity: 0;
            transition: opacity .35s;
        }

        .wd-card:hover {
            transform: translateY(-6px);
            border-color: var(--border);
            box-shadow: var(--shadow2);
        }

        .wd-card:hover::after {
            opacity: 1;
        }

        .wd-icon {
            width: 54px;
            height: 54px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
            margin-bottom: 18px;
            color: #fff;
            box-shadow: 0 8px 20px rgba(111, 86, 254, 0.2);
            position: relative;
            z-index: 1;
        }

        .wd-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }

        .wd-desc {
            font-size: 13px;
            color: var(--text3);
            line-height: 1.7;
            position: relative;
            z-index: 1;
        }

        @media (max-width: 1100px) {
            .wd-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 640px) {
            .wd-grid { grid-template-columns: 1fr; }
        }

        /* ══════════════════ WORKING MODEL ══════════════════ */
        .wmodel-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 56px;
        }

        .card-wmodel {
            background: #fff;
            border: 1px solid var(--border2);
            border-radius: var(--radius);
            padding: 28px;
            position: relative;
            overflow: hidden;
            transition: all .35s;
        }

        .card-wmodel::before {
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

        .card-wmodel:hover::before {
            transform: scaleX(1);
        }

        .card-wmodel:hover {
            background: var(--bg3);
            transform: translateY(-6px);
            box-shadow: var(--shadow2);
        }

        .wmodel-num {
            font-family: 'Cormorant Garamond', serif;
            font-size: 48px;
            font-weight: 700;
            background: var(--grad1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            opacity: .12;
            position: absolute;
            top: 16px;
            right: 20px;
            line-height: 1;
        }

        /* ══════════════════ ROLES SECTION ══════════════════ */
        .role-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 56px;
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

        /* Role card internals — matches the homepage "Every Role" section. */
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

        /* ══════════════════ SECURITY SECTION ══════════════════ */
        .security-illus-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .card-slide-right {
            display: flex;
            gap: 16px;
            align-items: flex-start;
            padding: 16px 20px;
            background: #fff;
            border: 1px solid var(--border2);
            border-radius: 12px;
            margin-bottom: 12px;
            transition: all .3s;
        }

        .card-slide-right:hover {
            background: var(--bg3);
            border-color: var(--border);
            transform: translateX(6px);
        }

        /* ══════════════════ TEAM SECTION ══════════════════ */
        .team-grid-about {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .team-card {
            background: #fff;
            border: 1px solid var(--border2);
            border-radius: var(--radius);
            padding: 28px 20px;
            text-align: center;
            transition: all .35s cubic-bezier(0.34, 1.56, 0.64, 1);
            cursor: default;
        }

        .team-card:hover {
            transform: translateY(-8px);
            border-color: var(--border);
            box-shadow: var(--shadow2);
        }

        /* ── Founder Spotlight (matches homepage) ── */
        .founder-spotlight {
            position: relative;
            margin: 0 auto;
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
            text-align: left;
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

            .founder-content {
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

        /* Join Team CTA */
        .join-team-box {
            margin-top: 56px;
            background: linear-gradient(135deg, var(--primary-faint), var(--secondary-faint));
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 40px 32px;
            max-width: 720px;
            margin-left: auto;
            margin-right: auto;
            text-align: center;
        }

        /* ══════════════════ STAGGER ANIMATION UTILITY ══════════════════ */
        .stagger>* {
            opacity: 0;
            transform: translateY(28px);
        }

        .stagger.visible>* {
            animation: slideInUp .65s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .stagger.visible>*:nth-child(1) {
            animation-delay: .04s;
        }

        .stagger.visible>*:nth-child(2) {
            animation-delay: .10s;
        }

        .stagger.visible>*:nth-child(3) {
            animation-delay: .16s;
        }

        .stagger.visible>*:nth-child(4) {
            animation-delay: .22s;
        }

        .stagger.visible>*:nth-child(5) {
            animation-delay: .28s;
        }

        .stagger.visible>*:nth-child(6) {
            animation-delay: .34s;
        }

        .stagger.visible>*:nth-child(7) {
            animation-delay: .40s;
        }

        .stagger.visible>*:nth-child(8) {
            animation-delay: .46s;
        }

        .stagger-left>* {
            opacity: 0;
            transform: translateX(-30px);
        }

        .stagger-left.visible>* {
            animation: staggerIn .65s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .stagger-left.visible>*:nth-child(1) {
            animation-delay: .05s;
        }

        .stagger-left.visible>*:nth-child(2) {
            animation-delay: .12s;
        }

        .stagger-left.visible>*:nth-child(3) {
            animation-delay: .19s;
        }

        .stagger-left.visible>*:nth-child(4) {
            animation-delay: .26s;
        }

        .stagger-left.visible>*:nth-child(5) {
            animation-delay: .33s;
        }

        .stagger-left.visible>*:nth-child(6) {
            animation-delay: .40s;
        }

        .reveal {
            opacity: 0;
            transform: translateY(32px);
            transition: opacity .75s cubic-bezier(0.16, 1, 0.3, 1), transform .75s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
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
        @media (max-width: 1100px) {

            .about-grid,
            .story-grid,
            .security-illus-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .story-illus-wrap {
                max-width: 420px;
                margin: 0 auto;
            }

            .story-stat-bubble {
                display: none;
            }

            .numbers-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .wmodel-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .role-grid-2 {
                grid-template-columns: 1fr;
            }

            .footer-grid {
                grid-template-columns: 1fr 1fr;
                gap: 36px;
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

            .wmodel-grid {
                grid-template-columns: 1fr;
            }

            .story-highlights {
                grid-template-columns: 1fr;
            }

            .team-grid-about {
                grid-template-columns: repeat(2, 1fr);
            }

            .footer-grid {
                grid-template-columns: 1fr;
            }

            .page-header {
                padding: 120px 5% 60px;
            }
        }

        @media (max-width: 480px) {
            .section {
                padding: 52px 4%;
            }

            .numbers-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .team-grid-about {
                grid-template-columns: 1fr;
            }

            .section-title {
                font-size: clamp(1.9rem, 7vw, 2.8rem);
            }

            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }

            .footer-bottom-links {
                justify-content: center;
                flex-wrap: wrap;
            }

            .join-team-box {
                padding: 28px 20px;
            }
        }
    </style>
</head>

<body>

    <!-- ══════════════════ NAVBAR ══════════════════ -->


    @include('components.website.header')


    <!-- ══════════════════ PAGE HEADER ══════════════════ -->
    <div class="page-header">
        <div class="blob1"></div>
        <div class="blob2"></div>
        <div class="page-header-content">
            <div class="section-tag tag-dual reveal">About Us</div>
            <h1 class="section-title reveal" style="margin-top:16px;font-size:clamp(1.7rem,3vw,2.5rem);">Your All in One Solution for<br /><span
                    class="gradient-text">Learning &amp; Management</span></h1>
            <p class="section-subtitle reveal">SUPERLMS is a cloud-based, all-in-one school management and learning
                platform that brings academics, administration, and communication onto a single platform. Instead of
                juggling registers, spreadsheets, WhatsApp groups, and paper records, schools get attendance,
                timetables, fees, assignments, exams, report cards, and parent communication in one connected
                ecosystem — trusted by schools across India, from small rural institutions to large multi-campus
                academies, with more than 50,000 students actively learning on the platform.</p>
        </div>
    </div>

    <!-- ══════════════════ MISSION SECTION ══════════════════ -->
    <section class="section" style="background:#fff;">
        <div class="about-grid">
            <div class="left">
                <div class="section-tag tag-violet">Our Mission</div>
                <h2 class="section-title">Making Education <span class="gradient-text">Accessible</span> &amp;
                    Affordable</h2>
                <p style="color:var(--text3);line-height:1.8;margin-bottom:20px;font-size:15px;">Our mission is simple:
                    make quality school-management technology accessible and affordable for every institution in India —
                    not just large, well-funded schools, but also small-town and rural institutions that are often
                    priced out of modern EdTech tools.</p>
                <p style="color:var(--text3);line-height:1.8;margin-bottom:32px;font-size:15px;">We believe a school in
                    a small town deserves the same powerful digital tools as a large city academy, at a price point that
                    actually makes sense for its budget. This belief shapes every feature we build and every pricing
                    decision we make.</p>
                <a href="{{ url('web/demo') }}" class="btn btn-primary btn-lg">Start Your Journey →</a>
            </div>
            <div class="right" style="display:flex;justify-content:center;align-items:center;">
                <div class="illus-float">
                    <!-- IllusMission SVG -->
                    <svg viewBox="0 0 320 300" fill="none" xmlns="http://www.w3.org/2000/svg"
                        style="width:100%;max-width:320px;">
                        <circle cx="160" cy="150" r="120" fill="url(#gm1)" opacity="0.06" />
                        <circle cx="160" cy="150" r="80" fill="url(#gm1)" opacity="0.08" />
                        <circle cx="160" cy="150" r="40" fill="url(#gm1)" opacity="0.15" />
                        <circle cx="160" cy="150" r="24" fill="url(#gm1)" />
                        <text x="160" y="158" text-anchor="middle" font-size="18">🎯</text>
                        <!-- orbit icons -->
                        <g>
                            <circle cx="230" cy="150" r="18" fill="white" stroke="#EDE8FF"
                                stroke-width="1.5" /><text x="230" y="155" text-anchor="middle" font-size="12">📊</text>
                        </g>
                        <g>
                            <circle cx="215" cy="93" r="18" fill="white" stroke="#EDE8FF"
                                stroke-width="1.5" /><text x="215" y="98" text-anchor="middle" font-size="12">✅</text>
                        </g>
                        <g>
                            <circle cx="160" cy="70" r="18" fill="white" stroke="#EDE8FF"
                                stroke-width="1.5" /><text x="160" y="75" text-anchor="middle" font-size="12">💰</text>
                        </g>
                        <g>
                            <circle cx="105" cy="93" r="18" fill="white" stroke="#EDE8FF"
                                stroke-width="1.5" /><text x="105" y="98" text-anchor="middle" font-size="12">📝</text>
                        </g>
                        <g>
                            <circle cx="90" cy="150" r="18" fill="white" stroke="#EDE8FF"
                                stroke-width="1.5" /><text x="90" y="155" text-anchor="middle" font-size="12">🏆</text>
                        </g>
                        <g>
                            <circle cx="105" cy="207" r="18" fill="white" stroke="#EDE8FF"
                                stroke-width="1.5" /><text x="105" y="212" text-anchor="middle" font-size="12">📱</text>
                        </g>
                        <rect x="20" y="250" width="130" height="40" rx="10" fill="white"
                            stroke="#EDE8FF" stroke-width="1" />
                        <text x="85" y="268" text-anchor="middle" font-size="10" fill="#6F56FE" font-weight="600">Since
                            2024</text>
                        <text x="85" y="281" text-anchor="middle" font-size="9" fill="#A99CC0">Aligarh, India</text>
                        <rect x="170" y="250" width="130" height="40" rx="10" fill="white"
                            stroke="#EDE8FF" stroke-width="1" />
                        <text x="235" y="268" text-anchor="middle" font-size="10" fill="#DB57B2"
                            font-weight="600">50,000+ Students</text>
                        <text x="235" y="281" text-anchor="middle" font-size="9" fill="#A99CC0">learning on SUPERLMS</text>
                        <defs>
                            <linearGradient id="gm1" x1="0" y1="0" x2="1"
                                y2="1">
                                <stop stop-color="#DB57B2" />
                                <stop offset="1" stop-color="#6F56FE" />
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════════ OUR STORY SECTION ══════════════════ -->
    <section class="section" style="background:var(--bg3);border-top:1px solid var(--border2);">
        <div style="max-width:1100px;margin:0 auto;">
            <div class="story-grid">

                <!-- Illustration side -->
                <div class="story-illus-wrap" style="animation:slideInLeft .75s ease forwards;opacity:0;">
                    <div class="illus-float-slow" style="width:100%;">
                        <!-- IllusStory SVG -->
                        <svg viewBox="0 0 400 360" fill="none" xmlns="http://www.w3.org/2000/svg"
                            style="width:100%;max-width:400px;">
                            <defs>
                                <linearGradient id="sg1" x1="0" y1="0" x2="1"
                                    y2="1">
                                    <stop stop-color="#DB57B2" />
                                    <stop offset="1" stop-color="#6F56FE" />
                                </linearGradient>
                                <linearGradient id="sg2" x1="0" y1="0" x2="1"
                                    y2="1">
                                    <stop stop-color="#6F56FE" />
                                    <stop offset="1" stop-color="#DB57B2" />
                                </linearGradient>
                            </defs>
                            <ellipse cx="200" cy="180" rx="140" ry="160" fill="url(#sg1)"
                                opacity="0.05" />
                            <ellipse cx="200" cy="180" rx="100" ry="120" fill="url(#sg1)"
                                opacity="0.07" />
                            <!-- city dots -->
                            <circle cx="130" cy="120" r="22" fill="#DB57B2" opacity="0.15" />
                            <circle cx="130" cy="120" r="14" fill="#DB57B2" opacity="0.25" />
                            <circle cx="130" cy="120" r="8" fill="#DB57B2" />
                            <text x="144" y="125" font-size="10" fill="#DB57B2" font-weight="700">Aligarh ★</text>
                            <circle cx="130" cy="120" r="16" stroke="#DB57B2" stroke-width="1.5"
                                opacity="0.4" stroke-dasharray="4 4" />
                            <circle cx="165" cy="95" r="5" fill="#6F56FE" opacity="0.7" />
                            <text x="173" y="99" font-size="8" fill="#A99CC0">Delhi</text>
                            <circle cx="240" cy="110" r="5" fill="#DB57B2" opacity="0.7" />
                            <text x="248" y="114" font-size="8" fill="#A99CC0">Lucknow</text>
                            <circle cx="110" cy="170" r="5" fill="#6F56FE" opacity="0.7" />
                            <text x="118" y="174" font-size="8" fill="#A99CC0">Jaipur</text>
                            <circle cx="230" cy="190" r="5" fill="#DB57B2" opacity="0.7" />
                            <text x="238" y="194" font-size="8" fill="#A99CC0">Patna</text>
                            <circle cx="155" cy="210" r="5" fill="#6F56FE" opacity="0.7" />
                            <text x="163" y="214" font-size="8" fill="#A99CC0">Bhopal</text>
                            <circle cx="200" cy="270" r="5" fill="#DB57B2" opacity="0.7" />
                            <text x="208" y="274" font-size="8" fill="#A99CC0">Mumbai</text>
                            <circle cx="270" cy="240" r="5" fill="#6F56FE" opacity="0.7" />
                            <text x="278" y="244" font-size="8" fill="#A99CC0">Hyderabad</text>
                            <circle cx="160" cy="300" r="5" fill="#DB57B2" opacity="0.7" />
                            <text x="168" y="304" font-size="8" fill="#A99CC0">Bangalore</text>
                            <!-- connecting lines -->
                            <line x1="130" y1="120" x2="165" y2="95" stroke="url(#sg1)"
                                stroke-width="1" opacity="0.25" stroke-dasharray="4 3" />
                            <line x1="130" y1="120" x2="240" y2="110" stroke="url(#sg1)"
                                stroke-width="1" opacity="0.25" stroke-dasharray="4 3" />
                            <line x1="130" y1="120" x2="110" y2="170" stroke="url(#sg1)"
                                stroke-width="1" opacity="0.25" stroke-dasharray="4 3" />
                            <line x1="130" y1="120" x2="230" y2="190" stroke="url(#sg1)"
                                stroke-width="1" opacity="0.25" stroke-dasharray="4 3" />
                            <line x1="130" y1="120" x2="155" y2="210" stroke="url(#sg1)"
                                stroke-width="1" opacity="0.25" stroke-dasharray="4 3" />
                            <line x1="130" y1="120" x2="200" y2="270" stroke="url(#sg1)"
                                stroke-width="1" opacity="0.25" stroke-dasharray="4 3" />
                            <line x1="130" y1="120" x2="270" y2="240" stroke="url(#sg1)"
                                stroke-width="1" opacity="0.25" stroke-dasharray="4 3" />
                            <line x1="130" y1="120" x2="160" y2="300" stroke="url(#sg1)"
                                stroke-width="1" opacity="0.25" stroke-dasharray="4 3" />
                            <!-- bottom stats -->
                            <rect x="28" y="50" width="80" height="52" rx="10" fill="white"
                                stroke="#EDE8FF" stroke-width="1" />
                            <text x="68" y="72" text-anchor="middle" font-size="10" fill="#6F56FE"
                                font-weight="700">2024</text>
                            <text x="68" y="86" text-anchor="middle" font-size="8" fill="#A99CC0">Founded in</text>
                            <text x="68" y="97" text-anchor="middle" font-size="8" fill="#A99CC0">Aligarh, UP</text>
                            <rect x="288" y="50" width="88" height="52" rx="10" fill="white"
                                stroke="#EDE8FF" stroke-width="1" />
                            <text x="332" y="72" text-anchor="middle" font-size="10" fill="#DB57B2"
                                font-weight="700">2026–27</text>
                            <text x="332" y="86" text-anchor="middle" font-size="8" fill="#A99CC0">Schools across</text>
                            <text x="332" y="97" text-anchor="middle" font-size="8" fill="#A99CC0">India</text>
                            <rect x="40" y="300" width="95" height="46" rx="10" fill="white"
                                stroke="#EDE8FF" stroke-width="1" />
                            <text x="87" y="320" text-anchor="middle" font-size="16">🏫</text>
                            <text x="87" y="338" text-anchor="middle" font-size="8" fill="#6F56FE"
                                font-weight="600">Growing Network</text>
                            <rect x="152" y="316" width="95" height="34" rx="10" fill="url(#sg1)"
                                opacity="0.1" stroke="url(#sg1)" stroke-width="0.5" />
                            <text x="200" y="337" text-anchor="middle" font-size="9" fill="#6F56FE"
                                font-weight="700">50,000+ Students</text>
                            <rect x="264" y="300" width="95" height="46" rx="10" fill="white"
                                stroke="#EDE8FF" stroke-width="1" />
                            <text x="312" y="320" text-anchor="middle" font-size="16">⭐</text>
                            <text x="312" y="338" text-anchor="middle" font-size="8" fill="#DB57B2"
                                font-weight="600">4.9/5 Rating</text>
                        </svg>
                    </div>
                    <!-- Floating stat bubbles -->
                    <div class="story-stat-bubble b1">
                        <span style="font-size:16px;">🚀</span>
                        <div>
                            <div style="font-size:11px;font-weight:700;color:var(--violet);">Founded 2024</div>
                            <div style="font-size:10px;color:var(--text3);">Aligarh, Uttar Pradesh</div>
                        </div>
                    </div>
                    <div class="story-stat-bubble b2">
                        <span style="font-size:16px;">🏫</span>
                        <div>
                            <div style="font-size:11px;font-weight:700;color:var(--pink-dark);">Growing Network</div>
                            <div style="font-size:10px;color:var(--text3);">of schools across India</div>
                        </div>
                    </div>
                    <div class="story-stat-bubble b3">
                        <span style="font-size:16px;">🎓</span>
                        <div>
                            <div style="font-size:11px;font-weight:700;color:var(--violet);">50,000+ Students</div>
                            <div style="font-size:10px;color:var(--text3);">actively learning</div>
                        </div>
                    </div>
                </div>

                <!-- Text side -->
                <div style="animation:slideInRight .9s ease .1s forwards;opacity:0;">
                    <div class="section-tag tag-pink">Our Story</div>
                    <h2 class="section-title">From a Basic <span class="gradient-text">Idea to a Platform</span></h2>
                    <p class="story-para">SUPERLMS began in 2024 out of a small office in Aligarh, Uttar Pradesh — not
                        as a corporate initiative, but as a response to a very real, everyday problem. Our founding team
                        watched teachers spend hours on paper attendance registers, principals repeatedly chase parents
                        for pending fees, and students miss timely feedback on their progress simply because the tools
                        available to schools hadn't kept pace with the rest of the world.</p>
                    <p class="story-para">The first version of SUPERLMS launched quietly with a single partner school
                        and a core team of three people. Word spread organically — one principal recommending the
                        platform to another, teachers sharing their experience in WhatsApp groups — and the school base
                        grew steadily from there.</p>
                    <p class="story-para">By the end of 2025, we had introduced dedicated Android and iOS applications,
                        integrated biometric attendance, and real-time WhatsApp notifications for parents. Today,
                        SUPERLMS supports a growing network of schools across India, with fully integrated modules that
                        continue to evolve based on direct feedback from the educators who use them every day.</p>
                    <div class="story-highlights">
                        <div class="story-highlight">
                            <div class="sh-icon">🌱</div>
                            <div>
                                <div class="sh-title">Bootstrapped Growth</div>
                                <div class="sh-desc">From a handful of schools to a nationwide presence — driven almost
                                    entirely by word-of-mouth and product quality.</div>
                            </div>
                        </div>
                        <div class="story-highlight">
                            <div class="sh-icon">🇮🇳</div>
                            <div>
                                <div class="sh-title">Made in India</div>
                                <div class="sh-desc">Designed from the ground up by a team that understands the
                                    day-to-day realities of Indian schools.</div>
                            </div>
                        </div>
                        <div class="story-highlight">
                            <div class="sh-icon">💡</div>
                            <div>
                                <div class="sh-title">Innovation-First</div>
                                <div class="sh-desc">New features, updates, and improvements released regularly —
                                    included in every plan at no extra cost.</div>
                            </div>
                        </div>
                        <div class="story-highlight">
                            <div class="sh-icon">🤝</div>
                            <div>
                                <div class="sh-title">Community-Driven</div>
                                <div class="sh-desc">Every major feature is shaped in collaboration with our network of
                                    partner schools.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════════ WHAT WE DO SECTION ══════════════════ -->
    <section class="section"
        style="background:linear-gradient(135deg,var(--primary-faint),var(--secondary-faint));border-top:1px solid var(--border2);border-bottom:1px solid var(--border2);">
        <div style="max-width:1060px;margin:0 auto;">
            <div class="reveal" style="text-align:center;margin-bottom:16px;">
                <div class="section-tag tag-pink">What We Do</div>
                <h2 class="section-title" style="margin-top:16px;">SUPERLMS <span
                        class="gradient-text">Streamlines</span> Everything</h2>
            </div>
            <div class="wd-grid stagger">
                <div class="wd-card">
                    <div class="wd-icon" style="background:linear-gradient(135deg,#DB57B2,#6F56FE);">📊</div>
                    <div class="wd-title">Streamlines Academic Management</div>
                    <div class="wd-desc">Attendance, timetables, fee management, and note-sharing, all in one unified
                        platform — saving administrators several hours of manual work every day.</div>
                </div>
                <div class="wd-card">
                    <div class="wd-icon" style="background:linear-gradient(135deg,#E878C4,#B83D92);">🌟</div>
                    <div class="wd-title">Enhances the Learning Experience</div>
                    <div class="wd-desc">Real-time progress tracking, digital resource sharing, and interactive
                        assessment tools that help both teachers and students engage more effectively.</div>
                </div>
                <div class="wd-card">
                    <div class="wd-icon" style="background:linear-gradient(135deg,#6F56FE,#5540D4);">💡</div>
                    <div class="wd-title">Keeps Costs Affordable</div>
                    <div class="wd-desc">Flexible, transparent pricing designed to make advanced school-management
                        features available to institutions of every size and budget.</div>
                </div>
                <div class="wd-card">
                    <div class="wd-icon" style="background:linear-gradient(135deg,#DB57B2,#6F56FE);">🔒</div>
                    <div class="wd-title">Secure &amp; Reliable Infrastructure</div>
                    <div class="wd-desc">Enterprise-grade security, daily backups, SSL encryption, and data handling
                        aligned with Indian data protection law keep your data always safe.</div>
                </div>
                <div class="wd-card">
                    <div class="wd-icon" style="background:linear-gradient(135deg,#E878C4,#B83D92);">📱</div>
                    <div class="wd-title">Multi-Platform Access</div>
                    <div class="wd-desc">Dedicated Android and iOS apps so students, teachers, and parents stay
                        connected anytime — even in low-bandwidth areas.</div>
                </div>
                <div class="wd-card">
                    <div class="wd-icon" style="background:linear-gradient(135deg,#6F56FE,#5540D4);">🤝</div>
                    <div class="wd-title">Dedicated Onboarding &amp; Support</div>
                    <div class="wd-desc">Every school is assigned an onboarding specialist, with fast, responsive
                        support throughout their journey on the platform.</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════════ WORKING MODEL SECTION ══════════════════ -->
    <section class="section" style="background:#fff;">
        <div style="max-width:1060px;margin:0 auto;">
            <div class="reveal" style="text-align:center;">
                <div class="section-tag tag-violet">How We Operate</div>
                <h2 class="section-title" style="margin-top:16px;">Our <span class="gradient-text">Working
                        Model</span></h2>
                <p class="section-subtitle" style="max-width:900px;margin:0 auto;">A transparent, school-first approach
                    that ensures every institution gets maximum value from day one. From the very first conversation to
                    going live and well beyond, we personally handle onboarding, data migration, staff training, and
                    day-to-day support — so your team is never left figuring things out alone. We begin by understanding
                    how your school already works, then configure SUPERLMS around your real classes, subjects, fee
                    structures, and academic calendar instead of forcing you into a rigid template. Every step is simple,
                    predictable, and completely stress-free, and our team stays closely involved long after go-live to
                    make sure the platform keeps delivering value as your school grows — with no hidden costs or surprises
                    along the way.</p>
            </div>
            <div class="wmodel-grid stagger">
                <div class="card-wmodel">
                    <div class="wmodel-num">01</div>
                    <div style="font-size:28px;margin-bottom:14px;">🤝</div>
                    <div
                        style="font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--text);margin-bottom:10px;">
                        School Onboarding</div>
                    <div style="font-size:13px;color:var(--text3);line-height:1.75;">We begin with a free needs
                        assessment call. Our team then configures the platform with your school's specific classes,
                        subjects, academic year, branding, and user roles — within 3–5 business days.</div>
                </div>
                <div class="card-wmodel">
                    <div class="wmodel-num">02</div>
                    <div style="font-size:28px;margin-bottom:14px;">🔄</div>
                    <div
                        style="font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--text);margin-bottom:10px;">
                        Data Migration</div>
                    <div style="font-size:13px;color:var(--text3);line-height:1.75;">Existing student records, staff
                        information, fee structures, and historical data are migrated securely and verified before
                        go-live — with an emphasis on zero data loss.</div>
                </div>
                <div class="card-wmodel">
                    <div class="wmodel-num">03</div>
                    <div style="font-size:28px;margin-bottom:14px;">👥</div>
                    <div
                        style="font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--text);margin-bottom:10px;">
                        Staff &amp; User Training</div>
                    <div style="font-size:13px;color:var(--text3);line-height:1.75;">We conduct live virtual training
                        for administrators, teachers, and parents. Custom guides, video tutorials, and a dedicated
                        WhatsApp support group ensure everyone is ready.</div>
                </div>
                <div class="card-wmodel">
                    <div class="wmodel-num">04</div>
                    <div style="font-size:28px;margin-bottom:14px;">🚀</div>
                    <div
                        style="font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--text);margin-bottom:10px;">
                        Go-Live &amp; Monitor</div>
                    <div style="font-size:13px;color:var(--text3);line-height:1.75;">Your school goes live with full
                        SUPERLMS access. Our team actively monitors the first 30 days to ensure smooth operation.
                    </div>
                </div>
                <div class="card-wmodel">
                    <div class="wmodel-num">05</div>
                    <div style="font-size:28px;margin-bottom:14px;">📈</div>
                    <div
                        style="font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--text);margin-bottom:10px;">
                        Continuous Updates</div>
                    <div style="font-size:13px;color:var(--text3);line-height:1.75;">Monthly feature releases, security
                        patches, and performance improvements — included in every plan at no additional
                        cost.</div>
                </div>
                <div class="card-wmodel">
                    <div class="wmodel-num">06</div>
                    <div style="font-size:28px;margin-bottom:14px;">📞</div>
                    <div
                        style="font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--text);margin-bottom:10px;">
                        Ongoing Support</div>
                    <div style="font-size:13px;color:var(--text3);line-height:1.75;">Dedicated support available via
                        phone, WhatsApp, email, and in-app chat — with fast response times on business days.</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════════ PLATFORM CAPABILITIES (ROLES) ══════════════════ -->
    <section class="section" style="background:var(--bg3);border-top:1px solid var(--border2);">
        <div style="max-width:1060px;margin:0 auto;">
            <div class="reveal" style="text-align:center;">
                <div class="section-tag tag-dual">Platform Capabilities</div>
                <h2 class="section-title" style="margin-top:16px;">Built for <span class="gradient-text">Every
                        Role</span></h2>
                <p class="section-subtitle" style="max-width:900px;margin:0 auto;">SUPERLMS is designed to serve the
                    unique needs of every stakeholder in a school ecosystem. Administrators get complete control over
                    day-to-day operations and a clear, real-time view of the entire school, teachers get simple tools to
                    manage classes, attendance, and assessments without the paperwork, students get an engaging way to
                    learn, submit work, and track their own progress, and parents stay closely connected to their child's
                    attendance, results, fees, and everyday school updates. Each role gets a tailored experience that
                    surfaces exactly the right features and hides everything they don't need — so everyone, from the
                    principal's office to the parent's phone, works together effortlessly on a single, connected platform
                    that keeps the whole school moving in sync.</p>
            </div>
            <div class="role-grid-2 stagger">
                <!-- Administrators -->
                <div class="role-card" style="background:var(--secondary-faint);border:1px solid var(--border);">
                    <div
                        style="font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:600;color:var(--text);margin-bottom:20px;">
                        🏛️ For Administrators</div>
                    <div style="display:flex;flex-direction:column;gap:9px;">
                        <div style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text2);">
                            <div
                                style="width:16px;height:16px;min-width:16px;background:rgba(34,197,94,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;color:#16A34A;margin-top:1px;">
                                ✓</div>Complete school dashboard with live KPIs
                        </div>
                        <div style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text2);">
                            <div
                                style="width:16px;height:16px;min-width:16px;background:rgba(34,197,94,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;color:#16A34A;margin-top:1px;">
                                ✓</div>Student &amp; teacher record management
                        </div>
                        <div style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text2);">
                            <div
                                style="width:16px;height:16px;min-width:16px;background:rgba(34,197,94,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;color:#16A34A;margin-top:1px;">
                                ✓</div>Fee collection, payroll &amp; financial reports
                        </div>
                        <div style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text2);">
                            <div
                                style="width:16px;height:16px;min-width:16px;background:rgba(34,197,94,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;color:#16A34A;margin-top:1px;">
                                ✓</div>Timetable generation &amp; substitute management
                        </div>
                        <div style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text2);">
                            <div
                                style="width:16px;height:16px;min-width:16px;background:rgba(34,197,94,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;color:#16A34A;margin-top:1px;">
                                ✓</div>Bulk ID card, admit card &amp; report card generation
                        </div>
                        <div style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text2);">
                            <div
                                style="width:16px;height:16px;min-width:16px;background:rgba(34,197,94,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;color:#16A34A;margin-top:1px;">
                                ✓</div>Transport route &amp; library management
                        </div>
                    </div>
                </div>
                <!-- Teachers -->
                <div class="role-card" style="background:var(--primary-faint);border:1px solid var(--border-pink);">
                    <div
                        style="font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:600;color:var(--text);margin-bottom:20px;">
                        👩‍🏫 For Teachers</div>
                    <div style="display:flex;flex-direction:column;gap:9px;">
                        <div style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text2);">
                            <div
                                style="width:16px;height:16px;min-width:16px;background:rgba(34,197,94,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;color:#16A34A;margin-top:1px;">
                                ✓</div>Mark attendance via mobile in seconds
                        </div>
                        <div style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text2);">
                            <div
                                style="width:16px;height:16px;min-width:16px;background:rgba(34,197,94,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;color:#16A34A;margin-top:1px;">
                                ✓</div>Upload notes, videos &amp; study materials
                        </div>
                        <div style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text2);">
                            <div
                                style="width:16px;height:16px;min-width:16px;background:rgba(34,197,94,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;color:#16A34A;margin-top:1px;">
                                ✓</div>Create quizzes, assignments &amp; grade submissions
                        </div>
                        <div style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text2);">
                            <div
                                style="width:16px;height:16px;min-width:16px;background:rgba(34,197,94,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;color:#16A34A;margin-top:1px;">
                                ✓</div>Track student performance per subject
                        </div>
                        <div style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text2);">
                            <div
                                style="width:16px;height:16px;min-width:16px;background:rgba(34,197,94,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;color:#16A34A;margin-top:1px;">
                                ✓</div>Communicate with parents via announcements
                        </div>
                        <div style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text2);">
                            <div
                                style="width:16px;height:16px;min-width:16px;background:rgba(34,197,94,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;color:#16A34A;margin-top:1px;">
                                ✓</div>Manage class-wise seating plans for exams
                        </div>
                    </div>
                </div>
                <!-- Students -->
                <div class="role-card" style="background:var(--secondary-faint);border:1px solid var(--border);">
                    <div
                        style="font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:600;color:var(--text);margin-bottom:20px;">
                        🎓 For Students</div>
                    <div style="display:flex;flex-direction:column;gap:9px;">
                        <div style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text2);">
                            <div
                                style="width:16px;height:16px;min-width:16px;background:rgba(34,197,94,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;color:#16A34A;margin-top:1px;">
                                ✓</div>Access subject notes, books &amp; content anytime
                        </div>
                        <div style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text2);">
                            <div
                                style="width:16px;height:16px;min-width:16px;background:rgba(34,197,94,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;color:#16A34A;margin-top:1px;">
                                ✓</div>View assignments, deadlines &amp; submit digitally
                        </div>
                        <div style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text2);">
                            <div
                                style="width:16px;height:16px;min-width:16px;background:rgba(34,197,94,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;color:#16A34A;margin-top:1px;">
                                ✓</div>Attempt online quizzes with instant results
                        </div>
                        <div style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text2);">
                            <div
                                style="width:16px;height:16px;min-width:16px;background:rgba(34,197,94,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;color:#16A34A;margin-top:1px;">
                                ✓</div>Check own attendance &amp; academic performance
                        </div>
                        <div style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text2);">
                            <div
                                style="width:16px;height:16px;min-width:16px;background:rgba(34,197,94,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;color:#16A34A;margin-top:1px;">
                                ✓</div>Download admit cards &amp; report cards
                        </div>
                        <div style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text2);">
                            <div
                                style="width:16px;height:16px;min-width:16px;background:rgba(34,197,94,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;color:#16A34A;margin-top:1px;">
                                ✓</div>Receive fee receipts &amp; timetable updates
                        </div>
                    </div>
                </div>
                <!-- Parents -->
                <div class="role-card" style="background:var(--primary-faint);border:1px solid var(--border-pink);">
                    <div
                        style="font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:600;color:var(--text);margin-bottom:20px;">
                        👨‍👩‍👧 For Parents</div>
                    <div style="display:flex;flex-direction:column;gap:9px;">
                        <div style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text2);">
                            <div
                                style="width:16px;height:16px;min-width:16px;background:rgba(34,197,94,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;color:#16A34A;margin-top:1px;">
                                ✓</div>Real-time child attendance notifications (SMS/WhatsApp)
                        </div>
                        <div style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text2);">
                            <div
                                style="width:16px;height:16px;min-width:16px;background:rgba(34,197,94,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;color:#16A34A;margin-top:1px;">
                                ✓</div>Track academic progress &amp; grade trends
                        </div>
                        <div style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text2);">
                            <div
                                style="width:16px;height:16px;min-width:16px;background:rgba(34,197,94,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;color:#16A34A;margin-top:1px;">
                                ✓</div>Pay school fees online &amp; download receipts
                        </div>
                        <div style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text2);">
                            <div
                                style="width:16px;height:16px;min-width:16px;background:rgba(34,197,94,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;color:#16A34A;margin-top:1px;">
                                ✓</div>View homework, test scores &amp; report cards
                        </div>
                        <div style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text2);">
                            <div
                                style="width:16px;height:16px;min-width:16px;background:rgba(34,197,94,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;color:#16A34A;margin-top:1px;">
                                ✓</div>Communicate directly with teachers
                        </div>
                        <div style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text2);">
                            <div
                                style="width:16px;height:16px;min-width:16px;background:rgba(34,197,94,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;color:#16A34A;margin-top:1px;">
                                ✓</div>Receive school announcements instantly
                        </div>
                    </div>
                </div>
                <!-- Accounts -->
                <div class="role-card" style="background:var(--secondary-faint);border:1px solid var(--border);">
                    <div class="role-card-title">🧾 For Accounts</div>
                    <div class="role-point"><div class="role-point-check">✓</div>Automated fee invoicing, dues tracking &amp; online collection</div>
                    <div class="role-point"><div class="role-point-check">✓</div>Payroll, salary slips &amp; staff expense management</div>
                    <div class="role-point"><div class="role-point-check">✓</div>Income, expense &amp; fee ledgers with financial reports</div>
                    <div class="role-point"><div class="role-point-check">✓</div>Online payment reconciliation &amp; instant receipts</div>
                    <div class="role-point"><div class="role-point-check">✓</div>Audit-ready accounting &amp; financial exports</div>
                    <div class="role-point"><div class="role-point-check">✓</div>Real-time dues, defaulter &amp; collection dashboards</div>
                </div>
                <!-- Exams Management -->
                <div class="role-card" style="background:var(--primary-faint);border:1px solid var(--border-pink);">
                    <div class="role-card-title">📝 For Exams Management</div>
                    <div class="role-point"><div class="role-point-check">✓</div>Build exam schedules, datesheets &amp; seating arrangements</div>
                    <div class="role-point"><div class="role-point-check">✓</div>Configure grading schemes &amp; marks entry workflows</div>
                    <div class="role-point"><div class="role-point-check">✓</div>Automated result processing &amp; rank calculation</div>
                    <div class="role-point"><div class="role-point-check">✓</div>Auto-generate admit cards, marksheets &amp; report cards in bulk</div>
                    <div class="role-point"><div class="role-point-check">✓</div>Subject &amp; class-wise performance analysis</div>
                    <div class="role-point"><div class="role-point-check">✓</div>Publish results instantly to students &amp; parents</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════════ SECURITY SECTION ══════════════════ -->
    <section class="section" style="background:#fff;">
        <div style="max-width:1060px;margin:0 auto;">
            <div class="reveal" style="text-align:center;margin-bottom:60px;">
                <div class="section-tag tag-dual">Security</div>
                <h2 class="section-title" style="margin-top:16px;">Enterprise-Grade <span
                        class="gradient-text">Security</span></h2>
                <p class="section-subtitle" style="margin:0 auto;">Your data and your students' data is protected with
                    industry-leading security standards.</p>
            </div>
            <div class="security-illus-grid">
                <div style="display:flex;justify-content:center;animation:slideInLeft .75s ease forwards;opacity:0;">
                    <div class="illus-float-slow">
                        <!-- IllusSecurity SVG -->
                        <svg viewBox="0 0 280 260" fill="none" xmlns="http://www.w3.org/2000/svg"
                            style="width:100%;max-width:280px;">
                            <path d="M140 20 L240 60 L240 160 Q240 230 140 250 Q40 230 40 160 L40 60 Z"
                                fill="url(#gs1)" opacity="0.08" />
                            <path d="M140 40 L220 72 L220 160 Q220 215 140 232 Q60 215 60 160 L60 72 Z" fill="white"
                                stroke="#EDE8FF" stroke-width="1.5" />
                            <circle cx="140" cy="130" r="36" fill="url(#gs1)" opacity="0.1" />
                            <circle cx="140" cy="130" r="36" stroke="#6F56FE" stroke-width="1.5" />
                            <rect x="124" y="118" width="32" height="24" rx="6" fill="none"
                                stroke="#6F56FE" stroke-width="2" />
                            <rect x="130" y="108" width="20" height="12" rx="10" fill="none"
                                stroke="#6F56FE" stroke-width="2" />
                            <circle cx="140" cy="130" r="4" fill="#6F56FE" />
                            <rect x="138" y="130" width="4" height="8" rx="2" fill="#6F56FE" />
                            <text x="80" y="85" font-size="9" fill="#7A6EA0" font-weight="500">SSL Encrypted</text>
                            <text x="165" y="85" font-size="9" fill="#7A6EA0" font-weight="500">Daily Backups</text>
                            <text x="90" y="190" font-size="9" fill="#7A6EA0" font-weight="500">DPDP
                                Aligned</text>
                            <circle cx="74" cy="78" r="6" fill="#DB57B2" opacity="0.7" />
                            <circle cx="204" cy="78" r="6" fill="#6F56FE" opacity="0.7" />
                            <circle cx="90" cy="183" r="6" fill="url(#gs1)" opacity="0.8" />
                            <defs>
                                <linearGradient id="gs1" x1="0" y1="0" x2="1"
                                    y2="1">
                                    <stop stop-color="#DB57B2" />
                                    <stop offset="1" stop-color="#6F56FE" />
                                </linearGradient>
                            </defs>
                        </svg>
                    </div>
                </div>
                <div style="animation:slideInRight .9s ease .1s forwards;opacity:0;">
                    <div class="card-slide-right">
                        <div
                            style="width:40px;height:40px;min-width:40px;border-radius:10px;background:var(--secondary-faint);display:flex;align-items:center;justify-content:center;font-size:18px;">
                            🔒</div>
                        <div>
                            <div style="font-weight:600;color:var(--text);margin-bottom:4px;font-size:14px;">256-bit
                                SSL Encryption</div>
                            <div style="font-size:13px;color:var(--text3);line-height:1.6;">All data in transit is
                                encrypted with bank-grade SSL, protecting every login, transaction, and communication.
                            </div>
                        </div>
                    </div>
                    <div class="card-slide-right">
                        <div
                            style="width:40px;height:40px;min-width:40px;border-radius:10px;background:var(--secondary-faint);display:flex;align-items:center;justify-content:center;font-size:18px;">
                            🛡️</div>
                        <div>
                            <div style="font-weight:600;color:var(--text);margin-bottom:4px;font-size:14px;">Role-Based
                                Access Control</div>
                            <div style="font-size:13px;color:var(--text3);line-height:1.6;">Granular permissions ensure
                                each user only sees the data relevant to their role.</div>
                        </div>
                    </div>
                    <div class="card-slide-right">
                        <div
                            style="width:40px;height:40px;min-width:40px;border-radius:10px;background:var(--secondary-faint);display:flex;align-items:center;justify-content:center;font-size:18px;">
                            ☁️</div>
                        <div>
                            <div style="font-weight:600;color:var(--text);margin-bottom:4px;font-size:14px;">Daily
                                Geo-Redundant Backups</div>
                            <div style="font-size:13px;color:var(--text3);line-height:1.6;">Automated daily backups
                                stored in geo-redundant cloud locations, recoverable within minutes.</div>
                        </div>
                    </div>
                    <div class="card-slide-right">
                        <div
                            style="width:40px;height:40px;min-width:40px;border-radius:10px;background:var(--secondary-faint);display:flex;align-items:center;justify-content:center;font-size:18px;">
                            ✅</div>
                        <div>
                            <div style="font-weight:600;color:var(--text);margin-bottom:4px;font-size:14px;">
                                Compliance-Aligned Data Handling</div>
                            <div style="font-size:13px;color:var(--text3);line-height:1.6;">Data handling in keeping
                                with the Indian Information Technology Act, 2000, and the Digital Personal Data
                                Protection Act, 2023.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════════ OUR PLATFORMS SECTION ══════════════════ -->
    <section class="section"
        style="background:linear-gradient(135deg,var(--primary-faint),var(--secondary-faint));border-top:1px solid var(--border2);border-bottom:1px solid var(--border2);">
        <div style="max-width:1060px;margin:0 auto;">
            <div class="reveal" style="text-align:center;">
                <div class="section-tag tag-dual">Our Platforms</div>
                <h2 class="section-title" style="margin-top:16px;">One Company, <span class="gradient-text">Three
                        Connected Platforms</span></h2>
                <p class="section-subtitle" style="margin:0 auto;">SUPERLMS is one of three connected platforms
                    operated by Super Learnings Private Limited, designed to support schools, students, and families
                    end-to-end — beyond the classroom, into the home.</p>
            </div>
            <div class="wmodel-grid stagger">
                <div class="card-wmodel">
                    <div style="font-size:28px;margin-bottom:14px;">🏫</div>
                    <div
                        style="font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--text);margin-bottom:10px;">
                        SUPERLMS</div>
                    <div style="font-size:13px;color:var(--text3);line-height:1.75;">Our core school management system
                        for attendance, fees, timetables, academics, and communication between administrators,
                        teachers, students, and parents.</div>
                </div>
                <div class="card-wmodel">
                    <div style="font-size:28px;margin-bottom:14px;">📚</div>
                    <div
                        style="font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--text);margin-bottom:10px;">
                        Edyone</div>
                    <div style="font-size:13px;color:var(--text3);line-height:1.75;">A companion EdTech platform
                        offering academic and competitive-exam courses, interactive lessons, practice tests, and smart
                        learning tools for students.</div>
                </div>
                <div class="card-wmodel">
                    <div style="font-size:28px;margin-bottom:14px;">🛡️</div>
                    <div
                        style="font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--text);margin-bottom:10px;">
                        Super Safe</div>
                    <div style="font-size:13px;color:var(--text3);line-height:1.75;">A parental-control application
                        focused on screen-time monitoring, content filtering, and online safety for children.</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ══════════════════ TEAM SECTION ══════════════════ -->
    <section class="section" style="background:var(--bg3);">
        <div style="max-width:1060px;margin:0 auto;text-align:center;">
            <div class="reveal">
                <div class="section-tag tag-violet" style="display:inline-flex;">Our Team</div>
                <h2 class="section-title" style="margin-top:16px;">Built by Entrepreneurs, <span
                        class="gradient-text">For Schools &amp; Students</span></h2>
                <p class="section-subtitle" style="max-width:900px;margin:16px auto 36px;">SUPERLMS is built by a team of educators,
                    engineers, and designers based in Aligarh, Uttar Pradesh — the same team that speaks directly with
                    teachers, principals, and parents to understand what schools actually need, rather than guessing
                    from a distance.</p>
            </div>

            <!-- Founder Spotlight (single, wide feature card — matches homepage) -->
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
                    <p class="founder-bio">Annant founded SUPERLMS with a clear goal: to make affordable, powerful
                        school-management technology accessible to every institution in India, from large city schools
                        to small towns. He continues to work closely with educators on the ground, shaping their
                        everyday challenges into practical features used by thousands of students, teachers, and
                        parents.</p>
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

            <!-- Join Our Team CTA -->
            <div class="join-team-box reveal">
                <div style="font-size:32px;margin-bottom:12px;">🤝</div>
                <div
                    style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;color:var(--text);margin-bottom:12px;">
                    Join Our Growing Team</div>
                <p style="color:var(--text3);font-size:14px;line-height:1.75;margin-bottom:24px;">We are actively
                    growing our team of educators, engineers, and designers who want to work on technology that has a
                    direct, visible impact on schools and students across the country. Whether you're a school exploring
                    a new management system, a parent curious about how SUPERLMS works, or someone interested in joining
                    our team — we'd love to hear from you.</p>
                <div
                    style="display:flex;flex-wrap:wrap;justify-content:center;gap:10px 24px;margin-bottom:24px;font-size:13px;color:var(--text3);">
                    <a href="mailto:support@superlms.in"
                        style="color:var(--violet);text-decoration:none;font-weight:600;">📧 support@superlms.in</a>
                    <a href="tel:+919084748563"
                        style="color:var(--violet);text-decoration:none;font-weight:600;">📱 +91 90847 48563</a>
                    <span>📍 Office No. 02, Braj Vihar Colony, Jattari Khair Aligarh, Uttar Pradesh India -202137</span>
                </div>
                <a href="{{ url('web/contact') }}" class="btn btn-primary btn-lg">Get in Touch →</a>
            </div>

        </div>
    </section>



    @include('components.website.app-section')

    @include('components.website.footer')


    <!-- ══════════════════ SCROLL REVEAL JS ══════════════════ -->
    <script>
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.05, rootMargin: '0px 0px -20px 0px' });

        document.querySelectorAll('.reveal, .stagger, .stagger-left').forEach(function(el) {
            if (el.getBoundingClientRect().top < window.innerHeight) {
                el.classList.add('visible');
            } else {
                observer.observe(el);
            }
        });
    </script>
</body>

</html>
