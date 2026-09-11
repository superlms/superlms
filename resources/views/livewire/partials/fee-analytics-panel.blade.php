{{--
    Fee > Analytics. Deliberately flat: no gradients, no icon chips, no chart
    library — just figures, hairline rules and thin bars, so the numbers are
    what you notice. Data comes from HandlesFeeAnalytics.
--}}
@php
    $a = $analyticsSummary ?? [];
    $rate = $a['rate'] ?? 0;
@endphp

<div class="space-y-4">

    {{-- ── Headline figures ─────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3.5">
            <p class="text-[11px] font-medium uppercase tracking-wider text-gray-400">Total Billable</p>
            <p class="mt-1.5 text-xl font-semibold text-gray-900 tabular-nums">₹{{ number_format($a['total_billable'] ?? 0, 0) }}</p>
            <p class="mt-0.5 text-[11px] text-gray-400">{{ $a['students'] ?? 0 }} students · {{ $a['riders'] ?? 0 }} on transport</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3.5">
            <p class="text-[11px] font-medium uppercase tracking-wider text-gray-400">Collected</p>
            <p class="mt-1.5 text-xl font-semibold text-emerald-600 tabular-nums">₹{{ number_format($a['total_collected'] ?? 0, 0) }}</p>
            <p class="mt-0.5 text-[11px] text-gray-400">
                @if (($a['penalty_collected'] ?? 0) > 0)
                    + ₹{{ number_format($a['penalty_collected'], 0) }} penalty
                @else
                    Academic + transport
                @endif
            </p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3.5">
            <p class="text-[11px] font-medium uppercase tracking-wider text-gray-400">Outstanding</p>
            <p class="mt-1.5 text-xl font-semibold {{ ($a['total_due'] ?? 0) > 0 ? 'text-rose-600' : 'text-gray-900' }} tabular-nums">₹{{ number_format($a['total_due'] ?? 0, 0) }}</p>
            <p class="mt-0.5 text-[11px] text-gray-400">{{ $a['defaulters'] ?? 0 }} students with dues</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3.5">
            <p class="text-[11px] font-medium uppercase tracking-wider text-gray-400">Collection Rate</p>
            <p class="mt-1.5 text-xl font-semibold text-gray-900 tabular-nums">{{ $rate }}%</p>
            <div class="mt-2 h-1 w-full rounded-full bg-gray-100">
                <div class="h-1 rounded-full bg-emerald-500" style="width: {{ min(100, $rate) }}%"></div>
            </div>
        </div>
    </div>

    {{-- ── Split by head ────────────────────────────────────────────────── --}}
    @php
        $heads = [
            ['Academic', $a['academic_billable'] ?? 0, $a['academic_collected'] ?? 0, $a['academic_due'] ?? 0],
            ['Transport', $a['transport_billable'] ?? 0, $a['transport_collected'] ?? 0, $a['transport_due'] ?? 0],
        ];
    @endphp
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-800">By head</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-[11px] uppercase tracking-wider text-gray-400">
                        <th class="px-5 py-2 text-left font-medium">Head</th>
                        <th class="px-5 py-2 text-right font-medium">Billable</th>
                        <th class="px-5 py-2 text-right font-medium">Collected</th>
                        <th class="px-5 py-2 text-right font-medium">Due</th>
                        <th class="px-5 py-2 text-right font-medium w-40">Rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 border-t border-gray-100">
                    @foreach ($heads as [$label, $billable, $collected, $due])
                        @php $pct = $billable > 0 ? min(100, round($collected / $billable * 100, 1)) : 0; @endphp
                        <tr>
                            <td class="px-5 py-3 text-gray-700">{{ $label }}</td>
                            <td class="px-5 py-3 text-right text-gray-900 tabular-nums">₹{{ number_format($billable, 0) }}</td>
                            <td class="px-5 py-3 text-right text-emerald-600 tabular-nums">₹{{ number_format($collected, 0) }}</td>
                            <td class="px-5 py-3 text-right tabular-nums {{ $due > 0 ? 'text-rose-600' : 'text-gray-400' }}">₹{{ number_format($due, 0) }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <div class="h-1 w-20 rounded-full bg-gray-100">
                                        <div class="h-1 rounded-full bg-emerald-500" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-500 tabular-nums w-10 text-right">{{ $pct }}%</span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    @if (($a['penalty_collected'] ?? 0) > 0)
                        <tr>
                            <td class="px-5 py-3 text-gray-700">Penalty</td>
                            <td class="px-5 py-3 text-right text-gray-300">—</td>
                            <td class="px-5 py-3 text-right text-emerald-600 tabular-nums">₹{{ number_format($a['penalty_collected'], 0) }}</td>
                            <td class="px-5 py-3 text-right text-gray-300">—</td>
                            <td class="px-5 py-3 text-right text-xs text-gray-400">Not part of billable</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Recent periods ───────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-200 px-5 py-4">
        <div class="flex flex-wrap items-start gap-x-10 gap-y-4">
            @foreach ($analyticsPeriods ?? [] as $label => $p)
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-wider text-gray-400">{{ $label }}</p>
                    <p class="mt-1 text-base font-semibold text-gray-900 tabular-nums">₹{{ number_format($p['amount'], 0) }}</p>
                    <p class="text-[11px] text-gray-400">{{ $p['count'] }} {{ Str::plural('payment', $p['count']) }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ── Daily trend + payment modes ──────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">

        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 px-5 py-4">
            <div class="flex items-baseline justify-between">
                <h3 class="text-sm font-semibold text-gray-800">Daily collections</h3>
                <p class="text-xs text-gray-400">
                    Last 14 days · <span class="text-gray-600 tabular-nums">₹{{ number_format($analyticsDaily['total'] ?? 0, 0) }}</span>
                </p>
            </div>

            <div class="mt-5 flex items-end gap-1.5 h-32">
                @foreach ($analyticsDaily['points'] ?? [] as $point)
                    <div class="flex-1 h-full flex items-end" title="{{ $point['title'] }} — ₹{{ number_format($point['amount'], 0) }} ({{ $point['count'] }})">
                        <div class="w-full rounded-t-sm {{ $point['amount'] > 0 ? 'bg-emerald-500' : 'bg-gray-200' }}"
                            style="height: {{ $point['pct'] }}%"></div>
                    </div>
                @endforeach
            </div>
            <div class="mt-1.5 flex gap-1.5">
                @foreach ($analyticsDaily['points'] ?? [] as $point)
                    <div class="flex-1 text-center text-[10px] text-gray-400 tabular-nums">{{ $point['label'] }}</div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 px-5 py-4">
            <h3 class="text-sm font-semibold text-gray-800">Payment modes</h3>
            <div class="mt-4 space-y-3.5">
                @forelse ($analyticsModes ?? [] as $mode)
                    <div>
                        <div class="flex items-baseline justify-between text-xs">
                            <span class="text-gray-600">{{ $mode['label'] }}</span>
                            <span class="text-gray-900 tabular-nums">₹{{ number_format($mode['amount'], 0) }}</span>
                        </div>
                        <div class="mt-1.5 h-1 w-full rounded-full bg-gray-100">
                            <div class="h-1 rounded-full bg-gray-800" style="width: {{ $mode['pct'] }}%"></div>
                        </div>
                        <p class="mt-1 text-[11px] text-gray-400 tabular-nums">{{ $mode['pct'] }}% · {{ $mode['count'] }} {{ Str::plural('payment', $mode['count']) }}</p>
                    </div>
                @empty
                    <p class="text-xs text-gray-400 py-6 text-center">No payments recorded yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ── Class-wise collection ────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-baseline justify-between">
            <h3 class="text-sm font-semibold text-gray-800">Class-wise collection</h3>
            <p class="text-xs text-gray-400">Weakest first</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-[11px] uppercase tracking-wider text-gray-400">
                        <th class="px-5 py-2 text-left font-medium">Class</th>
                        <th class="px-5 py-2 text-right font-medium">Students</th>
                        <th class="px-5 py-2 text-right font-medium">Billable</th>
                        <th class="px-5 py-2 text-right font-medium">Collected</th>
                        <th class="px-5 py-2 text-right font-medium">Due</th>
                        <th class="px-5 py-2 text-right font-medium w-40">Rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 border-t border-gray-100">
                    @forelse ($analyticsClassRows ?? [] as $row)
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-5 py-3 font-medium text-gray-800">{{ $row['name'] }}</td>
                            <td class="px-5 py-3 text-right text-gray-500 tabular-nums">{{ $row['students'] }}</td>
                            <td class="px-5 py-3 text-right text-gray-900 tabular-nums">₹{{ number_format($row['billable'], 0) }}</td>
                            <td class="px-5 py-3 text-right text-emerald-600 tabular-nums">₹{{ number_format($row['collected'], 0) }}</td>
                            <td class="px-5 py-3 text-right tabular-nums {{ $row['due'] > 0 ? 'text-rose-600' : 'text-gray-400' }}">₹{{ number_format($row['due'], 0) }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <div class="h-1 w-20 rounded-full bg-gray-100">
                                        <div class="h-1 rounded-full {{ $row['rate'] >= 75 ? 'bg-emerald-500' : ($row['rate'] >= 40 ? 'bg-amber-500' : 'bg-rose-500') }}"
                                            style="width: {{ min(100, $row['rate']) }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-500 tabular-nums w-10 text-right">{{ $row['rate'] }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-gray-400">No students in this selection.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Students ─────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-baseline justify-between">
            <h3 class="text-sm font-semibold text-gray-800">
                {{ $analyticsStudentScope === 'class' ? 'Students in this selection' : 'Largest outstanding balances' }}
            </h3>
            <p class="text-xs text-gray-400">
                {{ $analyticsStudentScope === 'class' ? 'Highest due first' : 'Top ' . count($analyticsStudentRows ?? []) . ' across all classes' }}
            </p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-[11px] uppercase tracking-wider text-gray-400">
                        <th class="px-5 py-2 text-left font-medium">Student</th>
                        <th class="px-5 py-2 text-left font-medium">Adm No</th>
                        <th class="px-5 py-2 text-left font-medium">Class</th>
                        <th class="px-5 py-2 text-right font-medium">Billable</th>
                        <th class="px-5 py-2 text-right font-medium">Collected</th>
                        <th class="px-5 py-2 text-right font-medium">Due</th>
                        <th class="px-5 py-2 text-right font-medium">Ledger</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 border-t border-gray-100">
                    @forelse ($analyticsStudentRows ?? [] as $row)
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-5 py-3 font-medium text-gray-800">{{ $row['name'] }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-gray-500">{{ $row['admission_no'] ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-600">
                                {{ $row['class'] }}@if ($row['section'])<span class="text-gray-400"> / {{ $row['section'] }}</span>@endif
                            </td>
                            <td class="px-5 py-3 text-right text-gray-900 tabular-nums">₹{{ number_format($row['billable'], 0) }}</td>
                            <td class="px-5 py-3 text-right text-emerald-600 tabular-nums">₹{{ number_format($row['collected'], 0) }}</td>
                            <td class="px-5 py-3 text-right tabular-nums {{ $row['due'] > 0 ? 'text-rose-600 font-medium' : 'text-gray-400' }}">₹{{ number_format($row['due'], 0) }}</td>
                            <td class="px-5 py-3 text-right">
                                <button wire:click="openStudentLedger({{ $row['id'] }})"
                                    class="text-xs font-medium text-gray-500 hover:text-gray-900 underline underline-offset-2 decoration-gray-300">
                                    Open
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-12 text-center text-sm text-gray-400">
                            {{ $analyticsStudentScope === 'class' ? 'No students in this selection.' : 'Every student is fully paid up.' }}
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
