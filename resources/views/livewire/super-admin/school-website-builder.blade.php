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
        {{-- Tabs --}}
        <nav class="flex gap-1 mt-3 -mb-4 overflow-x-auto">
            @foreach (['details' => 'Details', 'pages' => 'Pages', 'theme' => 'Theme', 'domain' => 'Domain & Publish'] as $tab => $label)
                <button wire:click="switchTab('{{ $tab }}')"
                    class="py-2.5 px-4 text-sm font-semibold border-b-2 whitespace-nowrap transition-colors
                        {{ $activeTab === $tab ? 'border-indigo-500 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-6 space-y-6">

        {{-- ══════════ DETAILS ══════════ --}}
        @if ($activeTab === 'details')
            <div class="bg-indigo-50 border border-indigo-100 rounded-xl px-4 py-3 text-xs text-indigo-700">
                Details auto-filled from the school's profile where available. Edit anything below and add what's missing.
            </div>

            @php
                $group = function ($title) { return $title; };
            @endphp

            {{-- Brand --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                <h2 class="text-base font-semibold text-gray-900">Brand</h2>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">School Name *</label>
                        <input type="text" wire:model="form.school_name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
                        @error('form.school_name')<p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tagline</label>
                        <input type="text" wire:model="form.tagline" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Logo URL <span class="text-gray-400">(leave blank to use the school's saved logo)</span></label>
                        <input type="text" wire:model="form.logo" placeholder="https://... or storage path" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
                    </div>
                </div>
            </div>

            {{-- Hero --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                <h2 class="text-base font-semibold text-gray-900">Hero Banner</h2>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Hero Title</label>
                    <input type="text" wire:model="form.hero_title" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Hero Subtitle</label>
                    <textarea wire:model="form.hero_subtitle" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 resize-y"></textarea>
                </div>
            </div>

            {{-- About --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                <h2 class="text-base font-semibold text-gray-900">About Section</h2>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">About Heading</label>
                    <input type="text" wire:model="form.about_heading" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">About Text</label>
                    <textarea wire:model="form.about_text" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 resize-y"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">About Text (paragraph 2)</label>
                    <textarea wire:model="form.about_text2" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 resize-y"></textarea>
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Vision</label>
                        <textarea wire:model="form.vision" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 resize-y"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Mission</label>
                        <textarea wire:model="form.mission" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 resize-y"></textarea>
                    </div>
                </div>
            </div>

            {{-- CTA --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                <h2 class="text-base font-semibold text-gray-900">Call-to-Action</h2>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Heading</label>
                        <input type="text" wire:model="form.cta_heading" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Text</label>
                        <input type="text" wire:model="form.cta_text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
                    </div>
                </div>
            </div>

            {{-- Contact + Social --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                <h2 class="text-base font-semibold text-gray-900">Contact &amp; Social</h2>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Phone</label>
                        <input type="text" wire:model="form.phone" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                        <input type="text" wire:model="form.email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Address</label>
                        <input type="text" wire:model="form.address" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Facebook URL</label>
                        <input type="text" wire:model="form.facebook" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Instagram URL</label>
                        <input type="text" wire:model="form.instagram" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">YouTube URL</label>
                        <input type="text" wire:model="form.youtube" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Twitter URL</label>
                        <input type="text" wire:model="form.twitter" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
                    </div>
                </div>
            </div>

            {{-- Classes repeater --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900">Classes <span class="text-xs text-gray-400">({{ count($classes) }})</span></h2>
                    <button wire:click="addClass" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg">+ Add</button>
                </div>
                @forelse ($classes as $i => $cls)
                    <div class="border border-gray-200 rounded-xl p-3 grid sm:grid-cols-5 gap-2 items-end" wire:key="cls-{{ $i }}">
                        <div class="sm:col-span-2"><label class="block text-[11px] text-gray-500 mb-1">Title</label><input wire:model="classes.{{ $i }}.title" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></div>
                        <div><label class="block text-[11px] text-gray-500 mb-1">Age</label><input wire:model="classes.{{ $i }}.age" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></div>
                        <div><label class="block text-[11px] text-gray-500 mb-1">Time</label><input wire:model="classes.{{ $i }}.time" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></div>
                        <div class="flex gap-1">
                            <div class="flex-1"><label class="block text-[11px] text-gray-500 mb-1">Capacity</label><input wire:model="classes.{{ $i }}.capacity" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></div>
                            <button wire:click="removeClass({{ $i }})" class="p-2 text-red-500 hover:bg-red-50 rounded-lg self-end"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-400">No classes added — the template will show sample classes until you add your own.</p>
                @endforelse
            </div>

            {{-- Team repeater --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900">Team <span class="text-xs text-gray-400">({{ count($team) }})</span></h2>
                    <button wire:click="addTeam" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg">+ Add</button>
                </div>
                @forelse ($team as $i => $member)
                    <div class="border border-gray-200 rounded-xl p-3 grid sm:grid-cols-3 gap-2 items-end" wire:key="team-{{ $i }}">
                        <div><label class="block text-[11px] text-gray-500 mb-1">Name</label><input wire:model="team.{{ $i }}.name" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></div>
                        <div><label class="block text-[11px] text-gray-500 mb-1">Role</label><input wire:model="team.{{ $i }}.role" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></div>
                        <div class="flex gap-1">
                            <div class="flex-1"><label class="block text-[11px] text-gray-500 mb-1">Photo URL</label><input wire:model="team.{{ $i }}.photo" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></div>
                            <button wire:click="removeTeam({{ $i }})" class="p-2 text-red-500 hover:bg-red-50 rounded-lg self-end"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-400">No team members added — auto-filled from the school's management team if available.</p>
                @endforelse
            </div>
        @endif

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
                    1. Point the domain's <strong>A record</strong> to the EDYONE server IP.<br>
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
