{{--
    More → Transport: the school's routes from Transport — pickup and drop
    times, the monthly and the annual fare — in the Mark Attendance list's
    look; above them the page's own text and photos, when Website Data has
    any. Receives $page = ['heading', 'body', 'image', 'photos'].
--}}
@extends('school-site.classic.layout', ['title' => $page['heading']])

@php
    $body   = trim((string) $page['body']);
    $photos = array_values(array_filter(array_merge([trim((string) $page['image'])], $page['photos'])));
    $routes = $lms->transport();
@endphp

@section('content')
    @include('school-site.classic.partials.banner', ['heading' => $page['heading']])

    <section class="page">
        <div class="wrap">
            @include('school-site.shared.lms-style')
            @if ($body !== '' || $photos)
                <div class="lms-card">
                    @if ($body !== '')
                        <div class="lms-text">@include('school-site.classic.partials.paras', ['text' => $body])</div>
                    @endif
                    @if ($photos)
                        <div class="photos" style="padding:0 20px 20px;">
                            @foreach ($photos as $p)
                                <figure>@include('school-site.classic.partials.ph', ['src' => $p, 'alt' => $page['heading'], 'lb' => true])</figure>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            @if ($routes)
                @include('school-site.shared.lms-transport', ['routes' => $routes])
            @elseif ($body === '' && ! $photos)
                <div class="lms-card"><p class="lms-empty">Transport details will be published here shortly.</p></div>
            @endif
        </div>
    </section>
@endsection
