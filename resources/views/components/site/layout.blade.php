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

    {{-- Floating "ask" pill: the question opens a WhatsApp chat with the team. --}}
    <form class="ask" id="askPill" data-wa="919084748563" autocomplete="off">
        <img class="ask-logo" src="{{ $logo }}" alt="">
        <label class="sr-only" for="askInput">Ask anything</label>
        <input id="askInput" type="text" placeholder="Ask anything…" maxlength="300">
        <button type="submit" aria-label="Send on WhatsApp"><x-site.icon name="arrow-up" /></button>
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
