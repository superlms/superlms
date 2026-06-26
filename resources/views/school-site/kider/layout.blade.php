{{--
    Kider school-website template — master layout.
    Receives: $site (SchoolWebsite), $c (resolved content), $theme, $nav (slug=>label), $current
--}}
@php $asset = fn($p) => asset('school-templates/kider/' . $p); @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? $c['school_name'] }}</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($c['about_text'] ?? ''), 150) }}">
    @if(!empty($c['logo']))<link href="{{ \App\Models\SchoolWebsite::media($c['logo']) }}" rel="icon">@endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@600&family=Lobster+Two:wght@700&display=swap" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <link href="{{ $asset('lib/animate/animate.min.css') }}" rel="stylesheet">
    <link href="{{ $asset('lib/owlcarousel/assets/owl.carousel.min.css') }}" rel="stylesheet">
    <link href="{{ $asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ $asset('css/style.css') }}" rel="stylesheet">

    {{-- ── Theme override (per-school colours) ── --}}
    <style>
        :root { --primary: {{ $theme['primary'] }}; --light: {{ $theme['light'] }}; --dark: {{ $theme['dark'] }}; }
        .bg-primary { background-color: {{ $theme['primary'] }} !important; }
        .text-primary { color: {{ $theme['primary'] }} !important; }
        .bg-light { background-color: {{ $theme['light'] }} !important; }
        .btn-primary { background-color: {{ $theme['primary'] }} !important; border-color: {{ $theme['primary'] }} !important; }
        .btn-primary:hover { background-color: {{ $theme['dark'] }} !important; border-color: {{ $theme['dark'] }} !important; }
        .border-primary { border-color: {{ $theme['primary'] }} !important; }
        .bg-dark { background-color: {{ $theme['dark'] }} !important; }
        .navbar-light .navbar-nav .nav-link.active, .navbar-light .navbar-nav .nav-link:hover { color: {{ $theme['primary'] }} !important; }
        a.btn-link.text-white-50:hover { color: {{ $theme['primary'] }} !important; }
    </style>
</head>

<body>
<div class="container-fluid bg-white p-0">

    {{-- Spinner --}}
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"><span class="sr-only">Loading...</span></div>
    </div>

    {{-- Navbar --}}
    <nav class="navbar navbar-expand-lg bg-white navbar-light sticky-top px-4 px-lg-5 py-lg-0">
        <a href="{{ url('/') }}" class="navbar-brand d-flex align-items-center">
            @if(!empty($c['logo']))
                <img src="{{ \App\Models\SchoolWebsite::media($c['logo']) }}" alt="{{ $c['school_name'] }}" style="height:46px;width:auto;" class="me-2">
            @else
                <h1 class="m-0 text-primary"><i class="fa fa-book-reader me-2"></i>{{ \Illuminate\Support\Str::limit($c['school_name'], 18) }}</h1>
            @endif
        </a>
        <button type="button" class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav mx-auto">
                @foreach ($nav as $slug => $label)
                    <a href="{{ $slug === 'home' ? url('/') : url($slug) }}"
                       class="nav-item nav-link {{ $current === $slug ? 'active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
            <a href="{{ $site->adminLoginUrl() }}" target="_blank" rel="noopener"
               class="btn btn-primary rounded-pill px-3 d-none d-lg-block">Login<i class="fa fa-arrow-right ms-2"></i></a>
        </div>
    </nav>

    {{-- Page content --}}
    @yield('content')

    {{-- Footer --}}
    <div class="container-fluid bg-dark text-white-50 footer pt-5 mt-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-lg-4 col-md-6">
                    <h3 class="text-white mb-4">Get In Touch</h3>
                    @if(!empty($c['address']))<p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>{{ $c['address'] }}</p>@endif
                    @if(!empty($c['phone']))<p class="mb-2"><i class="fa fa-phone-alt me-3"></i>{{ $c['phone'] }}</p>@endif
                    @if(!empty($c['email']))<p class="mb-2"><i class="fa fa-envelope me-3"></i>{{ $c['email'] }}</p>@endif
                    <div class="d-flex pt-2">
                        @if(!empty($c['twitter']))<a class="btn btn-outline-light btn-social" href="{{ $c['twitter'] }}" target="_blank"><i class="fab fa-twitter"></i></a>@endif
                        @if(!empty($c['facebook']))<a class="btn btn-outline-light btn-social" href="{{ $c['facebook'] }}" target="_blank"><i class="fab fa-facebook-f"></i></a>@endif
                        @if(!empty($c['youtube']))<a class="btn btn-outline-light btn-social" href="{{ $c['youtube'] }}" target="_blank"><i class="fab fa-youtube"></i></a>@endif
                        @if(!empty($c['instagram']))<a class="btn btn-outline-light btn-social" href="{{ $c['instagram'] }}" target="_blank"><i class="fab fa-instagram"></i></a>@endif
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <h3 class="text-white mb-4">Quick Links</h3>
                    @foreach ($nav as $slug => $label)
                        <a class="btn btn-link text-white-50" href="{{ $slug === 'home' ? url('/') : url($slug) }}">{{ $label }}</a>
                    @endforeach
                    <a class="btn btn-link text-white-50" href="{{ $site->adminLoginUrl() }}" target="_blank">Admin / Staff Login</a>
                </div>
                <div class="col-lg-4 col-md-6">
                    <h3 class="text-white mb-4">{{ $c['school_name'] }}</h3>
                    <p>{{ \Illuminate\Support\Str::limit(strip_tags($c['about_text']), 160) }}</p>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="copyright">
                <div class="row">
                    <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                        &copy; <a class="border-bottom" href="{{ url('/') }}">{{ $c['school_name'] }}</a>, All Rights Reserved.
                        Designed By <a class="border-bottom" href="https://htmlcodex.com" target="_blank" rel="noopener">HTML Codex</a>
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        Powered by <a class="border-bottom" href="https://edyonelms.in" target="_blank" rel="noopener">EDYONE LMS</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
</div>

<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ $asset('lib/wow/wow.min.js') }}"></script>
<script src="{{ $asset('lib/easing/easing.min.js') }}"></script>
<script src="{{ $asset('lib/waypoints/waypoints.min.js') }}"></script>
<script src="{{ $asset('lib/owlcarousel/owl.carousel.min.js') }}"></script>
<script src="{{ $asset('js/main.js') }}"></script>
@stack('scripts')
</body>
</html>
