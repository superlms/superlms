@extends('school-site.kider.layout')
@php use App\Models\SchoolWebsite; @endphp

@section('content')
    @include('school-site.kider.partials.page-header', ['heading' => 'About Us'])

    <div class="container-fluid py-5">
        <div class="container">
            <div class="row g-5 align-items-center">
                <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.1s">
                    <h1 class="mb-4">{{ $c['about_heading'] }}</h1>
                    <p class="mb-4">{{ $c['about_text'] }}</p>
                    @if (!empty($c['about_text2']))<p class="mb-4">{{ $c['about_text2'] }}</p>@endif
                    <div class="row g-3">
                        @if(!empty($c['phone']))<div class="col-sm-6"><p class="mb-0"><i class="fa fa-phone-alt text-primary me-2"></i>{{ $c['phone'] }}</p></div>@endif
                        @if(!empty($c['email']))<div class="col-sm-6"><p class="mb-0"><i class="fa fa-envelope text-primary me-2"></i>{{ $c['email'] }}</p></div>@endif
                    </div>
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

            @if (!empty($c['vision']) || !empty($c['mission']))
            <div class="row g-4 mt-4">
                @if (!empty($c['vision']))
                <div class="col-md-6 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="bg-light rounded h-100 p-5">
                        <h3 class="mb-3"><i class="fa fa-eye text-primary me-2"></i>Our Vision</h3>
                        <p class="mb-0">{{ $c['vision'] }}</p>
                    </div>
                </div>
                @endif
                @if (!empty($c['mission']))
                <div class="col-md-6 wow fadeInUp" data-wow-delay="0.3s">
                    <div class="bg-light rounded h-100 p-5">
                        <h3 class="mb-3"><i class="fa fa-bullseye text-primary me-2"></i>Our Mission</h3>
                        <p class="mb-0">{{ $c['mission'] }}</p>
                    </div>
                </div>
                @endif
            </div>
            @endif
        </div>
    </div>
@endsection
