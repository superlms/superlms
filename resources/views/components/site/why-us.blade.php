@php
    $def      = config('website_pages.why-us', []);
    // Header copy from config; the detailed reason sections below are fixed content.
    $tag      = $def['tag']      ?? 'Why SUPERLMS';
    $title    = $def['title']    ?? '';
    $subtitle = $def['subtitle'] ?? '';

    // Quick "reasons at a glance" cards.
    $reasons = [
        ['icon' => 'grid', 'title' => 'All-in-One', 'desc' => 'Software plus everything your campus needs — in one partner.'],
        ['icon' => 'rupee', 'title' => 'Genuinely Affordable', 'desc' => 'Transparent per-student pricing with no hidden setup fees.'],
        ['icon' => 'repeat', 'title' => 'Hybrid Web + App', 'desc' => 'Full power on the web, dedicated apps on every phone.'],
        ['icon' => 'wrench', 'title' => 'We Set It Up', 'desc' => 'Integration and data upload handled entirely by our team.'],
        ['icon' => 'life-buoy', 'title' => 'Real Human Support', 'desc' => 'Onboarding, training and help over call, chat & WhatsApp.'],
        ['icon' => 'flag', 'title' => 'Built for India', 'desc' => 'Designed around Indian boards, fees and school workflows.'],
    ];

    // Feature chips (mirrors the Services range).
    $features = [
        ['cap', 'School LMS'], ['user-plus', 'Admissions'], ['calendar', 'Attendance & Timetable'], ['chart', 'Exams & Report Cards'],
        ['card', 'Online Fees'], ['library', 'Study Material & Quizzes'], ['bell', 'Notifications'], ['id-card', 'ID Cards'],
        ['landmark', 'School Loans'], ['flask', 'Labs Setup'], ['presentation', 'Smart Classrooms'], ['bus', 'Smart Transport'],
        ['shirt', 'Uniforms & Books'], ['laptop', 'Online Education'],
    ];

    $sections = [
        ['eyebrow' => '01 · One Partner, Everything', 'title' => 'Everything your school needs — in one place', 'scene' => 'tiles', 'panel' => 'mint',
         'chips' => [['grid', '50+ Modules', null], ['handshake', 'One Partner', null]],
         'desc' => 'Most schools juggle five different vendors for software, ID cards, labs, transport and more. With SUPERLMS you get it all from a single trusted partner. Our LMS covers the entire academic workflow, and our services equip the rest of your campus — so nothing falls through the cracks.',
         'features' => true, 'link' => [url('web/services'), 'Explore all services']],
        ['eyebrow' => '02 · Pricing', 'title' => 'Genuinely affordable — and transparent', 'scene' => 'price', 'panel' => 'sand',
         'chips' => [['ban', 'No Hidden Fees', null], ['gift', 'Free Onboarding', null]],
         'desc' => 'World-class school technology should not cost a fortune. SUPERLMS is priced so that schools of every size can afford it — one simple plan, billed per student, with every module included. No per-feature add-ons, no setup charges and absolutely no hidden fees.',
         'points' => ['One simple plan that unlocks all 50+ modules', 'Just ₹250 per student a year — billed annually', 'Free onboarding, data migration & staff training included', 'Custom Enterprise pricing for large multi-campus groups'],
         'link' => [url('web/pricing'), 'View full pricing']],
        ['eyebrow' => '03 · Hybrid Platform', 'title' => 'Powerful on the web, brilliant on mobile', 'scene' => 'hybrid', 'panel' => 'sky',
         'chips' => [['laptop', 'Web Dashboard', null], ['smartphone', '4 Mobile Apps', 'Admin · Teacher · Student · Parent']],
         'desc' => 'SUPERLMS is truly hybrid. Your office staff get a full-featured web dashboard for heavy administrative work, while teachers, students and parents stay connected through dedicated mobile apps. The same live data, everywhere — no compromises, no separate logins.',
         'points' => ['Complete web dashboard for admissions, fees, exams & reports', 'Dedicated Android & iOS apps for every role', 'Real-time sync — update once, see it everywhere', 'Mark attendance, pay fees and check results on the go']],
        ['eyebrow' => '04 · Setup & Integration', 'title' => 'We handle the integration & data upload', 'scene' => 'upload', 'panel' => 'blush',
         'chips' => [['upload', 'We Upload Your Data', null], ['zap', 'Live in Days', null]],
         'desc' => "Switching systems sounds scary — but with SUPERLMS you don't lift a finger. Our team takes your existing records and uploads everything for you: students, staff, classes, fee structures and more. We integrate, configure and verify, so your school goes live smoothly without disrupting daily work.",
         'points' => ['Full data migration from spreadsheets or your old system', 'Classes, sections, subjects & fee structures configured for you', 'Integration with payments, SMS & communication channels', 'Most schools go live within a few days — at no extra cost']],
        ['eyebrow' => '05 · Support', 'title' => 'Real humans who understand schools', 'scene' => 'support', 'panel' => 'sage',
         'chips' => [['phone', 'Call · Chat · WhatsApp', null], ['teacher', 'Staff Training', null]],
         'desc' => "You're never left figuring things out alone. From day one you get hands-on onboarding, staff training and friendly ongoing support from a team that genuinely understands how Indian schools run — reachable over call, chat and WhatsApp whenever you need them.",
         'points' => ['Guided onboarding & live staff training sessions', 'Support over call, chat and WhatsApp — not just email tickets', 'A real team that knows schools, not a generic call centre', 'Regular updates & new features at no extra charge']],
        ['eyebrow' => '06 · Built for India', 'title' => 'Designed for Indian schools, not adapted', 'scene' => 'india', 'panel' => 'lilac',
         'chips' => [['school', 'Indian Boards', null], ['banknote', 'Indian Fee Structures', null]],
         'desc' => "SUPERLMS is built from the ground up around how Indian schools actually work — your boards, your fee cycles, your report cards and your day-to-day realities. It's not a foreign product forced to fit; it's made here, for schools like yours, and improved with feedback from real classrooms every day.",
         'points' => ['Workflows tuned to Indian boards & academic calendars', 'Flexible fee structures, concessions & receipts', 'Trusted by hundreds of schools across the country', 'Shaped by direct feedback from real schools']],
    ];
@endphp
@extends('components.site.layout')

@section('title', 'Why Us')

@section('content')

    <section class="phero">
        <div class="container">
            <span class="eyebrow rv">{{ $tag }}</span>
            <h1 class="h1 rv" style="--rd:.05s">{{ $title }}</h1>
            <p class="lead rv" style="--rd:.1s">{{ $subtitle }}</p>
        </div>
    </section>

    {{-- Reasons at a glance --}}
    <section style="padding:8px 0 40px;">
        <div class="container">
            <div class="grid g3">
                @foreach ($reasons as $i => $r)
                    <div class="card card-soft rv" style="--rd:{{ ($i % 3) * .07 }}s">
                        <div class="icon-sq"><x-site.icon :name="$r['icon']" /></div>
                        <h3>{{ $r['title'] }}</h3>
                        <p>{{ $r['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Detailed reasons --}}
    @foreach ($sections as $i => $s)
        <section class="section {{ $i % 2 === 0 ? '' : 'section-soft' }}">
            <div class="container">
                <div class="split {{ $i % 2 === 0 ? 'flip' : '' }}">
                    <div class="split-text rv {{ $i % 2 === 0 ? 'rv-r' : 'rv-l' }}">
                        <span class="eyebrow">{{ $s['eyebrow'] }}</span>
                        <h2 class="h3">{{ $s['title'] }}</h2>
                        <p class="body-l">{{ $s['desc'] }}</p>
                        @if (!empty($s['features']))
                            <div class="chips" style="justify-content:flex-start;margin-top:22px;">
                                @foreach ($features as $f)<span class="chip" style="height:38px;font-size:14px;padding:0 14px;"><x-site.icon :name="$f[0]" /> {{ $f[1] }}</span>@endforeach
                            </div>
                        @endif
                        @if (!empty($s['points']))
                            <ul class="ticks">
                                @foreach ($s['points'] as $p)<li><span class="tk"><x-site.icon name="check" /></span>{{ $p }}</li>@endforeach
                            </ul>
                        @endif
                        @if (!empty($s['link']))
                            <div class="actions"><a href="{{ $s['link'][0] }}" class="btn btn-outline">{{ $s['link'][1] }} <x-site.icon name="arrow-right" class="ic-arrow" /></a></div>
                        @endif
                    </div>
                    <div class="rv {{ $i % 2 === 0 ? 'rv-l' : 'rv-r' }}">
                        <div class="vpanel {{ $s['panel'] }}" style="min-height:480px;">
                            @foreach ($s['chips'] as $k => $c)
                                <div class="vchip {{ $k === 0 ? 'c1' : 'c2' }}"><x-site.icon :name="$c[0]" /><div>{{ $c[1] }}@if ($c[2])<small>{{ $c[2] }}</small>@endif</div></div>
                            @endforeach
                            @if ($s['scene'] === 'upload')
                                <div style="width:100%;max-width:360px;">@include('components.site.mock.minis', ['m' => 'upload'])</div>
                            @else
                                @include('components.site.mock.scene', ['m' => $s['scene']])
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endforeach

    <section class="section-tight">
        <div class="container">
            <div class="cta rv rv-s">
                <h2 class="h2">See why schools switch to <span class="grad">SUPERLMS</span></h2>
                <p class="lead">Book a free, no-obligation demo and we'll show you exactly how SUPERLMS works for your school —
                    from the software to the services that equip your whole campus.</p>
                <div class="actions">
                    <a href="{{ url('web/demo') }}" class="btn btn-primary btn-lg">Request a Demo <x-site.icon name="arrow-right" class="ic-arrow" /></a>
                    <a href="{{ url('web/contact') }}" class="btn btn-white btn-lg">Talk to Us</a>
                </div>
            </div>
        </div>
    </section>

@endsection
