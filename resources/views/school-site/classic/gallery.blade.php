@extends('school-site.classic.layout', ['title' => 'Photo Gallery'])
@php
    // Watermarked tiles until the school adds its photos.
    $photos = !empty($c['gallery']) ? $c['gallery'] : array_map(
        fn ($cap) => ['image' => null, 'caption' => $cap],
        ['Campus', 'Classrooms', 'Activities', 'Sports', 'Events', 'Labs']
    );
@endphp

@section('content')
    @include('school-site.classic.partials.banner', ['heading' => 'Photo Gallery'])

    <section class="page">
        <div class="wrap">
            <div class="card">
                <div class="photos">
                    @foreach ($photos as $g)
                        <figure>
                            @include('school-site.classic.partials.ph', ['src' => $g['image'] ?? null, 'alt' => $g['caption'] ?? '', 'lb' => true])
                            @if (!empty($g['caption']))<figcaption>{{ $g['caption'] }}</figcaption>@endif
                        </figure>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection
