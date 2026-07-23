<div class="min-h-screen bg-slate-50">

    {{-- ══════════════════════════════════════════════════════════
     DASHBOARD HEADER  (sticky app-style bar)
    ══════════════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="w-11 h-11 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center flex-shrink-0 shadow-sm">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    </span>
                    <div>
                        <h1 class="text-lg sm:text-xl font-bold text-gray-900">Analytics Dashboard</h1>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gray-50 border border-gray-200 text-sm text-gray-600 whitespace-nowrap">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        {{ now()->format('l, d M Y') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 text-xs font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                        {{ number_format($statsData['totalStudents'] ?? 0) }} Students
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        {{ $kpis['student_rate'] ?? 0 }}% Attendance
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 text-amber-700 text-xs font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        {{ $kpis['collect_rate'] ?? 0 }}% Collected
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════
     DASHBOARD BODY
    ══════════════════════════════════════════════════════════ --}}
    <div class="p-4 sm:p-6 space-y-6">

    {{-- ─────────────── SECTION: KEY METRICS ─────────────── --}}
    <x-admin.section-heading title="Key Metrics" subtitle="Live performance indicators with day-over-day movement" color="indigo"
        icon="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />

    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        {{-- Student attendance rate + delta --}}
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4">
            <p class="text-xs text-slate-400">Student Attendance</p>
            <p class="text-2xl font-bold text-slate-800 mt-1">{{ $kpis['student_rate'] ?? 0 }}%</p>
            @php $d = $kpis['student_delta'] ?? 0; @endphp
            <p class="mt-1 inline-flex items-center gap-1 text-xs font-medium {{ $d > 0 ? 'text-emerald-600' : ($d < 0 ? 'text-red-500' : 'text-slate-400') }}">
                @if ($d > 0)
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" /></svg>
                @elseif ($d < 0)
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                @endif
                {{ abs($d) }}% vs yesterday
            </p>
        </div>
        {{-- Teacher attendance --}}
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4">
            <p class="text-xs text-slate-400">Teacher Attendance</p>
            <p class="text-2xl font-bold text-slate-800 mt-1">{{ $kpis['teacher_rate'] ?? 0 }}%</p>
            <p class="mt-1 text-xs text-slate-400">Today</p>
        </div>
        {{-- Collection rate --}}
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4">
            <p class="text-xs text-slate-400">Fee Collection</p>
            <p class="text-2xl font-bold text-slate-800 mt-1">{{ $kpis['collect_rate'] ?? 0 }}%</p>
            <p class="mt-1 text-xs text-slate-400">of total expected</p>
        </div>
        {{-- Avg daily collection --}}
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4">
            <p class="text-xs text-slate-400">Avg / Day</p>
            <p class="text-2xl font-bold text-slate-800 mt-1">₹{{ number_format($kpis['avg_daily'] ?? 0, 0) }}</p>
            <p class="mt-1 text-xs text-slate-400">last 30 days</p>
        </div>
        {{-- Unpaid students --}}
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4">
            <p class="text-xs text-slate-400">Unpaid Students</p>
            <p class="text-2xl font-bold text-red-500 mt-1">{{ number_format($kpis['unpaid_students'] ?? 0) }}</p>
            <p class="mt-1 text-xs text-slate-400">no payment yet</p>
        </div>
        {{-- New admissions --}}
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4">
            <p class="text-xs text-slate-400">New Admissions</p>
            <p class="text-2xl font-bold text-violet-600 mt-1">{{ number_format($kpis['new_admissions'] ?? 0) }}</p>
            <p class="mt-1 text-xs text-slate-400">last 30 days</p>
        </div>
    </div>

    {{-- ─────────────── SECTION: ATTENDANCE ANALYTICS ─────────────── --}}
    <x-admin.section-heading title="Attendance Analytics" subtitle="Year-long trends, split & class-wise performance" color="emerald"
        icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />

    {{-- Attendance % trend (student vs teacher) + student split --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="font-bold text-slate-800 text-base">Attendance Rate Trend</h2>
                    <p class="text-xs text-slate-400">Monthly % · academic year Apr – Mar</p>
                </div>
                <div class="flex items-center gap-3 text-xs">
                    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-emerald-500 inline-block"></span> Students</span>
                    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-blue-500 inline-block"></span> Teachers</span>
                </div>
            </div>
            <div class="h-64" wire:key="att-trend">
                <canvas x-data="{
                    init() {
                        new Chart(this.$el.getContext('2d'), {
                            type: 'line',
                            data: {
                                labels: @js($attendanceTrendPct['labels'] ?? []),
                                datasets: [
                                    { label: 'Students', data: @js($attendanceTrendPct['student'] ?? []), borderColor: 'rgb(16,185,129)', backgroundColor: 'rgba(16,185,129,0.12)', fill: true, tension: 0.35, borderWidth: 2, pointRadius: 3 },
                                    { label: 'Teachers', data: @js($attendanceTrendPct['teacher'] ?? []), borderColor: 'rgb(59,130,246)', backgroundColor: 'rgba(59,130,246,0.06)', fill: false, tension: 0.35, borderWidth: 2, pointRadius: 3 }
                                ]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                                    y: { beginAtZero: true, max: 100, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 10 }, callback: (v) => v + '%' } }
                                }
                            }
                        });
                    }
                }"></canvas>
            </div>
        </div>

        {{-- Student split --}}
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold text-slate-800 text-base">Student Split</h2>
                <select wire:model.live="attendanceFilter"
                    class="text-xs border border-slate-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-indigo-400 bg-white">
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
                            options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12 } } } }
                        });
                    }
                }"></canvas>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <div class="text-center p-2 bg-emerald-50 rounded-lg">
                    <p class="text-xl font-bold text-emerald-600">{{ $studentPieData['present'] ?? 0 }}</p>
                    <p class="text-xs text-slate-500">Present</p>
                </div>
                <div class="text-center p-2 bg-red-50 rounded-lg">
                    <p class="text-xl font-bold text-red-500">{{ $studentPieData['absent'] ?? 0 }}</p>
                    <p class="text-xs text-slate-500">Absent</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Student & Teacher monthly volume bars --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold text-slate-800 text-base">Student Attendance Volume</h2>
                <div class="flex items-center gap-3 text-xs">
                    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-emerald-500 inline-block"></span> Present</span>
                    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-red-400 inline-block"></span> Absent</span>
                </div>
            </div>
            <div class="h-56" wire:key="stu-bar">
                <canvas x-data="{
                    init() {
                        new Chart(this.$el.getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: @js($attendanceMonths),
                                datasets: [
                                    { label: 'Present', data: @js($studentMonthlyAttendance['present'] ?? []), backgroundColor: 'rgba(16,185,129,0.75)', borderRadius: 4, borderSkipped: false },
                                    { label: 'Absent', data: @js($studentMonthlyAttendance['absent'] ?? []), backgroundColor: 'rgba(239,68,68,0.65)', borderRadius: 4, borderSkipped: false }
                                ]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: { x: { grid: { display: false }, ticks: { font: { size: 9 } } }, y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 10 } } } }
                            }
                        });
                    }
                }"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <h2 class="font-bold text-slate-800 text-base">Teacher Attendance Volume</h2>
                </div>
                <select wire:model.live="teacherAttFilter"
                    class="text-xs border border-slate-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-blue-400 bg-white">
                    <option value="7">Last 7 days</option>
                    <option value="14">Last 14 days</option>
                    <option value="30">Last 30 days</option>
                </select>
            </div>
            <div class="h-56" wire:key="tch-bar">
                <canvas x-data="{
                    init() {
                        new Chart(this.$el.getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: @js($attendanceMonths),
                                datasets: [
                                    { label: 'Present', data: @js($teacherMonthlyAttendance['present'] ?? []), backgroundColor: 'rgba(59,130,246,0.75)', borderRadius: 4, borderSkipped: false },
                                    { label: 'Absent', data: @js($teacherMonthlyAttendance['absent'] ?? []), backgroundColor: 'rgba(245,158,11,0.65)', borderRadius: 4, borderSkipped: false }
                                ]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: { x: { grid: { display: false }, ticks: { font: { size: 9 } } }, y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 10 } } } }
                            }
                        });
                    }
                }"></canvas>
            </div>
            <div class="mt-2 grid grid-cols-2 gap-2 text-center">
                <div class="p-2 bg-blue-50 rounded-lg"><span class="text-lg font-bold text-blue-600">{{ $teacherPieData['present'] ?? 0 }}</span> <span class="text-xs text-slate-500">Present ({{ $teacherAttFilter }}d)</span></div>
                <div class="p-2 bg-amber-50 rounded-lg"><span class="text-lg font-bold text-amber-500">{{ $teacherPieData['absent'] ?? 0 }}</span> <span class="text-xs text-slate-500">Absent ({{ $teacherAttFilter }}d)</span></div>
            </div>
        </div>
    </div>

    {{-- Class-wise attendance ranking --}}
    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="font-bold text-slate-800 text-base">Class-wise Attendance Ranking</h2>
                <p class="text-xs text-slate-400">Average attendance % over the last 30 days</p>
            </div>
        </div>
        @if (count($classAttendanceRank))
            <div class="space-y-3">
                @foreach ($classAttendanceRank as $idx => $row)
                    @php
                        $bar = $row['pct'] >= 85 ? 'bg-emerald-500' : ($row['pct'] >= 70 ? 'bg-amber-500' : 'bg-red-500');
                    @endphp
                    <div class="flex items-center gap-3">
                        <span class="w-6 text-xs font-semibold text-slate-400 flex-shrink-0">#{{ $idx + 1 }}</span>
                        <span class="w-24 sm:w-32 text-sm font-medium text-slate-700 truncate flex-shrink-0">{{ $row['name'] }}</span>
                        <div class="flex-1 bg-slate-100 rounded-full h-2.5 min-w-0">
                            <div class="h-2.5 rounded-full {{ $bar }}" style="width: {{ $row['pct'] }}%"></div>
                        </div>
                        <span class="w-12 text-right text-sm font-bold text-slate-700 flex-shrink-0">{{ $row['pct'] }}%</span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-10 text-slate-400 text-sm">No attendance recorded in the last 30 days.</div>
        @endif
    </div>

    {{-- ─────────────── SECTION: PERFORMANCE ─────────────── --}}
    <x-admin.section-heading title="Student Performance" subtitle="Top achievers & students who need attention" color="amber"
        icon="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />

    {{-- Performer filters --}}
    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4">
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-1.5 text-sm font-semibold text-slate-700">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                Filter by:
            </div>
            <select wire:model.live="performerClass"
                class="text-xs bg-white border border-slate-200 rounded-md px-3 py-1.5 text-slate-700 focus:ring-2 focus:ring-indigo-400">
                <option value="">All Classes</option>
                @foreach ($standards as $std)
                    <option value="{{ $std->id }}">{{ $std->name }}</option>
                @endforeach
            </select>
            @if ($sections)
                <select wire:model.live="performerSection"
                    class="text-xs bg-white border border-slate-200 rounded-md px-3 py-1.5 text-slate-700 focus:ring-2 focus:ring-indigo-400">
                    <option value="">All Sections</option>
                    @foreach ($sections as $sec)
                        <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        {{-- Top performers --}}
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <h2 class="font-bold text-slate-800 text-base mb-4">🏆 Top Performers</h2>
            @if (count($topStudents))
                <div class="space-y-3">
                    @foreach ($topStudents as $student)
                        @php
                            $medal = ['1' => '🥇', '2' => '🥈', '3' => '🥉'][$student['rank']] ?? '🎖';
                        @endphp
                        <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50">
                            <span class="text-xl flex-shrink-0">{{ $medal }}</span>
                            <div class="w-10 h-10 rounded-full overflow-hidden bg-slate-200 flex-shrink-0">
                                @if ($student['photo'])
                                    <img src="{{ $student['photo'] }}" alt="{{ $student['name'] }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-slate-500 font-bold">{{ strtoupper(substr($student['name'], 0, 1)) }}</div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-slate-800 truncate">{{ $student['name'] }}</p>
                                <p class="text-xs text-slate-400">{{ $student['class'] }} – {{ $student['section'] }}</p>
                            </div>
                            <span class="text-sm font-bold text-emerald-600 flex-shrink-0">{{ $student['score'] }}%</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-10 text-slate-400 text-sm">No student data for the selected filters.</div>
            @endif
        </div>

        {{-- Needs attention --}}
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <h2 class="font-bold text-slate-800 text-base mb-4">⚠️ Needs Attention</h2>
            @if (count($lowPerformers))
                <div class="space-y-3">
                    @foreach ($lowPerformers as $student)
                        <div class="flex items-center gap-3 p-3 rounded-xl bg-red-50/60">
                            <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center font-bold flex-shrink-0">
                                {{ strtoupper(substr($student['name'], 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-slate-800 truncate">{{ $student['name'] }}</p>
                                <p class="text-xs text-slate-400">{{ $student['class'] }} – {{ $student['section'] }}</p>
                            </div>
                            <span class="text-sm font-bold text-red-500 flex-shrink-0">{{ $student['score'] }}%</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-10 text-slate-400 text-sm">No low-attendance students for the selected filters.</div>
            @endif
        </div>
    </div>

    {{-- ─────────────── SECTION: ADMISSIONS & ENQUIRIES ─────────────── --}}
    <x-admin.section-heading title="Admissions & Enquiries" subtitle="Enrolment growth and lead response funnel" color="violet"
        icon="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {{-- Admissions trend --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="font-bold text-slate-800 text-base">Admissions Trend</h2>
                    <p class="text-xs text-slate-400">New enrolments per month · Apr – Mar</p>
                </div>
            </div>
            <div class="h-56" wire:key="adm-trend">
                <canvas x-data="{
                    init() {
                        new Chart(this.$el.getContext('2d'), {
                            type: 'line',
                            data: {
                                labels: @js($admissionsTrend['labels'] ?? []),
                                datasets: [{ label: 'Admissions', data: @js($admissionsTrend['data'] ?? []), borderColor: 'rgb(139,92,246)', backgroundColor: 'rgba(139,92,246,0.12)', fill: true, tension: 0.35, borderWidth: 2, pointRadius: 3 }]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: { x: { grid: { display: false }, ticks: { font: { size: 10 } } }, y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 10 }, precision: 0 } } }
                            }
                        });
                    }
                }"></canvas>
            </div>
        </div>

        {{-- Enquiry funnel + recent --}}
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <h2 class="font-bold text-slate-800 text-base mb-4">📬 Enquiry Funnel</h2>
            <div class="grid grid-cols-3 gap-2 mb-4">
                <div class="text-center p-2.5 bg-indigo-50 rounded-lg">
                    <p class="text-xl font-bold text-indigo-600">{{ $enquiryStats['total'] ?? 0 }}</p>
                    <p class="text-[11px] text-slate-500">Total</p>
                </div>
                <div class="text-center p-2.5 bg-emerald-50 rounded-lg">
                    <p class="text-xl font-bold text-emerald-600">{{ $enquiryStats['responded'] ?? 0 }}</p>
                    <p class="text-[11px] text-slate-500">Responded</p>
                </div>
                <div class="text-center p-2.5 bg-amber-50 rounded-lg">
                    <p class="text-xl font-bold text-amber-600">{{ $enquiryStats['pending'] ?? 0 }}</p>
                    <p class="text-[11px] text-slate-500">Pending</p>
                </div>
            </div>
            <div class="mb-4">
                <div class="flex justify-between text-xs mb-1"><span class="text-slate-500">Response rate</span><span class="font-semibold text-slate-700">{{ $enquiryStats['rate'] ?? 0 }}%</span></div>
                <div class="w-full bg-slate-100 rounded-full h-2"><div class="h-2 rounded-full bg-emerald-500" style="width: {{ $enquiryStats['rate'] ?? 0 }}%"></div></div>
            </div>
            <p class="text-[11px] font-semibold text-slate-400 uppercase mb-2">Latest enquiries</p>
            @forelse($adminEnquiries as $enq)
                <div class="flex items-center gap-2.5 py-2 {{ !$loop->last ? 'border-b border-slate-50' : '' }}">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0 font-bold text-indigo-700 text-xs">
                        {{ strtoupper(substr($enq['name'], 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-800 truncate">{{ $enq['name'] }}</p>
                        <p class="text-xs text-slate-400 truncate">{{ $enq['time'] }}</p>
                    </div>
                    <span class="flex-shrink-0 px-2 py-0.5 text-[10px] rounded-full font-medium {{ $enq['status'] === 'Responded' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">{{ $enq['status'] }}</span>
                </div>
            @empty
                <p class="text-center text-slate-400 text-sm py-4">No enquiries yet.</p>
            @endforelse
        </div>
    </div>

    {{-- ─────────────── SECTION: FEE ANALYTICS ─────────────── --}}
    <x-admin.section-heading title="Fee Analytics" subtitle="Collection performance & class-wise recovery" color="blue"
        icon="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $feeCards = [
                ['label' => 'Total Fee',     'value' => '₹' . number_format($feeStats['totalFee'] ?? 0),     'ic' => 'text-indigo-600', 'bg' => 'bg-indigo-50'],
                ['label' => 'Collected',     'value' => '₹' . number_format($feeStats['collected'] ?? 0),    'ic' => 'text-emerald-600','bg' => 'bg-emerald-50'],
                ['label' => 'Remaining',     'value' => '₹' . number_format($feeStats['remaining'] ?? 0),    'ic' => 'text-red-500',    'bg' => 'bg-red-50'],
                ['label' => 'Transport Fee', 'value' => '₹' . number_format($feeStats['transportFee'] ?? 0), 'ic' => 'text-amber-600',  'bg' => 'bg-amber-50'],
            ];
        @endphp
        @foreach ($feeCards as $fc)
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4">
                <p class="text-xs text-slate-400">{{ $fc['label'] }}</p>
                <p class="text-lg font-bold text-slate-800 mt-1">{{ $fc['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {{-- Fee by class chart --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="font-bold text-slate-800 text-base">Fee Collection by Class</h2>
                    <p class="text-xs text-slate-400">Collected vs remaining</p>
                </div>
                <div class="flex items-center gap-3 text-xs">
                    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-emerald-500 inline-block"></span> Collected</span>
                    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-red-400 inline-block"></span> Remaining</span>
                </div>
            </div>
            <div class="h-64" wire:key="fee-class-chart">
                <canvas x-data="{
                    init() {
                        const collected = @js($feeClassData['collected'] ?? []);
                        const remaining = @js($feeClassData['remaining'] ?? []);
                        new Chart(this.$el.getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: @js($feeClassData['labels'] ?? []),
                                datasets: [
                                    { label: 'Collected', data: collected, backgroundColor: 'rgba(16,185,129,0.75)', borderRadius: 4, borderSkipped: false },
                                    { label: 'Remaining', data: remaining, backgroundColor: 'rgba(239,68,68,0.6)', borderRadius: 4, borderSkipped: false }
                                ]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, callback: (v) => '₹' + v.toLocaleString('en-IN') } }
                                }
                            }
                        });
                    }
                }"></canvas>
            </div>
        </div>

        {{-- Collection rate by class --}}
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <h2 class="font-bold text-slate-800 text-base mb-4">Recovery Rate by Class</h2>
            @if (count($feeClassRate))
                <div class="space-y-3 max-h-72 overflow-y-auto pr-1">
                    @foreach ($feeClassRate as $row)
                        @php $bar = $row['pct'] >= 80 ? 'bg-emerald-500' : ($row['pct'] >= 50 ? 'bg-amber-500' : 'bg-red-500'); @endphp
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="font-medium text-slate-700 truncate pr-2">{{ $row['name'] }}</span>
                                <span class="font-semibold text-slate-700 flex-shrink-0">{{ $row['pct'] }}%</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2"><div class="h-2 rounded-full {{ $bar }}" style="width: {{ $row['pct'] }}%"></div></div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-10 text-slate-400 text-sm">No fee data available.</div>
            @endif
        </div>
    </div>

    {{-- ─────────────── SECTION: OPERATIONS ─────────────── --}}
    <x-admin.section-heading title="Operations" subtitle="Substitute arrangements & announcements" color="rose"
        icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />

    {{-- Arrangement --}}
    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
        <div class="flex items-center gap-2 mb-4">
            <h2 class="font-bold text-slate-800 text-base">🔄 Today's Absent Teachers — Arrangement</h2>
            @if (session('arrangement_saved'))
                <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Saved!</span>
            @endif
        </div>

        @if (count($arrangements))
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                            <th class="px-4 py-2.5 text-left rounded-tl-lg">Class</th>
                            <th class="px-4 py-2.5 text-left">Section</th>
                            <th class="px-4 py-2.5 text-left">Absent Teacher</th>
                            <th class="px-4 py-2.5 text-left">Time</th>
                            <th class="px-4 py-2.5 text-left">Available Teacher</th>
                            <th class="px-4 py-2.5 text-left rounded-tr-lg">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach ($arrangements as $arr)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-4 py-3 font-medium text-slate-800">{{ $arr['class'] }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $arr['section'] }}</td>
                                <td class="px-4 py-3 text-red-600">{{ $arr['absent_teacher'] }}</td>
                                <td class="px-4 py-3 text-slate-500 text-xs">{{ $arr['time'] }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <select wire:model="selectedTeachers.{{ $arr['id'] }}"
                                            class="text-xs border border-slate-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-indigo-400 bg-white min-w-32">
                                            <option value="">Select Teacher</option>
                                            @foreach ($availableTeachers as $teacher)
                                                <option value="{{ $teacher['id'] }}">{{ $teacher['name'] }}</option>
                                            @endforeach
                                        </select>
                                        <button wire:click="saveArrangement({{ $arr['id'] }})"
                                            class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-2.5 py-1.5 rounded-lg transition-colors">Assign</button>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 text-xs rounded-full font-medium {{ $arr['status'] === 'assigned' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ ucfirst($arr['status']) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-10 text-slate-400 text-sm">
                <svg class="w-10 h-10 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                All teachers are present today — no arrangements needed.
            </div>
        @endif
    </div>

    {{-- Announcements --}}
    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
        <h2 class="font-bold text-slate-800 text-base mb-4">📢 Recent Announcements</h2>
        @if (count($announcements))
            <div class="space-y-3">
                @foreach ($announcements as $ann)
                    @php
                        $typeColors = [
                            'general' => 'bg-blue-50 border-blue-200 text-blue-700',
                            'urgent'  => 'bg-red-50 border-red-200 text-red-700',
                            'event'   => 'bg-purple-50 border-purple-200 text-purple-700',
                        ];
                        $tc = $typeColors[$ann['type']] ?? $typeColors['general'];
                    @endphp
                    <div class="border {{ $tc }} rounded-xl p-4 flex items-start gap-3">
                        <span class="flex-shrink-0 text-base">{{ $ann['pinned'] ? '📌' : '📣' }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="font-semibold text-sm text-slate-800">{{ $ann['title'] }}</p>
                                <span class="px-2 py-0.5 text-xs rounded-full {{ $tc }} border font-medium capitalize">{{ $ann['type'] }}</span>
                            </div>
                            @if ($ann['body'])
                                <p class="text-xs text-slate-500 mt-1 leading-relaxed">{{ Str::limit($ann['body'], 120) }}</p>
                            @endif
                            <p class="text-xs text-slate-400 mt-1.5">{{ $ann['time'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-10 text-slate-400 text-sm">No announcements yet.</div>
        @endif
    </div>

    </div>
</div>
