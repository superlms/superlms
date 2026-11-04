<div class="min-h-screen bg-gray-50">
    {{-- The Super Admin's dashboard look (2026-11-04): KPI cards lead Key
         Metrics, every section's tiles and cards are the soft cards, line
         charts are smooth with a fading fill, bars rounded, all in violet /
         ink, and the fee's position is the one dark card. The figures and
         filters are as before. --}}
    @include('livewire.super-admin.partials.dash-styles')

    @php
        $inr     = fn ($v) => '₹' . number_format((float) $v, 0);
        $short   = fn ($v) => \App\Support\DashboardChart::inrShort((float) $v);
        $trendOf = fn (array $s) => \App\Support\DashboardChart::trend((float) (end($s) ?: 0), (float) ($s[count($s) - 2] ?? 0));

        // Today's attendance % against the last day marked before it, in points:
        // [trend, "vs …"] — no trend until today is marked.
        $daily  = $attendanceDailyTrend;
        $vsLast = function (array $pcts) use ($daily): array {
            $n = count($pcts);
            if ($n === 0 || $pcts[$n - 1] === null) {
                return [null, ''];
            }
            for ($i = $n - 2; $i >= 0; $i--) {
                if ($pcts[$i] !== null) {
                    $diff = round($pcts[$n - 1] - $pcts[$i], 1);

                    return [['pct' => abs($diff), 'up' => $diff >= 0], $i === $n - 2 ? 'vs yesterday' : 'vs ' . ($daily['labels'][$i] ?? '')];
                }
            }

            return [null, ''];
        };
        $stuPcts = $daily['student'] ?? [];
        $tchPcts = $daily['teacher'] ?? [];
        [$stuTrend, $stuAgainst] = $vsLast($stuPcts);
        [$tchTrend, $tchAgainst] = $vsLast($tchPcts);
        $stuMarked = !empty($kpis['student_marked']);
        $tchMarked = $tchPcts && end($tchPcts) !== null;

        $splitMarked = ($studentPieData['present'] ?? 0) + ($studentPieData['absent'] ?? 0);

        $avatar = fn ($name) => mb_strtoupper(mb_substr(trim((string) $name) ?: '?', 0, 1));
        $barTone = fn (float $pct, float $good, float $fair) => $pct >= $good ? '#7c3aed' : ($pct >= $fair ? '#f59e0b' : '#f43f5e');

        // Every chart's figures (see partials.dash-charts-js).
        $chartPayload = [
            'att' => [
                'labels'  => $daily['labels'] ?? [],
                'student' => $stuPcts, 'teacher' => $tchPcts,
                'sp' => $daily['studentPresent'] ?? [], 'sa' => $daily['studentAbsent'] ?? [],
                'tp' => $daily['teacherPresent'] ?? [], 'ta' => $daily['teacherAbsent'] ?? [],
            ],
            'volume'         => ['labels' => $monthlyAttendancePct['labels'] ?? [], 'student' => $monthlyAttendancePct['student'] ?? [], 'teacher' => $monthlyAttendancePct['teacher'] ?? []],
            'admissions'     => ['labels' => $admissionsTrend['labels'] ?? [], 'data' => $admissionsTrend['data'] ?? []],
            'feeMonthly'     => ['labels' => $monthly['labels'] ?? [], 'data' => $monthly['fee'] ?? []],
            'feeClass'       => ['labels' => $feeClassData['labels'] ?? [], 'collected' => $feeClassData['collected'] ?? [], 'remaining' => $feeClassData['remaining'] ?? []],
            'transportClass' => ['labels' => $transportClassData['labels'] ?? [], 'collected' => $transportClassData['collected'] ?? [], 'remaining' => $transportClassData['remaining'] ?? []],
            'transportRoute' => ['labels' => $transportRouteData['labels'] ?? [], 'collected' => $transportRouteData['collected'] ?? [], 'remaining' => $transportRouteData['remaining'] ?? []],
        ];
    @endphp

    {{-- All chart data lives in this attribute. It is part of the Livewire DOM,
         so every re-render (a filter, the admission year) refreshes it and the
         charts below are updated in place from it. --}}
    <div class="hidden" data-sa-charts="adminAnalytics" data-payload="{{ json_encode($chartPayload) }}"></div>

    {{-- ══════════════════════════════════════════════════════════
         HEADER — title and date (the figures lead Key Metrics).
    ══════════════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:min-h-15">
            <div class="min-w-0 sm:flex sm:items-baseline sm:gap-2">
                <h1 class="min-w-0 text-lg sm:text-xl font-bold text-gray-900 truncate">Analytics</h1>
                <p class="text-xs text-gray-400 mt-0.5 sm:mt-0 truncate">{{ now()->format('l, d M Y') }}</p>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 space-y-8">

        {{-- ═══════════════════════ KEY METRICS ═══════════════════════ --}}
        <section class="space-y-4">
            <x-admin.section-heading title="Key Metrics" />

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
                @include('livewire.super-admin.partials.dash-kpi', [
                    'label' => 'Student Attendance', 'value' => $stuMarked ? ($kpis['student_rate'] ?? 0) . '%' : '—', 'icon' => 'clipboard-document-check', 'color' => 'violet',
                    'series' => array_values(array_filter($stuPcts, fn ($v) => $v !== null)), 'trend' => $stuTrend, 'flat' => false, 'compare' => $stuAgainst,
                    'sub' => $stuMarked
                        ? number_format($studentPieData['present'] ?? 0) . ' present · ' . number_format($studentPieData['absent'] ?? 0) . ' absent today'
                        : 'Not marked yet today',
                    'href' => null,
                ])
                @include('livewire.super-admin.partials.dash-kpi', [
                    'label' => 'Teacher Attendance', 'value' => $tchMarked ? ($kpis['teacher_rate'] ?? 0) . '%' : '—', 'icon' => 'academic-cap', 'color' => 'sky',
                    'series' => array_values(array_filter($tchPcts, fn ($v) => $v !== null)), 'trend' => $tchTrend, 'flat' => false, 'compare' => $tchAgainst,
                    'sub' => $tchMarked ? 'Today' : 'Not marked yet today',
                    'href' => null,
                ])
                @include('livewire.super-admin.partials.dash-kpi', [
                    'label' => 'Fee Collection', 'value' => ($kpis['collect_rate'] ?? 0) . '%', 'icon' => 'banknotes', 'color' => 'amber',
                    'series' => $monthly['fee'] ?? [], 'trend' => $trendOf($monthly['fee'] ?? []), 'flat' => true, 'compare' => 'vs last month',
                    'sub' => $short($feeStats['collected'] ?? 0) . ' of ' . $short($feeStats['totalFee'] ?? 0) . ' total fee collected',
                    'href' => null,
                ])
                @include('livewire.super-admin.partials.dash-kpi', [
                    'label' => 'Total Students', 'value' => number_format($statsData['totalStudents'] ?? 0), 'icon' => 'users', 'color' => 'emerald',
                    'series' => $monthly['students'] ?? [], 'trend' => $trendOf($monthly['students'] ?? []), 'flat' => true, 'compare' => 'vs last month',
                    'sub' => number_format($kpis['new_admissions'] ?? 0) . ' new in the last 30 days',
                    'href' => null,
                ])
            </div>

            {{-- What the whole fee (academic + transport + Last Year Dues) is made of,
                 who has paid nothing yet, and the new admissions. --}}
            @include('livewire.super-admin.partials.analytics-tiles', ['cols' => 'grid-cols-2 lg:grid-cols-5', 'big' => false, 'items' => [
                ['Academic Fee', $inr($kpis['academic_fee'] ?? 0), 'total', 'text-gray-900'],
                ['Transport Fee', $inr($kpis['transport_fee'] ?? 0), 'total', 'text-gray-900'],
                ['Last Year Dues', $inr($kpis['last_year_dues'] ?? 0), 'total', 'text-gray-900'],
                ['Unpaid Students', number_format($kpis['unpaid_students'] ?? 0), 'no payment yet', 'text-rose-600'],
                ['New Admissions', number_format($kpis['new_admissions'] ?? 0), 'last 30 days', 'text-violet-600'],
            ]])
        </section>

        {{-- ═══════════════════════ ATTENDANCE ═══════════════════════ --}}
        <section class="space-y-4">
            <x-admin.section-heading title="Attendance" />

            {{-- Rate trend + student split --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="sa-card p-5 lg:col-span-2 min-w-0">
                    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                        <div>
                            <h3 class="sa-card-title">Attendance Rate Trend</h3>
                            <p class="sa-card-sub">Daily % · last 30 days</p>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-gray-500">
                            <span class="flex items-center gap-1.5"><span class="sa-dot" style="background:#7c3aed"></span>Students</span>
                            <span class="flex items-center gap-1.5"><span class="sa-dot" style="background:#111827"></span>Teachers</span>
                        </div>
                    </div>
                    <div class="h-64 relative" wire:ignore wire:key="chart-adminAttTrendChart">
                        <canvas id="adminAttTrendChart"></canvas>
                    </div>
                </div>

                {{-- Student split: today's students, present against absent --}}
                <div class="sa-card p-5 min-w-0">
                    <div class="mb-5">
                        <h3 class="sa-card-title">Student Split</h3>
                        <p class="sa-card-sub">Today · {{ now()->format('d M Y') }}</p>
                    </div>
                    @if ($splitMarked > 0)
                        @include('livewire.super-admin.partials.dash-donut', [
                            'segments' => [
                                ['label' => 'Present', 'value' => (int) ($studentPieData['present'] ?? 0), 'color' => '#7c3aed'],
                                ['label' => 'Absent', 'value' => (int) ($studentPieData['absent'] ?? 0), 'color' => '#f43f5e'],
                            ],
                            'center' => ($studentPieData['presentPct'] ?? 0) . '%', 'centerLabel' => 'Present', 'size' => 140,
                            'legend' => true, 'dark' => false, 'format' => fn ($v) => number_format((int) $v),
                        ])
                    @else
                        <div class="h-40 flex items-center justify-center text-center text-sm text-gray-400">Student attendance is not marked yet today.</div>
                    @endif
                </div>
            </div>

            {{-- Volume: each month's average attendance %, from 1 April of the session
                 to this month — the students', and the teachers'. A month's figure
                 is the average of its days' percentages. --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                @foreach ([
                    ['Student Attendance Volume', 'adminStuVolumeChart', '#7c3aed'],
                    ['Teacher Attendance Volume', 'adminTchVolumeChart', '#111827'],
                ] as [$title, $canvas, $dot])
                    <div class="sa-card p-5 min-w-0">
                        <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                            <div>
                                <h3 class="sa-card-title">{{ $title }}</h3>
                                <p class="sa-card-sub">Average of the days' % per month · from {{ $monthlyAttendancePct['from'] ?? '' }}</p>
                            </div>
                            <span class="flex items-center gap-1.5 text-xs text-gray-500"><span class="sa-dot" style="background: {{ $dot }}"></span>Attendance %</span>
                        </div>
                        <div class="h-56 relative" wire:ignore wire:key="chart-{{ $canvas }}">
                            <canvas id="{{ $canvas }}"></canvas>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Class-wise ranking --}}
            <div class="sa-card p-5 min-w-0">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                    <div>
                        <h3 class="sa-card-title">Class-wise Attendance Ranking</h3>
                        <p class="sa-card-sub">Average of each day's attendance % · overall</p>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        <span class="flex items-center gap-1.5"><span class="sa-dot" style="background:#7c3aed"></span>85%+</span>
                        <span class="flex items-center gap-1.5"><span class="sa-dot" style="background:#f59e0b"></span>70–85%</span>
                        <span class="flex items-center gap-1.5"><span class="sa-dot" style="background:#f43f5e"></span>Below 70%</span>
                    </div>
                </div>
                @if (count($classAttendanceRank))
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                        @foreach ($classAttendanceRank as $idx => $row)
                            <div class="min-w-0">
                                <div class="flex items-center justify-between gap-3 text-sm">
                                    <p class="font-medium text-gray-800 truncate" title="{{ $row['name'] }}"><span class="text-gray-400 tabular-nums mr-1.5">{{ $idx + 1 }}</span>{{ $row['name'] }}</p>
                                    <p class="text-gray-700 tabular-nums whitespace-nowrap font-semibold">{{ $row['pct'] }}%</p>
                                </div>
                                <div class="mt-1.5 h-1.5 rounded-full bg-gray-100">
                                    <div class="h-1.5 rounded-full" style="width: {{ min(100, $row['pct']) }}%; background: {{ $barTone((float) $row['pct'], 85, 70) }}"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-10 text-gray-400 text-sm">No attendance recorded yet.</div>
                @endif
            </div>
        </section>

        {{-- ═══════════════════════ PERFORMANCE ═══════════════════════ --}}
        <section class="space-y-4">
            <x-admin.section-heading title="Student Performance" />

            <div class="flex flex-wrap items-center justify-end gap-2">
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wide mr-1">Filter</span>
                <select wire:model.live="performerClass"
                    class="text-xs bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-violet-400">
                    <option value="">All Classes</option>
                    @foreach ($standards as $std)
                        <option value="{{ $std->id }}">{{ $std->name }}</option>
                    @endforeach
                </select>
                @if ($sections)
                    <select data-sole-section wire:model.live="performerSection"
                        class="text-xs bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-violet-400">
                        <option value="">All Sections</option>
                        @foreach ($sections as $sec)
                            <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            {{-- Ranked on exam marks (every paper added up, as a %): the first ten and
                 the last ten of the school, or of the class and section picked. --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                @foreach ([
                    ['Top 10 Rankers', $topRankers, 'text-emerald-600', 'bg-violet-50 text-violet-700'],
                    ['Bottom 10 Rankers', $bottomRankers, 'text-rose-600', 'bg-rose-50 text-rose-600'],
                ] as [$heading, $list, $tone, $badge])
                    <div class="sa-card min-w-0 overflow-hidden">
                        <div class="px-5 pt-5 pb-3">
                            <h3 class="sa-card-title">{{ $heading }}</h3>
                            <p class="sa-card-sub">Overall exam marks · every paper added up</p>
                        </div>
                        @if (count($list))
                            <div class="divide-y divide-gray-100 border-t border-gray-100">
                                @foreach ($list as $student)
                                    <div class="flex items-center gap-3 px-5 py-3">
                                        <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-semibold tabular-nums flex-shrink-0 {{ $badge }}">{{ $student['rank'] }}</span>
                                        <div class="w-9 h-9 rounded-xl overflow-hidden flex-shrink-0">
                                            @if ($student['photo'])
                                                <img src="{{ $student['photo'] }}" alt="{{ $student['name'] }}" class="w-full h-full object-cover">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center bg-violet-50 text-violet-600 text-xs font-semibold">{{ $avatar($student['name']) }}</div>
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-800 truncate" title="{{ $student['name'] }}">{{ $student['name'] }}</p>
                                            <p class="text-xs text-gray-400 truncate">{{ $student['class'] }} · {{ $student['section'] }}</p>
                                        </div>
                                        <div class="text-right flex-shrink-0">
                                            <p class="text-sm font-semibold {{ $tone }} tabular-nums">{{ $student['score'] }}%</p>
                                            <p class="text-[11px] text-gray-400 tabular-nums">{{ $student['obtained'] + 0 }} / {{ $student['max'] + 0 }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-10 text-gray-400 text-sm border-t border-gray-100">
                                {{ count($topRankers) ? 'Everyone with exam marks is in the top 10.' : 'No exam marks for the selected filters.' }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ═══════════════════════ ADMISSIONS & ENQUIRIES ═══════════════════════ --}}
        <section class="space-y-4">
            <x-admin.section-heading title="Admissions & Enquiries" />

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                {{-- Admissions trend: one bar for each date students were admitted on,
                     its number over it — the last fifteen such dates of the year. --}}
                <div class="sa-card p-5 lg:col-span-2 min-w-0">
                    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                        <div>
                            <h3 class="sa-card-title">Admissions Trend</h3>
                            <p class="sa-card-sub">
                                <span class="font-medium text-gray-600 tabular-nums">{{ number_format($admissionsTotal) }}</span>
                                admission{{ $admissionsTotal === 1 ? '' : 's' }} ·
                                {{ ($admissionsTrend['dates'] ?? 0) > count($admissionsTrend['labels'] ?? [])
                                    ? 'the last ' . count($admissionsTrend['labels'] ?? []) . ' admission dates'
                                    : 'counted on admission date' }}
                            </p>
                        </div>
                        <select wire:model.live="admissionYear"
                            class="text-xs bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-violet-400">
                            @foreach ($admissionYears as $y)
                                <option value="{{ $y }}">Apr {{ $y }} – Mar {{ $y + 1 }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="h-56 relative" wire:ignore wire:key="chart-adminAdmissionsChart">
                        <canvas id="adminAdmissionsChart"></canvas>
                    </div>
                    @if ($admissionsTotal === 0)
                        <p class="mt-3 text-xs text-gray-400">
                            Nothing recorded for this school year — try an earlier one. Students with no admission date are counted on the day their record was created.
                        </p>
                    @endif
                </div>

                {{-- Enquiry funnel + recent. The card keeps its size however many
                     enquiries there are: beside the Admissions card it is exactly as
                     tall as that one (it is laid over its own grid cell, so it cannot
                     stretch the row), on a narrow screen it has a height of its own,
                     and the latest enquiries scroll inside it. --}}
                <div class="relative h-96 lg:h-auto">
                    <div class="sa-card absolute inset-0 p-5 flex flex-col overflow-hidden">
                        <div class="flex items-start justify-between gap-3 mb-4 flex-shrink-0">
                            <div class="min-w-0">
                                <h3 class="sa-card-title">Enquiry Funnel</h3>
                                <p class="sa-card-sub">{{ $enquiryStats['student'] ?? 0 }} student · {{ $enquiryStats['teacher'] ?? 0 }} teacher · {{ $enquiryStats['website'] ?? 0 }} website</p>
                            </div>
                            <a href="{{ route('admin.enqueries', ['organization' => auth()->user()->organization_id]) }}" class="text-xs font-medium text-violet-600 hover:text-violet-800 whitespace-nowrap">Open →</a>
                        </div>
                        <div class="grid grid-cols-3 gap-3 flex-shrink-0">
                            @foreach ([
                                ['Total', $enquiryStats['total'] ?? 0, 'text-gray-900'],
                                ['Responded', $enquiryStats['responded'] ?? 0, 'text-emerald-600'],
                                ['Pending', $enquiryStats['pending'] ?? 0, 'text-amber-600'],
                            ] as [$label, $val, $tone])
                                <div class="rounded-xl bg-gray-50 px-3 py-2.5 min-w-0">
                                    <p class="text-xs text-gray-400 truncate">{{ $label }}</p>
                                    <p class="text-base font-semibold tabular-nums mt-0.5 {{ $tone }}">{{ number_format((int) $val) }}</p>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4 flex-shrink-0">
                            <div class="flex justify-between text-xs mb-1.5"><span class="text-gray-400">Response rate</span><span class="font-semibold text-gray-700 tabular-nums">{{ $enquiryStats['rate'] ?? 0 }}%</span></div>
                            <div class="w-full bg-gray-100 rounded-full h-1.5"><div class="h-1.5 rounded-full bg-violet-600" style="width: {{ min(100, $enquiryStats['rate'] ?? 0) }}%"></div></div>
                        </div>
                        <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide mt-5 mb-1 flex-shrink-0">Latest enquiries</p>
                        <div class="divide-y divide-gray-100 flex-1 min-h-0 overflow-y-auto overflow-x-hidden">
                            @forelse ($adminEnquiries as $enq)
                                <a href="{{ route('admin.enqueries', ['organization' => auth()->user()->organization_id, 'activeTab' => $enq['tab']]) }}"
                                   class="flex items-center gap-2.5 py-2.5 hover:bg-gray-50 rounded-lg">
                                    <span class="w-8 h-8 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center flex-shrink-0 font-semibold text-xs">{{ $avatar($enq['name']) }}</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate">{{ $enq['name'] }}</p>
                                        <p class="text-xs text-gray-400 truncate">{{ $enq['source'] }} · {{ $enq['time'] }}</p>
                                    </div>
                                    <span class="flex-shrink-0 px-2 py-0.5 text-[11px] rounded-full font-medium {{ $enq['status'] === 'Responded' ? 'bg-emerald-50 text-emerald-700' : ($enq['status'] === 'Pending' ? 'bg-rose-50 text-rose-600' : 'bg-gray-100 text-gray-600') }}">{{ $enq['status'] }}</span>
                                </a>
                            @empty
                                <p class="text-center text-gray-400 text-sm py-4">No enquiries yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ═══════════════════════ FEE ═══════════════════════ --}}
        <section class="space-y-4">
            <x-admin.section-heading title="Fee" />

            @include('livewire.super-admin.partials.analytics-tiles', ['cols' => 'grid-cols-2 lg:grid-cols-4', 'big' => true, 'items' => [
                ['Total Fee', $inr($feeStats['totalFee'] ?? 0), 'academic + transport + dues', 'text-gray-900'],
                ['Collected', $inr($feeStats['collected'] ?? 0), ($kpis['collect_rate'] ?? 0) . '% of the total', 'text-emerald-600'],
                ['Remaining', $inr($feeStats['remaining'] ?? 0), 'still to come in', 'text-rose-600'],
                ['Transport Fee', $inr($feeStats['transportFee'] ?? 0), 'of the total', 'text-gray-900'],
            ]])

            {{-- Month by month (area) + where the fee stands (dark) --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="sa-card p-5 lg:col-span-2 min-w-0">
                    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                        <div>
                            <h3 class="sa-card-title">Monthly Collection</h3>
                            <p class="sa-card-sub">Academic and transport fee collected · last 12 months</p>
                        </div>
                        <span class="sa-chip"><span class="sa-dot" style="background:#7c3aed"></span>{{ $short(array_sum($monthly['fee'] ?? [])) }} in 12 months</span>
                    </div>
                    <div class="h-64 relative" wire:ignore wire:key="chart-adminFeeMonthlyChart">
                        <canvas id="adminFeeMonthlyChart"></canvas>
                    </div>
                </div>

                <div class="sa-card-dark p-5 min-w-0 flex flex-col">
                    <div>
                        <h3 class="text-[15px] font-semibold text-white">Where the Fee Stands</h3>
                        <p class="text-xs text-white/50 mt-0.5">This session, up to today</p>
                    </div>
                    <div class="flex justify-center my-5">
                        @include('livewire.super-admin.partials.dash-donut', [
                            'segments' => [
                                ['label' => 'Collected', 'value' => (float) ($feeStats['collected'] ?? 0), 'color' => '#a78bfa'],
                                ['label' => 'Remaining', 'value' => (float) ($feeStats['remaining'] ?? 0), 'color' => 'rgba(255,255,255,.14)'],
                            ],
                            'center' => ($kpis['collect_rate'] ?? 0) . '%', 'centerLabel' => 'Collected', 'size' => 140, 'legend' => false, 'dark' => true, 'format' => null,
                        ])
                    </div>
                    <dl class="mt-auto space-y-2.5 text-sm">
                        <div class="flex items-center justify-between gap-2">
                            <dt class="flex items-center gap-2 text-white/60"><span class="sa-dot" style="background:#a78bfa"></span>Collected</dt>
                            <dd class="font-semibold tabular-nums">{{ $inr($feeStats['collected'] ?? 0) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <dt class="flex items-center gap-2 text-white/60"><span class="sa-dot" style="background:rgba(255,255,255,.3)"></span>Remaining</dt>
                            <dd class="font-semibold tabular-nums">{{ $inr($feeStats['remaining'] ?? 0) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-2 pt-2.5 border-t border-white/10">
                            <dt class="text-white/60">Average a day · 30 days</dt>
                            <dd class="font-semibold tabular-nums">{{ $inr($kpis['avg_daily'] ?? 0) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <dt class="text-white/60">Unpaid students</dt>
                            <dd class="font-semibold tabular-nums">{{ number_format($kpis['unpaid_students'] ?? 0) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                {{-- Fee by class chart --}}
                <div class="sa-card p-5 lg:col-span-2 min-w-0">
                    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                        <div>
                            <h3 class="sa-card-title">Fee Collection by Class</h3>
                            <p class="sa-card-sub">Collected vs remaining</p>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-gray-500">
                            <span class="flex items-center gap-1.5"><span class="sa-dot" style="background:#7c3aed"></span>Collected</span>
                            <span class="flex items-center gap-1.5"><span class="sa-dot" style="background:#c4b5fd"></span>Remaining</span>
                        </div>
                    </div>
                    <div class="h-64 relative" wire:ignore wire:key="chart-adminFeeClassChart">
                        <canvas id="adminFeeClassChart"></canvas>
                    </div>
                </div>

                {{-- Recovery rate by class --}}
                <div class="sa-card p-5 min-w-0">
                    <div class="mb-4">
                        <h3 class="sa-card-title">Recovery Rate by Class</h3>
                        <p class="sa-card-sub">Share of each class's fee collected</p>
                    </div>
                    @if (count($feeClassRate))
                        <div class="space-y-4 max-h-72 overflow-y-auto pr-1">
                            @foreach ($feeClassRate as $row)
                                <div class="min-w-0">
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <p class="font-medium text-gray-800 truncate" title="{{ $row['name'] }}">{{ $row['name'] }}</p>
                                        <p class="text-gray-700 tabular-nums whitespace-nowrap font-semibold">{{ $row['pct'] }}%</p>
                                    </div>
                                    <div class="mt-1.5 h-1.5 rounded-full bg-gray-100">
                                        <div class="h-1.5 rounded-full" style="width: {{ min(100, $row['pct']) }}%; background: {{ $barTone((float) $row['pct'], 80, 50) }}"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-10 text-gray-400 text-sm">No fee data available.</div>
                    @endif
                </div>
            </div>

            {{-- ── Transport fee ───────────────────────────────────────────────
                 Transport is billed off the route's monthly fee rather than a
                 fee structure, so it gets its own figures: the same class-by-class
                 read as above, and the same figures split by route. --}}
            <div class="pt-2">
                <h3 class="sa-card-title">Transport Fee</h3>
                <p class="sa-card-sub">Fare × each rider's months</p>
            </div>
            @include('livewire.super-admin.partials.analytics-tiles', ['cols' => 'grid-cols-2 lg:grid-cols-4', 'big' => false, 'items' => [
                ['Expected', $inr($transportFeeStats['expected'] ?? 0), number_format($transportFeeStats['riders'] ?? 0) . ' riders', 'text-gray-900'],
                ['Collected', $inr($transportFeeStats['collected'] ?? 0), null, 'text-emerald-600'],
                ['Remaining', $inr($transportFeeStats['remaining'] ?? 0), null, 'text-rose-600'],
                ['Recovered', ($transportFeeStats['rate'] ?? 0) . '%', 'of the expected', 'text-violet-600'],
            ]])

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                {{-- Transport fee by class --}}
                <div class="sa-card p-5 min-w-0">
                    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                        <div>
                            <h3 class="sa-card-title">Transport Fee by Class</h3>
                            <p class="sa-card-sub">Collected vs remaining</p>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-gray-500">
                            <span class="flex items-center gap-1.5"><span class="sa-dot" style="background:#7c3aed"></span>Collected</span>
                            <span class="flex items-center gap-1.5"><span class="sa-dot" style="background:#c4b5fd"></span>Remaining</span>
                        </div>
                    </div>
                    @if (count($transportClassData['labels'] ?? []))
                        <div class="h-64 relative" wire:ignore wire:key="chart-adminTransportClassChart">
                            <canvas id="adminTransportClassChart"></canvas>
                        </div>
                    @else
                        <div class="text-center py-16 text-gray-400 text-sm">No transport riders assigned yet.</div>
                    @endif
                </div>

                {{-- Transport fee by route: every route has a bar; the card keeps its
                     size and the chart, as tall as its routes need, scrolls inside it. --}}
                <div class="sa-card p-5 min-w-0">
                    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                        <div>
                            <h3 class="sa-card-title">Transport Fee by Route</h3>
                            <p class="sa-card-sub">Collected vs remaining · fare × each student's months</p>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-gray-500">
                            <span class="flex items-center gap-1.5"><span class="sa-dot" style="background:#7c3aed"></span>Collected</span>
                            <span class="flex items-center gap-1.5"><span class="sa-dot" style="background:#c4b5fd"></span>Remaining</span>
                        </div>
                    </div>
                    @if (count($transportRouteData['labels'] ?? []))
                        <div class="h-64 overflow-y-auto" wire:ignore wire:key="chart-adminTransportRouteChart">
                            <div class="relative" style="height: {{ max(256, count($transportRouteData['labels']) * 34 + 30) }}px">
                                <canvas id="adminTransportRouteChart"></canvas>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-16 text-gray-400 text-sm">No transport routes yet.</div>
                    @endif
                </div>
            </div>
        </section>

        {{-- ═══════════════════════ WHATSAPP ═══════════════════════
             The school's messages: the last 30 days or a month — each kind
             day by day, what the charged ones came to, delivery and the
             messages themselves. To read: the Super Admin turns them on. --}}
        @if ($wa = $this->whatsAppAnalytics())
            <section class="space-y-4">
                <x-admin.section-heading title="WhatsApp" />
                @include('livewire.partials.whatsapp-analytics', ['wa' => $wa, 'waEditable' => false, 'waKey' => 'admin'])
            </section>
        @endif

        {{-- ═══════════════════════ OPERATIONS ═══════════════════════ --}}
        <section class="space-y-4">
            <x-admin.section-heading title="Operations" />

            {{-- Arrangement --}}
            <div class="sa-card min-w-0 overflow-hidden">
                <div class="px-5 pt-5 pb-3 flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="sa-card-title flex items-center gap-2">
                            Today's Absent Teachers — Arrangement
                            @if (session('arrangement_saved'))
                                <span class="text-[11px] bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full font-medium">Saved</span>
                            @endif
                        </h3>
                        <p class="sa-card-sub">Assign a teacher who is free to each absent teacher's period</p>
                    </div>
                </div>

                @if (count($arrangements))
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm min-w-[720px]">
                            <thead class="text-gray-400 text-[11px] uppercase tracking-wide">
                                <tr class="border-y border-gray-100 bg-gray-50/60">
                                    <th class="px-5 py-2.5 text-left font-medium">Class</th>
                                    <th class="px-3 py-2.5 text-left font-medium">Section</th>
                                    <th class="px-3 py-2.5 text-left font-medium">Absent Teacher</th>
                                    <th class="px-3 py-2.5 text-left font-medium">Time</th>
                                    <th class="px-3 py-2.5 text-left font-medium">Available Teacher</th>
                                    <th class="px-5 py-2.5 text-left font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($arrangements as $arr)
                                    <tr class="hover:bg-gray-50/70 transition-colors">
                                        <td class="px-5 py-3 font-medium text-gray-800">{{ $arr['class'] }}</td>
                                        <td class="px-3 py-3 text-gray-600">{{ $arr['section'] }}</td>
                                        <td class="px-3 py-3 text-rose-600">{{ $arr['absent_teacher'] }}</td>
                                        <td class="px-3 py-3 text-gray-400 text-xs">{{ $arr['time'] }}</td>
                                        <td class="px-3 py-3">
                                            <div class="flex items-center gap-2">
                                                <select wire:model="selectedTeachers.{{ $arr['id'] }}"
                                                    class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-violet-400 bg-white min-w-32">
                                                    <option value="">Select Teacher</option>
                                                    @foreach ($availableTeachers as $teacher)
                                                        <option value="{{ $teacher['id'] }}">{{ $teacher['name'] }}</option>
                                                    @endforeach
                                                </select>
                                                <button wire:click="saveArrangement({{ $arr['id'] }})"
                                                    class="text-xs bg-violet-600 hover:bg-violet-700 text-white px-2.5 py-1.5 rounded-lg transition-colors">Assign</button>
                                            </div>
                                        </td>
                                        <td class="px-5 py-3">
                                            <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded-full {{ $arr['status'] === 'assigned' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                                <span class="sa-dot" style="background: {{ $arr['status'] === 'assigned' ? '#10b981' : '#f59e0b' }}; width: .375rem; height: .375rem"></span>
                                                {{ ucfirst($arr['status']) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-10 text-gray-400 text-sm border-t border-gray-100">All teachers are present today — no arrangements needed.</div>
                @endif
            </div>
        </section>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         CHART SCRIPTS
         The charts are drawn from the figures above and, after every
         re-render, updated in place from them — their canvases are
         wire:ignore'd so a re-render never blanks them.
    ══════════════════════════════════════════════════════════ --}}
    @include('livewire.super-admin.partials.dash-charts-js')
    <script>
        (function () {
            const VIOLET = '#7c3aed', INK = '#111827', SOFT = '#c4b5fd';
            const num   = v => Number(v || 0).toLocaleString('en-IN');
            const inr   = v => '₹' + num(v);
            const short = v => v >= 1e7 ? (v / 1e7).toFixed(1) + 'Cr' : v >= 1e5 ? (v / 1e5).toFixed(1) + 'L' : v >= 1e3 ? (v / 1e3).toFixed(0) + 'k' : v;
            const ticks = { color: '#9ca3af', font: { size: 10 } };
            const grid  = { color: 'rgba(17,24,39,0.05)' };

            // Quiet axes, no legend (each card draws its own), a dark tooltip.
            // kind: 'count', 'money' or 'pct' (0–100).
            function options(kind, extra) {
                const y = { beginAtZero: true, border: { display: false }, grid: grid,
                    ticks: Object.assign({ maxTicksLimit: 5, precision: 0 }, ticks) };
                if (kind === 'pct') { y.max = 100; y.ticks.stepSize = 20; y.ticks.maxTicksLimit = 6; y.ticks.callback = v => v + '%'; }
                if (kind === 'money') { y.ticks.callback = v => '₹' + short(v); }
                return Object.assign({
                    responsive: true, maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: INK, padding: 10, cornerRadius: 8, boxPadding: 4,
                            callbacks: kind === 'money'
                                ? { label: c => (c.dataset.label ? c.dataset.label + ': ' : '') + inr(c.parsed.y) }
                                : kind === 'pct' ? { label: c => (c.dataset.label ? c.dataset.label + ': ' : '') + c.raw + '%' } : {}
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, border: { display: false }, ticks: Object.assign({ maxRotation: 0, autoSkipPadding: 8 }, ticks) },
                        y: y
                    }
                }, extra || {});
            }

            // A smooth line with a soft fill fading down to the axis.
            function area(label, data, color, fill = true) {
                return {
                    label: label, data: data || [], borderColor: color, borderWidth: 2.5, tension: 0.42, spanGaps: true,
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

            const bar = (label, data, color, thick) => ({
                label: label, data: data || [], backgroundColor: color, borderRadius: 6, borderSkipped: false,
                barPercentage: 0.7, categoryPercentage: 0.6, maxBarThickness: thick || 18
            });

            // The number over each admissions bar.
            const barValues = {
                id: 'barValues',
                afterDatasetsDraw(chart) {
                    const ctx = chart.ctx;
                    chart.getDatasetMeta(0).data.forEach((b, i) => {
                        ctx.save();
                        ctx.font = '600 10px sans-serif';
                        ctx.fillStyle = '#374151';
                        ctx.textAlign = 'center';
                        ctx.fillText(num(chart.data.datasets[0].data[i]), b.x, b.y - 5);
                        ctx.restore();
                    });
                }
            };

            const collectedRemaining = (d, horizontal) => ({
                type: 'bar',
                data: { labels: d.labels || [], datasets: [bar('Collected', d.collected, VIOLET, horizontal ? 14 : 18), bar('Remaining', d.remaining, SOFT, horizontal ? 14 : 18)] },
                options: horizontal ? options('count', {
                    indexAxis: 'y',
                    interaction: { mode: 'index', intersect: false, axis: 'y' },
                    plugins: { legend: { display: false }, tooltip: { backgroundColor: INK, padding: 10, cornerRadius: 8, boxPadding: 4,
                        callbacks: { label: c => c.dataset.label + ': ' + inr(c.parsed.x) } } },
                    scales: {
                        x: { beginAtZero: true, border: { display: false }, grid: grid, ticks: Object.assign({ maxTicksLimit: 5, callback: v => '₹' + short(v) }, ticks) },
                        y: { grid: { display: false }, border: { display: false }, ticks: Object.assign({ autoSkip: false }, ticks) }
                    }
                }) : options('money')
            });

            saCharts.define('adminAnalytics', {
                // ── Attendance ──
                adminAttTrendChart: p => {
                    const s = area('Students', p.att.student, VIOLET), t = area('Teachers', p.att.teacher, INK, false);
                    s.present = p.att.sp; s.absent = p.att.sa; t.present = p.att.tp; t.absent = p.att.ta;
                    const o = options('pct');
                    o.plugins.tooltip.callbacks = { label: c => c.raw === null ? c.dataset.label + ': not marked'
                        : c.dataset.label + ': ' + c.raw + '% · Present ' + num(c.dataset.present[c.dataIndex]) + ' · Absent ' + num(c.dataset.absent[c.dataIndex]) };
                    return { type: 'line', data: { labels: p.att.labels || [], datasets: [s, t] }, options: o };
                },
                adminStuVolumeChart: p => ({
                    type: 'bar',
                    data: { labels: p.volume.labels || [], datasets: [bar('Attendance', p.volume.student, VIOLET, 28)] },
                    options: options('pct')
                }),
                adminTchVolumeChart: p => ({
                    type: 'bar',
                    data: { labels: p.volume.labels || [], datasets: [bar('Attendance', p.volume.teacher, INK, 28)] },
                    options: options('pct')
                }),

                // ── Admissions ──
                adminAdmissionsChart: p => {
                    const o = options('count', { layout: { padding: { top: 18 } } });
                    o.scales.x.ticks = Object.assign({ autoSkip: false, maxRotation: 60 }, ticks);
                    return {
                        type: 'bar',
                        data: { labels: p.admissions.labels || [], datasets: [bar('Admissions', p.admissions.data, VIOLET, 28)] },
                        options: o,
                        plugins: [barValues]
                    };
                },

                // ── Fee ──
                adminFeeMonthlyChart: p => ({
                    type: 'line',
                    data: { labels: p.feeMonthly.labels || [], datasets: [area('Collected', p.feeMonthly.data, VIOLET)] },
                    options: options('money')
                }),
                adminFeeClassChart: p => collectedRemaining(p.feeClass, false),
                adminTransportClassChart: p => (p.transportClass.labels || []).length ? collectedRemaining(p.transportClass, false) : null,
                adminTransportRouteChart: p => (p.transportRoute.labels || []).length ? collectedRemaining(p.transportRoute, true) : null,
            });
        })();
    </script>
</div>
