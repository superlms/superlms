@extends('components.site.layout')

@section('title', 'Features')

@php
    $list = [
        ['grid', 'Dashboard', 'Central overview of all school activity — attendance summary, fee status, upcoming exams, notices, and live stats at a glance for every role.', 'Core'],
        ['home', 'Home Analytics', 'Visual charts and KPIs on your homepage — enrollment trends, monthly fee collection, attendance rates, and exam scores updated in real time.', 'Analytics'],
        ['school', 'School Profile', 'Complete institutional profile management — school name, logo, address, affiliation board, academic year config, departments, and contact info.', 'Administration'],
        ['cap', 'Students', 'Complete student profiles with personal info, academic history, attendance record, fee status, uploaded documents, and parent contact details.', 'Management'],
        ['teacher', 'Teachers', 'Staff directory with subject mappings, class assignments, attendance tracking, payroll links, and individual performance reporting.', 'Management'],
        ['wallet', 'Fee', 'Collect fees online or offline, generate instant receipts, set due dates, apply late fines, manage scholarships, and export detailed financial reports.', 'Finance'],
        ['inbox', 'Enquiries', 'Track admission enquiries from first contact to enrolment — assign follow-ups, log calls, set reminders, and monitor conversion rates in one place.', 'Admissions'],
        ['library', 'Content', 'Upload and share notes, PDFs, videos, and presentations by subject and class. Students access study materials anytime, anywhere, even offline.', 'Learning'],
        ['book-open', 'Syllabus', 'Define and publish the syllabus for each class and subject. Track chapter-wise completion progress and align content delivery accordingly.', 'Learning'],
        ['calendar', 'Time Table', 'Auto-generate conflict-free timetables with an AI-assisted engine. Drag-and-drop editor, substitute teacher assignment, and instant sharing.', 'Scheduling'],
        ['pen', 'Homework', 'Assign homework with due dates and file attachments. Students submit digitally; teachers review, annotate, and grade submissions in one place.', 'Learning'],
        ['calendar-check', 'Attendance', 'Mark attendance via mobile, QR code, or biometric integration. Instant SMS and WhatsApp alerts sent to parents automatically on absence.', 'Core'],
        ['bulb', 'Quiz', 'Create auto-graded MCQ, true/false, and short-answer quizzes. Timed tests with instant results, detailed analytics, and class leaderboards.', 'Assessment'],
        ['trending-up', 'Performance', 'Subject-wise marks, grade trends, class rank comparisons, and detailed analytics dashboards for students, teachers, parents, and administrators.', 'Analytics'],
        ['landmark', 'Library', 'Manage physical and digital library — catalog books, track issue and return, set due dates, send overdue reminders, and manage fines automatically.', 'Resources'],
        ['book', 'Book', 'Digital book repository for students. Browse, borrow, and read e-books subject-wise directly within the app, with reading progress tracking.', 'Resources'],
        ['megaphone', 'Announcement', 'Broadcast notices to specific classes, roles, or the entire school instantly via app notification, SMS, email, or WhatsApp with scheduled delivery.', 'Communication'],
        ['refresh', 'Arrangement', 'Manage substitute teacher arrangements when staff are absent — auto-suggest replacements based on availability and notify affected classes instantly.', 'Management'],
        ['seat', 'Seating Plan', 'Design and publish exam hall seating arrangements visually. Print-ready or share digitally with students and invigilators before the exam.', 'Exams'],
        ['file-text', 'Exam Copy', 'Upload scanned or digital answer sheets to the platform. Teachers annotate, mark, and return evaluated copies to students digitally.', 'Exams'],
        ['clipboard', 'Report Card', 'Auto-generate formatted report cards with subject marks, grades, attendance, teacher remarks, and school branding. Share as PDF or print in bulk.', 'Assessment'],
        ['calendar', 'Calendar', 'School-wide academic calendar with holidays, exam schedules, events, PTMs, and important dates. One-click sync to Google or personal calendars.', 'Planning'],
        ['id-card', 'ID Card', 'Design and bulk-generate student and staff ID cards with photo, barcode, and school branding. Export print-ready PDFs for the entire school in seconds.', 'Administration'],
        ['ticket', 'Admit Card', 'Auto-generate exam admit cards with student details, roll number, exam schedule, photo, and school seal. Distribute digitally or allow self-download.', 'Exams'],
        ['scale', 'Rules & Regulation', 'Publish and manage school rules, code of conduct, disciplinary policies, and guidelines. Instantly accessible to all users within the app at any time.', 'Administration'],
        ['briefcase', 'Payroll', 'Manage staff salaries, allowances, deductions, and PF/ESI contributions. Generate payslips and export monthly payroll reports for accounts.', 'Finance'],
        ['bus', 'Transportation', 'Manage bus routes, stops, and assigned students. Track vehicles, collect transport fees, send delay alerts, and notify parents in real time.', 'Operations'],
        ['award', 'TC & Certificate', 'Issue Transfer Certificates, bonafide letters, and custom achievement certificates digitally. QR-code verified and tamper-proof for official use.', 'Documents'],
        ['sliders', 'Account Settings', 'Manage personal profile, notification preferences, password, linked devices, and language settings. Fully role-specific configuration for every user type.', 'Settings'],
        ['phone', 'Contact Admin', 'Direct in-app messaging channel between users and school administration. Raise queries, submit complaints, get support, and track issue resolution.', 'Support'],
        ['info', 'About App', 'Version information, release notes, developer contact details, and full platform documentation — always accessible within the app for all users.', 'Info'],
        ['star', 'Rate LMS', 'Students, teachers, and parents can rate their SUPERLMS experience. Feedback is aggregated and used to continuously improve the platform.', 'Feedback'],
        ['file-check', 'Terms of Use', 'In-app access to the Terms of Service, Privacy Policy, and data usage agreements — always current and transparent for every user on the platform.', 'Legal'],
        ['mail', 'Automated Emails', 'Trigger automated email alerts for fee dues, exam schedules, attendance summaries, assignment deadlines, and announcements — no manual effort needed.', 'Automation'],
        ['monitor', 'User Friendly Interface', 'Clean, intuitive design built for all age groups and tech comfort levels. Consistent experience across web, iOS, and Android with minimal learning curve.', 'Design'],
        ['tag', 'Standard', 'Set up classes, sections, and streams once — the entire platform organises itself around your academic structure automatically.', 'Administration'],
        ['receipt', 'Ledger', 'A crystal-clear book of every rupee in and out — balanced, searchable, and always audit-ready for accounts and management.', 'Finance'],
        ['card', 'Credit', 'Manage wallet balances and top-ups that keep premium services and communication features running smoothly, with a clear transaction trail.', 'Finance'],
        ['timer', 'Exam', 'Plan exams end to end — schedules, subjects, marks, and grades, all beautifully organised and ready to publish in a click.', 'Exams'],
        ['users', 'Users', 'Add staff and assign roles with pinpoint permissions, so every user sees exactly what they should — nothing more, nothing less.', 'Administration'],
        ['user-plus', 'Admissions', 'Run the entire admission journey online — from application and document upload to approval and enrolment — in one smooth, paperless flow.', 'Admissions'],
        ['list', 'Lists', 'Generate any student or staff list you need, filtered exactly how you want it, and export it print-ready in a single click.', 'Reports'],
        ['file-text', 'Terms & Conditions', 'The full terms of service, kept transparent and always up to date, accessible to every user right inside the app.', 'Legal'],
        ['lock', 'Privacy Policy', 'Clear, honest data practices your whole school community can trust — always current and available on demand.', 'Legal'],
    ];
@endphp

@section('content')

    <section class="phero">
        <div class="container">
            <span class="eyebrow rv">Features</span>
            <h1 class="h1 rv" style="--rd:.05s">Everything Your School <span class="grad">Needs</span></h1>
            <p class="lead rv" style="--rd:.1s">Our all-in-one school management solution offers fully integrated modules designed to simplify every aspect of school operations. From admissions, attendance, and academics to communication, finance, and reporting, it streamlines processes, improves efficiency, and enhances collaboration, giving schools a centralized platform to manage daily tasks effectively and effortlessly.</p>
            <div class="actions rv" style="--rd:.15s">
                <a href="{{ url('web/demo') }}" class="btn btn-primary">Book Free Demo</a>
                <a href="{{ url('web/pricing') }}" class="btn btn-outline">View Pricing</a>
            </div>
        </div>
    </section>

    <section style="padding:8px 0 40px;">
        <div class="container rv rv-s" style="max-width:1100px;">
            @include('components.site.mock.analytics-app')
        </div>
    </section>

    <section class="section" style="padding-top:72px;">
        <div class="container">
            <div class="fdir">
                @foreach ($list as $i => $f)
                    <div class="card rv" style="--rd:{{ ($i % 3) * .06 }}s">
                        <div class="icon-sq"><x-site.icon :name="$f[0]" /></div>
                        <h3>{{ $f[1] }}</h3>
                        <p>{{ $f[2] }}</p>
                        <span class="tag">{{ $f[3] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section-tight">
        <div class="container">
            <div class="cta rv rv-s">
                <span class="eyebrow">Get Started</span>
                <h2 class="h2">Ready to Experience These <span class="grad">Features?</span></h2>
                <p class="lead">Experience all features firsthand! Book a free demo to explore how our platform works with your school's data, showcasing seamless management of academics, attendance, communication, finance, and more.</p>
                <div class="actions">
                    <a href="{{ url('web/demo') }}" class="btn btn-primary btn-lg">Book Free Demo <x-site.icon name="arrow-right" class="ic-arrow" /></a>
                    <a href="{{ url('web/pricing') }}" class="btn btn-white btn-lg">View Pricing</a>
                </div>
            </div>
        </div>
    </section>

@endsection
