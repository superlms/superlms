<div class="min-h-screen bg-gray-50">
    <style>[x-cloak]{display:none !important;}</style>

    {{-- ══════════ STICKY HEADER — title, then the shared filter band ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">Fee Submission</h1>

                <div class="flex flex-wrap items-center gap-2">
                    @if ($selectedStudentId)
                        <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-1">
                            <span>Net Payable: <strong class="text-blue-600">₹{{ number_format($netPayable, 0) }}</strong></span>
                        </div>
                    @endif
                    <button wire:click="openSubmitPanel" @disabled(!$selectedStudentId)
                        class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                        <span class="hidden sm:inline">Update Fee</span>
                        <span class="sm:hidden">Update</span>
                    </button>
                </div>
            </div>
        </div>
        @include('livewire.partials.fee-submission-header')
    </div>

    <div class="p-4 sm:p-6 space-y-4">
        @include('livewire.partials.fee-submission-panel', [
            'feePrefix' => 'accounts',
            'feeOrg'    => auth()->user()->organization_id,
        ])
    </div>
</div>
