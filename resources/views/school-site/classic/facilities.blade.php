@extends('school-site.classic.layout', ['title' => 'Features & Facilities'])
@php
    $facilities = !empty($c['facilities']) ? $c['facilities'] : [
        ['icon' => '🏫', 'title' => 'Classrooms',   'desc' => 'Spacious, hygienic classrooms with ergonomic furniture, green boards, digital classes and plenty of light and air.'],
        ['icon' => '📚', 'title' => 'Library',      'desc' => 'A wide collection of books, magazines and digital resources to build reading and research habits.'],
        ['icon' => '💻', 'title' => 'Computer Lab', 'desc' => 'Modern systems with internet access to build every student’s technology skills.'],
        ['icon' => '🔬', 'title' => 'Science Labs', 'desc' => 'Physics, Chemistry and Biology labs for experiments and hands-on learning.'],
        ['icon' => '📐', 'title' => 'Math Lab',     'desc' => 'Concepts learnt through practical activities and innovative tools.'],
        ['icon' => '🎨', 'title' => 'Art & Craft',  'desc' => 'Painting, drawing and craft that encourage creativity and imagination.'],
        ['icon' => '🚌', 'title' => 'Transport',    'desc' => 'Safe and comfortable buses covering the major routes.'],
        ['icon' => '⚽', 'title' => 'Playground',   'desc' => 'A large playground for sports and outdoor activities that build fitness and teamwork.'],
        ['icon' => '🌳', 'title' => 'Park',         'desc' => 'A green, well-kept park where students relax and connect with nature.'],
    ];
@endphp

@push('styles')
<style>
    .fac { display: grid; grid-template-columns: 300px 1fr; gap: 24px; align-items: center; padding: 18px 0; border-bottom: 1px solid var(--line); }
    .fac:first-child { padding-top: 0; }
    .fac:last-child { border-bottom: 0; padding-bottom: 0; }
    .fac .ph { aspect-ratio: 16 / 10; border: 1px solid var(--line); }
    .fac .wm small { font-size: 34px; letter-spacing: 0; opacity: 1; }
    .fac h2 { font-size: 17px; color: #222; margin-bottom: 4px; }
    .fac h2 span { margin-right: 6px; }
    .fac p { color: #444; }
    @media (max-width: 720px) { .fac { grid-template-columns: 1fr; gap: 12px; } }
</style>
@endpush

@section('content')
    @include('school-site.classic.partials.banner', ['heading' => 'Features & Facilities'])

    <section class="page">
        <div class="wrap">
            <div class="card">
                @foreach ($facilities as $f)
                    <div class="fac">
                        @include('school-site.classic.partials.ph', ['src' => $f['image'] ?? null, 'alt' => $f['title'] ?? '', 'label' => $f['icon'] ?? null, 'lb' => true])
                        <div>
                            <h2>@if (!empty($f['icon']))<span aria-hidden="true">{{ $f['icon'] }}</span>@endif{{ $f['title'] ?? '' }}</h2>
                            <p>{{ $f['desc'] ?? '' }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
