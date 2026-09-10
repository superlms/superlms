<div class="min-h-screen bg-gray-50">

    @php
        $statusPill = fn($s) => match ($s) {
            'present'  => 'bg-emerald-100 text-emerald-700',
            'absent'   => 'bg-red-100 text-red-700',
            'half_day' => 'bg-amber-100 text-amber-700',
            'holiday'  => 'bg-indigo-100 text-indigo-700',
            default    => 'bg-gray-100 text-gray-400',
        };
        $statusText = fn($s) => match ($s) {
            'present'    => 'Present',
            'absent'     => 'Absent',
            'half_day'   => 'Half Day',
            'holiday'    => 'Holiday',
            'not_marked' => 'Not Marked',
            default      => ucfirst(str_replace('_', ' ', $s)),
        };
    @endphp

    {{-- ══════════ HEADER + main tabs ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">Attendance</h1>
            </div>
            @if ($mainTab === 'teacher')
                <button wire:click="openTeacherMark"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                    Mark Attendance
                </button>
            @elseif ($mainTab === 'student')
                <button wire:click="openStudentMark"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                    Mark Attendance
                </button>
            @elseif ($mainTab === 'class_teachers')
                <button wire:click="openAssignPanel"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    Assign Class Teacher
                </button>
            @endif
        </div>
        <div class="border-t border-gray-200 px-4 sm:px-6">
            <div class="flex gap-1">
                <button wire:click="switchMainTab('teacher')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $mainTab === 'teacher' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">Teacher Attendance</button>
                <button wire:click="switchMainTab('student')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $mainTab === 'student' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">Student Attendance</button>
                <button wire:click="switchMainTab('class_teachers')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $mainTab === 'class_teachers' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">Class Teachers</button>
            </div>
        </div>

        {{-- ══════════ FILTER BAND — stuck to header, full width (exams style) ══════════ --}}
        @php
            $showTeacherFilter = $mainTab === 'teacher';
            $showStudentFilter = $mainTab === 'student';
            $showCtFilter      = $mainTab === 'class_teachers';
        @endphp
        @if ($showTeacherFilter || $showStudentFilter || $showCtFilter)
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    Filter by:
                </div>

                {{-- ─── TEACHER ─── --}}
                @if ($mainTab === 'teacher')
                    <div class="inline-flex rounded-md border border-gray-200 bg-white p-0.5">
                        @foreach (['by_date' => 'By Date', 'by_month' => 'By Month', 'by_teacher' => 'By Teacher'] as $k => $label)
                            <button wire:click="switchTeacherView('{{ $k }}')"
                                class="px-3 py-1 text-xs font-semibold rounded transition-colors {{ $teacherView === $k ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50' }}">{{ $label }}</button>
                        @endforeach
                    </div>
                    {{-- Every control carries a wire:key naming its view. Without
                         one, Livewire's DOM morph reuses the control sitting in
                         the same slot of the previous view — which is what made
                         the By Teacher pickers bind to the wrong property. --}}
                    @if ($teacherView === 'by_date')
                        <input type="date" wire:key="t-bydate-date" wire:model.live="tDate" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 max-w-full sm:max-w-[13rem] truncate">
                        <select wire:key="t-bydate-status" wire:model.live="tByDateStatus" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 max-w-full sm:max-w-[13rem] truncate">
                            <option value="">All Status</option>
                            <option value="present">Present</option>
                            <option value="absent">Absent</option>
                            <option value="half_day">Half Day</option>
                            <option value="holiday">Holiday</option>
                            <option value="not_marked">Not Marked</option>
                        </select>
                    @elseif ($teacherView === 'by_month')
                        <input type="month" wire:key="t-bymonth-month" wire:model.live="tMonth" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 max-w-full sm:max-w-[13rem] truncate">
                        <select wire:key="t-bymonth-teacher" wire:model.live="tTeacherId" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 max-w-full sm:max-w-[13rem] truncate">
                            <option value="">Select teacher…</option>
                            @foreach ($teachers as $t)<option value="{{ $t->id }}">{{ $t->user->name ?? '—' }}</option>@endforeach
                        </select>
                    @elseif ($teacherView === 'by_teacher')
                        {{-- Method 3 · teacher → a specific month OR the whole school year --}}
                        <select wire:key="t-byteacher-teacher" wire:model.live="tTeacherId" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 max-w-full sm:max-w-[13rem] truncate">
                            <option value="">Select teacher…</option>
                            @foreach ($teachers as $t)<option value="{{ $t->id }}">{{ $t->user->name ?? '—' }}</option>@endforeach
                        </select>
                        <select wire:key="t-byteacher-range" wire:model.live="tRange" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 max-w-full sm:max-w-[13rem] truncate">
                            <option value="monthly">By Month</option>
                            <option value="yearly">Complete Year</option>
                        </select>
                        @if ($tRange === 'yearly')
                            {{-- The school year runs April → March, so the picker
                                 takes its starting year: 2026 = Apr 26–Mar 27. --}}
                            <select wire:key="t-byteacher-year" wire:model.live="tYear" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 max-w-full sm:max-w-[13rem] truncate">
                                @foreach ($academicYears as $y)<option value="{{ $y }}">Apr {{ $y }} – Mar {{ $y + 1 }}</option>@endforeach
                            </select>
                        @else
                            <input type="month" wire:key="t-byteacher-month" wire:model.live="tMonth" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 max-w-full sm:max-w-[13rem] truncate">
                        @endif
                    @endif

                {{-- ─── STUDENT ─── --}}
                @elseif ($mainTab === 'student')
                    <div class="inline-flex rounded-md border border-gray-200 bg-white p-0.5">
                        @foreach (['by_date' => 'By Date', 'by_student' => 'By Student'] as $k => $label)
                            <button wire:click="switchStudentView('{{ $k }}')"
                                class="px-3 py-1 text-xs font-semibold rounded transition-colors {{ $studentView === $k ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50' }}">{{ $label }}</button>
                        @endforeach
                    </div>
                    <select wire:key="s-standard" wire:model.live="stStandard" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 max-w-full sm:max-w-[13rem] truncate">
                        <option value="">Select class…</option>
                        @foreach ($standards as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                    </select>
                    <select wire:key="s-section" wire:model.live="stSection" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 max-w-full sm:max-w-[13rem] truncate">
                        <option value="">Select section…</option>
                        @foreach ($stSections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                    </select>
                    @if ($studentView === 'by_date')
                        <input type="date" wire:key="s-bydate-date" wire:model.live="stDate" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 max-w-full sm:max-w-[13rem] truncate">
                    @else
                        <select wire:key="s-bystudent-student" wire:model.live="stStudentId" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 max-w-full sm:max-w-[13rem] truncate">
                            <option value="">Select student…</option>
                            @foreach ($stStudents as $s)<option value="{{ $s->id }}">{{ $s->user->name ?? $s->full_name }}</option>@endforeach
                        </select>
                        <select wire:key="s-bystudent-range" wire:model.live="stRange" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 max-w-full sm:max-w-[13rem] truncate">
                            <option value="monthly">By Month</option>
                            <option value="yearly">Complete Year</option>
                        </select>
                        @if ($stRange === 'yearly')
                            <select wire:key="s-bystudent-year" wire:model.live="stYear" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 max-w-full sm:max-w-[13rem] truncate">
                                @foreach ($academicYears as $y)<option value="{{ $y }}">Apr {{ $y }} – Mar {{ $y + 1 }}</option>@endforeach
                            </select>
                        @else
                            <input type="month" wire:key="s-bystudent-month" wire:model.live="stMonth" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 max-w-full sm:max-w-[13rem] truncate">
                        @endif
                    @endif

                {{-- ─── CLASS TEACHERS ─── --}}
                @else
                    <div class="inline-flex rounded-md border border-gray-200 bg-white p-0.5">
                        <button wire:click="setCtMode('by_class')"
                            class="px-3 py-1 text-xs font-semibold rounded transition-colors {{ $ctMode === 'by_class' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50' }}">By Class</button>
                        <button wire:click="setCtMode('by_teacher')"
                            class="px-3 py-1 text-xs font-semibold rounded transition-colors {{ $ctMode === 'by_teacher' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50' }}">By Teacher</button>
                    </div>
                    @if ($ctMode === 'by_class')
                        <select wire:model.live="ctFilterStandard" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 max-w-full sm:max-w-[13rem] truncate">
                            <option value="">Select class…</option>
                            @foreach ($standards as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                        </select>
                        <select wire:model.live="ctFilterSection" @disabled(!$ctFilterStandard) class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 max-w-full sm:max-w-[13rem] truncate">
                            <option value="">All sections</option>
                            @foreach ($ctSections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                        </select>
                    @else
                        <select wire:model.live="ctFilterTeacher" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 max-w-full sm:max-w-[13rem] truncate">
                            <option value="">Select teacher…</option>
                            @foreach ($teachers as $t)<option value="{{ $t->id }}">{{ $t->user->name ?? '—' }}</option>@endforeach
                        </select>
                    @endif
                    @if ($ctFilterStandard || $ctFilterSection || $ctFilterTeacher)
                        <button wire:click="clearCtFilters"
                            class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            Clear
                        </button>
                    @endif
                @endif
            </div>
        </div>
        @endif
    </div>

    <div class="p-4 sm:p-6">

        {{-- ═══════════════════════════════════════════════════════════════════
             TEACHER ATTENDANCE
        ═══════════════════════════════════════════════════════════════════ --}}
        @if ($mainTab === 'teacher')
            {{-- ─── BY DATE ─── --}}
            @if ($teacherView === 'by_date')
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-5">
                    {{-- The day's analytics ride in the card header, not below the table. --}}
                    @if ($tByDateStats)
                        @include('livewire.admin._partials.attendance-dayheader', [
                            'stats' => $tByDateStats,
                            'title' => 'Teacher Attendance · ' . \Carbon\Carbon::parse($tDate)->format('l, d M Y'),
                        ])
                    @endif
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm min-w-[560px]">
                            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                                <tr>
                                    <th class="px-4 py-3 text-left w-12">#</th>
                                    <th class="px-4 py-3 text-left">Teacher</th>
                                    <th class="px-4 py-3 text-center w-32">Status</th>
                                    <th class="px-4 py-3 text-left">Remark</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($tByDateRows as $i => $row)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-400">{{ $i + 1 }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                @if ($row['image'])
                                                    <img src="{{ $row['image'] }}" class="w-9 h-9 rounded-full object-cover border border-gray-200">
                                                @else
                                                    <div class="w-9 h-9 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 font-bold text-xs">{{ strtoupper(substr($row['name'], 0, 1)) }}</div>
                                                @endif
                                                <div class="min-w-0">
                                                    <p class="font-medium text-gray-800 truncate">{{ $row['name'] }}</p>
                                                    <p class="text-xs text-gray-400 truncate">{{ $row['email'] }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $statusPill($row['status']) }}">{{ $statusText($row['status']) }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-500">{{ $row['remark'] ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-10 text-center text-gray-400">No teachers found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- ─── BY MONTH (month + teacher → month card, payroll style) ─── --}}
            @if ($teacherView === 'by_month')
                @if ($tCards)
                    @include('livewire.admin._partials.attendance-monthcards', ['cards' => $tCards, 'title' => $tCardsTitle, 'person' => $tCardsPerson])
                @else
                    <div class="bg-white rounded-xl border border-gray-200 py-12 text-center text-gray-400 text-sm">Select a teacher to view the monthly attendance.</div>
                @endif
            @endif

            {{-- ─── BY TEACHER (monthly / complete year — same card, more months) ─── --}}
            @if ($teacherView === 'by_teacher')
                @if (!$tTeacherId)
                    <div class="bg-white rounded-xl border border-gray-200 py-12 text-center text-gray-400 text-sm">Select a teacher to view analytics.</div>
                @elseif ($tCards)
                    @include('livewire.admin._partials.attendance-monthcards', ['cards' => $tCards, 'title' => $tCardsTitle, 'person' => $tCardsPerson])
                @else
                    <div class="bg-white rounded-xl border border-gray-200 py-12 text-center text-gray-400 text-sm">Pick a {{ $tRange === 'yearly' ? 'school year' : 'month' }} to view analytics.</div>
                @endif
            @endif
        @endif

        {{-- ═══════════════════════════════════════════════════════════════════
             STUDENT ATTENDANCE
        ═══════════════════════════════════════════════════════════════════ --}}
        @if ($mainTab === 'student')

            {{-- ─── BY DATE ─── --}}
            @if ($studentView === 'by_date')
                @if ($stStandard && $stSection)
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-5">
                        @if ($sByDateStats)
                            @include('livewire.admin._partials.attendance-dayheader', [
                                'stats' => $sByDateStats,
                                'title' => 'Student Attendance · ' . \Carbon\Carbon::parse($stDate)->format('l, d M Y'),
                            ])
                        @endif
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm min-w-[560px]">
                                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                                    <tr>
                                        <th class="px-4 py-3 text-left w-12">#</th>
                                        <th class="px-4 py-3 text-left">Student</th>
                                        <th class="px-4 py-3 text-center w-32">Status</th>
                                        <th class="px-4 py-3 text-left">Remark</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse ($sByDateRows as $i => $row)
                                        <tr>
                                            <td class="px-4 py-3 text-gray-400">{{ $i + 1 }}</td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-3">
                                                    @if ($row['image'])
                                                        <img src="{{ $row['image'] }}" class="w-9 h-9 rounded-full object-cover border border-gray-200">
                                                    @else
                                                        <div class="w-9 h-9 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 font-bold text-xs">{{ strtoupper(substr($row['name'], 0, 1)) }}</div>
                                                    @endif
                                                    <div class="min-w-0">
                                                        <p class="font-medium text-gray-800 truncate">{{ $row['name'] }}</p>
                                                        <p class="text-xs text-gray-400 truncate">{{ $row['email'] }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $statusPill($row['status']) }}">{{ $statusText($row['status']) }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-gray-500">{{ $row['remark'] ?: '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="px-4 py-10 text-center text-gray-400">No students found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="bg-white rounded-xl border border-gray-200 py-12 text-center text-gray-400 text-sm">Select class &amp; section to view attendance.</div>
                @endif
            @endif

            {{-- ─── BY STUDENT (month OR complete year — payroll-style cards) ─── --}}
            @if ($studentView === 'by_student')
                @if (!$stStudentId)
                    <div class="bg-white rounded-xl border border-gray-200 py-12 text-center text-gray-400 text-sm">Select class, section &amp; student to view the attendance.</div>
                @elseif ($sCards)
                    @include('livewire.admin._partials.attendance-monthcards', ['cards' => $sCards, 'title' => $sCardsTitle, 'person' => $sCardsPerson])
                @else
                    <div class="bg-white rounded-xl border border-gray-200 py-12 text-center text-gray-400 text-sm">Pick a {{ $stRange === 'yearly' ? 'school year' : 'month' }} to view the attendance.</div>
                @endif
            @endif
        @endif

        {{-- ═══════════════════════════════════════════════════════════════════
             CLASS TEACHERS
        ═══════════════════════════════════════════════════════════════════ --}}
        @if ($mainTab === 'class_teachers')
            {{-- Context line for the active lookup method --}}
            <div class="mb-4 text-sm text-gray-500">
                @if ($ctMode === 'by_class')
                    @if ($ctFilterStandard)
                        Showing class teacher(s) assigned to
                        <strong class="text-gray-700">{{ optional($standards->firstWhere('id', (int) $ctFilterStandard))->name }}</strong>@if ($ctFilterSection) ·
                        <strong class="text-gray-700">{{ optional($ctSections->firstWhere('id', (int) $ctFilterSection))->name }}</strong>@else (all sections)@endif.
                    @else
                        Pick a class above to see its assigned class teacher(s); add a section to narrow to one.
                    @endif
                @else
                    @if ($ctFilterTeacher)
                        Showing the class &amp; section
                        <strong class="text-gray-700">{{ optional($teachers->firstWhere('id', (int) $ctFilterTeacher))->user?->name }}</strong>
                        is assigned as class teacher for.
                    @else
                        Pick a teacher above to see which class &amp; section they are the class teacher of.
                    @endif
                @endif
            </div>

            {{-- Assigned listing --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[640px]">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                            <tr>
                                <th class="px-4 py-3 text-left w-12">#</th>
                                <th class="px-4 py-3 text-left">Class Teacher</th>
                                <th class="px-4 py-3 text-left">Assigned Class &amp; Section</th>
                                <th class="px-4 py-3 text-center w-28">Status</th>
                                <th class="px-4 py-3 text-center w-28">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($assignments as $i => $a)
                                <tr class="hover:bg-gray-50/70">
                                    <td class="px-4 py-3 text-gray-400">{{ $i + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            @if ($a->teacher?->user?->image)
                                                <img src="{{ $a->teacher->user->image }}" class="w-9 h-9 rounded-full object-cover border border-gray-200">
                                            @else
                                                <div class="w-9 h-9 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 font-bold text-xs">{{ strtoupper(substr($a->teacher?->user?->name ?? 'T', 0, 1)) }}</div>
                                            @endif
                                            <div class="min-w-0">
                                                <p class="font-medium text-gray-800 truncate">{{ $a->teacher?->user?->name ?? '—' }}</p>
                                                <p class="text-xs text-gray-400 truncate">{{ $a->teacher?->user?->email ?? '' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-100">
                                            {{ $a->standard->name ?? '—' }}@if ($a->section)<span class="text-blue-400">·</span> {{ $a->section->name }}@endif
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700">Assigned</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button wire:click="editAssign({{ $a->id }})" class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-amber-50 hover:text-amber-600" title="Edit">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </button>
                                            <button wire:click="confirmDeleteAssign({{ $a->id }})" class="p-1.5 rounded-md border border-red-200 text-red-500 hover:bg-red-50" title="Remove">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-12 text-center text-gray-400">
                                    No class teachers assigned{{ ($ctFilterStandard || $ctFilterSection || $ctFilterTeacher) ? ' for this filter' : '' }}.
                                    <button wire:click="openAssignPanel" class="block mx-auto mt-2 text-sm text-blue-600 hover:text-blue-800 font-medium">Assign a class teacher →</button>
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    {{-- ══════════ MARK ATTENDANCE SLIDE-INS ══════════
         Both panels follow the student add/edit panel: light scrim, plain
         header, one quiet toolbar, a flat list of rows, and the actions in the
         footer. Picking a status is handled by Alpine and only synced to the
         component — no request per click, so a class of forty marks as fast as
         you can tap and nothing is lost to a re-render mid-click. --}}
    @php
        $statusOpts = ['present' => 'Present', 'absent' => 'Absent', 'half_day' => 'Half', 'holiday' => 'Holiday'];
        $statusSel  = [
            'present'  => 'bg-emerald-50 text-emerald-700 font-medium',
            'absent'   => 'bg-red-50 text-red-600 font-medium',
            'half_day' => 'bg-amber-50 text-amber-700 font-medium',
            'holiday'  => 'bg-indigo-50 text-indigo-700 font-medium',
        ];
    @endphp

    {{-- ══════════ TEACHERS ══════════ --}}
    @if ($showTeacherMarkPanel)
    <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeTeacherMark"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col"
            wire:key="tmark-{{ $tMarkDate }}-{{ count($teacherMark) }}"
            x-data="{
                rows: @js(collect($teacherMark)->map(fn ($r) => (string) ($r['status'] ?? ''))->all()),
                get total() { return Object.keys(this.rows).length },
                get marked() { return Object.values(this.rows).filter(v => v !== '').length },
                pick(id, v) {
                    this.rows[id] = v;
                    /* Local set: the change rides along with the next request
                       (Save) instead of costing a round trip per click. */
                    this.$wire.$set('teacherMark.' + id + '.status', v, false);
                },
                all(v) { Object.keys(this.rows).forEach(id => this.pick(id, v)) },
            }">

            {{-- Re-seeds the panel from the server after the date changes, whether
                 the morph replaced the panel or updated it in place. --}}
            <div class="hidden" wire:key="tmark-seed-{{ $tMarkDate }}"
                x-init="rows = @js(collect($teacherMark)->map(fn ($r) => (string) ($r['status'] ?? ''))->all())"></div>

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-900">Mark Teacher Attendance</h2>
                    <p class="text-xs text-gray-500 mt-0.5">{{ \Carbon\Carbon::parse($tMarkDate)->format('l, d M Y') }}</p>
                </div>
                <button wire:click="closeTeacherMark" type="button"
                    class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Toolbar --}}
            <div class="px-6 py-3 border-b border-gray-100 flex flex-wrap items-center gap-2 flex-shrink-0">
                <input type="date" wire:model.live="tMarkDate"
                    class="text-sm border border-gray-300 rounded-md px-3 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                <div class="inline-flex items-center rounded-md border border-gray-200 overflow-hidden text-xs">
                    <button type="button" x-on:click="all('present')" class="px-2.5 py-1.5 text-gray-600 hover:bg-gray-50">All present</button>
                    <button type="button" x-on:click="all('absent')" class="px-2.5 py-1.5 text-gray-600 hover:bg-gray-50 border-l border-gray-200">All absent</button>
                    <button type="button" x-on:click="all('')" class="px-2.5 py-1.5 text-gray-500 hover:bg-gray-50 border-l border-gray-200">Clear</button>
                </div>
                <span class="ml-auto text-xs text-gray-400 tabular-nums" x-text="marked + ' of ' + total + ' marked'"></span>
            </div>

            {{-- One quiet line explaining the day --}}
            @if ($teacherMarkExisting)
                <p class="px-6 py-2 text-xs text-amber-700 border-b border-gray-100 flex-shrink-0">Already submitted for this date — change what you need and save to update it.</p>
            @elseif (\Carbon\Carbon::parse($tMarkDate)->isSunday())
                <p class="px-6 py-2 text-xs text-gray-500 border-b border-gray-100 flex-shrink-0">Sunday is a standing holiday, so everyone starts on Holiday.</p>
            @else
                <p class="px-6 py-2 text-xs text-gray-500 border-b border-gray-100 flex-shrink-0">Only the rows you set are saved — an unmarked day stays open.</p>
            @endif

            {{-- Rows --}}
            <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
                @forelse ($markTeachers as $i => $t)
                    <div wire:key="mark-t-{{ $t->id }}" class="flex flex-wrap items-center gap-x-3 gap-y-2 px-6 py-2.5"
                        :class="rows[{{ $t->id }}] === '' ? 'bg-gray-50/60' : ''">
                        <span class="w-4 text-[11px] text-gray-300 tabular-nums flex-shrink-0">{{ $i + 1 }}</span>
                        @if ($t->user?->image)
                            <img src="{{ $t->user->image }}" class="w-8 h-8 rounded-full object-cover border border-gray-200 flex-shrink-0">
                        @else
                            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 text-[11px] font-medium flex-shrink-0">{{ strtoupper(substr($t->user->name ?? 'T', 0, 1)) }}</div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-gray-800 truncate">{{ $t->user->name ?? '—' }}</p>
                            <p class="text-[11px] text-gray-400 truncate">{{ $t->user->email ?? '' }}</p>
                        </div>
                        <input type="text" wire:model="teacherMark.{{ $t->id }}.remark" placeholder="Remark"
                            class="w-28 sm:w-36 text-xs border border-gray-200 rounded-md px-2.5 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <div class="inline-flex items-center rounded-md border border-gray-200 overflow-hidden text-[11px] flex-shrink-0">
                            @foreach ($statusOpts as $st => $label)
                                <button type="button" x-on:click="pick({{ $t->id }}, '{{ $st }}')"
                                    class="px-2.5 py-1.5 {{ $loop->first ? '' : 'border-l border-gray-200' }}"
                                    :class="rows[{{ $t->id }}] === '{{ $st }}' ? '{{ $statusSel[$st] }}' : 'text-gray-500 hover:bg-gray-50'">{{ $label }}</button>
                            @endforeach
                            {{-- Leave a row blank and it saves nothing at all, so the
                                 day stays open to be marked later. --}}
                            <button type="button" x-on:click="pick({{ $t->id }}, '')" title="Leave unmarked"
                                class="px-2 py-1.5 border-l border-gray-200"
                                :class="rows[{{ $t->id }}] === '' ? 'bg-gray-100 text-gray-500' : 'text-gray-300 hover:text-gray-600 hover:bg-gray-50'">&times;</button>
                        </div>
                    </div>
                @empty
                    <p class="py-16 text-center text-sm text-gray-400">No teachers found.</p>
                @endforelse
            </div>

            {{-- Footer --}}
            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-between gap-2 flex-shrink-0">
                <button type="button" wire:click="markTeacherDayHoliday" wire:loading.attr="disabled" wire:target="markTeacherDayHoliday"
                    class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md disabled:opacity-60">
                    <span wire:loading.remove wire:target="markTeacherDayHoliday">{{ $tMarkDate === now()->toDateString() ? 'Mark today as holiday' : 'Mark this day as holiday' }}</span>
                    <span wire:loading wire:target="markTeacherDayHoliday">Saving...</span>
                </button>
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="closeTeacherMark" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button type="button" wire:click="submitTeacherAttendance" wire:loading.attr="disabled" wire:target="submitTeacherAttendance"
                        :disabled="marked === 0"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="submitTeacherAttendance">{{ $teacherMarkExisting ? 'Update Attendance' : 'Save Attendance' }}</span>
                        <span wire:loading wire:target="submitTeacherAttendance">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════ STUDENTS ══════════ --}}
    @if ($showStudentMarkPanel)
    <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeStudentMark"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col"
            wire:key="smark-{{ $sMarkDate }}-{{ $sMarkStandard }}-{{ $sMarkSection }}-{{ count($studentMark) }}"
            x-data="{
                rows: @js(collect($studentMark)->map(fn ($r) => (string) ($r['status'] ?? ''))->all()),
                get total() { return Object.keys(this.rows).length },
                get marked() { return Object.values(this.rows).filter(v => v !== '').length },
                pick(id, v) {
                    this.rows[id] = v;
                    this.$wire.$set('studentMark.' + id + '.status', v, false);
                },
                all(v) { Object.keys(this.rows).forEach(id => this.pick(id, v)) },
            }">

            {{-- Re-seeds the panel from the server after the class, section or
                 date changes, whether the morph replaced it or not. --}}
            <div class="hidden" wire:key="smark-seed-{{ $sMarkDate }}-{{ $sMarkStandard }}-{{ $sMarkSection }}"
                x-init="rows = @js(collect($studentMark)->map(fn ($r) => (string) ($r['status'] ?? ''))->all())"></div>

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-900">Mark Student Attendance</h2>
                    <p class="text-xs text-gray-500 mt-0.5">{{ \Carbon\Carbon::parse($sMarkDate)->format('l, d M Y') }}</p>
                </div>
                <button wire:click="closeStudentMark" type="button"
                    class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Toolbar: class → section → date, all local to this flow --}}
            <div class="px-6 py-3 border-b border-gray-100 flex flex-wrap items-center gap-2 flex-shrink-0">
                <select wire:model.live="sMarkStandard"
                    class="text-sm border border-gray-300 rounded-md px-3 py-1.5 bg-white focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                    <option value="">Select class…</option>
                    @foreach ($standards as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                </select>
                <select wire:model.live="sMarkSection" @disabled(!$sMarkStandard)
                    class="text-sm border border-gray-300 rounded-md px-3 py-1.5 bg-white disabled:opacity-50 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                    <option value="">Select section…</option>
                    @foreach ($sMarkSections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                </select>
                <input type="date" wire:model.live="sMarkDate"
                    class="text-sm border border-gray-300 rounded-md px-3 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                @if ($sMarkStandard && $sMarkSection)
                    <div class="inline-flex items-center rounded-md border border-gray-200 overflow-hidden text-xs">
                        <button type="button" x-on:click="all('present')" class="px-2.5 py-1.5 text-gray-600 hover:bg-gray-50">All present</button>
                        <button type="button" x-on:click="all('absent')" class="px-2.5 py-1.5 text-gray-600 hover:bg-gray-50 border-l border-gray-200">All absent</button>
                        <button type="button" x-on:click="all('')" class="px-2.5 py-1.5 text-gray-500 hover:bg-gray-50 border-l border-gray-200">Clear</button>
                    </div>
                    <span class="ml-auto text-xs text-gray-400 tabular-nums" x-text="marked + ' of ' + total + ' marked'"></span>
                @endif
            </div>

            @if ($studentMarkExisting)
                <p class="px-6 py-2 text-xs text-amber-700 border-b border-gray-100 flex-shrink-0">Already submitted for this date — change what you need and save to update it.</p>
            @elseif (\Carbon\Carbon::parse($sMarkDate)->isSunday())
                <p class="px-6 py-2 text-xs text-gray-500 border-b border-gray-100 flex-shrink-0">Sunday is a standing holiday, so everyone starts on Holiday.</p>
            @else
                <p class="px-6 py-2 text-xs text-gray-500 border-b border-gray-100 flex-shrink-0">Only the rows you set are saved — an unmarked day stays open.</p>
            @endif

            {{-- Rows --}}
            <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
                @if ($sMarkStandard && $sMarkSection)
                    @forelse ($markStudents as $i => $s)
                        <div wire:key="mark-s-{{ $s->id }}" class="flex flex-wrap items-center gap-x-3 gap-y-2 px-6 py-2.5"
                            :class="rows[{{ $s->id }}] === '' ? 'bg-gray-50/60' : ''">
                            <span class="w-4 text-[11px] text-gray-300 tabular-nums flex-shrink-0">{{ $i + 1 }}</span>
                            @if ($s->user?->image)
                                <img src="{{ $s->user->image }}" class="w-8 h-8 rounded-full object-cover border border-gray-200 flex-shrink-0">
                            @else
                                <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 text-[11px] font-medium flex-shrink-0">{{ strtoupper(substr($s->user->name ?? $s->full_name ?? 'S', 0, 1)) }}</div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-gray-800 truncate">{{ $s->user->name ?? $s->full_name }}</p>
                                <p class="text-[11px] text-gray-400 truncate">{{ $s->user->email ?? '' }}</p>
                            </div>
                            <input type="text" wire:model="studentMark.{{ $s->id }}.remark" placeholder="Remark"
                                class="w-28 sm:w-36 text-xs border border-gray-200 rounded-md px-2.5 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                            <div class="inline-flex items-center rounded-md border border-gray-200 overflow-hidden text-[11px] flex-shrink-0">
                                @foreach ($statusOpts as $st => $label)
                                    <button type="button" x-on:click="pick({{ $s->id }}, '{{ $st }}')"
                                        class="px-2.5 py-1.5 {{ $loop->first ? '' : 'border-l border-gray-200' }}"
                                        :class="rows[{{ $s->id }}] === '{{ $st }}' ? '{{ $statusSel[$st] }}' : 'text-gray-500 hover:bg-gray-50'">{{ $label }}</button>
                                @endforeach
                                <button type="button" x-on:click="pick({{ $s->id }}, '')" title="Leave unmarked"
                                    class="px-2 py-1.5 border-l border-gray-200"
                                    :class="rows[{{ $s->id }}] === '' ? 'bg-gray-100 text-gray-500' : 'text-gray-300 hover:text-gray-600 hover:bg-gray-50'">&times;</button>
                            </div>
                        </div>
                    @empty
                        <p class="py-16 text-center text-sm text-gray-400">No students in this class/section.</p>
                    @endforelse
                @else
                    <p class="py-16 text-center text-sm text-gray-400">Select a class &amp; section to start marking.</p>
                @endif
            </div>

            {{-- Footer --}}
            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-between gap-2 flex-shrink-0">
                <button type="button" wire:click="markStudentDayHoliday" wire:loading.attr="disabled" wire:target="markStudentDayHoliday"
                    @disabled(!$sMarkStandard || !$sMarkSection)
                    class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md disabled:opacity-40">
                    <span wire:loading.remove wire:target="markStudentDayHoliday">{{ $sMarkDate === now()->toDateString() ? 'Mark today as holiday' : 'Mark this day as holiday' }}</span>
                    <span wire:loading wire:target="markStudentDayHoliday">Saving...</span>
                </button>
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="closeStudentMark" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    {{-- Rows only exist once a class and section are picked, so the
                         marked count covers that case too. --}}
                    <button type="button" wire:click="submitStudentAttendance" wire:loading.attr="disabled" wire:target="submitStudentAttendance"
                        :disabled="marked === 0"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="submitStudentAttendance">{{ $studentMarkExisting ? 'Update Attendance' : 'Save Attendance' }}</span>
                        <span wire:loading wire:target="submitStudentAttendance">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════ ASSIGN CLASS TEACHER SLIDE-IN ══════════ --}}
    @if ($showAssignPanel)
        <div class="fixed inset-x-0 bottom-0 top-16 z-[9999] overflow-hidden">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[2px]" wire:click="closeAssignPanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $assignEditId ? 'Edit Assignment' : 'Assign Class Teacher' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Map a teacher to a class &amp; section.</p>
                    </div>
                    <button wire:click="closeAssignPanel" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Teacher <span class="text-red-500">*</span></label>
                        <select wire:model="assignTeacherId" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm bg-white">
                            <option value="">Select teacher…</option>
                            @foreach ($teachers as $t)<option value="{{ $t->id }}">{{ $t->user->name ?? '—' }}</option>@endforeach
                        </select>
                        @error('assignTeacherId')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Class <span class="text-red-500">*</span></label>
                            <select wire:model.live="assignStandardId" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm bg-white">
                                <option value="">Select…</option>
                                @foreach ($standards as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                            </select>
                            @error('assignStandardId')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Section</label>
                            <select wire:model="assignSectionId" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm bg-white">
                                <option value="">All / none</option>
                                @if ($assignStandardId)
                                    @foreach (\App\Models\Student\Section::where('standard_id', $assignStandardId)->get() as $sec)
                                        <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button wire:click="saveAssign" class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md">{{ $assignEditId ? 'Update Assignment' : 'Assign' }}</button>
                    </div>

                    <div class="border-t border-gray-100 pt-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Current Assignments</p>
                        <div class="space-y-2">
                            @forelse ($assignments as $a)
                                <div class="flex items-center justify-between gap-2 p-2.5 bg-gray-50 rounded-lg border border-gray-200">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate">{{ $a->teacher?->user?->name ?? '—' }}</p>
                                        <p class="text-xs text-gray-500">{{ $a->standard->name ?? '' }}{{ $a->section ? ' · ' . $a->section->name : '' }}</p>
                                    </div>
                                    <div class="flex items-center gap-1.5 flex-shrink-0">
                                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 mr-1">Assigned</span>
                                        <button wire:click="editAssign({{ $a->id }})" class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-amber-50 hover:text-amber-600" title="Edit">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </button>
                                        <button wire:click="confirmDeleteAssign({{ $a->id }})" class="p-1.5 rounded-md border border-red-200 text-red-500 hover:bg-red-50" title="Delete">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-gray-400">No assignments yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ DELETE ASSIGN CONFIRM ══════════ --}}
    @if ($pendingDeleteAssignId !== null)
        <div class="fixed inset-x-0 bottom-0 top-16 z-[9999] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDeleteAssign"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Remove assignment?</h3>
                        <p class="text-sm text-gray-500">This unassigns the class teacher. This action cannot be undone.</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button wire:click="cancelDeleteAssign" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="executeDeleteAssign" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">Remove</button>
                </div>
            </div>
        </div>
    @endif
</div>
