{{-- Contact → Appointment for Vibrant and Breeze: Classic's form to book a visit, with the school's contact beside it. --}}
@extends('school-site.' . $tpl . '.layout', ['title' => 'Appointment'])

@section('content')
    @include('school-site.' . $tpl . '.partials.banner', ['heading' => 'Appointment'])

    <section class="pg">
        <div class="wrap">
            <div class="form-split">
                <div class="panel">
                    <span class="eyebrow">Plan a visit</span>
                    <h2 class="h2">Book an <span class="hl">appointment</span></h2>
                    <p class="lead-text" style="margin:10px 0 24px;">
                        Planning a visit to {{ $c['school_name'] }}? Tell us when suits you and the school office will confirm a time.
                    </p>
                    @include('school-site.classic.partials.form', [
                        'kind'   => 'Appointment',
                        'type'   => 'appointment',
                        'button' => 'Request Appointment',
                        'fields' => [
                            ['name' => 'name', 'label' => 'Name', 'required' => true],
                            ['name' => 'phone', 'label' => 'Mobile No.', 'type' => 'tel', 'required' => true],
                            ['name' => 'email', 'label' => 'Email ID', 'type' => 'email'],
                            ['name' => 'date', 'label' => 'Preferred Date', 'type' => 'date', 'subject' => true],
                            ['name' => 'purpose', 'label' => 'Purpose', 'type' => 'select', 'wide' => true,
                                'options' => ['Admission enquiry', 'Meet the Principal', 'Campus visit', 'Other']],
                            ['name' => 'message', 'label' => 'Message', 'type' => 'textarea'],
                        ],
                    ])
                </div>
                @include('school-site.modern.partials.reach')
            </div>
        </div>
    </section>
@endsection
