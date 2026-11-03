{{-- Features → Features & Facilities for Vibrant and Breeze: a card a facility (Classic's data and starter set). --}}
@extends('school-site.' . $tpl . '.layout', ['title' => 'Features & Facilities'])
@php
    $sw         = fn ($i) => \App\Support\ModernSite::swatch($tpl, $i, $theme['primary']);
    $facilities = \App\Support\ModernSite::facilities($c, true);
@endphp

@section('content')
    @include('school-site.' . $tpl . '.partials.banner', ['heading' => 'Features & Facilities'])

    <section class="pg">
        <div class="wrap">
            <div class="sec-head center">
                <span class="eyebrow">Features &amp; Facilities</span>
                <h2 class="h2">Built for learning, play <span class="hl">and growth</span></h2>
                <p class="lead-text">At {{ $c['school_name'] }}, we are committed to the best facilities for the overall development of your child.</p>
            </div>
            <div class="fac-grid">
                @foreach ($facilities as $i => $f)
                    <article class="fac-card">
                        @include('school-site.classic.partials.ph', ['src' => $f['image'] ?? null, 'alt' => $f['title'] ?? '', 'label' => $f['icon'] ?? null, 'lb' => true])
                        <div class="fac-body">
                            <span class="badge-ico" style="{{ $sw($i) }}">@if (!empty($f['icon'])){{ $f['icon'] }}@else @include('school-site.modern.partials.icon', ['n' => 'star']) @endif</span>
                            <h2 class="h3">{{ $f['title'] ?? '' }}</h2>
                            <p>{{ $f['desc'] ?? '' }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endsection
