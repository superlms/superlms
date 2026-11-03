<div>
    {{-- The super admin's top bar has its own look (rounder Back, a pill
         search, the session switcher, the grouped buttons on the right);
         the school and accounts panels keep theirs. --}}
    @php
        $saBar = in_array(auth()->user()->role, ['super-admin', 'sub-super-admin']);
    @endphp
    @if ($saBar)
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

            /* The buttons on the right, together in one capsule. */
            .sa-tools {
                display: inline-flex; align-items: center; gap: 2px; padding: 3px;
                border-radius: 9999px; background: #f8fafc; border: 1px solid #e5e7eb;
            }
            .sa-tools-sep { width: 1px; height: 18px; background: #e2e8f0; margin: 0 3px; }
            .sa-tb {
                --c: #475569;
                position: relative; display: inline-flex; align-items: center; justify-content: center;
                width: 2.125rem; height: 2.125rem; border-radius: 9999px;
                color: #64748b; background: transparent;
                transition: background-color .18s, color .18s, box-shadow .18s, transform .18s;
            }
            .sa-tb:hover {
                background: #fff; color: var(--c);
                box-shadow: 0 1px 2px rgba(15, 23, 42, .06), 0 4px 12px -4px rgba(15, 23, 42, .2);
            }
            .sa-tb:active { transform: scale(.92); }
            .sa-tb:focus-visible { outline: 2px solid var(--c); outline-offset: 1px; }
            .sa-tb-ico { width: 1.2rem; height: 1.2rem; }

            .sa-tb--bell { --c: #d97706; }
            .sa-tb--user { --c: #7c3aed; }
            .sa-tb--out  { --c: #e11d48; }
            .sa-tb--ai { background: linear-gradient(135deg, #ede9fe, #fce7f3); }
            .sa-tb--ai:hover { background: linear-gradient(135deg, #ddd6fe, #fbcfe8); }
            .sa-tb--ai img { transition: transform .3s cubic-bezier(.2, .8, .2, 1); }
            .sa-tb--ai:hover img { transform: rotate(-10deg) scale(1.1); }

            .sa-tb--bell .sa-tb-ico { transform-origin: 50% 10%; }
            .sa-tb--bell:hover .sa-tb-ico { animation: sa-tb-ring .9s ease-in-out; }
            .sa-tb--bell.has-unread .sa-tb-ico { animation: sa-tb-ring-idle 5s ease-in-out infinite; }
            .sa-tb--user:hover .sa-tb-ico { animation: sa-tb-hop .5s ease; }
            .sa-tb--out:hover .sa-tb-ico { animation: sa-tb-nudge .9s ease-in-out infinite; }

            .sa-tb-badge {
                position: absolute; top: 0; right: 0; min-width: 16px; height: 16px; padding: 0 4px;
                border-radius: 9999px; background: #ef4444; color: #fff; font-size: 9.5px; font-weight: 700; line-height: 1;
                display: flex; align-items: center; justify-content: center; pointer-events: none;
                box-shadow: 0 0 0 2px #f8fafc; z-index: 1; isolation: isolate;
            }
            .sa-tb:hover .sa-tb-badge { box-shadow: 0 0 0 2px #fff; }
            .sa-tb-badge::before {
                content: ''; position: absolute; inset: 0; border-radius: inherit; background: #ef4444; z-index: -1;
                animation: sa-tb-ping 2s cubic-bezier(0, 0, .2, 1) infinite;
            }

            .sa-tip {
                position: absolute; top: calc(100% + 10px); left: 50%; transform: translate(-50%, -4px);
                padding: 4px 9px; border-radius: 8px; background: #1f2937; color: #fff;
                font-size: 11px; font-weight: 500; line-height: 1.3; white-space: nowrap;
                opacity: 0; pointer-events: none; transition: opacity .15s, transform .15s; z-index: 70;
                box-shadow: 0 4px 12px -2px rgba(15, 23, 42, .25);
            }
            .sa-tb:hover .sa-tip, .sa-tb:focus-visible .sa-tip { opacity: 1; transform: translate(-50%, 0); }
            .sa-tip--end { left: auto; right: 0; transform: translateY(-4px); }
            .sa-tb:hover .sa-tip--end, .sa-tb:focus-visible .sa-tip--end { transform: translateY(0); }

            @keyframes sa-tb-ring  { 0%, 100% { transform: rotate(0); } 15% { transform: rotate(14deg); } 30% { transform: rotate(-12deg); } 45% { transform: rotate(8deg); } 60% { transform: rotate(-5deg); } 75% { transform: rotate(2deg); } }
            @keyframes sa-tb-ring-idle { 0%, 80%, 100% { transform: rotate(0); } 83% { transform: rotate(14deg); } 86% { transform: rotate(-12deg); } 89% { transform: rotate(8deg); } 92% { transform: rotate(-5deg); } 95% { transform: rotate(2deg); } }
            @keyframes sa-tb-hop   { 0%, 100% { transform: translateY(0); } 45% { transform: translateY(-2.5px); } }
            @keyframes sa-tb-nudge { 0%, 100% { transform: translateX(0); } 50% { transform: translateX(2.5px); } }
            @keyframes sa-tb-ping  { 0% { transform: scale(1); opacity: .5; } 75%, 100% { transform: scale(2); opacity: 0; } }

            @media (prefers-reduced-motion: reduce) {
                .sa-tb, .sa-tb *, .sa-tb-badge::before { animation: none !important; transition: none !important; }
            }
        </style>
    @endif
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
                    class="flex items-center gap-1.5 {{ $saBar ? 'px-3 py-1.5 rounded-xl' : 'px-2.5 py-1.5 rounded-lg' }} border border-gray-200
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
                            Welcome! SuperLMS Admin
                        @endif
                    </h2>
                    {{-- The current academic session (Super Admin → Session). A
                         super admin who may open that page can switch it here
                         when there is more than one session, else open the page. --}}
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
                                    @foreach ($sessionList as $s)
                                        <button type="button" role="option" @if ($s->is_current) aria-selected="true" @endif
                                            @unless ($s->is_current) wire:click="switchSession({{ $s->id }})" @endunless
                                            x-on:click="open = false"
                                            class="w-full flex items-center gap-3 px-3 py-2 text-left transition-colors {{ $s->is_current ? 'bg-blue-50/60' : 'hover:bg-gray-50' }}">
                                            <span class="flex-1 min-w-0">
                                                <span class="block text-sm {{ $s->is_current ? 'font-semibold text-blue-700' : 'font-medium text-gray-800' }}">{{ $s->name }}</span>
                                                <span class="block text-xs text-gray-400">{{ $s->durationLabel() }}</span>
                                            </span>
                                            @if ($s->is_current)
                                                <svg class="w-4 h-4 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            @endif
                                        </button>
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
                    <div class="relative {{ $saBar ? 'sa-search-wrap' : '' }}">
                        <svg class="absolute {{ $saBar ? 'left-4' : 'left-3' }} top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" />
                        </svg>
                        @if ($saBar)
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
                                class="absolute {{ $saBar ? 'right-3.5' : 'right-2.5' }} top-1/2 -translate-y-1/2 text-gray-400
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
            @if ($saBar)
                {{-- Super admin: the buttons sit together in one soft capsule —
                     grey icons that take their own colour under the pointer
                     (bell amber, Profile violet, Logout rose), a white lift and a
                     label underneath; Super Assist on a violet-pink chip; a line
                     before Logout. The bell rings on hover — and by itself every
                     few seconds while something is unread, its count pulsing. --}}
                <div class="flex items-center flex-shrink-0">
                    <div class="sa-tools">
                        {{-- LMS assistant: only flips the global panel open, via a
                             browser event — no server round-trip. --}}
                        @if ($assistantEnabled)
                            <button type="button" x-on:click="$dispatch('gemini-toggle')" aria-label="Super Assist" class="sa-tb sa-tb--ai">
                                <img src="{{ \App\Support\Platform::logoUrl() }}" alt=""
                                    width="20" height="20" class="w-5 h-5 object-contain">
                                <span class="sa-tip">Super Assist</span>
                            </button>
                        @endif

                        <div class="relative inline-flex" wire:poll.60s>
                            <button type="button" wire:click="$toggle('showNotifications')" aria-label="Notifications"
                                class="sa-tb sa-tb--bell {{ ($unreadNotifications ?? 0) > 0 ? 'has-unread' : '' }}">
                                <x-icon name="bell" class="sa-tb-ico" />
                                @if (($unreadNotifications ?? 0) > 0)
                                    <span class="sa-tb-badge">{{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}</span>
                                @endif
                                <span class="sa-tip">Notifications</span>
                            </button>
                        </div>

                        {{-- Profile (2026-11-01): OTP numbers, platform logo and details. --}}
                        <a href="{{ route('super-admin.profile') }}" aria-label="Profile" class="sa-tb sa-tb--user">
                            <x-icon name="user-circle" class="sa-tb-ico" />
                            <span class="sa-tip">Profile</span>
                        </a>

                        <span class="sa-tools-sep" aria-hidden="true"></span>

                        <button type="button" wire:click="confirmLogout" aria-label="Logout" class="sa-tb sa-tb--out">
                            <x-icon name="arrow-right-on-rectangle" class="sa-tb-ico" />
                            <span class="sa-tip sa-tip--end">Logout</span>
                        </button>
                    </div>
                </div>
            @else
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

                    <x-button rounded class="h-9 w-9 bg-white" icon="arrow-right-on-rectangle" outline
                        wire:click="confirmLogout" />
                </div>
            @endif

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
