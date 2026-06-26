<div>
    {{-- ═══════════════════════════════════════════════════════════════════════════
         STICKY HEADER
    ═══════════════════════════════════════════════════════════════════════════ --}}
    <div class="sticky top-0 z-30 bg-white border-b border-gray-200 shadow-sm">
        <div class="px-6 pt-5 pb-4">
            {{-- Title row --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div>
                    <h1 class="text-xl font-bold text-gray-800">Fee Cycles</h1>
                    <p class="text-xs text-gray-500 mt-0.5">Manage fee payment installment cycles for your organisation</p>
                </div>
                <button wire:click="openModal"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl shadow transition whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Fee Cycle
                </button>
            </div>

            {{-- Analytics strip --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-emerald-50 rounded-xl px-4 py-3 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-emerald-600 font-medium">Total Cycles</p>
                        <p class="text-xl font-bold text-emerald-800">{{ $totalCycles }}</p>
                    </div>
                </div>
                <div class="bg-blue-50 rounded-xl px-4 py-3 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-blue-600 font-medium">Academic</p>
                        <p class="text-xl font-bold text-blue-800">{{ $academicCycles }}</p>
                    </div>
                </div>
                <div class="bg-amber-50 rounded-xl px-4 py-3 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-amber-600 font-medium">Transport</p>
                        <p class="text-xl font-bold text-amber-800">{{ $transportCycles }}</p>
                    </div>
                </div>
                <div class="bg-teal-50 rounded-xl px-4 py-3 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-teal-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-teal-600 font-medium">Active Cycles</p>
                        <p class="text-xl font-bold text-teal-800">{{ $activeCycles }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════════
         CONTENT AREA
    ═══════════════════════════════════════════════════════════════════════════ --}}
    <div class="p-6 space-y-5">

        {{-- Filter bar --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="sm:w-48">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Fee Type</label>
                    <select wire:model.live="filterFeeType"
                        class="w-full rounded-xl border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">All Types</option>
                        <option value="academic">Academic</option>
                        <option value="transport">Transport</option>
                    </select>
                </div>
                <div class="sm:w-48">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Academic Year</label>
                    <input type="text" wire:model.live.debounce.300ms="filterYear" placeholder="e.g. 2026-27"
                        class="w-full rounded-xl border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                </div>
            </div>
        </div>

        {{-- Cycles Table --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-emerald-50 border-b border-emerald-100">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-emerald-700">Serial</th>
                            <th class="px-4 py-3 text-left font-semibold text-emerald-700">Fee Type</th>
                            <th class="px-4 py-3 text-left font-semibold text-emerald-700">Due Date</th>
                            <th class="px-4 py-3 text-right font-semibold text-emerald-700">Fee %</th>
                            <th class="px-4 py-3 text-left font-semibold text-emerald-700">Academic Year</th>
                            <th class="px-4 py-3 text-center font-semibold text-emerald-700">Status</th>
                            <th class="px-4 py-3 text-center font-semibold text-emerald-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($cycles as $cycle)
                            <tr class="hover:bg-emerald-50/40 transition">
                                <td class="px-4 py-3 font-bold text-gray-800">
                                    #{{ $cycle->payment_serial }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold
                                        {{ $cycle->fee_type === 'academic' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ ucfirst($cycle->fee_type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    {{ $cycle->due_date?->format('d M Y') ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-800">
                                    {{ number_format($cycle->fee_percent, 2) }}%
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $cycle->academic_year }}</td>
                                <td class="px-4 py-3 text-center">
                                    <button wire:click="toggleActive({{ $cycle->id }})"
                                        class="px-2.5 py-0.5 rounded-full text-xs font-semibold cursor-pointer transition
                                            {{ $cycle->is_active
                                                ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200'
                                                : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                                        {{ $cycle->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button wire:click="editCycle({{ $cycle->id }})"
                                            class="p-1.5 rounded-lg text-emerald-600 hover:bg-emerald-50 transition" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <button wire:click="deleteCycle({{ $cycle->id }})"
                                            class="p-1.5 rounded-lg text-red-500 hover:bg-red-50 transition" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-gray-400">
                                    <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                    </svg>
                                    No fee cycles found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($cycles->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $cycles->links() }}
            </div>
            @endif
        </div>

    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════════
         ADD / EDIT MODAL
    ═══════════════════════════════════════════════════════════════════════════ --}}
    @if($modalOpen)
    <div class="fixed inset-0 z-[999] flex items-center justify-center px-4 py-6"
         style="background: rgba(0,0,0,0.45); backdrop-filter: blur(4px);">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col">
            {{-- Modal header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-800">
                    {{ $editId ? 'Edit Fee Cycle' : 'Add Fee Cycle' }}
                </h2>
                <button wire:click="$set('modalOpen', false)"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Modal body --}}
            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-5">

                {{-- Fee Type --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Fee Type <span class="text-red-500">*</span></label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" wire:model.live="feeType" value="academic"
                                class="text-emerald-600 border-gray-300 focus:ring-emerald-500" />
                            <span class="text-sm text-gray-700">Academic</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" wire:model.live="feeType" value="transport"
                                class="text-emerald-600 border-gray-300 focus:ring-emerald-500" />
                            <span class="text-sm text-gray-700">Transport</span>
                        </label>
                    </div>
                    @error('feeType') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Payment Serial --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Installment Number <span class="text-red-500">*</span>
                    </label>
                    <input type="number" wire:model="paymentSerial" min="1" placeholder="e.g. 1, 2, 3..."
                        class="w-full rounded-xl border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    @error('paymentSerial') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Due Date --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Due Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" wire:model="dueDate"
                        class="w-full rounded-xl border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    @error('dueDate') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Fee Percentage --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Fee Percentage <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" wire:model="feePercent" step="0.01" min="0" max="100"
                            placeholder="% of total fee"
                            class="w-full rounded-xl border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 pr-9" />
                        <span class="absolute inset-y-0 right-3 flex items-center text-gray-400 text-sm pointer-events-none">%</span>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Percentage of total fee due in this installment</p>
                    @error('feePercent') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Academic Year --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Academic Year</label>
                    <input type="text" wire:model="academicYear" placeholder="e.g. 2026-27"
                        class="w-full rounded-xl border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 bg-gray-50" />
                    @error('academicYear') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Active toggle --}}
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl border border-gray-200">
                    <input type="checkbox" wire:model="isActive" id="cycleIsActive"
                        class="w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
                    <label for="cycleIsActive" class="text-sm font-medium text-gray-700 cursor-pointer select-none">
                        Mark as Active
                    </label>
                    <span class="ml-auto text-xs text-gray-400">Inactive cycles won't appear in billing flows</span>
                </div>

            </div>

            {{-- Modal footer --}}
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl">
                <button wire:click="$set('modalOpen', false)"
                    class="px-5 py-2 text-sm font-semibold text-gray-600 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button wire:click="saveCycle" wire:loading.attr="disabled"
                    class="px-5 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow transition disabled:opacity-60">
                    <span wire:loading.remove wire:target="saveCycle">{{ $editId ? 'Update' : 'Save' }}</span>
                    <span wire:loading wire:target="saveCycle">Saving...</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════════
         DELETE CONFIRM MODAL
    ═══════════════════════════════════════════════════════════════════════════ --}}
    @if($pendingDeleteCycleId)
    <div class="fixed inset-0 z-[1000] flex items-center justify-center px-4 py-6"
         style="background: rgba(0,0,0,0.45); backdrop-filter: blur(4px);">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
            <div class="px-6 py-6 text-center">
                <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">Delete Fee Cycle?</h3>
                <p class="text-sm text-gray-500">This action cannot be undone. The fee cycle will be permanently removed.</p>
            </div>
            <div class="flex items-center gap-3 px-6 pb-6">
                <button wire:click="cancelDeleteCycle"
                    class="flex-1 px-4 py-2.5 text-sm font-semibold text-gray-600 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button wire:click="doDeleteCycle" wire:loading.attr="disabled"
                    class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow transition disabled:opacity-60">
                    <span wire:loading.remove wire:target="doDeleteCycle">Yes, Delete</span>
                    <span wire:loading wire:target="doDeleteCycle">Deleting...</span>
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
