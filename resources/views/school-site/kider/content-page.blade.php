{{--
    Generic content page — heading, rich body and an optional image.

    Every page pageGroups() marks as type 'content' renders through here
    (History, Vision, Mission, the desks, Labs, Classrooms, Transport,
    Rules, Curriculum, Career …), so a school can publish the whole CBSE
    page set from Website Data without a blade per page.
    Receives $page = ['heading', 'body', 'image'] on top of the usual data.
--}}
@extends('school-site.kider.layout')

@php
    use App\Models\SchoolWebsite;
    $img  = SchoolWebsite::media($page['image'] ?? null);
    $body = trim((string) ($page['body'] ?? ''));
    // Rules & Regulations: the rules saved on More → Rules & Regulations, under the page's own text.
    $rules = $current === 'rules' && $lms->hasRules() ? $lms->rules() : null;
@endphp

@section('content')
    @include('school-site.kider.partials.page-header', [
        'heading' => $page['heading'],
        'tag'     => $c['school_name'],
    ])

    <section class="section">
        <div class="section-inner">
            @if ($body === '' && ! $rules && $current !== 'career')
                <div style="max-width:640px;margin:0 auto;text-align:center;">
                    <p class="section-subtitle" style="margin:0 auto;">
                        This page is being updated. Please check back shortly, or
                        @if (!empty($c['phone'])) call us on {{ $c['phone'] }}. @else get in touch with the school office. @endif
                    </p>
                </div>
            @elseif ($body !== '')
                <div class="content-page" @if($img) style="display:grid;grid-template-columns:1.35fr 0.65fr;gap:48px;align-items:start;" @endif>
                    <div class="prose-body">
                        @foreach (preg_split('/\r\n\r\n|\n\n/', $body) as $para)
                            @if (trim($para) !== '')
                                <p>{!! nl2br(e(trim($para))) !!}</p>
                            @endif
                        @endforeach
                    </div>
                    @if ($img)
                        <figure style="border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow2);border:5px solid #fff;">
                            <img src="{{ $img }}" alt="{{ $page['heading'] }}" style="width:100%;height:100%;object-fit:cover;">
                        </figure>
                    @endif
                </div>
            @endif

            @if ($rules)
                <div class="form-card" style="max-width:860px;margin:{{ $body !== '' ? '40px' : '0' }} auto 0;">
                    @include('school-site.shared.lms-rules', ['rules' => $rules])
                </div>
            @endif
        </div>
    </section>

    {{-- Career: the job application form; applications arrive in the admin's Website Enquiries. --}}
    @if ($current === 'career')
        <section class="section section-alt">
            <div class="section-inner" style="max-width:760px;">
                <div class="section-head reveal">
                    <span class="section-tag">Join Our Team</span>
                    <h2 class="section-title">Apply for a Job</h2>
                </div>
                <div class="reveal">
                    @include('school-site.kider.partials.enquiry-form', ['site' => $site, 'type' => 'career', 'button' => 'Send Application', 'fields' => [
                        ['name' => 'name', 'label' => 'Your Name', 'required' => true],
                        ['name' => 'phone', 'label' => 'Mobile No.', 'type' => 'tel', 'required' => true, 'placeholder' => '10-digit mobile'],
                        ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'placeholder' => 'you@example.com'],
                        ['name' => 'qualification', 'label' => 'Qualification', 'required' => true],
                        ['name' => 'applied_for', 'label' => 'Designation', 'required' => true, 'subject' => true, 'placeholder' => 'e.g. PGT Physics'],
                        ['name' => 'experience', 'label' => 'Experience', 'placeholder' => 'e.g. 4 years'],
                        ['name' => 'message', 'label' => 'Why You?', 'type' => 'textarea'],
                    ]])
                </div>
            </div>
        </section>
    @endif

    <style>
        .prose-body p { font-size: 16px; line-height: 1.95; color: var(--text2); margin-bottom: 18px; }
        .prose-body p:first-of-type::first-letter { font-family: 'Baloo 2', cursive; font-size: 3.1rem; line-height: .86; font-weight: 800; color: var(--primary); float: left; margin: 6px 12px 0 0; }
        .prose-body p:last-child { margin-bottom: 0; }
        @media (max-width: 900px) { .content-page { grid-template-columns: 1fr !important; gap: 28px !important; } }
    </style>

    {{-- ══════════ CTA ══════════ --}}
    <section class="cta-section">
        <div class="cta-card reveal">
            <h2 class="cta-title">{{ $c['cta_heading'] }}</h2>
            <p class="cta-desc">{{ $c['cta_text'] }}</p>
            <div class="cta-actions">
                <a class="btn btn-primary btn-lg" href="{{ $site->isPageEnabled('contact') ? url('contact') : $site->adminLoginUrl() }}">Get In Touch →</a>
            </div>
        </div>
    </section>
@endsection
