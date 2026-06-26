<div class="min-h-screen bg-gray-50" x-data="{ expandedChapters: @entangle('expandedChapters').live }">

{{-- ══════════════════════════════════════════════════
     HEADER + FILTER BAR (exams-style)
══════════════════════════════════════════════════ --}}
<div class="bg-white border-b border-gray-200 sticky top-0 z-30">
    <div class="px-4 sm:px-6 py-4 sm:py-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">MCQ Management</h1>
                <p class="text-sm text-gray-500 mt-0.5">Manage chapter & topic questions</p>
            </div>
            <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200">
                <span class="pr-4">Questions: <strong class="text-blue-600">{{ $totalQuestions }}</strong></span>
            </div>
        </div>
    </div>

    {{-- Filter bar (exams-style) --}}
    <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filter by:
            </div>
            <select wire:model.live="filterStandard"
                class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[120px]">
                <option value="">Select Class</option>
                @foreach ($standards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
            </select>
            <select wire:model.live="filterSection" @disabled(!$filterStandard)
                class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed min-w-[140px]">
                <option value="">Section (optional)</option>
                @foreach ($filterSections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
            </select>
            <select wire:model.live="filterSubject" @disabled(!$filterStandard)
                class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed min-w-[140px]">
                <option value="">Select Subject</option>
                @foreach ($filterSubjects as $subj)<option value="{{ $subj->id }}">{{ $subj->name }}</option>@endforeach
            </select>

            @if ($filterStandard || $filterSection || $filterSubject)
                <button wire:click="clearFilters"
                    class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Clear
                </button>
            @endif
        </div>
    </div>
</div>

<div class="p-4 sm:p-6 space-y-4 sm:space-y-5">

@if (!$showList)
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center">
        <div class="w-14 h-14 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-3">
            <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
        </div>
        <p class="text-sm text-gray-600 font-medium">Pick a Class and Subject to manage MCQs.</p>
        <p class="text-xs text-gray-400 mt-1">Section filter is optional.</p>
    </div>
@else
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="divide-y divide-gray-200">
            @forelse ($chapters as $chapter)
                @php $chMcqCount = $chapterMcqCounts[$chapter->id] ?? 0; @endphp
                <div>
                    <div class="px-4 sm:px-6 py-3 flex items-center justify-between hover:bg-gray-50 transition cursor-pointer"
                        @click="expandedChapters.includes({{ $chapter->id }}) ? expandedChapters = expandedChapters.filter(i => i !== {{ $chapter->id }}) : expandedChapters = [...expandedChapters, {{ $chapter->id }}]">
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <svg class="w-4 h-4 text-gray-500 transition-transform duration-200 flex-shrink-0"
                                :class="{ 'rotate-90': expandedChapters.includes({{ $chapter->id }}) }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            <span class="text-xs font-medium text-gray-500 px-1.5 py-0.5 bg-blue-100 rounded flex-shrink-0">Ch {{ $chapter->order }}</span>
                            <h3 class="text-sm font-semibold text-gray-900 truncate">{{ $chapter->name }}</h3>
                            <span class="text-xs text-gray-400 flex-shrink-0">{{ $chapter->topics->count() }} topics</span>
                            @if ($chMcqCount > 0)
                                <span class="text-xs px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded font-medium flex-shrink-0">{{ $chMcqCount }} MCQ</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-1 ml-2 flex-shrink-0" @click.stop>
                            @if ($chMcqCount > 0)
                                <button wire:click="onViewMcq('chapter', {{ $chapter->id }})"
                                    class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                                <button wire:click="onEditMcq('chapter', {{ $chapter->id }})"
                                    class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button wire:click="onDeleteMcq('chapter', {{ $chapter->id }})"
                                    class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg" title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            @else
                                <button wire:click="onAddMcq('chapter', {{ $chapter->id }})"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg border border-blue-200">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Add MCQ
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Topics --}}
                    <div x-show="expandedChapters.includes({{ $chapter->id }})" x-collapse class="bg-gray-50">
                        @forelse ($chapter->topics as $topic)
                            @php $tpMcqCount = $topicMcqCounts[$topic->id] ?? 0; @endphp
                            <div class="px-8 sm:px-12 py-2.5 flex items-center justify-between hover:bg-white border-b border-gray-100 last:border-b-0 transition">
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full flex-shrink-0"></span>
                                    <span class="text-sm text-gray-800 truncate">{{ $topic->topic_name }}</span>
                                    @if ($tpMcqCount > 0)
                                        <span class="text-xs px-1.5 py-0.5 bg-emerald-100 text-emerald-700 rounded font-medium flex-shrink-0">{{ $tpMcqCount }} MCQ</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1 ml-2 flex-shrink-0">
                                    @if ($tpMcqCount > 0)
                                        <button wire:click="onViewMcq('topic', {{ $topic->id }})"
                                            class="p-1 text-blue-600 hover:bg-blue-50 rounded-lg" title="View">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                        <button wire:click="onEditMcq('topic', {{ $topic->id }})"
                                            class="p-1 text-emerald-600 hover:bg-emerald-50 rounded-lg" title="Edit">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button wire:click="onDeleteMcq('topic', {{ $topic->id }})"
                                            class="p-1 text-red-600 hover:bg-red-50 rounded-lg" title="Delete">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    @else
                                        <button wire:click="onAddMcq('topic', {{ $topic->id }})"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-200">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            Add MCQ
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="px-12 py-4 text-center text-xs text-gray-400">No topics</div>
                        @endforelse
                    </div>
                </div>
            @empty
                <div class="px-6 py-16 text-center">
                    <p class="text-gray-500 text-sm">No chapters found for this selection</p>
                </div>
            @endforelse
        </div>
    </div>
@endif
</div>

{{-- ═══════════════════════════════════════════════
     ADD MCQ SLIDE-IN
═══════════════════════════════════════════════ --}}
@if ($openAddModal)
<div class="fixed inset-0 z-50 overflow-hidden">
    <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeAddModal"></div>
    <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Add MCQs</h2>
                <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $addTargetName }}</p>
            </div>
            <button wire:click="closeAddModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700">Questions</h3>
                <button wire:click="addMcqRow" type="button"
                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-md border border-blue-200">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Question
                </button>
            </div>

            @foreach ($mcqRows as $qi => $row)
                <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-400 uppercase">Question {{ $qi + 1 }}</span>
                        @if (count($mcqRows) > 1)
                            <button wire:click="removeMcqRow({{ $qi }})" type="button"
                                class="p-1 text-red-400 hover:text-red-600 rounded-md">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div class="sm:col-span-3">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Question *</label>
                            <input type="text" wire:model="mcqRows.{{ $qi }}.question_text" placeholder="Enter question..."
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Time (sec)</label>
                            <input type="number" wire:model="mcqRows.{{ $qi }}.time_limit" min="5" max="300"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach ($row['options'] as $oi => $opt)
                            <div class="flex items-center gap-2">
                                <button wire:click="setCorrectOption({{ $qi }}, {{ $oi }})" type="button"
                                    class="flex-shrink-0 w-6 h-6 rounded-full border-2 flex items-center justify-center transition-colors
                                        {{ $opt['is_correct'] ? 'border-emerald-500 bg-emerald-500' : 'border-gray-300 hover:border-emerald-400' }}">
                                    @if ($opt['is_correct'])
                                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    @endif
                                </button>
                                <input type="text" wire:model="mcqRows.{{ $qi }}.options.{{ $oi }}.text" placeholder="Option {{ $oi + 1 }}"
                                    class="flex-1 px-3 py-1.5 text-sm border rounded-md focus:ring-1 focus:ring-blue-500
                                        {{ $opt['is_correct'] ? 'border-emerald-300 bg-emerald-50' : 'border-gray-300' }}">
                            </div>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-400">Click the circle to mark the correct answer</p>
                </div>
            @endforeach
        </div>

        <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
            <button wire:click="closeAddModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
            <button wire:click="onSaveMcqs" wire:loading.attr="disabled"
                class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                <span wire:loading.remove wire:target="onSaveMcqs">Save MCQs</span>
                <span wire:loading wire:target="onSaveMcqs">Saving…</span>
            </button>
        </div>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════
     EDIT MCQ SLIDE-IN
═══════════════════════════════════════════════ --}}
@if ($openEditModal)
<div class="fixed inset-0 z-50 overflow-hidden">
    <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeEditModal"></div>
    <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Edit MCQs</h2>
                <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $editTargetName }}</p>
            </div>
            <button wire:click="closeEditModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
            @forelse ($editMcqs as $qi => $mcq)
                <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 space-y-3">
                    <span class="text-xs font-bold text-gray-400 uppercase">Question {{ $qi + 1 }}</span>

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div class="sm:col-span-3">
                            <input type="text" wire:model="editMcqs.{{ $qi }}.question_text" placeholder="Question..."
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-blue-500">
                        </div>
                        <div>
                            <input type="number" wire:model="editMcqs.{{ $qi }}.time_limit" min="5"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach ($mcq['options'] as $oi => $opt)
                            <div class="flex items-center gap-2">
                                <button wire:click="setEditCorrectOption({{ $qi }}, {{ $oi }})" type="button"
                                    class="flex-shrink-0 w-6 h-6 rounded-full border-2 flex items-center justify-center transition-colors
                                        {{ $opt['is_correct'] ? 'border-emerald-500 bg-emerald-500' : 'border-gray-300 hover:border-emerald-400' }}">
                                    @if ($opt['is_correct'])
                                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    @endif
                                </button>
                                <input type="text" wire:model="editMcqs.{{ $qi }}.options.{{ $oi }}.text"
                                    class="flex-1 px-3 py-1.5 text-sm border rounded-md focus:ring-1 focus:ring-blue-500
                                        {{ $opt['is_correct'] ? 'border-emerald-300 bg-emerald-50' : 'border-gray-300' }}">
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-sm text-gray-400">No MCQs to edit.</div>
            @endforelse
        </div>

        <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
            <button wire:click="closeEditModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
            <button wire:click="onUpdateMcqs" wire:loading.attr="disabled"
                class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                <span wire:loading.remove wire:target="onUpdateMcqs">Update MCQs</span>
                <span wire:loading wire:target="onUpdateMcqs">Saving…</span>
            </button>
        </div>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════
     VIEW MCQ MODAL (Swipeable) — unchanged
═══════════════════════════════════════════════ --}}
@if ($openViewModal)
    <div class="fixed inset-0 z-[60] overflow-y-auto" aria-modal="true" x-data="{ slide: 0, total: {{ count($viewMcqs) }} }"
        x-init="slide = 0" @keydown.right.window="slide = Math.min(total - 1, slide + 1)"
        @keydown.left.window="slide = Math.max(0, slide - 1)">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" wire:click="closeViewModal"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-auto z-10 overflow-hidden">

                <div class="flex items-center justify-between px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">
                    <div>
                        <h2 class="text-base font-bold text-gray-900">{{ $viewTargetName }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5" x-text="'Question ' + (slide + 1) + ' of ' + total"></p>
                    </div>
                    <button wire:click="closeViewModal"
                        class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-white/60 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="h-1 bg-gray-100">
                    <div class="h-1 bg-blue-500 transition-all duration-300 rounded-r" :style="'width: ' + ((slide + 1) / total * 100) + '%'"></div>
                </div>

                <div class="px-6 py-6 min-h-[320px]">
                    @foreach ($viewMcqs as $qi => $mcq)
                        <div x-show="slide === {{ $qi }}"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 translate-x-8"
                            x-transition:enter-end="opacity-100 translate-x-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

                            <div class="flex items-center gap-2 mb-4">
                                <span class="text-xs font-bold text-white bg-blue-600 px-2.5 py-1 rounded-lg">Q{{ $qi + 1 }}</span>
                                @if ($mcq['time_limit'])
                                    <span class="inline-flex items-center gap-1 text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-lg">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ $mcq['time_limit'] }}s
                                    </span>
                                @endif
                            </div>

                            <h3 class="text-base font-semibold text-gray-900 mb-5 leading-relaxed">{{ $mcq['question_text'] }}</h3>

                            <div class="space-y-2.5">
                                @foreach ($mcq['options'] as $oi => $opt)
                                    <div class="flex items-center gap-3 px-4 py-3 rounded-xl border-2 transition-all
                                        {{ $opt['is_correct'] ? 'border-emerald-400 bg-emerald-50 shadow-sm shadow-emerald-100' : 'border-gray-200 bg-white' }}">
                                        <span class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold
                                            {{ $opt['is_correct'] ? 'bg-emerald-500 text-white' : 'bg-gray-100 text-gray-500' }}">
                                            {{ chr(65 + $oi) }}
                                        </span>
                                        <span class="text-sm flex-1 {{ $opt['is_correct'] ? 'text-emerald-800 font-semibold' : 'text-gray-700' }}">
                                            {{ $opt['text'] }}
                                        </span>
                                        @if ($opt['is_correct'])
                                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                    <button @click="slide = Math.max(0, slide - 1)" :disabled="slide === 0"
                        class="inline-flex items-center gap-1 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Prev
                    </button>
                    <div class="flex items-center gap-1.5 max-w-[200px] overflow-x-auto px-2">
                        @foreach ($viewMcqs as $qi => $mcq)
                            <button @click="slide = {{ $qi }}"
                                class="flex-shrink-0 w-2.5 h-2.5 rounded-full transition-all duration-200"
                                :class="slide === {{ $qi }} ? 'bg-blue-600 scale-125' : 'bg-gray-300 hover:bg-gray-400'"></button>
                        @endforeach
                    </div>
                    <button @click="slide = Math.min(total - 1, slide + 1)" :disabled="slide === total - 1"
                        class="inline-flex items-center gap-1 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition">
                        Next
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════
     DELETE MCQ SLIDE-IN (Select from list)
═══════════════════════════════════════════════ --}}
@if ($openDeleteModal)
<div class="fixed inset-0 z-50 overflow-hidden">
    <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeDeleteModal"></div>
    <div class="absolute top-0 right-0 bottom-0 w-full max-w-2xl bg-white shadow-2xl flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Delete MCQs</h2>
                <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $deleteTargetName }}</p>
            </div>
            <button wire:click="closeDeleteModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-6 py-6 space-y-3">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm text-gray-600">Select MCQs to delete:</p>
                <button wire:click="selectAllForDelete" type="button"
                    class="text-xs text-blue-600 hover:text-blue-800 font-medium">Select All</button>
            </div>

            @forelse ($deleteMcqList as $mcq)
                <div class="flex items-start gap-3 p-3 rounded-lg border transition-colors cursor-pointer
                    {{ in_array($mcq['id'], $selectedDeleteIds) ? 'border-red-300 bg-red-50' : 'border-gray-200 bg-white hover:bg-gray-50' }}"
                    wire:click="toggleDeleteSelect({{ $mcq['id'] }})">
                    <div class="flex-shrink-0 mt-0.5">
                        <div class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors
                            {{ in_array($mcq['id'], $selectedDeleteIds) ? 'border-red-500 bg-red-500' : 'border-gray-300' }}">
                            @if (in_array($mcq['id'], $selectedDeleteIds))
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            @endif
                        </div>
                    </div>
                    <p class="text-sm text-gray-800 flex-1">{{ $mcq['question_text'] }}</p>
                </div>
            @empty
                <div class="text-center py-8 text-sm text-gray-400">No MCQs found.</div>
            @endforelse
        </div>

        <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-between gap-2 flex-shrink-0">
            <p class="text-xs text-gray-500">
                @if (!empty($selectedDeleteIds))
                    <span class="text-red-600 font-medium">{{ count($selectedDeleteIds) }}</span> selected
                @else
                    None selected
                @endif
            </p>
            <div class="flex items-center gap-2">
                <button wire:click="closeDeleteModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                <button wire:click="askDeleteConfirm" @disabled(empty($selectedDeleteIds))
                    class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed">
                    Delete Selected
                </button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════
     FINAL DELETE CONFIRM OVERLAY
═══════════════════════════════════════════════ --}}
@if ($showDeleteConfirm)
<div class="fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDelete"></div>
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div class="flex-1">
                <h3 class="text-base font-semibold text-gray-900 mb-1">
                    Delete {{ count($selectedDeleteIds) }} MCQ(s)?
                </h3>
                <p class="text-sm text-gray-500">
                    Options and student answers attached to these MCQs will also be removed. This cannot be undone.
                </p>
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 mt-5">
            <button wire:click="cancelDelete" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
            <button wire:click="confirmDelete" wire:loading.attr="disabled"
                class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md disabled:opacity-60 flex items-center gap-1.5">
                <span wire:loading.remove wire:target="confirmDelete">Delete</span>
                <span wire:loading wire:target="confirmDelete">Deleting…</span>
            </button>
        </div>
    </div>
</div>
@endif

</div>
