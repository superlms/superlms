<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════════════
         HEADER — title, date, and the three numbers worth carrying
         at the top of every screen.
    ══════════════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">Analytics</h1>
                <p class="text-xs text-gray-400 mt-0.5">{{ now()->format('l, d M Y') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                @php
                    $headline = [
                        ['Students', number_format($statsData['totalStudents'] ?? 0)],
                        ['Attendance', ($kpis['student_rate'] ?? 0) . '%'],
                        ['Collected', ($kpis['collect_rate'] ?? 0) . '%'],
                    ];
                @endphp
                @foreach ($headline as [$label, $value])
                    <div>
                        <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wide">{{ $label }}</p>
                        <p class="text-base font-semibold text-gray-900 tabular-nums">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 space-y-8">

        {{-- ═══════════════════════ KEY METRICS ═══════════════════════ --}}
        <section class="space-y-4">
            <x-admin.section-heading title="Key Metrics" />

            {{-- Eight tiles, four to a row on a wide screen: Fee Collection is the share of the whole fee
                 (academic + transport + Last Year Dues) that has come in, and the three
                 tiles after it are what that whole fee is made of. --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden grid grid-cols-2 xl:grid-cols-4">
                @php
                    $d = $kpis['student_delta'] ?? 0;
                    $metrics = [
                        ['Student Attendance', ($kpis['student_rate'] ?? 0) . '%', null, 'text-gray-900'],
                        ['Teacher Attendance', ($kpis['teacher_rate'] ?? 0) . '%', 'Today', 'text-gray-900'],
                        ['Fee Collection', ($kpis['collect_rate'] ?? 0) . '%', 'of ₹' . number_format($feeStats['totalFee'] ?? 0, 0) . ' total fee', 'text-gray-900'],
                        ['Academic Fee', '₹' . number_format($kpis['academic_fee'] ?? 0, 0), 'total', 'text-gray-900'],
                        ['Transport Fee', '₹' . number_format($kpis['transport_fee'] ?? 0, 0), 'total', 'text-gray-900'],
                        ['Last Year Dues', '₹' . number_format($kpis['last_year_dues'] ?? 0, 0), 'total', 'text-gray-900'],
                        ['Unpaid Students', number_format($kpis['unpaid_students'] ?? 0), 'no payment yet', 'text-red-500'],
                        ['New Admissions', number_format($kpis['new_admissions'] ?? 0), 'last 30 days', 'text-violet-600'],
                    ];
                @endphp
                @foreach ($metrics as $i => [$label, $value, $sub, $tone])
                    <div class="px-5 py-4 -mr-px -mb-px border-r border-b border-gray-100">
                        <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">{{ $label }}</p>
                        <p class="text-[26px] leading-none font-semibold {{ $tone }} mt-2 tabular-nums">{{ $value }}</p>
                        @if ($i === 0 && empty($kpis['student_marked']))
                            {{-- Nothing to set against yesterday until today is marked. --}}
                            <p class="mt-2 text-xs text-gray-400">Not marked yet today</p>
                        @elseif ($i === 0)
                            <p class="mt-2 inline-flex items-center gap-1 text-xs font-medium {{ $d > 0 ? 'text-emerald-600' : ($d < 0 ? 'text-red-500' : 'text-gray-400') }}">
                                @if ($d > 0)
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" /></svg>
                                @elseif ($d < 0)
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                @endif
                                {{ abs($d) }}% vs yesterday
                            </p>
                        @else
                            <p class="mt-2 text-xs text-gray-400">{{ $sub }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ═══════════════════════ ATTENDANCE ═══════════════════════ --}}
        <section class="space-y-4">
            <x-admin.section-heading title="Attendance" />

            {{-- Rate trend + student split --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">Attendance Rate Trend</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Daily % · last 30 days</p>
                        </div>
                        <div class="flex items-center gap-3 text-[11px] text-gray-400">
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-500 inline-block"></span> Students</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-blue-500 inline-block"></span> Teachers</span>
                        </div>
                    </div>
                    <div class="h-64" wire:ignore wire:key="att-trend">
                        <canvas x-data="{
                            init() {
                                new Chart(this.$el.getContext('2d'), {
                                    type: 'line',
                                    data: {
                                        labels: @js($attendanceDailyTrend['labels'] ?? []),
                                        datasets: [
                                            { label: 'Students', data: @js($attendanceDailyTrend['student'] ?? []), present: @js($attendanceDailyTrend['studentPresent'] ?? []), absent: @js($attendanceDailyTrend['studentAbsent'] ?? []), borderColor: 'rgb(16,185,129)', backgroundColor: 'rgba(16,185,129,0.10)', fill: true, tension: 0.35, borderWidth: 2, pointRadius: 2, spanGaps: true },
                                            { label: 'Teachers', data: @js($attendanceDailyTrend['teacher'] ?? []), present: @js($attendanceDailyTrend['teacherPresent'] ?? []), absent: @js($attendanceDailyTrend['teacherAbsent'] ?? []), borderColor: 'rgb(59,130,246)', fill: false, tension: 0.35, borderWidth: 2, pointRadius: 2, spanGaps: true }
                                        ]
                                    },
                                    options: {
                                        responsive: true, maintainAspectRatio: false,
                                        interaction: { mode: 'index', intersect: false },
                                        plugins: {
                                            legend: { display: false },
                                            tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + c.raw + '% · Present ' + Number(c.dataset.present[c.dataIndex]).toLocaleString('en-IN') + ' · Absent ' + Number(c.dataset.absent[c.dataIndex]).toLocaleString('en-IN') } }
                                        },
                                        scales: {
                                            x: { grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 0, autoSkipPadding: 8 } },
                                            y: { beginAtZero: true, max: 100, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, callback: (v) => v + '%' } }
                                        }
                                    }
                                });
                            }
                        }"></canvas>
                    </div>
                </div>

                {{-- Student split: today's students, present against absent --}}
                @php $splitMarked = ($studentPieData['present'] ?? 0) + ($studentPieData['absent'] ?? 0); @endphp
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="mb-4">
                        <h3 class="text-sm font-semibold text-gray-800">Student Split</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Today · {{ now()->format('d M Y') }}</p>
                    </div>
                    @if ($splitMarked > 0)
                        <div class="h-40" wire:ignore wire:key="stu-pie">
                            <canvas x-data="{
                                init() {
                                    new Chart(this.$el.getContext('2d'), {
                                        type: 'doughnut',
                                        data: {
                                            labels: ['Present', 'Absent'],
                                            datasets: [{ data: [{{ $studentPieData['present'] ?? 0 }}, {{ $studentPieData['absent'] ?? 0 }}], share: [{{ $studentPieData['presentPct'] ?? 0 }}, {{ $studentPieData['absentPct'] ?? 0 }}], backgroundColor: ['rgba(16,185,129,0.85)', 'rgba(239,68,68,0.7)'], borderWidth: 0, hoverOffset: 6 }]
                                        },
                                        options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12, boxWidth: 10, usePointStyle: true } }, tooltip: { callbacks: { label: (c) => c.label + ': ' + c.dataset.share[c.dataIndex] + '% (' + Number(c.raw).toLocaleString('en-IN') + ')' } } } }
                                    });
                                }
                            }"></canvas>
                        </div>
                    @else
                        <div class="h-40 flex items-center justify-center text-center text-sm text-gray-400">Student attendance is not marked yet today.</div>
                    @endif
                    <div class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-2 gap-2 text-center">
                        {{-- The share of the students marked today; the counts beside the label. --}}
                        <div>
                            <p class="text-xl font-semibold text-emerald-600 tabular-nums">{{ $studentPieData['presentPct'] ?? 0 }}%</p>
                            <p class="text-[10px] text-gray-400 uppercase tracking-wide">Present · {{ number_format($studentPieData['present'] ?? 0) }}</p>
                        </div>
                        <div>
                            <p class="text-xl font-semibold text-red-500 tabular-nums">{{ $studentPieData['absentPct'] ?? 0 }}%</p>
                            <p class="text-[10px] text-gray-400 uppercase tracking-wide">Absent · {{ number_format($studentPieData['absent'] ?? 0) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Volume: each month's average attendance %, from 1 April of the session
                 to this month — the students', and the teachers'. A month's figure
                 is the average of its days' percentages, and it is all a bar says
                 when the pointer is on it. --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">Student Attendance Volume</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Average of the days' % per month · from {{ $monthlyAttendancePct['from'] ?? '' }}</p>
                        </div>
                        <div class="flex items-center gap-3 text-[11px] text-gray-400">
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-500 inline-block"></span> Attendance %</span>
                        </div>
                    </div>
                    <div class="h-56" wire:ignore wire:key="stu-bar">
                        <canvas x-data="{
                            init() {
                                new Chart(this.$el.getContext('2d'), {
                                    type: 'bar',
                                    data: {
                                        labels: @js($monthlyAttendancePct['labels'] ?? []),
                                        datasets: [
                                            { label: 'Attendance', data: @js($monthlyAttendancePct['student'] ?? []), backgroundColor: 'rgba(16,185,129,0.75)', borderRadius: 3, borderSkipped: false, maxBarThickness: 44 }
                                        ]
                                    },
                                    options: {
                                        responsive: true, maintainAspectRatio: false,
                                        plugins: {
                                            legend: { display: false },
                                            tooltip: { displayColors: false, callbacks: { title: () => null, label: (c) => c.raw + '%' } }
                                        },
                                        scales: { x: { grid: { display: false }, ticks: { font: { size: 9 }, maxRotation: 0, autoSkipPadding: 6 } }, y: { beginAtZero: true, max: 100, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, callback: (v) => v + '%' } } }
                                    }
                                });
                            }
                        }"></canvas>
                    </div>
                </div>

                {{-- Teacher volume: the same monthly average as the students', and
                     nothing else on the card. --}}
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">Teacher Attendance Volume</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Average of the days' % per month · from {{ $monthlyAttendancePct['from'] ?? '' }}</p>
                        </div>
                        <div class="flex items-center gap-3 text-[11px] text-gray-400">
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-blue-500 inline-block"></span> Attendance %</span>
                        </div>
                    </div>
                    <div class="h-56" wire:ignore wire:key="tch-bar">
                        <canvas x-data="{
                            init() {
                                new Chart(this.$el.getContext('2d'), {
                                    type: 'bar',
                                    data: {
                                        labels: @js($monthlyAttendancePct['labels'] ?? []),
                                        datasets: [
                                            { label: 'Attendance', data: @js($monthlyAttendancePct['teacher'] ?? []), backgroundColor: 'rgba(59,130,246,0.75)', borderRadius: 3, borderSkipped: false, maxBarThickness: 44 }
                                        ]
                                    },
                                    options: {
                                        responsive: true, maintainAspectRatio: false,
                                        plugins: {
                                            legend: { display: false },
                                            tooltip: { displayColors: false, callbacks: { title: () => null, label: (c) => c.raw + '%' } }
                                        },
                                        scales: { x: { grid: { display: false }, ticks: { font: { size: 9 }, maxRotation: 0, autoSkipPadding: 6 } }, y: { beginAtZero: true, max: 100, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, callback: (v) => v + '%' } } }
                                    }
                                });
                            }
                        }"></canvas>
                    </div>
                </div>
            </div>

            {{-- Class-wise ranking --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="mb-4">
                    <h3 class="text-sm font-semibold text-gray-800">Class-wise Attendance Ranking</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Average of each day's attendance % · overall</p>
                </div>
                @if (count($classAttendanceRank))
                    <div class="space-y-2.5">
                        @foreach ($classAttendanceRank as $idx => $row)
                            @php $bar = $row['pct'] >= 85 ? 'bg-emerald-500' : ($row['pct'] >= 70 ? 'bg-amber-500' : 'bg-red-500'); @endphp
                            <div class="flex items-center gap-3">
                                <span class="w-6 text-xs text-gray-300 flex-shrink-0 tabular-nums">{{ $idx + 1 }}</span>
                                <span class="w-24 sm:w-32 text-sm text-gray-700 truncate flex-shrink-0">{{ $row['name'] }}</span>
                                <div class="flex-1 bg-gray-100 rounded-full h-1.5 min-w-0">
                                    <div class="h-1.5 rounded-full {{ $bar }}" style="width: {{ $row['pct'] }}%"></div>
                                </div>
                                <span class="w-12 text-right text-sm font-semibold text-gray-800 flex-shrink-0 tabular-nums">{{ $row['pct'] }}%</span>
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
                    class="text-xs bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-indigo-400">
                    <option value="">All Classes</option>
                    @foreach ($standards as $std)
                        <option value="{{ $std->id }}">{{ $std->name }}</option>
                    @endforeach
                </select>
                @if ($sections)
                    <select wire:model.live="performerSection"
                        class="text-xs bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-indigo-400">
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
                @foreach ([['Top 10 Rankers', $topRankers, 'text-emerald-600'], ['Bottom 10 Rankers', $bottomRankers, 'text-red-500']] as [$heading, $list, $tone])
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100">
                            <h3 class="text-sm font-semibold text-gray-800">{{ $heading }}</h3>
                        </div>
                        @if (count($list))
                            <div class="divide-y divide-gray-100">
                                @foreach ($list as $student)
                                    <div class="flex items-center gap-3 px-5 py-3">
                                        <span class="w-7 text-xs text-gray-300 tabular-nums flex-shrink-0">{{ $student['rank'] }}</span>
                                        <div class="w-9 h-9 rounded-full overflow-hidden bg-gray-100 flex-shrink-0">
                                            @if ($student['photo'])
                                                <img src="{{ $student['photo'] }}" alt="{{ $student['name'] }}" class="w-full h-full object-cover">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center text-gray-500 text-xs font-bold">{{ strtoupper(substr($student['name'], 0, 1)) }}</div>
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-800 truncate">{{ $student['name'] }}</p>
                                            <p class="text-xs text-gray-400">{{ $student['class'] }} · {{ $student['section'] }}</p>
                                        </div>
                                        <div class="text-right flex-shrink-0">
                                            <p class="text-sm font-semibold {{ $tone }} tabular-nums">{{ $student['score'] }}%</p>
                                            <p class="text-[10px] text-gray-400 tabular-nums">{{ $student['obtained'] + 0 }} / {{ $student['max'] + 0 }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-10 text-gray-400 text-sm">
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
                {{-- Admissions trend --}}
                <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">Admissions Trend</h3>
                            <p class="text-xs text-gray-400 mt-0.5">
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
                    {{-- One bar for each date students were admitted on, its number over it:
                         the last fifteen such dates of the year, so they fit the card and
                         nothing scrolls sideways. --}}
                    <div wire:key="adm-trend-{{ $admissionYear }}">
                        <div class="h-56">
                            <canvas x-data="{
                                init() {
                                    new Chart(this.$el.getContext('2d'), {
                                        type: 'bar',
                                        data: {
                                            labels: @js($admissionsTrend['labels'] ?? []),
                                            datasets: [{ label: 'Admissions', data: @js($admissionsTrend['data'] ?? []), backgroundColor: 'rgba(139,92,246,0.7)', borderRadius: 3, borderSkipped: false, maxBarThickness: 30 }]
                                        },
                                        options: {
                                            responsive: true, maintainAspectRatio: false,
                                            layout: { padding: { top: 18 } },
                                            plugins: { legend: { display: false } },
                                            scales: { x: { grid: { display: false }, ticks: { font: { size: 9 }, autoSkip: false, maxRotation: 60 } }, y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, precision: 0 } } }
                                        },
                                        plugins: [{
                                            id: 'barValues',
                                            afterDatasetsDraw(chart) {
                                                const ctx = chart.ctx;
                                                chart.getDatasetMeta(0).data.forEach((bar, i) => {
                                                    ctx.save();
                                                    ctx.font = '600 10px sans-serif';
                                                    ctx.fillStyle = '#374151';
                                                    ctx.textAlign = 'center';
                                                    ctx.fillText(Number(chart.data.datasets[0].data[i]).toLocaleString('en-IN'), bar.x, bar.y - 5);
                                                    ctx.restore();
                                                });
                                            }
                                        }]
                                    });
                                }
                            }"></canvas>
                        </div>
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
                <div class="relative h-80 lg:h-auto">
                <div class="absolute inset-0 bg-white rounded-xl border border-gray-200 p-5 flex flex-col overflow-hidden">
                    <h3 class="text-sm font-semibold text-gray-800 mb-4 flex-shrink-0">Enquiry Funnel</h3>
                    <div class="grid grid-cols-3 gap-2 text-center flex-shrink-0">
                        <div>
                            <p class="text-xl font-semibold text-gray-900 tabular-nums">{{ $enquiryStats['total'] ?? 0 }}</p>
                            <p class="text-[10px] text-gray-400 uppercase tracking-wide">Total</p>
                        </div>
                        <div>
                            <p class="text-xl font-semibold text-emerald-600 tabular-nums">{{ $enquiryStats['responded'] ?? 0 }}</p>
                            <p class="text-[10px] text-gray-400 uppercase tracking-wide">Responded</p>
                        </div>
                        <div>
                            <p class="text-xl font-semibold text-amber-600 tabular-nums">{{ $enquiryStats['pending'] ?? 0 }}</p>
                            <p class="text-[10px] text-gray-400 uppercase tracking-wide">Pending</p>
                        </div>
                    </div>
                    <div class="mt-4 flex-shrink-0">
                        <div class="flex justify-between text-xs mb-1.5"><span class="text-gray-400">Response rate</span><span class="font-semibold text-gray-700">{{ $enquiryStats['rate'] ?? 0 }}%</span></div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5"><div class="h-1.5 rounded-full bg-emerald-500" style="width: {{ $enquiryStats['rate'] ?? 0 }}%"></div></div>
                    </div>
                    <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wide mt-5 mb-1 flex-shrink-0">Latest enquiries</p>
                    <div class="divide-y divide-gray-100 flex-1 min-h-0 overflow-y-auto">
                        @forelse($adminEnquiries as $enq)
                            <div class="flex items-center gap-2.5 py-2.5">
                                <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0 font-bold text-gray-500 text-xs">
                                    {{ strtoupper(substr($enq['name'], 0, 1)) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-800 truncate">{{ $enq['name'] }}</p>
                                    <p class="text-xs text-gray-400 truncate">{{ $enq['time'] }}</p>
                                </div>
                                <span class="flex-shrink-0 px-2 py-0.5 text-[10px] rounded-full font-medium {{ $enq['status'] === 'Responded' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $enq['status'] }}</span>
                            </div>
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

            <div class="bg-white rounded-xl border border-gray-200 grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-gray-100">
                @php
                    $feeCards = [
                        ['Total Fee',     '₹' . number_format($feeStats['totalFee'] ?? 0),     'text-gray-900'],
                        ['Collected',     '₹' . number_format($feeStats['collected'] ?? 0),    'text-emerald-600'],
                        ['Remaining',     '₹' . number_format($feeStats['remaining'] ?? 0),    'text-red-500'],
                        ['Transport Fee', '₹' . number_format($feeStats['transportFee'] ?? 0), 'text-gray-900'],
                    ];
                @endphp
                @foreach ($feeCards as [$label, $value, $tone])
                    <div class="px-5 py-4">
                        <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">{{ $label }}</p>
                        <p class="text-xl font-semibold {{ $tone }} mt-1.5 tabular-nums">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                {{-- Fee by class chart --}}
                <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">Fee Collection by Class</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Collected vs remaining</p>
                        </div>
                        <div class="flex items-center gap-3 text-[11px] text-gray-400">
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-500 inline-block"></span> Collected</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-red-400 inline-block"></span> Remaining</span>
                        </div>
                    </div>
                    <div class="h-64" wire:ignore wire:key="fee-class-chart">
                        <canvas x-data="{
                            init() {
                                new Chart(this.$el.getContext('2d'), {
                                    type: 'bar',
                                    data: {
                                        labels: @js($feeClassData['labels'] ?? []),
                                        datasets: [
                                            { label: 'Collected', data: @js($feeClassData['collected'] ?? []), backgroundColor: 'rgba(16,185,129,0.75)', borderRadius: 3, borderSkipped: false },
                                            { label: 'Remaining', data: @js($feeClassData['remaining'] ?? []), backgroundColor: 'rgba(239,68,68,0.6)', borderRadius: 3, borderSkipped: false }
                                        ]
                                    },
                                    options: {
                                        responsive: true, maintainAspectRatio: false,
                                        plugins: {
                                            legend: { display: false },
                                            tooltip: { callbacks: { label: (c) => c.dataset.label + ': ₹' + Number(c.raw).toLocaleString('en-IN') } }
                                        },
                                        scales: {
                                            x: { grid: { display: false }, ticks: { font: { size: 9 }, maxRotation: 0, autoSkipPadding: 6 } },
                                            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, callback: (v) => '₹' + Number(v).toLocaleString('en-IN') } }
                                        }
                                    }
                                });
                            }
                        }"></canvas>
                    </div>
                </div>

                {{-- Recovery rate by class --}}
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h3 class="text-sm font-semibold text-gray-800 mb-4">Recovery Rate by Class</h3>
                    @if (count($feeClassRate))
                        <div class="space-y-3 max-h-72 overflow-y-auto pr-1">
                            @foreach ($feeClassRate as $row)
                                @php $bar = $row['pct'] >= 80 ? 'bg-emerald-500' : ($row['pct'] >= 50 ? 'bg-amber-500' : 'bg-red-500'); @endphp
                                <div>
                                    <div class="flex justify-between text-xs mb-1.5">
                                        <span class="text-gray-600 truncate pr-2">{{ $row['name'] }}</span>
                                        <span class="font-semibold text-gray-800 flex-shrink-0 tabular-nums">{{ $row['pct'] }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-1.5"><div class="h-1.5 rounded-full {{ $bar }}" style="width: {{ $row['pct'] }}%"></div></div>
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
                 fee structure, so it gets its own pair: the same class-by-class
                 read as above, and the same figures split by route. --}}
            <div class="flex flex-wrap items-baseline justify-between gap-3 pt-2">
                <div>
                    <h3 class="text-sm font-semibold text-gray-800">Transport Fee</h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Expected ₹{{ number_format($transportFeeStats['expected'] ?? 0, 0) }} ·
                        Collected ₹{{ number_format($transportFeeStats['collected'] ?? 0, 0) }} ·
                        Remaining ₹{{ number_format($transportFeeStats['remaining'] ?? 0, 0) }}
                    </p>
                </div>
                <div class="flex items-center gap-3 text-[11px] text-gray-400">
                    <span class="font-semibold text-gray-700 tabular-nums">{{ $transportFeeStats['rate'] ?? 0 }}% recovered</span>
                    <span>{{ $transportFeeStats['riders'] ?? 0 }} riders</span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                {{-- Transport fee by class --}}
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">Transport Fee by Class</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Collected vs remaining</p>
                        </div>
                        <div class="flex items-center gap-3 text-[11px] text-gray-400">
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-sky-500 inline-block"></span> Collected</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-orange-400 inline-block"></span> Remaining</span>
                        </div>
                    </div>
                    @if (count($transportClassData['labels'] ?? []))
                        <div class="h-64" wire:ignore wire:key="transport-class-chart">
                            <canvas x-data="{
                                init() {
                                    new Chart(this.$el.getContext('2d'), {
                                        type: 'bar',
                                        data: {
                                            labels: @js($transportClassData['labels'] ?? []),
                                            datasets: [
                                                { label: 'Collected', data: @js($transportClassData['collected'] ?? []), backgroundColor: 'rgba(14,165,233,0.75)', borderRadius: 3, borderSkipped: false },
                                                { label: 'Remaining', data: @js($transportClassData['remaining'] ?? []), backgroundColor: 'rgba(251,146,60,0.7)', borderRadius: 3, borderSkipped: false }
                                            ]
                                        },
                                        options: {
                                            responsive: true, maintainAspectRatio: false,
                                            plugins: {
                                                legend: { display: false },
                                                tooltip: { callbacks: { label: (c) => c.dataset.label + ': ₹' + Number(c.raw).toLocaleString('en-IN') } }
                                            },
                                            scales: {
                                                x: { grid: { display: false }, ticks: { font: { size: 9 }, maxRotation: 0, autoSkipPadding: 6 } },
                                                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, callback: (v) => '₹' + Number(v).toLocaleString('en-IN') } }
                                            }
                                        }
                                    });
                                }
                            }"></canvas>
                        </div>
                    @else
                        <div class="text-center py-16 text-gray-400 text-sm">No transport riders assigned yet.</div>
                    @endif
                </div>

                {{-- Transport fee by route --}}
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">Transport Fee by Route</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Collected vs remaining · fare × each student's months</p>
                        </div>
                        <div class="flex items-center gap-3 text-[11px] text-gray-400">
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-sky-500 inline-block"></span> Collected</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-orange-400 inline-block"></span> Remaining</span>
                        </div>
                    </div>
                    {{-- Every route has a bar. The card keeps its size however many there
                         are: the chart is as tall as its routes need and scrolls inside
                         this fixed box. --}}
                    @if (count($transportRouteData['labels'] ?? []))
                        <div class="h-64 overflow-y-auto" wire:ignore wire:key="transport-route-chart">
                            <div style="height: {{ max(256, count($transportRouteData['labels']) * 34 + 30) }}px">
                            <canvas x-data="{
                                init() {
                                    new Chart(this.$el.getContext('2d'), {
                                        type: 'bar',
                                        data: {
                                            labels: @js($transportRouteData['labels'] ?? []),
                                            datasets: [
                                                { label: 'Collected', data: @js($transportRouteData['collected'] ?? []), backgroundColor: 'rgba(14,165,233,0.75)', borderRadius: 3, borderSkipped: false },
                                                { label: 'Remaining', data: @js($transportRouteData['remaining'] ?? []), backgroundColor: 'rgba(251,146,60,0.7)', borderRadius: 3, borderSkipped: false }
                                            ]
                                        },
                                        options: {
                                            indexAxis: 'y',
                                            responsive: true, maintainAspectRatio: false,
                                            plugins: {
                                                legend: { display: false },
                                                tooltip: { callbacks: { label: (c) => c.dataset.label + ': ₹' + Number(c.raw).toLocaleString('en-IN') } }
                                            },
                                            scales: {
                                                x: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 9 }, callback: (v) => '₹' + Number(v).toLocaleString('en-IN') } },
                                                y: { grid: { display: false }, ticks: { font: { size: 10 }, autoSkip: false } }
                                            }
                                        }
                                    });
                                }
                            }"></canvas>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-16 text-gray-400 text-sm">No transport routes yet.</div>
                    @endif
                </div>
            </div>
        </section>

        {{-- ═══════════════════════ OPERATIONS ═══════════════════════ --}}
        <section class="space-y-4">
            <x-admin.section-heading title="Operations" />

            {{-- Arrangement --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-2">
                    <h3 class="text-sm font-semibold text-gray-800">Today's Absent Teachers — Arrangement</h3>
                    @if (session('arrangement_saved'))
                        <span class="text-[10px] bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full font-medium">Saved</span>
                    @endif
                </div>

                @if (count($arrangements))
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm min-w-[720px]">
                            <thead class="bg-gray-50/70 text-gray-400 text-[11px] uppercase tracking-wide">
                                <tr>
                                    <th class="px-4 py-2.5 text-left font-medium">Class</th>
                                    <th class="px-4 py-2.5 text-left font-medium">Section</th>
                                    <th class="px-4 py-2.5 text-left font-medium">Absent Teacher</th>
                                    <th class="px-4 py-2.5 text-left font-medium">Time</th>
                                    <th class="px-4 py-2.5 text-left font-medium">Available Teacher</th>
                                    <th class="px-4 py-2.5 text-left font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($arrangements as $arr)
                                    <tr class="hover:bg-gray-50/70 transition-colors">
                                        <td class="px-4 py-3 font-medium text-gray-800">{{ $arr['class'] }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $arr['section'] }}</td>
                                        <td class="px-4 py-3 text-red-600">{{ $arr['absent_teacher'] }}</td>
                                        <td class="px-4 py-3 text-gray-400 text-xs">{{ $arr['time'] }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <select wire:model="selectedTeachers.{{ $arr['id'] }}"
                                                    class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-indigo-400 bg-white min-w-32">
                                                    <option value="">Select Teacher</option>
                                                    @foreach ($availableTeachers as $teacher)
                                                        <option value="{{ $teacher['id'] }}">{{ $teacher['name'] }}</option>
                                                    @endforeach
                                                </select>
                                                <button wire:click="saveArrangement({{ $arr['id'] }})"
                                                    class="text-xs bg-gray-900 hover:bg-gray-800 text-white px-2.5 py-1.5 rounded-lg transition-colors">Assign</button>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-0.5 text-[10px] rounded-full font-medium {{ $arr['status'] === 'assigned' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                                {{ ucfirst($arr['status']) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-10 text-gray-400 text-sm">All teachers are present today — no arrangements needed.</div>
                @endif
            </div>
        </section>
    </div>
</div>
