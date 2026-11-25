<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════
         STICKY HEADER — header + tabs + filters stay pinned
         on scroll (same behaviour as the Students page).
    ══════════════════════════════════════════════════ --}}
    <div class="sticky top-0 z-40">

    {{-- ══════════════════════════════════════════════════
         HEADER
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">Academic Structure</h1>
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

    {{-- ══════════════════════════════════════════════════
         TABS
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 px-6">
        <nav class="flex gap-1">
            @php
                $tabs = [
                    ['key' => 'standard', 'label' => 'Classes',  'color' => 'purple',  'count' => $tabCounts['standard']],
                    ['key' => 'section',  'label' => 'Sections', 'color' => 'blue',    'count' => $tabCounts['section']],
                    ['key' => 'subject',  'label' => 'Subjects', 'color' => 'emerald', 'count' => $tabCounts['subject']],
                ];
            @endphp
            @foreach ($tabs as $tab)
                <button wire:click="showTab('{{ $tab['key'] }}')"
                    class="relative py-3.5 px-4 text-sm font-semibold transition-colors border-b-2
                       {{ $activeTab === $tab['key']
                           ? 'border-' . $tab['color'] . '-500 text-' . $tab['color'] . '-700'
                           : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    {{ $tab['label'] }}
                    @if ($tab['count'] > 0)
                        <span class="ml-1.5 px-1.5 py-0.5 text-xs rounded-full
                    {{ $activeTab === $tab['key']
                        ? 'bg-' . $tab['color'] . '-100 text-' . $tab['color'] . '-700'
                        : 'bg-gray-100 text-gray-600' }}">
                            {{ $tab['count'] }}
                        </span>
                    @endif
                    @if ($tab['key'] === 'section' && $filterStandard)
                        <span class="ml-1 w-2 h-2 bg-blue-500 rounded-full inline-block"></span>
                    @endif
                    @if ($tab['key'] === 'subject' && ($filterSubjectStandard || $filterSection))
                        <span class="ml-1 w-2 h-2 bg-emerald-500 rounded-full inline-block"></span>
                    @endif
                </button>
            @endforeach
        </nav>
    </div>

    {{-- ══════════════════════════════════════════════════
         FILTERS — Exams template style
    ══════════════════════════════════════════════════ --}}
    <div class="border-t border-gray-200 border-b border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Filter by:
            </div>

            <input wire:model.live.debounce.300ms="search" type="text"
                placeholder="Search {{ $activeTab === 'standard' ? 'classes' : ($activeTab === 'section' ? 'sections' : 'subjects') }}..."
                class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-48 focus:ring-2 focus:ring-purple-500 focus:border-purple-500" />

            @if ($activeTab === 'section')
                <select wire:model.live="filterStandard"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">Select Class</option>
                    @foreach ($allStandards as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}@unless ($s->is_active) (Inactive)@endunless</option>
                    @endforeach
                </select>
            @endif

            @if ($activeTab === 'subject')
                <select wire:model.live="filterSubjectStandard"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">Select Class</option>
                    @foreach ($allStandards as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}@unless ($s->is_active) (Inactive)@endunless</option>
                    @endforeach
                </select>

                <span class="text-gray-300">→</span>

                <select data-sole-section wire:model.live="filterSection" @disabled(!$filterSubjectStandard)
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                    <option value="">Select Section</option>
                    @foreach ($availableSections as $sec)
                        <option value="{{ $sec->id }}">{{ $sec->name }}@if ($sec->code) ({{ $sec->code }})@endif @unless ($sec->is_active) (Inactive)@endunless</option>
                    @endforeach
                </select>
            @endif

            <select wire:model.live="filterStatus"
                class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>

            <select wire:model.live="perPage"
                class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>

            @if ($search || $filterStandard || $filterStatus || $filterSubjectStandard || $filterSection)
                <button wire:click="resetFilters"
                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Clear
                </button>
            @endif
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
        @php $listTargets = 'search,filterStandard,filterStatus,filterSubjectStandard,filterSection,perPage,showTab,drillIntoClass,drillIntoSection,resetFilters,gotoPage,setPage,nextPage,previousPage'; @endphp
        <div wire:loading.flex wire:target="{{ $listTargets }}" class="justify-center py-16">
            <div class="text-center">
                <div class="w-10 h-10 border-4 border-purple-200 border-t-purple-600 rounded-full animate-spin mx-auto"></div>
                <p class="text-sm text-gray-500 mt-3">Loading...</p>
            </div>
        </div>


        {{-- Classes, sections and subjects as cards in the logout pop-up's look
             (user's ask of 2026-11-25): a white card, a peach stage holding a
             white panel with the name, every figure under it, and pill buttons —
             View, Edit, Delete (rose) and Open (teal). Four to a row on a wide
             screen. A click on a class or section card opens what is in it. --}}
        @once
        <style>
            .sc-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 16px; }
            @media (min-width: 640px)  { .sc-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            @media (min-width: 1024px) { .sc-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
            @media (min-width: 1280px) { .sc-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
            .sc-card { display: flex; flex-direction: column; min-width: 0; border-radius: 26px; background: #fff; overflow: hidden;
                font-family: 'Geist', 'Segoe UI', system-ui, sans-serif; border: 1px solid #eef1f0;
                box-shadow: 0 1px 2px rgba(12,38,32,.04), 0 14px 30px -22px rgba(12,38,32,.35); transition: transform .25s cubic-bezier(.2,.7,.2,1), box-shadow .25s; }
            .sc-card.go { cursor: pointer; }
            .sc-card:hover { transform: translateY(-3px); box-shadow: 0 26px 44px -24px rgba(12,38,32,.42); }
            .sc-stage { margin: 10px 10px 0; padding: 12px 14px 0; border-radius: 20px; overflow: hidden; background: linear-gradient(160deg, #fde6cf 0%, #f7c9a0 100%); }
            .sc-top { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 12px; }
            .sc-pill { font-size: 11px; font-weight: 600; line-height: 1; padding: 5px 9px; border-radius: 999px; background: rgba(255,255,255,.62); color: #8a4b1c; white-space: nowrap; }
            .sc-pill.on { color: #047857; }
            .sc-pill.off { color: #6b7c76; }
            .sc-panel { margin-bottom: -16px; padding: 18px 14px 30px; border-radius: 16px 16px 0 0; background: #fff; text-align: center; box-shadow: 0 12px 30px -16px rgba(12,38,32,.35); }
            .sc-panel .sc-ic { display: flex; justify-content: center; margin-bottom: 8px; }
            .sc-panel h3 { font-family: 'Plus Jakarta Sans', 'Geist', system-ui, sans-serif; font-size: 18px; font-weight: 800; letter-spacing: -.03em; line-height: 1.25; color: #0c2620; overflow-wrap: anywhere; }
            .sc-panel p { margin-top: 5px; font-size: 12.5px; line-height: 1.45; color: #6b7c76; overflow-wrap: anywhere; }
            .sc-data { padding: 10px 18px 2px; }
            .sc-data > div { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; padding: 7px 0; border-bottom: 1px solid #f0f3f2; font-size: 13px; }
            .sc-data > div:last-child { border-bottom: 0; }
            .sc-data dt { color: #6b7c76; flex: none; }
            .sc-data dd { color: #0c2620; font-weight: 600; text-align: right; min-width: 0; overflow-wrap: anywhere; }
            .sc-data dd.soft { color: #95a39e; font-weight: 500; }
            .sc-btns { display: flex; gap: 8px; padding: 12px 14px 0; margin-top: auto; }
            .sc-btns + .sc-btns { margin-top: 0; padding-top: 8px; }
            .sc-btns:last-child { padding-bottom: 14px; }
            .sc-btn { flex: 1; min-width: 0; height: 38px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; border-radius: 999px;
                border: 1px solid transparent; font-size: 13px; font-weight: 500; letter-spacing: -.01em; white-space: nowrap;
                transition: background .2s, border-color .2s, box-shadow .25s, transform .25s cubic-bezier(.2,.7,.2,1); }
            .sc-btn svg { width: 15px; height: 15px; flex: none; }
            .sc-btn.ghost { background: #fff; color: #0c2620; border-color: #e5eae8; }
            .sc-btn.ghost:hover { border-color: #d6dedb; box-shadow: 0 1px 2px rgba(12,38,32,.06), 0 1px 3px rgba(12,38,32,.05); }
            .sc-btn.danger { background: #f0506e; color: #fff; }
            .sc-btn.danger:hover { background: #e11d48; box-shadow: 0 12px 26px -10px rgba(240,80,110,.7); transform: translateY(-1px); }
            .sc-btn.teal { background: #0d9488; color: #fff; }
            .sc-btn.teal:hover { background: #0f766e; box-shadow: 0 12px 26px -10px rgba(13,148,136,.7); transform: translateY(-1px); }
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
                                // Its class teachers: the class's own, else each section's.
                                $teachers = collect([$card['teacher'] ?? null])->merge(collect($card['sections'])->pluck('teacher'))->filter()->unique()->values();
                            @endphp
                            <div class="sc-card go" wire:key="class-card-{{ $std->id }}" wire:click="drillIntoClass({{ $std->id }})">
                                <div class="sc-stage">
                                    <div class="sc-top">
                                        <span class="sc-pill">#{{ $filteredStandards->firstItem() + $idx }}</span>
                                        <span class="sc-pill {{ $std->is_active ? 'on' : 'off' }}">{{ $std->is_active ? 'Active' : 'Inactive' }}</span>
                                    </div>
                                    <div class="sc-panel">
                                        <h3>{{ $std->name }}</h3>
                                        <p>{{ $wing !== '' ? $wing : 'Class' }}</p>
                                    </div>
                                </div>
                                <dl class="sc-data">
                                    <div><dt>Code</dt><dd class="{{ $std->code ? '' : 'soft' }}">{{ $std->code ?: '—' }}</dd></div>
                                    <div><dt>Order</dt><dd>{{ $std->order ?? '—' }}</dd></div>
                                    <div><dt>Sections</dt><dd class="{{ $sectionText !== '' ? '' : 'soft' }}">{{ $sectionText !== '' ? $sectionText : 'No sections' }}</dd></div>
                                    <div><dt>Students</dt><dd>{{ number_format($card['students']) }}</dd></div>
                                    <div><dt>Subjects</dt><dd>{{ number_format($card['subjects']) }}</dd></div>
                                    <div><dt>Class teacher</dt><dd class="{{ $teachers->count() ? '' : 'soft' }}">{{ $teachers->count() ? $teachers->implode(', ') : 'Not assigned' }}</dd></div>
                                </dl>
                                <div class="sc-btns" wire:click.stop>
                                    <button type="button" class="sc-btn ghost" wire:click.stop="onViewStandardAdmin({{ $std->id }})" title="View"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>View</button>
                                    <button type="button" class="sc-btn ghost" wire:click.stop="editStandard({{ $std->id }})" title="Edit"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>Edit</button>
                                    <button type="button" class="sc-btn danger" wire:click.stop="onDeleteStandard({{ $std->id }})" title="Delete"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>Delete</button>
                                </div>
                                <div class="sc-btns" wire:click.stop>
                                    <button type="button" class="sc-btn teal" wire:click.stop="drillIntoClass({{ $std->id }})" title="Open sections">Open sections<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg></button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-white rounded-xl border border-gray-200 px-4 py-16 text-center">
                        <p class="text-sm font-medium text-gray-600">No classes found</p>
                        <p class="text-xs text-gray-400 mt-1">
                            {{ ($search || $filterStatus) ? 'No classes match your filters.' : 'You haven\'t added any classes yet.' }}
                        </p>
                    </div>
                @endif
            @endif

            {{-- ════════════════ SECTIONS (List) ════════════════ --}}
            @if ($activeTab === 'section')
                @if (!$filterStandard)
                    <div class="bg-white rounded-xl border border-gray-200 px-4 py-16 text-center">
                        <p class="text-sm font-medium text-gray-600">Select a class to view sections</p>
                        <p class="text-xs text-gray-400 mt-1">Use the <strong>Class</strong> filter above, or click a class from the Classes tab.</p>
                    </div>
                @elseif ($filteredSections->count())
                    <div class="sc-grid">
                        @foreach ($filteredSections as $idx => $section)
                            @php $card = $sectionCards[$section->id] ?? ['students' => 0, 'subjects' => 0, 'teacher' => null]; @endphp
                            <div class="sc-card go" wire:key="section-card-{{ $section->id }}" wire:click="drillIntoSection({{ $section->id }})">
                                <div class="sc-stage">
                                    <div class="sc-top">
                                        <span class="sc-pill">#{{ $filteredSections->firstItem() + $idx }}</span>
                                        <span class="sc-pill {{ $section->is_active ? 'on' : 'off' }}">{{ $section->is_active ? 'Active' : 'Inactive' }}</span>
                                    </div>
                                    <div class="sc-panel">
                                        <h3>{{ $section->name }}</h3>
                                        <p>{{ $section->standard->name ?? '—' }}</p>
                                    </div>
                                </div>
                                <dl class="sc-data">
                                    <div><dt>Class</dt><dd>{{ $section->standard->name ?? '—' }}</dd></div>
                                    <div><dt>Code</dt><dd class="{{ $section->code ? '' : 'soft' }}">{{ $section->code ?: '—' }}</dd></div>
                                    <div><dt>Students</dt><dd>{{ number_format($card['students']) }}</dd></div>
                                    <div><dt>Subjects</dt><dd>{{ number_format($card['subjects']) }}</dd></div>
                                    <div><dt>Class teacher</dt><dd class="{{ $card['teacher'] ? '' : 'soft' }}">{{ $card['teacher'] ?: 'Not assigned' }}</dd></div>
                                    @if (trim((string) $section->description) !== '')
                                        <div><dt>About</dt><dd>{{ \Illuminate\Support\Str::limit($section->description, 60) }}</dd></div>
                                    @endif
                                </dl>
                                <div class="sc-btns" wire:click.stop>
                                    <button type="button" class="sc-btn ghost" wire:click.stop="onViewSectionAdmin({{ $section->id }})" title="View"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>View</button>
                                    <button type="button" class="sc-btn ghost" wire:click.stop="editSection({{ $section->id }})" title="Edit"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>Edit</button>
                                    <button type="button" class="sc-btn danger" wire:click.stop="onDeleteSection({{ $section->id }})" title="Delete"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>Delete</button>
                                </div>
                                <div class="sc-btns" wire:click.stop>
                                    <button type="button" class="sc-btn teal" wire:click.stop="drillIntoSection({{ $section->id }})" title="Open subjects">Open subjects<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg></button>
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
                        <p class="text-xs text-gray-400 mt-1">Pick a <strong>class</strong> then a <strong>section</strong> from the filters above, or drill in from the Sections tab.</p>
                    </div>
                @elseif ($filteredSubjects->count())
                    <div class="sc-grid">
                        @foreach ($filteredSubjects as $idx => $subject)
                            @php
                                $card = $subjectCards[$subject->id] ?? ['teachers' => [], 'mandatory' => null];
                                // In several sections: the first in full, the others by their last letter.
                                $secText = $subject->sections->count() ? \App\Support\SectionNames::joined($subject->sections->pluck('name')) : '';
                            @endphp
                            <div class="sc-card" wire:key="subject-card-{{ $subject->id }}">
                                <div class="sc-stage">
                                    <div class="sc-top">
                                        <span class="sc-pill">#{{ $filteredSubjects->firstItem() + $idx }}</span>
                                        <span class="sc-pill {{ $subject->is_active ? 'on' : 'off' }}">{{ $subject->is_active ? 'Active' : 'Inactive' }}</span>
                                    </div>
                                    <div class="sc-panel">
                                        <div class="sc-ic"><x-subject-icon :name="$subject->name" /></div>
                                        <h3>{{ $subject->name }}</h3>
                                        <p>{{ $subjectClassName ?: '—' }}</p>
                                    </div>
                                </div>
                                <dl class="sc-data">
                                    <div><dt>Code</dt><dd class="{{ $subject->code ? '' : 'soft' }}">{{ $subject->code ?: '—' }}</dd></div>
                                    <div><dt>Class</dt><dd>{{ $subjectClassName ?: '—' }}</dd></div>
                                    <div><dt>Sections</dt><dd class="{{ $secText !== '' ? '' : 'soft' }}">{{ $secText !== '' ? $secText : '—' }}</dd></div>
                                    <div><dt>Compulsory</dt><dd class="{{ $card['mandatory'] === null ? 'soft' : '' }}">{{ $card['mandatory'] === null ? '—' : ($card['mandatory'] ? 'Yes' : 'No') }}</dd></div>
                                    <div><dt>Teacher</dt><dd class="{{ $card['teachers'] ? '' : 'soft' }}">{{ $card['teachers'] ? implode(', ', $card['teachers']) : 'Not assigned' }}</dd></div>
                                    @if (trim((string) $subject->description) !== '')
                                        <div><dt>About</dt><dd>{{ \Illuminate\Support\Str::limit($subject->description, 60) }}</dd></div>
                                    @endif
                                </dl>
                                <div class="sc-btns" wire:click.stop>
                                    <button type="button" class="sc-btn ghost" wire:click.stop="onViewSubjectAdmin({{ $subject->id }})" title="View"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>View</button>
                                    <button type="button" class="sc-btn ghost" wire:click.stop="editSubject({{ $subject->id }})" title="Edit"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>Edit</button>
                                    <button type="button" class="sc-btn danger" wire:click.stop="onDeleteSubject({{ $subject->id }})" title="Delete"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>Delete</button>
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
                                Add to Academic Structure
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
