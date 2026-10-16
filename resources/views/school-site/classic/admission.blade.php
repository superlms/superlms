@extends('school-site.classic.layout', ['title' => 'Online Admission'])
@php
    $classOptions = array_values(array_filter(array_map(fn ($k) => trim((string) ($k['title'] ?? '')), $c['classes'] ?? [])))
        ?: ['Play Group', 'Nursery', 'LKG', 'UKG', 'Class I', 'Class II', 'Class III', 'Class IV', 'Class V', 'Class VI',
            'Class VII', 'Class VIII', 'Class IX', 'Class X', 'Class XI', 'Class XII'];
@endphp

@section('content')
    @include('school-site.classic.partials.banner', ['heading' => 'Admission'])

    <section class="page">
        <div class="wrap">
            <div class="card prose">
                <p>{{ $c['admission_intro'] }}</p>
                @if (!empty($c['admission_session']))
                    <p><b>Admission Schedule:</b> {{ $c['admission_session'] }}</p>
                @endif

                @if (!empty($c['admission_steps']))
                    <h3>Admission Process</h3>
                    <ol>
                        @foreach ($c['admission_steps'] as $step)
                            <li><b>{{ $step['title'] ?? '' }}</b>@if (!empty($step['desc'])) — {{ $step['desc'] }}@endif</li>
                        @endforeach
                    </ol>
                @endif

                @if (!empty($c['documents_required']))
                    <h3>Documents Required</h3>
                    <ul>
                        @foreach ($c['documents_required'] as $d)
                            <li>{{ $d['text'] ?? '' }}</li>
                        @endforeach
                    </ul>
                @endif

                @if (!empty($c['admission_rules']))
                    <h3>Rules &amp; Regulations</h3>
                    <ol>
                        @foreach ($c['admission_rules'] as $r)
                            <li>{{ $r['text'] ?? '' }}</li>
                        @endforeach
                    </ol>
                @endif

                @if (!empty($c['fee_note']))
                    <p><b>Fee:</b> {{ $c['fee_note'] }}
                        @if ($site->isPageEnabled('fee-structure')) <a href="{{ url('fee-structure') }}" style="color:var(--primary);font-weight:600;">See the fee structure →</a>@endif
                    </p>
                @endif
            </div>

            <div class="card">
                <h2 class="title">Online Admission Form</h2>
                <p style="color:var(--muted);margin:4px 0 18px;">Please fill in the form below to get in touch with us about admission.</p>
                @include('school-site.classic.partials.form', [
                    'kind'   => 'Online Admission',
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
    </section>
@endsection
