<div class="min-h-screen bg-gray-50">
    <style>[x-cloak]{display:none !important;}</style>

    {{-- ══════════ STICKY HEADER — title, then the shared sub-tabs / filters ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900">View Fee</h1>
        </div>
        @include('livewire.partials.view-fee-header')
    </div>

    <div class="p-4 sm:p-6 space-y-4">
        @include('livewire.partials.view-fee-panel', [
            'feePrefix' => 'accounts',
            'feeOrg'    => auth()->user()->organization_id,
        ])
    </div>
</div>
