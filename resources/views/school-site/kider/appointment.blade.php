@extends('school-site.kider.layout')

@section('content')
    @include('school-site.kider.partials.page-header', ['heading' => 'Make an Appointment'])

    <div class="container-fluid py-5">
        <div class="container">
            <div class="row g-5 align-items-center">
                <div class="col-lg-5 wow fadeInUp" data-wow-delay="0.1s">
                    <h1 class="mb-4">Book a Visit or Admission Appointment</h1>
                    <p class="mb-4">Planning to visit {{ $c['school_name'] }} or enquire about admissions? Fill in the form and our team will reach out to schedule a convenient time.</p>
                    @if(!empty($c['phone']))<p class="mb-2"><i class="fa fa-phone-alt text-primary me-3"></i>{{ $c['phone'] }}</p>@endif
                    @if(!empty($c['email']))<p class="mb-2"><i class="fa fa-envelope text-primary me-3"></i>{{ $c['email'] }}</p>@endif
                </div>
                <div class="col-lg-7 wow fadeInUp" data-wow-delay="0.3s">
                    <div class="bg-light rounded p-5">
                        @include('school-site.kider.partials.enquiry-form', ['site' => $site])
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
