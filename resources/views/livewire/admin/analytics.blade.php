<div class="p-5 bg-slate-50 min-h-screen space-y-6">

    {{-- ══════════════════════════════════════════════════════════
     PAGE HEADER
══════════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 sm:p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex items-start gap-3">
                <span class="w-11 h-11 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center flex-shrink-0 shadow-sm">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                </span>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 tracking-tight">School Analytics</h1>
                    <p class="text-slate-500 text-sm mt-0.5">Attendance, fees & activity insights · {{ now()->format('l, d F Y') }}</p>
                </div>
            </div>
            {{-- Header summary chips --}}
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 text-xs font-medium">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                    {{ number_format($statsData['totalStudents'] ?? 0) }} Students
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-medium">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    {{ number_format($statsData['presentToday'] ?? 0) }} Present Today
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 text-amber-700 text-xs font-medium">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    ₹{{ number_format($feeStats['collected'] ?? 0) }} Collected
                </span>
            </div>
        </div>
    </div>

    {{-- ─────────────── SECTION: OVERVIEW ─────────────── --}}
    <x-admin.section-heading title="Overview" subtitle="Key metrics at a glance" color="indigo"
        icon="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />

    {{-- ══════════════════════════════════════════════════════════
     1. STATS CARDS
══════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">

        @php
            $cards = [
                [
                    'label' => 'Total Students',
                    'value' => number_format($statsData['totalStudents']),
                    'icon' =>
                        'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                    'bg' => 'bg-indigo-50',
                    'ic' => 'text-indigo-600',
                ],
                [
                    'label' => 'Active Students',
                    'value' => number_format($statsData['activeStudents']),
                    'icon' =>
                        'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                    'bg' => 'bg-emerald-50',
                    'ic' => 'text-emerald-600',
                ],
                [
                    'label' => 'Present Today',
                    'value' => number_format($statsData['presentToday']),
                    'icon' => 'M5 13l4 4L19 7',
                    'bg' => 'bg-green-50',
                    'ic' => 'text-green-600',
                ],
                [
                    'label' => 'Absent Today',
                    'value' => number_format($statsData['absentToday']),
                    'icon' => 'M6 18L18 6M6 6l12 12',
                    'bg' => 'bg-red-50',
                    'ic' => 'text-red-500',
                ],
                [
                    'label' => 'New Admissions',
                    'value' => number_format($statsData['newAdmissions']),
                    'icon' => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
                    'bg' => 'bg-violet-50',
                    'ic' => 'text-violet-600',
                ],
            ];
        @endphp

        @foreach ($cards as $card)
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 flex items-center gap-3">
                <div class="p-2.5 rounded-xl {{ $card['bg'] }} flex-shrink-0">
                    <svg class="h-5 w-5 {{ $card['ic'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-slate-500 truncate">{{ $card['label'] }}</p>
                    <p class="text-xl font-bold text-slate-800">{{ $card['value'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ─────────────── SECTION: ATTENDANCE ANALYTICS ─────────────── --}}
    <x-admin.section-heading title="Attendance Analytics" subtitle="Students & teachers · academic year and recent trends" color="emerald"
        icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />

    {{-- ══════════════════════════════════════════════════════════
     2. STUDENT ATTENDANCE  (bar chart Apr–Mar + pie last N days)
══════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Bar Chart --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="font-bold text-slate-800 text-base">Student Attendance</h2>
                    <p class="text-xs text-slate-400">Academic year: April – March</p>
                </div>
                <div class="flex items-center gap-3 text-xs">
                    <span class="flex items-center gap-1"><span
                            class="w-3 h-3 rounded-sm bg-emerald-500 inline-block"></span> Present</span>
                    <span class="flex items-center gap-1"><span
                            class="w-3 h-3 rounded-sm bg-red-400 inline-block"></span> Absent</span>
                </div>
            </div>
            <div class="h-64" wire:ignore>
                <canvas id="studentAttendanceBar" x-data="{
                    chart: null,
                    init() {
                        const ctx = this.$el.getContext('2d');
                        this.chart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: @js($attendanceMonths),
                                datasets: [
                                    { label: 'Present', data: @js($studentMonthlyAttendance['present'] ?? []), backgroundColor: 'rgba(16,185,129,0.75)', borderRadius: 4, borderSkipped: false },
                                    { label: 'Absent', data: @js($studentMonthlyAttendance['absent'] ?? []), backgroundColor: 'rgba(239,68,68,0.65)', borderRadius: 4, borderSkipped: false }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 10 } } }
                                }
                            }
                        });
                    }
                }"></canvas>
            </div>
        </div>

        {{-- Pie Chart + Filter --}}
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold text-slate-800 text-base">Attendance Split</h2>
                <select wire:model.live="attendanceFilter"
                    class="text-xs border border-slate-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-indigo-400 bg-white">
                    <option value="7">Last 7 days</option>
                    <option value="14">Last 14 days</option>
                    <option value="30">Last 30 days</option>
                </select>
            </div>
            <div class="h-44" wire:ignore>
                <canvas id="studentAttPie" x-data="{
                    chart: null,
                    init() {
                        const ctx = this.$el.getContext('2d');
                        this.chart = new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: ['Present', 'Absent'],
                                datasets: [{ data: [@js($studentPieData['present'] ?? 0), @js($studentPieData['absent'] ?? 0)], backgroundColor: ['rgba(16,185,129,0.8)', 'rgba(239,68,68,0.7)'], borderWidth: 0, hoverOffset: 6 }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '65%',
                                plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12 } } }
                            }
                        });
                    }
                }"></canvas>
            </div>
            {{-- Numbers below pie --}}
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

    {{-- ══════════════════════════════════════════════════════════
     3. TEACHER ATTENDANCE
══════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="font-bold text-slate-800 text-base">Teacher Attendance</h2>
                    <p class="text-xs text-slate-400">Academic year: April – March</p>
                </div>
                <div class="flex items-center gap-3 text-xs">
                    <span class="flex items-center gap-1"><span
                            class="w-3 h-3 rounded-sm bg-blue-500 inline-block"></span> Present</span>
                    <span class="flex items-center gap-1"><span
                            class="w-3 h-3 rounded-sm bg-amber-400 inline-block"></span> Absent</span>
                </div>
            </div>
            <div class="h-64" wire:ignore>
                <canvas id="teacherAttendanceBar" x-data="{
                    chart: null,
                    init() {
                        const ctx = this.$el.getContext('2d');
                        this.chart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: @js($attendanceMonths),
                                datasets: [
                                    { label: 'Present', data: @js($teacherMonthlyAttendance['present'] ?? []), backgroundColor: 'rgba(59,130,246,0.75)', borderRadius: 4, borderSkipped: false },
                                    { label: 'Absent', data: @js($teacherMonthlyAttendance['absent'] ?? []), backgroundColor: 'rgba(245,158,11,0.65)', borderRadius: 4, borderSkipped: false }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 10 } } }
                                }
                            }
                        });
                    }
                }"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold text-slate-800 text-base">Teacher Split</h2>
                <select wire:model.live="teacherAttFilter"
                    class="text-xs border border-slate-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-blue-400 bg-white">
                    <option value="7">Last 7 days</option>
                    <option value="14">Last 14 days</option>
                    <option value="30">Last 30 days</option>
                </select>
            </div>
            <div class="h-44" wire:ignore>
                <canvas id="teacherAttPie" x-data="{
                    chart: null,
                    init() {
                        const ctx = this.$el.getContext('2d');
                        this.chart = new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: ['Present', 'Absent'],
                                datasets: [{ data: [@js($teacherPieData['present'] ?? 0), @js($teacherPieData['absent'] ?? 0)], backgroundColor: ['rgba(59,130,246,0.8)', 'rgba(245,158,11,0.75)'], borderWidth: 0, hoverOffset: 6 }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '65%',
                                plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12 } } }
                            }
                        });
                    }
                }"></canvas>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <div class="text-center p-2 bg-blue-50 rounded-lg">
                    <p class="text-xl font-bold text-blue-600">{{ $teacherPieData['present'] ?? 0 }}</p>
                    <p class="text-xs text-slate-500">Present</p>
                </div>
                <div class="text-center p-2 bg-amber-50 rounded-lg">
                    <p class="text-xl font-bold text-amber-500">{{ $teacherPieData['absent'] ?? 0 }}</p>
                    <p class="text-xs text-slate-500">Absent</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ─────────────── SECTION: PERFORMANCE ─────────────── --}}
    <x-admin.section-heading title="Performance" subtitle="Top attendance performers by class & section" color="amber"
        icon="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />

    {{-- ══════════════════════════════════════════════════════════
     4. BEST PERFORMERS
══════════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
            <div>
                <h2 class="font-bold text-slate-800 text-base">🏆 Best Performers</h2>
                <p class="text-xs text-slate-400">Top 3 students by attendance</p>
            </div>
            {{-- Filters --}}
            <div class="flex items-center gap-2">
                <select wire:model.live="performerClass"
                    class="text-xs border border-slate-200 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-indigo-400 bg-white">
                    <option value="">All Classes</option>
                    @foreach ($standards as $std)
                        <option value="{{ $std->id }}">{{ $std->name }}</option>
                    @endforeach
                </select>
                @if ($sections)
                    <select wire:model.live="performerSection"
                        class="text-xs border border-slate-200 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-indigo-400 bg-white">
                        <option value="">All Sections</option>
                        @foreach ($sections as $sec)
                            <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
        </div>

        @if (count($topStudents))
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @foreach ($topStudents as $student)
                    @php
                        $rankColors = [
                            '1' => [
                                'bg' => 'bg-amber-50',
                                'border' => 'border-amber-300',
                                'badge' => 'bg-amber-400 text-white',
                                'icon' => '🥇',
                            ],
                            '2' => [
                                'bg' => 'bg-slate-50',
                                'border' => 'border-slate-300',
                                'badge' => 'bg-slate-400 text-white',
                                'icon' => '🥈',
                            ],
                            '3' => [
                                'bg' => 'bg-orange-50',
                                'border' => 'border-orange-200',
                                'badge' => 'bg-orange-400 text-white',
                                'icon' => '🥉',
                            ],
                        ];
                        $rc = $rankColors[$student['rank']] ?? $rankColors['3'];
                    @endphp
                    <div
                        class="relative {{ $rc['bg'] }} border-2 {{ $rc['border'] }} rounded-2xl p-5 text-center">
                        {{-- Rank badge --}}
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                            <span class="text-xl">{{ $rc['icon'] }}</span>
                        </div>
                        {{-- Avatar --}}
                        <div
                            class="w-16 h-16 rounded-full mx-auto mb-3 overflow-hidden bg-slate-200 border-2 {{ $rc['border'] }}">
                            @if ($student['photo'])
                                <img src="{{ $student['photo'] }}" alt="{{ $student['name'] }}"
                                    class="w-full h-full object-cover">
                            @else
                                <div
                                    class="w-full h-full flex items-center justify-center text-slate-500 text-xl font-bold">
                                    {{ strtoupper(substr($student['name'], 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <p class="font-bold text-slate-800 text-sm">{{ $student['name'] }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $student['class'] }} – {{ $student['section'] }}
                        </p>
                        <div class="mt-3 bg-white rounded-full px-3 py-1 inline-block">
                            <span class="text-sm font-bold text-indigo-600">{{ $student['score'] }}%</span>
                            <span class="text-xs text-slate-400"> attendance</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-10 text-slate-400 text-sm">No student data found for selected filters.</div>
        @endif
    </div>

    {{-- ─────────────── SECTION: OPERATIONS & COMMUNICATION ─────────────── --}}
    <x-admin.section-heading title="Operations & Communication" subtitle="Enquiries, class-wise attendance, arrangements & announcements" color="blue"
        icon="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />

    {{-- ══════════════════════════════════════════════════════════
     5. ADMIN ENQUIRIES  +  CLASS DISTRIBUTION
══════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- Admin Enquiries --}}
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold text-slate-800 text-base">📬 Contact Enquiries</h2>
                <span class="text-xs text-slate-400">Latest 3</span>
            </div>
            @forelse($adminEnquiries as $enq)
                <div class="flex items-start gap-3 py-3 {{ !$loop->last ? 'border-b border-slate-50' : '' }}">
                    <div
                        class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0 font-bold text-indigo-700 text-sm">
                        {{ strtoupper(substr($enq['name'], 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-800 truncate">{{ $enq['name'] }}</p>
                        <p class="text-xs text-slate-400 truncate">{{ $enq['email'] }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $enq['time'] }}</p>
                    </div>
                    <span
                        class="flex-shrink-0 px-2 py-0.5 text-xs rounded-full font-medium
                {{ $enq['status'] === 'Responded' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ $enq['status'] }}
                    </span>
                </div>
            @empty
                <p class="text-center text-slate-400 text-sm py-8">No enquiries yet.</p>
            @endforelse
        </div>

        {{-- Class Distribution Bar --}}
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold text-slate-800 text-base">Class-wise Attendance Today</h2>
                <div class="flex items-center gap-3 text-xs">
                    <span class="flex items-center gap-1"><span
                            class="w-3 h-3 rounded-sm bg-emerald-500 inline-block"></span>P</span>
                    <span class="flex items-center gap-1"><span
                            class="w-3 h-3 rounded-sm bg-red-400 inline-block"></span>A</span>
                </div>
            </div>
            <div class="h-56" wire:ignore>
                <canvas id="classDistChart" x-data="{
                    chart: null,
                    init() {
                        const ctx = this.$el.getContext('2d');
                        this.chart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: @js($classDistribution['labels'] ?? []),
                                datasets: [
                                    { label: 'Present', data: @js($classDistribution['present'] ?? []), backgroundColor: 'rgba(16,185,129,0.75)', borderRadius: 4, borderSkipped: false },
                                    { label: 'Absent', data: @js($classDistribution['absent'] ?? []), backgroundColor: 'rgba(239,68,68,0.65)', borderRadius: 4, borderSkipped: false }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' } }
                                }
                            }
                        });
                    }
                }"></canvas>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════
     6. ARRANGEMENT
══════════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
        <div class="flex items-center gap-2 mb-4">
            <h2 class="font-bold text-slate-800 text-base">🔄 Arrangement – Today's Absent Teachers</h2>
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
                                <td class="px-4 py-3">
                                    <span class="flex items-center gap-1.5 text-red-600">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        {{ $arr['absent_teacher'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-500 text-xs">{{ $arr['time'] }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <select wire:model="selectedTeachers.{{ $arr['id'] }}"
                                            class="text-xs border border-slate-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-indigo-400 bg-white min-w-32">
                                            <option value="">Select Teacher</option>
                                            @foreach ($availableTeachers as $teacher)
                                                <option value="{{ $teacher['id'] }}"
                                                    {{ $arr['available_teacher_id'] == $teacher['id'] ? 'selected' : '' }}>
                                                    {{ $teacher['name'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button wire:click="saveArrangement({{ $arr['id'] }})"
                                            class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-2.5 py-1.5 rounded-lg transition-colors">Assign</button>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="px-2 py-0.5 text-xs rounded-full font-medium
                            {{ $arr['status'] === 'assigned' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
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
                <svg class="w-10 h-10 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                All teachers are present today — no arrangements needed.
            </div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════════════
     7. ANNOUNCEMENTS
══════════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
        <h2 class="font-bold text-slate-800 text-base mb-4">📢 Today's Announcements</h2>
        @if (count($announcements))
            <div class="space-y-3">
                @foreach ($announcements as $ann)
                    @php
                        $typeColors = [
                            'general' => 'bg-blue-50 border-blue-200 text-blue-700',
                            'urgent' => 'bg-red-50 border-red-200 text-red-700',
                            'event' => 'bg-purple-50 border-purple-200 text-purple-700',
                        ];
                        $tc = $typeColors[$ann['type']] ?? $typeColors['general'];
                    @endphp
                    <div class="border {{ $tc }} rounded-xl p-4 flex items-start gap-3">
                        @if ($ann['pinned'])
                            <span class="flex-shrink-0 text-base">📌</span>
                        @else
                            <span class="flex-shrink-0 text-base">📣</span>
                        @endif
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="font-semibold text-sm text-slate-800">{{ $ann['title'] }}</p>
                                <span
                                    class="px-2 py-0.5 text-xs rounded-full {{ $tc }} border font-medium capitalize">{{ $ann['type'] }}</span>
                            </div>
                            @if ($ann['body'])
                                <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                    {{ Str::limit($ann['body'], 120) }}</p>
                            @endif
                            <p class="text-xs text-slate-400 mt-1.5">{{ $ann['time'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-10 text-slate-400 text-sm">No announcements for today.</div>
        @endif
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- Recent Activity --}}
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <h2 class="font-bold text-slate-800 text-base mb-4">🕐 Recent Activity</h2>
            @forelse($recentActivities as $act)
                @php
                    $colorMap = ['green' => ['dot' => 'bg-emerald-500', 'bg' => 'bg-emerald-50'], 'blue' => ['dot' => 'bg-blue-500', 'bg' => 'bg-blue-50'], 'red' => ['dot' => 'bg-red-500', 'bg' => 'bg-red-50']];
                    $cm = $colorMap[$act['color']] ?? $colorMap['blue'];
                @endphp
                <div class="flex items-start gap-3 py-2.5 {{ !$loop->last ? 'border-b border-slate-50' : '' }}">
                    <div class="w-2 h-2 rounded-full {{ $cm['dot'] }} mt-2 flex-shrink-0"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-800">{{ $act['title'] }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ $act['description'] }}</p>
                    </div>
                    <span class="text-xs text-slate-400 flex-shrink-0">{{ $act['time'] }}</span>
                </div>
            @empty
                <p class="text-center text-slate-400 text-sm py-8">No recent activity.</p>
            @endforelse
        </div>

        {{-- Today's Homework --}}
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <h2 class="font-bold text-slate-800 text-base mb-4">📚 Today's Homework</h2>
            @forelse($todayHomework as $hw)
                <div class="flex items-start gap-3 py-2.5 {{ !$loop->last ? 'border-b border-slate-50' : '' }}">
                    <div
                        class="w-8 h-8 rounded-lg bg-violet-100 flex items-center justify-center flex-shrink-0 text-xs font-bold text-violet-700">
                        {{ strtoupper(substr($hw['subject']['name'], 0, 2)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-sm font-semibold text-slate-800">{{ $hw['subject']['name'] }}</p>
                            <span
                                class="text-xs bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded">{{ $hw['class'] }}-{{ $hw['section'] }}</span>
                        </div>
                        @if ($hw['details'])
                            <p class="text-xs text-slate-500 mt-0.5 truncate">{{ $hw['details'] }}</p>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-center text-slate-400 text-sm py-8">No homework assigned for today.</p>
            @endforelse
        </div>
    </div>

    {{-- ─────────────── SECTION: FEE ANALYTICS ─────────────── --}}
    <x-admin.section-heading title="Fee Analytics" subtitle="Collection performance across classes" color="violet"
        icon="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />

    {{-- ══════════════════════════════════════════════════════════
     9. FEE ANALYTICS
══════════════════════════════════════════════════════════ --}}
    <div class="space-y-5">

        {{-- Fee Stats Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @php
                $feeCards = [
                    [
                        'label' => 'Total Fee',
                        'value' => '₹' . number_format($feeStats['totalFee'] ?? 0),
                        'bg' => 'bg-indigo-50',
                        'ic' => 'text-indigo-600',
                        'icon' =>
                            'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
                    ],
                    [
                        'label' => 'Collected',
                        'value' => '₹' . number_format($feeStats['collected'] ?? 0),
                        'bg' => 'bg-green-50',
                        'ic' => 'text-green-600',
                        'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                    ],
                    [
                        'label' => 'Remaining',
                        'value' => '₹' . number_format($feeStats['remaining'] ?? 0),
                        'bg' => 'bg-red-50',
                        'ic' => 'text-red-500',
                        'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                    ],
                    [
                        'label' => 'Transport Fee',
                        'value' => '₹' . number_format($feeStats['transportFee'] ?? 0),
                        'bg' => 'bg-amber-50',
                        'ic' => 'text-amber-600',
                        'icon' =>
                            'M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2',
                    ],
                ];
            @endphp
            @foreach ($feeCards as $fc)
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 flex items-center gap-3">
                    <div class="p-2.5 rounded-xl {{ $fc['bg'] }} flex-shrink-0">
                        <svg class="h-5 w-5 {{ $fc['ic'] }}" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="{{ $fc['icon'] }}" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">{{ $fc['label'] }}</p>
                        <p class="text-lg font-bold text-slate-800">{{ $fc['value'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Fee Class Distribution --}}
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="font-bold text-slate-800 text-base">Fee Collection by Class</h2>
                    <p class="text-xs text-slate-400">Collected vs Remaining with percentage</p>
                </div>
                <div class="flex items-center gap-3 text-xs">
                    <span class="flex items-center gap-1"><span
                            class="w-3 h-3 rounded-sm bg-emerald-500 inline-block"></span> Collected</span>
                    <span class="flex items-center gap-1"><span
                            class="w-3 h-3 rounded-sm bg-red-400 inline-block"></span> Remaining</span>
                </div>
            </div>
            <div class="h-64" wire:ignore>
                <canvas id="feeClassChart" x-data="{
                    chart: null,
                    init() {
                        const ctx = this.$el.getContext('2d');
                        const collected = @js($feeClassData['collected'] ?? []);
                        const remaining = @js($feeClassData['remaining'] ?? []);
                
                        this.chart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: @js($feeClassData['labels'] ?? []),
                                datasets: [
                                    { label: 'Collected', data: collected, backgroundColor: 'rgba(16,185,129,0.75)', borderRadius: 4, borderSkipped: false },
                                    { label: 'Remaining', data: remaining, backgroundColor: 'rgba(239,68,68,0.65)', borderRadius: 4, borderSkipped: false }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        callbacks: {
                                            afterLabel: function(ctx) {
                                                const idx = ctx.dataIndex;
                                                const total = collected[idx] + remaining[idx];
                                                if (total > 0) {
                                                    const pct = ((ctx.parsed.y / total) * 100).toFixed(1);
                                                    return pct + '% of total';
                                                }
                                                return '';
                                            }
                                        }
                                    }
                                },
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
    </div>

</div>
