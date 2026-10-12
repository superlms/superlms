<div class="min-h-screen bg-white">

    {{-- ══════════ TABS ══════════ --}}
    <div class="bg-white border-b border-gray-200 px-3 sm:px-6 sticky top-0 z-40 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
        <nav class="flex gap-1 min-w-max">
            <button wire:click="switchTab('view')"
                class="py-3 sm:py-3.5 px-3 sm:px-5 text-sm font-semibold border-b-2 transition-colors whitespace-nowrap
                       {{ $activeTab === 'view'
                           ? 'border-blue-500 text-blue-700'
                           : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                View Privacy Policy
            </button>
            <button wire:click="switchTab('edit')"
                class="py-3 sm:py-3.5 px-3 sm:px-5 text-sm font-semibold border-b-2 transition-colors whitespace-nowrap
                       {{ $activeTab === 'edit'
                           ? 'border-purple-500 text-purple-700'
                           : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                {{ $privacyPolicy ? 'Edit' : 'Create' }} Privacy Policy
            </button>
        </nav>
    </div>

    {{-- ══════════ VIEW TAB ══════════ --}}
    @if ($activeTab === 'view')
        @if (!$privacyPolicy)
            <div class="flex flex-col items-center justify-center min-h-[60vh] text-center px-4">
                <div class="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-5">
                    <div class="flex-shrink-0">
                        <img src="{{ asset('website-image/Group 11525.png') }}" alt="SUPERLMS"
                            class="w-24 h-24 rounded-2xl object-contain border border-gray-200 shadow-sm bg-white p-2">
                    </div>
                </div>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No Privacy Policy Yet</h3>
                <p class="text-gray-400 text-sm mb-5 max-w-sm">Create your platform's privacy policy to display it here.
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
                $metadata = $privacyPolicy->metadata ?? [];
                $viewSections = $metadata['sections'] ?? [];
            @endphp

            {{-- COMPACT HEADER (fees style) --}}
            <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:min-h-12">
                    <div class="flex items-center gap-3 min-w-0">
                        <img src="{{ asset('website-image/Group 11525.png') }}" alt="SUPERLMS"
                            class="w-12 h-12 rounded-xl object-contain border border-gray-200 shadow-sm bg-white p-1 flex-shrink-0">
                        <div class="min-w-0">
                            <h1 class="text-lg sm:text-xl font-bold text-gray-900 truncate">Privacy Policy</h1>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        @if ($privacyPolicy->last_updated)
                            <span class="text-xs text-gray-400">
                                Last updated {{ $privacyPolicy->last_updated->format('d M Y') }}
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

            {{-- The sections as a plain document, out of their cards: "1. Heading",
                 the text under it, and a hairline between one section and the next. --}}
            <div class="px-4 sm:px-6 py-6">
                <div class="divide-y divide-gray-200">
                    @php $shown = 0; @endphp
                    @foreach ($viewSections as $i => $section)
                        @if (!empty($section['head']) || !empty($section['desc']))
                            @php $shown++; @endphp
                            <section class="py-5 first:pt-0">
                                @if (!empty($section['head']))
                                    <h2 class="text-base font-semibold text-black break-words">
                                        <span class="tabular-nums mr-1">{{ $shown }}.</span>{{ $section['head'] }}
                                    </h2>
                                @endif
                                @if (!empty($section['desc']))
                                    <p class="text-sm text-black leading-relaxed break-words {{ !empty($section['head']) ? 'mt-2' : '' }}">
                                        {!! nl2br(e($section['desc'])) !!}
                                    </p>
                                @endif
                            </section>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    {{-- ══════════ DELETE SECTION CONFIRM MODAL ══════════ --}}
    @if ($pendingDeleteSectionIndex !== null)
        <div class="lms-cover fixed inset-0 flex items-center justify-center bg-black/40 backdrop-blur-sm z-[9999] px-4">
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

    {{-- ══════════ EDIT TAB ══════════
         Plain and minimal, in the view's look (partials/sections-editor). --}}
    @if ($activeTab === 'edit')
        @include('livewire.super-admin.partials.sections-editor', [
            'title'    => 'Privacy Policy',
            'exists'   => (bool) $privacyPolicy,
            'sections' => $sections,
            'cancel'   => "switchTab('view')",
            'headHint' => 'e.g. Information We Collect',
        ])
    @endif

</div>
