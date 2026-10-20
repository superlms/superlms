{{--
    Curriculum → Academic Calendar: every event on the school's calendar this
    session (Calendar), by date, in the Mark Attendance list's look; under
    them any calendar documents uploaded in Website Data. Receives $heading
    and $documents = [{title, file, date}] as every document page does.
--}}
@extends('school-site.classic.layout', ['title' => $heading])

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
    @include('school-site.classic.partials.banner', ['heading' => $heading])

    <section class="page">
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
