<div class="min-h-screen bg-gray-50">

    {{-- ══════════ TABS ══════════ --}}
    <div class="bg-white border-b border-gray-200 px-6 sticky top-0 z-40">
        <nav class="flex gap-1">
            <button wire:click="switchTab('view')"
                class="py-3.5 px-5 text-sm font-semibold border-b-2 transition-colors
                       {{ $activeTab === 'view'
                           ? 'border-blue-500 text-blue-700'
                           : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                View Terms &amp; Conditions
            </button>
            <button wire:click="switchTab('edit')"
                class="py-3.5 px-5 text-sm font-semibold border-b-2 transition-colors
                       {{ $activeTab === 'edit'
                           ? 'border-purple-500 text-purple-700'
                           : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                {{ $termsCondition ? 'Edit' : 'Create' }} Terms &amp; Conditions
            </button>
        </nav>
    </div>

    {{-- ══════════ VIEW TAB ══════════ --}}
    @if ($activeTab === 'view')
        @if (!$termsCondition)
            <div class="flex flex-col items-center justify-center min-h-[60vh] text-center px-4">
                <div class="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-5">
                    <svg class="w-10 h-10 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No Terms &amp; Conditions Yet</h3>
                <p class="text-gray-400 text-sm mb-5 max-w-sm">Create your platform's terms and conditions to display them here.
                </p>
                <button wire:click="switchTab('edit')"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700
                           text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create Now
                </button>
            </div>
        @else
            @php
                $metadata = $termsCondition->metadata ?? [];
                $viewSections = $metadata['sections'] ?? [];
            @endphp

            {{-- COMPACT HEADER (fees style) — basic info now lives in About App --}}
            <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4 sm:py-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <img src="{{ asset('website-image/Group 11525.png') }}" alt="SUPERLMS"
                            class="w-12 h-12 rounded-xl object-contain border border-gray-200 shadow-sm bg-white p-1 flex-shrink-0">
                        <div class="min-w-0">
                            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 truncate">Terms &amp; Conditions</h1>
                            <p class="text-sm text-gray-500 mt-0.5 truncate">{{ count($viewSections) }} section(s)</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        @if ($termsCondition->last_updated)
                            <span class="text-xs px-3 py-1 bg-blue-50 text-blue-600 rounded-full border border-blue-100 font-medium">
                                Last Updated: {{ $termsCondition->last_updated->format('d M Y') }}
                            </span>
                        @endif
                        <button wire:click="switchTab('edit')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium
                                   text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit
                        </button>
                    </div>
                </div>
            </div>

            <div class="max-w-5xl mx-auto px-6 py-8 space-y-6">

                {{-- Sections --}}
                @foreach ($viewSections as $i => $section)
                    @if (!empty($section['head']) || !empty($section['desc']))
                        <div
                            class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden
                                    hover:border-blue-200 hover:shadow-md transition-all duration-200">
                            @if (!empty($section['head']))
                                <div
                                    class="px-6 py-4 border-b border-gray-100 flex items-center gap-3
                                            bg-gradient-to-r from-blue-50 to-indigo-50">
                                    <span
                                        class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600
                                                 text-white text-sm font-bold rounded-xl flex items-center
                                                 justify-center flex-shrink-0 shadow-sm">
                                        {{ $i + 1 }}
                                    </span>
                                    <h2 class="text-base font-semibold text-gray-900">{{ $section['head'] }}</h2>
                                </div>
                            @endif
                            @if (!empty($section['desc']))
                                <div class="px-6 py-5">
                                    <p class="text-sm text-gray-600 leading-relaxed">
                                        {!! nl2br(e($section['desc'])) !!}
                                    </p>
                                </div>
                            @endif
                        </div>
                    @endif
                @endforeach

            </div>
        @endif
    @endif

    {{-- ══════════ DELETE SECTION CONFIRM MODAL ══════════ --}}
    @if ($pendingDeleteSectionIndex !== null)
        <div class="fixed inset-0 flex items-center justify-center bg-black/40 backdrop-blur-sm z-[9999] px-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 bg-red-50 flex items-center gap-3">
                    <div class="w-9 h-9 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900">Delete Section</h3>
                </div>
                <div class="p-5">
                    <p class="text-sm text-gray-600">Are you sure you want to delete
                        <strong>Section {{ $pendingDeleteSectionIndex + 1 }}</strong>?
                        This action cannot be undone.
                    </p>
                </div>
                <div class="px-5 pb-5 flex items-center gap-2">
                    <button wire:click="executeRemoveSection"
                        class="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors">
                        Yes, Delete
                    </button>
                    <button wire:click="cancelRemoveSection"
                        class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif


    {{-- ══════════ EDIT TAB ══════════ --}}
    @if ($activeTab === 'edit')
        <div class="max-w-5xl mx-auto px-6 py-8 space-y-6">

            {{-- General (basic information now lives in the About App module) --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-indigo-50
                            flex items-center gap-3">
                    <div class="w-8 h-8 bg-blue-500 rounded-xl flex items-center justify-center shadow-sm">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h2 class="text-base font-semibold text-gray-900">General</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Last Updated</label>
                        <div class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm text-gray-700 flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span class="font-medium">{{ now()->format('d M Y') }}</span>
                            <span class="text-xs text-gray-400">· set automatically when you save</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sections --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-indigo-50
                            flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-indigo-500 rounded-xl flex items-center justify-center shadow-sm">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-gray-900">Terms &amp; Conditions Sections</h2>
                            <p class="text-xs text-gray-400">{{ count($sections) }} section(s)</p>
                        </div>
                    </div>
                    <button wire:click="addSection"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700
                               text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v16m8-8H4" />
                        </svg>
                        Add Section
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    @foreach ($sections as $index => $section)
                        <div class="border border-gray-200 rounded-xl p-4 hover:border-indigo-200 transition-colors">
                            <div class="flex items-center justify-between mb-3">
                                <span class="flex items-center gap-2">
                                    <span
                                        class="w-6 h-6 bg-indigo-100 text-indigo-700 text-xs font-bold
                                                 rounded-full flex items-center justify-center">
                                        {{ $index + 1 }}
                                    </span>
                                    <span class="text-sm font-medium text-gray-700">Section {{ $index + 1 }}</span>
                                </span>
                                @if (count($sections) > 1)
                                    <button wire:click="confirmRemoveSection({{ $index }})"
                                        class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                @endif
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Heading *</label>
                                    <input type="text" wire:model="sections.{{ $index }}.head"
                                        placeholder="e.g., Acceptance of Terms"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                               focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                                    @error("sections.{$index}.head")
                                        <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Description *</label>
                                    <textarea wire:model="sections.{{ $index }}.desc" rows="4" placeholder="Detailed description..."
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                               focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400"></textarea>
                                    @error("sections.{$index}.desc")
                                        <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            {{-- Save Button --}}
            <div class="flex justify-end pb-6">
                <button wire:click="save"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600
                           hover:from-green-700 hover:to-emerald-700 text-white font-semibold rounded-xl
                           shadow-md hover:shadow-lg transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Save Terms &amp; Conditions
                </button>
            </div>

        </div>
    @endif

</div>
