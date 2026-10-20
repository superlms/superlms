{{--
    Online Admission → Rules & Regulations: the rules saved on More → Rules &
    Regulations, the admission rules from Website Data, the additional
    information and the PDFs — in the Mark Attendance list's look; above them
    the page's own text and photos, when Website Data has any.
    Receives $page = ['heading', 'body', 'image', 'photos'].
--}}
@extends('school-site.classic.layout', ['title' => $page['heading']])

@php
    $body   = trim((string) $page['body']);
    $photos = array_values(array_filter(array_merge([trim((string) $page['image'])], $page['photos'])));
    $extra  = $c['admission_rules'] ?? [];
    $any    = $lms->hasRules() || trim(implode('', array_map(fn ($r) => (string) ($r['text'] ?? ''), $extra))) !== '';
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

            @include('school-site.shared.lms-rules', ['rules' => $lms->rules(), 'extra' => $extra])

            @if (! $any && $body === '' && ! $photos)
                <div class="lms-card"><p class="lms-empty">Our rules &amp; regulations will be published here shortly.</p></div>
            @endif
        </div>
    </section>
@endsection
