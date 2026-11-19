@php
    $authUser  = auth()->user();
    $allItems  = config('menu.' . App\Helpers\Constants::ROLEVALUE[$authUser->role]);

    // A screen marked 'sidebar' => false (Credit) is left off the menu.
    $allItems  = array_values(array_filter($allItems, fn($i) => ($i['sidebar'] ?? true) !== false));

    // Sub-super-admins see only the functionalities granted to them, and never
    // the Users management screen (reserved for the main super-admin).
    if ($authUser->role === 'sub-super-admin') {
        $granted  = (array) $authUser->permissions;
        $navItems = collect($allItems)
            ->reject(fn($i) => ($i['link'] ?? '') === 'super-admin.users')
            ->filter(fn($i) => in_array($i['link'] ?? '', $granted, true))
            ->values()
            ->all();
    } else {
        $navItems = $allItems;
    }

    // The card at the foot: who is signed in, and the current session.
    $saName    = trim((string) ($authUser->name ?? '')) ?: 'Super Admin';
    $saLogo    = \App\Support\Platform::logoUrl();   // the platform's logo on the card, not an initial
    $saRole    = $authUser->role === 'sub-super-admin' ? 'Sub Super Admin' : 'Super Admin';
    $saSession = \App\Models\AcademicSession::currentLabel();
    // Under the name: the role and the session ("Super Admin" once only).
    $saSub     = strcasecmp($saName, $saRole) === 0 ? $saSession : $saRole . ' · ' . $saSession;
@endphp

{{-- The Super Admin's sidebar in the look of the website's product screen
     (superlms.in → Features, 2026-11-18): a light panel with a hairline edge,
     the logo and SUPERLMS centred on one line (the divider under it kept but
     invisible), one plain list of rows (no group labels) with plain icons —
     the open page's name and icon both teal — and who is signed in at the
     foot, beside the platform's logo.
     It is always open on md+ (no menu button, no icon rail): the layout
     sets `sidebar-expanded` for the super admin before the first paint.
     Scrollbars in the school panel's style (this partial is only included for
     the super admin and sub-super-admins, so the other panels keep theirs):
     - the menu's scrollbar is a hairline, as the school sidebar's;
     - every box that scrolls inside a page (slide-in panel bodies, lists,
       wide tables) gets the page's own slim 8px bar, darker on hover,
       instead of the browser's wide one.
     The menu is a little narrower than the layout's 253px (226px), giving
     the page more width. --}}
@include('admin-components.sa-side-styles')

<!-- Off-canvas menu for mobile, show/hide based on off-canvas menu state. -->
<div x-show="offcanvas" x-cloak class="fixed inset-0 flex z-[60] md:hidden"
     role="dialog" aria-modal="true"
     x-on:click="if ($event.target.closest('a')) offcanvas = false">
    <div x-show="offcanvas" x-on:click="offcanvas = false" class="fixed inset-0 bg-black bg-opacity-50"
        x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300"
        x-transition:leave-start="opacity-100" aria-hidden="true"></div>

    <div x-show="offcanvas" x-transition:enter="transition ease-in-out duration-300 transform"
        x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full" class="relative flex-1 flex flex-col max-w-xs w-full">

        <div x-show="offcanvas" x-transition:enter="ease-in-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in-out duration-300"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="absolute top-0 right-0 -mr-12 pt-2">
            <button x-on:click="offcanvas = false" type="button"
                class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                <span class="sr-only">Close sidebar</span>
                <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="sa-side flex-1 h-0 flex flex-col">
            <div class="sa-brand">
                <a href="{{ route('super-admin.quick-links') }}" class="sa-brand-link">
                    <img src="{{ \App\Support\Platform::logoUrl() }}" alt="Logo">
                    <span>SUPERLMS</span>
                </a>
            </div>

            <div class="flex-1 min-h-0 overflow-y-auto">
                <nav class="sa-nav">
                    @foreach ($navItems as $menu_item)
                        @php $is_active = Route::is($menu_item['prefix']); @endphp
                        <a href="{{ route($menu_item['link']) }}" class="sa-link {{ $is_active ? 'on' : '' }}" @if ($is_active) aria-current="page" @endif>
                            <span class="sa-ic"><x-icon name="{{ $menu_item['icon'] }}" /></span>
                            <span>{{ $menu_item['title'] }}</span>
                        </a>
                    @endforeach
                </nav>
            </div>

            <div class="sa-user">
                <a href="{{ route('super-admin.profile') }}" class="sa-user-link" title="Profile">
                    <span class="sa-av"><img src="{{ $saLogo }}" alt=""></span>
                    <span class="sa-user-text"><span class="sa-user-name">{{ $saName }}</span><small>{{ $saSub }}</small></span>
                </a>
                <button type="button" class="sa-logout" title="Logout" aria-label="Logout"
                    x-on:click="offcanvas = false; Livewire.dispatch('open-logout-confirm')">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                </button>
            </div>
        </div>
    </div>

    <div class="flex-shrink-0 w-14">
        <!-- Force sidebar to shrink to fit close icon -->
    </div>
</div>

<!-- Static sidebar for desktop (collapsible icon rail ⇆ full) -->
<div class="lms-rail lms-sa-rail sa-rail hidden md:flex md:flex-col md:fixed md:inset-y-0 md:left-0">
    <div class="sa-side flex-1 flex flex-col min-h-0">
        <!-- Logo · SUPERLMS, the divider under it -->
        <div class="sa-brand">
            <a href="{{ route('super-admin.quick-links') }}" class="sa-brand-link" title="Quick Links">
                <img src="{{ \App\Support\Platform::logoUrl() }}" alt="Logo">
                <span>SUPERLMS</span>
            </a>
        </div>

        <!-- Scrollable navigation area -->
        <div id="sidebar-nav" class="relative flex-1 min-h-0 overflow-y-auto">
            <nav class="sa-nav">
                @foreach ($navItems as $menu_item)
                    @php $is_active = Route::is($menu_item['prefix']); @endphp
                    <a href="{{ route($menu_item['link']) }}" title="{{ $menu_item['title'] }}"
                        class="sa-link {{ $is_active ? 'on' : '' }}" @if ($is_active) aria-current="page" @endif>
                        <span class="sa-ic"><x-icon name="{{ $menu_item['icon'] }}" /></span>
                        <span class="lms-label">{{ $menu_item['title'] }}</span>
                    </a>
                @endforeach
            </nav>
        </div>

        <!-- Who is signed in -->
        <div class="sa-user">
            <a href="{{ route('super-admin.profile') }}" class="sa-user-link" title="Profile — {{ $saName }}">
                <span class="sa-av"><img src="{{ $saLogo }}" alt=""></span>
                <span class="sa-user-text"><span class="sa-user-name">{{ $saName }}</span><small>{{ $saSub }}</small></span>
            </a>
            <button type="button" class="sa-logout" title="Logout" aria-label="Logout"
                x-on:click="Livewire.dispatch('open-logout-confirm')">
                <svg fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
            </button>
        </div>
    </div>
</div>

<!-- JavaScript to scroll active tab into view -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('sidebar-nav');
        if (!container) return;

        const activeTab = container.querySelector('.sa-link.on');
        if (!activeTab) return;

        // Center the active item within the sidebar scroll container
        const containerH = container.clientHeight;
        const itemTop    = activeTab.offsetTop;
        const itemH      = activeTab.clientHeight;

        container.scrollTop = itemTop - (containerH / 2) + (itemH / 2);
    });
</script>
