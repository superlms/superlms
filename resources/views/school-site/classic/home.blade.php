@extends('school-site.classic.layout')
@php
    use App\Models\SchoolWebsite;

    $stored = $site->content ?? [];

    // Captions for slides the school has not written its own for.
    $captions = array_values(array_filter([
        ['title' => $c['hero_title'], 'subtitle' => $c['address'] ?: $c['hero_subtitle']],
        ['title' => $c['cta_heading'], 'subtitle' => $c['cta_text']],
    ], fn ($s) => trim((string) $s['title']) !== ''));

    // The slider: the school's slides; else up to three gallery photos under
    // those captions; else the captions alone, each on the logo watermark.
    $slides = array_values(array_filter($c['hero_slides'] ?? [], fn ($s) =>
        trim(($s['image'] ?? '') . ($s['title'] ?? '') . ($s['subtitle'] ?? '')) !== ''));
    if (! $slides) {
        $photos = array_values(array_filter($c['gallery'] ?? [], fn ($g) => trim((string) ($g['image'] ?? '')) !== ''));
        foreach (array_slice($photos, 0, 3) as $i => $g) {
            $slides[] = ['image' => $g['image']] + ($captions[$i % max(1, count($captions))] ?? []);
        }
        if (! $slides) {
            $slides = $captions ?: [['title' => $c['school_name'], 'subtitle' => '']];
        }
    }

    // Photos for the two text-and-picture bands, in order of preference.
    $pool = [];
    foreach ($c['gallery'] ?? [] as $g) { $pool[] = $g['image'] ?? ''; }
    foreach ($c['pages'] ?? [] as $p) { $pool[] = $p['image'] ?? ''; }
    $pool = array_values(array_unique(array_filter(array_map('trim', $pool))));

    $welcome = !empty($stored['about_heading']) ? $c['about_heading'] : 'Welcome to ' . $c['school_name'];
    $lead    = trim((string) ($stored['tagline'] ?? ''));

    $facilities = !empty($c['facilities']) ? $c['facilities'] : [
        ['icon' => '🏫', 'title' => 'Classroom',    'desc' => 'Spacious, well-ventilated classrooms with ergonomic furniture and smart learning tools.'],
        ['icon' => '📚', 'title' => 'Library',      'desc' => 'Books, magazines and digital resources that build reading and research habits.'],
        ['icon' => '💻', 'title' => 'Computer Lab', 'desc' => 'Modern systems with internet access to build every student’s technology skills.'],
        ['icon' => '🔬', 'title' => 'Science Labs', 'desc' => 'Well-equipped Physics, Chemistry and Biology labs for hands-on learning.'],
        ['icon' => '🚌', 'title' => 'Transport',    'desc' => 'Safe and comfortable buses covering the major routes.'],
        ['icon' => '⚽', 'title' => 'Playground',   'desc' => 'A large playground for sports, games and outdoor activities.'],
    ];
    $btnColours = ['#E8641B', '#111111', '#8CC63F', '#E6007E', '#F5A000', '#7B1FA2'];

    $philosophy = trim((string) ($c['philosophy'] ?? ''));
    $campus     = $site->pageContent('campus-life');
    $campusOn   = $site->isPageEnabled('campus-life') && trim($campus['body']) !== '';
@endphp

@push('styles')
<style>
    /* ══════════ SLIDER ══════════ */
    .slider { position: relative; height: clamp(240px, 41vw, 560px); overflow: hidden; background: var(--navy2); }
    .slide { position: absolute; inset: 0; transform: translateX(100%); transition: transform .9s cubic-bezier(.65, 0, .35, 1); }
    .slide.on { transform: none; }
    .slide > img { width: 100%; height: 100%; object-fit: cover; }
    .slide.on > img { animation: kb 7s ease-out forwards; }
    @keyframes kb { from { transform: scale(1); } to { transform: scale(1.06); } }
    .slide::after { content: ''; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(0,0,0,0) 35%, rgba(0,0,0,.38) 100%); pointer-events: none; }
    .slide .wm { display: none; background: linear-gradient(135deg, var(--dark) 0%, var(--navy2) 100%); }
    .slide.is-mark .wm { display: flex; }
    .slide .wm::before { opacity: .07; background-size: 90px; }
    .slide .wm img, .slide .wm svg { opacity: .16; max-height: 78%; max-width: 40%; }
    .slide-cap { position: absolute; z-index: 2; left: 0; right: 0; top: 50%; transform: translateY(-15%); text-align: center; padding: 0 64px; }
    .slide-cap h2 { color: var(--lime); font-weight: 700; text-transform: uppercase; font-size: clamp(20px, 3.4vw, 46px); line-height: 1.15; text-shadow: 3px 2px 3px rgba(0,0,0,.5); }
    .slide-cap p { color: #fff; font-size: clamp(13px, 1.35vw, 18px); margin-top: 8px; text-shadow: 1px 1px 3px rgba(0,0,0,.8); }
    .sl-btn { position: absolute; z-index: 3; top: 50%; transform: translateY(-50%); width: 44px; height: 44px; border-radius: 50%; border: 0; background: rgba(0,0,0,.32); color: #fff; font-size: 26px; line-height: 1; cursor: pointer; transition: background .2s; }
    .sl-btn:hover { background: rgba(0,0,0,.6); }
    .sl-prev { left: 14px; } .sl-next { right: 14px; }
    .sl-dots { position: absolute; z-index: 3; left: 0; right: 0; bottom: 52px; display: flex; justify-content: center; gap: 8px; }
    .sl-dots button { width: 11px; height: 11px; border-radius: 50%; border: 2px solid #fff; background: transparent; cursor: pointer; padding: 0; }
    .sl-dots button.on { background: #fff; }
    @media (prefers-reduced-motion: reduce) { .slide { transition: none; } .slide.on > img { animation: none; } }

    /* ══════════ WELCOME ══════════ */
    .welcome-wrap { position: relative; z-index: 4; margin-top: -40px; }
    .welcome-body { display: grid; grid-template-columns: 230px 1fr; gap: 30px; align-items: center; margin-top: 16px; }
    .welcome-logo { display: flex; justify-content: center; }
    .welcome-logo img { max-height: 210px; width: auto; }
    .welcome-logo svg { width: 200px; height: 200px; }

    /* ══════════ FEATURES ══════════ */
    .feat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; }
    .feat { background: #fff; border: 1px solid var(--line); display: flex; flex-direction: column; box-shadow: 0 1px 3px rgba(0,0,0,.04); }
    .feat h3 { font-size: 15px; font-weight: 500; color: var(--muted); padding: 10px 14px; }
    .feat .ph { aspect-ratio: 16 / 10; }
    .feat .wm small { font-size: 34px; letter-spacing: 0; opacity: 1; }
    .feat p { padding: 14px 14px 6px; color: #444; font-size: 13.5px; flex: 1; }
    .feat .btn { align-self: flex-start; margin: 8px 14px 18px; font-size: 12.5px; padding: 8px 18px; }

    /* ══════════ TEXT + PICTURE BANDS ══════════ */
    .band { background: #fff; border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); }
    .band-in { display: grid; grid-template-columns: 1fr 1fr; min-height: 420px; }
    .band-text { padding: 46px clamp(20px, 4vw, 56px); align-self: center; }
    .band-text h2 { font-size: clamp(20px, 2.1vw, 26px); font-weight: 700; line-height: 1.3; color: #111; margin-bottom: 16px; }
    .band .ph { min-height: 300px; height: 100%; }
    .band.rev .band-in > .ph { order: -1; }
    .band + .band { border-top: 0; }

    /* ══════════ ENROLL ══════════ */
    .enroll { background: linear-gradient(90deg, #FF5A00 0%, #FF7A00 55%, #FF9A1F 100%); color: #fff; padding: 46px 0; }
    .enroll h2 { font-size: clamp(20px, 2.1vw, 26px); font-weight: 700; }
    .enroll .enroll-lead { font-weight: 700; font-size: 13.5px; margin-top: 2px; }
    .enroll-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 36px; margin-top: 20px; font-size: 13.5px; }
    .enroll p { margin-bottom: 12px; }
    .enroll ul, .enroll ol { padding-left: 20px; margin-bottom: 12px; }
    .enroll li { margin-bottom: 3px; }
    .enroll .btn { background: #fff; color: #E85A00; }
    .enroll .btn-line { background: transparent; color: #fff; border: 1px solid rgba(255,255,255,.8); }

    .loc { padding: 44px 0 0; }
    .loc .map { margin-bottom: -1px; }

    @media (max-width: 1020px) { .feat-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 720px) {
        .slider { height: clamp(280px, 78vw, 380px); }
        .slide-cap { padding: 0 44px; transform: translateY(-62%); }
        .sl-btn { width: 34px; height: 34px; font-size: 20px; }
        .sl-dots { bottom: 36px; }
        .welcome-wrap { margin-top: -26px; }
        .welcome-body { grid-template-columns: 1fr; gap: 18px; }
        .welcome-logo img { max-height: 140px; }
        .welcome-logo svg { width: 140px; height: 140px; }
        .feat-grid { grid-template-columns: 1fr; }
        .band-in { grid-template-columns: 1fr; }
        .band .ph { min-height: 220px; aspect-ratio: 16 / 10; }
        .band.rev .band-in > .ph { order: 0; }
        .band-text { padding: 28px 18px; }
        .enroll-grid { grid-template-columns: 1fr; gap: 6px; }
    }
</style>
@endpush

@section('content')

    {{-- ══════════ SLIDER ══════════ --}}
    <section class="slider" id="slider" aria-roledescription="carousel" aria-label="{{ $c['school_name'] }}">
        @foreach ($slides as $i => $s)
            @php $slideUrl = SchoolWebsite::media($s['image'] ?? null); @endphp
            <div class="slide {{ $i === 0 ? 'on' : '' }} {{ $slideUrl ? '' : 'is-mark' }}" aria-roledescription="slide" aria-label="{{ $i + 1 }} of {{ count($slides) }}">
                @if ($slideUrl)
                    <img src="{{ $slideUrl }}" alt="{{ $s['title'] ?? '' }}" onerror="this.parentNode.classList.add('is-mark');this.remove();">
                @endif
                @include('school-site.classic.partials.watermark', ['label' => null])
                @if (trim(($s['title'] ?? '') . ($s['subtitle'] ?? '')) !== '')
                    <div class="slide-cap">
                        @if (!empty($s['title']))<h2>{{ $s['title'] }}</h2>@endif
                        @if (!empty($s['subtitle']))<p>{{ $s['subtitle'] }}</p>@endif
                    </div>
                @endif
            </div>
        @endforeach
        @if (count($slides) > 1)
            <button class="sl-btn sl-prev" type="button" aria-label="Previous slide">&#8249;</button>
            <button class="sl-btn sl-next" type="button" aria-label="Next slide">&#8250;</button>
            <div class="sl-dots">
                @foreach ($slides as $i => $s)
                    <button type="button" class="{{ $i === 0 ? 'on' : '' }}" aria-label="Slide {{ $i + 1 }}"></button>
                @endforeach
            </div>
        @endif
    </section>

    {{-- ══════════ WELCOME ══════════ --}}
    <div class="wrap welcome-wrap">
        <div class="card">
            <h2 class="title">{{ $welcome }}</h2>
            @if ($lead)<p class="lead">{{ $lead }}</p>@endif
            <div class="welcome-body">
                <div class="welcome-logo">
                    @if (SchoolWebsite::media($c['logo'] ?? null))
                        <img src="{{ SchoolWebsite::media($c['logo']) }}" alt="{{ $c['school_name'] }}">
                    @else
                        @include('school-site.classic.partials.seal', ['center' => null, 'ring' => $c['school_name'], 'sealId' => 'welcome'])
                    @endif
                </div>
                <div class="prose">
                    @include('school-site.classic.partials.paras', ['text' => $c['about_text']])
                    @if (!empty($c['about_text2']))
                        @include('school-site.classic.partials.paras', ['text' => $c['about_text2']])
                    @endif
                    @if ($site->isPageEnabled('about'))
                        <p><a class="btn" href="{{ url('about') }}">Read More</a></p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════ FEATURES & FACILITIES ══════════ --}}
    <section class="page">
        <div class="wrap">
            <div class="sec-head">
                <h2>Features &amp; Facilities</h2>
                <p>At {{ $c['school_name'] }}, we are committed to the best facilities for the overall development of your child.</p>
            </div>
            <div class="feat-grid">
                @foreach ($facilities as $i => $f)
                    <article class="feat">
                        <h3>{{ $f['title'] ?? '' }}</h3>
                        @include('school-site.classic.partials.ph', ['src' => $f['image'] ?? null, 'alt' => $f['title'] ?? '', 'label' => $f['icon'] ?? null])
                        <p>{{ $f['desc'] ?? '' }}</p>
                        @if ($site->isPageEnabled('facilities'))
                            <a class="btn" href="{{ url('facilities') }}" style="background: {{ $btnColours[$i % count($btnColours)] }}">Read More</a>
                        @endif
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ══════════ PHILOSOPHY (text · picture) ══════════ --}}
    @if ($philosophy !== '' || !empty($c['vision']) || !empty($c['mission']))
        <section class="band">
            <div class="band-in">
                <div class="band-text prose">
                    @if ($philosophy !== '')
                        <h2>Our Philosophy</h2>
                        @include('school-site.classic.partials.paras', ['text' => $philosophy])
                    @else
                        <h2>Our Vision &amp; Mission</h2>
                    @endif
                    @if (!empty($c['vision']))<p><b>Vision —</b> {{ $c['vision'] }}</p>@endif
                    @if (!empty($c['mission']))<p><b>Mission —</b> {{ $c['mission'] }}</p>@endif
                </div>
                @include('school-site.classic.partials.ph', ['src' => $pool[0] ?? null, 'alt' => $c['school_name']])
            </div>
        </section>
    @endif

    {{-- ══════════ CAMPUS LIFE (picture · text) ══════════ --}}
    @if ($campusOn)
        <section class="band rev">
            <div class="band-in">
                <div class="band-text prose">
                    <h2>{{ $campus['heading'] }}</h2>
                    @include('school-site.classic.partials.paras', ['text' => \Illuminate\Support\Str::limit($campus['body'], 1400)])
                    <p><a class="btn" href="{{ url('campus-life') }}">Read More</a></p>
                </div>
                @include('school-site.classic.partials.ph', ['src' => $campus['image'] ?: ($pool[1] ?? null), 'alt' => $campus['heading']])
            </div>
        </section>
    @endif

    {{-- ══════════ ENROLL NOW ══════════ --}}
    <section class="enroll">
        <div class="wrap">
            <h2>{{ $c['cta_heading'] }}</h2>
            <p class="enroll-lead">{{ $c['cta_text'] }}</p>
            <div class="enroll-grid">
                <div>
                    <p>{{ $c['admission_intro'] }}</p>
                    @if (!empty($c['admission_session']))<p><b>Admission Schedule:</b> {{ $c['admission_session'] }}</p>@endif
                    @if (!empty($c['admission_steps']))
                        <p><b>How to Apply:</b></p>
                        <ol>
                            @foreach ($c['admission_steps'] as $step)
                                <li><b>{{ $step['title'] ?? '' }}</b>@if (!empty($step['desc'])) — {{ $step['desc'] }}@endif</li>
                            @endforeach
                        </ol>
                    @endif
                </div>
                <div>
                    @if (!empty($c['documents_required']))
                        <p><b>Documents Required for Admission:</b></p>
                        <ul>
                            @foreach ($c['documents_required'] as $d)
                                <li>{{ $d['text'] ?? '' }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <p>
                        @if ($site->isPageEnabled('admission'))
                            Fill in the online admission form, or
                        @else
                            Interested parents may
                        @endif
                        contact the school office for admission forms and counselling during office hours.
                    </p>
                    <p style="display:flex;gap:10px;flex-wrap:wrap;">
                        @if ($site->isPageEnabled('admission'))<a class="btn" href="{{ url('admission') }}">Apply Online</a>@endif
                        @if (!empty($c['phone']))<a class="btn btn-line" href="tel:{{ preg_replace('/[^0-9+]/', '', explode(',', $c['phone'])[0]) }}">Call {{ trim(explode(',', $c['phone'])[0]) }}</a>@endif
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- ══════════ LOCATION ══════════ --}}
    <section class="loc">
        <div class="wrap">
            <div class="sec-head">
                <h2>Our Location</h2>
                <p>Reach us at the school or just drop in an email{{ !empty($c['email']) ? ' — ' . $c['email'] : '' }}.</p>
            </div>
        </div>
        @include('school-site.classic.partials.map', ['height' => 400])
    </section>

@endsection

@push('scripts')
<script>
(function () {
    var root = document.getElementById('slider');
    var slides = root ? root.querySelectorAll('.slide') : [];
    if (slides.length < 2) return;
    var dots = root.querySelectorAll('.sl-dots button'), at = 0, timer = null;
    var still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Slide `to` in from the side `dir` points to while the current one leaves the other way.
    function go(to, dir) {
        to = (to + slides.length) % slides.length;
        if (to === at) return;
        dir = dir || (to > at ? 1 : -1);
        var cur = slides[at], nxt = slides[to];
        nxt.style.transition = 'none';
        nxt.style.transform = 'translateX(' + (dir * 100) + '%)';
        void nxt.offsetWidth;
        nxt.style.transition = '';
        nxt.style.transform = '';
        nxt.classList.add('on');
        cur.style.transform = 'translateX(' + (-dir * 100) + '%)';
        cur.classList.remove('on');
        dots[at] && dots[at].classList.remove('on');
        dots[to] && dots[to].classList.add('on');
        at = to;
    }
    function play() { if (!still) { stop(); timer = setInterval(function () { go(at + 1, 1); }, 5000); } }
    function stop() { clearInterval(timer); timer = null; }

    root.querySelector('.sl-prev').addEventListener('click', function () { go(at - 1, -1); play(); });
    root.querySelector('.sl-next').addEventListener('click', function () { go(at + 1, 1); play(); });
    dots.forEach(function (d, i) { d.addEventListener('click', function () { go(i); play(); }); });
    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', play);
    document.addEventListener('visibilitychange', function () { document.hidden ? stop() : play(); });

    var x0 = null;
    root.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; stop(); }, { passive: true });
    root.addEventListener('touchend', function (e) {
        if (x0 === null) return;
        var dx = e.changedTouches[0].clientX - x0; x0 = null;
        if (Math.abs(dx) > 40) go(at + (dx < 0 ? 1 : -1), dx < 0 ? 1 : -1);
        play();
    });
    play();
})();
</script>
@endpush
