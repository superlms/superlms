{{-- ══════════════════════════════════════════════════════════════════
     Public website — "new" design shell.
     Pages: @extends('components.site.layout') with @section('title'),
     @section('content'); optional @push('head') / @push('scripts').
     The earlier design lives untouched in components/website/* —
     App\Support\WebsiteDesign picks which one is served.
══════════════════════════════════════════════════════════════════ --}}
@php
    $siteVer = @filemtime(public_path('site/site.css')) . '-' . @filemtime(public_path('site/site.js'));
    $logo = \App\Support\Platform::logoUrl();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script>document.documentElement.classList.add('js');</script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google-site-verification" content="GdMracC0ZkQ_UWHR922wYADBk2AS9KO5yALR8BFNFdY" />
    <title>@yield('title', 'SUPERLMS') — SUPERLMS</title>
    @hasSection('description')
        <meta name="description" content="@yield('description')">
    @endif
    <link rel="icon" type="image/png" href="{{ asset('website-image/Group 11525.png') }}">
    @include('partials.pwa-head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('site/site.css') }}?v={{ $siteVer }}">
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-7S4FD1GMPK"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-7S4FD1GMPK');
    </script>
    @stack('head')
</head>
<body class="has-ask">

    @include('components.site.partials.header', ['logo' => $logo])

    <main id="main">
        @yield('content')
    </main>

    @hasSection('no_app_section')
    @else
        @include('components.site.partials.app-section')
    @endif

    @include('components.site.partials.footer', ['logo' => $logo])

    {{-- "Ask anything": a chat answered from this website's own pages
         (POST /api/website/ask → App\Services\Gemini\WebsiteAssistant).
         When the assistant can't answer, it offers WhatsApp with the question. --}}
    <div class="askp" id="askPanel" role="dialog" aria-labelledby="askTitle" hidden>
        <div class="askp-head">
            <span class="askp-logo"><img src="{{ $logo }}" alt=""></span>
            <div class="askp-ttl">
                <b id="askTitle">{{ config('gemini.brand', 'Super LMS') }} Assistant</b>
                <small><i></i> Answers from superlms.in</small>
            </div>
            <button type="button" class="askp-btn" id="askReset" aria-label="New chat" title="New chat"><x-site.icon name="refresh" /></button>
            <button type="button" class="askp-btn" id="askClose" aria-label="Close" title="Close"><x-site.icon name="x" /></button>
        </div>
        <div class="askp-body" id="askBody" aria-live="polite">
            <div class="askp-intro" id="askIntro">
                <div class="askp-hi">Hi! Ask me anything about SUPERLMS.</div>
                <p>Features, pricing, setup, apps or services — in English, हिन्दी or Hinglish.</p>
                <div class="askp-sugs">
                    @foreach (['How much does SUPERLMS cost?', 'Which modules are included?', 'How does a school get started?', 'Is there a mobile app?', 'School ko kuch pay karna padta hai?'] as $s)
                        <button type="button" class="askp-sug" data-q="{{ $s }}">{{ $s }}</button>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="askp-foot">
            <a href="https://wa.me/919084748563" target="_blank" rel="noopener noreferrer" id="askWa"><x-site.icon name="whatsapp" /> WhatsApp the team</a>
            <a href="{{ url('web/demo') }}">Book a free demo <x-site.icon name="chevron-right" /></a>
        </div>
    </div>

    <form class="ask" id="askPill" data-wa="919084748563" autocomplete="off">
        <img class="ask-logo" src="{{ $logo }}" alt="">
        <label class="sr-only" for="askInput">Ask anything</label>
        <input id="askInput" type="text" placeholder="Ask anything…" maxlength="500" enterkeyhint="send">
        <button type="submit" aria-label="Ask"><x-site.icon name="arrow-up" /></button>
    </form>

    {{-- Toast (forms) --}}
    <div class="toast" id="siteToast" role="status" aria-live="polite">
        <div class="toast-ic"><x-site.icon name="check" /></div>
        <div>
            <div class="toast-t"></div>
            <div class="toast-m"></div>
        </div>
    </div>

    <script src="{{ asset('site/site.js') }}?v={{ $siteVer }}"></script>
    @stack('scripts')
</body>
</html>
