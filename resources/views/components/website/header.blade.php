{{-- ══════════════════════════════════════
     COMMON NAVBAR
══════════════════════════════════════ --}}
<nav class="navbar" id="navbar">
     <a class="nav-logo" href="#">
            <div class="flex-shrink-0 flex">
                <img src="{{ asset('website-image/Group 11525.png') }}" alt="Logo"
                    class="w-12 h-12 object-contain mb-2">
            </div>
            <div class="logo-text">SUPER<span>LMS</span></div>
        </a>

    <ul class="nav-links">
        <li>
            <a href="{{ url('/') }}" class="{{ request()->is('/') ? 'active' : '' }}">Home</a>
        </li>
        <li>
            <a href="{{ url('web/about') }}" class="{{ request()->is('web/about') ? 'active' : '' }}">About</a>
        </li>
        <li>
            <a href="{{ url('web/features') }}" class="{{ request()->is('web/features') ? 'active' : '' }}">Features</a>
        </li>
        <li>
            <a href="{{ url('web/services') }}" class="{{ request()->is('web/services') ? 'active' : '' }}">Services</a>
        </li>
        <li>
            <a href="{{ url('web/why-us') }}" class="{{ request()->is('web/why-us') ? 'active' : '' }}">Why Us</a>
        </li>
        <li>
            <a href="{{ url('web/pricing') }}" class="{{ request()->is('web/pricing') ? 'active' : '' }}">Pricing</a>
        </li>
        <li>
            <a href="{{ url('web/contact') }}" class="{{ request()->is('web/contact') ? 'active' : '' }}">Contact</a>
        </li>
    </ul>

    <div class="nav-cta">
        <a href="{{ url('web/demo') }}" class="btn btn-primary">Request Demo</a>
    </div>

    {{-- id="hamburger" & id="mobileClose" match superlms-homepage-single.html JS --}}
    <button class="hamburger" id="hamburger" aria-label="Open menu">
        <span></span><span></span><span></span>
    </button>
</nav>

{{-- Mobile Nav --}}
<div class="mobile-nav" id="mobileNav">
    <button class="mobile-nav-close" id="mobileClose">✕</button>
    <a href="{{ url('/') }}" class="mobile-nav-link">Home</a>
    <a href="{{ url('web/about') }}" class="mobile-nav-link">About</a>
    <a href="{{ url('web/features') }}" class="mobile-nav-link">Features</a>
    <a href="{{ url('web/services') }}" class="mobile-nav-link">Services</a>
    <a href="{{ url('web/why-us') }}" class="mobile-nav-link">Why Us</a>
    <a href="{{ url('web/pricing') }}" class="mobile-nav-link">Pricing</a>
    <a href="{{ url('web/contact') }}" class="mobile-nav-link">Contact</a>
    <a href="{{ url('web/demo') }}" class="btn btn-primary" style="font-size:15px;padding:13px 32px;">Request Demo</a>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var mobileNav  = document.getElementById('mobileNav');
        var hamburger  = document.getElementById('hamburger');
        var mobileClose = document.getElementById('mobileClose');
        if (hamburger)   hamburger.addEventListener('click',   function () { mobileNav && mobileNav.classList.add('open'); });
        if (mobileClose) mobileClose.addEventListener('click', function () { mobileNav && mobileNav.classList.remove('open'); });
        document.querySelectorAll('.mobile-nav-link, .mobile-nav a').forEach(function (l) {
            l.addEventListener('click', function () { mobileNav && mobileNav.classList.remove('open'); });
        });
        var navbar = document.getElementById('navbar');
        if (navbar) {
            window.addEventListener('scroll', function () {
                navbar.classList.toggle('scrolled', window.scrollY > 40);
            });
        }
    });
</script>
