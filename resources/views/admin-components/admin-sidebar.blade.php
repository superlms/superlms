@php
    $authUser = auth()->user();
    $allItems = config('menu.' . App\Helpers\Constants::ROLEVALUE[$authUser->role]);

    // Sub-admins see only the functionalities granted to them, and never the
    // Users management screen (reserved for the full school admin).
    if ($authUser->role === 'sub-admin') {
        $granted  = (array) $authUser->permissions;
        $navItems = collect($allItems)
            ->reject(fn($i) => ($i['link'] ?? '') === 'admin.users')
            ->filter(fn($i) => in_array($i['link'] ?? '', $granted, true))
            ->values()
            ->all();
    } else {
        $navItems = $allItems;
    }

    // Hide modules this school has not been granted (core items always stay).
    $navItems = \App\Support\ModuleAccess::filterMenu($navItems, $authUser->organization);
@endphp

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
        x-transition:leave-end="-translate-x-full" class="relative flex-1 flex flex-col max-w-xs w-full bg-white">

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

        <!-- Scrollable area -->
        <div class="flex-1 h-0 overflow-y-auto bg-white border-r border-gray-200">
            <!-- Logo Section -->
            <div class="flex-shrink-0 flex items-center px-4 py-4 border-b border-gray-200">
                <div class="flex justify-between gap-2 ">
                    <div class="flex-shrink-0 flex">
                        <img src="{{ auth()->user()->organization && auth()->user()->organization->logo ? auth()->user()->organization->logo : asset('website-image/Group 11525.png') }}"
                            alt="Logo" class="w-20 h-20 object-contain mb-2">
                        <h2 class="text-sm font-bold text-gray-900 text-center">
                            {{ auth()->user()->organization->name }}
                        </h2>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="px-4 py-4">
                <div class="mb-6">
                    <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-3">Dashboard</h3>
                    <div class="space-y-1">
                        @auth
                            @php
                                $currentOrganization = auth()->user()->organization_id;
                            @endphp
                            @foreach ($navItems as $menu_item)
                                @php
                                    $is_active = Route::is($menu_item['prefix']);
                                    $routeParams = ['organization' => $currentOrganization];
                                @endphp
                                <a href="{{ route($menu_item['link'], $routeParams) }}"
                                    class="{{ $is_active ? 'bg-purple-50 text-purple-700 border-r-2 border-purple-500' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }} group flex items-center px-2 py-2 text-sm font-medium rounded-lg transition-colors duration-150">
                                    <x-icon
                                        class="{{ $is_active ? 'text-purple-500' : 'text-gray-400 group-hover:text-gray-600' }} mr-2 flex-shrink-0 h-4 w-4"
                                        name="{{ $menu_item['icon'] }}" />
                                    {{ $menu_item['title'] }}
                                </a>
                            @endforeach
                        @endauth
                    </div>
                </div>
            </nav>
        </div>
    </div>

    <div class="flex-shrink-0 w-14">
        <!-- Dummy element to force sidebar to shrink -->
    </div>
</div>

<!-- Static sidebar for desktop (collapsible icon rail ⇆ full) -->
<div class="lms-rail hidden md:flex md:flex-col md:fixed md:inset-y-0 md:left-0">
    <div class="flex-1 flex flex-col min-h-0 bg-white border-r border-gray-200">
        <!-- Three-dot toggle -->
        <div class="lms-topbar flex items-center justify-between px-3 pt-3">
            <button @click="sidebarOpen = !sidebarOpen" type="button"
                :title="sidebarOpen ? 'Collapse menu' : 'Expand menu'"
                class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>
        <!-- Logo section -->
        <div class="lms-logo-wrap flex-shrink-0 flex flex-col items-center px-4 pb-4 pt-1 border-b border-gray-200">
            <img src="{{ auth()->user()->organization && auth()->user()->organization->logo ? auth()->user()->organization->logo : asset('website-image/Group 11525.png') }}"
                alt="Logo" class="lms-logo-img w-14 h-14 object-contain">
            <h2 class="lms-logo-name text-sm font-bold text-gray-900 text-center mt-1">
                {{ auth()->user()->organization->name }}
            </h2>
        </div>

        <!-- Scrollable navigation area -->
        <div id="sidebar-nav" class="flex-1 flex flex-col overflow-y-auto">
            <nav class="lms-nav flex-1 px-4 py-4 space-y-6">
                <!-- Dashboard Section -->
                <div>
                    <h3 class="lms-section-title text-xs font-medium text-gray-500 uppercase tracking-wider mb-3">Dashboard</h3>
                    <div class="space-y-1">
                        @auth
                            @php
                                $currentOrganization = auth()->user()->organization_id;
                            @endphp
                            @foreach ($navItems as $menu_item)
                                @php
                                    $is_active = Route::is($menu_item['prefix']);
                                    $routeParams = ['organization' => $currentOrganization];
                                @endphp
                                <a href="{{ route($menu_item['link'], $routeParams) }}"
                                    title="{{ $menu_item['title'] }}"
                                    @click="sidebarOpen = false; try { localStorage.setItem('lmsSidebar', 'closed') } catch (e) {}"
                                    class="{{ $is_active ? 'bg-purple-50 text-purple-700 border-r-2 border-purple-500' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }} lms-nav-link group flex items-center px-2 py-2 text-sm font-medium rounded-lg transition-colors duration-150">
                                    <x-icon
                                        class="{{ $is_active ? 'text-purple-500' : 'text-gray-400 group-hover:text-gray-600' }} lms-ico mr-2 flex-shrink-0 h-4 w-4"
                                        name="{{ $menu_item['icon'] }}" />
                                    <span class="lms-label">{{ $menu_item['title'] }}</span>
                                </a>
                            @endforeach
                        @endauth
                    </div>
                </div>
            </nav>
        </div>
    </div>
</div>

<!-- JavaScript to scroll active tab into view -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarNav = document.getElementById('sidebar-nav');
        const activeTab = sidebarNav.querySelector('.bg-purple-50');

        if (activeTab) {
            activeTab.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
            });
        }
    });
</script>
