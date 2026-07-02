<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════
         HEADER + TABS + FILTER BAR (exams-style)
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-4 sm:py-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Timetable</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Manage class schedules and teacher assignments</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                        <span class="pr-4">Schedules: <strong class="text-gray-800">{{ $totalSchedules }}</strong></span>
                        <span class="px-4">Teachers: <strong class="text-emerald-600">{{ $totalTeachers }}</strong></span>
                        <span class="px-4">Classes: <strong class="text-blue-600">{{ $totalClasses }}</strong></span>
                        <span class="pl-4">Subjects: <strong class="text-purple-600">{{ $totalSubjects }}</strong></span>
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
                <span>Schedules: <strong class="text-gray-800">{{ $totalSchedules }}</strong></span>
                <span>Teachers: <strong class="text-emerald-600">{{ $totalTeachers }}</strong></span>
                <span>Classes: <strong class="text-blue-600">{{ $totalClasses }}</strong></span>
                <span>Subjects: <strong class="text-purple-600">{{ $totalSubjects }}</strong></span>
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
                 CARDS — one per (class, section)
            ══════════════════════════════════════════════════ --}}
            @foreach ($sectionCards as $card)
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    {{-- Card header: combined Class · Section + Edit + Download --}}
                    <div class="flex items-center justify-between gap-3 px-4 sm:px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50/40 to-transparent">
                        <div class="min-w-0">
                            <h3 class="text-base sm:text-lg font-bold text-gray-900 truncate">
                                {{ $card['standard'] }} <span class="text-gray-400 font-normal mx-1">·</span> {{ $card['section'] }}
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $card['subject_groups']->count() }} subject{{ $card['subject_groups']->count() === 1 ? '' : 's' }} scheduled</p>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button wire:click="onEditSection({{ $card['standard_id'] }}, {{ $card['section_id'] }})"
                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-md shadow-sm">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Edit
                            </button>
                            <a href="{{ route('admin.timetable.pdf', ['organization' => auth()->user()->organization_id, 'standard' => $card['standard_id'], 'section' => $card['section_id']]) }}"
                                target="_blank"
                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-md shadow-sm">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" />
                                </svg>
                                Download
                            </a>
                            <button wire:click="onDeleteSection({{ $card['standard_id'] }}, {{ $card['section_id'] }})"
                                class="p-1.5 text-red-600 hover:bg-red-50 rounded-md" title="Delete entire section timetable">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Card body table --}}
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-100">
                                <tr>
                                    <th class="px-3 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider w-10">#</th>
                                    @if ($viewMode === 'teacher')
                                        <th class="px-3 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Class · Section</th>
                                    @endif
                                    <th class="px-3 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Subject</th>
                                    <th class="px-3 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Teacher(s)</th>
                                    <th class="px-3 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Time</th>
                                    <th class="px-3 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Days</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($card['subject_groups'] as $i => $g)
                                    <tr class="hover:bg-gray-50/70 transition-colors align-top">
                                        <td class="px-3 py-2.5 text-sm text-gray-500">{{ $i + 1 }}</td>
                                        @if ($viewMode === 'teacher')
                                            <td class="px-3 py-2.5 text-sm text-gray-700 whitespace-nowrap">
                                                <span class="text-xs px-2 py-0.5 bg-blue-50 text-blue-700 rounded-full font-medium border border-blue-100">
                                                    {{ $card['standard'] }} · {{ $card['section'] }}
                                                </span>
                                            </td>
                                        @endif
                                        <td class="px-3 py-2.5 text-sm font-medium text-gray-800">{{ $g['subject'] }}</td>
                                        <td class="px-3 py-2.5">
                                            <div class="space-y-1">
                                                @foreach ($g['teachers'] as $t)
                                                    <div class="flex items-start gap-2 text-sm">
                                                        <div class="w-6 h-6 rounded-full bg-teal-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                                            <span class="text-[10px] font-semibold text-teal-600">{{ strtoupper(substr($t['teacher_name'], 0, 1)) }}</span>
                                                        </div>
                                                        <div class="min-w-0">
                                                            <div class="text-gray-800 font-medium leading-tight">{{ $t['teacher_name'] }}</div>
                                                            <div class="text-[11px] text-gray-500 leading-tight">
                                                                {{ collect($t['days'])->map(fn($d) => $daysOfWeek[$d] ?? $d)->implode(', ') }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="px-3 py-2.5 text-sm text-gray-700 whitespace-nowrap">
                                            {{ \Carbon\Carbon::parse($g['start_time'])->format('h:i A') }} – {{ \Carbon\Carbon::parse($g['end_time'])->format('h:i A') }}
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <div class="flex flex-wrap gap-1">
                                                @foreach ($g['days'] as $d)
                                                    <span class="text-[10px] px-1.5 py-0.5 bg-indigo-50 text-indigo-700 rounded font-medium border border-indigo-100">{{ $daysOfWeek[$d] ?? $d }}</span>
                                                @endforeach
                                            </div>
                                        </td>
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
         ADD / EDIT SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($open)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-6xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $isEdit ? 'Edit Timetable' : 'New Timetable' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $isEdit ? 'Update teacher, time and days for each subject' : 'Pick class & section, then assign teacher & time per subject' }}
                        </p>
                    </div>
                    <button wire:click="closePanel" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                    {{-- Class & Section --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Class <span class="text-red-500">*</span></label>
                            <select wire:model.live="createStandardId" @disabled($isEdit)
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 disabled:bg-gray-100">
                                <option value="">Select Class</option>
                                @foreach ($standards as $std)
                                    <option value="{{ $std->id }}">{{ $std->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Section <span class="text-red-500">*</span></label>
                            <select wire:model.live="createSectionId" @disabled($isEdit || !$createStandardId)
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed">
                                <option value="">Select Section</option>
                                @foreach ($createSections as $sec)
                                    <option value="{{ $sec['id'] }}">{{ $sec['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Subject Schedules --}}
                    @if ($createStandardId && $createSectionId)
                        @if (empty($scheduleRows))
                            <div class="text-center py-10 border-2 border-dashed border-gray-200 rounded-lg">
                                <p class="text-sm text-gray-500">No subjects mapped to this section.</p>
                                <p class="text-xs text-gray-400 mt-1">Add subjects to the section first to schedule them here.</p>
                            </div>
                        @else
                            <div>
                                <div class="flex items-center justify-between gap-3 mb-3">
                                    <h3 class="text-sm font-semibold text-gray-700">Weekly Grid</h3>
                                    <div class="flex items-center gap-3">
                                        <button type="button" wire:click="copyMondayToAllDays"
                                            title="Copy each subject's Monday teacher to Tue–Sat"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                                            </svg>
                                            Same for all days
                                        </button>
                                        <span class="text-xs text-gray-500">{{ count($scheduleRows) }} subject{{ count($scheduleRows) === 1 ? '' : 's' }} from this section</span>
                                    </div>
                                </div>

                                {{-- Matrix: # · Subject · Time/Duration · one column per weekday.
                                     Each day cell picks the teacher for that subject on that day. --}}
                                <div class="border border-gray-200 rounded-lg overflow-x-auto">
                                    <table class="w-full min-w-[860px] border-collapse">
                                        <thead class="bg-gray-100 border-b border-gray-200">
                                            <tr class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">
                                                <th class="px-2 py-2 text-left w-8">#</th>
                                                <th class="px-2 py-2 text-left min-w-[120px]">Subject</th>
                                                <th class="px-2 py-2 text-left min-w-[140px]">Time / Duration</th>
                                                @foreach ($daysOfWeek as $dayNum => $dayName)
                                                    <th class="px-2 py-2 text-center min-w-[110px]">{{ $dayName }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            @foreach ($scheduleRows as $i => $row)
                                                <tr class="bg-white align-top">
                                                    <td class="px-2 py-2 text-sm text-gray-500">{{ $i + 1 }}</td>
                                                    <td class="px-2 py-2">
                                                        <div class="text-sm font-semibold text-gray-800" title="{{ $row['subject_name'] }}">
                                                            {{ $row['subject_name'] }}
                                                        </div>
                                                    </td>
                                                    <td class="px-2 py-2">
                                                        <div class="flex items-center gap-1">
                                                            <input type="time" wire:model.live="scheduleRows.{{ $i }}.start_time"
                                                                class="w-[88px] px-1.5 py-1 text-xs border border-gray-300 rounded-md bg-white focus:ring-1 focus:ring-blue-500">
                                                            <span class="text-gray-400 text-xs">–</span>
                                                            <input type="time" wire:model.live="scheduleRows.{{ $i }}.end_time"
                                                                class="w-[88px] px-1.5 py-1 text-xs border border-gray-300 rounded-md bg-white focus:ring-1 focus:ring-blue-500">
                                                        </div>
                                                        @php $dur = $this->rowDuration($i); @endphp
                                                        <div class="mt-1 text-[10px] font-medium {{ $dur ? 'text-blue-600' : 'text-red-500' }}">
                                                            {{ $dur ? $dur : 'Invalid range' }}
                                                        </div>
                                                    </td>
                                                    @foreach ($daysOfWeek as $dayNum => $dayName)
                                                        @php $cellConflict = $this->getCellConflict($i, $dayNum); @endphp
                                                        <td class="px-1.5 py-2">
                                                            <select wire:model.live="scheduleRows.{{ $i }}.day_teachers.{{ $dayNum }}"
                                                                class="w-full px-1.5 py-1 text-[11px] border rounded-md bg-white focus:ring-1 focus:ring-blue-500 {{ $cellConflict ? 'border-red-400 bg-red-50' : 'border-gray-300' }}">
                                                                <option value="">—</option>
                                                                @foreach ($allTeachers as $t)
                                                                    <option value="{{ $t->id }}">{{ $t->user?->name ?? '—' }}</option>
                                                                @endforeach
                                                            </select>
                                                            @if ($cellConflict)
                                                                <div class="mt-1 flex items-start gap-0.5 text-[9px] leading-tight text-red-600 font-medium">
                                                                    <svg class="w-2.5 h-2.5 mt-px flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                                    </svg>
                                                                    <span>{{ $cellConflict }}</span>
                                                                </div>
                                                            @endif
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <p class="text-[11px] text-gray-500 mt-2">
                                    Pick a teacher in each day cell. Leave it on “—” to skip that subject on that day. Cells turn red when the teacher is already busy or the class is double-booked.
                                </p>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-10 text-sm text-gray-400">Please select a class and section to load subjects.</div>
                    @endif
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closePanel" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="onSaveTimetable" wire:loading.attr="disabled"
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
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
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
