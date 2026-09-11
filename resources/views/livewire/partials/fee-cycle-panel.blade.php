{{-- ══════════════════════════════════════════════════════════════════
     FEE CYCLE + CALCULATOR — shared by Accounts\FeeCycles and the "Cycle"
     tab of Admin\Fee. Both hosts provide the same variables via
     App\Livewire\Concerns\HandlesFeeCycles::feeCycleViewData(), plus
     $standards. The tabs + filter row live in fee-cycle-header.blade.php,
     included by each host inside its own sticky header — this file is the
     body only (listing, calculator, modal, view panel). Edit either file
     and both pages update together.
══════════════════════════════════════════════════════════════════ --}}

@if ($cycleTab === 'cycle')
    {{-- ══════════ ONE CARD PER CYCLE — fee type + academic year ══════════
         Each card carries its own header: what the cycle is, what it adds up
         to, and Edit (the whole cycle at once) / Download / Print.
    ══════════════════════════════════════════════════════════════════════ --}}
    @forelse ($cycleGroups as $group)
        @php
            $kindLabel = ['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'custom' => 'Custom'][$group['kind']] ?? 'Custom';
            $kindClass = ['monthly' => 'bg-indigo-100 text-indigo-700', 'quarterly' => 'bg-violet-100 text-violet-700', 'custom' => 'bg-gray-100 text-gray-600'][$group['kind']] ?? 'bg-gray-100 text-gray-600';
            $balanced  = abs($group['percent'] - 100) < 0.01;
            $groupName = ucfirst($group['fee_type']) . ' Fee Cycle — ' . $kindLabel . ' · ' . $group['year'];
            $groupSub  = $group['count'] . ' installment' . ($group['count'] === 1 ? '' : 's')
                . ' · totals ' . rtrim(rtrim(number_format($group['percent'], 2), '0'), '.') . '%'
                . ($group['token'] ? ' · token fee ₹' . number_format($group['token']->amount, 2) : '');
        @endphp
        {{-- Print hands a bare copy of this card's table to a new window — the
             page's own Tailwind classes mean nothing there, so it carries its
             own stylesheet along. Alpine (not a <script> tag) so the button
             still works after a Livewire tab morph re-inserts the card. --}}
        <div wire:key="{{ $group['key'] }}"
            x-data="{
                printCycle() {
                    const body = this.$refs.printable;
                    const win = window.open('', '_blank', 'width=1000,height=700');
                    if (! win) { alert('Allow pop-ups for this site to print the fee cycle.'); return; }
                    const css = 'body{font-family:Arial,Helvetica,sans-serif;color:#111;margin:24px}'
                        + 'h1{font-size:15px;margin:0 0 3px}'
                        + 'h2{font-size:11px;color:#555;font-weight:400;margin:0 0 14px}'
                        + 'table{width:100%;border-collapse:collapse}'
                        + 'th,td{border:1px solid #d5d5d5;padding:6px 8px;font-size:11px;text-align:left}'
                        + 'th{background:#f3f4f6;text-transform:uppercase;font-size:10px}'
                        + '.cycle-actions{display:none}';
                    win.document.write('<html><head><title>' + body.dataset.title + '</title><style>' + css + '</style></head><body>'
                        + '<h1>' + body.dataset.title + '</h1><h2>' + body.dataset.sub + '</h2>'
                        + body.innerHTML + '</body></html>');
                    win.document.close();
                    win.focus();
                    setTimeout(() => { win.print(); win.close(); }, 350);
                }
            }"
            class="bg-white rounded-xl border border-gray-200 overflow-hidden">

            {{-- ── Listing header ── --}}
            <div class="flex flex-wrap items-center justify-between gap-3 px-4 sm:px-5 py-3 border-b border-gray-200 bg-gray-50/70">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-sm font-bold text-gray-900 capitalize">{{ $group['fee_type'] }} Fee</h3>
                        <span class="px-2 py-0.5 rounded text-[11px] font-semibold {{ $kindClass }}">{{ $kindLabel }}</span>
                        <span class="text-xs text-gray-500">{{ $group['year'] }}</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ $group['count'] }} installment{{ $group['count'] === 1 ? '' : 's' }} · totals
                        <strong class="{{ $balanced ? 'text-emerald-600' : 'text-amber-600' }}">{{ rtrim(rtrim(number_format($group['percent'], 2), '0'), '.') }}%</strong>
                        @if ($group['token'])
                            · token fee <strong class="text-gray-700">₹{{ number_format($group['token']->amount, 2) }}</strong>
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button wire:click="openCycleEdit('{{ $group['fee_type'] }}', '{{ $group['year'] }}')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-xs font-semibold text-gray-700 hover:bg-amber-50 hover:text-amber-700 hover:border-amber-300" title="Edit the whole cycle">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        Edit
                    </button>
                    <button wire:click="downloadCycle('{{ $group['fee_type'] }}', '{{ $group['year'] }}')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-xs font-semibold text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-300" title="Download as CSV">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-5l-4 4m0 0l-4-4m4 4V4" /></svg>
                        Download
                    </button>
                    <button type="button" @click="printCycle()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-xs font-semibold text-gray-700 hover:bg-blue-50 hover:text-blue-700 hover:border-blue-300" title="Print this cycle">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                        Print
                    </button>
                </div>
            </div>

            {{-- ── Installments table (also what the Print button hands the printer) ── --}}
            <div class="overflow-x-auto" x-ref="printable" data-title="{{ $groupName }}" data-sub="{{ $groupSub }}">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Installment</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Fee Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Due Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Period</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Fee %</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Penalty/Day</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Year</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-24 cycle-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($group['rows'] as $cy)
                            <tr wire:key="cycle-{{ $cy->id }}" class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-semibold text-gray-800">
                                    {{ $this->cycleRowLabel($cy, $group['kind']) }}
                                    @unless ($cy->is_token)
                                        <span class="text-gray-400 font-normal text-xs">#{{ $cy->payment_serial }}</span>
                                    @endunless
                                </td>
                                <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-[11px] {{ $cy->fee_type === 'academic' ? 'bg-emerald-100 text-emerald-700' : 'bg-teal-100 text-teal-700' }} capitalize">{{ $cy->fee_type }}</span></td>
                                <td class="px-4 py-3 text-gray-600">{{ optional($cy->due_date)->format('d M Y') ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                                    @if ($cy->start_date && $cy->end_date)
                                        {{ $cy->start_date->format('d M Y') }} – {{ $cy->end_date->format('d M Y') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ $cy->is_token ? '—' : rtrim(rtrim(number_format($cy->fee_percent, 2), '0'), '.') . '%' }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ $cy->is_token ? '₹' . number_format($cy->amount, 2) : '—' }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">₹{{ number_format($cy->penalty_per_day, 2) }}</td>
                                <td class="px-4 py-3 text-center text-gray-500">{{ $cy->academic_year }}</td>
                                <td class="px-4 py-3 cycle-actions">
                                    {{-- View only — Edit and Delete for one row live in the view card's own header. --}}
                                    <div class="flex items-center justify-center">
                                        <button wire:click="viewCycle({{ $cy->id }})" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md border border-gray-200 text-xs font-medium text-gray-600 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200" title="View">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            View
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-16 text-center">
            <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
            </div>
            <p class="text-sm font-semibold text-gray-800">No installments yet</p>
            <p class="text-xs text-gray-400 mt-1">Click "Add Fee Cycle" to define the fee cycle.</p>
        </div>
    @endforelse

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
                                    <td class="px-4 py-3 font-semibold text-gray-800">
                                        {{ $row['serial'] }}
                                        @if ($row['span'])
                                            <span class="text-gray-400 font-normal">({{ $row['span'] }})</span>
                                        @endif
                                    </td>
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
                $cycleTitle = $editingToken
                    ? 'Edit Token Fee'
                    : ($editCycleId
                        ? 'Edit Installment'
                        : ($cycleMode === '' ? 'Add Fee Cycle'
                            : ($cycleMode === 'monthly' ? 'Monthly Fee Cycle'
                                : ($cycleMode === 'quarterly' ? 'Quarterly Fee Cycle' : 'Custom Installment'))));
            @endphp
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $cycleTitle }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        @if ($editingToken)
                            The one-time up-front charge, collected before installments
                        @elseif (!$editCycleId && $cycleMode === '')
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

                @if ($editingToken)
                    {{-- ── Editing the token fee only ── --}}
                    <div class="grid grid-cols-1 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Amount (₹) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" min="0" wire:model="tokenFeeAmount" placeholder="e.g. 500"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Due Date <span class="text-red-500">*</span></label>
                                <input type="date" wire:model="tokenDueDate"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Penalty / Day (₹)</label>
                                <input type="number" step="0.01" min="0" wire:model="tokenPenaltyPerDay"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                            </div>
                        </div>
                    </div>
                @else

                {{-- ── Cycle type selector (add mode only — editing is always a single custom row) ── --}}
                @if (!$editCycleId)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Cycle Type <span class="text-red-500">*</span></label>
                        <select wire:model.live="cycleMode" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                            <option value="">Select…</option>
                            <option value="monthly">Monthly — split into 12 equal installments</option>
                            <option value="quarterly">Quarterly — split into 4 equal installments</option>
                            <option value="custom">Custom — add one installment yourself</option>
                        </select>
                    </div>
                @endif

                @if ($editCycleId || $cycleMode !== '')

                <p class="text-xs text-gray-400">{{ ucfirst($cycleFeeType) }} fee · Academic year {{ $cycleYear }}</p>

                {{-- ── MONTHLY ── --}}
                @if ($cycleMode === 'monthly')
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Due day of month <span class="text-red-500">*</span></label>
                            <select wire:model="cycleMonthlyDueDay" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                @for ($d = 1; $d <= 28; $d++)<option value="{{ $d }}">{{ $d }}</option>@endfor
                            </select>
                            @error('cycleMonthlyDueDay')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Penalty / Day (₹)</label>
                            <input type="number" step="0.01" min="0" wire:model="cyclePenaltyPerDay" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        </div>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 text-xs text-gray-600">
                        Creates <strong class="text-gray-800">12 installments</strong> (Apr → Mar), each ≈ <strong class="text-gray-800">8.33%</strong> of the fee, due on day <strong class="text-gray-800">{{ $cycleMonthlyDueDay }}</strong> of each month.
                    </div>

                {{-- ── QUARTERLY ── --}}
                @elseif ($cycleMode === 'quarterly')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Penalty / Day (₹)</label>
                        <input type="number" step="0.01" min="0" wire:model="cyclePenaltyPerDay" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 text-xs text-gray-600 space-y-0.5">
                        <p>Creates <strong class="text-gray-800">4 installments</strong> of <strong class="text-gray-800">25%</strong> each, due on the last day of each quarter:</p>
                        <p class="text-gray-500">Q1 Apr–Jun · Q2 Jul–Sep · Q3 Oct–Dec · Q4 Jan–Mar</p>
                    </div>

                {{-- ── CUSTOM ── --}}
                @elseif ($editCycleId)
                    {{-- Editing one existing installment --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Installment No. <span class="text-red-500">*</span></label>
                            <select wire:model="cycleSerial" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                @for ($i = 1; $i <= 12; $i++)<option value="{{ $i }}">Installment {{ $i }}</option>@endfor
                            </select>
                            @error('cycleSerial')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Due Date <span class="text-red-500">*</span></label>
                            <input type="date" wire:model="cycleDueDate" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                            @error('cycleDueDate')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    {{-- The stretch of the year this installment covers — what the listing prints as its period --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Start Date</label>
                            <input type="date" wire:model="cycleStartDate" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                            @error('cycleStartDate')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">End Date</label>
                            <input type="date" wire:model="cycleEndDate" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                            @error('cycleEndDate')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Fee % to Collect <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" min="0" max="100" wire:model.live.debounce.600ms="cycleFeePercent" placeholder="e.g. 25" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                            @error('cycleFeePercent')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Penalty / Day (₹)</label>
                            <input type="number" step="0.01" min="0" wire:model="cyclePenaltyPerDay" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        </div>
                    </div>
                    {{-- Changing this one's % re-splits the rest so the year still totals 100% --}}
                    @if (count($cycleSiblingPreview))
                        <div class="bg-blue-50 border border-blue-100 rounded-lg px-4 py-3">
                            <p class="text-xs font-semibold text-blue-800">The other installments will be re-balanced</p>
                            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1">
                                @foreach ($cycleSiblingPreview as $sibling)
                                    <span class="text-xs text-blue-700">
                                        #{{ $sibling['serial'] }}:
                                        <span class="text-blue-400 line-through">{{ rtrim(rtrim(number_format($sibling['was'], 2), '0'), '.') }}%</span>
                                        <strong>{{ rtrim(rtrim(number_format($sibling['percent'], 2), '0'), '.') }}%</strong>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @else
                    {{-- Adding fresh — any number of installments at once --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">Installments</label>
                            @php $pctTotal = $this->customPercentTotal; @endphp
                            <span class="text-xs {{ abs($pctTotal - 100) < 0.01 ? 'text-emerald-600' : 'text-amber-600' }}">
                                {{ count($customRows) }} row{{ count($customRows) === 1 ? '' : 's' }} · {{ rtrim(rtrim(number_format($pctTotal, 2), '0'), '.') }}%
                            </span>
                        </div>
                        <div class="border border-gray-200 rounded-lg overflow-x-auto">
                            <table class="w-full min-w-[620px]">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">
                                        <th class="px-2 py-2 text-left w-20">No.</th>
                                        <th class="px-2 py-2 text-left">Start Date</th>
                                        <th class="px-2 py-2 text-left">End Date</th>
                                        <th class="px-2 py-2 text-left">Due Date</th>
                                        <th class="px-2 py-2 text-left">Fee %</th>
                                        <th class="px-2 py-2 text-left">Penalty/Day</th>
                                        <th class="px-2 py-2 w-8"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($customRows as $i => $row)
                                        <tr wire:key="custom-row-{{ $i }}">
                                            <td class="px-2 py-2">
                                                <select wire:model="customRows.{{ $i }}.serial" class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md bg-white focus:ring-1 focus:ring-gray-400">
                                                    @for ($n = 1; $n <= 12; $n++)<option value="{{ $n }}">#{{ $n }}</option>@endfor
                                                </select>
                                            </td>
                                            <td class="px-2 py-2">
                                                <input type="date" wire:model="customRows.{{ $i }}.start_date" class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-1 focus:ring-gray-400">
                                            </td>
                                            <td class="px-2 py-2">
                                                <input type="date" wire:model="customRows.{{ $i }}.end_date" class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-1 focus:ring-gray-400">
                                            </td>
                                            <td class="px-2 py-2">
                                                <input type="date" wire:model="customRows.{{ $i }}.due_date" class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-1 focus:ring-gray-400">
                                            </td>
                                            <td class="px-2 py-2">
                                                <input type="number" step="0.01" min="0" max="100" wire:model.live.debounce.600ms="customRows.{{ $i }}.fee_percent" placeholder="e.g. 25"
                                                    class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-1 focus:ring-gray-400">
                                            </td>
                                            <td class="px-2 py-2">
                                                <input type="number" step="0.01" min="0" wire:model="customRows.{{ $i }}.penalty_per_day" class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-1 focus:ring-gray-400">
                                            </td>
                                            <td class="px-2 py-2 text-center">
                                                <button type="button" wire:click="removeCustomRow({{ $i }})" class="p-1 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded" title="Remove row">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="mt-1.5 text-xs text-gray-400">Change one row's % and the difference is shared out equally across the others — they keep their own numbers, and the set stays at 100%.</p>
                        <button type="button" wire:click="addCustomRow"
                            class="mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                            Add Row
                        </button>
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
                                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">Due Date</label>
                            <input type="date" wire:model="tokenDueDate"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">Penalty / Day (₹)</label>
                            <input type="number" step="0.01" min="0" wire:model="tokenPenaltyPerDay"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        </div>
                    </div>
                </div>

                @endif
                @endif
            </div>
            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                <button wire:click="closeCycleModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                @if ($editingToken || $editCycleId || $cycleMode !== '')
                    <button wire:click="saveCycle" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveCycle">{{ $editingToken ? 'Update Token Fee' : ($editCycleId ? 'Update Installment' : ($cycleMode === 'custom' ? 'Save Installments' : 'Generate Cycle')) }}</span>
                        <span wire:loading wire:target="saveCycle">Saving…</span>
                    </button>
                @endif
            </div>
        </div>
    </div>
@endif

{{-- ══════════ EDIT THE WHOLE CYCLE — every installment at once ══════════
     Opened from a listing card's Edit button. Each row is labelled the way
     the cycle was built (month name / quarter / installment no.), and its
     dates and numbers are editable. Change one row's % and the difference is
     shared out equally across the others — each keeps its own number, so
     20/17/33/30 with the 17 raised to 20 becomes 19/20/32/29.
══════════════════════════════════════════════════════════════════════ --}}
@if ($cycleEditOpen)
    @php
        $editKindLabel = ['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'custom' => 'Custom'][$editCycleKind] ?? 'Custom';
        $editTotal     = $this->editPercentTotal;
        $editBalanced  = abs($editTotal - 100) < 0.01;
        $editColHead   = $editCycleKind === 'monthly' ? 'Month' : ($editCycleKind === 'quarterly' ? 'Quarter' : 'Installment');
    @endphp
    <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeCycleEdit"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-900 truncate">Edit {{ $editKindLabel }} Fee Cycle</h2>
                    <p class="text-xs text-gray-500 mt-0.5">{{ ucfirst($editCycleFeeType) }} fee · Academic year {{ $editCycleYear }}</p>
                </div>
                <button wire:click="closeCycleEdit" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs text-gray-500">
                        Change any % and the difference is shared out equally across the other rows — they keep their own numbers, and the cycle stays at 100%.
                    </p>
                    <button type="button" wire:click="resetEditPercents"
                        class="flex-shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-semibold text-gray-600 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        Equal split
                    </button>
                </div>

                <div class="border border-gray-200 rounded-lg overflow-x-auto">
                    <table class="w-full min-w-[860px]">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">
                                <th class="px-3 py-2 text-left">{{ $editColHead }}</th>
                                <th class="px-2 py-2 text-left w-36">Start Date</th>
                                <th class="px-2 py-2 text-left w-36">End Date</th>
                                <th class="px-2 py-2 text-left w-36">Due Date</th>
                                <th class="px-2 py-2 text-left w-24">Fee %</th>
                                <th class="px-2 py-2 text-left w-28">Penalty/Day</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($editRows as $i => $row)
                                <tr wire:key="edit-cycle-row-{{ $row['id'] }}">
                                    <td class="px-3 py-2">
                                        <p class="text-xs font-semibold text-gray-800">{{ $row['label'] }}</p>
                                        <p class="text-[11px] text-gray-400">Installment #{{ $row['serial'] }}</p>
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="date" wire:model="editRows.{{ $i }}.start_date"
                                            class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-1 focus:ring-gray-400">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="date" wire:model="editRows.{{ $i }}.end_date"
                                            class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-1 focus:ring-gray-400">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="date" wire:model="editRows.{{ $i }}.due_date"
                                            class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-1 focus:ring-gray-400">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="number" step="0.01" min="0" max="100" wire:model.live.debounce.600ms="editRows.{{ $i }}.fee_percent"
                                            class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-1 focus:ring-gray-400">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="number" step="0.01" min="0" wire:model="editRows.{{ $i }}.penalty_per_day"
                                            class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-1 focus:ring-gray-400">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between rounded-lg border px-4 py-3 {{ $editBalanced ? 'bg-emerald-50 border-emerald-100' : 'bg-amber-50 border-amber-100' }}">
                    <span class="text-xs font-medium {{ $editBalanced ? 'text-emerald-700' : 'text-amber-700' }}">
                        {{ count($editRows) }} installment{{ count($editRows) === 1 ? '' : 's' }} · total
                    </span>
                    <span class="text-sm font-bold {{ $editBalanced ? 'text-emerald-700' : 'text-amber-700' }}">
                        {{ rtrim(rtrim(number_format($editTotal, 2), '0'), '.') }}%
                    </span>
                </div>

                @unless ($editBalanced)
                    <p class="text-xs text-amber-600">These don't add up to 100% — press "Equal split", or retype any one % to pull the rest back into line.</p>
                @endunless
            </div>

            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                <button wire:click="closeCycleEdit" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                <button wire:click="saveCycleEdit" wire:loading.attr="disabled"
                    class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                    <span wire:loading.remove wire:target="saveCycleEdit">Save Cycle</span>
                    <span wire:loading wire:target="saveCycleEdit">Saving…</span>
                </button>
            </div>
        </div>
    </div>
@endif

{{-- View installment / token — plain, read-only --}}
@if ($viewingCycle)
    <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeCycleView"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-md bg-white shadow-2xl flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-900 truncate">
                        {{ $viewingCycle->is_token ? 'Token Fee' : 'Installment #' . $viewingCycle->payment_serial }}
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">{{ ucfirst($viewingCycle->fee_type) }} · {{ $viewingCycle->academic_year }}</p>
                </div>
                {{-- Edit / Delete for this row live here, not in the listing --}}
                <div class="flex items-center gap-1.5 flex-shrink-0">
                    <button wire:click="editViewingCycle" class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-amber-50 hover:text-amber-600 hover:border-amber-200" title="Edit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    </button>
                    <button wire:click="deleteViewingCycle" class="p-1.5 rounded-md border border-red-200 text-red-500 hover:bg-red-50" title="Delete">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                    <span class="w-px h-5 bg-gray-200 mx-0.5"></span>
                    <button wire:click="closeCycleView" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100" title="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                @foreach ([
                    'Due Date'    => optional($viewingCycle->due_date)->format('d M Y') ?? 'N/A',
                    'Fee %'       => $viewingCycle->is_token ? 'N/A' : rtrim(rtrim(number_format($viewingCycle->fee_percent, 2), '0'), '.') . '%',
                    'Amount'      => $viewingCycle->is_token ? '₹' . number_format($viewingCycle->amount, 2) : 'N/A',
                    'Penalty / Day' => '₹' . number_format($viewingCycle->penalty_per_day, 2),
                    'Period'      => ($viewingCycle->start_date && $viewingCycle->end_date)
                        ? $viewingCycle->start_date->format('d M Y') . ' – ' . $viewingCycle->end_date->format('d M Y')
                        : 'N/A',
                    'Status'      => $viewingCycle->is_active ? 'Active' : 'Inactive',
                ] as $label => $value)
                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <span class="text-xs text-gray-400 uppercase tracking-wider">{{ $label }}</span>
                        <span class="col-span-2 text-gray-800 font-medium">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                <button wire:click="closeCycleView" class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
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
