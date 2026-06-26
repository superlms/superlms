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
    @include('school-site.kider.partials.page-header', ['heading' => 'Our Team'])

    <div class="container-fluid py-5">
        <div class="container">
            <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 600px;">
                <h1 class="mb-3">Meet Our Team</h1>
                <p>The dedicated educators and staff who make {{ $c['school_name'] }} a wonderful place to learn.</p>
            </div>
            <div class="row g-4">
                @foreach ($team as $i => $member)
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.{{ ($i % 3) + 1 }}s">
                    <div class="team-item position-relative">
                        <img class="img-fluid rounded-circle w-75" src="{{ SchoolWebsite::media($member['photo'] ?? null, $teamImgs[$i % count($teamImgs)]) }}" alt="">
                        <div class="team-text">
                            <h3>{{ $member['name'] ?? '' }}</h3>
                            <p class="text-primary mb-0">{{ $member['role'] ?? '' }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
