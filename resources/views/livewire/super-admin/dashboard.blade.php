<div class="sa-skin sa-home min-h-screen" data-no-empty-art>
    @include('livewire.super-admin.partials.dash-styles')
    @include('livewire.super-admin.partials.home-skin')

    @php
        // ─── Home in the dashboard layout (2026-11-03): KPI cards with their
        //     trend and sparkline, the fee chart beside a donut, activity, a
        //     bar chart and a dark card, then the rest in matching cards. ───
        $inr   = fn ($v) => '₹' . number_format((float) $v, 0);
        $short = fn ($v) => \App\Support\DashboardChart::inrShort((float) $v);
        $trend = fn (array $s) => \App\Support\DashboardChart::trend((float) (end($s) ?: 0), (float) ($s[count($s) - 2] ?? 0));

        $supportPct = ($supportStats['total'] ?? 0) > 0
            ? round(($supportStats['replied'] / $supportStats['total']) * 100, 1)
            : 0;
        $staffNotMarked = max(0, $staff['active'] - $staff['today']['marked']);

        $schoolSeries  = $monthlyRegistrations['schools'] ?? [];
        $studentSeries = $monthlyRegistrations['students'] ?? [];
        $teacherSeries = $monthlyRegistrations['teachers'] ?? [];
        $monthShort    = array_map(fn ($l) => explode(' ', (string) $l)[0], $monthlyRegistrations['labels'] ?? []);

        $feeData = $superAdminFee['data'];
        // The fee chart: one bar for every day money came in, under its date.
        $feeDays = $superAdminFee['days'] ?? [];
        $feeLast = $feeDays ? end($feeDays) : null;

        $allUsers    = (int) $totalStudents + (int) $totalTeachers;
        $activeUsers = (int) $activeStudents + (int) $activeTeachers;
        $activePct   = $allUsers > 0 ? round($activeUsers / $allUsers * 100) : 0;

        $activityTone = [
            'emerald' => ['#ecfdf5', '#059669'],
            'blue'    => ['#eff6ff', '#2563eb'],
            'violet'  => ['#effaf7', '#0d9488'],
        ];

        // The two Chart.js charts' figures (see partials.dash-charts-js).
        $homeCharts = [
            'fee' => [
                'labels'  => array_column($feeDays, 'label'),
                'data'    => array_column($feeDays, 'amount'),
                'full'    => array_column($feeDays, 'full'),
                'schools' => array_map(function ($d) use ($inr) {
                    $lines = array_map(fn ($s) => $s['name'] . ' · ' . $inr($s['amount']), array_slice($d['schools'], 0, 5));
                    if (count($d['schools']) > 5) {
                        $lines[] = '+' . (count($d['schools']) - 5) . ' more';
                    }
                    return $lines;
                }, $feeDays),
            ],
            'reg' => ['labels' => $monthShort, 'students' => $studentSeries, 'teachers' => $teacherSeries],
        ];
    @endphp

    {{-- The charts' figures: refreshed by every re-render, read again after it. --}}
    <div class="hidden" data-sa-charts="home" data-payload="{{ json_encode($homeCharts) }}"></div>

    {{-- ══════════════════════════════ HEADER ══════════════════════════════
         The website's analytics screen: the title, a search over the panel's
         pages, a Live clock, Refresh and Add School. --}}
    @php
        $saPages = collect(config('menu.super-admin', []))
            ->filter(fn ($l) => ($l['sidebar'] ?? true) !== false && \Illuminate\Support\Facades\Route::has($l['link']))
            ->filter(fn ($l) => auth()->user()?->canAccessSuperAdminRoute($l['link']) ?? false)
            ->map(fn ($l) => ['t' => $l['title'], 'u' => route($l['link'])])
            ->values();
    @endphp
    <div class="sk-head">
        <div class="sk-title">
            <h1>Overview</h1>
            <p>{{ now()->format('l, d M Y') }}</p>
        </div>

        <div class="sk-search" x-data="{ q: '', open: false, pages: {{ \Illuminate\Support\Js::from($saPages) }},
                get hits() { const s = this.q.trim().toLowerCase(); return s ? this.pages.filter(p => p.t.toLowerCase().includes(s)).slice(0, 8) : []; } }"
            x-on:click.away="open = false" x-on:keydown.escape="q = ''; open = false">
            <x-icon name="magnifying-glass" />
            <input type="text" x-model="q" x-on:focus="open = true" x-on:input="open = true" placeholder="Search pages..." autocomplete="off"
                x-on:keydown.enter.prevent="if (hits.length) window.location = hits[0].u" aria-label="Search pages">
            <button type="button" x-show="q" x-on:click="q = ''" style="display: none" aria-label="Clear"><x-icon name="x-mark" class="w-3.5 h-3.5" /></button>
            <div class="sk-menu" x-show="open && q" x-transition style="display: none; right: 0; width: auto;">
                <template x-for="p in hits" :key="p.u">
                    <a :href="p.u"><x-icon name="arrow-right" class="w-3.5 h-3.5 text-gray-300" /><span x-text="p.t"></span></a>
                </template>
                <p class="sk-menu-h" x-show="!hits.length" style="text-transform: none; letter-spacing: 0; font-size: 13px; padding: 12px 14px;">No pages found</p>
            </div>
        </div>

        <div class="sk-right">
            <span wire:ignore class="sk-pill live"
                x-data="{ t: '', tick() { this.t = new Date().toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true }); } }"
                x-init="tick(); setInterval(() => tick(), 15000)">Live · <span class="tabular-nums" x-text="t"></span></span>
            <button wire:click="refreshData" class="sk-pill" type="button">
                <svg class="w-3.5 h-3.5" wire:loading.class="animate-spin" wire:target="refreshData" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Refresh
            </button>
            <a href="{{ route('super-admin.schools') }}" class="sk-cta">
                <x-icon name="plus" class="w-4 h-4" />
                Add School
            </a>
        </div>
    </div>

    <div class="p-4 sm:p-6 space-y-5">

        {{-- ═══════════════ KPIs — figure, change vs last month, sparkline ═══════════════ --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
            @include('livewire.super-admin.partials.dash-kpi', [
                'label' => 'Total Schools', 'value' => number_format($totalSchools), 'icon' => 'building-office-2', 'color' => 'teal',
                'series' => $schoolSeries, 'trend' => $trend($schoolSeries),
                'sub' => number_format($activeSchools) . ' active · ' . number_format($newSchoolsThisMonth) . ' new this month',
                'href' => route('super-admin.schools'),
            ])
            @include('livewire.super-admin.partials.dash-kpi', [
                'label' => 'Total Students', 'value' => number_format($totalStudents), 'icon' => 'users', 'color' => 'teal',
                'series' => $studentSeries, 'trend' => $trend($studentSeries),
                'sub' => number_format($activeStudents) . ' active',
                'href' => route('super-admin.students'),
            ])
            @include('livewire.super-admin.partials.dash-kpi', [
                'label' => 'Total Teachers', 'value' => number_format($totalTeachers), 'icon' => 'academic-cap', 'color' => 'teal',
                'series' => $teacherSeries, 'trend' => $trend($teacherSeries),
                'sub' => number_format($activeTeachers) . ' active',
                'href' => route('super-admin.teachers'),
            ])
            @include('livewire.super-admin.partials.dash-kpi', [
                'label' => 'Fee Collected', 'value' => $short($superAdminFee['collected']), 'icon' => 'banknotes', 'color' => 'teal',
                'series' => $feeData,
                'trend' => \App\Support\DashboardChart::trend((float) $superAdminFee['this_month'], (float) $superAdminFee['last_month']),
                'sub' => $superAdminFee['pct'] . '% of ' . $short($superAdminFee['total']) . ' · ' . $superAdminFee['year'],
                'href' => route('super-admin.fees'),
            ])
        </div>

        {{-- ═══════════════ Fee collection (area) + students by school (donut) ═══════════════ --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
            <div class="sa-card p-5 xl:col-span-2 min-w-0">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
                    <div>
                        <h3 class="sa-card-title">Fee Collection</h3>
                        <p class="sa-card-sub">Super Admin fee collected on each date · last 12 months</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="sa-chip"><span class="sa-dot" style="background:#0d9488"></span>{{ $short($superAdminFee['year_total']) }} in 12 months</span>
                        @if ($feeLast)
                            <span class="sa-chip" style="background:#f3f4f6;color:#374151">Last on {{ $feeLast['label'] }}</span>
                        @endif
                        <span class="sa-chip" style="background:#fef2f2;color:#b91c1c">{{ $short($superAdminFee['pending']) }} pending</span>
                    </div>
                </div>
                <div class="h-64 relative">
                    <div class="h-full relative" wire:ignore wire:key="chart-homeFeeChart">
                        <canvas id="homeFeeChart"></canvas>
                    </div>
                    @if (!$feeDays)
                        <p class="absolute inset-0 flex items-center justify-center text-sm text-gray-400 pointer-events-none">No fee collected in the last 12 months</p>
                    @endif
                </div>
                <dl class="mt-5 pt-4 border-t border-gray-100 grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                    @foreach ([
                        ['Collected', $inr($superAdminFee['collected']), 'text-gray-900'],
                        ['Pending', $inr($superAdminFee['pending']), $superAdminFee['pending'] > 0 ? 'text-rose-600' : 'text-gray-900'],
                        ['This month', $inr($superAdminFee['this_month']), 'text-gray-900'],
                        ['Schools with dues', number_format($superAdminFee['schools_due']) . ' / ' . number_format($superAdminFee['schools']), $superAdminFee['schools_due'] > 0 ? 'text-rose-600' : 'text-gray-900'],
                    ] as [$label, $val, $tone])
                        <div class="min-w-0">
                            <dt class="text-xs text-gray-400">{{ $label }}</dt>
                            <dd class="mt-0.5 font-semibold tabular-nums truncate {{ $tone }}">{{ $val }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            {{-- Students by school: the total, one bar split by school, then
                 each school's students and share on a row of its own. --}}
            <div class="sa-card p-5 min-w-0 flex flex-col" data-home-students>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="sa-card-title">Students by School</h3>
                        <p class="sa-card-sub">The biggest schools and the rest</p>
                    </div>
                    <a href="{{ route('super-admin.students') }}" class="text-xs font-medium text-violet-600 hover:text-violet-800 whitespace-nowrap">View all →</a>
                </div>
                @if (count($studentsBySchool))
                    @php $studentPct = fn ($v) => $totalStudents > 0 ? round($v / $totalStudents * 100) : 0; @endphp
                    <div class="mt-5 flex items-baseline gap-2">
                        <p class="text-[28px] leading-none font-semibold text-gray-900 tabular-nums">{{ number_format($totalStudents) }}</p>
                        <p class="text-xs text-gray-400">students in {{ number_format($totalSchools) }} {{ (int) $totalSchools === 1 ? 'school' : 'schools' }}</p>
                    </div>
                    <div class="mt-4 flex h-2 gap-0.5 overflow-hidden rounded-full bg-gray-100">
                        @foreach ($studentsBySchool as $part)
                            @if ($part['value'] > 0)
                                <span class="h-full" style="flex: {{ $part['value'] }} 1 0%; background: {{ $part['color'] }}" title="{{ $part['label'] }} · {{ number_format($part['value']) }}"></span>
                            @endif
                        @endforeach
                    </div>
                    <ul class="mt-4 divide-y divide-gray-100">
                        @foreach ($studentsBySchool as $part)
                            <li class="flex items-center gap-3 py-2.5 text-sm min-w-0">
                                <span class="w-2.5 h-2.5 rounded-[3px] flex-shrink-0" style="background: {{ $part['color'] }}"></span>
                                <span class="flex-1 min-w-0 truncate {{ $part['label'] === 'Other schools' ? 'text-gray-400' : 'text-gray-700' }}" title="{{ $part['label'] }}">{{ $part['label'] }}</span>
                                <span class="font-semibold text-gray-900 tabular-nums whitespace-nowrap">{{ number_format($part['value']) }}</span>
                                <span class="w-10 text-right text-xs text-gray-400 tabular-nums">{{ $studentPct($part['value']) }}%</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="flex-1 flex items-center justify-center py-16 text-sm text-gray-400">No students yet</p>
                @endif
            </div>
        </div>

        {{-- ═══════════════ Recent activity · registrations (bars) · active users (dark) ═══════════════
             Three across from xl; below that the chart takes a row of its own
             first and the two cards share the next, so none is squeezed. --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-12 gap-5">
            {{-- A timeline of the newest schools, students and teachers. The
                 list scrolls on its own, with a 4px bar on the card's very
                 right edge (sa-scroll), not the page's wide one. --}}
            <div class="sa-card xl:col-span-4 min-w-0 flex flex-col overflow-hidden" data-home-activity>
                <div class="px-5 pt-5 pb-4 flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="sa-card-title">Recent Activity</h3>
                        <p class="sa-card-sub">New schools, students and teachers</p>
                    </div>
                    @if (count($activityFeed))
                        <span class="text-[11px] font-medium text-gray-500 bg-gray-100 rounded-full px-2 py-0.5 tabular-nums">{{ count($activityFeed) }} latest</span>
                    @endif
                </div>
                <ul class="sa-scroll flex-1 min-h-0 max-h-[19rem] pl-5 pr-3 pb-4">
                    @forelse ($activityFeed as $activity)
                        @php [$tint, $ink] = $activityTone[$activity['color']] ?? ['#f3f4f6', '#6b7280']; @endphp
                        <li class="relative flex gap-3 {{ $loop->last ? '' : 'pb-4' }}">
                            @unless ($loop->last)
                                <span class="absolute left-[15.5px] top-9 bottom-1 w-px bg-gray-100" aria-hidden="true"></span>
                            @endunless
                            <span class="relative w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0" style="background: {{ $tint }}; color: {{ $ink }}">
                                <x-icon name="{{ $activity['icon'] }}" class="w-4 h-4" />
                            </span>
                            <div class="flex-1 min-w-0 pt-0.5">
                                <div class="flex items-baseline justify-between gap-2">
                                    <p class="text-sm font-medium text-gray-800 truncate" title="{{ $activity['name'] ?? $activity['title'] }}">{{ $activity['name'] ?? $activity['title'] }}</p>
                                    <span class="text-[11px] text-gray-400 whitespace-nowrap flex-shrink-0">{{ $activity['time'] }}</span>
                                </div>
                                @php $actLine = ($activity['kind'] ?? $activity['title']) . (!empty($activity['school']) ? ' · ' . $activity['school'] : ''); @endphp
                                <p class="text-xs text-gray-400 mt-0.5 truncate" title="{{ $actLine }}">{{ $actLine }}</p>
                            </div>
                        </li>
                    @empty
                        <li class="py-10 text-center text-sm text-gray-400">No recent activity</li>
                    @endforelse
                </ul>
            </div>

            <div class="sa-card p-5 md:col-span-2 xl:col-span-5 md:order-first xl:order-none min-w-0">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                    <div>
                        <h3 class="sa-card-title">Registrations</h3>
                        <p class="sa-card-sub">Students and teachers added · last 12 months</p>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        <span class="flex items-center gap-1.5"><span class="sa-dot" style="background:#0d9488"></span>Students</span>
                        <span class="flex items-center gap-1.5"><span class="sa-dot" style="background:#111827"></span>Teachers</span>
                    </div>
                </div>
                <div class="h-64 relative" wire:ignore wire:key="chart-homeRegChart">
                    <canvas id="homeRegChart"></canvas>
                </div>
            </div>

            {{-- Active users: plain and grey — the share, one bar, the figures. --}}
            <div class="sa-card p-5 xl:col-span-3 min-w-0 flex flex-col" data-home-active>
                <div class="min-w-0">
                    <h3 class="sa-card-title">Active Users</h3>
                    <p class="sa-card-sub">Students and teachers who can sign in</p>
                </div>
                <div class="mt-5 flex items-baseline gap-2">
                    <p class="text-[32px] leading-none font-semibold text-gray-900 tabular-nums">{{ $activePct }}%</p>
                    <p class="text-xs text-gray-400 tabular-nums">{{ number_format($activeUsers) }} of {{ number_format($allUsers) }} active</p>
                </div>
                <div class="mt-3 h-1.5 rounded-full bg-gray-100">
                    <div class="h-1.5 rounded-full bg-gray-800" style="width: {{ min(100, $activePct) }}%"></div>
                </div>
                <dl class="mt-auto pt-4 divide-y divide-gray-100 text-sm">
                    @foreach ([
                        ['Students', $activeStudents, $totalStudents],
                        ['Teachers', $activeTeachers, $totalTeachers],
                        ['Schools', $activeSchools, $totalSchools],
                    ] as [$label, $on, $all])
                        <div class="flex items-center justify-between gap-2 py-2.5">
                            <dt class="text-gray-500">{{ $label }}</dt>
                            <dd class="tabular-nums whitespace-nowrap"><span class="font-semibold text-gray-900">{{ number_format($on) }}</span><span class="text-gray-400"> / {{ number_format($all) }}</span></dd>
                        </div>
                    @endforeach
                    <div class="flex items-center justify-between gap-2 pt-2.5">
                        <dt class="text-gray-500">Website · 7 days</dt>
                        <dd class="tabular-nums whitespace-nowrap"><span class="font-semibold text-gray-900">{{ number_format($websiteWeek['visits']) }}</span><span class="text-gray-400"> visits</span></dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- ═══════════════ Needs attention ═══════════════ --}}
        @if (count($waiting) > 0)
            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-5">
                @foreach ($waiting as $w)
                    <a href="{{ route($w['route']) }}" class="sa-card px-4 py-4 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs font-medium text-gray-500 truncate">{{ $w['label'] }}</p>
                            @if ($w['count'] > 0)
                                <span class="sa-dot animate-pulse" style="background:#f43f5e"></span>
                            @endif
                        </div>
                        <p class="text-2xl leading-none font-semibold mt-2.5 tabular-nums {{ $w['count'] > 0 ? 'text-rose-600' : 'text-gray-900' }}">{{ number_format($w['count']) }}</p>
                        <p class="text-xs text-gray-400 mt-1.5 truncate">{{ $w['sub'] }}</p>
                    </a>
                @endforeach
            </div>
        @endif

        {{-- ═══════════════ Calendar — the signed-in person's own: today · the next 7 days · tasks still open ═══════════════
             Left off for a sub-super-admin who was not given Calendar ($calendar null). --}}
        @if (!empty($calendar))
            @php
                // The Calendar's own colours for each kind.
                $calTone = ['meeting' => '#3b82f6', 'task' => '#f59e0b', 'event' => '#8b5cf6'];
                $calDay  = fn (string $date) => route('super-admin.calendar', ['month' => substr($date, 0, 7), 'day' => $date]);
                $calToday = $calendar['meetings'] + $calendar['tasks'] + $calendar['events'];
                $calLists = [
                    ['Today · ' . now()->format('d M'), $calToday, $calendar['today'], false, 'Nothing planned for today', $calendar['today_more']],
                    ['Next 7 days', $calendar['upcoming_count'], $calendar['upcoming'], true, 'Nothing in the next 7 days', max(0, $calendar['upcoming_count'] - count($calendar['upcoming']))],
                    ['Tasks still open', $calendar['overdue_count'], $calendar['overdue'], true, 'No task left open from earlier days', max(0, $calendar['overdue_count'] - count($calendar['overdue']))],
                ];
            @endphp
            <div class="sa-card min-w-0 overflow-hidden" data-home-calendar>
                <div class="px-5 pt-5 pb-4 flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="sa-card-title">Calendar</h3>
                        <p class="sa-card-sub">Your own meetings, tasks and events</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="sa-chip" style="background:#eff6ff;color:#1d4ed8"><span class="sa-dot" style="background:#3b82f6"></span>{{ $calendar['meetings'] }} {{ $calendar['meetings'] === 1 ? 'meeting' : 'meetings' }} today</span>
                        <span class="sa-chip" style="background:#fffbeb;color:#92400e"><span class="sa-dot" style="background:#f59e0b"></span>{{ $calendar['tasks_done'] }}/{{ $calendar['tasks'] }} tasks done</span>
                        <span class="sa-chip"><span class="sa-dot" style="background:#8b5cf6"></span>{{ $calendar['events'] }} {{ $calendar['events'] === 1 ? 'event' : 'events' }}</span>
                        <a href="{{ route('super-admin.calendar') }}" class="ml-1 text-xs font-medium text-violet-600 hover:text-violet-800 whitespace-nowrap">Open →</a>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 border-t border-gray-100">
                    @foreach ($calLists as [$heading, $count, $items, $withDate, $empty, $more])
                        @php $isOverdue = $loop->last; @endphp
                        <div class="min-w-0 pb-3 {{ !$loop->first ? 'border-t md:border-t-0 md:border-l border-gray-100' : '' }}">
                            <div class="px-5 pt-4 pb-1.5 flex items-center justify-between gap-2">
                                <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide truncate">{{ $heading }}</p>
                                <span class="text-[11px] font-semibold tabular-nums px-1.5 py-px rounded-full {{ $isOverdue && $count > 0 ? 'bg-rose-50 text-rose-600' : 'bg-gray-100 text-gray-500' }}">{{ number_format($count) }}</span>
                            </div>
                            @forelse ($items as $e)
                                <a href="{{ $calDay($e['date']) }}" class="flex items-start gap-3 px-5 py-2 hover:bg-gray-50/70">
                                    <span class="mt-0.5 w-1 h-9 rounded-full flex-shrink-0" style="background: {{ $e['done'] ? '#d1d5db' : ($calTone[$e['type']] ?? '#9ca3af') }}"></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium leading-snug truncate {{ $e['done'] ? 'line-through text-gray-400' : 'text-gray-800' }}" title="{{ $e['title'] }}">{{ $e['title'] }}</p>
                                        <p class="text-xs text-gray-400 truncate mt-0.5" title="{{ $e['with'] ? 'With ' . $e['with'] : '' }}">
                                            @if ($withDate)<span class="{{ $isOverdue ? 'text-rose-600 font-medium' : 'text-gray-500' }}">{{ $e['day'] }}</span> · @endif{{ $e['time'] }}@if ($e['with']) · With {{ $e['with'] }}@endif
                                        </p>
                                    </div>
                                </a>
                            @empty
                                <p class="px-5 py-5 text-sm text-gray-400">{{ $empty }}</p>
                            @endforelse
                            @if ($more > 0)
                                <a href="{{ route('super-admin.calendar') }}" class="block px-5 pt-1 text-xs font-medium text-violet-600 hover:text-violet-800">+{{ $more }} more</a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ═══════════════ Schools — recently registered · top by teachers ═══════════════ --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
            <div class="sa-card xl:col-span-2 min-w-0 overflow-hidden">
                <div class="px-5 pt-5 pb-3 flex items-start justify-between gap-3">
                    <div>
                        <h3 class="sa-card-title">Recently Registered Schools</h3>
                        <p class="sa-card-sub">The newest schools on SuperLMS</p>
                    </div>
                    <a href="{{ route('super-admin.schools') }}" class="text-xs font-medium text-violet-600 hover:text-violet-800 whitespace-nowrap">View all →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[520px]">
                        <thead class="text-gray-400 text-[11px] uppercase tracking-wide">
                            <tr class="border-y border-gray-100 bg-gray-50/60">
                                <th class="px-5 py-2.5 text-left font-medium">School</th>
                                <th class="px-3 py-2.5 text-right font-medium">Students</th>
                                <th class="px-3 py-2.5 text-right font-medium">Teachers</th>
                                <th class="px-3 py-2.5 text-left font-medium">Joined</th>
                                <th class="px-5 py-2.5 text-left font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($recentSchools as $school)
                                <tr class="hover:bg-gray-50/70">
                                    <td class="px-5 py-3 min-w-[200px]">
                                        <div class="flex items-center gap-3 min-w-0">
                                            @if ($school['logo'])
                                                <img src="{{ $school['logo'] }}" alt="" class="w-9 h-9 rounded-xl object-cover border border-gray-100 flex-shrink-0">
                                            @else
                                                <span class="w-9 h-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center text-xs font-semibold flex-shrink-0">{{ strtoupper(mb_substr($school['name'], 0, 1)) }}</span>
                                            @endif
                                            <p class="font-medium text-gray-800 leading-snug line-clamp-2 break-words" title="{{ $school['name'] }}">{{ $school['name'] }}</p>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-right text-gray-700 tabular-nums">{{ number_format($school['students_count']) }}</td>
                                    <td class="px-3 py-3 text-right text-gray-700 tabular-nums">{{ number_format($school['teachers_count']) }}</td>
                                    <td class="px-3 py-3 text-gray-500 whitespace-nowrap">{{ $school['created_at'] }}</td>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded-full {{ $school['status'] ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-600' }}">
                                            <span class="sa-dot" style="background: {{ $school['status'] ? '#10b981' : '#f43f5e' }}; width: .375rem; height: .375rem"></span>
                                            {{ $school['status'] ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-10 text-center text-sm text-gray-400">No schools registered yet</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- The five schools with the most teachers: rank, logo, name with
                 a bar against the first, and the count. --}}
            @php
                $teacherTop  = array_values(array_filter($topSchoolsByTeachers, fn ($s) => (int) $s['teachers_count'] > 0));
                $maxTeachers = max(1, (int) max(array_column($teacherTop, 'teachers_count') ?: [0]));
            @endphp
            <div class="sa-card min-w-0 flex flex-col overflow-hidden" data-home-top-teachers>
                <div class="px-5 pt-5 pb-3 flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="sa-card-title">Top Schools by Teachers</h3>
                        <p class="sa-card-sub">The schools with the most teachers</p>
                    </div>
                    <a href="{{ route('super-admin.teachers') }}" class="text-xs font-medium text-violet-600 hover:text-violet-800 whitespace-nowrap">View all →</a>
                </div>
                <ol class="divide-y divide-gray-100 border-t border-gray-100">
                    @forelse ($teacherTop as $school)
                        <li class="px-5 py-3 flex items-center gap-3 min-w-0">
                            <span class="w-5 flex-shrink-0 text-center text-xs font-semibold tabular-nums {{ $loop->first ? 'text-violet-600' : 'text-gray-400' }}">{{ $loop->iteration }}</span>
                            @if (!empty($school['logo']))
                                <img src="{{ $school['logo'] }}" alt="" class="w-9 h-9 rounded-xl object-cover border border-gray-100 flex-shrink-0">
                            @else
                                <span class="w-9 h-9 rounded-xl bg-gray-100 text-gray-500 flex items-center justify-center text-xs font-semibold flex-shrink-0">{{ strtoupper(mb_substr($school['name'], 0, 1)) }}</span>
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate" title="{{ $school['name'] }}">{{ $school['name'] }}</p>
                                <div class="mt-1.5 h-1 rounded-full bg-gray-100">
                                    <div class="h-1 rounded-full" style="width: {{ round($school['teachers_count'] / $maxTeachers * 100) }}%; background: {{ $loop->first ? '#0d9488' : '#9fdcd2' }}"></div>
                                </div>
                            </div>
                            <div class="pl-1 text-right flex-shrink-0">
                                <p class="text-sm font-semibold text-gray-900 tabular-nums leading-tight">{{ number_format($school['teachers_count']) }}</p>
                                <p class="text-[11px] text-gray-400 leading-tight">{{ (int) $school['teachers_count'] === 1 ? 'teacher' : 'teachers' }}</p>
                            </div>
                        </li>
                    @empty
                        <li class="px-5 py-10 text-center text-sm text-gray-400">No teachers yet</li>
                    @endforelse
                </ol>
            </div>
        </div>

        {{-- ═══════════════ Requests — tickets · enquiries · website ═══════════════ --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <div class="sa-card min-w-0 overflow-hidden">
                <div class="px-5 pt-5 pb-3 flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="sa-card-title">Latest Support Tickets</h3>
                        <p class="sa-card-sub">{{ $supportStats['this_month'] }} this month · {{ $supportStats['total'] }} in all · {{ $supportPct }}% replied</p>
                    </div>
                    <a href="{{ route('super-admin.support') }}" class="text-xs font-medium text-violet-600 hover:text-violet-800 whitespace-nowrap">Open →</a>
                </div>
                <div class="divide-y divide-gray-100 border-t border-gray-100">
                    @forelse ($latestTickets as $t)
                        <div class="px-5 py-3 flex items-center gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 leading-snug line-clamp-2 break-words" title="{{ $t['topic'] }}">{{ $t['topic'] }}</p>
                                <p class="text-xs text-gray-400 truncate mt-0.5" title="{{ $t['school'] }}">{{ $t['school'] }} · {{ $t['time'] }}</p>
                            </div>
                            <span class="text-[11px] font-medium px-2 py-0.5 rounded-full flex-shrink-0 {{ $t['replied'] ? 'bg-gray-100 text-gray-600' : 'bg-rose-50 text-rose-600' }}">{{ $t['replied'] ? 'Replied' : 'Pending' }}</span>
                        </div>
                    @empty
                        <p class="px-5 py-8 text-center text-sm text-gray-400">No support tickets yet</p>
                    @endforelse
                </div>
            </div>

            <div class="sa-card min-w-0 overflow-hidden">
                <div class="px-5 pt-5 pb-3 flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="sa-card-title">Latest Enquiries</h3>
                        <p class="sa-card-sub">{{ $enquiryStats['demo_total'] }} demo requests · {{ $enquiryStats['contact_total'] }} contact messages</p>
                    </div>
                    <a href="{{ route('super-admin.enquiries') }}" class="text-xs font-medium text-violet-600 hover:text-violet-800 whitespace-nowrap">Open →</a>
                </div>
                <div class="divide-y divide-gray-100 border-t border-gray-100">
                    @forelse ($latestEnquiries as $e)
                        <div class="px-5 py-3 flex items-center gap-3">
                            <span class="w-14 text-[11px] font-medium text-gray-400 flex-shrink-0 uppercase tracking-wide">{{ $e['kind'] }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate" title="{{ $e['name'] }}">{{ $e['name'] ?: '—' }}</p>
                                <p class="text-xs text-gray-400 truncate mt-0.5" title="{{ $e['school'] }}">{{ $e['school'] ?: '—' }} · {{ $e['time'] }}</p>
                            </div>
                            <span class="text-[11px] font-medium px-2 py-0.5 rounded-full flex-shrink-0 {{ $e['answered'] ? 'bg-gray-100 text-gray-600' : 'bg-rose-50 text-rose-600' }}">{{ $e['answered'] ? 'Answered' : 'Pending' }}</span>
                        </div>
                    @empty
                        <p class="px-5 py-8 text-center text-sm text-gray-400">No enquiries yet</p>
                    @endforelse
                </div>
            </div>

            {{-- Website: three figures side by side, a bar for each of the 7
                 days (today's in violet) and the most visited page — on the
                 card itself, no boxes inside it. --}}
            @php
                $webDaily = $websiteWeek['daily'] ?? [];
                $webMax   = max(1, (int) max(array_column($webDaily, 'count') ?: [0]));
            @endphp
            <div class="sa-card p-5 min-w-0 md:col-span-2 xl:col-span-1 flex flex-col" data-home-website>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="sa-card-title">Website · Last 7 Days</h3>
                        <p class="sa-card-sub">Visits to the SuperLMS website</p>
                    </div>
                    <a href="{{ route('super-admin.analytics') }}" class="text-xs font-medium text-violet-600 hover:text-violet-800 whitespace-nowrap">Analytics →</a>
                </div>
                <dl class="mt-5 grid grid-cols-3 divide-x divide-gray-100">
                    @foreach ([['Visits', $websiteWeek['visits']], ['Unique', $websiteWeek['unique']], ['Today', $websiteWeek['today']]] as [$label, $val])
                        <div class="min-w-0 {{ $loop->first ? 'pr-3' : 'px-3' }}">
                            <dt class="text-xs text-gray-400 truncate">{{ $label }}</dt>
                            <dd class="mt-1 text-xl leading-none font-semibold text-gray-900 tabular-nums truncate">{{ number_format($val) }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if ($webDaily)
                    <div class="mt-5 flex items-end gap-2 h-16" aria-label="Visits each day">
                        @foreach ($webDaily as $d)
                            <div class="flex-1 h-full flex items-end" title="{{ $d['date'] }} · {{ number_format($d['count']) }} {{ $d['count'] === 1 ? 'visit' : 'visits' }}">
                                <div class="w-full rounded-md {{ $d['today'] ? 'bg-violet-600' : 'bg-gray-200' }}" style="height: {{ $d['count'] > 0 ? max(6, round($d['count'] / $webMax * 100)) : 4 }}%"></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-1.5 flex gap-2">
                        @foreach ($webDaily as $d)
                            <span class="flex-1 text-center text-[10px] {{ $d['today'] ? 'font-semibold text-violet-600' : 'text-gray-400' }}">{{ $d['label'] }}</span>
                        @endforeach
                    </div>
                @endif
                <div class="mt-auto pt-4">
                    <div class="pt-3 border-t border-gray-100 flex items-baseline justify-between gap-3 min-w-0">
                        <p class="text-xs text-gray-400 whitespace-nowrap">Most visited</p>
                        <p class="text-sm font-medium text-gray-800 truncate" title="{{ $websiteWeek['top'] }}">{{ $websiteWeek['top'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════ Team & platform — salary · staff today · push · rating ═══════════════ --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
            <div class="sa-card p-5 min-w-0 flex flex-col">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="min-w-0">
                        <h3 class="sa-card-title">Salary · {{ $staff['month'] }}</h3>
                        <p class="sa-card-sub">{{ $staff['paid_count'] }} of {{ $staff['active'] }} paid</p>
                    </div>
                    <a href="{{ route('super-admin.payroll') }}" class="text-xs font-medium text-violet-600 hover:text-violet-800 whitespace-nowrap">Payroll →</a>
                </div>
                <p class="text-xs text-gray-400">Paid</p>
                <p class="mt-0.5 flex flex-wrap items-baseline gap-x-1.5 tabular-nums">
                    <span class="text-xl font-semibold text-gray-900">{{ $inr($staff['paid_amount']) }}</span>
                    <span class="text-xs text-gray-400">of {{ $inr($staff['bill']) }}</span>
                </p>
                <div class="mt-2.5 h-1.5 rounded-full bg-gray-100"><div class="h-1.5 rounded-full bg-violet-600" style="width: {{ min(100, $staff['paid_pct']) }}%"></div></div>
                <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide mt-4 mb-1">To pay · {{ $staff['unpaid_count'] }}</p>
                <div class="divide-y divide-gray-100">
                    @forelse ($staff['unpaid'] as $e)
                        <div class="flex items-center justify-between gap-3 py-2">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate" title="{{ $e['name'] }}">{{ $e['name'] }}</p>
                                <p class="text-xs text-gray-400 truncate">{{ \Illuminate\Support\Str::headline((string) $e['role']) }}</p>
                            </div>
                            <p class="text-sm font-semibold text-gray-700 tabular-nums whitespace-nowrap">{{ $inr($e['salary']) }}</p>
                        </div>
                    @empty
                        <p class="py-2 text-sm text-gray-400">Everyone has been paid</p>
                    @endforelse
                </div>
            </div>

            <div class="sa-card p-5 min-w-0">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="min-w-0">
                        <h3 class="sa-card-title">Staff Today</h3>
                        <p class="sa-card-sub">{{ now()->format('d M') }} · {{ $staff['today']['marked'] > 0 ? $staff['today']['pct'] . '% present' : 'not marked yet' }}</p>
                    </div>
                </div>
                @include('livewire.super-admin.partials.dash-donut', [
                    'segments' => [
                        ['label' => 'Present', 'value' => $staff['today']['present'], 'color' => '#0d9488'],
                        ['label' => 'Half day', 'value' => $staff['today']['half_day'], 'color' => '#9fdcd2'],
                        ['label' => 'Absent', 'value' => $staff['today']['absent'], 'color' => '#f43f5e'],
                        ['label' => 'Leave / holiday', 'value' => $staff['today']['leave'] + $staff['today']['holiday'], 'color' => '#111827'],
                        ['label' => 'Not marked', 'value' => $staffNotMarked, 'color' => '#e5e7eb'],
                    ],
                    'center' => $staff['today']['present'] . '/' . $staff['active'], 'centerLabel' => 'Present', 'size' => 120, 'legend' => true,
                    'format' => fn ($v) => (int) $v,
                ])
            </div>

            {{-- Push: the counts side by side and the latest one sent, on the
                 card itself (no boxes inside it). --}}
            <div class="sa-card p-5 min-w-0 flex flex-col" data-home-push>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="sa-card-title">Push Notifications</h3>
                        <p class="sa-card-sub">Sent from the panel</p>
                    </div>
                    <a href="{{ route('super-admin.push-notification') }}" class="text-xs font-medium text-violet-600 hover:text-violet-800 whitespace-nowrap">Open →</a>
                </div>
                <dl class="mt-5 grid grid-cols-3 divide-x divide-gray-100">
                    @foreach ([['This month', $pushSummary['this_month']], ['In all', $pushSummary['total']], ['Reached', $pushSummary['reached'] ?? 0]] as [$label, $val])
                        <div class="min-w-0 {{ $loop->first ? 'pr-3' : 'px-3' }}">
                            <dt class="text-xs text-gray-400 truncate">{{ $label }}</dt>
                            <dd class="mt-1 text-lg leading-none font-semibold text-gray-900 tabular-nums truncate" title="{{ number_format($val) }}">{{ number_format($val) }}</dd>
                        </div>
                    @endforeach
                </dl>
                <div class="mt-auto pt-5">
                    <div class="pt-3 border-t border-gray-100 min-w-0">
                        <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">Latest</p>
                        @if ($pushSummary['latest'])
                            <p class="text-sm font-medium text-gray-800 mt-1 leading-snug line-clamp-2 break-words" title="{{ $pushSummary['latest']['title'] }}">{{ $pushSummary['latest']['title'] }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ number_format($pushSummary['latest']['recipients']) }} people · {{ $pushSummary['latest']['time'] }}</p>
                        @else
                            <p class="text-sm text-gray-400 mt-1">Nothing sent yet</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- LMS rating: figures only — the average, how many reviews gave
                 each number of stars, and the schools rated and not yet. --}}
            @php
                $starPath = 'M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z';
                $avgStars   = (int) round((float) $ratingStats['avg_rating']);
                $rateDist   = $ratingStats['dist'] ?? array_fill_keys([5, 4, 3, 2, 1], 0);
                $rateCount  = (int) ($ratingStats['reviews'] ?? array_sum($rateDist));
                $rateMax    = max(1, (int) max($rateDist ?: [0]));
            @endphp
            <div class="sa-card p-5 min-w-0 flex flex-col" data-home-rating>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="sa-card-title">LMS Rating</h3>
                        <p class="sa-card-sub">{{ number_format($rateCount) }} {{ $rateCount === 1 ? 'review' : 'reviews' }} by schools</p>
                    </div>
                    <a href="{{ route('super-admin.rating') }}" class="text-xs font-medium text-violet-600 hover:text-violet-800 whitespace-nowrap">View →</a>
                </div>
                <div class="mt-5 flex items-center gap-3">
                    <p class="text-[32px] leading-none font-semibold text-gray-900 tabular-nums">{{ number_format($ratingStats['avg_rating'], 1) }}</p>
                    <div>
                        <div class="flex">
                            @for ($i = 1; $i <= 5; $i++)
                                <svg class="w-4 h-4 {{ $i <= $avgStars ? 'text-amber-400' : 'text-gray-200' }}" fill="currentColor" viewBox="0 0 20 20"><path d="{{ $starPath }}" /></svg>
                            @endfor
                        </div>
                        <p class="text-[11px] text-gray-400 mt-0.5">average, out of 5</p>
                    </div>
                </div>
                <ul class="mt-4 space-y-1.5">
                    @foreach ($rateDist as $stars => $n)
                        <li class="flex items-center gap-2 text-xs">
                            <span class="w-2 text-gray-500 tabular-nums">{{ $stars }}</span>
                            <svg class="w-3 h-3 text-amber-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="{{ $starPath }}" /></svg>
                            <span class="flex-1 h-1.5 rounded-full bg-gray-100">
                                <span class="block h-1.5 rounded-full bg-amber-400" style="width: {{ $n > 0 ? max(4, round($n / $rateMax * 100)) : 0 }}%"></span>
                            </span>
                            <span class="w-6 text-right text-gray-500 tabular-nums">{{ number_format($n) }}</span>
                        </li>
                    @endforeach
                </ul>
                <dl class="mt-auto pt-4">
                    <div class="pt-3 border-t border-gray-100 grid grid-cols-2 divide-x divide-gray-100">
                        <div class="min-w-0 pr-3">
                            <dt class="text-xs text-gray-400 truncate">Schools rated</dt>
                            <dd class="mt-1 text-lg leading-none font-semibold text-gray-900 tabular-nums">{{ number_format($ratingStats['schools_rated']) }}<span class="text-xs font-normal text-gray-400"> / {{ number_format($ratingStats['total_schools']) }}</span></dd>
                        </div>
                        <div class="min-w-0 px-3">
                            <dt class="text-xs text-gray-400 truncate">Not yet</dt>
                            <dd class="mt-1 text-lg leading-none font-semibold text-gray-900 tabular-nums">{{ number_format($ratingStats['remaining']) }}</dd>
                        </div>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    {{-- The fee and registration charts. They used to be drawn once from an
         Alpine init on the canvas, so the panel's auto-refresh (which re-renders
         the page whenever something on it changes, e.g. "2 minutes ago")
         wiped their size and left them blank until a reload. Now the canvases
         are wire:ignore'd and the charts are updated in place from the
         figures above after every re-render. --}}
    @include('livewire.super-admin.partials.dash-charts-js')
    <script>
        (function () {
            const VIOLET = '#0d9488', INK = '#111827';
            const short = v => v >= 1e7 ? (v / 1e7).toFixed(1) + 'Cr' : v >= 1e5 ? (v / 1e5).toFixed(1) + 'L' : v >= 1e3 ? (v / 1e3).toFixed(0) + 'k' : v;
            const axis = { color: '#9ca3af', font: { size: 11 } };
            const tooltip = { backgroundColor: INK, padding: 10, cornerRadius: 8 };

            saCharts.define('home', {
                // One bar for each day money came in, under its date; the
                // tooltip gives the full date and the schools it came from.
                homeFeeChart: p => ({
                    type: 'bar',
                    data: {
                        labels: p.fee.labels || [],
                        datasets: [{
                            label: 'Collected', data: p.fee.data || [],
                            backgroundColor: VIOLET, hoverBackgroundColor: '#0f766e',
                            borderRadius: 6, borderSkipped: false, barPercentage: 0.8, categoryPercentage: 0.7, maxBarThickness: 28,
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: false },
                            tooltip: Object.assign({}, tooltip, { displayColors: false,
                                callbacks: {
                                    title: items => items.length ? ((p.fee.full || [])[items[0].dataIndex] || items[0].label) : '',
                                    label: c => '₹' + Number(c.parsed.y).toLocaleString('en-IN'),
                                    afterBody: items => items.length ? ((p.fee.schools || [])[items[0].dataIndex] || []) : [],
                                } })
                        },
                        scales: {
                            x: { grid: { display: false }, border: { display: false }, ticks: Object.assign({ maxRotation: 0, autoSkip: true }, axis) },
                            y: { beginAtZero: true, border: { display: false }, grid: { color: 'rgba(17,24,39,0.05)' },
                                 ticks: Object.assign({ maxTicksLimit: 5, callback: v => '₹' + short(v) }, axis) }
                        }
                    }
                }),
                homeRegChart: p => ({
                    type: 'bar',
                    data: {
                        labels: p.reg.labels || [],
                        datasets: [
                            { label: 'Students', data: p.reg.students || [], backgroundColor: VIOLET, borderRadius: 6, borderSkipped: false, barPercentage: 0.7, categoryPercentage: 0.6, maxBarThickness: 12 },
                            { label: 'Teachers', data: p.reg.teachers || [], backgroundColor: INK, borderRadius: 6, borderSkipped: false, barPercentage: 0.7, categoryPercentage: 0.6, maxBarThickness: 12 }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: { legend: { display: false }, tooltip: tooltip },
                        scales: {
                            x: { grid: { display: false }, border: { display: false }, ticks: Object.assign({ maxRotation: 0, autoSkip: true }, axis) },
                            y: { beginAtZero: true, border: { display: false }, grid: { color: 'rgba(17,24,39,0.05)' }, ticks: Object.assign({ precision: 0, maxTicksLimit: 5 }, axis) }
                        }
                    }
                }),
            });
        })();
    </script>
</div>
