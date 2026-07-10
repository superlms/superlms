<div class="{{ $embedded ? '' : 'min-h-screen' }} bg-gray-50">

    @unless ($embedded)
    {{-- ══════════ HEADER (admin template) ══════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4 sm:py-5 sticky top-0 z-30">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Fee Structure</h1>
                <p class="text-sm text-gray-500 mt-0.5">Academic fee structure &amp; transport fee overview</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-gray-50 border border-gray-200 text-xs font-medium text-gray-600">
                    Academic <strong class="text-gray-900">{{ $academicCount }}</strong>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100 text-xs font-medium text-blue-600">
                    Total ₹<strong>{{ number_format($totalAcademicAmt, 0) }}</strong>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-100 text-xs font-medium text-emerald-600">
                    Routes <strong>{{ $routeCount }}</strong>
                </span>
                @if ($activeTab === 'academic')
                    <button wire:click="openStructureModal()"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm ml-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                        Add Fee Structure
                    </button>
                @endif
            </div>
        </div>
    </div>
    @endunless

    {{-- ══════════ TABS ══════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6">
        <nav class="flex gap-1 overflow-x-auto">
            @foreach ([
                'academic'         => 'Academic Fees',
                'transport_routes' => 'Transport Routes',
                'transport_fees'   => 'Student Transport Fees',
            ] as $tab => $label)
                <button wire:click="setTab('{{ $tab }}')"
                    class="py-3 px-4 text-sm font-medium whitespace-nowrap border-b-2 transition-colors
                        {{ $activeTab === $tab ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    <div class="p-4 sm:p-6 space-y-5">

        {{-- ══════════ ACADEMIC TAB ══════════ --}}
        @if ($activeTab === 'academic')
            {{-- Exam-style filter bar: filters (left) + analytics/add/print (right) --}}
            <div class="bg-gray-50 border border-gray-200 rounded-lg px-3 sm:px-4 py-3 flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    Filter by:
                </div>
                <select wire:model.live="filterStructureStandard" class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Classes</option>
                    @foreach ($standards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
                </select>
                <select wire:model.live="filterStructureSection" @disabled(!$filterStructureStandard) class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50">
                    <option value="">All Sections</option>
                    @foreach ($sections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                </select>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search fee name…"
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-44 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @if ($filterStructureStandard || $filterStructureSection || $search)
                    <button wire:click="clearStructureFilters" class="text-xs text-blue-600 hover:text-blue-800 font-medium">Clear</button>
                @endif

                <div class="ml-auto flex items-center gap-2">
                    <span class="hidden md:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-white border border-gray-200 text-xs font-medium text-gray-600">
                        Total: <strong class="text-blue-700">₹{{ number_format($totalAcademicAmt, 0) }}</strong>
                    </span>
                    @if ($embedded)
                        <button wire:click="openAnalytics"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                            Analytics
                        </button>
                    @endif
                    <a href="{{ route('admin.fee-structure.pdf', ['organization' => auth()->user()->organization_id, 'standard' => $filterStructureStandard, 'section' => $filterStructureSection]) }}" target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print
                    </a>
                    <a href="{{ route('admin.fee-structure.pdf', ['organization' => auth()->user()->organization_id, 'standard' => $filterStructureStandard, 'section' => $filterStructureSection, 'download' => 1]) }}" target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Download
                    </a>
                    <button wire:click="openStructureModal()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                        Add Fee Structure
                    </button>
                </div>
            </div>

            {{-- 3-column grid: one card per class + section --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse ($structureGroups as $g)
                    <div wire:key="grp-{{ $g['standard_id'] }}-{{ $g['section_id'] ?? 0 }}" class="bg-white rounded-xl border border-gray-200 overflow-hidden flex flex-col">
                        {{-- Card header: class + section --}}
                        <div class="px-4 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <h3 class="text-sm font-bold text-white truncate">{{ $g['class'] }} <span class="font-normal text-white/80">· {{ $g['section'] }}</span></h3>
                                <p class="text-[11px] text-white/70">{{ $g['year'] }} · {{ count($g['rows']) }} fee head(s)</p>
                            </div>
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <button wire:click="viewGroup({{ $g['standard_id'] }}, {{ $g['section_id'] ?? 'null' }})" title="View" class="p-1.5 rounded-md bg-white/15 text-white hover:bg-white/25">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                                <button wire:click="editGroup({{ $g['standard_id'] }}, {{ $g['section_id'] ?? 'null' }})" title="Edit" class="p-1.5 rounded-md bg-white/15 text-white hover:bg-white/25">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button wire:click="deleteGroup({{ $g['standard_id'] }}, {{ $g['section_id'] ?? 'null' }})" title="Delete" class="p-1.5 rounded-md bg-white/15 text-white hover:bg-red-500">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                        {{-- Fee heads: name + amount --}}
                        <div class="flex-1 divide-y divide-gray-100">
                            @foreach ($g['rows'] as $r)
                                <div class="flex items-center justify-between px-4 py-2.5 text-sm">
                                    <span class="text-gray-700 truncate pr-3">{{ $r->fee_name }}</span>
                                    <span class="font-semibold text-gray-800 flex-shrink-0">₹{{ number_format($r->amount, 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                        {{-- Total --}}
                        <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-t-2 border-gray-200">
                            <span class="text-xs font-bold uppercase tracking-wide text-gray-600">Total</span>
                            <span class="text-base font-bold text-blue-700">₹{{ number_format($g['total'], 2) }}</span>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full bg-white rounded-xl border border-gray-200 px-4 py-12 text-center text-gray-400">No academic fee structures found.</div>
                @endforelse
            </div>

            @if ($structureGroups->isNotEmpty())
                <div class="flex items-center justify-end gap-3 px-5 py-3 bg-gray-900 text-white rounded-xl">
                    <span class="text-xs uppercase tracking-wide text-white/70">Grand Total</span>
                    <span class="text-lg font-bold">₹{{ number_format($structureGrandTotal, 2) }}</span>
                </div>
            @endif
        @endif

        {{-- ══════════ TRANSPORT ROUTES TAB ══════════ --}}
        @if ($activeTab === 'transport_routes')
            {{-- Exam-style filter bar (matches Fee Submission) --}}
            <div class="bg-gray-50 border border-gray-200 rounded-lg px-3 sm:px-4 py-3 flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    Filter by:
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search route…"
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-56 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <span class="ml-auto text-xs text-gray-500">Annual fee = monthly × 11 (June free)</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse ($routes as $r)
                    <div wire:key="route-{{ $r->id }}" class="bg-white rounded-xl border border-gray-200 p-5">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <h3 class="text-base font-semibold text-gray-900 truncate">{{ $r->route_name }}</h3>
                                <p class="text-xs text-gray-500 mt-0.5">{{ $r->driver->user->name ?? 'No driver' }}{{ $r->pickup_time ? ' · ' . $r->pickup_time : '' }}</p>
                            </div>
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $r->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">{{ $r->is_active ? 'Active' : 'Inactive' }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 mt-4 text-center">
                            <div class="bg-blue-50 rounded-lg py-2.5">
                                <p class="text-base font-bold text-blue-700">₹{{ number_format($r->monthly_fee, 0) }}</p>
                                <p class="text-[10px] uppercase tracking-wide text-blue-400">Monthly</p>
                            </div>
                            <div class="bg-emerald-50 rounded-lg py-2.5">
                                <p class="text-base font-bold text-emerald-700">₹{{ number_format($this->annualFee($r->monthly_fee), 0) }}</p>
                                <p class="text-[10px] uppercase tracking-wide text-emerald-400">Annual × 11</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-16 bg-white rounded-xl border border-gray-200">
                        <p class="text-base font-semibold text-gray-800">No transport routes</p>
                        <p class="text-sm text-gray-400 mt-1">Routes are managed in the Transport section.</p>
                    </div>
                @endforelse
            </div>
        @endif

        {{-- ══════════ STUDENT TRANSPORT FEES TAB ══════════ --}}
        @if ($activeTab === 'transport_fees')
            @include('livewire.partials.transport-fee-summary')
        @endif
    </div>

    {{-- ══════════ ACADEMIC FEE STRUCTURE SLIDE-IN PANEL ══════════ --}}
    @if ($structureModalOpen)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeStructureModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editStructureId || $editingGroup ? 'Edit Fee Structure' : 'Add Fee Structure' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Academic fees only. Transport fees are derived from routes.</p>
                    </div>
                    <button wire:click="closeStructureModal"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Class <span class="text-red-500">*</span></label>
                            <select wire:model.live="structureStandardId" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm bg-white">
                                <option value="">Select class…</option>
                                @foreach ($standards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
                            </select>
                            @error('structureStandardId')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Academic Year <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="academicYear" placeholder="2026-27" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm">
                            @error('academicYear')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    @if (!$editStructureId && !$editingGroup)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Sections <span class="text-gray-400 font-normal">(leave empty for all)</span></label>
                            <div class="border border-gray-200 rounded-md p-3 max-h-32 overflow-y-auto space-y-1.5">
                                @forelse ($formSections as $sec)
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" value="{{ $sec->id }}" wire:model="structureSectionIds" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        {{ $sec->name }}
                                    </label>
                                @empty
                                    <p class="text-xs text-gray-400">Select a class to load sections (or leave empty to apply to all).</p>
                                @endforelse
                            </div>
                        </div>
                    @endif

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">Fee Components <span class="text-red-500">*</span></label>
                            <button wire:click="addFeeRow" class="text-xs font-medium text-blue-600 hover:text-blue-800">+ Add row</button>
                        </div>
                        <div class="space-y-2">
                            @foreach ($feeRows as $i => $row)
                                <div wire:key="row-{{ $i }}" class="flex items-center gap-2">
                                    <input type="text" wire:model="feeRows.{{ $i }}.name" placeholder="Fee name (e.g. Tuition)"
                                        class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm">
                                    <input type="number" min="0" step="0.01" wire:model="feeRows.{{ $i }}.amount" placeholder="Amount"
                                        class="w-32 border border-gray-300 rounded-md px-3 py-2 text-sm">
                                    @if (!$editStructureId && count($feeRows) > 1)
                                        <button wire:click="removeFeeRow({{ $i }})" class="p-2 text-red-500 hover:bg-red-50 rounded-md">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                    @endif
                                </div>
                                @error("feeRows.{$i}.name")<p class="text-xs text-red-500">{{ $message }}</p>@enderror
                                @error("feeRows.{$i}.amount")<p class="text-xs text-red-500">{{ $message }}</p>@enderror
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeStructureModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveStructure" class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md">{{ $editStructureId || $editingGroup ? 'Update' : 'Add' }}</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ VIEW SLIDE-IN PANEL ══════════ --}}
    @if ($viewModalOpen && !empty($viewStructureData))
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeViewModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div><h2 class="text-lg font-semibold text-gray-900">Fee Structure Details</h2></div>
                    <button wire:click="closeViewModal"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                    <div class="grid grid-cols-2 gap-6">
                        <div><p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Class</p><p class="text-sm text-gray-800">{{ $viewStructureData['class'] }}</p></div>
                        <div><p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Section</p><p class="text-sm text-gray-800">{{ $viewStructureData['section'] }}</p></div>
                        <div><p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Fee Name</p><p class="text-sm text-gray-800">{{ $viewStructureData['fee_name'] }}</p></div>
                        <div><p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Amount</p><p class="text-sm font-semibold text-gray-800">₹{{ number_format($viewStructureData['amount'], 0) }}</p></div>
                        <div><p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Academic Year</p><p class="text-sm text-gray-800">{{ $viewStructureData['academic_year'] }}</p></div>
                    </div>
                </div>
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end flex-shrink-0">
                    <button wire:click="closeViewModal" class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md">Close</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ DELETE CONFIRM ══════════ --}}
    @if ($pendingDeleteStructureId !== null)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDeleteStructure"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Delete fee structure?</h3>
                        <p class="text-sm text-gray-500">This permanently removes the fee component. This action cannot be undone.</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button wire:click="cancelDeleteStructure" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="doDeleteStructure" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">Delete</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ VIEW GROUP SLIDE-IN ══════════ --}}
    @if ($viewGroupOpen && !empty($viewGroupData))
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeViewGroup"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $viewGroupData['class'] }} · {{ $viewGroupData['section'] }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Academic Year {{ $viewGroupData['year'] }}</p>
                    </div>
                    <button wire:click="closeViewGroup" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-6">
                    <table class="w-full text-sm">
                        <thead class="text-gray-400 text-[11px] uppercase tracking-wide border-b border-gray-100">
                            <tr><th class="py-2 text-left w-10">#</th><th class="py-2 text-left">Fee Name</th><th class="py-2 text-right">Amount</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($viewGroupData['rows'] as $i => $r)
                                <tr><td class="py-2.5 text-gray-500">{{ $i + 1 }}</td><td class="py-2.5 text-gray-800">{{ $r['fee_name'] }}</td><td class="py-2.5 text-right font-semibold text-gray-800">₹{{ number_format($r['amount'], 2) }}</td></tr>
                            @endforeach
                            <tr class="border-t-2 border-gray-200"><td colspan="2" class="py-2.5 text-right font-bold text-gray-700 uppercase text-xs">Total</td><td class="py-2.5 text-right font-bold text-blue-700">₹{{ number_format($viewGroupData['total'], 2) }}</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end flex-shrink-0">
                    <button wire:click="closeViewGroup" class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md">Close</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ GROUP DELETE CONFIRM ══════════ --}}
    @if ($pendingDeleteGroup !== null)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDeleteGroup"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Delete this fee structure?</h3>
                        <p class="text-sm text-gray-500">This removes <strong>all</strong> fee components for this class &amp; section. This cannot be undone.</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button wire:click="cancelDeleteGroup" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="doDeleteGroup" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">Delete</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Transport payment panel + delete confirm (shared) --}}
    @include('livewire.partials.transport-payment-panel')
</div>
