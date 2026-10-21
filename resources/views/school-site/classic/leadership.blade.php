@extends('school-site.classic.layout', ['title' => 'Leadership'])
@php
    // One message from the school until it adds its leaders' own.
    $leaders = !empty($c['leadership']) ? $c['leadership'] : [
        ['name' => $c['school_name'], 'role' => 'From the Principal', 'photo' => null, 'message' => $c['about_text']],
    ];
@endphp

@push('styles')
<style>
    .leader { display: grid; grid-template-columns: 200px 1fr; gap: 28px; align-items: start; }
    .leader .ph { aspect-ratio: 4 / 5; border: 1px solid var(--line); }
    .leader-role { font-size: 12px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--primary); }
    .leader h2 { font-size: 20px; color: #222; margin-bottom: 10px; }
    @media (max-width: 720px) { .leader { grid-template-columns: 1fr; gap: 16px; } .leader .ph { width: 100%; max-width: 220px; margin: 0 auto; } .leader > div { text-align: center; } .leader .prose { text-align: left; } }
</style>
@endpush

@section('content')
    @include('school-site.classic.partials.banner', ['heading' => 'Leadership'])

    <section class="page">
        <div class="wrap">
            @foreach ($leaders as $m)
                <div class="card leader">
                    @include('school-site.classic.partials.ph', ['src' => $m['photo'] ?? null, 'alt' => $m['name'] ?? '', 'lb' => true])
                    <div>
                        @if (!empty($m['role']))<p class="leader-role">{{ $m['role'] }}</p>@endif
                        <h2>{{ $m['name'] ?? '' }}</h2>
                        <div class="prose">@include('school-site.classic.partials.paras', ['text' => $m['message'] ?? ''])</div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endsection
