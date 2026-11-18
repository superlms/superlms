<div>
    {{-- The super admin's top bar has a few touches of its own: a rounder Back
         and a pill search — the school admin's and sub-admin's bar has those
         too ($saLook) — and the session switcher and Profile (super admin
         only, $saBar). The accounts panel keeps its own. The buttons on the
         right are everyone's blue ones. --}}
    @php
        $saBar  = in_array(auth()->user()->role, ['super-admin', 'sub-super-admin']);
        $saLook = $saBar || in_array(auth()->user()->role, ['admin', 'sub-admin']);
    @endphp
    @if ($saLook)
        <style>
            /* Search: a white pill with the login fields' border, blue once typed in. */
            .sa-search {
                background: #fff; color: #111827; border: 1.5px solid #d1d5db; border-radius: 9999px;
                outline: none; box-shadow: none; transition: border-color .15s;
            }
            .sa-search:hover { border-color: #9ca3af; }
            .sa-search:focus { outline: none; box-shadow: none !important; border-color: #9ca3af; }
            .sa-search:not(:placeholder-shown) { border-color: #2563eb; }
            .sa-search-wrap:has(.sa-search:not(:placeholder-shown)) > svg { color: #2563eb; }

        </style>
    @endif
    {{-- lms-navbar-row: the bar itself — on md+ shown only on Quick Links for
         the admin and super admin panels (components.layouts.app); the
         popups below it stay available everywhere. --}}
    <nav class="lms-navbar-row bg-white border-b border-gray-200 px-2 sm:px-6 lg:px-8">
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
                    class="flex items-center gap-1.5 {{ $saLook ? 'px-3 py-1.5 rounded-xl' : 'px-2.5 py-1.5 rounded-lg' }} border border-gray-200
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
                        @elseif (in_array(auth()->user()->role, ['admin', 'sub-admin']))
                            Welcome! {{ auth()->user()->organization->name }}
                        @else
                            Welcome! SuperLMS Admin
                        @endif
                    </h2>
                    {{-- The current academic session (Super Admin → Session). A
                         super admin who may open that page can pick another one here
                         when there is more than one session — Session then asks for
                         its two codes — else open the page. --}}
                    @php
                        $sessionLabel = \App\Models\AcademicSession::currentLabel();
                        $sessionLink  = $saBar && auth()->user()->canAccessSuperAdminRoute('super-admin.sessions');
                        $sessionList  = $sessionLink ? \App\Models\AcademicSession::switchable() : collect();
                    @endphp
                    @if ($sessionLink && $sessionList->count() > 1)
                        <div class="relative" x-data="{ open: false }" x-on:click.away="open = false"
                            x-on:keydown.escape.window="open = false">
                            <button type="button" x-on:click="open = !open" :aria-expanded="open" aria-haspopup="listbox"
                                title="Current session — switch it here"
                                class="inline-flex items-center gap-1 text-xs font-medium pl-2.5 pr-2 py-1 rounded-full bg-blue-50 text-blue-600
                                       border border-blue-100 whitespace-nowrap hover:bg-blue-100 hover:border-blue-200 transition-colors">
                                {{ $sessionLabel }}
                                <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="open && 'rotate-180'"
                                    fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div x-show="open" style="display: none"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                class="absolute left-0 top-full mt-2 w-64 bg-white rounded-xl border border-gray-200 shadow-lg z-[999] overflow-hidden">
                                <p class="px-3 pt-2.5 pb-1.5 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Current session</p>
                                <div class="max-h-64 overflow-y-auto pb-1" role="listbox">
                                    {{-- Another session becomes current only behind its two codes
                                         (an email one and a WhatsApp one): picking it opens them on Session. --}}
                                    @foreach ($sessionList as $s)
                                        @if ($s->is_current)
                                            <div role="option" aria-selected="true"
                                                class="w-full flex items-center gap-3 px-3 py-2 text-left bg-blue-50/60">
                                                <span class="flex-1 min-w-0">
                                                    <span class="block text-sm font-semibold text-blue-700">{{ $s->name }}</span>
                                                    <span class="block text-xs text-gray-400">{{ $s->durationLabel() }}</span>
                                                </span>
                                                <svg class="w-4 h-4 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            </div>
                                        @else
                                            <a role="option" href="{{ route('super-admin.sessions', ['switch' => $s->id]) }}" x-on:click="open = false"
                                                title="Make {{ $s->name }} current — needs a code from the Super Admin's email and one from WhatsApp"
                                                class="w-full flex items-center gap-3 px-3 py-2 text-left transition-colors hover:bg-gray-50">
                                                <span class="flex-1 min-w-0">
                                                    <span class="block text-sm font-medium text-gray-800">{{ $s->name }}</span>
                                                    <span class="block text-xs text-gray-400">{{ $s->durationLabel() }}</span>
                                                </span>
                                                <svg class="w-3.5 h-3.5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                                <a href="{{ route('super-admin.sessions') }}"
                                    class="flex items-center justify-between px-3 py-2.5 border-t border-gray-100 text-xs font-medium text-gray-600 hover:text-blue-700 hover:bg-gray-50 transition-colors">
                                    Manage sessions
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </div>
                        </div>
                    @elseif ($sessionLink)
                        <a href="{{ route('super-admin.sessions') }}" title="Current session — change it on Session"
                            class="text-xs font-medium px-2.5 py-1 rounded-full bg-blue-50 text-blue-600 border border-blue-100
                                   whitespace-nowrap hover:bg-blue-100 hover:border-blue-200 transition-colors">{{ $sessionLabel }}</a>
                    @else
                        <span title="Current session"
                            class="text-xs font-medium px-2.5 py-1 rounded-full bg-blue-50
                                         text-blue-600 border border-blue-100 whitespace-nowrap">{{ $sessionLabel }}</span>
                    @endif
                </div>
            </div>

            {{-- ── CENTER — Search (hidden on the smallest phones; reach pages via the
                 hamburger menu instead so the navbar stays uncluttered) ── --}}
            <div class="hidden sm:block flex-1 max-w-md mx-auto" x-data="{ open: false }" x-on:click.away="open = false"
                x-on:keydown.escape.window="open = false">

                <div class="relative">
                    {{-- Input --}}
                    {{-- Super admin: a white pill with the login fields' border —
                         grey while empty, blue as soon as something is typed. --}}
                    <div class="relative {{ $saLook ? 'sa-search-wrap' : '' }}">
                        <svg class="absolute {{ $saLook ? 'left-4' : 'left-3' }} top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" />
                        </svg>
                        @if ($saLook)
                            <input wire:model.live.debounce.250ms="search" x-on:focus="open = true" type="text"
                                placeholder="Search pages..." autocomplete="off"
                                class="sa-search w-full pl-10 pr-9 py-2 text-sm placeholder-gray-400" />
                        @else
                            <input wire:model.live.debounce.250ms="search" x-on:focus="open = true" type="text"
                                placeholder="Search pages..." autocomplete="off"
                                class="w-full pl-9 pr-8 py-2 text-sm bg-gray-50 border border-gray-200
                                       rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20
                                       focus:border-blue-400 focus:bg-white transition-all placeholder-gray-400" />
                        @endif

                        @if ($search)
                            <button wire:click="$set('search', '')" x-on:click="open = true"
                                class="absolute {{ $saLook ? 'right-3.5' : 'right-2.5' }} top-1/2 -translate-y-1/2 text-gray-400
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

            {{-- ── RIGHT — Actions — the blue outlined round buttons, the
                 super admin's as everyone's (2026-11-03: back to these). ── --}}
            <div class="flex items-center gap-1.5 flex-shrink-0">
                {{-- LMS assistant. The panel itself lives in the global
                     components.gemini-assistant component at the end of the
                     layout; this only flips it open, via a browser event, so
                     opening it costs no server round-trip. --}}
                @if ($assistantEnabled)
                    <button type="button" x-on:click="$dispatch('gemini-toggle')"
                        title="Super Assist"
                        aria-label="Super Assist"
                        class="h-9 w-9 rounded-full bg-white border border-gray-300 hover:bg-gray-50 hover:border-gray-400 flex items-center justify-center transition-colors">
                        <img src="{{ \App\Support\Platform::logoUrl() }}" alt=""
                            width="20" height="20" class="w-5 h-5 object-contain">
                    </button>
                @endif

                <div class="relative inline-flex" wire:poll.60s>
                    <x-button rounded class="h-9 w-9 bg-white" icon="bell-alert" outline
                        wire:click="$toggle('showNotifications')" title="Notifications" />
                    @if (($unreadNotifications ?? 0) > 0)
                        <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center pointer-events-none">{{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}</span>
                    @endif
                </div>

                {{-- The super admin's Profile, just before Logout (2026-11-01): OTP
                     numbers, platform logo and details live there. Admin/sub-admin
                     reach Profile from More, accounts from the sidebar. --}}
                @if ($saBar)
                    <x-button rounded class="h-9 w-9 bg-white" icon="user" outline
                        href="{{ route('super-admin.profile') }}" title="Profile" aria-label="Profile" />
                @endif

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

                <x-button rounded class="h-9 w-9 bg-white" icon="arrow-right-on-rectangle" outline
                    wire:click="confirmLogout" title="Logout" />
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

    {{-- ── Super Admin Logout Modal ──
         In the superlms.in website's card look (its "pcards", user's ask of
         2026-11-18): a white card whose top is a pastel stage holding a small
         white panel — signing out, who is signed in, how the next sign-in
         goes — and under it the question in the display face, with the
         website's pill buttons. A click outside or Esc cancels. --}}
    @if ($showSuperAdminLogoutModal)
        @php
            $loUser = auth()->user();
            $loName = trim((string) ($loUser->name ?? '')) ?: 'Super Admin';
            $loRole = $loUser->role === 'sub-super-admin' ? 'Sub Super Admin' : 'Super Admin';
        @endphp
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600&family=Plus+Jakarta+Sans:wght@700;800&display=swap">
        <style>
            .lo-shade { background: rgba(12, 38, 32, .32); -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px); animation: lo-fade .2s ease both; }
            .lo-card { width: 100%; max-width: 380px; border-radius: 26px; background: #fff; overflow: hidden; font-family: 'Geist', 'Segoe UI', system-ui, sans-serif;
                box-shadow: 0 2px 4px rgba(12,38,32,.05), 0 44px 90px -30px rgba(12,38,32,.45); animation: lo-pop .35s cubic-bezier(.2,.7,.2,1) both; }
            .lo-stage { height: 196px; margin: 10px 10px 0; padding: 22px 22px 0; border-radius: 20px; overflow: hidden; display: flex; align-items: flex-start; justify-content: center;
                background: linear-gradient(160deg, #fde6cf 0%, #f7c9a0 100%); }
            .lo-panel { width: 100%; height: calc(100% + 30px); padding: 16px; border-radius: 16px 16px 0 0; background: #fff; box-shadow: 0 12px 30px -16px rgba(12,38,32,.35); }
            .lo-head { display: flex; align-items: center; gap: 10px; }
            .lo-ic { width: 32px; height: 32px; flex: none; border-radius: 10px; display: grid; place-items: center; background: #fff1f2; color: #f0506e; }
            .lo-ic svg { width: 17px; height: 17px; }
            .lo-head b, .lo-who b { display: block; font-size: 13.5px; font-weight: 600; line-height: 1.25; color: #0c2620; }
            .lo-head small, .lo-who small { display: block; font-size: 11.5px; line-height: 1.3; color: #6b7c76; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .lo-who { display: flex; align-items: center; gap: 10px; margin-top: 14px; padding: 10px 12px; border-radius: 12px; background: #f4f6f5; min-width: 0; }
            .lo-who > span:last-child { min-width: 0; }
            .lo-av { width: 32px; height: 32px; flex: none; border-radius: 50%; display: grid; place-items: center; background: #fff; border: 1px solid #e5eae8; }
            .lo-av img { width: 20px; height: 20px; object-fit: contain; }
            .lo-row { display: flex; align-items: center; gap: 8px; margin-top: 10px; padding: 9px 12px; border-radius: 12px; background: #f4f6f5; font-size: 12px; font-weight: 500; color: #2b433b; }
            .lo-row svg { width: 14px; height: 14px; flex: none; color: #0d9488; }
            .lo-body { padding: 22px 26px 26px; text-align: center; }
            .lo-body h3 { font-family: 'Plus Jakarta Sans', 'Geist', system-ui, sans-serif; font-size: 20px; font-weight: 800; letter-spacing: -.03em; line-height: 1.25; color: #0c2620; text-wrap: balance; }
            .lo-body p { margin-top: 8px; font-size: 14px; line-height: 1.6; color: #6b7c76; }
            .lo-btns { display: flex; gap: 10px; margin-top: 22px; }
            .lo-btn { flex: 1; height: 46px; border-radius: 999px; border: 1px solid transparent; font-size: 15px; font-weight: 500; letter-spacing: -.01em; transition: background .2s, border-color .2s, box-shadow .25s, transform .25s cubic-bezier(.2,.7,.2,1); }
            .lo-btn.ghost { background: #fff; color: #0c2620; border-color: #e5eae8; }
            .lo-btn.ghost:hover { border-color: #d6dedb; box-shadow: 0 1px 2px rgba(12,38,32,.06), 0 1px 3px rgba(12,38,32,.05); }
            .lo-btn.danger { background: #f0506e; color: #fff; }
            .lo-btn.danger:hover { background: #e11d48; box-shadow: 0 12px 26px -10px rgba(240,80,110,.7); transform: translateY(-1px); }
            .lo-btn:disabled { opacity: .7; cursor: wait; }
            @keyframes lo-fade { from { opacity: 0; } to { opacity: 1; } }
            @keyframes lo-pop { from { opacity: 0; transform: translateY(14px) scale(.96); } to { opacity: 1; transform: none; } }
            @media (prefers-reduced-motion: reduce) { .lo-shade, .lo-card { animation: none; } }
        </style>
        <div class="lms-cover lo-shade fixed inset-0 flex items-center justify-center z-[9999] px-4"
            wire:click.self="$set('showSuperAdminLogoutModal', false)"
            x-data x-on:keydown.escape.window="$wire.set('showSuperAdminLogoutModal', false)">
            <div class="lo-card" role="dialog" aria-modal="true" aria-labelledby="lo-title">
                <div class="lo-stage">
                    <div class="lo-panel">
                        <div class="lo-head">
                            <span class="lo-ic"><svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg></span>
                            <span><b>Sign out</b><small>{{ $loRole }} panel</small></span>
                        </div>
                        <div class="lo-who">
                            <span class="lo-av"><img src="{{ \App\Support\Platform::logoUrl() }}" alt=""></span>
                            <span><b>{{ $loName }}</b><small>{{ $loUser->email }}</small></span>
                        </div>
                        <div class="lo-row">
                            <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                            Next sign-in: password + OTP
                        </div>
                    </div>
                </div>
                <div class="lo-body">
                    <h3 id="lo-title">Logout from the {{ $loRole }} panel?</h3>
                    <p>You’ll be signed out on this device. Sign in again any time with your email, password and OTP.</p>
                    <div class="lo-btns">
                        <button type="button" wire:click="$set('showSuperAdminLogoutModal', false)" class="lo-btn ghost">Cancel</button>
                        <button type="button" wire:click="superAdminLogout" wire:loading.attr="disabled" class="lo-btn danger">
                            <span wire:loading.remove wire:target="superAdminLogout">Yes, Logout</span>
                            <span wire:loading wire:target="superAdminLogout">Logging out…</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Admin Logout Modal ── --}}
    @if ($showAdminLogoutModal)
        <div class="lms-cover fixed inset-0 flex items-center justify-center bg-black/30 backdrop-blur-sm z-[9999] px-4">
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
        <div class="lms-cover fixed inset-0 flex items-center justify-center bg-black/30 backdrop-blur-sm z-[9999] px-4">
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
