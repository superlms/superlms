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
                {{-- Chips only on Employees tab --}}
                @if ($activeTab === 'employees')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-gray-50 border border-gray-200 text-xs font-medium text-gray-600">Total <strong class="text-gray-900">{{ $empStats['total'] }}</strong></span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100 text-xs font-medium text-blue-600">Teachers <strong>{{ $empStats['teacher'] }}</strong></span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-purple-50 border border-purple-100 text-xs font-medium text-purple-600">Mgmt <strong>{{ $empStats['management'] }}</strong></span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-100 text-xs font-medium text-emerald-600">Staff <strong>{{ $empStats['employee'] }}</strong></span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-amber-50 border border-amber-100 text-xs font-medium text-amber-600">Drivers <strong>{{ $empStats['driver'] }}</strong></span>
                    <button wire:click="openEmpModal()"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors ml-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Add Employee
                    </button>
                @endif

                {{-- Attendance tab: header button opens the marking screen (view mode only) --}}
                @if ($activeTab === 'attendance' && $attendanceMode === 'view')
                    <button wire:click="startMarking"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                        Mark Attendance
                    </button>
                @endif
            </div>
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
                    <option value="name_asc">Name (A–Z)</option>
                    <option value="name_desc">Name (Z–A)</option>
                    <option value="salary_asc">Salary (Low–High)</option>
                    <option value="salary_desc">Salary (High–Low)</option>
                    <option value="type">Type</option>
                </select>
                @if ($empSearch || $empTypeFilter || $empSort !== 'name_asc')
                    <button wire:click="clearEmpFilters" class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>

    @elseif ($activeTab === 'attendance' && $attendanceMode === 'view')
        <div class="bg-gray-50 border-b border-gray-200 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $filterIcon }}" /></svg>
                    Filter by:
                </div>
                <div class="flex items-center gap-1.5">
                    <label class="text-xs text-gray-500">Date</label>
                    <input type="date" wire:model.live="attendanceDate" max="{{ now()->format('Y-m-d') }}"
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                </div>
                <span class="text-gray-300 text-xs">or</span>
                <select wire:model.live="filterAttendanceType" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">Employee type</option>
                    <option value="teacher">Teacher</option>
                    <option value="management">Management</option>
                    <option value="employee">Employee</option>
                    <option value="driver">Driver</option>
                </select>
                <select wire:model.live="attEmpId" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[170px]">
                    <option value="">Select employee</option>
                    @foreach ($attEmployees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->name }} ({{ ucfirst($emp->type) }})</option>
                    @endforeach
                </select>
                @if ($attEmpId)
                    <div class="flex items-center gap-1.5">
                        <label class="text-xs text-gray-500">Month</label>
                        <input type="month" wire:model.live="attMonth" max="{{ now()->format('Y-m') }}"
                            class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700" />
                    </div>
                    @unless ($attMonth)
                        @php $acadStart = now()->month >= 4 ? now()->year : now()->year - 1; @endphp
                        <select wire:model.live="attYear" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700" title="Academic year (Apr–Mar)">
                            @for ($y = $acadStart; $y >= $acadStart - 5; $y--)
                                <option value="{{ $y }}">{{ $y }}–{{ substr($y + 1, 2) }}</option>
                            @endfor
                        </select>
                    @endunless
                    <select wire:model.live="attStatus" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="">All status</option>
                        <option value="present">Present</option>
                        <option value="absent">Absent</option>
                        <option value="half_day">Half day</option>
                        <option value="leave">Leave</option>
                        <option value="holiday">Holiday</option>
                    </select>
                @endif
                @if ($attendanceDate || $filterAttendanceType || $attEmpId || $attMonth || $attStatus)
                    <button wire:click="clearAttFilters" class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>

    @elseif ($activeTab === 'salary')
        <div class="bg-gray-50 border-b border-gray-200 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $filterIcon }}" /></svg>
                    Filter by:
                </div>
                <input wire:model.live.debounce.300ms="salarySearch" type="text" placeholder="Search employee…"
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-56 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                <div class="flex items-center gap-1.5">
                    <label class="text-xs text-gray-500">Month</label>
                    <input type="month" wire:model.live="salaryMonth" max="{{ now()->format('Y-m') }}" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700" />
                </div>
                <select wire:model.live="filterSalaryType" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">All Types</option>
                    <option value="teacher">Teacher</option>
                    <option value="management">Management</option>
                    <option value="employee">Employee</option>
                    <option value="driver">Driver</option>
                </select>
                @if ($salarySearch || $filterSalaryType)
                    <button wire:click="clearSalaryFilters" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Clear
                    </button>
                @endif
                @unless ($canPaySalaryMonth)
                    <span class="ml-auto inline-flex items-center gap-1 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-md px-2.5 py-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        {{ \Carbon\Carbon::parse($salaryMonth . '-01')->format('M Y') }} payable after month ends
                    </span>
                @endunless
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

            {{-- Employee list (table) --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 w-10">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Employee</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Mobile</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Salary</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($employeesList as $i => $emp)
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
                                                <p class="text-xs text-gray-400 truncate">{{ $emp->designation ?? '—' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-xs px-2 py-0.5 rounded-full font-medium border capitalize {{ $typeChip[$emp->type] ?? 'bg-gray-50 text-gray-600 border-gray-200' }}">{{ $emp->type }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $emp->mobile ?? '—' }}</td>
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

            @if ($attendanceMode === 'mark')
                {{-- ─── MARK SCREEN ─── --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 bg-gradient-to-r from-emerald-50 to-teal-50 flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-gray-700">Mark Attendance — {{ \Carbon\Carbon::parse($attendanceDate)->format('d M Y') }}</h3>
                        <span class="text-xs text-gray-400">Teachers are read-only (synced from Teacher Attendance)</span>
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
                                @forelse ($attEmployees as $i => $emp)
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
                                            <span class="text-xs px-2 py-0.5 rounded-full font-medium border capitalize {{ $typeChip[$emp->type] ?? 'bg-gray-50 text-gray-600 border-gray-200' }}">{{ $emp->type }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($emp->isTeacher())
                                                <div class="flex items-center gap-2">
                                                    @if ($sel)
                                                        <span class="text-xs px-2 py-1 rounded-full font-medium capitalize {{ ['present' => 'bg-green-50 text-green-700 border border-green-100', 'absent' => 'bg-red-50 text-red-700 border border-red-100', 'half_day' => 'bg-amber-50 text-amber-700 border border-amber-100', 'leave' => 'bg-blue-50 text-blue-700 border border-blue-100'][$sel] ?? 'bg-gray-50 text-gray-600' }}">{{ str_replace('_', ' ', $sel) }}</span>
                                                    @else
                                                        <span class="text-xs text-gray-400">Not marked</span>
                                                    @endif
                                                    <span class="text-[10px] text-gray-400 italic">from Teacher Attendance</span>
                                                </div>
                                            @else
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
                                            @endif
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
                {{-- ─── DATE VIEW: everyone's status on the chosen date ─── --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-indigo-50">
                        <h3 class="text-sm font-semibold text-gray-700">Attendance — {{ \Carbon\Carbon::parse($attendanceDate)->format('d M Y') }}</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 w-10">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Employee</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($attEmployees as $i => $emp)
                                    @php $status = $this->getAttendanceStatus($emp->id); @endphp
                                    <tr class="hover:bg-gray-50/50 transition-colors" wire:key="view-{{ $emp->id }}">
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
                                            <span class="text-xs px-2 py-0.5 rounded-full font-medium border capitalize {{ $typeChip[$emp->type] ?? 'bg-gray-50 text-gray-600 border-gray-200' }}">{{ $emp->type }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($status)
                                                <span class="text-xs px-2 py-1 rounded-full font-medium capitalize {{ ['present' => 'bg-green-50 text-green-700 border border-green-100', 'absent' => 'bg-red-50 text-red-700 border border-red-100', 'half_day' => 'bg-amber-50 text-amber-700 border border-amber-100', 'leave' => 'bg-blue-50 text-blue-700 border border-blue-100'][$status] ?? 'bg-gray-50 text-gray-600' }}">{{ str_replace('_', ' ', $status) }}</span>
                                                @if ($emp->isTeacher())<span class="ml-1 text-[10px] text-gray-400 italic">teacher</span>@endif
                                            @else
                                                <span class="text-xs text-gray-400">Not marked</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400">No employees found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            @elseif ($attView === 'employee' && $attEmp)
                {{-- ─── EMPLOYEE VIEW: chosen month, or the whole year day-by-day ─── --}}
                @php
                    $cP = $attCounts['present'] ?? 0; $cA = $attCounts['absent'] ?? 0;
                    $cH = $attCounts['half_day'] ?? 0; $cL = $attCounts['leave'] ?? 0;
                    $cHol = $attCounts['holiday'] ?? 0; $cM = $attCounts['marked'] ?? 0;
                    $pct = $cM > 0 ? round(($cP + 0.5 * $cH) / $cM * 100) : 0;
                @endphp
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 bg-gradient-to-r from-purple-50 to-indigo-50 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700">{{ $attEmp->name }} <span class="font-normal text-gray-400">· {{ $attPeriodLabel }}</span></h3>
                            <p class="text-[11px] text-gray-400 capitalize">{{ $attEmp->type }}{{ $attEmp->designation ? ' · ' . $attEmp->designation : '' }}</p>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100">{{ $pct }}% present</span>
                    </div>
                    <div class="p-4">
                        {{-- Summary --}}
                        <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 text-center mb-4">
                            <div class="rounded-lg bg-emerald-50 border border-emerald-100 py-2"><p class="text-lg font-bold text-emerald-600">{{ $cP }}</p><p class="text-[10px] text-emerald-500 uppercase">Present</p></div>
                            <div class="rounded-lg bg-red-50 border border-red-100 py-2"><p class="text-lg font-bold text-red-600">{{ $cA }}</p><p class="text-[10px] text-red-500 uppercase">Absent</p></div>
                            <div class="rounded-lg bg-amber-50 border border-amber-100 py-2"><p class="text-lg font-bold text-amber-600">{{ $cH }}</p><p class="text-[10px] text-amber-500 uppercase">Half</p></div>
                            <div class="rounded-lg bg-blue-50 border border-blue-100 py-2"><p class="text-lg font-bold text-blue-600">{{ $cL }}</p><p class="text-[10px] text-blue-500 uppercase">Leave</p></div>
                            <div class="rounded-lg bg-gray-50 border border-gray-200 py-2"><p class="text-lg font-bold text-gray-500">{{ $cHol }}</p><p class="text-[10px] text-gray-400 uppercase">Holiday</p></div>
                            <div class="rounded-lg bg-gray-100 border border-gray-200 py-2"><p class="text-lg font-bold text-gray-800">{{ $cM }}</p><p class="text-[10px] text-gray-400 uppercase">Marked</p></div>
                        </div>

                        {{-- Day-by-day, grouped by month --}}
                        @forelse ($attByMonth as $ym => $days)
                            <div class="mb-4">
                                <p class="text-xs font-semibold text-gray-600 mb-1.5">{{ \Carbon\Carbon::parse($ym . '-01')->format('F Y') }}</p>
                                <div class="grid grid-cols-7 sm:grid-cols-10 md:grid-cols-12 gap-1.5">
                                    @foreach ($days as $d)
                                        @php
                                            $cell = ['present' => 'bg-emerald-100 text-emerald-700 border-emerald-200', 'absent' => 'bg-red-100 text-red-700 border-red-200', 'half_day' => 'bg-amber-100 text-amber-700 border-amber-200', 'leave' => 'bg-blue-100 text-blue-700 border-blue-200'][$d['status']] ?? 'bg-gray-50 text-gray-400 border-gray-200';
                                            $mark = ['present' => 'P', 'absent' => 'A', 'half_day' => 'H', 'leave' => 'L'][$d['status']] ?? '·';
                                        @endphp
                                        <div class="rounded-md border {{ $cell }} p-1.5 text-center" title="{{ \Carbon\Carbon::parse($d['date'])->format('D, d M Y') }} · {{ ucfirst(str_replace('_', ' ', $d['status'])) }}">
                                            <div class="text-[10px] opacity-70 leading-none">{{ $d['day'] }}</div>
                                            <div class="text-xs font-bold leading-tight mt-0.5">{{ $mark }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400 text-center py-6">No days match this filter.</p>
                        @endforelse

                        <div class="flex flex-wrap gap-x-3 gap-y-1 text-[11px] text-gray-400 mt-1">
                            <span><span class="inline-block w-2.5 h-2.5 rounded-sm bg-emerald-200 border border-emerald-300 align-middle"></span> Present</span>
                            <span><span class="inline-block w-2.5 h-2.5 rounded-sm bg-red-200 border border-red-300 align-middle"></span> Absent</span>
                            <span><span class="inline-block w-2.5 h-2.5 rounded-sm bg-amber-200 border border-amber-300 align-middle"></span> Half</span>
                            <span><span class="inline-block w-2.5 h-2.5 rounded-sm bg-blue-200 border border-blue-300 align-middle"></span> Leave</span>
                            <span><span class="inline-block w-2.5 h-2.5 rounded-sm bg-gray-100 border border-gray-300 align-middle"></span> Holiday / not marked</span>
                        </div>
                    </div>
                </div>
            @else
                {{-- ─── PROMPT (nothing selected yet) ─── --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-10 sm:p-12 text-center">
                    <div class="w-14 h-14 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </div>
                    <p class="text-sm text-gray-600 font-medium">Use the filters above to view attendance.</p>
                    <div class="text-xs text-gray-400 mt-2 space-y-0.5">
                        <p>• Pick a <strong>Date</strong> to see everyone's status that day.</p>
                        <p>• Pick <strong>Type → Employee → Month</strong> for that employee's chosen month.</p>
                        <p>• Pick <strong>Type → Employee</strong> (no month) for their whole year, then narrow by month or status.</p>
                    </div>
                </div>
            @endif
        @endif

        {{-- ══════════ SALARY TAB ══════════ --}}
        @if ($activeTab === 'salary')

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 bg-gradient-to-r from-emerald-50 to-teal-50 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Salary — {{ \Carbon\Carbon::parse($salaryMonth . '-01')->format('M Y') }} <span class="text-xs font-normal text-gray-400">(calculated from attendance)</span></h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 w-10">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Employee</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Base</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">P / A / H</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Payable</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($salaryEmployees as $i => $emp)
                                @php
                                    $b = $salaryBreakdowns[$emp->id] ?? ['present' => 0, 'absent' => 0, 'halfDay' => 0, 'payable' => $emp->salary];
                                    $payment = $monthSalaryPayments->get($emp->id);
                                    $isPaid = $payment && $payment->status === 'paid';
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition-colors {{ $isPaid ? 'bg-green-50/20' : '' }}" wire:key="sal-{{ $emp->id }}">
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
                                    <td class="px-4 py-3"><span class="text-xs px-2 py-0.5 rounded-full font-medium border capitalize {{ $typeChip[$emp->type] ?? 'bg-gray-50 text-gray-600 border-gray-200' }}">{{ $emp->type }}</span></td>
                                    <td class="px-4 py-3 text-sm text-gray-700">₹{{ number_format($emp->salary, 0) }}</td>
                                    <td class="px-4 py-3 text-center text-xs">
                                        <span class="text-emerald-600 font-semibold">{{ $b['present'] }}</span> /
                                        <span class="text-red-500 font-semibold">{{ $b['absent'] }}</span> /
                                        <span class="text-amber-500 font-semibold">{{ $b['halfDay'] }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-900">₹{{ number_format($b['payable'], 0) }}</td>
                                    <td class="px-4 py-3">
                                        @if ($isPaid)
                                            <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-medium bg-green-50 text-green-700 border border-green-100"><span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Paid</span>
                                        @else
                                            <span class="text-xs text-gray-400">Not paid</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($canPaySalaryMonth)
                                            <button wire:click="openPayModal({{ $emp->id }})"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors {{ $isPaid ? 'bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100' : 'bg-emerald-600 hover:bg-emerald-700 text-white' }}">
                                                {{ $isPaid ? 'Update' : 'Pay' }}
                                            </button>
                                        @else
                                            <button disabled title="Payable after the month ends"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg bg-gray-100 text-gray-400 cursor-not-allowed">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                                Locked
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-4 py-8 text-center text-sm text-gray-400">No employees found</td></tr>
                            @endforelse
                        </tbody>
                        @if ($salaryEmployees->count())
                            <tfoot class="bg-gray-50 border-t border-gray-200">
                                <tr>
                                    <td colspan="5" class="px-4 py-2.5 text-xs font-semibold text-gray-600">Total ({{ $salaryEmployees->count() }} employees)</td>
                                    <td class="px-4 py-2.5 text-sm font-bold text-gray-800">₹{{ number_format($totalPayable, 0) }}</td>
                                    <td colspan="2" class="px-4 py-2.5 text-xs text-gray-500">Paid: <strong class="text-emerald-700">₹{{ number_format($totalPaidAmount, 0) }}</strong></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
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
                                        <p class="text-xs text-gray-400 capitalize">{{ $payment->employee?->type ?? '' }}</p>
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

    {{-- ══════════ ADD/EDIT EMPLOYEE SLIDE-IN PANEL ══════════ --}}
    @if ($showEmpModal)
        <div class="fixed inset-0 z-[9999] overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeEmpModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editEmpId ? 'Edit Employee' : 'Add Employee' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">For type <strong>Teacher</strong>, attendance syncs with the Attendance module.</p>
                    </div>
                    <button wire:click="closeEmpModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto overflow-x-hidden px-6 py-6 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Photo</label>
                        @if ($editEmpId && $empExistingPhoto && !$empPhoto)
                            <div class="flex items-center gap-3 mb-2">
                                <img src="{{ $empExistingPhoto }}" class="w-12 h-12 rounded-full object-cover border border-gray-200">
                                <span class="text-xs text-gray-400">Current photo</span>
                            </div>
                        @endif
                        <input type="file" wire:model="empPhoto" accept="image/*"
                            class="block w-full min-w-0 text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition-colors">
                        <p class="text-[11px] text-gray-400 mt-0.5">JPG/PNG · max 1 MB</p>
                        @error('empPhoto')<p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Name *</label>
                            <input type="text" wire:model.defer="empName" placeholder="Full name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                            @error('empName')<p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                            <input type="email" wire:model.defer="empEmail" placeholder="Email address" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Mobile</label>
                            <input type="text" wire:model.defer="empMobile" placeholder="Mobile number" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Designation</label>
                            <input type="text" wire:model.defer="empDesignation" placeholder="e.g. Manager" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Type *</label>
                            <select wire:model.live="empType" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 bg-white">
                                <option value="teacher">Teacher</option>
                                <option value="management">Management</option>
                                <option value="employee">Employee</option>
                                <option value="driver">Driver</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Salary (₹) *</label>
                            <input type="number" wire:model.defer="empSalary" placeholder="Monthly salary" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                            @error('empSalary')<p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Joining Date</label>
                            <input type="date" wire:model.defer="empJoiningDate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Address</label>
                            <input type="text" wire:model.defer="empAddress" placeholder="Address" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                        </div>
                    </div>

                    @if ($empType === 'teacher')
                        <div class="bg-blue-50 rounded-xl border border-blue-100 p-3">
                            <label class="block text-xs font-medium text-blue-700 mb-1">Link to Teacher Detail <span class="text-blue-400 font-normal">(optional — links attendance to teacher records)</span></label>
                            <select wire:model.defer="empTeacherDetailId" class="w-full border border-blue-200 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-400">
                                <option value="">— Don't link —</option>
                                @foreach (\App\Models\Teacher\TeacherDetail::with('user')->where('organization_id', auth()->user()->organization_id)->get() as $td)
                                    <option value="{{ $td->id }}">{{ $td->user?->name ?? 'Teacher #' . $td->id }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="border border-gray-200 rounded-xl p-4 space-y-3 bg-gray-50">
                        <h4 class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Bank Details</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Bank Name</label>
                                <input type="text" wire:model.defer="empBankName" placeholder="Bank name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Account Holder</label>
                                <input type="text" wire:model.defer="empHolderName" placeholder="Account holder name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Account Number</label>
                                <input type="text" wire:model.defer="empAccountNo" placeholder="Account number" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">IFSC Code</label>
                                <input type="text" wire:model.defer="empIfsc" placeholder="IFSC code" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Branch</label>
                                <input type="text" wire:model.defer="empBranch" placeholder="Branch name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
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
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <h2 class="text-lg font-semibold text-gray-900">Employee Details</h2>
                    <button wire:click="closeEmpDetailModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div class="flex flex-col items-center text-center pb-4 border-b border-gray-100">
                        @if ($selectedEmployee->photo)
                            <img src="{{ $selectedEmployee->photo }}" class="w-20 h-20 rounded-full object-cover border-2 border-gray-200 shadow-sm mb-3">
                        @else
                            <div class="w-20 h-20 rounded-full bg-indigo-100 flex items-center justify-center mb-3 shadow-sm"><span class="text-2xl font-bold text-indigo-600">{{ strtoupper(substr($selectedEmployee->name, 0, 1)) }}</span></div>
                        @endif
                        <h3 class="text-lg font-bold text-gray-900">{{ $selectedEmployee->name }}</h3>
                        <p class="text-sm text-gray-500">{{ $selectedEmployee->designation ?? '—' }}</p>
                        <span class="mt-1 text-xs px-2.5 py-0.5 rounded-full font-medium capitalize border {{ $typeChip[$selectedEmployee->type] ?? 'bg-gray-50 text-gray-600 border-gray-200' }}">{{ $selectedEmployee->type }}</span>
                        @if ($selectedEmployee->isTeacher() && $selectedEmployee->teacher_detail_id)
                            <span class="mt-1 text-xs text-blue-500">Linked: {{ $selectedEmployee->teacherDetail?->user?->name ?? 'Teacher #' . $selectedEmployee->teacher_detail_id }}</span>
                        @elseif ($selectedEmployee->type === 'driver' && $selectedEmployee->driver_detail_id)
                            <span class="mt-1 text-xs text-amber-600">Linked: {{ $selectedEmployee->driverDetail?->user?->name ?? 'Driver #' . $selectedEmployee->driver_detail_id }}</span>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        @foreach (['Mobile' => $selectedEmployee->mobile, 'Email' => $selectedEmployee->email, 'Salary' => '₹' . number_format($selectedEmployee->salary, 0), 'Joining' => $selectedEmployee->joining_date?->format('d M Y')] as $label => $value)
                            <div class="bg-gray-50 rounded-xl p-3"><p class="text-xs text-gray-400 mb-0.5">{{ $label }}</p><p class="text-sm font-semibold text-gray-800">{{ $value ?? '—' }}</p></div>
                        @endforeach
                    </div>

                    @if ($selectedEmployee->address)
                        <div class="bg-gray-50 rounded-xl p-3"><p class="text-xs text-gray-400 mb-0.5">Address</p><p class="text-sm font-semibold text-gray-800">{{ $selectedEmployee->address }}</p></div>
                    @endif

                    @if ($selectedEmployee->bank_name)
                        <div class="bg-blue-50 rounded-xl border border-blue-100 p-4">
                            <p class="text-xs font-semibold text-blue-600 uppercase tracking-wide mb-2">Bank Details</p>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach (['Bank' => $selectedEmployee->bank_name, 'Holder' => $selectedEmployee->bank_holder_name, 'Account' => $selectedEmployee->bank_account_no, 'IFSC' => $selectedEmployee->bank_ifsc, 'Branch' => $selectedEmployee->bank_branch] as $label => $value)
                                    <div><p class="text-xs text-blue-400">{{ $label }}</p><p class="text-xs font-semibold text-gray-800 font-mono">{{ $value ?? '—' }}</p></div>
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

</div>
