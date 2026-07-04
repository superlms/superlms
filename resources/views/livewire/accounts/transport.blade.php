<div class="min-h-screen bg-gray-50">

    {{-- ══════════ HEADER (white) + analytics + tabs ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-4 sm:py-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Transportation</h1>
                <p class="text-sm text-gray-500 mt-0.5">Manage drivers &amp; routes</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200">
                    <span class="pr-4">Drivers: <strong class="text-gray-800">{{ $this->statistics['drivers'] }}</strong></span>
                    <span class="px-4">Routes: <strong class="text-blue-600">{{ $this->statistics['routes'] }}</strong></span>
                    <span class="px-4">Students: <strong class="text-emerald-600">{{ $this->statistics['students'] }}</strong></span>
                    <span class="pl-4 hidden sm:inline">Revenue: <strong class="text-amber-600">₹{{ number_format($this->statistics['monthly_revenue'], 0) }}</strong></span>
                </div>
                @if ($activeTab === 'fees' && $feeStudentId)
                    <button wire:click="openPaymentPanel"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        Pay Now
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
    </div>

    <div class="p-4 sm:p-6">

        {{-- ═══════════════════════ ROUTES TAB ═══════════════════════ --}}
        @if ($activeTab === 'transportation')
            <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                <div class="flex flex-wrap items-center gap-2">
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search route…"
                        class="text-sm bg-white border border-gray-200 rounded-md px-3 py-2 text-gray-700 w-56 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <select wire:model.live="filterDriver" class="text-sm bg-white border border-gray-200 rounded-md px-2.5 py-2 text-gray-700">
                        <option value="">All Drivers</option>
                        @foreach ($availableDrivers as $d)
                            <option value="{{ $d['id'] }}">{{ $d['name'] }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="filterStatus" class="text-sm bg-white border border-gray-200 rounded-md px-2.5 py-2 text-gray-700">
                        <option value="">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <button wire:click="createTransport"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    Add Route
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse ($transportations as $t)
                    <div wire:key="route-{{ $t->id }}" class="bg-white rounded-xl border border-gray-200 hover:border-blue-200 hover:shadow-md transition-all p-5">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <h3 class="text-base font-semibold text-gray-900 truncate">{{ $t->route_name }}</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Pickup: {{ $t->pickup_time ?: '—' }}</p>
                            </div>
                            <button wire:click="toggleTransportStatus({{ $t->id }})"
                                class="text-[11px] font-semibold px-2 py-0.5 rounded-full flex-shrink-0 {{ $t->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $t->is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </div>

                        <div class="flex items-center gap-3 mt-4 pt-3 border-t border-gray-100">
                            @if ($t->driver?->image)
                                <img src="{{ $t->driver->image }}" class="w-10 h-10 rounded-full object-cover border border-gray-200 flex-shrink-0">
                            @else
                                <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 text-sm font-bold flex-shrink-0">{{ strtoupper(substr($t->driver->user->name ?? 'D', 0, 1)) }}</div>
                            @endif
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ $t->driver->user->name ?? '—' }}</p>
                                <p class="text-xs text-gray-400 truncate">{{ $t->driver->vehicle_no ?: 'No vehicle' }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2 mt-4 text-center">
                            <div class="bg-gray-50 rounded-lg py-2">
                                <p class="text-sm font-bold text-gray-700">{{ $t->capacity ?: '—' }}</p>
                                <p class="text-[10px] uppercase tracking-wide text-gray-400">Seats</p>
                            </div>
                            <div class="bg-blue-50 rounded-lg py-2">
                                <p class="text-sm font-bold text-blue-700">₹{{ number_format($t->monthly_fee, 0) }}</p>
                                <p class="text-[10px] uppercase tracking-wide text-blue-400">Monthly</p>
                            </div>
                            <div class="bg-emerald-50 rounded-lg py-2">
                                <p class="text-sm font-bold text-emerald-700">₹{{ number_format($this->annualFee($t->monthly_fee), 0) }}</p>
                                <p class="text-[10px] uppercase tracking-wide text-emerald-400">Annual×11</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5 mt-4 pt-3 border-t border-gray-100">
                            <button wire:click="editTransport({{ $t->id }})"
                                class="flex-1 text-xs font-medium px-3 py-1.5 rounded-md border border-gray-200 text-gray-600 hover:bg-amber-50 hover:text-amber-600">Edit</button>
                            <button wire:click="confirmDeleteRoute({{ $t->id }})"
                                class="px-2 py-1.5 rounded-md border border-red-200 text-red-500 hover:bg-red-50" title="Delete">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-16 bg-white rounded-xl border border-gray-200">
                        <p class="text-base font-semibold text-gray-800">No routes found</p>
                        <p class="text-sm text-gray-400 mt-1">Add a route to get started.</p>
                    </div>
                @endforelse
            </div>
            @if ($transportations->hasPages())
                <div class="mt-6">{{ $transportations->links() }}</div>
            @endif
        @endif

        {{-- ═══════════════════════ TRANSPORT STUDENTS TAB ═══════════════════════ --}}
        @if ($activeTab === 'students')
            @php $txStudents = $this->transportStudents(); @endphp
            <div class="flex flex-wrap items-center gap-2 mb-5">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search student name / admission…"
                    class="text-sm bg-white border border-gray-200 rounded-md px-3 py-2 text-gray-700 w-64 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <select wire:model.live="filterRoute" class="text-sm bg-white border border-gray-200 rounded-md px-2.5 py-2 text-gray-700">
                    <option value="">All Routes</option>
                    @foreach ($routeOptions as $r)<option value="{{ $r->id }}">{{ $r->route_name }}</option>@endforeach
                </select>
                <span class="ml-auto text-sm text-gray-500">{{ $txStudents->total() }} student(s) · Annual = monthly × billable months</span>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Student</th>
                            <th class="px-4 py-3 text-left">Route</th>
                            <th class="px-4 py-3 text-right w-28">Annual Fee</th>
                            <th class="px-4 py-3 text-right w-24">Paid</th>
                            <th class="px-4 py-3 text-right w-28">Remaining</th>
                            <th class="px-4 py-3 text-center w-44">Action</th>
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
                                <td class="px-4 py-3 text-gray-600">{{ $s->_route->route_name ?? '—' }}</td>
                                <td class="px-4 py-3 text-right text-gray-700">
                                    ₹{{ number_format($s->_annual, 0) }}
                                    <span class="block text-[11px] text-gray-400">{{ $s->_monthsCount }}/12 mo</span>
                                </td>
                                <td class="px-4 py-3 text-right text-emerald-600">₹{{ number_format($s->_paid, 0) }}</td>
                                <td class="px-4 py-3 text-right font-semibold {{ $s->_remaining > 0 ? 'text-red-600' : 'text-gray-400' }}">₹{{ number_format($s->_remaining, 0) }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1.5">
                                        @if ($s->_route)
                                            <button wire:click="editTransportStudent({{ $s->id }}, {{ $s->_route->id }})"
                                                class="text-xs font-medium px-3 py-1.5 rounded-md border border-gray-200 text-gray-600 hover:bg-gray-50">Months</button>
                                        @endif
                                        <button wire:click="$set('activeTab','fees'); selectFeeStudent({{ $s->id }})"
                                            class="text-xs font-medium px-3 py-1.5 rounded-md border border-blue-200 text-blue-600 hover:bg-blue-50">Fees</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">No students using transport.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if ($txStudents->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">{{ $txStudents->links() }}</div>
                @endif
            </div>
        @endif

        {{-- ═══════════════════════ FEE SUMMARY TAB ═══════════════════════ --}}
        @if ($activeTab === 'fees')
            @include('livewire.partials.transport-fee-summary', ['feeChromeInHeader' => true])
        @endif

        {{-- ═══════════════════════ DRIVERS TAB ═══════════════════════ --}}
        @if ($activeTab === 'drivers')
            <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                <div class="flex flex-wrap items-center gap-2">
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search driver…"
                        class="text-sm bg-white border border-gray-200 rounded-md px-3 py-2 text-gray-700 w-56 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <select wire:model.live="filterRoute" class="text-sm bg-white border border-gray-200 rounded-md px-2.5 py-2 text-gray-700">
                        <option value="">All Routes</option>
                        @foreach ($routeOptions as $r)
                            <option value="{{ $r->id }}">{{ $r->route_name }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="filterStatus" class="text-sm bg-white border border-gray-200 rounded-md px-2.5 py-2 text-gray-700">
                        <option value="">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <button wire:click="createDriver"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    Add Driver
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse ($drivers as $d)
                    <div wire:key="driver-{{ $d->id }}" class="bg-white rounded-xl border border-gray-200 hover:border-blue-200 hover:shadow-md transition-all p-5">
                        <div class="flex items-start gap-3">
                            @if ($d->image)
                                <img src="{{ $d->image }}" class="w-14 h-14 rounded-full object-cover border-2 border-white shadow flex-shrink-0">
                            @else
                                <div class="w-14 h-14 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 font-bold text-lg flex-shrink-0">{{ strtoupper(substr($d->user->name ?? 'D', 0, 1)) }}</div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <h3 class="text-base font-semibold text-gray-900 truncate">{{ $d->user->name ?? '—' }}</h3>
                                        <p class="text-xs text-gray-500 truncate">{{ $d->user->email ?? '' }}</p>
                                    </div>
                                    <button wire:click="toggleDriverStatus({{ $d->id }})"
                                        class="text-[11px] font-semibold px-2 py-0.5 rounded-full flex-shrink-0 {{ $d->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                                        {{ $d->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 space-y-1.5 text-xs text-gray-500">
                            <div class="flex items-center gap-2">
                                <span class="text-gray-400 w-20">Phone</span><span class="text-gray-700">{{ $d->phone ?: '—' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-400 w-20">License</span><span class="text-gray-700">{{ $d->license_no ?: '—' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-400 w-20">Vehicle</span><span class="text-gray-700">{{ $d->vehicle_no ?: '—' }}{{ $d->vehicle_type ? ' · ' . $d->vehicle_type : '' }}</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="text-gray-400 w-20 flex-shrink-0">Routes</span>
                                <span class="text-gray-700">
                                    @forelse ($d->transportations as $r)
                                        <span class="inline-block bg-blue-50 text-blue-700 rounded px-1.5 py-0.5 mr-1 mb-1">{{ $r->route_name }}</span>
                                    @empty
                                        —
                                    @endforelse
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5 mt-4 pt-3 border-t border-gray-100">
                            <button wire:click="editDriver({{ $d->id }})"
                                class="flex-1 text-xs font-medium px-3 py-1.5 rounded-md border border-gray-200 text-gray-600 hover:bg-amber-50 hover:text-amber-600">Edit</button>
                            <button wire:click="confirmDeleteDriver({{ $d->id }})"
                                class="px-2 py-1.5 rounded-md border border-red-200 text-red-500 hover:bg-red-50" title="Delete">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-16 bg-white rounded-xl border border-gray-200">
                        <p class="text-base font-semibold text-gray-800">No drivers found</p>
                        <p class="text-sm text-gray-400 mt-1">Add a driver to assign routes.</p>
                    </div>
                @endforelse
            </div>
            @if ($drivers->hasPages())
                <div class="mt-6">{{ $drivers->links() }}</div>
            @endif
        @endif
    </div>

    {{-- ══════════ DRIVER SLIDE-IN PANEL ══════════ --}}
    @if ($driverModal)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeDriverModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editDriverId ? 'Edit Driver' : 'Add Driver' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Driver login is created with default password 123456.</p>
                    </div>
                    <button wire:click="closeDriverModal"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">

                    {{-- Photo --}}
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
                                class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-gray-300 rounded-md">
                            <p class="text-xs text-gray-400 mt-1">JPG/PNG up to 2MB.</p>
                            <div wire:loading wire:target="driver_image" class="text-xs text-blue-600 mt-1">Uploading…</div>
                            @error('driver_image')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Name <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="driver_name" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('driver_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Email <span class="text-red-500">*</span></label>
                            <input type="email" wire:model="driver_email" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('driver_email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone</label>
                            <input type="text" wire:model="driver_phone" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">License No.</label>
                            <input type="text" wire:model="license_no" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Vehicle No.</label>
                            <input type="text" wire:model="driver_vehicle_no" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
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
                            <input type="number" min="0" max="50" wire:model="experience_years" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="flex items-end pb-2">
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" wire:model="driver_is_active" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"> Active
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Address</label>
                        <textarea wire:model="driver_address" rows="2" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
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
                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editTransportId ? 'Edit Route' : 'Add Route' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">A driver can be assigned to multiple routes.</p>
                    </div>
                    <button wire:click="closeTransportModal"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Route Name <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="route_name" placeholder="e.g. Route 1 — North Zone"
                            class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @error('route_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Driver <span class="text-red-500">*</span></label>
                            <select wire:model="driver_detail_id" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm bg-white">
                                <option value="">Select driver…</option>
                                @foreach ($availableDrivers as $d)
                                    <option value="{{ $d['id'] }}">{{ $d['name'] }}{{ $d['vehicle_no'] ? ' · ' . $d['vehicle_no'] : '' }}</option>
                                @endforeach
                            </select>
                            @error('driver_detail_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Pickup Time</label>
                            <input type="time" wire:model="pickup_time" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('pickup_time')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Monthly Fee (₹)</label>
                            <input type="number" min="0" step="0.01" wire:model="monthly_fee" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Capacity</label>
                            <input type="number" min="0" wire:model="capacity" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
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

    {{-- ══════════ DELETE CONFIRMS ══════════ --}}
    @php
        $tDeletes = [
            ['flag' => $pendingDeleteDriverId, 'cancel' => 'cancelDeleteDriver', 'exec' => 'executeDeleteDriver', 'title' => 'Delete driver?', 'body' => 'Removes the driver and their login. Assigned routes will have no driver.'],
            ['flag' => $pendingDeleteRouteId,  'cancel' => 'cancelDeleteRoute',  'exec' => 'executeDeleteRoute',  'title' => 'Delete route?',  'body' => 'Removes the route and unassigns all its students.'],
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
                            <p class="text-sm text-gray-500">{{ $d['body'] }}</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2 mt-5">
                        <button wire:click="{{ $d['cancel'] }}" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                        <button wire:click="{{ $d['exec'] }}" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">Delete</button>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    {{-- ══════════ TRANSPORT MONTHS SLIDE-IN PANEL ══════════ --}}
    @if ($editTxStudentModal)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeEditTransportStudent"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-md bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Monthly Schedule</h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $editTxStudentName }} — turn a month off when the student isn't using transport.</p>
                    </div>
                    <button wire:click="closeEditTransportStudent"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-6">
                    @php $onCount = collect($editTxBillableMonths)->filter()->count(); @endphp
                    <p class="text-sm text-gray-500 mb-4">
                        <span class="font-semibold text-gray-700">{{ $onCount }}</span> billable month(s). Months turned
                        <span class="font-medium text-gray-700">Off</span> show as <span class="font-medium text-gray-700">No Transport</span>
                        in the student app and are not charged.
                    </p>
                    <div class="grid grid-cols-2 gap-2.5">
                        @foreach ($monthsOrder as $key => $label)
                            @php $on = $editTxBillableMonths[$key] ?? false; @endphp
                            <button type="button" wire:click="toggleTxMonth('{{ $key }}')"
                                class="flex items-center justify-between px-3.5 py-2.5 rounded-lg border text-sm transition-colors {{ $on ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'border-gray-200 bg-gray-50 text-gray-400' }}">
                                <span class="font-medium">{{ $label }}</span>
                                <span class="text-xs font-semibold">{{ $on ? 'On' : 'Off' }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeEditTransportStudent" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveTransportStudentMonths" class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md">Save Schedule</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Transport fee payment panel + delete confirm (shared) --}}
    @include('livewire.partials.transport-payment-panel')
</div>
