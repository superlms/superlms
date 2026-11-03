{{--
    Curriculum → Academic Calendar for Vibrant and Breeze, as on Classic:
    every event on the school's calendar this session, by date, then any
    calendar documents uploaded in Website Data. Receives $heading and
    $documents = [{title, file, date}] as every document page does.
--}}
@extends('school-site.' . $tpl . '.layout', ['title' => $heading])

@php
    use App\Models\SchoolWebsite;

    $events = $lms->events();
    $docs   = array_values(array_filter(array_map(fn ($d) => [
        'title' => trim((string) ($d['title'] ?? '')),
        'date'  => trim((string) ($d['date'] ?? '')),
        'url'   => SchoolWebsite::media($d['file'] ?? null),
    ], $documents), fn ($d) => $d['title'] !== '' || $d['url']));
@endphp

@section('content')
    @include('school-site.' . $tpl . '.partials.banner', ['heading' => $heading])

    <section class="pg">
        <div class="wrap">
            @include('school-site.shared.lms-style')
            @if ($events)
                @include('school-site.shared.lms-events', ['events' => $events])
            @endif

            @if ($docs)
                @include('school-site.shared.lms-docs', ['title' => $events ? 'Calendar Documents' : $heading, 'docs' => $docs])
            @endif

            @if (! $events && ! $docs)
                <div class="lms-card"><p class="lms-empty">The academic calendar will be published here shortly.</p></div>
            @endif
        </div>
    </section>
@endsection
