<div class="min-h-screen bg-gray-50">

    {{-- Sticky Header --}}
    <div class="sticky top-0 z-30 bg-white border-b border-gray-200 shadow-sm">
        <div class="px-6 py-4 flex items-center justify-between">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900">Payments</h1>
            <button wire:click="resetFilters"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 text-xs font-medium text-gray-600 bg-white hover:bg-gray-50 transition">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                Reset Filters
            </button>
        </div>
    </div>

    {{-- Page body — the same three partials the admin Fee > Payments tab uses --}}
    <div class="px-6 py-5 space-y-4">
        @include('livewire.partials.payments-filters')
        @include('livewire.partials.payments-analytics')
        @include('livewire.partials.payments-table')
    </div>
</div>
