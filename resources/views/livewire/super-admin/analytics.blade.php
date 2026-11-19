{{-- Analytics in Home's look (user's ask of 2026-11-18): the Home skin and
     header, and each functionality's figures on a section of its own, picked
     with the chips under the header (the URL's #hash keeps the one open).
     Every section is drawn hidden and x-show opens the picked one, so a
     re-render (the month window, the website range) keeps it open: Livewire's
     morph leaves an element x-show has shown alone, and the hidden ones stay
     hidden. --}}
@php
    $anTabs = array_filter([
        'overview'  => ['Overview', 'squares-2x2'],
        'schools'   => ['Schools', 'building-office-2'],
        'website'   => ['Website', 'globe-alt'],
        'fee'       => ['Fee', 'banknotes'],
        'payroll'   => ['Payroll', 'credit-card'],
        'calendar'  => !empty($calendarStats) ? ['Calendar', 'calendar-days'] : null,
        'enquiries' => ['Enquiries', 'inbox-stack'],
        'support'   => ['Support', 'lifebuoy'],
    ]);
@endphp
<div class="sa-skin sa-home min-h-screen"
    x-data="{
        tabs: {{ \Illuminate\Support\Js::from(array_keys($anTabs)) }},
        tab: 'overview',
        init() { const h = location.hash.slice(1); if (this.tabs.includes(h)) this.tab = h; },
        go(t) { this.tab = t; history.replaceState(null, '', '#' + t); this.$nextTick(() => window.dispatchEvent(new Event('resize'))); },
    }">

    {{-- All chart data lives in this attribute. It is part of the Livewire DOM,
         so every morph (month-window or website-range change) refreshes it and
         the script below re-reads it — charts always render with current data. --}}
    <div id="analytics-data" data-sa-charts="analytics" data-payload="{{ json_encode($chartPayload) }}" class="hidden"></div>

    {{-- The dashboard look (2026-11-03), shared with Home. --}}
    @include('livewire.super-admin.partials.dash-styles')
    @include('livewire.super-admin.partials.home-skin')

    @php
        $inr = fn ($v) => '₹' . number_format((float) $v, 0);
        $short = fn ($v) => \App\Support\DashboardChart::inrShort((float) $v);
        $trend = fn (array $s) => \App\Support\DashboardChart::trend((float) (end($s) ?: 0), (float) ($s[count($s) - 2] ?? 0));
        $supportReplyRate = ($supportStats['total'] ?? 0) > 0
            ? round((($supportStats['replied'] ?? 0) / $supportStats['total']) * 100, 1)
            : 0;
        // The tabs are one page now: what each tab showed is a section below.
        $windowLabel = 'Last ' . $months . ' months';
        $tilesAccent = 'teal';   // the stat tiles' marks in Home's teal (partials.analytics-tiles)
        $ring = ['#0d9488', '#111827', '#5eead4', '#38bdf8', '#f59e0b', '#10b981', '#cbd5e1'];
        // A payment-mode split as a donut's parts.
        $modeParts = fn (array $m) => array_map(
            fn ($label, $value, $i) => ['label' => $label, 'value' => (float) $value, 'color' => $ring[$i % count($ring)]],
            $m['labels'] ?? [], $m['data'] ?? [], array_keys($m['data'] ?? [])
        );
        $initial = fn ($name) => strtoupper(mb_substr(trim((string) $name) ?: '?', 0, 1));

        // The extra detail (2026-11-19): a change against last month as a pill,
        // and a share as a whole %.
        $delta = function ($now, $before): array {
            $now = (float) $now; $before = (float) $before;
            if ($before <= 0) {
                return $now > 0 ? ['New', 'up'] : ['—', 'flat'];
            }
            $pct = (int) round(($now - $before) / $before * 100);

            return [($pct > 0 ? '+' : '') . $pct . '%', $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'flat')];
        };
        $pctOf = fn ($part, $whole) => (float) $whole > 0 ? round((float) $part / (float) $whole * 100, 1) : 0;
        $can   = fn (string $route) => \Illuminate\Support\Facades\Route::has($route) && (auth()->user()?->canAccessSuperAdminRoute($route) ?? false);
    @endphp

    {{-- ══════════════════════════════ HEADER ══════════════════════════════
         Home's header row: the title and date, and the window the monthly
         charts cover; under it a chip for each functionality's figures. --}}
    <div class="sk-head">
        <div class="sk-title">
            <h1>Analytics</h1>
            <p>{{ now()->format('l, d M Y') }}</p>
        </div>
        <div class="sk-right">
            <span wire:ignore class="sk-pill live"
                x-data="{ t: '', tick() { this.t = new Date().toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true }); } }"
                x-init="tick(); setInterval(() => tick(), 15000)">Live · <span class="tabular-nums" x-text="t"></span></span>
            <select wire:model.live="months" title="Months on the monthly charts" class="sk-select" aria-label="Months on the monthly charts">
                <option value="6">Last 6 months</option>
                <option value="12">Last 12 months</option>
                <option value="24">Last 24 months</option>
            </select>
        </div>
    </div>

    <nav class="an-chips" aria-label="Analytics sections">
        @foreach ($anTabs as $key => [$label, $icon])
            <button type="button" class="an-chip" :class="tab === '{{ $key }}' && 'on'" x-on:click="go('{{ $key }}')" :aria-pressed="tab === '{{ $key }}'">
                <x-icon name="{{ $icon }}" /> {{ $label }}
            </button>
        @endforeach
    </nav>

    <div class="p-4 sm:p-6 pt-4 sm:pt-5">

        {{-- ═══════════════════════ KEY METRICS ═══════════════════════ --}}
        <section class="space-y-4" data-an="overview" x-show="tab === 'overview'" style="display: none">

            @php
                $growth = $overviewStats['studentGrowthPct'] ?? 0;
                $regs   = $monthlyRegistrations;
            @endphp
            {{-- The four figures the page leads with: change against last month
                 and a sparkline over the months picked in the header. --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
                @include('livewire.super-admin.partials.dash-kpi', [
                    'label' => 'Total Schools', 'value' => number_format($overviewStats['totalSchools'] ?? 0), 'icon' => 'building-office-2', 'color' => 'teal',
                    'series' => $regs['schools'] ?? [], 'trend' => $trend($regs['schools'] ?? []),
                    'sub' => number_format($overviewStats['activeSchools'] ?? 0) . ' active · ' . number_format($overviewStats['inactiveSchools'] ?? 0) . ' inactive',
                ])
                @include('livewire.super-admin.partials.dash-kpi', [
                    'label' => 'Total Students', 'value' => number_format($overviewStats['totalStudents'] ?? 0), 'icon' => 'users', 'color' => 'teal',
                    'series' => $regs['students'] ?? [], 'trend' => $trend($regs['students'] ?? []),
                    'sub' => number_format($overviewStats['activeStudents'] ?? 0) . ' active',
                ])
                @include('livewire.super-admin.partials.dash-kpi', [
                    'label' => 'Total Teachers', 'value' => number_format($overviewStats['totalTeachers'] ?? 0), 'icon' => 'academic-cap', 'color' => 'teal',
                    'series' => $regs['teachers'] ?? [], 'trend' => $trend($regs['teachers'] ?? []),
                    'sub' => number_format($overviewStats['activeTeachers'] ?? 0) . ' active',
                ])
                @include('livewire.super-admin.partials.dash-kpi', [
                    'label' => 'Fee Collected', 'value' => $short($feeStats['totalCollected'] ?? 0), 'icon' => 'banknotes', 'color' => 'teal',
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

            {{-- This month against last, how healthy the platform is, and what
                 is waiting on the Super Admin (2026-11-19). --}}
            @php
                $cmpRows = [
                    ['New schools', $overviewStats['newSchoolsThisMonth'] ?? 0, $schoolExtra['newLastMonth'] ?? 0, false],
                    ['New students', $overviewStats['studentsThisMonth'] ?? 0, $overviewStats['studentsLastMonth'] ?? 0, false],
                    ['New teachers', $schoolExtra['teachersThisMonth'] ?? 0, $schoolExtra['teachersLastMonth'] ?? 0, false],
                    ['Fee collected', $feeStats['collectedThisMonth'] ?? 0, $feeStats['collectedLastMonth'] ?? 0, true],
                    ['Demo requests', $enquiryStats['demo']['thisMonth'] ?? 0, $enquiryExtra['demoLastMonth'] ?? 0, false],
                    ['Contact messages', $enquiryStats['contact']['thisMonth'] ?? 0, $enquiryExtra['contactLastMonth'] ?? 0, false],
                    ['Support tickets', $supportStats['thisMonth'] ?? 0, $supportExtra['lastMonth'] ?? 0, false],
                ];
                $health = [
                    ['Schools active', $pctOf($overviewStats['activeSchools'] ?? 0, $overviewStats['totalSchools'] ?? 0), number_format($overviewStats['activeSchools'] ?? 0) . ' of ' . number_format($overviewStats['totalSchools'] ?? 0)],
                    ['Students active', $pctOf($overviewStats['activeStudents'] ?? 0, $overviewStats['totalStudents'] ?? 0), number_format($overviewStats['activeStudents'] ?? 0) . ' of ' . number_format($overviewStats['totalStudents'] ?? 0)],
                    ['Teachers active', $pctOf($overviewStats['activeTeachers'] ?? 0, $overviewStats['totalTeachers'] ?? 0), number_format($overviewStats['activeTeachers'] ?? 0) . ' of ' . number_format($overviewStats['totalTeachers'] ?? 0)],
                    ['Fee collected', (float) ($feeStats['collectionRate'] ?? 0), $short($feeStats['totalCollected'] ?? 0) . ' of ' . $short($feeStats['totalFeeToCollect'] ?? 0)],
                    ['Enquiries answered', $pctOf($enquiryStats['combined']['replied'] ?? 0, $enquiryStats['combined']['total'] ?? 0), number_format($enquiryStats['combined']['replied'] ?? 0) . ' of ' . number_format($enquiryStats['combined']['total'] ?? 0)],
                    ['Tickets answered', (float) $supportReplyRate, number_format($supportStats['replied'] ?? 0) . ' of ' . number_format($supportStats['total'] ?? 0)],
                ];
                $waiting = array_values(array_filter([
                    ['Demo requests to answer', (int) ($enquiryStats['demo']['pending'] ?? 0), 'super-admin.enquiries', 'presentation-chart-line'],
                    ['Contact messages to answer', (int) ($enquiryStats['contact']['pending'] ?? 0), 'super-admin.enquiries', 'envelope'],
                    ['Support tickets to answer', (int) ($supportStats['pending'] ?? 0), 'super-admin.support', 'lifebuoy'],
                    ['Schools with fee dues', (int) ($feeStats['schoolsDue'] ?? $feeStats['unpaidRecords'] ?? 0), 'super-admin.fees', 'banknotes'],
                    ['Unpaid for ' . ($payrollStats['payableMonth'] ?? 'last month'), (int) ($payrollStats['pendingThisMonth'] ?? 0), 'super-admin.payroll', 'credit-card'],
                    !empty($calendarStats) ? ['Tasks overdue', (int) ($calendarStats['overdue'] ?? 0), 'super-admin.calendar', 'calendar-days'] : null,
                ]));
            @endphp
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
                <div class="sa-card p-5 min-w-0">
                    <div class="mb-2">
                        <h3 class="sa-card-title">This Month vs Last Month</h3>
                        <p class="sa-card-sub">{{ now()->format('M Y') }} so far against {{ now()->subMonthNoOverflow()->format('M Y') }}</p>
                    </div>
                    <table class="an-cmp">
                        <thead><tr><th>Figure</th><th>{{ now()->format('M') }}</th><th>{{ now()->subMonthNoOverflow()->format('M') }}</th><th>Change</th></tr></thead>
                        <tbody>
                            @foreach ($cmpRows as [$label, $now, $before, $money])
                                @php [$chg, $dir] = $delta($now, $before); @endphp
                                <tr>
                                    <td>{{ $label }}</td>
                                    <td class="font-semibold">{{ $money ? $short($now) : number_format($now) }}</td>
                                    <td class="text-gray-500">{{ $money ? $short($before) : number_format($before) }}</td>
                                    <td><span class="an-delta {{ $dir }}">{{ $chg }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="sa-card p-5 min-w-0">
                    <div class="mb-4">
                        <h3 class="sa-card-title">Platform Health</h3>
                        <p class="sa-card-sub">The share that is active, collected or answered</p>
                    </div>
                    <div class="space-y-3.5">
                        @foreach ($health as [$label, $pct, $line])
                            <div class="min-w-0">
                                <div class="flex items-baseline justify-between gap-3 text-sm">
                                    <p class="font-medium text-gray-800 truncate">{{ $label }}</p>
                                    <p class="whitespace-nowrap tabular-nums"><span class="font-semibold text-gray-900">{{ $pct }}%</span> <span class="text-xs text-gray-400">· {{ $line }}</span></p>
                                </div>
                                <div class="an-meter mt-1.5"><i style="width: {{ min(100, $pct) }}%; background: {{ $pct >= 75 ? '#0d9488' : ($pct >= 40 ? '#f59e0b' : '#f43f5e') }}"></i></div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="sa-card p-5 min-w-0">
                    <div class="mb-2">
                        <h3 class="sa-card-title">Needs Attention</h3>
                        <p class="sa-card-sub">What is waiting on you right now</p>
                    </div>
                    <div>
                        @foreach ($waiting as [$label, $count, $route, $icon])
                            @php $tag = $can($route) ? 'a' : 'div'; @endphp
                            <{{ $tag }} @if ($tag === 'a') href="{{ route($route) }}" @endif class="an-row group">
                                <span class="an-av" @if ($count > 0) style="background:#fff1f2;color:#be123c" @endif><x-icon name="{{ $icon }}" class="w-4 h-4" /></span>
                                <p class="flex-1 min-w-0 text-sm font-medium text-gray-800 truncate {{ $tag === 'a' ? 'group-hover:text-teal-700' : '' }}">{{ $label }}</p>
                                @if ($count > 0)
                                    <span class="an-tag rose tabular-nums">{{ number_format($count) }}</span>
                                @else
                                    <span class="an-tag green">All clear</span>
                                @endif
                            </{{ $tag }}>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- ═══════════════════════ SCHOOLS ═══════════════════════ --}}
        <section class="space-y-4" data-an="schools" x-show="tab === 'schools'" style="display: none">

            {{-- The schools in figures (2026-11-19). --}}
            @include('livewire.super-admin.partials.analytics-tiles', ['items' => [
                ['Active Schools', number_format($overviewStats['activeSchools'] ?? 0), $pctOf($overviewStats['activeSchools'] ?? 0, $overviewStats['totalSchools'] ?? 0) . '% of all schools', 'text-emerald-600'],
                ['Inactive Schools', number_format($overviewStats['inactiveSchools'] ?? 0), 'switched off', ($overviewStats['inactiveSchools'] ?? 0) > 0 ? 'text-red-500' : 'text-gray-900'],
                ['Joined This Month', number_format($overviewStats['newSchoolsThisMonth'] ?? 0), number_format($schoolExtra['newLastMonth'] ?? 0) . ' last month', 'text-violet-600'],
                ['Largest School', number_format($schoolExtra['largest'] ?? 0), 'students', 'text-gray-900'],
                ['Avg Students / School', number_format($overviewStats['avgStudentsPerSchool'] ?? 0, 1), 'students in a school', 'text-gray-900'],
                ['Avg Teachers / School', number_format($schoolExtra['avgTeachers'] ?? 0, 1), 'teachers in a school', 'text-gray-900'],
                ['Students per Teacher', number_format($schoolExtra['studentsPerTeacher'] ?? 0, 1), 'across the platform', 'text-gray-900'],
                ['Schools without Students', number_format($schoolExtra['emptySchools'] ?? 0), 'nobody added yet', ($schoolExtra['emptySchools'] ?? 0) > 0 ? 'text-red-500' : 'text-gray-900'],
            ]])

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
                                <div class="h-1.5 rounded-full" style="width: {{ round($count / $bucketTop * 100) }}%; background: {{ $count === $bucketTop ? '#0d9488' : '#99e0d3' }}"></div>
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
                                                    <div class="h-1.5 rounded-full" style="width: {{ round($s[$main] / $topMax * 100) }}%; background: {{ $loop->first ? '#0d9488' : '#99e0d3' }}"></div>
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

            {{-- Where the schools are and what they teach in (2026-11-19). --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                @foreach ([
                    ['Schools by State', 'Where the schools are', $schoolGroups['state'] ?? []],
                    ['Schools by Board', 'The board each school is with', $schoolGroups['board'] ?? []],
                    ['Schools by Medium', 'The language each school teaches in', $schoolGroups['medium'] ?? []],
                ] as [$heading, $subline, $rows])
                    <div class="sa-card p-5 min-w-0">
                        <div class="mb-4">
                            <h3 class="sa-card-title">{{ $heading }}</h3>
                            <p class="sa-card-sub">{{ $subline }}</p>
                        </div>
                        @include('livewire.super-admin.partials.an-bars', ['rows' => $rows, 'empty' => 'No schools yet'])
                    </div>
                @endforeach
            </div>

            {{-- The schools that joined last, and active against inactive. --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="sa-card p-5 lg:col-span-2 min-w-0">
                    <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2 mb-2">
                        <div>
                            <h3 class="sa-card-title">Newest Schools</h3>
                            <p class="sa-card-sub">The last schools to join, with their size</p>
                        </div>
                        @if ($can('super-admin.schools'))
                            <a href="{{ route('super-admin.schools') }}" class="text-xs font-medium text-violet-600 hover:text-violet-800 whitespace-nowrap">Schools →</a>
                        @endif
                    </div>
                    @if (count($newestSchools))
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8">
                            @foreach ($newestSchools as $s)
                                <div class="an-row" style="border-top: 1px solid #f0f3f2">
                                    <span class="an-av">@if ($s['logo'])<img src="{{ $s['logo'] }}" alt="">@else{{ $initial($s['name']) }}@endif</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate" title="{{ $s['name'] }}">{{ $s['name'] }}</p>
                                        <p class="text-xs text-gray-400 truncate">Joined {{ $s['joined'] }} · {{ number_format($s['students']) }} students · {{ number_format($s['teachers']) }} teachers</p>
                                    </div>
                                    <span class="an-tag {{ $s['active'] ? 'green' : 'rose' }}">{{ $s['active'] ? 'Active' : 'Inactive' }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center py-10 text-gray-400 text-sm">No schools yet</p>
                    @endif
                </div>
                <div class="sa-card p-5 min-w-0">
                    <div class="mb-4">
                        <h3 class="sa-card-title">Schools at a Glance</h3>
                        <p class="sa-card-sub">Switched on and switched off</p>
                    </div>
                    @include('livewire.super-admin.partials.dash-donut', [
                        'segments' => [
                            ['label' => 'Active', 'value' => (int) ($overviewStats['activeSchools'] ?? 0), 'color' => '#0d9488'],
                            ['label' => 'Inactive', 'value' => (int) ($overviewStats['inactiveSchools'] ?? 0), 'color' => '#f43f5e'],
                        ],
                        'center' => number_format($overviewStats['totalSchools'] ?? 0), 'centerLabel' => 'Schools', 'size' => 140, 'legend' => true,
                        'format' => fn ($v) => number_format($v),
                    ])
                </div>
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
                            ['label' => 'Male', 'value' => (int) ($genderSplit['male'] ?? 0), 'color' => '#0d9488'],
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
        <section class="space-y-4" data-an="website" x-show="tab === 'website'" style="display: none">

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

            {{-- More on the range (2026-11-19): today, people coming back, the
                 busiest day and the range before this one. --}}
            @php
                $wChange = $websiteExtra['changePct'] ?? null;
                $wBusy   = $websiteExtra['busiest'] ?? null;
            @endphp
            @include('livewire.super-admin.partials.analytics-tiles', ['cols' => 'grid-cols-2 lg:grid-cols-5', 'items' => [
                ['Visits Today', number_format($websiteExtra['today'] ?? 0), 'since midnight', 'text-violet-600'],
                ['Return Visits', number_format($websiteExtra['returnVisits'] ?? 0), 'by people who came back', 'text-gray-900'],
                ['Visits per Visitor', number_format($websiteExtra['perVisitor'] ?? 0, 1), 'on average', 'text-gray-900'],
                ['Busiest Day', $wBusy ? number_format($wBusy['visits']) : '—', $wBusy ? $wBusy['label'] : 'no visits yet', 'text-gray-900'],
                ['vs Range Before', $wChange === null ? '—' : (($wChange > 0 ? '+' : '') . $wChange . '%'), number_format($websiteExtra['prevVisits'] ?? 0) . ' visits then', $wChange === null ? 'text-gray-900' : ($wChange >= 0 ? 'text-emerald-600' : 'text-red-500')],
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

                {{-- The range's visitors: new people against visits by people
                     who came back. A white card like the rest of the tab (user's
                     ask, 2026-11-19 — it was the page's one dark card). --}}
                @php
                    $visits  = (int) ($websiteStats['totalVisits'] ?? 0);
                    $uniques = (int) ($websiteStats['uniqueVisitors'] ?? 0);
                @endphp
                <div class="sa-card p-5 min-w-0 flex flex-col" data-visitors-card>
                    <h3 class="sa-card-title">Visitors</h3>
                    <p class="sa-card-sub">People and their visits in this range</p>
                    <div class="flex justify-center my-5">
                        @include('livewire.super-admin.partials.dash-donut', [
                            'segments' => [
                                ['label' => 'Unique visitors', 'value' => $uniques, 'color' => '#0d9488'],
                                ['label' => 'Return visits', 'value' => max(0, $visits - $uniques), 'color' => '#38bdf8'],
                            ],
                            'center' => number_format($uniques), 'centerLabel' => 'People', 'size' => 140, 'legend' => false,
                        ])
                    </div>
                    <dl class="mt-auto space-y-2.5 text-sm">
                        <div class="flex items-center justify-between gap-2">
                            <dt class="flex items-center gap-2 text-gray-500"><span class="sa-dot" style="background:#0d9488"></span>Unique visitors</dt>
                            <dd class="font-semibold tabular-nums text-gray-900">{{ number_format($uniques) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <dt class="flex items-center gap-2 text-gray-500"><span class="sa-dot" style="background:#38bdf8"></span>Return visits</dt>
                            <dd class="font-semibold tabular-nums text-gray-900">{{ number_format(max(0, $visits - $uniques)) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-2 pt-2.5 border-t border-gray-100">
                            <dt class="text-gray-500">Visits a day</dt>
                            <dd class="font-semibold tabular-nums text-gray-900">{{ number_format($websiteStats['avgPerDay'] ?? 0) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- The visits by day of the week, over the range. --}}
            @php
                $wDays = $websiteExtra['weekdays'] ?? [];
                $wTop  = max(1, (int) max($wDays ?: [0]));
                $wSum  = (int) array_sum($wDays);
            @endphp
            <div class="sa-card p-5 min-w-0">
                <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2 mb-4">
                    <div>
                        <h3 class="sa-card-title">Visits by Weekday</h3>
                        <p class="sa-card-sub">Which days people visit most, in this range</p>
                    </div>
                    <span class="sa-chip">{{ number_format($wSum) }} visits</span>
                </div>
                @if ($wSum > 0)
                    <div class="grid grid-cols-7 gap-2 sm:gap-4 items-end h-44">
                        @foreach ($wDays as $day => $count)
                            <div class="flex flex-col items-center justify-end h-full min-w-0">
                                <span class="text-xs font-semibold text-gray-700 tabular-nums mb-1">{{ number_format($count) }}</span>
                                <div class="w-full max-w-[44px] rounded-lg" style="height: {{ max(4, round($count / $wTop * 100)) }}%; background: {{ $count === $wTop ? '#0d9488' : '#99e0d3' }}"></div>
                                <span class="mt-2 text-xs text-gray-500">{{ $day }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-400 py-8 text-center">No visits in this range yet</p>
                @endif
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
        <section class="space-y-4" data-an="fee" x-show="tab === 'fee'" style="display: none">

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

            {{-- Schools by what they have paid, the biggest dues and the
                 payments themselves (2026-11-19). --}}
            @php
                $fStatus = $feeExtra['status'] ?? ['full' => 0, 'part' => 0, 'none' => 0];
                $fDues   = $feeExtra['topDues'] ?? [];
                $fDueTop = max(1, (float) max(array_column($fDues, 'remaining') ?: [0]));
            @endphp
            @include('livewire.super-admin.partials.analytics-tiles', ['items' => [
                ['Payments Received', number_format($feeExtra['receipts'] ?? 0), 'receipts in all', 'text-gray-900'],
                ['Payments This Month', number_format($feeExtra['paymentsThisMonth'] ?? 0), $inr($feeStats['collectedThisMonth'] ?? 0) . ' received', 'text-violet-600'],
                ['Average Payment', $inr($feeExtra['avgPayment'] ?? 0), 'a receipt', 'text-gray-900'],
                ['Largest Payment', $inr($feeExtra['largestPayment'] ?? 0), 'one receipt', 'text-gray-900'],
            ]])

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="sa-card p-5 min-w-0">
                    <div class="mb-4">
                        <h3 class="sa-card-title">Schools by Payment</h3>
                        <p class="sa-card-sub">Paid in full, part paid, nothing paid{{ !empty($feeStats['year']) ? ' · ' . $feeStats['year'] : '' }}</p>
                    </div>
                    @if (array_sum($fStatus) > 0)
                        @include('livewire.super-admin.partials.dash-donut', [
                            'segments' => [
                                ['label' => 'Paid in full', 'value' => (int) $fStatus['full'], 'color' => '#0d9488'],
                                ['label' => 'Part paid', 'value' => (int) $fStatus['part'], 'color' => '#f59e0b'],
                                ['label' => 'Nothing paid', 'value' => (int) $fStatus['none'], 'color' => '#f43f5e'],
                            ],
                            'center' => number_format(array_sum($fStatus)), 'centerLabel' => 'Schools', 'size' => 140, 'legend' => true,
                            'format' => fn ($v) => number_format($v),
                        ])
                    @else
                        <p class="text-sm text-gray-400 py-8 text-center">No fee structures yet</p>
                    @endif
                </div>

                <div class="sa-card p-5 min-w-0">
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div class="min-w-0">
                            <h3 class="sa-card-title">Highest Dues</h3>
                            <p class="sa-card-sub">The schools with the most still to pay</p>
                        </div>
                        @if ($can('super-admin.fees'))
                            <a href="{{ route('super-admin.fees') }}" class="text-xs font-medium text-violet-600 hover:text-violet-800 whitespace-nowrap">Fees →</a>
                        @endif
                    </div>
                    @if (count($fDues))
                        <div class="space-y-3.5">
                            @foreach ($fDues as $d)
                                <div class="min-w-0">
                                    <div class="flex items-baseline justify-between gap-3 text-sm">
                                        <p class="font-medium text-gray-800 truncate" title="{{ $d['name'] }}">{{ $d['name'] }}</p>
                                        <p class="font-semibold text-red-500 tabular-nums whitespace-nowrap">{{ $inr($d['remaining']) }}</p>
                                    </div>
                                    <div class="mt-1.5 flex items-center gap-3">
                                        <div class="flex-1 h-1.5 rounded-full bg-gray-100"><div class="h-1.5 rounded-full" style="width: {{ round($d['remaining'] / $fDueTop * 100) }}%; background: {{ $loop->first ? '#f43f5e' : '#fda4af' }}"></div></div>
                                        <span class="text-[11px] text-gray-400 tabular-nums whitespace-nowrap">{{ $d['pct'] }}% paid</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 py-8 text-center">No dues — every school has paid</p>
                    @endif
                </div>

                <div class="sa-card overflow-hidden min-w-0">
                    <div class="px-5 pt-5 pb-3">
                        <h3 class="sa-card-title">Latest Payments</h3>
                        <p class="sa-card-sub">The most recent fee received</p>
                    </div>
                    @if (count($feeRecent))
                        <div class="max-h-80 overflow-y-auto divide-y divide-gray-100 border-t border-gray-100">
                            @foreach ($feeRecent as $r)
                                <div class="px-5 py-3 flex items-center gap-3">
                                    <span class="an-av">{{ $initial($r['name']) }}</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate" title="{{ $r['name'] }}">{{ $r['name'] }}</p>
                                        <p class="text-xs text-gray-400 truncate">{{ $r['date'] }} · {{ $r['mode'] }}@if ($r['number']) · #{{ $r['number'] }}@endif</p>
                                    </div>
                                    <span class="text-sm font-semibold text-emerald-600 whitespace-nowrap tabular-nums">{{ $inr($r['amount']) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 py-10 text-center border-t border-gray-100">No payments yet</p>
                    @endif
                </div>
            </div>

            <div class="sa-card p-5">
                <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2 mb-4">
                    <div>
                        <h3 class="sa-card-title">Payments per Month</h3>
                        <p class="text-xs text-gray-400 mt-0.5">How many receipts were made · {{ $windowLabel }}</p>
                    </div>
                    <span class="flex items-center gap-1 text-[11px] text-gray-400"><span class="w-2.5 h-2.5 rounded-full bg-gray-900 inline-block"></span> Payments</span>
                </div>
                <div class="h-48 relative" wire:ignore wire:key="chart-feeCountChart">
                    <canvas id="feeCountChart"></canvas>
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
        <section class="space-y-4" data-an="payroll" x-show="tab === 'payroll'" style="display: none">

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

            {{-- The payable month's bill against what has been paid, and the
                 people on the payroll (2026-11-19). --}}
            @php $pPct = (float) ($payrollExtra['paidPct'] ?? 0); @endphp
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="sa-card p-5 min-w-0 lg:col-span-2">
                    <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2 mb-4">
                        <div>
                            <h3 class="sa-card-title">{{ $payrollStats['payableMonth'] ?? '—' }} Payroll Progress</h3>
                            <p class="sa-card-sub">The month's salary bill against what has been paid for it</p>
                        </div>
                        <span class="sa-chip">{{ $pPct }}% paid</span>
                    </div>
                    <div class="an-meter" style="height: 12px"><i style="width: {{ min(100, $pPct) }}%; background: #0d9488"></i></div>
                    <div class="mt-5 grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach ([
                            ['Salary bill', $inr($payrollStats['totalMonthlySalaryBill'] ?? 0), 'text-gray-900'],
                            ['Paid', $inr($payrollStats['totalPaidAmount'] ?? 0), 'text-emerald-600'],
                            ['Left to pay', $inr($payrollExtra['leftToPay'] ?? 0), ($payrollExtra['leftToPay'] ?? 0) > 0 ? 'text-red-500' : 'text-gray-900'],
                            ['People paid', number_format($payrollStats['paidThisMonth'] ?? 0) . ' / ' . number_format($payrollStats['totalEmployees'] ?? 0), 'text-gray-900'],
                        ] as [$label, $val, $tone])
                            <div class="rounded-xl bg-gray-50 px-4 py-3 min-w-0">
                                <p class="text-xs text-gray-500 truncate">{{ $label }}</p>
                                <p class="mt-1 text-base font-semibold tabular-nums {{ $tone }} break-words">{{ $val }}</p>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                        <p class="text-gray-500">Payments this year <span class="font-semibold text-gray-900 tabular-nums">{{ number_format($payrollExtra['paymentsThisYear'] ?? 0) }}</span></p>
                        <p class="text-gray-500">Average a month <span class="font-semibold text-gray-900 tabular-nums">{{ $inr($payrollExtra['avgMonth'] ?? 0) }}</span></p>
                        <p class="text-gray-500">Paid this year <span class="font-semibold text-gray-900 tabular-nums">{{ $inr($payrollStats['paidThisYear'] ?? 0) }}</span></p>
                    </div>
                </div>
                <div class="sa-card p-5 min-w-0">
                    <div class="mb-4">
                        <h3 class="sa-card-title">Employees</h3>
                        <p class="sa-card-sub">Active and inactive on the payroll</p>
                    </div>
                    @include('livewire.super-admin.partials.dash-donut', [
                        'segments' => [
                            ['label' => 'Active', 'value' => (int) ($payrollExtra['active'] ?? 0), 'color' => '#0d9488'],
                            ['label' => 'Inactive', 'value' => (int) ($payrollExtra['inactive'] ?? 0), 'color' => '#e5e7eb'],
                        ],
                        'center' => number_format($payrollStats['totalEmployees'] ?? 0), 'centerLabel' => 'People', 'size' => 140, 'legend' => true,
                        'format' => fn ($v) => number_format($v),
                    ])
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
                                    <div class="h-1.5 rounded-full" style="width: {{ round($row['count'] / $typeTop * 100) }}%; background: {{ $row['count'] === $typeTop ? '#111827' : '#99e0d3' }}"></div>
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

        {{-- ═══════════════════════ CALENDAR ═══════════════════════
             The signed-in person's own meetings, tasks and events (nobody
             else's). Off the page for a sub-super-admin without Calendar. --}}
        @if (!empty($calendarStats))
            @php
                $cal = $calendarStats;
                $many = fn (int $n, string $word) => $n . ' ' . \Illuminate\Support\Str::plural($word, $n);
            @endphp
            <section class="space-y-4" data-an="calendar" x-show="tab === 'calendar'" style="display: none">

                @include('livewire.super-admin.partials.analytics-tiles', ['items' => [
                    ['Meetings · ' . $cal['month'], number_format($cal['meetings']), number_format($cal['meetingsLast']) . ' last month', 'text-gray-900'],
                    ['Tasks · ' . $cal['month'], number_format($cal['tasks']), number_format($cal['tasksDone']) . ' done · ' . $cal['tasksPct'] . '%', 'text-gray-900'],
                    ['Events · ' . $cal['month'], number_format($cal['events']), number_format($cal['eventsLast']) . ' last month', 'text-gray-900'],
                    ['Tasks Still Open', number_format($cal['overdue']), 'from earlier days, not done', $cal['overdue'] > 0 ? 'text-red-500' : 'text-gray-900'],
                    ['Today', number_format($cal['today']), $many($cal['todayMeetings'], 'meeting') . ' · ' . $many($cal['todayTasks'], 'task') . ' · ' . $many($cal['todayEvents'], 'event'), 'text-violet-600'],
                    ['Next 7 Days', number_format($cal['next7']), 'coming up', 'text-gray-900'],
                    ['Tasks Done', $cal['windowPct'] . '%', number_format($cal['windowDone']) . ' of ' . number_format($cal['windowTasks']) . ' · ' . strtolower($windowLabel), 'text-emerald-600'],
                    ['In All', number_format($cal['windowTotal']), 'meetings, tasks and events · ' . strtolower($windowLabel), 'text-gray-900'],
                ]])

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <div class="lg:col-span-2 sa-card p-5">
                        <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2 mb-4">
                            <div>
                                <h3 class="sa-card-title">Calendar by Month</h3>
                                <p class="text-xs text-gray-400 mt-0.5">Your meetings, tasks and events · {{ $windowLabel }}</p>
                            </div>
                            <div class="flex items-center gap-3 text-[11px] text-gray-400">
                                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#3b82f6"></span> Meetings</span>
                                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#f59e0b"></span> Tasks</span>
                                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#8b5cf6"></span> Events</span>
                            </div>
                        </div>
                        <div class="h-56 relative" wire:ignore wire:key="chart-calendarMonthlyChart">
                            <canvas id="calendarMonthlyChart"></canvas>
                        </div>
                    </div>
                    <div class="sa-card p-5 min-w-0 flex flex-col">
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div class="min-w-0">
                                <h3 class="sa-card-title">Tasks</h3>
                                <p class="sa-card-sub">Done, still to do and overdue · {{ $windowLabel }}</p>
                            </div>
                            {{-- $calendarStats is only filled when the route is there. --}}
                            <a href="{{ route('super-admin.calendar') }}" class="text-xs font-medium text-violet-600 hover:text-violet-800 whitespace-nowrap">Calendar →</a>
                        </div>
                        @if ($cal['windowTasks'] > 0)
                            @include('livewire.super-admin.partials.dash-donut', [
                                'segments' => [
                                    ['label' => 'Done', 'value' => (int) $cal['windowDone'], 'color' => '#10b981'],
                                    ['label' => 'To do', 'value' => (int) $cal['windowToDo'], 'color' => '#f59e0b'],
                                    ['label' => 'Overdue', 'value' => (int) $cal['windowOverdue'], 'color' => '#f43f5e'],
                                ],
                                'center' => $cal['windowPct'] . '%', 'centerLabel' => 'Done', 'size' => 148, 'legend' => true,
                                'format' => fn ($v) => number_format($v),
                            ])
                        @else
                            <p class="text-sm text-gray-400 py-8 text-center">No tasks in these months</p>
                        @endif
                    </div>
                </div>

                {{-- What is coming up, and this month by kind (2026-11-19). --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <div class="sa-card p-5 lg:col-span-2 min-w-0">
                        <div class="mb-2">
                            <h3 class="sa-card-title">Coming Up</h3>
                            <p class="sa-card-sub">Today and the next seven days · tasks still to do</p>
                        </div>
                        @if (count($calendarUpcoming))
                            <div>
                                @foreach ($calendarUpcoming as $e)
                                    @php $kind = ['meeting' => ['blue', 'users'], 'task' => ['amber', 'check-circle'], 'event' => ['teal', 'sparkles']][$e['type']] ?? ['teal', 'calendar-days']; @endphp
                                    <div class="an-row">
                                        <span class="an-av"><x-icon name="{{ $kind[1] }}" class="w-4 h-4" /></span>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-800 truncate" title="{{ $e['title'] }}">{{ $e['title'] }}</p>
                                            <p class="text-xs text-gray-400 truncate">{{ $e['when'] }}@if ($e['where']) · {{ $e['where'] }}@endif</p>
                                        </div>
                                        <span class="an-tag {{ $kind[0] }}">{{ ucfirst($e['type']) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-400 py-8 text-center">Nothing in the next seven days</p>
                        @endif
                    </div>
                    <div class="sa-card p-5 min-w-0">
                        <div class="mb-4">
                            <h3 class="sa-card-title">{{ $cal['month'] }} by Kind</h3>
                            <p class="sa-card-sub">Meetings, tasks and events this month</p>
                        </div>
                        @if (($cal['meetings'] + $cal['tasks'] + $cal['events']) > 0)
                            @include('livewire.super-admin.partials.dash-donut', [
                                'segments' => [
                                    ['label' => 'Meetings', 'value' => (int) $cal['meetings'], 'color' => '#3b82f6'],
                                    ['label' => 'Tasks', 'value' => (int) $cal['tasks'], 'color' => '#f59e0b'],
                                    ['label' => 'Events', 'value' => (int) $cal['events'], 'color' => '#8b5cf6'],
                                ],
                                'center' => number_format($cal['meetings'] + $cal['tasks'] + $cal['events']), 'centerLabel' => 'This month', 'size' => 140, 'legend' => true,
                                'format' => fn ($v) => number_format($v),
                            ])
                        @else
                            <p class="text-sm text-gray-400 py-8 text-center">Nothing this month yet</p>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        {{-- ═══════════════════════ ENQUIRIES ═══════════════════════ --}}
        <section class="space-y-4" data-an="enquiries" x-show="tab === 'enquiries'" style="display: none">

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

            {{-- Demo requests by status, the reply rates, where they come
                 from, and the latest enquiries (2026-11-19). --}}
            @php $eStatus = $enquiryExtra['status'] ?? []; @endphp
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="sa-card p-5 min-w-0">
                    <div class="mb-4">
                        <h3 class="sa-card-title">Demo Requests by Status</h3>
                        <p class="sa-card-sub">{{ number_format($enquiryExtra['upcomingMeetings'] ?? 0) }} demo {{ ($enquiryExtra['upcomingMeetings'] ?? 0) === 1 ? 'meeting' : 'meetings' }} booked ahead</p>
                    </div>
                    @if (array_sum($eStatus) > 0)
                        @include('livewire.super-admin.partials.dash-donut', [
                            'segments' => [
                                ['label' => 'Pending', 'value' => (int) ($eStatus['pending'] ?? 0), 'color' => '#f59e0b'],
                                ['label' => 'Booked', 'value' => (int) ($eStatus['booked'] ?? 0), 'color' => '#3b82f6'],
                                ['label' => 'Completed', 'value' => (int) ($eStatus['completed'] ?? 0), 'color' => '#0d9488'],
                                ['label' => 'Cancelled', 'value' => (int) ($eStatus['cancelled'] ?? 0), 'color' => '#e5e7eb'],
                            ],
                            'center' => number_format(array_sum($eStatus)), 'centerLabel' => 'Demos', 'size' => 140, 'legend' => true,
                            'format' => fn ($v) => number_format($v),
                        ])
                    @else
                        <p class="text-sm text-gray-400 py-8 text-center">No demo requests yet</p>
                    @endif
                </div>

                <div class="sa-card p-5 min-w-0">
                    <div class="mb-4">
                        <h3 class="sa-card-title">Answered</h3>
                        <p class="sa-card-sub">The share of each kind that has a reply</p>
                    </div>
                    <div class="space-y-4">
                        @foreach ([
                            ['Demo requests', (float) ($enquiryStats['demo']['replyRate'] ?? 0), $enquiryStats['demo']['replied'] ?? 0, $enquiryStats['demo']['total'] ?? 0],
                            ['Contact messages', (float) ($enquiryStats['contact']['replyRate'] ?? 0), $enquiryStats['contact']['replied'] ?? 0, $enquiryStats['contact']['total'] ?? 0],
                            ['All enquiries', $pctOf($enquiryStats['combined']['replied'] ?? 0, $enquiryStats['combined']['total'] ?? 0), $enquiryStats['combined']['replied'] ?? 0, $enquiryStats['combined']['total'] ?? 0],
                        ] as [$label, $pct, $done, $all])
                            <div class="min-w-0">
                                <div class="flex items-baseline justify-between gap-3 text-sm">
                                    <p class="font-medium text-gray-800 truncate">{{ $label }}</p>
                                    <p class="whitespace-nowrap tabular-nums"><span class="font-semibold text-gray-900">{{ $pct }}%</span> <span class="text-xs text-gray-400">· {{ number_format($done) }} of {{ number_format($all) }}</span></p>
                                </div>
                                <div class="an-meter mt-1.5"><i style="width: {{ min(100, $pct) }}%; background: #0d9488"></i></div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="sa-card p-5 min-w-0">
                    <div class="mb-4">
                        <h3 class="sa-card-title">Top Cities</h3>
                        <p class="sa-card-sub">Where the demo requests come from</p>
                    </div>
                    @include('livewire.super-admin.partials.an-bars', ['rows' => $enquiryExtra['cities'] ?? [], 'empty' => 'No cities given yet'])
                </div>
            </div>

            <div class="sa-card p-5 min-w-0">
                <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2 mb-2">
                    <div>
                        <h3 class="sa-card-title">Latest Enquiries</h3>
                        <p class="sa-card-sub">Demo requests and contact messages, newest first</p>
                    </div>
                    @if ($can('super-admin.enquiries'))
                        <a href="{{ route('super-admin.enquiries') }}" class="text-xs font-medium text-violet-600 hover:text-violet-800 whitespace-nowrap">Enquiries →</a>
                    @endif
                </div>
                @if (count($enquiryRecent))
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8">
                        @foreach ($enquiryRecent as $e)
                            <div class="an-row" style="border-top: 1px solid #f0f3f2">
                                <span class="an-av">{{ $initial($e['name']) }}</span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800 truncate" title="{{ $e['name'] }}">{{ $e['name'] }}</p>
                                    <p class="text-xs text-gray-400 truncate">{{ $e['kind'] }} · {{ $e['school'] }} · {{ $e['ago'] }}</p>
                                </div>
                                <span class="an-tag {{ $e['replied'] || in_array($e['status'], ['Booked', 'Completed'], true) ? 'green' : ($e['status'] === 'Cancelled' ? '' : 'amber') }}">{{ $e['status'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-400 py-8 text-center">No enquiries yet</p>
                @endif
            </div>
        </section>

        {{-- ═══════════════════════ SUPPORT ═══════════════════════ --}}
        <section class="space-y-4" data-an="support" x-show="tab === 'support'" style="display: none">

            @php [$sChg, $sDir] = $delta($supportStats['thisMonth'] ?? 0, $supportExtra['lastMonth'] ?? 0); @endphp
            @include('livewire.super-admin.partials.analytics-tiles', ['cols' => 'grid-cols-2 lg:grid-cols-3 xl:grid-cols-6', 'items' => [
                ['Total Tickets', number_format($supportStats['total'] ?? 0), ($supportStats['avgPerMonth'] ?? 0) . ' a month on average', 'text-gray-900'],
                ['Pending', number_format($supportStats['pending'] ?? 0), 'not replied', 'text-red-500'],
                ['Replied', number_format($supportStats['replied'] ?? 0), $supportReplyRate . '% reply rate', 'text-emerald-600'],
                ['This Month', number_format($supportStats['thisMonth'] ?? 0), number_format($supportStats['thisWeek'] ?? 0) . ' this week', 'text-gray-900'],
                ['Last Month', number_format($supportExtra['lastMonth'] ?? 0), 'this month ' . $sChg, 'text-gray-900'],
                ['Oldest Waiting', ($supportStats['pending'] ?? 0) > 0 ? number_format($supportExtra['oldestPendingDays'] ?? 0) . ' d' : '—', ($supportStats['pending'] ?? 0) > 0 ? 'days since asked' : 'nothing waiting', ($supportExtra['oldestPendingDays'] ?? 0) > 3 ? 'text-red-500' : 'text-gray-900'],
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
                                ['label' => 'Replied', 'value' => (int) ($supportStats['replied'] ?? 0), 'color' => '#0d9488'],
                                ['label' => 'Pending', 'value' => (int) ($supportStats['pending'] ?? 0), 'color' => '#e5e7eb'],
                            ],
                            'center' => $supportReplyRate . '%', 'centerLabel' => 'Replied', 'size' => 150, 'legend' => false,
                        ])
                    </div>
                    <p class="text-xs text-gray-400 mt-4 text-center">{{ number_format($supportStats['replied'] ?? 0) }} of {{ number_format($supportStats['total'] ?? 0) }} tickets replied</p>
                </div>
            </div>

            {{-- Who writes, about what, and the latest tickets (2026-11-19). --}}
            @php $sSchools = $supportExtra['schools'] ?? []; @endphp
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="sa-card p-5 min-w-0">
                    <div class="mb-2">
                        <h3 class="sa-card-title">Schools Writing Most</h3>
                        <p class="sa-card-sub">Tickets from each school, and how many wait</p>
                    </div>
                    @if (count($sSchools))
                        <div>
                            @foreach ($sSchools as $row)
                                <div class="an-row">
                                    <span class="an-av">{{ $initial($row['name']) }}</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate" title="{{ $row['name'] }}">{{ $row['name'] }}</p>
                                        <p class="text-xs text-gray-400">{{ number_format($row['count']) }} {{ $row['count'] === 1 ? 'ticket' : 'tickets' }}</p>
                                    </div>
                                    @if ($row['pending'] > 0)
                                        <span class="an-tag rose">{{ number_format($row['pending']) }} waiting</span>
                                    @else
                                        <span class="an-tag green">All replied</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 py-8 text-center">No tickets yet</p>
                    @endif
                </div>

                <div class="sa-card p-5 min-w-0">
                    <div class="mb-4">
                        <h3 class="sa-card-title">Topics</h3>
                        <p class="sa-card-sub">What the tickets are about</p>
                    </div>
                    @include('livewire.super-admin.partials.an-bars', ['rows' => $supportExtra['topics'] ?? [], 'empty' => 'No tickets yet'])
                </div>

                <div class="sa-card p-5 min-w-0">
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <div class="min-w-0">
                            <h3 class="sa-card-title">Latest Tickets</h3>
                            <p class="sa-card-sub">Newest first</p>
                        </div>
                        @if ($can('super-admin.support'))
                            <a href="{{ route('super-admin.support') }}" class="text-xs font-medium text-violet-600 hover:text-violet-800 whitespace-nowrap">Support →</a>
                        @endif
                    </div>
                    @if (count($supportRecent))
                        <div>
                            @foreach ($supportRecent as $t)
                                <div class="an-row" style="align-items: flex-start">
                                    <span class="an-av">{{ $initial($t['school']) }}</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate" title="{{ $t['school'] }}">{{ $t['school'] }}</p>
                                        <p class="text-xs text-gray-400 truncate" title="{{ $t['text'] }}">{{ $t['topic'] }}@if ($t['text']) · {{ $t['text'] }}@endif</p>
                                        <p class="text-[11px] text-gray-400 mt-0.5">{{ $t['ago'] }}</p>
                                    </div>
                                    <span class="an-tag {{ $t['replied'] ? 'green' : 'amber' }}">{{ $t['replied'] ? 'Replied' : 'Pending' }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 py-8 text-center">No tickets yet</p>
                    @endif
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
            const VIOLET = '#0d9488', INK = '#111827', SOFT = '#99e0d3';   // Home's teal (the names are older than the colours)

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

                feeCountChart: p => ({
                    type: 'bar',
                    data: { labels: p.labels, datasets: [bar('Payments', p.fee.count, INK)] },
                    options: options()
                }),

                // ── Payroll ──
                payrollMonthlyChart: p => ({
                    type: 'bar',
                    data: { labels: p.labels, datasets: [bar('Salary paid', p.payroll.paid, INK)] },
                    options: options(true)
                }),

                // ── Calendar (the Calendar's own colour for each kind) ──
                calendarMonthlyChart: p => p.calendar ? {
                    type: 'bar',
                    data: { labels: p.labels, datasets: [
                        bar('Meetings', p.calendar.meetings, '#3b82f6'),
                        bar('Tasks', p.calendar.tasks, '#f59e0b'),
                        bar('Events', p.calendar.events, '#8b5cf6'),
                    ] },
                    options: options()
                } : null,

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
