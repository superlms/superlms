<div class="min-h-screen bg-gray-50">
    @include('livewire.super-admin.partials.dash-styles')

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
        $feePeak = count($feeData) && max($feeData) > 0 ? array_search(max($feeData), $feeData, true) : -1;

        $allUsers    = (int) $totalStudents + (int) $totalTeachers;
        $activeUsers = (int) $activeStudents + (int) $activeTeachers;
        $activePct   = $allUsers > 0 ? round($activeUsers / $allUsers * 100) : 0;

        $activityTone = [
            'emerald' => ['#ecfdf5', '#059669'],
            'blue'    => ['#eff6ff', '#2563eb'],
            'violet'  => ['#f5f3ff', '#7c3aed'],
        ];

        // The two Chart.js charts' figures (see partials.dash-charts-js).
        $homeCharts = [
            'fee' => ['labels' => $superAdminFee['labels'], 'data' => $feeData, 'peak' => (int) $feePeak],
            'reg' => ['labels' => $monthShort, 'students' => $studentSeries, 'teachers' => $teacherSeries],
        ];
    @endphp

    {{-- The charts' figures: refreshed by every re-render, read again after it. --}}
    <div class="hidden" data-sa-charts="home" data-payload="{{ json_encode($homeCharts) }}"></div>

    {{-- ══════════════════════════════ HEADER ══════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3 sticky top-0 z-40">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:min-h-9">
            <div class="min-w-0 sm:flex sm:items-baseline sm:gap-2">
                <h1 class="min-w-0 text-lg sm:text-xl font-bold text-gray-900 truncate">Overview</h1>
                <p class="text-xs text-gray-400 mt-0.5 sm:mt-0 truncate">{{ now()->format('l, d M Y') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="refreshData"
                    class="inline-flex items-center gap-1.5 px-3 py-[7px] text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" wire:loading.class="animate-spin" wire:target="refreshData" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh
                </button>
                <a href="{{ route('super-admin.schools') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-white bg-violet-600 rounded-lg hover:bg-violet-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add School
                </a>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 space-y-5">

        {{-- ═══════════════ KPIs — figure, change vs last month, sparkline ═══════════════ --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
            @include('livewire.super-admin.partials.dash-kpi', [
                'label' => 'Total Schools', 'value' => number_format($totalSchools), 'icon' => 'building-office-2', 'color' => 'violet',
                'series' => $schoolSeries, 'trend' => $trend($schoolSeries),
                'sub' => number_format($activeSchools) . ' active · ' . number_format($newSchoolsThisMonth) . ' new this month',
                'href' => route('super-admin.schools'),
            ])
            @include('livewire.super-admin.partials.dash-kpi', [
                'label' => 'Total Students', 'value' => number_format($totalStudents), 'icon' => 'users', 'color' => 'sky',
                'series' => $studentSeries, 'trend' => $trend($studentSeries),
                'sub' => number_format($activeStudents) . ' active',
                'href' => route('super-admin.students'),
            ])
            @include('livewire.super-admin.partials.dash-kpi', [
                'label' => 'Total Teachers', 'value' => number_format($totalTeachers), 'icon' => 'academic-cap', 'color' => 'emerald',
                'series' => $teacherSeries, 'trend' => $trend($teacherSeries),
                'sub' => number_format($activeTeachers) . ' active',
                'href' => route('super-admin.teachers'),
            ])
            @include('livewire.super-admin.partials.dash-kpi', [
                'label' => 'Fee Collected', 'value' => $short($superAdminFee['collected']), 'icon' => 'banknotes', 'color' => 'amber',
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
                        <p class="sa-card-sub">Super Admin fee collected month by month · last 12 months</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="sa-chip"><span class="sa-dot" style="background:#7c3aed"></span>{{ $short($superAdminFee['year_total']) }} in 12 months</span>
                        <span class="sa-chip" style="background:#fef2f2;color:#b91c1c">{{ $short($superAdminFee['pending']) }} pending</span>
                    </div>
                </div>
                <div class="h-64 relative" wire:ignore wire:key="chart-homeFeeChart">
                    <canvas id="homeFeeChart"></canvas>
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

            <div class="sa-card p-5 min-w-0">
                <div class="flex items-start justify-between gap-3 mb-5">
                    <div>
                        <h3 class="sa-card-title">Students by School</h3>
                        <p class="sa-card-sub">The biggest schools and the rest</p>
                    </div>
                    <a href="{{ route('super-admin.students') }}" class="text-xs font-medium text-violet-600 hover:text-violet-800 whitespace-nowrap">View all →</a>
                </div>
                @if (count($studentsBySchool))
                    <div class="flex justify-center mb-5">
                        @include('livewire.super-admin.partials.dash-donut', [
                            'segments' => $studentsBySchool, 'center' => number_format($totalStudents), 'centerLabel' => 'Students',
                            'size' => 168, 'legend' => false,
                        ])
                    </div>
                    <ul class="space-y-2.5">
                        @foreach ($studentsBySchool as $part)
                            <li class="flex items-start gap-2 text-sm min-w-0">
                                <span class="sa-dot mt-[0.4rem]" style="background: {{ $part['color'] }}"></span>
                                <span class="flex-1 min-w-0 text-gray-600 leading-snug line-clamp-2 break-words" title="{{ $part['label'] }}">{{ $part['label'] }}</span>
                                <span class="pl-2 text-right leading-snug whitespace-nowrap">
                                    <span class="font-semibold text-gray-900 tabular-nums">{{ $totalStudents > 0 ? round($part['value'] / $totalStudents * 100) : 0 }}%</span>
                                    <span class="block text-[11px] text-gray-400 tabular-nums">{{ number_format($part['value']) }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="py-16 text-center text-sm text-gray-400">No students yet</p>
                @endif
            </div>
        </div>

        {{-- ═══════════════ Recent activity · registrations (bars) · active users (dark) ═══════════════
             Three across from xl; below that the chart takes a row of its own
             first and the two cards share the next, so none is squeezed. --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-12 gap-5">
            <div class="sa-card p-5 xl:col-span-4 min-w-0">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <h3 class="sa-card-title">Recent Activity</h3>
                        <p class="sa-card-sub">New schools, students and teachers</p>
                    </div>
                </div>
                <div class="space-y-4 overflow-y-auto max-h-[19rem] pr-1">
                    @forelse ($activityFeed as $activity)
                        @php [$tint, $ink] = $activityTone[$activity['color']] ?? ['#f3f4f6', '#6b7280']; @endphp
                        <div class="flex items-start gap-3">
                            <span class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background: {{ $tint }}; color: {{ $ink }}">
                                <x-icon name="{{ $activity['icon'] }}" class="w-4 h-4" />
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-baseline justify-between gap-2">
                                    <p class="text-sm font-medium text-gray-800 truncate" title="{{ $activity['title'] }}">{{ $activity['title'] }}</p>
                                    <span class="text-[11px] text-gray-400 whitespace-nowrap flex-shrink-0">{{ $activity['time'] }}</span>
                                </div>
                                <p class="text-xs text-gray-400 mt-0.5 leading-snug line-clamp-2 break-words" title="{{ $activity['description'] }}">{{ $activity['description'] }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="py-10 text-center text-sm text-gray-400">No recent activity</p>
                    @endforelse
                </div>
            </div>

            <div class="sa-card p-5 md:col-span-2 xl:col-span-5 md:order-first xl:order-none min-w-0">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                    <div>
                        <h3 class="sa-card-title">Registrations</h3>
                        <p class="sa-card-sub">Students and teachers added · last 12 months</p>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        <span class="flex items-center gap-1.5"><span class="sa-dot" style="background:#7c3aed"></span>Students</span>
                        <span class="flex items-center gap-1.5"><span class="sa-dot" style="background:#111827"></span>Teachers</span>
                    </div>
                </div>
                <div class="h-64 relative" wire:ignore wire:key="chart-homeRegChart">
                    <canvas id="homeRegChart"></canvas>
                </div>
            </div>

            <div class="sa-card-dark p-5 xl:col-span-3 min-w-0 flex flex-col">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-[15px] font-semibold text-white">Active Users</h3>
                        <p class="text-xs text-white/50 mt-0.5">Students and teachers who can sign in</p>
                    </div>
                </div>
                <div class="flex justify-center my-5">
                    @include('livewire.super-admin.partials.dash-donut', [
                        'segments' => [
                            ['label' => 'Active students', 'value' => (int) $activeStudents, 'color' => '#a78bfa'],
                            ['label' => 'Active teachers', 'value' => (int) $activeTeachers, 'color' => '#38bdf8'],
                            ['label' => 'Inactive', 'value' => max(0, $allUsers - $activeUsers), 'color' => 'rgba(255,255,255,.14)'],
                        ],
                        'center' => $activePct . '%', 'centerLabel' => 'Active', 'size' => 140, 'legend' => false, 'dark' => true,
                    ])
                </div>
                <dl class="mt-auto space-y-2.5 text-sm">
                    <div class="flex items-center justify-between gap-2">
                        <dt class="flex items-center gap-2 text-white/60"><span class="sa-dot" style="background:#a78bfa"></span>Students</dt>
                        <dd class="font-semibold tabular-nums">{{ number_format($activeStudents) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <dt class="flex items-center gap-2 text-white/60"><span class="sa-dot" style="background:#38bdf8"></span>Teachers</dt>
                        <dd class="font-semibold tabular-nums">{{ number_format($activeTeachers) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2 pt-2.5 border-t border-white/10">
                        <dt class="text-white/60">Active schools</dt>
                        <dd class="font-semibold tabular-nums">{{ number_format($activeSchools) }} / {{ number_format($totalSchools) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <dt class="text-white/60">Website · 7 days</dt>
                        <dd class="font-semibold tabular-nums">{{ number_format($websiteWeek['visits']) }} visits</dd>
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

            <div class="sa-card p-5 min-w-0">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <h3 class="sa-card-title">Top Schools by Teachers</h3>
                        <p class="sa-card-sub">Top 5</p>
                    </div>
                </div>
                @php $maxTeachers = max(1, (int) max(array_column($topSchoolsByTeachers, 'teachers_count') ?: [0])); @endphp
                <div class="space-y-4">
                    @forelse ($topSchoolsByTeachers as $school)
                        <div class="min-w-0">
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <p class="font-medium text-gray-800 truncate" title="{{ $school['name'] }}"><span class="text-gray-400 tabular-nums mr-1.5">{{ $school['rank'] }}</span>{{ $school['name'] }}</p>
                                <p class="text-gray-700 tabular-nums whitespace-nowrap font-semibold">{{ number_format($school['teachers_count']) }}</p>
                            </div>
                            <div class="mt-1.5 h-1.5 rounded-full bg-gray-100">
                                <div class="h-1.5 rounded-full" style="width: {{ round($school['teachers_count'] / $maxTeachers * 100) }}%; background: {{ $loop->first ? '#7c3aed' : '#c4b5fd' }}"></div>
                            </div>
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm text-gray-400">No schools yet</p>
                    @endforelse
                </div>
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

            <div class="sa-card p-5 min-w-0 md:col-span-2 xl:col-span-1">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="min-w-0">
                        <h3 class="sa-card-title">Website · Last 7 Days</h3>
                        <p class="sa-card-sub">Visits to the SuperLMS website</p>
                    </div>
                    <a href="{{ route('super-admin.analytics') }}" class="text-xs font-medium text-violet-600 hover:text-violet-800 whitespace-nowrap">Analytics →</a>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    @foreach ([['Visits', number_format($websiteWeek['visits'])], ['Unique', number_format($websiteWeek['unique'])], ['Today', number_format($websiteWeek['today'])]] as [$label, $val])
                        <div class="rounded-xl bg-gray-50 px-3 py-3 min-w-0">
                            <p class="text-xs text-gray-400 truncate">{{ $label }}</p>
                            <p class="text-base font-semibold text-gray-900 tabular-nums mt-0.5 break-words">{{ $val }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3 rounded-xl bg-violet-50 px-3.5 py-3 min-w-0">
                    <p class="text-xs text-violet-500">Most visited page</p>
                    <p class="text-sm font-semibold text-violet-800 mt-1 leading-snug break-all line-clamp-2" title="{{ $websiteWeek['top'] }}">{{ $websiteWeek['top'] }}</p>
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
                        ['label' => 'Present', 'value' => $staff['today']['present'], 'color' => '#7c3aed'],
                        ['label' => 'Half day', 'value' => $staff['today']['half_day'], 'color' => '#c4b5fd'],
                        ['label' => 'Absent', 'value' => $staff['today']['absent'], 'color' => '#f43f5e'],
                        ['label' => 'Leave / holiday', 'value' => $staff['today']['leave'] + $staff['today']['holiday'], 'color' => '#111827'],
                        ['label' => 'Not marked', 'value' => $staffNotMarked, 'color' => '#e5e7eb'],
                    ],
                    'center' => $staff['today']['present'] . '/' . $staff['active'], 'centerLabel' => 'Present', 'size' => 120, 'legend' => true,
                    'format' => fn ($v) => (int) $v,
                ])
            </div>

            <div class="sa-card p-5 min-w-0 flex flex-col">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="min-w-0">
                        <h3 class="sa-card-title">Push Notifications</h3>
                        <p class="sa-card-sub">Sent from the panel</p>
                    </div>
                    <a href="{{ route('super-admin.push-notification') }}" class="text-xs font-medium text-violet-600 hover:text-violet-800 whitespace-nowrap">Open →</a>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    @foreach ([['This month', $pushSummary['this_month']], ['In all', $pushSummary['total']]] as [$label, $val])
                        <div class="rounded-xl bg-gray-50 px-3.5 py-3 min-w-0">
                            <p class="text-xs text-gray-400 truncate">{{ $label }}</p>
                            <p class="text-lg font-semibold text-gray-900 tabular-nums mt-0.5">{{ number_format($val) }}</p>
                        </div>
                    @endforeach
                </div>
                @if ($pushSummary['latest'])
                    <div class="mt-3 rounded-xl bg-violet-50 p-3.5 min-w-0">
                        <p class="text-[11px] font-medium text-violet-500 uppercase tracking-wide">Latest</p>
                        <p class="text-sm font-medium text-violet-900 mt-1 leading-snug line-clamp-2 break-words" title="{{ $pushSummary['latest']['title'] }}">{{ $pushSummary['latest']['title'] }}</p>
                        <p class="text-xs text-violet-500/80 mt-1">{{ number_format($pushSummary['latest']['recipients']) }} people · {{ $pushSummary['latest']['time'] }}</p>
                    </div>
                @else
                    <p class="mt-3 text-sm text-gray-400">Nothing sent yet</p>
                @endif
            </div>

            @php
                $starPath = 'M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z';
                $avgStars = (int) round((float) $ratingStats['avg_rating']);
            @endphp
            <div class="sa-card p-5 min-w-0 flex flex-col">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="min-w-0">
                        <h3 class="sa-card-title">LMS Rating</h3>
                        <p class="sa-card-sub">{{ $ratingStats['schools_rated'] }} schools rated · {{ $ratingStats['remaining'] }} not yet</p>
                    </div>
                    <a href="{{ route('super-admin.rating') }}" class="text-xs font-medium text-violet-600 hover:text-violet-800 whitespace-nowrap">View →</a>
                </div>
                <div class="flex items-center gap-3">
                    <p class="text-[28px] leading-none font-semibold text-gray-900 tabular-nums">{{ number_format($ratingStats['avg_rating'], 1) }}</p>
                    <div>
                        <div class="flex">
                            @for ($i = 1; $i <= 5; $i++)
                                <svg class="w-4 h-4 {{ $i <= $avgStars ? 'text-amber-400' : 'text-gray-200' }}" fill="currentColor" viewBox="0 0 20 20"><path d="{{ $starPath }}" /></svg>
                            @endfor
                        </div>
                        <p class="text-[11px] text-gray-400 mt-0.5">average, out of 5</p>
                    </div>
                </div>
                @if ($latestReview)
                    <div class="mt-4 rounded-xl bg-gray-50 p-3.5 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg class="w-3 h-3 {{ $i <= $latestReview['rating'] ? 'text-amber-400' : 'text-gray-200' }}" fill="currentColor" viewBox="0 0 20 20"><path d="{{ $starPath }}" /></svg>
                                @endfor
                            </div>
                            <span class="text-[11px] text-gray-400 whitespace-nowrap">{{ $latestReview['time'] }}</span>
                        </div>
                        <p class="text-sm font-medium text-gray-800 mt-1.5 leading-snug line-clamp-2 break-words" title="{{ $latestReview['school'] }}">{{ $latestReview['school'] }}</p>
                        @if ($latestReview['feedback'])
                            <p class="text-xs text-gray-500 mt-0.5 leading-snug line-clamp-3 break-words" title="{{ $latestReview['feedback'] }}">{{ $latestReview['feedback'] }}</p>
                        @endif
                    </div>
                @else
                    <p class="mt-4 text-sm text-gray-400">No reviews yet</p>
                @endif
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
            const VIOLET = '#7c3aed', INK = '#111827';
            const short = v => v >= 1e7 ? (v / 1e7).toFixed(1) + 'Cr' : v >= 1e5 ? (v / 1e5).toFixed(1) + 'L' : v >= 1e3 ? (v / 1e3).toFixed(0) + 'k' : v;
            const axis = { color: '#9ca3af', font: { size: 11 } };
            const tooltip = { backgroundColor: INK, padding: 10, cornerRadius: 8 };

            saCharts.define('home', {
                homeFeeChart: p => ({
                    type: 'line',
                    data: {
                        labels: p.fee.labels || [],
                        datasets: [{
                            label: 'Collected', data: p.fee.data || [],
                            borderColor: VIOLET, borderWidth: 2.5, tension: 0.42, fill: true,
                            backgroundColor: c => {
                                const a = c.chart.chartArea;
                                if (!a) return 'rgba(124,58,237,0.12)';
                                const g = c.chart.ctx.createLinearGradient(0, a.top, 0, a.bottom);
                                g.addColorStop(0, 'rgba(124,58,237,0.28)');
                                g.addColorStop(1, 'rgba(124,58,237,0)');
                                return g;
                            },
                            pointRadius: c => c.dataIndex === p.fee.peak ? 5 : 0,
                            pointHoverRadius: 5, pointBackgroundColor: '#fff', pointBorderColor: VIOLET, pointBorderWidth: 2.5,
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: false },
                            tooltip: Object.assign({}, tooltip, { displayColors: false,
                                callbacks: { label: c => '₹' + Number(c.parsed.y).toLocaleString('en-IN') } })
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
