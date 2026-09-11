<div class="min-h-screen bg-gray-50">
    <style>[x-cloak]{display:none !important;}</style>

    {{-- ══════════ STICKY HEADER — title + Add button, then the grey filter band ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900">Fee Concession</h1>
            <button wire:click="openConcessionModal()"
                class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                <span class="hidden sm:inline">Add Concession</span>
                <span class="sm:hidden">Add</span>
            </button>
        </div>
        @include('livewire.partials.fee-concession-header')
    </div>

    <div class="p-4 sm:p-6 space-y-5">
        @include('livewire.partials.fee-concession-panel')
    </div>
</div>
