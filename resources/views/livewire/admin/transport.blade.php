<div class="min-h-screen bg-gray-50">

{{-- ══════════════════════════════════════════════════
     HEADER + TABS + EXAMS-STYLE FILTER BAR
══════════════════════════════════════════════════ --}}
<div class="bg-white border-b border-gray-200 sticky top-0 z-30">
    <div class="px-4 sm:px-6 py-4 sm:py-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Transportation</h1>
            <p class="text-sm text-gray-500 mt-0.5">Manage routes, drivers, students &amp; transport fees</p>
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

    {{-- Filter bar (exams-style thin gray) — tab-aware. Hidden on Fee Summary. --}}
    @if ($activeTab !== 'fees')
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
                    @foreach ($routeOptions as $r)<option value="{{ $r->id }}">{{ $r->route_name }}</option>@endforeach
                </select>
                <select wire:model.live="filterStatus" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[120px]">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            @elseif ($activeTab === 'students')
                <select wire:model.live="filterRoute" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[180px]">
                    <option value="">Select Route *</option>
                    @foreach ($routeOptions as $r)<option value="{{ $r->id }}">{{ $r->route_name }}</option>@endforeach
                </select>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search student name / admission…"
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-64">
            @endif

            @if ($search || $filterDriver || $filterRoute || $filterStatus)
                <button wire:click="$set('search',''); $set('filterDriver',''); $set('filterRoute',''); $set('filterStatus','')"
                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Clear
                </button>
            @endif
        </div>
    </div>
    @endif
</div>

<div class="p-4 sm:p-6">

{{-- ═══════════════════════ ROUTES TAB ═══════════════════════ --}}
@if ($activeTab === 'transportation')
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left">Route</th>
                        <th class="px-4 py-3 text-left">Driver</th>
                        <th class="px-4 py-3 text-left">Vehicle</th>
                        <th class="px-4 py-3 text-left">Pickup</th>
                        <th class="px-4 py-3 text-right w-24">Monthly</th>
                        <th class="px-4 py-3 text-right w-24">Annual×11</th>
                        <th class="px-4 py-3 text-center w-20">Seats</th>
                        <th class="px-4 py-3 text-center w-20">Students</th>
                        <th class="px-4 py-3 text-center w-24">Status</th>
                        <th class="px-4 py-3 text-center w-28">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($transportations as $t)
                        <tr wire:key="route-{{ $t->id }}" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $t->route_name }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    @if ($t->driver?->image)
                                        <img src="{{ $t->driver->image }}" class="w-7 h-7 rounded-full object-cover border border-gray-200">
                                    @else
                                        <div class="w-7 h-7 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 text-xs font-bold">{{ strtoupper(substr($t->driver?->user?->name ?? 'D', 0, 1)) }}</div>
                                    @endif
                                    <span class="text-gray-700">{{ $t->driver?->user?->name ?? '—' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $t->driver?->vehicle_no ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $t->pickup_time ?: '—' }}</td>
                            <td class="px-4 py-3 text-right text-blue-700 font-semibold">₹{{ number_format($t->monthly_fee, 0) }}</td>
                            <td class="px-4 py-3 text-right text-emerald-700 font-semibold">₹{{ number_format($this->annualFee($t->monthly_fee), 0) }}</td>
                            <td class="px-4 py-3 text-center text-gray-600">{{ $t->capacity ?: '—' }}</td>
                            <td class="px-4 py-3 text-center text-gray-600">{{ $t->students->count() }}</td>
                            <td class="px-4 py-3 text-center">
                                <button wire:click="toggleTransportStatus({{ $t->id }})"
                                    class="text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $t->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $t->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <button wire:click="editTransport({{ $t->id }})" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-md" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button wire:click="confirmDeleteRoute({{ $t->id }})" class="p-1.5 text-red-600 hover:bg-red-50 rounded-md" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-4 py-12 text-center text-gray-400">No routes found. <button wire:click="createTransport" class="text-blue-600 hover:underline ml-1">Add the first route →</button></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($transportations->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $transportations->links() }}</div>
        @endif
    </div>
@endif

{{-- ═══════════════════════ DRIVERS TAB ═══════════════════════ --}}
@if ($activeTab === 'drivers')
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left">Driver</th>
                        <th class="px-4 py-3 text-left">Phone</th>
                        <th class="px-4 py-3 text-left">License</th>
                        <th class="px-4 py-3 text-left">Vehicle</th>
                        <th class="px-4 py-3 text-center w-20">Exp</th>
                        <th class="px-4 py-3 text-left">Routes</th>
                        <th class="px-4 py-3 text-center w-24">Status</th>
                        <th class="px-4 py-3 text-center w-28">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($drivers as $d)
                        <tr wire:key="driver-{{ $d->id }}" class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($d->image)
                                        <img src="{{ $d->image }}" class="w-9 h-9 rounded-full object-cover border border-gray-200">
                                    @else
                                        <div class="w-9 h-9 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 font-bold text-sm">{{ strtoupper(substr($d->user->name ?? 'D', 0, 1)) }}</div>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="font-medium text-gray-900 truncate">{{ $d->user->name ?? '—' }}</p>
                                        <p class="text-xs text-gray-400 truncate">{{ $d->user->email ?? '' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $d->phone ?: '—' }}</td>
                            <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ $d->license_no ?: '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $d->vehicle_no ?: '—' }}{{ $d->vehicle_type ? ' · ' . $d->vehicle_type : '' }}</td>
                            <td class="px-4 py-3 text-center text-gray-600">{{ $d->experience_years ?: 0 }}y</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($d->transportations as $r)
                                        <span class="inline-block bg-blue-50 text-blue-700 rounded px-1.5 py-0.5 text-xs">{{ $r->route_name }}</span>
                                    @empty
                                        <span class="text-xs text-gray-400">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button wire:click="toggleDriverStatus({{ $d->id }})"
                                    class="text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $d->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $d->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
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
                        <tr><td colspan="8" class="px-4 py-12 text-center text-gray-400">No drivers found. <button wire:click="createDriver" class="text-blue-600 hover:underline ml-1">Add the first driver →</button></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($drivers->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $drivers->links() }}</div>
        @endif
    </div>
@endif

{{-- ═══════════════════════ TRANSPORT STUDENTS TAB ═══════════════════════ --}}
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
                            <th class="px-4 py-3 text-left">Student</th>
                            <th class="px-4 py-3 text-left">Driver</th>
                            <th class="px-4 py-3 text-right w-28">Monthly Fee</th>
                            <th class="px-4 py-3 text-center w-24">Months</th>
                            <th class="px-4 py-3 text-right w-28">Annual</th>
                            <th class="px-4 py-3 text-right w-24">Paid</th>
                            <th class="px-4 py-3 text-right w-28">Remaining</th>
                            <th class="px-4 py-3 text-center w-32">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($txStudents as $s)
                            <tr wire:key="txs-{{ $s->id }}" class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($s->user?->image)
                                            <img src="{{ $s->user->image }}" class="w-9 h-9 rounded-full object-cover border border-gray-200">
                                        @else
                                            <div class="w-9 h-9 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 text-xs font-bold">{{ strtoupper(substr($s->full_name ?? 'S', 0, 1)) }}</div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="font-medium text-gray-800 truncate">{{ $s->full_name }}</p>
                                            <p class="text-xs text-gray-400 truncate">{{ $s->admission_no }} · {{ $s->standard->name ?? '' }}{{ $s->section ? '-' . $s->section->name : '' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $s->_driverName }}</td>
                                <td class="px-4 py-3 text-right text-blue-700 font-semibold">₹{{ number_format($s->_monthly, 0) }}</td>
                                <td class="px-4 py-3 text-center text-gray-600">{{ $s->_monthsCount }}/12</td>
                                <td class="px-4 py-3 text-right text-gray-800 font-semibold">₹{{ number_format($s->_annual, 0) }}</td>
                                <td class="px-4 py-3 text-right text-emerald-700 font-semibold">₹{{ number_format($s->_paid, 0) }}</td>
                                <td class="px-4 py-3 text-right font-semibold {{ $s->_remaining > 0 ? 'text-red-600' : 'text-gray-400' }}">₹{{ number_format($s->_remaining, 0) }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <button wire:click="selectFeeStudent({{ $s->id }}); $set('activeTab','fees')"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-md" title="View fee summary">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                        <button wire:click="editTransportStudent({{ $s->id }}, {{ $s->_route->id }})"
                                            class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-md" title="Modify billable months &amp; fee">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button wire:click="confirmDeleteTransportStudent({{ $s->id }}, {{ $s->_route->id }}, @js($s->full_name))"
                                            class="p-1.5 text-red-600 hover:bg-red-50 rounded-md" title="Remove from transport">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-12 text-center text-gray-400">No students assigned to this route yet.</td></tr>
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
    @include('livewire.partials.transport-fee-summary')
@endif

</div>

{{-- ══════════ DRIVER SLIDE-IN PANEL ══════════ --}}
@if ($driverModal)
    <div class="fixed inset-0 z-50 overflow-hidden">
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
                        <p class="text-xs text-gray-400 mt-1">JPG/PNG up to 2MB.</p>
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
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Email <span class="text-red-500">*</span></label>
                        <input type="email" wire:model="driver_email" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500">
                        @error('driver_email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone</label>
                        <input type="text" wire:model="driver_phone" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500">
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
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Vehicle Type</label>
                        <select wire:model="driver_vehicle_type" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm bg-white">
                            <option value="">Select…</option>
                            @foreach ($vehicleTypes as $vt)<option value="{{ $vt }}">{{ $vt }}</option>@endforeach
                        </select>
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
                                    <span class="text-sm text-gray-700">{{ $r->route_name }}</span>
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
    <div class="fixed inset-0 z-50 overflow-hidden">
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
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Driver <span class="text-gray-400 font-normal">(optional)</span></label>
                        <select wire:model="driver_detail_id" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm bg-white">
                            <option value="">No driver yet</option>
                            @foreach ($availableDrivers as $d)
                                <option value="{{ $d['id'] }}">{{ $d['name'] }}{{ $d['vehicle_no'] ? ' · ' . $d['vehicle_no'] : '' }}</option>
                            @endforeach
                        </select>
                        @error('driver_detail_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        <p class="text-[11px] text-gray-400 mt-1">You can add routes first and assign a driver later (from the Driver form).</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Pickup Time</label>
                        <input type="time" wire:model="pickup_time" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Monthly Fee (₹)</label>
                        <input type="number" min="0" step="0.01" wire:model="monthly_fee" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Capacity</label>
                        <input type="number" min="0" wire:model="capacity" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500">
                    </div>
                </div>
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

{{-- ══════════ TRANSPORT STUDENT MONTHLY-TOGGLES SLIDE-IN ══════════ --}}
@if ($editTxStudentModal)
    <div class="fixed inset-0 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeEditTransportStudent"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Monthly Fee Schedule</h2>
                    <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $editTxStudentName }}</p>
                </div>
                <button wire:click="closeEditTransportStudent" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                <p class="text-sm text-gray-600">
                    Toggle on each month the student is charged transport fee for. Annual fee = monthly × active months.
                </p>

                <div class="space-y-1.5">
                    @foreach ($monthsOrder as $key => $label)
                        @php $on = $editTxBillableMonths[$key] ?? false; @endphp
                        <button type="button" wire:click="toggleTxMonth('{{ $key }}')"
                            class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-lg border transition-colors
                                {{ $on ? 'bg-emerald-50 border-emerald-200' : 'bg-gray-50 border-gray-200' }}">
                            <span class="text-sm font-medium {{ $on ? 'text-emerald-800' : 'text-gray-600' }}">{{ $label }}</span>
                            <span class="relative inline-flex items-center w-10 h-6 rounded-full transition-colors {{ $on ? 'bg-emerald-500' : 'bg-gray-300' }}">
                                <span class="absolute left-0.5 inline-block w-5 h-5 bg-white rounded-full shadow transform transition-transform {{ $on ? 'translate-x-4' : 'translate-x-0' }}"></span>
                            </span>
                        </button>
                    @endforeach
                </div>

                @php $activeCount = collect($editTxBillableMonths)->filter()->count(); @endphp
                <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 space-y-1.5">
                    <div class="flex items-center justify-between text-xs text-blue-700">
                        <span><strong>{{ $activeCount }}</strong> month(s) active out of 12 · By default June is off (vacation).</span>
                    </div>
                    <div class="flex items-center justify-between border-t border-blue-100 pt-1.5">
                        <span class="text-xs text-blue-600">Monthly ₹{{ number_format($editTxMonthly, 0) }} × {{ $activeCount }}</span>
                        <span class="text-sm font-bold text-blue-800">Annual: ₹{{ number_format($editTxMonthly * $activeCount, 0) }}</span>
                    </div>
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
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4">
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

{{-- Transport fee payment panel + delete confirm (shared) --}}
@include('livewire.partials.transport-payment-panel')
</div>
