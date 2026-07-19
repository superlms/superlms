@extends('school-site.kider.layout')

@section('content')
    @include('school-site.kider.partials.page-header', ['heading' => 'Make an Appointment', 'tag' => 'Visit Us'])

    <section class="section">
        <div class="split section-inner" style="align-items:start;">
            <div class="reveal">
                <span class="section-tag">Book a Visit</span>
                <h2 class="section-title">Schedule a Visit or Admission Appointment</h2>
                <p class="body-text">Planning to visit {{ $c['school_name'] }} or enquire about admissions? Fill in the form and our team will reach out to schedule a convenient time.</p>
                <div style="display:flex;flex-direction:column;gap:10px;margin-top:16px;">
                    @if(!empty($c['phone']))<p class="body-text" style="margin:0;">📞 {{ $c['phone'] }}</p>@endif
                    @if(!empty($c['email']))<p class="body-text" style="margin:0;">✉️ {{ $c['email'] }}</p>@endif
                    @if(!empty($c['address']))<p class="body-text" style="margin:0;">📍 {{ $c['address'] }}</p>@endif
                </div>
            </div>
            <div class="reveal">
                @include('school-site.kider.partials.enquiry-form', ['site' => $site])
            </div>
        </div>
    </section>
@endsection
