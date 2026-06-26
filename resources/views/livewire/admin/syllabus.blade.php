<div class="min-h-screen bg-gray-50" x-data="{
    expandedSubjects: @entangle('expandedSubjects').live,
    expandedChapters: @entangle('expandedChapters').live,
}">

{{-- ══════════════════════════════════════════════════
     HEADER + FILTER BAR (exams-style)
══════════════════════════════════════════════════ --}}
<div class="bg-white border-b border-gray-200 sticky top-0 z-30">
    <div class="px-4 sm:px-6 py-4 sm:py-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Syllabus</h1>
                <p class="text-sm text-gray-500 mt-0.5">Manage chapters and topics</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                    <span class="pr-4">Classes: <strong class="text-gray-800">{{ $totalStandards }}</strong></span>
                    <span class="px-4">Subjects: <strong class="text-purple-600">{{ $totalSubjects }}</strong></span>
                    <span class="px-4">Chapters: <strong class="text-blue-600">{{ $totalChapters }}</strong></span>
                    <span class="pl-4">Topics: <strong class="text-emerald-600">{{ $totalTopics }}</strong></span>
                </div>
                <button wire:click="onAddChapter"
                    class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span class="hidden sm:inline">Add Chapter</span>
                </button>
                <button wire:click="onAddTopic"
                    class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span class="hidden sm:inline">Add Topic</span>
                </button>
            </div>
        </div>
        <div class="flex lg:hidden items-center gap-3 text-xs text-gray-500 mt-3 flex-wrap">
            <span>Classes: <strong class="text-gray-800">{{ $totalStandards }}</strong></span>
            <span>Subjects: <strong class="text-purple-600">{{ $totalSubjects }}</strong></span>
            <span>Chapters: <strong class="text-blue-600">{{ $totalChapters }}</strong></span>
            <span>Topics: <strong class="text-emerald-600">{{ $totalTopics }}</strong></span>
        </div>
    </div>

    {{-- Filter bar (exams-style) --}}
    <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filter by:
            </div>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search chapters / topics..."
                class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-56 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
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
                @foreach ($filterSubjectsList as $sub)<option value="{{ $sub->id }}">{{ $sub->name }}</option>@endforeach
            </select>

            @if ($search || $filterStandard || $filterSection || $filterSubject)
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

@if (!$filterStandard || !$filterSubject)
    {{-- ─── Empty state: filters required ─────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center">
        <div class="w-14 h-14 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-3">
            <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
        </div>
        <p class="text-sm text-gray-600 font-medium">Pick a Class and Subject to view the syllabus.</p>
        <p class="text-xs text-gray-400 mt-1">Section filter is optional.</p>
    </div>
@elseif ($subjects->isEmpty())
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center">
        <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
            <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-3-3v6m9 5a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h10l5 5v11z"/></svg>
        </div>
        <p class="text-sm text-gray-600 font-medium">No syllabus found for this selection.</p>
        <button wire:click="onAddChapter" class="mt-3 text-sm font-medium text-blue-600 hover:text-blue-800">Add a chapter →</button>
    </div>
@else
    @foreach ($subjects as $subject)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            {{-- Subject header --}}
            <button wire:click="toggleSubject({{ $subject->id }})" type="button"
                class="w-full flex items-center justify-between gap-3 px-4 sm:px-6 py-3.5 border-b border-gray-200 bg-gradient-to-r from-blue-50/40 to-transparent hover:bg-blue-50/60 transition-colors">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="w-9 h-9 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 font-bold text-sm flex-shrink-0">
                        {{ strtoupper(substr($subject->name, 0, 1)) }}
                    </span>
                    <div class="min-w-0 text-left">
                        <h3 class="text-base font-semibold text-gray-900 truncate">{{ $subject->name }}</h3>
                        <p class="text-xs text-gray-500">{{ $subject->chapters->count() }} chapter(s)</p>
                    </div>
                </div>
                <svg class="w-5 h-5 text-gray-400 transition-transform duration-200"
                     :class="expandedSubjects.includes({{ $subject->id }}) ? 'rotate-180' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            {{-- Chapters list (collapsed by default) --}}
            <div x-show="expandedSubjects.includes({{ $subject->id }})" x-cloak class="divide-y divide-gray-100">
                @forelse ($subject->chapters as $chapter)
                    <div class="px-4 sm:px-6 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <button wire:click="toggleChapter({{ $chapter->id }})" type="button"
                                class="flex items-center gap-2 text-left flex-1 min-w-0">
                                <svg class="w-4 h-4 text-gray-400 transition-transform"
                                     :class="expandedChapters.includes({{ $chapter->id }}) ? 'rotate-90' : ''"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                <span class="text-xs font-semibold text-gray-400">#{{ $chapter->order }}</span>
                                <span class="text-sm font-medium text-gray-800 truncate">{{ $chapter->name }}</span>
                                <span class="text-xs text-gray-400 hidden sm:inline">· {{ $chapter->topics->count() }} topic(s)</span>
                            </button>
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <button wire:click="onEditChapter({{ $chapter->id }})" title="Edit chapter"
                                    class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-md transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button wire:click="deleteChapter({{ $chapter->id }})" title="Delete chapter"
                                    class="p-1.5 text-red-600 hover:bg-red-50 rounded-md transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                        @if ($chapter->description)
                            <p class="text-xs text-gray-500 ml-6 mt-1">{{ $chapter->description }}</p>
                        @endif

                        {{-- Topics --}}
                        <div x-show="expandedChapters.includes({{ $chapter->id }})" x-cloak class="ml-6 mt-2 pl-3 border-l-2 border-gray-100 space-y-1.5">
                            @forelse ($chapter->topics as $topic)
                                <div class="flex items-center justify-between gap-2 group">
                                    <span class="text-sm text-gray-700 truncate">
                                        <span class="text-xs text-gray-400 mr-1">{{ $loop->iteration }}.</span>
                                        {{ $topic->topic_name }}
                                    </span>
                                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0">
                                        <button wire:click="onEditTopic({{ $topic->id }})" title="Edit topic"
                                            class="p-1 text-emerald-600 hover:bg-emerald-50 rounded">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button wire:click="deleteTopic({{ $topic->id }})" title="Delete topic"
                                            class="p-1 text-red-600 hover:bg-red-50 rounded">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-gray-400 italic">No topics yet.</p>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <div class="px-4 sm:px-6 py-6 text-center">
                        <p class="text-sm text-gray-500">No chapters yet for this subject.</p>
                        <button wire:click="onAddChapter" class="mt-2 text-xs font-medium text-blue-600 hover:text-blue-800">Add a chapter →</button>
                    </div>
                @endforelse
            </div>
        </div>
    @endforeach

    @if ($subjects->hasPages())
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3">{{ $subjects->links() }}</div>
    @endif
@endif

</div>

{{-- ═══════════════════════════════════════════════════
     ADD CHAPTER SLIDE-IN
═══════════════════════════════════════════════════ --}}
@if ($openChapterModal)
<div class="fixed inset-0 z-50 overflow-hidden">
    <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeChapterModal"></div>
    <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Add Chapters</h2>
                <p class="text-xs text-gray-500 mt-0.5">Pick class &amp; subject, then add one or more chapters</p>
            </div>
            <button wire:click="closeChapterModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Class <span class="text-red-500">*</span></label>
                    <select wire:model.live="chapterStandardId"
                        class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500">
                        <option value="">Select Class</option>
                        @foreach ($standards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Section <span class="text-gray-400 font-normal">(optional)</span></label>
                    <select wire:model.live="chapterSectionId" @disabled(!$chapterStandardId)
                        class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 disabled:opacity-50">
                        <option value="">All Sections</option>
                        @foreach ($chapterSections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Subject <span class="text-red-500">*</span></label>
                    <select wire:model.live="chapterSubjectId" @disabled(!$chapterStandardId)
                        class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 disabled:opacity-50">
                        <option value="">Select Subject</option>
                        @foreach ($chapterSubjects as $sub)<option value="{{ $sub->id }}">{{ $sub->name }}</option>@endforeach
                    </select>
                </div>
            </div>

            @if ($chapterStandardId && $chapterSubjectId)
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-700">Chapters</h3>
                        <button wire:click="addChapterRow" type="button"
                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-md border border-blue-200">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            Add Chapter
                        </button>
                    </div>

                    @if (empty($chapterRows))
                        <div class="text-center py-8 border-2 border-dashed border-gray-200 rounded-lg">
                            <p class="text-sm text-gray-400 mb-3">No chapters added yet</p>
                            <button wire:click="addChapterRow" type="button"
                                class="text-sm font-medium text-blue-600 hover:text-blue-800">Add the first chapter →</button>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($chapterRows as $i => $row)
                                <div class="bg-gray-50 rounded-lg border border-gray-200 p-3 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-gray-400 uppercase">Chapter {{ $i + 1 }}</span>
                                        <button wire:click="removeChapterRow({{ $i }})" type="button"
                                            class="p-1 text-red-400 hover:text-red-600 hover:bg-red-50 rounded">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-12 gap-2">
                                        <div class="col-span-9">
                                            <input type="text" wire:model="chapterRows.{{ $i }}.name" placeholder="Chapter name *"
                                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md bg-white focus:ring-1 focus:ring-blue-500">
                                        </div>
                                        <div class="col-span-3">
                                            <input type="number" wire:model="chapterRows.{{ $i }}.order" placeholder="Order" min="1"
                                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md bg-white focus:ring-1 focus:ring-blue-500">
                                        </div>
                                    </div>
                                    <input type="text" wire:model="chapterRows.{{ $i }}.description" placeholder="Description (optional)"
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md bg-white focus:ring-1 focus:ring-blue-500">
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <div class="text-center py-10 text-sm text-gray-400">Please pick a class and subject to start adding chapters.</div>
            @endif
        </div>

        <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
            <button wire:click="closeChapterModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
            <button wire:click="onSaveChapters" wire:loading.attr="disabled"
                class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                <span wire:loading.remove wire:target="onSaveChapters">Save Chapters</span>
                <span wire:loading wire:target="onSaveChapters">Saving…</span>
            </button>
        </div>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════════
     ADD TOPIC SLIDE-IN
═══════════════════════════════════════════════════ --}}
@if ($openTopicModal)
<div class="fixed inset-0 z-50 overflow-hidden">
    <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeTopicModal"></div>
    <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Add Topics</h2>
                <p class="text-xs text-gray-500 mt-0.5">Pick class &amp; subject &amp; chapter, then add one or more topics</p>
            </div>
            <button wire:click="closeTopicModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
            <div class="grid grid-cols-4 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Class <span class="text-red-500">*</span></label>
                    <select wire:model.live="topicStandardId"
                        class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500">
                        <option value="">Select Class</option>
                        @foreach ($standards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Section <span class="text-gray-400 font-normal">(opt)</span></label>
                    <select wire:model.live="topicSectionId" @disabled(!$topicStandardId)
                        class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 disabled:opacity-50">
                        <option value="">All Sections</option>
                        @foreach ($topicSections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Subject <span class="text-red-500">*</span></label>
                    <select wire:model.live="topicSubjectId" @disabled(!$topicStandardId)
                        class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 disabled:opacity-50">
                        <option value="">Select Subject</option>
                        @foreach ($topicSubjects as $sub)<option value="{{ $sub->id }}">{{ $sub->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Chapter <span class="text-red-500">*</span></label>
                    <select wire:model.live="topicChapterId" @disabled(!$topicSubjectId)
                        class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 disabled:opacity-50">
                        <option value="">Select Chapter</option>
                        @foreach ($topicChapters as $ch)<option value="{{ $ch->id }}">#{{ $ch->order }} · {{ $ch->name }}</option>@endforeach
                    </select>
                </div>
            </div>

            @if ($topicChapterId)
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-700">Topics</h3>
                        <button wire:click="addTopicRow" type="button"
                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-emerald-600 bg-emerald-50 hover:bg-emerald-100 rounded-md border border-emerald-200">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            Add Topic
                        </button>
                    </div>

                    @if (empty($topicRows))
                        <div class="text-center py-8 border-2 border-dashed border-gray-200 rounded-lg">
                            <p class="text-sm text-gray-400 mb-3">No topics added yet</p>
                            <button wire:click="addTopicRow" type="button"
                                class="text-sm font-medium text-emerald-600 hover:text-emerald-800">Add the first topic →</button>
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach ($topicRows as $i => $row)
                                <div class="bg-gray-50 rounded-lg border border-gray-200 p-3 grid grid-cols-12 gap-2 items-center">
                                    <span class="col-span-1 text-xs font-bold text-gray-400 uppercase text-center">#{{ $i + 1 }}</span>
                                    <input type="text" wire:model="topicRows.{{ $i }}.name" placeholder="Topic name *"
                                        class="col-span-9 px-3 py-2 text-sm border border-gray-300 rounded-md bg-white focus:ring-1 focus:ring-emerald-500">
                                    <input type="number" wire:model="topicRows.{{ $i }}.order" placeholder="Order" min="1"
                                        class="col-span-1 px-2 py-2 text-sm border border-gray-300 rounded-md bg-white focus:ring-1 focus:ring-emerald-500">
                                    <button wire:click="removeTopicRow({{ $i }})" type="button"
                                        class="col-span-1 p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded justify-self-end">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <div class="text-center py-10 text-sm text-gray-400">Please pick a class, subject &amp; chapter to start adding topics.</div>
            @endif
        </div>

        <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
            <button wire:click="closeTopicModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
            <button wire:click="onSaveTopics" wire:loading.attr="disabled"
                class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                <span wire:loading.remove wire:target="onSaveTopics">Save Topics</span>
                <span wire:loading wire:target="onSaveTopics">Saving…</span>
            </button>
        </div>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════════
     EDIT CHAPTER SLIDE-IN
═══════════════════════════════════════════════════ --}}
@if ($editChapterModal)
<div class="fixed inset-0 z-50 overflow-hidden">
    <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeEditChapterModal"></div>
    <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Edit Chapter</h2>
                <p class="text-xs text-gray-500 mt-0.5">Update the chapter name, description and order</p>
            </div>
            <button wire:click="closeEditChapterModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Chapter Name <span class="text-red-500">*</span></label>
                <input type="text" wire:model="editChapterName"
                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Order</label>
                <input type="number" wire:model="editChapterOrder" min="1"
                    class="w-32 px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Description</label>
                <textarea rows="3" wire:model="editChapterDesc"
                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm resize-none focus:ring-1 focus:ring-blue-500"></textarea>
            </div>
        </div>
        <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
            <button wire:click="closeEditChapterModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
            <button wire:click="onUpdateChapter" wire:loading.attr="disabled"
                class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                <span wire:loading.remove wire:target="onUpdateChapter">Update Chapter</span>
                <span wire:loading wire:target="onUpdateChapter">Saving…</span>
            </button>
        </div>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════════
     EDIT TOPIC SLIDE-IN
═══════════════════════════════════════════════════ --}}
@if ($editTopicModal)
<div class="fixed inset-0 z-50 overflow-hidden">
    <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeEditTopicModal"></div>
    <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Edit Topic</h2>
                <p class="text-xs text-gray-500 mt-0.5">Rename this topic</p>
            </div>
            <button wire:click="closeEditTopicModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Topic Name <span class="text-red-500">*</span></label>
                <input type="text" wire:model="editTopicName"
                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-emerald-500">
            </div>
        </div>
        <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
            <button wire:click="closeEditTopicModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
            <button wire:click="onUpdateTopic" wire:loading.attr="disabled"
                class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                <span wire:loading.remove wire:target="onUpdateTopic">Update Topic</span>
                <span wire:loading wire:target="onUpdateTopic">Saving…</span>
            </button>
        </div>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════════
     DELETE CONFIRM OVERLAY (custom, no WireUI dialog)
═══════════════════════════════════════════════════ --}}
@if ($showDeleteConfirm)
<div class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDelete"></div>
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div class="flex-1">
                <h3 class="text-base font-semibold text-gray-900 mb-1">
                    {{ $deleteTargetType === 'chapter' ? 'Delete this chapter?' : 'Delete this topic?' }}
                </h3>
                <p class="text-sm text-gray-500">
                    {{ $deleteTargetType === 'chapter'
                        ? 'All topics under this chapter will also be deleted. This cannot be undone.'
                        : 'This action cannot be undone.' }}
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
