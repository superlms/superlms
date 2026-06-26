<div>
    <nav class="bg-white border-b border-gray-200 px-2 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 gap-2 sm:gap-4">

            {{-- ── LEFT ── --}}
            <div class="flex items-center gap-3 flex-shrink-0">
                {{-- Hamburger (mobile only) --}}
                <div class="md:hidden">
                    <button x-on:click="offcanvas = true" type="button"
                        class="flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200
                               text-gray-400 hover:text-gray-600 hover:bg-gray-50 transition-colors">
                        <span class="sr-only">Open sidebar</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
                <div class="md:hidden w-px h-5 bg-gray-200"></div>

                {{-- Back button --}}
                <button onclick="history.back()"
                    class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-gray-200
                           text-gray-500 hover:text-gray-800 hover:bg-gray-50 text-sm transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span class="hidden sm:inline">Back</span>
                </button>

                <div class="hidden lg:flex items-center gap-2">
                    <h2 class="text-[15px] font-medium text-gray-900 leading-none">
                        @if (auth()->user()->role === 'accounts')
                            Welcome! {{ auth()->user()->organization->name }} Accounts
                        @elseif (auth()->user()->role === 'admin')
                            Welcome! {{ auth()->user()->organization->name }}
                        @else
                            Welcome! Edyone LMS Admin
                        @endif
                    </h2>
                    <span
                        class="text-xs font-medium px-2.5 py-1 rounded-full bg-blue-50
                                     text-blue-600 border border-blue-100 whitespace-nowrap">
                        @php $year = now()->year; @endphp
                        {{ $year }}–{{ ($year + 1) % 100 }}
                    </span>
                </div>
            </div>

            {{-- ── CENTER — Search (hidden on the smallest phones; reach pages via the
                 hamburger menu instead so the navbar stays uncluttered) ── --}}
            <div class="hidden sm:block flex-1 max-w-md mx-auto" x-data="{ open: false }" x-on:click.away="open = false"
                x-on:keydown.escape.window="open = false">

                <div class="relative">
                    {{-- Input --}}
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" />
                        </svg>
                        <input wire:model.live.debounce.250ms="search" x-on:focus="open = true" type="text"
                            placeholder="Search pages..." autocomplete="off"
                            class="w-full pl-9 pr-8 py-2 text-sm bg-gray-50 border border-gray-200
                                   rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20
                                   focus:border-blue-400 focus:bg-white transition-all placeholder-gray-400" />

                        @if ($search)
                            <button wire:click="$set('search', '')" x-on:click="open = true"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400
                                       hover:text-gray-600 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        @endif
                    </div>

                    {{-- Dropdown --}}
                    <div x-show="open" x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                        x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                        class="absolute top-full mt-2 left-0 right-0 bg-white rounded-xl border border-gray-200
                               shadow-lg z-[999] overflow-hidden"
                        style="display: none;">

                        {{-- ── Search Results ── --}}
                        @if ($search && count($searchResults) > 0)
                            <div class="py-1.5">
                                <p
                                    class="px-3 pt-2 pb-1 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">
                                    Results
                                </p>
                                @foreach ($searchResults as $item)
                                    <button
                                        wire:click="navigateTo('{{ $item['link'] }}', '{{ addslashes($item['title']) }}', '{{ $item['icon'] }}', '{{ $item['bgClass'] }}', '{{ $item['textClass'] }}')"
                                        x-on:click="open = false"
                                        class="w-full flex items-center gap-3 px-3 py-2.5 hover:bg-gray-50
                                               text-left transition-colors group">
                                        <div
                                            class="w-8 h-8 rounded-lg {{ $item['bgClass'] }} flex items-center
                                                    justify-center flex-shrink-0">
                                            <x-icon :name="$item['icon']" class="w-4 h-4 {{ $item['textClass'] }}" />
                                        </div>
                                        <span
                                            class="text-sm text-gray-700 group-hover:text-gray-900 font-medium flex-1">
                                            {{ $item['title'] }}
                                        </span>
                                        <svg class="w-3.5 h-3.5 text-gray-300 group-hover:text-gray-500" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                @endforeach
                            </div>

                            {{-- ── No Results ── --}}
                        @elseif ($search && count($searchResults) === 0)
                            <div class="px-4 py-8 text-center">
                                <svg class="w-8 h-8 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" />
                                </svg>
                                <p class="text-sm text-gray-400">
                                    No pages found for
                                    <strong class="text-gray-600">"{{ $search }}"</strong>
                                </p>
                            </div>

                            {{-- ── Recent Searches ── --}}
                        @elseif (!$search && count($recentSearches) > 0)
                            <div class="py-1.5">
                                <div class="flex items-center justify-between px-3 pt-2 pb-1">
                                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">
                                        Recent
                                    </p>
                                    <button wire:click="clearRecentSearches"
                                        class="text-[11px] text-gray-400 hover:text-red-500 transition-colors">
                                        Clear all
                                    </button>
                                </div>
                                @foreach ($recentSearches as $recent)
                                    <button
                                        wire:click="navigateTo('{{ $recent['route'] }}', '{{ addslashes($recent['title']) }}', '{{ $recent['icon'] }}', '{{ $recent['bgClass'] }}', '{{ $recent['textClass'] }}')"
                                        x-on:click="open = false"
                                        class="w-full flex items-center gap-3 px-3 py-2.5 hover:bg-gray-50
                                               text-left transition-colors group">
                                        <div
                                            class="w-8 h-8 rounded-lg {{ $recent['bgClass'] }} flex items-center
                                                    justify-center flex-shrink-0">
                                            <x-icon :name="$recent['icon']" class="w-4 h-4 {{ $recent['textClass'] }}" />
                                        </div>
                                        <span class="text-sm text-gray-600 group-hover:text-gray-900 flex-1">
                                            {{ $recent['title'] }}
                                        </span>
                                        <svg class="w-3.5 h-3.5 text-gray-300 group-hover:text-gray-500"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                @endforeach
                            </div>

                            {{-- ── Empty (no search, no recents) ── --}}
                        @else
                            <div class="px-4 py-6 text-center">
                                <svg class="w-7 h-7 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" />
                                </svg>
                                <p class="text-sm text-gray-400">Type to search pages...</p>
                            </div>
                        @endif

                    </div>
                </div>
            </div>

            {{-- ── RIGHT — Actions ── --}}
            <div class="flex items-center gap-1.5 flex-shrink-0">
                <div class="relative inline-flex" wire:poll.60s>
                    <x-button rounded class="h-9 w-9 bg-white" icon="bell-alert" outline
                        wire:click="$toggle('showNotifications')" />
                    @if (($unreadNotifications ?? 0) > 0)
                        <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center pointer-events-none">{{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}</span>
                    @endif
                </div>
                @if (in_array(auth()->user()->role, ['accounts', 'admin', 'sub-admin']))
                    <div class="relative inline-flex"
                        x-data="{ count: {{ (int) ($unreadMessages ?? 0) }} }"
                        x-on:chat-sync.window="count = $event.detail.unread">
                        <x-button rounded class="h-9 w-9 bg-white" icon="chat-bubble-oval-left-ellipsis" outline
                            wire:click="messagesPage" title="Messages" />
                        <span x-show="count > 0" x-cloak x-text="count > 99 ? '99+' : count"
                            class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center pointer-events-none"></span>
                    </div>
                @endif

                @if (auth()->user()->role !== 'super-admin')
                    <x-button rounded class="h-9 w-9 bg-white" icon="user" outline wire:click="profilePage" />
                @endif

                <x-button rounded class="h-9 w-9 bg-white" icon="arrow-right-on-rectangle" outline
                    wire:click="confirmLogout" />
            </div>

        </div>
    </nav>

    {{-- ── Notification Panel ── --}}
    @if ($showNotifications)
        <div class="fixed inset-0 z-[100]" x-data="{ show: true }" x-show="show"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div class="absolute inset-0 bg-black/20 backdrop-blur-sm" wire:click="$set('showNotifications', false)">
            </div>

            <div class="absolute right-0 top-0 h-full w-96 bg-white shadow-xl z-10"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">

                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-800">Notifications</h3>
                    <button wire:click="$set('showNotifications', false)"
                        class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="h-full overflow-y-auto pb-16">
                    <livewire:components.notification />
                </div>
            </div>
        </div>
    @endif

    {{-- ── Super Admin Logout Modal ── --}}
    @if ($showSuperAdminLogoutModal)
        <div class="fixed inset-0 flex items-center justify-center bg-black/30 backdrop-blur-sm z-[9999] px-4">
            <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-9 h-9 bg-red-50 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Admin Logout</h3>
                        <p class="text-xs text-gray-400">Admin Panel</p>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mb-5">Are you sure you want to logout from the Admin Panel?</p>
                <div class="flex items-center gap-2">
                    <button wire:click="superAdminLogout"
                        class="flex-1 py-2 bg-red-500 hover:bg-red-600 text-white text-sm
                               font-medium rounded-lg transition-colors">
                        Yes, Logout
                    </button>
                    <button wire:click="$set('showSuperAdminLogoutModal', false)"
                        class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm
                               font-medium rounded-lg transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Admin Logout Modal ── --}}
    @if ($showAdminLogoutModal)
        <div class="fixed inset-0 flex items-center justify-center bg-black/30 backdrop-blur-sm z-[9999] px-4">
            <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-9 h-9 bg-red-50 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">School Logout</h3>
                        <p class="text-xs text-gray-400">School Panel</p>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mb-5">Are you sure you want to logout from the School Panel?</p>
                <div class="flex items-center gap-2">
                    <button wire:click="adminLogout"
                        class="flex-1 py-2 bg-red-500 hover:bg-red-600 text-white text-sm
                               font-medium rounded-lg transition-colors">
                        Yes, Logout
                    </button>
                    <button wire:click="$set('showAdminLogoutModal', false)"
                        class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm
                               font-medium rounded-lg transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Accounts Logout Modal ── --}}
    @if ($showAccountsLogoutModal)
        <div class="fixed inset-0 flex items-center justify-center bg-black/30 backdrop-blur-sm z-[9999] px-4">
            <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-9 h-9 bg-red-50 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Accounts Logout</h3>
                        <p class="text-xs text-gray-400">Accounts Panel</p>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mb-5">Are you sure you want to logout from the Accounts Panel?</p>
                <div class="flex items-center gap-2">
                    <button wire:click="accountsLogout"
                        class="flex-1 py-2 bg-red-500 hover:bg-red-600 text-white text-sm
                               font-medium rounded-lg transition-colors">
                        Yes, Logout
                    </button>
                    <button wire:click="$set('showAccountsLogoutModal', false)"
                        class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm
                               font-medium rounded-lg transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
