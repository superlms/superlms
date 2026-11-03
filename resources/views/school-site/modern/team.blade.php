{{-- More → Our Team for Vibrant and Breeze: the team on the School Profile, a card each — round photo, name, designation. --}}
@extends('school-site.' . $tpl . '.layout', ['title' => 'Our Team'])
@php $sw = fn ($i) => \App\Support\ModernSite::swatch($tpl, $i, $theme['primary']); @endphp

@section('content')
    @include('school-site.' . $tpl . '.partials.banner', ['heading' => 'Our Team'])

    <section class="pg">
        <div class="wrap">
            @if (empty($c['team']))
                <div class="panel"><p class="muted-note">Our team will be introduced here shortly.</p></div>
            @else
                <div class="sec-head center">
                    <span class="eyebrow">Our Team</span>
                    <h2 class="h2">The people behind <span class="hl">{{ $c['school_name'] }}</span></h2>
                </div>
                <div class="people">
                    @foreach (array_values($c['team']) as $i => $m)
                        <figure class="person">
                            @include('school-site.classic.partials.ph', ['src' => $m['photo'] ?? null, 'alt' => $m['name'] ?? ''])
                            <figcaption>
                                <b>{{ $m['name'] ?? '' }}</b>
                                @if (!empty($m['role']))<span class="chip" style="{{ $sw($i) }}">{{ $m['role'] }}</span>@endif
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
