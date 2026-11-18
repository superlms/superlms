@extends('components.site.layout')

@section('title', 'About Us')

@php
    $doCards = [
        ['ic' => 'chart', 't' => 'Streamlines Academic Management', 'd' => 'Attendance, timetables, fee management, and note-sharing, all in one unified platform — saving administrators several hours of manual work every day.'],
        ['ic' => 'sparkles', 't' => 'Enhances the Learning Experience', 'd' => 'Real-time progress tracking, digital resource sharing, and interactive assessment tools that help both teachers and students engage more effectively.'],
        ['ic' => 'bulb', 't' => 'Keeps Costs Affordable', 'd' => 'Flexible, transparent pricing designed to make advanced school-management features available to institutions of every size and budget.'],
        ['ic' => 'lock', 't' => 'Secure & Reliable Infrastructure', 'd' => 'Enterprise-grade security, daily backups, SSL encryption, and data handling aligned with Indian data protection law keep your data always safe.'],
        ['ic' => 'smartphone', 't' => 'Multi-Platform Access', 'd' => 'Dedicated Android and iOS apps so students, teachers, and parents stay connected anytime — even in low-bandwidth areas.'],
        ['ic' => 'handshake', 't' => 'Dedicated Onboarding & Support', 'd' => 'Every school is assigned an onboarding specialist, with fast, responsive support throughout their journey on the platform.'],
    ];

    $model = [
        ['n' => '01', 'ic' => 'handshake', 't' => 'School Onboarding', 'd' => "We begin with a free needs assessment call. Our team then configures the platform with your school's specific classes, subjects, academic year, branding, and user roles — within 3–5 business days."],
        ['n' => '02', 'ic' => 'refresh', 't' => 'Data Migration', 'd' => 'Existing student records, staff information, fee structures, and historical data are migrated securely and verified before go-live — with an emphasis on zero data loss.'],
        ['n' => '03', 'ic' => 'users', 't' => 'Staff & User Training', 'd' => 'We conduct live virtual training for administrators, teachers, and parents. Custom guides, video tutorials, and a dedicated WhatsApp support group ensure everyone is ready.'],
        ['n' => '04', 'ic' => 'rocket', 't' => 'Go-Live & Monitor', 'd' => 'Your school goes live with full SUPERLMS access. Our team actively monitors the first 30 days to ensure smooth operation.'],
        ['n' => '05', 'ic' => 'trending-up', 't' => 'Continuous Updates', 'd' => 'Monthly feature releases, security patches, and performance improvements — included in every plan at no additional cost.'],
        ['n' => '06', 'ic' => 'phone', 't' => 'Ongoing Support', 'd' => 'Dedicated support available via phone, WhatsApp, email, and in-app chat — with fast response times on business days.'],
    ];

    $roles = [
        ['for' => 'Administrators', 'ic' => 'landmark', 'pts' => ['Complete school dashboard with live KPIs', 'Student & teacher record management', 'Fee collection, payroll & financial reports', 'Timetable generation & substitute management', 'Bulk ID card, admit card & report card generation', 'Transport route & library management']],
        ['for' => 'Teachers', 'ic' => 'teacher', 'pts' => ['Mark attendance via mobile in seconds', 'Upload notes, videos & study materials', 'Create quizzes, assignments & grade submissions', 'Track student performance per subject', 'Communicate with parents via announcements', 'Manage class-wise seating plans for exams']],
        ['for' => 'Students', 'ic' => 'cap', 'pts' => ['Access subject notes, books & content anytime', 'View assignments, deadlines & submit digitally', 'Attempt online quizzes with instant results', 'Check own attendance & academic performance', 'Download admit cards & report cards', 'Receive fee receipts & timetable updates']],
        ['for' => 'Parents', 'ic' => 'family', 'pts' => ['Real-time child attendance notifications (SMS/WhatsApp)', 'Track academic progress & grade trends', 'Pay school fees online & download receipts', 'View homework, test scores & report cards', 'Communicate directly with teachers', 'Receive school announcements instantly']],
        ['for' => 'Accounts', 'ic' => 'receipt', 'pts' => ['Automated fee invoicing, dues tracking & online collection', 'Payroll, salary slips & staff expense management', 'Income, expense & fee ledgers with financial reports', 'Online payment reconciliation & instant receipts', 'Audit-ready accounting & financial exports', 'Real-time dues, defaulter & collection dashboards']],
        ['for' => 'Exams Management', 'ic' => 'clipboard', 'pts' => ['Build exam schedules, datesheets & seating arrangements', 'Configure grading schemes & marks entry workflows', 'Automated result processing & rank calculation', 'Auto-generate admit cards, marksheets & report cards in bulk', 'Subject & class-wise performance analysis', 'Publish results instantly to students & parents']],
    ];
@endphp

@section('content')

    {{-- ═══════════════ PAGE HERO ═══════════════ --}}
    <section class="phero">
        <div class="container">
            <span class="eyebrow rv">About Us</span>
            <h1 class="h1 rv" style="--rd:.05s">Your All in One Solution for<br><span class="grad">Learning &amp; Management</span></h1>
            <p class="lead rv" style="--rd:.1s">SUPERLMS is a cloud-based, all-in-one school management and learning
                platform that brings academics, administration, and communication onto a single platform. Instead of
                juggling registers, spreadsheets, WhatsApp groups, and paper records, schools get attendance,
                timetables, fees, assignments, exams, report cards, and parent communication in one connected
                ecosystem — trusted by schools across India, from small rural institutions to large multi-campus
                academies, with more than 50,000 students actively learning on the platform.</p>
        </div>
    </section>

    {{-- ═══════════════ MISSION ═══════════════ --}}
    <section class="section" style="padding-top:40px;">
        <div class="container">
            <div class="split">
                <div class="split-text rv rv-l">
                    <span class="eyebrow">Our Mission</span>
                    <h2 class="h2">Making Education <span class="grad">Accessible</span> &amp; Affordable</h2>
                    <p class="body-l" style="margin-top:20px;">Our mission is simple:
                        make quality school-management technology accessible and affordable for every institution in India —
                        not just large, well-funded schools, but also small-town and rural institutions that are often
                        priced out of modern EdTech tools.</p>
                    <p class="body-l" style="margin-top:14px;">We believe a school in
                        a small town deserves the same powerful digital tools as a large city academy, at a price point that
                        actually makes sense for its budget. This belief shapes every feature we build and every pricing
                        decision we make.</p>
                    <div class="actions"><a href="{{ url('web/demo') }}" class="btn btn-primary">Start Your Journey <x-site.icon name="arrow-right" class="ic-arrow" /></a></div>
                </div>
                <div class="rv rv-r">
                    <div class="vpanel mint" style="min-height:500px;flex-direction:column;">
                        @include('components.site.mock.scene', ['m' => 'orbit'])
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════ OUR STORY ═══════════════ --}}
    <section class="section section-soft">
        <div class="container">
            <div class="split flip" style="align-items:start;">
                <div class="split-text rv rv-r">
                    <span class="eyebrow">Our Story</span>
                    <h2 class="h2">From a Basic <span class="grad">Idea to a Platform</span></h2>
                    <p class="body-l" style="margin-top:20px;">SUPERLMS began in 2024 out of a small office in Aligarh, Uttar Pradesh — not
                        as a corporate initiative, but as a response to a very real, everyday problem. Our founding team
                        watched teachers spend hours on paper attendance registers, principals repeatedly chase parents
                        for pending fees, and students miss timely feedback on their progress simply because the tools
                        available to schools hadn't kept pace with the rest of the world.</p>
                    <p class="body-l" style="margin-top:14px;">The first version of SUPERLMS launched quietly with a single partner school
                        and a core team of three people. Word spread organically — one principal recommending the
                        platform to another, teachers sharing their experience in WhatsApp groups — and the school base
                        grew steadily from there.</p>
                    <p class="body-l" style="margin-top:14px;">By the end of 2025, we had introduced dedicated Android and iOS applications,
                        integrated biometric attendance, and real-time WhatsApp notifications for parents. Today,
                        SUPERLMS supports a growing network of schools across India, with fully integrated modules that
                        continue to evolve based on direct feedback from the educators who use them every day.</p>

                    <div class="grid g2" style="margin-top:28px;gap:12px;">
                        @foreach ([['sprout', 'Bootstrapped Growth', 'From a handful of schools to a nationwide presence — driven almost entirely by word-of-mouth and product quality.'], ['flag', 'Made in India', 'Designed from the ground up by a team that understands the day-to-day realities of Indian schools.'], ['bulb', 'Innovation-First', 'New features, updates, and improvements released regularly — included in every plan at no extra cost.'], ['handshake', 'Community-Driven', 'Every major feature is shaped in collaboration with our network of partner schools.']] as $h)
                            <div class="card" style="padding:20px;">
                                <div class="icon-sq" style="width:40px;height:40px;font-size:19px;margin-bottom:12px;"><x-site.icon :name="$h[0]" /></div>
                                <h3 style="font-size:16.5px;">{{ $h[1] }}</h3>
                                <p style="font-size:14px;">{{ $h[2] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="rv rv-l">
                    <div class="vpanel" style="background:#fff;min-height:520px;">
                        @include('components.site.mock.scene', ['m' => 'imap'])
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════ WHAT WE DO ═══════════════ --}}
    <section class="section">
        <div class="container">
            <div class="head rv">
                <span class="eyebrow">What We Do</span>
                <h2 class="h2">SUPERLMS <span class="grad">Streamlines</span> Everything</h2>
            </div>
            <div class="grid g3">
                @foreach ($doCards as $i => $c)
                    <div class="card card-soft rv" style="--rd:{{ ($i % 3) * .07 }}s">
                        <div class="icon-sq"><x-site.icon :name="$c['ic']" /></div>
                        <h3>{{ $c['t'] }}</h3>
                        <p>{{ $c['d'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════════ WORKING MODEL ═══════════════ --}}
    <section class="section section-soft">
        <div class="container">
            <div class="head rv" style="max-width:940px;">
                <span class="eyebrow">How We Operate</span>
                <h2 class="h2">Our <span class="grad">Working Model</span></h2>
                <p class="lead" style="max-width:900px;">A transparent, school-first approach
                    that ensures every institution gets maximum value from day one. From the very first conversation to
                    going live and well beyond, we personally handle onboarding, data migration, staff training, and
                    day-to-day support — so your team is never left figuring things out alone. We begin by understanding
                    how your school already works, then configure SUPERLMS around your real classes, subjects, fee
                    structures, and academic calendar instead of forcing you into a rigid template. Every step is simple,
                    predictable, and completely stress-free, and our team stays closely involved long after go-live to
                    make sure the platform keeps delivering value as your school grows — with no hidden costs or surprises
                    along the way.</p>
            </div>
            <div class="wm">
                @foreach ($model as $i => $w)
                    <div class="wm-card rv" style="--rd:{{ ($i % 3) * .07 }}s">
                        <span class="wm-num">{{ $w['n'] }}</span>
                        <div class="icon-sq"><x-site.icon :name="$w['ic']" /></div>
                        <h3>{{ $w['t'] }}</h3>
                        <p>{{ $w['d'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════════ BUILT FOR EVERY ROLE ═══════════════ --}}
    <section class="section">
        <div class="container">
            <div class="head rv" style="max-width:940px;">
                <span class="eyebrow">Platform Capabilities</span>
                <h2 class="h2">Built for <span class="grad">Every Role</span></h2>
                <p class="lead" style="max-width:900px;">SUPERLMS is designed to serve the
                    unique needs of every stakeholder in a school ecosystem. Administrators get complete control over
                    day-to-day operations and a clear, real-time view of the entire school, teachers get simple tools to
                    manage classes, attendance, and assessments without the paperwork, students get an engaging way to
                    learn, submit work, and track their own progress, and parents stay closely connected to their child's
                    attendance, results, fees, and everyday school updates. Each role gets a tailored experience that
                    surfaces exactly the right features and hides everything they don't need — so everyone, from the
                    principal's office to the parent's phone, works together effortlessly on a single, connected platform
                    that keeps the whole school moving in sync.</p>
            </div>
            <div class="grid g3">
                @foreach ($roles as $i => $r)
                    <div class="role rv" style="--rd:{{ ($i % 3) * .08 }}s">
                        <div class="role-head">
                            <div><div class="role-for">For</div><div class="role-name">{{ $r['for'] }}</div></div>
                            <span class="role-ico"><x-site.icon :name="$r['ic']" /></span>
                        </div>
                        <ul>@foreach ($r['pts'] as $p)<li><x-site.icon name="check" />{{ $p }}</li>@endforeach</ul>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════════ SECURITY ═══════════════ --}}
    <section class="section section-soft">
        <div class="container">
            <div class="head rv">
                <span class="eyebrow">Security</span>
                <h2 class="h2">Enterprise-Grade <span class="grad">Security</span></h2>
                <p class="lead">Your data and your students' data is protected with
                    industry-leading security standards.</p>
            </div>
            <div class="split">
                <div class="rv rv-l">
                    <div class="vpanel" style="background:#fff;">@include('components.site.mock.scene', ['m' => 'shield'])</div>
                </div>
                <div class="info-list rv rv-r">
                    @foreach ([['lock', '256-bit SSL Encryption', 'All data in transit is encrypted with bank-grade SSL, protecting every login, transaction, and communication.'], ['shield', 'Role-Based Access Control', 'Granular permissions ensure each user only sees the data relevant to their role.'], ['cloud', 'Daily Geo-Redundant Backups', 'Automated daily backups stored in geo-redundant cloud locations, recoverable within minutes.'], ['badge-check', 'Compliance-Aligned Data Handling', 'Data handling in keeping with the Indian Information Technology Act, 2000, and the Digital Personal Data Protection Act, 2023.']] as $s)
                        <div class="info">
                            <div class="icon-sq"><x-site.icon :name="$s[0]" /></div>
                            <div>
                                <div class="info-v" style="margin:0;font-size:17px;">{{ $s[1] }}</div>
                                <p style="font-size:15px;color:var(--muted);line-height:1.6;margin-top:4px;">{{ $s[2] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════ OUR PLATFORMS ═══════════════ --}}
    <section class="section">
        <div class="container">
            <div class="head rv">
                <span class="eyebrow">Our Platforms</span>
                <h2 class="h2">One Company, <span class="grad">Three Connected Platforms</span></h2>
                <p class="lead">SUPERLMS is one of three connected platforms
                    operated by Super Learnings Private Limited, designed to support schools, students, and families
                    end-to-end — beyond the classroom, into the home.</p>
            </div>
            <div class="apps">
                <div class="app is-feat rv">
                    <div class="app-logo"><img src="{{ \App\Support\Platform::logoUrl() }}" alt="Logo"></div>
                    <div class="app-name">SUPERLMS</div>
                    <p style="margin-bottom:0;">Our core school management system
                        for attendance, fees, timetables, academics, and communication between administrators,
                        teachers, students, and parents.</p>
                </div>
                <div class="app rv" style="--rd:.08s">
                    <div class="app-logo"><img src="{{ asset('admin-image/c8cd2e7a3c40476d15ae54f52a8565b83bfc20b3.png') }}" alt="Logo"></div>
                    <div class="app-name">Edyone</div>
                    <p style="margin-bottom:0;">A companion EdTech platform
                        offering academic and competitive-exam courses, interactive lessons, practice tests, and smart
                        learning tools for students.</p>
                </div>
                <div class="app rv" style="--rd:.16s">
                    <div class="app-logo"><img src="{{ asset('admin-image/Group 33079.png') }}" alt="Logo"></div>
                    <div class="app-name">Super Safe</div>
                    <p style="margin-bottom:0;">A parental-control application
                        focused on screen-time monitoring, content filtering, and online safety for children.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════ TEAM ═══════════════ --}}
    <section class="section section-soft">
        <div class="container container-md">
            <div class="head rv">
                <span class="eyebrow">Our Team</span>
                <h2 class="h2">Built by Entrepreneurs, <span class="grad">For Schools &amp; Students</span></h2>
                <p class="lead">SUPERLMS is built by a team of educators,
                    engineers, and designers based in Aligarh, Uttar Pradesh — the same team that speaks directly with
                    teachers, principals, and parents to understand what schools actually need, rather than guessing
                    from a distance.</p>
            </div>

            <div class="founder-on-soft">
                @include('components.site.partials.founder', ['bio' => 'Annant founded SUPERLMS with a clear goal: to make affordable, powerful school-management technology accessible to every institution in India, from large city schools to small towns. He continues to work closely with educators on the ground, shaping their everyday challenges into practical features used by thousands of students, teachers, and parents.'])
            </div>

            <div class="cta rv" style="margin-top:28px;padding:56px 32px;">
                <div class="icon-sq" style="margin:0 auto 16px;background:#fff;"><x-site.icon name="handshake" /></div>
                <h3 class="h3">Join Our Growing Team</h3>
                <p class="lead" style="font-size:16.5px;">We are actively
                    growing our team of educators, engineers, and designers who want to work on technology that has a
                    direct, visible impact on schools and students across the country. Whether you're a school exploring
                    a new management system, a parent curious about how SUPERLMS works, or someone interested in joining
                    our team — we'd love to hear from you.</p>
                <div class="chips" style="margin-top:22px;">
                    <a class="chip" href="mailto:support@superlms.in"><x-site.icon name="mail" /> support@superlms.in</a>
                    <a class="chip" href="tel:+919084748563"><x-site.icon name="phone" /> +91 90847 48563</a>
                    <span class="chip"><x-site.icon name="map-pin" /> Office No. 02, Braj Vihar Colony, Jattari Khair Aligarh, Uttar Pradesh India -202137</span>
                </div>
                <div class="actions"><a href="{{ url('web/contact') }}" class="btn btn-primary">Get in Touch <x-site.icon name="arrow-right" class="ic-arrow" /></a></div>
            </div>
        </div>
    </section>

@endsection
