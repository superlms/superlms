<div class="min-h-screen bg-gray-50">
    @include('livewire.super-admin.partials.dash-styles')

    {{-- Reports' chips: one family with Profile's (in Tailwind's components
         layer, prefixed so no global class of the same name gets in). --}}
    <style>
        @layer components {
            .rp-chip {
                display: inline-flex; align-items: center; gap: .3rem; height: 1.375rem; padding: 0 .55rem;
                border-radius: 9999px; border: 1px solid transparent; white-space: nowrap;
                font-size: .6875rem; font-weight: 600; line-height: 1; font-variant-numeric: tabular-nums;
            }
            .rp-chip > svg { width: .75rem; height: .75rem; flex-shrink: 0; }
            .rp-chip.rp-violet  { background: #f5f3ff; color: #6d28d9; border-color: #e9e3fe; }
            .rp-chip.rp-emerald { background: #ecfdf5; color: #047857; border-color: #d3f5e5; }
            .rp-chip.rp-rose    { background: #fff1f2; color: #be123c; border-color: #ffe1e5; }
            .rp-chip.rp-amber   { background: #fffbeb; color: #b45309; border-color: #fdefc8; }
            .rp-chip.rp-sky     { background: #f0f9ff; color: #0369a1; border-color: #dff1fd; }
            .rp-chip.rp-gray    { background: #f8fafc; color: #64748b; border-color: #e9edf2; }
            .rp-icon { width: 2.25rem; height: 2.25rem; border-radius: .75rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        }
    </style>

    @php
        // key => [label, money?, icon, colour]. Credit is off the Super Admin, so off here too.
        $metricMeta = [
            'revenue'   => ['Platform Revenue', true,  'banknotes',              'amber'],
            'fees'      => ['Fees Collected',   true,  'wallet',                 'violet'],
            'students'  => ['New Students',     false, 'users',                  'sky'],
            'schools'   => ['New Schools',      false, 'building-office-2',      'violet'],
            'teachers'  => ['New Teachers',     false, 'academic-cap',           'emerald'],
            'support'   => ['Support Tickets',  false, 'lifebuoy',               'rose'],
            'enquiries' => ['Enquiries',        false, 'chat-bubble-left-right', 'sky'],
        ];
        $tones = [
            'violet'  => ['#f5f3ff', '#7c3aed'],
            'sky'     => ['#f0f9ff', '#0284c7'],
            'emerald' => ['#ecfdf5', '#059669'],
            'amber'   => ['#fffbeb', '#d97706'],
            'rose'    => ['#fff1f2', '#e11d48'],
            'ink'     => ['#f3f4f6', '#111827'],
        ];
        $fmt   = fn($v, $money) => $money ? '₹' . number_format((float) $v) : number_format((int) $v);
        $short = fn($v) => \App\Support\DashboardChart::inrShort((float) $v);
        $monthLabel = $monthOptions[$selectedMonth] ?? $selectedMonth;
        $isCurrent  = $selectedMonth === now()->format('Y-m');

        // Today against yesterday: [chip tone, chip text, the words after it].
        $vsYesterday = function (string $k, bool $money) use ($today, $yesterday, $fmt) {
            $d = (float) ($today[$k] ?? 0) - (float) ($yesterday[$k] ?? 0);
            if ($d == 0) {
                return ['rp-gray', 'Same', 'as yesterday'];
            }
            return [$d > 0 ? 'rp-emerald' : 'rp-rose', ($d > 0 ? '+' : '−') . $fmt(abs($d), $money), 'vs yesterday'];
        };

        // What each KPI says at its foot.
        $b = $breakdowns;
        $kpiSub = [
            'revenue'   => number_format($b['counts']['receipts'] ?? 0) . ' receipts' . (!empty($monthStats['bestRevenue']) ? ' · best ' . $monthStats['bestRevenue']['label'] : ''),
            'fees'      => number_format($b['counts']['payments'] ?? 0) . ' payments · ' . $short($monthStats['avgFees'] ?? 0) . ' a day',
            'students'  => ($monthStats['avgStudents'] ?? 0) . ' a day · ' . count($b['studentsBySchool'] ?? []) . (count($b['studentsBySchool'] ?? []) >= 6 ? '+' : '') . ' schools',
            'schools'   => number_format($snapshot['schools'] ?? 0) . ' schools in all',
            'teachers'  => number_format($snapshot['teachers'] ?? 0) . ' teachers in all',
            'support'   => ($b['support']['replied'] ?? 0) . ' replied · ' . ($b['support']['pending'] ?? 0) . ' pending',
            'enquiries' => ($b['enquiries']['demo'] ?? 0) . ' demo · ' . ($b['enquiries']['contact'] ?? 0) . ' contact',
        ];

        // The charts' figures (days to come are left empty, so lines stop at today).
        $upToToday = fn($m) => array_map(fn($r) => $r['isFuture'] ? null : $r[$m], $rows);
        $reportCharts = [
            'labels'   => array_map(fn($r) => (int) substr($r['date'], 8), $rows),
            'dates'    => array_column($rows, 'date'),
            'titles'   => array_map(fn($r) => $r['label'] . ' · ' . $r['sub'], $rows),
            'fees'     => $upToToday('fees'),
            'revenue'  => $upToToday('revenue'),
            'students' => $upToToday('students'),
            'teachers' => $upToToday('teachers'),
        ];

        $palette  = ['#7c3aed', '#111827', '#38bdf8', '#f59e0b', '#10b981', '#f43f5e'];
        $withTone = fn(array $list) => array_map(fn($s, $i) => $s + ['color' => $palette[$i % count($palette)]], $list, array_keys($list));
    @endphp

    {{-- The charts' figures: refreshed by every re-render, read again after it. --}}
    <div class="hidden" data-sa-charts="reports" data-payload="{{ json_encode($reportCharts) }}"></div>

    {{-- ══════════════════════════════ HEADER ══════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3 sticky top-0 z-40">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:min-h-9">
            <div class="min-w-0 sm:flex sm:items-baseline sm:gap-2">
                <h1 class="min-w-0 text-lg sm:text-xl font-bold text-gray-900 truncate">Reports</h1>
                <p class="text-xs text-gray-400 mt-0.5 sm:mt-0 truncate">{{ now()->format('l, d M Y') }}</p>
            </div>
            <div class="flex items-center gap-2" wire:loading.class="opacity-60" wire:target="setMonth">
                <select wire:change="setMonth($event.target.value)" aria-label="Report month"
                    class="h-9 text-sm bg-white border border-gray-200 rounded-lg pl-3 pr-8 text-gray-700 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500">
                    @foreach ($monthOptions as $key => $label)
                        <option value="{{ $key }}" @selected($key === $selectedMonth)>{{ $label }}</option>
                    @endforeach
                </select>
                <button wire:click="exportCsv"
                    class="h-9 inline-flex items-center gap-1.5 px-3 text-sm font-medium text-white bg-violet-600 rounded-lg hover:bg-violet-700 transition-colors">
                    <x-icon name="arrow-down-tray" class="w-4 h-4" />
                    Export
                </button>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 space-y-5">

        {{-- ══════════════════════════════ TODAY ══════════════════════════════
             Each figure opens who / what made it up; each says how it stands
             against yesterday. --}}
        <section>
            <div class="flex items-baseline justify-between gap-3 mb-3">
                <h2 class="sa-card-title">Today</h2>
                <span class="text-xs text-gray-400">{{ $today['label'] ?? now()->format('l, d M Y') }}</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-7 gap-3 sm:gap-4">
                @foreach ($metricMeta as $k => [$label, $isMoney, $icon, $tone])
                    @php
                        $val = $today[$k] ?? 0;
                        [$chipTone, $chipText, $chipAfter] = $vsYesterday($k, $isMoney);
                        [$tint, $ink] = $tones[$tone];
                    @endphp
                    <button type="button"
                        @if ($val > 0) wire:click="openDetail('{{ $today['date'] }}', '{{ $k }}')" @endif
                        @disabled($val <= 0)
                        class="sa-card px-4 py-4 min-w-0 text-left flex flex-col {{ $val > 0 ? 'cursor-pointer' : 'cursor-default' }}">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-xs font-medium text-gray-500 leading-snug line-clamp-2 min-h-[2rem]">{{ $label }}</p>
                            <span class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0" style="background: {{ $tint }}; color: {{ $ink }}">
                                <x-icon name="{{ $icon }}" class="w-3.5 h-3.5" />
                            </span>
                        </div>
                        <p class="text-xl leading-none font-semibold mt-1.5 tabular-nums truncate text-gray-900" title="{{ $fmt($val, $isMoney) }}">{{ $fmt($val, $isMoney) }}</p>
                        <p class="mt-2.5 flex flex-wrap items-center gap-x-1.5 gap-y-1">
                            <span class="rp-chip {{ $chipTone }}">{{ $chipText }}</span>
                            <span class="text-[11px] text-gray-400 whitespace-nowrap">{{ $chipAfter }}</span>
                        </p>
                    </button>
                @endforeach
            </div>
        </section>

        {{-- ═══════════════════════════ THE MONTH ═══════════════════════════
             Each card: the month's figure, its days as a sparkline, and the
             change against the month before over the same days. --}}
        <section class="space-y-5">
            <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                <div class="flex items-baseline gap-2 min-w-0">
                    <h2 class="sa-card-title">{{ $monthLabel }}</h2>
                    <span class="rp-chip rp-violet">{{ $isCurrent ? 'Month to date · ' . ($monthStats['daysGone'] ?? 0) . ' of ' . ($monthStats['days'] ?? 0) . ' days' : ($monthStats['days'] ?? 0) . ' days' }}</span>
                </div>
                <span class="text-xs text-gray-400">Click a figure for its full detail · changes are {{ $compareLabel }}</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
                @foreach ($metricMeta as $k => [$label, $isMoney, $icon, $tone])
                    @php $total = $totals[$k] ?? 0; @endphp
                    @include('livewire.super-admin.partials.dash-kpi', [
                        'label'   => $label,
                        'value'   => $isMoney ? $short($total) : number_format((int) $total),
                        'icon'    => $icon,
                        'color'   => $tone,
                        'series'  => array_map(fn($r) => (float) $r[$k], array_values(array_filter($rows, fn($r) => !$r['isFuture']))),
                        'trend'   => \App\Support\DashboardChart::trend((float) $total, (float) ($prevTotals[$k] ?? 0)),
                        'compare' => $compareLabel,
                        'sub'     => $kpiSub[$k] ?? null,
                        'click'   => $total > 0 ? "openMonthDetail('{$k}')" : null,
                        'href'    => null,
                    ])
                @endforeach

                {{-- The month in a few lines --}}
                <div class="sa-card-dark p-5 min-w-0 flex flex-col">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-[15px] font-semibold text-white">Month at a Glance</h3>
                            <p class="text-xs text-white/50 mt-0.5">{{ $monthLabel }}</p>
                        </div>
                        <span class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center flex-shrink-0"><x-icon name="sparkles" class="w-4 h-4 text-violet-200" /></span>
                    </div>
                    <div class="mt-4 flex items-baseline gap-1.5">
                        <span class="text-[26px] leading-none font-semibold tabular-nums">{{ $monthStats['activeDays'] ?? 0 }}</span>
                        <span class="text-xs text-white/50">of {{ $monthStats['daysGone'] ?? 0 }} days with activity</span>
                    </div>
                    <div class="mt-2 h-1.5 rounded-full bg-white/10">
                        <div class="h-1.5 rounded-full bg-violet-400" style="width: {{ ($monthStats['daysGone'] ?? 0) > 0 ? round(($monthStats['activeDays'] ?? 0) / $monthStats['daysGone'] * 100) : 0 }}%"></div>
                    </div>
                    <dl class="mt-auto pt-4 space-y-2.5 text-sm">
                        @foreach ([
                            ['Best fee day',     $monthStats['bestFees'] ?? null,    fn($v) => $short($v)],
                            ['Best revenue day', $monthStats['bestRevenue'] ?? null, fn($v) => $short($v)],
                            ['Most sign-ups',    $monthStats['busiest'] ?? null,     fn($v) => number_format($v) . ' new'],
                        ] as [$label, $day, $show])
                            <div class="flex items-start justify-between gap-2 min-w-0">
                                <dt class="min-w-0">
                                    <p class="text-white/60 whitespace-nowrap">{{ $label }}</p>
                                    <p class="text-[11px] text-white/40 truncate">{{ $day ? $day['label'] : 'Nothing yet' }}</p>
                                </dt>
                                <dd class="font-semibold tabular-nums whitespace-nowrap">{{ $day ? $show($day['value']) : '—' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>
        </section>

        {{-- ═══════════════ Fees collected (area) · by payment mode (donut) ═══════════════ --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
            <div class="sa-card p-5 xl:col-span-2 min-w-0">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
                    <div>
                        <h3 class="sa-card-title">Fees Collected · Day by Day</h3>
                        <p class="sa-card-sub">What the schools collected from students, {{ $monthLabel }} · click a day for its payments</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="sa-chip"><span class="sa-dot" style="background:#7c3aed"></span>{{ $short($totals['fees'] ?? 0) }} this month</span>
                        @php $feeTrend = \App\Support\DashboardChart::trend((float) ($totals['fees'] ?? 0), (float) ($prevTotals['fees'] ?? 0)); @endphp
                        @if ($feeTrend)
                            <span class="rp-chip {{ $feeTrend['up'] ? 'rp-emerald' : 'rp-rose' }}">{{ $feeTrend['up'] ? '▲' : '▼' }} {{ rtrim(rtrim(number_format($feeTrend['pct'], 1), '0'), '.') }}% {{ $compareLabel }}</span>
                        @endif
                    </div>
                </div>
                <div class="h-64 relative" wire:ignore wire:key="chart-reportFeesChart">
                    <canvas id="reportFeesChart"></canvas>
                </div>
            </div>

            <div class="sa-card p-5 min-w-0">
                <div class="flex items-start justify-between gap-3 mb-5">
                    <div>
                        <h3 class="sa-card-title">Fees by Payment Mode</h3>
                        <p class="sa-card-sub">{{ number_format($b['counts']['payments'] ?? 0) }} payments in {{ $monthLabel }}</p>
                    </div>
                </div>
                @if (count($b['feesByMode'] ?? []))
                    @include('livewire.super-admin.partials.dash-donut', [
                        'segments' => $withTone($b['feesByMode']), 'center' => $short($totals['fees'] ?? 0), 'centerLabel' => 'Collected',
                        'size' => 150, 'legend' => true, 'format' => fn($v) => $short($v),
                    ])
                @else
                    <p class="py-16 text-center text-sm text-gray-400">No fees collected in {{ $monthLabel }}</p>
                @endif
            </div>
        </div>

        {{-- ═══════════════ Platform revenue (bars) · by school ═══════════════ --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
            <div class="sa-card p-5 xl:col-span-2 min-w-0">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
                    <div>
                        <h3 class="sa-card-title">Platform Revenue · Day by Day</h3>
                        <p class="sa-card-sub">What the schools paid SuperLMS, every receipt on its day · click a day for its receipts</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="sa-chip" style="background:#fffbeb;color:#b45309"><span class="sa-dot" style="background:#f59e0b"></span>{{ $short($totals['revenue'] ?? 0) }} this month</span>
                        @foreach (array_slice($b['revenueByMode'] ?? [], 0, 3) as $mode)
                            <span class="rp-chip rp-gray">{{ $mode['label'] }} {{ $short($mode['value']) }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="h-64 relative" wire:ignore wire:key="chart-reportRevenueChart">
                    <canvas id="reportRevenueChart"></canvas>
                </div>
            </div>

            @include('livewire.super-admin.partials.reports-top-list', [
                'title' => 'Revenue by School', 'sub' => 'Who paid SuperLMS in ' . $monthLabel,
                'list' => $b['revenueBySchool'] ?? [], 'money' => true, 'unit' => 'receipt', 'color' => '#f59e0b',
                'empty' => 'No platform revenue in ' . $monthLabel, 'more' => route('super-admin.fees'),
            ])
        </div>

        {{-- ═══════════════ Registrations (bars) · new students by school ═══════════════ --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
            <div class="sa-card p-5 xl:col-span-2 min-w-0">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                    <div>
                        <h3 class="sa-card-title">Registrations · Day by Day</h3>
                        <p class="sa-card-sub">Students and teachers added, {{ $monthLabel }}</p>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        <span class="flex items-center gap-1.5"><span class="sa-dot" style="background:#7c3aed"></span>Students · {{ number_format($totals['students'] ?? 0) }}</span>
                        <span class="flex items-center gap-1.5"><span class="sa-dot" style="background:#111827"></span>Teachers · {{ number_format($totals['teachers'] ?? 0) }}</span>
                    </div>
                </div>
                <div class="h-64 relative" wire:ignore wire:key="chart-reportRegChart">
                    <canvas id="reportRegChart"></canvas>
                </div>
            </div>

            @include('livewire.super-admin.partials.reports-top-list', [
                'title' => 'New Students by School', 'sub' => 'Where the ' . number_format($totals['students'] ?? 0) . ' new students joined',
                'list' => $b['studentsBySchool'] ?? [], 'money' => false, 'unit' => null, 'color' => '#7c3aed',
                'empty' => 'No new students in ' . $monthLabel, 'more' => route('super-admin.students'),
            ])
        </div>

        {{-- ═══════════════ Fees by school · support · enquiries ═══════════════ --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @include('livewire.super-admin.partials.reports-top-list', [
                'title' => 'Top Schools by Fees Collected', 'sub' => 'From their students in ' . $monthLabel,
                'list' => $b['feesBySchool'] ?? [], 'money' => true, 'unit' => 'payment', 'color' => '#111827',
                'empty' => 'No fees collected in ' . $monthLabel, 'more' => null,
            ])

            @php $sup = $b['support'] ?? ['total' => 0, 'replied' => 0, 'pending' => 0, 'topics' => []]; @endphp
            <div class="sa-card p-5 min-w-0 flex flex-col">
                <div class="flex items-start gap-3 mb-4">
                    <span class="rp-icon" style="background:#fff1f2;color:#e11d48"><x-icon name="lifebuoy" class="w-4 h-4" /></span>
                    <div class="flex-1 min-w-0">
                        <h3 class="sa-card-title">Support Tickets</h3>
                        <p class="sa-card-sub">{{ $sup['total'] }} raised by schools in {{ $monthLabel }}</p>
                    </div>
                    <a href="{{ route('super-admin.support') }}" class="text-xs font-medium text-violet-600 hover:text-violet-800 whitespace-nowrap">Open →</a>
                </div>
                @if ($sup['total'] > 0)
                    <div class="flex items-center gap-4">
                        @include('livewire.super-admin.partials.dash-donut', [
                            'segments' => [
                                ['label' => 'Replied', 'value' => $sup['replied'], 'color' => '#10b981'],
                                ['label' => 'Pending', 'value' => $sup['pending'], 'color' => '#f43f5e'],
                            ],
                            'center' => round($sup['replied'] / $sup['total'] * 100) . '%', 'centerLabel' => 'Replied', 'size' => 104, 'legend' => false,
                        ])
                        <dl class="flex-1 min-w-0 space-y-2 text-sm">
                            <div class="flex items-center justify-between gap-2"><dt class="flex items-center gap-2 text-gray-500"><span class="sa-dot" style="background:#10b981"></span>Replied</dt><dd class="font-semibold tabular-nums">{{ $sup['replied'] }}</dd></div>
                            <div class="flex items-center justify-between gap-2"><dt class="flex items-center gap-2 text-gray-500"><span class="sa-dot" style="background:#f43f5e"></span>Pending</dt><dd class="font-semibold tabular-nums {{ $sup['pending'] > 0 ? 'text-rose-600' : '' }}">{{ $sup['pending'] }}</dd></div>
                        </dl>
                    </div>
                    @if (count($sup['topics']))
                        <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide mt-5 mb-2">Most asked about</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($sup['topics'] as $topic)
                                <span class="rp-chip rp-gray max-w-full" title="{{ $topic['label'] }}"><span class="truncate max-w-[12rem]">{{ $topic['label'] }}</span><span class="text-gray-400">{{ $topic['count'] }}</span></span>
                            @endforeach
                        </div>
                    @endif
                @else
                    <p class="py-10 text-center text-sm text-gray-400">No tickets in {{ $monthLabel }}</p>
                @endif
            </div>

            @php
                $enq = $b['enquiries'] ?? ['demo' => 0, 'contact' => 0];
                $enqTotal = $enq['demo'] + $enq['contact'];
            @endphp
            <div class="sa-card p-5 min-w-0 flex flex-col md:col-span-2 xl:col-span-1">
                <div class="flex items-start gap-3 mb-4">
                    <span class="rp-icon" style="background:#f0f9ff;color:#0284c7"><x-icon name="chat-bubble-left-right" class="w-4 h-4" /></span>
                    <div class="flex-1 min-w-0">
                        <h3 class="sa-card-title">Website Enquiries</h3>
                        <p class="sa-card-sub">{{ $enqTotal }} from superlms.in in {{ $monthLabel }}</p>
                    </div>
                    <a href="{{ route('super-admin.enquiries') }}" class="text-xs font-medium text-violet-600 hover:text-violet-800 whitespace-nowrap">Open →</a>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    @foreach ([['Demo requests', $enq['demo'], '#7c3aed'], ['Contact messages', $enq['contact'], '#38bdf8']] as [$label, $val, $color])
                        <div class="rounded-xl bg-gray-50 ring-1 ring-gray-100 px-3.5 py-3 min-w-0">
                            <p class="text-xs text-gray-400 truncate flex items-center gap-1.5"><span class="sa-dot" style="background: {{ $color }}"></span>{{ $label }}</p>
                            <p class="text-xl font-semibold text-gray-900 tabular-nums mt-1">{{ number_format($val) }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 h-2 rounded-full bg-gray-100 overflow-hidden flex">
                    @if ($enqTotal > 0)
                        <div style="width: {{ round($enq['demo'] / $enqTotal * 100) }}%; background:#7c3aed"></div>
                        <div style="width: {{ 100 - round($enq['demo'] / $enqTotal * 100) }}%; background:#38bdf8"></div>
                    @endif
                </div>
                <div class="mt-auto pt-4 flex items-center justify-between gap-2 text-xs text-gray-400">
                    <span>All time</span>
                    <span class="font-semibold text-gray-700 tabular-nums">{{ number_format($snapshot['enquiries'] ?? 0) }}</span>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════ ALL-TIME TOTALS ═══════════════════════ --}}
        <section>
            <div class="flex items-baseline justify-between gap-3 mb-3">
                <h2 class="sa-card-title">All-Time Totals</h2>
                <span class="text-xs text-gray-400">Since SuperLMS began · the share {{ $monthLabel }} added</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-7 gap-3 sm:gap-4">
                @foreach ($metricMeta as $k => [$label, $isMoney, $icon, $tone])
                    @php
                        $all   = (float) ($snapshot[$k] ?? 0);
                        $added = (float) ($totals[$k] ?? 0);
                        $share = $all > 0 ? min(100, round($added / $all * 100)) : 0;
                        [$tint, $ink] = $tones[$tone];
                    @endphp
                    <div class="sa-card px-4 py-4 min-w-0">
                        <p class="text-xs font-medium text-gray-500 truncate">{{ $label }}</p>
                        <p class="text-xl leading-none font-semibold text-gray-900 mt-2.5 tabular-nums truncate" title="{{ $fmt($all, $isMoney) }}">{{ $isMoney ? $short($all) : number_format($all) }}</p>
                        <div class="mt-3 h-1 rounded-full bg-gray-100"><div class="h-1 rounded-full" style="width: {{ max($added > 0 ? 3 : 0, $share) }}%; background: {{ $ink }}"></div></div>
                        <p class="text-[11px] text-gray-400 mt-1.5 truncate">{{ $added > 0 ? '+' . ($isMoney ? $short($added) : number_format($added)) . ' in ' . $monthLabel : 'None in ' . $monthLabel }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ═══════════════════════════ DAY BY DAY ════════════════════════════ --}}
        <div class="sa-card min-w-0 overflow-hidden">
            <div class="px-5 pt-5 pb-4 flex flex-wrap items-start justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0">
                    <span class="rp-icon" style="background:#f5f3ff;color:#7c3aed"><x-icon name="calendar-days" class="w-4 h-4" /></span>
                    <div class="min-w-0">
                        <h3 class="sa-card-title">Day by Day</h3>
                        <p class="sa-card-sub">{{ count($rows) }} days of {{ $monthLabel }} · click any number for who or what made it</p>
                    </div>
                </div>
                {{-- Month filter: April 2026 → this month. --}}
                <div class="flex items-center gap-2" wire:loading.class="opacity-60" wire:target="setMonth">
                    <select wire:change="setMonth($event.target.value)" aria-label="Day by day month"
                        class="h-9 text-sm bg-white border border-gray-200 rounded-lg pl-3 pr-8 text-gray-700 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500">
                        @foreach ($monthOptions as $key => $label)
                            <option value="{{ $key }}" @selected($key === $selectedMonth)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button wire:click="exportCsv" title="Download this month as a CSV"
                        class="h-9 inline-flex items-center gap-1.5 px-3 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <x-icon name="arrow-down-tray" class="w-4 h-4" />
                        CSV
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto border-t border-gray-100">
                <table class="w-full text-sm min-w-[900px]">
                    <thead class="text-gray-400 text-[11px] uppercase tracking-wide">
                        <tr class="border-b border-gray-100 bg-gray-50/60">
                            <th class="text-left font-medium px-5 py-2.5 whitespace-nowrap">Date</th>
                            <th class="text-center font-medium px-3 py-2.5">Students</th>
                            <th class="text-center font-medium px-3 py-2.5">Teachers</th>
                            <th class="text-center font-medium px-3 py-2.5">Schools</th>
                            <th class="text-right font-medium px-3 py-2.5 min-w-[140px]">Revenue</th>
                            <th class="text-right font-medium px-3 py-2.5 min-w-[150px]">Fees Collected</th>
                            <th class="text-center font-medium px-3 py-2.5">Support</th>
                            <th class="text-center font-medium px-5 py-2.5">Enquiries</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($rows as $row)
                            @php
                                $revPct = min(100, ($row['revenue'] / ($peaks['revenue'] ?? 1)) * 100);
                                $feePct = min(100, ($row['fees'] / ($peaks['fees'] ?? 1)) * 100);
                                $weekend = in_array($row['sub'], ['Sat', 'Sun'], true);
                            @endphp
                            <tr wire:key="day-{{ $row['date'] }}" class="{{ $row['isToday'] ? 'bg-violet-50/50' : 'hover:bg-gray-50/70' }} {{ $row['isFuture'] ? 'opacity-40' : '' }}">
                                <td class="px-5 py-2.5 whitespace-nowrap">
                                    <span class="font-medium text-gray-800 tabular-nums">{{ $row['label'] }}</span>
                                    <span class="text-xs ml-1 {{ $weekend ? 'text-rose-400' : 'text-gray-400' }}">{{ $row['sub'] }}</span>
                                    @if ($row['isToday'])
                                        <span class="rp-chip rp-violet ml-1.5">Today</span>
                                    @endif
                                </td>
                                @foreach (['students', 'teachers', 'schools'] as $m)
                                    <td class="text-center px-3 py-2.5 tabular-nums">
                                        @if ($row[$m] > 0)
                                            <button wire:click="openDetail('{{ $row['date'] }}', '{{ $m }}')" class="min-w-7 px-1.5 py-0.5 rounded-md font-semibold text-gray-800 hover:bg-violet-50 hover:text-violet-700">{{ $row[$m] }}</button>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                @endforeach
                                @foreach ([['revenue', $revPct, '#f59e0b'], ['fees', $feePct, '#7c3aed']] as [$m, $pct, $barColor])
                                    <td class="px-3 py-2.5">
                                        <div class="flex items-center justify-end gap-2">
                                            <div class="hidden sm:block flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden max-w-[64px]">
                                                <div class="h-full rounded-full" style="width: {{ $pct }}%; background: {{ $barColor }}"></div>
                                            </div>
                                            @if ($row[$m] > 0)
                                                <button wire:click="openDetail('{{ $row['date'] }}', '{{ $m }}')" class="px-1.5 py-0.5 rounded-md font-semibold text-gray-800 hover:bg-violet-50 hover:text-violet-700 whitespace-nowrap tabular-nums">
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
                                            <button wire:click="openDetail('{{ $row['date'] }}', '{{ $m }}')" class="min-w-7 px-1.5 py-0.5 rounded-md font-semibold text-gray-800 hover:bg-violet-50 hover:text-violet-700">{{ $row[$m] }}</button>
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
                        <tr class="bg-gray-50/80 border-t border-gray-200 font-semibold text-gray-900 tabular-nums">
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
    </div>

    {{-- ══════════════ DAY + METRIC DETAIL DRAWER ══════════════ --}}
    @if ($detailOpen)
        @php
            $detailSum = array_sum(array_map(fn($r) => (float) ($r['amount'] ?? 0), $detailRows));
            $hasMoney  = count(array_filter($detailRows, fn($r) => isset($r['amount']))) > 0;
        @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden" x-data @keydown.escape.window="$wire.closeDetail()">
            <div class="absolute inset-0 bg-black/30 backdrop-blur-[1.5px]" wire:click="closeDetail"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-lg bg-white shadow-2xl flex flex-col sm:rounded-l-2xl"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-x-full opacity-0"
                x-transition:enter-end="translate-x-0 opacity-100">

                <div class="flex items-start justify-between gap-3 px-6 py-4 border-b border-gray-100 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-base font-semibold text-gray-900 truncate">{{ $detailLabel }}</h2>
                        <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                            <span class="rp-chip rp-violet">{{ count($detailRows) }} record{{ count($detailRows) === 1 ? '' : 's' }}</span>
                            @if ($hasMoney)
                                <span class="rp-chip rp-emerald">₹{{ number_format($detailSum) }} in all</span>
                            @endif
                        </div>
                    </div>
                    <button wire:click="closeDetail"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <x-icon name="x-mark" class="w-5 h-5" />
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
                    @forelse ($detailRows as $row)
                        <div class="px-6 py-3 flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3 min-w-0">
                                <span class="w-8 h-8 rounded-full bg-violet-50 text-violet-600 flex items-center justify-center text-xs font-semibold flex-shrink-0">{{ strtoupper(mb_substr($row['title'] ?? '—', 0, 1)) }}</span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-800 truncate">{{ $row['title'] ?? '—' }}</p>
                                    @if (!empty($row['subtitle']))
                                        <p class="text-xs text-gray-500 truncate">{{ $row['subtitle'] }}</p>
                                    @endif
                                    @if (!empty($row['meta']))
                                        <p class="text-xs text-gray-400 truncate mt-0.5">{{ $row['meta'] }}</p>
                                    @endif
                                </div>
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

    {{-- The three charts, kept drawn through every re-render (see partials.dash-charts-js).
         A bar or point opens that day's detail. --}}
    @include('livewire.super-admin.partials.dash-charts-js')
    <script>
        (function () {
            const VIOLET = '#7c3aed', INK = '#111827', AMBER = '#f59e0b';
            const short = v => v >= 1e7 ? (v / 1e7).toFixed(1) + 'Cr' : v >= 1e5 ? (v / 1e5).toFixed(1) + 'L' : v >= 1e3 ? (v / 1e3).toFixed(0) + 'k' : v;
            const inr = v => '₹' + Number(v || 0).toLocaleString('en-IN');
            const axis = { color: '#9ca3af', font: { size: 11 } };
            const tooltip = { backgroundColor: INK, padding: 10, cornerRadius: 8 };
            const x = { grid: { display: false }, border: { display: false }, ticks: Object.assign({ maxRotation: 0, autoSkip: true, maxTicksLimit: 16 }, axis) };
            const yMoney = { beginAtZero: true, border: { display: false }, grid: { color: 'rgba(17,24,39,0.05)' }, ticks: Object.assign({ maxTicksLimit: 5, callback: v => '₹' + short(v) }, axis) };
            const yCount = { beginAtZero: true, border: { display: false }, grid: { color: 'rgba(17,24,39,0.05)' }, ticks: Object.assign({ precision: 0, maxTicksLimit: 5 }, axis) };

            // A click on a day opens that day's records for the metric.
            const opener = (p, metric) => (evt, els, chart) => {
                if (!els.length) return;
                const i = els[0].index;
                const v = chart.data.datasets[els[0].datasetIndex].data[i];
                const host = chart.canvas.closest('[wire\\:id]');
                if (!v || !host || !window.Livewire) return;
                const m = typeof metric === 'function' ? metric(els[0].datasetIndex) : metric;
                window.Livewire.find(host.getAttribute('wire:id')).call('openDetail', p.dates[i], m);
            };
            const titles = p => ({ title: items => p.titles[items[0].dataIndex] });

            saCharts.define('reports', {
                reportFeesChart: p => ({
                    type: 'line',
                    data: {
                        labels: p.labels || [],
                        datasets: [{
                            label: 'Fees collected', data: p.fees || [],
                            borderColor: VIOLET, borderWidth: 2.5, tension: 0.4, fill: true, spanGaps: false,
                            backgroundColor: c => {
                                const a = c.chart.chartArea;
                                if (!a) return 'rgba(124,58,237,0.12)';
                                const g = c.chart.ctx.createLinearGradient(0, a.top, 0, a.bottom);
                                g.addColorStop(0, 'rgba(124,58,237,0.28)');
                                g.addColorStop(1, 'rgba(124,58,237,0)');
                                return g;
                            },
                            pointRadius: 0, pointHoverRadius: 5, pointHitRadius: 12,
                            pointBackgroundColor: '#fff', pointBorderColor: VIOLET, pointBorderWidth: 2.5,
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        onClick: opener(p, 'fees'),
                        plugins: { legend: { display: false }, tooltip: Object.assign({}, tooltip, { displayColors: false, callbacks: Object.assign(titles(p), { label: c => inr(c.parsed.y) }) }) },
                        scales: { x: x, y: yMoney }
                    }
                }),
                reportRevenueChart: p => ({
                    type: 'bar',
                    data: {
                        labels: p.labels || [],
                        datasets: [{ label: 'Platform revenue', data: p.revenue || [], backgroundColor: AMBER, hoverBackgroundColor: '#d97706', borderRadius: 6, borderSkipped: false, maxBarThickness: 16 }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        onClick: opener(p, 'revenue'),
                        plugins: { legend: { display: false }, tooltip: Object.assign({}, tooltip, { displayColors: false, callbacks: Object.assign(titles(p), { label: c => inr(c.parsed.y) }) }) },
                        scales: { x: x, y: yMoney }
                    }
                }),
                reportRegChart: p => ({
                    type: 'bar',
                    data: {
                        labels: p.labels || [],
                        datasets: [
                            { label: 'Students', data: p.students || [], backgroundColor: VIOLET, borderRadius: 6, borderSkipped: false, barPercentage: 0.7, categoryPercentage: 0.6, maxBarThickness: 12 },
                            { label: 'Teachers', data: p.teachers || [], backgroundColor: INK, borderRadius: 6, borderSkipped: false, barPercentage: 0.7, categoryPercentage: 0.6, maxBarThickness: 12 }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        onClick: (evt, els, chart) => {
                            const hit = chart.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);
                            opener(p, i => i === 0 ? 'students' : 'teachers')(evt, hit, chart);
                        },
                        plugins: { legend: { display: false }, tooltip: Object.assign({}, tooltip, { callbacks: titles(p) }) },
                        scales: { x: x, y: yCount }
                    }
                }),
            });
        })();
    </script>
</div>
