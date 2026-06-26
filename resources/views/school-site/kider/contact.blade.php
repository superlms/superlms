@extends('school-site.kider.layout')

@section('content')
    @include('school-site.kider.partials.page-header', ['heading' => 'Contact Us'])

    <div class="container-fluid py-5">
        <div class="container">
            <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 600px;">
                <h1 class="mb-3">Get In Touch</h1>
                <p>Have a question or want to enquire about admissions? Send us a message and our team will respond shortly.</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 wow fadeInUp" data-wow-delay="0.1s">
                    @if(!empty($c['address']))
                    <div class="d-flex bg-light rounded p-4 mb-4">
                        <i class="fa fa-map-marker-alt text-primary fa-2x me-3"></i>
                        <div><h5>Address</h5><p class="mb-0">{{ $c['address'] }}</p></div>
                    </div>
                    @endif
                    @if(!empty($c['email']))
                    <div class="d-flex bg-light rounded p-4 mb-4">
                        <i class="fa fa-envelope text-primary fa-2x me-3"></i>
                        <div><h5>Email</h5><p class="mb-0">{{ $c['email'] }}</p></div>
                    </div>
                    @endif
                    @if(!empty($c['phone']))
                    <div class="d-flex bg-light rounded p-4">
                        <i class="fa fa-phone-alt text-primary fa-2x me-3"></i>
                        <div><h5>Phone</h5><p class="mb-0">{{ $c['phone'] }}</p></div>
                    </div>
                    @endif
                </div>
                <div class="col-lg-8 wow fadeInUp" data-wow-delay="0.3s">
                    @include('school-site.kider.partials.enquiry-form', ['site' => $site])
                </div>
            </div>
        </div>
    </div>
@endsection
