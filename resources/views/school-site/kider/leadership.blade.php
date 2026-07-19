@extends('school-site.kider.layout')
@php
    use App\Models\SchoolWebsite;
    // Fall back to a single principal-style message if none configured yet.
    $leaders = !empty($c['leadership']) ? $c['leadership'] : [
        [
            'name'    => $c['school_name'],
            'role'    => 'From the Principal',
            'photo'   => null,
            'message' => $c['about_text'] ?? 'At our school we believe every child is unique. Our dedicated team works to nurture curiosity, character and confidence in a safe and caring environment.',
        ],
    ];
    $leadImgs = ['team-1.jpg','team-2.jpg','team-3.jpg'];
@endphp

@section('content')
    @include('school-site.kider.partials.page-header', [
        'heading' => 'Leadership',
        'tag' => 'Messages',
        'sub' => 'Guiding words from the people who lead ' . $c['school_name'] . '.',
    ])

    <section class="section">
        <div class="section-inner" style="display:flex;flex-direction:column;gap:28px;">
            @foreach ($leaders as $i => $m)
                <div class="info-card reveal" style="display:grid;grid-template-columns:auto 1fr;gap:24px;align-items:start;padding:28px;">
                    <div style="width:110px;height:110px;border-radius:20px;overflow:hidden;flex-shrink:0;background:var(--faint);border:4px solid #fff;box-shadow:var(--shadow3);">
                        <img src="{{ SchoolWebsite::media($m['photo'] ?? null, $leadImgs[$i % count($leadImgs)]) }}" alt="{{ $m['name'] ?? '' }}" style="width:100%;height:100%;object-fit:cover;">
                    </div>
                    <div>
                        <p class="team-role" style="margin-bottom:2px;">{{ $m['role'] ?? '' }}</p>
                        <h3 class="info-title" style="font-size:22px;">{{ $m['name'] ?? '' }}</h3>
                        <p class="body-text" style="margin-top:10px;white-space:pre-line;">{{ $m['message'] ?? '' }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endsection
