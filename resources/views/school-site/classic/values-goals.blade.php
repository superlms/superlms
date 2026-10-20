{{--
    About Us → Values & Goals (it was History; /history still opens it): the
    school's Values and Goals as written on its School Profile, side by side
    in the Mark Attendance list's card look (user's ask of 2026-10-21).
--}}
@extends('school-site.classic.layout', ['title' => 'Values & Goals'])

@php
    $parts = array_filter(['Our Values' => trim((string) ($c['values'] ?? '')), 'Our Goals' => trim((string) ($c['goals'] ?? ''))]);
@endphp

@push('styles')
<style>
    .vg { display: grid; grid-template-columns: repeat({{ max(count($parts), 1) }}, minmax(0, 1fr)); gap: 22px; align-items: start; }
    .vg .lms-card { margin: 0; }
    @media (max-width: 760px) { .vg { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
    @include('school-site.classic.partials.banner', ['heading' => 'Values & Goals'])

    <section class="page">
        <div class="wrap">
            @include('school-site.shared.lms-style')
            @if ($parts)
                <div class="vg">
                    @foreach ($parts as $title => $text)
                        <div class="lms-card">
                            <div class="lms-head"><h2>{{ $title }}</h2></div>
                            <div class="lms-text">@include('school-site.classic.partials.paras', ['text' => $text])</div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="lms-card"><p class="lms-empty">Our values and goals will be published here shortly.</p></div>
            @endif
        </div>
    </section>
@endsection
