{{--
    Online Admission → Admission: the online admission form alone (user's ask
    of 2026-10-20) — the classes, fees and rules have pages of their own. Its
    Class list is the school's classes (the Standard page), else Website
    Data's class cards; it arrives in Enquiries → Website Enquiries.
--}}
@extends('school-site.classic.layout', ['title' => 'Online Admission'])
@php
    $classOptions = $lms->classNames()
        ?: array_values(array_filter(array_map(fn ($k) => trim((string) ($k['title'] ?? '')), $c['classes'] ?? [])))
        ?: ['Play Group', 'Nursery', 'LKG', 'UKG', 'Class I', 'Class II', 'Class III', 'Class IV', 'Class V', 'Class VI',
            'Class VII', 'Class VIII', 'Class IX', 'Class X', 'Class XI', 'Class XII'];
@endphp

@section('content')
    @include('school-site.classic.partials.banner', ['heading' => 'Online Admission'])

    <section class="page">
        <div class="wrap" style="max-width:880px;">
            @include('school-site.shared.lms-style')
            <div class="lms-card">
                <div class="lms-head">
                    <h2>Online Admission Form</h2>
                    <span class="meta">* Required</span>
                    <p class="note">Fill in the form below and the school office will get in touch with you.</p>
                </div>
                <div class="lms-form">
                @include('school-site.classic.partials.form', [
                    'kind'   => 'Online Admission',
                    'type'   => 'admission',
                    'button' => 'Submit',
                    'fields' => [
                        ['name' => 'name', 'label' => 'Student Name', 'required' => true],
                        ['name' => 'class', 'label' => 'Class', 'type' => 'select', 'options' => $classOptions, 'required' => true, 'subject' => true],
                        ['name' => 'parent', 'label' => "Father's / Mother's Name"],
                        ['name' => 'phone', 'label' => 'Phone', 'type' => 'tel', 'required' => true],
                        ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
                        ['name' => 'dob', 'label' => 'Date of Birth', 'type' => 'date'],
                        ['name' => 'address', 'label' => 'Address', 'wide' => true],
                        ['name' => 'previous_school', 'label' => 'Previous School'],
                        ['name' => 'reference', 'label' => 'Reference', 'placeholder' => 'How did you hear about us?'],
                        ['name' => 'message', 'label' => 'Message', 'type' => 'textarea'],
                    ],
                ])
                </div>
            </div>
        </div>
    </section>
@endsection
