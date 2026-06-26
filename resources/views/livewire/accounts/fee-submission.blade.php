<div>
    {{-- ================================================================== --}}
    {{--  STICKY HEADER                                                      --}}
    {{-- ================================================================== --}}
    <div class="sticky top-0 z-30 bg-white border-b border-gray-200 shadow-sm">
        <div class="px-6 py-4 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-emerald-700 leading-tight">Fee Submission</h1>
                <p class="text-xs text-gray-400 mt-0.5">Submit fee payments and manage student dues</p>
            </div>
            @if(!empty($studentInfo) && count($studentTransactions) > 0)
                <button wire:click="toggleTransactionHistory"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-50 text-emerald-700 rounded-lg text-sm font-medium border border-emerald-200 hover:bg-emerald-100 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Transaction History
                    <span class="bg-emerald-600 text-white text-xs rounded-full px-1.5 py-0.5 leading-none">{{ count($studentTransactions) }}</span>
                </button>
            @endif
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

            {{-- Student Profile Card --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-start gap-5">
                    {{-- Avatar --}}
                    <div class="flex-shrink-0">
                        <div class="w-16 h-16 rounded-2xl bg-emerald-100 flex items-center justify-center">
                            <span class="text-2xl font-bold text-emerald-700 leading-none">
                                {{ mb_strtoupper(mb_substr($studentInfo['name'], 0, 1)) }}{{ mb_strtoupper(mb_substr(strstr($studentInfo['name'], ' ') ?: '', 1, 1)) }}
                            </span>
                        </div>
                    </div>
                    {{-- Info --}}
                    <div class="flex-1 min-w-0">
                        <h2 class="text-base font-bold text-gray-800">{{ $studentInfo['name'] }}</h2>
                        <p class="text-sm text-emerald-600 font-medium mt-0.5">{{ $studentInfo['class'] }} — {{ $studentInfo['section'] }}</p>
                        <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-x-6 gap-y-2 text-sm">
                            <div>
                                <p class="text-[11px] text-gray-400 uppercase tracking-wide">Admission No</p>
                                <p class="font-semibold text-gray-700">{{ $studentInfo['admission_no'] }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] text-gray-400 uppercase tracking-wide">Roll No</p>
                                <p class="font-semibold text-gray-700">{{ $studentInfo['roll_no'] }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] text-gray-400 uppercase tracking-wide">Father's Name</p>
                                <p class="font-semibold text-gray-700">{{ $studentInfo['father_name'] }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] text-gray-400 uppercase tracking-wide">Phone</p>
                                <p class="font-semibold text-gray-700">{{ $studentInfo['phone'] ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Fee Analytics Strip --}}
            @if(!empty($feeBreakdown))
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="bg-emerald-50 rounded-2xl p-4 border border-emerald-100">
                        <p class="text-[11px] font-semibold text-emerald-600 uppercase tracking-wide">Total Fee</p>
                        <p class="text-xl font-bold text-emerald-800 mt-1">₹{{ number_format($feeBreakdown['total_fee'], 2) }}</p>
                        <p class="text-[11px] text-emerald-500 mt-1">
                            Acad: ₹{{ number_format($feeBreakdown['total_academic_fee'], 2) }}
                            &nbsp;·&nbsp;
                            Trans: ₹{{ number_format($feeBreakdown['total_transport_fee'], 2) }}
                        </p>
                    </div>
                    <div class="bg-green-50 rounded-2xl p-4 border border-green-100">
                        <p class="text-[11px] font-semibold text-green-600 uppercase tracking-wide">Total Paid</p>
                        <p class="text-xl font-bold text-green-800 mt-1">₹{{ number_format($feeBreakdown['total_paid'], 2) }}</p>
                        <p class="text-[11px] text-green-500 mt-1">
                            Acad: ₹{{ number_format($feeBreakdown['academic_paid'], 2) }}
                            &nbsp;·&nbsp;
                            Trans: ₹{{ number_format($feeBreakdown['transport_paid'], 2) }}
                        </p>
                    </div>
                    <div class="bg-red-50 rounded-2xl p-4 border border-red-100">
                        <p class="text-[11px] font-semibold text-red-500 uppercase tracking-wide">Remaining</p>
                        <p class="text-xl font-bold {{ $feeBreakdown['total_remaining'] > 0 ? 'text-red-700' : 'text-emerald-700' }} mt-1">
                            ₹{{ number_format($feeBreakdown['total_remaining'], 2) }}
                        </p>
                        <p class="text-[11px] text-red-400 mt-1">
                            Acad: ₹{{ number_format($feeBreakdown['academic_remaining'], 2) }}
                            &nbsp;·&nbsp;
                            Trans: ₹{{ number_format($feeBreakdown['transport_remaining'], 2) }}
                        </p>
                    </div>
                    <div class="bg-amber-50 rounded-2xl p-4 border border-amber-100">
                        <p class="text-[11px] font-semibold text-amber-600 uppercase tracking-wide">Penalties &amp; Waivers</p>
                        <p class="text-xl font-bold text-amber-800 mt-1">₹{{ number_format($feeBreakdown['total_penalty'], 2) }}</p>
                        <p class="text-[11px] text-amber-500 mt-1">
                            Waiver: ₹{{ number_format($feeBreakdown['total_waiver'], 2) }}
                        </p>
                    </div>
                </div>

                {{-- Fee Breakdown Table --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-gray-50">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Fee Breakdown</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Fee Type</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Fee</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Paid</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Remaining</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @php
                                    $academicStatus = $feeBreakdown['academic_remaining'] <= 0 ? 'paid' : ($feeBreakdown['academic_paid'] > 0 ? 'partial' : 'unpaid');
                                    $transportStatus = $feeBreakdown['total_transport_fee'] <= 0 ? 'na' : ($feeBreakdown['transport_remaining'] <= 0 ? 'paid' : ($feeBreakdown['transport_paid'] > 0 ? 'partial' : 'unpaid'));
                                    $statusConfig = [
                                        'paid'    => ['bg-emerald-100 text-emerald-700', 'Paid'],
                                        'partial' => ['bg-amber-100 text-amber-700', 'Partial'],
                                        'unpaid'  => ['bg-red-100 text-red-600', 'Unpaid'],
                                        'na'      => ['bg-gray-100 text-gray-400', 'N/A'],
                                    ];
                                @endphp
                                <tr class="hover:bg-emerald-50/40 transition">
                                    <td class="px-5 py-3.5">
                                        <span class="inline-flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                            <span class="font-semibold text-gray-700">Academic</span>
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-medium text-gray-800">₹{{ number_format($feeBreakdown['total_academic_fee'], 2) }}</td>
                                    <td class="px-5 py-3.5 text-right font-semibold text-emerald-600">₹{{ number_format($feeBreakdown['academic_paid'], 2) }}</td>
                                    <td class="px-5 py-3.5 text-right font-semibold {{ $feeBreakdown['academic_remaining'] > 0 ? 'text-red-500' : 'text-emerald-600' }}">
                                        ₹{{ number_format($feeBreakdown['academic_remaining'], 2) }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusConfig[$academicStatus][0] }}">
                                            {{ $statusConfig[$academicStatus][1] }}
                                        </span>
                                    </td>
                                </tr>
                                <tr class="hover:bg-blue-50/30 transition">
                                    <td class="px-5 py-3.5">
                                        <span class="inline-flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                                            <span class="font-semibold text-gray-700">Transport</span>
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-medium text-gray-800">₹{{ number_format($feeBreakdown['total_transport_fee'], 2) }}</td>
                                    <td class="px-5 py-3.5 text-right font-semibold text-blue-600">₹{{ number_format($feeBreakdown['transport_paid'], 2) }}</td>
                                    <td class="px-5 py-3.5 text-right font-semibold {{ $feeBreakdown['transport_remaining'] > 0 ? 'text-red-500' : 'text-emerald-600' }}">
                                        ₹{{ number_format($feeBreakdown['transport_remaining'], 2) }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusConfig[$transportStatus][0] }}">
                                            {{ $statusConfig[$transportStatus][1] }}
                                        </span>
                                    </td>
                                </tr>
                                <tr class="bg-gray-50 font-bold">
                                    <td class="px-5 py-3 text-gray-700">Total</td>
                                    <td class="px-5 py-3 text-right text-gray-800">₹{{ number_format($feeBreakdown['total_fee'], 2) }}</td>
                                    <td class="px-5 py-3 text-right text-emerald-700">₹{{ number_format($feeBreakdown['total_paid'], 2) }}</td>
                                    <td class="px-5 py-3 text-right {{ $feeBreakdown['total_remaining'] > 0 ? 'text-red-600' : 'text-emerald-700' }}">
                                        ₹{{ number_format($feeBreakdown['total_remaining'], 2) }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
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

            {{-- ---------------------------------------------------------- --}}
            {{--  PAYMENT HISTORY (COLLAPSIBLE)                              --}}
            {{-- ---------------------------------------------------------- --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <button wire:click="toggleTransactionHistory"
                    class="w-full flex items-center justify-between px-5 py-4 hover:bg-gray-50/60 transition text-left">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="text-sm font-semibold text-gray-700">Payment History</span>
                        @if(count($studentTransactions) > 0)
                            <span class="bg-emerald-100 text-emerald-700 text-xs font-semibold px-2 py-0.5 rounded-full">
                                {{ count($studentTransactions) }}
                            </span>
                        @endif
                    </span>
                    <svg class="w-4 h-4 text-gray-400 transition-transform {{ $showTransactionHistory ? 'rotate-180' : '' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                @if($showTransactionHistory)
                    <div class="border-t border-gray-50">
                        @if(count($studentTransactions) > 0)
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-emerald-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wide">Date</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wide">Fee Type</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wide">Mode</th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold text-emerald-700 uppercase tracking-wide">Amount</th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold text-emerald-700 uppercase tracking-wide">Penalty</th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold text-emerald-700 uppercase tracking-wide">Waiver</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wide">Remark</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wide">Collected By</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        @foreach($studentTransactions as $tx)
                                            <tr class="hover:bg-emerald-50/40 transition">
                                                <td class="px-4 py-3 text-xs text-gray-600 whitespace-nowrap">
                                                    {{ \Carbon\Carbon::parse($tx['payment_date'])->format('d M Y') }}
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                                        {{ $tx['fee_type'] === 'academic' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700' }}">
                                                        {{ ucfirst($tx['fee_type']) }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-xs text-gray-600 capitalize">
                                                    {{ ucfirst(str_replace('_', ' ', $tx['payment_mode'])) }}
                                                </td>
                                                <td class="px-4 py-3 text-right font-semibold text-gray-800 text-xs">
                                                    ₹{{ number_format($tx['amount'], 2) }}
                                                </td>
                                                <td class="px-4 py-3 text-right text-xs {{ ($tx['penalty_amount'] ?? 0) > 0 ? 'text-red-500 font-medium' : 'text-gray-300' }}">
                                                    {{ ($tx['penalty_amount'] ?? 0) > 0 ? '₹' . number_format($tx['penalty_amount'], 2) : '—' }}
                                                </td>
                                                <td class="px-4 py-3 text-right text-xs {{ ($tx['waiver_amount'] ?? 0) > 0 ? 'text-amber-600 font-medium' : 'text-gray-300' }}">
                                                    {{ ($tx['waiver_amount'] ?? 0) > 0 ? '₹' . number_format($tx['waiver_amount'], 2) : '—' }}
                                                </td>
                                                <td class="px-4 py-3 text-xs text-gray-500 max-w-[140px] truncate">
                                                    {{ $tx['remark'] ?? '—' }}
                                                </td>
                                                <td class="px-4 py-3 text-xs text-gray-500">
                                                    {{ $tx['submitted_by'] ?? '—' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-10">
                                <svg class="w-10 h-10 mx-auto text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="text-sm text-gray-400">No payment history yet.</p>
                            </div>
                        @endif
                    </div>
                @endif
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
