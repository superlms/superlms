<div class="min-h-screen bg-gray-50">

    {{-- ══════════ HEADER ══════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4 sm:py-5 sticky top-0 z-50">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Payroll</h1>
                <p class="text-sm text-gray-500 mt-0.5">Manage employees, attendance and salaries</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                {{-- Analytics chips --}}
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-gray-50 border border-gray-200 text-xs font-medium text-gray-600">
                    Total <strong class="text-gray-900">{{ $empStats['total'] }}</strong>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100 text-xs font-medium text-blue-600">
                    Teachers <strong>{{ $empStats['teacher'] }}</strong>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-purple-50 border border-purple-100 text-xs font-medium text-purple-600">
                    Mgmt <strong>{{ $empStats['management'] }}</strong>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-100 text-xs font-medium text-emerald-600">
                    Staff <strong>{{ $empStats['employee'] }}</strong>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-amber-50 border border-amber-100 text-xs font-medium text-amber-600">
                    Drivers <strong>{{ $empStats['driver'] }}</strong>
                </span>
                @if ($activeTab === 'employees')
                    <button wire:click="openEmpModal()"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700
                               text-white text-sm font-semibold rounded-lg shadow-sm transition-colors ml-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Employee
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════ TABS ══════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6">
        <nav class="flex gap-1 overflow-x-auto">
            @foreach ([
        'employees' => 'Employees',
        'attendance' => 'Attendance',
        'salary' => 'Mark Salary',
        'payments' => 'Payments',
    ] as $tab => $label)
                <button wire:click="$set('activeTab', '{{ $tab }}')"
                    class="py-3 px-4 text-sm font-medium whitespace-nowrap border-b-2 transition-colors
                        {{ $activeTab === $tab
                            ? 'border-blue-600 text-blue-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    <div class="p-4 sm:p-6 space-y-5">

        {{-- ══════════ EMPLOYEES TAB ══════════ --}}
        @if ($activeTab === 'employees')

            {{-- Employee Cards --}}
            @if ($employees->count())
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach ($employees as $emp)
                        @php
                            $typeColors = [
                                'teacher' => [
                                    'bg' => 'bg-blue-100',
                                    'text' => 'text-blue-700',
                                    'badge' => 'bg-blue-50 text-blue-700 border-blue-100',
                                ],
                                'management' => [
                                    'bg' => 'bg-purple-100',
                                    'text' => 'text-purple-700',
                                    'badge' => 'bg-purple-50 text-purple-700 border-purple-100',
                                ],
                                'employee' => [
                                    'bg' => 'bg-emerald-100',
                                    'text' => 'text-emerald-700',
                                    'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                ],
                                'driver' => [
                                    'bg' => 'bg-amber-100',
                                    'text' => 'text-amber-700',
                                    'badge' => 'bg-amber-50 text-amber-700 border-amber-100',
                                ],
                            ];
                            $tc = $typeColors[$emp->type] ?? $typeColors['employee'];
                        @endphp
                        <div
                            class="bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md
                                    transition-all duration-200 overflow-hidden flex flex-col">
                            <div
                                class="pt-5 pb-3 px-4 flex flex-col items-center text-center border-b border-gray-100 relative">
                                <span
                                    class="absolute top-3 right-3 text-xs px-2 py-0.5 rounded-full font-medium border capitalize {{ $tc['badge'] }}">
                                    {{ $emp->type }}
                                </span>
                                @if ($emp->photo)
                                    <img src="{{ $emp->photo }}"
                                        class="w-14 h-14 rounded-full object-cover border-2 border-gray-200 shadow-sm mb-2">
                                @else
                                    <div
                                        class="w-14 h-14 rounded-full {{ $tc['bg'] }} flex items-center justify-center mb-2 shadow-sm">
                                        <span class="text-lg font-bold {{ $tc['text'] }}">
                                            {{ strtoupper(substr($emp->name, 0, 1)) }}
                                        </span>
                                    </div>
                                @endif
                                <h3 class="text-sm font-bold text-gray-900">{{ $emp->name }}</h3>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $emp->designation ?? '—' }}</p>
                                @if ($emp->isTeacher() && $emp->teacher_detail_id)
                                    <span class="mt-1 text-xs text-blue-500">Linked to Teacher</span>
                                @endif
                            </div>

                            <div class="p-4 space-y-2 flex-1">
                                @if ($emp->mobile)
                                    <div class="flex items-center gap-2 text-xs text-gray-500">
                                        <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                        {{ $emp->mobile }}
                                    </div>
                                @endif
                                @if ($emp->email)
                                    <div class="flex items-center gap-2 text-xs text-gray-500">
                                        <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        <span class="truncate">{{ $emp->email }}</span>
                                    </div>
                                @endif
                                <div class="flex items-center justify-between pt-1">
                                    <span class="text-xs text-gray-400">Salary</span>
                                    <span
                                        class="text-sm font-bold text-emerald-700">₹{{ number_format($emp->salary, 0) }}</span>
                                </div>
                            </div>

                            <div class="flex items-center border-t border-gray-100 divide-x divide-gray-100">
                                <button wire:click="viewEmployee({{ $emp->id }})"
                                    class="flex-1 flex items-center justify-center gap-1 py-2.5 text-xs font-medium
                                           text-blue-600 hover:bg-blue-50 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    View
                                </button>
                                <button wire:click="openEmpModal({{ $emp->id }})"
                                    class="flex-1 flex items-center justify-center gap-1 py-2.5 text-xs font-medium
                                           text-amber-600 hover:bg-amber-50 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit
                                </button>
                                <button wire:click="deleteEmployee({{ $emp->id }})"
                                    class="flex-1 flex items-center justify-center gap-1 py-2.5 text-xs font-medium
                                           text-red-600 hover:bg-red-50 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Delete
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center">
                    <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <p class="text-gray-500 text-sm">No employees added yet.</p>
                    <button wire:click="openEmpModal()"
                        class="mt-3 text-sm text-blue-600 hover:text-blue-800 font-medium">Add first employee</button>
                </div>
            @endif
        @endif

        {{-- ══════════ ATTENDANCE TAB ══════════ --}}
        @if ($activeTab === 'attendance')

            {{-- Analytics --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                    <p class="text-xs text-gray-400 mb-1">Present</p>
                    <p class="text-2xl font-bold text-emerald-600">{{ $presentCount }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                    <p class="text-xs text-gray-400 mb-1">Absent</p>
                    <p class="text-2xl font-bold text-red-500">{{ $absentCount }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                    <p class="text-xs text-gray-400 mb-1">Half Day</p>
                    <p class="text-2xl font-bold text-amber-500">{{ $halfDayCount }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                    <p class="text-xs text-gray-400 mb-1">On Leave</p>
                    <p class="text-2xl font-bold text-blue-500">{{ $leaveCount }}</p>
                </div>
            </div>

            {{-- Filters --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Date</label>
                        <input type="date" wire:model.live="attendanceDate"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                   focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Month View</label>
                        <input type="month" wire:model.live="attendanceMonth"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                   focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Filter Type</label>
                        <select wire:model.live="filterAttendanceType"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                   focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="">All Types</option>
                            <option value="teacher">Teacher</option>
                            <option value="management">Management</option>
                            <option value="employee">Employee</option>
                            <option value="driver">Driver</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Daily Attendance --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-indigo-50">
                    <h3 class="text-sm font-semibold text-gray-700">
                        Mark Attendance — {{ \Carbon\Carbon::parse($attendanceDate)->format('d M Y') }}
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Employee</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Mark</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($employees as $i => $emp)
                                @php
                                    $status = $this->getAttendanceStatus($emp->id);
                                    $typeColors = [
                                        'teacher' => 'bg-blue-100 text-blue-700',
                                        'management' => 'bg-purple-100 text-purple-700',
                                        'employee' => 'bg-emerald-100 text-emerald-700',
                                        'driver' => 'bg-amber-100 text-amber-700',
                                    ];
                                    $tc = $typeColors[$emp->type] ?? 'bg-gray-100 text-gray-700';
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-4 py-3 text-xs text-gray-400">{{ $i + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            @if ($emp->photo)
                                                <img src="{{ $emp->photo }}"
                                                    class="w-8 h-8 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                            @else
                                                <div
                                                    class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                                    <span
                                                        class="text-xs font-bold text-gray-600">{{ strtoupper(substr($emp->name, 0, 1)) }}</span>
                                                </div>
                                            @endif
                                            <div>
                                                <p class="text-sm font-medium text-gray-800">{{ $emp->name }}</p>
                                                <p class="text-xs text-gray-400">{{ $emp->designation ?? '' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="text-xs px-2 py-0.5 rounded-full font-medium capitalize {{ $tc }}">
                                            {{ $emp->type }}
                                            @if ($emp->isTeacher() && $emp->teacher_detail_id)
                                                <span class="text-[10px] opacity-70">•linked</span>
                                            @endif
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($status === 'present')
                                            <span
                                                class="text-xs px-2 py-1 rounded-full font-medium bg-green-50 text-green-700 border border-green-100">Present</span>
                                        @elseif ($status === 'absent')
                                            <span
                                                class="text-xs px-2 py-1 rounded-full font-medium bg-red-50 text-red-700 border border-red-100">Absent</span>
                                        @elseif ($status === 'half_day')
                                            <span
                                                class="text-xs px-2 py-1 rounded-full font-medium bg-amber-50 text-amber-700 border border-amber-100">Half
                                                Day</span>
                                        @elseif ($status === 'leave')
                                            <span
                                                class="text-xs px-2 py-1 rounded-full font-medium bg-blue-50 text-blue-700 border border-blue-100">Leave</span>
                                        @else
                                            <span class="text-xs text-gray-400">Not marked</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-1">
                                            <button wire:click="markAttendance({{ $emp->id }}, 'present')"
                                                class="px-2 py-1 text-xs font-medium rounded-lg transition-colors
                                                       {{ $status === 'present' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-green-100 hover:text-green-700' }}">
                                                P
                                            </button>
                                            <button wire:click="markAttendance({{ $emp->id }}, 'absent')"
                                                class="px-2 py-1 text-xs font-medium rounded-lg transition-colors
                                                       {{ $status === 'absent' ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-red-100 hover:text-red-700' }}">
                                                A
                                            </button>
                                            <button wire:click="markAttendance({{ $emp->id }}, 'half_day')"
                                                class="px-2 py-1 text-xs font-medium rounded-lg transition-colors
                                                       {{ $status === 'half_day' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-amber-100 hover:text-amber-700' }}">
                                                H
                                            </button>
                                            <button wire:click="markAttendance({{ $emp->id }}, 'leave')"
                                                class="px-2 py-1 text-xs font-medium rounded-lg transition-colors
                                                       {{ $status === 'leave' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-blue-100 hover:text-blue-700' }}">
                                                L
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400">No
                                        employees found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Monthly Summary --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 bg-gradient-to-r from-purple-50 to-indigo-50">
                    <h3 class="text-sm font-semibold text-gray-700">
                        Monthly Summary — {{ \Carbon\Carbon::parse($attendanceMonth . '-01')->format('M Y') }}
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Employee</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Present</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Absent</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Half Day</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Leave</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($employees as $emp)
                                @php
                                    if ($emp->isTeacher() && isset($teacherMonthAttendance[$emp->id])) {
                                        $records = $teacherMonthAttendance[$emp->id];
                                        $present = $records->where('status', 1)->count();
                                        $absent = $records->where('status', 0)->count();
                                        $halfDay = $records->whereIn('status', [2, 3])->count();
                                        $leave = 0;
                                        $total = $records->count();
                                    } else {
                                        $records = $monthAttendance->get($emp->id, collect());
                                        $present = $records->where('status', 'present')->count();
                                        $absent = $records->where('status', 'absent')->count();
                                        $halfDay = $records->where('status', 'half_day')->count();
                                        $leave = $records->where('status', 'leave')->count();
                                        $total = $records->count();
                                    }
                                @endphp
                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-medium text-gray-800">{{ $emp->name }}</p>
                                        <p class="text-xs text-gray-400 capitalize">
                                            {{ $emp->type }}
                                            @if ($emp->isTeacher() && $emp->teacher_detail_id)
                                                · linked
                                            @endif
                                        </p>
                                    </td>
                                    <td class="px-4 py-3 text-center"><span
                                            class="text-sm font-bold text-emerald-600">{{ $present }}</span></td>
                                    <td class="px-4 py-3 text-center"><span
                                            class="text-sm font-bold text-red-500">{{ $absent }}</span></td>
                                    <td class="px-4 py-3 text-center"><span
                                            class="text-sm font-bold text-amber-500">{{ $halfDay }}</span></td>
                                    <td class="px-4 py-3 text-center"><span
                                            class="text-sm font-bold text-blue-500">{{ $leave }}</span></td>
                                    <td class="px-4 py-3 text-center"><span
                                            class="text-sm font-bold text-gray-700">{{ $total }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- ══════════ SALARY TAB ══════════ --}}
        @if ($activeTab === 'salary')

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                    <p class="text-xs text-gray-400 mb-1">Paid This Month</p>
                    <p class="text-2xl font-bold text-emerald-600">{{ $paidThisMonth }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                    <p class="text-xs text-gray-400 mb-1">Pending</p>
                    <p class="text-2xl font-bold text-amber-500">{{ max(0, $pendingThisMonth) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                    <p class="text-xs text-gray-400 mb-1">Total Paid</p>
                    <p class="text-2xl font-bold text-gray-800">₹{{ number_format($totalPaidAmount, 0) }}</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Month</label>
                        <input type="month" wire:model.live="salaryMonth"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                   focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Filter Type</label>
                        <select wire:model.live="filterSalaryType"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                   focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="">All Types</option>
                            <option value="teacher">Teacher</option>
                            <option value="management">Management</option>
                            <option value="employee">Employee</option>
                            <option value="driver">Driver</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 bg-gradient-to-r from-emerald-50 to-teal-50">
                    <h3 class="text-sm font-semibold text-gray-700">
                        Salary List — {{ \Carbon\Carbon::parse($salaryMonth . '-01')->format('M Y') }}
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Employee</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Salary</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Mode</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($salaryEmployees as $i => $emp)
                                @php
                                    $payment = $monthSalaryPayments->get($emp->id);
                                    $isPaid = $payment && $payment->status === 'paid';
                                    $typeColors = [
                                        'teacher' => 'bg-blue-100 text-blue-700',
                                        'management' => 'bg-purple-100 text-purple-700',
                                        'employee' => 'bg-emerald-100 text-emerald-700',
                                        'driver' => 'bg-amber-100 text-amber-700',
                                    ];
                                    $tc = $typeColors[$emp->type] ?? 'bg-gray-100 text-gray-700';
                                @endphp
                                <tr
                                    class="hover:bg-gray-50/50 transition-colors {{ $isPaid ? 'bg-green-50/20' : '' }}">
                                    <td class="px-4 py-3 text-xs text-gray-400">{{ $i + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            @if ($emp->photo)
                                                <img src="{{ $emp->photo }}"
                                                    class="w-8 h-8 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                            @else
                                                <div
                                                    class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                                    <span
                                                        class="text-xs font-bold text-gray-600">{{ strtoupper(substr($emp->name, 0, 1)) }}</span>
                                                </div>
                                            @endif
                                            <div>
                                                <p class="text-sm font-medium text-gray-800">{{ $emp->name }}</p>
                                                <p class="text-xs text-gray-400">{{ $emp->designation ?? '' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="text-xs px-2 py-0.5 rounded-full font-medium capitalize {{ $tc }}">
                                            {{ $emp->type }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-bold text-gray-800">
                                            ₹{{ number_format($emp->salary, 0) }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($isPaid)
                                            <span
                                                class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full
                                                         font-medium bg-green-50 text-green-700 border border-green-100">
                                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                                Paid
                                            </span>
                                        @elseif ($payment && $payment->status === 'pending')
                                            <span
                                                class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full
                                                         font-medium bg-amber-50 text-amber-700 border border-amber-100">
                                                <span class="w-1.5 h-1.5 bg-amber-400 rounded-full"></span>
                                                Pending
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400">Not paid</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600 capitalize">
                                        {{ $payment ? str_replace('_', ' ', $payment->payment_mode) : '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center">
                                            <button wire:click="openPayModal({{ $emp->id }})"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold
                                                       rounded-lg transition-colors
                                                       {{ $isPaid
                                                           ? 'bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100'
                                                           : 'bg-emerald-600 hover:bg-emerald-700 text-white' }}">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                                </svg>
                                                {{ $isPaid ? 'Update' : 'Pay' }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400">No
                                        employees found</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($salaryEmployees->count())
                            <tfoot class="bg-gray-50 border-t border-gray-200">
                                <tr>
                                    <td colspan="3" class="px-4 py-2.5 text-xs font-semibold text-gray-600">
                                        Total ({{ $salaryEmployees->count() }} employees)
                                    </td>
                                    <td class="px-4 py-2.5 text-sm font-bold text-gray-800">
                                        ₹{{ number_format($salaryEmployees->sum('salary'), 0) }}
                                    </td>
                                    <td colspan="3" class="px-4 py-2.5 text-xs text-gray-500">
                                        Paid: <strong
                                            class="text-emerald-700">₹{{ number_format($totalPaidAmount, 0) }}</strong>
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        @endif

        {{-- ══════════ PAYMENTS TAB ══════════ --}}
        @if ($activeTab === 'payments')

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Employee</label>
                        <select wire:model.live="filterPaymentEmpId"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                   focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="">All Employees</option>
                            @foreach ($allEmployeesForFilter as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Month</label>
                        <input type="month" wire:model.live="filterPaymentMonth"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                   focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Employee</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Month</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Mode</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Receipt</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($payments as $i => $payment)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-4 py-3 text-xs text-gray-400">{{ $i + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-medium text-gray-800">
                                            {{ $payment->employee?->name ?? '—' }}</p>
                                        <p class="text-xs text-gray-400 capitalize">
                                            {{ $payment->employee?->type ?? '' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ \Carbon\Carbon::parse($payment->month . '-01')->format('M Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-800">
                                        ₹{{ number_format($payment->amount, 0) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 capitalize">
                                        {{ str_replace('_', ' ', $payment->payment_mode) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($payment->status === 'paid')
                                            <span
                                                class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full
                                                         font-medium bg-green-50 text-green-700 border border-green-100">
                                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Paid
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full
                                                         font-medium bg-amber-50 text-amber-700 border border-amber-100">
                                                <span class="w-1.5 h-1.5 bg-amber-400 rounded-full"></span> Pending
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600">
                                        {{ $payment->payment_date?->format('d M Y') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs font-mono text-gray-500">
                                        {{ $payment->receipt_number ?? '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-10 text-center text-sm text-gray-400">
                                        No payment records found
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    {{-- ══════════ ADD/EDIT EMPLOYEE SLIDE-IN PANEL ══════════ --}}
    @if ($showEmpModal)
        <div class="fixed inset-0 z-[9999] overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeEmpModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editEmpId ? 'Edit Employee' : 'Add Employee' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">For type <strong>Teacher</strong>, attendance syncs with the Attendance module.</p>
                    </div>
                    <button wire:click="closeEmpModal"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">

                    {{-- Photo --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Photo</label>
                        @if ($editEmpId && $empExistingPhoto && !$empPhoto)
                            <div class="flex items-center gap-3 mb-2">
                                <img src="{{ $empExistingPhoto }}"
                                    class="w-12 h-12 rounded-full object-cover border border-gray-200">
                                <span class="text-xs text-gray-400">Current photo</span>
                            </div>
                        @endif
                        <input type="file" wire:model="empPhoto" accept="image/*"
                            class="block w-full text-sm text-gray-500
                                   file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0
                                   file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700
                                   hover:file:bg-blue-100 transition-colors">
                        @error('empPhoto')
                            <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Basic Info --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Name *</label>
                            <input type="text" wire:model.defer="empName" placeholder="Full name"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                            @error('empName')
                                <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                            <input type="email" wire:model.defer="empEmail" placeholder="Email address"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Mobile</label>
                            <input type="text" wire:model.defer="empMobile" placeholder="Mobile number"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Designation</label>
                            <input type="text" wire:model.defer="empDesignation" placeholder="e.g. Manager"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Type *</label>
                            <select wire:model.live="empType"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 bg-white">
                                <option value="teacher">Teacher</option>
                                <option value="management">Management</option>
                                <option value="employee">Employee</option>
                                <option value="driver">Driver</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Salary (₹) *</label>
                            <input type="number" wire:model.defer="empSalary" placeholder="Monthly salary"
                                min="0"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                            @error('empSalary')
                                <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Joining Date</label>
                            <input type="date" wire:model.defer="empJoiningDate"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Address</label>
                            <input type="text" wire:model.defer="empAddress" placeholder="Address"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                        </div>
                    </div>

                    {{-- Teacher link --}}
                    @if ($empType === 'teacher')
                        <div class="bg-blue-50 rounded-xl border border-blue-100 p-3">
                            <label class="block text-xs font-medium text-blue-700 mb-1">
                                Link to Teacher Detail
                                <span class="text-blue-400 font-normal">(optional — links attendance to teacher
                                    records)</span>
                            </label>
                            <select wire:model.defer="empTeacherDetailId"
                                class="w-full border border-blue-200 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-400">
                                <option value="">— Don't link —</option>
                                @foreach (\App\Models\Teacher\TeacherDetail::with('user')->where('organization_id', Auth::user()->organization_id)->get() as $td)
                                    <option value="{{ $td->id }}">
                                        {{ $td->user?->name ?? 'Teacher #' . $td->id }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Bank Details --}}
                    <div class="border border-gray-200 rounded-xl p-4 space-y-3 bg-gray-50">
                        <h4 class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Bank Details</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Bank Name</label>
                                <input type="text" wire:model.defer="empBankName" placeholder="Bank name"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Account Holder</label>
                                <input type="text" wire:model.defer="empHolderName"
                                    placeholder="Account holder name"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Account Number</label>
                                <input type="text" wire:model.defer="empAccountNo" placeholder="Account number"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">IFSC Code</label>
                                <input type="text" wire:model.defer="empIfsc" placeholder="IFSC code"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Branch</label>
                                <input type="text" wire:model.defer="empBranch" placeholder="Branch name"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeEmpModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveEmployee" class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md">{{ $editEmpId ? 'Update Employee' : 'Add Employee' }}</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ EMPLOYEE DETAIL SLIDE-IN PANEL ══════════ --}}
    @if ($showEmpDetailModal && $selectedEmployee)
        <div class="fixed inset-0 z-[9999] overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeEmpDetailModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div><h2 class="text-lg font-semibold text-gray-900">Employee Details</h2></div>
                    <button wire:click="closeEmpDetailModal"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div class="flex flex-col items-center text-center pb-4 border-b border-gray-100">
                        @if ($selectedEmployee->photo)
                            <img src="{{ $selectedEmployee->photo }}"
                                class="w-20 h-20 rounded-full object-cover border-2 border-gray-200 shadow-sm mb-3">
                        @else
                            <div
                                class="w-20 h-20 rounded-full bg-indigo-100 flex items-center justify-center mb-3 shadow-sm">
                                <span class="text-2xl font-bold text-indigo-600">
                                    {{ strtoupper(substr($selectedEmployee->name, 0, 1)) }}
                                </span>
                            </div>
                        @endif
                        <h3 class="text-lg font-bold text-gray-900">{{ $selectedEmployee->name }}</h3>
                        <p class="text-sm text-gray-500">{{ $selectedEmployee->designation ?? '—' }}</p>
                        <span
                            class="mt-1 text-xs px-2.5 py-0.5 rounded-full font-medium capitalize
                            {{ match ($selectedEmployee->type) {
                                'teacher' => 'bg-blue-50 text-blue-700 border border-blue-100',
                                'management' => 'bg-purple-50 text-purple-700 border border-purple-100',
                                'driver' => 'bg-amber-50 text-amber-700 border border-amber-100',
                                default => 'bg-emerald-50 text-emerald-700 border border-emerald-100',
                            } }}">
                            {{ $selectedEmployee->type }}
                        </span>
                        @if ($selectedEmployee->isTeacher() && $selectedEmployee->teacher_detail_id)
                            <span class="mt-1 text-xs text-blue-500">
                                Linked:
                                {{ $selectedEmployee->teacherDetail?->user?->name ?? 'Teacher #' . $selectedEmployee->teacher_detail_id }}
                            </span>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        @foreach ([
        'Mobile' => $selectedEmployee->mobile,
        'Email' => $selectedEmployee->email,
        'Salary' => '₹' . number_format($selectedEmployee->salary, 0),
        'Joining' => $selectedEmployee->joining_date?->format('d M Y'),
    ] as $label => $value)
                            <div class="bg-gray-50 rounded-xl p-3">
                                <p class="text-xs text-gray-400 mb-0.5">{{ $label }}</p>
                                <p class="text-sm font-semibold text-gray-800">{{ $value ?? '—' }}</p>
                            </div>
                        @endforeach
                    </div>

                    @if ($selectedEmployee->address)
                        <div class="bg-gray-50 rounded-xl p-3">
                            <p class="text-xs text-gray-400 mb-0.5">Address</p>
                            <p class="text-sm font-semibold text-gray-800">{{ $selectedEmployee->address }}</p>
                        </div>
                    @endif

                    @if ($selectedEmployee->bank_name)
                        <div class="bg-blue-50 rounded-xl border border-blue-100 p-4">
                            <p class="text-xs font-semibold text-blue-600 uppercase tracking-wide mb-2">Bank Details
                            </p>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach ([
        'Bank' => $selectedEmployee->bank_name,
        'Holder' => $selectedEmployee->bank_holder_name,
        'Account' => $selectedEmployee->bank_account_no,
        'IFSC' => $selectedEmployee->bank_ifsc,
        'Branch' => $selectedEmployee->bank_branch,
    ] as $label => $value)
                                    <div>
                                        <p class="text-xs text-blue-400">{{ $label }}</p>
                                        <p class="text-xs font-semibold text-gray-800 font-mono">{{ $value ?? '—' }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end flex-shrink-0">
                    <button wire:click="closeEmpDetailModal" class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md">Close</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ PAY SALARY SLIDE-IN PANEL ══════════ --}}
    @if ($showPayModal)
        @php $payEmp = \App\Models\Admin\AdminEmployee::find($payEmployeeId); @endphp
        <div class="fixed inset-0 z-[9999] overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePayModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Pay Salary</h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $payEmp?->name }} · {{ \Carbon\Carbon::parse($salaryMonth . '-01')->format('M Y') }}</p>
                    </div>
                    <button wire:click="closePayModal"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Amount (₹) *</label>
                        <input type="number" wire:model.defer="payAmount" min="0"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                   focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400">
                        @error('payAmount')
                            <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Payment Mode *</label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach (['cash' => 'Cash', 'online' => 'Online', 'bank_transfer' => 'Bank Transfer', 'cheque' => 'Cheque'] as $mode => $label)
                                <button type="button" wire:click="$set('payMode', '{{ $mode }}')"
                                    class="py-2 text-xs font-semibold rounded-lg border transition-colors
                                           {{ $payMode === $mode
                                               ? 'bg-emerald-600 text-white border-emerald-600'
                                               : 'bg-white text-gray-600 border-gray-300 hover:border-emerald-400 hover:text-emerald-600' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Payment Date *</label>
                        <input type="date" wire:model.defer="payDate"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                   focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400">
                    </div>

                    @if (in_array($payMode, ['online', 'bank_transfer', 'cheque']))
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Transaction / Cheque ID</label>
                            <input type="text" wire:model.defer="payTransactionId"
                                placeholder="Transaction reference"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                       focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400">
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Remark (Optional)</label>
                        <input type="text" wire:model.defer="payRemark" placeholder="Optional note"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                   focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400">
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closePayModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="savePayment"
                        class="px-5 py-2 text-sm font-semibold rounded-md text-white {{ $payMode === 'cash' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-blue-600 hover:bg-blue-700' }}">
                        {{ $payMode === 'cash' ? 'Mark as Paid' : 'Pay Now' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
