<div class="min-h-screen bg-gray-50">
    <style>[x-cloak]{display:none !important;}</style>

    {{-- ══════════════════════════════════════════════════
         HEADER + TABS + FILTER BAR (exams-style)
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900">Timetable</h1>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                        <span class="pr-4">Total Classes: <strong class="text-blue-600">{{ $totalClasses }}</strong></span>
                        <span class="px-4">Total Sections: <strong class="text-gray-800">{{ $totalSections }}</strong></span>
                        <span class="px-4">Timetable Created: <strong class="text-emerald-600">{{ $timetableCreated }}</strong></span>
                        <span class="pl-4">Remaining: <strong class="text-amber-600">{{ $remainingSections }}</strong></span>
                    </div>
                    <button wire:click="onCreateTimetable"
                        class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span class="hidden sm:inline">Create Timetable</span>
                        <span class="sm:hidden">New</span>
                    </button>
                </div>
            </div>

            <div class="flex lg:hidden items-center gap-3 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Classes: <strong class="text-blue-600">{{ $totalClasses }}</strong></span>
                <span>Sections: <strong class="text-gray-800">{{ $totalSections }}</strong></span>
                <span>Created: <strong class="text-emerald-600">{{ $timetableCreated }}</strong></span>
                <span>Remaining: <strong class="text-amber-600">{{ $remainingSections }}</strong></span>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="border-t border-gray-200 px-4 sm:px-6">
            <div class="flex gap-1">
                <button wire:click="setViewMode('class')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $viewMode === 'class' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 12h18M3 17h18" />
                        </svg>
                        Class View
                    </span>
                </button>
                <button wire:click="setViewMode('teacher')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $viewMode === 'teacher' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Teacher View
                    </span>
                </button>
            </div>
        </div>

        {{-- Filter bar --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter by:
                </div>

                @if ($viewMode === 'class')
                    <select wire:model.live="filterClass"
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-w-[140px]">
                        <option value="">Select Class</option>
                        @foreach ($standards as $std)
                            <option value="{{ $std->id }}">{{ $std->name }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="filterSection" @disabled(!$filterClass)
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50 disabled:cursor-not-allowed min-w-[140px]">
                        <option value="">Select Section</option>
                        @foreach ($filterSections as $sec)
                            <option value="{{ $sec['id'] }}">{{ $sec['name'] }}</option>
                        @endforeach
                    </select>
                @else
                    <select wire:model.live="filterTeacher"
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-w-[200px]">
                        <option value="">Select a Teacher</option>
                        @foreach ($allTeachers as $t)
                            <option value="{{ $t->id }}">{{ $t->user?->name ?? '—' }}</option>
                        @endforeach
                    </select>
                @endif

                <div class="flex items-center gap-1.5 ml-1">
                    @foreach ($daysOfWeek as $dayNum => $dayName)
                        <button wire:click="toggleFilterDay({{ $dayNum }})"
                            class="px-2 py-1 text-xs font-medium rounded-md border transition-colors {{ in_array($dayNum, $filterDays) ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                            {{ $dayName }}
                        </button>
                    @endforeach
                </div>

                @if ($filterClass || $filterSection || $filterTeacher || !empty($filterDays))
                    <button wire:click="clearFilters"
                        class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 space-y-4 sm:space-y-5">

        {{-- ══════════════════════════════════════════════════
             EMPTY STATES (no selection yet)
        ══════════════════════════════════════════════════ --}}
        @if ($viewMode === 'class' && (!$filterClass || !$filterSection))
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center">
                <div class="w-14 h-14 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <p class="text-sm text-gray-600 font-medium">Select a class and section to view its timetable.</p>
            </div>
        @elseif ($viewMode === 'teacher' && !$filterTeacher)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center">
                <div class="w-14 h-14 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <p class="text-sm text-gray-600 font-medium">Select a teacher to view their schedule.</p>
            </div>
        @elseif ($sectionCards->isEmpty())
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center">
                <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <p class="text-sm text-gray-600 font-medium">No timetable entries yet.</p>
                <button wire:click="onCreateTimetable" class="mt-3 text-sm font-medium text-blue-600 hover:text-blue-800">Create one →</button>
            </div>
        @else

            {{-- ══════════════════════════════════════════════════
                 CARDS — one per (class, section), in plain text: no tags, no
                 initials in circles; the days read "Mon, Tue, Wed".
            ══════════════════════════════════════════════════ --}}
            @foreach ($sectionCards as $card)
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    {{-- Card header: combined Class · Section + action icons --}}
                    <div class="flex items-center justify-between gap-3 px-4 sm:px-6 py-3 border-b border-gray-100">
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-gray-900 truncate">{{ $card['standard'] }} · {{ $card['section'] }}</h3>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $card['subject_groups']->count() }} subject{{ $card['subject_groups']->count() === 1 ? '' : 's' }} scheduled</p>
                        </div>
                        @php
                            $ttPdfUrl = $viewMode === 'teacher'
                                ? route('admin.timetable.teacher.pdf', ['organization' => auth()->user()->organization_id, 'teacher' => $filterTeacher])
                                : route('admin.timetable.pdf', ['organization' => auth()->user()->organization_id, 'standard' => $card['standard_id'], 'section' => $card['section_id']]);
                            $ttCardPrintUrl = $viewMode === 'teacher'
                                ? route('admin.timetable.print', array_filter(['organization' => auth()->user()->organization_id, 'mode' => 'teacher', 'teacher' => $filterTeacher, 'days' => implode(',', $filterDays)]))
                                : route('admin.timetable.print', array_filter(['organization' => auth()->user()->organization_id, 'mode' => 'class', 'standard' => $card['standard_id'], 'section' => $card['section_id'], 'days' => implode(',', $filterDays)]));
                        @endphp
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <button wire:click="onEditSection({{ $card['standard_id'] }}, {{ $card['section_id'] }})" title="Edit"
                                class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <a href="{{ $ttPdfUrl }}" target="_blank" title="Download"
                                class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" />
                                </svg>
                            </a>
                            <a href="{{ $ttCardPrintUrl }}" target="_blank" title="Print"
                                class="p-1.5 text-gray-500 hover:bg-gray-100 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                            </a>
                            <button wire:click="onDeleteSection({{ $card['standard_id'] }}, {{ $card['section_id'] }})" title="Delete entire section timetable"
                                class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Card body: one plain line per period --}}
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b border-gray-100">
                                <tr class="text-xs text-gray-400 uppercase tracking-wider">
                                    <th class="px-4 sm:px-6 py-2 text-left font-medium w-10">#</th>
                                    <th class="px-3 py-2 text-left font-medium whitespace-nowrap">Time</th>
                                    <th class="px-3 py-2 text-left font-medium">Subject</th>
                                    <th class="px-3 py-2 text-left font-medium">Teacher(s)</th>
                                    @if ($viewMode === 'teacher')
                                        <th class="px-3 py-2 text-left font-medium">Class · Section</th>
                                    @endif
                                    <th class="px-3 py-2 text-left font-medium">Days</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($card['subject_groups'] as $i => $g)
                                    <tr class="align-top">
                                        <td class="px-4 sm:px-6 py-2.5 text-gray-400">{{ $i + 1 }}</td>
                                        <td class="px-3 py-2.5 text-gray-700 whitespace-nowrap">
                                            {{ \Carbon\Carbon::parse($g['start_time'])->format('h:i A') }} – {{ \Carbon\Carbon::parse($g['end_time'])->format('h:i A') }}
                                        </td>
                                        <td class="px-3 py-2.5 text-gray-900">{{ $g['subject'] }}</td>
                                        <td class="px-3 py-2.5 text-gray-700">{{ collect($g['teachers'])->pluck('teacher_name')->implode(', ') }}</td>
                                        @if ($viewMode === 'teacher')
                                            <td class="px-3 py-2.5 text-gray-700 whitespace-nowrap">{{ $card['standard'] }} · {{ $card['section'] }}</td>
                                        @endif
                                        <td class="px-3 py-2.5 text-gray-500">{{ collect($g['days'])->map(fn ($d) => $daysOfWeek[$d] ?? $d)->implode(', ') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════
         ADD / EDIT SLIDE-IN PANEL — laid out as Mark Attendance is: a plain
         header, one quiet toolbar (the class and its section), a flat list of
         rows, and the actions in the footer.

         A row is one time slot: its duration, then the start and end times
         together — typed, 24-hour — then who teaches what on which days. When
         the days picked are not the whole week, a further column opens to the
         side for the days left over: days, subject, teacher. The + at the end of
         a row puts the same period (teacher, subject, time) into other sections
         of the same class as well.
    ══════════════════════════════════════════════════ --}}
    @if ($open)
        @php
            // A list that floats on the screen (position: fixed, placed at its
            // button) rather than inside the rows' scroll box, so a lower row's
            // list is seen whole; it opens upward when there is no room below.
            $menuJs = "{ open: false, pos: {}, place() { const r = this.\$refs.btn.getBoundingClientRect(); const h = this.\$refs.menu.offsetHeight || 230; const below = window.innerHeight - r.bottom; const up = below < h + 12 && r.top > below; this.pos = { left: Math.max(8, Math.min(r.left, window.innerWidth - 216)) + 'px', top: up ? 'auto' : (r.bottom + 4) + 'px', bottom: up ? (window.innerHeight - r.top + 4) + 'px' : 'auto' }; }, toggle() { this.place(); this.open = !this.open; if (this.open) this.\$nextTick(() => this.place()); } }";
            $box = 'text-xs bg-white border border-gray-200 rounded-md px-2 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400';
            $otherSections = $this->otherSections();
        @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-7xl bg-white shadow-2xl flex flex-col">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900">{{ $isEdit ? 'Edit Timetable' : 'New Timetable' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">One row a period: its time, then the subject, the teacher and the days</p>
                    </div>
                    <button wire:click="closePanel" type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Toolbar: the class and its section --}}
                <div class="px-6 py-3 border-b border-gray-100 flex flex-wrap items-center gap-2 flex-shrink-0">
                    <select wire:model.live="createStandardId" @disabled($isEdit)
                        class="text-sm border border-gray-300 rounded-md px-3 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400 disabled:bg-gray-50 disabled:text-gray-500">
                        <option value="">Select Class</option>
                        @foreach ($standards as $std)
                            <option value="{{ $std->id }}">{{ $std->name }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="createSectionId" @disabled($isEdit || !$createStandardId)
                        class="text-sm border border-gray-300 rounded-md px-3 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400 disabled:bg-gray-50 disabled:text-gray-500">
                        <option value="">Select Section</option>
                        @foreach ($createSections as $sec)
                            <option value="{{ $sec['id'] }}">{{ $sec['name'] }}</option>
                        @endforeach
                    </select>
                    @if ($createStandardId && $createSectionId && !empty($sectionSubjects))
                        <span class="ml-auto text-xs text-gray-400 tabular-nums">{{ count($scheduleRows) }} row{{ count($scheduleRows) === 1 ? '' : 's' }}</span>
                    @endif
                </div>

                @if ($createStandardId && $createSectionId && !empty($sectionSubjects))
                    {{-- One quiet line explaining the rows --}}
                    <p class="px-6 py-2 text-xs text-gray-500 border-b border-gray-100 flex-shrink-0">
                        Type the times as 24-hour, e.g. 09:00 and 13:30. Days left over open a column to the side for another subject and teacher; + puts the period into other sections of the class too.
                    </p>
                @endif

                {{-- Rows --}}
                <div class="flex-1 overflow-auto">
                    @if (!$createStandardId || !$createSectionId)
                        <p class="py-16 text-center text-sm text-gray-400">Select a class and section to load its subjects.</p>
                    @elseif (empty($sectionSubjects))
                        <div class="py-16 text-center">
                            <p class="text-sm text-gray-500">No subjects mapped to this section.</p>
                            <p class="text-xs text-gray-400 mt-1">Add subjects to the section first to schedule them here.</p>
                        </div>
                    @else
                        <div class="min-w-max">
                            {{-- Widths chosen so a row with one side column fits the panel
                                 without scrolling sideways on a laptop screen. --}}
                            <div class="flex items-center gap-1.5 px-6 py-2 border-b border-gray-100 text-[11px] text-gray-400 uppercase tracking-wider">
                                <span class="w-5 flex-shrink-0">#</span>
                                <span class="w-16 flex-shrink-0">Duration</span>
                                <span class="w-[7.75rem] flex-shrink-0">Start – End</span>
                                <span class="w-[7.5rem] flex-shrink-0">Subject</span>
                                <span class="w-[8.5rem] flex-shrink-0">Teacher</span>
                                <span class="w-[7.5rem] flex-shrink-0">Days</span>
                            </div>

                            <div class="divide-y divide-gray-100">
                                @foreach ($scheduleRows as $i => $row)
                                    @php
                                        $dur           = $this->rowDuration($i);
                                        $shareConflict = $this->getShareConflict($i);
                                        $sharedWith    = collect($row['sections'] ?? [])->map(fn ($id) => (int) $id);
                                        $messages      = [];
                                    @endphp
                                    <div wire:key="row-{{ $i }}" class="px-6 py-2.5">
                                        <div class="flex items-start gap-1.5">
                                            {{-- 1. Serial --}}
                                            <span class="w-5 pt-1.5 text-[11px] text-gray-300 tabular-nums flex-shrink-0">{{ $i + 1 }}</span>

                                            {{-- 2. Duration (worked out from the times) --}}
                                            <span class="w-16 pt-1.5 text-xs flex-shrink-0 {{ $dur ? 'text-gray-600' : 'text-red-500' }}">{{ $dur ?: '—' }}</span>

                                            {{-- 3. Start and end, together: typed, 24-hour. The colon puts itself in. --}}
                                            <div class="w-[7.75rem] flex items-center gap-1 flex-shrink-0">
                                                @foreach (['start_time' => 'Start time', 'end_time' => 'End time'] as $timeKey => $timeLabel)
                                                    <input type="text" inputmode="numeric" maxlength="5" placeholder="{{ $timeKey === 'start_time' ? '09:00' : '09:45' }}" title="{{ $timeLabel }} (24-hour)"
                                                        wire:model.blur="scheduleRows.{{ $i }}.{{ $timeKey }}" wire:key="row-{{ $i }}-{{ $timeKey }}"
                                                        x-data x-on:input="let d = $el.value.replace(/\D/g, '').slice(0, 4); $el.value = d.length > 2 ? d.slice(0, 2) + ':' + d.slice(2) : d"
                                                        class="w-[3.4rem] text-center tabular-nums {{ $box }}">
                                                    @if ($loop->first)<span class="text-gray-300">–</span>@endif
                                                @endforeach
                                            </div>

                                            {{-- 4. Who teaches what on which days. The first part reads subject,
                                                 teacher, days; a part for the days left over reads days, subject,
                                                 teacher, and sits to the side behind a line. --}}
                                            @foreach ($row['parts'] as $p => $part)
                                                @php
                                                    $availableDays = $this->availableDaysForPart($i, $p);
                                                    $selectedDays  = collect($part['days'] ?? [])->map(fn ($d) => (int) $d);
                                                    $conflict      = $this->getPartConflict($i, $p);
                                                    if ($conflict) {
                                                        $messages[] = (collect($sectionSubjects)->firstWhere('id', (int) $part['subject_id'])['name'] ?? 'Subject') . ': ' . $conflict;
                                                    }
                                                @endphp
                                                <div wire:key="row-{{ $i }}-part-{{ $p }}" class="flex items-start gap-1.5 flex-shrink-0 {{ $p ? 'pl-1.5 border-l border-gray-200' : '' }}">
                                                    @foreach ($p === 0 ? ['subject', 'teacher', 'days'] : ['days', 'subject', 'teacher'] as $control)
                                                        @if ($control === 'subject')
                                                            <select wire:model.live="scheduleRows.{{ $i }}.parts.{{ $p }}.subject_id" title="Subject"
                                                                class="{{ $p ? 'w-28' : 'w-[7.5rem]' }} {{ $box }}">
                                                                @foreach ($sectionSubjects as $s)
                                                                    <option value="{{ $s['id'] }}">{{ $s['name'] }}</option>
                                                                @endforeach
                                                            </select>
                                                        @elseif ($control === 'teacher')
                                                            <select wire:model.live="scheduleRows.{{ $i }}.parts.{{ $p }}.teacher_id" title="Teacher"
                                                                class="{{ $p ? 'w-32' : 'w-[8.5rem]' }} text-xs bg-white border rounded-md px-2 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400 {{ $conflict ? 'border-red-300 text-red-700' : 'border-gray-200' }}">
                                                                <option value="">Select teacher</option>
                                                                @foreach ($allTeachers as $t)
                                                                    <option value="{{ $t->id }}">{{ $t->user?->name ?? '—' }}</option>
                                                                @endforeach
                                                            </select>
                                                        @else
                                                            <div class="{{ $p ? 'w-[6.5rem]' : 'w-[7.5rem]' }}" x-data="{{ $menuJs }}" @click.outside="open = false"
                                                                @scroll.window.capture="open && place()" @resize.window="open && place()">
                                                                <button type="button" x-ref="btn" @click="toggle()"
                                                                    class="w-full flex items-center justify-between gap-1 text-left hover:bg-gray-50 {{ $box }}">
                                                                    <span class="truncate {{ $selectedDays->isEmpty() ? 'text-gray-400' : 'text-gray-800' }}">
                                                                        {{ $selectedDays->isEmpty() ? ($p ? 'Other days' : 'Select days') : $selectedDays->sort()->map(fn ($d) => $daysOfWeek[$d] ?? $d)->implode(', ') }}
                                                                    </span>
                                                                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                                                </button>
                                                                <div x-ref="menu" x-show="open" x-cloak :style="pos"
                                                                    class="fixed z-20 w-44 bg-white border border-gray-200 rounded-md shadow-lg py-1">
                                                                    @foreach ($daysOfWeek as $dayNum => $dayName)
                                                                        @if (in_array($dayNum, $availableDays, true))
                                                                            <label class="flex items-center gap-2 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50 cursor-pointer">
                                                                                <input type="checkbox" value="{{ $dayNum }}"
                                                                                    wire:model.live="scheduleRows.{{ $i }}.parts.{{ $p }}.days"
                                                                                    class="rounded border-gray-300 text-gray-800 focus:ring-gray-400 w-3.5 h-3.5">
                                                                                {{ $daysOfWeekFull[$dayNum] ?? $dayName }}
                                                                            </label>
                                                                        @endif
                                                                    @endforeach
                                                                    @php $hiddenCount = count($daysOfWeek) - count($availableDays); @endphp
                                                                    @if ($hiddenCount > 0)
                                                                        <p class="px-3 py-1 text-[10px] text-gray-400 border-t border-gray-100 mt-1">
                                                                            {{ $hiddenCount }} day{{ $hiddenCount === 1 ? '' : 's' }} taken at this time
                                                                        </p>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endforeach

                                            {{-- 5. Remove the row; and, last of all, the + : the same teacher, subject
                                                 and time in other sections of this class. --}}
                                            <div class="flex items-center gap-0.5 flex-shrink-0">
                                                <button type="button" wire:click="removeRow({{ $i }})" title="Remove row"
                                                    class="p-1.5 text-gray-300 hover:text-red-600 hover:bg-red-50 rounded-md">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                                </button>
                                                <div x-data="{{ $menuJs }}" @click.outside="open = false"
                                                    @scroll.window.capture="open && place()" @resize.window="open && place()">
                                                    <button type="button" x-ref="btn" @click="toggle()" title="Also in other sections of this class"
                                                        class="inline-flex items-center gap-0.5 p-1.5 rounded-md text-xs font-medium {{ $sharedWith->isEmpty() ? 'text-gray-500 hover:text-gray-800 hover:bg-gray-100' : 'text-blue-700 bg-blue-50 hover:bg-blue-100' }}">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                                        @if ($sharedWith->isNotEmpty())<span class="tabular-nums">{{ $sharedWith->count() }}</span>@endif
                                                    </button>
                                                    <div x-ref="menu" x-show="open" x-cloak :style="pos"
                                                        class="fixed z-20 w-52 bg-white border border-gray-200 rounded-md shadow-lg py-1">
                                                        <p class="px-3 py-1.5 text-[11px] text-gray-500 border-b border-gray-100">Same teacher, subject and time, also in:</p>
                                                        @forelse ($otherSections as $sec)
                                                            <label class="flex items-center gap-2 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50 cursor-pointer">
                                                                <input type="checkbox" value="{{ $sec['id'] }}"
                                                                    wire:model.live="scheduleRows.{{ $i }}.sections"
                                                                    class="rounded border-gray-300 text-gray-800 focus:ring-gray-400 w-3.5 h-3.5">
                                                                {{ $sec['name'] }}
                                                            </label>
                                                        @empty
                                                            <p class="px-3 py-2 text-xs text-gray-400">This class has no other section.</p>
                                                        @endforelse
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- What stands in the way of this row, in a quiet line under it --}}
                                        @php
                                            if ($shareConflict) {
                                                $messages[] = $shareConflict;
                                            }
                                            if ($sharedWith->isNotEmpty()) {
                                                $names = collect($otherSections)->whereIn('id', $sharedWith->all())->pluck('name')->implode(', ');
                                            }
                                        @endphp
                                        @if ($sharedWith->isNotEmpty() && ($names ?? '') !== '')
                                            <p class="mt-1 pl-7 text-[11px] text-blue-700">Also in {{ $names }}</p>
                                        @endif
                                        @foreach ($messages as $message)
                                            <p class="mt-1 pl-7 text-[11px] text-red-600">{{ $message }}</p>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>

                            <div class="px-6 py-3">
                                <button type="button" wire:click="addRow"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs text-gray-600 border border-gray-200 rounded-md hover:bg-gray-50">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                    Add row
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button type="button" wire:click="closePanel" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button type="button" wire:click="onSaveTimetable" wire:loading.attr="disabled" wire:target="onSaveTimetable"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="onSaveTimetable">{{ $isEdit ? 'Update Timetable' : 'Create Timetable' }}</span>
                        <span wire:loading wire:target="onSaveTimetable">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         DELETE CONFIRM OVERLAY
    ══════════════════════════════════════════════════ --}}
    @if ($showDeleteConfirm)
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-[9999] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDelete"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Delete entire section timetable?</h3>
                        <p class="text-sm text-gray-500">All scheduled entries for this class &amp; section will be removed.</p>
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
