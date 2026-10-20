@extends('school-site.classic.layout', ['title' => 'Appointment'])

@section('content')
    @include('school-site.classic.partials.banner', ['heading' => 'Appointment'])

    <section class="page">
        <div class="wrap" style="max-width:860px;">
            <div class="card">
                <p style="color:var(--muted);margin-bottom:18px;">
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
        </div>
    </section>
@endsection
