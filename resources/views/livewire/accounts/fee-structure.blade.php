<div class="min-h-screen bg-gray-50">
    <style>[x-cloak]{display:none !important;}</style>

    {{-- ══════════ STICKY HEADER — title + Add button, then tabs / grey filters ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900">Fee Structure</h1>
            @include('livewire.partials.fee-structure-actions')
        </div>
        @include('livewire.partials.fee-structure-header')
    </div>

    <div class="p-4 sm:p-6 space-y-5">
        @include('livewire.partials.fee-structure-panel')
    </div>
</div>
