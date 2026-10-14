<div class="min-h-screen bg-gray-50">

    @php
        $saved      = $existingContent->content ?? [];
        $headRules  = count($saved['sections'] ?? []);
        $savedFiles = $saved['files'] ?? [];
        $savedOn    = !empty($saved['last_updated'])
            ? \Illuminate\Support\Carbon::parse($saved['last_updated'])->format('d M Y')
            : $existingContent?->updated_at?->format('d M Y');

        // The Super Admin pages' look: white cards on the grey page, a quiet
        // header row, a row per entry, the Students list's icons.
        $rCard = 'bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden';
        $rHead = 'px-5 py-3.5 border-b border-gray-100 flex items-center justify-between gap-3';
        $rTh   = 'px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider';
        $rThC  = 'px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider';
        $rIn   = 'w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm bg-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500';
        $rAdd  = 'inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 hover:text-blue-800 flex-shrink-0';
        $rDel  = 'p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors';
        $rView = 'p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors';
        $plus  = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>';
        $bin   = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>';
        $eye   = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>';
        $mb    = fn ($bytes) => $bytes > 0 ? round($bytes / 1048576, 1) . ' MB' : '—';
    @endphp

    {{-- ══════════════════════════════════════════════════
         HEADING, then the TABS under it — the Super Admin header's look: the
         name with a plain line beside it; Last updated + Edit on the view,
         Cancel / Save while editing
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200">
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:min-h-9">
                <div class="flex items-center gap-3 min-w-0">
                    <x-admin.back-to-more />
                    <div class="min-w-0 sm:flex sm:items-baseline sm:gap-2">
                        <h1 class="min-w-0 text-lg sm:text-xl font-bold text-gray-900 truncate">Rules & Regulations</h1>
                        @if ($activeTab === 'edit')
                            <p class="text-xs text-gray-400 mt-0.5 sm:mt-0 truncate">
                                {{ count($sections) }} {{ count($sections) === 1 ? 'section' : 'sections' }} · published for your school with Save
                            </p>
                        @elseif ($existingContent && $headRules > 0)
                            <p class="text-xs text-gray-400 mt-0.5 sm:mt-0 truncate">{{ $headRules }} {{ Str::plural('rule', $headRules) }}</p>
                        @endif
                    </div>
                </div>
                @if ($activeTab === 'view' && $existingContent)
                    <div class="flex items-center gap-3 flex-shrink-0">
                        @if ($savedOn)
                            <span class="text-xs text-gray-400">Last updated {{ $savedOn }}</span>
                        @endif
                        <button wire:click="showTab('edit')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium
                                   text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit
                        </button>
                    </div>
                @elseif ($activeTab === 'edit')
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button type="button" wire:click="showTab('view')"
                            class="flex-1 sm:flex-none px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                        <button type="button" wire:click="saveContent" wire:loading.attr="disabled" wire:target="saveContent"
                            class="flex-1 sm:flex-none px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md disabled:opacity-60">
                            <span wire:loading.remove wire:target="saveContent">Save</span>
                            <span wire:loading wire:target="saveContent">Saving…</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>
        <nav class="border-t border-gray-200 px-3 sm:px-6 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            <div class="flex gap-1 min-w-max">
                <button wire:click="showTab('view')"
                    class="py-3 sm:py-3.5 px-3 sm:px-5 text-sm font-semibold border-b-2 transition-colors whitespace-nowrap
                           {{ $activeTab === 'view'
                               ? 'border-blue-500 text-blue-700'
                               : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    View Rules &amp; Regulations
                </button>
                <button wire:click="showTab('edit')"
                    class="py-3 sm:py-3.5 px-3 sm:px-5 text-sm font-semibold border-b-2 transition-colors whitespace-nowrap
                           {{ $activeTab === 'edit'
                               ? 'border-purple-500 text-purple-700'
                               : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    {{ $existingContent ? 'Edit' : 'Create' }} Rules &amp; Regulations
                </button>
            </div>
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
                $viewAddInfo = array_values(array_filter($saved['additional_info'] ?? [], fn ($info) => !empty($info['key']) && !empty($info['value'])));
            @endphp

            <div class="p-4 sm:p-6 space-y-5">

                {{-- The rules in the Super Admin view's look: S.No and the rule,
                     its heading in bold and the text under it. --}}
                @include('livewire.super-admin.partials.sections-view', ['sections' => $saved['sections'] ?? [], 'title' => 'Rules & Regulations'])

                {{-- Additional Information --}}
                @if (count($viewAddInfo) > 0)
                    <div class="{{ $rCard }}">
                        <div class="{{ $rHead }}">
                            <h2 class="text-sm font-semibold text-gray-800">Additional Information</h2>
                            <span class="text-xs text-gray-400">{{ count($viewAddInfo) }} {{ count($viewAddInfo) === 1 ? 'entry' : 'entries' }}</span>
                        </div>
                        <table class="w-full table-fixed min-w-0!">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="{{ $rTh }} w-32 sm:w-48">Key</th>
                                    <th class="{{ $rTh }}">Value</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($viewAddInfo as $info)
                                    <tr class="hover:bg-gray-50/60 transition-colors align-top">
                                        <td class="px-4 py-3 text-sm text-gray-500 break-words">{{ $info['key'] }}</td>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900 leading-relaxed break-words">{{ $info['value'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- Attached Documents --}}
                @if (count($savedFiles) > 0)
                    <div class="{{ $rCard }}">
                        <div class="{{ $rHead }}">
                            <h2 class="text-sm font-semibold text-gray-800">Attached Documents</h2>
                            <span class="text-xs text-gray-400">{{ count($savedFiles) }} file(s)</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[560px] table-fixed">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="{{ $rTh }} w-16">S.No</th>
                                        <th class="{{ $rTh }}">Document</th>
                                        <th class="{{ $rTh }} w-32">Size</th>
                                        <th class="{{ $rThC }} w-24">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($savedFiles as $fi => $file)
                                        @php $isLarge = ($file['file_size'] ?? 0) > 2 * 1048576; @endphp
                                        <tr class="hover:bg-gray-50/60 transition-colors">
                                            <td class="px-4 py-3 text-sm text-gray-500 font-medium">{{ $fi + 1 }}</td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <span class="w-9 h-9 rounded-lg bg-red-50 text-red-600 flex flex-col items-center justify-center flex-shrink-0">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                        <span class="text-[8px] font-bold leading-none mt-0.5 uppercase">{{ $file['file_type'] ?? 'PDF' }}</span>
                                                    </span>
                                                    <span class="text-sm font-semibold text-gray-900 truncate" title="{{ $file['title'] ?? '' }}">{{ $file['title'] ?? 'Document' }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-sm {{ $isLarge ? 'text-amber-600' : 'text-gray-500' }}">
                                                {{ $mb($file['file_size'] ?? 0) }}{{ $isLarge ? ' · Large' : '' }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center justify-center">
                                                    <a href="{{ $file['file_path'] ?? '#' }}" target="_blank" rel="noopener noreferrer" title="View" class="{{ $rView }}">{!! $eye !!}</a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

            </div>
        @endif
    @endif

    {{-- ══════════════════════════════════════════════════
         EDIT / CREATE TAB — the Super Admin edit pages' look: each part a
         white card, a row per entry, the Students list's icons; same fields,
         adds, removes and save
    ══════════════════════════════════════════════════ --}}
    @if ($activeTab === 'edit')
        <div class="p-4 sm:p-6 space-y-5">

            {{-- ── Rule Sections ── --}}
            <div class="{{ $rCard }}">
                <div class="{{ $rHead }}">
                    <h2 class="text-sm font-semibold text-gray-800">Rules &amp; Regulation Sections</h2>
                    <span class="text-xs text-gray-400">{{ count($sections) }} {{ count($sections) === 1 ? 'section' : 'sections' }}</span>
                </div>
                @if ($usingDefaults)
                    <p class="px-5 py-3 border-b border-gray-100 text-xs text-amber-700 leading-relaxed">
                        These are the standard school rules, loaded for you to start from. Edit or remove
                        whichever you like and press <strong>Save</strong> to publish them as your school's rules.
                    </p>
                @endif
                <div class="overflow-x-auto">
                    {{-- Fits a phone (min-w-0! beats app.css's 640px phone floor for tables). --}}
                    <table class="w-full table-fixed min-w-0!">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-3 sm:px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-14 sm:w-16">S.No</th>
                                <th class="px-3 sm:px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Heading &amp; Text</th>
                                <th class="px-1 sm:px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-16 sm:w-20">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($sections as $index => $section)
                                <tr class="align-top" wire:key="rule-section-{{ $index }}">
                                    <td class="px-3 sm:px-4 pb-4 pt-6 text-sm text-gray-500 font-medium tabular-nums">{{ $index + 1 }}</td>
                                    <td class="px-3 sm:px-4 py-4 space-y-2">
                                        <input type="text" wire:model="sections.{{ $index }}.head" placeholder="e.g., Attendance Rules" aria-label="Heading {{ $index + 1 }}"
                                            class="{{ $rIn }} font-medium text-gray-900 @error("sections.{$index}.head") border-red-400 @enderror">
                                        @error("sections.{$index}.head")
                                            <p class="text-xs text-red-500">{{ $message }}</p>
                                        @enderror
                                        <textarea wire:model="sections.{{ $index }}.desc" rows="4" placeholder="Detailed description of the rule..." aria-label="Text {{ $index + 1 }}"
                                            class="{{ $rIn }} text-gray-800 leading-relaxed resize-y @error("sections.{$index}.desc") border-red-400 @enderror"></textarea>
                                        @error("sections.{$index}.desc")
                                            <p class="text-xs text-red-500">{{ $message }}</p>
                                        @enderror
                                    </td>
                                    <td class="px-1 sm:px-4 pb-4 pt-5">
                                        <div class="flex items-center justify-center">
                                            @if (count($sections) > 1)
                                                <button type="button" wire:click="removeSection({{ $index }})" title="Delete section" class="{{ $rDel }}">{!! $bin !!}</button>
                                            @else
                                                <span class="text-xs text-gray-300">—</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-3.5 border-t border-gray-100">
                    <button type="button" wire:click="addSection" class="{{ $rAdd }}">{!! $plus !!} Add section</button>
                </div>
            </div>

            {{-- ── Additional Information ── --}}
            <div class="{{ $rCard }}">
                <div class="{{ $rHead }}">
                    <h2 class="text-sm font-semibold text-gray-800">Additional Information</h2>
                    <button type="button" wire:click="addAdditionalInfo" class="{{ $rAdd }}">{!! $plus !!} Add info</button>
                </div>
                @if (count($additionalInfo))
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[560px] table-fixed">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="{{ $rTh }} w-16">S.No</th>
                                    <th class="{{ $rTh }} w-56">Key</th>
                                    <th class="{{ $rTh }}">Value</th>
                                    <th class="{{ $rThC }} w-20">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($additionalInfo as $index => $info)
                                    <tr class="align-top" wire:key="rule-info-{{ $index }}">
                                        <td class="px-4 pb-4 pt-6 text-sm text-gray-500 font-medium tabular-nums">{{ $index + 1 }}</td>
                                        <td class="px-4 py-4">
                                            <input type="text" wire:model="additionalInfo.{{ $index }}.key" placeholder="e.g., Fees, Contact" aria-label="Key {{ $index + 1 }}" class="{{ $rIn }}">
                                        </td>
                                        <td class="px-4 py-4">
                                            <textarea wire:model="additionalInfo.{{ $index }}.value" rows="2" placeholder="Detailed information..." aria-label="Value {{ $index + 1 }}" class="{{ $rIn }} resize-y"></textarea>
                                        </td>
                                        <td class="px-4 pb-4 pt-5">
                                            <div class="flex items-center justify-center">
                                                <button type="button" wire:click="removeAdditionalInfo({{ $index }})" title="Delete" class="{{ $rDel }}">{!! $bin !!}</button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="px-5 py-8 text-center text-sm text-gray-400">No additional info added yet.</p>
                @endif
            </div>

            {{-- ── Attached Documents ── --}}
            <div class="{{ $rCard }}">
                <div class="{{ $rHead }}">
                    <div class="min-w-0 sm:flex sm:items-baseline sm:gap-2">
                        <h2 class="text-sm font-semibold text-gray-800">Attached Documents</h2>
                        <p class="text-xs text-gray-400">PDF · up to 2 MB each</p>
                    </div>
                    <button type="button" wire:click="addFileField" class="{{ $rAdd }}">{!! $plus !!} Add document</button>
                </div>

                @if (count($savedFiles) === 0 && count($files) === 0)
                    <p class="px-5 py-8 text-center text-sm text-gray-400">No documents attached. Click "Add document" to upload PDFs.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[620px] table-fixed">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="{{ $rTh }} w-16">S.No</th>
                                    <th class="{{ $rTh }}">Document</th>
                                    <th class="{{ $rTh }} w-64">Type · Size / File</th>
                                    <th class="{{ $rThC }} w-24">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                {{-- Already saved (Delete takes it off at once, as before) --}}
                                @foreach ($savedFiles as $fi => $file)
                                    @php $isLarge = ($file['file_size'] ?? 0) > 2 * 1048576; @endphp
                                    <tr class="hover:bg-gray-50/60 transition-colors" wire:key="rule-file-{{ $fi }}">
                                        <td class="px-4 py-3 text-sm text-gray-500 font-medium">{{ $fi + 1 }}</td>
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-900 truncate" title="{{ $file['title'] ?? '' }}">{{ $file['title'] ?? 'Document' }}</td>
                                        <td class="px-4 py-3 text-sm whitespace-nowrap {{ $isLarge ? 'text-amber-600' : 'text-gray-500' }}">
                                            {{ strtoupper($file['file_type'] ?? 'PDF') }} · {{ $mb($file['file_size'] ?? 0) }}{{ $isLarge ? ' · Large' : '' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-center gap-1">
                                                <a href="{{ $file['file_path'] ?? '#' }}" target="_blank" rel="noopener noreferrer" title="View" class="{{ $rView }}">{!! $eye !!}</a>
                                                <button type="button" wire:click="removeExistingFile({{ $fi }})" title="Delete" class="{{ $rDel }}">{!! $bin !!}</button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                                {{-- New, uploaded with Save --}}
                                @foreach ($files as $index => $file)
                                    <tr class="align-top bg-amber-50/40" wire:key="rule-new-file-{{ $index }}">
                                        <td class="px-4 pb-4 pt-6 text-sm text-gray-500 font-medium tabular-nums">{{ count($savedFiles) + $index + 1 }}</td>
                                        <td class="px-4 py-4">
                                            <input type="text" wire:model="fileTitles.{{ $index }}" placeholder="Document title, e.g. Fee Structure PDF" aria-label="Document title {{ $index + 1 }}"
                                                class="{{ $rIn }} @error("fileTitles.{$index}") border-red-400 @enderror">
                                            @error("fileTitles.{$index}")
                                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                            @enderror
                                        </td>
                                        <td class="px-4 py-4">
                                            <input type="file" wire:model="files.{{ $index }}" accept=".pdf" aria-label="PDF file {{ $index + 1 }}"
                                                class="w-full min-w-0 text-sm text-gray-700">
                                            @error("files.{$index}")
                                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                            @enderror
                                            <p wire:loading wire:target="files.{{ $index }}" class="mt-1 text-xs text-blue-600">Uploading…</p>
                                        </td>
                                        <td class="px-4 pb-4 pt-5">
                                            <div class="flex items-center justify-center">
                                                <button type="button" wire:click="removeFileField({{ $index }})" title="Remove" class="{{ $rDel }}">{!! $bin !!}</button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- ── Save ── --}}
            <div class="flex justify-end">
                <button type="button" wire:click="saveContent" wire:loading.attr="disabled" wire:target="saveContent"
                    class="w-full sm:w-auto px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md disabled:opacity-60">
                    Save Rules &amp; Regulations
                </button>
            </div>

        </div>
    @endif

</div>
