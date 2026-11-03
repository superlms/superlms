<div class="min-h-screen bg-gray-50">

    {{-- All chart data lives in this attribute. It is part of the Livewire DOM,
         so every morph (month-window or website-range change) refreshes it and
         the script below re-reads it — charts always render with current data. --}}
    <div id="analytics-data" data-sa-charts="analytics" data-payload="{{ json_encode($chartPayload) }}" class="hidden"></div>

    {{-- The dashboard look (2026-11-03), shared with Home. --}}
    @include('livewire.super-admin.partials.dash-styles')

    @php
        $inr = fn ($v) => '₹' . number_format((float) $v, 0);
        $short = fn ($v) => \App\Support\DashboardChart::inrShort((float) $v);
        $trend = fn (array $s) => \App\Support\DashboardChart::trend((float) (end($s) ?: 0), (float) ($s[count($s) - 2] ?? 0));
        $supportReplyRate = ($supportStats['total'] ?? 0) > 0
            ? round((($supportStats['replied'] ?? 0) / $supportStats['total']) * 100, 1)
            : 0;
        // The tabs are one page now: what each tab showed is a section below.
        $windowLabel = 'Last ' . $months . ' months';
        $ring = ['#7c3aed', '#111827', '#a78bfa', '#38bdf8', '#f59e0b', '#10b981', '#cbd5e1'];
        // A payment-mode split as a donut's parts.
        $modeParts = fn (array $m) => array_map(
            fn ($label, $value, $i) => ['label' => $label, 'value' => (float) $value, 'color' => $ring[$i % count($ring)]],
            $m['labels'] ?? [], $m['data'] ?? [], array_keys($m['data'] ?? [])
        );
        $initial = fn ($name) => strtoupper(mb_substr(trim((string) $name) ?: '?', 0, 1));
    @endphp

    {{-- ══════════════════════════════════════════════════════════
         HEADER — title, date and the window the monthly charts cover
         (the Schools / Students / Rating figures left the header).
    ══════════════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 sm:min-h-15">
            <div class="min-w-0 sm:flex sm:items-baseline sm:gap-2">
                <h1 class="min-w-0 text-lg sm:text-xl font-bold text-gray-900 truncate">Analytics</h1>
                <p class="text-xs text-gray-400 mt-0.5 sm:mt-0 truncate">{{ now()->format('l, d M Y') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                <select wire:model.live="months" title="Months on the monthly charts"
                    class="text-xs bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-indigo-400">
                    <option value="6">Last 6 months</option>
                    <option value="12">Last 12 months</option>
                    <option value="24">Last 24 months</option>
                </select>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 space-y-8">

        {{-- ═══════════════════════ KEY METRICS ═══════════════════════ --}}
        <section class="space-y-4">
            <x-admin.section-heading title="Key Metrics" />

            @php
                $growth = $overviewStats['studentGrowthPct'] ?? 0;
                $regs   = $monthlyRegistrations;
            @endphp
            {{-- The four figures the page leads with: change against last month
                 and a sparkline over the months picked in the header. --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
                @include('livewire.super-admin.partials.dash-kpi', [
                    'label' => 'Total Schools', 'value' => number_format($overviewStats['totalSchools'] ?? 0), 'icon' => 'building-office-2', 'color' => 'violet',
                    'series' => $regs['schools'] ?? [], 'trend' => $trend($regs['schools'] ?? []),
                    'sub' => number_format($overviewStats['activeSchools'] ?? 0) . ' active · ' . number_format($overviewStats['inactiveSchools'] ?? 0) . ' inactive',
                ])
                @include('livewire.super-admin.partials.dash-kpi', [
                    'label' => 'Total Students', 'value' => number_format($overviewStats['totalStudents'] ?? 0), 'icon' => 'users', 'color' => 'sky',
                    'series' => $regs['students'] ?? [], 'trend' => $trend($regs['students'] ?? []),
                    'sub' => number_format($overviewStats['activeStudents'] ?? 0) . ' active',
                ])
                @include('livewire.super-admin.partials.dash-kpi', [
                    'label' => 'Total Teachers', 'value' => number_format($overviewStats['totalTeachers'] ?? 0), 'icon' => 'academic-cap', 'color' => 'emerald',
                    'series' => $regs['teachers'] ?? [], 'trend' => $trend($regs['teachers'] ?? []),
                    'sub' => number_format($overviewStats['activeTeachers'] ?? 0) . ' active',
                ])
                @include('livewire.super-admin.partials.dash-kpi', [
                    'label' => 'Fee Collected', 'value' => $short($feeStats['totalCollected'] ?? 0), 'icon' => 'banknotes', 'color' => 'amber',
                    'series' => $feeMonthly['collected'] ?? [],
                    'trend' => \App\Support\DashboardChart::trend((float) ($feeStats['collectedThisMonth'] ?? 0), (float) ($feeStats['collectedLastMonth'] ?? 0)),
                    'sub' => ($feeStats['collectionRate'] ?? 0) . '% of ' . $short($feeStats['totalFeeToCollect'] ?? 0),
                ])
            </div>

            @include('livewire.super-admin.partials.analytics-tiles', ['cols' => 'grid-cols-2 lg:grid-cols-5', 'items' => [
                ['Avg / School', number_format($overviewStats['avgStudentsPerSchool'] ?? 0, 1), 'students in a school', 'text-gray-900'],
                ['New Schools', number_format($overviewStats['newSchoolsThisMonth'] ?? 0), 'this month', 'text-gray-900'],
                ['New Students', number_format($overviewStats['studentsThisMonth'] ?? 0), ($growth > 0 ? '+' : '') . $growth . '% vs last month (' . number_format($overviewStats['studentsLastMonth'] ?? 0) . ')', 'text-violet-600'],
                ['Rating', ($ratingStats['avg'] ?? 0) . ' ★', number_format($ratingStats['total'] ?? 0) . ' reviews', 'text-gray-900'],
                ['5-Star Reviews', number_format($ratingStats['fiveStar'] ?? 0), 'of ' . number_format($ratingStats['total'] ?? 0), 'text-emerald-600'],
            ]])
        </section>

        {{-- ═══════════════════════ SCHOOLS ═══════════════════════ --}}
        <section class="space-y-4">
            <x-admin.section-heading title="Schools" />

            {{-- Registrations + schools onboarded --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div class="sa-card p-5">
                    <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2 mb-4">
                        <div>
                            <h3 class="sa-card-title">Monthly Registrations</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Students and teachers added · {{ $windowLabel }}</p>
                        </div>
                        <div class="flex items-center gap-3 text-[11px] text-gray-400">
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-violet-600 inline-block"></span> Students</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-gray-900 inline-block"></span> Teachers</span>
                        </div>
                    </div>
                    <div class="h-56 relative" wire:ignore wire:key="chart-overviewMonthlyChart">
                        <canvas id="overviewMonthlyChart"></canvas>
                    </div>
                </div>
                <div class="sa-card p-5">
                    <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2 mb-4">
                        <div>
                            <h3 class="sa-card-title">Schools Onboarded</h3>
                            <p class="text-xs text-gray-400 mt-0.5">New schools · {{ $windowLabel }}</p>
                        </div>
                        <span class="flex items-center gap-1 text-[11px] text-gray-400"><span class="w-2.5 h-2.5 rounded-full bg-violet-600 inline-block"></span> New schools</span>
                    </div>
                    <div class="h-56 relative" wire:ignore wire:key="chart-schoolsMonthlyChart">
                        <canvas id="schoolsMonthlyChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- School size: each band's schools and their share of all schools.
                 The bands from 500 up overlap (a school of 1,600 is in 500+,
                 1000+ and 1500+), as the figures always have. --}}
            @php
                $allSchools = max(1, (int) ($overviewStats['totalSchools'] ?? array_sum($schoolBuckets)));
                $bucketTop  = max(1, (int) max($schoolBuckets ?: [0]));
            @endphp
            <div class="sa-card p-5">
                <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2 mb-4">
                    <div>
                        <h3 class="sa-card-title">Schools by Size</h3>
                        <p class="sa-card-sub">Schools by how many students they have</p>
                    </div>
                    <span class="sa-chip">{{ number_format($overviewStats['totalSchools'] ?? 0) }} schools</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                    @foreach ($schoolBuckets as $label => $count)
                        <div class="rounded-xl bg-gray-50 px-4 py-3.5 min-w-0">
                            <p class="text-xs text-gray-500 truncate">{{ $label }} students</p>
                            <div class="mt-1 flex items-baseline justify-between gap-2">
                                <p class="text-xl font-semibold text-gray-900 tabular-nums">{{ number_format($count) }}</p>
                                <span class="text-xs text-gray-400 tabular-nums">{{ round($count / $allSchools * 100) }}%</span>
                            </div>
                            <div class="mt-2.5 h-1.5 rounded-full bg-white">
                                <div class="h-1.5 rounded-full" style="width: {{ round($count / $bucketTop * 100) }}%; background: {{ $count === $bucketTop ? '#7c3aed' : '#c4b5fd' }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Top schools: rank, the figure with a bar against the first, the other figure under it --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                @foreach ([
                    ['Top 5 by Students', 'The schools with the most students', $topByStudents, 'students', 'teachers'],
                    ['Top 5 by Teachers', 'The schools with the most teachers', $topByTeachers, 'teachers', 'students'],
                ] as [$heading, $subline, $list, $main, $other])
                    @php $topMax = max(1, (int) max(array_column($list, $main) ?: [0])); @endphp
                    <div class="sa-card p-5 min-w-0">
                        <div class="mb-4">
                            <h3 class="sa-card-title">{{ $heading }}</h3>
                            <p class="sa-card-sub">{{ $subline }}</p>
                        </div>
                        @if (count($list))
                            <div class="space-y-4">
                                @foreach ($list as $s)
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="w-6 h-6 rounded-lg text-[11px] font-semibold flex items-center justify-center flex-shrink-0 tabular-nums {{ $loop->first ? 'bg-violet-600 text-white' : 'bg-gray-100 text-gray-500' }}">{{ $s['rank'] }}</span>
                                        @if ($s['logo'])
                                            <img src="{{ $s['logo'] }}" alt="" class="w-9 h-9 rounded-xl object-cover border border-gray-100 flex-shrink-0">
                                        @else
                                            <span class="w-9 h-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center text-xs font-semibold flex-shrink-0">{{ $initial($s['name']) }}</span>
                                        @endif
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-baseline justify-between gap-3">
                                                <p class="text-sm font-medium text-gray-800 truncate" title="{{ $s['name'] }}">{{ $s['name'] }}</p>
                                                <p class="text-sm font-semibold text-gray-900 tabular-nums whitespace-nowrap">{{ number_format($s[$main]) }} <span class="text-xs font-normal text-gray-400">{{ $main }}</span></p>
                                            </div>
                                            <div class="mt-1.5 flex items-center gap-3">
                                                <div class="flex-1 h-1.5 rounded-full bg-gray-100">
                                                    <div class="h-1.5 rounded-full" style="width: {{ round($s[$main] / $topMax * 100) }}%; background: {{ $loop->first ? '#7c3aed' : '#c4b5fd' }}"></div>
                                                </div>
                                                <span class="text-[11px] text-gray-400 tabular-nums whitespace-nowrap">{{ number_format($s[$other]) }} {{ $other }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-center py-10 text-gray-400 text-sm">No schools yet</p>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Gender split + rating distribution --}}
            @php
                $genderTotal = (int) array_sum(array_map('intval', $genderSplit ?: []));
            @endphp
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div class="sa-card p-5 min-w-0">
                    <div class="mb-4">
                        <h3 class="sa-card-title">Students by Gender</h3>
                        <p class="sa-card-sub">{{ number_format($genderTotal) }} students</p>
                    </div>
                    @include('livewire.super-admin.partials.dash-donut', [
                        'segments' => [
                            ['label' => 'Male', 'value' => (int) ($genderSplit['male'] ?? 0), 'color' => '#7c3aed'],
                            ['label' => 'Female', 'value' => (int) ($genderSplit['female'] ?? 0), 'color' => '#f472b6'],
                            ['label' => 'Other', 'value' => (int) ($genderSplit['other'] ?? 0), 'color' => '#fbbf24'],
                            ['label' => 'Not set', 'value' => (int) ($genderSplit['unknown'] ?? 0), 'color' => '#e5e7eb'],
                        ],
                        'center' => number_format($genderTotal), 'centerLabel' => 'Students', 'size' => 150, 'legend' => true,
                        'format' => fn ($v) => number_format($v),
                    ])
                </div>
                <div class="sa-card p-5 min-w-0">
                    <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2 mb-4">
                        <h3 class="sa-card-title">Rating Distribution</h3>
                        <span class="text-xs text-gray-400">{{ number_format($ratingStats['total'] ?? 0) }} reviews</span>
                    </div>
                    @if (($ratingStats['total'] ?? 0) > 0)
                        <div class="space-y-2.5">
                            @foreach ($ratingStats['distribution'] ?? [] as $star => $row)
                                <div class="flex items-center gap-3">
                                    <span class="w-8 text-xs text-gray-500 flex-shrink-0">{{ $star }} ★</span>
                                    <div class="flex-1 bg-gray-100 rounded-full h-1.5 min-w-0">
                                        <div class="h-1.5 rounded-full {{ $star >= 4 ? 'bg-emerald-500' : ($star === 3 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ $row['pct'] }}%"></div>
                                    </div>
                                    <span class="min-w-[5.5rem] text-right text-xs text-gray-500 flex-shrink-0 tabular-nums whitespace-nowrap">{{ number_format($row['count']) }} · {{ $row['pct'] }}%</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 py-8 text-center">No reviews yet</p>
                    @endif
                </div>
            </div>
        </section>

        {{-- ═══════════════════════ WEBSITE ═══════════════════════ --}}
        <section class="space-y-4">
            <x-admin.section-heading title="Website" />

            {{-- Range: any dates within the last 90 days --}}
            <div class="flex flex-wrap items-center justify-end gap-2">
                {{-- diffInDays() of start-of-day to end-of-day is a fraction one
                     short of a whole day (29.9999… + 1 for 30 days), so the
                     count is the whole days in it. --}}
                @php $visitDays = (int) floor($websiteStats['days'] ?? 0); @endphp
                <span class="text-xs text-gray-400 mr-auto">
                    {{ $websiteStats['from'] ?? '' }} – {{ $websiteStats['to'] ?? '' }}
                    · {{ $visitDays }} {{ $visitDays === 1 ? 'day' : 'days' }}
                </span>
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wide mr-1">Range</span>
                <input type="date" wire:model.live="visitFrom" min="{{ $this->visitMinDate }}" max="{{ $this->visitMaxDate }}" aria-label="From"
                    class="text-xs bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-indigo-400">
                <span class="text-xs text-gray-400">to</span>
                <input type="date" wire:model.live="visitTo" min="{{ $this->visitMinDate }}" max="{{ $this->visitMaxDate }}" aria-label="To"
                    class="text-xs bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-indigo-400">
                @foreach ([7 => '7 days', 30 => '30 days', 90 => '90 days'] as $days => $lbl)
                    <button wire:click="setVisitRange({{ $days }})"
                        class="px-2.5 py-1.5 text-xs font-medium rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 transition-colors">
                        {{ $lbl }}
                    </button>
                @endforeach
            </div>

            @include('livewire.super-admin.partials.analytics-tiles', ['cols' => 'grid-cols-2 lg:grid-cols-5', 'items' => [
                ['Total Visits', number_format($websiteStats['totalVisits'] ?? 0), null, 'text-gray-900'],
                ['Unique Visitors', number_format($websiteStats['uniqueVisitors'] ?? 0), null, 'text-gray-900'],
                ['Avg / Day', number_format($websiteStats['avgPerDay'] ?? 0), null, 'text-gray-900'],
                ['Pages Tracked', number_format($websiteStats['pagesTracked'] ?? 0), null, 'text-gray-900'],
                ['Most Visited', $websiteStats['topPage'] ?? '—', null, 'text-gray-900'],
            ]])

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
                <div class="sa-card p-5 xl:col-span-2 min-w-0">
                    <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2 mb-4">
                        <h3 class="sa-card-title">Visits Over Time</h3>
                        <div class="flex items-center gap-3 text-[11px] text-gray-400">
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-violet-600 inline-block"></span> Visits</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-gray-900 inline-block"></span> Unique</span>
                        </div>
                    </div>
                    <div class="h-64 relative" wire:ignore wire:key="chart-websiteVisitsChart">
                        <canvas id="websiteVisitsChart"></canvas>
                    </div>
                </div>

                {{-- The range's visitors, on the page's one dark card: new people
                     against visits by people who came back. --}}
                @php
                    $visits  = (int) ($websiteStats['totalVisits'] ?? 0);
                    $uniques = (int) ($websiteStats['uniqueVisitors'] ?? 0);
                @endphp
                <div class="sa-card-dark p-5 min-w-0 flex flex-col">
                    <h3 class="text-[15px] font-semibold text-white">Visitors</h3>
                    <p class="text-xs text-white/50 mt-0.5">People and their visits in this range</p>
                    <div class="flex justify-center my-5">
                        @include('livewire.super-admin.partials.dash-donut', [
                            'segments' => [
                                ['label' => 'Unique visitors', 'value' => $uniques, 'color' => '#a78bfa'],
                                ['label' => 'Return visits', 'value' => max(0, $visits - $uniques), 'color' => '#38bdf8'],
                            ],
                            'center' => number_format($uniques), 'centerLabel' => 'People', 'size' => 140, 'legend' => false, 'dark' => true,
                        ])
                    </div>
                    <dl class="mt-auto space-y-2.5 text-sm">
                        <div class="flex items-center justify-between gap-2">
                            <dt class="flex items-center gap-2 text-white/60"><span class="sa-dot" style="background:#a78bfa"></span>Unique visitors</dt>
                            <dd class="font-semibold tabular-nums">{{ number_format($uniques) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <dt class="flex items-center gap-2 text-white/60"><span class="sa-dot" style="background:#38bdf8"></span>Return visits</dt>
                            <dd class="font-semibold tabular-nums">{{ number_format(max(0, $visits - $uniques)) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-2 pt-2.5 border-t border-white/10">
                            <dt class="text-white/60">Visits a day</dt>
                            <dd class="font-semibold tabular-nums">{{ number_format($websiteStats['avgPerDay'] ?? 0) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="sa-card overflow-hidden">
                <div class="px-5 pt-5 pb-3">
                    <h3 class="sa-card-title">Pages — Visits &amp; Users</h3>
                    <p class="sa-card-sub">Every page visited in this range, most visited first</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[560px]">
                        <thead class="text-gray-400 text-[11px] uppercase tracking-wide">
                            <tr class="border-y border-gray-100 bg-gray-50/60">
                                <th class="px-5 py-2.5 text-left font-medium">Page</th>
                                <th class="px-4 py-2.5 text-right font-medium">Visits</th>
                                <th class="px-4 py-2.5 text-right font-medium">Unique Users</th>
                                <th class="px-5 py-2.5 text-left font-medium w-1/3">Share</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($websitePages as $row)
                                <tr class="hover:bg-gray-50/70">
                                    <td class="px-5 py-2.5 font-medium text-gray-800 break-all">{{ $row['page'] }}</td>
                                    <td class="px-4 py-2.5 text-right text-gray-700 tabular-nums">{{ number_format($row['visits']) }}</td>
                                    <td class="px-4 py-2.5 text-right text-gray-700 tabular-nums">{{ number_format($row['uniques']) }}</td>
                                    <td class="px-5 py-2.5">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-1 bg-gray-100 rounded-full h-1.5 min-w-0">
                                                <div class="h-1.5 rounded-full bg-violet-600" style="width: {{ $row['pct'] }}%"></div>
                                            </div>
                                            <span class="w-12 text-right text-xs text-gray-500 tabular-nums">{{ $row['pct'] }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-10 text-center">
                                        <p class="text-sm text-gray-400">No visits recorded for this range yet.</p>
                                        <p class="text-xs text-gray-400 mt-1">Traffic is tracked from the moment this feature goes live.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        {{-- ═══════════════════════ FEE ═══════════════════════ --}}
        <section class="space-y-4">
            <x-admin.section-heading title="Fee" />

            @include('livewire.super-admin.partials.analytics-tiles', ['items' => [
                ['To Collect', $inr($feeStats['totalFeeToCollect'] ?? 0), 'fee structures' . (!empty($feeStats['year']) ? ' · ' . $feeStats['year'] : ''), 'text-gray-900'],
                ['Collected', $inr($feeStats['totalCollected'] ?? 0), number_format($feeStats['payments'] ?? $feeStats['paidRecords'] ?? 0) . ' payments', 'text-emerald-600'],
                ['Remaining', $inr(max(0, $feeStats['totalRemaining'] ?? 0)), number_format($feeStats['schoolsDue'] ?? $feeStats['unpaidRecords'] ?? 0) . ' schools with dues', 'text-red-500'],
                ['Collection Rate', ($feeStats['collectionRate'] ?? 0) . '%', 'of the fee to collect', 'text-gray-900'],
                ['This Month', $inr($feeStats['collectedThisMonth'] ?? 0), 'collected', 'text-gray-900'],
                ['Last Month', $inr($feeStats['collectedLastMonth'] ?? 0), 'collected', 'text-gray-900'],
                ['Schools with Fee', number_format($feeStats['totalSchools'] ?? 0), 'have a fee structure', 'text-gray-900'],
                ['Avg / Student', $inr($feeStats['avgFeePerStudent'] ?? 0), 'fee to collect per student', 'text-gray-900'],
            ]])

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="lg:col-span-2 sa-card p-5">
                    <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2 mb-4">
                        <div>
                            <h3 class="sa-card-title">Monthly Fee Collection</h3>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $windowLabel }}</p>
                        </div>
                        <span class="flex items-center gap-1 text-[11px] text-gray-400"><span class="w-2.5 h-2.5 rounded-full bg-violet-600 inline-block"></span> Collected</span>
                    </div>
                    <div class="h-56 relative" wire:ignore wire:key="chart-feeMonthlyChart">
                        <canvas id="feeMonthlyChart"></canvas>
                    </div>
                </div>
                <div class="sa-card p-5 min-w-0">
                    <div class="mb-4">
                        <h3 class="sa-card-title">Collection by Payment Mode</h3>
                        <p class="sa-card-sub">All fee collected, by how it was paid</p>
                    </div>
                    @if (array_sum($feeModes['data'] ?? []) > 0)
                        @include('livewire.super-admin.partials.dash-donut', [
                            'segments' => $modeParts($feeModes),
                            'center' => $short(array_sum($feeModes['data'])), 'centerLabel' => 'Collected', 'size' => 148, 'legend' => true,
                            'format' => $short,
                        ])
                    @else
                        <p class="text-sm text-gray-400 py-8 text-center">No paid records yet</p>
                    @endif
                </div>
            </div>

            @if (count($schoolFeeList) > 0)
                <div class="sa-card overflow-hidden">
                    <div class="px-5 pt-5 pb-3">
                        <h3 class="sa-card-title">Schools Fee Breakdown</h3>
                        <p class="sa-card-sub">Top 10 by fee{{ !empty($feeStats['year']) ? ' · ' . $feeStats['year'] : '' }}</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm min-w-[600px]">
                            <thead class="text-gray-400 text-[11px] uppercase tracking-wide">
                                <tr class="border-y border-gray-100 bg-gray-50/60">
                                    <th class="px-5 py-2.5 text-left font-medium">School</th>
                                    <th class="px-4 py-2.5 text-right font-medium">To Collect</th>
                                    <th class="px-4 py-2.5 text-right font-medium">Collected</th>
                                    <th class="px-4 py-2.5 text-right font-medium">Remaining</th>
                                    <th class="px-5 py-2.5 text-right font-medium">Collected %</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($schoolFeeList as $row)
                                    <tr class="hover:bg-gray-50/70">
                                        <td class="px-5 py-2.5 min-w-[180px]">
                                            <p class="font-medium text-gray-800 leading-snug line-clamp-2 break-words" title="{{ $row['name'] }}">{{ $row['name'] }}</p>
                                        </td>
                                        <td class="px-4 py-2.5 text-right text-gray-700 tabular-nums">{{ $inr($row['toCollect']) }}</td>
                                        <td class="px-4 py-2.5 text-right text-gray-700 tabular-nums">{{ $inr($row['collected']) }}</td>
                                        <td class="px-4 py-2.5 text-right tabular-nums {{ $row['remaining'] > 0 ? 'text-red-500' : 'text-gray-700' }}">{{ $inr($row['remaining']) }}</td>
                                        <td class="px-5 py-2.5">
                                            <div class="flex items-center justify-end gap-3">
                                                <div class="w-20 bg-gray-100 rounded-full h-1.5">
                                                    <div class="h-1.5 rounded-full bg-violet-600" style="width: {{ min(100, $row['pct']) }}%"></div>
                                                </div>
                                                <span class="w-12 text-right text-xs font-semibold text-gray-700 tabular-nums">{{ $row['pct'] }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </section>

        {{-- ═══════════════════════ PAYROLL ═══════════════════════ --}}
        <section class="space-y-4">
            <x-admin.section-heading title="Payroll" />

            @include('livewire.super-admin.partials.analytics-tiles', ['items' => [
                ['Employees', number_format($payrollStats['totalEmployees'] ?? 0), null, 'text-gray-900'],
                ['Monthly Bill', $inr($payrollStats['totalMonthlySalaryBill'] ?? 0), 'active employees', 'text-gray-900'],
                ['Paid · ' . ($payrollStats['payableMonth'] ?? '—'), $inr($payrollStats['totalPaidAmount'] ?? 0), number_format($payrollStats['paidThisMonth'] ?? 0) . ' employees paid', 'text-emerald-600'],
                ['Unpaid · ' . ($payrollStats['payableMonth'] ?? '—'), number_format($payrollStats['pendingThisMonth'] ?? 0), 'employees', 'text-red-500'],
                ['Paid This Year', $inr($payrollStats['paidThisYear'] ?? 0), now()->format('Y'), 'text-gray-900'],
                ['Avg Salary', $inr($payrollStats['avgSalary'] ?? 0), null, 'text-gray-900'],
                ['Lowest Salary', $inr($payrollStats['lowestSalary'] ?? 0), 'active employees', 'text-gray-900'],
                ['Highest Salary', $inr($payrollStats['highestSalary'] ?? 0), null, 'text-gray-900'],
            ]])

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="lg:col-span-2 sa-card p-5">
                    <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2 mb-4">
                        <div>
                            <h3 class="sa-card-title">Salary Paid per Month</h3>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $windowLabel }}</p>
                        </div>
                        <span class="flex items-center gap-1 text-[11px] text-gray-400"><span class="w-2.5 h-2.5 rounded-full bg-gray-900 inline-block"></span> Paid out</span>
                    </div>
                    <div class="h-56 relative" wire:ignore wire:key="chart-payrollMonthlyChart">
                        <canvas id="payrollMonthlyChart"></canvas>
                    </div>
                </div>
                <div class="sa-card p-5 min-w-0">
                    <div class="mb-4">
                        <h3 class="sa-card-title">Payouts by Mode</h3>
                        <p class="sa-card-sub">All salary paid, by how it was paid</p>
                    </div>
                    @if (array_sum($payrollModes['data'] ?? []) > 0)
                        @include('livewire.super-admin.partials.dash-donut', [
                            'segments' => $modeParts($payrollModes),
                            'center' => $short(array_sum($payrollModes['data'])), 'centerLabel' => 'Paid out', 'size' => 148, 'legend' => true,
                            'format' => $short,
                        ])
                    @else
                        <p class="text-sm text-gray-400 py-8 text-center">No paid records yet</p>
                    @endif
                </div>
            </div>

            @if (!empty($payrollStats['byType']))
                @php $typeTop = max(1, (int) max(array_column($payrollStats['byType'], 'count') ?: [0])); @endphp
                <div class="sa-card p-5">
                    <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2 mb-4">
                        <div>
                            <h3 class="sa-card-title">Employees by Type</h3>
                            <p class="sa-card-sub">Everyone on the payroll and their monthly salary bill</p>
                        </div>
                        <span class="sa-chip">{{ number_format($payrollStats['totalEmployees'] ?? 0) }} employees</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                        @foreach ($payrollStats['byType'] as $type => $row)
                            <div class="rounded-xl bg-gray-50 px-4 py-3.5 min-w-0">
                                <p class="text-xs text-gray-500 truncate">{{ \Illuminate\Support\Str::headline((string) $type) }}</p>
                                <p class="mt-1 text-xl font-semibold text-gray-900 tabular-nums">{{ number_format($row['count']) }}</p>
                                <div class="mt-2 h-1.5 rounded-full bg-white">
                                    <div class="h-1.5 rounded-full" style="width: {{ round($row['count'] / $typeTop * 100) }}%; background: {{ $row['count'] === $typeTop ? '#111827' : '#c4b5fd' }}"></div>
                                </div>
                                <p class="mt-2 text-xs text-gray-400 tabular-nums break-words">{{ $inr($row['salary']) }} a month</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div class="sa-card overflow-hidden min-w-0">
                    <div class="px-5 pt-5 pb-3 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="sa-card-title">Unpaid for {{ $payrollStats['payableMonth'] ?? '—' }}</h3>
                            <p class="sa-card-sub">Active employees not paid for that month yet</p>
                        </div>
                        <span class="sa-chip" @if (count($payrollPending) > 0) style="background:#fef2f2;color:#b91c1c" @endif>{{ count($payrollPending) }}</span>
                    </div>
                    @if (count($payrollPending) > 0)
                        <div class="max-h-80 overflow-y-auto divide-y divide-gray-100 border-t border-gray-100">
                            @foreach ($payrollPending as $row)
                                <div class="px-5 py-3 flex items-center gap-3">
                                    <span class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xs font-semibold flex-shrink-0">{{ $initial($row['name']) }}</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate" title="{{ $row['name'] }}">{{ $row['name'] }}</p>
                                        <p class="text-xs text-gray-400 truncate">{{ $row['designation'] ?: \Illuminate\Support\Str::headline((string) $row['type']) }}</p>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-700 whitespace-nowrap tabular-nums">{{ $inr($row['salary']) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 py-10 text-center border-t border-gray-100">Everyone has been paid</p>
                    @endif
                </div>

                <div class="sa-card overflow-hidden min-w-0">
                    <div class="px-5 pt-5 pb-3">
                        <h3 class="sa-card-title">Recent Payouts</h3>
                        <p class="sa-card-sub">The latest salaries paid, whatever month they were for</p>
                    </div>
                    @if (count($payrollRecent) > 0)
                        <div class="max-h-80 overflow-y-auto divide-y divide-gray-100 border-t border-gray-100">
                            @foreach ($payrollRecent as $row)
                                <div class="px-5 py-3 flex items-center gap-3">
                                    <span class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs font-semibold flex-shrink-0">{{ $initial($row['name']) }}</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate" title="{{ $row['name'] }}">{{ $row['name'] }}</p>
                                        <p class="text-xs text-gray-400 leading-snug break-words">{{ $row['month'] }} · {{ $row['mode'] }} · {{ $row['date'] }}</p>
                                    </div>
                                    <span class="text-sm font-semibold text-emerald-600 whitespace-nowrap tabular-nums">{{ $inr($row['amount']) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 py-10 text-center border-t border-gray-100">No payouts yet</p>
                    @endif
                </div>
            </div>
        </section>

        {{-- ═══════════════════════ ENQUIRIES ═══════════════════════ --}}
        <section class="space-y-4">
            <x-admin.section-heading title="Enquiries" />

            @include('livewire.super-admin.partials.analytics-tiles', ['items' => [
                ['Demo Requests', number_format($enquiryStats['demo']['total'] ?? 0), number_format($enquiryStats['demo']['thisMonth'] ?? 0) . ' this month · ' . number_format($enquiryStats['demo']['thisWeek'] ?? 0) . ' this week', 'text-gray-900'],
                ['Demo Pending', number_format($enquiryStats['demo']['pending'] ?? 0), 'not replied', 'text-red-500'],
                ['Demo Replied', number_format($enquiryStats['demo']['replied'] ?? 0), ($enquiryStats['demo']['replyRate'] ?? 0) . '% reply rate', 'text-emerald-600'],
                ['Total Enquiries', number_format($enquiryStats['combined']['total'] ?? 0), 'demo + contact', 'text-gray-900'],
                ['Contact Messages', number_format($enquiryStats['contact']['total'] ?? 0), number_format($enquiryStats['contact']['thisMonth'] ?? 0) . ' this month · ' . number_format($enquiryStats['contact']['thisWeek'] ?? 0) . ' this week', 'text-gray-900'],
                ['Contact Pending', number_format($enquiryStats['contact']['pending'] ?? 0), 'not replied', 'text-red-500'],
                ['Contact Replied', number_format($enquiryStats['contact']['replied'] ?? 0), ($enquiryStats['contact']['replyRate'] ?? 0) . '% reply rate', 'text-emerald-600'],
                ['Total Pending', number_format($enquiryStats['combined']['pending'] ?? 0), number_format($enquiryStats['combined']['replied'] ?? 0) . ' replied', 'text-red-500'],
            ]])

            <div class="sa-card p-5">
                <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2 mb-4">
                    <div>
                        <h3 class="sa-card-title">Monthly Enquiries</h3>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $windowLabel }}</p>
                    </div>
                    <div class="flex items-center gap-3 text-[11px] text-gray-400">
                        <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-violet-600 inline-block"></span> Demo</span>
                        <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-gray-900 inline-block"></span> Contact</span>
                    </div>
                </div>
                <div class="h-56 relative" wire:ignore wire:key="chart-enquiryMonthlyChart">
                    <canvas id="enquiryMonthlyChart"></canvas>
                </div>
            </div>
        </section>

        {{-- ═══════════════════════ SUPPORT ═══════════════════════ --}}
        <section class="space-y-4">
            <x-admin.section-heading title="Support" />

            @include('livewire.super-admin.partials.analytics-tiles', ['items' => [
                ['Total Tickets', number_format($supportStats['total'] ?? 0), ($supportStats['avgPerMonth'] ?? 0) . ' a month on average', 'text-gray-900'],
                ['Pending', number_format($supportStats['pending'] ?? 0), 'not replied', 'text-red-500'],
                ['Replied', number_format($supportStats['replied'] ?? 0), $supportReplyRate . '% reply rate', 'text-emerald-600'],
                ['This Month', number_format($supportStats['thisMonth'] ?? 0), number_format($supportStats['thisWeek'] ?? 0) . ' this week', 'text-gray-900'],
            ]])

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="lg:col-span-2 sa-card p-5">
                    <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2 mb-4">
                        <div>
                            <h3 class="sa-card-title">Monthly Support Tickets</h3>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $windowLabel }}</p>
                        </div>
                        <div class="flex items-center gap-3 text-[11px] text-gray-400">
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-violet-300 inline-block"></span> Total</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-violet-600 inline-block"></span> Replied</span>
                        </div>
                    </div>
                    <div class="h-56 relative" wire:ignore wire:key="chart-supportMonthlyChart">
                        <canvas id="supportMonthlyChart"></canvas>
                    </div>
                </div>
                <div class="sa-card p-5 flex flex-col">
                    <h3 class="sa-card-title mb-4">Reply Rate</h3>
                    <div class="flex-1 flex items-center justify-center">
                        @include('livewire.super-admin.partials.dash-donut', [
                            'segments' => [
                                ['label' => 'Replied', 'value' => (int) ($supportStats['replied'] ?? 0), 'color' => '#7c3aed'],
                                ['label' => 'Pending', 'value' => (int) ($supportStats['pending'] ?? 0), 'color' => '#e5e7eb'],
                            ],
                            'center' => $supportReplyRate . '%', 'centerLabel' => 'Replied', 'size' => 150, 'legend' => false,
                        ])
                    </div>
                    <p class="text-xs text-gray-400 mt-4 text-center">{{ number_format($supportStats['replied'] ?? 0) }} of {{ number_format($supportStats['total'] ?? 0) }} tickets replied</p>
                </div>
            </div>
        </section>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         CHART SCRIPTS
         The charts are drawn from #analytics-data and, after every
         re-render (the month window, the website range, the panel's
         auto-refresh), updated in place from it — their canvases are
         wire:ignore'd so a re-render never blanks them.
    ══════════════════════════════════════════════════════════ --}}
    @include('livewire.super-admin.partials.dash-charts-js')
    <script>
        (function () {
            // The dashboard palette (2026-11-03): brand violet leads, ink and
            // soft violet follow.
            const VIOLET = '#7c3aed', INK = '#111827', SOFT = '#c4b5fd';

            const inr   = v => '₹' + Number(v).toLocaleString('en-IN');
            const short = v => v >= 1e7 ? (v / 1e7).toFixed(1) + 'Cr' : v >= 1e5 ? (v / 1e5).toFixed(1) + 'L' : v >= 1e3 ? (v / 1e3).toFixed(0) + 'k' : v;

            // Quiet axes, no legend (each card draws its own), a dark tooltip.
            function options(money = false) {
                return {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: INK, padding: 10, cornerRadius: 8, boxPadding: 4,
                            callbacks: money ? {
                                label: ctx => (ctx.dataset.label ? ctx.dataset.label + ': ' : '') + inr(ctx.parsed.y ?? ctx.parsed)
                            } : {}
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, border: { display: false }, ticks: { color: '#9ca3af', font: { size: 10 }, maxRotation: 0, autoSkipPadding: 8 } },
                        y: {
                            beginAtZero: true,
                            border: { display: false },
                            grid: { color: 'rgba(17,24,39,0.05)' },
                            ticks: {
                                color: '#9ca3af', font: { size: 10 }, precision: 0, maxTicksLimit: 5,
                                callback: money ? (v => '₹' + short(v)) : (v => v)
                            }
                        }
                    }
                };
            }

            // A smooth line with a soft fill fading down to the axis.
            function area(label, data, color, fill = true) {
                return {
                    label: label, data: data || [], borderColor: color, borderWidth: 2.5, tension: 0.42,
                    pointRadius: 0, pointHoverRadius: 5, pointBackgroundColor: '#fff', pointBorderColor: color, pointBorderWidth: 2.5,
                    fill: fill,
                    backgroundColor: c => {
                        const a = c.chart.chartArea;
                        if (!fill || !a) return 'transparent';
                        const g = c.chart.ctx.createLinearGradient(0, a.top, 0, a.bottom);
                        g.addColorStop(0, color + '47');
                        g.addColorStop(1, color + '00');
                        return g;
                    },
                };
            }

            const bar = (label, data, color) => ({
                label: label, data: data || [], backgroundColor: color, borderRadius: 6, borderSkipped: false,
                barPercentage: 0.7, categoryPercentage: 0.6, maxBarThickness: 18
            });

            // The gender and payment-mode donuts are drawn on the server
            // (partials.dash-donut), with their figures in the legend.
            saCharts.define('analytics', {
                // ── Schools ──
                overviewMonthlyChart: p => ({
                    type: 'bar',
                    data: { labels: p.labels, datasets: [bar('Students', p.overview.students, VIOLET), bar('Teachers', p.overview.teachers, INK)] },
                    options: options()
                }),
                schoolsMonthlyChart: p => ({
                    type: 'line',
                    data: { labels: p.labels, datasets: [area('Schools', p.overview.schools, VIOLET)] },
                    options: options()
                }),

                // ── Website ──
                websiteVisitsChart: p => p.website ? {
                    type: 'line',
                    data: { labels: p.website.labels || [], datasets: [area('Visits', p.website.visits, VIOLET), area('Unique', p.website.uniques, INK, false)] },
                    options: options()
                } : null,

                // ── Fee ──
                feeMonthlyChart: p => ({
                    type: 'line',
                    data: { labels: p.labels, datasets: [area('Collected', p.fee.collected, VIOLET)] },
                    options: options(true)
                }),

                // ── Payroll ──
                payrollMonthlyChart: p => ({
                    type: 'bar',
                    data: { labels: p.labels, datasets: [bar('Salary paid', p.payroll.paid, INK)] },
                    options: options(true)
                }),

                // ── Enquiries ──
                enquiryMonthlyChart: p => ({
                    type: 'line',
                    data: { labels: p.labels, datasets: [area('Demo', p.enquiry.demo, VIOLET), area('Contact', p.enquiry.contact, INK, false)] },
                    options: options()
                }),

                // ── Support ──
                supportMonthlyChart: p => ({
                    type: 'bar',
                    data: { labels: p.labels, datasets: [bar('Total', p.support.total, SOFT), bar('Replied', p.support.replied, VIOLET)] },
                    options: options()
                }),
            });
        })();
    </script>

</div>
