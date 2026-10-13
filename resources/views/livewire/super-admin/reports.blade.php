<div class="min-h-screen bg-gray-50">

    @php
        // [label, isMoney] — the figures in the school dashboard's quiet look
        // (no per-metric colours or emoji). Credit is off the Super Admin, so off here too.
        $metricMeta = [
            'students'  => ['New Students',     false],
            'teachers'  => ['New Teachers',     false],
            'schools'   => ['New Schools',      false],
            'revenue'   => ['Platform Revenue', true],
            'fees'      => ['Fees Collected',   true],
            'support'   => ['Support Tickets',  false],
            'enquiries' => ['Enquiries',        false],
        ];
        $fmt = fn($v, $money) => $money ? '₹' . number_format((float) $v) : number_format((int) $v);
        $monthLabel = $monthOptions[$selectedMonth] ?? $selectedMonth;
    @endphp

    {{-- ══════════════════════════════ HEADER ══════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:min-h-15">
            <div class="min-w-0 sm:flex sm:items-baseline sm:gap-2">
                <h1 class="min-w-0 text-lg sm:text-xl font-bold text-gray-900 truncate">Reports</h1>
                <p class="text-xs text-gray-400 mt-0.5 sm:mt-0 truncate">{{ now()->format('l, d M Y') }}</p>
            </div>

            <div class="flex items-center gap-2 self-start sm:self-auto" wire:loading.class="opacity-60">
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">Month</span>
                <select wire:change="setMonth($event.target.value)"
                    class="text-sm bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @foreach ($monthOptions as $key => $label)
                        <option value="{{ $key }}" @selected($key === $selectedMonth)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 space-y-8">

        {{-- ══════════════════════════════ TODAY ══════════════════════════════
             Each figure opens who / what made it up.
        ══════════════════════════════════════════════════════════════════ --}}
        <section>
            <div class="flex items-baseline justify-between gap-3 mb-3">
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Today</h2>
                <span class="text-xs text-gray-400">{{ $today['label'] ?? now()->format('l, d M Y') }}</span>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-7">
                @foreach ($metricMeta as $k => [$label, $isMoney])
                    @php $val = $today[$k] ?? 0; @endphp
                    <button type="button"
                        @if ($val > 0) wire:click="openDetail('{{ $today['date'] }}', '{{ $k }}')" @endif
                        @disabled($val <= 0)
                        class="text-left px-5 py-4 -mr-px -mb-px border-r border-b border-gray-100 min-w-0 transition-colors
                               {{ $val > 0 ? 'hover:bg-gray-50/70 cursor-pointer' : 'cursor-default' }}">
                        <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide truncate">{{ $label }}</p>
                        <p class="text-xl font-semibold mt-1.5 tabular-nums truncate text-gray-900">{{ $fmt($val, $isMoney) }}</p>
                    </button>
                @endforeach
            </div>
        </section>

        {{-- ═══════════════════════════ MONTH TOTALS ═══════════════════════════ --}}
        <section>
            <div class="flex items-baseline justify-between gap-3 mb-3">
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest">{{ $monthLabel }}</h2>
                <span class="text-xs text-gray-400">Click a figure for its full detail</span>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-7">
                @foreach ($metricMeta as $k => [$label, $isMoney])
                    @php $total = $totals[$k] ?? 0; @endphp
                    <button type="button"
                        @if ($total > 0) wire:click="openMonthDetail('{{ $k }}')" @endif
                        @disabled($total <= 0)
                        class="text-left px-5 py-4 -mr-px -mb-px border-r border-b border-gray-100 min-w-0 transition-colors
                               {{ $total > 0 ? 'hover:bg-gray-50/70 cursor-pointer' : 'cursor-default' }}">
                        <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide truncate">{{ $label }}</p>
                        <p class="text-xl font-semibold mt-1.5 tabular-nums truncate {{ $total > 0 ? 'text-gray-900' : 'text-gray-300' }}">{{ $fmt($total, $isMoney) }}</p>
                    </button>
                @endforeach
            </div>
        </section>

        {{-- ═══════════════════════════ ALL-TIME TOTALS ═══════════════════════ --}}
        <section>
            <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">All-Time Totals</h2>
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-7">
                @foreach ($metricMeta as $k => [$label, $isMoney])
                    <div class="px-5 py-4 -mr-px -mb-px border-r border-b border-gray-100 min-w-0">
                        <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide truncate">{{ $label }}</p>
                        <p class="text-xl font-semibold text-gray-900 mt-1.5 tabular-nums truncate">{{ $fmt($snapshot[$k] ?? 0, $isMoney) }}</p>
                        <p class="text-xs text-gray-400 mt-1 truncate">
                            {{ ($totals[$k] ?? 0) > 0 ? '+' . $fmt($totals[$k], $isMoney) : 'No activity' }} in {{ $monthLabel }}
                        </p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ═══════════════════════════ DAY BY DAY ════════════════════════════ --}}
        <section>
            <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Day by Day</h2>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
                    {{-- Month filter: April 2026 → this month. --}}
                    <div class="flex items-center gap-2" wire:loading.class="opacity-60" wire:target="setMonth">
                        <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">Month</span>
                        <select wire:change="setMonth($event.target.value)" aria-label="Day by day month"
                            class="text-sm bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @foreach ($monthOptions as $key => $label)
                                <option value="{{ $key }}" @selected($key === $selectedMonth)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <span class="text-xs text-gray-400">{{ count($rows) }} days · click any number for detail</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[860px]">
                        <thead class="bg-gray-50/70 text-gray-400 text-[11px] uppercase tracking-wide">
                            <tr>
                                <th class="text-left font-medium px-5 py-2.5 whitespace-nowrap">Date</th>
                                <th class="text-center font-medium px-3 py-2.5">Students</th>
                                <th class="text-center font-medium px-3 py-2.5">Teachers</th>
                                <th class="text-center font-medium px-3 py-2.5">Schools</th>
                                <th class="text-right font-medium px-3 py-2.5 min-w-[130px]">Revenue</th>
                                <th class="text-right font-medium px-3 py-2.5 min-w-[130px]">Fees Collected</th>
                                <th class="text-center font-medium px-3 py-2.5">Support</th>
                                <th class="text-center font-medium px-5 py-2.5">Enquiries</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($rows as $row)
                                @php
                                    $revPct = min(100, ($row['revenue'] / ($peaks['revenue'] ?? 1)) * 100);
                                    $feePct = min(100, ($row['fees'] / ($peaks['fees'] ?? 1)) * 100);
                                @endphp
                                <tr class="hover:bg-gray-50/70 {{ $row['isFuture'] ? 'opacity-40' : '' }}">
                                    <td class="px-5 py-2.5 whitespace-nowrap">
                                        <span class="font-medium text-gray-800">{{ $row['label'] }}</span>
                                        <span class="text-xs text-gray-400 ml-1">{{ $row['sub'] }}</span>
                                        @if ($row['isToday'])
                                            <span class="ml-1 text-xs font-medium text-blue-600">Today</span>
                                        @endif
                                    </td>
                                    @foreach (['students', 'teachers', 'schools'] as $m)
                                        <td class="text-center px-3 py-2.5 tabular-nums">
                                            @if ($row[$m] > 0)
                                                <button wire:click="openDetail('{{ $row['date'] }}', '{{ $m }}')" class="font-medium text-gray-800 hover:text-blue-600 hover:underline">{{ $row[$m] }}</button>
                                            @else
                                                <span class="text-gray-300">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    @foreach ([['revenue', $revPct], ['fees', $feePct]] as [$m, $pct])
                                        <td class="px-3 py-2.5">
                                            <div class="flex items-center justify-end gap-2">
                                                <div class="hidden sm:block flex-1 h-1 bg-gray-100 rounded-full overflow-hidden max-w-[60px]">
                                                    <div class="h-full bg-gray-800 rounded-full" style="width: {{ $pct }}%"></div>
                                                </div>
                                                @if ($row[$m] > 0)
                                                    <button wire:click="openDetail('{{ $row['date'] }}', '{{ $m }}')" class="font-medium text-gray-800 hover:text-blue-600 hover:underline whitespace-nowrap tabular-nums">
                                                        ₹{{ number_format($row[$m]) }}
                                                    </button>
                                                @else
                                                    <span class="text-gray-300 whitespace-nowrap">—</span>
                                                @endif
                                            </div>
                                        </td>
                                    @endforeach
                                    @foreach (['support', 'enquiries'] as $m)
                                        <td class="text-center {{ $m === 'enquiries' ? 'px-5' : 'px-3' }} py-2.5 tabular-nums">
                                            @if ($row[$m] > 0)
                                                <button wire:click="openDetail('{{ $row['date'] }}', '{{ $m }}')" class="font-medium text-gray-800 hover:text-blue-600 hover:underline">{{ $row[$m] }}</button>
                                            @else
                                                <span class="text-gray-300">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-gray-400 py-10">No data.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-50/70 border-t border-gray-200 font-semibold text-gray-900 tabular-nums">
                                <td class="px-5 py-3">Total</td>
                                <td class="text-center px-3 py-3">{{ number_format($totals['students'] ?? 0) }}</td>
                                <td class="text-center px-3 py-3">{{ number_format($totals['teachers'] ?? 0) }}</td>
                                <td class="text-center px-3 py-3">{{ number_format($totals['schools'] ?? 0) }}</td>
                                <td class="text-right px-3 py-3">₹{{ number_format($totals['revenue'] ?? 0) }}</td>
                                <td class="text-right px-3 py-3">₹{{ number_format($totals['fees'] ?? 0) }}</td>
                                <td class="text-center px-3 py-3">{{ number_format($totals['support'] ?? 0) }}</td>
                                <td class="text-center px-5 py-3">{{ number_format($totals['enquiries'] ?? 0) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </section>
    </div>

    {{-- ══════════════ DAY + METRIC DETAIL DRAWER ══════════════ --}}
    @if ($detailOpen)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden" x-data @keydown.escape.window="$wire.closeDetail()">
            <div class="absolute inset-0 bg-black/30 backdrop-blur-[1.5px]" wire:click="closeDetail"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-lg bg-white shadow-2xl flex flex-col"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-x-full opacity-0"
                x-transition:enter-end="translate-x-0 opacity-100">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-base font-semibold text-gray-900 truncate">{{ $detailLabel }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ count($detailRows) }} record{{ count($detailRows) === 1 ? '' : 's' }}</p>
                    </div>
                    <button wire:click="closeDetail"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
                    @forelse ($detailRows as $row)
                        <div class="px-6 py-3 flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ $row['title'] ?? '—' }}</p>
                                @if (!empty($row['subtitle']))
                                    <p class="text-xs text-gray-500 truncate">{{ $row['subtitle'] }}</p>
                                @endif
                                @if (!empty($row['meta']))
                                    <p class="text-xs text-gray-400 truncate mt-0.5">{{ $row['meta'] }}</p>
                                @endif
                            </div>
                            <div class="text-right flex-shrink-0">
                                @if (isset($row['amount']))
                                    <p class="text-sm font-semibold text-gray-900 tabular-nums">₹{{ number_format($row['amount'], 0) }}</p>
                                @endif
                                @if (!empty($row['time']))
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $row['time'] }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 text-center py-10">No records found.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
