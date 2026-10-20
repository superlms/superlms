{{--
    Online Admission → Fee Structure: the fees set on Fee → Fee Structure, in
    the Mark Attendance list's look, with Website Data's fee note; under them
    any fee documents uploaded in Website Data. Receives $heading and
    $documents = [{title, file, date}] as every document page does.
--}}
@extends('school-site.kider.layout')

@php
    use App\Models\SchoolWebsite;

    $fees = $lms->fees();
    $note = trim((string) ($c['fee_note'] ?? ''));
    $docs = array_values(array_filter(array_map(fn ($d) => [
        'title' => trim((string) ($d['title'] ?? '')),
        'date'  => trim((string) ($d['date'] ?? '')),
        'url'   => SchoolWebsite::media($d['file'] ?? null),
    ], $documents), fn ($d) => $d['title'] !== '' || $d['url']));
@endphp

@section('content')
    @include('school-site.kider.partials.page-header', ['heading' => $heading, 'tag' => 'Admissions'])

    <section class="section">
        <div class="section-inner" style="max-width:1040px;">
            @include('school-site.shared.lms-style')
            @if ($fees)
                @include('school-site.shared.lms-fees', ['fees' => $fees, 'note' => $note])
            @elseif ($note !== '')
                <div class="lms-card"><p class="lms-text">{{ $note }}</p></div>
            @endif

            @if ($docs)
                @include('school-site.shared.lms-docs', ['title' => $fees ? 'Fee Documents' : $heading, 'docs' => $docs])
            @endif

            @if (! $fees && ! $docs && $note === '')
                <div class="lms-card"><p class="lms-empty">The fee structure will be published here shortly.</p></div>
            @endif
        </div>
    </section>
@endsection
