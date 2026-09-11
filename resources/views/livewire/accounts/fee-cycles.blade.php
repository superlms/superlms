<div class="min-h-screen bg-gray-50">
    <style>[x-cloak]{display:none !important;}</style>

    {{-- ══════════ STICKY HEADER — title only; tabs/stats live in the shared panel ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30 px-4 sm:px-6 py-3">
        <h1 class="text-lg sm:text-xl font-bold text-gray-900">Fee Cycle</h1>
    </div>

    <div class="p-4 sm:p-6 space-y-5">
        @include('livewire.partials.fee-cycle-panel')
    </div>
</div>
