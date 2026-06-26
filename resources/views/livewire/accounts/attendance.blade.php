<div class="min-h-screen bg-gray-50">

    {{-- ══════════ HEADER + main tabs ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-4 sm:py-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Attendance</h1>
                <p class="text-sm text-gray-500 mt-0.5">Mark &amp; review teacher and student attendance</p>
            </div>
            <button wire:click="openAssignPanel"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                Assign Class Teacher
            </button>
        </div>
        <div class="border-t border-gray-200 px-4 sm:px-6">
            <div class="flex gap-1">
                <button wire:click="switchMainTab('teacher')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $mainTab === 'teacher' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">Teacher Attendance</button>
                <button wire:click="switchMainTab('student')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $mainTab === 'student' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">Student Attendance</button>
            </div>
        </div>
    </div>

    @php
        $statusPill = function ($status) {
            return match ($status) {
                'present' => 'bg-emerald-100 text-emerald-700',
                'absent'  => 'bg-red-100 text-red-700',
                default   => 'bg-gray-100 text-gray-500',
            };
        };
    @endphp

    <div class="p-4 sm:p-6">

        {{-- ═══════════════════════════════════════════════
             TEACHER ATTENDANCE
        ═══════════════════════════════════════════════ --}}
        @if ($mainTab === 'teacher')
            <div class="flex flex-wrap gap-2 mb-5">
                @foreach (['mark' => 'Mark Attendance', 'by_date' => 'By Date', 'by_teacher' => 'By Teacher'] as $k => $label)
                    <button wire:click="switchTeacherView('{{ $k }}')"
                        class="px-4 py-2 text-sm font-medium rounded-lg border transition-colors {{ $teacherView === $k ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">{{ $label }}</button>
                @endforeach
            </div>

            {{-- MARK --}}
            @if ($teacherView === 'mark')
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-600">Date</label>
                            <input type="date" wire:model.live="markDate" class="text-sm border border-gray-300 rounded-md px-3 py-1.5">
                            <span class="text-xs text-gray-400">All marked present by default</span>
                        </div>
                        <button wire:click="submitTeacherAttendance"
                            class="inline-flex items-center gap-1.5 px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            Submit Attendance
                        </button>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                            <tr>
                                <th class="px-4 py-3 text-left w-12">#</th>
                                <th class="px-4 py-3 text-left">Teacher</th>
                                <th class="px-4 py-3 text-center w-48">Status</th>
                                <th class="px-4 py-3 text-left w-64">Remark</th>
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
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button wire:click="setTeacherStatus({{ $t->id }}, 'present')"
                                                class="px-3 py-1.5 text-xs font-semibold rounded-md border {{ ($teacherMark[$t->id]['status'] ?? 'present') === 'present' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-gray-600 border-gray-200' }}">Present</button>
                                            <button wire:click="setTeacherStatus({{ $t->id }}, 'absent')"
                                                class="px-3 py-1.5 text-xs font-semibold rounded-md border {{ ($teacherMark[$t->id]['status'] ?? '') === 'absent' ? 'bg-red-600 text-white border-red-600' : 'bg-white text-gray-600 border-gray-200' }}">Absent</button>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="text" wire:model="teacherMark.{{ $t->id }}.remark" placeholder="Optional remark"
                                            class="w-full text-sm border border-gray-200 rounded-md px-3 py-1.5">
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-10 text-center text-gray-400">No teachers found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- BY DATE --}}
            @if ($teacherView === 'by_date')
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
                        <label class="text-sm text-gray-600">Date</label>
                        <input type="date" wire:model.live="byDateTeacherDate" class="text-sm border border-gray-300 rounded-md px-3 py-1.5">
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                            <tr>
                                <th class="px-4 py-3 text-left w-12">#</th>
                                <th class="px-4 py-3 text-left">Teacher</th>
                                <th class="px-4 py-3 text-center w-32">Status</th>
                                <th class="px-4 py-3 text-left">Remark</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($byDateTeacherRows as $i => $row)
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
                                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full uppercase {{ $statusPill($row['status']) }}">{{ $row['status'] }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">{{ $row['remark'] ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-10 text-center text-gray-400">No teachers found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- BY TEACHER (calendar) --}}
            @if ($teacherView === 'by_teacher')
                <div class="bg-white rounded-xl border border-gray-200 p-5 mb-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-xl">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Month</label>
                            <input type="month" wire:model.live="byTeacherMonth" class="w-full text-sm border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Teacher</label>
                            <select wire:model.live="byTeacherId" class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 bg-white">
                                <option value="">Select teacher…</option>
                                @foreach ($teachers->sortBy(fn($t) => $t->user->name ?? '') as $t)
                                    <option value="{{ $t->id }}">{{ $t->user->name ?? '—' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                @if ($teacherCalendar)
                    @include('livewire.admin._partials.attendance-calendar', ['calendar' => $teacherCalendar])
                @else
                    <div class="bg-white rounded-xl border border-gray-200 py-12 text-center text-gray-400 text-sm">Select a teacher to view the monthly calendar.</div>
                @endif
            @endif
        @endif

        {{-- ═══════════════════════════════════════════════
             STUDENT ATTENDANCE
        ═══════════════════════════════════════════════ --}}
        @if ($mainTab === 'student')
            <div class="flex flex-wrap gap-2 mb-5">
                @foreach (['by_date' => 'By Date', 'by_student' => 'By Student', 'by_class' => 'By Class'] as $k => $label)
                    <button wire:click="switchStudentView('{{ $k }}')"
                        class="px-4 py-2 text-sm font-medium rounded-lg border transition-colors {{ $studentView === $k ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">{{ $label }}</button>
                @endforeach
            </div>

            {{-- BY DATE (markable) --}}
            @if ($studentView === 'by_date')
                <div class="bg-white rounded-xl border border-gray-200 p-5 mb-5">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Date</label>
                            <input type="date" wire:model.live="sdDate" class="w-full text-sm border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Class</label>
                            <select wire:model.live="sdStandard" class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 bg-white">
                                <option value="">Select class…</option>
                                @foreach ($standards as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Section</label>
                            <select wire:model.live="sdSection" class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 bg-white">
                                <option value="">Select section…</option>
                                @foreach ($sdSections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                </div>

                @if ($sdStandard && $sdSection)
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                            <span class="text-sm text-gray-600">{{ $sdStudents->count() }} student(s) · default present</span>
                            <button wire:click="submitStudentAttendance"
                                class="inline-flex items-center gap-1.5 px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                Submit
                            </button>
                        </div>
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                                <tr>
                                    <th class="px-4 py-3 text-left w-12">#</th>
                                    <th class="px-4 py-3 text-left">Student</th>
                                    <th class="px-4 py-3 text-center w-48">Status</th>
                                    <th class="px-4 py-3 text-left w-64">Remark</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($sdStudents as $i => $s)
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
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button wire:click="setStudentStatus({{ $s->id }}, 'present')"
                                                    class="px-3 py-1.5 text-xs font-semibold rounded-md border {{ ($studentMark[$s->id]['status'] ?? 'present') === 'present' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-gray-600 border-gray-200' }}">Present</button>
                                                <button wire:click="setStudentStatus({{ $s->id }}, 'absent')"
                                                    class="px-3 py-1.5 text-xs font-semibold rounded-md border {{ ($studentMark[$s->id]['status'] ?? '') === 'absent' ? 'bg-red-600 text-white border-red-600' : 'bg-white text-gray-600 border-gray-200' }}">Absent</button>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <input type="text" wire:model="studentMark.{{ $s->id }}.remark" placeholder="Optional remark"
                                                class="w-full text-sm border border-gray-200 rounded-md px-3 py-1.5">
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-10 text-center text-gray-400">No students in this class/section.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="bg-white rounded-xl border border-gray-200 py-12 text-center text-gray-400 text-sm">Select class &amp; section to mark attendance.</div>
                @endif
            @endif

            {{-- BY STUDENT (calendar) --}}
            @if ($studentView === 'by_student')
                <div class="bg-white rounded-xl border border-gray-200 p-5 mb-5">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Month</label>
                            <input type="month" wire:model.live="ssMonth" class="w-full text-sm border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Class</label>
                            <select wire:model.live="ssStandard" class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 bg-white">
                                <option value="">Select…</option>
                                @foreach ($standards as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Section</label>
                            <select wire:model.live="ssSection" class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 bg-white">
                                <option value="">Select…</option>
                                @foreach ($ssSections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Student</label>
                            <select wire:model.live="ssStudentId" class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 bg-white">
                                <option value="">Select…</option>
                                @foreach ($ssStudents as $s)<option value="{{ $s->id }}">{{ $s->user->name ?? $s->full_name }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                </div>

                @if ($studentCalendar)
                    @include('livewire.admin._partials.attendance-calendar', ['calendar' => $studentCalendar])
                @else
                    <div class="bg-white rounded-xl border border-gray-200 py-12 text-center text-gray-400 text-sm">Select a student to view the monthly calendar.</div>
                @endif
            @endif

            {{-- BY CLASS --}}
            @if ($studentView === 'by_class')
                <div class="bg-white rounded-xl border border-gray-200 p-5 mb-5">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Class</label>
                            <select wire:model.live="scStandard" class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 bg-white">
                                <option value="">Select class…</option>
                                @foreach ($standards as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Section</label>
                            <select wire:model.live="scSection" class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 bg-white">
                                <option value="">Select section…</option>
                                @foreach ($scSections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Date</label>
                            <input type="date" wire:model.live="scDate" class="w-full text-sm border border-gray-300 rounded-md px-3 py-2">
                        </div>
                    </div>
                </div>

                @if ($scStandard && $scSection)
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                                <tr>
                                    <th class="px-4 py-3 text-left w-12">#</th>
                                    <th class="px-4 py-3 text-left">Student</th>
                                    <th class="px-4 py-3 text-center w-32">Status</th>
                                    <th class="px-4 py-3 text-left">Remark</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($byClassRows as $i => $row)
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
                                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full uppercase {{ $statusPill($row['status']) }}">{{ $row['status'] }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-500">{{ $row['remark'] ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-10 text-center text-gray-400">No students found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="bg-white rounded-xl border border-gray-200 py-12 text-center text-gray-400 text-sm">Select class &amp; section to view attendance.</div>
                @endif
            @endif
        @endif
    </div>

    {{-- ══════════ ASSIGN CLASS TEACHER SLIDE-IN ══════════ --}}
    @if ($showAssignPanel)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeAssignPanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $assignEditId ? 'Edit Assignment' : 'Assign Class Teacher' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Map a teacher to a class &amp; section.</p>
                    </div>
                    <button wire:click="closeAssignPanel"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Teacher <span class="text-red-500">*</span></label>
                        <select wire:model="assignTeacherId" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm bg-white">
                            <option value="">Select teacher…</option>
                            @foreach ($teachers->sortBy(fn($t) => $t->user->name ?? '') as $t)
                                <option value="{{ $t->id }}">{{ $t->user->name ?? '—' }}</option>
                            @endforeach
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
                        <button wire:click="saveAssign" class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md">{{ $assignEditId ? 'Update' : 'Assign' }}</button>
                    </div>

                    <div class="border-t border-gray-100 pt-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Current Assignments</p>
                        <div class="space-y-2">
                            @forelse ($assignments as $a)
                                <div class="flex items-center justify-between gap-2 p-2.5 bg-gray-50 rounded-lg border border-gray-200">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate">{{ $a->teacher->user->name ?? '—' }}</p>
                                        <p class="text-xs text-gray-500">{{ $a->standard->name ?? '' }}{{ $a->section ? ' · ' . $a->section->name : '' }}</p>
                                    </div>
                                    <div class="flex items-center gap-1.5 flex-shrink-0">
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
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4">
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
