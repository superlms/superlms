{{-- Photogallery → Photo Gallery for Vibrant and Breeze: the school's photos in a grid; a click opens each large. --}}
@extends('school-site.' . $tpl . '.layout', ['title' => 'Photo Gallery'])
@php
    // Watermarked tiles until the school adds its photos.
    $photos = !empty($c['gallery']) ? $c['gallery'] : array_map(
        fn ($cap) => ['image' => null, 'caption' => $cap],
        ['Campus', 'Classrooms', 'Activities', 'Sports', 'Events', 'Labs']
    );
@endphp

@section('content')
    @include('school-site.' . $tpl . '.partials.banner', ['heading' => 'Photo Gallery'])

    <section class="pg">
        <div class="wrap">
            <div class="photos">
                @foreach ($photos as $g)
                    <figure>
                        @include('school-site.classic.partials.ph', ['src' => $g['image'] ?? null, 'alt' => $g['caption'] ?? '', 'lb' => true])
                        @if (!empty($g['caption']))<figcaption>{{ $g['caption'] }}</figcaption>@endif
                    </figure>
                @endforeach
            </div>
        </div>
    </section>
@endsection
