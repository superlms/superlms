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

    {{-- The map from Website Data (the school's pin, its embed link or its address). --}}
    @php $mapSrc = \App\Models\SchoolWebsite::mapSrc($c); @endphp
    @if ($mapSrc)
        <section class="section" style="padding-top:0;">
            <div class="section-inner reveal">
                <div style="height:380px;border-radius:24px;overflow:hidden;background:#EEF0F4;">
                    <iframe src="{{ $mapSrc }}" title="Map to {{ $c['school_name'] }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                            style="width:100%;height:100%;border:0;" allowfullscreen></iframe>
                </div>
            </div>
        </section>
    @endif
@endsection
