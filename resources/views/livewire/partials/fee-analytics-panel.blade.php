{{--
    Fee > Analytics, in the Dashboard (Home) page's look: quiet uppercase
    section titles, one flat row of figures, thin progress rules, Chart.js
    charts in plain white cards and tables with a soft grey head — and more
    of the detail behind each figure. Data comes from HandlesFeeAnalytics
    (shared with the accounts dashboard, which includes this same partial).

    Each chart's wrapper is keyed by its own data, so a filter change redraws it.
--}}
@php
    $a    = $analyticsSummary ?? [];
    $rate = $a['rate'] ?? 0;
    $pct  = fn ($part, $whole) => $whole > 0 ? min(100, round($part / $whole * 100, 1)) : 0;

    $academicPct  = $pct($a['academic_collected'] ?? 0, $a['academic_billable'] ?? 0);
    $transportPct = $pct($a['transport_collected'] ?? 0, $a['transport_billable'] ?? 0);
    $paidUpPct    = $pct($a['fully_paid'] ?? 0, $a['students'] ?? 0);
    $perStudent   = ($a['students'] ?? 0) > 0 ? ($a['total_collected'] ?? 0) / $a['students'] : 0;

    $daily     = $analyticsDaily['points'] ?? [];
    $monthly   = $analyticsMonthly['points'] ?? [];
    $modes     = $analyticsModes ?? [];
    $classRows = $analyticsClassRows ?? [];
    // The class chart reads in class order rather than the table's weakest-first.
    $classChart = collect($classRows)->sortBy('name', SORT_NATURAL)->values();

    $modeColors = ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#06b6d4', '#8b5cf6', '#64748b'];
    $rupeeTick  = "(v) => '₹' + Number(v).toLocaleString('en-IN')";
@endphp

<div class="space-y-8">

    {{-- ══════════════════════════════════════════════════════════════════
         OVERVIEW — one flat row of the four figures, then each head's
         collection as a thin rule.
    ══════════════════════════════════════════════════════════════════ --}}
    <section>
        <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Overview</h2>

        <div class="bg-white rounded-xl border border-gray-200 divide-y sm:divide-y-0 sm:divide-x divide-gray-100 grid grid-cols-2 sm:grid-cols-4">
            @foreach ([
                ['Total Billable', '₹' . number_format($a['total_billable'] ?? 0, 0), ($a['students'] ?? 0) . ' students · ' . ($a['riders'] ?? 0) . ' on transport', 'text-gray-900'],
                ['Collected', '₹' . number_format($a['total_collected'] ?? 0, 0), ($a['penalty_collected'] ?? 0) > 0 ? '+ ₹' . number_format($a['penalty_collected'], 0) . ' penalty' : 'Academic + transport', 'text-emerald-600'],
                ['Outstanding', '₹' . number_format($a['total_due'] ?? 0, 0), ($a['defaulters'] ?? 0) . ' students with dues', ($a['total_due'] ?? 0) > 0 ? 'text-rose-600' : 'text-gray-900'],
                ['Collection Rate', $rate . '%', '₹' . number_format($perStudent, 0) . ' collected a student', 'text-gray-900'],
            ] as [$label, $value, $sub, $tone])
                <div class="px-5 py-4">
                    <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">{{ $label }}</p>
                    <p class="text-[26px] leading-none font-semibold {{ $tone }} mt-2 tabular-nums">{{ $value }}</p>
                    <p class="text-xs text-gray-400 mt-1.5">{{ $sub }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
            @foreach ([
                ['Academic', $academicPct . '%', '₹' . number_format($a['academic_collected'] ?? 0, 0) . ' of ₹' . number_format($a['academic_billable'] ?? 0, 0) . ' · ₹' . number_format($a['academic_due'] ?? 0, 0) . ' due', $academicPct],
                ['Transport', $transportPct . '%', '₹' . number_format($a['transport_collected'] ?? 0, 0) . ' of ₹' . number_format($a['transport_billable'] ?? 0, 0) . ' · ₹' . number_format($a['transport_due'] ?? 0, 0) . ' due', $transportPct],
                ['Fully Paid Students', ($a['fully_paid'] ?? 0) . ' / ' . ($a['students'] ?? 0), $paidUpPct . '% paid up · ' . ($a['defaulters'] ?? 0) . ' still owe', $paidUpPct],
            ] as [$label, $value, $sub, $bar])
                <div class="bg-white rounded-xl border border-gray-200 px-5 py-4">
                    <div class="flex items-baseline justify-between gap-2">
                        <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">{{ $label }}</p>
                        <p class="text-lg font-semibold text-gray-900 tabular-nums">{{ $value }}</p>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-1 mt-3">
                        <div class="bg-gray-800 h-1 rounded-full" style="width: {{ min(100, $bar) }}%"></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">{{ $sub }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════════
         COLLECTION TREND — the last 14 days a bar each, beside the recent
         periods.
    ══════════════════════════════════════════════════════════════════ --}}
    <section>
        <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Collection Trend</h2>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5">
                <div class="mb-4">
                    <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">Collected · Last 14 days</p>
                    <p class="text-2xl font-semibold text-gray-900 mt-1 tabular-nums">₹{{ number_format($analyticsDaily['total'] ?? 0, 0) }}
                        <span class="text-xs font-normal text-gray-400">· {{ $analyticsDaily['count'] ?? 0 }} {{ Str::plural('payment', $analyticsDaily['count'] ?? 0) }}</span>
                    </p>
                </div>
                <div class="h-56" wire:ignore wire:key="fa-daily-{{ md5(json_encode($daily)) }}">
                    <canvas x-data="{
                        init() {
                            if (!window.Chart) return;
                            new Chart(this.$el.getContext('2d'), {
                                type: 'bar',
                                data: {
                                    labels: @js(array_column($daily, 'title')),
                                    datasets: [{ label: 'Collected', data: @js(array_column($daily, 'amount')), backgroundColor: 'rgba(16,185,129,0.75)', borderRadius: 3, borderSkipped: false, maxBarThickness: 30 }]
                                },
                                options: {
                                    responsive: true, maintainAspectRatio: false,
                                    plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => '₹' + Number(c.raw).toLocaleString('en-IN') } } },
                                    scales: {
                                        x: { grid: { display: false }, ticks: { font: { size: 9 }, maxRotation: 0, autoSkipPadding: 8, callback: function (v) { return String(this.getLabelForValue(v)).slice(0, 2); } } },
                                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, callback: {{ $rupeeTick }} } }
                                    }
                                }
                            });
                        }
                    }"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col">
                <h3 class="text-sm font-semibold text-gray-800 mb-4">Recent periods</h3>
                <dl class="space-y-3 text-sm">
                    @foreach ($analyticsPeriods ?? [] as $label => $p)
                        <div class="flex items-baseline justify-between {{ $label === 'This Week' ? 'pt-3 border-t border-gray-100' : '' }}">
                            <dt class="text-gray-500">{{ $label }} <span class="text-[11px] text-gray-400">· {{ $p['count'] }} {{ Str::plural('payment', $p['count']) }}</span></dt>
                            <dd class="font-semibold text-gray-900 tabular-nums">₹{{ number_format($p['amount'], 0) }}</dd>
                        </div>
                    @endforeach
                </dl>
                <div class="mt-auto pt-5">
                    <div class="flex justify-between text-xs mb-1.5"><span class="text-gray-400">Collected of billable</span><span class="font-semibold text-gray-700">{{ $rate }}%</span></div>
                    <div class="w-full bg-gray-100 rounded-full h-1.5"><div class="bg-gray-800 h-1.5 rounded-full" style="width: {{ min(100, $rate) }}%"></div></div>
                </div>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════════
         MONTH BY MONTH — six months of collections, and how they were paid.
    ══════════════════════════════════════════════════════════════════ --}}
    <section>
        <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Month by Month</h2>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5">
                <div class="mb-4">
                    <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">Collected · Last 6 months</p>
                    <p class="text-2xl font-semibold text-gray-900 mt-1 tabular-nums">₹{{ number_format($analyticsMonthly['total'] ?? 0, 0) }}
                        <span class="text-xs font-normal text-gray-400">· {{ $analyticsMonthly['count'] ?? 0 }} {{ Str::plural('payment', $analyticsMonthly['count'] ?? 0) }}</span>
                    </p>
                </div>
                <div class="h-56" wire:ignore wire:key="fa-monthly-{{ md5(json_encode($monthly)) }}">
                    <canvas x-data="{
                        init() {
                            if (!window.Chart) return;
                            new Chart(this.$el.getContext('2d'), {
                                type: 'bar',
                                data: {
                                    labels: @js(array_column($monthly, 'title')),
                                    datasets: [{ label: 'Collected', data: @js(array_column($monthly, 'amount')), backgroundColor: 'rgba(99,102,241,0.7)', borderRadius: 3, borderSkipped: false, maxBarThickness: 48 }]
                                },
                                options: {
                                    responsive: true, maintainAspectRatio: false,
                                    plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => '₹' + Number(c.raw).toLocaleString('en-IN') } } },
                                    scales: {
                                        x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, callback: {{ $rupeeTick }} } }
                                    }
                                }
                            });
                        }
                    }"></canvas>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-3 sm:grid-cols-6 gap-2 text-center">
                    @foreach ($monthly as $m)
                        <div>
                            <p class="text-sm font-semibold text-gray-800 tabular-nums">₹{{ number_format($m['amount'], 0) }}</p>
                            <p class="text-[10px] text-gray-400 uppercase">{{ $m['label'] }} · {{ $m['count'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-800 mb-4">Payment modes</h3>
                @if (count($modes))
                    <div class="h-40" wire:ignore wire:key="fa-modes-{{ md5(json_encode($modes)) }}">
                        <canvas x-data="{
                            init() {
                                if (!window.Chart) return;
                                new Chart(this.$el.getContext('2d'), {
                                    type: 'doughnut',
                                    data: {
                                        labels: @js(array_column($modes, 'label')),
                                        datasets: [{ data: @js(array_column($modes, 'amount')), backgroundColor: @js(array_slice($modeColors, 0, max(1, count($modes)))), borderWidth: 0 }]
                                    },
                                    options: {
                                        responsive: true, maintainAspectRatio: false, cutout: '68%',
                                        plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => c.label + ': ₹' + Number(c.raw).toLocaleString('en-IN') } } }
                                    }
                                });
                            }
                        }"></canvas>
                    </div>
                @endif
                <div class="mt-4 space-y-2.5">
                    @forelse ($modes as $i => $mode)
                        <div class="flex items-center gap-2 text-xs">
                            <span class="w-2.5 h-2.5 rounded-sm flex-shrink-0" style="background: {{ $modeColors[$i % count($modeColors)] }}"></span>
                            <span class="flex-1 text-gray-600">{{ $mode['label'] }} <span class="text-gray-400">· {{ $mode['count'] }}</span></span>
                            <span class="text-gray-400 tabular-nums">{{ $mode['pct'] }}%</span>
                            <span class="w-20 text-right font-semibold text-gray-800 tabular-nums">₹{{ number_format($mode['amount'], 0) }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 py-6 text-center">No payments recorded yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════════
         BY CLASS — collected against due for every class, then the figures,
         weakest first, with each head beside them.
    ══════════════════════════════════════════════════════════════════ --}}
    <section>
        <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">By Class</h2>

        @if ($classChart->isNotEmpty())
            <div class="bg-white rounded-xl border border-gray-200 p-5 mb-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-800">Collected and due, class by class</h3>
                    <div class="flex items-center gap-3 text-[11px] text-gray-400">
                        <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-500 inline-block"></span> Collected</span>
                        <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-rose-400 inline-block"></span> Due</span>
                    </div>
                </div>
                <div class="h-64" wire:ignore wire:key="fa-class-{{ md5(json_encode($classChart)) }}">
                    <canvas x-data="{
                        init() {
                            if (!window.Chart) return;
                            new Chart(this.$el.getContext('2d'), {
                                type: 'bar',
                                data: {
                                    labels: @js($classChart->pluck('name')->all()),
                                    datasets: [
                                        { label: 'Collected', data: @js($classChart->pluck('collected')->all()), backgroundColor: 'rgba(16,185,129,0.8)', borderRadius: 3, maxBarThickness: 34, stack: 'fee' },
                                        { label: 'Due', data: @js($classChart->pluck('due')->all()), backgroundColor: 'rgba(251,113,133,0.75)', borderRadius: 3, maxBarThickness: 34, stack: 'fee' }
                                    ]
                                },
                                options: {
                                    responsive: true, maintainAspectRatio: false,
                                    plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => c.dataset.label + ': ₹' + Number(c.raw).toLocaleString('en-IN') } } },
                                    scales: {
                                        x: { stacked: true, grid: { display: false }, ticks: { font: { size: 10 } } },
                                        y: { stacked: true, beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, callback: {{ $rupeeTick }} } }
                                    }
                                }
                            });
                        }
                    }"></canvas>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">Class-wise collection</h3>
                    <p class="text-xs text-gray-400">Weakest first</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[560px]">
                        <thead class="bg-gray-50/70 text-gray-400 text-[11px] uppercase tracking-wide">
                            <tr>
                                <th class="px-5 py-2.5 text-left font-medium">Class</th>
                                <th class="px-4 py-2.5 text-right font-medium">Students</th>
                                <th class="px-4 py-2.5 text-right font-medium">Billable</th>
                                <th class="px-4 py-2.5 text-right font-medium">Collected</th>
                                <th class="px-4 py-2.5 text-right font-medium">Due</th>
                                <th class="px-5 py-2.5 text-right font-medium w-40">Rate</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($classRows as $row)
                                <tr class="hover:bg-gray-50/70">
                                    <td class="px-5 py-2.5 font-medium text-gray-700">{{ $row['name'] }}</td>
                                    <td class="px-4 py-2.5 text-right text-gray-500 tabular-nums">{{ $row['students'] }}</td>
                                    <td class="px-4 py-2.5 text-right text-gray-900 tabular-nums">₹{{ number_format($row['billable'], 0) }}</td>
                                    <td class="px-4 py-2.5 text-right text-emerald-600 tabular-nums">₹{{ number_format($row['collected'], 0) }}</td>
                                    <td class="px-4 py-2.5 text-right tabular-nums {{ $row['due'] > 0 ? 'text-rose-600' : 'text-gray-400' }}">₹{{ number_format($row['due'], 0) }}</td>
                                    <td class="px-5 py-2.5">
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

            {{-- By head, as the Dashboard's Overall card --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col">
                <h3 class="text-sm font-semibold text-gray-800 mb-4">By head</h3>
                <div class="space-y-5 text-sm">
                    @foreach ([
                        ['Academic', $a['academic_billable'] ?? 0, $a['academic_collected'] ?? 0, $a['academic_due'] ?? 0, $academicPct],
                        ['Transport', $a['transport_billable'] ?? 0, $a['transport_collected'] ?? 0, $a['transport_due'] ?? 0, $transportPct],
                    ] as [$label, $billable, $collected, $due, $headPct])
                        <div>
                            <div class="flex items-baseline justify-between">
                                <span class="font-semibold text-gray-700">{{ $label }}</span>
                                <span class="text-xs text-gray-500 tabular-nums">{{ $headPct }}%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-1 mt-2">
                                <div class="bg-gray-800 h-1 rounded-full" style="width: {{ min(100, $headPct) }}%"></div>
                            </div>
                            <dl class="mt-2 space-y-1 text-xs">
                                <div class="flex justify-between"><dt class="text-gray-400">Billable</dt><dd class="text-gray-800 tabular-nums">₹{{ number_format($billable, 0) }}</dd></div>
                                <div class="flex justify-between"><dt class="text-gray-400">Collected</dt><dd class="text-emerald-600 tabular-nums">₹{{ number_format($collected, 0) }}</dd></div>
                                <div class="flex justify-between"><dt class="text-gray-400">Due</dt><dd class="tabular-nums {{ $due > 0 ? 'text-rose-600' : 'text-gray-400' }}">₹{{ number_format($due, 0) }}</dd></div>
                            </dl>
                        </div>
                    @endforeach
                    @if (($a['penalty_collected'] ?? 0) > 0)
                        <div class="pt-4 border-t border-gray-100 flex items-baseline justify-between text-xs">
                            <span class="text-gray-500">Penalty collected <span class="text-gray-400">(not part of billable)</span></span>
                            <span class="font-semibold text-emerald-600 tabular-nums">₹{{ number_format($a['penalty_collected'], 0) }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════════
         STUDENTS — the biggest dues (or everyone in the chosen class), each
         opening their ledger.
    ══════════════════════════════════════════════════════════════════ --}}
    <section>
        <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Students</h2>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-800">
                    {{ $analyticsStudentScope === 'class' ? 'Students in this selection' : 'Largest outstanding balances' }}
                </h3>
                <p class="text-xs text-gray-400">
                    {{ $analyticsStudentScope === 'class' ? 'Highest due first' : 'Top ' . count($analyticsStudentRows ?? []) . ' across all classes' }}
                </p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[720px]">
                    <thead class="bg-gray-50/70 text-gray-400 text-[11px] uppercase tracking-wide">
                        <tr>
                            <th class="px-5 py-2.5 text-left font-medium">Student</th>
                            <th class="px-4 py-2.5 text-left font-medium">Adm No</th>
                            <th class="px-4 py-2.5 text-left font-medium">Class</th>
                            <th class="px-4 py-2.5 text-right font-medium">Billable</th>
                            <th class="px-4 py-2.5 text-right font-medium">Collected</th>
                            <th class="px-4 py-2.5 text-right font-medium">Due</th>
                            <th class="px-4 py-2.5 text-right font-medium w-32">Paid</th>
                            <th class="px-5 py-2.5 text-right font-medium">Ledger</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($analyticsStudentRows ?? [] as $row)
                            @php $rowPct = $pct($row['collected'], $row['billable']); @endphp
                            <tr class="hover:bg-gray-50/70">
                                <td class="px-5 py-2.5 font-medium text-gray-700">{{ $row['name'] }}</td>
                                <td class="px-4 py-2.5 font-mono text-xs text-gray-500">{{ $row['admission_no'] ?? '—' }}</td>
                                <td class="px-4 py-2.5 text-gray-600">
                                    {{ $row['class'] }}@if ($row['section'])<span class="text-gray-400"> / {{ $row['section'] }}</span>@endif
                                </td>
                                <td class="px-4 py-2.5 text-right text-gray-900 tabular-nums">₹{{ number_format($row['billable'], 0) }}</td>
                                <td class="px-4 py-2.5 text-right text-emerald-600 tabular-nums">₹{{ number_format($row['collected'], 0) }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums {{ $row['due'] > 0 ? 'text-rose-600 font-medium' : 'text-gray-400' }}">₹{{ number_format($row['due'], 0) }}</td>
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="h-1 w-14 rounded-full bg-gray-100">
                                            <div class="h-1 rounded-full bg-gray-800" style="width: {{ $rowPct }}%"></div>
                                        </div>
                                        <span class="text-xs text-gray-500 tabular-nums w-10 text-right">{{ $rowPct }}%</span>
                                    </div>
                                </td>
                                <td class="px-5 py-2.5 text-right">
                                    <button wire:click="openStudentLedger({{ $row['id'] }})"
                                        class="text-xs font-medium text-blue-600 hover:text-blue-800">
                                        Open →
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-5 py-12 text-center text-sm text-gray-400">
                                {{ $analyticsStudentScope === 'class' ? 'No students in this selection.' : 'Every student is fully paid up.' }}
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

</div>
