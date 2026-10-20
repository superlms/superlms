{{--
    Online Admission → Rules & Regulations: the rules saved on More → Rules &
    Regulations, the admission rules from Website Data, the additional
    information and the PDFs — in the Mark Attendance list's look; above them
    the page's own text and photo, when Website Data has any.
    Receives $page = ['heading', 'body', 'image', 'photos'].
--}}
@extends('school-site.kider.layout')

@php
    use App\Models\SchoolWebsite;

    $body   = trim((string) ($page['body'] ?? ''));
    $photos = array_values(array_filter(array_merge([trim((string) ($page['image'] ?? ''))], $page['photos'] ?? [])));
    $extra  = $c['admission_rules'] ?? [];
    $any    = $lms->hasRules() || trim(implode('', array_map(fn ($r) => (string) ($r['text'] ?? ''), $extra))) !== '';
@endphp

@section('content')
    @include('school-site.kider.partials.page-header', ['heading' => $page['heading'], 'tag' => 'Admissions'])

    <section class="section">
        <div class="section-inner" style="max-width:1040px;">
            @include('school-site.shared.lms-style')
            @if ($body !== '' || $photos)
                <div class="lms-card">
                    @if ($body !== '')
                        <div class="lms-text">
                            @foreach (preg_split('/\r?\n\s*\r?\n/', $body) as $para)
                                @if (trim($para) !== '')<p>{!! nl2br(e(trim($para))) !!}</p>@endif
                            @endforeach
                        </div>
                    @endif
                    @if ($photos)
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;padding:0 20px 20px;">
                            @foreach ($photos as $p)
                                <img src="{{ SchoolWebsite::media($p) }}" alt="{{ $page['heading'] }}" loading="lazy" style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:10px;">
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
