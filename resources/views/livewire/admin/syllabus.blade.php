<div class="min-h-screen bg-gray-50">

{{-- ══════════════════════════════════════════════════
     HEADER + FILTER BAR (exams-style)
══════════════════════════════════════════════════ --}}
<div class="bg-white border-b border-gray-200 sticky top-0 z-30">
    <div class="px-4 sm:px-6 py-3">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">Syllabus</h1>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                    <span class="pr-4">Classes: <strong class="text-gray-800">{{ $totalStandards }}</strong></span>
                    <span class="px-4">Subjects: <strong class="text-purple-600">{{ $totalSubjects }}</strong></span>
                    <span class="pl-4">Chapters: <strong class="text-blue-600">{{ $totalChapters }}</strong></span>
                </div>
                <button wire:click="onAddChapter"
                    class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span class="hidden sm:inline">Add Chapter</span>
                </button>
            </div>
        </div>
        <div class="flex lg:hidden items-center gap-3 text-xs text-gray-500 mt-3 flex-wrap">
            <span>Classes: <strong class="text-gray-800">{{ $totalStandards }}</strong></span>
            <span>Subjects: <strong class="text-purple-600">{{ $totalSubjects }}</strong></span>
            <span>Chapters: <strong class="text-blue-600">{{ $totalChapters }}</strong></span>
        </div>
    </div>

    {{-- Filter bar (exams-style) --}}
    <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filter by:
            </div>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search chapters..."
                class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-56 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
            <select wire:model.live="filterStandard"
                class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[120px]">
                <option value="">Select Class</option>
                @foreach ($standards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
            </select>
            <select data-sole-section wire:model.live="filterSection" @disabled(!$filterStandard)
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
    <div class="bg-white rounded-xl border border-gray-200 py-12 text-center text-gray-400 text-sm">
        Pick a class and subject to view the syllabus. The section is optional.
    </div>
@elseif ($subjects->isEmpty())
    <div class="bg-white rounded-xl border border-gray-200 py-12 text-center text-sm text-gray-400">
        No syllabus found for this selection.
        <button wire:click="onAddChapter" class="block mx-auto mt-2 text-sm text-blue-600 hover:text-blue-800 font-medium">Add a chapter →</button>
    </div>
@else
    @php
        $clsName = optional($standards->firstWhere('id', (int) $filterStandard))->name;
        $secName = $filterSection ? optional(collect($filterSections)->firstWhere('id', (int) $filterSection))->name : null;
    @endphp
    @foreach ($subjects as $subject)
        {{-- The Attendance day list's look: a plain card, the subject and its class in
             the header strip with its actions, and the chapters in two columns filled
             across (1 left, 2 right, 3 left …) — number and name. --}}
        @php
            $chapters = $subject->chapters->values();
            $two      = $chapters->count() > 1;
            $cols     = 'grid grid-cols-[4rem_minmax(0,1fr)] items-center';
        @endphp
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden" wire:key="syl-subject-{{ $subject->id }}">
            <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-gray-700 truncate">
                        {{ $subject->name }}
                        @if ($clsName)
                            <span class="font-normal text-gray-400"> · {{ $clsName }}{{ $secName ? ' / ' . $secName : '' }}</span>
                        @endif
                    </h3>
                    <p class="text-[11px] text-gray-400">{{ $chapters->count() }} {{ $chapters->count() === 1 ? 'chapter' : 'chapters' }}</p>
                </div>
                <div class="flex items-center gap-1.5">
                    <button wire:click="onManageChapters({{ $subject->id }})" title="Edit chapters"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-200 rounded-md hover:bg-gray-50">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Edit
                    </button>
                    <button wire:click="onManageChapters({{ $subject->id }})" title="Delete chapters"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Delete
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <div class="min-w-[320px] text-sm">
                    <div class="grid {{ $two ? 'lg:grid-cols-2' : '' }} bg-gray-50 text-gray-500 text-xs font-bold uppercase">
                        @foreach ($two ? [0, 1] : [0] as $side)
                            <div class="{{ $cols }} {{ $side ? 'hidden lg:grid lg:border-l lg:border-gray-200' : '' }}">
                                <span class="px-4 py-3">#</span>
                                <span class="px-4 py-3">Chapter</span>
                            </div>
                        @endforeach
                    </div>

                    @if ($chapters->isEmpty())
                        <div class="px-4 py-10 text-center text-gray-400">
                            No chapters yet for this subject.
                            <button wire:click="onManageChapters({{ $subject->id }})" class="block mx-auto mt-2 text-sm text-blue-600 hover:text-blue-800 font-medium">Add a chapter →</button>
                        </div>
                    @else
                        <div class="grid {{ $two ? 'lg:grid-cols-2' : '' }}">
                            @foreach ($chapters as $i => $chapter)
                                <div wire:key="syl-ch-{{ $chapter->id }}" class="{{ $cols }} border-t border-gray-100 {{ $two && $i % 2 === 1 ? 'lg:border-l lg:border-l-gray-200' : '' }}">
                                    <span class="px-4 py-3 text-gray-400 tabular-nums">{{ $chapter->order ?: $i + 1 }}</span>
                                    <span class="px-4 py-3 font-medium text-gray-800 break-words">{{ $chapter->name }}</span>
                                </div>
                            @endforeach
                            {{-- An odd count leaves the last place on the right empty; it still
                                 carries the line between the columns to the foot. --}}
                            @if ($two && $chapters->count() % 2 === 1)
                                <div class="hidden lg:block border-t border-gray-100 lg:border-l lg:border-l-gray-200"></div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach

    @if ($subjects->hasPages())
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3">{{ $subjects->links() }}</div>
    @endif
@endif

</div>

{{-- ═══════════════════════════════════════════════════
     ADD / MANAGE CHAPTERS SLIDE-IN — the Mark Attendance panel's look: plain
     header, one toolbar (class → section → subject), a quiet line, a flat list
     of numbered rows, and the actions in the footer.
═══════════════════════════════════════════════════ --}}
@if ($openChapterModal)
@php $chapterIsEdit = collect($chapterRows)->contains(fn($r) => !empty($r['id'])); @endphp
<div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
    <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeChapterModal"></div>
    <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
            <div class="min-w-0">
                <h2 class="text-lg font-semibold text-gray-900">{{ $chapterIsEdit ? 'Manage Chapters' : 'Add Chapters' }}</h2>
                <p class="text-xs text-gray-500 mt-0.5">Pick class &amp; subject — its chapters load below to edit, and you can add more.</p>
            </div>
            <button wire:click="closeChapterModal" type="button"
                class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Toolbar: class → section → subject --}}
        <div class="px-6 py-3 border-b border-gray-100 flex flex-wrap items-center gap-2 flex-shrink-0">
            <select wire:model.live="chapterStandardId"
                class="text-sm border border-gray-300 rounded-md px-3 py-1.5 bg-white focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                <option value="">Select class…</option>
                @foreach ($standards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
            </select>
            <select wire:model.live="chapterSectionId" @disabled(!$chapterStandardId)
                class="text-sm border border-gray-300 rounded-md px-3 py-1.5 bg-white disabled:opacity-50 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                <option value="">All sections</option>
                @foreach ($chapterSections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
            </select>
            <select wire:model.live="chapterSubjectId" @disabled(!$chapterStandardId)
                class="text-sm border border-gray-300 rounded-md px-3 py-1.5 bg-white disabled:opacity-50 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                <option value="">Select subject…</option>
                @foreach ($chapterSubjects as $sub)<option value="{{ $sub->id }}">{{ $sub->name }}</option>@endforeach
            </select>
            @if ($chapterStandardId && $chapterSubjectId)
                <span class="ml-auto text-xs text-gray-400 tabular-nums">{{ count($chapterRows) }} {{ count($chapterRows) === 1 ? 'chapter' : 'chapters' }}</span>
            @endif
        </div>

        {{-- One quiet line --}}
        @if ($chapterStandardId && $chapterSubjectId)
            <p class="px-6 py-2 text-xs text-gray-500 border-b border-gray-100 flex-shrink-0">
                {{ $chapterIsEdit ? 'Change a name or its number, add more, or take one off with × — a saved chapter taken off is deleted when you save.' : 'Write each chapter with its number; add as many as you need.' }}
            </p>
        @endif

        {{-- Rows --}}
        <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
            @if ($chapterStandardId && $chapterSubjectId)
                @forelse ($chapterRows as $i => $row)
                    <div wire:key="chrow-{{ $i }}" class="flex items-center gap-x-3 px-6 py-2.5 {{ empty($row['id']) ? 'bg-blue-50/30' : '' }}">
                        <span class="w-4 text-[11px] text-gray-300 tabular-nums flex-shrink-0">{{ $i + 1 }}</span>
                        <input type="text" wire:model="chapterRows.{{ $i }}.name" placeholder="Chapter name *"
                            class="flex-1 min-w-0 text-sm border border-gray-200 rounded-md px-2.5 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <input type="number" wire:model="chapterRows.{{ $i }}.order" placeholder="No." min="1" title="Chapter number"
                            class="w-20 text-sm border border-gray-200 rounded-md px-2.5 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <button wire:click="removeChapterRow({{ $i }})" type="button" title="Take off"
                            class="px-2 py-1.5 rounded-md text-gray-300 hover:text-gray-600 hover:bg-gray-50 flex-shrink-0">&times;</button>
                    </div>
                @empty
                    <div class="py-16 text-center text-sm text-gray-400">
                        No chapters yet for this subject.
                        <button wire:click="addChapterRow" type="button" class="block mx-auto mt-2 text-sm text-blue-600 hover:text-blue-800 font-medium">Add the first chapter →</button>
                    </div>
                @endforelse
            @else
                <p class="py-16 text-center text-sm text-gray-400">Select a class &amp; subject to start.</p>
            @endif
        </div>

        {{-- Footer --}}
        <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-between gap-2 flex-shrink-0">
            <button wire:click="addChapterRow" type="button" @disabled(!$chapterStandardId || !$chapterSubjectId)
                class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md disabled:opacity-40">
                + Add chapter
            </button>
            <div class="flex items-center gap-2">
                <button wire:click="closeChapterModal" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                <button wire:click="onSaveChapters" wire:loading.attr="disabled" wire:target="onSaveChapters" type="button"
                    class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                    <span wire:loading.remove wire:target="onSaveChapters">Save Chapters</span>
                    <span wire:loading wire:target="onSaveChapters">Saving…</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endif

</div>
