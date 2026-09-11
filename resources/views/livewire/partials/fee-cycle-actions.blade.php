{{-- ══════════════════════════════════════════════════════════════════
     FEE CYCLE — the counts + "Add Fee Cycle" button that belong in the
     host's own header row, next to the page/tab title (Accounts\FeeCycles
     and Admin\Fee's 'cycle' tab both include this). Kept apart from
     fee-cycle-header (tabs + calculator filters) so each can sit at the
     right height in the header.
══════════════════════════════════════════════════════════════════ --}}
<div class="flex flex-wrap items-center gap-2">
    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-1 divide-x divide-gray-200">
        <span class="pr-4">Total: <strong class="text-gray-800">{{ $totalCycles }}</strong></span>
        <span class="px-4">Academic: <strong class="text-emerald-600">{{ $academicCycles }}</strong></span>
        <span class="pl-4">Transport: <strong class="text-teal-600">{{ $transportCycles }}</strong></span>
    </div>
    <button wire:click="openCycleModal()"
        class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
        <span class="hidden sm:inline">Add Fee Cycle</span>
        <span class="sm:hidden">Add</span>
    </button>
</div>
