{{--
    Generic content page — every page pageGroups() marks 'content' (History,
    Vision, the desks, Labs, Sports, Building, Curriculum, Career …). The
    desks put the photo beside the message, as a letter from that person;
    other pages put it to the right of the text; "more photos" make a gallery
    under it. Career adds its application form.
    Receives $page = ['heading', 'body', 'image', 'photos'].
--}}
@extends('school-site.classic.layout', ['title' => $page['heading']])

@php
    $desks  = ['chairman', 'founder', 'co-founder', 'managing-director', 'executive-director', 'director', 'manager-desk', 'principal-desk'];
    $isDesk = in_array($current, $desks, true);
    $body   = trim((string) $page['body']);
    $image  = trim((string) $page['image']);
    $form   = $current === 'career';
    // Rules & Regulations: the rules saved on More → Rules & Regulations, under the page's own text.
    $rules  = $current === 'rules' && $lms->hasRules() ? $lms->rules() : null;
@endphp

@push('styles')
<style>
    .desk { display: grid; grid-template-columns: 220px 1fr; gap: 30px; align-items: start; }
    .desk .ph { aspect-ratio: 4 / 5; border: 1px solid var(--line); }
    .side { display: grid; grid-template-columns: 1fr 360px; gap: 30px; align-items: start; }
    .side .ph { aspect-ratio: 4 / 3; border: 1px solid var(--line); }
    .photos-head { font-size: 15px; font-weight: 600; color: #222; margin: 26px 0 12px; }
    @media (max-width: 820px) {
        .desk, .side { grid-template-columns: 1fr; gap: 20px; }
        .desk .ph { max-width: 240px; }
    }
</style>
@endpush

@section('content')
    @include('school-site.classic.partials.banner', ['heading' => $page['heading']])

    <section class="page">
        <div class="wrap">
            <div class="card">
                @if ($body === '' && $image === '' && ! $page['photos'] && ! $form && ! $rules)
                    <p class="muted-note">
                        This page is being updated. Please check back shortly, or
                        @if (!empty($c['phone'])) call us on {{ $c['phone'] }}. @else get in touch with the school office. @endif
                    </p>
                @elseif ($isDesk)
                    <div class="desk">
                        @include('school-site.classic.partials.ph', ['src' => $image ?: null, 'alt' => $page['heading'], 'lb' => true])
                        <div class="prose">@include('school-site.classic.partials.paras', ['text' => $body])</div>
                    </div>
                @elseif ($image !== '')
                    <div class="side">
                        <div class="prose">@include('school-site.classic.partials.paras', ['text' => $body])</div>
                        @include('school-site.classic.partials.ph', ['src' => $image, 'alt' => $page['heading'], 'lb' => true])
                    </div>
                @elseif ($body !== '')
                    <div class="prose">@include('school-site.classic.partials.paras', ['text' => $body])</div>
                @endif

                @if ($page['photos'])
                    @if ($body !== '' || $image !== '')<h3 class="photos-head">Photos</h3>@endif
                    <div class="photos">
                        @foreach ($page['photos'] as $p)
                            <figure>@include('school-site.classic.partials.ph', ['src' => $p, 'alt' => $page['heading'], 'lb' => true])</figure>
                        @endforeach
                    </div>
                @endif

                @if ($rules)
                    @if ($body !== '' || $image !== '' || $page['photos'])<h3 class="photos-head">Rules &amp; Regulations</h3>@endif
                    @include('school-site.shared.lms-rules', ['rules' => $rules])
                @endif
            </div>

            @if ($form)
                <div class="card">
                    <h2 class="title" style="margin-bottom:16px;">Apply for a Job</h2>
                    @include('school-site.classic.partials.form', [
                        'kind'   => 'Career application',
                        'type'   => 'career',
                        'button' => 'Send',
                        'fields' => [
                            ['name' => 'name', 'label' => 'Name', 'required' => true],
                            ['name' => 'phone', 'label' => 'Mobile No.', 'type' => 'tel', 'required' => true],
                            ['name' => 'email', 'label' => 'Email ID', 'type' => 'email'],
                            ['name' => 'qualification', 'label' => 'Qualification', 'required' => true],
                            ['name' => 'applied_for', 'label' => 'Designation', 'required' => true, 'subject' => true, 'placeholder' => 'e.g. PGT Physics'],
                            ['name' => 'experience', 'label' => 'Experience', 'placeholder' => 'e.g. 4 years'],
                            ['name' => 'message', 'label' => 'Why You?', 'type' => 'textarea'],
                        ],
                    ])
                </div>
            @endif
        </div>
    </section>
@endsection
