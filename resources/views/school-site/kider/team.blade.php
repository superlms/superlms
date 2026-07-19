@extends('school-site.kider.layout')
@php
    use App\Models\SchoolWebsite;
    $team = !empty($c['team']) ? $c['team'] : [
        ['name' => 'Principal', 'role' => 'Head of School', 'photo' => null],
        ['name' => 'Vice Principal', 'role' => 'Academics', 'photo' => null],
        ['name' => 'Head Teacher', 'role' => 'Primary Wing', 'photo' => null],
    ];
    $teamImgs = ['team-1.jpg','team-2.jpg','team-3.jpg'];
@endphp

@section('content')
    @include('school-site.kider.partials.page-header', [
        'heading' => 'Our Team',
        'tag' => 'Our People',
        'sub' => 'The dedicated educators and staff who make ' . $c['school_name'] . ' a wonderful place to learn.',
    ])

    <section class="section">
        <div class="section-inner">
            <div class="cards-grid">
                @foreach ($team as $i => $member)
                    <div class="team-card reveal">
                        <div class="team-photo"><img src="{{ SchoolWebsite::media($member['photo'] ?? null, $teamImgs[$i % count($teamImgs)]) }}" alt=""></div>
                        <div class="team-body">
                            <p class="team-name">{{ $member['name'] ?? '' }}</p>
                            <p class="team-role">{{ $member['role'] ?? '' }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
