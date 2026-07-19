@extends('school-site.kider.layout')
@php
    use App\Models\SchoolWebsite;
    $classes = !empty($c['classes']) ? $c['classes'] : [
        ['title' => 'Art & Drawing', 'age' => '3-5 Years', 'time' => '9-10 AM',  'capacity' => '30 Kids', 'image' => null],
        ['title' => 'Music & Dance', 'age' => '4-6 Years', 'time' => '10-11 AM', 'capacity' => '30 Kids', 'image' => null],
        ['title' => 'Sport & Games', 'age' => '5-7 Years', 'time' => '11-12 AM', 'capacity' => '30 Kids', 'image' => null],
    ];
    $team = !empty($c['team']) ? $c['team'] : [
        ['name' => 'Principal', 'role' => 'Head of School', 'photo' => null],
    ];
    $clsImgs = ['classes-1.jpg','classes-2.jpg','classes-3.jpg','classes-4.jpg','classes-5.jpg','classes-6.jpg'];
    $teamImgs = ['team-1.jpg','team-2.jpg','team-3.jpg'];
    $hasLogo = !empty($c['logo']);

    // Hero highlight stats — use configured, else derive sensible defaults.
    $stats = !empty($c['stats']) ? $c['stats'] : array_values(array_filter([
        !empty($c['board'])          ? ['value' => $c['board'], 'label' => 'Affiliated'] : null,
        !empty($c['medium'])         ? ['value' => $c['medium'], 'label' => 'Medium'] : null,
        !empty($c['affiliation_no']) ? ['value' => $c['affiliation_no'], 'label' => 'Affiliation No.'] : null,
    ]));

    // Facilities preview (top 6) + why-us — with graceful sample fallbacks.
    $facilities = !empty($c['facilities']) ? array_slice($c['facilities'], 0, 6) : [
        ['icon' => '📚', 'title' => 'Library',      'desc' => 'Books, magazines & digital resources.'],
        ['icon' => '🔬', 'title' => 'Science Labs', 'desc' => 'Well-equipped Physics, Chemistry & Biology labs.'],
        ['icon' => '💻', 'title' => 'Computer Lab',  'desc' => 'Modern systems with internet access.'],
        ['icon' => '⚽', 'title' => 'Playground',   'desc' => 'Spacious grounds for sports & play.'],
        ['icon' => '🚌', 'title' => 'Transport',    'desc' => 'Safe buses covering major routes.'],
        ['icon' => '🎨', 'title' => 'Art & Craft',  'desc' => 'Painting, drawing & creative activities.'],
    ];
    $whyUs = !empty($c['why_us']) ? $c['why_us'] : [
        ['icon' => '🌟', 'title' => 'Quality Education', 'desc' => 'A balanced focus on academics and values.'],
        ['icon' => '🛡️', 'title' => 'Safe & Caring',    'desc' => 'A secure, nurturing environment for every child.'],
        ['icon' => '🎯', 'title' => 'Holistic Growth',   'desc' => 'Sports, arts and activities alongside studies.'],
    ];
@endphp

@section('content')

    {{-- ══════════ HERO ══════════ --}}
    <section class="hero">
        <div class="grid-bg"></div>
        {{-- floating confetti blobs --}}
        <span class="blob float1" style="width:26px;height:26px;background:var(--c-yellow);top:120px;left:8%;"></span>
        <span class="blob sq float2" style="width:34px;height:34px;background:var(--c-pink);top:180px;right:44%;"></span>
        <span class="blob float3" style="width:18px;height:18px;background:var(--c-blue);bottom:90px;left:40%;"></span>
        <span class="blob float2" style="width:22px;height:22px;background:var(--c-mint);top:150px;right:6%;"></span>
        <div class="hero-inner">
            <div class="reveal">
                <span class="section-tag">🎈 {{ $c['tagline'] ?? 'Welcome' }}</span>
                <h1 class="hero-title">{{ $c['hero_title'] }}</h1>
                <p class="hero-sub">{{ $c['hero_subtitle'] }}</p>
                <div class="hero-actions">
                    @if ($site->isPageEnabled('contact'))
                        <a href="{{ url('contact') }}" class="btn btn-primary btn-lg">Enquire Now →</a>
                    @endif
                    @if ($site->isPageEnabled('classes'))
                        <a href="{{ url('classes') }}" class="btn btn-outline btn-lg">Our Classes</a>
                    @endif
                </div>
            </div>
            <div class="hero-media reveal">
                <span class="blob sq float1" style="width:64px;height:64px;background:var(--c-purple);opacity:.55;top:-18px;right:6%;z-index:1;"></span>
                <span class="blob float3" style="width:44px;height:44px;background:var(--c-peach);opacity:.6;bottom:8%;right:-10px;z-index:1;"></span>
                <div class="hero-visual {{ $hasLogo ? 'is-logo' : '' }}">
                    <img src="{{ SchoolWebsite::media($c['logo'] ?? null, 'carousel-1.jpg') }}" alt="{{ $c['school_name'] }}">
                </div>
                @if (!empty($c['tagline']))
                    <div class="hero-badge float1">
                        <div class="dot">✨</div>
                        <div><b>{{ \Illuminate\Support\Str::limit($c['tagline'], 22) }}</b><span>{{ $c['school_name'] }}</span></div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- ══════════ STATS BAND ══════════ --}}
    @if (!empty($stats))
    <section class="section" style="padding-top:0;">
        <div class="section-inner">
            <div class="cards-grid" style="grid-template-columns:repeat({{ min(count($stats), 4) }}, 1fr);">
                @foreach (array_slice($stats, 0, 4) as $s)
                    <div class="info-card reveal" style="text-align:center;padding:24px;">
                        <p class="gradient-text" style="font-family:'Baloo 2',cursive;font-weight:800;font-size:clamp(1.6rem,3vw,2.2rem);line-height:1;">{{ $s['value'] ?? '' }}</p>
                        <p class="info-desc" style="margin-top:6px;font-weight:600;">{{ $s['label'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ══════════ ABOUT ══════════ --}}
    <section class="section">
        <div class="split section-inner">
            <div class="reveal">
                <span class="section-tag">About Us</span>
                <h2 class="section-title">{{ $c['about_heading'] }}</h2>
                <p class="body-text">{{ $c['about_text'] }}</p>
                @if (!empty($c['about_text2']))<p class="body-text">{{ $c['about_text2'] }}</p>@endif
                @if ($site->isPageEnabled('about'))
                    <a class="btn btn-primary btn-lg" href="{{ url('about') }}" style="margin-top:8px;">Read More</a>
                @endif
            </div>
            <div class="split-media {{ $hasLogo ? 'is-logo' : '' }} reveal">
                <img src="{{ SchoolWebsite::media($c['logo'] ?? null, 'about-1.jpg') }}" alt="About {{ $c['school_name'] }}">
            </div>
        </div>
    </section>

    {{-- ══════════ WHY US ══════════ --}}
    <section class="section section-alt">
        <div class="section-inner">
            <div class="section-head reveal">
                <span class="section-tag">Why Choose Us</span>
                <h2 class="section-title">A Great Place to <span class="gradient-text">Learn &amp; Grow</span></h2>
            </div>
            <div class="cards-grid">
                @foreach ($whyUs as $w)
                    <div class="info-card reveal">
                        <div class="info-icon">{{ $w['icon'] ?? '⭐' }}</div>
                        <h3 class="info-title">{{ $w['title'] ?? '' }}</h3>
                        <p class="info-desc">{{ $w['desc'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ══════════ FACILITIES PREVIEW ══════════ --}}
    <section class="section">
        <div class="section-inner">
            <div class="section-head reveal">
                <span class="section-tag">Facilities</span>
                <h2 class="section-title">World-Class <span class="gradient-text">Infrastructure</span></h2>
                <p class="section-subtitle">Everything a child needs to learn, play and grow — under one roof.</p>
            </div>
            <div class="cards-grid">
                @foreach ($facilities as $f)
                    <div class="info-card reveal">
                        <div class="info-icon">{{ $f['icon'] ?? '⭐' }}</div>
                        <h3 class="info-title">{{ $f['title'] ?? '' }}</h3>
                        <p class="info-desc">{{ $f['desc'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
            @if ($site->isPageEnabled('facilities'))
                <div style="text-align:center;margin-top:36px;">
                    <a class="btn btn-outline btn-lg reveal" href="{{ url('facilities') }}">View All Facilities</a>
                </div>
            @endif
        </div>
    </section>

    {{-- ══════════ CLASSES ══════════ --}}
    @if ($site->isPageEnabled('classes'))
    <section class="section section-alt">
        <div class="section-inner">
            <div class="section-head reveal">
                <span class="section-tag">Programmes</span>
                <h2 class="section-title">Our <span class="gradient-text">Classes</span></h2>
                <p class="section-subtitle">Engaging, age-appropriate programmes designed to help every child learn and grow.</p>
            </div>
            <div class="cards-grid">
                @foreach (array_slice($classes, 0, 3) as $i => $cls)
                    <div class="class-card reveal">
                        <div class="class-thumb"><img src="{{ SchoolWebsite::media($cls['image'] ?? null, $clsImgs[$i % count($clsImgs)]) }}" alt=""></div>
                        <div class="class-body">
                            <p class="class-name">{{ $cls['title'] ?? '' }}</p>
                            <div class="class-meta">
                                <div><p class="k">Age</p><p class="v">{{ $cls['age'] ?? '-' }}</p></div>
                                <div><p class="k">Time</p><p class="v">{{ $cls['time'] ?? '-' }}</p></div>
                                <div><p class="k">Capacity</p><p class="v">{{ $cls['capacity'] ?? '-' }}</p></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ══════════ TEAM ══════════ --}}
    @if ($site->isPageEnabled('team'))
    <section class="section">
        <div class="section-inner">
            <div class="section-head reveal">
                <span class="section-tag">Our People</span>
                <h2 class="section-title">Meet Our <span class="gradient-text">Team</span></h2>
                <p class="section-subtitle">The dedicated educators and staff who make {{ $c['school_name'] }} special.</p>
            </div>
            <div class="cards-grid">
                @foreach (array_slice($team, 0, 3) as $i => $member)
                    <div class="team-card reveal">
                        <div class="team-photo"><img src="{{ SchoolWebsite::media($member['photo'] ?? null, $teamImgs[$i % count($teamImgs)]) }}" alt=""></div>
                        <div class="team-body">
                            <p class="team-name">{{ $member['name'] ?? '' }}</p>
                            <p class="team-role">{{ $member['role'] ?? '' }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ══════════ CTA ══════════ --}}
    <section class="cta-section">
        <div class="cta-card reveal">
            <h2 class="cta-title">{{ $c['cta_heading'] }}</h2>
            <p class="cta-desc">{{ $c['cta_text'] }}</p>
            <div class="cta-actions">
                <a class="btn btn-primary btn-lg" href="{{ $site->isPageEnabled('contact') ? url('contact') : $site->adminLoginUrl() }}">Get Started →</a>
                @if ($site->isPageEnabled('about'))
                    <a class="btn btn-outline btn-lg" href="{{ url('about') }}">Learn More</a>
                @endif
            </div>
        </div>
    </section>

@endsection
