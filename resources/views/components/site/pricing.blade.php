@extends('components.site.layout')

@section('title', 'Pricing')

@php
    $mods = [
        ['home', 'Home', "Your school's command centre — today's attendance, fees, notices and live stats the second you log in."],
        ['chart', 'Analytics', 'Turn raw numbers into decisions — enrollment, collections, attendance and results in living charts.'],
        ['tag', 'Standard', 'Set up classes, sections and streams once, and the whole platform organises itself around them.'],
        ['cap', 'Students', "Every learner's full story in one profile — academics, attendance, fees, documents and parent contacts."],
        ['teacher', 'Teachers', 'A complete staff directory with subjects, classes, attendance and performance, all linked together.'],
        ['wallet', 'Fees', 'Collect online or offline, auto-generate receipts, apply fines and track dues — money made effortless.'],
        ['receipt', 'Ledger', 'A crystal-clear book of every rupee in and out — balanced, searchable and always audit-ready.'],
        ['briefcase', 'Payroll', 'Salaries, allowances, deductions and slips — staff get paid right, on time, every single month.'],
        ['card', 'Credit', 'Manage wallet balances and top-ups that keep your premium services running without a hitch.'],
        ['calendar-check', 'Attendance', 'Mark in seconds by app, QR or biometric — parents get an instant ping the moment a child is absent.'],
        ['bus', 'Transportation', "Routes, stops, vehicles and live alerts — every child's ride home, tracked and safe."],
        ['pen', 'Homework', 'Assign with due dates and files; students submit digitally and teachers grade it all in one place.'],
        ['calendar', 'Time Table', 'Conflict-free schedules built in minutes, with substitutes and instant sharing to every phone.'],
        ['refresh', 'Arrangement', 'Teacher absent? Auto-suggest a substitute and notify the class before the bell even rings.'],
        ['megaphone', 'Announcement', 'Reach the whole school or a single class instantly — push, SMS and email all in one shot.'],
        ['calendar', 'Calendar', 'Holidays, exams, events and PTMs in one shared calendar everyone can sync in a single click.'],
        ['book-open', 'Syllabus', 'Publish the course plan and track completion chapter by chapter, subject by subject.'],
        ['library', 'Content', 'Share notes, PDFs, videos and slides so students learn anytime, anywhere, on any device.'],
        ['bulb', 'Quiz', 'Auto-graded quizzes with timers, instant results and leaderboards that make revision genuinely fun.'],
        ['book', 'Book', 'A digital library in every pocket — browse, borrow and read e-books subject-wise, right in the app.'],
        ['inbox', 'Enquiries', 'Capture every admission lead and nurture it from the first hello to the final enrolment.'],
        ['id-card', 'ID Card', 'Design once, generate in bulk — photo, barcode and branding, print-ready in seconds.'],
        ['timer', 'Exam', 'Plan exams end to end — schedules, subjects, marks and grades, all beautifully organised.'],
        ['ticket', 'Admit Card', 'Auto-build admit cards with roll numbers, schedules and school seal, shared digitally in a click.'],
        ['seat', 'Seating Plan', 'Fair, tidy exam-hall seating designed in a click and shared with students and invigilators.'],
        ['trending-up', 'Performance', 'Spot every rising star and every gap with marks, trends and class rankings at a glance.'],
        ['file-text', 'Exam Copy', 'Upload answer sheets, annotate, grade and return copies — the whole paper trail goes paperless.'],
        ['clipboard', 'Report Card', 'Polished report cards with grades, remarks and attendance, exported to crisp PDFs for parents.'],
        ['award', 'TC & Certificate', 'Issue transfer certificates and bonafide letters — QR-verified and completely tamper-proof.'],
        ['users', 'Users', 'Add staff and roles with pinpoint permissions so everyone sees exactly what they should.'],
        ['user-plus', 'Admissions', 'Run the whole admission journey online, from application to enrolment, in one smooth flow.'],
        ['list', 'Lists', 'Generate any student or staff list you need and export it, filtered and ready, in a click.'],
        ['scale', 'Rules & Regulation', 'Publish the code of conduct and policies where every student and parent can find them.'],
        ['message', 'Contact Admin', 'A direct line to the office — raise queries, get answers and track every resolution.'],
        ['info', 'About App', 'Version info, release notes and support contacts, always just a tap away.'],
        ['star', 'Rate LMS', 'Students, teachers and parents rate their experience so the platform keeps getting better.'],
        ['file-text', 'Terms & Conditions', 'The full terms of service, kept transparent and always up to date.'],
        ['lock', 'Privacy Policy', 'Clear, honest data practices your whole school community can trust.'],
        ['book', 'Terms Of Use', 'Simple, plain-English guidelines on using the platform the right way.'],
    ];
@endphp

@section('content')

    <section class="phero">
        <div class="container">
            <span class="eyebrow rv">Pricing</span>
            <h1 class="h1 rv" style="--rd:.05s">Simple, <span class="grad">Transparent</span> Pricing</h1>
            <p class="lead rv" style="--rd:.1s">Enjoy simple, transparent pricing with one comprehensive plan built specifically
                for schools. Get clear, upfront costs with no surprises, no add-ons, and absolutely no hidden fees.
                Everything you need is included in a straightforward package designed to make budgeting easy and
                predictable from day one.</p>
        </div>
    </section>

    {{-- ═══════════════ PLANS ═══════════════ --}}
    <section style="padding:8px 0 96px;">
        <div class="container">
            <div class="pricecards" style="max-width:980px;">
                <div class="pc is-feat rv">
                    <span class="pc-flag">Standard Plan</span>
                    <div class="pc-name">School Plan</div>
                    <div class="pc-price">₹20<small>.83</small></div>
                    <div class="pc-per">per month/user <strong>(billed annually)</strong></div>
                    <div class="pc-note">= ₹250 / year &nbsp;·&nbsp; All features included</div>
                    <a href="{{ url('web/demo') }}" class="btn btn-primary">Get Started <x-site.icon name="arrow-right" class="ic-arrow" /></a>
                    <div class="pc-line"></div>
                    <ul>
                        <li><x-site.icon name="check" /> All LMS modules included</li>
                        <li><x-site.icon name="check" /> Unlimited teachers &amp; students</li>
                        <li><x-site.icon name="check" /> Attendance, Fee, Exam &amp; more</li>
                        <li><x-site.icon name="check" /> Mobile apps (iOS &amp; Android)</li>
                        <li><x-site.icon name="check" /> Dedicated onboarding support</li>
                        <li><x-site.icon name="check" /> Free updates forever</li>
                        <li><x-site.icon name="check" /> 99.9% uptime SLA</li>
                    </ul>
                    <div class="pc-sub">No credit card required to start</div>
                </div>

                <div class="pc rv" style="--rd:.08s">
                    <span class="pc-flag">&nbsp;</span>
                    <div class="pc-name">Enterprise</div>
                    <div class="pc-price">Custom</div>
                    <div class="pc-per">tailored pricing for your institution</div>
                    <div class="pc-note">Multi-campus &nbsp;·&nbsp; Large schools &nbsp;·&nbsp; Special needs</div>
                    <a href="{{ url('web/contact') }}" class="btn btn-dark">Contact Sales <x-site.icon name="arrow-right" class="ic-arrow" /></a>
                    <div class="pc-line"></div>
                    <ul>
                        <li><x-site.icon name="check" /> Everything in School Plan</li>
                        <li><x-site.icon name="check" /> Multi-campus management</li>
                        <li><x-site.icon name="check" /> Custom branding &amp; white-label</li>
                        <li><x-site.icon name="check" /> Dedicated account manager</li>
                        <li><x-site.icon name="check" /> On-site training &amp; onboarding</li>
                        <li><x-site.icon name="check" /> API integrations</li>
                        <li><x-site.icon name="check" /> Custom SLA &amp; data agreements</li>
                    </ul>
                    <div class="pc-sub">We'll respond within 24 hours</div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════ ZERO COST TO SCHOOLS ═══════════════ --}}
    <section class="section section-soft">
        <div class="container">
            <div class="split">
                <div class="split-text rv rv-l">
                    <span class="eyebrow">Zero Cost to Schools</span>
                    <h2 class="h2">The School Pays <span class="grad">Nothing</span></h2>
                    <p class="body-l" style="margin-top:20px;">Here's the best part — whatever the pricing is,
                        we charge it directly to the students. The school never pays us a single rupee. And on top of that,
                        every school gets its very own website, completely free of cost.</p>
                    <div class="info-list" style="margin-top:28px;">
                        @foreach ([['cap', 'Charged to Students, Not the School', 'Whatever the plan costs is collected straight from the students. The school carries zero financial burden — ever.'], ['gift', '₹0 Payable by the School', 'No licence fees, no setup charges, no hidden costs. The school pays absolutely nothing to go live with SUPERLMS.'], ['globe', 'Free School Website', 'Every school gets its own professional website — designed, hosted and maintained by us — free of cost.']] as $i => $z)
                            <div class="info rv" style="--rd:{{ $i * .08 }}s">
                                <div class="icon-sq"><x-site.icon :name="$z[0]" /></div>
                                <div>
                                    <div class="info-v" style="margin:0;font-size:17px;">{{ $z[1] }}</div>
                                    <p style="font-size:15px;color:var(--muted);line-height:1.6;margin-top:4px;">{{ $z[2] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="rv rv-r">
                    <div class="vpanel mint">
                        <div class="mk" style="--cyc:9s;width:100%;max-width:380px;">
                            <div class="mpanel round" style="padding:22px;">
                                <div class="mtitle"><x-site.icon name="school" /> School invoice · 2026-27</div>
                                <div class="msub" style="margin:4px 0 14px;">SUPERLMS · School Plan</div>
                                <div class="mline ap" style="--d:.2s"><span>All 50+ modules</span><b>Included</b></div>
                                <div class="mline ap" style="--d:.5s"><span>Setup &amp; onboarding</span><b>₹0</b></div>
                                <div class="mline ap" style="--d:.8s"><span>School website</span><b>Free</b></div>
                                <div class="mline ap" style="--d:1.1s"><span>Collected from students</span><b>₹250 / year</b></div>
                                <div class="zero-total pop" style="--d:1.6s">
                                    <span>Payable by the school</span>
                                    <b>₹0</b>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════ ALL MODULES ═══════════════ --}}
    <section class="section">
        <div class="container">
            <div class="head rv">
                <span class="eyebrow">All Modules</span>
                <h2 class="h2">Everything Included in <span class="grad">Your Plan</span></h2>
                <p class="lead">Everything listed below comes fully included
                    in your School Plan — no hidden charges, no add-ons, and no feature restrictions. Enjoy
                    unlimited access to all powerful modules, designed to streamline academics, administration,
                    communication, and reporting.</p>
            </div>
            <div class="mods">
                @foreach ($mods as $i => $m)
                    <div class="mod rv" style="--rd:{{ ($i % 3) * .05 }}s">
                        <div class="icon-sq"><x-site.icon :name="$m[0]" /></div>
                        <div>
                            <div class="mod-n">{{ $m[1] }} <x-site.icon name="check" /></div>
                            <div class="mod-d">{{ $m[2] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="band rv" style="margin-top:56px;">
                <div>
                    <div class="icon-sq" style="background:#fff;margin-bottom:14px;"><x-site.icon name="school" /></div>
                    <h3 class="h3">Ready to Get Started?</h3>
                    <p>Book your free personalized demo today and explore all modules in
                        action. Our team will guide you through every feature and handle the complete setup for you.</p>
                </div>
                <div class="actions">
                    <a href="{{ url('web/demo') }}" class="btn btn-primary">Book Free Demo</a>
                    <a href="{{ url('web/contact') }}" class="link-arrow">Talk to Sales <x-site.icon name="chevron-right" /></a>
                </div>
            </div>
        </div>
    </section>

@endsection
