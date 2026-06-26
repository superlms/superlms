{{-- Add-payment slide-in + delete-payment confirm. Shared by Admin & Accounts Transport. --}}
@if ($showPaymentPanel)
    <div class="fixed inset-0 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePaymentPanel"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
            <button wire:click="closePaymentPanel"
                class="absolute top-4 right-4 z-20 w-9 h-9 flex items-center justify-center rounded-full bg-white border border-gray-200 hover:bg-red-50 hover:border-red-300 text-gray-500 hover:text-red-500 shadow-md">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
            <div class="flex-1 overflow-y-auto px-6 pt-6 pb-6 space-y-5">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Record Transport Fee Payment</h2>
                    <p class="text-xs text-gray-500 mt-0.5">A receipt number is generated automatically.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Amount (₹) <span class="text-red-500">*</span></label>
                    <input type="number" min="1" step="0.01" wire:model="payAmount"
                        class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                    @error('payAmount')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Mode <span class="text-red-500">*</span></label>
                        <select wire:model="payMode" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm bg-white">
                            <option value="cash">Cash</option>
                            <option value="online">Online</option>
                            <option value="upi">UPI</option>
                            <option value="cheque">Cheque</option>
                        </select>
                        @error('payMode')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Date <span class="text-red-500">*</span></label>
                        <input type="date" wire:model="payDate" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                        @error('payDate')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Remark</label>
                    <textarea wire:model="payRemark" rows="2" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"></textarea>
                    @error('payRemark')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                <button wire:click="closePaymentPanel" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                <button wire:click="savePayment" class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md">Save Payment</button>
            </div>
        </div>
    </div>
@endif

@if ($pendingDeletePaymentId !== null)
    <div class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDeletePayment"></div>
        <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-base font-semibold text-gray-900 mb-1">Delete payment?</h3>
                    <p class="text-sm text-gray-500">This removes the transaction and its receipt. This action cannot be undone.</p>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 mt-5">
                <button wire:click="cancelDeletePayment" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                <button wire:click="executeDeletePayment" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">Delete</button>
            </div>
        </div>
    </div>
@endif
