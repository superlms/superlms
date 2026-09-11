{{-- ══════════════════════════════════════════════════════════════════
     FEE SUBMISSION — the body: the selected student's ledger (same partial
     View Fee renders) plus the slide-in "Collect Fee" panel.

     Fed by App\Livewire\Concerns\HandlesFeeSubmission, plus $feePrefix
     ('admin' | 'accounts') and $feeOrg for the receipt links. The filter band
     lives in fee-submission-header.blade.php, included by each host inside
     its own sticky header.
══════════════════════════════════════════════════════════════════ --}}

@if (!$selectedStudentId || empty($selectedStudentInfo))
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="text-center py-16 px-4">
            <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
            </div>
            <p class="text-sm font-semibold text-gray-800">Select a student</p>
            <p class="text-xs text-gray-400 mt-1">Use the filters above (Class → Section → Student) or search by name to view fee details.</p>
        </div>
    </div>
@endif

@if ($selectedStudentId && !empty($submissionLedger))
    <div class="space-y-4">
        @include('livewire.partials.student-fee-view', [
            'sv'        => $submissionLedger,
            'feePrefix' => $feePrefix,
            'feeOrg'    => $feeOrg,
        ])
    </div>
@endif

{{-- ── Update / Collect Fee slide-in panel ── --}}
@if ($showSubmitPanel)
    <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeSubmitPanel"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-md bg-white shadow-2xl flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Collect Fee</h2>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $selectedStudentInfo['name'] ?? '' }} · Net ₹{{ number_format($netPayable, 0) }}</p>
                </div>
                <button wire:click="closeSubmitPanel" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                @php
                    $capMap     = $feeTypeCaps ?? ['academic' => 0, 'transport' => 0, 'penalty' => 0];
                    $currentCap = $capMap[$submitFeeType] ?? 0;
                @endphp
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Amount (₹) <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" min="1" max="{{ $currentCap > 0 ? $currentCap : 0 }}" wire:model="submitAmount" placeholder="Enter amount" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    @error('submitAmount')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    <p class="mt-1.5 text-[11px] text-gray-400">
                        Up to ₹{{ number_format($currentCap, 2) }} due for {{ $submitFeeType === 'penalty' ? 'penalties' : $submitFeeType }}.
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Fee Type</label>
                        <select wire:model.live="submitFeeType" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            <option value="academic" @disabled($capMap['academic'] <= 0)>Academic@if ($capMap['academic'] <= 0) (fully paid)@endif</option>
                            <option value="transport" @disabled($capMap['transport'] <= 0)>Transport@if ($capMap['transport'] <= 0) (fully paid)@endif</option>
                            <option value="penalty" @disabled($capMap['penalty'] <= 0)>Penalties@if ($capMap['penalty'] <= 0) (none due)@endif</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Payment Mode</label>
                        <select wire:model="submitPaymentMode" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            <option value="cash">Cash</option>
                            <option value="online">Online</option>
                            <option value="cheque">Cheque</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Date <span class="text-red-500">*</span></label>
                    <input type="date" wire:model="submitDate" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    @error('submitDate')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Collected By <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="submittedBy" placeholder="Staff name" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    @error('submittedBy')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Remark</label>
                    <input type="text" wire:model="submitRemark" placeholder="Optional" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                <button wire:click="closeSubmitPanel" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                <button wire:click="submitFeePayment" wire:loading.attr="disabled" wire:target="submitFeePayment"
                    class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                    <span wire:loading.remove wire:target="submitFeePayment">Submit Payment</span>
                    <span wire:loading wire:target="submitFeePayment">Saving…</span>
                </button>
            </div>
        </div>
    </div>
@endif
