<div class="min-h-screen bg-gray-50">

    @php
        // ─── Derived metrics (kept in-view so the controller stays untouched) ───
        $studentAttPct = $totalStudents > 0 ? round(($studentsPresentToday / $totalStudents) * 100, 1) : 0;
        $teacherAttPct = $totalTeachers > 0 ? round(($teachersPresentToday / $totalTeachers) * 100, 1) : 0;
        $collectionPct = $totalFee > 0 ? round(($overallFeeCollected / $totalFee) * 100, 1) : 0;

        // Chart series pulled from the 7-day dataset the controller already builds.
        $chartDays        = array_column($last7DaysData, 'day');
        $chartStuPresent  = array_column($last7DaysData, 'student_present');
        $chartStuAbsent   = array_column($last7DaysData, 'student_absent');
        $chartTchPresent  = array_column($last7DaysData, 'teacher_present');
        $chartFee         = array_column($last7DaysData, 'fee_collected');
    @endphp

    {{-- ══════════════════════════════ HEADER ══════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">Dashboard</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    Welcome back, {{ auth()->user()->name }} · here's what's happening today
                </p>
            </div>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-gray-50 border border-gray-200 text-sm text-gray-600 whitespace-nowrap">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    {{ now()->format('l, d M Y') }}
                </span>
                {{-- Search --}}
                <div class="relative w-full sm:w-72">
                    <input type="text" wire:model.live="searchQuery"
                        placeholder="Search features…"
                        class="w-full py-2 pl-9 pr-3 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>

                    {{-- Search dropdown --}}
                    @if ($searchQuery && count($searchResults) > 0)
                        <div class="absolute z-20 top-full mt-1 left-0 right-0 bg-white rounded-lg shadow-xl border border-gray-200 overflow-hidden">
                            @foreach ($searchResults as $route => $label)
                                <div wire:click="selectResult('{{ $route }}')"
                                    class="px-4 py-2.5 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0 text-sm font-medium text-gray-700">
                                    {{ $label }}
                                </div>
                            @endforeach
                        </div>
                    @elseif($searchQuery && empty($searchResults))
                        <div class="absolute z-20 top-full mt-1 left-0 right-0 bg-white rounded-lg shadow border border-gray-200 px-4 py-3 text-center text-sm text-gray-500">
                            No results for "{{ $searchQuery }}"
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 space-y-6">

        {{-- ══════════════════════════ KEY STATS ══════════════════════════ --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Students --}}
            <a href="{{ route('admin.student', ['organization' => $organization]) }}"
                class="group bg-white rounded-xl border border-gray-200 p-5 hover:border-blue-300 hover:shadow-md transition-all">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-gray-400 uppercase tracking-wide">Students</p>
                    <span class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    </span>
                </div>
                <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($totalStudents) }}</p>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-1 text-emerald-600"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>{{ $activeStudents }} active</span>
                    <span class="inline-flex items-center gap-1 text-gray-400"><span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>{{ $inactiveStudents }} inactive</span>
                </div>
            </a>

            {{-- Teachers --}}
            <a href="{{ route('admin.teacher', ['organization' => $organization]) }}"
                class="group bg-white rounded-xl border border-gray-200 p-5 hover:border-emerald-300 hover:shadow-md transition-all">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-gray-400 uppercase tracking-wide">Teachers</p>
                    <span class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    </span>
                </div>
                <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($totalTeachers) }}</p>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-1 text-emerald-600"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>{{ $activeTeachers }} active</span>
                    <span class="inline-flex items-center gap-1 text-gray-400"><span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>{{ $inactiveTeachers }} inactive</span>
                </div>
            </a>

            {{-- Classes --}}
            <a href="{{ route('admin.standard', ['organization' => $organization]) }}"
                class="group bg-white rounded-xl border border-gray-200 p-5 hover:border-purple-300 hover:shadow-md transition-all">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-gray-400 uppercase tracking-wide">Classes</p>
                    <span class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    </span>
                </div>
                <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($totalClasses) }}</p>
                <p class="mt-2 text-xs text-gray-400">Active classes</p>
            </a>

            {{-- Subjects --}}
            <a href="{{ route('admin.standard', ['organization' => $organization]) }}"
                class="group bg-white rounded-xl border border-gray-200 p-5 hover:border-amber-300 hover:shadow-md transition-all">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-gray-400 uppercase tracking-wide">Subjects</p>
                    <span class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                    </span>
                </div>
                <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($totalSubjects) }}</p>
                <p class="mt-2 text-xs text-gray-400">Across all classes</p>
            </a>
        </div>

        {{-- ══════════════════════ SECONDARY STATS ══════════════════════ --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <p class="text-xl font-bold text-emerald-600">{{ $studentAttPct }}%</p>
                <p class="text-xs text-gray-400 mt-0.5">Student Attendance</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <p class="text-xl font-bold text-blue-600">{{ $teacherAttPct }}%</p>
                <p class="text-xs text-gray-400 mt-0.5">Teacher Attendance</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <p class="text-xl font-bold text-gray-800">₹{{ number_format($feeCollectedToday, 0) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Collected Today</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <p class="text-xl font-bold text-purple-600">{{ $collectionPct }}%</p>
                <p class="text-xs text-gray-400 mt-0.5">Fee Collection</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center col-span-2 lg:col-span-1">
                <p class="text-xl font-bold text-amber-600">₹{{ number_format($feeRemaining, 0) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Fee Pending</p>
            </div>
        </div>

        {{-- ══════════════════════ QUICK ACCESS ══════════════════════ --}}
        <div>
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Quick Access</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3">
                @php
                    $quick = [
                        ['label' => 'Attendance',   'route' => 'admin.attendance',   'bg' => 'bg-blue-50',    'ic' => 'text-blue-600',    'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                        ['label' => 'Announcement', 'route' => 'admin.announcement', 'bg' => 'bg-purple-50',  'ic' => 'text-purple-600',  'icon' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z'],
                        ['label' => 'Arrangements', 'route' => 'admin.arrangement',  'bg' => 'bg-indigo-50',  'ic' => 'text-indigo-600',  'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                        ['label' => 'Fee',          'route' => 'admin.fee',          'bg' => 'bg-emerald-50', 'ic' => 'text-emerald-600', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['label' => 'Homework',     'route' => 'admin.homework',     'bg' => 'bg-amber-50',   'ic' => 'text-amber-600',   'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
                        ['label' => 'Analytics',    'route' => 'admin.analytics',    'bg' => 'bg-rose-50',    'ic' => 'text-rose-600',    'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                    ];
                @endphp
                @foreach ($quick as $q)
                    <a href="{{ route($q['route'], ['organization' => $organization]) }}"
                        class="group bg-white rounded-xl border border-gray-200 p-4 flex flex-col items-center text-center gap-2 hover:border-blue-300 hover:shadow-md transition-all">
                        <span class="w-11 h-11 rounded-xl {{ $q['bg'] }} flex items-center justify-center transition-colors">
                            <svg class="w-5 h-5 {{ $q['ic'] }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $q['icon'] }}" /></svg>
                        </span>
                        <span class="text-sm font-medium text-gray-700 group-hover:text-blue-700">{{ $q['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- ══════════════════════ CHARTS ROW ══════════════════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Attendance trend --}}
            <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="font-semibold text-gray-800">Attendance Trend</h2>
                        <p class="text-xs text-gray-400">Last 7 days</p>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-emerald-500 inline-block"></span> Students</span>
                        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-red-400 inline-block"></span> Absent</span>
                        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-blue-500 inline-block"></span> Teachers</span>
                    </div>
                </div>
                <div class="h-64" wire:ignore>
                    <canvas id="homeAttendanceTrend" x-data="{
                        chart: null,
                        init() {
                            this.chart = new Chart(this.$el.getContext('2d'), {
                                type: 'line',
                                data: {
                                    labels: @js($chartDays),
                                    datasets: [
                                        { label: 'Students Present', data: @js($chartStuPresent), borderColor: 'rgb(16,185,129)', backgroundColor: 'rgba(16,185,129,0.12)', fill: true, tension: 0.35, borderWidth: 2, pointRadius: 3 },
                                        { label: 'Students Absent', data: @js($chartStuAbsent), borderColor: 'rgb(239,68,68)', backgroundColor: 'rgba(239,68,68,0.08)', fill: true, tension: 0.35, borderWidth: 2, pointRadius: 3 },
                                        { label: 'Teachers Present', data: @js($chartTchPresent), borderColor: 'rgb(59,130,246)', backgroundColor: 'rgba(59,130,246,0.06)', fill: false, tension: 0.35, borderWidth: 2, pointRadius: 3, borderDash: [4,3] }
                                    ]
                                },
                                options: {
                                    responsive: true, maintainAspectRatio: false,
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

            {{-- Today's attendance donut --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h2 class="font-semibold text-gray-800 mb-4">Today's Attendance</h2>
                <div class="h-40" wire:ignore>
                    <canvas id="homeTodayDonut" x-data="{
                        chart: null,
                        init() {
                            this.chart = new Chart(this.$el.getContext('2d'), {
                                type: 'doughnut',
                                data: {
                                    labels: ['Present', 'Absent'],
                                    datasets: [{ data: [{{ $studentsPresentToday }}, {{ $studentsAbsentToday }}], backgroundColor: ['rgba(16,185,129,0.85)', 'rgba(239,68,68,0.75)'], borderWidth: 0, hoverOffset: 6 }]
                                },
                                options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12 } } } }
                            });
                        }
                    }"></canvas>
                </div>
                <div class="mt-4 space-y-3">
                    <div>
                        <div class="flex justify-between text-xs mb-1"><span class="text-gray-500">Students</span><span class="font-semibold text-gray-700">{{ $studentAttPct }}%</span></div>
                        <div class="w-full bg-gray-100 rounded-full h-2"><div class="bg-emerald-500 h-2 rounded-full" style="width: {{ $studentAttPct }}%"></div></div>
                    </div>
                    <div>
                        <div class="flex justify-between text-xs mb-1"><span class="text-gray-500">Teachers</span><span class="font-semibold text-gray-700">{{ $teacherAttPct }}%</span></div>
                        <div class="w-full bg-gray-100 rounded-full h-2"><div class="bg-blue-500 h-2 rounded-full" style="width: {{ $teacherAttPct }}%"></div></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════ FEE ANALYTICS ══════════════════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Fee collection bar --}}
            <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="font-semibold text-gray-800">Fee Collection</h2>
                        <p class="text-xs text-gray-400">Daily collection · last 7 days</p>
                    </div>
                    <a href="{{ route('admin.fee', ['organization' => $organization]) }}" class="text-xs font-medium text-blue-600 hover:text-blue-800">Manage fees →</a>
                </div>
                <div class="h-56" wire:ignore>
                    <canvas id="homeFeeBar" x-data="{
                        chart: null,
                        init() {
                            this.chart = new Chart(this.$el.getContext('2d'), {
                                type: 'bar',
                                data: {
                                    labels: @js($chartDays),
                                    datasets: [{ label: 'Collected', data: @js($chartFee), backgroundColor: 'rgba(99,102,241,0.75)', borderRadius: 4, borderSkipped: false }]
                                },
                                options: {
                                    responsive: true, maintainAspectRatio: false,
                                    plugins: { legend: { display: false } },
                                    scales: {
                                        x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 10 }, callback: (v) => '₹' + v.toLocaleString('en-IN') } }
                                    }
                                }
                            });
                        }
                    }"></canvas>
                </div>
            </div>

            {{-- Fee overview --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h2 class="font-semibold text-gray-800 mb-4">Fee Overview</h2>
                <div class="space-y-3">
                    <div class="flex justify-between items-center p-3 bg-emerald-50 rounded-lg">
                        <span class="text-sm text-gray-600">Collected</span>
                        <span class="font-bold text-emerald-600">₹{{ number_format($overallFeeCollected) }}</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-amber-50 rounded-lg">
                        <span class="text-sm text-gray-600">Remaining</span>
                        <span class="font-bold text-amber-600">₹{{ number_format($feeRemaining) }}</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                        <span class="text-sm text-gray-600">Total Expected</span>
                        <span class="font-bold text-blue-600">₹{{ number_format($totalFee) }}</span>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex justify-between text-xs mb-1"><span class="text-gray-500">Collection Progress</span><span class="font-semibold text-gray-700">{{ $collectionPct }}%</span></div>
                    <div class="w-full bg-gray-100 rounded-full h-2.5"><div class="bg-gradient-to-r from-emerald-500 to-emerald-600 h-2.5 rounded-full" style="width: {{ $collectionPct }}%"></div></div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════ MAIN GRID ══════════════════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- LEFT (2/3) — attendance detail tables --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Student attendance 7 days --}}
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <span class="w-2 h-2 bg-emerald-500 rounded-full"></span> Student Attendance · Last 7 Days
                        </h2>
                        <a href="{{ route('admin.attendance', ['organization' => $organization]) }}" class="text-xs font-medium text-blue-600 hover:text-blue-800">Open →</a>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                            <tr>
                                <th class="px-4 py-3 text-left">Day</th>
                                <th class="px-4 py-3 text-center">Present</th>
                                <th class="px-4 py-3 text-center">Absent</th>
                                <th class="px-4 py-3 text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($last7DaysData as $data)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2.5 font-medium text-gray-800">{{ $data['day'] }}</td>
                                    <td class="px-4 py-2.5 text-center text-emerald-600 font-medium">{{ $data['student_present'] }}</td>
                                    <td class="px-4 py-2.5 text-center text-red-500 font-medium">{{ $data['student_absent'] }}</td>
                                    <td class="px-4 py-2.5 text-center text-gray-600">{{ $data['student_total'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Teacher attendance 7 days --}}
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <span class="w-2 h-2 bg-blue-500 rounded-full"></span> Teacher Attendance · Last 7 Days
                        </h2>
                        <a href="{{ route('admin.arrangement', ['organization' => $organization]) }}" class="text-xs font-medium text-blue-600 hover:text-blue-800">Arrangements →</a>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                            <tr>
                                <th class="px-4 py-3 text-left">Day</th>
                                <th class="px-4 py-3 text-center">Present</th>
                                <th class="px-4 py-3 text-center">Absent</th>
                                <th class="px-4 py-3 text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($last7DaysData as $data)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2.5 font-medium text-gray-800">{{ $data['day'] }}</td>
                                    <td class="px-4 py-2.5 text-center text-emerald-600 font-medium">{{ $data['teacher_present'] }}</td>
                                    <td class="px-4 py-2.5 text-center text-red-500 font-medium">{{ $data['teacher_absent'] }}</td>
                                    <td class="px-4 py-2.5 text-center text-gray-600">{{ $data['teacher_total'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Recent searches --}}
                @if (count($recentSearches) > 0)
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-gray-700">Recent Searches</h2>
                            <button wire:click="clearRecentSearches" class="text-xs font-medium text-red-500 hover:text-red-700">Clear all</button>
                        </div>
                        <div class="divide-y divide-gray-100">
                            @foreach ($recentSearches as $search)
                                <div wire:click="$set('searchQuery', '{{ $search['term'] }}')"
                                    class="px-5 py-3 hover:bg-gray-50 cursor-pointer flex justify-between items-center">
                                    <div>
                                        <div class="text-sm font-medium text-gray-800">{{ $search['term'] }}</div>
                                        <div class="text-xs text-gray-400">{{ $search['time']->diffForHumans() }}</div>
                                    </div>
                                    <svg class="h-4 w-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- RIGHT (1/3) --}}
            <div class="space-y-6">

                {{-- Upcoming events --}}
                <div class="bg-white rounded-xl border border-gray-200">
                    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-gray-700">Upcoming Events</h2>
                        <a href="{{ route('admin.calender', ['organization' => $organization]) }}" class="text-xs font-medium text-blue-600 hover:text-blue-800">Calendar →</a>
                    </div>
                    <div class="p-5">
                        @if (count($upcomingEvents) > 0)
                            <div class="relative">
                                <div class="border-l-2 border-gray-100 absolute h-full left-1.5 top-1"></div>
                                @foreach ($upcomingEvents as $event)
                                    <div class="mb-5 last:mb-0 ml-6 relative">
                                        <div class="absolute w-3 h-3 bg-{{ $event['color'] }}-500 rounded-full -left-[26px] top-1 border-2 border-white"></div>
                                        <div class="flex justify-between items-start gap-2">
                                            <p class="text-sm font-medium text-gray-800">{{ $event['title'] }}</p>
                                            <span class="bg-{{ $event['color'] }}-100 text-{{ $event['color'] }}-600 text-xs px-2 py-0.5 rounded-full whitespace-nowrap">{{ $event['formatted_date'] }}</span>
                                        </div>
                                        @if ($event['description'])
                                            <p class="text-xs text-gray-400 mt-0.5">{{ Str::limit($event['description'], 50) }}</p>
                                        @endif
                                        <div class="flex flex-wrap gap-2 text-xs text-gray-400 mt-1">
                                            @if ($event['is_all_day'])
                                                <span>All Day</span>
                                            @elseif($event['start_time'])
                                                <span>{{ $event['start_time'] }}@if ($event['end_time']) – {{ $event['end_time'] }}@endif</span>
                                            @endif
                                            @if ($event['location'])
                                                <span>· {{ Str::limit($event['location'], 20) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-center text-gray-400 text-sm py-8">No upcoming events</p>
                        @endif
                    </div>
                </div>

                {{-- Queries --}}
                <div class="bg-white rounded-xl border border-gray-200">
                    <div class="px-5 py-3 border-b border-gray-100">
                        <h2 class="text-sm font-semibold text-gray-700">Queries</h2>
                    </div>
                    <div class="p-5 space-y-3">
                        <div class="flex justify-between items-center p-3 bg-emerald-50 rounded-lg">
                            <span class="text-sm text-gray-600">Student Queries</span>
                            <span class="font-bold text-emerald-600">{{ $studentQueries }}</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-amber-50 rounded-lg">
                            <span class="text-sm text-gray-600">Teacher Queries</span>
                            <span class="font-bold text-amber-600">{{ $teacherQueries }}</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                            <span class="text-sm text-gray-600">Website Queries</span>
                            <span class="font-bold text-blue-600">{{ $websiteQueries }}</span>
                        </div>
                    </div>
                </div>

                {{-- Quick links --}}
                <div class="bg-white rounded-xl border border-gray-200">
                    <div class="px-5 py-3 border-b border-gray-100">
                        <h2 class="text-sm font-semibold text-gray-700">Quick Links</h2>
                    </div>
                    <div class="p-3">
                        @php
                            $links = [
                                ['View All Students', 'admin.student'],
                                ['View All Teachers', 'admin.teacher'],
                                ['Manage Classes', 'admin.standard'],
                                ['View Analytics', 'admin.analytics'],
                            ];
                        @endphp
                        @foreach ($links as $l)
                            <a href="{{ route($l[1], ['organization' => $organization]) }}"
                                class="flex items-center justify-between px-2 py-2.5 hover:bg-gray-50 rounded-lg transition">
                                <span class="text-sm text-gray-700">{{ $l[0] }}</span>
                                <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- LMS rating --}}
                <div class="bg-white rounded-xl border border-gray-200">
                    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-gray-700">LMS Rating</h2>
                        <span class="text-sm"><span class="text-gray-400">Avg</span> <span class="font-bold text-yellow-600">{{ number_format($averageRating, 1) }}/5</span></span>
                    </div>
                    @if ($latestRating)
                        <div class="p-5">
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex items-center">
                                        <div class="flex">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <svg class="w-4 h-4 {{ $i <= $latestRating->rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                            @endfor
                                        </div>
                                        <span class="ml-2 text-sm font-semibold text-gray-700">{{ $latestRating->rating }}/5</span>
                                    </div>
                                    <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($latestRating->created_at)->format('d M, Y') }}</span>
                                </div>
                                <p class="text-sm text-gray-600 line-clamp-2">{{ $latestRating->feedback }}</p>
                            </div>
                            <a href="{{ route('admin.rate-lms', ['organization' => $organization]) }}"
                                class="mt-3 w-full inline-flex justify-center items-center px-4 py-2 text-sm font-medium rounded-lg text-white bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 transition">
                                View Ratings
                                <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </a>
                        </div>
                    @else
                        <div class="p-6 text-center">
                            <p class="text-sm text-gray-500 mb-4">No ratings yet</p>
                            <a href="{{ route('admin.rate-lms', ['organization' => $organization]) }}"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg text-white bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 transition">Rate LMS</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
