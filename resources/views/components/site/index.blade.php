@extends('components.site.layout')

@section('title', "India's #1 School Management Platform")

@php
    $features = [
        ['t' => 'Smart Analytics', 'tag' => 'Analytics', 'ic' => 'chart', 'm' => 'analytics', 'p' => 'mint',
         'd' => 'Real-time performance tracking, subject-wise trend analysis, attendance heatmaps and detailed admin, teacher & student reports with exportable dashboards.'],
        ['t' => 'Attendance Management', 'tag' => 'Automation', 'ic' => 'calendar-check', 'm' => 'attendance', 'p' => 'blush',
         'd' => 'Mark attendance via mobile, QR code or biometric. Instant SMS & WhatsApp alerts sent automatically to parents the moment a student is absent.'],
        ['t' => 'Timetable Scheduling', 'tag' => 'Scheduling', 'ic' => 'calendar', 'm' => 'timetable', 'p' => 'sky',
         'd' => 'AI-assisted, conflict-free timetable generation with drag-and-drop editing. Handles substitute teacher assignments and distributes schedules instantly.'],
        ['t' => 'Fee Management', 'tag' => 'Finance', 'ic' => 'wallet', 'm' => 'fee', 'p' => 'sand',
         'd' => 'Online & offline fee collection, late fine automation, scholarship management, instant receipt generation and comprehensive financial reporting.'],
        ['t' => 'Assignments & Quizzes', 'tag' => 'Assessment', 'ic' => 'pen', 'm' => 'quiz', 'p' => 'lilac',
         'd' => 'Create, distribute, and auto-grade assignments and online tests (MCQ, true/false, short answers). Instant results, leaderboards, and performance analytics.'],
        ['t' => 'School Administration', 'tag' => 'Administration', 'ic' => 'school', 'm' => 'idcards', 'p' => 'sage',
         'd' => 'Complete school profile, student & staff management, ID card/admit card bulk generation, library, payroll, transport routes and rules & regulations — all in one place.'],
        ['t' => 'Digital Content & Library', 'tag' => 'Learning', 'ic' => 'library', 'm' => 'content', 'p' => 'sky',
         'd' => 'Upload chapters, notes, videos and PDFs subject-wise. Students access a rich digital library anytime, anywhere, on any device — online or offline.'],
        ['t' => 'Communication & Alerts', 'tag' => 'Engagement', 'ic' => 'message', 'm' => 'alerts', 'p' => 'blush',
         'd' => 'Keep parents, teachers and students connected with announcements, push notifications, homework alerts and instant SMS & WhatsApp updates.'],
        ['t' => 'Report Cards & Certificates', 'tag' => 'Records', 'ic' => 'award', 'm' => 'reportcard', 'p' => 'mint',
         'd' => 'Auto-generated report cards, term results, transfer & bonafide certificates and ID cards — designed, branded and ready to print in one click.'],
    ];

    $roles = [
        ['for' => 'Administrators', 'ic' => 'landmark', 'pts' => ['Complete school dashboard with live KPIs', 'Student & teacher record management', 'Fee collection, payroll & financial reports', 'Timetable generation & substitute management', 'Bulk ID card, admit card & report card generation', 'Transport route & library management']],
        ['for' => 'Teachers', 'ic' => 'teacher', 'pts' => ['Mark attendance via mobile in seconds', 'Upload notes, videos & study materials', 'Create quizzes, assignments & grade them', 'Track student performance per subject', 'Communicate with parents via announcements', 'Manage class-wise seating plans for exams']],
        ['for' => 'Students', 'ic' => 'cap', 'pts' => ['Access subject notes, books & content anytime', 'View assignments, deadlines & submit digitally', 'Attempt online quizzes with instant results', 'Check own attendance & academic performance', 'Download admit cards & report cards', 'Receive fee receipts & timetable updates']],
        ['for' => 'Parents', 'ic' => 'family', 'pts' => ['Real-time child attendance notifications (SMS/WhatsApp)', 'Track academic progress & grade trends', 'Pay school fees online & download receipts', 'View homework, test scores & report cards', 'Communicate directly with teachers', 'Receive school announcements instantly']],
        ['for' => 'Accounts', 'ic' => 'receipt', 'pts' => ['Automated fee invoicing, dues tracking & online collection', 'Payroll, salary slips & staff expense management', 'Income, expense & fee ledgers with financial reports', 'Online payment reconciliation & instant receipts', 'Export GST-ready accounting & audit data', 'Real-time dues, defaulter & collection dashboards']],
        ['for' => 'Exams Management', 'ic' => 'clipboard', 'pts' => ['Build exam schedules, datesheets & seating arrangements', 'Configure grading schemes & marks entry workflows', 'Automated result processing & rank calculation', 'Auto-generate admit cards, marksheets & report cards in bulk', 'Subject & class-wise performance analysis', 'Publish results instantly to students & parents']],
    ];

    $faqs = [
        ['c' => 'General', 'q' => 'What is SUPERLMS, and who is it designed for?', 'a' => "SUPERLMS is a comprehensive Learning Management System for schools and educational institutions that streamlines teaching, learning, and administrative tasks like attendance, fees, timetables, and assessments. It's designed for students, teachers, and school administrators."],
        ['c' => 'Getting Started', 'q' => 'How easy is it to get started with SUPERLMS?', 'a' => 'Getting started with SUPERLMS is straightforward — schools can begin by signing up or requesting a demo, then set up user roles, dashboards, and key modules without steep technical barriers, making onboarding quick and manageable.'],
        ['c' => 'General', 'q' => 'Can SUPERLMS be accessed on mobile devices?', 'a' => 'Yes — SUPERLMS can be accessed on mobile devices. It offers a mobile-friendly app (including an Android APK) so students and educators can log in, view syllabus and content, submit & view homework, and all school related features and interact with the system conveniently from smartphones and tablets.'],
        ['c' => 'Features', 'q' => 'Does SUPERLMS support attendance and fee management?', 'a' => 'Yes — SUPERLMS includes both attendance tracking and fee management as core features. It lets schools record and monitor student attendance easily and handle tuition fees, payment records, and related administrative tasks within the same platform.'],
        ['c' => 'Features', 'q' => 'Is SUPERLMS customizable to suit specific institutional needs?', 'a' => 'Yes — SUPERLMS offers customizable options so institutions can adapt the system to their specific academic structure, workflows, permissions, and feature use cases to better match their operational needs and preferences.'],
        ['c' => 'Security', 'q' => 'What security measures does SUPERLMS have in place?', 'a' => 'SUPERLMS uses secure cloud hosting and encrypted data storage to protect personal and academic information. It enforces secure user authentication, role-based access controls, and privacy safeguards so only authorised school administrators and users can access sensitive data.'],
        ['c' => 'Features', 'q' => 'Can teachers easily share study materials through SUPERLMS?', 'a' => 'Yes! SUPERLMS allows teachers to easily share study materials like PDFs, videos, presentations, and more directly through the platform. Once uploaded, students receive instant notifications, ensuring timely access. This seamless process enhances learning, keeps students engaged, and simplifies resource management for educators, all within a single centralized system.'],
    ];
@endphp

@section('content')

    {{-- ═══════════════ HERO ═══════════════ --}}
    <section class="hero">
        <div class="container hero-grid">
            <div>
                <a href="{{ url('web/about') }}" class="badge-pill rv">
                    <span class="bp-dot"></span>
                    India's #1 School Management Platform
                    <x-site.icon name="chevron-right" />
                </a>

                <h1 class="display rv" style="--rd:.05s">
                    Engaging, Accessible &amp;
                    <span class="grad">Affordable</span>
                    Learning Management System.
                </h1>

                <p class="lead rv" style="--rd:.1s">
                    SUPERLMS is a complete academic ecosystem for schools and institutions.
                    Smart attendance, automated timetables, fee management, and real-time progress
                    tracking — all in one powerful platform trusted by many schools across India.
                </p>

                <div class="actions rv" style="--rd:.15s">
                    <a href="{{ url('web/demo') }}" class="btn btn-primary btn-lg">Request Free Demo</a>
                    <a href="{{ url('web/features') }}" class="btn btn-outline btn-lg">Explore Features <x-site.icon name="arrow-right" class="ic-arrow" /></a>
                </div>

                <div class="hero-proof rv" style="--rd:.2s">
                    <span class="stars">@for ($i = 0; $i < 5; $i++)<x-site.icon name="star-fill" />@endfor</span>
                    <span class="pf"><b id="heroRating">4.9★</b> Rating</span>
                    <span class="pf"><b id="heroSchools">many</b> Schools</span>
                    <span class="pf"><b id="heroStudents">100K+</b> Students</span>
                    <span class="pf"><b>50+</b> Modules</span>
                </div>
            </div>

            <div class="hero-visual rv rv-r" style="--rd:.15s">
                @include('components.site.mock.hero-dashboard')
            </div>
        </div>
    </section>

    {{-- ═══════════════ PARTNER SCHOOLS (logos from /api/website/schools) ═══════════════ --}}
    <div class="logos" id="logosBand" hidden>
        <div class="marquee" id="marquee"></div>
    </div>

    {{-- ═══════════════ HOW IT WORKS ═══════════════ --}}
    <section class="section">
        <div class="container">
            <div class="head rv">
                <span class="eyebrow">Process</span>
                <h2 class="h2">How It <span class="grad">Works</span></h2>
                <p class="lead">Getting your school onto SUPERLMS is easier than you think. You share your school's data once, our team sets everything up
                    for you, and every student, teacher and administrator gets their own secure account — so classes,
                    attendance, fees and homework start running online from day one. No technical knowledge needed at
                    your end; here's how a school goes live in 3 simple steps.</p>
            </div>

            <div class="pcards">
                <article class="pcard rv" style="--rd:0s">
                    <div class="pcard-stage bg-peach">@include('components.site.mock.minis', ['m' => 'upload'])</div>
                    <div class="pcard-body">
                        <span class="kicker">01</span>
                        <h3>Integrate with School</h3>
                        <p>Connect your school data, configure departments, classes, and subjects. Our
                            team handles the entire migration and setup within 3–5 business days at no extra cost.</p>
                    </div>
                </article>
                <article class="pcard rv" style="--rd:.1s">
                    <div class="pcard-stage bg-sky">@include('components.site.mock.minis', ['m' => 'accounts'])</div>
                    <div class="pcard-body">
                        <span class="kicker">02</span>
                        <h3>Get LMS Account</h3>
                        <p>Every student, teacher, and administrator gets a personalized secure account
                            with role-based access, tailored views, and an intuitive onboarding workflow.</p>
                    </div>
                </article>
                <article class="pcard rv" style="--rd:.2s">
                    <div class="pcard-stage bg-mint">@include('components.site.mock.minis', ['m' => 'learn'])</div>
                    <div class="pcard-body">
                        <span class="kicker">03</span>
                        <h3>Start to Learn</h3>
                        <p>Begin learning instantly with engaging multimedia content, real-time progress
                            tracking, interactive assignments, online quizzes, and expert teacher guidance.</p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    {{-- ═══════════════ STATS ═══════════════ --}}
    <section class="section-tight" style="padding-top:0;">
        <div class="container">
            <div class="stats rv">
                <div class="stat"><div class="stat-n" id="statSchools">many</div><div class="stat-l"><x-site.icon name="school" /> Partnered Schools</div></div>
                <div class="stat"><div class="stat-n" id="statTeachers">500+</div><div class="stat-l"><x-site.icon name="teacher" /> Subject Experts</div></div>
                <div class="stat"><div class="stat-n" id="statStudents">100K+</div><div class="stat-l"><x-site.icon name="cap" /> Online Students</div></div>
                <div class="stat"><div class="stat-n" id="statRating">4.9/5</div><div class="stat-l"><x-site.icon name="star" /> Average Rating</div></div>
            </div>
        </div>
    </section>

    {{-- ═══════════════ DATA-DRIVEN ANALYTICS ═══════════════ --}}
    <section class="section">
        <div class="container">
            <div class="head rv">
                <span class="eyebrow">Live Insights</span>
                <h2 class="h2">Data-Driven <span class="grad">Decisions</span></h2>
                <p class="lead">Get real-time
                    analytics on attendance, academic performance, fee collection, and more. Administrators see the full
                    picture; teachers see their class; parents see their child — all with a single login.</p>
            </div>

            <div class="rv rv-s" style="max-width:1080px;margin:0 auto;">
                @include('components.site.mock.analytics-app')
            </div>

            <div class="pcards four" style="margin-top:56px;">
                <article class="pcard rv">
                    <div class="pcard-stage bg-peach">@include('components.site.mock.minis', ['m' => 'heatmap'])</div>
                    <div class="pcard-body"><h3>Attendance heatmaps &amp; trend analysis</h3></div>
                </article>
                <article class="pcard rv" style="--rd:.08s">
                    <div class="pcard-stage bg-sky">@include('components.site.mock.minis', ['m' => 'rank'])</div>
                    <div class="pcard-body"><h3>Subject-wise performance &amp; rank comparisons</h3></div>
                </article>
                <article class="pcard rv" style="--rd:.16s">
                    <div class="pcard-stage bg-mint">@include('components.site.mock.minis', ['m' => 'forecast'])</div>
                    <div class="pcard-body"><h3>Fee collection dashboards &amp; monthly forecasting</h3></div>
                </article>
                <article class="pcard rv" style="--rd:.24s">
                    <div class="pcard-stage bg-lilac">@include('components.site.mock.minis', ['m' => 'reportgen'])</div>
                    <div class="pcard-body"><h3>Automated report card generation in one click</h3></div>
                </article>
            </div>
        </div>
    </section>

    {{-- ═══════════════ FEATURES (tabs) ═══════════════ --}}
    <section class="section section-soft">
        <div class="container" data-tabs data-auto="7000">
            <div class="head rv">
                <span class="eyebrow">Features</span>
                <h2 class="h2">Everything You Need to <span class="grad">Succeed</span></h2>
                <p class="lead">A complete suite of 50+ modules
                    designed to streamline learning and administration for every role — admissions, attendance, fees,
                    exams, homework, timetables, transport, payroll and much more. One login for administrators,
                    teachers, students and parents, so your whole school runs from a single platform instead of five
                    different apps.</p>
            </div>

            <div class="seg-wrap rv">
                <div class="seg" role="tablist">
                    @foreach ($features as $i => $f)
                        <button type="button" role="tab" data-tab="{{ $i }}">{{ $f['t'] }}<span class="seg-prog"></span></button>
                    @endforeach
                </div>
            </div>

            @foreach ($features as $i => $f)
                <div class="tabpanel split" data-panel="{{ $i }}" role="tabpanel">
                    <div class="split-text">
                        <span class="eyebrow"><x-site.icon :name="$f['ic']" /> {{ $f['tag'] }}</span>
                        <h3 class="h3">{{ $f['t'] }}</h3>
                        <p class="body-l">{{ $f['d'] }}</p>
                        <div class="actions">
                            <a href="{{ url('web/features') }}" class="btn btn-primary">View All 50+ Features <x-site.icon name="arrow-right" class="ic-arrow" /></a>
                            <a href="{{ url('web/demo') }}" class="link-arrow">Request Free Demo <x-site.icon name="chevron-right" /></a>
                        </div>
                    </div>
                    <div class="vpanel {{ $f['p'] }}">
                        @include('components.site.mock.feature', ['m' => $f['m']])
                    </div>
                </div>
            @endforeach

            <p class="center rv" style="margin-top:44px;font-size:15px;color:var(--muted);font-weight:500;">And
                44+ more modules — fully included in every plan</p>
        </div>
    </section>

    {{-- ═══════════════ WHO IT'S FOR ═══════════════ --}}
    <section class="section">
        <div class="container">
            <div class="head rv">
                <span class="eyebrow">Built for Everyone</span>
                <h2 class="h2">One Platform, <span class="grad">Every Role</span></h2>
                <p class="lead">SUPERLMS is built to serve the unique needs of every stakeholder in your
                    school ecosystem — from administrators and teachers to students, parents and the accounts &amp;
                    examination teams. Each role gets a dedicated, role-based dashboard with exactly the tools they need,
                    so everyone works from a single source of truth without juggling spreadsheets, paperwork or
                    disconnected apps.</p>
            </div>

            <div class="grid g3">
                @foreach ($roles as $i => $r)
                    <div class="role rv" style="--rd:{{ ($i % 3) * 0.08 }}s">
                        <div class="role-head">
                            <div><div class="role-for">For</div><div class="role-name">{{ $r['for'] }}</div></div>
                            <span class="role-ico"><x-site.icon :name="$r['ic']" /></span>
                        </div>
                        <ul>
                            @foreach ($r['pts'] as $p)
                                <li><x-site.icon name="check" />{{ $p }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════════ THREE APPS ═══════════════ --}}
    <section class="section section-soft">
        <div class="container">
            <div class="head rv">
                <span class="eyebrow">Available On All Platforms</span>
                <h2 class="h2">One Company, <span class="grad">Three Platforms</span></h2>
                <p class="lead">One company, three powerful platforms — Edyone, SuperLMS, and Super Safe
                    — built to keep your entire school ecosystem connected. Access everything from any device, anytime,
                    anywhere.</p>
            </div>

            <div class="apps">
                <div class="app rv">
                    <span class="pc-flag"></span>
                    <span class="tag tag-gray"><x-site.icon name="smartphone" /> Android</span>
                    <div class="app-logo"><img src="{{ asset('admin-image/c8cd2e7a3c40476d15ae54f52a8565b83bfc20b3.png') }}" alt="Logo"></div>
                    <div class="app-name">Edyone</div>
                    <p>Edyone is a comprehensive edtech platform offering academic and
                        competitive exam courses, interactive lessons, practice tests, performance tracking, and smart
                        learning tools for students.</p>
                    <a href="https://superlms.in" target="_blank" rel="noopener noreferrer" class="btn btn-dark btn-sm"><x-site.icon name="play" /> Coming Soon</a>
                </div>

                <div class="app is-feat rv" style="--rd:.08s">
                    <span class="pc-flag">Download</span>
                    <span class="tag"><x-site.icon name="smartphone" /> Android</span>
                    <div class="app-logo"><img src="{{ \App\Support\Platform::logoUrl() }}" alt="Logo"></div>
                    <div class="app-name">SuperLMS</div>
                    <p>SuperLMS is a complete school management system enabling attendance
                        tracking, fee management, timetables, academic reports, and seamless communication between
                        administrators, teachers, students, and parents.</p>
                    <a href="https://play.google.com/store/apps/details?id=com.edyoneapp&pcampaignid=web_share" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm"><x-site.icon name="download" /> Get it on Google Play</a>
                    <div class="app-meta"><span>⭐ 4.9</span><span>·</span><span>5K+ Downloads</span></div>
                </div>

                <div class="app rv" style="--rd:.16s">
                    <span class="pc-flag"></span>
                    <span class="tag tag-gray"><x-site.icon name="smartphone" /> Android</span>
                    <div class="app-logo"><img src="{{ asset('admin-image/Group 33079.png') }}" alt="Logo"></div>
                    <div class="app-name">Super Safe</div>
                    <p>Super Safe is a powerful parental control app that monitors screen
                        time, filters inappropriate content, tracks online activity, and helps parents ensure their
                        children's digital safety.</p>
                    <a href="https://superlmssafe.in" target="_blank" rel="noopener noreferrer" class="btn btn-dark btn-sm"><x-site.icon name="rocket" /> Coming Soon.</a>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════ PRICING SNAPSHOT ═══════════════ --}}
    <section class="section">
        <div class="container">
            <div class="head rv">
                <span class="eyebrow">Pricing</span>
                <h2 class="h2">Simple, <span class="grad">Transparent</span> Pricing</h2>
                <p class="lead">One simple plan that unlocks all 50+ modules — no per-feature add-ons, no
                    setup charges and absolutely no hidden fees. You pay just ₹250 per user a year, billed annually, with
                    free onboarding, data migration and support included. Need something bigger? Our Enterprise plan is
                    custom-built for multi-campus groups with white-label branding and API access — pricing that finally
                    makes sense for every school, big or small.</p>
            </div>

            <div class="pricecards">
                <div class="pc is-feat rv">
                    <span class="pc-flag">Best Value</span>
                    <div class="pc-name">School Plan</div>
                    <div class="pc-price">₹20<small>.83</small></div>
                    <div class="pc-per">per month/user <strong>(billed annually)</strong></div>
                    <div class="pc-note">= ₹250/year · All 50+ modules</div>
                    <div class="pc-actions"><a href="{{ url('web/pricing') }}" class="btn btn-primary">See Full Plan <x-site.icon name="arrow-right" class="ic-arrow" /></a></div>
                </div>
                <div class="pc rv" style="--rd:.08s">
                    <span class="pc-flag" style="color:var(--muted);">&nbsp;</span>
                    <div class="pc-name">Enterprise</div>
                    <div class="pc-price">Custom</div>
                    <div class="pc-per">tailored for large institutions</div>
                    <div class="pc-note">Multi-campus · White-label · API</div>
                    <div class="pc-actions"><a href="{{ url('web/contact') }}" class="btn btn-dark">Contact Sales <x-site.icon name="arrow-right" class="ic-arrow" /></a></div>
                </div>
            </div>

            <div class="center rv" style="margin-top:44px;">
                <a href="{{ url('web/pricing') }}" class="btn btn-outline">View Full Pricing &amp; All Modules <x-site.icon name="arrow-right" class="ic-arrow" /></a>
            </div>
        </div>
    </section>

    {{-- ═══════════════ TESTIMONIALS ═══════════════ --}}
    <section class="section section-soft">
        <div class="container">
            <div class="head rv">
                <span class="eyebrow">Testimonials</span>
                <h2 class="h2">Why Schools <span class="grad">Love</span> SUPERLMS</h2>
                <p class="lead">From small private schools to large multi-campus institutions, principals,
                    teachers and parents trust SUPERLMS to run their day-to-day work. Here's what they have to say about
                    saving hours of paperwork, faster fee collection and happier classrooms.</p>
            </div>

            <div class="tslider rv" id="tSlider">
                <div class="tview"><div class="ttrack" id="tTrack"></div></div>
                <div class="tnav">
                    <button type="button" class="tbtn" id="tPrev" aria-label="Previous"><x-site.icon name="chevron-left" /></button>
                    <div class="tdots" id="tDots"></div>
                    <button type="button" class="tbtn" id="tNext" aria-label="Next"><x-site.icon name="chevron-right" /></button>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════ TEAM ═══════════════ --}}
    <section class="section">
        <div class="container container-md">
            <div class="head rv">
                <span class="eyebrow">Our Team</span>
                <h2 class="h2">Meet the <span class="grad">Minds Behind</span> SUPERLMS</h2>
                <p class="lead">Founded by Annant Dagur with one mission — to put
                    quality, affordable school-management technology within reach of every institution in India, from
                    big city campuses to small-town classrooms.</p>
            </div>

            @include('components.site.partials.founder', ['bio' => 'The visionary behind SUPERLMS. Annant started SUPERLMS with one mission — to make quality, affordable school-management technology accessible to every institution in India, from large city schools to small towns. He works closely with educators on the ground, turning their everyday challenges into simple, powerful features that thousands of students, teachers and parents now rely on.'])

            <div class="note-box rv">
                We're a team of <strong>educators, engineers, and designers</strong> based in Aligarh,
                UP. Every feature we build is shaped by direct feedback from real schools.
                <div><a href="{{ url('web/about') }}" class="btn btn-outline">Learn Our Story <x-site.icon name="arrow-right" class="ic-arrow" /></a></div>
            </div>
        </div>
    </section>

    {{-- ═══════════════ WHY US ═══════════════ --}}
    <section class="section section-soft">
        <div class="container">
            <div class="head rv">
                <span class="eyebrow">Why Us</span>
                <h2 class="h2">Why Schools <span class="grad">Choose</span> SUPERLMS</h2>
                <p class="lead">The bottom line: SUPERLMS is the one partner your
                    school needs. You get every module in one place at genuinely affordable, transparent per-student
                    pricing — full power on the web and dedicated apps on every phone. Our team handles the entire
                    setup and data upload, real humans support you on call, chat and WhatsApp, and everything is
                    designed around Indian boards, fees and school workflows — not adapted from somewhere else.</p>
            </div>
            <div class="chips rv">
                <span class="chip"><x-site.icon name="grid" /> All-in-One</span>
                <span class="chip"><x-site.icon name="rupee" /> Genuinely Affordable</span>
                <span class="chip"><x-site.icon name="repeat" /> Hybrid Web + App</span>
                <span class="chip"><x-site.icon name="wrench" /> We Set It Up</span>
                <span class="chip"><x-site.icon name="life-buoy" /> Real Human Support</span>
                <span class="chip"><x-site.icon name="flag" /> Built for India</span>
            </div>
            <div class="center rv" style="margin-top:36px;">
                <a href="{{ url('web/why-us') }}" class="btn btn-outline">See Why Schools Switch <x-site.icon name="arrow-right" class="ic-arrow" /></a>
            </div>
        </div>
    </section>

    {{-- ═══════════════ SERVICES ═══════════════ --}}
    <section class="section">
        <div class="container">
            <div class="head rv">
                <span class="eyebrow">Our Services</span>
                <h2 class="h2">Beyond Software — <span class="grad">Complete School Solutions</span></h2>
                <p class="lead">In short: SUPERLMS goes far beyond an LMS. From
                    running your classes online to printing ID cards, arranging school loans, setting up labs and smart
                    classrooms, managing transport, supplying uniforms &amp; books, and taking your teaching fully
                    online — everything your campus needs comes from one trusted partner, so you never juggle multiple
                    vendors again.</p>
            </div>
            <div class="chips rv">
                <span class="chip"><x-site.icon name="cap" /> School LMS</span>
                <span class="chip"><x-site.icon name="id-card" /> ID Cards</span>
                <span class="chip"><x-site.icon name="landmark" /> School Loans</span>
                <span class="chip"><x-site.icon name="flask" /> Labs Setup</span>
                <span class="chip"><x-site.icon name="presentation" /> Smart Classrooms</span>
                <span class="chip"><x-site.icon name="bus" /> Smart Transport</span>
                <span class="chip"><x-site.icon name="shirt" /> Uniforms &amp; Books</span>
                <span class="chip"><x-site.icon name="laptop" /> Online Education</span>
            </div>
            <div class="center rv" style="margin-top:36px;">
                <a href="{{ url('web/services') }}" class="btn btn-outline">Explore All Services <x-site.icon name="arrow-right" class="ic-arrow" /></a>
            </div>
        </div>
    </section>

    {{-- ═══════════════ SECURITY ═══════════════ --}}
    <section class="section section-soft">
        <div class="container">
            <div class="head rv">
                <span class="eyebrow">Security &amp; Reliability</span>
                <h2 class="h2">Enterprise-Grade <span class="grad">Protection</span></h2>
                <p class="lead">Your data and your students' data is protected with industry-leading
                    standards — always.</p>
            </div>
            <div class="grid g4">
                <div class="card rv"><div class="icon-sq"><x-site.icon name="lock" /></div><h3>256-bit SSL</h3><p>Bank-grade encryption for all data in transit</p></div>
                <div class="card rv" style="--rd:.06s"><div class="icon-sq"><x-site.icon name="shield" /></div><h3>Role-Based Access</h3><p>Granular permissions — right data to right people only</p></div>
                <div class="card rv" style="--rd:.12s"><div class="icon-sq"><x-site.icon name="cloud" /></div><h3>Daily Backups</h3><p>Geo-redundant cloud backups, recoverable in minutes</p></div>
                <div class="card rv" style="--rd:.18s"><div class="icon-sq"><x-site.icon name="badge-check" /></div><h3>GDPR Compliant</h3><p>Fully compliant with Indian IT Act &amp; DPDP Act 2023</p></div>
            </div>
        </div>
    </section>

    {{-- ═══════════════ FAQ ═══════════════ --}}
    <section class="section">
        <div class="container" data-faq>
            <div class="head rv">
                <span class="eyebrow">FAQ</span>
                <h2 class="h2">Frequently Asked <span class="grad">Questions</span></h2>
            </div>

            <div class="faq-chips rv">
                <div class="seg">
                    @foreach (['General', 'Getting Started', 'Features', 'Security'] as $i => $c)
                        <button type="button" class="{{ $i === 0 ? 'is-active' : '' }}" data-cat-btn="{{ $c }}">{{ $c }}</button>
                    @endforeach
                </div>
            </div>

            <div class="faq rv">
                @foreach ($faqs as $i => $f)
                    <details class="faq-item" data-cat="{{ $f['c'] }}" @if ($i === 0) open @endif>
                        <summary>{{ $f['q'] }}<span class="faq-i"><x-site.icon name="plus" /></span></summary>
                        <div class="faq-a">{{ $f['a'] }}</div>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════════ CTA ═══════════════ --}}
    <section class="section-tight">
        <div class="container">
            <div class="cta rv rv-s">
                <span class="eyebrow">Get Started</span>
                <h2 class="h2">Ready to Transform Your <span class="grad">Institution?</span></h2>
                <p class="lead">Join many schools already using SUPERLMS. Get started with a free personalized demo
                    today — no commitment, no credit card required.</p>
                <div class="actions">
                    <a href="{{ url('web/demo') }}" class="btn btn-primary btn-lg">Request Free Demo <x-site.icon name="arrow-right" class="ic-arrow" /></a>
                    <a href="{{ url('web/pricing') }}" class="btn btn-white btn-lg">View Pricing <x-site.icon name="arrow-right" class="ic-arrow" /></a>
                </div>
            </div>
        </div>
    </section>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';
    var esc = window.siteEsc;

    /* ── Live stats ── */
    fetch('/api/website/stats').then(function (r) { return r.json(); }).then(function (res) {
        var data = res && res.data; if (!data) return;
        var s = data.schools || 0, u = data.students || 0, t = data.teachers || 0, r = data.rating || 4.9;
        var set = function (id, v) { var el = document.getElementById(id); if (el) el.textContent = v; };
        if (s > 0) { ['heroSchools', 'dashSchools', 'statSchools'].forEach(function (id) { set(id, s + '+'); }); }
        if (u > 0) { ['heroStudents', 'dashStudents', 'statStudents'].forEach(function (id) { set(id, window.siteFmt(u)); }); }
        if (t > 0) set('statTeachers', t + '+');
        set('heroRating', r + '★'); set('dashRating', r + '★'); set('statRating', r + '/5');
    }).catch(function () {});

    /* ── Partner school logos ── */
    fetch('/api/website/schools').then(function (r) { return r.json(); }).then(function (res) {
        var data = res && res.data, band = document.getElementById('logosBand'), mq = document.getElementById('marquee');
        if (!band || !mq || !data || !data.length) return;
        var fallback = "{{ asset('website-image/Vector 215.png') }}";
        var item = function (sc) {
            var name = typeof sc === 'string' ? sc : sc.name;
            var logo = (typeof sc === 'object' && sc.logo_url) ? sc.logo_url : fallback;
            return '<div class="marquee-item"><img src="' + esc(logo) + '" alt="' + esc(name) + ' logo" loading="lazy" onerror="this.onerror=null;this.src=\'' + fallback + '\'"></div>';
        };
        var html = data.map(item).join('');
        if (data.length < 5) {
            mq.classList.add('is-static');
            mq.innerHTML = '<div class="marquee-track">' + html + '</div>';
        } else {
            var reps = Math.max(1, Math.ceil(10 / data.length)), row = '';
            for (var i = 0; i < reps; i++) row += html;
            mq.style.setProperty('--dur', Math.max(24, data.length * reps * 3.2) + 's');
            mq.innerHTML = '<div class="marquee-track">' + row + '</div><div class="marquee-track" aria-hidden="true">' + row + '</div>';
        }
        band.hidden = false;
    }).catch(function () {});

    /* ── Testimonials slider ── */
    var track = document.getElementById('tTrack'), dots = document.getElementById('tDots');
    var cur = 0, total = 0, auto = null;
    var colors = ['#0d9488', '#0f766e', '#2fae9f', '#115e56'];
    function vis() { return window.innerWidth >= 980 ? 3 : (window.innerWidth >= 640 ? 2 : 1); }
    function stars(n) { var s = ''; for (var i = 0; i < Math.max(1, Math.min(5, n || 5)); i++) s += '<svg class="ic" viewBox="0 0 24 24" fill="currentColor"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/></svg>'; return s; }
    function layout() {
        if (!total) return;
        var v = Math.min(vis(), total), max = Math.max(0, total - v);
        cur = Math.min(cur, max);
        Array.prototype.forEach.call(track.children, function (c) { c.style.width = (100 / v) + '%'; });
        track.style.transform = 'translateX(-' + (cur * 100 / v) + '%)';
        var n = max + 1;
        if (dots.children.length !== n) {
            dots.innerHTML = '';
            for (var i = 0; i < n; i++) {
                var b = document.createElement('button'); b.type = 'button'; b.setAttribute('aria-label', 'Slide ' + (i + 1));
                (function (k) { b.addEventListener('click', function () { cur = k; layout(); restart(); }); })(i);
                dots.appendChild(b);
            }
        }
        Array.prototype.forEach.call(dots.children, function (b, i) { b.classList.toggle('is-active', i === cur); });
    }
    function step(d) { var v = Math.min(vis(), total), max = Math.max(0, total - v); cur = (cur + d + max + 1) % (max + 1); layout(); }
    function restart() { clearInterval(auto); auto = setInterval(function () { step(1); }, 4200); }
    function build(items) {
        total = items.length;
        track.innerHTML = items.map(function (t, i) {
            var av = t.logo_url
                ? '<div class="tav img"><img src="' + esc(t.logo_url) + '" alt="' + esc(t.school_name) + ' logo" loading="lazy"></div>'
                : '<div class="tav" style="background:' + colors[i % colors.length] + '">' + esc(t.initials) + '</div>';
            return '<div class="tcard"><div class="tcard-in"><div class="stars">' + stars(t.rating) + '</div>' +
                '<p class="tquote">"' + esc(t.feedback) + '"</p>' +
                '<div class="tauthor">' + av + '<div><div class="tname">' + esc(t.school_name) + '</div>' +
                '<div class="trole">Verified School</div></div></div></div></div>';
        }).join('');
        layout(); restart();
    }
    document.getElementById('tPrev').addEventListener('click', function () { step(-1); restart(); });
    document.getElementById('tNext').addEventListener('click', function () { step(1); restart(); });
    window.addEventListener('resize', layout);

    var fallbackT = [
        { feedback: 'SUPERLMS transformed how we manage our institution. Attendance tracking and fee collection became completely seamless.', rating: 5, school_name: 'Delhi Public School', logo_url: null, initials: 'DP' },
        { feedback: 'The setup was incredibly smooth and support was always available. Student engagement increased by over 60%.', rating: 5, school_name: 'Sunrise Academy', logo_url: null, initials: 'SA' },
        { feedback: "Sharing notes and assignments is so easy now. Parents can track their child's progress in real time.", rating: 5, school_name: "St. Mary's School", logo_url: null, initials: 'SM' }
    ];
    fetch('/api/website/testimonials').then(function (r) { return r.json(); })
        .then(function (res) { build(res && res.data && res.data.length ? res.data : fallbackT); })
        .catch(function () { build(fallbackT); });
})();
</script>
@endpush
