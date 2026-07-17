<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Features — SUPERLMS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link rel="icon" type="image/png" href="{{ asset('website-image/Group 11525.png') }}">

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

    /* ══════════════════ FEATURES SECTION ══════════════════ */
    .section { padding: 80px 6%; }

    .features-grid {
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
      cursor: default;
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
    .btn-xl { padding: 15px 40px; font-size: 16px; border-radius: 13px; }

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
    @media (max-width: 1024px) { .features-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 768px) {
      .nav-links, .nav-cta { display: none; }
      .hamburger { display: flex; }
      .features-grid { grid-template-columns: 1fr; }
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

  <!-- NAVBAR -->


    @include('components.website.header')


  <!-- PAGE HEADER -->
  <div class="page-header">
    <div class="grid-bg"></div>
    <div class="page-header-content">
      <div class="section-tag tag-dual">Features</div>
      <h1 class="section-title" style="margin-top:16px;">Everything Your School <span class="gradient-text">Needs</span></h1>
      <p class="section-subtitle">Our all-in-one school management solution offers fully integrated modules designed to simplify every aspect of school operations. From admissions, attendance, and academics to communication, finance, and reporting, it streamlines processes, improves efficiency, and enhances collaboration, giving schools a centralized platform to manage daily tasks effectively and effortlessly.</p>
    </div>
  </div>

  <!-- FEATURES GRID -->
  <section class="section" style="background:#fff;">
    <div class="features-grid">

      <!-- 1 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(111,86,254,.1);">📊</div>
        <div class="feature-title">Dashboard</div>
        <div class="feature-desc">Central overview of all school activity — attendance summary, fee status, upcoming exams, notices, and live stats at a glance for every role.</div>
        <span class="feature-tag tag-v">Core</span>
      </div>

      <!-- 3 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(111,86,254,.08);">🏠</div>
        <div class="feature-title">Home Analytics</div>
        <div class="feature-desc">Visual charts and KPIs on your homepage — enrollment trends, monthly fee collection, attendance rates, and exam scores updated in real time.</div>
        <span class="feature-tag tag-v">Analytics</span>
      </div>

      <!-- 4 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(219,87,178,.08);">🏫</div>
        <div class="feature-title">School Profile</div>
        <div class="feature-desc">Complete institutional profile management — school name, logo, address, affiliation board, academic year config, departments, and contact info.</div>
        <span class="feature-tag tag-p">Administration</span>
      </div>

      <!-- 5 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(111,86,254,.1);">👨‍🎓</div>
        <div class="feature-title">Students</div>
        <div class="feature-desc">Complete student profiles with personal info, academic history, attendance record, fee status, uploaded documents, and parent contact details.</div>
        <span class="feature-tag tag-v">Management</span>
      </div>

      <!-- 6 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(219,87,178,.1);">👨‍🏫</div>
        <div class="feature-title">Teachers</div>
        <div class="feature-desc">Staff directory with subject mappings, class assignments, attendance tracking, payroll links, and individual performance reporting.</div>
        <span class="feature-tag tag-p">Management</span>
      </div>

      <!-- 7 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(111,86,254,.08);">💰</div>
        <div class="feature-title">Fee</div>
        <div class="feature-desc">Collect fees online or offline, generate instant receipts, set due dates, apply late fines, manage scholarships, and export detailed financial reports.</div>
        <span class="feature-tag tag-v">Finance</span>
      </div>

      <!-- 8 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(219,87,178,.08);">📞</div>
        <div class="feature-title">Enquiries</div>
        <div class="feature-desc">Track admission enquiries from first contact to enrolment — assign follow-ups, log calls, set reminders, and monitor conversion rates in one place.</div>
        <span class="feature-tag tag-p">Admissions</span>
      </div>

      <!-- 9 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(111,86,254,.1);">📚</div>
        <div class="feature-title">Content</div>
        <div class="feature-desc">Upload and share notes, PDFs, videos, and presentations by subject and class. Students access study materials anytime, anywhere, even offline.</div>
        <span class="feature-tag tag-v">Learning</span>
      </div>

      <!-- 10 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(219,87,178,.1);">📖</div>
        <div class="feature-title">Syllabus</div>
        <div class="feature-desc">Define and publish the syllabus for each class and subject. Track chapter-wise completion progress and align content delivery accordingly.</div>
        <span class="feature-tag tag-p">Learning</span>
      </div>

      <!-- 11 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(111,86,254,.08);">🗓️</div>
        <div class="feature-title">Time Table</div>
        <div class="feature-desc">Auto-generate conflict-free timetables with an AI-assisted engine. Drag-and-drop editor, substitute teacher assignment, and instant sharing.</div>
        <span class="feature-tag tag-v">Scheduling</span>
      </div>

      <!-- 12 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(219,87,178,.08);">📦</div>
        <div class="feature-title">Homework</div>
        <div class="feature-desc">Assign homework with due dates and file attachments. Students submit digitally; teachers review, annotate, and grade submissions in one place.</div>
        <span class="feature-tag tag-p">Learning</span>
      </div>

      <!-- 13 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(111,86,254,.1);">✅</div>
        <div class="feature-title">Attendance</div>
        <div class="feature-desc">Mark attendance via mobile, QR code, or biometric integration. Instant SMS and WhatsApp alerts sent to parents automatically on absence.</div>
        <span class="feature-tag tag-v">Core</span>
      </div>

      <!-- 14 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(219,87,178,.1);">🧠</div>
        <div class="feature-title">Quiz</div>
        <div class="feature-desc">Create auto-graded MCQ, true/false, and short-answer quizzes. Timed tests with instant results, detailed analytics, and class leaderboards.</div>
        <span class="feature-tag tag-p">Assessment</span>
      </div>

      <!-- 15 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(111,86,254,.08);">📈</div>
        <div class="feature-title">Performance</div>
        <div class="feature-desc">Subject-wise marks, grade trends, class rank comparisons, and detailed analytics dashboards for students, teachers, parents, and administrators.</div>
        <span class="feature-tag tag-v">Analytics</span>
      </div>

      <!-- 16 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(219,87,178,.08);">🏛️</div>
        <div class="feature-title">Library</div>
        <div class="feature-desc">Manage physical and digital library — catalog books, track issue and return, set due dates, send overdue reminders, and manage fines automatically.</div>
        <span class="feature-tag tag-p">Resources</span>
      </div>

      <!-- 17 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(111,86,254,.1);">📗</div>
        <div class="feature-title">Book</div>
        <div class="feature-desc">Digital book repository for students. Browse, borrow, and read e-books subject-wise directly within the app, with reading progress tracking.</div>
        <span class="feature-tag tag-v">Resources</span>
      </div>

      <!-- 18 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(219,87,178,.1);">📢</div>
        <div class="feature-title">Announcement</div>
        <div class="feature-desc">Broadcast notices to specific classes, roles, or the entire school instantly via app notification, SMS, email, or WhatsApp with scheduled delivery.</div>
        <span class="feature-tag tag-p">Communication</span>
      </div>

      <!-- 19 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(111,86,254,.08);">🔄</div>
        <div class="feature-title">Arrangement</div>
        <div class="feature-desc">Manage substitute teacher arrangements when staff are absent — auto-suggest replacements based on availability and notify affected classes instantly.</div>
        <span class="feature-tag tag-v">Management</span>
      </div>

      <!-- 20 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(219,87,178,.08);">🪑</div>
        <div class="feature-title">Seating Plan</div>
        <div class="feature-desc">Design and publish exam hall seating arrangements visually. Print-ready or share digitally with students and invigilators before the exam.</div>
        <span class="feature-tag tag-p">Exams</span>
      </div>

      <!-- 21 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(111,86,254,.1);">📃</div>
        <div class="feature-title">Exam Copy</div>
        <div class="feature-desc">Upload scanned or digital answer sheets to the platform. Teachers annotate, mark, and return evaluated copies to students digitally.</div>
        <span class="feature-tag tag-v">Exams</span>
      </div>

      <!-- 22 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(219,87,178,.1);">📋</div>
        <div class="feature-title">Report Card</div>
        <div class="feature-desc">Auto-generate formatted report cards with subject marks, grades, attendance, teacher remarks, and school branding. Share as PDF or print in bulk.</div>
        <span class="feature-tag tag-p">Assessment</span>
      </div>

      <!-- 23 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(111,86,254,.08);">📅</div>
        <div class="feature-title">Calendar</div>
        <div class="feature-desc">School-wide academic calendar with holidays, exam schedules, events, PTMs, and important dates. One-click sync to Google or personal calendars.</div>
        <span class="feature-tag tag-v">Planning</span>
      </div>

      <!-- 24 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(219,87,178,.08);">🪪</div>
        <div class="feature-title">ID Card</div>
        <div class="feature-desc">Design and bulk-generate student and staff ID cards with photo, barcode, and school branding. Export print-ready PDFs for the entire school in seconds.</div>
        <span class="feature-tag tag-p">Administration</span>
      </div>

      <!-- 25 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(111,86,254,.1);">📄</div>
        <div class="feature-title">Admit Card</div>
        <div class="feature-desc">Auto-generate exam admit cards with student details, roll number, exam schedule, photo, and school seal. Distribute digitally or allow self-download.</div>
        <span class="feature-tag tag-v">Exams</span>
      </div>

      <!-- 26 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(219,87,178,.1);">📜</div>
        <div class="feature-title">Rules &amp; Regulation</div>
        <div class="feature-desc">Publish and manage school rules, code of conduct, disciplinary policies, and guidelines. Instantly accessible to all users within the app at any time.</div>
        <span class="feature-tag tag-p">Administration</span>
      </div>

      <!-- 27 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(111,86,254,.08);">💼</div>
        <div class="feature-title">Payroll</div>
        <div class="feature-desc">Manage staff salaries, allowances, deductions, and PF/ESI contributions. Generate payslips and export monthly payroll reports for accounts.</div>
        <span class="feature-tag tag-v">Finance</span>
      </div>

      <!-- 28 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(219,87,178,.08);">🚌</div>
        <div class="feature-title">Transportation</div>
        <div class="feature-desc">Manage bus routes, stops, and assigned students. Track vehicles, collect transport fees, send delay alerts, and notify parents in real time.</div>
        <span class="feature-tag tag-p">Operations</span>
      </div>

      <!-- 29 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(111,86,254,.1);">🎓</div>
        <div class="feature-title">TC &amp; Certificate</div>
        <div class="feature-desc">Issue Transfer Certificates, bonafide letters, and custom achievement certificates digitally. QR-code verified and tamper-proof for official use.</div>
        <span class="feature-tag tag-v">Documents</span>
      </div>

      <!-- 30 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(219,87,178,.1);">⚙️</div>
        <div class="feature-title">Account Settings</div>
        <div class="feature-desc">Manage personal profile, notification preferences, password, linked devices, and language settings. Fully role-specific configuration for every user type.</div>
        <span class="feature-tag tag-p">Settings</span>
      </div>

      <!-- 31 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(111,86,254,.08);">📞</div>
        <div class="feature-title">Contact Admin</div>
        <div class="feature-desc">Direct in-app messaging channel between users and school administration. Raise queries, submit complaints, get support, and track issue resolution.</div>
        <span class="feature-tag tag-v">Support</span>
      </div>

      <!-- 32 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(219,87,178,.08);">ℹ️</div>
        <div class="feature-title">About App</div>
        <div class="feature-desc">Version information, release notes, developer contact details, and full platform documentation — always accessible within the app for all users.</div>
        <span class="feature-tag tag-p">Info</span>
      </div>

      <!-- 33 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(111,86,254,.1);">⭐</div>
        <div class="feature-title">Rate LMS</div>
        <div class="feature-desc">Students, teachers, and parents can rate their SUPERLMS experience. Feedback is aggregated and used to continuously improve the platform.</div>
        <span class="feature-tag tag-v">Feedback</span>
      </div>

      <!-- 34 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(219,87,178,.1);">📑</div>
        <div class="feature-title">Terms of Use</div>
        <div class="feature-desc">In-app access to the Terms of Service, Privacy Policy, and data usage agreements — always current and transparent for every user on the platform.</div>
        <span class="feature-tag tag-p">Legal</span>
      </div>

      <!-- 35 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(111,86,254,.08);">📧</div>
        <div class="feature-title">Automated Emails</div>
        <div class="feature-desc">Trigger automated email alerts for fee dues, exam schedules, attendance summaries, assignment deadlines, and announcements — no manual effort needed.</div>
        <span class="feature-tag tag-v">Automation</span>
      </div>

      <!-- 36 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(219,87,178,.08);">🖥️</div>
        <div class="feature-title">User Friendly Interface</div>
        <div class="feature-desc">Clean, intuitive design built for all age groups and tech comfort levels. Consistent experience across web, iOS, and Android with minimal learning curve.</div>
        <span class="feature-tag tag-p">Design</span>
      </div>

      <!-- 37 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(111,86,254,.1);">🏷️</div>
        <div class="feature-title">Standard</div>
        <div class="feature-desc">Set up classes, sections, and streams once — the entire platform organises itself around your academic structure automatically.</div>
        <span class="feature-tag tag-v">Administration</span>
      </div>

      <!-- 38 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(219,87,178,.1);">📒</div>
        <div class="feature-title">Ledger</div>
        <div class="feature-desc">A crystal-clear book of every rupee in and out — balanced, searchable, and always audit-ready for accounts and management.</div>
        <span class="feature-tag tag-p">Finance</span>
      </div>

      <!-- 39 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(111,86,254,.08);">💳</div>
        <div class="feature-title">Credit</div>
        <div class="feature-desc">Manage wallet balances and top-ups that keep premium services and communication features running smoothly, with a clear transaction trail.</div>
        <span class="feature-tag tag-v">Finance</span>
      </div>

      <!-- 40 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(219,87,178,.08);">📝</div>
        <div class="feature-title">Exam</div>
        <div class="feature-desc">Plan exams end to end — schedules, subjects, marks, and grades, all beautifully organised and ready to publish in a click.</div>
        <span class="feature-tag tag-p">Exams</span>
      </div>

      <!-- 41 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(111,86,254,.1);">👥</div>
        <div class="feature-title">Users</div>
        <div class="feature-desc">Add staff and assign roles with pinpoint permissions, so every user sees exactly what they should — nothing more, nothing less.</div>
        <span class="feature-tag tag-v">Administration</span>
      </div>

      <!-- 42 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(219,87,178,.1);">🆕</div>
        <div class="feature-title">Admissions</div>
        <div class="feature-desc">Run the entire admission journey online — from application and document upload to approval and enrolment — in one smooth, paperless flow.</div>
        <span class="feature-tag tag-p">Admissions</span>
      </div>

      <!-- 43 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(111,86,254,.08);">🗂️</div>
        <div class="feature-title">Lists</div>
        <div class="feature-desc">Generate any student or staff list you need, filtered exactly how you want it, and export it print-ready in a single click.</div>
        <span class="feature-tag tag-v">Reports</span>
      </div>

      <!-- 44 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(219,87,178,.08);">📑</div>
        <div class="feature-title">Terms &amp; Conditions</div>
        <div class="feature-desc">The full terms of service, kept transparent and always up to date, accessible to every user right inside the app.</div>
        <span class="feature-tag tag-p">Legal</span>
      </div>

      <!-- 45 -->
      <div class="feature-card">
        <div class="feature-icon-wrap" style="background:rgba(111,86,254,.1);">🔒</div>
        <div class="feature-title">Privacy Policy</div>
        <div class="feature-desc">Clear, honest data practices your whole school community can trust — always current and available on demand.</div>
        <span class="feature-tag tag-v">Legal</span>
      </div>

    </div>
  </section>

  <!-- CTA SECTION -->
  <section class="cta-section">
    <div class="cta-bg"></div>
    <div class="cta-card">
      <!-- Emoji strip -->
      <div style="display:flex;justify-content:center;gap:16px;margin-bottom:28px;flex-wrap:wrap;">
        <span style="font-size:28px;">🏫</span>
        <span style="font-size:28px;">📚</span>
        <span style="font-size:28px;">🎓</span>
        <span style="font-size:28px;">✅</span>
        <span style="font-size:28px;">🚀</span>
      </div>
      <div class="section-tag tag-dual" style="margin-bottom:20px;">Get Started</div>
      <h2 class="cta-title">Ready to Experience These <span class="gradient-text">Features?</span></h2>
      <p class="cta-desc">Experience all features firsthand! Book a free demo to explore how our platform works with your school's data, showcasing seamless management of academics, attendance, communication, finance, and more.</p>
      <div class="cta-actions">
        <a href="{{ url('web/demo') }}" class="btn btn-primary btn-xl">Book Free Demo</a>
        <a href="{{ url('web/pricing') }}" class="btn btn-outline btn-xl">View Pricing</a>
      </div>
    </div>
  </section>



    @include('components.website.app-section')

    @include('components.website.footer')


</body>
</html>
