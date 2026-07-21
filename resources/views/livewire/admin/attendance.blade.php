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
        <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg sm:text-2xl font-bold text-gray-900">Attendance</h1>
                <p class="text-sm text-gray-500 mt-0.5">Mark &amp; review teacher and student attendance</p>
            </div>
            @if ($mainTab === 'teacher')
                @if ($teacherView === 'mark')
                    <button wire:click="closeTeacherMark"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold rounded-lg border border-gray-200 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                        Back to Records
                    </button>
                @else
                    <button wire:click="openTeacherMark"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                        Mark Attendance
                    </button>
                @endif
            @elseif ($mainTab === 'student')
                @if ($studentView === 'mark')
                    <button wire:click="closeStudentMark"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold rounded-lg border border-gray-200 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                        Back to Records
                    </button>
                @else
                    <button wire:click="openStudentMark"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                        Mark Attendance
                    </button>
                @endif
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
            $showTeacherFilter = $mainTab === 'teacher' && $teacherView !== 'mark';
            $showStudentFilter = $mainTab === 'student';
            $showCtFilter      = $mainTab === 'class_teachers';
        @endphp
        @if ($showTeacherFilter || $showStudentFilter || $showCtFilter)
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-3">
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
                    @if ($teacherView === 'by_date')
                        <span class="text-gray-300">→</span>
                        <input type="date" wire:model.live="tDate" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <span class="text-gray-300">→</span>
                        <select wire:model.live="tByDateStatus" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                            <option value="">All Status</option>
                            <option value="present">Present</option>
                            <option value="absent">Absent</option>
                            <option value="half_day">Half Day</option>
                            <option value="holiday">Holiday</option>
                            <option value="not_marked">Not Marked</option>
                        </select>
                    @elseif ($teacherView === 'by_month')
                        <span class="text-gray-300">→</span>
                        <input type="month" wire:model.live="tMonth" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <span class="text-gray-300">→</span>
                        <select wire:model.live="tTeacherId" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                            <option value="">Select teacher…</option>
                            @foreach ($teachers as $t)<option value="{{ $t->id }}">{{ $t->user->name ?? '—' }}</option>@endforeach
                        </select>
                    @elseif ($teacherView === 'by_teacher')
                        {{-- Method 3 · teacher → a specific month OR the complete year --}}
                        <span class="text-gray-300">→</span>
                        <select wire:model.live="tTeacherId" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                            <option value="">Select teacher…</option>
                            @foreach ($teachers as $t)<option value="{{ $t->id }}">{{ $t->user->name ?? '—' }}</option>@endforeach
                        </select>
                        <span class="text-gray-300">→</span>
                        <select wire:model.live="tRange" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                            <option value="monthly">By Month</option>
                            <option value="yearly">Complete Year</option>
                        </select>
                        <span class="text-gray-300">→</span>
                        @if ($tRange === 'yearly')
                            <input type="number" min="2000" max="2100" wire:model.live="tYear" class="w-24 text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        @else
                            <input type="month" wire:model.live="tMonth" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        @endif
                    @endif

                {{-- ─── STUDENT ─── --}}
                @elseif ($mainTab === 'student')
                    @if ($studentView === 'mark')
                        {{-- Mark flow: pick class → section → date, then the list appears below --}}
                        <span class="text-xs font-semibold text-gray-500">Choose class &amp; section to mark:</span>
                        <select wire:model.live="stStandard" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                            <option value="">Select class…</option>
                            @foreach ($standards as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                        </select>
                        <span class="text-gray-300">→</span>
                        <select wire:model.live="stSection" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                            <option value="">Select section…</option>
                            @foreach ($stSections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                        </select>
                        <span class="text-gray-300">→</span>
                        <input type="date" wire:model.live="stDate" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    @else
                        <div class="inline-flex rounded-md border border-gray-200 bg-white p-0.5">
                            @foreach (['by_date' => 'By Date', 'by_student' => 'By Student'] as $k => $label)
                                <button wire:click="switchStudentView('{{ $k }}')"
                                    class="px-3 py-1 text-xs font-semibold rounded transition-colors {{ $studentView === $k ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50' }}">{{ $label }}</button>
                            @endforeach
                        </div>
                        <span class="text-gray-300">→</span>
                        <select wire:model.live="stStandard" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                            <option value="">Select class…</option>
                            @foreach ($standards as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                        </select>
                        <span class="text-gray-300">→</span>
                        <select wire:model.live="stSection" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                            <option value="">Select section…</option>
                            @foreach ($stSections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                        </select>
                        @if ($studentView === 'by_date')
                            <span class="text-gray-300">→</span>
                            <input type="date" wire:model.live="stDate" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        @else
                            <span class="text-gray-300">→</span>
                            <select wire:model.live="stStudentId" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                                <option value="">Select student…</option>
                                @foreach ($stStudents as $s)<option value="{{ $s->id }}">{{ $s->user->name ?? $s->full_name }}</option>@endforeach
                            </select>
                            <span class="text-gray-300">→</span>
                            <select wire:model.live="stRange" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                                <option value="monthly">By Month</option>
                                <option value="yearly">Complete Year</option>
                            </select>
                            <span class="text-gray-300">→</span>
                            @if ($stRange === 'yearly')
                                <input type="number" min="2000" max="2100" wire:model.live="stYear" class="w-24 text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                            @else
                                <input type="month" wire:model.live="stMonth" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                            @endif
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
                        <span class="text-gray-300">→</span>
                        <select wire:model.live="ctFilterStandard" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                            <option value="">Select class…</option>
                            @foreach ($standards as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                        </select>
                        <span class="text-gray-300">→</span>
                        <select wire:model.live="ctFilterSection" @disabled(!$ctFilterStandard) class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                            <option value="">All sections</option>
                            @foreach ($ctSections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                        </select>
                    @else
                        <span class="text-gray-300">→</span>
                        <select wire:model.live="ctFilterTeacher" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
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
            {{-- ─── MARK ─── --}}
            @if ($teacherView === 'mark')
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <label class="text-sm text-gray-600">Date</label>
                            <input type="date" wire:model.live="tDate" class="text-sm border border-gray-300 rounded-md px-3 py-1.5">
                            <button wire:click="markAllTeachers('present')" class="px-3 py-1.5 text-xs font-semibold rounded-md border border-emerald-200 text-emerald-700 bg-emerald-50 hover:bg-emerald-100">Mark all Present</button>
                            <button wire:click="markAllTeachers('holiday')" class="px-3 py-1.5 text-xs font-semibold rounded-md border border-indigo-200 text-indigo-700 bg-indigo-50 hover:bg-indigo-100">Mark all as Holiday</button>
                        </div>
                        <button wire:click="submitTeacherAttendance" wire:loading.attr="disabled"
                            class="inline-flex items-center gap-1.5 px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-semibold rounded-lg disabled:opacity-60">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            <span wire:loading.remove wire:target="submitTeacherAttendance">Save Attendance</span>
                            <span wire:loading wire:target="submitTeacherAttendance">Saving…</span>
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm min-w-[640px]">
                            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                                <tr>
                                    <th class="px-4 py-3 text-left w-12">#</th>
                                    <th class="px-4 py-3 text-left">Teacher</th>
                                    <th class="px-4 py-3 text-center w-72">Status</th>
                                    <th class="px-4 py-3 text-left w-56">Remark</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($markTeachers as $i => $t)
                                    <tr wire:key="mark-t-{{ $t->id }}">
                                        <td class="px-4 py-3 text-gray-400">{{ $i + 1 }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                @if ($t->user?->image)
                                                    <img src="{{ $t->user->image }}" class="w-9 h-9 rounded-full object-cover border border-gray-200">
                                                @else
                                                    <div class="w-9 h-9 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 font-bold text-xs">{{ strtoupper(substr($t->user->name ?? 'T', 0, 1)) }}</div>
                                                @endif
                                                <div class="min-w-0">
                                                    <p class="font-medium text-gray-800 truncate">{{ $t->user->name ?? '—' }}</p>
                                                    <p class="text-xs text-gray-400 truncate">{{ $t->user->email ?? '' }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            @php $cur = $teacherMark[$t->id]['status'] ?? 'present'; @endphp
                                            <div class="flex items-center justify-center gap-1">
                                                <button wire:click="setTeacherStatus({{ $t->id }}, 'present')" class="px-2.5 py-1.5 text-xs font-semibold rounded-md border {{ $cur === 'present' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-gray-600 border-gray-200' }}">Present</button>
                                                <button wire:click="setTeacherStatus({{ $t->id }}, 'absent')" class="px-2.5 py-1.5 text-xs font-semibold rounded-md border {{ $cur === 'absent' ? 'bg-red-600 text-white border-red-600' : 'bg-white text-gray-600 border-gray-200' }}">Absent</button>
                                                <button wire:click="setTeacherStatus({{ $t->id }}, 'half_day')" class="px-2.5 py-1.5 text-xs font-semibold rounded-md border {{ $cur === 'half_day' ? 'bg-amber-500 text-white border-amber-500' : 'bg-white text-gray-600 border-gray-200' }}">Half Day</button>
                                                @if ($cur === 'holiday')
                                                    <span class="px-2.5 py-1.5 text-xs font-semibold rounded-md bg-indigo-100 text-indigo-700">Holiday</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <input type="text" wire:model="teacherMark.{{ $t->id }}.remark" placeholder="Optional remark" class="w-full text-sm border border-gray-200 rounded-md px-3 py-1.5">
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-10 text-center text-gray-400">No teachers found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- ─── BY DATE ─── --}}
            @if ($teacherView === 'by_date')
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-5">
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
                @if ($tByDateStats)
                    @include('livewire.admin._partials.attendance-daystats', ['stats' => $tByDateStats])
                @endif
            @endif

            {{-- ─── BY MONTH (month + teacher → compact month card with small analytics) ─── --}}
            @if ($teacherView === 'by_month')
                @if ($tMonthCalendar)
                    @include('livewire.admin._partials.attendance-calendar', ['calendar' => $tMonthCalendar, 'compact' => true])
                @else
                    <div class="bg-white rounded-xl border border-gray-200 py-12 text-center text-gray-400 text-sm">Select a teacher to view the monthly attendance.</div>
                @endif
            @endif

            {{-- ─── BY TEACHER (monthly / yearly) ─── --}}
            @if ($teacherView === 'by_teacher')
                @if (!$tTeacherId)
                    <div class="bg-white rounded-xl border border-gray-200 py-12 text-center text-gray-400 text-sm">Select a teacher to view analytics.</div>
                @elseif ($tRange === 'yearly' && $tTeacherYearly)
                    @include('livewire.admin._partials.attendance-yearly', ['yearly' => $tTeacherYearly])
                @elseif ($tTeacherCalendar)
                    @include('livewire.admin._partials.attendance-calendar', ['calendar' => $tTeacherCalendar])
                @endif
            @endif
        @endif

        {{-- ═══════════════════════════════════════════════════════════════════
             STUDENT ATTENDANCE
        ═══════════════════════════════════════════════════════════════════ --}}
        @if ($mainTab === 'student')

            {{-- ─── MARK ─── --}}
            @if ($studentView === 'mark')
                @if ($stStandard && $stSection)
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm text-gray-600">{{ $markStudents->count() }} student(s)</span>
                                <button wire:click="markAllStudents('present')" class="px-3 py-1.5 text-xs font-semibold rounded-md border border-emerald-200 text-emerald-700 bg-emerald-50 hover:bg-emerald-100">Mark all Present</button>
                                <button wire:click="markAllStudents('holiday')" class="px-3 py-1.5 text-xs font-semibold rounded-md border border-indigo-200 text-indigo-700 bg-indigo-50 hover:bg-indigo-100">Mark all as Holiday</button>
                            </div>
                            <button wire:click="submitStudentAttendance" wire:loading.attr="disabled"
                                class="inline-flex items-center gap-1.5 px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-semibold rounded-lg disabled:opacity-60">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                <span wire:loading.remove wire:target="submitStudentAttendance">Save</span>
                                <span wire:loading wire:target="submitStudentAttendance">Saving…</span>
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm min-w-[640px]">
                                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                                    <tr>
                                        <th class="px-4 py-3 text-left w-12">#</th>
                                        <th class="px-4 py-3 text-left">Student</th>
                                        <th class="px-4 py-3 text-center w-72">Status</th>
                                        <th class="px-4 py-3 text-left w-56">Remark</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse ($markStudents as $i => $s)
                                        <tr wire:key="mark-s-{{ $s->id }}">
                                            <td class="px-4 py-3 text-gray-400">{{ $i + 1 }}</td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-3">
                                                    @if ($s->user?->image)
                                                        <img src="{{ $s->user->image }}" class="w-9 h-9 rounded-full object-cover border border-gray-200">
                                                    @else
                                                        <div class="w-9 h-9 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 font-bold text-xs">{{ strtoupper(substr($s->user->name ?? 'S', 0, 1)) }}</div>
                                                    @endif
                                                    <div class="min-w-0">
                                                        <p class="font-medium text-gray-800 truncate">{{ $s->user->name ?? $s->full_name }}</p>
                                                        <p class="text-xs text-gray-400 truncate">{{ $s->user->email ?? '' }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                @php $cur = $studentMark[$s->id]['status'] ?? 'present'; @endphp
                                                <div class="flex items-center justify-center gap-1">
                                                    <button wire:click="setStudentStatus({{ $s->id }}, 'present')" class="px-2.5 py-1.5 text-xs font-semibold rounded-md border {{ $cur === 'present' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-gray-600 border-gray-200' }}">Present</button>
                                                    <button wire:click="setStudentStatus({{ $s->id }}, 'absent')" class="px-2.5 py-1.5 text-xs font-semibold rounded-md border {{ $cur === 'absent' ? 'bg-red-600 text-white border-red-600' : 'bg-white text-gray-600 border-gray-200' }}">Absent</button>
                                                    <button wire:click="setStudentStatus({{ $s->id }}, 'half_day')" class="px-2.5 py-1.5 text-xs font-semibold rounded-md border {{ $cur === 'half_day' ? 'bg-amber-500 text-white border-amber-500' : 'bg-white text-gray-600 border-gray-200' }}">Half Day</button>
                                                    @if ($cur === 'holiday')
                                                        <span class="px-2.5 py-1.5 text-xs font-semibold rounded-md bg-indigo-100 text-indigo-700">Holiday</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <input type="text" wire:model="studentMark.{{ $s->id }}.remark" placeholder="Optional remark" class="w-full text-sm border border-gray-200 rounded-md px-3 py-1.5">
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="px-4 py-10 text-center text-gray-400">No students in this class/section.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="bg-white rounded-xl border border-gray-200 py-12 text-center text-gray-400 text-sm">Select class &amp; section to mark attendance.</div>
                @endif
            @endif

            {{-- ─── BY DATE ─── --}}
            @if ($studentView === 'by_date')
                @if ($stStandard && $stSection)
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-5">
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
                    @if ($sByDateStats)
                        @include('livewire.admin._partials.attendance-daystats', ['stats' => $sByDateStats])
                    @endif
                @else
                    <div class="bg-white rounded-xl border border-gray-200 py-12 text-center text-gray-400 text-sm">Select class &amp; section to view attendance.</div>
                @endif
            @endif

            {{-- ─── BY STUDENT (student → month OR complete year) ─── --}}
            @if ($studentView === 'by_student')
                @if (!$stStudentId)
                    <div class="bg-white rounded-xl border border-gray-200 py-12 text-center text-gray-400 text-sm">Select class, section &amp; student to view the attendance.</div>
                @elseif ($stRange === 'yearly' && $sStudentYearly)
                    @include('livewire.admin._partials.attendance-yearly', ['yearly' => $sStudentYearly])
                @elseif ($sStudentCalendar)
                    @include('livewire.admin._partials.attendance-calendar', ['calendar' => $sStudentCalendar])
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

    {{-- ══════════ ASSIGN CLASS TEACHER SLIDE-IN ══════════ --}}
    @if ($showAssignPanel)
        <div class="fixed inset-0 z-[9999] overflow-hidden">
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
        <div class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
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
