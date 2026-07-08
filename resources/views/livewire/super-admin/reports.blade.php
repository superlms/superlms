<div class="min-h-screen bg-gray-50">

    {{-- ══════════════ STICKY HEADER + RANGE TOGGLE ══════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="px-4 sm:px-6 pt-4 pb-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Reports</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    Day-by-day &amp; monthly breakdown — students, teachers, schools, revenue, fees, credit, support &amp; enquiries
                </p>
            </div>

            <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-1 self-start" wire:loading.class="opacity-60">
                @foreach (['30d' => 'Last 30 Days', 'monthly' => 'Monthly (12 mo)'] as $key => $label)
                    <button wire:click="setRange('{{ $key }}')"
                        class="px-3 sm:px-4 py-1.5 text-xs sm:text-sm font-semibold rounded-md transition-colors
                               {{ $range === $key ? 'bg-emerald-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    @php
        // [label, accent text, accent bg, isMoney, icon, border accent]
        $metricMeta = [
            'students'  => ['New Students',    'text-blue-600',    'bg-blue-50',    false, '🎓', 'border-l-blue-400'],
            'teachers'  => ['New Teachers',    'text-indigo-600',  'bg-indigo-50',  false, '👩‍🏫', 'border-l-indigo-400'],
            'schools'   => ['New Schools',     'text-purple-600',  'bg-purple-50',  false, '🏫', 'border-l-purple-400'],
            'revenue'   => ['Platform Revenue','text-emerald-600', 'bg-emerald-50', true,  '💰', 'border-l-emerald-400'],
            'fees'      => ['Fees Collected',  'text-amber-600',   'bg-amber-50',   true,  '🧾', 'border-l-amber-400'],
            'credit'    => ['Credit Apps',     'text-rose-600',    'bg-rose-50',    false, '💳', 'border-l-rose-400'],
            'support'   => ['Support Tickets', 'text-sky-600',     'bg-sky-50',     false, '🛟', 'border-l-sky-400'],
            'enquiries' => ['Enquiries',       'text-cyan-600',    'bg-cyan-50',    false, '📩', 'border-l-cyan-400'],
        ];
        $fmt = fn($v, $money) => $money ? '₹' . number_format((float) $v) : number_format((int) $v);
        $periodLabel = $range === 'monthly' ? 'last 12 mo' : 'last 30 days';
    @endphp

    <div class="p-4 sm:p-6 space-y-6">

        {{-- ══════════════ ALL-TIME SNAPSHOT ══════════════ --}}
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">All-Time Totals</p>
            <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-3">
                @foreach ($metricMeta as $k => [$label, $txt, $bg, $isMoney, $icon, $accent])
                    <div class="group rounded-xl border border-gray-200 bg-white p-4 transition-all duration-200
                                hover:shadow-md hover:-translate-y-0.5 hover:border-gray-300">
                        <div class="flex items-center justify-between mb-2">
                            <div class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium {{ $bg }} {{ $txt }}">
                                {{ $label }}
                            </div>
                            <span class="text-base opacity-60 group-hover:opacity-100 transition-opacity">{{ $icon }}</span>
                        </div>
                        <div class="text-lg sm:text-xl font-bold text-gray-900 truncate">
                            {{ $fmt($snapshot[$k] ?? 0, $isMoney) }}
                        </div>
                        <div class="mt-1 text-[11px] {{ ($totals[$k] ?? 0) > 0 ? $txt . ' font-semibold' : 'text-gray-400' }} truncate">
                            {{ ($totals[$k] ?? 0) > 0 ? '+' . $fmt($totals[$k], $isMoney) : 'No change' }} · {{ $periodLabel }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ══════════════ PERIOD TOTALS ══════════════ --}}
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                {{ $range === 'monthly' ? 'Last 12 Months' : 'Last 30 Days' }} — Period Totals
            </p>
            <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-3">
                @foreach ($metricMeta as $k => [$label, $txt, $bg, $isMoney, $icon, $accent])
                    <div class="rounded-lg border border-gray-200 border-l-4 {{ $accent }} bg-white px-3 py-2.5
                                transition-colors hover:bg-gray-50/60">
                        <div class="text-[11px] text-gray-500">{{ $icon }} {{ $label }}</div>
                        <div class="text-base sm:text-lg font-bold {{ $txt }} truncate">
                            {{ $fmt($totals[$k] ?? 0, $isMoney) }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ══════════════ DETAILED TABLE ══════════════ --}}
        <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-sm font-bold text-gray-900">
                    {{ $range === 'monthly' ? 'Month-by-Month Breakdown' : 'Day-by-Day Breakdown' }}
                </h2>
                <span class="text-xs text-gray-400">{{ count($rows) }} {{ $range === 'monthly' ? 'months' : 'days' }}</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                            <th class="text-left font-semibold px-4 py-2.5 whitespace-nowrap">{{ $range === 'monthly' ? 'Month' : 'Date' }}</th>
                            <th class="text-center font-semibold px-3 py-2.5">Students</th>
                            <th class="text-center font-semibold px-3 py-2.5">Teachers</th>
                            <th class="text-center font-semibold px-3 py-2.5">Schools</th>
                            <th class="text-right font-semibold px-3 py-2.5 min-w-[130px]">Revenue</th>
                            <th class="text-right font-semibold px-3 py-2.5 min-w-[130px]">Fees Collected</th>
                            <th class="text-center font-semibold px-3 py-2.5">Credit</th>
                            <th class="text-center font-semibold px-3 py-2.5">Support</th>
                            <th class="text-center font-semibold px-3 py-2.5">Enquiries</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($rows as $row)
                            @php
                                $isEmpty = ($row['students'] + $row['teachers'] + $row['schools'] + $row['revenue']
                                          + $row['fees'] + $row['credit'] + $row['support'] + $row['enquiries']) == 0;
                                $revPct  = min(100, ($row['revenue'] / ($peaks['revenue'] ?? 1)) * 100);
                                $feePct  = min(100, ($row['fees'] / ($peaks['fees'] ?? 1)) * 100);
                            @endphp
                            <tr class="odd:bg-white even:bg-gray-50/50 hover:bg-emerald-50/40 transition-colors {{ $isEmpty ? 'opacity-60' : '' }}">
                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    <span class="font-semibold text-gray-800">{{ $row['label'] }}</span>
                                    @if ($row['sub'])
                                        <span class="text-xs text-gray-400 ml-1">{{ $row['sub'] }}</span>
                                    @endif
                                    @if ($loop->first)
                                        <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">
                                            {{ $range === 'monthly' ? 'This month' : 'Today' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center px-3 py-2.5 {{ $row['students'] ? 'text-blue-700 font-semibold' : 'text-gray-300' }}">{{ $row['students'] ?: '—' }}</td>
                                <td class="text-center px-3 py-2.5 {{ $row['teachers'] ? 'text-indigo-700 font-semibold' : 'text-gray-300' }}">{{ $row['teachers'] ?: '—' }}</td>
                                <td class="text-center px-3 py-2.5 {{ $row['schools'] ? 'text-purple-700 font-semibold' : 'text-gray-300' }}">{{ $row['schools'] ?: '—' }}</td>
                                <td class="px-3 py-2.5">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="hidden sm:block flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden max-w-[60px]">
                                            <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $revPct }}%"></div>
                                        </div>
                                        <span class="{{ $row['revenue'] ? 'text-emerald-700 font-semibold' : 'text-gray-300' }} whitespace-nowrap">
                                            {{ $row['revenue'] ? '₹' . number_format($row['revenue']) : '—' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-3 py-2.5">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="hidden sm:block flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden max-w-[60px]">
                                            <div class="h-full bg-amber-500 rounded-full" style="width: {{ $feePct }}%"></div>
                                        </div>
                                        <span class="{{ $row['fees'] ? 'text-amber-700 font-semibold' : 'text-gray-300' }} whitespace-nowrap">
                                            {{ $row['fees'] ? '₹' . number_format($row['fees']) : '—' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="text-center px-3 py-2.5 {{ $row['credit'] ? 'text-rose-700 font-semibold' : 'text-gray-300' }}">{{ $row['credit'] ?: '—' }}</td>
                                <td class="text-center px-3 py-2.5 {{ $row['support'] ? 'text-sky-700 font-semibold' : 'text-gray-300' }}">{{ $row['support'] ?: '—' }}</td>
                                <td class="text-center px-3 py-2.5 {{ $row['enquiries'] ? 'text-cyan-700 font-semibold' : 'text-gray-300' }}">{{ $row['enquiries'] ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-gray-400 py-8">No data.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50 font-bold text-gray-800 border-t-2 border-gray-200">
                            <td class="px-4 py-3">Total</td>
                            <td class="text-center px-3 py-3 text-blue-700">{{ number_format($totals['students'] ?? 0) }}</td>
                            <td class="text-center px-3 py-3 text-indigo-700">{{ number_format($totals['teachers'] ?? 0) }}</td>
                            <td class="text-center px-3 py-3 text-purple-700">{{ number_format($totals['schools'] ?? 0) }}</td>
                            <td class="text-right px-3 py-3 text-emerald-700">₹{{ number_format($totals['revenue'] ?? 0) }}</td>
                            <td class="text-right px-3 py-3 text-amber-700">₹{{ number_format($totals['fees'] ?? 0) }}</td>
                            <td class="text-center px-3 py-3 text-rose-700">{{ number_format($totals['credit'] ?? 0) }}</td>
                            <td class="text-center px-3 py-3 text-sky-700">{{ number_format($totals['support'] ?? 0) }}</td>
                            <td class="text-center px-3 py-3 text-cyan-700">{{ number_format($totals['enquiries'] ?? 0) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>
</div>
