<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════
         STICKY HEADER — header + tabs + filters stay pinned
         on scroll (same behaviour as the Students page).
    ══════════════════════════════════════════════════ --}}
    <div class="sticky top-0 z-40">

    {{-- ══════════════════════════════════════════════════
         HEADER — no tabs and no filter band (user's ask of 2026-11-25):
         the page opens on the classes; a class's arrow opens its sections
         (or, with a single section, its subjects), a section its subjects.
         The back arrow leads back up. Since 2026-11-27 (user's ask) the page
         is "Classes" (it was Standard / Academic Structure) and the header
         holds only that name — no trail.
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                @if ($activeTab !== 'standard')
                    <button wire:click="goBack" title="Back"
                        class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                    </button>
                @endif
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 whitespace-nowrap">Classes</h1>
            </div>
            <div class="flex flex-wrap gap-2">
                <button wire:click="openAddForm"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-purple-600 hover:bg-purple-700
                           text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add
                </button>
            </div>
        </div>
    </div>

    </div>{{-- end sticky header --}}

    {{-- ══════════════════════════════════════════════════
         CONTENT
    ══════════════════════════════════════════════════ --}}
    <div class="p-4 sm:p-6">

        {{-- Only a new list (a filter, a search, a tab, a page) swaps the list
             for the spinner. Before, every request did — opening View or Edit,
             each letter of a subject's name, and the page's own background
             refresh — so the list kept vanishing, and a class or section picked
             meanwhile waited behind it. --}}
        @php $listTargets = 'search,filterStandard,filterStatus,filterSubjectStandard,filterSection,perPage,showTab,drillIntoClass,drillIntoSection,openClass,goBack,resetFilters,gotoPage,setPage,nextPage,previousPage'; @endphp
        <div wire:loading.flex wire:target="{{ $listTargets }}" class="justify-center py-16">
            <div class="text-center">
                <div class="w-10 h-10 border-4 border-purple-200 border-t-purple-600 rounded-full animate-spin mx-auto"></div>
                <p class="text-sm text-gray-500 mt-3">Loading...</p>
            </div>
        </div>


        {{-- Classes, sections and subjects as cards (redesigned 2026-11-26, the
             user's asks): a white card — no peach band — with the name and its
             sections on the left and the code and students small on the right
             (no Order); the class teacher, then how many subjects; a full-width
             line; then View / Edit / Delete as round buttons like the arrow that
             opens what is in it. Four to a row on a wide screen. --}}
        @once
        <style>
            .sc-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 14px; }
            @media (min-width: 640px)  { .sc-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            @media (min-width: 1024px) { .sc-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
            @media (min-width: 1280px) { .sc-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
            .sc-card { display: flex; flex-direction: column; min-width: 0; border-radius: 18px; background: #fff; overflow: hidden;
                font-family: 'Geist', 'Segoe UI', system-ui, sans-serif; border: 1px solid #e9eeec;
                box-shadow: 0 1px 2px rgba(12,38,32,.04), 0 12px 26px -22px rgba(12,38,32,.35); transition: transform .25s cubic-bezier(.2,.7,.2,1), box-shadow .25s, border-color .25s; }
            .sc-card.go { cursor: pointer; }
            .sc-card:hover { transform: translateY(-2px); border-color: #d8f3ec; box-shadow: 0 22px 38px -24px rgba(12,38,32,.42); }
            .sc-head { display: flex; align-items: flex-start; gap: 10px; padding: 14px 14px 0; min-width: 0; }
            .sc-no { flex: none; min-width: 34px; height: 34px; padding: 0 6px; border-radius: 10px; background: #effaf7; color: #0f766e;
                font-size: 12.5px; font-weight: 700; display: flex; align-items: center; justify-content: center; font-variant-numeric: tabular-nums; }
            .sc-head > svg, .sc-head > img, .sc-head > span:not(.sc-no) { flex: none; }
            .sc-name { flex: 1 1 auto; min-width: 0; }
            .sc-name h3 { font-family: 'Plus Jakarta Sans', 'Geist', system-ui, sans-serif; font-size: 15px; font-weight: 800; letter-spacing: -.025em;
                line-height: 1.25; color: #0c2620; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .sc-name p { margin-top: 2px; font-size: 11.5px; line-height: 1.3; color: #6b7c76; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .sc-meta { flex: none; max-width: 46%; text-align: right; font-size: 11px; line-height: 1.45; color: #8b9893; padding-top: 1px; }
            .sc-meta p { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .sc-meta b { color: #2b433b; font-weight: 600; font-variant-numeric: tabular-nums; }
            .sc-info { padding: 11px 14px 13px; font-size: 12.5px; line-height: 1.5; color: #6b7c76; }
            .sc-info p { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .sc-info b { color: #2b433b; font-weight: 600; }
            /* The full-width line, then the round buttons. Since 2026-11-27
               (user's ask) every one in the arrow's teal, light — a pale teal
               circle, the icon in teal — and a touch bolder under the pointer
               (a deeper circle, a thicker line). */
            .sc-acts { display: flex; align-items: center; gap: 6px; padding: 10px 12px 11px 14px; margin-top: auto; border-top: 1px solid #eef1f0; }
            .sc-acts .sc-dot { width: 8px; height: 8px; border-radius: 999px; margin-right: 4px; flex: none; }
            .sc-btn { width: 32px; height: 32px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; flex: none;
                background: #e8f6f2; color: #0f766e; transition: background-color .2s, color .2s, transform .2s; }
            .sc-btn svg { width: 16px; height: 16px; }
            .sc-btn svg, .sc-btn svg path { stroke-width: 2px; transition: stroke-width .15s; }
            .sc-btn:hover { background: #cfeee6; color: #0b5f58; transform: translateY(-1px); }
            .sc-btn:hover svg, .sc-btn:hover svg path { stroke-width: 2.6px; }
            .sc-btn:focus-visible { outline: 2px solid #b1e6db; outline-offset: 2px; }
            .sc-go { margin-left: auto; } .sc-go:hover { transform: translateX(2px); }
        </style>
        @endonce
        <div wire:loading.remove wire:target="{{ $listTargets }}">

            {{-- ════════════════ CLASSES (List) ════════════════ --}}
            @if ($activeTab === 'standard')
                @php
                    // A school under two names: each class shows which one its documents carry.
                    $wingNames = $this->wingOptions;
                @endphp
                @if ($filteredStandards->count())
                    <div class="sc-grid">
                        @foreach ($filteredStandards as $idx => $std)
                            @php
                                $card = $classCards[$std->id] ?? ['students' => 0, 'subjects' => 0, 'teacher' => null, 'sections' => []];
                                $wing = $wingNames ? ($wingNames[\App\Support\SchoolBrand::wingOf($std)] ?? '') : '';
                                // The first section in full, the others by their last letter: "Section A & B & C".
                                $sectionText = \App\Support\SectionNames::joined($std->sections->pluck('name'));
                                $oneSection = $std->sections->count() === 1;
                                // Its class teachers: the class's own, else each section's.
                                $teachers = collect([$card['teacher'] ?? null])->merge(collect($card['sections'])->pluck('teacher'))->filter()->unique()->values();
                            @endphp
                            <div class="sc-card go" wire:key="class-card-{{ $std->id }}" wire:click="openClass({{ $std->id }})"
                                title="{{ $oneSection ? 'Open its subjects' : 'Open its sections' }}">
                                {{-- Name and sections on the left; its code and students, small, on the right. --}}
                                <div class="sc-head">
                                    <span class="sc-no">{{ $filteredStandards->firstItem() + $idx }}</span>
                                    <div class="sc-name">
                                        <h3 title="{{ $std->name }}">{{ $std->name }}</h3>
                                        <p>{{ $sectionText !== '' ? $sectionText : 'No sections' }}@if ($wing !== '') · {{ $wing }}@endif</p>
                                    </div>
                                    <div class="sc-meta">
                                        <p>Code <b>{{ $std->code ?: '—' }}</b></p>
                                        <p><b>{{ number_format($card['students']) }}</b> {{ $card['students'] === 1 ? 'student' : 'students' }}</p>
                                    </div>
                                </div>
                                <div class="sc-info">
                                    <p title="{{ $teachers->implode(', ') }}">Class teacher: <b>{{ $teachers->count() ? $teachers->implode(', ') : 'Not assigned' }}</b></p>
                                    <p>Subjects: <b>{{ number_format($card['subjects']) }}</b></p>
                                </div>
                                <div class="sc-acts" wire:click.stop>
                                    <span class="sc-dot {{ $std->is_active ? 'bg-green-500' : 'bg-red-500' }}" title="{{ $std->is_active ? 'Active' : 'Inactive' }}"></span>
                                    <button type="button" wire:click.stop="onViewStandardAdmin({{ $std->id }})" class="sc-btn sc-view" title="View" aria-label="View"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg></button>
                                    <button type="button" wire:click.stop="editStandard({{ $std->id }})" class="sc-btn sc-edit" title="Edit" aria-label="Edit"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></button>
                                    <button type="button" wire:click.stop="onDeleteStandard({{ $std->id }})" class="sc-btn sc-del" title="Delete" aria-label="Delete"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                                    <button type="button" wire:click.stop="openClass({{ $std->id }})" class="sc-btn sc-go" title="{{ $oneSection ? 'Open its subjects' : 'Open its sections' }}" aria-label="{{ $oneSection ? 'Open its subjects' : 'Open its sections' }}"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg></button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-white rounded-xl border border-gray-200 px-4 py-16 text-center">
                        <p class="text-sm font-medium text-gray-600">No classes found</p>
                        <p class="text-xs text-gray-400 mt-1">
                            You haven't added any classes yet.
                        </p>
                    </div>
                @endif
            @endif

            {{-- ════════════════ SECTIONS (List) ════════════════ --}}
            @if ($activeTab === 'section')
                @if (!$filterStandard)
                    <div class="bg-white rounded-xl border border-gray-200 px-4 py-16 text-center">
                        <p class="text-sm font-medium text-gray-600">Select a class to view sections</p>
                        <p class="text-xs text-gray-400 mt-1">Open a class from the classes' cards.</p>
                    </div>
                @elseif ($filteredSections->count())
                    <div class="sc-grid">
                        @foreach ($filteredSections as $idx => $section)
                            @php $card = $sectionCards[$section->id] ?? ['students' => 0, 'subjects' => 0, 'teacher' => null]; @endphp
                            <div class="sc-card go" wire:key="section-card-{{ $section->id }}" wire:click="drillIntoSection({{ $section->id }})" title="Open its subjects">
                                <div class="sc-head">
                                    <span class="sc-no">{{ $filteredSections->firstItem() + $idx }}</span>
                                    <div class="sc-name">
                                        <h3 title="{{ $section->name }}">{{ $section->name }}</h3>
                                        <p>{{ $section->standard->name ?? '—' }}</p>
                                    </div>
                                    <div class="sc-meta">
                                        <p>Code <b>{{ $section->code ?: '—' }}</b></p>
                                        <p><b>{{ number_format($card['students']) }}</b> {{ $card['students'] === 1 ? 'student' : 'students' }}</p>
                                    </div>
                                </div>
                                <div class="sc-info">
                                    <p title="{{ $section->description }}">Class teacher: <b>{{ $card['teacher'] ?: 'Not assigned' }}</b>@if (trim((string) $section->description) !== '') · {{ $section->description }}@endif</p>
                                    <p>Subjects: <b>{{ number_format($card['subjects']) }}</b></p>
                                </div>
                                <div class="sc-acts" wire:click.stop>
                                    <span class="sc-dot {{ $section->is_active ? 'bg-green-500' : 'bg-red-500' }}" title="{{ $section->is_active ? 'Active' : 'Inactive' }}"></span>
                                    <button type="button" wire:click.stop="onViewSectionAdmin({{ $section->id }})" class="sc-btn sc-view" title="View" aria-label="View"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg></button>
                                    <button type="button" wire:click.stop="editSection({{ $section->id }})" class="sc-btn sc-edit" title="Edit" aria-label="Edit"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></button>
                                    <button type="button" wire:click.stop="onDeleteSection({{ $section->id }})" class="sc-btn sc-del" title="Delete" aria-label="Delete"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                                    <button type="button" wire:click.stop="drillIntoSection({{ $section->id }})" class="sc-btn sc-go" title="Open its subjects" aria-label="Open its subjects"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg></button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-white rounded-xl border border-gray-200 px-4 py-16 text-center">
                        <p class="text-sm font-medium text-gray-600">No sections in this class</p>
                        <p class="text-xs text-gray-400 mt-1">Use <strong>Add</strong> above and choose <strong>Section</strong>.</p>
                    </div>
                @endif
            @endif

            {{-- ════════════════ SUBJECTS (List) ════════════════ --}}
            @if ($activeTab === 'subject')
                @if (!$filterSection)
                    <div class="bg-white rounded-xl border border-gray-200 px-4 py-16 text-center">
                        <p class="text-sm font-medium text-gray-600">Select a section to view subjects</p>
                        <p class="text-xs text-gray-400 mt-1">Open a class, then one of its sections.</p>
                    </div>
                @elseif ($filteredSubjects->count())
                    <div class="sc-grid">
                        @foreach ($filteredSubjects as $idx => $subject)
                            @php
                                $card = $subjectCards[$subject->id] ?? ['teachers' => [], 'mandatory' => null];
                                // In several sections: the first in full, the others by their last letter.
                                $secText = $subject->sections->count() ? \App\Support\SectionNames::joined($subject->sections->pluck('name')) : '';
                                // In the small figure box: the sections by their letters ("A", "A, B").
                                $secShort = $subject->sections->pluck('name')->map(fn ($n) => trim(preg_replace('/^section\s+/i', '', (string) $n)))->filter()->implode(', ');
                            @endphp
                            <div class="sc-card" wire:key="subject-card-{{ $subject->id }}">
                                <div class="sc-head">
                                    <x-subject-icon :name="$subject->name" />
                                    <div class="sc-name">
                                        <h3 title="{{ $subject->name }}">{{ $subject->name }}</h3>
                                        <p>{{ $subjectClassName ?: '—' }}</p>
                                    </div>
                                    <div class="sc-meta">
                                        <p>Code <b>{{ $subject->code ?: '—' }}</b></p>
                                        <p>{{ $card['mandatory'] === null ? '—' : ($card['mandatory'] ? 'Compulsory' : 'Optional') }}</p>
                                    </div>
                                </div>
                                <div class="sc-info">
                                    <p title="{{ $subject->description }}">Teacher: <b>{{ $card['teachers'] ? implode(', ', $card['teachers']) : 'Not assigned' }}</b>@if (trim((string) $subject->description) !== '') · {{ $subject->description }}@endif</p>
                                    <p title="{{ $secText }}">Sections: <b>{{ $secShort !== '' ? $secShort : '—' }}</b></p>
                                </div>
                                <div class="sc-acts" wire:click.stop>
                                    <span class="sc-dot {{ $subject->is_active ? 'bg-green-500' : 'bg-red-500' }}" title="{{ $subject->is_active ? 'Active' : 'Inactive' }}"></span>
                                    <button type="button" wire:click.stop="onViewSubjectAdmin({{ $subject->id }})" class="sc-btn sc-view" title="View" aria-label="View"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg></button>
                                    <button type="button" wire:click.stop="editSubject({{ $subject->id }})" class="sc-btn sc-edit" title="Edit" aria-label="Edit"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></button>
                                    <button type="button" wire:click.stop="onDeleteSubject({{ $subject->id }})" class="sc-btn sc-del" title="Delete" aria-label="Delete"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-white rounded-xl border border-gray-200 px-4 py-16 text-center">
                        <p class="text-sm font-medium text-gray-600">No subjects in this section</p>
                        <p class="text-xs text-gray-400 mt-1">Use <strong>Add</strong> above and choose <strong>Subject</strong>.</p>
                    </div>
                @endif
            @endif

            @if ($list->hasPages())
                <div class="mt-6 pt-4 border-t border-gray-100">
                    {{ $list->links() }}
                </div>
            @endif

        </div>{{-- end wire:loading.remove --}}
    </div>

    {{-- ══════════════════════════════════════════════════
         ADD CHOOSER — pick what to add first
    ══════════════════════════════════════════════════ --}}
    @if ($openForm)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-2xl bg-white shadow-2xl flex flex-col">
                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            @if ($editId)
                                {{ $addType === 'class' ? 'Edit Class' : ($addType === 'section' ? 'Edit Section' : 'Edit Subject') }}
                            @else
                                Add to Classes
                            @endif
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $editId ? 'Update details' : 'Choose what to add, then fill in its details.' }}
                        </p>
                    </div>
                    <button wire:click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form wire:submit.prevent="save" class="flex-1 overflow-y-auto px-6 py-6 space-y-4">

                    {{-- Step 1: pick what to add (hidden while editing) --}}
                    @unless ($editId)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">What would you like to add? <span class="text-red-500">*</span></label>
                            <select wire:model.live="addType" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm bg-white">
                                <option value="">Select type…</option>
                                <option value="class">Class</option>
                                <option value="section">Section</option>
                                <option value="subject">Subject</option>
                            </select>
                        </div>
                    @endunless

                    @if ($addType === '')
                        <div class="text-center py-12 border border-dashed border-gray-200 rounded-xl">
                            <p class="text-sm font-medium text-gray-600">Pick a type above</p>
                            <p class="text-xs text-gray-400 mt-1">Its form will appear here.</p>
                        </div>
                    @endif

                    {{-- ─── CLASS fields ─── --}}
                    @if ($addType === 'class')
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Class Name <span class="text-red-500">*</span></label>
                                <input wire:model.defer="standardName" type="text" placeholder="e.g. Class 10"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-purple-500 focus:border-purple-500">
                                @error('standardName')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Class Code <span class="text-red-500">*</span></label>
                                <input wire:model.live="standardCode" type="text" placeholder="01"
                                    maxlength="10"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-purple-500 focus:border-purple-500">
                                <p class="mt-1.5 text-xs text-gray-500">
                                    Roll numbers for this class are the code followed by 001, 002…
                                    @php $preview = trim((string) $standardCode) ?: '01'; @endphp
                                    — code <span class="font-semibold text-gray-700">{{ $standardCode ?: '—' }}</span>
                                    gives <span class="font-semibold text-gray-700">{{ $preview }}001, {{ $preview }}002, {{ $preview }}003…</span>
                                    @if($editId) Changing the code changes every roll number of the class with it. @endif
                                </p>
                                @error('standardCode')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:items-end">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Display Order</label>
                                <input wire:model.defer="standardOrder" type="number" placeholder="0"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-purple-500 focus:border-purple-500">
                            </div>
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" wire:model.defer="standardActive" class="rounded"> Active
                            </label>
                        </div>
                        {{-- The school runs under two names: which one this class's ID cards,
                             admit cards, report cards and receipts carry (App\Support\SchoolBrand). --}}
                        @if ($wingOpts = $this->wingOptions)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Wing <span class="text-red-500">*</span></label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    @foreach ($wingOpts as $wingKey => $wingName)
                                        <label wire:key="wing-opt-{{ $wingKey }}"
                                            class="flex items-center gap-2.5 px-3.5 py-2.5 border rounded-md text-sm cursor-pointer transition-colors {{ (int) $standardWing === (int) $wingKey ? 'border-purple-500 bg-purple-50 text-purple-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                                            <input type="radio" wire:model.live="standardWing" value="{{ $wingKey }}" class="text-purple-600 focus:ring-purple-500">
                                            <span class="truncate">{{ $wingName }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <p class="mt-1.5 text-xs text-gray-500">This class's ID cards, admit cards, report cards and fee receipts show this name and its logo.</p>
                                @error('standardWing')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        @endif
                    @endif

                    {{-- ─── SECTION fields ─── --}}
                    @if ($addType === 'section')
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Section Name <span class="text-red-500">*</span></label>
                                <input wire:model.defer="sectionName" type="text" placeholder="e.g. A"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                @error('sectionName')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Class <span class="text-red-500">*</span></label>
                                <select wire:model.defer="selectedStandard" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                                    <option value="">Select Class</option>
                                    @foreach ($allStandards as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }}@unless ($s->is_active) (Inactive)@endunless</option>
                                    @endforeach
                                </select>
                                @error('selectedStandard')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" wire:model.defer="sectionActive" class="rounded"> Active
                        </label>
                    @endif

                    {{-- ─── SUBJECT fields ─── --}}
                    @if ($addType === 'subject')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Subject Name <span class="text-red-500">*</span></label>
                            <input wire:model.live="subjectName" type="text" placeholder="e.g. Mathematics"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                            @error('subjectName')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Class <span class="text-red-500">*</span></label>
                                <select wire:model.live="selectedStandardForSubject" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                                    <option value="">Select Class</option>
                                    @foreach ($allStandards as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }}@unless ($s->is_active) (Inactive)@endunless</option>
                                    @endforeach
                                </select>
                                @error('selectedStandardForSubject')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Sections <span class="text-red-500">*</span></label>
                                <div class="border border-gray-300 rounded-md px-3 py-2 max-h-32 overflow-y-auto bg-white {{ !$selectedStandardForSubject ? 'opacity-60' : '' }}">
                                    @if ($selectedStandardForSubject && $formSections->count())
                                        @foreach ($formSections as $sec)
                                            <label wire:key="subject-sec-{{ $sec->id }}" class="flex items-center gap-2 py-1 text-sm">
                                                <input type="checkbox" wire:model="selectedSectionsForSubject" value="{{ $sec->id }}" class="rounded">
                                                {{ $sec->name }}
                                                @if ($sec->code) <span class="text-xs text-gray-400">({{ $sec->code }})</span> @endif
                                                @unless ($sec->is_active) <span class="text-xs text-gray-400">(Inactive)</span> @endunless
                                            </label>
                                        @endforeach
                                    @else
                                        <span class="text-xs text-gray-400">{{ $selectedStandardForSubject ? 'No sections in this class.' : 'Select a class first.' }}</span>
                                    @endif
                                </div>
                                @error('selectedSectionsForSubject')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        {{-- No image upload: the icon comes from the subject name itself. --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Icon</label>
                            <div class="flex items-center gap-3 px-3.5 py-3 border border-gray-200 rounded-md bg-gray-50">
                                <x-subject-icon :name="$subjectName" size="w-10 h-10" />
                                <p class="text-xs text-gray-500">
                                    Picked automatically from the subject name. Unrecognised subjects get a default icon.
                                </p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-6">
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" wire:model.defer="isMandatory" class="rounded"> Mandatory Subject
                            </label>
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" wire:model.defer="subjectActive" class="rounded"> Active
                            </label>
                        </div>
                    @endif
                </form>

                {{-- Footer --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    @if ($addType)
                        <button wire:click="save" wire:loading.attr="disabled"
                            class="px-5 py-2 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60
                                {{ $addType === 'class' ? 'bg-purple-600 hover:bg-purple-700' : ($addType === 'section' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-emerald-600 hover:bg-emerald-700') }}">
                            <span wire:loading.remove wire:target="save">{{ $editId ? 'Update' : 'Create' }} {{ ucfirst($addType) }}</span>
                            <span wire:loading wire:target="save">Saving...</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         VIEW SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($showViewModal)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeViewModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $viewModalTitle }}</h2>
                    </div>
                    <button wire:click="closeViewModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    @foreach ($viewData as $key => $val)
                        @if (!in_array($key, ['image', 'detail_image']) && $val !== null && $val !== '')
                            <div class="grid grid-cols-3 gap-3 text-sm">
                                <span class="text-xs text-gray-400 uppercase tracking-wider">{{ str_replace('_', ' ', $key) }}</span>
                                <span class="col-span-2 text-gray-800 font-medium break-words">{{ $val }}</span>
                            </div>
                        @endif
                    @endforeach
                    @if (!empty($viewData['image']))
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">Image</p>
                            <img src="{{ $viewData['image'] }}" class="w-32 h-32 rounded-md object-cover border">
                        </div>
                    @endif
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeViewModal" class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         DELETE CONFIRM OVERLAY
    ══════════════════════════════════════════════════ --}}
    @if ($showDeleteConfirm)
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDelete"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">
                            @switch($deleteTargetType)
                                @case('class') Delete class? @break
                                @case('section') Delete section? @break
                                @case('subject') Delete subject? @break
                                @default Confirm delete?
                            @endswitch
                        </h3>
                        <p class="text-sm text-gray-500">
                            @switch($deleteTargetType)
                                @case('class')
                                    All sections must be deleted first. Any students still assigned will be set to Inactive and need a new class.
                                @break
                                @case('section')
                                    All subjects added to this section will be deleted automatically. Students in this section will need a new section assigned.
                                @break
                                @case('subject')
                                    This will permanently remove the subject and its section mappings.
                                @break
                                @default
                                    Are you sure? This action cannot be undone.
                            @endswitch
                        </p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button wire:click="cancelDelete" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="confirmDelete" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md disabled:opacity-60 flex items-center gap-1.5">
                        <span wire:loading.remove wire:target="confirmDelete">Delete</span>
                        <span wire:loading wire:target="confirmDelete">Deleting...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
