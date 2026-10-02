<div class="min-h-screen bg-gray-50">

{{-- ══════════════════════════════════════════════════
     HEADER + TABS + EXAMS-STYLE FILTER BAR
══════════════════════════════════════════════════ --}}
<div class="bg-white border-b border-gray-200 sticky top-0 z-30">
    <div class="px-4 sm:px-6 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-lg sm:text-xl font-bold text-gray-900">Transportation</h1>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200 mr-1">
                <span class="pr-4">Drivers: <strong class="text-gray-800">{{ $this->statistics['drivers'] }}</strong></span>
                <span class="px-4">Routes: <strong class="text-blue-600">{{ $this->statistics['routes'] }}</strong></span>
                <span class="px-4">Students: <strong class="text-emerald-600">{{ $this->statistics['students'] }}</strong></span>
                <span class="pl-4">Revenue: <strong class="text-amber-600">₹{{ number_format($this->statistics['monthly_revenue'], 0) }}</strong></span>
            </div>
            @if ($activeTab === 'transportation')
                <button wire:click="createTransport"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    Add Route
                </button>
            @elseif ($activeTab === 'drivers')
                <button wire:click="createDriver"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    Add Driver
                </button>
            @endif
        </div>
    </div>

    <div class="border-t border-gray-200 px-4 sm:px-6">
        <div class="flex gap-1 overflow-x-auto">
            <button wire:click="$set('activeTab', 'transportation')"
                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap {{ $activeTab === 'transportation' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">Routes</button>
            <button wire:click="$set('activeTab', 'drivers')"
                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap {{ $activeTab === 'drivers' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">Drivers</button>
            <button wire:click="$set('activeTab', 'students')"
                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap {{ $activeTab === 'students' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">Transport Students</button>
            <button wire:click="$set('activeTab', 'fees')"
                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap {{ $activeTab === 'fees' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">Fee Summary</button>
        </div>
    </div>

    {{-- Filter bar (exams-style thin gray) — tab-aware. --}}
    <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filter by:
            </div>

            @if ($activeTab === 'transportation')
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search route name…"
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-56">
                <select wire:model.live="filterDriver" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[140px]">
                    <option value="">All Drivers</option>
                    @foreach ($availableDrivers as $d)<option value="{{ $d['id'] }}">{{ $d['name'] }}</option>@endforeach
                </select>
                <select wire:model.live="filterStatus" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[120px]">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            @elseif ($activeTab === 'drivers')
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search driver name / license / vehicle…"
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-64">
                <select wire:model.live="filterRoute" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[140px]">
                    <option value="">All Routes</option>
                    @foreach ($routeOptions as $r)<option value="{{ $r->id }}">{{ $r->label }}</option>@endforeach
                </select>
                <select wire:model.live="filterStatus" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[120px]">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            @elseif ($activeTab === 'students')
                <select wire:model.live="filterRoute" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[180px]">
                    <option value="">Select Route *</option>
                    @foreach ($routeOptions as $r)<option value="{{ $r->id }}">{{ $r->label }}</option>@endforeach
                </select>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search student name / admission…"
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-64">
            @elseif ($activeTab === 'fees')
                <select wire:model.live="feeFilterRoute" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[180px]">
                    <option value="">Select Route *</option>
                    @foreach ($this->feeRouteOptions() as $r)<option value="{{ $r->id }}">{{ $r->route_name }}@if ($r->vehicle_type) — {{ $r->vehicle_type }}@endif</option>@endforeach
                </select>
                <select wire:model.live="feeStudentId" @disabled(empty($feeFilterRoute))
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[220px] disabled:opacity-50">
                    <option value="">{{ $feeFilterRoute ? 'Select Student *' : 'Pick a route first' }}</option>
                    @foreach ($this->feeRouteStudents() as $st)
                        <option value="{{ $st->id }}">{{ $st->full_name }} · {{ $st->admission_no }}</option>
                    @endforeach
                </select>
            @endif

            @if ($activeTab === 'fees' ? ($feeFilterRoute || $feeStudentId) : ($search || $filterDriver || $filterRoute || $filterStatus))
                <button wire:click="$set('search',''); $set('filterDriver',''); $set('filterRoute',''); $set('filterStatus',''); $set('feeFilterRoute',''); $set('feeStudentId','')"
                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Clear
                </button>
            @endif
        </div>
    </div>
</div>

<div class="p-4 sm:p-6">

{{-- ═══════════════════════ ROUTES TAB ═══════════════════════
     One row per route. The vehicle types sit under the route's name and the
     vehicle numbers under the driver's, both small and plain; the monthly fare
     has its 11-month total under it; whether the route is on is the dot before
     the actions, as the Students list has it — a click on it still turns the
     route on or off. --}}
@if ($activeTab === 'transportation')
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left">Route</th>
                        <th class="px-4 py-3 text-left">Driver</th>
                        <th class="px-4 py-3 text-left">Pickup</th>
                        <th class="px-4 py-3 text-left">Drop</th>
                        <th class="px-4 py-3 text-right w-36">Monthly</th>
                        <th class="px-4 py-3 text-center w-20">Students</th>
                        <th class="px-4 py-3 text-center w-36">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($transportations as $t)
                        <tr wire:key="route-{{ $t->key }}" class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900">{{ $t->route_name }}</p>
                                @if (count($t->vehicle_types))
                                    <p class="text-xs text-gray-400">{{ implode(', ', $t->vehicle_types) }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if (count($t->driver_names))
                                    <div class="flex items-center gap-2">
                                        @if ($t->driver?->image)
                                            <img src="{{ $t->driver->image }}" class="w-7 h-7 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                        @else
                                            <div class="w-7 h-7 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 text-xs font-bold flex-shrink-0">{{ strtoupper(substr($t->driver_names[0], 0, 1)) }}</div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="text-gray-700">{{ implode(', ', $t->driver_names) }}</p>
                                            @if (count($t->vehicle_nos))
                                                <p class="text-xs text-gray-400">{{ implode(', ', $t->vehicle_nos) }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $t->pickup_time ?: '—' }}</td>
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $t->drop_time ?: '—' }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <p class="text-blue-700 font-semibold">₹{{ number_format($t->monthly_fee, 0) }}</p>
                                <p class="text-xs text-gray-400">₹{{ number_format($this->annualFee($t->monthly_fee), 0) }} · {{ $billableMonths }} months</p>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-600">{{ $t->students }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <button wire:click="toggleTransportStatus('{{ $t->key }}')" class="p-1 mr-0.5 rounded-full hover:bg-gray-100" title="{{ $t->is_active ? 'Active' : 'Inactive' }}">
                                        <span class="block w-2 h-2 rounded-full {{ $t->is_active ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                    </button>
                                    <button wire:click="viewRoute('{{ $t->key }}')" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-md" title="View">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                    <button wire:click="editTransport('{{ $t->key }}')" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-md" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button wire:click="confirmDeleteRoute('{{ $t->key }}')" class="p-1.5 text-red-600 hover:bg-red-50 rounded-md" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-12 text-center text-gray-400">No routes found. <button wire:click="createTransport" class="text-blue-600 hover:underline ml-1">Add the first route →</button></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($transportations->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $transportations->links() }}</div>
        @endif
    </div>
@endif

{{-- ═══════════════════════ DRIVERS TAB ═══════════════════════
     Number, photo (a click shows it large), name with the vehicle number small
     under it, mobile, licence, the driver's routes as small plain text; whether
     the driver is on is the dot before the actions, as the Students list has
     it — a click on it still turns the driver on or off. --}}
@if ($activeTab === 'drivers')
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left w-12">S.No</th>
                        <th class="px-4 py-3 text-left">Driver</th>
                        <th class="px-4 py-3 text-left">Mobile</th>
                        <th class="px-4 py-3 text-left">License</th>
                        <th class="px-4 py-3 text-left">Routes</th>
                        <th class="px-4 py-3 text-center w-36">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($drivers as $d)
                        <tr wire:key="driver-{{ $d->id }}" class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-500 font-medium">{{ $drivers->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($d->image)
                                        <img src="{{ $d->image }}" wire:click="showDriverPhoto({{ $d->id }})" title="View photo"
                                            class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0 cursor-zoom-in hover:opacity-90">
                                    @else
                                        <div class="w-9 h-9 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 font-bold text-sm flex-shrink-0">{{ strtoupper(substr($d->user->name ?? 'D', 0, 1)) }}</div>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="font-medium text-gray-900 truncate">{{ $d->user->name ?? '—' }}</p>
                                        @if ($d->vehicle_no)
                                            <p class="text-xs text-gray-400 truncate">{{ $d->vehicle_no }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ $d->phone ?: '—' }}</td>
                            <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ $d->license_no ?: '—' }}</td>
                            <td class="px-4 py-3 text-xs text-gray-500">
                                {{ $d->transportations->map(fn ($r) => $r->route_name . ($r->vehicle_type ? ' · ' . $r->vehicle_type : ''))->implode(', ') ?: '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <button wire:click="toggleDriverStatus({{ $d->id }})" class="p-1 mr-0.5 rounded-full hover:bg-gray-100" title="{{ $d->is_active ? 'Active' : 'Inactive' }}">
                                        <span class="block w-2 h-2 rounded-full {{ $d->is_active ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                    </button>
                                    <button wire:click="viewDriver({{ $d->id }})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-md" title="View">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                    <button wire:click="editDriver({{ $d->id }})" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-md" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button wire:click="confirmDeleteDriver({{ $d->id }})" class="p-1.5 text-red-600 hover:bg-red-50 rounded-md" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">No drivers found. <button wire:click="createDriver" class="text-blue-600 hover:underline ml-1">Add the first driver →</button></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($drivers->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $drivers->links() }}</div>
        @endif
    </div>
@endif

{{-- ═══════════════════════ TRANSPORT STUDENTS TAB ═══════════════════════
     A number first; the year's fee sits small under the monthly fee with the
     months it is counted over ("₹11,000 · 11 months") in place of the Months
     and Annual columns. --}}
@if ($activeTab === 'students')
    @if (empty($filterRoute))
        {{-- Empty state: must pick a route --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center">
            <div class="w-14 h-14 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
            </div>
            <p class="text-sm text-gray-600 font-medium">Pick a route to see its students.</p>
            <p class="text-xs text-gray-400 mt-1">Annual transport fee = monthly × billable months (per student).</p>
        </div>
    @else
        @php $txStudents = $this->transportStudents(); @endphp
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left w-12">S.No</th>
                            <th class="px-4 py-3 text-left">Student</th>
                            <th class="px-4 py-3 text-left">Driver</th>
                            <th class="px-4 py-3 text-right w-40">Monthly Fee</th>
                            <th class="px-4 py-3 text-right w-24">Paid</th>
                            <th class="px-4 py-3 text-right w-28">Remaining</th>
                            <th class="px-4 py-3 text-center w-32">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($txStudents as $s)
                            <tr wire:key="txs-{{ $s->id }}" class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-500 font-medium">{{ $txStudents->firstItem() + $loop->index }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($s->user?->image)
                                            <img src="{{ $s->user->image }}" class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                        @else
                                            <div class="w-9 h-9 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 text-xs font-bold flex-shrink-0">{{ strtoupper(substr($s->full_name ?? 'S', 0, 1)) }}</div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="font-medium text-gray-800 truncate">{{ $s->full_name }}</p>
                                            <p class="text-xs text-gray-400 truncate">{{ $s->admission_no }} · {{ $s->standard->name ?? '' }}{{ $s->section ? '-' . $s->section->name : '' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $s->_driverName }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <p class="text-blue-700 font-semibold">₹{{ number_format($s->_monthly, 0) }}</p>
                                    <p class="text-xs text-gray-400">₹{{ number_format($s->_annual, 0) }} · {{ $s->_monthsCount }} {{ $s->_monthsCount === 1 ? 'month' : 'months' }}</p>
                                </td>
                                <td class="px-4 py-3 text-right text-emerald-700 font-semibold">₹{{ number_format($s->_paid, 0) }}</td>
                                <td class="px-4 py-3 text-right font-semibold {{ $s->_remaining > 0 ? 'text-red-600' : 'text-gray-400' }}">₹{{ number_format($s->_remaining, 0) }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <button wire:click="viewTransportStudentDetail({{ $s->id }}, {{ $s->_route->id }})"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-md" title="View transport detail">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                        <button wire:click="editTransportStudent({{ $s->id }}, {{ $s->_route->id }})"
                                            class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-md" title="Modify billable months &amp; fee">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-12 text-center text-gray-400">No students assigned to this route yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($txStudents->hasPages())
                <div class="px-4 py-3 border-t border-gray-100">{{ $txStudents->links() }}</div>
            @endif
        </div>
    @endif
@endif

{{-- ═══════════════════════ FEE SUMMARY TAB ═══════════════════════ --}}
@if ($activeTab === 'fees')
    @include('livewire.partials.transport-fee-summary', [
        'feeChromeInHeader' => true,
        'feeFilterInHeader' => true,
        'feeReadOnly'       => true,
    ])
@endif

</div>

{{-- ══════════ DRIVER SLIDE-IN PANEL ══════════ --}}
@if ($driverModal)
    <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeDriverModal"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">{{ $editDriverId ? 'Edit Driver' : 'Add Driver' }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Driver login is created with default password 123456.</p>
                </div>
                <button wire:click="closeDriverModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">

                <div class="flex items-center gap-4">
                    @if ($driver_image)
                        <img src="{{ $driver_image->temporaryUrl() }}" class="w-16 h-16 rounded-full object-cover border-2 border-white shadow flex-shrink-0">
                    @elseif ($driver_image_existing)
                        <img src="{{ $driver_image_existing }}" class="w-16 h-16 rounded-full object-cover border-2 border-white shadow flex-shrink-0">
                    @else
                        <div class="w-16 h-16 rounded-full bg-blue-50 flex items-center justify-center border-2 border-white shadow flex-shrink-0">
                            <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </div>
                    @endif
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Driver Photo</label>
                        <input type="file" wire:model="driver_image" accept="image/*"
                            class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-gray-300 rounded-md">
                        <p class="text-xs text-gray-400 mt-1">JPG/PNG up to 1MB.</p>
                        <div wire:loading wire:target="driver_image" class="text-xs text-blue-600 mt-1">Uploading…</div>
                        @error('driver_image')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Name <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="driver_name" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500">
                        @error('driver_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                        <input type="email" wire:model="driver_email" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500">
                        @error('driver_email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="driver_phone" maxlength="10" inputmode="numeric" placeholder="10-digit mobile" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500">
                        @error('driver_phone')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">License No.</label>
                        <input type="text" wire:model="license_no" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Vehicle No.</label>
                        <input type="text" wire:model="driver_vehicle_no" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Experience (yrs)</label>
                        <input type="number" min="0" max="50" wire:model="experience_years" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div class="flex items-end pb-2">
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" wire:model="driver_is_active" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"> Active
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Address</label>
                    <textarea wire:model="driver_address" rows="2" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500"></textarea>
                </div>

                {{-- Assign this driver to one or more routes --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Assign Routes <span class="text-gray-400 font-normal">(select one or more)</span></label>
                    @if (count($routeOptions) === 0)
                        <p class="text-xs text-gray-400 border border-dashed border-gray-200 rounded-md p-3">No routes yet. Create routes first, then assign them to this driver.</p>
                    @else
                        <div class="border border-gray-200 rounded-md divide-y divide-gray-100 max-h-56 overflow-y-auto">
                            @foreach ($routeOptions as $r)
                                <label class="flex items-center gap-2.5 px-3 py-2 hover:bg-gray-50 cursor-pointer">
                                    <input type="checkbox" wire:model="driver_routes" value="{{ $r->id }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">
                                        {{ $r->route_name }}
                                        @if ($r->vehicle_type)
                                            <span class="ml-1 inline-block bg-indigo-50 text-indigo-700 rounded px-1.5 py-0.5 text-[11px] font-medium">{{ $r->vehicle_type }}</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">This driver will be set on the selected routes. Unchecking a route releases it (no driver).</p>
                    @endif
                </div>
            </div>
            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                <button wire:click="closeDriverModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                <button wire:click="saveDriver" wire:loading.attr="disabled" class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60">
                    <span wire:loading.remove wire:target="saveDriver">{{ $editDriverId ? 'Update' : 'Add Driver' }}</span>
                    <span wire:loading wire:target="saveDriver">Saving…</span>
                </button>
            </div>
        </div>
    </div>
@endif

{{-- ══════════ ROUTE SLIDE-IN PANEL ══════════ --}}
@if ($transportModal)
    <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeTransportModal"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">{{ $editTransportId ? 'Edit Route' : 'Add Route' }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">A driver can be assigned to multiple routes.</p>
                </div>
                <button wire:click="closeTransportModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Route Name <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="route_name" placeholder="e.g. Route 1 — North Zone"
                        class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500">
                    @error('route_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        Vehicle Type <span class="text-red-500">*</span>
                    </label>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($vehicleTypes as $vt)
                            <label class="inline-flex items-center gap-2 px-3 py-1.5 border rounded-lg cursor-pointer transition-colors
                                {{ in_array($vt, $route_vehicle_types) ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                                <input type="checkbox" wire:model.live="route_vehicle_types" value="{{ $vt }}"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm font-medium">{{ $vt }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-400 mt-1.5">
                        @if (count($route_vehicle_types) > 1)
                            {{ count($route_vehicle_types) }} routes will be created — one per vehicle type. The list
                            shows them as a single route, and a driver is assigned to each type separately.
                        @else
                            Pick more than one to run this route with several vehicle types.
                        @endif
                    </p>
                    @error('route_vehicle_types')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Pickup Time</label>
                        <input type="time" wire:model="pickup_time" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500">
                        @error('pickup_time')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Drop Time</label>
                        <input type="time" wire:model="drop_time" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500">
                        @error('drop_time')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Monthly Fee (₹)</label>
                        <input type="number" min="0" step="0.01" wire:model="monthly_fee" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500">
                        @error('monthly_fee')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Capacity</label>
                        <input type="number" min="0" wire:model="capacity" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500">
                        @error('capacity')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <p class="text-[11px] text-gray-400">Assign a driver to this route from the Driver form.</p>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" wire:model="transport_is_active" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"> Active route
                </label>
            </div>
            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                <button wire:click="closeTransportModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                <button wire:click="saveTransport" class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md">{{ $editTransportId ? 'Update' : 'Add Route' }}</button>
            </div>
        </div>
    </div>
@endif

{{-- ══════════ ROUTE DETAIL SLIDE-IN PANEL ══════════ --}}
@if ($showRouteView)
    <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeRouteView"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $routeViewTitle }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Route Details</p>
                </div>
                <button wire:click="closeRouteView" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                @foreach ($routeViewDetails as $label => $value)
                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <span class="text-xs text-gray-400 uppercase tracking-wider">{{ $label }}</span>
                        <span class="col-span-2 text-gray-800 font-medium">{{ $value }}</span>
                    </div>
                @endforeach
            </div>

            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                <button wire:click="closeRouteView" class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
            </div>
        </div>
    </div>
@endif

{{-- ══════════ DRIVER DETAIL SLIDE-IN PANEL ══════════ --}}
@if ($showDriverView)
    <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeDriverView"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $driverViewTitle }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Driver Details</p>
                </div>
                <button wire:click="closeDriverView" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                @foreach ($driverViewDetails as $label => $value)
                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <span class="text-xs text-gray-400 uppercase tracking-wider">{{ $label }}</span>
                        <span class="col-span-2 text-gray-800 font-medium">{{ $value }}</span>
                    </div>
                @endforeach
            </div>

            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                <button wire:click="closeDriverView" class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
            </div>
        </div>
    </div>
@endif

{{-- ══════════ TRANSPORT STUDENT MONTHS SLIDE-IN ══════════
     The months the student is charged for, as a plain grid of the twelve: a
     month that is on is dark, one that is off is an outline, and a click
     switches it. The fee they add up to is the line above them. --}}
@if ($editTxStudentModal)
    @php $activeCount = collect($editTxBillableMonths)->filter()->count(); @endphp
    <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeEditTransportStudent"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-900">Monthly Fee Schedule</h2>
                    <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $editTxStudentName }}</p>
                </div>
                <button wire:click="closeEditTransportStudent" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                <div class="grid grid-cols-3 gap-3 text-sm">
                    <span class="text-xs text-gray-400 uppercase tracking-wider">Monthly Fee</span>
                    <span class="col-span-2 text-gray-800 font-medium">₹{{ number_format($editTxMonthly, 0) }}</span>
                </div>
                <div class="grid grid-cols-3 gap-3 text-sm">
                    <span class="text-xs text-gray-400 uppercase tracking-wider">Annual Fee</span>
                    <span class="col-span-2 text-gray-800 font-medium">₹{{ number_format($editTxMonthly * $activeCount, 0) }} · {{ $activeCount }} {{ $activeCount === 1 ? 'month' : 'months' }}</span>
                </div>

                <div class="pt-4 mt-2 border-t border-gray-100">
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-3">Months Charged</p>
                    <div class="grid grid-cols-4 gap-2">
                        @foreach ($monthsOrder as $key => $label)
                            @php $on = $editTxBillableMonths[$key] ?? false; @endphp
                            <button type="button" wire:key="txm-{{ $key }}" wire:click="toggleTxMonth('{{ $key }}')" title="{{ $label }}"
                                class="py-2 text-sm rounded-md border transition-colors
                                    {{ $on ? 'bg-gray-900 border-gray-900 text-white font-medium' : 'bg-white border-gray-200 text-gray-400 hover:bg-gray-50' }}">
                                {{ substr($label, 0, 3) }}
                            </button>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-400 mt-3">Click a month to turn it on or off.</p>
                </div>
            </div>
            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                <button wire:click="closeEditTransportStudent" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                <button wire:click="saveTransportStudentMonths" wire:loading.attr="disabled"
                    class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60 flex items-center gap-1.5">
                    <span wire:loading.remove wire:target="saveTransportStudentMonths">Save</span>
                    <span wire:loading wire:target="saveTransportStudentMonths">Saving…</span>
                </button>
            </div>
        </div>
    </div>
@endif

{{-- ══════════ TRANSPORT STUDENT DETAIL SLIDE-IN ══════════
     Laid out as the Students page's View: the photo, then one plain list of
     label and value — the student, the route, the fee — and under a line the
     twelve months with what each stands at. Edit opens the months. --}}
@if ($viewTxStudentModal && $viewTxStudentData)
    @php
        $v = $viewTxStudentData;
        $vMonths = (int) $v['months_count'];
        $monthLine = fn (array $m) => match ($m['status']) {
            'paid'     => 'Paid · ₹' . number_format($m['amount'], 0),
            'partial'  => 'Partial · ₹' . number_format($m['paid'], 0) . ' of ₹' . number_format($m['amount'], 0),
            'unpaid'   => 'Unpaid · ₹' . number_format($m['amount'], 0),
            'upcoming' => 'Upcoming · ₹' . number_format($m['amount'], 0),
            default    => 'Not used',
        };
    @endphp
    <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeViewTxStudent"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $v['name'] }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5 truncate">Transport Details</p>
                </div>
                <button wire:click="closeViewTxStudent" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                <div class="flex justify-center pb-2">
                    @if ($v['image'])
                        <img src="{{ $v['image'] }}" alt="{{ $v['name'] }}" class="w-24 h-24 rounded-full object-cover border border-gray-200">
                    @else
                        <div class="w-24 h-24 rounded-full bg-indigo-100 flex items-center justify-center">
                            <span class="text-3xl font-semibold text-indigo-600">{{ strtoupper(substr($v['name'], 0, 1)) }}</span>
                        </div>
                    @endif
                </div>

                @foreach ([
                    'Admission No' => $v['admission'] ?: '—',
                    'Class'        => $v['class'] ?: '—',
                    'Email'        => $v['email'],
                    'Mobile'       => $v['mobile'],
                    'Route'        => $v['route'],
                    'Driver'       => $v['driver'],
                    'Pickup Time'  => $v['pickup_time'],
                    'Drop Time'    => $v['drop_time'],
                    'Monthly Fee'  => '₹' . number_format($v['monthly'], 0),
                    'Annual Fee'   => '₹' . number_format($v['annual'], 0) . ' · ' . $vMonths . ($vMonths === 1 ? ' month' : ' months'),
                    'Paid'         => '₹' . number_format($v['paid'], 0),
                    'Remaining'    => '₹' . number_format($v['remaining'], 0),
                ] as $label => $value)
                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <span class="text-xs text-gray-400 uppercase tracking-wider">{{ $label }}</span>
                        <span class="col-span-2 text-gray-800 font-medium">{{ $value }}</span>
                    </div>
                @endforeach

                {{-- The months of the school year, each with what it stands at --}}
                @if (!empty($v['month_status']))
                    <div class="pt-4 mt-2 border-t border-gray-100 space-y-4">
                        @foreach ($v['month_status'] as $m)
                            <div class="grid grid-cols-3 gap-3 text-sm">
                                <span class="text-xs text-gray-400 uppercase tracking-wider">{{ $m['label'] }}</span>
                                <span class="col-span-2 font-medium {{ $m['status'] === 'not_used' ? 'text-gray-400' : 'text-gray-800' }}">{{ $monthLine($m) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-between flex-shrink-0">
                <button type="button" wire:click="editViewedTransportStudent"
                    class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Edit</button>
                <button type="button" wire:click="closeViewTxStudent"
                    class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
            </div>
        </div>
    </div>
@endif

{{-- ══════════ DELETE CONFIRM OVERLAYS ══════════ --}}
@php
    $tDeletes = [
        ['flag' => $pendingDeleteDriverId, 'cancel' => 'cancelDeleteDriver', 'exec' => 'executeDeleteDriver', 'title' => 'Delete driver?', 'body' => 'Removes the driver and their login. Assigned routes will have no driver.'],
        ['flag' => $pendingDeleteRouteId,  'cancel' => 'cancelDeleteRoute',  'exec' => 'executeDeleteRoute',  'title' => 'Delete route?',  'body' => 'Removes the route and unassigns all its students.'],
        ['flag' => $pendingDeleteTxStudentId, 'cancel' => 'cancelDeleteTransportStudent', 'exec' => 'executeDeleteTransportStudent', 'title' => 'Remove from transport?', 'body' => 'Removes <strong>' . e($pendingDeleteTxStudentName) . '</strong> from this route. Their transport fee no longer applies. Past payments are kept as history.'],
    ];
@endphp
@foreach ($tDeletes as $d)
    @if ($d['flag'] !== null)
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-[60] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="{{ $d['cancel'] }}"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">{{ $d['title'] }}</h3>
                        <p class="text-sm text-gray-500">{!! $d['body'] !!}</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button wire:click="{{ $d['cancel'] }}" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="{{ $d['exec'] }}" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">Confirm</button>
                </div>
            </div>
        </div>
    @endif
@endforeach

{{-- A driver's photo in the list, clicked: shown large in the middle of the
     window (lms-cover), its cross in its own corner — as the Teachers list. --}}
@if ($driverPhoto)
    <div class="lms-cover fixed inset-0 z-[9999] flex overflow-y-auto p-4 bg-black/80"
        wire:click.self="closeDriverPhoto" x-on:keydown.escape.window="$wire.closeDriverPhoto()">
        <div class="relative m-auto max-w-full">
            <img src="{{ $driverPhoto }}" alt=""
                style="--photo: min(28rem, 90vw, calc(100vh - 8rem)); width: var(--photo); height: var(--photo)"
                class="rounded-lg object-cover shadow-2xl bg-white">
            <button type="button" wire:click="closeDriverPhoto" title="Close"
                class="absolute top-2 right-2 w-9 h-9 flex items-center justify-center rounded-full bg-black/50 hover:bg-black/70 text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
    </div>
@endif

{{-- Transport fee payment panel + delete confirm (shared) --}}
@include('livewire.partials.transport-payment-panel')
</div>
