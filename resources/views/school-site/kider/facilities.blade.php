@extends('school-site.kider.layout')
@php
    // Sensible default facilities so the page looks complete before the school
    // adds its own (mirrors a typical CBSE school's infrastructure).
    $facilities = !empty($c['facilities']) ? $c['facilities'] : [
        ['icon' => '📚', 'title' => 'Library',       'desc' => 'A rich collection of books, magazines and digital resources.'],
        ['icon' => '💻', 'title' => 'Computer Lab',  'desc' => 'Modern systems with high-speed internet access.'],
        ['icon' => '🔬', 'title' => 'Science Labs',  'desc' => 'Well-equipped Physics, Chemistry and Biology labs.'],
        ['icon' => '🎨', 'title' => 'Art & Craft',   'desc' => 'Space for painting, drawing and creative activities.'],
        ['icon' => '🚌', 'title' => 'Transport',     'desc' => 'Safe, comfortable buses covering major routes.'],
        ['icon' => '⚽', 'title' => 'Playground',    'desc' => 'Spacious grounds for sports and outdoor activities.'],
        ['icon' => '🏫', 'title' => 'Smart Classrooms', 'desc' => 'Spacious, well-ventilated rooms with smart learning tools.'],
        ['icon' => '🏋️', 'title' => 'Gymnasium',     'desc' => 'A fully equipped gym for fitness and games.'],
        ['icon' => '🌳', 'title' => 'Green Campus',   'desc' => 'A clean, green environment that inspires learning.'],
    ];
@endphp

@section('content')
    @include('school-site.kider.partials.page-header', [
        'heading' => 'Facilities',
        'tag' => 'Infrastructure',
        'sub' => 'Everything a child needs to learn, play and grow — under one roof at ' . $c['school_name'] . '.',
    ])

    <section class="section">
        <div class="section-inner">
            <div class="cards-grid">
                @foreach ($facilities as $f)
                    <div class="info-card reveal">
                        <div class="info-icon">{{ $f['icon'] ?? '⭐' }}</div>
                        <h3 class="info-title">{{ $f['title'] ?? '' }}</h3>
                        <p class="info-desc">{{ $f['desc'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="cta-section">
        <div class="cta-card reveal">
            <h2 class="cta-title">{{ $c['cta_heading'] }}</h2>
            <p class="cta-desc">{{ $c['cta_text'] }}</p>
            <div class="cta-actions">
                <a class="btn btn-primary btn-lg" href="{{ $site->isPageEnabled('admission') ? url('admission') : ($site->isPageEnabled('contact') ? url('contact') : $site->adminLoginUrl()) }}">Enquire Now →</a>
            </div>
        </div>
    </section>
@endsection
