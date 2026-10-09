{{-- ══════════════════════════════════════════════════════════════════
     VIEW FEE — one student's ledger, read top to bottom:
       1. who they are, with the collection lines beside them
       2. what the fee is made of, academic and transport, net of concession
       3. the school's fee cycle, and which installments are cleared
       4. every payment, full width, each with its slip

     In the Mark Attendance look: each part a plain card with a heading strip
     (its name, a small line under it, the counts on the right) and flat rows
     under hairline rules — plain text throughout, no labels in capitals, no
     chips, no bars.

     Fed by App\Livewire\Concerns\HandlesStudentFeeView::buildStudentFeeView()
     as $sv, plus $feePrefix ('admin' | 'accounts') and $feeOrg for the
     receipt links. Shared by Admin\Fee's View & Submit Fee tab and the
     accounts View Fee / Fee Submission pages.
══════════════════════════════════════════════════════════════════ --}}

@php
    $stu    = $sv['student'];
    $tot    = $sv['totals'];
    $sides  = ['Academic' => $sv['academic']];
    if ($sv['hasTransport']) {
        $sides['Transport'] = $sv['transport'];
    }
    // Transport receipts are printed by their own controller.
    $receipt = fn (array $p) => route(
        $feePrefix . ($p['kind'] === 'transport' ? '.transport.receipt' : '.fee.receipt'),
        ['organization' => $feeOrg, 'id' => $p['id']]
    );
    $money = fn ($v) => '₹' . number_format((float) $v, 2);
@endphp

{{-- ══════════ 1. STUDENT + COLLECTION LINES ══════════ --}}
<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 text-[11px] font-medium flex-shrink-0">
                {{ $stu['initial'] }}
            </div>
            <div class="min-w-0">
                <h3 class="text-sm font-semibold text-gray-700 truncate">{{ $stu['name'] }}</h3>
                <p class="text-[11px] text-gray-400">{{ $stu['class_section'] }} · Adm {{ $stu['admission_no'] }} · Roll {{ $stu['roll_no'] }}</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-gray-600">
            <span>Total <strong>{{ $money($tot['net']) }}</strong></span>
            <span>Paid <strong>{{ $money($tot['paid']) }}</strong></span>
            <span>Remaining <strong class="{{ $tot['remaining'] > 0 ? 'text-red-600' : '' }}">{{ $money($tot['remaining']) }}</strong></span>
        </div>
    </div>

    {{-- Details, as flat label rows --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 sm:divide-x divide-gray-100 border-b border-gray-100">
        @foreach ([
            ["Father's name" => $stu['father_name'], "Mother's name" => $stu['mother_name'], 'Phone' => $stu['phone']],
            ['Admission no.' => $stu['admission_no'], 'Roll no.' => $stu['roll_no'], 'Class / section' => $stu['class_section']],
        ] as $col => $pairs)
            <div wire:key="sv-detail-col-{{ $col }}" class="divide-y divide-gray-100">
                @foreach ($pairs as $label => $value)
                    <div class="flex items-center gap-3 px-4 py-2.5 text-sm">
                        <span class="w-28 flex-shrink-0 text-xs text-gray-500">{{ $label }}</span>
                        <span class="flex-1 text-gray-800 truncate">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    {{-- Collection — overall, then each side --}}
    @php
        $lines = ['Overall' => [
            'net' => $tot['net'], 'paid' => $tot['paid'], 'remaining' => $tot['remaining'], 'pct' => $tot['pct'],
        ]] + $sides;
    @endphp
    <div class="overflow-x-auto">
        <div class="min-w-[520px] text-sm">
            <div class="grid grid-cols-[minmax(0,1fr)_7rem_7rem_7rem_4rem] items-center bg-gray-50 text-xs text-gray-500">
                <span class="px-4 py-2">Collection</span>
                <span class="px-4 py-2 text-right">Paid</span>
                <span class="px-4 py-2 text-right">Remaining</span>
                <span class="px-4 py-2 text-right">Total</span>
                <span class="px-4 py-2 text-right">Done</span>
            </div>
            @foreach ($lines as $label => $line)
                <div wire:key="sv-line-{{ \Illuminate\Support\Str::slug($label) }}" class="grid grid-cols-[minmax(0,1fr)_7rem_7rem_7rem_4rem] items-center border-t border-gray-100">
                    <span class="px-4 py-2.5 text-gray-800">{{ $label }}</span>
                    <span class="px-4 py-2.5 text-right text-gray-700 tabular-nums">{{ $money($line['paid']) }}</span>
                    <span class="px-4 py-2.5 text-right tabular-nums {{ $line['remaining'] > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ $money($line['remaining']) }}</span>
                    <span class="px-4 py-2.5 text-right text-gray-700 tabular-nums">{{ $money($line['net']) }}</span>
                    <span class="px-4 py-2.5 text-right text-gray-500 tabular-nums">{{ $line['pct'] }}%</span>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ══════════ 2. FEE STRUCTURE — academic and transport, net of concession ══════════ --}}
<div class="grid grid-cols-1 {{ $sv['hasTransport'] ? 'lg:grid-cols-2' : '' }} gap-4">
    @foreach ($sides as $label => $side)
        @php
            // The student's own rows (Last Year Dues, earlier years' fees) read apart.
            $feeRows = array_values(array_filter($side['rows'], fn ($r) => empty($r['is_dues'])));
            $dueRows = array_values(array_filter($side['rows'], fn ($r) => !empty($r['is_dues'])));
        @endphp
        <div wire:key="sv-side-{{ \Illuminate\Support\Str::slug($label) }}" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="text-sm font-semibold text-gray-700">{{ $label }} fee</h3>
                    <p class="text-[11px] text-gray-400">{{ count($side['rows']) }} head{{ count($side['rows']) === 1 ? '' : 's' }}</p>
                </div>
                <span class="text-[11px] text-gray-600">Payable <strong>{{ $money($side['net']) }}</strong></span>
            </div>

            <div class="divide-y divide-gray-100 text-sm">
                @if (count($side['rows']) === 0)
                    <p class="px-4 py-6 text-center text-xs text-gray-400">No {{ strtolower($label) }} fee set for this class.</p>
                @elseif (count($dueRows))
                    {{-- The class's own heads and their total, then the past years' dues
                         and theirs, then both together. --}}
                    @foreach ($feeRows as $n => $r)
                        <div class="flex items-center gap-3 px-4 py-2.5">
                            <span class="w-4 text-[11px] text-gray-300 tabular-nums flex-shrink-0">{{ $n + 1 }}</span>
                            <span class="flex-1 text-gray-800 truncate">{{ $r['fee_name'] }}</span>
                            <span class="text-gray-700 tabular-nums">{{ $money($r['amount']) }}</span>
                        </div>
                    @endforeach
                    @if (!count($feeRows))
                        <p class="px-4 py-2.5 text-xs text-gray-400">No {{ strtolower($label) }} fee set for this class.</p>
                    @endif
                    <div class="flex items-center gap-3 px-4 py-2.5 bg-gray-50/60">
                        <span class="flex-1 text-gray-700">{{ $label }} total</span>
                        <span class="text-gray-800 font-medium tabular-nums">{{ $money(array_sum(array_column($feeRows, 'amount'))) }}</span>
                    </div>

                    <p class="px-4 py-2 text-xs text-gray-500">Past years' dues</p>
                    @foreach ($dueRows as $n => $r)
                        <div class="flex items-center gap-3 px-4 py-2.5">
                            <span class="w-4 text-[11px] text-gray-300 tabular-nums flex-shrink-0">{{ $n + 1 }}</span>
                            <span class="flex-1 text-gray-800 truncate">{{ $r['fee_name'] }}</span>
                            <span class="text-gray-700 tabular-nums">{{ $money($r['amount']) }}</span>
                        </div>
                    @endforeach
                    <div class="flex items-center gap-3 px-4 py-2.5 bg-gray-50/60">
                        <span class="flex-1 text-gray-700">Dues total</span>
                        <span class="text-gray-800 font-medium tabular-nums">{{ $money(array_sum(array_column($dueRows, 'amount'))) }}</span>
                    </div>

                    <div class="flex items-center gap-3 px-4 py-2.5">
                        <span class="flex-1 text-gray-800 font-medium">Overall total</span>
                        <span class="text-gray-900 font-semibold tabular-nums">{{ $money($side['gross']) }}</span>
                    </div>
                @else
                    @foreach ($side['rows'] as $n => $r)
                        <div class="flex items-center gap-3 px-4 py-2.5">
                            <span class="w-4 text-[11px] text-gray-300 tabular-nums flex-shrink-0">{{ $n + 1 }}</span>
                            <span class="flex-1 text-gray-800 truncate">{{ $r['fee_name'] }}</span>
                            <span class="text-gray-700 tabular-nums">{{ $money($r['amount']) }}</span>
                        </div>
                    @endforeach
                @endif

                @if ($side['concession'] > 0)
                    <div class="flex items-center gap-3 px-4 py-2.5">
                        <span class="flex-1 text-gray-500">Fee</span>
                        <span class="text-gray-400 line-through tabular-nums">{{ $money($side['gross']) }}</span>
                    </div>
                    <div class="flex items-center gap-3 px-4 py-2.5">
                        <span class="flex-1 text-gray-500">Concession</span>
                        <span class="text-emerald-600 tabular-nums">− {{ $money($side['concession']) }}</span>
                    </div>
                @endif
                @if (count($side['rows']) || $side['concession'] > 0)
                    <div class="flex items-center gap-3 px-4 py-2.5">
                        <span class="flex-1 text-gray-800 font-medium">Payable</span>
                        <span class="text-gray-900 font-semibold tabular-nums">{{ $money($side['net']) }}</span>
                    </div>
                @endif

                {{-- Where the transport fee comes from, and what the concession was --}}
                @if (!empty($side['route']))
                    <p class="px-4 py-2.5 text-xs text-gray-500">
                        {{ $side['route']['name'] }} · {{ $money($side['route']['monthly']) }} a month ×
                        {{ $side['route']['months'] }} months · Driver {{ $side['route']['driver'] }}
                    </p>
                @endif
                @if (count($side['applied']))
                    <p class="px-4 py-2.5 text-xs text-gray-500">
                        @foreach ($side['applied'] as $a)
                            {{ $a['reason'] }} — {{ $a['label'] }} ({{ $money($a['amount']) }}){{ !$loop->last ? ' · ' : '' }}
                        @endforeach
                    </p>
                @endif
            </div>
        </div>
    @endforeach
</div>

{{-- ══════════ 3. FEE CYCLE — how the year is split, and where it stands ══════════ --}}
@foreach ($sv['cycles'] ?? [] as $cycle)
    @php
        $cycleDue = max(0, $cycle['total'] - $cycle['paid']);
        // Penalty keys are newer than the ledger shape a live Livewire session
        // may still be holding, so every read of them defaults to 0.
        $cyclePenaltyNet = (float) ($cycle['penalty_net'] ?? 0);
        $statusWord = [
            'paid'    => ['text-gray-700', 'Paid'],
            'partial' => ['text-amber-600', 'Partial'],
            'pending' => ['text-red-600', 'Due'],
            'na'      => ['text-gray-400', '—'],
        ];
        $cycleCols = 'grid grid-cols-[2.5rem_minmax(0,1fr)_7rem_6.5rem_6.5rem_6.5rem_5rem_6rem] items-center';
    @endphp
    <div wire:key="sv-cycle-{{ $cycle['fee_type'] }}" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
            <div class="min-w-0">
                <h3 class="text-sm font-semibold text-gray-700">{{ ucfirst($cycle['fee_type']) }} fee cycle · {{ $cycle['label'] }}</h3>
                <p class="text-[11px] text-gray-400">{{ $cycle['year'] }} · {{ $cycle['paid_count'] }} of {{ count($cycle['installments']) }} cleared</p>
            </div>
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-gray-600">
                <span>Paid <strong>{{ $money($cycle['paid']) }}</strong></span>
                <span>Due <strong class="{{ $cycleDue > 0 ? 'text-red-600' : '' }}">{{ $money($cycleDue) }}</strong></span>
                <span>Total <strong>{{ $money($cycle['total']) }}</strong></span>
                @if ($cyclePenaltyNet > 0)
                    <span>Penalty due <strong class="text-amber-600">{{ $money($cyclePenaltyNet) }}</strong></span>
                @endif
            </div>
        </div>
        <div class="overflow-x-auto">
            <div class="min-w-[760px] text-sm">
                <div class="{{ $cycleCols }} bg-gray-50 text-xs text-gray-500">
                    <span class="px-4 py-2">#</span>
                    <span class="px-4 py-2">Installment</span>
                    <span class="px-4 py-2">Due date</span>
                    <span class="px-4 py-2 text-right">Amount</span>
                    <span class="px-4 py-2 text-right">Paid</span>
                    <span class="px-4 py-2 text-right">Balance</span>
                    <span class="px-4 py-2">Status</span>
                    <span class="px-4 py-2 text-right">Penalty</span>
                </div>
                @foreach ($cycle['installments'] as $n => $inst)
                    @php
                        [$tone, $word] = $statusWord[$inst['status']] ?? $statusWord['na'];
                        $instPenaltyNet = (float) ($inst['penalty_net'] ?? 0);
                    @endphp
                    <div wire:key="sv-inst-{{ $cycle['fee_type'] }}-{{ $n }}" class="{{ $cycleCols }} border-t border-gray-100">
                        <span class="px-4 py-2.5 text-[11px] text-gray-300 tabular-nums">{{ $n + 1 }}</span>
                        <span class="px-4 py-2.5 text-gray-800 min-w-0 truncate">
                            {{ $inst['label'] }}@if ($inst['percent'] > 0)<span class="text-gray-400 text-xs"> · {{ rtrim(rtrim(number_format($inst['percent'], 2), '0'), '.') }}%</span>@endif
                        </span>
                        <span class="px-4 py-2.5 whitespace-nowrap {{ $inst['overdue'] ? 'text-red-600' : 'text-gray-600' }}">
                            {{ $inst['due_date'] ?? '—' }}@if ($inst['overdue'])<span class="text-xs"> · overdue</span>@endif
                        </span>
                        <span class="px-4 py-2.5 text-right text-gray-700 tabular-nums">{{ $money($inst['amount']) }}</span>
                        <span class="px-4 py-2.5 text-right tabular-nums {{ $inst['paid'] > 0 ? 'text-gray-700' : 'text-gray-400' }}">{{ $money($inst['paid']) }}</span>
                        <span class="px-4 py-2.5 text-right tabular-nums {{ $inst['balance'] > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ $money($inst['balance']) }}</span>
                        <span class="px-4 py-2.5 {{ $tone }}">{{ $word }}</span>
                        <span class="px-4 py-2.5 text-right tabular-nums {{ $instPenaltyNet > 0 ? 'text-amber-600' : 'text-gray-400' }}">
                            {{ $instPenaltyNet > 0 ? $money($instPenaltyNet) : '—' }}
                        </span>
                    </div>
                @endforeach
                <div class="{{ $cycleCols }} border-t border-gray-100 bg-gray-50/60">
                    <span></span>
                    <span class="px-4 py-2.5 text-gray-700">Total</span>
                    <span></span>
                    <span class="px-4 py-2.5 text-right text-gray-800 font-medium tabular-nums">{{ $money($cycle['total']) }}</span>
                    <span class="px-4 py-2.5 text-right text-gray-800 font-medium tabular-nums">{{ $money($cycle['paid']) }}</span>
                    <span class="px-4 py-2.5 text-right font-medium tabular-nums {{ $cycleDue > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ $money($cycleDue) }}</span>
                    <span></span>
                    <span class="px-4 py-2.5 text-right font-medium tabular-nums {{ $cyclePenaltyNet > 0 ? 'text-amber-600' : 'text-gray-400' }}">{{ $money($cyclePenaltyNet) }}</span>
                </div>
            </div>
        </div>
    </div>
@endforeach

{{-- ══════════ 3b. PENALTIES — which fee cycle installments are late, and by how much ══════════ --}}
@php
    $penaltyRows = collect($sv['cycles'] ?? [])->flatMap(function ($cycle) {
        return collect($cycle['installments'] ?? [])
            ->filter(fn ($inst) => ($inst['penalty'] ?? 0) > 0)
            ->map(fn ($inst) => $inst + ['fee_type' => $cycle['fee_type']]);
    })->values();
    $penaltyWaived = collect($sv['cycles'] ?? [])->sum('penalty_waived');
    $penaltyPaid   = collect($sv['cycles'] ?? [])->sum('penalty_paid');
    $penaltyNet    = collect($sv['cycles'] ?? [])->sum('penalty_net');
    $penCols = 'grid grid-cols-[2.5rem_7rem_minmax(0,1fr)_7rem_5.5rem_6rem_6.5rem] items-center';
@endphp
<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
        <div>
            <h3 class="text-sm font-semibold text-gray-700">Penalties</h3>
            <p class="text-[11px] text-gray-400">Fee cycle installments paid late</p>
        </div>
        @if ($penaltyRows->isNotEmpty())
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-gray-600">
                <span>Late <strong>{{ $penaltyRows->count() }}</strong></span>
                <span>Still due <strong class="text-amber-600">{{ $money($penaltyNet) }}</strong></span>
            </div>
        @endif
    </div>
    @if ($penaltyRows->isEmpty())
        <p class="px-4 py-6 text-center text-xs text-gray-400">No penalties due — every installment is paid or within its due date.</p>
    @else
        <div class="overflow-x-auto">
            <div class="min-w-[640px] text-sm">
                <div class="{{ $penCols }} bg-gray-50 text-xs text-gray-500">
                    <span class="px-4 py-2">#</span>
                    <span class="px-4 py-2">Fee cycle</span>
                    <span class="px-4 py-2">Installment</span>
                    <span class="px-4 py-2">Due date</span>
                    <span class="px-4 py-2 text-right">Days late</span>
                    <span class="px-4 py-2 text-right">A day</span>
                    <span class="px-4 py-2 text-right">Accrued</span>
                </div>
                @foreach ($penaltyRows as $n => $row)
                    <div wire:key="sv-penalty-{{ $row['fee_type'] }}-{{ $n }}" class="{{ $penCols }} border-t border-gray-100">
                        <span class="px-4 py-2.5 text-[11px] text-gray-300 tabular-nums">{{ $n + 1 }}</span>
                        <span class="px-4 py-2.5 text-gray-600">{{ ucfirst($row['fee_type']) }}</span>
                        <span class="px-4 py-2.5 text-gray-800 truncate">{{ $row['label'] }}</span>
                        <span class="px-4 py-2.5 text-red-600 whitespace-nowrap">{{ $row['due_date'] ?? '—' }}</span>
                        <span class="px-4 py-2.5 text-right text-gray-600 tabular-nums">{{ $row['days_late'] ?? 0 }}</span>
                        <span class="px-4 py-2.5 text-right text-gray-600 tabular-nums">{{ $money($row['penalty_per_day'] ?? 0) }}</span>
                        <span class="px-4 py-2.5 text-right text-amber-600 tabular-nums">{{ $money($row['penalty'] ?? 0) }}</span>
                    </div>
                @endforeach
                @if ($penaltyWaived > 0 || $penaltyPaid > 0)
                    <p class="px-4 py-2.5 border-t border-gray-100 text-xs text-gray-500">
                        Accrued {{ $money($penaltyRows->sum('penalty')) }}
                        @if ($penaltyWaived > 0) · Waived <span class="text-emerald-600">− {{ $money($penaltyWaived) }}</span>@endif
                        @if ($penaltyPaid > 0) · Paid <span class="text-emerald-600">− {{ $money($penaltyPaid) }}</span>@endif
                    </p>
                @endif
                <div class="flex items-center gap-3 px-4 py-2.5 border-t border-gray-100 bg-gray-50/60">
                    <span class="flex-1 text-gray-700">Penalty still due</span>
                    <span class="text-amber-600 font-medium tabular-nums">{{ $money($penaltyNet) }}</span>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- ══════════ 4. PAYMENTS — full width, each with its slip ══════════ --}}
@php $payCols = 'grid grid-cols-[2.5rem_9rem_8.5rem_6rem_7rem_minmax(0,1fr)_6rem_7rem_3.5rem] items-center'; @endphp
<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
        <div>
            <h3 class="text-sm font-semibold text-gray-700">Payments</h3>
            <p class="text-[11px] text-gray-400">Every payment, newest first, each with its slip</p>
        </div>
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-gray-600">
            <span>Entries <strong>{{ count($sv['payments']) }}</strong></span>
            <span>Collected <strong>{{ $money($tot['paid']) }}</strong></span>
        </div>
    </div>
    <div class="overflow-x-auto">
        <div class="min-w-[860px] text-sm">
            <div class="{{ $payCols }} bg-gray-50 text-xs text-gray-500">
                <span class="px-4 py-2">#</span>
                <span class="px-4 py-2">Receipt</span>
                <span class="px-4 py-2">Date</span>
                <span class="px-4 py-2">Type</span>
                <span class="px-4 py-2">Mode</span>
                <span class="px-4 py-2">Collected by</span>
                <span class="px-4 py-2 text-right">Penalty</span>
                <span class="px-4 py-2 text-right">Amount</span>
                <span class="px-4 py-2 text-center">Slip</span>
            </div>
            @forelse ($sv['payments'] as $n => $p)
                <div wire:key="sv-pay-{{ $p['kind'] }}-{{ $p['id'] }}" class="{{ $payCols }} border-t border-gray-100">
                    <span class="px-4 py-2.5 text-[11px] text-gray-300 tabular-nums">{{ $n + 1 }}</span>
                    <span class="px-4 py-2.5 text-xs text-gray-700 font-mono truncate">{{ $p['receipt_number'] }}</span>
                    <span class="px-4 py-2.5 text-gray-600 whitespace-nowrap">
                        {{ $p['payment_date'] ?? '—' }}
                        {{-- Fee Submission passes `editDates`; View Fee does not, and stays read-only. --}}
                        @if (!empty($editDates) && !empty($p['id']))
                            <button type="button" wire:click="openPaymentDateEdit('{{ $p['kind'] }}', {{ (int) $p['id'] }})" title="Edit date"
                                class="inline-flex items-center justify-center w-6 h-6 ml-0.5 align-middle rounded-md text-amber-600 hover:bg-amber-50">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </button>
                        @endif
                    </span>
                    <span class="px-4 py-2.5 text-gray-600">{{ ucfirst($p['fee_type']) }}</span>
                    <span class="px-4 py-2.5 {{ $p['is_concession'] ? 'text-emerald-600' : 'text-gray-600' }}">{{ ucfirst(str_replace('_', ' ', $p['payment_mode'])) }}</span>
                    <span class="px-4 py-2.5 text-gray-600 truncate">{{ $p['collected_by'] }}</span>
                    <span class="px-4 py-2.5 text-right tabular-nums {{ $p['penalty_amount'] > 0 ? 'text-amber-600' : 'text-gray-400' }}">{{ $money($p['penalty_amount']) }}</span>
                    <span class="px-4 py-2.5 text-right text-gray-800 tabular-nums">{{ $money($p['amount']) }}</span>
                    <span class="px-4 py-2.5 text-center">
                        <a href="{{ $receipt($p) }}" target="_blank" title="Open slip"
                            class="inline-flex p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                        </a>
                    </span>
                </div>
            @empty
                <p class="px-4 py-10 text-center text-sm text-gray-400 border-t border-gray-100">No payments yet — collected fees appear here with their slips.</p>
            @endforelse
            @if (count($sv['payments']))
                <div class="flex items-center gap-3 px-4 py-2.5 border-t border-gray-100 bg-gray-50/60">
                    <span class="flex-1 text-gray-700">Total</span>
                    <span class="text-gray-500 tabular-nums">Penalty {{ $money($tot['penalties']) }}</span>
                    <span class="w-28 text-right text-gray-800 font-medium tabular-nums">{{ $money($tot['paid']) }}</span>
                </div>
            @endif
        </div>
    </div>
</div>
