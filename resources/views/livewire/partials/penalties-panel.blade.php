{{-- ══════════════════════════════════════════════════════════════════
     PENALTIES — the body. By Student shows one student's accrued penalty per
     fee cycle (net of any waiver/payment); By Class lists the class with each
     student's current total.

     Fed by App\Livewire\Concerns\HandlesPenalties (plus HandlesStudentFeeView
     for the per-student breakdown). The sub-tabs and filter band live in
     penalties-header.blade.php, included by the host inside its own sticky
     header.
══════════════════════════════════════════════════════════════════ --}}

@if ($penaltySubTab === 'by_student')

    @if (empty($penaltyStudentView))
        <div class="bg-white rounded-2xl border border-dashed border-gray-200 px-4 py-16 text-center">
            <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            </div>
            <p class="text-sm font-semibold text-gray-800">Select a student</p>
            <p class="text-xs text-gray-400 mt-1">Pick a class, section and student from the filter above to see what they owe in penalties.</p>
        </div>
    @else
        @php
            $pStu       = $penaltyStudentView['student'];
            $pCycles    = collect($penaltyStudentView['cycles'] ?? []);
            $pAcademic  = $pCycles->firstWhere('fee_type', 'academic');
            $pTransport = $pCycles->firstWhere('fee_type', 'transport');
            $pTotalNet  = $pCycles->sum('penalty_net');
        @endphp

        {{-- Student + summary strip --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center text-sm font-bold flex-shrink-0">
                    {{ $pStu['initial'] }}
                </div>
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-gray-900 truncate">{{ $pStu['name'] }}</h3>
                    <p class="text-[11px] text-gray-400 mt-0.5">{{ $pStu['class_section'] }} · Adm {{ $pStu['admission_no'] }}</p>
                </div>
            </div>
            <div class="px-4 py-3 grid grid-cols-3 divide-x divide-gray-100">
                <div class="pr-3">
                    <p class="text-[11px] uppercase tracking-wider text-gray-400">Academic</p>
                    <p class="text-lg font-bold {{ ($pAcademic['penalty_net'] ?? 0) > 0 ? 'text-amber-600' : 'text-gray-300' }} mt-0.5">₹{{ number_format($pAcademic['penalty_net'] ?? 0, 2) }}</p>
                </div>
                <div class="px-3">
                    <p class="text-[11px] uppercase tracking-wider text-gray-400">Transport</p>
                    <p class="text-lg font-bold {{ ($pTransport['penalty_net'] ?? 0) > 0 ? 'text-amber-600' : 'text-gray-300' }} mt-0.5">₹{{ number_format($pTransport['penalty_net'] ?? 0, 2) }}</p>
                </div>
                <div class="pl-3">
                    <p class="text-[11px] uppercase tracking-wider text-gray-400">Total Still Due</p>
                    <p class="text-lg font-bold {{ $pTotalNet > 0 ? 'text-amber-600' : 'text-gray-300' }} mt-0.5">₹{{ number_format($pTotalNet, 2) }}</p>
                </div>
            </div>
        </div>

        {{-- Per fee-cycle breakdown --}}
        @forelse ($pCycles as $cycle)
            @php
                $overdueInstallments = collect($cycle['installments'])->filter(fn ($i) => $i['overdue']);
            @endphp
            <div wire:key="pen-cycle-{{ $cycle['fee_type'] }}" class="bg-white rounded-xl border border-gray-200 overflow-hidden mt-4">
                <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
                    <h3 class="text-sm font-semibold text-gray-900">
                        {{ ucfirst($cycle['fee_type']) }} Fee Cycle
                        <span class="text-gray-400 font-normal">· {{ $cycle['label'] }} · {{ $cycle['year'] }}</span>
                    </h3>
                    <span class="text-[11px] text-gray-400 tabular-nums">
                        Accrued <strong class="text-gray-600">₹{{ number_format($cycle['penalty_total'], 2) }}</strong>
                        @if ($cycle['penalty_waived'] > 0) · Waived <strong class="text-emerald-600">− ₹{{ number_format($cycle['penalty_waived'], 2) }}</strong>@endif
                        @if ($cycle['penalty_paid'] > 0) · Paid <strong class="text-emerald-600">− ₹{{ number_format($cycle['penalty_paid'], 2) }}</strong>@endif
                        · Still Due <strong class="{{ $cycle['penalty_net'] > 0 ? 'text-amber-600' : 'text-gray-400' }}">₹{{ number_format($cycle['penalty_net'], 2) }}</strong>
                    </span>
                </div>
                @if ($overdueInstallments->isEmpty())
                    <p class="px-4 py-6 text-center text-xs text-gray-400">No overdue installments on this cycle.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b border-gray-100">
                                <tr class="text-[11px] uppercase tracking-wider text-gray-400">
                                    <th class="px-4 py-2 text-left font-normal">Installment</th>
                                    <th class="px-4 py-2 text-left font-normal">Due Date</th>
                                    <th class="px-4 py-2 text-right font-normal">Balance</th>
                                    <th class="px-4 py-2 text-right font-normal">Days Late</th>
                                    <th class="px-4 py-2 text-right font-normal">Rate / Day</th>
                                    <th class="px-4 py-2 text-right font-normal">Accrued</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($overdueInstallments as $n => $inst)
                                    <tr wire:key="pen-inst-{{ $cycle['fee_type'] }}-{{ $n }}" class="hover:bg-gray-50/70">
                                        <td class="px-4 py-2 text-gray-700">{{ $inst['label'] }}</td>
                                        <td class="px-4 py-2 text-rose-500 whitespace-nowrap">{{ $inst['due_date'] ?? '—' }}</td>
                                        <td class="px-4 py-2 text-right text-gray-600 tabular-nums">₹{{ number_format($inst['balance'], 2) }}</td>
                                        <td class="px-4 py-2 text-right text-gray-600 tabular-nums">{{ $inst['days_late'] }}</td>
                                        <td class="px-4 py-2 text-right text-gray-500 tabular-nums">₹{{ number_format($inst['penalty_per_day'], 2) }}</td>
                                        <td class="px-4 py-2 text-right font-semibold text-amber-600 tabular-nums">₹{{ number_format($inst['penalty'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @empty
            <p class="mt-4 text-center text-xs text-gray-400">No fee cycle defined for this student's class.</p>
        @endforelse
    @endif

@else

    {{-- ── By class ── --}}
    @if (!empty($penaltyClassList))
        @php
            $classTotal = array_sum(array_column($penaltyClassList, 'total_penalty'));
        @endphp
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-sm font-semibold text-gray-900">Students</h3>
                <span class="text-[11px] text-gray-400">
                    {{ count($penaltyClassList) }} student{{ count($penaltyClassList) === 1 ? '' : 's' }} ·
                    Total penalty due <strong class="text-amber-600">₹{{ number_format($classTotal, 2) }}</strong>
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
                            <th class="px-4 py-2 text-right font-normal">Total Due</th>
                            <th class="px-4 py-2 text-center font-normal w-20">View</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($penaltyClassList as $n => $row)
                            <tr wire:key="pen-class-{{ $row['id'] }}" class="hover:bg-gray-50/70">
                                <td class="px-4 py-2.5 text-[11px] text-gray-300 tabular-nums">{{ $n + 1 }}</td>
                                <td class="px-4 py-2.5 font-medium text-gray-800">{{ $row['name'] }}</td>
                                <td class="px-4 py-2.5 text-gray-500 text-xs">{{ $row['admission_no'] }}</td>
                                <td class="px-4 py-2.5 text-gray-600 whitespace-nowrap">{{ $row['class_section'] }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums {{ $row['academic_penalty'] > 0 ? 'text-gray-700' : 'text-gray-300' }}">₹{{ number_format($row['academic_penalty'], 2) }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums {{ $row['transport_penalty'] > 0 ? 'text-gray-700' : 'text-gray-300' }}">₹{{ number_format($row['transport_penalty'], 2) }}</td>
                                <td class="px-4 py-2.5 text-right font-semibold tabular-nums {{ $row['total_penalty'] > 0 ? 'text-amber-600' : 'text-gray-300' }}">₹{{ number_format($row['total_penalty'], 2) }}</td>
                                <td class="px-4 py-2.5 text-center">
                                    <button wire:click="viewPenaltyFromClass({{ $row['id'] }})" title="View penalty detail"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-200 text-gray-400 hover:bg-amber-50 hover:text-amber-600 hover:border-amber-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
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

{{-- ── Waiver slide-in panel ── --}}
@if ($showPenaltyWaiver)
    <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePenaltyWaiver"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-md bg-white shadow-2xl flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Waive Penalty</h2>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $penaltyStudentView['student']['name'] ?? '' }}</p>
                </div>
                <button wire:click="closePenaltyWaiver" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Amount <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" min="0.01" wire:model="waiverAmount" placeholder="{{ $waiverMode === 'percent' ? 'e.g. 50' : 'e.g. 200' }}" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                    @error('waiverAmount')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Type</label>
                        <select wire:model="waiverFeeType" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                            <option value="academic">Academic</option>
                            <option value="transport">Transport</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Mode</label>
                        <select wire:model="waiverMode" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                            <option value="amount">Fixed Amount (₹)</option>
                            <option value="percent">Percentage (%)</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Collected By <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="waiverCollectedBy" placeholder="Staff name" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                    @error('waiverCollectedBy')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                <button wire:click="closePenaltyWaiver" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                <button wire:click="savePenaltyWaiver" wire:loading.attr="disabled" wire:target="savePenaltyWaiver"
                    class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                    <span wire:loading.remove wire:target="savePenaltyWaiver">Waive Penalty</span>
                    <span wire:loading wire:target="savePenaltyWaiver">Saving…</span>
                </button>
            </div>
        </div>
    </div>
@endif
