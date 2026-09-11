{{-- ══════════════════════════════════════════════════════════════════
     FEE CONCESSION — shared by Accounts\FeeConcessions and the
     "Concession" tab of Admin\Fee. Both hosts provide the same variables
     via App\Livewire\Concerns\HandlesFeeConcessions::feeConcessionViewData(),
     plus $standards. Edit this one file and both pages update together.
══════════════════════════════════════════════════════════════════ --}}

{{-- Filter band + Add button --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-4">
    <div class="px-4 sm:px-6 py-3 bg-gray-50 flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
            Filter by:
        </div>
        <select wire:model.live="filterConcStandardId" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
            <option value="">All classes</option>
            @foreach ($standards as $std)
                <option value="{{ $std->id }}">{{ $std->name }}</option>
            @endforeach
        </select>
        <select wire:model.live="filterConcSectionId" @disabled(!$filterConcStandardId) class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
            <option value="">All sections</option>
            @foreach ($filterConcSections as $sec)
                <option value="{{ $sec->id }}">{{ $sec->name }}</option>
            @endforeach
        </select>
        <select wire:model.live="filterConcStudentId" @disabled(!$filterConcStandardId) class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
            <option value="">All students</option>
            @foreach ($filterConcStudents as $stu)
                <option value="{{ $stu->id }}">{{ $stu->full_name ?? ($stu->user->name ?? 'Unknown') }}</option>
            @endforeach
        </select>
        <input type="date" wire:model.live="filterConcDate" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">

        @if ($filterConcStandardId || $filterConcSectionId || $filterConcStudentId || $filterConcDate)
            <button wire:click="clearConcFilters" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                Clear
            </button>
        @endif

        <button wire:click="openConcessionModal()"
            class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm ml-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Add Concession
        </button>
    </div>
</div>

{{-- Listing table --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class / Section</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Applies To</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Concession</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Reason</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Granted</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Year</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($concessions as $c)
                <tr wire:key="conc-{{ $c->id }}" class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-800">{{ $c->studentDetail->full_name ?? ($c->studentDetail->user->name ?? '—') }}</p>
                        <p class="text-xs text-gray-400">{{ $c->studentDetail->father_name ?? '' }}</p>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $c->standard->name ?? '—' }}{{ $c->section ? ' / ' . $c->section->name : '' }}</td>
                    <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-[11px] bg-gray-100 text-gray-600 capitalize">{{ $c->fee_type }}</span></td>
                    <td class="px-4 py-3 text-right font-semibold text-emerald-600">{{ $c->concession_type === 'percent' ? $c->value . '%' : '₹' . number_format($c->value, 2) }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $c->reason ?: '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $c->created_at?->format('d M Y') ?? '—' }}</td>
                    <td class="px-4 py-3 text-center text-gray-500">{{ $c->academic_year }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-center gap-1.5">
                            <button wire:click="viewConcession({{ $c->id }})" class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600" title="View">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                            <button wire:click="openConcessionModal({{ $c->id }})" class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-amber-50 hover:text-amber-600" title="Edit">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-16 text-center">
                        <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                        </div>
                        <p class="text-sm font-semibold text-gray-800">No concessions {{ ($filterConcStandardId || $filterConcSectionId || $filterConcStudentId || $filterConcDate) ? 'for this filter' : 'yet' }}</p>
                        <p class="text-xs text-gray-400 mt-1">Click "Add Concession" to grant a student a fee discount.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    @if ($concessions->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">{{ $concessions->links() }}</div>
    @endif
</div>

{{-- Add / Edit concession slide-in --}}
@if ($concModalOpen)
    <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeConcessionModal"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-md bg-white shadow-2xl flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">{{ $editConcessionId ? 'Edit Concession' : 'Add Concession' }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Grant a student a fee discount</p>
                </div>
                <button wire:click="closeConcessionModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Class <span class="text-red-500">*</span></label>
                        <select wire:model.live="concFilterStandard" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">Select…</option>
                            @foreach ($standards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Section</label>
                        <select wire:model.live="concFilterSection" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">All</option>
                            @foreach ($concModalSections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Student <span class="text-red-500">*</span></label>
                    <select wire:model="concStudentId" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="">Select student…</option>
                        @foreach ($concStudents as $stu)
                            <option value="{{ $stu->id }}">{{ $stu->full_name ?? ($stu->user->name ?? 'Unknown') }}@if ($stu->father_name) — {{ $stu->father_name }}@endif</option>
                        @endforeach
                    </select>
                    @error('concStudentId')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Type</label>
                        <select wire:model.live="concType" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="amount">Flat Amount (₹)</option>
                            <option value="percent">Percentage (%)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Value <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" min="0" wire:model="concValue" placeholder="{{ $concType === 'percent' ? 'e.g. 25' : 'e.g. 2000' }}" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                        @error('concValue')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Applies To</label>
                        <select wire:model="concFeeType" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="all">All Fees</option>
                            <option value="academic">Academic</option>
                            <option value="transport">Transport</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Academic Year</label>
                        <input type="text" wire:model="concYear" placeholder="2026-27" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Reason</label>
                    <input type="text" wire:model="concReason" placeholder="e.g. Staff ward, Sibling, Merit" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
            </div>
            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                <button wire:click="closeConcessionModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                <button wire:click="saveConcession" class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md">{{ $editConcessionId ? 'Update Concession' : 'Add Concession' }}</button>
            </div>
        </div>
    </div>
@endif

{{-- View concession — plain, read-only --}}
@if ($viewingConcession)
    <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeConcessionView"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-md bg-white shadow-2xl flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-900 truncate">
                        {{ $viewingConcession->studentDetail->full_name ?? ($viewingConcession->studentDetail->user->name ?? 'Concession') }}
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ $viewingConcession->standard->name ?? '' }}{{ $viewingConcession->section ? ' / ' . $viewingConcession->section->name : '' }} · {{ $viewingConcession->academic_year }}
                    </p>
                </div>
                <button wire:click="closeConcessionView" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                @foreach ([
                    'Father\'s Name' => $viewingConcession->studentDetail->father_name ?? 'N/A',
                    'Type'           => $viewingConcession->concession_type === 'percent' ? 'Percentage' : 'Flat Amount',
                    'Value'          => $viewingConcession->concession_type === 'percent' ? $viewingConcession->value . '%' : '₹' . number_format($viewingConcession->value, 2),
                    'Applies To'     => ucfirst($viewingConcession->fee_type),
                    'Reason'         => $viewingConcession->reason ?: 'N/A',
                    'Granted On'     => $viewingConcession->created_at?->format('d M Y') ?? 'N/A',
                ] as $label => $value)
                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <span class="text-xs text-gray-400 uppercase tracking-wider">{{ $label }}</span>
                        <span class="col-span-2 text-gray-800 font-medium">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                <button wire:click="closeConcessionView" class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
            </div>
        </div>
    </div>
@endif
