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
                <p class="body-text">{{ $c['about_text'] }}</p>
                @if (!empty($c['about_text2']))<p class="body-text">{{ $c['about_text2'] }}</p>@endif
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
