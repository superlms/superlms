@php
    $def      = config('website_pages.services', []);
    // Header copy from config; the detailed service sections below are fixed content.
    $tag      = $def['tag']      ?? 'Our Services';
    $title    = $def['title']    ?? '';
    $subtitle = $def['subtitle'] ?? '';

    $services = [
        ['id' => 'lms', 'ic' => 'cap', 'nav' => 'School LMS', 'eyebrow' => '01 · Software', 'title' => 'All-in-One School LMS',
         'scene' => ['feature', 'analytics'], 'panel' => 'mint',
         'chips' => [['grid', '50+ Modules', null], ['smartphone', '4 Apps', 'Admin · Teacher · Student · Parent']],
         'desc' => 'At the heart of everything is the SUPERLMS Learning Management System — a complete, affordable platform that runs your entire school from a single login. From admissions to report cards, attendance to fees, everything is connected and available on the web and on dedicated mobile apps.',
         'points' => ['Admissions, attendance, timetable, exams & report cards', 'Online fee collection with instant receipts & reconciliation', 'Study material, books, quizzes & parent communication', 'Separate apps for admins, teachers, students and parents', 'Transparent per-student pricing with free onboarding & training']],
        ['id' => 'idcards', 'ic' => 'id-card', 'nav' => 'ID Cards', 'eyebrow' => '02 · Identity', 'title' => 'Student & Staff ID Cards',
         'scene' => ['feature', 'idcards'], 'panel' => 'sage',
         'chips' => [['sparkles', 'Custom Branding', null], ['zap', 'Bulk Printing', null]],
         'desc' => 'We design, print and deliver professional ID cards for your students and staff — generated directly from your LMS data, so there is no manual typing or design hassle. Pick a template, approve the branding, and we handle the rest in bulk.',
         'points' => ["Beautiful templates customised with your school's branding", 'Auto-filled from student & staff records — zero data entry', 'Durable PVC printed cards delivered to your school', 'Optional QR / barcode for attendance & library access', 'Reprints for new admissions handled any time']],
        ['id' => 'loans', 'ic' => 'landmark', 'nav' => 'School Loans', 'eyebrow' => '03 · Finance', 'title' => 'School Loans & Financing',
         'scene' => ['scene', 'loan'], 'panel' => 'sand',
         'chips' => [['file-text', 'Easy Paperwork', null], ['calendar', 'Flexible EMIs', null]],
         'desc' => 'Growing a school takes capital. Through our lending partners, we help schools access easy loans and financing for infrastructure, expansion, equipment and working capital — with minimal paperwork and guidance at every step.',
         'points' => ['Tie-ups with trusted banks & NBFCs for school financing', 'Funding for buildings, labs, buses, panels & renovations', 'Quick, simplified documentation with hands-on support', 'Flexible repayment options and comfortable EMIs', 'End-to-end guidance from application to disbursal']],
        ['id' => 'labs', 'ic' => 'flask', 'nav' => 'Labs Setup', 'eyebrow' => '04 · Infrastructure', 'title' => 'Computer & Science Labs Setup',
         'scene' => ['scene', 'labs'], 'panel' => 'sky',
         'chips' => [['laptop', 'Computer Labs', null], ['flask', 'Science Labs', null]],
         'desc' => 'We set up fully-equipped computer and science laboratories — from planning the layout to supplying, installing and maintaining everything. Give your students hands-on, practical learning environments without the procurement headache.',
         'points' => ['Computers, networking, furniture & complete lab layout', 'Physics, chemistry & biology lab equipment and kits', 'Professional installation, setup & safety compliance', 'Maintenance & AMC so labs keep running smoothly', 'Packages to fit every budget and school size']],
        ['id' => 'smart', 'ic' => 'presentation', 'nav' => 'Smart Classrooms', 'eyebrow' => '05 · Classroom', 'title' => 'Smart Classrooms & Resource Upgrades',
         'scene' => ['scene', 'smartclass'], 'panel' => 'lilac',
         'chips' => [['monitor', 'Interactive Panels', null], ['video', 'Digital Content', null]],
         'desc' => 'Transform ordinary classrooms into engaging, interactive learning spaces. We upgrade your resources with interactive flat panels, smart boards and ready-made digital curriculum content — plus the training your teachers need to use them confidently.',
         'points' => ['Interactive flat panels & smart boards installed end-to-end', 'Curriculum-aligned digital content for every subject', 'Projectors, audio systems & modern classroom furniture', 'Hands-on teacher training to make the most of the tech', 'Ongoing support and upgrades as your needs grow']],
        ['id' => 'transport', 'ic' => 'bus', 'nav' => 'Smart Transport', 'eyebrow' => '06 · Safety', 'title' => 'Smart & Safe Transport',
         'scene' => ['scene', 'transport'], 'panel' => 'mint',
         'chips' => [['navigation', 'Live GPS', null], ['camera', 'CCTV & Biometric', null]],
         'desc' => 'Keep every child safe on the journey to and from school. We fit your buses with GPS tracking, biometric attendance, CCTV cameras and live monitoring — giving parents peace of mind and your school complete visibility over its fleet.',
         'points' => ['Real-time GPS tracking of every bus on a live map', 'Biometric / RFID boarding & de-boarding attendance', 'CCTV cameras inside buses for continuous monitoring', 'Parent alerts for pick-up, drop and route updates', 'Speed, route & driver monitoring with reports']],
        ['id' => 'essentials', 'ic' => 'shirt', 'nav' => 'Uniforms & Books', 'eyebrow' => '07 · Essentials', 'title' => 'Uniforms, Books & Stationery',
         'scene' => ['scene', 'order'], 'panel' => 'blush',
         'chips' => [['book', 'Books & Stationery', null], ['truck', 'Doorstep Delivery', null]],
         'desc' => "We supply quality school uniforms, books and stationery to students — so parents don't have to run around markets and schools get consistent, affordable essentials delivered on time, every session.",
         'points' => ["Custom-stitched uniforms in your school's design & colours", 'Comfortable, durable fabric built for daily wear', 'Complete book sets, notebooks & stationery bundles', 'Delivery to the school or directly to families', 'Affordable, transparent pricing with bulk savings']],
        ['id' => 'online', 'ic' => 'laptop', 'nav' => 'Online Education', 'eyebrow' => '08 · Learning', 'title' => 'Online Education',
         'scene' => ['scene', 'liveclass'], 'panel' => 'sky',
         'chips' => [['video', 'Live & Recorded', null], ['pen', 'Tests & Doubts', null]],
         'desc' => "Learning shouldn't stop at the school gate. We provide online education for students through live and recorded classes, digital study material and assessments — all accessible from the SUPERLMS app, so students can keep learning anytime, anywhere.",
         'points' => ['Live online classes plus recorded lectures to revisit', 'Digital notes, books and study material by subject', 'Online quizzes & assessments with instant results', 'Doubt-solving support to keep students on track', 'Learn anywhere, on any device, through the mobile app']],
    ];
@endphp
@extends('components.site.layout')

@section('title', 'Services')

@section('content')

    <section class="phero">
        <div class="container">
            <span class="eyebrow rv">{{ $tag }}</span>
            <h1 class="h1 rv" style="--rd:.05s">{{ $title }}</h1>
            <p class="lead rv" style="--rd:.1s">{{ $subtitle }}</p>
            <div class="chips rv" style="--rd:.15s;margin-top:34px;">
                @foreach ($services as $s)
                    <a class="chip" href="#{{ $s['id'] }}"><x-site.icon :name="$s['ic']" /> {{ $s['nav'] }}</a>
                @endforeach
            </div>
        </div>
    </section>

    @foreach ($services as $i => $s)
        <section class="section {{ $i % 2 === 0 ? '' : 'section-soft' }}" id="{{ $s['id'] }}" style="scroll-margin-top:72px;">
            <div class="container">
                <div class="split {{ $i % 2 === 0 ? '' : 'flip' }}">
                    <div class="split-text rv {{ $i % 2 === 0 ? 'rv-l' : 'rv-r' }}">
                        <span class="eyebrow">{{ $s['eyebrow'] }}</span>
                        <h2 class="h3">{{ $s['title'] }}</h2>
                        <p class="body-l">{{ $s['desc'] }}</p>
                        <ul class="ticks">
                            @foreach ($s['points'] as $p)<li><span class="tk"><x-site.icon name="check" /></span>{{ $p }}</li>@endforeach
                        </ul>
                    </div>
                    <div class="rv {{ $i % 2 === 0 ? 'rv-r' : 'rv-l' }}">
                        <div class="vpanel {{ $s['panel'] }}" style="min-height:500px;">
                            @foreach ($s['chips'] as $k => $c)
                                <div class="vchip {{ $k === 0 ? 'c1' : 'c2' }}"><x-site.icon :name="$c[0]" /><div>{{ $c[1] }}@if ($c[2])<small>{{ $c[2] }}</small>@endif</div></div>
                            @endforeach
                            @include('components.site.mock.' . $s['scene'][0], ['m' => $s['scene'][1]])
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endforeach

    <section class="section-tight">
        <div class="container">
            <div class="cta rv rv-s">
                <h2 class="h2">Ready to digitise &amp; equip your <span class="grad">school?</span></h2>
                <p class="lead">Tell us what your school needs — software, infrastructure, transport, essentials or online
                    learning — and we'll tailor the right mix of SUPERLMS services for you.</p>
                <div class="actions">
                    <a href="{{ url('web/demo') }}" class="btn btn-primary btn-lg">Request a Demo <x-site.icon name="arrow-right" class="ic-arrow" /></a>
                    <a href="{{ url('web/contact') }}" class="btn btn-white btn-lg">Talk to Us</a>
                </div>
            </div>
        </div>
    </section>

@endsection
