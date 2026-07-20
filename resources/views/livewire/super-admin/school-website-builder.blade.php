<div class="min-h-screen bg-gray-50">

    {{-- ══════════ TOP BAR ══════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4 sticky top-0 z-40">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('super-admin.portal-website') }}" class="p-2 -ml-2 text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <div class="min-w-0">
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900 truncate">{{ $organization->name }} — Website</h1>
                    <p class="text-xs text-gray-500 mt-0.5">
                        @if ($status && $domain)
                            <span class="text-green-600 font-medium">● Live</span> at {{ $domain }}
                        @elseif ($status)
                            <span class="text-amber-600 font-medium">● Published</span> — domain pending
                        @else
                            <span class="text-gray-400 font-medium">● Draft</span>
                        @endif
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                @if ($domain)
                    <a href="https://{{ $domain }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        View Live
                    </a>
                @endif
                <button wire:click="save"
                    class="inline-flex items-center gap-2 px-5 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-semibold rounded-lg shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Save
                </button>
            </div>
        </div>
        {{-- Tabs — super-admin owns only the site shell; content lives in the
             school admin's More → Website Data screen. --}}
        <nav class="flex gap-1 mt-3 -mb-4 overflow-x-auto">
            @foreach (['pages' => 'Pages', 'theme' => 'Theme', 'domain' => 'Domain & Publish'] as $tab => $label)
                <button wire:click="switchTab('{{ $tab }}')"
                    class="py-2.5 px-4 text-sm font-semibold border-b-2 whitespace-nowrap transition-colors
                        {{ $activeTab === $tab ? 'border-indigo-500 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-6 space-y-6">

        <div class="bg-indigo-50 border border-indigo-100 rounded-xl px-4 py-3 text-xs text-indigo-700">
            You control the site's <strong>pages, theme, domain &amp; publishing</strong> here. The school fills in all the
            content (brand, about, leadership, facilities, admissions, gallery…) from their admin panel under
            <strong>More → Website Data</strong>, and it reflects on the live site automatically.
        </div>

        {{-- ══════════ PAGES ══════════ --}}
        @if ($activeTab === 'pages')
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-900 mb-1">Website Pages</h2>
                <p class="text-xs text-gray-400 mb-5">Choose which pages appear on the school's website. Home is always included.</p>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach ($allPages as $slug => $label)
                        @php $on = in_array($slug, $pages) || $slug === 'home'; @endphp
                        <button type="button" wire:click="togglePage('{{ $slug }}')"
                            class="flex items-center justify-between px-4 py-3 rounded-xl border text-sm font-medium transition
                                {{ $on ? 'border-indigo-300 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-500 hover:border-gray-300' }}
                                {{ $slug === 'home' ? 'opacity-70 cursor-not-allowed' : '' }}">
                            <span>{{ $label }}</span>
                            @if ($on)
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
                            @else
                                <span class="w-5 h-5 rounded-full border border-gray-300"></span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ══════════ THEME ══════════ --}}
        @if ($activeTab === 'theme')
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-900 mb-1">Colour Theme</h2>
                <p class="text-xs text-gray-400 mb-5">Pick a preset, or set a custom primary colour to override it.</p>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-6">
                    @foreach ($themePresets as $key => $preset)
                        <button type="button" wire:click="$set('theme_preset', '{{ $key }}')"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl border transition
                                {{ $theme_preset === $key ? 'border-indigo-400 ring-2 ring-indigo-100' : 'border-gray-200 hover:border-gray-300' }}">
                            <span class="w-8 h-8 rounded-full flex-shrink-0" style="background: {{ $preset['primary'] }}"></span>
                            <span class="text-sm font-medium text-gray-700">{{ $preset['label'] }}</span>
                        </button>
                    @endforeach
                </div>
                <div class="max-w-xs">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Custom primary colour (optional)</label>
                    <div class="flex items-center gap-2">
                        <input type="color" wire:model.live="primary" class="h-10 w-14 border border-gray-300 rounded-lg p-1">
                        <input type="text" wire:model="primary" placeholder="#FE5D37" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        @if ($primary)
                            <button wire:click="$set('primary', '')" class="text-xs text-gray-400 hover:text-gray-600">clear</button>
                        @endif
                    </div>
                    @error('primary')<p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>@enderror
                </div>
            </div>
        @endif

        {{-- ══════════ DOMAIN & PUBLISH ══════════ --}}
        @if ($activeTab === 'domain')
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                <h2 class="text-base font-semibold text-gray-900">Custom Domain</h2>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Domain</label>
                    <input type="text" wire:model="domain" placeholder="myschool.com"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
                    @error('domain')<p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>@enderror
                </div>
                <div class="bg-amber-50 border border-amber-100 rounded-xl px-4 py-3 text-xs text-amber-800 leading-relaxed">
                    <p class="font-semibold mb-1">One-time DNS setup (by the school / ops):</p>
                    1. Point the domain's <strong>A record</strong> to the SUPERLMS server IP.<br>
                    2. On the server, add this domain to the web server (nginx <code>server_name</code>) and issue an SSL certificate.<br>
                    3. Toggle <strong>Publish</strong> below — the site goes live on this domain automatically.
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <label class="flex items-center justify-between cursor-pointer">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Publish website</h2>
                        <p class="text-xs text-gray-400 mt-0.5">When ON, the site is live on its domain. When OFF, it stays a private draft.</p>
                    </div>
                    <input type="checkbox" wire:model="status" class="w-11 h-6 rounded-full appearance-none bg-gray-200 checked:bg-green-500 relative transition cursor-pointer
                        before:content-[''] before:absolute before:top-0.5 before:left-0.5 before:w-5 before:h-5 before:bg-white before:rounded-full before:transition checked:before:translate-x-5">
                </label>
            </div>
        @endif

    </div>
</div>
