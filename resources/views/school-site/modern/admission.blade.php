{{--
    Online Admission → Admission for Vibrant and Breeze: the online admission
    form, as on Classic (its Class list the school's classes, else Website
    Data's class cards; it arrives in Enquiries → Website Enquiries), with
    how to reach the admission office beside it.
--}}
@extends('school-site.' . $tpl . '.layout', ['title' => 'Online Admission'])
@php
    use App\Support\ModernSite;

    $sw  = fn ($i) => ModernSite::swatch($tpl, $i, $theme['primary']);
    $tel = ModernSite::tel($c);
    $classOptions = $lms->classNames()
        ?: array_values(array_filter(array_map(fn ($k) => trim((string) ($k['title'] ?? '')), $c['classes'] ?? [])))
        ?: ['Play Group', 'Nursery', 'LKG', 'UKG', 'Class I', 'Class II', 'Class III', 'Class IV', 'Class V', 'Class VI',
            'Class VII', 'Class VIII', 'Class IX', 'Class X', 'Class XI', 'Class XII'];
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
