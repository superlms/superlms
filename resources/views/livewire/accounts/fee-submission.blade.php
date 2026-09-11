<div>
    {{-- ================================================================== --}}
    {{--  STICKY HEADER                                                      --}}
    {{-- ================================================================== --}}
    <div class="sticky top-0 z-30 bg-white border-b border-gray-200 shadow-sm">
        <div class="px-6 py-4 flex items-center justify-between">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">Fee Submission</h1>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{--  PAGE CONTENT                                                       --}}
    {{-- ================================================================== --}}
    <div class="p-6 space-y-5">

        {{-- ---------------------------------------------------------- --}}
        {{--  FILTER SECTION                                             --}}
        {{-- ---------------------------------------------------------- --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">Select Student</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1.5">
                        Class <span class="text-red-500">*</span>
                    </label>
                    <select wire:model.live="submissionStandardId"
                        class="w-full rounded-xl border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 bg-gray-50">
                        <option value="">— Select Class —</option>
                        @foreach($standards as $std)
                            <option value="{{ $std->id }}">{{ $std->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1.5">Section</label>
                    <select wire:model.live="submissionSectionId"
                        class="w-full rounded-xl border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 bg-gray-50"
                        @disabled(!$submissionStandardId)>
                        <option value="">All Sections</option>
                        @foreach($sections as $sec)
                            <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1.5">
                        Student <span class="text-red-500">*</span>
                    </label>
                    <select wire:model.live="selectedStudentId"
                        class="w-full rounded-xl border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 bg-gray-50"
                        @disabled(!$submissionStandardId)>
                        <option value="">— Select Student —</option>
                        @foreach($students as $stu)
                            <option value="{{ $stu->id }}">
                                {{ $stu->user->name ?? $stu->full_name }} ({{ $stu->admission_no }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- ---------------------------------------------------------- --}}
        {{--  STUDENT SELECTED STATE                                     --}}
        {{-- ---------------------------------------------------------- --}}
        @if(!empty($studentInfo))

            {{-- The student, their fee net of concession, and every payment —
                 the same ledger View Fee and the admin tab render. --}}
            @if (!empty($submissionLedger))
                <div class="space-y-4">
                    @include('livewire.partials.student-fee-view', [
                        'sv'        => $submissionLedger,
                        'feePrefix' => 'accounts',
                        'feeOrg'    => auth()->user()->organization_id,
                    ])
                </div>
            @endif

            {{-- ---------------------------------------------------------- --}}
            {{--  SUBMIT PAYMENT FORM                                        --}}
            {{-- ---------------------------------------------------------- --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-50 flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    <p class="text-sm font-semibold text-gray-700">Submit Payment</p>
                </div>
                <div class="p-5 space-y-4">

                    {{-- Row 1: Fee Type + Amount + Payment Mode --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1.5">
                                Fee Type <span class="text-red-500">*</span>
                            </label>
                            <div class="flex gap-2">
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" wire:model.live="submitFeeType" value="academic" class="sr-only peer">
                                    <div class="w-full text-center px-3 py-2.5 rounded-xl border-2 text-sm font-medium transition
                                        peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700
                                        border-gray-200 text-gray-500 hover:border-emerald-300">
                                        Academic
                                        @if(!empty($feeBreakdown))
                                            <span class="block text-[10px] mt-0.5 opacity-75">₹{{ number_format($feeBreakdown['academic_remaining'], 2) }} due</span>
                                        @endif
                                    </div>
                                </label>
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" wire:model.live="submitFeeType" value="transport" class="sr-only peer">
                                    <div class="w-full text-center px-3 py-2.5 rounded-xl border-2 text-sm font-medium transition
                                        peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700
                                        border-gray-200 text-gray-500 hover:border-blue-300">
                                        Transport
                                        @if(!empty($feeBreakdown))
                                            <span class="block text-[10px] mt-0.5 opacity-75">₹{{ number_format($feeBreakdown['transport_remaining'], 2) }} due</span>
                                        @endif
                                    </div>
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1.5">
                                Amount <span class="text-red-500">*</span>
                                @if(!empty($feeBreakdown))
                                    <span class="text-emerald-600 font-semibold ml-1">
                                        (Remaining: ₹{{ $submitFeeType === 'transport'
                                            ? number_format($feeBreakdown['transport_remaining'], 2)
                                            : number_format($feeBreakdown['academic_remaining'], 2) }})
                                    </span>
                                @endif
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-medium text-sm">₹</span>
                                <input type="number" wire:model="submitAmount" step="0.01" min="0" placeholder="0.00"
                                    class="w-full pl-7 rounded-xl border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 bg-gray-50" />
                            </div>
                            @error('submitAmount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1.5">
                                Payment Mode <span class="text-red-500">*</span>
                            </label>
                            <select wire:model.live="submitPaymentMode"
                                class="w-full rounded-xl border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 bg-gray-50">
                                <option value="cash">Cash</option>
                                <option value="online">Online</option>
                                <option value="cheque">Cheque</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="waiver">Waiver</option>
                            </select>
                        </div>
                    </div>

                    {{-- Row 2: Date + Remark --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1.5">
                                Payment Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" wire:model="submitDate"
                                class="w-full rounded-xl border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 bg-gray-50" />
                            @error('submitDate') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1.5">Remark</label>
                            <input type="text" wire:model="submitRemark" placeholder="Optional note..."
                                class="w-full rounded-xl border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 bg-gray-50" />
                        </div>
                    </div>

                    {{-- Penalty Amount --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1.5">Penalty Amount (optional)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-medium text-sm">₹</span>
                                <input type="number" wire:model="penaltyAmount" step="0.01" min="0" placeholder="0.00"
                                    class="w-full pl-7 rounded-xl border-gray-200 text-sm focus:border-red-400 focus:ring-red-400 bg-gray-50" />
                            </div>
                            @error('penaltyAmount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Waiver fields (shown only when payment mode = waiver) --}}
                    @if($submitPaymentMode === 'waiver')
                        <div class="bg-amber-50 rounded-xl border border-amber-200 p-4 space-y-3">
                            <p class="text-xs font-semibold text-amber-700 flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                </svg>
                                Waiver Details
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1.5">
                                        Waiver Amount <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-medium text-sm">₹</span>
                                        <input type="number" wire:model="waiverAmount" step="0.01" min="0" placeholder="0.00"
                                            class="w-full pl-7 rounded-xl border-amber-200 text-sm focus:border-amber-500 focus:ring-amber-500 bg-white" />
                                    </div>
                                    @error('waiverAmount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1.5">
                                        Waiver Reason <span class="text-red-500">*</span>
                                    </label>
                                    <textarea wire:model="waiverReason" rows="2" placeholder="Reason for waiver..."
                                        class="w-full rounded-xl border-amber-200 text-sm focus:border-amber-500 focus:ring-amber-500 bg-white resize-none"></textarea>
                                    @error('waiverReason') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Payment Summary Preview --}}
                    @if($submitAmount)
                        <div class="bg-gray-50 rounded-xl border border-gray-100 p-4">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Payment Summary</p>
                            <div class="space-y-1.5 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Base Amount</span>
                                    <span class="font-medium">₹{{ number_format((float)$submitAmount, 2) }}</span>
                                </div>
                                @if($penaltyAmount && (float)$penaltyAmount > 0)
                                    <div class="flex justify-between text-red-500">
                                        <span>+ Penalty</span>
                                        <span class="font-medium">₹{{ number_format((float)$penaltyAmount, 2) }}</span>
                                    </div>
                                @endif
                                @if($submitPaymentMode === 'waiver' && $waiverAmount && (float)$waiverAmount > 0)
                                    <div class="flex justify-between text-amber-600">
                                        <span>− Waiver</span>
                                        <span class="font-medium">₹{{ number_format((float)$waiverAmount, 2) }}</span>
                                    </div>
                                @endif
                                @php
                                    $netAmt = (float)$submitAmount + (float)($penaltyAmount ?: 0) - (float)(($submitPaymentMode === 'waiver' ? $waiverAmount : 0) ?: 0);
                                @endphp
                                <div class="flex justify-between border-t border-gray-200 pt-2 mt-2 font-bold text-emerald-700 text-base">
                                    <span>Net Amount</span>
                                    <span>₹{{ number_format(max(0, $netAmt), 2) }}</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Submit Button --}}
                    <button wire:click="submitFeePayment" wire:loading.attr="disabled"
                        class="w-full flex items-center justify-center gap-2 px-5 py-3 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800
                               text-white font-semibold rounded-xl transition text-sm disabled:opacity-60 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="submitFeePayment">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                        <span wire:loading wire:target="submitFeePayment">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>
                        </span>
                        <span wire:loading.remove wire:target="submitFeePayment">Submit Payment</span>
                        <span wire:loading wire:target="submitFeePayment">Processing...</span>
                    </button>
                    <p class="text-[11px] text-gray-400 text-center -mt-1">Receipt number will be generated automatically</p>
                </div>
            </div>

        @else
            {{-- ---------------------------------------------------------- --}}
            {{--  EMPTY STATE                                                --}}
            {{-- ---------------------------------------------------------- --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-16 text-center">
                <div class="w-20 h-20 rounded-2xl bg-emerald-50 flex items-center justify-center mx-auto mb-5">
                    <svg class="w-10 h-10 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-600 mb-2">No Student Selected</h3>
                <p class="text-sm text-gray-400 max-w-sm mx-auto">
                    Choose a class, section, and student from the filters above to view their fee details and submit a payment.
                </p>
            </div>
        @endif

    </div>{{-- /page content --}}
</div>
