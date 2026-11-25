{{--
    Online Admission → Admission: the online admission form alone (user's ask
    of 2026-10-20) — the classes, fees and rules have pages of their own. Its
    fields are the admin's Add Student form's, with the student's photo and
    signature (shared.admission-form, user's ask of 2026-11-25); it arrives in
    the admin's Admissions and Enquiries → Website Enquiries both.
--}}
@extends('school-site.classic.layout', ['title' => 'Online Admission'])

@section('content')
    @include('school-site.classic.partials.banner', ['heading' => 'Online Admission'])

    <section class="page">
        <div class="wrap" style="max-width:880px;">
            @include('school-site.shared.lms-style')
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
        </div>
    </section>
@endsection
