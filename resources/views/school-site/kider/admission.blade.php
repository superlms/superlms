{{--
    Online Admission → Admission: the online admission form alone (user's ask
    of 2026-10-20) — the classes, fees and rules have pages of their own. Its
    Class list is the school's classes (the Standard page), else Website
    Data's class cards; it arrives in Enquiries → Website Enquiries.
--}}
@extends('school-site.kider.layout')
@php
    $classOptions = $lms->classNames()
        ?: array_values(array_filter(array_map(fn ($k) => trim((string) ($k['title'] ?? '')), $c['classes'] ?? [])))
        ?: ['Play Group', 'Nursery', 'LKG', 'UKG', 'Class I', 'Class II', 'Class III', 'Class IV', 'Class V', 'Class VI',
            'Class VII', 'Class VIII', 'Class IX', 'Class X', 'Class XI', 'Class XII'];
@endphp

@section('content')
    @include('school-site.kider.partials.page-header', [
        'heading' => 'Online Admission',
        'tag'     => 'Admissions Open',
    ])

    <section class="section">
        <div class="section-inner" style="max-width:860px;">
            @include('school-site.shared.lms-style')
            <div class="lms-card reveal">
                <div class="lms-head">
                    <h2>Online Admission Form</h2>
                    <span class="meta">* Required</span>
                    <p class="note">Fill in the form below and the school office will get in touch with you.</p>
                </div>
                <div class="lms-form">
                @include('school-site.kider.partials.enquiry-form', ['site' => $site, 'type' => 'admission', 'button' => 'Submit', 'fields' => [
                    ['name' => 'name', 'label' => 'Student Name', 'required' => true],
                    ['name' => 'class', 'label' => 'Class', 'type' => 'select', 'options' => $classOptions, 'required' => true, 'subject' => true],
                    ['name' => 'parent', 'label' => "Father's / Mother's Name"],
                    ['name' => 'phone', 'label' => 'Phone', 'type' => 'tel', 'required' => true, 'placeholder' => '10-digit mobile'],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'placeholder' => 'you@example.com'],
                    ['name' => 'dob', 'label' => 'Date of Birth', 'type' => 'date'],
                    ['name' => 'address', 'label' => 'Address', 'full' => true],
                    ['name' => 'message', 'label' => 'Message', 'type' => 'textarea'],
                ]])
                </div>
            </div>
        </div>
    </section>
@endsection
