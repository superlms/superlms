<div class="min-h-screen bg-gray-50">
    <style>[x-cloak]{display:none !important;}</style>

    {{-- ══════════ STICKY HEADER — title + Waiver, then sub-tabs / grey filters ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900">Penalties</h1>
            @include('livewire.partials.penalties-actions')
        </div>
        @include('livewire.partials.penalties-header')
    </div>

    <div class="p-4 sm:p-6 space-y-5">
        @include('livewire.partials.penalties-panel')
    </div>
</div>
