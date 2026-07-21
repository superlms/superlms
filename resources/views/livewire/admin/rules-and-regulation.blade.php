<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════
         TABS
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 px-6">
        <nav class="flex gap-1">
            <button wire:click="showTab('view')"
                class="py-3.5 px-5 text-sm font-semibold border-b-2 transition-colors
                       {{ $activeTab === 'view'
                           ? 'border-blue-500 text-blue-700'
                           : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                View Rules &amp; Regulations
            </button>
            <button wire:click="showTab('edit')"
                class="py-3.5 px-5 text-sm font-semibold border-b-2 transition-colors
                       {{ $activeTab === 'edit'
                           ? 'border-purple-500 text-purple-700'
                           : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                {{ $existingContent ? 'Edit' : 'Create' }} Rules &amp; Regulations
            </button>
        </nav>
    </div>

    {{-- ══════════════════════════════════════════════════
         VIEW TAB
    ══════════════════════════════════════════════════ --}}
    @if ($activeTab === 'view')
        @if (!$existingContent)
            {{-- Empty state --}}
            <div class="flex flex-col items-center justify-center min-h-[60vh] text-center px-4">
                <div class="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-5">
                    <svg class="w-10 h-10 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No Rules & Regulations Yet</h3>
                <p class="text-gray-400 text-sm mb-6">Create your school's rules and regulations to display them here.
                </p>
                <button wire:click="showTab('edit')"
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
                $content = $existingContent->content ?? [];
                $viewSections = $content['sections'] ?? [];
                $viewAddInfo = $content['additional_info'] ?? [];
                $viewFiles = $content['files'] ?? [];
            @endphp

            {{-- COMPACT HEADER (super-admin about-app style) --}}
            <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <x-admin.back-to-more />
                        <img src="{{ auth()->user()->organization && auth()->user()->organization->logo ? auth()->user()->organization->logo : asset('website-image/Group 11525.png') }}"
                            alt="Logo"
                            class="w-12 h-12 rounded-xl object-contain border border-gray-200 shadow-sm bg-white p-1 flex-shrink-0">
                        <div class="min-w-0">
                            <h1 class="text-lg sm:text-xl font-bold text-gray-900 truncate">Rules & Regulations</h1>
                            <p class="text-sm text-gray-500 mt-0.5 truncate">
                                Last Updated: {{ \Carbon\Carbon::parse($content['last_updated'])->format('d M Y, h:i A') }}
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2 items-center flex-shrink-0">
                        @if (count($viewSections) > 0)
                            <span
                                class="px-3 py-1 bg-red-50 text-red-700 text-xs font-semibold
                                         rounded-full border border-red-100">
                                {{ count($viewSections) }} {{ Str::plural('Rule', count($viewSections)) }}
                            </span>
                        @endif
                        <button wire:click="showTab('edit')"
                            class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium
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

            <div class="max-w-5xl mx-auto px-6 py-8 space-y-5">
                {{-- Rule Sections --}}
                @foreach ($viewSections as $i => $section)
                    @if (!empty($section['head']) || !empty($section['desc']))
                        <div id="rule-{{ $i + 1 }}"
                            class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden
                                    hover:border-red-200 hover:shadow-md transition-all duration-200">
                            @if (!empty($section['head']))
                                <div
                                    class="px-6 py-4 border-b border-gray-100 flex items-center gap-3
                                            bg-gradient-to-r from-red-50 to-orange-50">
                                    <span
                                        class="w-8 h-8 bg-gradient-to-br from-red-500 to-orange-500
                                                 text-white text-sm font-bold rounded-xl flex items-center
                                                 justify-center flex-shrink-0 shadow-sm">
                                        {{ $i + 1 }}
                                    </span>
                                    <h2 class="text-base font-semibold text-gray-900">{{ $section['head'] }}</h2>
                                </div>
                            @endif
                            @if (!empty($section['desc']))
                                <div class="px-6 py-5">
                                    <div class="text-sm text-gray-600 leading-relaxed">
                                        {!! nl2br(e($section['desc'])) !!}
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                @endforeach

                {{-- Additional Info --}}
                @if (count($viewAddInfo) > 0)
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                        <div
                            class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-purple-50 to-indigo-50
                                    flex items-center gap-3">
                            <div class="w-8 h-8 bg-purple-500 rounded-xl flex items-center justify-center shadow-sm">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h2 class="text-base font-semibold text-gray-900">Additional Information</h2>
                        </div>
                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach ($viewAddInfo as $info)
                                @if (!empty($info['key']) && !empty($info['value']))
                                    <div class="bg-purple-50 rounded-xl border border-purple-100 p-4">
                                        <p class="text-xs font-semibold text-purple-500 uppercase tracking-wide mb-1.5">
                                            {{ $info['key'] }}
                                        </p>
                                        <p class="text-sm text-gray-700 leading-relaxed">{{ $info['value'] }}</p>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Attached Documents --}}
                @if (count($viewFiles) > 0)
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                        <div
                            class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-cyan-50
                                    flex items-center gap-3">
                            <div class="w-8 h-8 bg-blue-500 rounded-xl flex items-center justify-center shadow-sm">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h2 class="text-base font-semibold text-gray-900">Attached Documents</h2>
                            <span class="ml-auto text-xs text-gray-400">{{ count($viewFiles) }} file(s)</span>
                        </div>
                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach ($viewFiles as $file)
                                @php
                                    $sizeBytes = $file['file_size'] ?? 0;
                                    $sizeMB = $sizeBytes > 0 ? round($sizeBytes / 1048576, 1) : null;
                                    $isLarge = $sizeBytes > 2 * 1048576; // > 2MB
                                @endphp
                                <div
                                    class="flex items-center justify-between p-4 bg-blue-50
                                            rounded-xl border border-blue-100 gap-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div
                                            class="w-10 h-10 bg-blue-100 rounded-xl flex items-center
                                                    justify-center flex-shrink-0">
                                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-800 truncate">
                                                {{ $file['title'] ?? 'Document' }}
                                            </p>
                                            <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                                                <span class="text-xs text-gray-400 uppercase">
                                                    {{ $file['file_type'] ?? 'PDF' }}
                                                </span>
                                                @if ($sizeMB !== null)
                                                    <span
                                                        class="text-xs px-1.5 py-0.5 rounded-full font-medium
                                                        {{ $isLarge ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700' }}">
                                                        {{ $sizeMB }} MB
                                                        @if ($isLarge)
                                                            ⚠ Large
                                                        @endif
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <a href="{{ $file['file_path'] ?? '#' }}" target="_blank"
                                        class="flex-shrink-0 inline-flex items-center gap-1 px-3 py-1.5
                                              bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold
                                              rounded-lg transition-colors shadow-sm">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                        View
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>{{-- end max-w-4xl --}}
        @endif
    @endif

    {{-- ══════════════════════════════════════════════════
         EDIT / CREATE TAB
    ══════════════════════════════════════════════════ --}}
    @if ($activeTab === 'edit')
        <div class="mx-auto px-6 py-8 space-y-6">

            {{-- ── Rule Sections ── --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-red-50 to-orange-50
                            flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-red-500 rounded-xl flex items-center justify-center shadow-sm">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-gray-900">Rules &amp; Regulation Sections</h2>
                            <p class="text-xs text-gray-400">{{ count($sections) }} section(s)</p>
                        </div>
                    </div>
                    <button wire:click="addSection"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-red-600 hover:bg-red-700
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
                        <div class="border border-gray-200 rounded-xl p-4 hover:border-red-200 transition-colors">
                            <div class="flex items-center justify-between mb-3">
                                <span class="flex items-center gap-2">
                                    <span
                                        class="w-6 h-6 bg-red-100 text-red-700 text-xs font-bold
                                                 rounded-full flex items-center justify-center">
                                        {{ $index + 1 }}
                                    </span>
                                    <span class="text-sm font-medium text-gray-700">Section {{ $index + 1 }}</span>
                                </span>
                                @if (count($sections) > 1)
                                    <button wire:click="removeSection({{ $index }})"
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
                                        placeholder="e.g., Attendance Rules"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                                  focus:ring-2 focus:ring-red-400 focus:border-red-400">
                                    @error("sections.{$index}.head")
                                        <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Description *</label>
                                    <textarea wire:model="sections.{{ $index }}.desc" rows="4"
                                        placeholder="Detailed description of the rule..."
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                                     focus:ring-2 focus:ring-red-400 focus:border-red-400"></textarea>
                                    @error("sections.{$index}.desc")
                                        <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ── Additional Information ── --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-purple-50 to-indigo-50
                            flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-purple-500 rounded-xl flex items-center justify-center shadow-sm">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h2 class="text-base font-semibold text-gray-900">Additional Information</h2>
                    </div>
                    <button wire:click="addAdditionalInfo"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-purple-600 hover:bg-purple-700
                               text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v16m8-8H4" />
                        </svg>
                        Add Info
                    </button>
                </div>
                <div class="p-6 space-y-3">
                    @forelse($additionalInfo as $index => $info)
                        <div
                            class="grid grid-cols-1 md:grid-cols-3 gap-3 items-start
                                    border border-gray-200 rounded-xl p-4 hover:border-purple-200 transition-colors">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Key</label>
                                <input type="text" wire:model="additionalInfo.{{ $index }}.key"
                                    placeholder="e.g., Fees, Contact"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                              focus:ring-2 focus:ring-purple-400 focus:border-purple-400">
                            </div>
                            <div class="md:col-span-2">
                                <div class="flex justify-between items-center mb-1">
                                    <label class="block text-xs font-medium text-gray-600">Value</label>
                                    <button wire:click="removeAdditionalInfo({{ $index }})"
                                        class="p-1 text-red-500 hover:bg-red-50 rounded transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <textarea wire:model="additionalInfo.{{ $index }}.value" rows="2" placeholder="Detailed information..."
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                                 focus:ring-2 focus:ring-purple-400 focus:border-purple-400"></textarea>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 text-center py-4">No additional info added yet.</p>
                    @endforelse
                </div>
            </div>

            {{-- ── Documents ── --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-cyan-50
                            flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-blue-500 rounded-xl flex items-center justify-center shadow-sm">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h2 class="text-base font-semibold text-gray-900">Attached Documents</h2>
                    </div>
                    <button wire:click="addFileField"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-blue-600 hover:bg-blue-700
                               text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v16m8-8H4" />
                        </svg>
                        Add Document
                    </button>
                </div>

                <div class="p-6 space-y-4">

                    {{-- Already saved files --}}
                    @php
                        $savedFiles = $existingContent ? $existingContent->content['files'] ?? [] : [];
                    @endphp
                    @if (count($savedFiles) > 0)
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">
                                Saved Documents
                            </p>
                            <div class="space-y-2">
                                @foreach ($savedFiles as $fi => $file)
                                    @php
                                        $sizeBytes = $file['file_size'] ?? 0;
                                        $sizeMB = $sizeBytes > 0 ? round($sizeBytes / 1048576, 1) : null;
                                        $isLarge = $sizeBytes > 2 * 1048576;
                                    @endphp
                                    <div
                                        class="flex items-center justify-between p-3 bg-blue-50
                                                rounded-xl border border-blue-100 gap-3">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div
                                                class="w-8 h-8 bg-blue-100 rounded-lg flex items-center
                                                        justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-blue-600" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-gray-800 truncate">
                                                    {{ $file['title'] ?? 'Document' }}
                                                </p>
                                                <div class="flex items-center gap-2 flex-wrap mt-0.5">
                                                    <span class="text-xs text-gray-400 uppercase">
                                                        {{ $file['file_type'] ?? 'PDF' }}
                                                    </span>
                                                    @if ($sizeMB !== null)
                                                        <span
                                                            class="text-xs px-1.5 py-0.5 rounded-full font-medium
                                                            {{ $isLarge ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700' }}">
                                                            {{ $sizeMB }} MB
                                                            @if ($isLarge)
                                                                ⚠ Large
                                                            @endif
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 flex-shrink-0">
                                            <a href="{{ $file['file_path'] ?? '#' }}" target="_blank"
                                                class="p-1.5 rounded-lg border border-blue-200 text-blue-600
                                                      hover:bg-blue-100 transition-colors"
                                                title="View">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </a>
                                            <button wire:click="removeExistingFile({{ $fi }})"
                                                class="p-1.5 rounded-lg border border-red-200 text-red-500
                                                       hover:bg-red-50 transition-colors"
                                                title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- New file uploads --}}
                    @if (count($files) > 0)
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">
                                New Files to Upload
                            </p>
                            <div class="space-y-3">
                                @foreach ($files as $index => $file)
                                    <div
                                        class="border border-gray-200 rounded-xl p-4 bg-yellow-50
                                                border-yellow-200 hover:border-yellow-300 transition-colors">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                                    Document Title *
                                                </label>
                                                <input type="text" wire:model="fileTitles.{{ $index }}"
                                                    placeholder="e.g., Fee Structure PDF"
                                                    class="w-full border border-gray-300 rounded-lg px-3 py-2
                                                              text-sm focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                                                @error("fileTitles.{$index}")
                                                    <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            <div>
                                                <div class="flex justify-between items-center mb-1">
                                                    <label class="block text-xs font-medium text-gray-600">
                                                        PDF File (max 10MB)
                                                    </label>
                                                    <button wire:click="removeFileField({{ $index }})"
                                                        class="text-xs text-red-600 hover:text-red-800 transition-colors">
                                                        Remove
                                                    </button>
                                                </div>
                                                <input type="file" wire:model="files.{{ $index }}"
                                                    accept=".pdf"
                                                    class="w-full border border-gray-300 rounded-lg px-3 py-1.5
                                                              text-sm focus:ring-2 focus:ring-blue-400 bg-white">
                                                @error("files.{$index}")
                                                    <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </div>

                                        {{-- Upload progress --}}
                                        <div wire:loading wire:target="files.{{ $index }}"
                                            class="flex items-center gap-1.5 text-xs text-blue-600 mt-2">
                                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                                    stroke="currentColor" stroke-width="4" />
                                                <path class="opacity-75" fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                            </svg>
                                            Uploading...
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (count($savedFiles) === 0 && count($files) === 0)
                        <p class="text-sm text-gray-400 text-center py-4">
                            No documents attached. Click "Add Document" to upload PDFs.
                        </p>
                    @endif
                </div>
            </div>

            {{-- ── Save Button ── --}}
            <div class="flex justify-end pb-6">
                <button wire:click="saveContent"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-600
                           to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-semibold
                           rounded-xl shadow-md hover:shadow-lg transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Save Rules &amp; Regulations
                </button>
            </div>

        </div>{{-- end max-w-4xl --}}
    @endif

</div>
