@extends('school-site.kider.layout')
@php use App\Models\SchoolWebsite; @endphp

@section('content')
    @include('school-site.kider.partials.page-header', ['heading' => 'About Us', 'tag' => 'Who We Are'])

    {{-- ══════════ ABOUT ══════════ --}}
    <section class="section">
        <div class="split section-inner">
            <div class="reveal">
                <span class="section-tag">Our Story</span>
                <h2 class="section-title">{{ $c['about_heading'] }}</h2>
                @if (!empty($c['motto']))<p class="team-role" style="font-size:15px;margin-bottom:12px;">“{{ $c['motto'] }}”</p>@endif
                <p class="body-text">{{ $c['about_text'] }}</p>
                @if (!empty($c['about_text2']))<p class="body-text">{{ $c['about_text2'] }}</p>@endif
                @if (!empty($c['history_text']))<p class="body-text">{{ $c['history_text'] }}</p>@endif
                @if (!empty($c['philosophy']))<p class="body-text">{{ $c['philosophy'] }}</p>@endif
                <div style="display:flex;flex-wrap:wrap;gap:20px;margin-top:18px;">
                    @if(!empty($c['phone']))<p class="body-text" style="margin:0;">📞 {{ $c['phone'] }}</p>@endif
                    @if(!empty($c['email']))<p class="body-text" style="margin:0;">✉️ {{ $c['email'] }}</p>@endif
                </div>
            </div>
            <div class="split-media {{ !empty($c['logo']) ? 'is-logo' : '' }} reveal">
                <img src="{{ SchoolWebsite::media($c['logo'] ?? null, 'about-1.jpg') }}" alt="About {{ $c['school_name'] }}">
            </div>
        </div>
    </section>

    {{-- ══════════ AFFILIATION / AT A GLANCE ══════════ --}}
    @php
        $glance = array_values(array_filter([
            !empty($c['board'])          ? ['k' => 'Board',          'v' => $c['board']] : null,
            !empty($c['medium'])         ? ['k' => 'Medium',         'v' => $c['medium']] : null,
            !empty($c['affiliation_no']) ? ['k' => 'Affiliation No.','v' => $c['affiliation_no']] : null,
            !empty($c['school_code'])    ? ['k' => 'School Code',    'v' => $c['school_code']] : null,
        ]));
    @endphp
    @if (!empty($glance))
    <section class="section" style="padding-top:0;">
        <div class="section-inner">
            <div class="cards-grid" style="grid-template-columns:repeat({{ min(count($glance), 4) }}, 1fr);">
                @foreach ($glance as $g)
                    <div class="info-card reveal" style="text-align:center;padding:22px;">
                        <p class="gradient-text" style="font-family:'Baloo 2',cursive;font-weight:800;font-size:clamp(1.2rem,2.4vw,1.6rem);line-height:1.1;">{{ $g['v'] }}</p>
                        <p class="info-desc" style="margin-top:6px;font-weight:600;">{{ $g['k'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ══════════ VISION / MISSION ══════════ --}}
    @if (!empty($c['vision']) || !empty($c['mission']))
    <section class="section section-alt">
        <div class="section-inner">
            <div class="cards-grid" style="grid-template-columns:repeat(2,1fr);">
                @if (!empty($c['vision']))
                    <div class="info-card reveal">
                        <div class="info-icon">🌟</div>
                        <h3 class="info-title">Our Vision</h3>
                        <p class="info-desc">{{ $c['vision'] }}</p>
                    </div>
                @endif
                @if (!empty($c['mission']))
                    <div class="info-card reveal">
                        <div class="info-icon">🎯</div>
                        <h3 class="info-title">Our Mission</h3>
                        <p class="info-desc">{{ $c['mission'] }}</p>
                    </div>
                @endif
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
            </div>
        </div>
    </section>
@endsection
