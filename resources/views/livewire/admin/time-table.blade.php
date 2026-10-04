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
                    @if ($viewMode === 'periods')
                        {{-- On the Periods tab the button adds the school's periods (or, once added, changes them). --}}
                        <button wire:click="openPeriodPanel"
                            class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="{{ empty($periods) ? 'M12 4v16m8-8H4' : 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' }}" />
                            </svg>
                            <span class="hidden sm:inline">{{ empty($periods) ? 'Add Periods' : 'Edit Periods' }}</span>
                            <span class="sm:hidden">{{ empty($periods) ? 'Add' : 'Edit' }}</span>
                        </button>
                    @else
                        <button wire:click="onCreateTimetable"
                            class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="hidden sm:inline">Create Timetable</span>
                            <span class="sm:hidden">New</span>
                        </button>
                    @endif
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
                <button wire:click="setViewMode('periods')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $viewMode === 'periods' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Periods
                    </span>
                </button>
            </div>
        </div>

        {{-- Filter bar (the Periods tab has nothing to filter) --}}
        @if ($viewMode !== 'periods')
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
                    <select data-sole-section wire:model.live="filterSection" @disabled(!$filterClass)
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
        @endif
    </div>

    <div class="p-4 sm:p-6 space-y-4 sm:space-y-5">

        @if ($viewMode === 'periods')
            {{-- ══════════════════════════════════════════════════
                 PERIODS — the school's day, laid out as Mark Attendance is: a
                 plain header, one line of headings, a flat list of rows. Each
                 period with its serial, its time and how long it is; the lunch
                 break where it falls.
            ══════════════════════════════════════════════════ --}}
            @if (empty($periods))
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center">
                    <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="text-sm text-gray-600 font-medium">No periods added yet.</p>
                    <p class="text-xs text-gray-400 mt-1">Add how many periods the day has, when each starts and ends, and the lunch break.</p>
                    <button wire:click="openPeriodPanel" class="mt-3 text-sm font-medium text-blue-600 hover:text-blue-800">Add periods →</button>
                </div>
            @else
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between gap-3 px-4 sm:px-6 py-3 border-b border-gray-100">
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-gray-900 truncate">Periods</h3>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ count($periods) }} period{{ count($periods) === 1 ? '' : 's' }}@if ($lunch) · Lunch break {{ \Carbon\Carbon::parse($lunch['start'])->format('h:i A') }} – {{ \Carbon\Carbon::parse($lunch['end'])->format('h:i A') }}@endif
                            </p>
                        </div>
                        <button wire:click="openPeriodPanel" title="Edit"
                            class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex items-center gap-3 px-4 sm:px-6 py-2 border-b border-gray-100 text-[11px] text-gray-400 uppercase tracking-wider">
                        <span class="w-5 flex-shrink-0">#</span>
                        <span class="flex-1 min-w-0">Period</span>
                        <span class="w-44 flex-shrink-0">Time</span>
                        <span class="w-16 flex-shrink-0 text-right">Duration</span>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @foreach ($dayRows as $r)
                            <div wire:key="day-row-{{ $r['type'] }}-{{ $r['no'] ?? 'lunch' }}"
                                class="flex items-center gap-3 px-4 sm:px-6 py-2.5 {{ $r['type'] === 'lunch' ? 'bg-gray-50/60' : '' }}">
                                <span class="w-5 text-[11px] text-gray-300 tabular-nums flex-shrink-0">{{ $r['no'] ?? '' }}</span>
                                <span class="flex-1 min-w-0 text-sm truncate {{ $r['type'] === 'lunch' ? 'text-gray-500' : 'text-gray-800' }}">{{ $r['type'] === 'lunch' ? 'Lunch break' : 'Period ' . $r['no'] }}</span>
                                <span class="w-44 text-sm text-gray-600 tabular-nums flex-shrink-0">{{ \Carbon\Carbon::parse($r['start'])->format('h:i A') }} – {{ \Carbon\Carbon::parse($r['end'])->format('h:i A') }}</span>
                                <span class="w-16 text-xs text-gray-400 tabular-nums text-right flex-shrink-0">{{ $this->span($r['start'], $r['end']) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        {{-- ══════════════════════════════════════════════════
             EMPTY STATES (no selection yet)
        ══════════════════════════════════════════════════ --}}
        @elseif ($viewMode === 'class' && (!$filterClass || !$filterSection))
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
                 CARDS — one per (class, section), laid out as Mark Attendance
                 is: a plain header with the actions, one line of headings, a
                 flat list of rows. A row is a period: its serial, its time,
                 then who teaches what on which days; the lunch break sits
                 where it falls. No table, no tags, no coloured boxes.
            ══════════════════════════════════════════════════ --}}
            @foreach ($sectionCards as $card)
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    {{-- Card header: combined Class · Section + action icons --}}
                    <div class="flex items-center justify-between gap-3 px-4 sm:px-6 py-3 border-b border-gray-100">
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-gray-900 truncate">{{ $card['standard'] }} · {{ $card['section'] }}</h3>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $card['count'] }} period{{ $card['count'] === 1 ? '' : 's' }}</p>
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

                    {{-- Card body: one line of headings, then a flat list of periods --}}
                    <div class="overflow-x-auto">
                        <div class="min-w-[40rem]">
                            <div class="flex items-center gap-3 px-4 sm:px-6 py-2 border-b border-gray-100 text-[11px] text-gray-400 uppercase tracking-wider">
                                <span class="w-5 flex-shrink-0">#</span>
                                <span class="w-40 flex-shrink-0">Time</span>
                                <span class="flex-1 min-w-0">Subject</span>
                                <span class="flex-1 min-w-0">Teacher</span>
                                @if ($viewMode === 'teacher')
                                    <span class="w-36 flex-shrink-0">Class · Section</span>
                                @endif
                                <span class="w-48 flex-shrink-0">Days</span>
                            </div>
                            <div class="divide-y divide-gray-100">
                                @foreach ($card['rows'] as $r)
                                    @if ($r['type'] === 'lunch')
                                        <div class="flex items-center gap-3 px-4 sm:px-6 py-2 bg-gray-50/60">
                                            <span class="w-5 flex-shrink-0"></span>
                                            <span class="w-40 text-sm text-gray-500 tabular-nums flex-shrink-0">{{ \Carbon\Carbon::parse($r['start_time'])->format('h:i A') }} – {{ \Carbon\Carbon::parse($r['end_time'])->format('h:i A') }}</span>
                                            <span class="flex-1 min-w-0 text-sm text-gray-500">Lunch break</span>
                                        </div>
                                    @else
                                        <div class="px-4 sm:px-6 py-2.5 space-y-1">
                                            @foreach ($r['lines'] as $l => $line)
                                                <div class="flex items-start gap-3">
                                                    <span class="w-5 pt-0.5 text-[11px] text-gray-300 tabular-nums flex-shrink-0">{{ $l === 0 ? ($r['no'] ?? '–') : '' }}</span>
                                                    <span class="w-40 text-sm text-gray-600 tabular-nums flex-shrink-0">
                                                        @if ($l === 0){{ \Carbon\Carbon::parse($r['start_time'])->format('h:i A') }} – {{ \Carbon\Carbon::parse($r['end_time'])->format('h:i A') }}@endif
                                                    </span>
                                                    <span class="flex-1 min-w-0 text-sm text-gray-900">{{ $line['subject'] }}</span>
                                                    <span class="flex-1 min-w-0 text-sm text-gray-700">{{ $line['teacher'] }}</span>
                                                    @if ($viewMode === 'teacher')
                                                        <span class="w-36 text-sm text-gray-700 flex-shrink-0">{{ $card['standard'] }} · {{ $card['section'] }}</span>
                                                    @endif
                                                    <span class="w-48 text-sm text-gray-500 flex-shrink-0">{{ collect($line['days'])->map(fn ($d) => $daysOfWeek[$d] ?? $d)->implode(', ') }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════
         ADD / EDIT SLIDE-IN PANEL — laid out as Mark Attendance is: a plain
         header, one quiet toolbar (the class and its section), a flat list of
         rows, and the actions in the footer.

         A row is one of the school's periods (Timetable → Periods), shown by
         its serial alone — no time. Then the teacher who takes it in this
         section, the subject, and — when the class has more than one section —
         the sections: tick another and it gets the same teacher, subject and
         time. A period opens on the whole week; when the days picked are not
         the whole week, a line opens under it for the days left over: teacher,
         subject, sections.
    ══════════════════════════════════════════════════ --}}
    @if ($open)
        @php
            // A list that floats on the screen (position: fixed, placed at its
            // button) rather than inside the rows' scroll box, so a lower row's
            // list is seen whole; it opens upward when there is no room below.
            $menuJs = "{ open: false, pos: {}, place() { const r = this.\$refs.btn.getBoundingClientRect(); const h = this.\$refs.menu.offsetHeight || 230; const below = window.innerHeight - r.bottom; const up = below < h + 12 && r.top > below; this.pos = { left: Math.max(8, Math.min(r.left, window.innerWidth - 216)) + 'px', top: up ? 'auto' : (r.bottom + 4) + 'px', bottom: up ? (window.innerHeight - r.top + 4) + 'px' : 'auto' }; }, toggle() { this.place(); this.open = !this.open; if (this.open) this.\$nextTick(() => this.place()); } }";
            $box = 'text-xs bg-white border border-gray-200 rounded-md px-2 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400';
            $otherSections = $this->otherSections();
            $hasSections   = count($createSections) > 1;   // a class with one section has no Section box
            $thisSection   = collect($createSections)->firstWhere('id', (int) $createSectionId)['name'] ?? '';
            $ready         = $createStandardId && $createSectionId && !empty($sectionSubjects);
            $periodRowCount = collect($scheduleRows)->whereNotNull('period')->count();
            $lunchDrawn    = false;
        @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-4xl bg-white shadow-2xl flex flex-col">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900">{{ $isEdit ? 'Edit Timetable' : 'New Timetable' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">One row a period: the teacher, the subject{{ $hasSections ? ', the sections' : '' }} and the days</p>
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
                    @if ($ready && $periodRowCount)
                        <span class="ml-auto text-xs text-gray-400 tabular-nums">{{ $periodRowCount }} period{{ $periodRowCount === 1 ? '' : 's' }}</span>
                    @endif
                </div>

                @if ($ready && !empty($scheduleRows))
                    {{-- One quiet line explaining the rows --}}
                    <p class="px-6 py-2 text-xs text-gray-500 border-b border-gray-100 flex-shrink-0">
                        A period opens on the whole week. Untick a day and a line opens under it for the teacher who takes the days left.@if ($hasSections) Tick another section and it gets the same teacher, subject and time.@endif
                    </p>
                @endif

                {{-- Rows --}}
                <div class="flex-1 overflow-auto">
                    @if (!$createStandardId || !$createSectionId)
                        <p class="py-16 text-center text-sm text-gray-400">Select a class and section.</p>
                    @elseif (empty($sectionSubjects))
                        <div class="py-16 text-center">
                            <p class="text-sm text-gray-500">No subjects mapped to this section.</p>
                            <p class="text-xs text-gray-400 mt-1">Add subjects to the section first to schedule them here.</p>
                        </div>
                    @else
                        @if (empty($periods))
                            {{-- The periods come first: without them there is no row to fill. --}}
                            <div class="{{ empty($scheduleRows) ? 'py-16' : 'py-4 border-b border-gray-100' }} text-center">
                                <p class="text-sm text-gray-500">The school's periods are not added yet.</p>
                                <p class="text-xs text-gray-400 mt-1">Add how many periods there are and their times on the Periods tab; they then come here one row each.</p>
                                <button type="button" wire:click="goAddPeriods" class="mt-3 text-sm font-medium text-blue-600 hover:text-blue-800">Add periods →</button>
                            </div>
                        @endif

                        @if (!empty($scheduleRows))
                            <div class="min-w-[38rem]">
                                <div class="flex items-center gap-2 px-6 py-2 border-b border-gray-100 text-[11px] text-gray-400 uppercase tracking-wider">
                                    <span class="w-12 flex-shrink-0">Period</span>
                                    <span class="flex-1 min-w-0">Teacher</span>
                                    <span class="flex-1 min-w-0">Subject</span>
                                    @if ($hasSections)
                                        <span class="w-36 flex-shrink-0">Section</span>
                                    @endif
                                    <span class="w-40 flex-shrink-0">Days</span>
                                    <span class="w-7 flex-shrink-0"></span>
                                </div>

                                <div class="divide-y divide-gray-100">
                                    @foreach ($scheduleRows as $i => $row)
                                        @php
                                            $shareConflict = $this->getShareConflict($i);
                                            // The lunch break, as one quiet line between the periods it falls between.
                                            $drawLunch = !$lunchDrawn && $lunch && $row['period'] && $i > 0 && $row['start_time'] >= $lunch['start'];
                                            if ($drawLunch) $lunchDrawn = true;
                                        @endphp
                                        @if ($drawLunch)
                                            <div wire:key="row-lunch" class="flex items-center gap-2 px-6 py-1.5 bg-gray-50/60 text-[11px] text-gray-400">
                                                <span class="w-12 flex-shrink-0"></span>
                                                <span>Lunch break</span>
                                            </div>
                                        @endif
                                        <div wire:key="row-{{ $i }}" class="px-6 py-2.5 space-y-1.5">
                                            @if (!$row['period'])
                                                {{-- A time the section has a class at that is not one of the periods:
                                                     kept, shown by its time, and it can be taken off. --}}
                                                <p class="text-[11px] text-gray-400">{{ $row['start_time'] }} – {{ $row['end_time'] }} · not one of the school's periods</p>
                                            @endif

                                            @foreach ($row['parts'] as $p => $part)
                                                @php
                                                    $availableDays = $this->availableDaysForPart($i, $p);
                                                    $selectedDays  = collect($part['days'] ?? [])->map(fn ($d) => (int) $d)->sort()->values();
                                                    $conflict      = $this->getPartConflict($i, $p);
                                                    $partSections  = collect($part['sections'] ?? [])->map(fn ($id) => (int) $id);
                                                    $sectionLabel  = \App\Support\SectionNames::joined(collect([$thisSection])->merge(collect($otherSections)->whereIn('id', $partSections->all())->pluck('name')));
                                                    $daysLabel     = $selectedDays->isEmpty()
                                                        ? 'Select days'
                                                        : ($selectedDays->count() === count($daysOfWeek) ? 'All days' : $selectedDays->map(fn ($d) => $daysOfWeek[$d] ?? $d)->implode(', '));
                                                @endphp
                                                <div wire:key="row-{{ $i }}-part-{{ $p }}">
                                                    <div class="flex items-start gap-2">
                                                        {{-- 1. The period, by its serial alone --}}
                                                        <span class="w-12 pt-1.5 text-sm text-gray-800 tabular-nums flex-shrink-0">{{ $p === 0 ? ($row['period'] ?: '–') : '' }}</span>

                                                        {{-- 2. Teacher --}}
                                                        <select wire:model.live="scheduleRows.{{ $i }}.parts.{{ $p }}.teacher_id" title="Teacher"
                                                            class="flex-1 min-w-0 text-xs bg-white border rounded-md px-2 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400 {{ $conflict ? 'border-red-300 text-red-700' : 'border-gray-200' }}">
                                                            <option value="">Select teacher</option>
                                                            @foreach ($allTeachers as $t)
                                                                <option value="{{ $t->id }}">{{ $t->user?->name ?? '—' }}</option>
                                                            @endforeach
                                                        </select>

                                                        {{-- 3. Subject --}}
                                                        <select wire:model.live="scheduleRows.{{ $i }}.parts.{{ $p }}.subject_id" title="Subject"
                                                            class="flex-1 min-w-0 {{ $box }}">
                                                            <option value="">Select subject</option>
                                                            @foreach ($sectionSubjects as $s)
                                                                <option value="{{ $s['id'] }}">{{ $s['name'] }}</option>
                                                            @endforeach
                                                        </select>

                                                        {{-- 4. Sections — only when the class has more than one. This
                                                             section is always in; each other one ticked gets the same
                                                             teacher, subject and time. --}}
                                                        @if ($hasSections)
                                                            <div class="w-36 flex-shrink-0" x-data="{{ $menuJs }}" @click.outside="open = false"
                                                                @scroll.window.capture="open && place()" @resize.window="open && place()">
                                                                <button type="button" x-ref="btn" @click="toggle()" title="Sections"
                                                                    class="w-full flex items-center justify-between gap-1 text-left hover:bg-gray-50 {{ $box }}">
                                                                    <span class="truncate text-gray-800">{{ $sectionLabel }}</span>
                                                                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                                                </button>
                                                                <div x-ref="menu" x-show="open" x-cloak :style="pos"
                                                                    class="fixed z-20 w-52 bg-white border border-gray-200 rounded-md shadow-lg py-1">
                                                                    <label class="flex items-center gap-2 px-3 py-1.5 text-xs text-gray-400">
                                                                        <input type="checkbox" checked disabled class="rounded border-gray-300 text-gray-400 w-3.5 h-3.5">
                                                                        {{ $thisSection }}
                                                                    </label>
                                                                    @foreach ($otherSections as $sec)
                                                                        <label class="flex items-center gap-2 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50 cursor-pointer">
                                                                            <input type="checkbox" value="{{ $sec['id'] }}"
                                                                                wire:model.live="scheduleRows.{{ $i }}.parts.{{ $p }}.sections"
                                                                                class="rounded border-gray-300 text-gray-800 focus:ring-gray-400 w-3.5 h-3.5">
                                                                            {{ $sec['name'] }}
                                                                        </label>
                                                                    @endforeach
                                                                    <p class="px-3 py-1 text-[10px] text-gray-400 border-t border-gray-100 mt-1">Same teacher, subject and time in each one ticked</p>
                                                                </div>
                                                            </div>
                                                        @endif

                                                        {{-- 5. Days: the whole week unless some are unticked; a line under
                                                             this one then takes the days left. --}}
                                                        <div class="w-40 flex-shrink-0" x-data="{{ $menuJs }}" @click.outside="open = false"
                                                            @scroll.window.capture="open && place()" @resize.window="open && place()">
                                                            <button type="button" x-ref="btn" @click="toggle()" title="Days"
                                                                class="w-full flex items-center justify-between gap-1 text-left hover:bg-gray-50 {{ $box }}">
                                                                <span class="truncate {{ $selectedDays->isEmpty() ? 'text-gray-400' : 'text-gray-800' }}">{{ $daysLabel }}</span>
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
                                                                        {{ $hiddenCount }} day{{ $hiddenCount === 1 ? '' : 's' }} {{ $p ? 'with the teacher above' : 'taken at this time' }}
                                                                    </p>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        {{-- A time that is not a period can be taken off the timetable --}}
                                                        <span class="w-7 flex-shrink-0">
                                                            @if (!$row['period'] && $p === 0)
                                                                <button type="button" wire:click="removeRow({{ $i }})" title="Remove from the timetable"
                                                                    class="p-1.5 text-gray-300 hover:text-red-600 hover:bg-red-50 rounded-md">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                                                </button>
                                                            @endif
                                                        </span>
                                                    </div>
                                                    @if ($conflict)
                                                        <p class="mt-1 pl-14 text-[11px] text-red-600">{{ $conflict }}</p>
                                                    @endif
                                                </div>
                                            @endforeach

                                            @if ($shareConflict)
                                                <p class="pl-14 text-[11px] text-red-600">{{ $shareConflict }}</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
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
         PERIODS SLIDE-IN PANEL — the school's day, laid out as Mark Attendance
         is. How many periods there are; a row for each with its start and its
         end, typed as 24-hour (the colon puts itself in); and the lunch break,
         from when to when.
    ══════════════════════════════════════════════════ --}}
    @if ($showPeriodPanel)
        @php
            $periodErrors = $this->periodErrors();
            $timeBox = 'w-[3.75rem] text-center tabular-nums text-sm bg-white border rounded-md px-2 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400';
            $timeMask = "let d = \$el.value.replace(/\\D/g, '').slice(0, 4); \$el.value = d.length > 2 ? d.slice(0, 2) + ':' + d.slice(2) : d";
        @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePeriodPanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900">{{ empty($periods) ? 'Add Periods' : 'Edit Periods' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">The school's day: its periods and the lunch break</p>
                    </div>
                    <button wire:click="closePeriodPanel" type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Toolbar: how many periods --}}
                <div class="px-6 py-3 border-b border-gray-100 flex flex-wrap items-center gap-3 flex-shrink-0">
                    <label for="tt-period-count" class="text-sm text-gray-700">Number of periods</label>
                    <input id="tt-period-count" type="text" inputmode="numeric" maxlength="2" placeholder="8"
                        wire:model.live.debounce.400ms="periodCount"
                        x-data x-on:input="$el.value = $el.value.replace(/\D/g, '').slice(0, 2)"
                        class="w-16 text-center tabular-nums text-sm border border-gray-300 rounded-md px-3 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                    <span class="ml-auto text-xs text-gray-400">Up to {{ \App\Livewire\Admin\TimeTable::MAX_PERIODS }}</span>
                </div>

                {{-- One quiet line explaining the times --}}
                <p class="px-6 py-2 text-xs text-gray-500 border-b border-gray-100 flex-shrink-0">
                    Type the times as 24-hour, e.g. 09:00 and 13:30. A period starts where the one before it ends unless you type another time.
                </p>

                {{-- Rows --}}
                <div class="flex-1 overflow-y-auto">
                    @if (empty($periodRows))
                        <p class="py-16 text-center text-sm text-gray-400">Enter how many periods there are.</p>
                    @else
                        <div class="flex items-center gap-3 px-6 py-2 border-b border-gray-100 text-[11px] text-gray-400 uppercase tracking-wider">
                            <span class="w-5 flex-shrink-0">#</span>
                            <span class="flex-1 min-w-0">Period</span>
                            <span class="w-[8.5rem] flex-shrink-0">Start – End</span>
                            <span class="w-14 flex-shrink-0 text-right">Duration</span>
                        </div>
                        <div class="divide-y divide-gray-100">
                            @foreach ($periodRows as $k => $pr)
                                @php $span = $this->span($pr['start'] ?? '', $pr['end'] ?? ''); @endphp
                                <div wire:key="period-row-{{ $k }}" class="px-6 py-2.5">
                                    <div class="flex items-center gap-3">
                                        <span class="w-5 text-[11px] text-gray-300 tabular-nums flex-shrink-0">{{ $k + 1 }}</span>
                                        <span class="flex-1 min-w-0 text-sm text-gray-800">Period {{ $k + 1 }}</span>
                                        <div class="w-[8.5rem] flex items-center gap-1 flex-shrink-0">
                                            @foreach (['start' => 'Start time', 'end' => 'End time'] as $timeKey => $timeLabel)
                                                <input type="text" inputmode="numeric" maxlength="5" placeholder="{{ $timeKey === 'start' ? '09:00' : '09:45' }}" title="{{ $timeLabel }} (24-hour)"
                                                    wire:model.blur="periodRows.{{ $k }}.{{ $timeKey }}" wire:key="period-row-{{ $k }}-{{ $timeKey }}"
                                                    x-data x-on:input="{{ $timeMask }}"
                                                    class="{{ $timeBox }} {{ isset($periodErrors[$k]) ? 'border-red-300' : 'border-gray-200' }}">
                                                @if ($loop->first)<span class="text-gray-300">–</span>@endif
                                            @endforeach
                                        </div>
                                        <span class="w-14 text-xs text-gray-400 tabular-nums text-right flex-shrink-0">{{ $span ?: '—' }}</span>
                                    </div>
                                    @isset($periodErrors[$k])
                                        <p class="mt-1 pl-8 text-[11px] text-red-600">{{ $periodErrors[$k] }}</p>
                                    @endisset
                                </div>
                            @endforeach

                            {{-- The lunch break, from when to when --}}
                            @php $lunchSpan = $this->span($lunchStart, $lunchEnd); @endphp
                            <div wire:key="period-row-lunch" class="px-6 py-2.5 bg-gray-50/60">
                                <div class="flex items-center gap-3">
                                    <span class="w-5 flex-shrink-0"></span>
                                    <span class="flex-1 min-w-0 text-sm text-gray-800">Lunch break</span>
                                    <div class="w-[8.5rem] flex items-center gap-1 flex-shrink-0">
                                        <input type="text" inputmode="numeric" maxlength="5" placeholder="12:00" title="Lunch break starts (24-hour)"
                                            wire:model.blur="lunchStart" wire:key="period-lunch-start"
                                            x-data x-on:input="{{ $timeMask }}"
                                            class="{{ $timeBox }} {{ isset($periodErrors['lunch']) ? 'border-red-300' : 'border-gray-200' }}">
                                        <span class="text-gray-300">–</span>
                                        <input type="text" inputmode="numeric" maxlength="5" placeholder="12:30" title="Lunch break ends (24-hour)"
                                            wire:model.blur="lunchEnd" wire:key="period-lunch-end"
                                            x-data x-on:input="{{ $timeMask }}"
                                            class="{{ $timeBox }} {{ isset($periodErrors['lunch']) ? 'border-red-300' : 'border-gray-200' }}">
                                    </div>
                                    <span class="w-14 text-xs text-gray-400 tabular-nums text-right flex-shrink-0">{{ $lunchSpan ?: '—' }}</span>
                                </div>
                                @isset($periodErrors['lunch'])
                                    <p class="mt-1 pl-8 text-[11px] text-red-600">{{ $periodErrors['lunch'] }}</p>
                                @endisset
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button type="button" wire:click="closePeriodPanel" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button type="button" wire:click="savePeriods" wire:loading.attr="disabled" wire:target="savePeriods"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="savePeriods">Save Periods</span>
                        <span wire:loading wire:target="savePeriods">Saving...</span>
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
