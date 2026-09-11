<div class="min-h-screen bg-gray-50">

    {{-- ══════════ STICKY HEADER — title, then tabs / grey filters ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900">Payments</h1>
        </div>
        @include('livewire.partials.payments-header')
    </div>

    {{-- Body — the same listing the admin Fee > Payments tab shows --}}
    <div class="p-4 sm:p-6 space-y-5">
        @include('livewire.partials.payments-table')
    </div>
</div>
