<div class="min-h-screen bg-gray-50">
    {{-- The Super Admin's dashboard look (2026-11-04): KPI cards with their
         change and a sparkline, the fee chart beside a donut, the attendance
         rate beside one dark card, then the rest in matching cards. --}}
    @include('livewire.super-admin.partials.dash-styles')

    @php
        $org     = ['organization' => $organization];
        $inr     = fn ($v) => '₹' . number_format((float) $v, 0);
        $short   = fn ($v) => \App\Support\DashboardChart::inrShort((float) $v);
        $trendOf = fn (array $s) => \App\Support\DashboardChart::trend((float) (end($s) ?: 0), (float) ($s[count($s) - 2] ?? 0));

        $collectionPct = $totalFee > 0 ? round(($overallFeeCollected / $totalFee) * 100, 1) : 0;

        // Today, and the days of the last 30 that were marked (oldest first).
        $pick        = $this->trendDay();
        $todayMarked = ($pick['student_total'] ?? 0) > 0;
        $markedDays  = array_values(array_filter($last15DaysData, fn ($d) => ($d['student_total'] ?? 0) > 0));
        $earlier     = array_values(array_filter($markedDays, fn ($d) => $d['date'] < ($pick['date'] ?? '')));
        $prevDay     = $earlier ? end($earlier) : null;
        // The change in attendance is in points against the last day marked before today.
        $attTrend = $todayMarked && $prevDay
            ? ['pct' => abs(round($pick['student_pct'] - $prevDay['student_pct'], 1)), 'up' => $pick['student_pct'] >= $prevDay['student_pct']]
            : null;
        $attAgainst = $prevDay && $prevDay['date'] === now()->subDay()->toDateString()
            ? 'vs yesterday'
            : ($prevDay ? 'vs ' . \Carbon\Carbon::parse($prevDay['date'])->format('D, d M') : '');
        $teacherMarked = ($pick['teacher_total'] ?? 0) > 0;
        $notMarked     = max(0, (int) $totalStudents - (int) ($pick['student_total'] ?? 0));

        $fee     = $feeSeries['data'] ?? [];
        $feePeak = count($fee) && max($fee) > 0 ? array_search(max($fee), $fee, true) : -1;

        $examLatest = $examTrend ? end($examTrend) : null;

        // The charts' figures (see partials.dash-charts-js); a day nobody was
        // marked on is a gap in the attendance lines, not a 0%.
        $homeCharts = [
            'fee'  => ['labels' => $feeSeries['labels'] ?? [], 'data' => $fee, 'peak' => (int) $feePeak],
            'att'  => [
                'labels'   => array_column($last15DaysData, 'label'),
                'students' => array_map(fn ($d) => $d['student_total'] > 0 ? $d['student_pct'] : null, $last15DaysData),
                'teachers' => array_map(fn ($d) => $d['teacher_total'] > 0 ? $d['teacher_pct'] : null, $last15DaysData),
                'sp' => array_column($last15DaysData, 'student_present'), 'sa' => array_column($last15DaysData, 'student_absent'),
                'tp' => array_column($last15DaysData, 'teacher_present'), 'ta' => array_column($last15DaysData, 'teacher_absent'),
            ],
            'exam' => [
                'labels' => array_column($examTrend, 'label'),
                'avg'    => array_column($examTrend, 'avg'),
                'pass'   => array_column($examTrend, 'pass_pct'),
                'meta'   => $examTrend,
            ],
        ];

        $tones = [
            'violet'  => ['#f5f3ff', '#7c3aed'],
            'sky'     => ['#f0f9ff', '#0284c7'],
            'emerald' => ['#ecfdf5', '#059669'],
            'amber'   => ['#fffbeb', '#d97706'],
            'rose'    => ['#fff1f2', '#e11d48'],
            'gray'    => ['#f3f4f6', '#6b7280'],
        ];
        $eventTone = [
            'blue' => 'sky', 'indigo' => 'violet', 'purple' => 'violet', 'green' => 'emerald',
            'orange' => 'amber', 'red' => 'rose', 'gray' => 'gray',
        ];
        $starPath = 'M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z';
    @endphp

    {{-- The charts' figures: refreshed by every re-render, read again after it. --}}
    <div class="hidden" data-sa-charts="adminHome" data-payload="{{ json_encode($homeCharts) }}"></div>

    {{-- ══════════════════════════════ HEADER ══════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:min-h-9">
            <div class="min-w-0 sm:flex sm:items-baseline sm:gap-2">
                <h1 class="min-w-0 text-lg sm:text-xl font-bold text-gray-900 truncate">Dashboard</h1>
                <p class="text-xs text-gray-400 mt-0.5 sm:mt-0 truncate">{{ now()->format('l, d M Y') }}</p>
            </div>
            <div class="flex items-center gap-2">
                {{-- Search --}}
                <div class="relative flex-1 sm:flex-none sm:w-72">
                    <input type="text" wire:model.live="searchQuery"
                        placeholder="Search features…"
                        class="w-full py-[7px] pl-9 pr-3 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent">
                    <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>

                    {{-- Search dropdown --}}
                    @if ($searchQuery && count($searchResults) > 0)
                        <div class="absolute z-20 top-full mt-1 left-0 right-0 bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden">
                            @foreach ($searchResults as $route => $label)
                                <div wire:click="selectResult('{{ $route }}')"
                                    class="px-4 py-2.5 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0 text-sm font-medium text-gray-700">
                                    {{ $label }}
                                </div>
                            @endforeach
                        </div>
                    @elseif($searchQuery && empty($searchResults))
                        <div class="absolute z-20 top-full mt-1 left-0 right-0 bg-white rounded-xl shadow border border-gray-200 px-4 py-3 text-center text-sm text-gray-500">
                            No results for "{{ $searchQuery }}"
                        </div>
                    @endif
                </div>
                @if (auth()->user()?->canAccessAdminRoute('admin.analytics'))
                    <a href="{{ route('admin.analytics', $org) }}"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-white bg-violet-600 rounded-lg hover:bg-violet-700 transition-colors whitespace-nowrap">
                        <x-icon name="chart-bar" class="w-4 h-4" />
                        <span class="hidden sm:inline">Analytics</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 space-y-5">

        {{-- ═══════════════ KPIs — figure, its change, sparkline ═══════════════ --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
            @include('livewire.super-admin.partials.dash-kpi', [
                'label' => 'Total Students', 'value' => number_format($totalStudents), 'icon' => 'users', 'color' => 'violet',
                'series' => $monthly['students'] ?? [], 'trend' => $trendOf($monthly['students'] ?? []), 'flat' => true, 'compare' => 'vs last month',
                'sub' => number_format($activeStudents) . ' active · ' . number_format($totalClasses) . ' classes',
                'href' => route('admin.student', $org),
            ])
            @include('livewire.super-admin.partials.dash-kpi', [
                'label' => 'Total Teachers', 'value' => number_format($totalTeachers), 'icon' => 'academic-cap', 'color' => 'emerald',
                'series' => $monthly['teachers'] ?? [], 'trend' => $trendOf($monthly['teachers'] ?? []), 'flat' => true, 'compare' => 'vs last month',
                'sub' => number_format($activeTeachers) . ' active · ' . number_format($totalSubjects) . ' subjects',
                'href' => route('admin.teacher', $org),
            ])
            @include('livewire.super-admin.partials.dash-kpi', [
                'label' => 'Fee Collected', 'value' => $short($overallFeeCollected), 'icon' => 'banknotes', 'color' => 'amber',
                'series' => $monthly['fee'] ?? [], 'trend' => $trendOf($monthly['fee'] ?? []), 'flat' => true, 'compare' => 'vs last month',
                'sub' => $collectionPct . '% of ' . $short($totalFee) . ' · ' . $inr($feeCollectedToday) . ' today',
                'href' => route('admin.fee', $org),
            ])
            @include('livewire.super-admin.partials.dash-kpi', [
                'label' => 'Attendance Today', 'value' => $todayMarked ? $pick['student_pct'] . '%' : '—', 'icon' => 'clipboard-document-check', 'color' => 'sky',
                'series' => array_column($markedDays, 'student_pct'), 'trend' => $attTrend, 'flat' => false, 'compare' => $attAgainst,
                'sub' => $todayMarked
                    ? number_format($pick['student_present']) . ' of ' . number_format($pick['student_total']) . ' students present · teachers ' . ($teacherMarked ? $pick['teacher_pct'] . '%' : 'not marked')
                    : 'Not marked yet today',
                'href' => route('admin.attendance', $org),
            ])
        </div>

        {{-- ═══════════════ Fee collection (area) + students by class (donut) ═══════════════ --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
            <div class="sa-card p-5 xl:col-span-2 min-w-0">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
                    <div>
                        <h3 class="sa-card-title">Fee Collection</h3>
                        <p class="sa-card-sub">Academic and transport fee collected day by day · {{ strtolower($this->feeRangeLabel()) }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="sa-chip"><span class="sa-dot" style="background:#7c3aed"></span>{{ $short($feeRangeTotal) }} in 30 days</span>
                        <span class="sa-chip" style="background:#fef2f2;color:#b91c1c">{{ $short($feeRemaining) }} pending</span>
                    </div>
                </div>
                <div class="h-64 relative" wire:ignore wire:key="chart-adminHomeFeeChart">
                    <canvas id="adminHomeFeeChart"></canvas>
                </div>
                <dl class="mt-5 pt-4 border-t border-gray-100 grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                    @foreach ([
                        ['Collected', $inr($overallFeeCollected), 'text-gray-900'],
                        ['Pending', $inr($feeRemaining), $feeRemaining > 0 ? 'text-rose-600' : 'text-gray-900'],
                        ['Expected', $inr($totalFee), 'text-gray-900'],
                        ['Collected today', $inr($feeCollectedToday), 'text-gray-900'],
                    ] as [$label, $val, $tone])
                        <div class="min-w-0">
                            <dt class="text-xs text-gray-400">{{ $label }}</dt>
                            <dd class="mt-0.5 font-semibold tabular-nums truncate {{ $tone }}" title="{{ $val }}">{{ $val }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            <div class="sa-card p-5 min-w-0">
                <div class="flex items-start justify-between gap-3 mb-5">
                    <div>
                        <h3 class="sa-card-title">Students by Class</h3>
                        <p class="sa-card-sub">The biggest classes and the rest</p>
                    </div>
                    <a href="{{ route('admin.student', $org) }}" class="text-xs font-medium text-violet-600 hover:text-violet-800 whitespace-nowrap">View all →</a>
                </div>
                @if (count($studentsByClass))
                    <div class="flex justify-center mb-5">
                        @include('livewire.super-admin.partials.dash-donut', [
                            'segments' => $studentsByClass, 'center' => number_format($totalStudents), 'centerLabel' => 'Students',
                            'size' => 168, 'legend' => false, 'dark' => false, 'format' => null,
                        ])
                    </div>
                    <ul class="space-y-2.5">
                        @foreach ($studentsByClass as $part)
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

        {{-- ═══════════════ Attendance rate (lines) + today (dark) ═══════════════ --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
            <div class="sa-card p-5 xl:col-span-2 min-w-0">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                    <div>
                        <h3 class="sa-card-title">Attendance Rate</h3>
                        <p class="sa-card-sub">Students and teachers present, day by day · last 30 days</p>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        <span class="flex items-center gap-1.5"><span class="sa-dot" style="background:#7c3aed"></span>Students</span>
                        <span class="flex items-center gap-1.5"><span class="sa-dot" style="background:#111827"></span>Teachers</span>
                    </div>
                </div>
                <div class="h-64 relative" wire:ignore wire:key="chart-adminHomeAttChart">
                    <canvas id="adminHomeAttChart"></canvas>
                </div>
            </div>

            <div class="sa-card-dark p-5 min-w-0 flex flex-col">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-[15px] font-semibold text-white">Today</h3>
                        <p class="text-xs text-white/50 mt-0.5">{{ $pick ? \Carbon\Carbon::parse($pick['date'])->format('D, d M Y') : now()->format('D, d M Y') }} · students</p>
                    </div>
                    <a href="{{ route('admin.attendance', $org) }}" class="text-xs font-medium text-white/60 hover:text-white whitespace-nowrap">Open →</a>
                </div>
                <div class="flex justify-center my-5">
                    @include('livewire.super-admin.partials.dash-donut', [
                        'segments' => [
                            ['label' => 'Present', 'value' => (int) ($pick['student_present'] ?? 0), 'color' => '#a78bfa'],
                            ['label' => 'Half day', 'value' => (int) ($pick['student_half'] ?? 0), 'color' => '#38bdf8'],
                            ['label' => 'Absent', 'value' => (int) ($pick['student_absent'] ?? 0), 'color' => '#fb7185'],
                            ['label' => 'Not marked', 'value' => $notMarked, 'color' => 'rgba(255,255,255,.14)'],
                        ],
                        'center' => $todayMarked ? $pick['student_pct'] . '%' : '—', 'centerLabel' => 'Present', 'size' => 140, 'legend' => false, 'dark' => true, 'format' => null,
                    ])
                </div>
                <dl class="mt-auto space-y-2.5 text-sm">
                    <div class="flex items-center justify-between gap-2">
                        <dt class="flex items-center gap-2 text-white/60"><span class="sa-dot" style="background:#a78bfa"></span>Present</dt>
                        <dd class="font-semibold tabular-nums">{{ number_format($pick['student_present'] ?? 0) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <dt class="flex items-center gap-2 text-white/60"><span class="sa-dot" style="background:#38bdf8"></span>Half day</dt>
                        <dd class="font-semibold tabular-nums">{{ number_format($pick['student_half'] ?? 0) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <dt class="flex items-center gap-2 text-white/60"><span class="sa-dot" style="background:#fb7185"></span>Absent</dt>
                        <dd class="font-semibold tabular-nums">{{ number_format($pick['student_absent'] ?? 0) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2 pt-2.5 border-t border-white/10">
                        <dt class="text-white/60">Teachers present</dt>
                        <dd class="font-semibold tabular-nums">{{ number_format($pick['teacher_present'] ?? 0) }} / {{ number_format($totalTeachers) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <dt class="text-white/60">Teachers' rate</dt>
                        <dd class="font-semibold tabular-nums">{{ $teacherMarked ? $pick['teacher_pct'] . '%' : 'Not marked' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- ═══════════════ Quick access ═══════════════ --}}
        <div class="grid grid-cols-3 sm:grid-cols-6 gap-3 sm:gap-5">
            @foreach ([
                ['Attendance',   'admin.attendance',   'clipboard-document-check', 'violet'],
                ['Announcement', 'admin.announcement', 'megaphone',                'sky'],
                ['Arrangements', 'admin.arrangement',  'arrows-right-left',        'emerald'],
                ['Fee',          'admin.fee',          'currency-rupee',           'amber'],
                ['Homework',     'admin.homework',     'book-open',                'rose'],
                ['Analytics',    'admin.analytics',    'chart-bar',                'violet'],
            ] as [$label, $route, $icon, $tone])
                @php [$tint, $ink] = $tones[$tone]; @endphp
                <a href="{{ route($route, $org) }}" class="sa-card px-3 py-4 flex flex-col items-center text-center gap-2.5 min-w-0">
                    <span class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: {{ $tint }}; color: {{ $ink }}">
                        <x-icon name="{{ $icon }}" class="w-5 h-5" />
                    </span>
                    <span class="text-xs font-medium text-gray-700 truncate max-w-full">{{ $label }}</span>
                </a>
            @endforeach
        </div>

        {{-- ═══════════════ The week (table) + upcoming events ═══════════════ --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
            <div class="sa-card xl:col-span-2 min-w-0 overflow-hidden">
                <div class="px-5 pt-5 pb-3 flex items-start justify-between gap-3">
                    <div>
                        <h3 class="sa-card-title">Attendance · Last 7 Days</h3>
                        <p class="sa-card-sub">Today first, then each day before it</p>
                    </div>
                    <a href="{{ route('admin.attendance', $org) }}" class="text-xs font-medium text-violet-600 hover:text-violet-800 whitespace-nowrap">Open →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[520px]">
                        <thead class="text-gray-400 text-[11px] uppercase tracking-wide">
                            <tr class="border-t border-gray-100 bg-gray-50/60">
                                <th class="px-5 pt-2.5 pb-1 text-left font-medium" rowspan="2">Day</th>
                                <th class="px-4 pt-2.5 pb-1 text-center font-medium" colspan="2">Students</th>
                                {{-- A rule down the middle, between the students' columns and the teachers'. --}}
                                <th class="px-4 pt-2.5 pb-1 text-center font-medium border-l border-gray-100" colspan="2">Teachers</th>
                            </tr>
                            <tr class="border-b border-gray-100 bg-gray-50/60 text-[10px]">
                                <th class="px-4 pb-2 text-center font-normal">Present</th>
                                <th class="px-4 pb-2 text-center font-normal">Absent</th>
                                <th class="px-4 pb-2 text-center font-normal border-l border-gray-100">Present</th>
                                <th class="px-4 pb-2 text-center font-normal">Absent</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach (array_reverse($last7DaysData) as $data)
                                <tr class="hover:bg-gray-50/70">
                                    <td class="px-5 py-2.5 font-medium text-gray-700">{{ $data['day'] }}</td>
                                    <td class="px-4 py-2.5 text-center text-gray-700 tabular-nums">{{ $data['student_present'] }}</td>
                                    <td class="px-4 py-2.5 text-center tabular-nums {{ $data['student_absent'] > 0 ? 'text-rose-600' : 'text-gray-700' }}">{{ $data['student_absent'] }}</td>
                                    <td class="px-4 py-2.5 text-center text-gray-700 tabular-nums border-l border-gray-100">{{ $data['teacher_present'] }}</td>
                                    <td class="px-4 py-2.5 text-center tabular-nums {{ $data['teacher_absent'] > 0 ? 'text-rose-600' : 'text-gray-700' }}">{{ $data['teacher_absent'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="sa-card p-5 min-w-0">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <h3 class="sa-card-title">Upcoming Events</h3>
                        <p class="sa-card-sub">The next on the school calendar</p>
                    </div>
                    <a href="{{ route('admin.calender', $org) }}" class="text-xs font-medium text-violet-600 hover:text-violet-800 whitespace-nowrap">Calendar →</a>
                </div>
                <div class="space-y-4">
                    @forelse ($upcomingEvents as $event)
                        @php [$tint, $ink] = $tones[$eventTone[$event['color']] ?? 'gray']; @endphp
                        <div class="flex items-start gap-3">
                            <span class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background: {{ $tint }}; color: {{ $ink }}">
                                <x-icon name="calendar-days" class="w-4 h-4" />
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-baseline justify-between gap-2">
                                    <p class="text-sm font-medium text-gray-800 leading-snug line-clamp-2 break-words" title="{{ $event['title'] }}">{{ $event['title'] }}</p>
                                    <span class="text-[11px] text-gray-400 whitespace-nowrap flex-shrink-0">{{ $event['formatted_date'] }}</span>
                                </div>
                                @if ($event['description'])
                                    <p class="text-xs text-gray-400 mt-0.5 leading-snug line-clamp-2 break-words">{{ $event['description'] }}</p>
                                @endif
                                <p class="text-xs text-gray-400 mt-0.5">
                                    @if ($event['is_all_day'])
                                        All day
                                    @elseif ($event['start_time'])
                                        {{ $event['start_time'] }}@if ($event['end_time']) – {{ $event['end_time'] }}@endif
                                    @endif
                                    @if ($event['location'])
                                        · {{ \Illuminate\Support\Str::limit($event['location'], 24) }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="py-10 text-center text-sm text-gray-400">No upcoming events</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ═══════════════ Exams · queries · rating · links ═══════════════ --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
            <div class="xl:col-span-2 space-y-5 min-w-0">
                {{-- One point per exam: every student's overall marks in it as a
                     percentage, averaged over all of them, and the share of those
                     students who passed on their overall marks. --}}
                <div class="sa-card p-5 min-w-0">
                    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                        <div>
                            <h3 class="sa-card-title">Exam Performance</h3>
                            <p class="sa-card-sub">Students' overall average, exam by exam @if (count($examTrend) > 0)· last {{ count($examTrend) }} exam{{ count($examTrend) === 1 ? '' : 's' }}@endif</p>
                        </div>
                        <a href="{{ route('admin.performance', $org) }}" class="text-xs font-medium text-violet-600 hover:text-violet-800 whitespace-nowrap">Open →</a>
                    </div>

                    @if (count($examTrend) > 0)
                        <div class="flex flex-wrap items-end justify-between gap-3 mb-4">
                            <div class="flex flex-wrap items-end gap-3">
                                <div>
                                    <p class="text-xs text-gray-400">Latest · {{ $examLatest['exam'] }}</p>
                                    <p class="text-[26px] leading-none font-semibold tracking-tight text-gray-900 tabular-nums mt-1.5">{{ $examLatest['avg'] }}%</p>
                                </div>
                                @if ($examTrendDelta != 0)
                                    <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-xs font-semibold tabular-nums {{ $examTrendDelta > 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $examTrendDelta > 0 ? 'M12 19V5m-6 6l6-6 6 6' : 'M12 5v14m6-6l-6 6-6-6' }}" />
                                        </svg>
                                        {{ abs($examTrendDelta) }} pts
                                    </span>
                                    <span class="text-xs text-gray-400 pb-0.5">vs previous</span>
                                @else
                                    <span class="text-xs text-gray-400 pb-0.5">No change vs previous</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-3 text-xs text-gray-500">
                                <span class="flex items-center gap-1.5"><span class="sa-dot" style="background:#7c3aed"></span>Average %</span>
                                <span class="flex items-center gap-1.5"><span class="sa-dot" style="background:#111827"></span>Pass %</span>
                            </div>
                        </div>

                        <div class="h-56 relative" wire:ignore wire:key="chart-adminHomeExamChart">
                            <canvas id="adminHomeExamChart"></canvas>
                        </div>

                        <dl class="mt-5 pt-4 border-t border-gray-100 grid grid-cols-3 gap-4 text-sm">
                            @foreach ([
                                ['Overall average', $examTrendAvg . '%', 'text-gray-900'],
                                ['Latest pass rate', $examLatest['pass_pct'] . '%', 'text-emerald-600'],
                                ['Students', number_format($examLatest['students']), 'text-gray-900'],
                            ] as [$label, $val, $tone])
                                <div class="min-w-0">
                                    <dt class="text-xs text-gray-400">{{ $label }}</dt>
                                    <dd class="mt-0.5 font-semibold tabular-nums truncate {{ $tone }}">{{ $val }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @else
                        <div class="py-10 text-center">
                            <p class="text-sm text-gray-500">No exam marks recorded yet.</p>
                            <p class="text-xs text-gray-400 mt-1">The trend appears once marks are uploaded against an exam.</p>
                        </div>
                    @endif
                </div>

                {{-- Recent searches --}}
                @if (count($recentSearches) > 0)
                    <div class="sa-card min-w-0 overflow-hidden">
                        <div class="px-5 pt-5 pb-3 flex items-start justify-between gap-3">
                            <h3 class="sa-card-title">Recent Searches</h3>
                            <button wire:click="clearRecentSearches" class="text-xs font-medium text-rose-500 hover:text-rose-700">Clear all</button>
                        </div>
                        <div class="divide-y divide-gray-100 border-t border-gray-100">
                            @foreach ($recentSearches as $search)
                                <div wire:click="$set('searchQuery', '{{ $search['term'] }}')"
                                    class="px-5 py-2.5 hover:bg-gray-50 cursor-pointer flex justify-between items-center">
                                    <div>
                                        <div class="text-sm text-gray-700">{{ $search['term'] }}</div>
                                        <div class="text-xs text-gray-400">{{ $search['time']->diffForHumans() }}</div>
                                    </div>
                                    <svg class="h-4 w-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="space-y-5 min-w-0">
                {{-- Queries --}}
                <div class="sa-card p-5 min-w-0">
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div>
                            <h3 class="sa-card-title">Queries</h3>
                            <p class="sa-card-sub">From the apps and the school website</p>
                        </div>
                        <a href="{{ route('admin.enqueries', $org) }}" class="text-xs font-medium text-violet-600 hover:text-violet-800 whitespace-nowrap">Open →</a>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        @foreach ([['Students', $studentQueries], ['Teachers', $teacherQueries], ['Website', $websiteQueries]] as [$label, $val])
                            <div class="rounded-xl bg-gray-50 px-3 py-3 min-w-0">
                                <p class="text-xs text-gray-400 truncate">{{ $label }}</p>
                                <p class="text-base font-semibold text-gray-900 tabular-nums mt-0.5">{{ number_format((int) $val) }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- LMS rating --}}
                @php $avgStars = (int) round((float) $averageRating); @endphp
                <div class="sa-card p-5 min-w-0">
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div class="min-w-0">
                            <h3 class="sa-card-title">LMS Rating</h3>
                            <p class="sa-card-sub">{{ number_format((int) $totalRatings) }} rating{{ (int) $totalRatings === 1 ? '' : 's' }} from your school</p>
                        </div>
                        <a href="{{ route('admin.rate-lms', $org) }}" class="text-xs font-medium text-violet-600 hover:text-violet-800 whitespace-nowrap">{{ $latestRating ? 'View →' : 'Rate →' }}</a>
                    </div>
                    <div class="flex items-center gap-3">
                        <p class="text-[28px] leading-none font-semibold text-gray-900 tabular-nums">{{ number_format((float) $averageRating, 1) }}</p>
                        <div>
                            <div class="flex">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg class="w-4 h-4 {{ $i <= $avgStars ? 'text-amber-400' : 'text-gray-200' }}" fill="currentColor" viewBox="0 0 20 20"><path d="{{ $starPath }}" /></svg>
                                @endfor
                            </div>
                            <p class="text-[11px] text-gray-400 mt-0.5">average, out of 5</p>
                        </div>
                    </div>
                    @if ($latestRating)
                        <div class="mt-4 rounded-xl bg-gray-50 p-3.5 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <svg class="w-3 h-3 {{ $i <= $latestRating->rating ? 'text-amber-400' : 'text-gray-200' }}" fill="currentColor" viewBox="0 0 20 20"><path d="{{ $starPath }}" /></svg>
                                    @endfor
                                </div>
                                <span class="text-[11px] text-gray-400 whitespace-nowrap">{{ \Carbon\Carbon::parse($latestRating->created_at)->format('d M Y') }}</span>
                            </div>
                            @if ($latestRating->feedback)
                                <p class="text-xs text-gray-500 mt-1.5 leading-snug line-clamp-3 break-words">{{ $latestRating->feedback }}</p>
                            @endif
                        </div>
                    @else
                        <p class="mt-4 text-sm text-gray-400">No ratings yet</p>
                    @endif
                </div>

                {{-- Quick links --}}
                <div class="sa-card min-w-0 overflow-hidden">
                    <div class="px-5 pt-5 pb-3">
                        <h3 class="sa-card-title">Quick Links</h3>
                    </div>
                    <div class="divide-y divide-gray-100 border-t border-gray-100">
                        @foreach ([
                            ['View All Students', 'admin.student'],
                            ['View All Teachers', 'admin.teacher'],
                            ['Manage Classes', 'admin.standard'],
                            ['View Analytics', 'admin.analytics'],
                        ] as [$label, $route])
                            <a href="{{ route($route, $org) }}" class="flex items-center justify-between px-5 py-2.5 hover:bg-gray-50 transition-colors">
                                <span class="text-sm text-gray-700">{{ $label }}</span>
                                <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- The fee, attendance and exam charts: their canvases are wire:ignore'd
         and they are updated in place from the figures above after every
         re-render (a search keystroke re-renders the page), so none goes blank. --}}
    @include('livewire.super-admin.partials.dash-charts-js')
    <script>
        (function () {
            const VIOLET = '#7c3aed', INK = '#111827';
            const short = v => v >= 1e7 ? (v / 1e7).toFixed(1) + 'Cr' : v >= 1e5 ? (v / 1e5).toFixed(1) + 'L' : v >= 1e3 ? (v / 1e3).toFixed(0) + 'k' : v;
            const num = v => Number(v || 0).toLocaleString('en-IN');
            const axis = { color: '#9ca3af', font: { size: 11 } };
            const tooltip = { backgroundColor: INK, padding: 10, cornerRadius: 8, boxPadding: 4 };
            const fade = (color, top) => c => {
                const a = c.chart.chartArea;
                if (!a) return 'transparent';
                const g = c.chart.ctx.createLinearGradient(0, a.top, 0, a.bottom);
                g.addColorStop(0, color + top);
                g.addColorStop(1, color + '00');
                return g;
            };
            const xAxis = { grid: { display: false }, border: { display: false }, ticks: Object.assign({ maxRotation: 0, autoSkip: true, autoSkipPadding: 8 }, axis) };
            const pctAxis = { beginAtZero: true, max: 100, border: { display: false }, grid: { color: 'rgba(17,24,39,0.05)' },
                ticks: Object.assign({ maxTicksLimit: 6, stepSize: 20, callback: v => v + '%' }, axis) };

            saCharts.define('adminHome', {
                adminHomeFeeChart: p => ({
                    type: 'line',
                    data: {
                        labels: p.fee.labels || [],
                        datasets: [{
                            label: 'Collected', data: p.fee.data || [],
                            borderColor: VIOLET, borderWidth: 2.5, tension: 0.42, fill: true, backgroundColor: fade(VIOLET, '47'),
                            pointRadius: c => c.dataIndex === p.fee.peak ? 5 : 0,
                            pointHoverRadius: 5, pointBackgroundColor: '#fff', pointBorderColor: VIOLET, pointBorderWidth: 2.5,
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: false },
                            tooltip: Object.assign({}, tooltip, { displayColors: false, callbacks: { label: c => '₹' + num(c.parsed.y) } })
                        },
                        scales: {
                            x: xAxis,
                            y: { beginAtZero: true, border: { display: false }, grid: { color: 'rgba(17,24,39,0.05)' },
                                 ticks: Object.assign({ maxTicksLimit: 5, callback: v => '₹' + short(v) }, axis) }
                        }
                    }
                }),
                adminHomeAttChart: p => ({
                    type: 'line',
                    data: {
                        labels: p.att.labels || [],
                        datasets: [
                            { label: 'Students', data: p.att.students || [], present: p.att.sp || [], absent: p.att.sa || [],
                              borderColor: VIOLET, borderWidth: 2.5, tension: 0.42, fill: true, backgroundColor: fade(VIOLET, '40'), spanGaps: true,
                              pointRadius: 0, pointHoverRadius: 5, pointBackgroundColor: '#fff', pointBorderColor: VIOLET, pointBorderWidth: 2.5 },
                            { label: 'Teachers', data: p.att.teachers || [], present: p.att.tp || [], absent: p.att.ta || [],
                              borderColor: INK, borderWidth: 2, tension: 0.42, fill: false, spanGaps: true,
                              pointRadius: 0, pointHoverRadius: 5, pointBackgroundColor: '#fff', pointBorderColor: INK, pointBorderWidth: 2 }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: false },
                            tooltip: Object.assign({}, tooltip, { callbacks: {
                                label: c => c.raw === null ? c.dataset.label + ': not marked'
                                    : c.dataset.label + ': ' + c.raw + '% · ' + num(c.dataset.present[c.dataIndex]) + ' present, ' + num(c.dataset.absent[c.dataIndex]) + ' absent'
                            } })
                        },
                        scales: { x: xAxis, y: pctAxis }
                    }
                }),
                adminHomeExamChart: p => (p.exam.labels || []).length ? {
                    type: 'line',
                    data: {
                        labels: p.exam.labels,
                        datasets: [
                            { label: 'Average %', data: p.exam.avg || [], borderColor: VIOLET, borderWidth: 2.5, tension: 0.42, fill: true, backgroundColor: fade(VIOLET, '40'),
                              pointRadius: 3, pointHoverRadius: 5, pointBackgroundColor: '#fff', pointBorderColor: VIOLET, pointBorderWidth: 2 },
                            { label: 'Pass %', data: p.exam.pass || [], borderColor: INK, borderWidth: 2, borderDash: [4, 3], tension: 0.42, fill: false,
                              pointRadius: 2, pointHoverRadius: 4, pointBackgroundColor: INK }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: false },
                            tooltip: Object.assign({}, tooltip, { callbacks: {
                                title: items => (p.exam.meta[items[0].dataIndex] || {}).exam || '',
                                label: c => c.dataset.label + ': ' + c.raw + '%',
                                afterBody: items => {
                                    const m = p.exam.meta[items[0].dataIndex] || {};
                                    return m.date + ' · ' + m.passed + ' of ' + m.students + ' students passed · ' + m.papers + ' papers';
                                }
                            } })
                        },
                        scales: { x: xAxis, y: pctAxis }
                    }
                } : null,
            });
        })();
    </script>
</div>
