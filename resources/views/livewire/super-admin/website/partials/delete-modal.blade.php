{{-- Confirm modal for removing a repeater row. Driven by $pendingDelete. --}}
@if ($pendingDelete !== null)
    <div class="fixed inset-0 flex items-center justify-center bg-black/40 backdrop-blur-sm z-[9999] px-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 bg-red-50 flex items-center gap-3">
                <div class="w-9 h-9 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-gray-900">Delete Item</h3>
            </div>
            <div class="p-5">
                <p class="text-sm text-gray-600">Are you sure you want to delete this item? This action cannot be undone.</p>
            </div>
            <div class="px-5 pb-5 flex items-center gap-2">
                <button wire:click="executeRemoveRow"
                    class="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors">
                    Yes, Delete
                </button>
                <button wire:click="cancelRemoveRow"
                    class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg transition-colors">
                    Cancel
                </button>
            </div>
        </div>
    </div>
@endif
