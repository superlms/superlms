<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Contact Us — SUPERLMS</title>
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
                transform: translateY(32px);
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
                transform: translateX(-60px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
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

        /* ── HEADER HERO ── */
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
            pointer-events: none;
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
            pointer-events: none;
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
            max-width: 680px;
            margin: 0 auto;
            font-weight: 400;
        }

        /* ── CONTACT SECTION ── */
        .section {
            padding: 80px 6%;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1.6fr;
            gap: 60px;
            max-width: 1060px;
            margin: 0 auto;
        }

        /* Info cards */
        .info-card {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 14px;
            padding: 18px 20px;
            background: #fff;
            border: 1px solid var(--border2);
            border-radius: var(--radius-sm);
            transition: all .3s;
            animation: slideInLeft .6s ease forwards;
            opacity: 0;
        }

        .info-card:nth-child(1) {
            animation-delay: .1s;
        }

        .info-card:nth-child(2) {
            animation-delay: .2s;
        }

        .info-card:nth-child(3) {
            animation-delay: .3s;
        }

        .info-card:hover {
            border-color: var(--border);
            background: var(--bg3);
            transform: translateX(6px);
        }

        .info-icon {
            width: 40px;
            height: 40px;
            min-width: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .info-label {
            font-size: 11px;
            color: var(--text3);
            margin-bottom: 3px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .8px;
        }

        .info-value {
            font-size: 14px;
            color: var(--text);
            font-weight: 500;
        }

        .left-col-tag {
            margin-bottom: 24px;
        }

        /* Contact form card */
        .contact-form {
            background: #fff;
            border: 1px solid var(--border2);
            border-radius: var(--radius-lg);
            padding: 40px;
            box-shadow: var(--shadow3);
            animation: slideInRight .7s ease .1s forwards;
            opacity: 0;
        }

        .form-heading {
            font-family: 'Cormorant Garamond', serif;
            font-size: 24px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 24px;
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
        .form-textarea,
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

        .form-input::placeholder,
        .form-textarea::placeholder {
            color: var(--text4);
        }

        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            border-color: var(--violet);
            box-shadow: 0 0 0 3px rgba(111, 86, 254, 0.1);
            background: #fff;
        }

        .form-textarea {
            min-height: 130px;
            resize: vertical;
        }

        .form-select {
            -webkit-appearance: none;
            cursor: pointer;
        }

        .btn-submit {
            width: 100%;
            padding: 14px 32px;
            font-size: 15px;
            border-radius: 11px;
            background: var(--grad1);
            color: #fff;
            border: none;
            font-family: 'DM Sans', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: all .3s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 4px 16px rgba(111, 86, 254, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(111, 86, 254, 0.45);
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
            .contact-grid {
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

            .contact-form {
                padding: 24px 18px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .page-header {
                padding: 110px 5% 60px;
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
        <div class="section-tag tag-dual">Contact Us</div>
        <h1 class="section-title">Your Doubt, <span class="gradient-text">Our Priority</span></h1>
        <p class="section-subtitle">Have a question or concern? We're always ready to help. No issue is too small, and
            every query receives our full attention. Our dedicated support team listens carefully, responds quickly, and
            provides clear, dependable solutions—so you can move forward with confidence and complete peace of mind.</p>
    </div>

    <!-- CONTACT SECTION -->
    <section class="section" style="background:#fff;">
        <div class="contact-grid">

            <!-- LEFT: Info -->
            <div>
                <div class="section-tag tag-violet left-col-tag">Get In Touch</div>

                <div class="info-card">
                    <div class="info-icon" style="background:var(--secondary-faint);">📧</div>
                    <div>
                        <div class="info-label">Email Address</div>
                        <div class="info-value">support@superlms.in</div>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-icon" style="background:var(--primary-faint);">📱</div>
                    <div>
                        <div class="info-label">Phone / WhatsApp</div>
                        <div class="info-value">+91 9084748563</div>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-icon" style="background:var(--secondary-faint);">📍</div>
                    <div>
                        <div class="info-label">Office Address</div>
                        <div class="info-value">Floor 2, Braj Vihar Colony, Main Road Jattari, Aligarh, UP 202137</div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Form -->
            <div class="contact-form">
                <div class="form-heading">Send Us a Message</div>
                <form id="contactForm" onsubmit="handleContactSubmit(event)">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Full Name *</label>
                            <input class="form-input" type="text" name="name" placeholder="Your full name"
                                required />
                        </div>
                        <div class="form-group">
                            <label class="form-label">School Name *</label>
                            <input class="form-input" type="text" name="school" placeholder="Your school name"
                                required />
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
                            <input class="form-input" type="email" name="email" placeholder="your@email.com"
                                required pattern="[^@\s]+@[^@\s]+\.[^@\s]+"
                                title="Enter a valid email address" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Subject *</label>
                        <select class="form-select" name="subject" required>
                            <option value="">Select a subject</option>
                            <option>General Inquiry</option>
                            <option>Request Demo</option>
                            <option>Pricing Information</option>
                            <option>Technical Support</option>
                            <option>Partnership</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Message *</label>
                        <textarea class="form-textarea" name="message" placeholder="Tell us how we can help..." required></textarea>
                    </div>
                    <button type="submit" class="btn-submit">Send Message</button>
                </form>
            </div>
        </div>
    </section>

    <!-- TOAST -->
    <div class="toast hidden" id="toast">
        <div class="toast-icon">✅</div>
        <div>
            <div class="toast-title">Message Sent!</div>
            <div class="toast-msg">We'll get back to you within 3 business days.</div>
        </div>
    </div>



    @include('components.website.app-section')

    @include('components.website.footer')


    <script>
        async function handleContactSubmit(e) {
            e.preventDefault();
            const form = e.target;
            const btn = form.querySelector('.btn-submit');
            const orig = btn.textContent;
            btn.textContent = 'Sending…';
            btn.disabled = true;

            try {
                const res = await fetch('/api/website/contact', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        full_name: form.name.value,
                        school_name: form.school.value,
                        phone_number: form.phone.value,
                        email: form.email.value,
                        subject: form.subject.value,
                        description: form.message.value,
                    }),
                });
                const json = await res.json();
                if (json.success) {
                    form.reset();
                    showToast(json.message || 'Message sent!', true);
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
            if (titleEl) titleEl.textContent = success ? 'Message Sent!' : 'Error';
            if (msgEl) msgEl.textContent = msg;
            if (iconEl) iconEl.textContent = success ? '✅' : '❌';
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 4500);
        }
    </script>
</body>

</html>
