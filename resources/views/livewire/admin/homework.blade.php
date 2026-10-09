<div class="{{ ($embedded ?? false) ? '' : 'min-h-screen bg-gray-50' }}">

    {{-- The filter row scrolls sideways when it runs out of width instead of
         wrapping onto a second line. Hiding the scrollbar keeps its height
         identical whether or not anything is filtered. --}}
    <style>
        .lms-filter-row { scrollbar-width: none; -ms-overflow-style: none; }
        .lms-filter-row::-webkit-scrollbar { display: none; }
    </style>

    {{-- ══════════════════════════════════════════════════
         HEADER (full-width, sticky, stats + Add button + filter bar)
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 {{ ($embedded ?? false) ? '' : 'sticky top-0 z-30' }}">
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900">Homework</h1>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                        <span class="pr-4">Total: <strong class="text-gray-800">{{ $statistics['total'] ?? 0 }}</strong></span>
                        <span class="px-4">This Week: <strong class="text-emerald-600">{{ $statistics['this_week'] ?? 0 }}</strong></span>
                        <span class="px-4">Teachers: <strong class="text-purple-600">{{ $statistics['by_teacher'] ?? 0 }}</strong></span>
                        <span class="pl-4">Classes: <strong class="text-amber-500">{{ $statistics['by_class'] ?? 0 }}</strong></span>
                    </div>

                    @if ($activeTab === 'homework')
                        <button wire:click="onAddHomework"
                            class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="hidden sm:inline">Add Homework</span>
                            <span class="sm:hidden">New</span>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Mobile/Tablet stats --}}
            <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Total: <strong class="text-gray-800">{{ $statistics['total'] ?? 0 }}</strong></span>
                <span>This Week: <strong class="text-emerald-600">{{ $statistics['this_week'] ?? 0 }}</strong></span>
                <span>Teachers: <strong class="text-purple-600">{{ $statistics['by_teacher'] ?? 0 }}</strong></span>
                <span>Classes: <strong class="text-amber-500">{{ $statistics['by_class'] ?? 0 }}</strong></span>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="border-t border-gray-200 px-4 sm:px-6">
            <div class="flex gap-1">
                <button wire:click="switchTab('homework')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'homework' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">Homework</button>
                <button wire:click="switchTab('status')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'status' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">Homework Status</button>
            </div>
        </div>

        {{-- Filter bar (tab-aware) --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
            <div class="lms-filter-row flex flex-nowrap items-center gap-2 overflow-x-auto">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700 flex-shrink-0">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter by:
                </div>

                @if ($activeTab === 'homework')
                    {{-- Date → Class → Section → Subject → Teacher.

                         The first three are the scope: nothing is listed until a
                         date, a class and a section are all chosen. Subject and
                         teacher are two ways of narrowing that same scope, so
                         choosing a teacher takes the subject picker away and
                         lists the homework for the subjects that teacher teaches
                         there. Moving the date leaves every other picker as it
                         is and just re-reads that day. --}}
                    <input wire:key="hw-search" wire:model.live.debounce.300ms="search" type="text" placeholder="Search title, description, teacher..."
                        class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-56 flex-shrink-0 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />

                    <input type="date" wire:key="hw-date" wire:model.live="filterDate" title="Assigned on"
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 flex-shrink-0">
                    @if ($filterDate)
                        <button wire:click="$set('filterDate', '')" title="Any date"
                            class="-ml-1 w-6 h-6 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-200 flex-shrink-0">&times;</button>
                    @endif

                    <select wire:key="hw-standard" wire:model.live="filterStandard" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 flex-shrink-0">
                        <option value="">All Standards</option>
                        @foreach ($standards as $standard)
                            <option value="{{ $standard->id }}">{{ $standard->name }}</option>
                        @endforeach
                    </select>

                    <select wire:key="hw-section" data-sole-section wire:model.live="filterSection" @disabled(!$filterStandard)
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 flex-shrink-0">
                        <option value="">All Sections</option>
                        @foreach ($filterSections as $section)
                            <option value="{{ $section->id }}">{{ $section->name }}</option>
                        @endforeach
                    </select>

                    @unless ($filterTeacher)
                        <select wire:key="hw-subject" wire:model.live="filterSubject" @disabled(!$filterStandard)
                            class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 flex-shrink-0">
                            <option value="">All Subjects</option>
                            @foreach ($filterSubjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    @endunless

                    <select wire:key="hw-teacher" wire:model.live="filterTeacher" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 flex-shrink-0 max-w-[11rem]">
                        <option value="">All Teachers</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                        @endforeach
                    </select>

                    {{-- Today is the date's default, so it alone is nothing to clear. --}}
                    @if ($search || $filterTeacher || $filterDate !== $this->todayDate() || $filterStandard || $filterSection || $filterSubject)
                        <button wire:click="clearFilters"
                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50 flex-shrink-0">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            Clear
                        </button>
                    @endif
                @else
                    {{-- Homework Status: date → class → section → student → subject.
                         Class and section set the scope; the rest narrow it. --}}
                    {{-- Homework older than 30 days is purged nightly, so the
                         picker stops there rather than offering empty days. --}}
                    <input type="date" wire:key="st-date" wire:model.live="hwStatusDate"
                        min="{{ $this->hwStatusMinDate() }}" max="{{ $this->hwStatusMaxDate() }}"
                        title="Assigned on — last 30 days only"
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 flex-shrink-0">
                    @if ($hwStatusDate)
                        <button wire:click="$set('hwStatusDate', '')" title="Recent days"
                            class="-ml-1 w-6 h-6 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-200 flex-shrink-0">&times;</button>
                    @endif
                    <select wire:key="st-standard" wire:model.live="hwStatusStandard" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 flex-shrink-0">
                        <option value="">Select class…</option>
                        @foreach ($standards as $standard)
                            <option value="{{ $standard->id }}">{{ $standard->name }}</option>
                        @endforeach
                    </select>
                    <select wire:key="st-section" data-sole-section wire:model.live="hwStatusSection" @disabled(!$hwStatusStandard)
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 flex-shrink-0">
                        <option value="">Select section…</option>
                        @foreach ($hwStatusSections as $section)
                            <option value="{{ $section->id }}">{{ $section->name }}</option>
                        @endforeach
                    </select>
                    <select wire:key="st-student" wire:model.live="hwStatusStudent" @disabled(!$hwStatusSection)
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 flex-shrink-0 max-w-[12rem]">
                        <option value="">All students</option>
                        @foreach ($hwStatusStudents as $st)
                            <option value="{{ $st->id }}">{{ $st->full_name }}{{ $st->roll_no ? ' · Roll ' . $st->roll_no : '' }}</option>
                        @endforeach
                    </select>
                    <select wire:key="st-subject" wire:model.live="hwStatusSubject" @disabled(!$hwStatusSection)
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 flex-shrink-0">
                        <option value="">All subjects</option>
                        @foreach ($hwStatusSubjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                        @endforeach
                    </select>

                    @if ($hwStatusDate !== $this->todayDate() || $hwStatusStandard || $hwStatusSection || $hwStatusStudent || $hwStatusSubject)
                        <button wire:click="clearStatusFilters"
                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50 flex-shrink-0">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            Clear
                        </button>
                    @endif
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         BODY / TABLE
    ══════════════════════════════════════════════════ --}}
    <div class="p-4 sm:p-6">

    @if ($activeTab === 'homework')
    @if (!$isFiltered)
        {{-- Scope-first: a date, a class and a section before anything is listed. --}}
        @php $missing = $this->missingScopeLabels(); @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
            <div class="w-12 h-12 mx-auto mb-3 bg-blue-50 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
            </div>
            <p class="text-sm font-semibold text-gray-800">Pick a date, a class and a section</p>
            <p class="text-xs text-gray-400 mt-1">
                Still to choose: <strong class="text-gray-600">{{ implode(', ', $missing) }}</strong>.
                Subject and teacher then narrow that day's list.
            </p>
        </div>
    @else
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Homework</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Set by</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Subject</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($homeworks as $homework)
                        <tr class="hover:bg-gray-50 transition-colors">
                            {{-- A little narrower than the free width (max-w-md), so the others breathe --}}
                            <td class="px-4 py-3">
                                <div class="max-w-md">
                                <div class="flex items-start gap-2">
                                    <p class="text-sm font-semibold text-gray-600">{{ $homework->title ?? 'No Title' }}</p>
                                    @if ($homework->file)
                                        <svg class="w-3.5 h-3.5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" title="Has attachment">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                        </svg>
                                    @endif
                                </div>
                                @if ($homework->description)
                                    <p class="text-xs text-gray-400 line-clamp-1 mt-0.5">{{ Str::limit($homework->description, 80) }}</p>
                                @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @php $setBy = $homework->user; @endphp
                                @if (($setBy->role ?? '') === 'teacher')
                                    <p class="text-sm text-gray-700">Teacher</p>
                                    <p class="text-[11px] text-gray-400 truncate">{{ $setBy->name }}</p>
                                @elseif ($setBy)
                                    <p class="text-sm text-gray-700">Admin</p>
                                @else
                                    <p class="text-sm text-gray-400">—</p>
                                @endif
                            </td>
                            {{-- The class, its section under it in small plain text --}}
                            <td class="px-4 py-3">
                                <p class="text-sm text-gray-700">{{ $homework->standard->name ?? 'Unknown' }}</p>
                                @if ($homework->section)
                                    <p class="text-xs text-gray-400">{{ $homework->section->name }}</p>
                                @endif
                            </td>
                            {{-- Subject as plain text, as the other lists show it --}}
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ $homework->subject->name ?? 'All Subjects' }}
                            </td>
                            {{-- Actions in the Students list's look: coloured icons, no borders --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <button wire:click="onViewHomework({{ $homework->id }})" title="View"
                                        class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <button wire:click="onEditHomework({{ $homework->id }})" title="Edit"
                                        class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button wire:click="onDeleteHomework({{ $homework->id }})" title="Delete"
                                        class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-16 text-center">
                                <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold text-gray-800">No homework found</p>
                                <p class="text-xs text-gray-400 mt-1">
                                    @if ($filterTeacher)
                                        Nothing for this teacher's subjects on this day — try another date, or clear the teacher.
                                    @elseif ($search || $filterSubject)
                                        Try another subject or search term, or another date.
                                    @else
                                        Nothing was set for this class and section on this day.
                                    @endif
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($homeworks->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $homeworks->links() }}
            </div>
        @endif
    </div>
    @endif

    @else
    {{-- ═══════════════════ HOMEWORK STATUS REGISTER ═══════════════════
         In the Mark Attendance look: a plain heading strip with the counts,
         then flat rows — number, the student (or the day), how many are done,
         and the subjects as plain text, the ones not done in red. --}}
    @if (!$hwStatusStandard || !$hwStatusSection)
        <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
            <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
            </div>
            <p class="text-sm font-semibold text-gray-800">Track homework completion</p>
            <p class="text-xs text-gray-400 mt-1">Pick a class and section. Add a date, a student or a subject to narrow it further.</p>
        </div>
    @else
        @php
            $hwAll = collect($statusRows);
            $hwDoneAll = $status['mode'] === 'by_student'
                ? $hwAll->filter(fn ($r) => $r['total'] > 0 && $r['completed'] === $r['total'])->count()
                : null;
        @endphp
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="text-sm font-semibold text-gray-700">
                        {{ $status['mode'] === 'by_day' ? 'Day by day' : 'Every student in this section' }}
                    </h3>
                    <p class="text-[11px] text-gray-400">{{ $status['scope'] }} · in red: not marked done in the app</p>
                </div>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-gray-600">
                    @if ($status['mode'] === 'by_student')
                        <span>Students <strong>{{ $hwAll->count() }}</strong></span>
                        <span>All done <strong>{{ $hwDoneAll }}</strong></span>
                        <span>Pending <strong>{{ $hwAll->count() - $hwDoneAll }}</strong></span>
                    @else
                        <span>Days <strong>{{ $hwAll->count() }}</strong></span>
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto">
                <div class="min-w-[640px] text-sm">
                    @php $hwCols = 'grid grid-cols-[3rem_minmax(0,14rem)_6rem_minmax(0,1fr)] items-start'; @endphp
                    <div class="{{ $hwCols }} bg-gray-50 text-gray-500 text-xs font-bold uppercase">
                        <span class="px-4 py-3">#</span>
                        <span class="px-4 py-3">{{ $status['mode'] === 'by_day' ? 'Date' : 'Student' }}</span>
                        <span class="px-4 py-3">Done</span>
                        <span class="px-4 py-3">Homework</span>
                    </div>

                    @forelse ($statusRows as $i => $row)
                        @php
                            $hwDone = $status['mode'] === 'by_day'
                                ? count(array_filter($row['items'], fn ($it) => $it['complete']))
                                : $row['completed'];
                            $hwTotal = $status['mode'] === 'by_day' ? count($row['items']) : $row['total'];
                        @endphp
                        <div wire:key="hwst-{{ $i }}" class="{{ $hwCols }} border-t border-gray-100">
                            <span class="px-4 py-3 text-gray-400">{{ $i + 1 }}</span>
                            <div class="px-4 py-3 min-w-0">
                                @if ($status['mode'] === 'by_day')
                                    <p class="font-medium text-gray-800">{{ $row['date'] }}</p>
                                    <p class="text-xs text-gray-400">{{ $row['day'] }}</p>
                                @else
                                    <p class="font-medium text-gray-800 truncate">{{ $row['name'] }}</p>
                                    @if ($row['roll_no'])
                                        <p class="text-xs text-gray-400">Roll {{ $row['roll_no'] }}</p>
                                    @endif
                                @endif
                            </div>
                            <span class="px-4 py-3 whitespace-nowrap {{ $hwTotal > 0 && $hwDone < $hwTotal ? 'text-red-600 font-medium' : 'text-gray-700' }}">
                                {{ $hwTotal > 0 ? $hwDone . ' / ' . $hwTotal : '—' }}
                            </span>
                            <div class="px-4 py-3 text-gray-700 leading-relaxed">
                                @forelse ($row['items'] as $k => $it)
                                    <span title="{{ $it['title'] }}" class="font-semibold {{ $it['complete'] ? 'text-gray-800' : 'text-red-600' }}">{{ $it['subject'] }}@if ($status['mode'] === 'by_student' && !$hwStatusDate)<span class="font-normal text-gray-400"> ({{ $it['date'] }})</span>@endif</span>@if (!$loop->last) <span class="inline-block w-1.5 h-1.5 mx-1 rounded-full bg-gray-400 align-middle"></span>@endif
                                @empty
                                    <span class="text-gray-400">No homework</span>
                                @endforelse
                            </div>
                        </div>
                    @empty
                        <p class="px-4 py-10 text-center text-gray-400">No students in this section.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
    @endif
    </div>

    {{-- ══════════════════════════════════════════════════
         ADD / EDIT SLIDE-IN PANEL — the Mark Attendance panel's look: class,
         section and subject in one row (Single / All subjects beside them when
         adding), then flat rows. Every field, rule and save is as before.
    ══════════════════════════════════════════════════ --}}
    @if ($open)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editId ? 'Edit Homework' : 'New Homework' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            @if ($editId)
                                Update homework details
                            @elseif ($matchedId)
                                <span class="text-emerald-600 font-medium">Already set today for this class</span> — change it and update.
                            @elseif ($todaysHomework)
                                <span class="text-emerald-600 font-medium">Today's homework for this class is filled in</span> — change it, or add the rest.
                            @else
                                Pick the class, then write the homework — for one subject or for all of them.
                            @endif
                        </p>
                    </div>
                    <button wire:click="closeModal" type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- Toolbar: class → section → subject, and Single / All subjects --}}
                <div class="px-6 py-3 border-b border-gray-100 flex flex-wrap items-center gap-2 flex-shrink-0">
                    <select wire:model.live="standard_id"
                        class="text-sm border border-gray-300 rounded-md px-3 py-1.5 bg-white focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <option value="">Select class…</option>
                        @foreach ($standards as $standard)
                            <option value="{{ $standard->id }}">{{ $standard->name }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="section_id" @disabled(!$standard_id)
                        class="text-sm border border-gray-300 rounded-md px-3 py-1.5 bg-white disabled:opacity-50 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <option value="">All sections</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}">{{ $section->name }}</option>
                        @endforeach
                    </select>
                    @if ($subject_selection === 'single')
                        <select wire:model.live="subject_id" @disabled(!$standard_id)
                            class="text-sm border border-gray-300 rounded-md px-3 py-1.5 bg-white disabled:opacity-50 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                            <option value="">Select subject…</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    @endif

                    @if (!$editId)
                        <div class="ml-auto flex items-center gap-4 text-sm text-gray-700">
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" wire:model.live="subject_selection" value="single" class="text-gray-900 focus:ring-gray-400">
                                Single subject
                            </label>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" wire:model.live="subject_selection" value="all" class="text-gray-900 focus:ring-gray-400">
                                All subjects
                            </label>
                        </div>
                    @endif
                </div>

                @if ($errors->hasAny(['standard_id', 'section_id', 'subject_id']))
                    <p class="px-6 py-2 text-xs text-red-500 border-b border-gray-100 flex-shrink-0">
                        {{ $errors->first('standard_id') ?: ($errors->first('section_id') ?: $errors->first('subject_id')) }}
                    </p>
                @endif

                @if ($subject_selection === 'single' && (!$standard_id || !$subject_id))
                    <div class="flex-1 overflow-y-auto">
                        <p class="py-16 text-center text-sm text-gray-400">Select a class and a subject to write the homework.</p>
                    </div>
                @elseif ($subject_selection === 'single')
                    {{-- ── SINGLE SUBJECT: one flat row a field ── --}}
                    <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
                        <div class="flex items-start gap-3 px-6 py-3">
                            <span class="w-28 pt-2 text-xs font-medium text-gray-500 flex-shrink-0">Title <span class="text-red-500">*</span></span>
                            <div class="flex-1 min-w-0">
                                <input wire:model.defer="title" type="text" placeholder="e.g. Chapter 3 exercises"
                                    class="w-full text-sm border border-gray-200 rounded-md px-2.5 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                @error('title')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        {{-- No Description when adding; a homework that has one keeps it on Edit
                             (and when today's homework is brought up from Add). --}}
                        @if ($editId || $matchedId)
                            <div class="flex items-start gap-3 px-6 py-3">
                                <span class="w-28 pt-2 text-xs font-medium text-gray-500 flex-shrink-0">Description</span>
                                <div class="flex-1 min-w-0">
                                    <textarea wire:model.defer="description" rows="4" placeholder="Enter homework description..."
                                        class="w-full text-sm border border-gray-200 rounded-md px-2.5 py-1.5 resize-none focus:ring-1 focus:ring-gray-400 focus:border-gray-400"></textarea>
                                    @error('description')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        @endif

                        <div class="flex items-start gap-3 px-6 py-3">
                            <span class="w-28 pt-2 text-xs font-medium text-gray-500 flex-shrink-0">Attachment</span>
                            <div class="flex-1 min-w-0">
                                @if (($editId || $matchedId) && !$homework_file)
                                    @php $homeworkModel = \App\Models\Admin\HomeWork::find($editId ?: $matchedId) @endphp
                                    @if ($homeworkModel && $homeworkModel->file)
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-sm text-blue-600 truncate">{{ basename($homeworkModel->file) }}</span>
                                            <button wire:click="$set('homework_file', null)" type="button" class="text-red-600 hover:text-red-800 text-xs">Remove</button>
                                        </div>
                                    @endif
                                @endif

                                @if ($tempFileUrl)
                                    <p class="text-sm text-gray-600 mb-1 truncate">{{ $tempFileUrl }}</p>
                                @endif

                                <input type="file" wire:model="homework_file"
                                    accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png"
                                    class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                                <div wire:loading wire:target="homework_file" class="text-xs text-blue-600 mt-1">Uploading…</div>
                                @error('homework_file')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                <p class="text-xs text-gray-400 mt-1">Optional · PDF, Word, Excel, PowerPoint, Text, Images · max 1 MB</p>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- ── ALL SUBJECTS: one flat row a subject ── --}}
                    @if ($standard_id && count($subjects) > 0)
                        <p class="px-6 py-2 text-xs text-gray-500 border-b border-gray-100 flex-shrink-0">
                            Write the homework for the subjects that have it — a subject left without a title is skipped.
                        </p>
                    @endif

                    <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
                        @if (!$standard_id)
                            <p class="py-16 text-center text-sm text-gray-400">Select a class to load its subjects.</p>
                        @elseif (count($subjects) === 0)
                            <p class="py-16 text-center text-sm text-gray-400">No subjects are mapped to this class / section.</p>
                        @else
                            @foreach ($subjects as $k => $subject)
                                <div wire:key="hw-sub-{{ $subject->id }}" class="flex items-start gap-3 px-6 py-3">
                                    <span class="w-4 pt-2 text-[11px] text-gray-300 tabular-nums flex-shrink-0">{{ $k + 1 }}</span>
                                    <div class="w-40 pt-0.5 flex items-center gap-2 flex-shrink-0 min-w-0">
                                        <x-subject-icon :name="$subject->name" size="w-7 h-7" />
                                        <div class="min-w-0">
                                            <span class="block text-sm font-medium text-gray-900 truncate">{{ $subject->name }}</span>
                                            @if (isset($todaysHomework[$subject->id]))
                                                <span class="block text-[11px] text-emerald-600">Set today · updates</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0 space-y-1.5">
                                        <input wire:model.defer="subjectHomeworks.{{ $subject->id }}.title" type="text"
                                            placeholder="Title (leave blank to skip this subject)"
                                            class="w-full text-sm border border-gray-200 rounded-md px-2.5 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                        <input type="file" wire:model="subjectHomeworks.{{ $subject->id }}.file"
                                            accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png"
                                            class="block w-full text-xs text-gray-500 file:mr-3 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                                        <div wire:loading wire:target="subjectHomeworks.{{ $subject->id }}.file" class="text-xs text-blue-600">Uploading…</div>
                                        @if (!empty($subjectHomeworks[$subject->id]['file']))
                                            <p class="text-xs text-gray-600 truncate">Selected: {{ $subjectHomeworks[$subject->id]['file']->getClientOriginalName() }}</p>
                                        @elseif (!empty($todaysHomework[$subject->id]['file']))
                                            <p class="text-xs text-gray-500 truncate">Attached: {{ $todaysHomework[$subject->id]['file'] }} <span class="text-gray-400">· pick a file to replace it</span></p>
                                        @endif
                                        @error('subjectHomeworks.' . $subject->id . '.file')<p class="text-xs text-red-500">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                @endif

                {{-- Footer --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeModal" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="onSave" wire:loading.attr="disabled" wire:target="onSave" type="button"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="onSave">{{ ($editId || $matchedId) ? 'Update Homework' : ($todaysHomework ? 'Save Homework' : 'Create Homework') }}</span>
                        <span wire:loading wire:target="onSave">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         VIEW SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($showViewModal && $viewHomework)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeViewModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $viewHomework->title }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $viewHomework->subject ? $viewHomework->subject->name : 'All Subjects' }} ·
                            {{ $viewHomework->created_at?->format('d M Y') }}
                        </p>
                    </div>
                    <button wire:click="closeViewModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Description</p>
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $viewHomework->description }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Set by</p>
                            <p class="text-gray-800 font-medium">
                                @if (($viewHomework->user->role ?? '') === 'teacher')
                                    Teacher <span class="text-gray-500 font-normal">· {{ $viewHomework->user->name }}</span>
                                @elseif ($viewHomework->user)
                                    Admin
                                @else
                                    —
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Class</p>
                            <p class="text-gray-800 font-medium">
                                {{ $viewHomework->standard->name ?? 'Unknown' }}
                                @if ($viewHomework->section)- {{ $viewHomework->section->name }}@endif
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Subject</p>
                            <p class="text-gray-800 font-medium">{{ $viewHomework->subject ? $viewHomework->subject->name : 'All Subjects' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Assigned</p>
                            <p class="text-gray-800 font-medium">{{ $viewHomework->created_at?->format('M d, Y h:i A') ?? 'Unknown' }}</p>
                        </div>
                    </div>

                    @if ($viewHomework->file)
                        <div class="pt-4 border-t border-gray-100">
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">Attachment</p>
                            <a href="{{ $viewHomework->file }}" target="_blank"
                                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                View Attachment
                            </a>
                        </div>
                    @endif
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeViewModal" class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════ DELETE CONFIRM ══════════════════════════
         Its own modal rather than WireUI's dialog: that one builds its button
         colours at runtime and those classes are not in the compiled Tailwind
         bundle, so it came out unstyled. The backdrop is inline for the same
         reason. --}}
    @if ($showDeleteModal)
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-[10000] flex items-center justify-center px-4"
            style="background:rgba(0,0,0,0.45);backdrop-filter:blur(4px);">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center" wire:click.stop>
                <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-800 mb-1">Delete Homework?</h3>
                <p class="text-sm text-gray-500 mb-5">This removes the homework and its attachment for good.</p>
                <div class="flex justify-center gap-3">
                    <button wire:click="cancelDelete"
                        class="px-4 py-2 text-sm border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button wire:click="doDeleteHomework" wire:loading.attr="disabled" wire:target="doDeleteHomework"
                        class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold shadow transition disabled:opacity-60">
                        <span wire:loading.remove wire:target="doDeleteHomework">Delete</span>
                        <span wire:loading wire:target="doDeleteHomework">Deleting…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
