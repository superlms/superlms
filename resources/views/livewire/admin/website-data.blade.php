<div class="min-h-screen bg-gray-50">

    {{-- ══════════ TOP BAR ══════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4 sticky top-0 z-40">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('admin.more', ['organization' => $organization?->id]) }}" class="p-2 -ml-2 text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <div class="min-w-0">
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900 truncate">Website Data</h1>
                    <p class="text-xs text-gray-500 mt-0.5">Manage the content shown on your school's public website. Pages, theme &amp; domain are set by SUPERLMS.</p>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <button wire:click="save"
                    class="inline-flex items-center gap-2 px-5 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-semibold rounded-lg shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Save
                </button>
            </div>
        </div>
        {{-- Tabs --}}
        <nav class="flex gap-1 mt-3 -mb-4 overflow-x-auto">
            @foreach (['details' => 'Details', 'about' => 'About', 'leadership' => 'Leadership', 'facilities' => 'Facilities', 'admission' => 'Admissions', 'gallery' => 'Gallery'] as $tab => $label)
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
                Details auto-filled from your school's profile where available. Edit anything below and add what's missing.
            </div>

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
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Motto <span class="text-gray-400">(e.g. Serving Nation Through Education)</span></label>
                        <input type="text" wire:model="form.motto" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Medium <span class="text-gray-400">(e.g. English Medium, Co-Ed)</span></label>
                        <input type="text" wire:model="form.medium" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Board <span class="text-gray-400">(e.g. CBSE)</span></label>
                        <input type="text" wire:model="form.board" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Affiliation No.</label>
                        <input type="text" wire:model="form.affiliation_no" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">School Code</label>
                        <input type="text" wire:model="form.school_code" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
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
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Telegram URL</label>
                        <input type="text" wire:model="form.telegram" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
                    </div>
                </div>
            </div>

            {{-- Classes repeater --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900">Classes <span class="text-xs text-gray-400">({{ count($classes) }})</span></h2>
                    <button wire:click="addRow('classes')" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg">+ Add</button>
                </div>
                @forelse ($classes as $i => $cls)
                    <div class="border border-gray-200 rounded-xl p-3 grid sm:grid-cols-5 gap-2 items-end" wire:key="cls-{{ $i }}">
                        <div class="sm:col-span-2"><label class="block text-[11px] text-gray-500 mb-1">Title</label><input wire:model="classes.{{ $i }}.title" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></div>
                        <div><label class="block text-[11px] text-gray-500 mb-1">Age</label><input wire:model="classes.{{ $i }}.age" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></div>
                        <div><label class="block text-[11px] text-gray-500 mb-1">Time</label><input wire:model="classes.{{ $i }}.time" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></div>
                        <div class="flex gap-1">
                            <div class="flex-1"><label class="block text-[11px] text-gray-500 mb-1">Capacity</label><input wire:model="classes.{{ $i }}.capacity" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></div>
                            <button wire:click="removeRow('classes', {{ $i }})" class="p-2 text-red-500 hover:bg-red-50 rounded-lg self-end"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
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
                    <button wire:click="addRow('team')" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg">+ Add</button>
                </div>
                @forelse ($team as $i => $member)
                    <div class="border border-gray-200 rounded-xl p-3 grid sm:grid-cols-3 gap-2 items-end" wire:key="team-{{ $i }}">
                        <div><label class="block text-[11px] text-gray-500 mb-1">Name</label><input wire:model="team.{{ $i }}.name" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></div>
                        <div><label class="block text-[11px] text-gray-500 mb-1">Role</label><input wire:model="team.{{ $i }}.role" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></div>
                        <div class="flex gap-1">
                            <div class="flex-1"><label class="block text-[11px] text-gray-500 mb-1">Photo URL</label><input wire:model="team.{{ $i }}.photo" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></div>
                            <button wire:click="removeRow('team', {{ $i }})" class="p-2 text-red-500 hover:bg-red-50 rounded-lg self-end"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-400">No team members added — auto-filled from the school's management team if available.</p>
                @endforelse
            </div>
        @endif

        {{-- ══════════ ABOUT (history / philosophy / why-us / stats) ══════════ --}}
        @if ($activeTab === 'about')
            <div class="bg-indigo-50 border border-indigo-100 rounded-xl px-4 py-3 text-xs text-indigo-700">
                Richer About-page content. Vision &amp; Mission live under the <strong>Details</strong> tab.
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                <h2 class="text-base font-semibold text-gray-900">Our Story</h2>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">History</label>
                    <textarea wire:model="form.history_text" rows="4" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 resize-y"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Philosophy</label>
                    <textarea wire:model="form.philosophy" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 resize-y"></textarea>
                </div>
            </div>

            {{-- Stats repeater --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900">Highlight Stats <span class="text-xs text-gray-400">({{ count($stats) }}) — shown on the home hero</span></h2>
                    <button wire:click="addRow('stats')" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg">+ Add</button>
                </div>
                @forelse ($stats as $i => $row)
                    <div class="border border-gray-200 rounded-xl p-3 grid sm:grid-cols-2 gap-2 items-end" wire:key="stat-{{ $i }}">
                        <div><label class="block text-[11px] text-gray-500 mb-1">Value (e.g. Nursery–X, CBSE, 500+)</label><input wire:model="stats.{{ $i }}.value" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></div>
                        <div class="flex gap-1">
                            <div class="flex-1"><label class="block text-[11px] text-gray-500 mb-1">Label</label><input wire:model="stats.{{ $i }}.label" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></div>
                            <button wire:click="removeRow('stats', {{ $i }})" class="p-2 text-red-500 hover:bg-red-50 rounded-lg self-end"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-400">No stats added — the hero shows default highlights.</p>
                @endforelse
            </div>

            {{-- Why-us repeater --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900">Why Choose Us <span class="text-xs text-gray-400">({{ count($whyUs) }})</span></h2>
                    <button wire:click="addRow('whyUs')" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg">+ Add</button>
                </div>
                @forelse ($whyUs as $i => $row)
                    <div class="border border-gray-200 rounded-xl p-3 grid sm:grid-cols-6 gap-2 items-end" wire:key="why-{{ $i }}">
                        <div><label class="block text-[11px] text-gray-500 mb-1">Icon (emoji)</label><input wire:model="whyUs.{{ $i }}.icon" placeholder="🌟" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></div>
                        <div class="sm:col-span-2"><label class="block text-[11px] text-gray-500 mb-1">Title</label><input wire:model="whyUs.{{ $i }}.title" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></div>
                        <div class="sm:col-span-3 flex gap-1">
                            <div class="flex-1"><label class="block text-[11px] text-gray-500 mb-1">Description</label><input wire:model="whyUs.{{ $i }}.desc" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></div>
                            <button wire:click="removeRow('whyUs', {{ $i }})" class="p-2 text-red-500 hover:bg-red-50 rounded-lg self-end"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-400">No points added — a default set is shown.</p>
                @endforelse
            </div>
        @endif

        {{-- ══════════ LEADERSHIP ══════════ --}}
        @if ($activeTab === 'leadership')
            <div class="bg-indigo-50 border border-indigo-100 rounded-xl px-4 py-3 text-xs text-indigo-700">
                Leadership messages — Chairman, Founder, Principal, Director, etc. Each shows as a card with their photo and message.
            </div>
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900">Leadership Messages <span class="text-xs text-gray-400">({{ count($leadership) }})</span></h2>
                    <button wire:click="addRow('leadership')" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg">+ Add</button>
                </div>
                @forelse ($leadership as $i => $row)
                    <div class="border border-gray-200 rounded-xl p-3 space-y-2" wire:key="lead-{{ $i }}">
                        <div class="grid sm:grid-cols-3 gap-2">
                            <div><label class="block text-[11px] text-gray-500 mb-1">Name</label><input wire:model="leadership.{{ $i }}.name" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></div>
                            <div><label class="block text-[11px] text-gray-500 mb-1">Role</label><input wire:model="leadership.{{ $i }}.role" placeholder="Chairman / Principal" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></div>
                            <div><label class="block text-[11px] text-gray-500 mb-1">Photo URL</label><input wire:model="leadership.{{ $i }}.photo" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></div>
                        </div>
                        <div class="flex gap-1 items-end">
                            <div class="flex-1"><label class="block text-[11px] text-gray-500 mb-1">Message</label><textarea wire:model="leadership.{{ $i }}.message" rows="3" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm resize-y"></textarea></div>
                            <button wire:click="removeRow('leadership', {{ $i }})" class="p-2 text-red-500 hover:bg-red-50 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-400">No leadership messages yet. Click "+ Add".</p>
                @endforelse
            </div>
        @endif

        {{-- ══════════ FACILITIES ══════════ --}}
        @if ($activeTab === 'facilities')
            <div class="bg-indigo-50 border border-indigo-100 rounded-xl px-4 py-3 text-xs text-indigo-700">
                Facilities &amp; features (Labs, Library, Transport, Playground…). Shown as an icon grid on the Facilities page and previewed on Home.
            </div>
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900">Facilities <span class="text-xs text-gray-400">({{ count($facilities) }})</span></h2>
                    <button wire:click="addRow('facilities')" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg">+ Add</button>
                </div>
                @forelse ($facilities as $i => $row)
                    <div class="border border-gray-200 rounded-xl p-3 grid sm:grid-cols-6 gap-2 items-end" wire:key="fac-{{ $i }}">
                        <div><label class="block text-[11px] text-gray-500 mb-1">Icon (emoji)</label><input wire:model="facilities.{{ $i }}.icon" placeholder="🔬" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></div>
                        <div class="sm:col-span-2"><label class="block text-[11px] text-gray-500 mb-1">Title</label><input wire:model="facilities.{{ $i }}.title" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></div>
                        <div class="sm:col-span-3 flex gap-1">
                            <div class="flex-1"><label class="block text-[11px] text-gray-500 mb-1">Description</label><input wire:model="facilities.{{ $i }}.desc" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></div>
                            <button wire:click="removeRow('facilities', {{ $i }})" class="p-2 text-red-500 hover:bg-red-50 rounded-lg self-end"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-400">No facilities added — a default set is shown on the site until you add your own.</p>
                @endforelse
            </div>
        @endif

        {{-- ══════════ ADMISSIONS ══════════ --}}
        @if ($activeTab === 'admission')
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                <h2 class="text-base font-semibold text-gray-900">Admissions</h2>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Intro</label>
                    <textarea wire:model="form.admission_intro" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 resize-y"></textarea>
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Session (e.g. 2026-2027)</label>
                        <input type="text" wire:model="form.admission_session" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Fee Structure Note</label>
                        <input type="text" wire:model="form.fee_note" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Curriculum</label>
                    <textarea wire:model="form.curriculum_text" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 resize-y"></textarea>
                </div>
            </div>

            {{-- Admission steps --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900">Admission Steps <span class="text-xs text-gray-400">({{ count($admissionSteps) }})</span></h2>
                    <button wire:click="addRow('admissionSteps')" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg">+ Add</button>
                </div>
                @forelse ($admissionSteps as $i => $row)
                    <div class="border border-gray-200 rounded-xl p-3 grid sm:grid-cols-3 gap-2 items-end" wire:key="step-{{ $i }}">
                        <div><label class="block text-[11px] text-gray-500 mb-1">Title</label><input wire:model="admissionSteps.{{ $i }}.title" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></div>
                        <div class="sm:col-span-2 flex gap-1">
                            <div class="flex-1"><label class="block text-[11px] text-gray-500 mb-1">Description</label><input wire:model="admissionSteps.{{ $i }}.desc" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></div>
                            <button wire:click="removeRow('admissionSteps', {{ $i }})" class="p-2 text-red-500 hover:bg-red-50 rounded-lg self-end"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-400">No steps added — default steps are shown.</p>
                @endforelse
            </div>

            {{-- Documents required + Rules (two simple text lists) --}}
            <div class="grid lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-semibold text-gray-900">Documents Required <span class="text-xs text-gray-400">({{ count($documentsRequired) }})</span></h2>
                        <button wire:click="addRow('documentsRequired')" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg">+ Add</button>
                    </div>
                    @forelse ($documentsRequired as $i => $row)
                        <div class="flex gap-1 items-center" wire:key="doc-{{ $i }}">
                            <input wire:model="documentsRequired.{{ $i }}.text" placeholder="e.g. Birth Certificate" class="flex-1 border border-gray-300 rounded-lg px-2 py-1.5 text-sm">
                            <button wire:click="removeRow('documentsRequired', {{ $i }})" class="p-2 text-red-500 hover:bg-red-50 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400">No documents added — a default checklist is shown.</p>
                    @endforelse
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-semibold text-gray-900">Rules &amp; Regulations <span class="text-xs text-gray-400">({{ count($admissionRules) }})</span></h2>
                        <button wire:click="addRow('admissionRules')" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg">+ Add</button>
                    </div>
                    @forelse ($admissionRules as $i => $row)
                        <div class="flex gap-1 items-center" wire:key="rule-{{ $i }}">
                            <input wire:model="admissionRules.{{ $i }}.text" placeholder="e.g. New session begins on April 1st" class="flex-1 border border-gray-300 rounded-lg px-2 py-1.5 text-sm">
                            <button wire:click="removeRow('admissionRules', {{ $i }})" class="p-2 text-red-500 hover:bg-red-50 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400">No rules added yet.</p>
                    @endforelse
                </div>
            </div>
        @endif

        {{-- ══════════ GALLERY ══════════ --}}
        @if ($activeTab === 'gallery')
            <div class="bg-indigo-50 border border-indigo-100 rounded-xl px-4 py-3 text-xs text-indigo-700">
                Photo gallery — add image URLs with optional captions. Shown as a grid on the Gallery page.
            </div>
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900">Gallery Images <span class="text-xs text-gray-400">({{ count($gallery) }})</span></h2>
                    <button wire:click="addRow('gallery')" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg">+ Add</button>
                </div>
                @forelse ($gallery as $i => $row)
                    <div class="border border-gray-200 rounded-xl p-3 grid sm:grid-cols-2 gap-2 items-end" wire:key="gal-{{ $i }}">
                        <div><label class="block text-[11px] text-gray-500 mb-1">Image URL</label><input wire:model="gallery.{{ $i }}.image" placeholder="https://..." class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></div>
                        <div class="flex gap-1">
                            <div class="flex-1"><label class="block text-[11px] text-gray-500 mb-1">Caption</label><input wire:model="gallery.{{ $i }}.caption" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></div>
                            <button wire:click="removeRow('gallery', {{ $i }})" class="p-2 text-red-500 hover:bg-red-50 rounded-lg self-end"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-400">No images added yet. Click "+ Add".</p>
                @endforelse
            </div>
        @endif

    </div>
</div>
