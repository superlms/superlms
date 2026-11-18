@php
    $def      = config('website_pages.faqs', []);
    $tag      = $def['tag']      ?? 'Help Center';
    $title    = $def['title']    ?? '';
    $subtitle = $def['subtitle'] ?? '';
    $faqs       = $faqs ?? collect();
    $categories = $categories ?? collect();
@endphp
@extends('components.site.layout')

@section('title', 'FAQs')

@section('content')

    <section class="phero">
        <div class="container">
            <span class="eyebrow rv">{{ $tag }}</span>
            <h1 class="h1 rv" style="--rd:.05s">{{ $title }}</h1>
            <p class="lead rv" style="--rd:.1s">{{ $subtitle }}</p>
        </div>
    </section>

    <section class="section" style="padding-top:24px;">
        <div class="container" data-faq>
            @if ($faqs->isEmpty())
                <p class="faq-empty">No FAQs published yet. Please check back soon.</p>
            @else
                @if ($categories->count() > 1)
                    <div class="faq-chips rv">
                        <div class="seg">
                            <button type="button" class="is-active" data-cat-btn="all">All</button>
                            @foreach ($categories as $cat)
                                <button type="button" data-cat-btn="{{ $cat }}">{{ $cat }}</button>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="faq rv">
                    @foreach ($faqs as $faq)
                        <details class="faq-item" data-cat="{{ $faq->category }}">
                            <summary>{{ $faq->question }}<span class="faq-i"><x-site.icon name="plus" /></span></summary>
                            <div class="faq-a">{!! nl2br(e($faq->answer)) !!}</div>
                        </details>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="section-tight">
        <div class="container">
            <div class="cta rv rv-s">
                <h2 class="h2">Still have <span class="grad">questions?</span></h2>
                <p class="lead">Our friendly team is always happy to help you with anything about SUPERLMS — whether it's
                    choosing the right plan, setting up your school, migrating your data or understanding a specific feature.
                    Reach out over call, chat or WhatsApp and we'll get back to you quickly. You can also book a free, no-obligation
                    demo and see exactly how SUPERLMS can work for your institution.</p>
                <div class="actions">
                    <a href="{{ url('web/contact') }}" class="btn btn-primary btn-lg">Contact Us <x-site.icon name="arrow-right" class="ic-arrow" /></a>
                    <a href="{{ url('web/demo') }}" class="btn btn-white btn-lg">Request a Demo</a>
                </div>
            </div>
        </div>
    </section>

@endsection
