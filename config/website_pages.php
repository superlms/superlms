<?php

/*
|--------------------------------------------------------------------------
| Dynamic marketing page defaults
|--------------------------------------------------------------------------
| Single source of truth for the built-in content of the website's dynamic
| pages (why-us, services, careers, become-executive, blogs, faqs).
|
|  - WebsitePageSeeder seeds the website_pages table from this array.
|  - The website blades use these values as a fallback when no DB row exists
|    (or a key is missing), so the pages always render fully.
*/

return [

    'why-us' => [
        'tag'      => 'Why EDYONE LMS',
        'title'    => 'The smarter choice for modern schools',
        'subtitle' => 'Hundreds of schools across India trust EDYONE to run admissions, academics, fees and communication on a single affordable platform — and to equip their campuses with everything from ID cards to smart classrooms and transport. Here is exactly why schools choose us over anyone else.',
        'items'    => [
            ['icon' => '💰', 'title' => 'Genuinely Affordable', 'desc' => 'Transparent, per-student pricing with no hidden setup fees. Built so that schools of every size can afford world-class technology.'],
            ['icon' => '🧩', 'title' => 'All-in-One Platform', 'desc' => 'Admissions, attendance, timetable, exams, fees, study material and parent communication — everything in one login instead of five different tools.'],
            ['icon' => '📱', 'title' => 'Mobile First', 'desc' => 'Dedicated apps for admins, teachers, students and parents — so your whole school stays connected from any phone, anywhere.'],
            ['icon' => '💳', 'title' => 'Online Fee Collection', 'desc' => 'Collect fees online with instant receipts and reconciliation. Parents pay in seconds, your accounts team saves hours every week.'],
            ['icon' => '🛟', 'title' => 'Real Human Support', 'desc' => 'Onboarding, training and ongoing help from a team that actually understands schools — over call, chat and WhatsApp.'],
            ['icon' => '🇮🇳', 'title' => 'Built for India', 'desc' => 'Designed around Indian school workflows, boards, fee structures and languages — not a foreign product forced to fit.'],
        ],
    ],

    'services' => [
        'tag'      => 'Our Services',
        'title'    => 'Everything your school needs, end to end',
        'subtitle' => 'EDYONE is much more than software. From our all-in-one LMS to ID cards, school loans, labs, smart classrooms, GPS-enabled transport, uniforms, books and online education — we partner with schools across India to digitise, equip and grow every part of their campus. Explore the complete range of services below.',
        'items'    => [
            ['icon' => '🎓', 'title' => 'School Management System', 'desc' => 'Manage students, staff, classes, sections and the full academic year from one powerful dashboard.'],
            ['icon' => '📝', 'title' => 'Admissions & Enquiries', 'desc' => 'Capture enquiries, run online admissions and convert leads into enrolled students with less paperwork.'],
            ['icon' => '🗓️', 'title' => 'Attendance & Timetable', 'desc' => 'Daily attendance, automated timetables and arrangement management for teachers and classes.'],
            ['icon' => '📊', 'title' => 'Exams & Report Cards', 'desc' => 'Set up exams, enter marks and generate professional report cards in a few clicks.'],
            ['icon' => '💳', 'title' => 'Fee Management', 'desc' => 'Define fee structures, collect payments online, and track dues with automatic receipts and reminders.'],
            ['icon' => '📚', 'title' => 'Digital Content & Quizzes', 'desc' => 'Share syllabus, study material, books and quizzes so learning continues beyond the classroom.'],
            ['icon' => '🔔', 'title' => 'Notifications & Communication', 'desc' => 'Reach parents and staff instantly with announcements, push notifications and calendar updates.'],
            ['icon' => '🆔', 'title' => 'ID Cards & Documents', 'desc' => 'Generate student and staff ID cards and essential documents directly from the platform.'],
            ['icon' => '🤝', 'title' => 'Onboarding & Training', 'desc' => 'Guided setup, data migration and staff training so your school goes live smoothly and quickly.'],
        ],
    ],

    'careers' => [
        'tag'      => 'Careers',
        'title'    => 'Build your career with EDYONE',
        'subtitle' => 'Join the team building India\'s most affordable school management platform. Explore our open roles and the salaries they offer, see how our hiring process works, and apply in minutes — we review every application and get back to you.',
        'jobs' => [
            ['role' => 'Business Development Executive', 'department' => 'Sales',        'location' => 'Aligarh / Remote', 'type' => 'Full-time', 'salary' => '₹3–6 LPA + incentives'],
            ['role' => 'School Partnership Associate',   'department' => 'Partnerships', 'location' => 'Remote',          'type' => 'Full-time', 'salary' => '₹2.5–5 LPA'],
            ['role' => 'Customer Support Associate',      'department' => 'Support',      'location' => 'Aligarh, UP',     'type' => 'Full-time', 'salary' => '₹2–3.5 LPA'],
            ['role' => 'School Onboarding Specialist',    'department' => 'Operations',   'location' => 'Hybrid',          'type' => 'Full-time', 'salary' => '₹3–5 LPA'],
        ],
    ],

    'become-executive' => [
        'tag'      => 'Partner Program',
        'title'    => 'Become an Executive with Edyone LMS',
        'subtitle' => 'Partner with EDYONE to bring affordable school technology to institutions in your region. Work remotely, on your own schedule, with a simple joining process and fast payouts — and earn attractive recurring income for every school you bring on board.',
    ],

    'blogs' => [
        'tag'      => 'The EDYONE Blog',
        'title'    => 'Ideas & insights for modern schools',
        'subtitle' => 'Welcome to the EDYONE blog — your go-to space for practical ideas, product updates and real stories from schools across India. We share hands-on tips on attendance, fees, exams, admissions and parent communication, alongside guides that help school leaders, teachers and administrators run their institutions better, teach smarter and engage families more effectively. Whether you are just going digital or looking to get more out of your LMS, there is something here to help your school grow.',
        // Blog posts are now stored in the `blogs` table and managed from the
        // super-admin Blogs screen (no longer config-driven).
    ],

    'faqs' => [
        'tag'      => 'Help Center',
        'title'    => 'Frequently asked questions',
        'subtitle' => "Everything you need to know about EDYONE LMS — from pricing and setup to features, security and support — all in one place. Browse the questions below by category to quickly find what you're looking for, whether you're a school leader, teacher or parent exploring the platform. Can't find your answer here? Don't worry — our friendly team is always just a quick message away and happy to help.",
        // FAQs are now stored in the `faqs` table and managed from the
        // super-admin FAQs screen (no longer config-driven).
    ],

];
