{{--
    Generic content page for Vibrant and Breeze — every page pageGroups()
    marks 'content' (Vision, the desks, Labs, Sports, Building, Curriculum,
    Career …), as on Classic: a desk puts the photo beside the message as a
    letter; other pages put it beside the text; "more photos" make a gallery
    under it; Career adds its application form.
    Receives $page = ['heading', 'body', 'image', 'photos'].
--}}
@extends('school-site.' . $tpl . '.layout', ['title' => $page['heading']])

@php
    $desks  = ['chairman', 'founder', 'co-founder', 'managing-director', 'executive-director', 'director', 'manager-desk', 'principal-desk'];
    $isDesk = in_array($current, $desks, true);
    $body   = trim((string) $page['body']);
    $image  = trim((string) $page['image']);
    $form   = $current === 'career';
@endphp

@section('content')
    @include('school-site.' . $tpl . '.partials.banner', ['heading' => $page['heading']])

    <section class="pg">
        <div class="wrap">
            <div class="panel">
                @if ($body === '' && $image === '' && ! $page['photos'] && ! $form)
                    <p class="muted-note">
                        This page is being updated. Please check back shortly, or
                        @if (!empty($c['phone'])) call us on {{ $c['phone'] }}. @else get in touch with the school office. @endif
                    </p>
                @elseif ($isDesk)
                    <div class="desk">
                        @include('school-site.classic.partials.ph', ['src' => $image ?: null, 'alt' => $page['heading'], 'lb' => true])
                        <div>
                            <span class="quote-mark">@include('school-site.modern.partials.icon', ['n' => 'quote'])</span>
                            <div class="prose">@include('school-site.classic.partials.paras', ['text' => $body])</div>
                        </div>
                    </div>
                @elseif ($image !== '')
                    <div class="side">
                        <div class="prose">@include('school-site.classic.partials.paras', ['text' => $body])</div>
                        @include('school-site.classic.partials.ph', ['src' => $image, 'alt' => $page['heading'], 'lb' => true])
                    </div>
                @elseif ($body !== '')
                    <div class="prose" style="max-width:860px;">@include('school-site.classic.partials.paras', ['text' => $body])</div>
                @endif

                @if ($page['photos'])
                    @if ($body !== '' || $image !== '')<h3 class="photos-head">Photos</h3>@endif
                    <div class="photos">
                        @foreach ($page['photos'] as $p)
                            <figure>@include('school-site.classic.partials.ph', ['src' => $p, 'alt' => $page['heading'], 'lb' => true])</figure>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($form)
                <div class="panel">
                    <div class="panel-head">
                        <div>
                            <span class="eyebrow">Careers</span>
                            <h2 class="h2">Apply for a Job</h2>
                        </div>
                    </div>
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
