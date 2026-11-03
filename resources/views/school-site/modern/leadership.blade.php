{{-- Messaging → Leadership for Vibrant and Breeze: a card a leader, the photo beside the message (Classic's data). --}}
@extends('school-site.' . $tpl . '.layout', ['title' => 'Leadership'])
@php
    // One message from the school until it adds its leaders' own.
    $leaders = !empty($c['leadership']) ? $c['leadership'] : [
        ['name' => $c['school_name'], 'role' => 'From the Principal', 'photo' => null, 'message' => $c['about_text']],
    ];
@endphp

@section('content')
    @include('school-site.' . $tpl . '.partials.banner', ['heading' => 'Leadership'])

    <section class="pg">
        <div class="wrap">
            @foreach ($leaders as $m)
                <div class="panel leader">
                    @include('school-site.classic.partials.ph', ['src' => $m['photo'] ?? null, 'alt' => $m['name'] ?? '', 'lb' => true])
                    <div>
                        <span class="quote-mark">@include('school-site.modern.partials.icon', ['n' => 'quote'])</span>
                        @if (!empty($m['role']))<p class="leader-role">{{ $m['role'] }}</p>@endif
                        <h2 class="h3">{{ $m['name'] ?? '' }}</h2>
                        <div class="prose">@include('school-site.classic.partials.paras', ['text' => $m['message'] ?? ''])</div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endsection
