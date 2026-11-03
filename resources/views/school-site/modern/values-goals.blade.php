{{--
    About Us → Values & Goals for Vibrant and Breeze: the school's Values and
    Goals as written on its School Profile, side by side, as on Classic.
--}}
@extends('school-site.' . $tpl . '.layout', ['title' => 'Values & Goals'])

@php
    $sw    = fn ($i) => \App\Support\ModernSite::swatch($tpl, $i, $theme['primary']);
    $parts = array_filter([
        'Our Values' => ['heart', trim((string) ($c['values'] ?? ''))],
        'Our Goals'  => ['flag', trim((string) ($c['goals'] ?? ''))],
    ], fn ($p) => $p[1] !== '');
@endphp

@section('content')
    @include('school-site.' . $tpl . '.partials.banner', ['heading' => 'Values & Goals'])

    <section class="pg">
        <div class="wrap">
            @if ($parts)
                <div class="grid-2" @if (count($parts) === 1) style="grid-template-columns:minmax(0,1fr);" @endif>
                    @foreach (array_values($parts) as $i => [$icon, $text])
                        <div class="tile" style="padding:32px;">
                            <span class="badge-ico" style="{{ $sw($i + 2) }}">@include('school-site.modern.partials.icon', ['n' => $icon])</span>
                            <h2 class="h3" style="font-size:24px;">{{ array_keys($parts)[$i] }}</h2>
                            <div class="prose">@include('school-site.classic.partials.paras', ['text' => $text])</div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="panel"><p class="muted-note">Our values and goals will be published here shortly.</p></div>
            @endif
        </div>
    </section>
@endsection
