@php
    // What the sidebar lists is worked out in one place, which the Quick Links
    // screen asks too: a sub-admin's granted screens only (never Users), no
    // screen of a module the school has not been given, and none that is
    // opened from the More screen instead (Exam Copy).
    $navItems = \App\Support\AdminMenu::sidebar(auth()->user());

    $authUser   = auth()->user();
    $school     = $authUser->organization;
    $orgParam   = ['organization' => $authUser->organization_id];
    $schoolName = trim((string) ($school->name ?? '')) ?: 'School';
    $schoolLogo = $school && $school->logo ? $school->logo : \App\Support\Platform::logoUrl();

    // The card at the foot: who is signed in, their role and the session.
    $adName    = trim((string) ($authUser->name ?? '')) ?: 'School Admin';
    $adRole    = $authUser->role === 'sub-admin' ? 'Sub Admin' : 'School Admin';
    $adSession = \App\Models\AcademicSession::currentLabel();
    $adSub     = strcasecmp($adName, $adRole) === 0 ? $adSession : $adRole . ' · ' . $adSession;
    $adProfile = \Illuminate\Support\Facades\Route::has('admin.profile') ? route('admin.profile', $orgParam) : null;
@endphp

{{-- The school panel's sidebar in the Super Admin's exact style (user's ask
     of 2026-11-19, admin-components.sa-side-styles): a light panel with a
     hairline edge, the school's logo and name at the top (a 60px row, its
     faint divider level with Quick Links' header line), one plain list with
     plain icons — the open page's name and icon in teal on a soft pill — and
     who is signed in at the foot, with Profile and Logout. Always open on md+
     (no menu button): the layout sets `sidebar-expanded` before the first
     paint. --}}
@include('admin-components.sa-side-styles')
<style>
    /* The school's name can be long: up to two lines beside its logo, in a
       row exactly 60px tall (as the Super Admin's brand row with its line). */
    .sa-brand.is-school { height: 60px; padding: 0 16px; justify-content: flex-start; }
    .sa-brand.is-school .sa-brand-link { justify-content: flex-start; gap: 10px; width: 100%; }
    .sa-brand.is-school .sa-brand-link img { width: 32px; height: 32px; border-radius: 8px; }
    .sa-brand.is-school .sa-brand-link span { font-size: 13.5px; font-weight: 700; line-height: 1.22; letter-spacing: -.005em; white-space: normal;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .sa-av.is-school img { width: 100%; height: 100%; object-fit: cover; }
</style>

<!-- Off-canvas menu for mobile -->
<div x-show="offcanvas" x-cloak class="fixed inset-0 flex z-[60] md:hidden"
     role="dialog" aria-modal="true"
     x-on:click="if ($event.target.closest('a')) offcanvas = false">
    <!-- Backdrop -->
    <div x-show="offcanvas" x-on:click="offcanvas = false" class="fixed inset-0 bg-black bg-opacity-50"
        x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300"
        x-transition:leave-start="opacity-100" aria-hidden="true"></div>

    <!-- Sidebar content -->
    <div x-show="offcanvas" x-transition:enter="transition ease-in-out duration-300 transform"
        x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full" class="relative flex-1 flex flex-col max-w-xs w-full">

        <!-- Close button -->
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
            <div class="sa-brand is-school">
                <a href="{{ route('admin.quick-links', $orgParam) }}" class="sa-brand-link">
                    <img src="{{ $schoolLogo }}" alt="Logo">
                    <span>{{ $schoolName }}</span>
                </a>
            </div>

            <div class="flex-1 min-h-0 overflow-y-auto">
                <nav class="sa-nav">
                    @foreach ($navItems as $menu_item)
                        @php $is_active = Route::is($menu_item['prefix']); @endphp
                        <a href="{{ route($menu_item['link'], $orgParam) }}" class="sa-link {{ $is_active ? 'on' : '' }}" @if ($is_active) aria-current="page" @endif>
                            <span class="sa-ic"><x-icon name="{{ $menu_item['icon'] }}" /></span>
                            <span>{{ $menu_item['title'] }}</span>
                        </a>
                    @endforeach
                </nav>
            </div>

            <div class="sa-user">
                <a @if ($adProfile) href="{{ $adProfile }}" @endif class="sa-user-link" title="Profile">
                    <span class="sa-av is-school"><img src="{{ $schoolLogo }}" alt=""></span>
                    <span class="sa-user-text"><span class="sa-user-name">{{ $adName }}</span><small>{{ $adSub }}</small></span>
                </a>
                <button type="button" class="sa-logout" title="Logout" aria-label="Logout"
                    x-on:click="offcanvas = false; Livewire.dispatch('open-logout-confirm')">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                </button>
            </div>
        </div>
    </div>

    <div class="flex-shrink-0 w-14">
        <!-- Dummy element to force sidebar to shrink -->
    </div>
</div>

<!-- Static sidebar for desktop: always open, as the Super Admin's -->
<div class="lms-rail lms-admin-rail sa-rail hidden md:flex md:flex-col md:fixed md:inset-y-0 md:left-0">
    <div class="sa-side flex-1 flex flex-col min-h-0">
        <!-- The school's logo and name, the divider under them -->
        <div class="sa-brand is-school">
            <a href="{{ route('admin.quick-links', $orgParam) }}" class="sa-brand-link" title="Quick Links — {{ $schoolName }}">
                <img src="{{ $schoolLogo }}" alt="Logo">
                <span class="lms-logo-name">{{ $schoolName }}</span>
            </a>
        </div>

        <!-- Scrollable navigation area -->
        <div id="sidebar-nav" class="relative flex-1 min-h-0 overflow-y-auto">
            <nav class="sa-nav">
                @foreach ($navItems as $menu_item)
                    @php $is_active = Route::is($menu_item['prefix']); @endphp
                    <a href="{{ route($menu_item['link'], $orgParam) }}" title="{{ $menu_item['title'] }}"
                        class="sa-link {{ $is_active ? 'on' : '' }}" @if ($is_active) aria-current="page" @endif>
                        <span class="sa-ic"><x-icon name="{{ $menu_item['icon'] }}" /></span>
                        <span class="lms-label">{{ $menu_item['title'] }}</span>
                    </a>
                @endforeach
            </nav>
        </div>

        <!-- Who is signed in -->
        <div class="sa-user">
            <a @if ($adProfile) href="{{ $adProfile }}" @endif class="sa-user-link" title="Profile — {{ $adName }}">
                <span class="sa-av is-school"><img src="{{ $schoolLogo }}" alt=""></span>
                <span class="sa-user-text"><span class="sa-user-name">{{ $adName }}</span><small>{{ $adSub }}</small></span>
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
        container.scrollTop = activeTab.offsetTop - (container.clientHeight / 2) + (activeTab.clientHeight / 2);
    });
</script>
