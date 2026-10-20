@extends('school-site.kider.layout')
@php
    $steps = !empty($c['admission_steps']) ? $c['admission_steps'] : [
        ['title' => 'Enquire',          'desc' => 'Fill the enquiry form below or visit the school office.'],
        ['title' => 'Submit Documents', 'desc' => 'Provide the required documents for verification.'],
        ['title' => 'Interaction',      'desc' => 'A short, friendly interaction with the child and parents.'],
        ['title' => 'Confirm Admission','desc' => 'Complete the fee formalities and welcome to the family!'],
    ];
    $docs = !empty($c['documents_required']) ? $c['documents_required'] : [
        ['text' => 'Birth Certificate'],
        ['text' => 'Transfer Certificate (for classes above Nursery)'],
        ['text' => 'Aadhaar Card'],
        ['text' => 'Passport-size Photographs'],
        ['text' => 'Address Proof'],
    ];
    $rules = $c['admission_rules'] ?? [];

    // The school's own records (Standard, Fee Structure, Rules & Regulations), always as they are now.
    $lmsClasses = $lms->classes();
    $lmsFees    = $lms->fees();
    $classOptions = $lms->classNames()
        ?: array_values(array_filter(array_map(fn ($k) => trim((string) ($k['title'] ?? '')), $c['classes'] ?? [])))
        ?: ['Play Group', 'Nursery', 'LKG', 'UKG', 'Class I', 'Class II', 'Class III', 'Class IV', 'Class V', 'Class VI',
            'Class VII', 'Class VIII', 'Class IX', 'Class X', 'Class XI', 'Class XII'];
@endphp

@section('content')
    @include('school-site.kider.partials.page-header', [
        'heading' => 'Admissions',
        'tag' => 'Join Us' . (!empty($c['admission_session']) ? ' · ' . $c['admission_session'] : ''),
        'sub' => $c['admission_intro'] ?? 'Admissions are open. Enquire today and give your child the best start.',
    ])

    {{-- Steps --}}
    <section class="section">
        <div class="section-inner">
            <div class="section-head reveal">
                <span class="section-tag">How to Apply</span>
                <h2 class="section-title">Admission in <span class="gradient-text">4 Simple Steps</span></h2>
            </div>
            <div class="cards-grid">
                @foreach ($steps as $i => $s)
                    <div class="info-card reveal">
                        <div class="info-icon">{{ $i + 1 }}</div>
                        <h3 class="info-title">{{ $s['title'] ?? '' }}</h3>
                        <p class="info-desc">{{ $s['desc'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Classes, sections and subjects; the fee structure; the rules — from the school's records.
         The bands after the steps alternate their background. --}}
    @php $band = 0; @endphp
    @if ($lmsClasses)
        <section class="section {{ ++$band % 2 ? 'section-alt' : '' }}">
            <div class="section-inner reveal">
                <div class="section-head">
                    <span class="section-tag">Classes</span>
                    <h2 class="section-title">Classes, Sections &amp; Subjects</h2>
                </div>
                <div class="form-card" style="padding:0;overflow:hidden;">
                    @include('school-site.shared.lms-classes', ['classes' => $lmsClasses])
                </div>
            </div>
        </section>
    @endif

    @if ($lmsFees)
        <section class="section {{ ++$band % 2 ? 'section-alt' : '' }}">
            <div class="section-inner reveal" style="max-width:860px;">
                <div class="section-head">
                    <span class="section-tag">Fees</span>
                    <h2 class="section-title">Fee Structure</h2>
                </div>
                @include('school-site.shared.lms-fees', ['fees' => $lmsFees])
            </div>
        </section>
    @endif

    @if ($lms->hasRules())
        <section class="section {{ ++$band % 2 ? 'section-alt' : '' }}">
            <div class="section-inner reveal" style="max-width:860px;">
                <div class="section-head">
                    <span class="section-tag">Please Note</span>
                    <h2 class="section-title">Rules &amp; Regulations</h2>
                </div>
                <div class="form-card">
                    @include('school-site.shared.lms-rules', ['rules' => $lms->rules()])
                </div>
            </div>
        </section>
    @endif

    {{-- Documents + Rules --}}
    <section class="section {{ ++$band % 2 ? 'section-alt' : '' }}">
        <div class="section-inner split" style="align-items:start;">
            <div class="reveal">
                <span class="section-tag">Checklist</span>
                <h2 class="section-title">Documents Required</h2>
                <div style="display:flex;flex-direction:column;gap:12px;margin-top:16px;">
                    @foreach ($docs as $d)
                        <div style="display:flex;align-items:center;gap:12px;">
                            <span class="info-icon" style="width:34px;height:34px;font-size:16px;border-radius:11px;margin:0;flex-shrink:0;">✓</span>
                            <span class="body-text" style="margin:0;">{{ $d['text'] ?? '' }}</span>
                        </div>
                    @endforeach
                </div>

                @if (!empty($c['fee_note']) || !empty($c['curriculum_text']))
                    <div style="margin-top:24px;display:flex;flex-direction:column;gap:14px;">
                        @if (!empty($c['fee_note']))
                            <div><span class="section-tag" style="margin-bottom:6px;">Fee Structure</span><p class="body-text" style="margin:0;">{{ $c['fee_note'] }}</p></div>
                        @endif
                        @if (!empty($c['curriculum_text']))
                            <div><span class="section-tag" style="margin-bottom:6px;">Curriculum</span><p class="body-text" style="margin:0;">{{ $c['curriculum_text'] }}</p></div>
                        @endif
                    </div>
                @endif

                @if (!empty($rules))
                    <div style="margin-top:24px;">
                        <span class="section-tag" style="margin-bottom:6px;">{{ $lms->hasRules() ? 'Admission Rules' : 'Rules & Regulations' }}</span>
                        <ul style="list-style:none;display:flex;flex-direction:column;gap:8px;margin-top:8px;">
                            @foreach ($rules as $r)
                                <li class="body-text" style="margin:0;display:flex;gap:8px;"><span style="color:var(--primary);font-weight:700;">•</span><span>{{ $r['text'] ?? '' }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            {{-- Online admission form --}}
            <div class="reveal">
                <span class="section-tag">Enquire Now</span>
                <h2 class="section-title" style="margin-bottom:20px;">Online Admission</h2>
                @include('school-site.kider.partials.enquiry-form', ['site' => $site, 'type' => 'admission', 'button' => 'Submit', 'fields' => [
                    ['name' => 'name', 'label' => 'Student Name', 'required' => true],
                    ['name' => 'class', 'label' => 'Class', 'type' => 'select', 'options' => $classOptions, 'required' => true, 'subject' => true],
                    ['name' => 'parent', 'label' => "Father's / Mother's Name"],
                    ['name' => 'phone', 'label' => 'Phone', 'type' => 'tel', 'required' => true, 'placeholder' => '10-digit mobile'],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'placeholder' => 'you@example.com'],
                    ['name' => 'dob', 'label' => 'Date of Birth', 'type' => 'date'],
                    ['name' => 'message', 'label' => 'Message', 'type' => 'textarea'],
                ]])
            </div>
        </div>
    </section>
@endsection
