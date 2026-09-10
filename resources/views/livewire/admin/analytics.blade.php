<div class="min-h-screen bg-gray-50">

    @php
        // The day the teacher-volume dropdown is pointing at.
        $tDay = $this->teacherDay();
        $teacherDays = $teacherDailyAttendance['days'] ?? [];
    @endphp

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
            <x-admin.section-heading title="Key Metrics" subtitle="Live indicators with day-over-day movement" />

            <div class="bg-white rounded-xl border border-gray-200 grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 divide-x divide-y xl:divide-y-0 divide-gray-100">
                @php
                    $d = $kpis['student_delta'] ?? 0;
                    $metrics = [
                        ['Student Attendance', ($kpis['student_rate'] ?? 0) . '%', null, 'text-gray-900'],
                        ['Teacher Attendance', ($kpis['teacher_rate'] ?? 0) . '%', 'Today', 'text-gray-900'],
                        ['Fee Collection', ($kpis['collect_rate'] ?? 0) . '%', 'of total expected', 'text-gray-900'],
                        ['Avg / Day', '₹' . number_format($kpis['avg_daily'] ?? 0, 0), 'last 30 days', 'text-gray-900'],
                        ['Unpaid Students', number_format($kpis['unpaid_students'] ?? 0), 'no payment yet', 'text-red-500'],
                        ['New Admissions', number_format($kpis['new_admissions'] ?? 0), 'last 30 days', 'text-violet-600'],
                    ];
                @endphp
                @foreach ($metrics as $i => [$label, $value, $sub, $tone])
                    <div class="px-5 py-4">
                        <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">{{ $label }}</p>
                        <p class="text-[26px] leading-none font-semibold {{ $tone }} mt-2 tabular-nums">{{ $value }}</p>
                        @if ($i === 0)
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
            <x-admin.section-heading title="Attendance" subtitle="Year-long rate, daily volume and class-wise standing" />

            {{-- Rate trend + student split --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">Attendance Rate Trend</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Monthly % · school year Apr – Mar</p>
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
                                        labels: @js($attendanceTrendPct['labels'] ?? []),
                                        datasets: [
                                            { label: 'Students', data: @js($attendanceTrendPct['student'] ?? []), borderColor: 'rgb(16,185,129)', backgroundColor: 'rgba(16,185,129,0.10)', fill: true, tension: 0.35, borderWidth: 2, pointRadius: 2 },
                                            { label: 'Teachers', data: @js($attendanceTrendPct['teacher'] ?? []), borderColor: 'rgb(59,130,246)', fill: false, tension: 0.35, borderWidth: 2, pointRadius: 2 }
                                        ]
                                    },
                                    options: {
                                        responsive: true, maintainAspectRatio: false,
                                        plugins: { legend: { display: false } },
                                        scales: {
                                            x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                                            y: { beginAtZero: true, max: 100, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, callback: (v) => v + '%' } }
                                        }
                                    }
                                });
                            }
                        }"></canvas>
                    </div>
                </div>

                {{-- Student split --}}
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-800">Student Split</h3>
                        <select wire:model.live="attendanceFilter"
                            class="text-xs bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-indigo-400">
                            <option value="1">Today</option>
                            <option value="7">Last 7 days</option>
                            <option value="14">Last 14 days</option>
                            <option value="30">Last 30 days</option>
                        </select>
                    </div>
                    <div class="h-40" wire:key="stu-pie-{{ $attendanceFilter }}">
                        <canvas x-data="{
                            init() {
                                new Chart(this.$el.getContext('2d'), {
                                    type: 'doughnut',
                                    data: {
                                        labels: ['Present', 'Absent'],
                                        datasets: [{ data: [{{ $studentPieData['present'] ?? 0 }}, {{ $studentPieData['absent'] ?? 0 }}], backgroundColor: ['rgba(16,185,129,0.85)', 'rgba(239,68,68,0.7)'], borderWidth: 0, hoverOffset: 6 }]
                                    },
                                    options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12, boxWidth: 10, usePointStyle: true } } } }
                                });
                            }
                        }"></canvas>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-2 gap-2 text-center">
                        <div>
                            <p class="text-xl font-semibold text-emerald-600 tabular-nums">{{ $studentPieData['present'] ?? 0 }}</p>
                            <p class="text-[10px] text-gray-400 uppercase tracking-wide">Present</p>
                        </div>
                        <div>
                            <p class="text-xl font-semibold text-red-500 tabular-nums">{{ $studentPieData['absent'] ?? 0 }}</p>
                            <p class="text-[10px] text-gray-400 uppercase tracking-wide">Absent</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Volume: students by month, teachers by date --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">Student Attendance Volume</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Per month · school year Apr – Mar</p>
                        </div>
                        <div class="flex items-center gap-3 text-[11px] text-gray-400">
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-500 inline-block"></span> Present</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-red-400 inline-block"></span> Absent</span>
                        </div>
                    </div>
                    <div class="h-56" wire:ignore wire:key="stu-bar">
                        <canvas x-data="{
                            init() {
                                new Chart(this.$el.getContext('2d'), {
                                    type: 'bar',
                                    data: {
                                        labels: @js($attendanceMonths),
                                        datasets: [
                                            { label: 'Present', data: @js($studentMonthlyAttendance['present'] ?? []), backgroundColor: 'rgba(16,185,129,0.75)', borderRadius: 3, borderSkipped: false },
                                            { label: 'Absent', data: @js($studentMonthlyAttendance['absent'] ?? []), backgroundColor: 'rgba(239,68,68,0.6)', borderRadius: 3, borderSkipped: false }
                                        ]
                                    },
                                    options: {
                                        responsive: true, maintainAspectRatio: false,
                                        plugins: { legend: { display: false } },
                                        scales: { x: { grid: { display: false }, ticks: { font: { size: 9 }, maxRotation: 0, autoSkipPadding: 6 } }, y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 } } } }
                                    }
                                });
                            }
                        }"></canvas>
                    </div>
                </div>

                {{-- Teacher volume reads day by day: the window sets the bars,
                     the date dropdown reports one of those days on its own. --}}
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">Teacher Attendance Volume</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Per day · pick a date for its numbers</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <select wire:model.live="teacherAttFilter"
                                class="text-xs bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-400">
                                <option value="7">Last 7 days</option>
                                <option value="14">Last 14 days</option>
                                <option value="30">Last 30 days</option>
                            </select>
                            <select wire:model.live="teacherAttDate"
                                class="text-xs bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-400">
                                @foreach (array_reverse($teacherDays) as $day)
                                    <option value="{{ $day['date'] }}">{{ $day['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="h-56" wire:key="tch-bar-{{ $teacherAttFilter }}">
                        <canvas x-data="{
                            init() {
                                new Chart(this.$el.getContext('2d'), {
                                    type: 'bar',
                                    data: {
                                        labels: @js($teacherDailyAttendance['labels'] ?? []),
                                        datasets: [
                                            { label: 'Present', data: @js($teacherDailyAttendance['present'] ?? []), backgroundColor: 'rgba(59,130,246,0.75)', borderRadius: 3, borderSkipped: false, maxBarThickness: 26 },
                                            { label: 'Absent', data: @js($teacherDailyAttendance['absent'] ?? []), backgroundColor: 'rgba(245,158,11,0.65)', borderRadius: 3, borderSkipped: false, maxBarThickness: 26 }
                                        ]
                                    },
                                    options: {
                                        responsive: true, maintainAspectRatio: false,
                                        plugins: { legend: { display: false } },
                                        scales: { x: { grid: { display: false }, ticks: { font: { size: 9 }, maxRotation: 0, autoSkipPadding: 6 } }, y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 } } } }
                                    }
                                });
                            }
                        }"></canvas>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-[11px] text-gray-400 mb-2">{{ $tDay['label'] ?? '—' }}</p>
                        <div class="grid grid-cols-4 gap-2 text-center">
                            <div><p class="text-base font-semibold text-blue-600 tabular-nums">{{ $tDay['present'] ?? 0 }}</p><p class="text-[10px] text-gray-400 uppercase">Present</p></div>
                            <div><p class="text-base font-semibold text-red-500 tabular-nums">{{ $tDay['absent'] ?? 0 }}</p><p class="text-[10px] text-gray-400 uppercase">Absent</p></div>
                            <div><p class="text-base font-semibold text-amber-500 tabular-nums">{{ $tDay['half'] ?? 0 }}</p><p class="text-[10px] text-gray-400 uppercase">Half</p></div>
                            <div><p class="text-base font-semibold text-gray-800 tabular-nums">{{ $tDay['pct'] ?? 0 }}%</p><p class="text-[10px] text-gray-400 uppercase">Rate</p></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Class-wise ranking --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="mb-4">
                    <h3 class="text-sm font-semibold text-gray-800">Class-wise Attendance Ranking</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Average attendance % over the last 30 days</p>
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
                    <div class="text-center py-10 text-gray-400 text-sm">No attendance recorded in the last 30 days.</div>
                @endif
            </div>
        </section>

        {{-- ═══════════════════════ PERFORMANCE ═══════════════════════ --}}
        <section class="space-y-4">
            <x-admin.section-heading title="Student Performance" subtitle="Top achievers and students who need attention" />

            <div class="flex flex-wrap items-center gap-2">
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

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                {{-- Top performers --}}
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-800">Top Performers</h3>
                    </div>
                    @if (count($topStudents))
                        <div class="divide-y divide-gray-100">
                            @foreach ($topStudents as $student)
                                <div class="flex items-center gap-3 px-5 py-3">
                                    <span class="w-5 text-xs text-gray-300 tabular-nums flex-shrink-0">{{ $student['rank'] }}</span>
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
                                    <span class="text-sm font-semibold text-emerald-600 flex-shrink-0 tabular-nums">{{ $student['score'] }}%</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-10 text-gray-400 text-sm">No student data for the selected filters.</div>
                    @endif
                </div>

                {{-- Needs attention --}}
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-800">Needs Attention</h3>
                    </div>
                    @if (count($lowPerformers))
                        <div class="divide-y divide-gray-100">
                            @foreach ($lowPerformers as $student)
                                <div class="flex items-center gap-3 px-5 py-3">
                                    <div class="w-9 h-9 rounded-full bg-red-50 text-red-500 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                        {{ strtoupper(substr($student['name'], 0, 1)) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate">{{ $student['name'] }}</p>
                                        <p class="text-xs text-gray-400">{{ $student['class'] }} · {{ $student['section'] }}</p>
                                    </div>
                                    <span class="text-sm font-semibold text-red-500 flex-shrink-0 tabular-nums">{{ $student['score'] }}%</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-10 text-gray-400 text-sm">No low-attendance students for the selected filters.</div>
                    @endif
                </div>
            </div>
        </section>

        {{-- ═══════════════════════ ADMISSIONS & ENQUIRIES ═══════════════════════ --}}
        <section class="space-y-4">
            <x-admin.section-heading title="Admissions &amp; Enquiries" subtitle="Enrolment growth and lead response" />

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                {{-- Admissions trend --}}
                <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">Admissions Trend</h3>
                            <p class="text-xs text-gray-400 mt-0.5">
                                <span class="font-medium text-gray-600 tabular-nums">{{ number_format($admissionsTotal) }}</span>
                                admission{{ $admissionsTotal === 1 ? '' : 's' }} · counted on admission date
                            </p>
                        </div>
                        <select wire:model.live="admissionYear"
                            class="text-xs bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-violet-400">
                            @foreach ($admissionYears as $y)
                                <option value="{{ $y }}">Apr {{ $y }} – Mar {{ $y + 1 }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="h-56" wire:key="adm-trend-{{ $admissionYear }}">
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
                                        plugins: { legend: { display: false } },
                                        scales: { x: { grid: { display: false }, ticks: { font: { size: 9 }, maxRotation: 0 } }, y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, precision: 0 } } }
                                    }
                                });
                            }
                        }"></canvas>
                    </div>
                    @if ($admissionsTotal === 0)
                        <p class="mt-3 text-xs text-gray-400">
                            Nothing recorded for this school year — try an earlier one. Students with no admission date are counted on the day their record was created.
                        </p>
                    @endif
                </div>

                {{-- Enquiry funnel + recent --}}
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h3 class="text-sm font-semibold text-gray-800 mb-4">Enquiry Funnel</h3>
                    <div class="grid grid-cols-3 gap-2 text-center">
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
                    <div class="mt-4">
                        <div class="flex justify-between text-xs mb-1.5"><span class="text-gray-400">Response rate</span><span class="font-semibold text-gray-700">{{ $enquiryStats['rate'] ?? 0 }}%</span></div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5"><div class="h-1.5 rounded-full bg-emerald-500" style="width: {{ $enquiryStats['rate'] ?? 0 }}%"></div></div>
                    </div>
                    <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wide mt-5 mb-1">Latest enquiries</p>
                    <div class="divide-y divide-gray-100">
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
        </section>

        {{-- ═══════════════════════ FEE ═══════════════════════ --}}
        <section class="space-y-4">
            <x-admin.section-heading title="Fee" subtitle="Collection performance and class-wise recovery" />

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
                            <p class="text-xs text-gray-400 mt-0.5">Collected vs remaining</p>
                        </div>
                        <div class="flex items-center gap-3 text-[11px] text-gray-400">
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-sky-500 inline-block"></span> Collected</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-orange-400 inline-block"></span> Remaining</span>
                        </div>
                    </div>
                    @if (count($transportRouteData['labels'] ?? []))
                        <div class="h-64" wire:ignore wire:key="transport-route-chart">
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
                                                y: { grid: { display: false }, ticks: { font: { size: 10 } } }
                                            }
                                        }
                                    });
                                }
                            }"></canvas>
                        </div>
                    @else
                        <div class="text-center py-16 text-gray-400 text-sm">No transport routes with riders yet.</div>
                    @endif
                </div>
            </div>
        </section>

        {{-- ═══════════════════════ OPERATIONS ═══════════════════════ --}}
        <section class="space-y-4">
            <x-admin.section-heading title="Operations" subtitle="Substitute arrangements and announcements" />

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

            {{-- Announcements --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-800">Recent Announcements</h3>
                </div>
                @if (count($announcements))
                    <div class="divide-y divide-gray-100">
                        @foreach ($announcements as $ann)
                            @php
                                $tone = [
                                    'general' => 'bg-blue-50 text-blue-700',
                                    'urgent'  => 'bg-red-50 text-red-700',
                                    'event'   => 'bg-purple-50 text-purple-700',
                                ][$ann['type']] ?? 'bg-gray-100 text-gray-600';
                            @endphp
                            <div class="px-5 py-3.5">
                                <div class="flex items-center gap-2 flex-wrap">
                                    @if ($ann['pinned'])
                                        <span class="text-[10px] font-medium text-gray-400 uppercase tracking-wide">Pinned</span>
                                    @endif
                                    <p class="font-medium text-sm text-gray-800">{{ $ann['title'] }}</p>
                                    <span class="px-2 py-0.5 text-[10px] rounded-full {{ $tone }} font-medium capitalize">{{ $ann['type'] }}</span>
                                </div>
                                @if ($ann['body'])
                                    <p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ Str::limit($ann['body'], 120) }}</p>
                                @endif
                                <p class="text-xs text-gray-400 mt-1.5">{{ $ann['time'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-10 text-gray-400 text-sm">No announcements yet.</div>
                @endif
            </div>
        </section>
    </div>
</div>
