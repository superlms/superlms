{{-- ══════════════════════════════════════════════════════════════════
     VIEW FEE — the body. By Student hands one student's ledger to
     `livewire.partials.student-fee-view`; By Class lists the class and lets
     you open any row's ledger with the same partial.

     Fed by App\Livewire\Concerns\HandlesViewFee, plus $feePrefix
     ('admin' | 'accounts') and $feeOrg for the receipt links. The sub-tabs
     and filter band live in view-fee-header.blade.php, included by each host
     inside its own sticky header.
══════════════════════════════════════════════════════════════════ --}}

@if ($viewSubTab === 'by_student')

    @if (!empty($studentFeeView))
        @include('livewire.partials.student-fee-view', [
            'sv'        => $studentFeeView,
            'feePrefix' => $feePrefix,
            'feeOrg'    => $feeOrg,
            // The admin's View & Submit Fee lets a payment's date be corrected.
            'editDates' => $feeEditDates ?? false,
        ])
    @else
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-16 text-center">
            <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            </div>
            <p class="text-sm font-semibold text-gray-800">Select a student</p>
            <p class="text-xs text-gray-400 mt-1">Pick a class, section and student from the filter above to see the full ledger.</p>
        </div>
    @endif

@else

    {{-- ── One student, opened from the class list ── --}}
    @if ($classViewStudentId && !empty($classStudentFeeView))
        <button wire:click="backToClassList"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-gray-900">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Back to class list
        </button>
        @include('livewire.partials.student-fee-view', [
            'sv'        => $classStudentFeeView,
            'feePrefix' => $feePrefix,
            'feeOrg'    => $feeOrg,
            'editDates' => $feeEditDates ?? false,
        ])

    @elseif (!empty($classFeeList))
        @php
            $classTotals = [
                'fee'       => array_sum(array_column($classFeeList, 'totalFee')),
                'collected' => array_sum(array_column($classFeeList, 'totalCollected')),
                'pending'   => array_sum(array_column($classFeeList, 'pending')),
            ];
        @endphp
        {{-- In the Mark Attendance look: a heading strip with the counts, then
             flat rows — plain text, the status a word, the ledger an icon. --}}
        @php $vfCols = 'grid grid-cols-[2.5rem_minmax(0,1fr)_6rem_6.5rem_6.5rem_7rem_7rem_6.5rem_4.5rem_3.5rem] items-center'; @endphp
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="text-sm font-semibold text-gray-700">Students</h3>
                    <p class="text-[11px] text-gray-400">Fee, collected and pending for each student of the class</p>
                </div>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-gray-600">
                    <span>Students <strong>{{ count($classFeeList) }}</strong></span>
                    <span>Fee <strong>₹{{ number_format($classTotals['fee'], 2) }}</strong></span>
                    <span>Collected <strong>₹{{ number_format($classTotals['collected'], 2) }}</strong></span>
                    <span>Pending <strong class="{{ $classTotals['pending'] > 0 ? 'text-red-600' : '' }}">₹{{ number_format($classTotals['pending'], 2) }}</strong></span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <div class="min-w-[980px] text-sm">
                    <div class="{{ $vfCols }} bg-gray-50 text-xs text-gray-500">
                        <span class="px-4 py-2">#</span>
                        <span class="px-4 py-2">Student</span>
                        <span class="px-4 py-2">Class</span>
                        <span class="px-4 py-2 text-right">Academic</span>
                        <span class="px-4 py-2 text-right">Transport</span>
                        <span class="px-4 py-2 text-right">Total fee</span>
                        <span class="px-4 py-2 text-right">Collected</span>
                        <span class="px-4 py-2 text-right">Pending</span>
                        <span class="px-4 py-2">Status</span>
                        <span class="px-4 py-2 text-center">View</span>
                    </div>
                    @foreach ($classFeeList as $n => $row)
                        @php
                            if ($row['pending'] <= 0 && $row['totalFee'] > 0) {
                                [$statusClass, $statusLabel] = ['text-gray-700', 'Paid'];
                            } elseif ($row['totalCollected'] > 0) {
                                [$statusClass, $statusLabel] = ['text-amber-600', 'Partial'];
                            } else {
                                [$statusClass, $statusLabel] = ['text-red-600', 'Unpaid'];
                            }
                        @endphp
                        <div wire:key="vf-class-{{ $row['id'] }}" class="{{ $vfCols }} border-t border-gray-100">
                            <span class="px-4 py-2.5 text-[11px] text-gray-300 tabular-nums">{{ $n + 1 }}</span>
                            <div class="px-4 py-2.5 min-w-0">
                                <p class="text-gray-800 truncate">{{ $row['name'] }}</p>
                                <p class="text-[11px] text-gray-400 truncate">Adm {{ $row['admission_no'] }}</p>
                            </div>
                            <span class="px-4 py-2.5 text-gray-600 truncate">{{ $row['class_section'] }}</span>
                            <span class="px-4 py-2.5 text-right text-gray-700 tabular-nums">₹{{ number_format($row['academicFee'], 2) }}</span>
                            <span class="px-4 py-2.5 text-right tabular-nums {{ $row['hasTransport'] ? 'text-gray-700' : 'text-gray-400' }}">
                                {{ $row['hasTransport'] ? '₹' . number_format($row['transportFee'], 2) : '—' }}
                            </span>
                            <span class="px-4 py-2.5 text-right text-gray-800 tabular-nums">₹{{ number_format($row['totalFee'], 2) }}</span>
                            <span class="px-4 py-2.5 text-right text-gray-700 tabular-nums">₹{{ number_format($row['totalCollected'], 2) }}</span>
                            <span class="px-4 py-2.5 text-right tabular-nums {{ $row['pending'] > 0 ? 'text-red-600' : 'text-gray-400' }}">₹{{ number_format($row['pending'], 2) }}</span>
                            <span class="px-4 py-2.5 {{ $statusClass }}">{{ $statusLabel }}</span>
                            <span class="px-4 py-2.5 text-center">
                                <button wire:click="viewStudentFromClass({{ $row['id'] }})" title="View ledger"
                                    class="inline-flex p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                            </span>
                        </div>
                    @endforeach
                    <div class="{{ $vfCols }} border-t border-gray-100 bg-gray-50/60">
                        <span></span>
                        <span class="px-4 py-2.5 text-gray-700">Total</span>
                        <span></span><span></span><span></span>
                        <span class="px-4 py-2.5 text-right text-gray-800 font-medium tabular-nums">₹{{ number_format($classTotals['fee'], 2) }}</span>
                        <span class="px-4 py-2.5 text-right text-gray-800 font-medium tabular-nums">₹{{ number_format($classTotals['collected'], 2) }}</span>
                        <span class="px-4 py-2.5 text-right font-medium tabular-nums {{ $classTotals['pending'] > 0 ? 'text-red-600' : 'text-gray-400' }}">₹{{ number_format($classTotals['pending'], 2) }}</span>
                        <span></span><span></span>
                    </div>
                </div>
            </div>
        </div>

    @else
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-16 text-center">
            <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            </div>
            <p class="text-sm font-semibold text-gray-800">Pick a class</p>
            <p class="text-xs text-gray-400 mt-1">Choose a class — and a section, if you want — then press <strong class="text-gray-600">Load Students</strong>.</p>
        </div>
    @endif

@endif
