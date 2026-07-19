<div class="min-h-screen bg-gray-50">

    {{-- ══════════════ STICKY HEADER + MONTH SELECT ══════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="px-4 sm:px-6 pt-4 pb-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">Reports</h1>
            </div>

            <div class="flex items-center gap-2 self-start" wire:loading.class="opacity-60">
                <label class="text-xs text-gray-500">Month</label>
                <select wire:change="setMonth($event.target.value)"
                    class="text-xs sm:text-sm bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 font-semibold focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    @foreach ($monthOptions as $key => $label)
                        <option value="{{ $key }}" @selected($key === $selectedMonth)>{{ $label }}</option>
                    @endforeach
                </select>
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
        $monthLabel = $monthOptions[$selectedMonth] ?? $selectedMonth;
    @endphp

    <div class="p-4 sm:p-6 space-y-6">

        {{-- ══════════════ TODAY ══════════════ --}}
        <div class="rounded-xl border border-emerald-100 bg-gradient-to-r from-emerald-50/60 to-white p-4 sm:p-5">
            <div class="flex items-center gap-2 mb-3">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <h2 class="text-sm font-bold text-gray-900">Today — {{ $today['label'] ?? now()->format('l, d M Y') }}</h2>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
                @foreach ($metricMeta as $k => [$label, $txt, $bg, $isMoney, $icon, $accent])
                    @php $val = $today[$k] ?? 0; @endphp
                    <button type="button"
                        @if ($val > 0) wire:click="openDetail('{{ $today['date'] }}', '{{ $k }}')" @endif
                        @disabled($val <= 0)
                        class="text-left rounded-lg border border-gray-100 bg-white px-3 py-2.5 transition-colors
                               {{ $val > 0 ? 'hover:bg-gray-50 hover:border-gray-200 cursor-pointer' : 'opacity-50 cursor-default' }}">
                        <div class="text-[11px] text-gray-500">{{ $icon }} {{ $label }}</div>
                        <div class="text-base font-bold {{ $txt }}">{{ $fmt($val, $isMoney) }}</div>
                    </button>
                @endforeach
            </div>
        </div>

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
                            {{ ($totals[$k] ?? 0) > 0 ? '+' . $fmt($totals[$k], $isMoney) : 'No activity' }} in {{ $monthLabel }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ══════════════ MONTH TOTALS ══════════════ --}}
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                {{ $monthLabel }} — Month Totals
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

        {{-- ══════════════ DAY-BY-DAY TABLE ══════════════ --}}
        <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-sm font-bold text-gray-900">Day-by-Day — {{ $monthLabel }}</h2>
                <span class="text-xs text-gray-400">{{ count($rows) }} days · click any number for detail</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                            <th class="text-left font-semibold px-4 py-2.5 whitespace-nowrap">Date</th>
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
                            <tr class="odd:bg-white even:bg-gray-50/50 hover:bg-emerald-50/40 transition-colors {{ $isEmpty ? 'opacity-60' : '' }} {{ $row['isFuture'] ? 'opacity-40' : '' }}">
                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    <span class="font-semibold text-gray-800">{{ $row['label'] }}</span>
                                    <span class="text-xs text-gray-400 ml-1">{{ $row['sub'] }}</span>
                                    @if ($row['isToday'])
                                        <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">
                                            Today
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center px-3 py-2.5">
                                    @if ($row['students'] > 0)
                                        <button wire:click="openDetail('{{ $row['date'] }}', 'students')" class="text-blue-700 font-semibold hover:underline">{{ $row['students'] }}</button>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="text-center px-3 py-2.5">
                                    @if ($row['teachers'] > 0)
                                        <button wire:click="openDetail('{{ $row['date'] }}', 'teachers')" class="text-indigo-700 font-semibold hover:underline">{{ $row['teachers'] }}</button>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="text-center px-3 py-2.5">
                                    @if ($row['schools'] > 0)
                                        <button wire:click="openDetail('{{ $row['date'] }}', 'schools')" class="text-purple-700 font-semibold hover:underline">{{ $row['schools'] }}</button>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="hidden sm:block flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden max-w-[60px]">
                                            <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $revPct }}%"></div>
                                        </div>
                                        @if ($row['revenue'] > 0)
                                            <button wire:click="openDetail('{{ $row['date'] }}', 'revenue')" class="text-emerald-700 font-semibold hover:underline whitespace-nowrap">
                                                ₹{{ number_format($row['revenue']) }}
                                            </button>
                                        @else
                                            <span class="text-gray-300 whitespace-nowrap">—</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-2.5">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="hidden sm:block flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden max-w-[60px]">
                                            <div class="h-full bg-amber-500 rounded-full" style="width: {{ $feePct }}%"></div>
                                        </div>
                                        @if ($row['fees'] > 0)
                                            <button wire:click="openDetail('{{ $row['date'] }}', 'fees')" class="text-amber-700 font-semibold hover:underline whitespace-nowrap">
                                                ₹{{ number_format($row['fees']) }}
                                            </button>
                                        @else
                                            <span class="text-gray-300 whitespace-nowrap">—</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center px-3 py-2.5">
                                    @if ($row['credit'] > 0)
                                        <button wire:click="openDetail('{{ $row['date'] }}', 'credit')" class="text-rose-700 font-semibold hover:underline">{{ $row['credit'] }}</button>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="text-center px-3 py-2.5">
                                    @if ($row['support'] > 0)
                                        <button wire:click="openDetail('{{ $row['date'] }}', 'support')" class="text-sky-700 font-semibold hover:underline">{{ $row['support'] }}</button>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="text-center px-3 py-2.5">
                                    @if ($row['enquiries'] > 0)
                                        <button wire:click="openDetail('{{ $row['date'] }}', 'enquiries')" class="text-cyan-700 font-semibold hover:underline">{{ $row['enquiries'] }}</button>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
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

    {{-- ══════════════ DAY + METRIC DETAIL DRAWER ══════════════ --}}
    @if ($detailOpen)
        @teleport('body')
        <div class="fixed inset-0 z-[70] overflow-hidden" x-data @keydown.escape.window="$wire.closeDetail()">
            <div class="absolute inset-0 bg-black/30 backdrop-blur-[1.5px]" wire:click="closeDetail"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-lg bg-white shadow-2xl flex flex-col"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-x-full opacity-0"
                x-transition:enter-end="translate-x-0 opacity-100">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-base font-bold text-gray-900 truncate">{{ $detailLabel }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ count($detailRows) }} record{{ count($detailRows) === 1 ? '' : 's' }}</p>
                    </div>
                    <button wire:click="closeDetail"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-2">
                    @forelse ($detailRows as $row)
                        <div class="py-3 border-b border-gray-50 last:border-0 flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-800 truncate">{{ $row['title'] ?? '—' }}</p>
                                @if (!empty($row['subtitle']))
                                    <p class="text-xs text-gray-500 truncate">{{ $row['subtitle'] }}</p>
                                @endif
                                @if (!empty($row['meta']))
                                    <p class="text-[11px] text-gray-400 truncate mt-0.5">{{ $row['meta'] }}</p>
                                @endif
                            </div>
                            <div class="text-right flex-shrink-0">
                                @if (isset($row['amount']))
                                    <p class="text-sm font-bold text-emerald-600">₹{{ number_format($row['amount'], 0) }}</p>
                                @endif
                                @if (!empty($row['time']))
                                    <p class="text-[11px] text-gray-400 mt-0.5">{{ $row['time'] }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 text-center py-10">No records found for this day.</p>
                    @endforelse
                </div>
            </div>
        </div>
        @endteleport
    @endif
</div>
