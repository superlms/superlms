{{-- Announcement bar + sticky navbar + mobile menu --}}
@php
    $links = [
        ['url' => url('/'),             'label' => 'Home',     'on' => request()->is('/')],
        ['url' => url('web/about'),     'label' => 'About',    'on' => request()->is('web/about')],
        ['url' => url('web/features'),  'label' => 'Features', 'on' => request()->is('web/features')],
        ['url' => url('web/services'),  'label' => 'Services', 'on' => request()->is('web/services')],
        ['url' => url('web/why-us'),    'label' => 'Why Us',   'on' => request()->is('web/why-us')],
        ['url' => url('web/pricing'),   'label' => 'Pricing',  'on' => request()->is('web/pricing')],
        ['url' => url('web/contact'),   'label' => 'Contact',  'on' => request()->is('web/contact')],
    ];
@endphp

<div class="topbar" role="region" aria-label="Announcement">
    <div class="container">
        <span class="topbar-ico"><x-site.icon name="gift" /></span>
        <span><b>Zero Cost to Schools:</b> The School Pays Nothing.</span>
        <a href="{{ url('web/pricing') }}">View Pricing <x-site.icon name="chevron-right" /></a>
    </div>
    <button type="button" class="topbar-close" aria-label="Close"><x-site.icon name="x" /></button>
</div>

<header class="nav" id="navbar">
    <div class="container nav-in">
        <a class="brand" href="{{ url('/') }}" aria-label="SUPERLMS home">
            <img src="{{ $logo }}" alt="Logo">
            <span class="brand-word">SUPER<span>LMS</span></span>
        </a>

        <nav aria-label="Main">
            <ul class="nav-links">
                @foreach ($links as $l)
                    <li><a href="{{ $l['url'] }}" class="{{ $l['on'] ? 'is-active' : '' }}">{{ $l['label'] }}</a></li>
                @endforeach
            </ul>
        </nav>

        <div class="nav-right">
            <a class="nav-phone" href="tel:+919084748563"><x-site.icon name="phone" /> +91 9084748563</a>
            <a href="{{ url('web/demo') }}" class="btn btn-primary btn-sm">Request Demo</a>
            <button type="button" class="nav-burger" data-menu-open aria-label="Open menu"><x-site.icon name="menu" /></button>
        </div>
    </div>
</header>

<div class="mnav" id="mnav" aria-hidden="true">
    <div class="mnav-panel">
        <div class="mnav-top">
            <a class="brand" href="{{ url('/') }}">
                <img src="{{ $logo }}" alt="Logo">
                <span class="brand-word">SUPER<span>LMS</span></span>
            </a>
            <button type="button" class="nav-burger" data-menu-close aria-label="Close menu" style="display:grid"><x-site.icon name="x" /></button>
        </div>
        @foreach ($links as $l)
            <a href="{{ $l['url'] }}" class="mnav-link {{ $l['on'] ? 'is-active' : '' }}">{{ $l['label'] }} <x-site.icon name="chevron-right" /></a>
        @endforeach
        <div class="actions">
            <a href="{{ url('web/demo') }}" class="btn btn-primary">Request Demo</a>
            <a href="tel:+919084748563" class="btn btn-outline"><x-site.icon name="phone" /> +91 9084748563</a>
        </div>
    </div>
</div>
