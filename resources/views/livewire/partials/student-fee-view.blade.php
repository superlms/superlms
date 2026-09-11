{{-- ══════════════════════════════════════════════════════════════════
     VIEW FEE — one student's ledger, read top to bottom:
       1. who they are, with the collection lines beside them
       2. what the fee is made of, academic and transport, net of concession
       3. every payment, full width, each with its slip

     Fed by App\Livewire\Concerns\HandlesStudentFeeView::buildStudentFeeView()
     as $sv, plus $feePrefix ('admin' | 'accounts') and $feeOrg for the
     receipt links. Shared by Admin\Fee's View Fee tab and Accounts\ViewFee.
══════════════════════════════════════════════════════════════════ --}}

@php
    $stu    = $sv['student'];
    $tot    = $sv['totals'];
    $sides  = ['Academic' => $sv['academic']];
    if ($sv['hasTransport']) {
        $sides['Transport'] = $sv['transport'];
    }
    $receipt = fn ($id) => route($feePrefix . '.fee.receipt', ['organization' => $feeOrg, 'id' => $id]);
@endphp

{{-- ══════════ 1. STUDENT + COLLECTION LINES ══════════ --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-3">
        <div class="w-9 h-9 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center text-sm font-bold flex-shrink-0">
            {{ $stu['initial'] }}
        </div>
        <div class="min-w-0">
            <h3 class="text-sm font-semibold text-gray-900 truncate">{{ $stu['name'] }}</h3>
            <p class="text-[11px] text-gray-400 mt-0.5">{{ $stu['class_section'] }} · Adm {{ $stu['admission_no'] }}</p>
        </div>
    </div>

    {{-- Details, two columns, nothing drawn between them --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 px-4 py-3">
        @foreach ([
            ["Father's Name" => $stu['father_name'], "Mother's Name" => $stu['mother_name'], 'Phone' => $stu['phone']],
            ['Admission No.' => $stu['admission_no'], 'Roll No.' => $stu['roll_no'], 'Class / Section' => $stu['class_section']],
        ] as $col => $pairs)
            <div wire:key="sv-detail-col-{{ $col }}">
                @foreach ($pairs as $label => $value)
                    <div class="flex items-center gap-3 py-1.5 text-sm">
                        <span class="w-32 flex-shrink-0 text-[11px] uppercase tracking-wider text-gray-400">{{ $label }}</span>
                        <span class="flex-1 text-gray-700 truncate">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    {{-- Collection lines — overall, then each fee side --}}
    <div class="border-t border-gray-100 px-4 py-3 space-y-3">
        @php
            $lines = ['Overall' => [
                'net' => $tot['net'], 'paid' => $tot['paid'], 'remaining' => $tot['remaining'], 'pct' => $tot['pct'],
            ]] + $sides;
        @endphp
        @foreach ($lines as $label => $line)
            <div wire:key="sv-line-{{ \Illuminate\Support\Str::slug($label) }}">
                <div class="flex items-baseline justify-between gap-3 text-xs">
                    <span class="font-semibold text-gray-700">{{ $label }}</span>
                    <span class="text-gray-400 tabular-nums">
                        Paid <strong class="text-emerald-600">₹{{ number_format($line['paid'], 2) }}</strong>
                        · Remaining <strong class="{{ $line['remaining'] > 0 ? 'text-rose-500' : 'text-gray-400' }}">₹{{ number_format($line['remaining'], 2) }}</strong>
                        · Total <strong class="text-gray-700">₹{{ number_format($line['net'], 2) }}</strong>
                    </span>
                </div>
                <div class="mt-1.5 h-1.5 w-full rounded-full bg-gray-100 overflow-hidden">
                    <div class="h-1.5 rounded-full {{ $line['pct'] >= 100 ? 'bg-emerald-500' : 'bg-blue-500' }}"
                        style="width: {{ max(2, $line['pct']) }}%"></div>
                </div>
            </div>
        @endforeach
    </div>
</div>

{{-- ══════════ 2. FEE STRUCTURE — academic and transport, net of concession ══════════ --}}
<div class="grid grid-cols-1 {{ $sv['hasTransport'] ? 'lg:grid-cols-2' : '' }} gap-4">
    @foreach ($sides as $label => $side)
        <div wire:key="sv-side-{{ \Illuminate\Support\Str::slug($label) }}" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between gap-2">
                <h3 class="text-sm font-semibold text-gray-900">{{ $label }} Fee</h3>
                <span class="text-[11px] text-gray-400">{{ count($side['rows']) }} head{{ count($side['rows']) === 1 ? '' : 's' }}</span>
            </div>

            @if (count($side['rows']))
                <div class="px-4 py-3">
                    @foreach ($side['rows'] as $n => $r)
                        <div class="flex items-center gap-3 py-1.5 text-sm">
                            <span class="w-5 text-[11px] text-gray-300 tabular-nums">{{ $n + 1 }}</span>
                            <span class="flex-1 text-gray-600 truncate">{{ $r['fee_name'] }}</span>
                            <span class="text-gray-800 tabular-nums">₹{{ number_format($r['amount'], 2) }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="px-4 py-6 text-center text-xs text-gray-400">No {{ strtolower($label) }} fee set for this class.</p>
            @endif

            <div class="border-t border-gray-100 px-4 py-2.5 space-y-1">
                @if ($side['concession'] > 0)
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-400">Fee</span>
                        <span class="text-gray-400 line-through tabular-nums">₹{{ number_format($side['gross'], 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-400">Concession</span>
                        <span class="text-emerald-600 tabular-nums">− ₹{{ number_format($side['concession'], 2) }}</span>
                    </div>
                @endif
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Payable</span>
                    <span class="text-sm font-bold text-gray-900 tabular-nums">₹{{ number_format($side['net'], 2) }}</span>
                </div>

                {{-- The concession itself, in the small print --}}
                @if (count($side['applied']))
                    <p class="pt-1 text-[11px] leading-relaxed text-gray-400">
                        @foreach ($side['applied'] as $a)
                            {{ $a['reason'] }} — {{ $a['label'] }} (₹{{ number_format($a['amount'], 2) }}){{ !$loop->last ? ' · ' : '' }}
                        @endforeach
                    </p>
                @endif
            </div>
        </div>
    @endforeach
</div>

{{-- ══════════ 3. PAYMENTS — full width, each with its slip ══════════ --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between gap-2">
        <h3 class="text-sm font-semibold text-gray-900">Payments</h3>
        <span class="text-[11px] text-gray-400">
            {{ count($sv['payments']) }} entr{{ count($sv['payments']) === 1 ? 'y' : 'ies' }} ·
            ₹{{ number_format($tot['paid'], 2) }} collected
        </span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-gray-100">
                <tr class="text-[11px] uppercase tracking-wider text-gray-400">
                    <th class="px-4 py-2 text-left font-normal w-10">#</th>
                    <th class="px-4 py-2 text-left font-normal">Receipt</th>
                    <th class="px-4 py-2 text-left font-normal">Date</th>
                    <th class="px-4 py-2 text-left font-normal">Type</th>
                    <th class="px-4 py-2 text-left font-normal">Mode</th>
                    <th class="px-4 py-2 text-left font-normal">Collected By</th>
                    <th class="px-4 py-2 text-right font-normal">Penalty</th>
                    <th class="px-4 py-2 text-right font-normal">Amount</th>
                    <th class="px-4 py-2 text-center font-normal w-20">Slip</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sv['payments'] as $n => $p)
                    <tr wire:key="sv-pay-{{ $p['id'] }}" class="hover:bg-gray-50/70">
                        <td class="px-4 py-2.5 text-[11px] text-gray-300 tabular-nums">{{ $n + 1 }}</td>
                        <td class="px-4 py-2.5 font-mono text-xs text-gray-700">{{ $p['receipt_number'] }}</td>
                        <td class="px-4 py-2.5 text-gray-600 whitespace-nowrap">{{ $p['payment_date'] ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-gray-600 capitalize">{{ $p['fee_type'] }}</td>
                        <td class="px-4 py-2.5 capitalize {{ $p['is_concession'] ? 'text-emerald-600' : 'text-gray-600' }}">
                            {{ str_replace('_', ' ', $p['payment_mode']) }}
                        </td>
                        <td class="px-4 py-2.5 text-gray-500">{{ $p['collected_by'] }}</td>
                        <td class="px-4 py-2.5 text-right tabular-nums {{ $p['penalty_amount'] > 0 ? 'text-amber-600' : 'text-gray-300' }}">
                            ₹{{ number_format($p['penalty_amount'], 2) }}
                        </td>
                        <td class="px-4 py-2.5 text-right font-semibold text-gray-900 tabular-nums">₹{{ number_format($p['amount'], 2) }}</td>
                        <td class="px-4 py-2.5 text-center">
                            <a href="{{ $receipt($p['id']) }}" target="_blank" title="Open slip"
                                class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-200 text-gray-400 hover:bg-gray-50 hover:text-gray-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center">
                            <p class="text-sm font-semibold text-gray-800">No payments yet</p>
                            <p class="text-xs text-gray-400 mt-1">Collected fees will appear here with their slips.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if (count($sv['payments']))
                <tfoot>
                    <tr class="border-t border-gray-200">
                        <td colspan="6" class="px-4 py-2.5 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Total</td>
                        <td class="px-4 py-2.5 text-right text-gray-500 tabular-nums">₹{{ number_format($tot['penalties'], 2) }}</td>
                        <td class="px-4 py-2.5 text-right font-bold text-gray-900 tabular-nums">₹{{ number_format($tot['paid'], 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
