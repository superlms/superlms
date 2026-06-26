<div class="min-h-screen bg-gray-50">

    @php
        $typeColors = [
            'user'       => ['bg' => 'bg-blue-100',   'text' => 'text-blue-700',   'badge' => 'bg-blue-50 text-blue-700 border-blue-100',     'stat' => 'text-blue-600'],
            'counsellor' => ['bg' => 'bg-teal-100',   'text' => 'text-teal-700',   'badge' => 'bg-teal-50 text-teal-700 border-teal-100',     'stat' => 'text-teal-600'],
            'team'       => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-700', 'badge' => 'bg-indigo-50 text-indigo-700 border-indigo-100','stat' => 'text-indigo-600'],
            'management' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700', 'badge' => 'bg-purple-50 text-purple-700 border-purple-100','stat' => 'text-purple-600'],
            'other'      => ['bg' => 'bg-gray-100',   'text' => 'text-gray-600',   'badge' => 'bg-gray-50 text-gray-600 border-gray-200',      'stat' => 'text-gray-600'],
        ];
    @endphp

    {{-- ══════════ STICKY HEADER ══════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4 sticky top-0 z-50">

        {{-- Title row --}}
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Payroll</h1>
                <p class="text-xs text-gray-400 mt-0.5">
                    @if ($activeTab === 'employees') Manage your team
                    @elseif ($activeTab === 'attendance') Track daily attendance
                    @elseif ($activeTab === 'salary') Mark & calculate salaries
                    @else Payment history
                    @endif
                </p>
            </div>

            @if ($activeTab === 'employees')
                <button wire:click="openEmpModal()"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700
                           text-white text-sm font-semibold rounded-lg shadow-sm transition-colors flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Employee
                </button>
            @endif
        </div>

        {{-- Tab-specific analytics --}}
        @if ($activeTab === 'employees')
            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500">
                <span>Total: <strong class="text-gray-800">{{ $empStats['total'] }}</strong></span>
                <span class="text-gray-300">|</span>
                <span>User: <strong class="text-blue-600">{{ $empStats['user'] }}</strong></span>
                <span>Counsellor: <strong class="text-teal-600">{{ $empStats['counsellor'] }}</strong></span>
                <span>Team: <strong class="text-indigo-600">{{ $empStats['team'] }}</strong></span>
                <span>Management: <strong class="text-purple-600">{{ $empStats['management'] }}</strong></span>
                <span>Other: <strong class="text-gray-600">{{ $empStats['other'] }}</strong></span>
            </div>

        @elseif ($activeTab === 'attendance')
            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500">
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    Present: <strong class="text-emerald-600 ml-0.5">{{ $presentCount }}</strong>
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                    Absent: <strong class="text-red-600 ml-0.5">{{ $absentCount }}</strong>
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                    Half Day: <strong class="text-amber-600 ml-0.5">{{ $halfDayCount }}</strong>
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                    Leave: <strong class="text-blue-600 ml-0.5">{{ $leaveCount }}</strong>
                </span>
                <span class="text-gray-300">|</span>
                <span class="text-gray-400">
                    {{ \Carbon\Carbon::parse($attendanceDate)->format('d M Y') }}
                </span>
            </div>

        @elseif ($activeTab === 'salary')
            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500">
                <span>Total to Pay:
                    <strong class="text-gray-800">₹{{ number_format($totalSalaryToPay, 0) }}</strong>
                </span>
                <span class="text-gray-300">|</span>
                <span>Employees: <strong class="text-gray-700">{{ $totalEmployees }}</strong></span>
                <span>Paid: <strong class="text-emerald-600">{{ $paidThisMonth }}</strong></span>
                <span>Remaining: <strong class="text-amber-600">{{ $remainingEmp }}</strong></span>
                <span>Paid Amount: <strong class="text-blue-600">₹{{ number_format($totalPaidAmount, 0) }}</strong></span>
                <span class="text-gray-300">|</span>
                <span class="text-gray-400">{{ \Carbon\Carbon::parse($salaryMonth . '-01')->format('M Y') }}</span>
            </div>
        @endif
    </div>

    {{-- ══════════ TABS ══════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6">
        <nav class="flex gap-1 overflow-x-auto">
            @foreach ([
                'employees'  => 'Employees',
                'attendance' => 'Attendance',
                'salary'     => 'Mark Salary',
                'payments'   => 'Payments',
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

        {{-- ══════════════════════════════════════
             EMPLOYEES TAB
        ══════════════════════════════════════ --}}
        @if ($activeTab === 'employees')

            {{-- Employee Cards --}}
            @if ($employees->count())
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach ($employees as $emp)
                        @php $tc = $typeColors[$emp->type] ?? $typeColors['other']; @endphp
                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md
                                    transition-all duration-200 flex flex-col overflow-hidden">

                            {{-- Card Top --}}
                            <div class="pt-5 pb-3 px-4 flex flex-col items-center text-center border-b border-gray-100 relative">
                                <span class="absolute top-3 right-3 text-xs px-2 py-0.5 rounded-full font-medium border capitalize {{ $tc['badge'] }}">
                                    {{ $emp->type }}
                                </span>
                                @if ($emp->photo)
                                    <img src="{{ $emp->photo }}"
                                        class="w-14 h-14 rounded-full object-cover border-2 border-gray-200 shadow-sm mb-2">
                                @else
                                    <div class="w-14 h-14 rounded-full {{ $tc['bg'] }} flex items-center justify-center mb-2 shadow-sm">
                                        <span class="text-lg font-bold {{ $tc['text'] }}">
                                            {{ strtoupper(substr($emp->name, 0, 1)) }}
                                        </span>
                                    </div>
                                @endif
                                <h3 class="text-sm font-bold text-gray-900">{{ $emp->name }}</h3>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $emp->designation ?? '—' }}</p>
                            </div>

                            {{-- Card Body --}}
                            <div class="px-4 py-3 space-y-1.5 flex-1">
                                @if ($emp->mobile)
                                    <div class="flex items-center gap-2 text-xs text-gray-500">
                                        <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                        {{ $emp->mobile }}
                                    </div>
                                @endif
                                @if ($emp->email)
                                    <div class="flex items-center gap-2 text-xs text-gray-500">
                                        <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        <span class="truncate">{{ $emp->email }}</span>
                                    </div>
                                @endif
                                <div class="flex items-center justify-between pt-1 border-t border-gray-50">
                                    <span class="text-xs text-gray-400">Base Salary</span>
                                    <span class="text-sm font-bold text-emerald-700">₹{{ number_format($emp->salary, 0) }}</span>
                                </div>
                            </div>

                            {{-- Card Actions --}}
                            <div class="flex items-center border-t border-gray-100 divide-x divide-gray-100">
                                <button wire:click="viewEmployee({{ $emp->id }})"
                                    class="flex-1 flex items-center justify-center gap-1 py-2.5 text-xs font-medium
                                           text-blue-600 hover:bg-blue-50 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    View
                                </button>
                                <button wire:click="openEmpModal({{ $emp->id }})"
                                    class="flex-1 flex items-center justify-center gap-1 py-2.5 text-xs font-medium
                                           text-amber-600 hover:bg-amber-50 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit
                                </button>
                                <button wire:click="confirmDeleteEmployee({{ $emp->id }})"
                                    class="flex-1 flex items-center justify-center gap-1 py-2.5 text-xs font-medium
                                           text-red-600 hover:bg-red-50 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <p class="text-gray-500 text-sm font-medium">No employees added yet</p>
                    <button wire:click="openEmpModal()"
                        class="mt-3 text-sm text-blue-600 hover:text-blue-800 font-medium">
                        Add first employee
                    </button>
                </div>
            @endif
        @endif

        {{-- ══════════════════════════════════════
             ATTENDANCE TAB
        ══════════════════════════════════════ --}}
        @if ($activeTab === 'attendance')

            {{-- View Mode Toggle --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-1 inline-flex gap-1">
                <button wire:click="$set('attendanceViewMode', 'today')"
                    class="px-4 py-2 text-sm font-medium rounded-lg transition-colors
                           {{ $attendanceViewMode === 'today'
                               ? 'bg-blue-600 text-white shadow-sm'
                               : 'text-gray-500 hover:text-gray-700' }}">
                    By Today
                </button>
                <button wire:click="$set('attendanceViewMode', 'employee')"
                    class="px-4 py-2 text-sm font-medium rounded-lg transition-colors
                           {{ $attendanceViewMode === 'employee'
                               ? 'bg-blue-600 text-white shadow-sm'
                               : 'text-gray-500 hover:text-gray-700' }}">
                    By Employee
                </button>
            </div>

            {{-- ─── BY TODAY ─── --}}
            @if ($attendanceViewMode === 'today')

                {{-- Filters --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Date</label>
                            <input type="date" wire:model.live="attendanceDate"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                       focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Filter by Type</label>
                            <select wire:model.live="filterAttendanceType"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                       focus:ring-2 focus:ring-blue-500 bg-white">
                                <option value="">All Types</option>
                                <option value="user">User</option>
                                <option value="counsellor">Counsellor</option>
                                <option value="team">Team</option>
                                <option value="management">Management</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Attendance Table --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-indigo-50">
                        <h3 class="text-sm font-semibold text-gray-700">
                            Mark Attendance — {{ \Carbon\Carbon::parse($attendanceDate)->format('d M Y') }}
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[560px]">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Employee</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Mark</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($attendanceEmployees as $i => $emp)
                                    @php
                                        $status = $this->getAttendanceStatus($emp->id);
                                        $tc = $typeColors[$emp->type] ?? $typeColors['other'];
                                    @endphp
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-4 py-3 text-xs text-gray-400">{{ $i + 1 }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2.5">
                                                @if ($emp->photo)
                                                    <img src="{{ $emp->photo }}"
                                                        class="w-8 h-8 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                                @else
                                                    <div class="w-8 h-8 rounded-full {{ $tc['bg'] }} flex items-center justify-center flex-shrink-0">
                                                        <span class="text-xs font-bold {{ $tc['text'] }}">{{ strtoupper(substr($emp->name, 0, 1)) }}</span>
                                                    </div>
                                                @endif
                                                <div>
                                                    <p class="text-sm font-medium text-gray-800">{{ $emp->name }}</p>
                                                    <p class="text-xs text-gray-400">{{ $emp->designation ?? '' }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="text-xs px-2 py-0.5 rounded-full font-medium capitalize border {{ $tc['badge'] }}">
                                                {{ $emp->type }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if ($status === 'present')
                                                <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-green-50 text-green-700 border border-green-100">Present</span>
                                            @elseif ($status === 'absent')
                                                <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-red-50 text-red-700 border border-red-100">Absent</span>
                                            @elseif ($status === 'half_day')
                                                <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-amber-50 text-amber-700 border border-amber-100">Half Day</span>
                                            @elseif ($status === 'leave')
                                                <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-blue-50 text-blue-700 border border-blue-100">Leave</span>
                                            @else
                                                <span class="text-xs text-gray-400">Not marked</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-1">
                                                <button wire:click="markAttendance({{ $emp->id }}, 'present')"
                                                    title="Present"
                                                    class="px-2.5 py-1 text-xs font-semibold rounded-lg transition-colors
                                                           {{ $status === 'present' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-green-100 hover:text-green-700' }}">P</button>
                                                <button wire:click="markAttendance({{ $emp->id }}, 'absent')"
                                                    title="Absent"
                                                    class="px-2.5 py-1 text-xs font-semibold rounded-lg transition-colors
                                                           {{ $status === 'absent' ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-red-100 hover:text-red-700' }}">A</button>
                                                <button wire:click="markAttendance({{ $emp->id }}, 'half_day')"
                                                    title="Half Day"
                                                    class="px-2.5 py-1 text-xs font-semibold rounded-lg transition-colors
                                                           {{ $status === 'half_day' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-amber-100 hover:text-amber-700' }}">H</button>
                                                <button wire:click="markAttendance({{ $emp->id }}, 'leave')"
                                                    title="Leave"
                                                    class="px-2.5 py-1 text-xs font-semibold rounded-lg transition-colors
                                                           {{ $status === 'leave' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-blue-100 hover:text-blue-700' }}">L</button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-400">No employees found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            {{-- ─── BY EMPLOYEE (Calendar) ─── --}}
            @else

                {{-- Filters --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Month</label>
                            <input type="month" wire:model.live="attendanceViewMonth"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                       focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Employee Type</label>
                            <select wire:model.live="attendanceViewType"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                       focus:ring-2 focus:ring-blue-500 bg-white">
                                <option value="">All Types</option>
                                <option value="user">User</option>
                                <option value="counsellor">Counsellor</option>
                                <option value="team">Team</option>
                                <option value="management">Management</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Employee</label>
                            <select wire:model.live="attendanceViewEmpId"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                       focus:ring-2 focus:ring-blue-500 bg-white">
                                <option value="">Select employee…</option>
                                @foreach ($attendanceFilteredEmps as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->type }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Calendar --}}
                @if ($calendarEmployee)
                    @php
                        $calMonthObj  = \Carbon\Carbon::parse($attendanceViewMonth . '-01');
                        $calDays      = $calMonthObj->daysInMonth;
                        $calStartDow  = ($calMonthObj->dayOfWeek + 6) % 7; // Mon=0 … Sun=6
                        $tc = $typeColors[$calendarEmployee->type] ?? $typeColors['other'];
                    @endphp

                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">

                        {{-- Calendar Header --}}
                        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                @if ($calendarEmployee->photo)
                                    <img src="{{ $calendarEmployee->photo }}"
                                        class="w-10 h-10 rounded-full object-cover border border-gray-200">
                                @else
                                    <div class="w-10 h-10 rounded-full {{ $tc['bg'] }} flex items-center justify-center">
                                        <span class="text-sm font-bold {{ $tc['text'] }}">{{ strtoupper(substr($calendarEmployee->name, 0, 1)) }}</span>
                                    </div>
                                @endif
                                <div>
                                    <p class="text-sm font-bold text-gray-900">{{ $calendarEmployee->name }}</p>
                                    <p class="text-xs text-gray-400 capitalize">{{ $calendarEmployee->type }} · {{ $calMonthObj->format('F Y') }}</p>
                                </div>
                            </div>
                            {{-- Summary chips --}}
                            <div class="hidden sm:flex items-center gap-2 text-xs">
                                <span class="px-2.5 py-1 bg-green-50 text-green-700 rounded-full font-medium">P: {{ $calendarSummary['present'] }}</span>
                                <span class="px-2.5 py-1 bg-red-50 text-red-700 rounded-full font-medium">A: {{ $calendarSummary['absent'] }}</span>
                                <span class="px-2.5 py-1 bg-amber-50 text-amber-700 rounded-full font-medium">H: {{ $calendarSummary['half_day'] }}</span>
                                <span class="px-2.5 py-1 bg-blue-50 text-blue-700 rounded-full font-medium">L: {{ $calendarSummary['leave'] }}</span>
                                <span class="px-2.5 py-1 bg-gray-100 text-gray-500 rounded-full font-medium">Holiday: {{ $calendarSummary['holiday'] }}</span>
                            </div>
                        </div>

                        {{-- Calendar Grid --}}
                        <div class="p-4 sm:p-5">
                            <div class="grid grid-cols-7 gap-1.5 mb-1.5">
                                @foreach (['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'] as $dl)
                                    <div class="text-center text-xs font-semibold text-gray-400 py-1">{{ $dl }}</div>
                                @endforeach
                            </div>
                            <div class="grid grid-cols-7 gap-1.5">
                                {{-- Empty cells before first day --}}
                                @for ($i = 0; $i < $calStartDow; $i++)
                                    <div></div>
                                @endfor

                                @for ($d = 1; $d <= $calDays; $d++)
                                    @php
                                        $ds  = $attendanceViewMonth . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);
                                        $cs  = $calendarAttendance[$ds] ?? null;
                                        $cellCls = match($cs) {
                                            'present'  => 'bg-green-500 text-white',
                                            'absent'   => 'bg-red-500 text-white',
                                            'half_day' => 'bg-amber-400 text-white',
                                            'leave'    => 'bg-blue-400 text-white',
                                            default    => 'bg-gray-100 text-gray-400',
                                        };
                                        $cellTitle = match($cs) {
                                            'present'  => 'Present',
                                            'absent'   => 'Absent',
                                            'half_day' => 'Half Day',
                                            'leave'    => 'Leave',
                                            default    => 'Holiday',
                                        };
                                    @endphp
                                    <div title="{{ $cellTitle }}"
                                        class="rounded-lg py-2 text-center text-xs font-semibold {{ $cellCls }}">
                                        {{ $d }}
                                    </div>
                                @endfor
                            </div>

                            {{-- Mobile Summary --}}
                            <div class="sm:hidden flex flex-wrap gap-2 mt-4 text-xs">
                                <span class="px-2.5 py-1 bg-green-50 text-green-700 rounded-full font-medium">Present: {{ $calendarSummary['present'] }}</span>
                                <span class="px-2.5 py-1 bg-red-50 text-red-700 rounded-full font-medium">Absent: {{ $calendarSummary['absent'] }}</span>
                                <span class="px-2.5 py-1 bg-amber-50 text-amber-700 rounded-full font-medium">Half Day: {{ $calendarSummary['half_day'] }}</span>
                                <span class="px-2.5 py-1 bg-blue-50 text-blue-700 rounded-full font-medium">Leave: {{ $calendarSummary['leave'] }}</span>
                                <span class="px-2.5 py-1 bg-gray-100 text-gray-500 rounded-full font-medium">Holiday: {{ $calendarSummary['holiday'] }}</span>
                            </div>

                            {{-- Legend --}}
                            <div class="flex flex-wrap items-center gap-3 mt-4 pt-4 border-t border-gray-100 text-xs text-gray-500">
                                <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-green-500"></span> Present</div>
                                <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-red-500"></span> Absent</div>
                                <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-amber-400"></span> Half Day</div>
                                <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-blue-400"></span> Leave</div>
                                <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-gray-200"></span> Holiday (Unmarked)</div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-10 text-center text-sm text-gray-400">
                        Select an employee to view their attendance calendar
                    </div>
                @endif
            @endif
        @endif

        {{-- ══════════════════════════════════════
             SALARY TAB
        ══════════════════════════════════════ --}}
        @if ($activeTab === 'salary')

            {{-- Filters --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Month</label>
                        <input type="month" wire:model.live="salaryMonth"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                   focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Filter by Type</label>
                        <select wire:model.live="filterSalaryType"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                   focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="">All Types</option>
                            <option value="user">User</option>
                            <option value="counsellor">Counsellor</option>
                            <option value="team">Team</option>
                            <option value="management">Management</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Salary List --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 bg-gradient-to-r from-emerald-50 to-teal-50">
                    <h3 class="text-sm font-semibold text-gray-700">
                        Salary — {{ \Carbon\Carbon::parse($salaryMonth . '-01')->format('F Y') }}
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[700px]">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Employee</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Base</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Deduction</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Net Salary</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($salaryEmployees as $i => $emp)
                                @php
                                    $payment = $monthSalaryPayments->get($emp->id);
                                    $isPaid  = $payment && $payment->status === 'paid';
                                    $sc      = $salaryCalculations[$emp->id] ?? ['base' => $emp->salary, 'net' => $emp->salary, 'deduction' => 0, 'absent' => 0, 'half' => 0];
                                    $tc      = $typeColors[$emp->type] ?? $typeColors['other'];
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition-colors {{ $isPaid ? 'bg-green-50/20' : '' }}">
                                    <td class="px-4 py-3 text-xs text-gray-400">{{ $i + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            @if ($emp->photo)
                                                <img src="{{ $emp->photo }}"
                                                    class="w-8 h-8 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                            @else
                                                <div class="w-8 h-8 rounded-full {{ $tc['bg'] }} flex items-center justify-center flex-shrink-0">
                                                    <span class="text-xs font-bold {{ $tc['text'] }}">{{ strtoupper(substr($emp->name, 0, 1)) }}</span>
                                                </div>
                                            @endif
                                            <div>
                                                <p class="text-sm font-medium text-gray-800">{{ $emp->name }}</p>
                                                <p class="text-xs text-gray-400">{{ $emp->designation ?? '' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-xs px-2 py-0.5 rounded-full font-medium capitalize border {{ $tc['badge'] }}">
                                            {{ $emp->type }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <p class="text-sm text-gray-500">₹{{ number_format($sc['base'], 0) }}</p>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if ($sc['deduction'] > 0)
                                            <p class="text-sm font-medium text-red-600">−₹{{ number_format($sc['deduction'], 0) }}</p>
                                            <p class="text-xs text-gray-400">
                                                @if ($sc['absent'] > 0) {{ $sc['absent'] }}A @endif
                                                @if ($sc['half'] > 0) {{ $sc['half'] }}H @endif
                                            </p>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <p class="text-sm font-bold text-gray-900">₹{{ number_format($sc['net'], 0) }}</p>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if ($isPaid)
                                            <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-medium bg-green-50 text-green-700 border border-green-100">
                                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Paid
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-medium bg-gray-50 text-gray-500 border border-gray-200">
                                                <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span> Pending
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button wire:click="openPayModal({{ $emp->id }})"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors
                                                   {{ $isPaid
                                                       ? 'bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100'
                                                       : 'bg-emerald-600 hover:bg-emerald-700 text-white' }}">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                            </svg>
                                            {{ $isPaid ? 'Update' : 'Pay' }}
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-10 text-center text-sm text-gray-400">No employees found</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($salaryEmployees->count())
                            <tfoot class="bg-gray-50 border-t border-gray-200">
                                <tr>
                                    <td colspan="3" class="px-4 py-2.5 text-xs font-semibold text-gray-600">
                                        Total ({{ $salaryEmployees->count() }} employees)
                                    </td>
                                    <td class="px-4 py-2.5 text-xs text-gray-500">
                                        ₹{{ number_format($salaryEmployees->sum('salary'), 0) }}
                                    </td>
                                    <td class="px-4 py-2.5 text-xs font-medium text-red-600">
                                        −₹{{ number_format(array_sum(array_column($salaryCalculations, 'deduction')), 0) }}
                                    </td>
                                    <td class="px-4 py-2.5 text-sm font-bold text-gray-800">
                                        ₹{{ number_format($totalSalaryToPay, 0) }}
                                    </td>
                                    <td colspan="2" class="px-4 py-2.5 text-xs text-gray-500">
                                        Paid: <strong class="text-emerald-700">₹{{ number_format($totalPaidAmount, 0) }}</strong>
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        @endif

        {{-- ══════════════════════════════════════
             PAYMENTS TAB
        ══════════════════════════════════════ --}}
        @if ($activeTab === 'payments')

            {{-- Filters --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Month</label>
                        <input type="month" wire:model.live="filterPaymentMonth"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                   focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Employee (optional)</label>
                        <select wire:model.live="filterPaymentEmpId"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                   focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="">All Employees</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->type }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Payments Table --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px]">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Employee</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Month</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Mode</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Ref</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($payments as $i => $payment)
                                @php $tc = $typeColors[$payment->employee?->type ?? 'other'] ?? $typeColors['other']; @endphp
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-4 py-3 text-xs text-gray-400">{{ $i + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-medium text-gray-800">{{ $payment->employee?->name ?? '—' }}</p>
                                        <span class="text-xs px-1.5 py-0.5 rounded font-medium capitalize border {{ $tc['badge'] }}">
                                            {{ $payment->employee?->type ?? '' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <p class="text-sm font-medium text-gray-700">
                                            {{ \Carbon\Carbon::parse($payment->month . '-01')->format('M Y') }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <p class="text-sm font-bold text-gray-800">₹{{ number_format($payment->amount, 0) }}</p>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 capitalize">
                                        {{ str_replace('_', ' ', $payment->payment_mode) }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if ($payment->status === 'paid')
                                            <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-medium bg-green-50 text-green-700 border border-green-100">
                                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Paid
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-medium bg-amber-50 text-amber-700 border border-amber-100">
                                                <span class="w-1.5 h-1.5 bg-amber-400 rounded-full"></span> Pending
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-600">
                                        {{ $payment->payment_date?->format('d M Y') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs font-mono text-gray-400">
                                        {{ $payment->transaction_id ?? '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-12 text-center">
                                        <p class="text-sm text-gray-400">No payment records found</p>
                                        @if ($filterPaymentMonth)
                                            <p class="text-xs text-gray-300 mt-1">
                                                for {{ \Carbon\Carbon::parse($filterPaymentMonth . '-01')->format('F Y') }}
                                            </p>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>

    {{-- ══════════════════════════════════════════════
         ADD / EDIT EMPLOYEE SLIDE-IN PANEL
    ══════════════════════════════════════════════ --}}
    @if ($showEmpModal)
        <div class="fixed inset-0 z-[9999] flex items-start justify-end bg-black/30 backdrop-blur-sm"
            wire:click.self="closeEmpModal">
            <div class="relative w-full max-w-2xl h-screen bg-white shadow-2xl flex flex-col"
                x-data x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-x-full opacity-0"
                x-transition:enter-end="translate-x-0 opacity-100">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-white flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl {{ $editEmpId ? 'bg-amber-100' : 'bg-blue-100' }} flex items-center justify-center flex-shrink-0">
                            @if ($editEmpId)
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                </svg>
                            @endif
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-gray-900">{{ $editEmpId ? 'Edit Employee' : 'Add Employee' }}</h2>
                            <p class="text-xs text-gray-400">{{ $editEmpId ? 'Update employee details' : 'Fill in the details below' }}</p>
                        </div>
                    </div>
                    <button wire:click="closeEmpModal"
                        class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto px-6 py-5 space-y-6">

                    {{-- Photo --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Photo</label>
                        <div class="flex items-center gap-4">
                            @if ($editEmpId && $empExistingPhoto && !$empPhoto)
                                <img src="{{ $empExistingPhoto }}"
                                    class="w-14 h-14 rounded-full object-cover border-2 border-gray-200 shadow-sm flex-shrink-0">
                            @else
                                <div class="w-14 h-14 rounded-full bg-gray-100 border-2 border-dashed border-gray-300
                                            flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                            @endif
                            <div class="flex-1">
                                <input type="file" wire:model="empPhoto" accept="image/*"
                                    class="block w-full text-sm text-gray-500
                                           file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0
                                           file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700
                                           hover:file:bg-blue-100 cursor-pointer">
                                @error('empPhoto')
                                    <span class="text-red-500 text-xs mt-0.5 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Basic Info --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Basic Information</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <x-input wire:model.defer="empName"        label="Name *"        placeholder="Full name" />
                            <x-input wire:model.defer="empEmail"       label="Email"         placeholder="Email address" />
                            <x-input wire:model.defer="empMobile"      label="Mobile"        placeholder="Mobile number" />
                            <x-input wire:model.defer="empDesignation" label="Designation"   placeholder="e.g. Manager" />

                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Type *</label>
                                <select wire:model.defer="empType"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                           focus:ring-2 focus:ring-blue-500 bg-white">
                                    <option value="user">User</option>
                                    <option value="counsellor">Counsellor</option>
                                    <option value="team">Team</option>
                                    <option value="management">Management</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <x-input wire:model.defer="empSalary"      label="Salary (₹) *" placeholder="Monthly salary" />
                            <x-input wire:model.defer="empJoiningDate" label="Joining Date" type="date" />
                            <x-input wire:model.defer="empAddress"     label="Address"      placeholder="Address" />
                        </div>
                    </div>

                    {{-- Bank Details --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Bank Details</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <x-input wire:model.defer="empBankName"   label="Bank Name"      placeholder="Bank name" />
                            <x-input wire:model.defer="empHolderName" label="Account Holder" placeholder="Account holder name" />
                            <x-input wire:model.defer="empAccountNo"  label="Account Number" placeholder="Account number" />
                            <x-input wire:model.defer="empIfsc"       label="IFSC Code"      placeholder="IFSC code" />
                            <div class="sm:col-span-2">
                                <x-input wire:model.defer="empBranch" label="Branch"         placeholder="Branch name" />
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Footer --}}
                <div class="flex-shrink-0 px-6 py-4 border-t border-gray-100 bg-gray-50/60 flex items-center gap-3">
                    <button wire:click="saveEmployee"
                        class="flex-1 py-2.5 text-sm font-semibold rounded-lg transition-colors flex items-center justify-center gap-2
                               {{ $editEmpId ? 'bg-amber-500 hover:bg-amber-600 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ $editEmpId ? 'Update Employee' : 'Add Employee' }}
                    </button>
                    <button wire:click="closeEmpModal"
                        class="flex-1 py-2.5 bg-white hover:bg-gray-100 text-gray-600 text-sm font-medium
                               rounded-lg border border-gray-200 transition-colors">
                        Cancel
                    </button>
                </div>

            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════
         EMPLOYEE DETAIL MODAL (minimal)
    ══════════════════════════════════════════════ --}}
    @if ($showEmpDetailModal && $selectedEmployee)
        @php $tc = $typeColors[$selectedEmployee->type] ?? $typeColors['other']; @endphp
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm z-[9990]"
             wire:click="closeEmpDetailModal"></div>
        <div class="fixed inset-0 flex items-center justify-center z-[9999] px-4 pointer-events-none">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm flex flex-col max-h-[90vh] pointer-events-auto">

                {{-- Header --}}
                <div class="flex-shrink-0 flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-bold text-gray-900">Employee Details</h3>
                    <button wire:click="closeEmpDetailModal"
                        class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto p-5 space-y-4">

                    {{-- Profile --}}
                    <div class="flex flex-col items-center text-center">
                        @if ($selectedEmployee->photo)
                            <img src="{{ $selectedEmployee->photo }}"
                                class="w-16 h-16 rounded-full object-cover border-2 border-gray-200 shadow-sm mb-3">
                        @else
                            <div class="w-16 h-16 rounded-full {{ $tc['bg'] }} flex items-center justify-center mb-3 shadow-sm">
                                <span class="text-xl font-bold {{ $tc['text'] }}">
                                    {{ strtoupper(substr($selectedEmployee->name, 0, 1)) }}
                                </span>
                            </div>
                        @endif
                        <h3 class="text-base font-bold text-gray-900">{{ $selectedEmployee->name }}</h3>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $selectedEmployee->designation ?? '—' }}</p>
                        <span class="mt-2 text-xs px-2.5 py-0.5 rounded-full font-medium capitalize border {{ $tc['badge'] }}">
                            {{ $selectedEmployee->type }}
                        </span>
                    </div>

                    {{-- Key info --}}
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ([
                            'Mobile'  => $selectedEmployee->mobile,
                            'Email'   => $selectedEmployee->email,
                            'Salary'  => '₹' . number_format($selectedEmployee->salary, 0),
                            'Joining' => $selectedEmployee->joining_date?->format('d M Y'),
                        ] as $label => $value)
                            <div class="bg-gray-50 rounded-xl p-3">
                                <p class="text-xs text-gray-400 mb-0.5">{{ $label }}</p>
                                <p class="text-xs font-semibold text-gray-800 truncate">{{ $value ?? '—' }}</p>
                            </div>
                        @endforeach
                    </div>

                    @if ($selectedEmployee->address)
                        <div class="bg-gray-50 rounded-xl p-3">
                            <p class="text-xs text-gray-400 mb-0.5">Address</p>
                            <p class="text-xs font-semibold text-gray-800">{{ $selectedEmployee->address }}</p>
                        </div>
                    @endif

                    {{-- Bank --}}
                    @if ($selectedEmployee->bank_name)
                        <div class="bg-blue-50 rounded-xl border border-blue-100 p-3 space-y-2">
                            <p class="text-xs font-semibold text-blue-600 uppercase tracking-wide">Bank Details</p>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach ([
                                    'Bank'    => $selectedEmployee->bank_name,
                                    'Holder'  => $selectedEmployee->bank_holder_name,
                                    'Account' => $selectedEmployee->bank_account_no,
                                    'IFSC'    => $selectedEmployee->bank_ifsc,
                                    'Branch'  => $selectedEmployee->bank_branch,
                                ] as $label => $value)
                                    @if ($value)
                                        <div>
                                            <p class="text-xs text-blue-400">{{ $label }}</p>
                                            <p class="text-xs font-semibold text-gray-800 font-mono">{{ $value }}</p>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="flex-shrink-0 px-5 py-3 border-t border-gray-100 bg-gray-50/60 rounded-b-2xl flex gap-2">
                    <button wire:click="openEmpModal({{ $selectedEmployee->id }}); closeEmpDetailModal();"
                        class="flex-1 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition-colors">
                        Edit
                    </button>
                    <button wire:click="closeEmpDetailModal"
                        class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-medium rounded-lg transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════
         DELETE EMPLOYEE CONFIRM
    ══════════════════════════════════════════════ --}}
    @if ($pendingDeleteEmpId)
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm z-[9990]"
             wire:click="cancelDeleteEmployee"></div>
        <div class="fixed inset-0 flex items-center justify-center z-[9999] px-4 pointer-events-none">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm pointer-events-auto p-6 text-center">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-gray-900 mb-1">Delete Employee?</h3>
                <p class="text-xs text-gray-400 mb-5">This will permanently delete the employee and all their records.</p>
                <div class="flex gap-2">
                    <button wire:click="executeDeleteEmployee"
                        class="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors">
                        Yes, Delete
                    </button>
                    <button wire:click="cancelDeleteEmployee"
                        class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════
         PAY SALARY MODAL
    ══════════════════════════════════════════════ --}}
    @if ($showPayModal && $payEmp)
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm z-[9990]"
             wire:click="closePayModal"></div>
        <div class="fixed inset-0 flex items-center justify-center z-[9999] px-4 pointer-events-none">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm flex flex-col max-h-[90vh] pointer-events-auto">

                {{-- Header --}}
                <div class="flex-shrink-0 flex items-center justify-between px-5 py-4 border-b border-gray-100
                            bg-gradient-to-r from-emerald-50 to-teal-50 rounded-t-2xl">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 bg-emerald-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">Pay Salary</h3>
                            <p class="text-xs text-gray-400">
                                {{ $payEmp->name }} · {{ \Carbon\Carbon::parse($salaryMonth . '-01')->format('M Y') }}
                            </p>
                        </div>
                    </div>
                    <button wire:click="closePayModal"
                        class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-white rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto p-5 space-y-4">

                    {{-- Amount --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Amount (₹) <span class="text-red-500">*</span></label>
                        <input type="number" wire:model.defer="payAmount" min="0"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                   focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400">
                        @error('payAmount')
                            <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Payment Mode --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-2">Payment Mode <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach ([
                                'cash'          => 'Cash',
                                'cheque'        => 'Cheque',
                                'online'        => 'Online',
                                'bank_transfer' => 'Bank Transfer',
                            ] as $mode => $label)
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

                    {{-- Payment Date --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Payment Date <span class="text-red-500">*</span></label>
                        <input type="date" wire:model.defer="payDate"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                   focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400">
                        @error('payDate')
                            <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Cheque Number (only for cheque mode) --}}
                    @if ($payMode === 'cheque')
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Cheque Number <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.defer="payChequeId" placeholder="Enter cheque number"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                       focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400">
                            @error('payChequeId')
                                <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    {{-- Remark --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Remark (optional)</label>
                        <input type="text" wire:model.defer="payRemark" placeholder="Optional note"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                   focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400">
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex-shrink-0 px-5 pb-5 pt-3 border-t border-gray-100 flex items-center gap-2">
                    <button wire:click="savePayment"
                        class="flex-1 py-2.5 text-sm font-semibold rounded-lg transition-colors flex items-center justify-center gap-2
                               {{ in_array($payMode, ['online', 'bank_transfer'])
                                   ? 'bg-blue-600 hover:bg-blue-700 text-white'
                                   : 'bg-emerald-600 hover:bg-emerald-700 text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if (in_array($payMode, ['online', 'bank_transfer']))
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            @endif
                        </svg>
                        {{ in_array($payMode, ['online', 'bank_transfer']) ? 'Pay Now' : 'Mark as Paid' }}
                    </button>
                    <button wire:click="closePayModal"
                        class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
