{{-- ══════════════════════════════════════════════════════════════════
     FEE STRUCTURE — the body: the academic cards, the transport route's
     month-by-month card, and every slide-in. Shared by Admin\FeeStructure
     and Accounts\FeeStructure through App\Livewire\Concerns\HandlesFeeStructures.
     The tabs + filter band live in fee-structure-header.blade.php, included
     by each host inside its own sticky header.
══════════════════════════════════════════════════════════════════ --}}

@php
    // Print opens the printable HTML; download streams the PDF. Same route.
    $pdfLink = fn (array $params = []) => route($pdfPrefix . '.fee-structure.pdf',
        array_merge(['organization' => $pdfOrg], $params));
    $txLink  = fn (array $params = []) => route($pdfPrefix . '.fee-structure.transport-pdf',
        array_merge(['organization' => $pdfOrg], $params));
@endphp

@if ($structureTab === 'academic')

    @if (!$filterStructureStandard)
        <div class="bg-white rounded-2xl border border-dashed border-gray-200 px-4 py-16 text-center">
            <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-gray-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
            </div>
            <p class="text-sm font-semibold text-gray-800">Select a class to see its fee structure</p>
            <p class="text-xs text-gray-400 mt-1">Pick a class — and a section, if you want — from the filter above.</p>
        </div>
    @else
        <div class="space-y-4">
            @forelse ($structureGroups as $g)
                @php
                    $secParam = $g['section_id'] ?? '';
                    // Split the heads down the middle so each column reads top-to-bottom.
                    $feeCols = array_chunk($g['rows']->all(), (int) ceil(max(1, count($g['rows'])) / 2));
                @endphp
                <div wire:key="grp-{{ $g['standard_id'] }}-{{ $g['section_id'] ?? 0 }}"
                    class="bg-white rounded-xl border border-gray-200 overflow-hidden">

                    {{-- Card header — view / edit / delete / download / print --}}
                    <div class="px-4 py-3 border-b border-gray-100 flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-gray-900 truncate">
                                {{ $g['class'] }} <span class="text-gray-400 font-normal">· {{ $g['section'] }}</span>
                            </h3>
                            <p class="text-[11px] text-gray-400 mt-0.5">{{ $g['year'] }} · {{ count($g['rows']) }} fee head{{ count($g['rows']) === 1 ? '' : 's' }}</p>
                        </div>
                        <div class="flex items-center gap-0.5 flex-shrink-0">
                            <button wire:click="viewGroup({{ $g['standard_id'] }}, {{ $g['section_id'] ?? 'null' }})" title="View"
                                class="p-1.5 rounded-md text-gray-400 hover:text-blue-600 hover:bg-blue-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                            <button wire:click="editGroup({{ $g['standard_id'] }}, {{ $g['section_id'] ?? 'null' }})" title="Edit"
                                class="p-1.5 rounded-md text-gray-400 hover:text-amber-600 hover:bg-amber-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button wire:click="deleteGroup({{ $g['standard_id'] }}, {{ $g['section_id'] ?? 'null' }})" title="Delete"
                                class="p-1.5 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                            <a href="{{ $pdfLink(['standard' => $g['standard_id'], 'section' => $secParam, 'download' => 1]) }}" target="_blank" title="Download"
                                class="p-1.5 rounded-md text-gray-400 hover:text-emerald-600 hover:bg-emerald-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </a>
                            <a href="{{ $pdfLink(['standard' => $g['standard_id'], 'section' => $secParam]) }}" target="_blank" title="Print"
                                class="p-1.5 rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            </a>
                        </div>
                    </div>

                    {{-- Fee heads across two columns, nothing drawn between them --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 px-4 py-3">
                        @foreach ($feeCols as $col => $items)
                            <div wire:key="grp-col-{{ $g['standard_id'] }}-{{ $g['section_id'] ?? 0 }}-{{ $col }}">
                                @foreach ($items as $n => $r)
                                    <div class="flex items-center gap-3 py-1.5 text-sm">
                                        <span class="w-5 text-[11px] text-gray-300 tabular-nums">{{ $col * count($feeCols[0]) + $n + 1 }}</span>
                                        <span class="flex-1 text-gray-600 truncate">{{ $r->fee_name }}</span>
                                        <span class="text-gray-800 tabular-nums">₹{{ number_format($r->amount, 2) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                    <div class="flex items-center justify-between px-4 py-2.5 border-t border-gray-100">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Total</span>
                        <span class="text-sm font-bold text-gray-900 tabular-nums">₹{{ number_format($g['total'], 2) }}</span>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-2xl border border-dashed border-gray-200 px-4 py-14 text-center">
                    <p class="text-sm font-semibold text-gray-800">No fee structure for this selection</p>
                    <p class="text-xs text-gray-400 mt-1">Use <strong class="text-gray-600">Add Fee Structure</strong> to create one.</p>
                </div>
            @endforelse
        </div>
    @endif

@else

    {{-- ══════════ TRANSPORT — one route's monthly structure ══════════ --}}
    @if (!$selectedRoute)
        <div class="bg-white rounded-2xl border border-dashed border-gray-200 px-4 py-16 text-center">
            <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-gray-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1"/></svg>
            </div>
            <p class="text-sm font-semibold text-gray-800">Select a route to see its monthly structure</p>
            <p class="text-xs text-gray-400 mt-1">Pick a route from the filter above.</p>
        </div>
    @else
        @php
            $annual = collect($routeMonthRows)->sum('amount');
            $paidMonths = collect($routeMonthRows)->where('free', false)->count();
            // Apr–Sep on the left, Oct–Mar on the right.
            $monthCols = array_chunk($routeMonthRows, (int) ceil(max(1, count($routeMonthRows)) / 2));
        @endphp
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">

            {{-- Card header — view / edit / delete / print / download --}}
            <div class="px-4 py-3 border-b border-gray-100 flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-gray-900 truncate">{{ $selectedRoute->route_name }}</h3>
                    <p class="text-[11px] text-gray-400 mt-0.5">
                        ₹{{ number_format($selectedRoute->monthly_fee, 2) }} / month · {{ $paidMonths }} billable months
                        @if (!$selectedRoute->is_active) · <span class="text-rose-500">Inactive</span> @endif
                    </p>
                </div>
                <div class="flex items-center gap-0.5 flex-shrink-0">
                    <button wire:click="openRouteView" title="View"
                        class="p-1.5 rounded-md text-gray-400 hover:text-blue-600 hover:bg-blue-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                    <button wire:click="openRouteEdit" title="Edit"
                        class="p-1.5 rounded-md text-gray-400 hover:text-amber-600 hover:bg-amber-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button wire:click="deleteRoute" title="Delete"
                        class="p-1.5 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                    <a href="{{ $txLink(['route' => $selectedRoute->id]) }}" target="_blank" title="Print"
                        class="p-1.5 rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    </a>
                    <a href="{{ $txLink(['route' => $selectedRoute->id, 'download' => 1]) }}" target="_blank" title="Download"
                        class="p-1.5 rounded-md text-gray-400 hover:text-emerald-600 hover:bg-emerald-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </a>
                </div>
            </div>

            {{-- Month-by-month across two columns, nothing drawn between them --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 px-4 py-3">
                @foreach ($monthCols as $col => $items)
                    <div wire:key="month-col-{{ $col }}">
                        @foreach ($items as $n => $m)
                            <div class="flex items-center gap-3 py-1.5 text-sm">
                                <span class="w-5 text-[11px] text-gray-300 tabular-nums">{{ $col * count($monthCols[0]) + $n + 1 }}</span>
                                <span class="flex-1 text-gray-600">
                                    {{ $m['month'] }}
                                    @if ($m['free'])<span class="ml-1.5 text-[10px] uppercase tracking-wide text-gray-400">Free</span>@endif
                                </span>
                                <span class="{{ $m['free'] ? 'text-gray-300' : 'text-gray-800' }} tabular-nums">₹{{ number_format($m['amount'], 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>

            <div class="flex items-center justify-between px-4 py-2.5 border-t border-gray-100">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Annual Total</span>
                <span class="text-sm font-bold text-gray-900 tabular-nums">₹{{ number_format($annual, 2) }}</span>
            </div>
        </div>
    @endif

@endif

{{-- ══════════════════════════════════════════════════════════════════
     ADD / EDIT FEE STRUCTURE — slide-in
══════════════════════════════════════════════════════════════════ --}}
@if ($structureModalOpen)
    <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeStructureModal"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">{{ $editingGroup ? 'Edit Fee Structure' : 'Add Fee Structure' }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Pick a class and sections, then list every fee head in one go.</p>
                </div>
                <button wire:click="closeStructureModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Class <span class="text-red-500">*</span></label>
                        <select wire:model.live="structureStandardId" @disabled($editingGroup)
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm bg-white disabled:bg-gray-50 disabled:text-gray-500">
                            <option value="">Select class…</option>
                            @foreach ($standards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
                        </select>
                        @error('structureStandardId')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Academic Year <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="academicYear" placeholder="2026-27"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                        @error('academicYear')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                @if ($editingGroup)
                    <p class="text-xs text-gray-400">
                        Editing <strong class="text-gray-600">{{ $editGroupSectionId ? 'one section' : 'the All Sections' }}</strong> structure —
                        saving replaces every fee head below.
                    </p>
                @else
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Sections <span class="text-gray-400 font-normal">(leave empty to apply to all)</span></label>
                        <div class="border border-gray-200 rounded-md p-3 max-h-32 overflow-y-auto space-y-1.5">
                            @forelse ($formSections as $sec)
                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" value="{{ $sec->id }}" wire:model="structureSectionIds" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    {{ $sec->name }}
                                </label>
                            @empty
                                <p class="text-xs text-gray-400">Select a class to load its sections (or leave empty for all).</p>
                            @endforelse
                        </div>
                    </div>
                @endif

                {{-- Serial · fee name · amount, any number of rows at once --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700">Fee Heads <span class="text-red-500">*</span></label>
                        <span class="text-xs text-gray-500">{{ count($feeRows) }} row{{ count($feeRows) === 1 ? '' : 's' }} · ₹{{ number_format($this->feeRowsTotal, 2) }}</span>
                    </div>
                    <div class="border border-gray-200 rounded-lg overflow-x-auto">
                        <table class="w-full min-w-[380px]">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">
                                    <th class="px-2 py-2 text-left w-10">#</th>
                                    <th class="px-2 py-2 text-left">Fee Name</th>
                                    <th class="px-2 py-2 text-left w-32">Amount (₹)</th>
                                    <th class="px-2 py-2 w-8"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($feeRows as $i => $row)
                                    <tr wire:key="fee-row-{{ $i }}">
                                        <td class="px-2 py-2 text-xs text-gray-400 tabular-nums">{{ $i + 1 }}</td>
                                        <td class="px-2 py-2">
                                            <input type="text" wire:model="feeRows.{{ $i }}.name" placeholder="e.g. Tuition"
                                                class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-1 focus:ring-blue-500">
                                            @error("feeRows.{$i}.name")<p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>@enderror
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="number" min="0" step="0.01" wire:model="feeRows.{{ $i }}.amount" placeholder="0.00"
                                                class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-1 focus:ring-blue-500">
                                            @error("feeRows.{$i}.amount")<p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>@enderror
                                        </td>
                                        <td class="px-2 py-2 text-center">
                                            @if (count($feeRows) > 1)
                                                <button type="button" wire:click="removeFeeRow({{ $i }})" class="p-1 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded" title="Remove row">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button type="button" wire:click="addFeeRow"
                        class="mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                        Add Row
                    </button>
                </div>
            </div>

            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                <button wire:click="closeStructureModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                <button wire:click="saveStructure" wire:loading.attr="disabled"
                    class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60">
                    <span wire:loading.remove wire:target="saveStructure">{{ $editingGroup ? 'Update Structure' : 'Add Structure' }}</span>
                    <span wire:loading wire:target="saveStructure">Saving…</span>
                </button>
            </div>
        </div>
    </div>
@endif

{{-- ══════════ VIEW FEE STRUCTURE — slide-in, edit/delete in its header ══════════ --}}
@if ($viewGroupOpen && !empty($viewGroupData))
    @php $vSec = $viewGroupData['section_id'] ?? ''; @endphp
    <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeViewGroup"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-md bg-white shadow-2xl flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $viewGroupData['class'] }} · {{ $viewGroupData['section'] }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Academic Year {{ $viewGroupData['year'] }}</p>
                </div>
                <div class="flex items-center gap-1 flex-shrink-0">
                    <button wire:click="editViewingGroup" title="Edit" class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-amber-50 hover:text-amber-600 hover:border-amber-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button wire:click="deleteGroup({{ $viewGroupData['standard_id'] }}, {{ $viewGroupData['section_id'] ?? 'null' }})" title="Delete" class="p-1.5 rounded-md border border-red-200 text-red-500 hover:bg-red-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                    <a href="{{ $pdfLink(['standard' => $viewGroupData['standard_id'], 'section' => $vSec, 'download' => 1]) }}" target="_blank" title="Download" class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </a>
                    <a href="{{ $pdfLink(['standard' => $viewGroupData['standard_id'], 'section' => $vSec]) }}" target="_blank" title="Print" class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-gray-100 hover:text-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    </a>
                    <span class="w-px h-5 bg-gray-200 mx-0.5"></span>
                    <button wire:click="closeViewGroup" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100" title="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-6">
                <table class="w-full text-sm">
                    <thead class="text-gray-400 text-[11px] uppercase tracking-wide border-b border-gray-100">
                        <tr><th class="py-2 text-left w-8">#</th><th class="py-2 text-left">Fee Name</th><th class="py-2 text-right">Amount</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($viewGroupData['rows'] as $i => $r)
                            <tr>
                                <td class="py-2.5 text-gray-300 tabular-nums">{{ $i + 1 }}</td>
                                <td class="py-2.5 text-gray-700">{{ $r['fee_name'] }}</td>
                                <td class="py-2.5 text-right text-gray-800 tabular-nums">₹{{ number_format($r['amount'], 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="border-t border-gray-200">
                            <td colspan="2" class="py-2.5 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Total</td>
                            <td class="py-2.5 text-right font-bold text-gray-900 tabular-nums">₹{{ number_format($viewGroupData['total'], 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

{{-- ══════════ ROUTE VIEW — slide-in ══════════ --}}
@if ($routeViewOpen && $selectedRoute)
    <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeRouteView"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-md bg-white shadow-2xl flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $selectedRoute->route_name }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Monthly transport structure</p>
                </div>
                <div class="flex items-center gap-1 flex-shrink-0">
                    <button wire:click="openRouteEdit" title="Edit" class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-amber-50 hover:text-amber-600 hover:border-amber-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button wire:click="deleteRoute" title="Delete" class="p-1.5 rounded-md border border-red-200 text-red-500 hover:bg-red-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                    <span class="w-px h-5 bg-gray-200 mx-0.5"></span>
                    <button wire:click="closeRouteView" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100" title="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                @foreach ([
                    'Driver'       => $selectedRoute->driver->user->name ?? 'No driver',
                    'Pickup Time'  => $selectedRoute->pickup_time ?: 'N/A',
                    'Monthly Fee'  => '₹' . number_format($selectedRoute->monthly_fee, 2),
                    'Free Month'   => $structureFreeMonth,
                    'Annual Total' => '₹' . number_format(collect($routeMonthRows)->sum('amount'), 2),
                    'Status'       => $selectedRoute->is_active ? 'Active' : 'Inactive',
                ] as $label => $value)
                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <span class="text-xs text-gray-400 uppercase tracking-wider">{{ $label }}</span>
                        <span class="col-span-2 text-gray-800 font-medium">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif

{{-- ══════════ ROUTE EDIT — slide-in ══════════ --}}
@if ($routeEditOpen)
    <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeRouteEdit"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-md bg-white shadow-2xl flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Edit Route Fee</h2>
                    <p class="text-xs text-gray-500 mt-0.5">The monthly fee drives the whole year ({{ $structureFreeMonth }} free)</p>
                </div>
                <button wire:click="closeRouteEdit" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Route Name <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="routeName" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                    @error('routeName')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Monthly Fee (₹) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" min="0" wire:model.live.debounce.500ms="routeMonthlyFee" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                        @error('routeMonthlyFee')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Pickup Time</label>
                        <input type="text" wire:model="routePickupTime" placeholder="e.g. 07:15 AM" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" wire:model="routeIsActive" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    Route is active
                </label>
                <div class="bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 text-xs text-gray-600">
                    Annual total becomes
                    <strong class="text-gray-900">₹{{ number_format(((float) $routeMonthlyFee) * (count($structureMonths) - 1), 2) }}</strong>
                    ({{ count($structureMonths) - 1 }} billable months).
                </div>
            </div>
            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                <button wire:click="closeRouteEdit" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                <button wire:click="saveRoute" class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md">Update Route</button>
            </div>
        </div>
    </div>
@endif

{{-- ══════════ DELETE CONFIRMS ══════════ --}}
@if ($pendingDeleteGroup !== null)
    <div class="fixed inset-x-0 bottom-0 top-16 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDeleteGroup"></div>
        <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-base font-semibold text-gray-900 mb-1">Delete this fee structure?</h3>
                    <p class="text-sm text-gray-500">This removes <strong>every</strong> fee head for this class &amp; section. This cannot be undone.</p>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 mt-5">
                <button wire:click="cancelDeleteGroup" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                <button wire:click="doDeleteGroup" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">Delete</button>
            </div>
        </div>
    </div>
@endif

@if ($pendingDeleteRouteId !== null)
    <div class="fixed inset-x-0 bottom-0 top-16 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDeleteRoute"></div>
        <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-base font-semibold text-gray-900 mb-1">Delete this route?</h3>
                    <p class="text-sm text-gray-500">The route and its fee disappear. Routes that still have students assigned cannot be deleted from here.</p>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 mt-5">
                <button wire:click="cancelDeleteRoute" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                <button wire:click="doDeleteRoute" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">Delete</button>
            </div>
        </div>
    </div>
@endif
