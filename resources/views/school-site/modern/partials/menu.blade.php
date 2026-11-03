{{--
    The site's menu for the modern templates: the groups of $nav (each a
    dropdown, or a link when its group is a single page, as on Classic), a
    row on a wide screen and a drawer on a phone, a tablet or any screen
    the row does not fit on (html.nav-drawer, see the scripts partial).
--}}
@php
    use App\Models\SchoolWebsite;

    $link   = fn ($slug) => $slug === 'home' ? url('/') : url($slug);
    $single = array_keys(array_filter(SchoolWebsite::classicNavGroups(), fn ($s) => count($s) === 1));
    $tel    = \App\Support\ModernSite::tel($c);
@endphp
<nav class="menu" id="menu" aria-label="Main">
    <div class="wrap menu-bar">
        <a href="{{ url('/') }}" class="menu-brand" aria-label="{{ $c['school_name'] }} — Home">
            <span class="mb-logo">@include('school-site.classic.partials.logo', ['sealId' => 'bar'])</span>
            <span>{{ $c['school_name'] }}</span>
        </a>
        <button class="menu-toggle" type="button" id="menuToggle" aria-expanded="false" aria-controls="menuPanel">
            Menu <span class="bars" aria-hidden="true"><i></i><i></i><i></i></span>
        </button>
        <div class="menu-layer">
        <div class="menu-shade" data-menu-close></div>
        <div class="menu-panel" id="menuPanel">
            <div class="menu-panel-head">
                <span class="mb-logo">@include('school-site.classic.partials.logo', ['sealId' => 'drawer'])</span>
                <b>{{ $c['school_name'] }}</b>
                <button type="button" class="menu-close" data-menu-close aria-label="Close menu">&times;</button>
            </div>
            <ul class="menu-list" id="menuList">
                @foreach ($nav as $group => $items)
                    @php $isActive = array_key_exists($current, $items); @endphp
                    @if (count($items) === 1 && in_array($group, $single, true))
                        @php $slug = array_key_first($items); @endphp
                        <li class="{{ $isActive ? 'is-active' : '' }}"><a href="{{ $link($slug) }}">{{ $group }}</a></li>
                    @else
                        <li class="has-drop {{ $isActive ? 'is-active' : '' }}">
                            <button type="button" aria-haspopup="true">
                                {{ $group }}
                                <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            <ul class="drop">
                                @foreach ($items as $slug => $label)
                                    <li><a href="{{ $link($slug) }}" class="{{ $current === $slug ? 'active' : '' }}"><i class="dot" aria-hidden="true"></i>{{ $label }}</a></li>
                                @endforeach
                            </ul>
                        </li>
                    @endif
                @endforeach
            </ul>
            <div class="menu-panel-foot">
                @if ($tel !== '')<a class="btn" href="tel:{{ $tel }}">Call Us</a>@endif
                <a class="btn btn-ghost" href="{{ $site->adminLoginUrl() }}" target="_blank" rel="noopener">Login</a>
            </div>
        </div>
        </div>
    </div>
</nav>
<script>
// A drawer on a phone or tablet, and on any screen the menu's groups do not
// fit on one row of — decided as soon as the menu is drawn.
(function () {
    var html = document.documentElement, list = document.getElementById('menuList');
    window.__fitMenu = function () {
        var drawer = window.innerWidth <= 1020;
        if (!drawer && list && list.children.length > 1) {
            html.classList.remove('nav-drawer');
            var k = list.children;
            drawer = k[k.length - 1].offsetTop > k[0].offsetTop + 4;
        }
        if (html.classList.contains('nav-drawer') !== drawer) html.classList.toggle('nav-drawer', drawer);
        return drawer;
    };
    window.__fitMenu();
})();
</script>
