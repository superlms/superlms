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
        ])
    @else
        <div class="bg-white rounded-2xl border border-dashed border-gray-200 px-4 py-16 text-center">
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
        ])

    @elseif (!empty($classFeeList))
        @php
            $classTotals = [
                'fee'       => array_sum(array_column($classFeeList, 'totalFee')),
                'collected' => array_sum(array_column($classFeeList, 'totalCollected')),
                'pending'   => array_sum(array_column($classFeeList, 'pending')),
            ];
        @endphp
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-sm font-semibold text-gray-900">Students</h3>
                <span class="text-[11px] text-gray-400">
                    {{ count($classFeeList) }} student{{ count($classFeeList) === 1 ? '' : 's' }} ·
                    Fee ₹{{ number_format($classTotals['fee'], 2) }} ·
                    Collected ₹{{ number_format($classTotals['collected'], 2) }} ·
                    Pending ₹{{ number_format($classTotals['pending'], 2) }}
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-100">
                        <tr class="text-[11px] uppercase tracking-wider text-gray-400">
                            <th class="px-4 py-2 text-left font-normal w-10">#</th>
                            <th class="px-4 py-2 text-left font-normal">Student</th>
                            <th class="px-4 py-2 text-left font-normal">Adm No.</th>
                            <th class="px-4 py-2 text-left font-normal">Class / Section</th>
                            <th class="px-4 py-2 text-right font-normal">Academic</th>
                            <th class="px-4 py-2 text-right font-normal">Transport</th>
                            <th class="px-4 py-2 text-right font-normal">Total Fee</th>
                            <th class="px-4 py-2 text-right font-normal">Collected</th>
                            <th class="px-4 py-2 text-right font-normal">Pending</th>
                            <th class="px-4 py-2 text-center font-normal">Status</th>
                            <th class="px-4 py-2 text-center font-normal w-20">View</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($classFeeList as $n => $row)
                            @php
                                if ($row['pending'] <= 0 && $row['totalFee'] > 0) {
                                    [$statusClass, $statusLabel] = ['bg-emerald-50 text-emerald-600', 'Paid'];
                                } elseif ($row['totalCollected'] > 0) {
                                    [$statusClass, $statusLabel] = ['bg-amber-50 text-amber-600', 'Partial'];
                                } else {
                                    [$statusClass, $statusLabel] = ['bg-rose-50 text-rose-500', 'Unpaid'];
                                }
                            @endphp
                            <tr wire:key="vf-class-{{ $row['id'] }}" class="hover:bg-gray-50/70">
                                <td class="px-4 py-2.5 text-[11px] text-gray-300 tabular-nums">{{ $n + 1 }}</td>
                                <td class="px-4 py-2.5 font-medium text-gray-800">{{ $row['name'] }}</td>
                                <td class="px-4 py-2.5 text-gray-500 text-xs">{{ $row['admission_no'] }}</td>
                                <td class="px-4 py-2.5 text-gray-600 whitespace-nowrap">{{ $row['class_section'] }}</td>
                                <td class="px-4 py-2.5 text-right text-gray-700 tabular-nums">₹{{ number_format($row['academicFee'], 2) }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums {{ $row['hasTransport'] ? 'text-gray-700' : 'text-gray-300' }}">
                                    {{ $row['hasTransport'] ? '₹' . number_format($row['transportFee'], 2) : '—' }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-semibold text-gray-900 tabular-nums">₹{{ number_format($row['totalFee'], 2) }}</td>
                                <td class="px-4 py-2.5 text-right text-emerald-600 tabular-nums">₹{{ number_format($row['totalCollected'], 2) }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums {{ $row['pending'] > 0 ? 'text-rose-500' : 'text-gray-300' }}">₹{{ number_format($row['pending'], 2) }}</td>
                                <td class="px-4 py-2.5 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    <button wire:click="viewStudentFromClass({{ $row['id'] }})" title="View ledger"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-200 text-gray-400 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-gray-200">
                            <td colspan="6" class="px-4 py-2.5 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Total</td>
                            <td class="px-4 py-2.5 text-right font-bold text-gray-900 tabular-nums">₹{{ number_format($classTotals['fee'], 2) }}</td>
                            <td class="px-4 py-2.5 text-right font-bold text-emerald-600 tabular-nums">₹{{ number_format($classTotals['collected'], 2) }}</td>
                            <td class="px-4 py-2.5 text-right font-bold text-rose-500 tabular-nums">₹{{ number_format($classTotals['pending'], 2) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    @else
        <div class="bg-white rounded-2xl border border-dashed border-gray-200 px-4 py-16 text-center">
            <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            </div>
            <p class="text-sm font-semibold text-gray-800">Pick a class</p>
            <p class="text-xs text-gray-400 mt-1">Choose a class — and a section, if you want — then press <strong class="text-gray-600">Load Students</strong>.</p>
        </div>
    @endif

@endif
