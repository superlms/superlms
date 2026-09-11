{{-- ══════════════════════════════════════════════════════════════════
     PENALTIES — the header stat and the Waiver button, meant to sit in the
     host's own title row (Admin\Fee's Penalties tab or Accounts\Penalties).
     Fed by App\Livewire\Concerns\HandlesPenalties.
══════════════════════════════════════════════════════════════════ --}}

<div class="flex flex-wrap items-center gap-2">
    @if ($penaltySubTab === 'by_student' && $penaltyViewStudentId && !empty($penaltyStudentView))
        @php
            $penHdrCycles  = collect($penaltyStudentView['cycles'] ?? []);
            $penHdrAccrued = $penHdrCycles->sum('penalty_total');
            $penHdrNet     = $penHdrCycles->sum('penalty_net');
        @endphp
        <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-1 divide-x divide-gray-200">
            <span class="pr-4">Accrued: <strong class="text-red-600">₹{{ number_format($penHdrAccrued, 0) }}</strong></span>
            <span class="pl-4">Still Due: <strong class="text-gray-800">₹{{ number_format($penHdrNet, 0) }}</strong></span>
        </div>
    @endif

    <button wire:click="openPenaltyWaiver" @disabled(!$penaltyViewStudentId)
        class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-amber-600 hover:bg-amber-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
        <span>Waiver</span>
    </button>
</div>
