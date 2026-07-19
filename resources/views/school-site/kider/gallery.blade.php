@extends('school-site.kider.layout')
@php
    use App\Models\SchoolWebsite;
    // Placeholder tiles from the template's bundled images until the school adds photos.
    $imgs = !empty($c['gallery']) ? $c['gallery'] : [
        ['image' => null, 'caption' => 'Campus'],
        ['image' => null, 'caption' => 'Classrooms'],
        ['image' => null, 'caption' => 'Activities'],
        ['image' => null, 'caption' => 'Sports'],
        ['image' => null, 'caption' => 'Events'],
        ['image' => null, 'caption' => 'Labs'],
    ];
    $fallbacks = ['classes-1.jpg','classes-2.jpg','classes-3.jpg','classes-4.jpg','classes-5.jpg','classes-6.jpg','about-1.jpg','carousel-1.jpg'];
@endphp

@section('content')
    @include('school-site.kider.partials.page-header', [
        'heading' => 'Gallery',
        'tag' => 'Photos',
        'sub' => 'A glimpse of life at ' . $c['school_name'] . '.',
    ])

    <section class="section">
        <div class="section-inner">
            <div class="cards-grid">
                @foreach ($imgs as $i => $g)
                    <div class="class-card reveal">
                        <div class="class-thumb" style="aspect-ratio:4/3;">
                            <img src="{{ SchoolWebsite::media($g['image'] ?? null, $fallbacks[$i % count($fallbacks)]) }}" alt="{{ $g['caption'] ?? '' }}">
                        </div>
                        @if (!empty($g['caption']))
                            <div class="class-body" style="padding:16px 18px;">
                                <p class="team-name" style="text-align:center;">{{ $g['caption'] }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
