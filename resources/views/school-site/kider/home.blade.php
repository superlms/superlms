@extends('school-site.kider.layout')
@php
    use App\Models\SchoolWebsite;
    $classes = !empty($c['classes']) ? $c['classes'] : [
        ['title' => 'Art & Drawing', 'age' => '3-5 Years', 'time' => '9-10 AM',  'capacity' => '30 Kids', 'image' => null],
        ['title' => 'Music & Dance', 'age' => '4-6 Years', 'time' => '10-11 AM', 'capacity' => '30 Kids', 'image' => null],
        ['title' => 'Sport & Games', 'age' => '5-7 Years', 'time' => '11-12 AM', 'capacity' => '30 Kids', 'image' => null],
    ];
    $team = !empty($c['team']) ? $c['team'] : [
        ['name' => 'Principal', 'role' => 'Head of School', 'photo' => null],
    ];
    $clsImgs = ['classes-1.jpg','classes-2.jpg','classes-3.jpg','classes-4.jpg','classes-5.jpg','classes-6.jpg'];
    $teamImgs = ['team-1.jpg','team-2.jpg','team-3.jpg'];
@endphp

@section('content')

    {{-- ── Hero carousel ── --}}
    <div class="container-fluid p-0 mb-5">
        <div class="owl-carousel header-carousel position-relative">
            @foreach (['carousel-1.jpg','carousel-2.jpg'] as $i => $img)
            <div class="owl-carousel-item position-relative">
                <img class="img-fluid" src="{{ SchoolWebsite::media(null, $img) }}" alt="">
                <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center" style="background: rgba(0, 0, 0, .35);">
                    <div class="container">
                        <div class="row justify-content-start">
                            <div class="col-10 col-lg-8">
                                <h1 class="display-3 text-white animated slideInDown mb-4">{{ $i === 0 ? $c['hero_title'] : ('A Brighter Future at ' . $c['school_name']) }}</h1>
                                <p class="fs-5 fw-medium text-white mb-4 pb-2">{{ $c['hero_subtitle'] }}</p>
                                @if ($site->isPageEnabled('contact'))
                                    <a href="{{ url('contact') }}" class="btn btn-primary rounded-pill py-sm-3 px-sm-5 me-3 animated slideInLeft">Enquire Now</a>
                                @endif
                                @if ($site->isPageEnabled('classes'))
                                    <a href="{{ url('classes') }}" class="btn btn-dark rounded-pill py-sm-3 px-sm-5 animated slideInRight">Our Classes</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── About ── --}}
    <div class="container-fluid py-5">
        <div class="container">
            <div class="row g-5 align-items-center">
                <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.1s">
                    <h1 class="mb-4">{{ $c['about_heading'] }}</h1>
                    <p class="mb-4">{{ $c['about_text'] }}</p>
                    @if (!empty($c['about_text2']))<p class="mb-4">{{ $c['about_text2'] }}</p>@endif
                    @if ($site->isPageEnabled('about'))
                        <a class="btn btn-primary rounded-pill py-3 px-5" href="{{ url('about') }}">Read More</a>
                    @endif
                </div>
                <div class="col-lg-6 about-img wow fadeInUp" data-wow-delay="0.5s">
                    <div class="row">
                        <div class="col-12 text-center">
                            <img class="img-fluid w-75 rounded-circle bg-light p-3" src="{{ SchoolWebsite::media($c['logo'] ?? null, 'about-1.jpg') }}" alt="">
                        </div>
                        <div class="col-6 text-start" style="margin-top: -150px;">
                            <img class="img-fluid w-100 rounded-circle bg-light p-3" src="{{ SchoolWebsite::media(null, 'about-2.jpg') }}" alt="">
                        </div>
                        <div class="col-6 text-end" style="margin-top: -150px;">
                            <img class="img-fluid w-100 rounded-circle bg-light p-3" src="{{ SchoolWebsite::media(null, 'about-3.jpg') }}" alt="">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Call to action ── --}}
    <div class="container-fluid py-5">
        <div class="container">
            <div class="bg-light rounded">
                <div class="row g-0">
                    <div class="col-lg-6 wow fadeIn" data-wow-delay="0.1s" style="min-height: 400px;">
                        <div class="position-relative h-100">
                            <img class="position-absolute w-100 h-100 rounded" src="{{ SchoolWebsite::media(null, 'call-to-action.jpg') }}" style="object-fit: cover;">
                        </div>
                    </div>
                    <div class="col-lg-6 wow fadeIn" data-wow-delay="0.5s">
                        <div class="h-100 d-flex flex-column justify-content-center p-5">
                            <h1 class="mb-4">{{ $c['cta_heading'] }}</h1>
                            <p class="mb-4">{{ $c['cta_text'] }}</p>
                            <a class="btn btn-primary py-3 px-5" href="{{ $site->isPageEnabled('contact') ? url('contact') : $site->adminLoginUrl() }}">Get Started Now<i class="fa fa-arrow-right ms-2"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Classes ── --}}
    @if ($site->isPageEnabled('classes'))
    <div class="container-fluid py-5">
        <div class="container">
            <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 600px;">
                <h1 class="mb-3">Our Classes</h1>
                <p>Engaging, age-appropriate programmes designed to help every child learn and grow.</p>
            </div>
            <div class="row g-4">
                @foreach (array_slice($classes, 0, 3) as $i => $cls)
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.{{ $i + 1 }}s">
                    <div class="classes-item">
                        <div class="bg-light rounded-circle w-75 mx-auto p-3">
                            <img class="img-fluid rounded-circle" src="{{ SchoolWebsite::media($cls['image'] ?? null, $clsImgs[$i % count($clsImgs)]) }}" alt="">
                        </div>
                        <div class="bg-light rounded p-4 pt-5 mt-n5">
                            <span class="d-block text-center h3 mt-3 mb-4">{{ $cls['title'] ?? '' }}</span>
                            <div class="row g-1">
                                <div class="col-4"><div class="border-top border-3 border-primary pt-2"><h6 class="text-primary mb-1">Age:</h6><small>{{ $cls['age'] ?? '-' }}</small></div></div>
                                <div class="col-4"><div class="border-top border-3 border-success pt-2"><h6 class="text-success mb-1">Time:</h6><small>{{ $cls['time'] ?? '-' }}</small></div></div>
                                <div class="col-4"><div class="border-top border-3 border-warning pt-2"><h6 class="text-warning mb-1">Capacity:</h6><small>{{ $cls['capacity'] ?? '-' }}</small></div></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- ── Team ── --}}
    @if ($site->isPageEnabled('team'))
    <div class="container-fluid py-5">
        <div class="container">
            <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 600px;">
                <h1 class="mb-3">Our Team</h1>
                <p>Meet the dedicated educators and staff who make {{ $c['school_name'] }} special.</p>
            </div>
            <div class="row g-4">
                @foreach (array_slice($team, 0, 3) as $i => $member)
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.{{ $i + 1 }}s">
                    <div class="team-item position-relative">
                        <img class="img-fluid rounded-circle w-75" src="{{ SchoolWebsite::media($member['photo'] ?? null, $teamImgs[$i % count($teamImgs)]) }}" alt="">
                        <div class="team-text">
                            <h3>{{ $member['name'] ?? '' }}</h3>
                            <p class="text-primary">{{ $member['role'] ?? '' }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

@endsection
