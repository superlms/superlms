{{-- ══════════════════════════════════════════════════════════════════
     FEE CYCLE + CALCULATOR — shared by Accounts\FeeCycles and the "Cycle"
     tab of Admin\Fee. Both hosts provide the same variables via
     App\Livewire\Concerns\HandlesFeeCycles::feeCycleViewData(), plus
     $standards. Edit this one file and both pages update together.
══════════════════════════════════════════════════════════════════ --}}

{{-- Tabs --}}
<div class="border border-gray-200 bg-white rounded-xl overflow-hidden mb-4">
    <div class="px-4 sm:px-6 pt-1 border-b border-gray-200">
        <div class="flex gap-1">
            @foreach (['cycle' => 'Fee Cycle', 'calculator' => 'Calculator'] as $tab => $label)
                <button wire:click="switchCycleTab('{{ $tab }}')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $cycleTab === $tab ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">{{ $label }}</button>
            @endforeach
        </div>
    </div>

    @if ($cycleTab === 'cycle')
        <div class="px-4 sm:px-6 py-3 bg-gray-50 flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white border border-gray-200 text-xs font-medium text-gray-600">
                Total <strong class="text-gray-900">{{ $totalCycles }}</strong>
            </span>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-100 text-xs font-medium text-emerald-600">
                Academic <strong>{{ $academicCycles }}</strong>
            </span>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-teal-50 border border-teal-100 text-xs font-medium text-teal-600">
                Transport <strong>{{ $transportCycles }}</strong>
            </span>
            <button wire:click="openCycleModal()"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm ml-auto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                Add Fee Cycle
            </button>
        </div>
    @else
        {{-- Calculator's own filter band — class, section, installment --}}
        <div class="px-4 sm:px-6 py-3 bg-gray-50 flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                Filter by:
            </div>
            <select wire:model.live="calcStandardId" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                <option value="">Select class…</option>
                @foreach ($standards as $std)
                    <option value="{{ $std->id }}">{{ $std->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="calcSectionId" @disabled(!$calcStandardId) class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
                <option value="">All sections</option>
                @foreach ($calcSections as $sec)
                    <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="calcSerial" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                <option value="">All installments</option>
                @foreach ($cycles->where('fee_type', 'academic')->where('is_token', false) as $cy)
                    <option value="{{ $cy->payment_serial }}">Installment {{ $cy->payment_serial }}</option>
                @endforeach
            </select>
        </div>
    @endif
</div>

@if ($cycleTab === 'cycle')
    {{-- Installments table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Installment</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Fee Type</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Due Date</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Fee %</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Penalty/Day</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Year</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($cycles as $cy)
                    <tr wire:key="cycle-{{ $cy->id }}" class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-semibold text-gray-800">
                            {{ $cy->is_token ? 'Token' : '#' . $cy->payment_serial }}
                        </td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-[11px] {{ $cy->fee_type === 'academic' ? 'bg-emerald-100 text-emerald-700' : 'bg-teal-100 text-teal-700' }} capitalize">{{ $cy->fee_type }}</span></td>
                        <td class="px-4 py-3 text-gray-600">{{ optional($cy->due_date)->format('d M Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ $cy->is_token ? '—' : rtrim(rtrim(number_format($cy->fee_percent, 2), '0'), '.') . '%' }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ $cy->is_token ? '₹' . number_format($cy->amount, 2) : '—' }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">₹{{ number_format($cy->penalty_per_day, 2) }}</td>
                        <td class="px-4 py-3 text-center text-gray-500">{{ $cy->academic_year }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1.5">
                                @unless ($cy->is_token)
                                    <button wire:click="openCycleModal({{ $cy->id }})" class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-amber-50 hover:text-amber-600" title="Edit">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </button>
                                @endunless
                                <button wire:click="deleteCycle({{ $cy->id }})" class="p-1.5 rounded-md border border-red-200 text-red-500 hover:bg-red-50" title="Delete">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-16 text-center">
                            <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            </div>
                            <p class="text-sm font-semibold text-gray-800">No installments yet</p>
                            <p class="text-xs text-gray-400 mt-1">Click "Add Fee Cycle" to define the fee cycle.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@else
    {{-- ══════════ CALCULATOR TAB (per class/section) ══════════ --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if (!$calcStandardId)
            <div class="px-4 sm:px-5 py-10 text-center text-sm text-gray-400">Select a class to calculate installment amounts.</div>
        @elseif (empty($calcRows))
            <div class="px-4 sm:px-5 py-10 text-center text-sm text-amber-600">No academic installments defined yet — add one from the Fee Cycle tab.</div>
        @else
            <div class="px-4 sm:px-5 py-3 flex flex-wrap items-center gap-x-6 gap-y-1 border-b border-gray-100">
                <span class="text-sm text-gray-500">Fee per student: <strong class="text-gray-900">₹{{ number_format($calcTotalFee, 2) }}</strong></span>
                <span class="text-sm text-gray-500">Students in class: <strong class="text-gray-900">{{ $calcStudentCount }}</strong></span>
                @if ($calcTotalFee <= 0)
                    <span class="text-xs text-amber-600">No academic fee structure found for this class — amounts show ₹0.</span>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Installment</th>
                            <th class="px-4 py-3 text-left">Due Date</th>
                            <th class="px-4 py-3 text-right">Fee %</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                            <th class="px-4 py-3 text-right">Total (class)</th>
                            <th class="px-4 py-3 text-right">Total Collected</th>
                            <th class="px-4 py-3 text-right">Remaining</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($calcRows as $row)
                            @if ($calcSerial === '' || '#' . $calcSerial === $row['serial'])
                                <tr class="hover:bg-gray-50/70">
                                    <td class="px-4 py-3 font-semibold text-gray-800">{{ $row['serial'] }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $row['due_date'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ $row['percent'] === null ? '—' : rtrim(rtrim(number_format($row['percent'], 2), '0'), '.') . '%' }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-emerald-700">₹{{ number_format($row['amount'], 2) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-800">₹{{ number_format($row['class_total'], 2) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">₹{{ number_format($row['collected'], 2) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">₹{{ number_format($row['remaining'], 2) }}</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif

{{-- Add / Edit installment slide-in --}}
@if ($cycleModalOpen)
    <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeCycleModal"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-md bg-white shadow-2xl flex flex-col">
            @php
                $cycleTitle = $editCycleId
                    ? 'Edit Installment'
                    : ($cycleMode === '' ? 'Add Fee Cycle'
                        : ($cycleMode === 'monthly' ? 'Monthly Fee Cycle'
                            : ($cycleMode === 'quarterly' ? 'Quarterly Fee Cycle' : 'Custom Installment')));
            @endphp
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $cycleTitle }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        @if (!$editCycleId && $cycleMode === '')
                            Choose how you want to build the fee cycle
                        @else
                            The rupee amount is computed per class from its own fee
                        @endif
                    </p>
                </div>
                <button wire:click="closeCycleModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">

                {{-- ── Cycle type selector (add mode only — editing is always a single custom row) ── --}}
                @if (!$editCycleId)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Cycle Type <span class="text-red-500">*</span></label>
                        <select wire:model.live="cycleMode" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">Select…</option>
                            <option value="monthly">Monthly — split into 12 equal installments</option>
                            <option value="quarterly">Quarterly — split into 4 equal installments</option>
                            <option value="custom">Custom — add one installment yourself</option>
                        </select>
                    </div>
                @endif

                @if ($editCycleId || $cycleMode !== '')

                {{-- Previously-added installments (for the chosen fee type & year) --}}
                @php $prevInstallments = $cycleExisting->where('id', '!=', $editCycleId)->where('is_token', false); @endphp
                @if ($prevInstallments->isNotEmpty())
                    <div class="bg-emerald-50 border border-emerald-100 rounded-lg px-4 py-3">
                        <p class="text-xs font-semibold text-emerald-700 mb-2">Installments already added ({{ ucfirst($cycleFeeType) }} · {{ $cycleYear }})</p>
                        <div class="space-y-1">
                            @foreach ($prevInstallments as $pi)
                                <div class="flex items-center justify-between text-xs text-emerald-800">
                                    <span class="font-medium">Installment {{ $pi->payment_serial }}</span>
                                    <span>{{ rtrim(rtrim(number_format($pi->fee_percent, 2), '0'), '.') }}% · due {{ optional($pi->due_date)->format('d M Y') ?? '—' }}</span>
                                </div>
                            @endforeach
                            <div class="flex items-center justify-between text-xs font-semibold text-emerald-900 pt-1 mt-1 border-t border-emerald-200">
                                <span>Total allocated</span>
                                <span>{{ rtrim(rtrim(number_format($prevInstallments->sum('fee_percent'), 2), '0'), '.') }}%</span>
                            </div>
                        </div>
                        @if (in_array($cycleMode, ['monthly', 'quarterly'], true))
                            <p class="text-[11px] text-emerald-500 mt-2">These will be replaced by the {{ $cycleMode }} split.</p>
                        @endif
                    </div>
                @endif

                {{-- Fee type + academic year (all modes) --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Fee Type</label>
                        <select wire:model.live="cycleFeeType" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="academic">Academic</option>
                            <option value="transport">Transport</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Academic Year</label>
                        <input type="text" wire:model.live="cycleYear" placeholder="2026-27" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                </div>

                {{-- ── MONTHLY ── --}}
                @if ($cycleMode === 'monthly')
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Due day of month <span class="text-red-500">*</span></label>
                            <select wire:model="cycleMonthlyDueDay" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                                @for ($d = 1; $d <= 28; $d++)<option value="{{ $d }}">{{ $d }}</option>@endfor
                            </select>
                            @error('cycleMonthlyDueDay')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Penalty / Day (₹)</label>
                            <input type="number" step="0.01" min="0" wire:model="cyclePenaltyPerDay" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                    </div>
                    <div class="bg-emerald-50 border border-emerald-100 rounded-lg px-4 py-3 text-xs text-emerald-700">
                        Creates <strong>12 installments</strong> (Apr → Mar), each ≈ <strong>8.33%</strong> of the fee, due on day <strong>{{ $cycleMonthlyDueDay }}</strong> of each month.
                    </div>

                {{-- ── QUARTERLY ── --}}
                @elseif ($cycleMode === 'quarterly')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Penalty / Day (₹)</label>
                        <input type="number" step="0.01" min="0" wire:model="cyclePenaltyPerDay" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    <div class="bg-emerald-50 border border-emerald-100 rounded-lg px-4 py-3 text-xs text-emerald-700 space-y-0.5">
                        <p>Creates <strong>4 installments</strong> of <strong>25%</strong> each, due on the last day of each quarter:</p>
                        <p class="text-emerald-600">Q1 Apr–Jun · Q2 Jul–Sep · Q3 Oct–Dec · Q4 Jan–Mar</p>
                    </div>

                {{-- ── CUSTOM ── --}}
                @else
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Installment No. <span class="text-red-500">*</span></label>
                            <select wire:model="cycleSerial" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                                @for ($i = 1; $i <= 12; $i++)<option value="{{ $i }}">Installment {{ $i }}</option>@endfor
                            </select>
                            @error('cycleSerial')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Due Date <span class="text-red-500">*</span></label>
                            <input type="date" wire:model="cycleDueDate" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                            @error('cycleDueDate')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Fee % to Collect <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" min="0" max="100" wire:model="cycleFeePercent" placeholder="e.g. 25" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                            @error('cycleFeePercent')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Penalty / Day (₹)</label>
                            <input type="number" step="0.01" min="0" wire:model="cyclePenaltyPerDay" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                    </div>
                @endif

                {{-- ── TOKEN FEE — a one-time up-front charge, optional in every mode ── --}}
                <div class="border border-gray-200 rounded-lg p-4 space-y-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-700">Token Fee <span class="text-xs font-normal text-gray-400">(optional, one-time)</span></p>
                        <p class="text-xs text-gray-500 mt-0.5">Collected once, up front — the installments above then split whatever is left of the fee.</p>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">Amount (₹)</label>
                            <input type="number" step="0.01" min="0" wire:model="tokenFeeAmount" placeholder="e.g. 500"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">Due Date</label>
                            <input type="date" wire:model="tokenDueDate"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">Penalty / Day (₹)</label>
                            <input type="number" step="0.01" min="0" wire:model="tokenPenaltyPerDay"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                    </div>
                </div>

                @endif
            </div>
            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                <button wire:click="closeCycleModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                @if ($editCycleId || $cycleMode !== '')
                    <button wire:click="saveCycle" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveCycle">{{ $editCycleId ? 'Update Installment' : ($cycleMode === 'custom' ? 'Add Installment' : 'Generate Cycle') }}</span>
                        <span wire:loading wire:target="saveCycle">Saving…</span>
                    </button>
                @endif
            </div>
        </div>
    </div>
@endif

{{-- Delete confirm --}}
@if ($pendingDeleteCycleId !== null)
    <div class="fixed inset-x-0 bottom-0 top-16 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDeleteCycle"></div>
        <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-base font-semibold text-gray-900 mb-1">Delete installment?</h3>
                    <p class="text-sm text-gray-500">This removes the installment from the fee cycle. This cannot be undone.</p>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 mt-5">
                <button wire:click="cancelDeleteCycle" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                <button wire:click="doDeleteCycle" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">Delete</button>
            </div>
        </div>
    </div>
@endif
