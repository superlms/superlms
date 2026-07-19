@extends('school-site.kider.layout')

@section('content')
    @include('school-site.kider.partials.page-header', [
        'heading' => 'Contact Us',
        'tag' => 'Get In Touch',
        'sub' => 'Have a question or want to enquire about admissions? Send us a message and our team will respond shortly.',
    ])

    <section class="section">
        <div class="split section-inner" style="grid-template-columns: 0.85fr 1.15fr; align-items:start;">
            <div class="reveal">
                @if(!empty($c['address']))
                    <div class="contact-card">
                        <div class="info-icon">📍</div>
                        <div><h5>Address</h5><p>{{ $c['address'] }}</p></div>
                    </div>
                @endif
                @if(!empty($c['email']))
                    <div class="contact-card">
                        <div class="info-icon">✉️</div>
                        <div><h5>Email</h5><p>{{ $c['email'] }}</p></div>
                    </div>
                @endif
                @if(!empty($c['phone']))
                    <div class="contact-card">
                        <div class="info-icon">📞</div>
                        <div><h5>Phone</h5><p>{{ $c['phone'] }}</p></div>
                    </div>
                @endif
            </div>
            <div class="reveal">
                @include('school-site.kider.partials.enquiry-form', ['site' => $site])
            </div>
        </div>
    </section>
@endsection
