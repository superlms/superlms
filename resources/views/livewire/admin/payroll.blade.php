<div class="min-h-screen bg-gray-50">
    <style>[x-cloak]{display:none !important;}</style>

    @php
        $typeChip = [
            'teacher'    => 'bg-blue-50 text-blue-700 border-blue-100',
            'management' => 'bg-purple-50 text-purple-700 border-purple-100',
            'employee'   => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            'driver'     => 'bg-amber-50 text-amber-700 border-amber-100',
        ];
    @endphp

    {{-- ══════════ STICKY: HEADER + TABS + FILTERS ══════════ --}}
    <div class="sticky top-0 z-40">
    {{-- ══════════ HEADER ══════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">Payroll</h1>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                {{-- Same divided analytics strip the Students header uses --}}
                <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                    <span class="pr-4">Total: <strong class="text-gray-800">{{ $empStats['total'] }}</strong></span>
                    <span class="px-4">Management: <strong class="text-purple-600">{{ $empStats['management'] }}</strong></span>
                    <span class="px-4">Drivers: <strong class="text-amber-600">{{ $empStats['driver'] }}</strong></span>
                    <span class="px-4">Employees: <strong class="text-emerald-600">{{ $empStats['employee'] }}</strong></span>
                    <span class="pl-4">Teachers: <strong class="text-blue-600">{{ $empStats['teacher'] }}</strong></span>
                </div>

                @if ($activeTab === 'employees')
                    <button wire:click="openEmpModal()"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors ml-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Add Employee
                    </button>
                @endif

                {{-- Mark Salary tab: once a person is picked, a payment can be added to them --}}
                @if ($activeTab === 'salary' && $salaryPerson)
                    <button wire:click="openSalaryPayment"
                        class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700
                               text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Add Payment
                    </button>
                @endif

                {{-- Attendance tab: the Add Student button's look; it opens the mark
                     panel on today, for everyone at once (view mode only) --}}
                @if ($activeTab === 'attendance' && $attendanceMode === 'view')
                    <button wire:click="openMarkPanel"
                        class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700
                               text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                        Mark Attendance
                    </button>
                @endif
            </div>
        </div>

        {{-- Mobile / Tablet stats --}}
        <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
            <span>Total: <strong class="text-gray-800">{{ $empStats['total'] }}</strong></span>
            <span>Management: <strong class="text-purple-600">{{ $empStats['management'] }}</strong></span>
            <span>Drivers: <strong class="text-amber-600">{{ $empStats['driver'] }}</strong></span>
            <span>Employees: <strong class="text-emerald-600">{{ $empStats['employee'] }}</strong></span>
            <span>Teachers: <strong class="text-blue-600">{{ $empStats['teacher'] }}</strong></span>
        </div>
    </div>

    {{-- ══════════ TABS ══════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6">
        <nav class="flex gap-1 overflow-x-auto">
            @foreach (['employees' => 'Employees', 'attendance' => 'Attendance', 'salary' => 'Mark Salary', 'payments' => 'Payments'] as $tab => $label)
                <button wire:click="$set('activeTab', '{{ $tab }}')"
                    class="py-3 px-4 text-sm font-medium whitespace-nowrap border-b-2 transition-colors
                        {{ $activeTab === $tab ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    {{-- ══════════ FILTER BAND (full-width, exams-style, per tab) ══════════ --}}
    @php $filterIcon = 'M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z'; @endphp

    @if ($activeTab === 'employees')
        <div class="bg-gray-50 border-b border-gray-200 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $filterIcon }}" /></svg>
                    Filter by:
                </div>
                <input wire:model.live.debounce.300ms="empSearch" type="text" placeholder="Search name, designation, mobile…"
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-64 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                <select wire:model.live="empTypeFilter" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">All Types</option>
                    <option value="teacher">Teacher</option>
                    <option value="management">Management</option>
                    <option value="employee">Employee</option>
                    <option value="driver">Driver</option>
                </select>
                <span class="text-gray-300">·</span>
                <div class="flex items-center gap-1.5 text-xs font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" /></svg>
                    Sort:
                </div>
                <select wire:model.live="empSort" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="type_order">Type (Mgmt → Teacher → Driver → Employee)</option>
                    <option value="name_asc">Name (A–Z)</option>
                    <option value="name_desc">Name (Z–A)</option>
                    <option value="salary_asc">Salary (Low–High)</option>
                    <option value="salary_desc">Salary (High–Low)</option>
                </select>
                @if ($empSearch || $empTypeFilter || $empSort !== 'type_order')
                    <button wire:click="clearEmpFilters" class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>

    @elseif ($activeTab === 'attendance' && $attendanceMode === 'view')
        {{-- Two ways in, and nothing else: a type and then one of its people (that
             person's months), or a date (everyone, that day). --}}
        <div class="bg-gray-50 border-b border-gray-200 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $filterIcon }}" /></svg>
                    Filter by:
                </div>
                <select wire:model.live="filterAttendanceType" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">Select type</option>
                    <option value="management">Management</option>
                    <option value="teacher">Teacher</option>
                    <option value="driver">Driver</option>
                    <option value="employee">Employee</option>
                </select>
                <select wire:model.live="attEmpId" @disabled(!$filterAttendanceType)
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[170px] disabled:opacity-50 disabled:cursor-not-allowed">
                    <option value="">{{ $filterAttendanceType ? 'Select ' . $filterAttendanceType : 'Select a type first' }}</option>
                    @if ($filterAttendanceType)
                        @foreach ($attEmployees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }}{{ count($emp->types()) > 1 ? ' (' . implode(', ', array_map('ucfirst', $emp->types())) . ')' : '' }}</option>
                        @endforeach
                    @endif
                </select>
                <span class="text-gray-300 text-xs">or</span>
                <div class="flex items-center gap-1.5">
                    <label class="text-xs text-gray-500">Date</label>
                    <input type="date" wire:model.live="attendanceDate" max="{{ now()->format('Y-m-d') }}"
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                </div>
                @if ($attendanceDate || $filterAttendanceType || $attEmpId)
                    <button wire:click="clearAttFilters" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>

    @elseif ($activeTab === 'salary')
        {{-- A type, then one of its people: the tab is that person's salary account. --}}
        <div class="bg-gray-50 border-b border-gray-200 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $filterIcon }}" /></svg>
                    Filter by:
                </div>
                <select wire:model.live="salaryType" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">Select type</option>
                    <option value="management">Management</option>
                    <option value="teacher">Teacher</option>
                    <option value="driver">Driver</option>
                    <option value="employee">Employee</option>
                </select>
                <select wire:model.live="salaryEmpId" @disabled(!$salaryType)
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[170px] disabled:opacity-50 disabled:cursor-not-allowed">
                    <option value="">{{ $salaryType ? 'Select ' . $salaryType : 'Select a type first' }}</option>
                    @foreach ($salaryPeople as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->name }}{{ count($emp->types()) > 1 ? ' (' . implode(', ', array_map('ucfirst', $emp->types())) . ')' : '' }}</option>
                    @endforeach
                </select>
                @if ($salaryType || $salaryEmpId)
                    <button wire:click="clearSalaryPerson" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>

    @elseif ($activeTab === 'payments')
        <div class="bg-gray-50 border-b border-gray-200 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $filterIcon }}" /></svg>
                    Filter by:
                </div>
                <input wire:model.live.debounce.300ms="paymentSearch" type="text" placeholder="Search employee…"
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-56 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                <select wire:model.live="filterPaymentEmpId" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[160px]">
                    <option value="">All Employees</option>
                    @foreach ($allEmployeesForFilter as $emp)<option value="{{ $emp->id }}">{{ $emp->name }}</option>@endforeach
                </select>
                <div class="flex items-center gap-1.5">
                    <label class="text-xs text-gray-500">Month</label>
                    <input type="month" wire:model.live="filterPaymentMonth" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700" />
                </div>
                @if ($paymentSearch || $filterPaymentEmpId || $filterPaymentMonth)
                    <button wire:click="clearPaymentFilters" class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>
    @endif
    </div>{{-- /sticky header+tabs+filters --}}

    <div class="p-4 sm:p-6 space-y-5">

        {{-- ══════════ EMPLOYEES TAB ══════════ --}}
        @if ($activeTab === 'employees')

            {{-- Employee list (table). Under the name: the person's type — both, for
                 a teacher or a manager who also drives (they are still one row, kept
                 as a teacher or as management: one attendance, one salary) — and the
                 designation when it says more. After the mobile: this month's
                 attendance so far, present days out of working days. --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 w-10">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Employee</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Mobile</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Attendance · {{ now()->format('M') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Salary</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($employeesList as $i => $emp)
                                @php
                                    $typeLabels = array_map('ucfirst', $emp->types());
                                    $subLine    = implode(', ', $typeLabels);
                                    $says = trim((string) $emp->designation);
                                    if ($says !== '' && !in_array(mb_strtolower($says), array_map('mb_strtolower', $typeLabels), true)) {
                                        $subLine .= ' · ' . $says;
                                    }
                                    $att  = $monthAttendance[$emp->id] ?? null;
                                    $days = $att ? rtrim(rtrim(number_format($att['present'], 1), '0'), '.') : null;
                                @endphp
                                <tr class="hover:bg-gray-50/60 transition-colors" wire:key="emp-{{ $emp->id }}">
                                    <td class="px-4 py-3 text-xs text-gray-400">{{ $i + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            @if ($emp->photo)
                                                <img src="{{ $emp->photo }}" class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                            @else
                                                <div class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                                    <span class="text-xs font-bold text-gray-600">{{ strtoupper(substr($emp->name, 0, 1)) }}</span>
                                                </div>
                                            @endif
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-gray-800 truncate">{{ $emp->name }}</p>
                                                <p class="text-xs text-gray-400 truncate">{{ $subLine }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $emp->mobile ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 tabular-nums whitespace-nowrap">
                                        @if ($att && $att['working'] > 0)
                                            <span title="Present {{ $days }} of {{ $att['working'] }} working days this month — absent {{ $att['absent'] }}, half day {{ $att['half'] }}, leave {{ $att['leave'] }}">
                                                <span class="font-semibold text-gray-800">{{ $days }}</span> / {{ $att['working'] }}
                                            </span>
                                        @else
                                            <span class="text-gray-400" title="Not marked yet this month">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm font-bold text-emerald-700">₹{{ number_format($emp->salary, 0) }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <button wire:click="viewEmployee({{ $emp->id }})" title="View" class="p-1.5 rounded-md text-blue-600 hover:bg-blue-50 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            </button>
                                            <button wire:click="openEmpModal({{ $emp->id }})" title="Edit" class="p-1.5 rounded-md text-amber-600 hover:bg-amber-50 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </button>
                                            <button wire:click="deleteEmployee({{ $emp->id }})" title="Delete" class="p-1.5 rounded-md text-red-600 hover:bg-red-50 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-gray-400">No employees found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- ══════════ ATTENDANCE TAB ══════════ --}}
        @if ($activeTab === 'attendance')

            @if ($attendanceMode === 'pick_date')
                {{-- ─── STEP 1: WHICH DAY ARE WE MARKING? ─── --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm max-w-lg mx-auto">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h3 class="text-base font-semibold text-gray-900">Mark Attendance</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Step 1 of 2 — pick the date you are marking.</p>
                    </div>
                    <div class="px-5 py-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Date <span class="text-red-500">*</span></label>
                        <input type="date" wire:model.defer="markDate" max="{{ now()->format('Y-m-d') }}"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @error('markDate')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        <p class="text-xs text-gray-500 mt-3">
                            Teachers are not marked here — their attendance comes from the Teacher Attendance module.
                        </p>
                    </div>
                    <div class="px-5 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2">
                        <button wire:click="cancelMarking" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                        <button wire:click="confirmMarkDate"
                            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-md inline-flex items-center gap-1.5">
                            Continue
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                        </button>
                    </div>
                </div>

            @elseif ($attendanceMode === 'mark')
                {{-- ─── STEP 2: MARK THE STAFF (no teachers) ─── --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 bg-gradient-to-r from-emerald-50 to-teal-50 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700">Mark Attendance — {{ \Carbon\Carbon::parse($attendanceDate)->format('d M Y') }}</h3>
                            <p class="text-[11px] text-gray-500 mt-0.5">Step 2 of 2 — management, drivers and employees.</p>
                        </div>
                        <button wire:click="backToDatePick" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-md hover:bg-gray-50">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                            Change date
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 w-10">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Employee</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Mark</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($markEmployees as $i => $emp)
                                    @php $sel = $attendanceDraft[$emp->id] ?? $this->getAttendanceStatus($emp->id); @endphp
                                    <tr class="hover:bg-gray-50/50 transition-colors" wire:key="mark-{{ $emp->id }}">
                                        <td class="px-4 py-3 text-xs text-gray-400">{{ $i + 1 }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2.5">
                                                @if ($emp->photo)
                                                    <img src="{{ $emp->photo }}" class="w-8 h-8 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                                @else
                                                    <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0"><span class="text-xs font-bold text-gray-600">{{ strtoupper(substr($emp->name, 0, 1)) }}</span></div>
                                                @endif
                                                <div><p class="text-sm font-medium text-gray-800">{{ $emp->name }}</p><p class="text-xs text-gray-400">{{ $emp->designation ?? '' }}</p></div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex flex-wrap gap-1">@foreach ($emp->types() as $t)<span class="text-xs px-2 py-0.5 rounded-full font-medium border capitalize {{ $typeChip[$t] ?? 'bg-gray-50 text-gray-600 border-gray-200' }}">{{ $t }}</span>@endforeach</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-1">
                                                @foreach (['present' => 'P', 'absent' => 'A', 'half_day' => 'H', 'leave' => 'L'] as $st => $ltr)
                                                    @php $active = ['present' => 'bg-green-500 text-white', 'absent' => 'bg-red-500 text-white', 'half_day' => 'bg-amber-500 text-white', 'leave' => 'bg-blue-500 text-white'][$st]; @endphp
                                                    <button wire:click="setDraft({{ $emp->id }}, '{{ $st }}')"
                                                        class="w-8 h-7 text-xs font-bold rounded-lg transition-colors {{ $sel === $st ? $active : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">{{ $ltr }}</button>
                                                @endforeach
                                                @isset($attendanceDraft[$emp->id])
                                                    <span class="ml-1 text-[10px] text-amber-600 font-medium">unsaved</span>
                                                @endisset
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400">No employees found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t border-gray-200 flex items-center justify-end gap-2">
                        <button wire:click="cancelMarking" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                        <button wire:click="submitAttendance" wire:loading.attr="disabled" wire:target="submitAttendance"
                            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-md flex items-center gap-1.5 disabled:opacity-60">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            <span wire:loading.remove wire:target="submitAttendance">Submit Attendance</span>
                            <span wire:loading wire:target="submitAttendance">Saving…</span>
                        </button>
                    </div>
                </div>
            @elseif ($attView === 'date')
                {{-- ─── DATE VIEW: everyone's status on the chosen date — a plain list ─── --}}
                @php
                    $dayText = ['present' => 'Present', 'absent' => 'Absent', 'half_day' => 'Half day', 'leave' => 'Leave', 'holiday' => 'Holiday'];
                    $dayTone = ['present' => 'text-emerald-600', 'absent' => 'text-red-500', 'half_day' => 'text-amber-600', 'leave' => 'text-blue-600', 'holiday' => 'text-indigo-500'];
                    $dayDot  = ['present' => 'bg-emerald-500', 'absent' => 'bg-red-500', 'half_day' => 'bg-amber-500', 'leave' => 'bg-blue-500', 'holiday' => 'bg-indigo-400'];
                    $dayCount = collect($dayMarks)->countBy(fn ($m) => $m['status'] ?: 'not_marked');
                @endphp
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-gray-100 flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                        <h3 class="text-sm font-semibold text-gray-800">{{ \Carbon\Carbon::parse($attendanceDate)->format('l, d M Y') }}</h3>
                        <p class="text-xs text-gray-400 tabular-nums">
                            @foreach ($dayText as $st => $label)
                                {{ $label }} <span class="text-gray-700 font-medium">{{ $dayCount[$st] ?? 0 }}</span><span class="text-gray-300"> · </span>
                            @endforeach
                            Not marked <span class="text-gray-700 font-medium">{{ $dayCount['not_marked'] ?? 0 }}</span>
                        </p>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($dayEmployees as $i => $emp)
                            @php $mark = $dayMarks[$emp->id] ?? ['status' => null, 'remark' => '']; @endphp
                            <div class="flex items-center gap-3 px-5 py-2.5" wire:key="view-{{ $emp->id }}">
                                <span class="w-5 text-[11px] text-gray-300 tabular-nums flex-shrink-0">{{ $i + 1 }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm text-gray-800 truncate">{{ $emp->name }}</p>
                                    <p class="text-[11px] text-gray-400 truncate">{{ implode(', ', array_map('ucfirst', $emp->types())) }}</p>
                                </div>
                                @if ($mark['remark'] !== '')
                                    <span class="hidden sm:block text-xs text-gray-400 truncate max-w-[14rem]" title="{{ $mark['remark'] }}">{{ $mark['remark'] }}</span>
                                @endif
                                <span class="w-24 flex-shrink-0 inline-flex items-center justify-end gap-1.5 text-xs font-medium {{ $dayTone[$mark['status']] ?? 'text-gray-300' }}">
                                    @if ($mark['status'])
                                        <span class="w-1.5 h-1.5 rounded-full {{ $dayDot[$mark['status']] ?? 'bg-gray-300' }}"></span>
                                        {{ $dayText[$mark['status']] ?? ucfirst(str_replace('_', ' ', $mark['status'])) }}
                                    @else
                                        Not marked
                                    @endif
                                </span>
                            </div>
                        @empty
                            <p class="py-12 text-center text-sm text-gray-400">No employees found.</p>
                        @endforelse
                    </div>
                </div>

            @elseif ($attView === 'employee' && $attEmp)
                {{-- ─── EMPLOYEE VIEW: the session's months, a quiet card each ─── --}}
                @php
                    // Soft tints and plain numbers: a day says what it was without shouting.
                    $dayCell = [
                        'present'  => 'bg-emerald-50 text-emerald-700',
                        'absent'   => 'bg-red-50 text-red-600',
                        'half_day' => 'bg-amber-50 text-amber-700',
                        'leave'    => 'bg-blue-50 text-blue-700',
                        'holiday'  => 'text-gray-300',
                    ];
                @endphp
                <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 px-1">
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-gray-800 truncate">{{ $attEmp->name }}</h3>
                        <p class="text-xs text-gray-400">{{ implode(', ', array_map('ucfirst', $attEmp->types())) }} · {{ $attPeriodLabel }}</p>
                    </div>
                    <p class="text-xs text-gray-400 tabular-nums">
                        Present <span class="text-gray-700 font-medium">{{ $attCounts['present'] ?? 0 }}</span><span class="text-gray-300"> · </span>
                        Absent <span class="text-gray-700 font-medium">{{ $attCounts['absent'] ?? 0 }}</span><span class="text-gray-300"> · </span>
                        Half day <span class="text-gray-700 font-medium">{{ $attCounts['half_day'] ?? 0 }}</span><span class="text-gray-300"> · </span>
                        Leave <span class="text-gray-700 font-medium">{{ $attCounts['leave'] ?? 0 }}</span>
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @forelse ($attMonths as $ym => $m)
                        @php $mc = $m['counts']; @endphp
                        <div class="bg-white rounded-xl border border-gray-200 p-4" wire:key="cal-{{ $ym }}">
                            <div class="flex items-baseline justify-between mb-3">
                                <p class="text-xs font-medium text-gray-700">{{ $m['label'] }}</p>
                                <span class="text-xs text-gray-400 tabular-nums">{{ $mc['marked'] > 0 ? $m['pct'] . '%' : '—' }}</span>
                            </div>

                            {{-- Sunday-first weekday header --}}
                            <div class="grid grid-cols-7 gap-1 mb-1">
                                @foreach (['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $dow)
                                    <div class="text-center text-[9px] text-gray-300">{{ $dow }}</div>
                                @endforeach
                            </div>

                            <div class="grid grid-cols-7 gap-1">
                                {{-- Blanks before the 1st so the weekdays line up --}}
                                @for ($b = 0; $b < $m['lead']; $b++)
                                    <div></div>
                                @endfor

                                @foreach ($m['cells'] as $d)
                                    @if (!$d['in_period'])
                                        <div class="py-1 text-center text-[10px] leading-none text-gray-200 tabular-nums">{{ $d['day'] }}</div>
                                    @else
                                        <div class="rounded py-1 text-center text-[10px] leading-none tabular-nums {{ $dayCell[$d['status']] ?? $dayCell['holiday'] }} {{ $d['dim'] ? 'opacity-30' : '' }}"
                                            title="{{ \Carbon\Carbon::parse($d['date'])->format('D, d M Y') }} · {{ ucfirst(str_replace('_', ' ', $d['status'])) }}">
                                            {{ $d['day'] }}
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                            {{-- That month's figures, on one plain line --}}
                            <p class="mt-3 pt-2.5 border-t border-gray-100 text-[10px] text-gray-400 tabular-nums">
                                P <span class="text-gray-700 font-medium">{{ $mc['present'] }}</span><span class="text-gray-300"> · </span>
                                A <span class="text-gray-700 font-medium">{{ $mc['absent'] }}</span><span class="text-gray-300"> · </span>
                                H <span class="text-gray-700 font-medium">{{ $mc['half_day'] }}</span><span class="text-gray-300"> · </span>
                                L <span class="text-gray-700 font-medium">{{ $mc['leave'] }}</span>
                            </p>
                        </div>
                    @empty
                        <p class="sm:col-span-2 lg:col-span-3 text-sm text-gray-400 text-center py-6">Nothing to show for this period.</p>
                    @endforelse
                </div>

                <div class="flex flex-wrap gap-x-3 gap-y-1 text-[11px] text-gray-400 px-1">
                    <span><span class="inline-block w-2.5 h-2.5 rounded-sm bg-emerald-100 align-middle"></span> Present</span>
                    <span><span class="inline-block w-2.5 h-2.5 rounded-sm bg-red-100 align-middle"></span> Absent</span>
                    <span><span class="inline-block w-2.5 h-2.5 rounded-sm bg-amber-100 align-middle"></span> Half day</span>
                    <span><span class="inline-block w-2.5 h-2.5 rounded-sm bg-blue-100 align-middle"></span> Leave</span>
                    <span><span class="inline-block w-2.5 h-2.5 rounded-sm bg-white border border-gray-200 align-middle"></span> Holiday / not marked</span>
                </div>
            @else
                {{-- ─── PROMPT (nothing selected yet) ─── --}}
                <div class="bg-white rounded-xl border border-gray-200 p-10 sm:p-12 text-center">
                    <p class="text-sm text-gray-600 font-medium">Use the filter above to view attendance.</p>
                    <div class="text-xs text-gray-400 mt-2 space-y-0.5">
                        <p>Pick a <strong class="font-medium text-gray-500">type</strong>, then a person, to see their months.</p>
                        <p>Or pick a <strong class="font-medium text-gray-500">date</strong> to see everyone's attendance that day.</p>
                    </div>
                </div>
            @endif
        @endif

        {{-- ══════════ SALARY TAB ══════════
             One person's salary account. Their details first; then every month
             from 1 April of the session to this one — what they are paid a month,
             the attendance marked, the salary it works out to and what was paid —
             with the totals under it. Payments are added from the header. --}}
        @if ($activeTab === 'salary')
            @if ($salaryPerson && $salaryAccount)
                @php
                    $money = fn ($v) => '₹' . number_format((float) $v, 0);
                    $personTypes = array_map('ucfirst', $salaryPerson->types());
                    $personLine  = implode(', ', $personTypes);
                    $says = trim((string) $salaryPerson->designation);
                    if ($says !== '' && !in_array(mb_strtolower($says), array_map('mb_strtolower', $personTypes), true)) {
                        $personLine .= ' · ' . $says;
                    }
                    $personFacts = [
                        'Mobile'         => $salaryPerson->mobile ?: '—',
                        'Email'          => $salaryPerson->email ?: '—',
                        'Monthly salary' => $money($salaryPerson->salary),
                        'Joining date'   => $salaryPerson->joining_date ? \Carbon\Carbon::parse($salaryPerson->joining_date)->format('d M Y') : '—',
                        'Bank'           => $salaryPerson->bank_name ?: '—',
                        'Account no.'    => $salaryPerson->bank_account_no ?: '—',
                        'IFSC'           => $salaryPerson->bank_ifsc ?: '—',
                        'Account holder' => $salaryPerson->bank_holder_name ?: '—',
                    ];
                @endphp

                {{-- The person --}}
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-center gap-3">
                        @if ($salaryPerson->photo)
                            <img src="{{ $salaryPerson->photo }}" class="w-11 h-11 rounded-full object-cover border border-gray-200 flex-shrink-0">
                        @else
                            <div class="w-11 h-11 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                <span class="text-sm font-bold text-gray-600">{{ strtoupper(substr($salaryPerson->name, 0, 1)) }}</span>
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="text-base font-semibold text-gray-900 truncate">{{ $salaryPerson->name }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ $personLine }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-6 gap-y-3 mt-4 pt-4 border-t border-gray-100">
                        @foreach ($personFacts as $label => $value)
                            <div class="min-w-0">
                                <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wide">{{ $label }}</p>
                                <p class="text-sm text-gray-800 truncate" title="{{ $value }}">{{ $value }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Their months --}}
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-gray-100 flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                        <h3 class="text-sm font-semibold text-gray-800">Salary by month</h3>
                        <p class="text-xs text-gray-400 tabular-nums">
                            Salary <span class="text-gray-700 font-medium">{{ $money($salaryAccount['payable']) }}</span><span class="text-gray-300"> · </span>
                            Paid <span class="text-emerald-600 font-medium">{{ $money($salaryAccount['paid']) }}</span><span class="text-gray-300"> · </span>
                            Balance <span class="font-medium {{ $salaryAccount['balance'] > 0 ? 'text-red-500' : 'text-gray-700' }}">{{ $money($salaryAccount['balance']) }}</span>
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500">Month</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Attendance</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Monthly salary</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Salary by attendance</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500">Payment</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($salaryAccount['months'] as $m)
                                    <tr class="hover:bg-gray-50/60 transition-colors" wire:key="sal-month-{{ $m['ym'] }}">
                                        <td class="px-5 py-3 align-top">
                                            <p class="text-sm font-medium text-gray-800">{{ $m['label'] }}</p>
                                            @if ($m['running'])
                                                <p class="text-[11px] text-gray-400">Running month — so far</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 align-top text-xs text-gray-400 tabular-nums whitespace-nowrap">
                                            @if ($m['present'] + $m['absent'] + $m['half'] + $m['leave'] > 0)
                                                Present <span class="text-gray-700 font-medium">{{ $m['present'] }}</span><span class="text-gray-300"> · </span>
                                                Absent <span class="text-gray-700 font-medium">{{ $m['absent'] }}</span><span class="text-gray-300"> · </span>
                                                Half <span class="text-gray-700 font-medium">{{ $m['half'] }}</span><span class="text-gray-300"> · </span>
                                                Leave <span class="text-gray-700 font-medium">{{ $m['leave'] }}</span>
                                            @else
                                                Not marked
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 align-top text-right text-sm text-gray-600 tabular-nums">{{ $money($m['base']) }}</td>
                                        <td class="px-4 py-3 align-top text-right text-sm font-semibold text-gray-900 tabular-nums">{{ $money($m['payable']) }}</td>
                                        <td class="px-5 py-3 align-top text-right">
                                            @if ($m['payments'])
                                                <p class="text-sm font-semibold text-emerald-600 tabular-nums">{{ $money($m['paid']) }}</p>
                                                @foreach ($m['payments'] as $pay)
                                                    <p class="text-[11px] text-gray-400 tabular-nums" title="{{ $pay['remark'] }}">
                                                        {{ $pay['date'] }}@if (count($m['payments']) > 1) · {{ $money($pay['amount']) }}@endif{{ $pay['from'] ? ' · ' . $pay['from'] : '' }}
                                                    </p>
                                                @endforeach
                                            @else
                                                <span class="text-sm text-gray-300">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50 border-t border-gray-200">
                                <tr>
                                    <td colspan="3" class="px-5 py-3 text-xs font-semibold text-gray-600">Total · {{ count($salaryAccount['months']) }} month{{ count($salaryAccount['months']) === 1 ? '' : 's' }}</td>
                                    <td class="px-4 py-3 text-right text-sm font-bold text-gray-900 tabular-nums">{{ $money($salaryAccount['payable']) }}</td>
                                    <td class="px-5 py-3 text-right text-sm font-bold text-emerald-600 tabular-nums">{{ $money($salaryAccount['paid']) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @else
                {{-- ─── PROMPT (nobody picked yet) ─── --}}
                <div class="bg-white rounded-xl border border-gray-200 p-10 sm:p-12 text-center">
                    <p class="text-sm text-gray-600 font-medium">Use the filter above to open someone's salary.</p>
                    <p class="text-xs text-gray-400 mt-2">Pick a <strong class="font-medium text-gray-500">type</strong>, then a person — their months since April and an Add Payment button follow.</p>
                </div>
            @endif
        @endif

        {{-- ══════════ PAYMENTS TAB ══════════ --}}
        @if ($activeTab === 'payments')

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 w-10">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Employee</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Month</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Mode</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Paid By</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($payments as $i => $payment)
                                <tr class="hover:bg-gray-50/50 transition-colors" wire:key="pay-{{ $payment->id }}">
                                    <td class="px-4 py-3 text-xs text-gray-400">{{ $i + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-medium text-gray-800">{{ $payment->employee?->name ?? '—' }}</p>
                                        <p class="text-xs text-gray-400 capitalize">{{ $payment->employee ? implode(', ', $payment->employee->types()) : '' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ \Carbon\Carbon::parse($payment->month . '-01')->format('M Y') }}</td>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-800">₹{{ number_format($payment->amount, 0) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 capitalize">{{ str_replace('_', ' ', $payment->payment_mode) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $payment->paid_by ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        @if ($payment->status === 'paid')
                                            <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-medium bg-green-50 text-green-700 border border-green-100"><span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Paid</span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-medium bg-amber-50 text-amber-700 border border-amber-100"><span class="w-1.5 h-1.5 bg-amber-400 rounded-full"></span> Pending</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600">{{ $payment->payment_date?->format('d M Y') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-4 py-10 text-center text-sm text-gray-400">No payment records found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    {{-- ══════════ ADD/EDIT EMPLOYEE SLIDE-IN PANEL ══════════
         The Students form's look: a wide panel, the photo row, one flat
         two-column grid, the actions in the footer.

         Adding opens on "who are you adding?". Management and Employee carry on
         in the form below; Driver gets the driver's own form (Transport's
         fields beside the salary and bank details) and becomes a Transport
         driver too; Teacher opens the Teachers page's own form (further down).
         An entry being edited keeps the form it always had, type included. --}}
    @if ($showEmpModal)
        @php
            $addingDriver = !$editEmpId && $empTypeChosen && $empType === 'driver';
            $kindLabel    = ['management' => 'Management', 'teacher' => 'Teacher', 'driver' => 'Driver', 'employee' => 'Employee'];
        @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-[9999] overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeEmpModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col">

                {{-- Fixed header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ $editEmpId ? 'Edit Employee' : ($empTypeChosen ? 'New ' . ($kindLabel[$empType] ?? 'Employee') : 'Add Employee') }}
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            @if ($editEmpId)
                                Update the details below
                            @elseif (!$empTypeChosen)
                                Choose who you are adding
                            @elseif ($addingDriver)
                                Also added under Transport → Drivers, with a login on the default password 123456
                            @else
                                Fill in the details below
                            @endif
                        </p>
                    </div>
                    <button wire:click="closeEmpModal" type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Scrollable body --}}
                <div class="flex-1 overflow-y-auto overflow-x-hidden">
                    <div class="px-6 py-6 space-y-5">

                        {{-- Adding: first say who it is; the form follows the answer. The
                             row is in a file of its own — it sits on top of the teacher
                             form as well. --}}
                        @unless ($editEmpId)
                            @include('livewire.partials.payroll-add-chooser', ['selected' => $empTypeChosen ? $empType : '', 'call' => 'chooseEmpType'])
                        @endunless

                        @if ($empTypeChosen)
                        {{-- Photo (single inline row, as on Students) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                {{ $addingDriver ? 'Driver' : 'Employee' }} Photo <span class="text-gray-400 font-normal">(Optional, max 1 MB)</span>
                            </label>
                            <div class="flex items-center gap-3">
                                @if ($empPhoto)
                                    <img src="{{ $empPhoto->temporaryUrl() }}"
                                        class="w-12 h-12 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                @elseif ($empExistingPhoto)
                                    <img src="{{ $empExistingPhoto }}"
                                        class="w-12 h-12 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                @else
                                    <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                @endif
                                <x-admin.photo-cropper model="empPhoto" class="flex-1 text-sm" />
                            </div>
                            <div wire:loading wire:target="empPhoto" class="text-xs text-blue-600 mt-1">Uploading…</div>
                            @error('empPhoto')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        {{-- One flat two-column grid --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name <span class="text-red-500">*</span></label>
                                <input wire:model.defer="empName" type="text" maxlength="255" placeholder="Enter full name"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                           @error('empName') border-red-400 @enderror">
                                @error('empName')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Email <span class="text-gray-400 font-normal text-xs">(optional)</span></label>
                                <input wire:model.defer="empEmail" type="email" placeholder="name@example.com"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                           @error('empEmail') border-red-400 @enderror">
                                @error('empEmail')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Mobile @if ($addingDriver)<span class="text-red-500">*</span>@endif</label>
                                <input wire:model.defer="empMobile" type="tel" maxlength="10" inputmode="numeric"
                                    oninput="this.value=this.value.replace(/\D/g,'').slice(0,10)" placeholder="10-digit mobile"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                           @error('empMobile') border-red-400 @enderror">
                                @error('empMobile')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            @if ($addingDriver)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">License No.</label>
                                <input wire:model.defer="drvLicenseNo" type="text" maxlength="50" placeholder="Driving license number"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                           @error('drvLicenseNo') border-red-400 @enderror">
                                @error('drvLicenseNo')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Vehicle No.</label>
                                <input wire:model.defer="drvVehicleNo" type="text" maxlength="30" placeholder="e.g. RJ14 AB 1234"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                           @error('drvVehicleNo') border-red-400 @enderror">
                                @error('drvVehicleNo')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Experience (yrs)</label>
                                <input wire:model.defer="drvExperience" type="number" min="0" max="50" placeholder="0"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                           @error('drvExperience') border-red-400 @enderror">
                                @error('drvExperience')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            @else
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Designation</label>
                                <input wire:model.defer="empDesignation" type="text" maxlength="100" placeholder="e.g. Manager"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                           @error('empDesignation') border-red-400 @enderror">
                                @error('empDesignation')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            @endif
                            {{-- An entry being edited can still be moved to another type, as before. --}}
                            @if ($editEmpId)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Type <span class="text-red-500">*</span></label>
                                    <select wire:model.live="empType"
                                        class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="teacher">Teacher</option>
                                        <option value="management">Management</option>
                                        <option value="employee">Employee</option>
                                        <option value="driver">Driver</option>
                                    </select>
                                    @error('empType')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                            @endif
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Salary (₹) <span class="text-red-500">*</span></label>
                                <input wire:model.defer="empSalary" type="number" min="0" placeholder="Monthly salary"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                           @error('empSalary') border-red-400 @enderror">
                                @error('empSalary')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Joining Date</label>
                                <input wire:model.defer="empJoiningDate" type="date"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                           @error('empJoiningDate') border-red-400 @enderror">
                                @error('empJoiningDate')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            @if ($editEmpId && $empType === 'teacher')
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Link to Teacher Detail <span class="text-gray-400 font-normal text-xs">(optional — links attendance to teacher records)</span></label>
                                    <select wire:model.defer="empTeacherDetailId"
                                        class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">— Don't link —</option>
                                        @foreach (\App\Models\Teacher\TeacherDetail::with('user')->where('organization_id', auth()->user()->organization_id)->get() as $td)
                                            <option value="{{ $td->id }}">{{ $td->user?->name ?? 'Teacher #' . $td->id }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Bank Name</label>
                                <input wire:model.defer="empBankName" type="text" maxlength="100" placeholder="Bank name"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                           @error('empBankName') border-red-400 @enderror">
                                @error('empBankName')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Account Holder</label>
                                <input wire:model.defer="empHolderName" type="text" maxlength="100" placeholder="Account holder name"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                           @error('empHolderName') border-red-400 @enderror">
                                @error('empHolderName')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Account Number</label>
                                <input wire:model.defer="empAccountNo" type="text" maxlength="20" inputmode="numeric" placeholder="Account number"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                           @error('empAccountNo') border-red-400 @enderror">
                                @error('empAccountNo')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">IFSC Code</label>
                                <input wire:model.defer="empIfsc" type="text" maxlength="11" placeholder="e.g. HDFC0001234"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                           @error('empIfsc') border-red-400 @enderror">
                                @error('empIfsc')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Branch</label>
                                <input wire:model.defer="empBranch" type="text" maxlength="100" placeholder="Branch name"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                           @error('empBranch') border-red-400 @enderror">
                                @error('empBranch')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        {{-- Full-width address --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Address</label>
                            <textarea wire:model.defer="empAddress" rows="2" maxlength="500" placeholder="Address"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 resize-none
                                       @error('empAddress') border-red-400 @enderror"></textarea>
                            @error('empAddress')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        @if ($addingDriver)
                            {{-- The routes this driver runs, as Transport's driver form assigns them --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Assign Routes <span class="text-gray-400 font-normal text-xs">(select one or more)</span></label>
                                @if (count($driverRouteOptions) === 0)
                                    <p class="text-xs text-gray-400 border border-dashed border-gray-200 rounded-md p-3">No routes yet. Create them under Transport, then assign them to this driver there.</p>
                                @else
                                    <div class="border border-gray-300 rounded-md divide-y divide-gray-100 max-h-56 overflow-y-auto">
                                        @foreach ($driverRouteOptions as $r)
                                            <label class="flex items-center gap-2.5 px-3 py-2.5 hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" wire:model.defer="drvRoutes" value="{{ $r->id }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                <span class="text-sm text-gray-700">
                                                    {{ $r->route_name }}
                                                    @if ($r->vehicle_type)
                                                        <span class="text-xs text-gray-400">· {{ $r->vehicle_type }}</span>
                                                    @endif
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            {{-- Active toggle --}}
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model.defer="drvActive" class="rounded">
                                <span class="text-sm text-gray-700">Active (can log in)</span>
                            </label>
                        @endif
                        @endif
                    </div>
                </div>

                {{-- Fixed footer --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeEmpModal" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    {{-- Nothing to save until "who are you adding?" is answered. --}}
                    @if ($empTypeChosen)
                        <button wire:click="saveEmployee" type="button" wire:loading.attr="disabled" wire:target="saveEmployee, empPhoto"
                            class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                            <span wire:loading.remove wire:target="saveEmployee">{{ $editEmpId ? 'Update Employee' : 'Save ' . ($kindLabel[$empType] ?? 'Employee') }}</span>
                            <span wire:loading wire:target="saveEmployee">Saving...</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ ADD → TEACHER ══════════
         The Teachers page's own add form — the same component, in its form-only
         mode — so a teacher added from here is added exactly as there (username,
         welcome mail and all) and then has a payroll row like every teacher. --}}
    @if ($showTeacherForm)
        @livewire(\App\Livewire\Admin\Teacher::class, ['formOnly' => true], key('payroll-add-teacher'))
    @endif

    {{-- ══════════ EMPLOYEE DETAIL SLIDE-IN PANEL ══════════ --}}
    @if ($showEmpDetailModal && $selectedEmployee)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeEmpDetailModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $selectedEmployee->name }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Employee Details</p>
                    </div>
                    <button wire:click="closeEmpDetailModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    @foreach ($employeeDetails as $label => $value)
                        <div class="grid grid-cols-3 gap-3 text-sm">
                            <span class="text-xs text-gray-400 uppercase tracking-wider">{{ $label }}</span>
                            <span class="col-span-2 text-gray-800 font-medium">{{ $value }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeEmpDetailModal" class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ ADD PAYMENT SLIDE-IN PANEL ══════════
         A payment to the person the Mark Salary tab is on: amount, date, from
         and a remark. (The month-by-month pay panel below is the earlier one,
         kept; this page no longer opens it.) --}}
    @if ($showSalaryPayPanel && $salaryPerson)
        <div class="fixed inset-x-0 bottom-0 top-16 z-[9999] overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeSalaryPayment"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900">Add Payment</h2>
                        <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $salaryPerson->name }} · balance ₹{{ number_format((float) ($salaryAccount['balance'] ?? 0), 0) }}</p>
                    </div>
                    <button wire:click="closeSalaryPayment" type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Amount (₹) <span class="text-red-500">*</span></label>
                            <input wire:model.defer="spAmount" type="number" step="1" min="1" placeholder="0"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                       @error('spAmount') border-red-400 @enderror">
                            @error('spAmount')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Date <span class="text-red-500">*</span></label>
                            <input wire:model.defer="spDate" type="date" max="{{ now()->format('Y-m-d') }}"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                       @error('spDate') border-red-400 @enderror">
                            @error('spDate')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">From <span class="text-red-500">*</span></label>
                        <input wire:model.defer="spFrom" type="text" maxlength="255" placeholder="Who is making this payment"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                   @error('spFrom') border-red-400 @enderror">
                        @error('spFrom')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Remark</label>
                        <textarea wire:model.defer="spRemark" rows="3" maxlength="500" placeholder="What is this payment for?"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 resize-none
                                   @error('spRemark') border-red-400 @enderror"></textarea>
                        @error('spRemark')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeSalaryPayment" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveSalaryPayment" type="button" wire:loading.attr="disabled" wire:target="saveSalaryPayment"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveSalaryPayment">Save Payment</span>
                        <span wire:loading wire:target="saveSalaryPayment">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ PAY SALARY SLIDE-IN PANEL ══════════ --}}
    @if ($showPayModal)
        @php $payEmp = \App\Models\Admin\AdminEmployee::find($payEmployeeId); @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-[9999] overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePayModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Pay Salary</h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $payEmp?->name }} · {{ \Carbon\Carbon::parse($salaryMonth . '-01')->format('M Y') }}</p>
                    </div>
                    <button wire:click="closePayModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto overflow-x-hidden px-6 py-6 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Amount (₹) * <span class="text-gray-400 font-normal">(attendance-adjusted)</span></label>
                        <input type="number" wire:model.defer="payAmount" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400">
                        @error('payAmount')<p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Payment Mode *</label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach (['cash' => 'Cash', 'online' => 'Online', 'bank_transfer' => 'Bank Transfer', 'cheque' => 'Cheque'] as $mode => $label)
                                <button type="button" wire:click="$set('payMode', '{{ $mode }}')"
                                    class="py-2 text-xs font-semibold rounded-lg border transition-colors {{ $payMode === $mode ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-gray-600 border-gray-300 hover:border-emerald-400 hover:text-emerald-600' }}">{{ $label }}</button>
                            @endforeach
                        </div>
                        @if (in_array($payMode, ['online', 'bank_transfer']))
                            <p class="mt-1.5 text-[11px] text-emerald-700 bg-emerald-50 border border-emerald-100 rounded-md px-2 py-1">Amount will be credited to the employee's account.</p>
                        @endif
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Paid By *</label>
                        <input type="text" wire:model.defer="payPaidBy" placeholder="Who is making this payment"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400">
                        @error('payPaidBy')<p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Payment Date *</label>
                        <input type="date" wire:model.defer="payDate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400">
                    </div>

                    @if (in_array($payMode, ['online', 'bank_transfer', 'cheque']))
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Transaction / Cheque ID</label>
                            <input type="text" wire:model.defer="payTransactionId" placeholder="Transaction reference" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400">
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Remark (Optional)</label>
                        <input type="text" wire:model.defer="payRemark" placeholder="Optional note" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400">
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closePayModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="savePayment" class="px-5 py-2 text-sm font-semibold rounded-md text-white {{ in_array($payMode, ['online', 'bank_transfer']) ? 'bg-blue-600 hover:bg-blue-700' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                        {{ in_array($payMode, ['online', 'bank_transfer']) ? 'Pay & Credit' : 'Mark as Paid' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ MARK ATTENDANCE SLIDE-IN ══════════
         The Attendance page's mark panel: light scrim, plain header, one quiet
         toolbar, a flat list of rows and the actions in the footer. Everyone is
         on it at once — teachers first, then management, drivers and employees,
         A to Z within each. Picking a status is handled by Alpine and only
         synced to the component (no request per click); it all saves together.
         A teacher's row offers what the Attendance module marks a teacher with
         and is saved to its records; everyone else has Leave where a teacher
         has Holiday. --}}
    @php
        // Holiday on every row, so a holiday can be given to everyone at once; a
        // teacher's row has no Leave (the Attendance module keeps none for them).
        $markOpts = [
            'staff'   => ['present' => 'Present', 'absent' => 'Absent', 'half_day' => 'Half', 'leave' => 'Leave', 'holiday' => 'Holiday'],
            'teacher' => ['present' => 'Present', 'absent' => 'Absent', 'half_day' => 'Half', 'holiday' => 'Holiday'],
        ];
        // The remark every row shares, if they share one, for the "Remark for
        // all" box — a saved holiday opens with its remark in it.
        $panelRemarks = collect($panelRows)->map(fn ($r) => (string) ($r['remark'] ?? ''))->unique();
        $panelCommon  = $panelRemarks->count() === 1 ? $panelRemarks->first() : '';
        $markSel = [
            'present'  => 'bg-emerald-50 text-emerald-700 font-medium',
            'absent'   => 'bg-red-50 text-red-600 font-medium',
            'half_day' => 'bg-amber-50 text-amber-700 font-medium',
            'holiday'  => 'bg-indigo-50 text-indigo-700 font-medium',
            'leave'    => 'bg-blue-50 text-blue-700 font-medium',
        ];
    @endphp
    @if ($showMarkPanel)
    <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeMarkPanel"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col"
            wire:key="pmark-{{ count($panelRows) }}"
            {{-- x-data carries no server data, so it reads the same on every
                 render and a date change updates the panel in place; the rows
                 come from the seed below. --}}
            x-data="{
                rows: {},
                remarks: {},
                common: '',
                get total() { return Object.keys(this.rows).length },
                get marked() { return Object.values(this.rows).filter(v => v !== '').length },
                get allHoliday() { return this.total > 0 && Object.values(this.rows).every(v => v === 'holiday') },
                /* Everyone on Holiday: the day has to say what it is for, so no
                   row may go without a remark. */
                get remarkMissing() {
                    return this.allHoliday && Object.keys(this.rows).some(id => ! String(this.remarks[id] || '').trim());
                },
                /* The one remark of a holiday for everyone, written into every
                   row — each can still be changed on its own. (The remarks are
                   kept here as well as on the component, so the check above
                   follows every keystroke.) */
                remarkAll(text) {
                    Object.keys(this.rows).forEach(id => {
                        this.remarks[id] = text;
                        this.$wire.$set('panelRows.' + id + '.remark', text, false);
                    });
                },
                pick(id, v) {
                    this.rows[id] = v;
                    /* Local set: the change rides along with the next request
                       (Save) instead of costing a round trip per click. */
                    this.$wire.$set('panelRows.' + id + '.status', v, false);
                },
                all(v) { Object.keys(this.rows).forEach(id => this.pick(id, v)) },
            }">

            {{-- Seeds the rows when the panel opens, and again for each new date
                 (a new key is a new element, so its x-init runs again). --}}
            <div class="hidden" wire:key="pmark-seed-{{ $panelDate }}"
                x-init="rows = @js(collect($panelRows)->map(fn ($r) => (string) ($r['status'] ?? ''))->all()); remarks = @js(collect($panelRows)->map(fn ($r) => (string) ($r['remark'] ?? ''))->all()); common = @js($panelCommon)"></div>

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-900">Mark Attendance</h2>
                    <p class="text-xs text-gray-500 mt-0.5">{{ \Carbon\Carbon::parse($panelDate)->format('l, d M Y') }}</p>
                </div>
                <button wire:click="closeMarkPanel" type="button"
                    class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Toolbar --}}
            <div class="px-6 py-3 border-b border-gray-100 flex flex-wrap items-center gap-2 flex-shrink-0">
                {{-- Left alone by re-renders, so the calendar and a date being
                     typed are never torn down mid-way; only a whole date is
                     sent, once the typing pauses. --}}
                <input type="date" wire:ignore value="{{ $panelDate }}" max="{{ now()->toDateString() }}"
                    x-on:input.debounce.400ms="if (/^(19|20)\d{2}-\d{2}-\d{2}$/.test($el.value) && $el.value !== $wire.panelDate) $wire.$set('panelDate', $el.value)"
                    class="text-sm border border-gray-300 rounded-md px-3 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                <div class="inline-flex items-center rounded-md border border-gray-200 overflow-hidden text-xs">
                    <button type="button" x-on:click="all('present')" class="px-2.5 py-1.5 text-gray-600 hover:bg-gray-50">All present</button>
                    <button type="button" x-on:click="all('absent')" class="px-2.5 py-1.5 text-gray-600 hover:bg-gray-50 border-l border-gray-200">All absent</button>
                    <button type="button" x-on:click="all('holiday')" class="px-2.5 py-1.5 text-gray-600 hover:bg-gray-50 border-l border-gray-200">All holiday</button>
                    <button type="button" x-on:click="all('')" class="px-2.5 py-1.5 text-gray-500 hover:bg-gray-50 border-l border-gray-200">Clear</button>
                </div>
                <span class="ml-auto text-xs text-gray-400 tabular-nums" x-text="marked + ' of ' + total + ' marked'"></span>
            </div>

            {{-- Everyone on Holiday: one remark, typed once, goes on every row —
                 and it is compulsory. --}}
            <div x-show="allHoliday" style="display: none" class="px-6 py-2.5 border-b border-gray-100 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <label for="pmark-remark-all" class="text-xs font-medium text-gray-600 flex-shrink-0">Remark for all <span class="text-red-500">*</span></label>
                    <input id="pmark-remark-all" type="text" x-model="common" x-on:input="remarkAll(common)" maxlength="255" placeholder="e.g. Diwali"
                        class="flex-1 min-w-0 text-sm border rounded-md px-3 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                        :class="remarkMissing ? 'border-red-300' : 'border-gray-300'">
                </div>
                <p x-show="remarkMissing" class="mt-1 text-xs text-red-500">Say what the holiday is for — it is needed before saving.</p>
            </div>

            {{-- One quiet line explaining the day --}}
            @if ($panelExisting)
                <p class="px-6 py-2 text-xs text-amber-700 border-b border-gray-100 flex-shrink-0">Already marked for this date — change what you need and save to update it.</p>
            @else
                <p class="px-6 py-2 text-xs text-gray-500 border-b border-gray-100 flex-shrink-0">Only the rows you set are saved — an unmarked day stays open.</p>
            @endif

            {{-- Rows. The status classes are bound as objects, so a date change
                 (which updates these rows in place) takes the old day's
                 colours off again. --}}
            <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
                @forelse ($markPeople as $i => $emp)
                    @php
                        $asTeacher = $emp->isTeacher() && $emp->teacher_detail_id;
                        $typeLine  = implode(', ', array_map('ucfirst', $emp->types()));
                        $subLine   = $emp->designation && strcasecmp((string) $emp->designation, $typeLine) !== 0
                            ? $typeLine . ' · ' . $emp->designation
                            : $typeLine;
                    @endphp
                    <div wire:key="pmark-row-{{ $emp->id }}" class="flex flex-wrap items-center gap-x-3 gap-y-2 px-6 py-2.5"
                        :class="{ 'bg-gray-50/60': rows[{{ $emp->id }}] === '' }">
                        <span class="w-5 text-[11px] text-gray-300 tabular-nums flex-shrink-0">{{ $i + 1 }}</span>
                        @if ($emp->photo)
                            <img src="{{ $emp->photo }}" class="w-8 h-8 rounded-full object-cover border border-gray-200 flex-shrink-0">
                        @else
                            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 text-[11px] font-medium flex-shrink-0">{{ strtoupper(substr($emp->name, 0, 1)) }}</div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-gray-800 truncate">{{ $emp->name }}</p>
                            <p class="text-[11px] text-gray-400 truncate">{{ $subLine }}</p>
                        </div>
                        <input type="text" wire:model="panelRows.{{ $emp->id }}.remark" x-on:input="remarks[{{ $emp->id }}] = $event.target.value" placeholder="Remark" maxlength="255"
                            class="w-28 sm:w-36 text-xs border border-gray-200 rounded-md px-2.5 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        {{-- A fixed place for the statuses, the group at its right edge: a
                             teacher's row has four of them and anyone else's five, and
                             the remark boxes still line up down the list. --}}
                        <div class="flex justify-end flex-shrink-0" style="width: 19.5rem">
                        <div class="inline-flex items-center rounded-md border border-gray-200 overflow-hidden text-[11px] flex-shrink-0">
                            @foreach ($markOpts[$asTeacher ? 'teacher' : 'staff'] as $st => $label)
                                <button type="button" x-on:click="pick({{ $emp->id }}, '{{ $st }}')"
                                    class="w-14 py-1.5 text-center {{ $loop->first ? '' : 'border-l border-gray-200' }}"
                                    :class="{ '{{ $markSel[$st] }}': rows[{{ $emp->id }}] === '{{ $st }}', 'text-gray-500 hover:bg-gray-50': rows[{{ $emp->id }}] !== '{{ $st }}' }">{{ $label }}</button>
                            @endforeach
                            {{-- Leave a row blank and it saves nothing at all, so the
                                 day stays open to be marked later. --}}
                            <button type="button" x-on:click="pick({{ $emp->id }}, '')" title="Leave unmarked"
                                class="px-2 py-1.5 border-l border-gray-200"
                                :class="{ 'bg-gray-100 text-gray-500': rows[{{ $emp->id }}] === '', 'text-gray-300 hover:text-gray-600 hover:bg-gray-50': rows[{{ $emp->id }}] !== '' }">&times;</button>
                        </div>
                        </div>
                    </div>
                @empty
                    <p class="py-16 text-center text-sm text-gray-400">No employees found.</p>
                @endforelse
            </div>

            {{-- Footer --}}
            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                <button type="button" wire:click="closeMarkPanel" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                <button type="button" wire:click="saveMarkPanel" wire:loading.attr="disabled" wire:target="saveMarkPanel"
                    :disabled="marked === 0 || remarkMissing"
                    class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                    <span wire:loading.remove wire:target="saveMarkPanel">{{ $panelExisting ? 'Update Attendance' : 'Save Attendance' }}</span>
                    <span wire:loading wire:target="saveMarkPanel">Saving...</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════ DELETE EMPLOYEE CONFIRM ══════════
         The page's own modal (the Attendance page's), not WireUI's dialog —
         that one's runtime classes are not in the compiled Tailwind bundle. --}}
    @if ($pendingDeleteEmpId !== null)
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-[9999] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDeleteEmployee"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Delete Employee?</h3>
                        <p class="text-sm text-gray-500">This will delete the employee and all their records.</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button wire:click="cancelDeleteEmployee" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">No</button>
                    <button wire:click="doDeleteEmployee" wire:loading.attr="disabled" wire:target="doDeleteEmployee" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md disabled:opacity-60">Yes, delete</button>
                </div>
            </div>
        </div>
    @endif

</div>
