{{--
    Super Admin → School Website → a school. The school's own detail page's
    look: a plain header, then one card with the tabs. Pages is the Modules
    tab (a toolbar with Enable all / Disable all and what is on, a ticked list
    with hairlines, Save at the foot); Template & Theme and Domain & Publish
    are the same quiet bordered lists. The super admin owns only this shell —
    the school fills in the content under More → Website Data.
--}}
@php
    $catalogue = array_keys($allPages);
    $pagesOn   = count(array_intersect($catalogue, array_merge(['home'], $pages)));
@endphp
<div class="min-h-screen bg-gray-50">

    {{-- ══════════ HEADER ══════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3 sticky top-0 z-40">
        <div class="flex items-center justify-between gap-4 sm:min-h-9">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('super-admin.portal-website') }}"
                   class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-gray-200 text-gray-500 hover:text-gray-800 hover:bg-gray-50 text-sm transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <span class="hidden sm:inline">Back</span>
                </a>
                <div class="min-w-0 sm:flex sm:items-baseline sm:gap-2">
                    <h1 class="min-w-0 text-lg font-bold text-gray-900 leading-none truncate">{{ $organization->name }}</h1>
                    <p class="text-xs mt-0.5 sm:mt-0 truncate">
                        @if ($status && $domain)
                            <span class="text-emerald-600">Live</span> <span class="text-gray-400">· {{ $domain }}</span>
                        @elseif ($status)
                            <span class="text-amber-600">Published</span> <span class="text-gray-400">· no domain yet</span>
                        @else
                            <span class="text-gray-400">Draft</span>
                        @endif
                    </p>
                </div>
            </div>
            @if ($domain)
                <a href="https://{{ $domain }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 flex-shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    <span class="hidden sm:inline">View site</span>
                </a>
            @endif
        </div>
    </div>

    <div class="p-4 sm:p-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">

            {{-- Tab bar --}}
            <div class="flex border-b border-gray-200 overflow-x-auto">
                @foreach (['pages' => 'Pages', 'theme' => 'Template & Theme', 'domain' => 'Domain & Publish'] as $tab => $label)
                    <button type="button" wire:click="switchTab('{{ $tab }}')"
                        class="px-5 py-3 text-sm font-medium whitespace-nowrap transition-colors border-b-2
                            {{ $activeTab === $tab ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- ════════ PAGES — the Modules tab's look ════════ --}}
            @if ($activeTab === 'pages')
                <div class="p-5 space-y-3">
                    <p class="text-xs text-gray-500">
                        Turn pages on or off for this school's website. A page turned off leaves the site's menu and its
                        link shows Page not found. The school writes each page under More → Website Data. Home is always on.
                    </p>

                    <div class="border border-gray-200 rounded-md">
                        {{-- Toolbar --}}
                        <div class="px-4 py-2.5 border-b border-gray-100 flex flex-wrap items-center gap-2">
                            <div class="inline-flex items-center rounded-md border border-gray-200 overflow-hidden text-xs">
                                <button type="button" wire:click="enableAllPages" class="px-2.5 py-1.5 text-gray-600 hover:bg-gray-50">Enable all</button>
                                <button type="button" wire:click="disableAllPages" class="px-2.5 py-1.5 text-gray-600 hover:bg-gray-50 border-l border-gray-200">Disable all</button>
                            </div>
                            <span class="ml-auto text-xs text-gray-400 tabular-nums">{{ $pagesOn }} of {{ count($catalogue) }} on</span>
                        </div>

                        {{-- Rows, under the menu group each page sits in --}}
                        @foreach ($pageGroups as $group => $items)
                            <p class="px-4 pt-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ $group }}</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($items as $slug => [$label, $type])
                                    <label wire:key="page-{{ $slug }}"
                                        class="flex items-center gap-2.5 px-4 py-2 border-b border-gray-100 {{ $slug === 'home' ? 'cursor-default' : 'cursor-pointer hover:bg-gray-50' }}">
                                        @if ($slug === 'home')
                                            <input type="checkbox" checked disabled class="h-4 w-4 rounded border-gray-300 text-blue-600 opacity-60" />
                                        @else
                                            <input type="checkbox" wire:model="pages" value="{{ $slug }}" @checked(in_array($slug, $pages, true))
                                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                        @endif
                                        <span class="text-sm text-gray-700 truncate">{{ $label }}</span>
                                        @if ($slug === 'home')<span class="ml-auto text-[11px] text-gray-400">Always on</span>@endif
                                    </label>
                                @endforeach
                            </div>
                        @endforeach

                        {{-- Footer --}}
                        <div class="px-4 py-2.5 flex justify-end">
                            @include('livewire.super-admin.partials.website-save', ['saveLabel' => 'Save Pages'])
                        </div>
                    </div>
                </div>
            @endif

            {{-- ════════ TEMPLATE & THEME ════════ --}}
            @if ($activeTab === 'theme')
                <div class="p-5 space-y-3">
                    <p class="text-xs text-gray-500">
                        The site's design and colours. Every template reads the same Website Data, so switching keeps
                        everything the school has filled in.
                    </p>

                    <div class="border border-gray-200 rounded-md">
                        <p class="px-4 pt-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Template</p>
                        @foreach ($templates as $key => $tpl)
                            <label wire:key="tpl-{{ $key }}" class="flex items-start gap-2.5 px-4 py-2.5 border-b border-gray-100 cursor-pointer hover:bg-gray-50">
                                <input type="radio" wire:model.live="template" value="{{ $key }}" @checked($template === $key)
                                    class="mt-0.5 h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                <span class="min-w-0">
                                    <span class="block text-sm text-gray-800">{{ $tpl['label'] }}</span>
                                    <span class="block text-xs text-gray-400">{{ $tpl['desc'] }}</span>
                                </span>
                            </label>
                        @endforeach
                        @error('template')<p class="px-4 py-2 text-xs text-red-500 border-b border-gray-100">{{ $message }}</p>@enderror

                        <p class="px-4 pt-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Colour</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($themePresets as $key => $preset)
                                <label wire:key="preset-{{ $key }}" class="flex items-center gap-2.5 px-4 py-2 border-b border-gray-100 cursor-pointer hover:bg-gray-50">
                                    <input type="radio" wire:model.live="theme_preset" value="{{ $key }}" @checked($theme_preset === $key)
                                        class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                    <span class="w-3.5 h-3.5 rounded-full flex-shrink-0 ring-1 ring-black/10" style="background: {{ $preset['primary'] }}"></span>
                                    <span class="text-sm text-gray-700 truncate">{{ $preset['label'] }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center gap-2.5">
                            <span class="text-sm text-gray-700">Custom colour</span>
                            {{-- Not wire:model: an empty colour would show as black; it starts on the preset's colour. --}}
                            <input type="color" value="{{ $primary ?: ($themePresets[$theme_preset]['primary'] ?? '#2563EB') }}"
                                wire:change="$set('primary', $event.target.value)" wire:key="swatch-{{ $theme_preset }}-{{ $primary }}"
                                class="h-7 w-9 border border-gray-200 rounded p-0.5 cursor-pointer">
                            <input type="text" wire:model.blur="primary" value="{{ $primary }}" placeholder="Preset's own"
                                class="w-28 text-xs border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @if ($primary)
                                <button type="button" wire:click="$set('primary', '')" class="text-xs text-gray-400 hover:text-gray-600">Clear</button>
                            @endif
                            <span class="text-xs text-gray-400">overrides the preset's main colour</span>
                            @error('primary')<span class="w-full text-xs text-red-500">{{ $message }}</span>@enderror
                        </div>

                        <div class="px-4 py-2.5 flex justify-end">
                            @include('livewire.super-admin.partials.website-save', ['saveLabel' => 'Save Template & Theme'])
                        </div>
                    </div>
                </div>
            @endif

            {{-- ════════ DOMAIN & PUBLISH ════════ --}}
            @if ($activeTab === 'domain')
                <div class="p-5 space-y-3">
                    <p class="text-xs text-gray-500">
                        Where the site lives and whether it is live. Point the domain's A record (and www) to the same
                        address as superlms.in — its certificate is issued by itself once the site is published.
                    </p>

                    <div class="border border-gray-200 rounded-md">
                        <div class="px-4 py-3 border-b border-gray-100">
                            <label for="site-domain" class="block text-[11px] font-semibold uppercase tracking-wider text-gray-400 mb-1.5">Domain</label>
                            <input id="site-domain" type="text" wire:model="domain" value="{{ $domain }}" placeholder="myschool.com"
                                class="w-full max-w-md text-sm border border-gray-200 rounded-md px-3 py-1.5 text-gray-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @error('domain')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center gap-3">
                            <button type="button" role="switch" aria-checked="{{ $status ? 'true' : 'false' }}" wire:click="$toggle('status')"
                                class="relative inline-flex h-5 w-9 flex-shrink-0 items-center rounded-full transition-colors {{ $status ? 'bg-emerald-500' : 'bg-gray-300' }}">
                                <span class="inline-block h-4 w-4 rounded-full bg-white shadow transition-transform {{ $status ? 'translate-x-4.5' : 'translate-x-0.5' }}"></span>
                            </button>
                            <span class="text-sm font-medium text-gray-800">Publish website</span>
                            <span class="text-xs {{ $status ? 'text-emerald-600' : 'text-gray-400' }}">{{ $status ? 'On' : 'Off' }}</span>
                            <span class="w-full text-xs text-gray-400">On: the site is live at its domain. Off: a private draft, not served on the domain.</span>
                        </div>

                        <div class="px-4 py-2.5 flex justify-end">
                            @include('livewire.super-admin.partials.website-save', ['saveLabel' => 'Save Domain & Publish'])
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
