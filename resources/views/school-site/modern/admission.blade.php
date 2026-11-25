{{--
    Online Admission → Admission for Vibrant, Breeze and Mint: the online
    admission form, as on Classic — the admin's Add Student fields with the
    student's photo and signature (shared.admission-form); it arrives in the
    admin's Admissions and Enquiries → Website Enquiries both — with how to
    reach the admission office beside it.
--}}
@extends('school-site.' . $tpl . '.layout', ['title' => 'Online Admission'])
@php
    use App\Support\ModernSite;

    $sw  = fn ($i) => ModernSite::swatch($tpl, $i, $theme['primary']);
    $tel = ModernSite::tel($c);
@endphp

@section('content')
    @include('school-site.' . $tpl . '.partials.banner', ['heading' => 'Online Admission'])

    <section class="pg">
        <div class="wrap">
            @include('school-site.shared.lms-style')
            <div class="form-split">
                <div class="lms-card">
                    <div class="lms-head">
                        <h2>Online Admission Form</h2>
                        <span class="meta">* Required</span>
                        <p class="note">Fill in the student's details, add the photo and signature, and the school office will get in touch with you.</p>
                    </div>
                    <div class="lms-form">
                    @include('school-site.shared.admission-form')
                    </div>
                </div>

                <aside class="help-card">
                    <span class="badge-ico" style="{{ $sw(0) }}">@include('school-site.modern.partials.icon', ['n' => 'school'])</span>
                    <h3 class="h3">Need help with admission?</h3>
                    <p>The admission office will guide you through classes, fees and the documents to bring.</p>
                    <div class="info-list">
                        @if (!empty($c['phone']))<div class="info-card"><div><span>Call</span><a href="tel:{{ $tel }}">{{ $c['phone'] }}</a></div></div>@endif
                        @if (!empty($c['email']))<div class="info-card"><div><span>Email</span><a href="mailto:{{ $c['email'] }}">{{ $c['email'] }}</a></div></div>@endif
                        @if (!empty($c['address']))<div class="info-card"><div><span>Visit</span><b>{{ $c['address'] }}</b></div></div>@endif
                    </div>
                </aside>
            </div>
        </div>
    </section>
@endsection
