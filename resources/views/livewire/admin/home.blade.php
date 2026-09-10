<div class="min-h-screen bg-gray-50">

    @php
        // ─── Derived metrics (kept in-view so the controller stays untouched) ───
        $studentAttPct = $totalStudents > 0 ? round(($studentsPresentToday / $totalStudents) * 100, 1) : 0;
        $teacherAttPct = $totalTeachers > 0 ? round(($teachersPresentToday / $totalTeachers) * 100, 1) : 0;
        $collectionPct = $totalFee > 0 ? round(($overallFeeCollected / $totalFee) * 100, 1) : 0;

        // Fifteen-day series, split so student and teacher each get their own chart.
        $trendLabels   = array_column($last15DaysData, 'label');
        $trendStuPres  = array_column($last15DaysData, 'student_present');
        $trendStuAbs   = array_column($last15DaysData, 'student_absent');
        $trendTchPres  = array_column($last15DaysData, 'teacher_present');
        $trendTchAbs   = array_column($last15DaysData, 'teacher_absent');

        // The day the trend dropdown is pointing at.
        $pick = $this->trendDay();

        // Exam performance: one point per exam, average percentage across every
        // paper marked for it.
        $examLabels = array_column($examTrend, 'label');
        $examAvg    = array_column($examTrend, 'avg');
        $examPass   = array_column($examTrend, 'pass_pct');
        $examLatest = $examTrend ? end($examTrend) : null;
    @endphp

    {{-- ══════════════════════════════ HEADER ══════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">Dashboard</h1>
                <p class="text-xs text-gray-400 mt-0.5">{{ now()->format('l, d M Y') }}</p>
            </div>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                {{-- Search --}}
                <div class="relative w-full sm:w-72">
                    <input type="text" wire:model.live="searchQuery"
                        placeholder="Search features…"
                        class="w-full py-2 pl-9 pr-3 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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

    <div class="p-4 sm:p-6 space-y-8">

        {{-- ══════════════════════════════════════════════════════════════════
             OVERVIEW — one flat row. Counts on the left, today's rates on the
             right, no boxes competing for attention.
        ══════════════════════════════════════════════════════════════════ --}}
        <section>
            <div class="flex items-baseline justify-between mb-3">
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Overview</h2>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 divide-y sm:divide-y-0 sm:divide-x divide-gray-100 grid grid-cols-2 sm:grid-cols-4">
                @php
                    $counts = [
                        ['Students', number_format($totalStudents), $activeStudents . ' active', 'admin.student'],
                        ['Teachers', number_format($totalTeachers), $activeTeachers . ' active', 'admin.teacher'],
                        ['Classes',  number_format($totalClasses),  'across the school', 'admin.standard'],
                        ['Subjects', number_format($totalSubjects), 'currently taught',  'admin.standard'],
                    ];
                @endphp
                @foreach ($counts as [$label, $value, $sub, $route])
                    <a href="{{ route($route, ['organization' => $organization]) }}"
                        class="px-5 py-4 hover:bg-gray-50/70 transition-colors first:rounded-l-xl last:rounded-r-xl">
                        <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">{{ $label }}</p>
                        <p class="text-[26px] leading-none font-semibold text-gray-900 mt-2 tabular-nums">{{ $value }}</p>
                        <p class="text-xs text-gray-400 mt-1.5">{{ $sub }}</p>
                    </a>
                @endforeach
            </div>

            {{-- Today, as plain numbers with a thin progress rule underneath. --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
                @php
                    $todayCards = [
                        ['Student Attendance', $studentAttPct . '%', $studentsPresentToday . ' of ' . $totalStudents . ' present', $studentAttPct, 'bg-emerald-500'],
                        ['Teacher Attendance', $teacherAttPct . '%', $teachersPresentToday . ' of ' . $totalTeachers . ' present', $teacherAttPct, 'bg-blue-500'],
                        ['Fee Collected', '₹' . number_format($overallFeeCollected, 0), $collectionPct . '% of ₹' . number_format($totalFee, 0) . ' expected', $collectionPct, 'bg-indigo-500'],
                    ];
                @endphp
                @foreach ($todayCards as [$label, $value, $sub, $pct, $bar])
                    <div class="bg-white rounded-xl border border-gray-200 px-5 py-4">
                        <div class="flex items-baseline justify-between gap-2">
                            <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">{{ $label }}</p>
                            <p class="text-lg font-semibold text-gray-900 tabular-nums">{{ $value }}</p>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1 mt-3">
                            <div class="{{ $bar }} h-1 rounded-full" style="width: {{ min(100, $pct) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">{{ $sub }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ══════════════════════════════ QUICK ACCESS ══════════════════════ --}}
        <section>
            <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Quick Access</h2>
            <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 gap-3">
                @php
                    $quick = [
                        ['label' => 'Attendance',   'route' => 'admin.attendance',   'ic' => 'text-blue-600',    'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                        ['label' => 'Announcement', 'route' => 'admin.announcement', 'ic' => 'text-purple-600',  'icon' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z'],
                        ['label' => 'Arrangements', 'route' => 'admin.arrangement',  'ic' => 'text-indigo-600',  'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                        ['label' => 'Fee',          'route' => 'admin.fee',          'ic' => 'text-emerald-600', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['label' => 'Homework',     'route' => 'admin.homework',     'ic' => 'text-amber-600',   'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
                        ['label' => 'Analytics',    'route' => 'admin.analytics',    'ic' => 'text-rose-600',    'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                    ];
                @endphp
                @foreach ($quick as $q)
                    <a href="{{ route($q['route'], ['organization' => $organization]) }}"
                        class="group bg-white rounded-xl border border-gray-200 px-3 py-4 flex flex-col items-center text-center gap-2 hover:border-gray-300 hover:shadow-sm transition-all">
                        <svg class="w-5 h-5 {{ $q['ic'] }}" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $q['icon'] }}" /></svg>
                        <span class="text-xs font-medium text-gray-600 group-hover:text-gray-900">{{ $q['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- ══════════════════════════════════════════════════════════════════
             ATTENDANCE TREND — students and teachers get a chart each. The
             shared dropdown picks one of the last 15 days and both cards
             report that day underneath their own chart.
        ══════════════════════════════════════════════════════════════════ --}}
        <section>
            <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                <div>
                    <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Attendance Trend</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Last 15 days</p>
                </div>
                <select wire:model.live="attTrendDate"
                    class="text-xs bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-400">
                    @foreach (array_reverse($last15DaysData) as $d)
                        <option value="{{ $d['date'] }}">{{ \Carbon\Carbon::parse($d['date'])->format('D, d M Y') }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                {{-- Students --}}
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-800">Students</h3>
                        <div class="flex items-center gap-3 text-[11px] text-gray-400">
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-500 inline-block"></span> Present</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-red-400 inline-block"></span> Absent</span>
                        </div>
                    </div>
                    <div class="h-52" wire:ignore wire:key="home-stu-trend">
                        <canvas x-data="{
                            init() {
                                new Chart(this.$el.getContext('2d'), {
                                    type: 'line',
                                    data: {
                                        labels: @js($trendLabels),
                                        datasets: [
                                            { label: 'Present', data: @js($trendStuPres), borderColor: 'rgb(16,185,129)', backgroundColor: 'rgba(16,185,129,0.10)', fill: true, tension: 0.35, borderWidth: 2, pointRadius: 2 },
                                            { label: 'Absent', data: @js($trendStuAbs), borderColor: 'rgb(248,113,113)', backgroundColor: 'rgba(248,113,113,0.06)', fill: true, tension: 0.35, borderWidth: 2, pointRadius: 2 }
                                        ]
                                    },
                                    options: {
                                        responsive: true, maintainAspectRatio: false,
                                        plugins: { legend: { display: false } },
                                        scales: {
                                            x: { grid: { display: false }, ticks: { font: { size: 9 }, maxRotation: 0, autoSkipPadding: 8 } },
                                            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 } } }
                                        }
                                    }
                                });
                            }
                        }"></canvas>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-[11px] text-gray-400 mb-2">{{ $pick ? \Carbon\Carbon::parse($pick['date'])->format('D, d M Y') : '—' }}</p>
                        <div class="grid grid-cols-4 gap-2 text-center">
                            <div><p class="text-base font-semibold text-emerald-600 tabular-nums">{{ $pick['student_present'] ?? 0 }}</p><p class="text-[10px] text-gray-400 uppercase">Present</p></div>
                            <div><p class="text-base font-semibold text-red-500 tabular-nums">{{ $pick['student_absent'] ?? 0 }}</p><p class="text-[10px] text-gray-400 uppercase">Absent</p></div>
                            <div><p class="text-base font-semibold text-amber-500 tabular-nums">{{ $pick['student_half'] ?? 0 }}</p><p class="text-[10px] text-gray-400 uppercase">Half</p></div>
                            <div><p class="text-base font-semibold text-gray-800 tabular-nums">{{ $pick['student_pct'] ?? 0 }}%</p><p class="text-[10px] text-gray-400 uppercase">Rate</p></div>
                        </div>
                    </div>
                </div>

                {{-- Teachers --}}
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-800">Teachers</h3>
                        <div class="flex items-center gap-3 text-[11px] text-gray-400">
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-blue-500 inline-block"></span> Present</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-amber-400 inline-block"></span> Absent</span>
                        </div>
                    </div>
                    <div class="h-52" wire:ignore wire:key="home-tch-trend">
                        <canvas x-data="{
                            init() {
                                new Chart(this.$el.getContext('2d'), {
                                    type: 'line',
                                    data: {
                                        labels: @js($trendLabels),
                                        datasets: [
                                            { label: 'Present', data: @js($trendTchPres), borderColor: 'rgb(59,130,246)', backgroundColor: 'rgba(59,130,246,0.10)', fill: true, tension: 0.35, borderWidth: 2, pointRadius: 2 },
                                            { label: 'Absent', data: @js($trendTchAbs), borderColor: 'rgb(251,191,36)', backgroundColor: 'rgba(251,191,36,0.08)', fill: true, tension: 0.35, borderWidth: 2, pointRadius: 2 }
                                        ]
                                    },
                                    options: {
                                        responsive: true, maintainAspectRatio: false,
                                        plugins: { legend: { display: false } },
                                        scales: {
                                            x: { grid: { display: false }, ticks: { font: { size: 9 }, maxRotation: 0, autoSkipPadding: 8 } },
                                            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 } } }
                                        }
                                    }
                                });
                            }
                        }"></canvas>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-[11px] text-gray-400 mb-2">{{ $pick ? \Carbon\Carbon::parse($pick['date'])->format('D, d M Y') : '—' }}</p>
                        <div class="grid grid-cols-4 gap-2 text-center">
                            <div><p class="text-base font-semibold text-blue-600 tabular-nums">{{ $pick['teacher_present'] ?? 0 }}</p><p class="text-[10px] text-gray-400 uppercase">Present</p></div>
                            <div><p class="text-base font-semibold text-red-500 tabular-nums">{{ $pick['teacher_absent'] ?? 0 }}</p><p class="text-[10px] text-gray-400 uppercase">Absent</p></div>
                            <div><p class="text-base font-semibold text-amber-500 tabular-nums">{{ $pick['teacher_half'] ?? 0 }}</p><p class="text-[10px] text-gray-400 uppercase">Half</p></div>
                            <div><p class="text-base font-semibold text-gray-800 tabular-nums">{{ $pick['teacher_pct'] ?? 0 }}%</p><p class="text-[10px] text-gray-400 uppercase">Rate</p></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ══════════════════════════════════════════════════════════════════
             FEE COLLECTION — the bars come from the payments actually recorded
             in the selected range, bucketed by day / week / month to suit it.
        ══════════════════════════════════════════════════════════════════ --}}
        <section>
            <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Fee Collection</h2>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                        <div>
                            <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">Collected · {{ $this->feeRangeLabel() }}</p>
                            <p class="text-2xl font-semibold text-gray-900 mt-1 tabular-nums">₹{{ number_format($feeRangeTotal, 0) }}</p>
                        </div>
                        <select wire:model.live="feeRange"
                            class="text-xs bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-indigo-400">
                            <option value="7">Last 7 days</option>
                            <option value="15">Last 15 days</option>
                            <option value="30">Last 30 days</option>
                            <option value="60">Last 60 days</option>
                            <option value="90">Last 90 days</option>
                            <option value="180">Last 6 months</option>
                        </select>
                    </div>
                    <div class="h-56" wire:key="home-fee-{{ $feeRange }}">
                        <canvas x-data="{
                            init() {
                                new Chart(this.$el.getContext('2d'), {
                                    type: 'bar',
                                    data: {
                                        labels: @js($feeSeries['labels'] ?? []),
                                        datasets: [{ label: 'Collected', data: @js($feeSeries['data'] ?? []), backgroundColor: 'rgba(99,102,241,0.7)', borderRadius: 3, borderSkipped: false, maxBarThickness: 34 }]
                                    },
                                    options: {
                                        responsive: true, maintainAspectRatio: false,
                                        plugins: {
                                            legend: { display: false },
                                            tooltip: { callbacks: { label: (c) => '₹' + Number(c.raw).toLocaleString('en-IN') } }
                                        },
                                        scales: {
                                            x: { grid: { display: false }, ticks: { font: { size: 9 }, maxRotation: 0, autoSkipPadding: 8 } },
                                            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, callback: (v) => '₹' + Number(v).toLocaleString('en-IN') } }
                                        }
                                    }
                                });
                            }
                        }"></canvas>
                    </div>
                </div>

                {{-- Overall position --}}
                <div class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-800">Overall</h3>
                        <a href="{{ route('admin.fee', ['organization' => $organization]) }}" class="text-xs font-medium text-blue-600 hover:text-blue-800">Manage →</a>
                    </div>
                    <dl class="space-y-3 text-sm">
                        <div class="flex items-baseline justify-between">
                            <dt class="text-gray-500">Collected</dt>
                            <dd class="font-semibold text-emerald-600 tabular-nums">₹{{ number_format($overallFeeCollected) }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between">
                            <dt class="text-gray-500">Pending</dt>
                            <dd class="font-semibold text-amber-600 tabular-nums">₹{{ number_format($feeRemaining) }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between pt-3 border-t border-gray-100">
                            <dt class="text-gray-500">Expected</dt>
                            <dd class="font-semibold text-gray-900 tabular-nums">₹{{ number_format($totalFee) }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between">
                            <dt class="text-gray-500">Collected today</dt>
                            <dd class="font-semibold text-gray-900 tabular-nums">₹{{ number_format($feeCollectedToday, 0) }}</dd>
                        </div>
                    </dl>
                    <div class="mt-auto pt-5">
                        <div class="flex justify-between text-xs mb-1.5"><span class="text-gray-400">Progress</span><span class="font-semibold text-gray-700">{{ $collectionPct }}%</span></div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5"><div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ min(100, $collectionPct) }}%"></div></div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ══════════════════════════════════════════════════════════════════
             DETAIL — the last week day by day, plus the side column.
        ══════════════════════════════════════════════════════════════════ --}}
        <section>
            <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">This Week</h2>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                {{-- LEFT — one table carrying both sides, so the week reads at a glance --}}
                <div class="lg:col-span-2 space-y-5">
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-800">Attendance · Last 7 Days</h3>
                            <a href="{{ route('admin.attendance', ['organization' => $organization]) }}" class="text-xs font-medium text-blue-600 hover:text-blue-800">Open →</a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm min-w-[520px]">
                                <thead class="bg-gray-50/70 text-gray-400 text-[11px] uppercase tracking-wide">
                                    <tr>
                                        <th class="px-5 py-2.5 text-left font-medium">Day</th>
                                        <th class="px-4 py-2.5 text-center font-medium" colspan="2">Students</th>
                                        <th class="px-4 py-2.5 text-center font-medium" colspan="2">Teachers</th>
                                    </tr>
                                    <tr class="text-[10px]">
                                        <th></th>
                                        <th class="px-4 pb-2 text-center font-normal text-emerald-500">Present</th>
                                        <th class="px-4 pb-2 text-center font-normal text-red-400">Absent</th>
                                        <th class="px-4 pb-2 text-center font-normal text-blue-500">Present</th>
                                        <th class="px-4 pb-2 text-center font-normal text-red-400">Absent</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($last7DaysData as $data)
                                        <tr class="hover:bg-gray-50/70">
                                            <td class="px-5 py-2.5 font-medium text-gray-700">{{ $data['day'] }}</td>
                                            <td class="px-4 py-2.5 text-center text-emerald-600 tabular-nums">{{ $data['student_present'] }}</td>
                                            <td class="px-4 py-2.5 text-center text-red-500 tabular-nums">{{ $data['student_absent'] }}</td>
                                            <td class="px-4 py-2.5 text-center text-blue-600 tabular-nums">{{ $data['teacher_present'] }}</td>
                                            <td class="px-4 py-2.5 text-center text-red-500 tabular-nums">{{ $data['teacher_absent'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- ── Exam performance trend ─────────────────────────────
                         One point per exam, average percentage across every
                         paper marked for it, so results read as a line rather
                         than a pile of numbers. --}}
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-800">Exam Performance Trend</h3>
                                <p class="text-[11px] text-gray-400 mt-0.5">Average percentage @if (count($examTrend) > 0)· last {{ count($examTrend) }} exam{{ count($examTrend) === 1 ? '' : 's' }}@endif</p>
                            </div>
                            <a href="{{ route('admin.performance', ['organization' => $organization]) }}" class="text-xs font-medium text-blue-600 hover:text-blue-800">Open →</a>
                        </div>

                        @if (count($examTrend) > 0)
                            <div class="px-5 pt-4">
                                <div class="flex flex-wrap items-end gap-6">
                                    <div>
                                        <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">Latest · {{ $examLatest['exam'] }}</p>
                                        <p class="text-2xl font-semibold text-gray-900 mt-1 tabular-nums">{{ $examLatest['avg'] }}%</p>
                                    </div>
                                    <div class="pb-1">
                                        @if ($examTrendDelta > 0)
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600">▲ {{ $examTrendDelta }} pts vs previous</span>
                                        @elseif ($examTrendDelta < 0)
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-red-500">▼ {{ abs($examTrendDelta) }} pts vs previous</span>
                                        @else
                                            <span class="text-xs font-medium text-gray-400">No change vs previous</span>
                                        @endif
                                    </div>
                                    <div class="pb-1 ml-auto flex items-center gap-3 text-[11px] text-gray-400">
                                        <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-violet-500 inline-block"></span> Average %</span>
                                        <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-400 inline-block"></span> Pass %</span>
                                    </div>
                                </div>
                            </div>

                            <div class="h-56 px-3 pt-3 pb-1" wire:ignore wire:key="home-exam-trend">
                                <canvas x-data="{
                                    init() {
                                        const meta = @js($examTrend);
                                        new Chart(this.$el.getContext('2d'), {
                                            type: 'line',
                                            data: {
                                                labels: @js($examLabels),
                                                datasets: [
                                                    { label: 'Average %', data: @js($examAvg), borderColor: 'rgb(139,92,246)', backgroundColor: 'rgba(139,92,246,0.12)', fill: true, tension: 0.35, borderWidth: 2, pointRadius: 3, pointBackgroundColor: 'rgb(139,92,246)' },
                                                    { label: 'Pass %', data: @js($examPass), borderColor: 'rgb(52,211,153)', borderDash: [4, 3], fill: false, tension: 0.35, borderWidth: 2, pointRadius: 2 }
                                                ]
                                            },
                                            options: {
                                                responsive: true, maintainAspectRatio: false,
                                                plugins: {
                                                    legend: { display: false },
                                                    tooltip: {
                                                        callbacks: {
                                                            title: (items) => meta[items[0].dataIndex].exam,
                                                            label: (c) => c.dataset.label + ': ' + c.raw + '%',
                                                            afterBody: (items) => {
                                                                const m = meta[items[0].dataIndex];
                                                                return m.date + ' · ' + m.papers + ' papers · ' + m.students + ' students';
                                                            }
                                                        }
                                                    }
                                                },
                                                scales: {
                                                    x: { grid: { display: false }, ticks: { font: { size: 9 }, maxRotation: 0, autoSkipPadding: 6 } },
                                                    y: { beginAtZero: true, max: 100, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, stepSize: 20, callback: (v) => v + '%' } }
                                                }
                                            }
                                        });
                                    }
                                }"></canvas>
                            </div>

                            <div class="px-5 py-3 border-t border-gray-100 grid grid-cols-3 gap-2 text-center">
                                <div>
                                    <p class="text-base font-semibold text-gray-800 tabular-nums">{{ $examTrendAvg }}%</p>
                                    <p class="text-[10px] text-gray-400 uppercase">Overall avg</p>
                                </div>
                                <div>
                                    <p class="text-base font-semibold text-emerald-600 tabular-nums">{{ $examLatest['pass_pct'] }}%</p>
                                    <p class="text-[10px] text-gray-400 uppercase">Latest pass rate</p>
                                </div>
                                <div>
                                    <p class="text-base font-semibold text-gray-800 tabular-nums">{{ $examLatest['students'] }}</p>
                                    <p class="text-[10px] text-gray-400 uppercase">Students</p>
                                </div>
                            </div>
                        @else
                            <div class="px-5 py-10 text-center">
                                <p class="text-sm text-gray-500">No exam marks recorded yet.</p>
                                <p class="text-xs text-gray-400 mt-1">The trend appears once marks are uploaded against an exam.</p>
                            </div>
                        @endif
                    </div>

                    {{-- Recent searches --}}
                    @if (count($recentSearches) > 0)
                        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-gray-800">Recent Searches</h3>
                                <button wire:click="clearRecentSearches" class="text-xs font-medium text-red-500 hover:text-red-700">Clear all</button>
                            </div>
                            <div class="divide-y divide-gray-100">
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

                {{-- RIGHT --}}
                <div class="space-y-5">

                    {{-- Upcoming events --}}
                    <div class="bg-white rounded-xl border border-gray-200">
                        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-800">Upcoming Events</h3>
                            <a href="{{ route('admin.calender', ['organization' => $organization]) }}" class="text-xs font-medium text-blue-600 hover:text-blue-800">Calendar →</a>
                        </div>
                        <div class="p-5">
                            @if (count($upcomingEvents) > 0)
                                <div class="relative">
                                    <div class="border-l border-gray-100 absolute h-full left-1 top-1.5"></div>
                                    @foreach ($upcomingEvents as $event)
                                        <div class="mb-5 last:mb-0 ml-5 relative">
                                            <div class="absolute w-2 h-2 bg-{{ $event['color'] }}-500 rounded-full -left-[21px] top-1.5 ring-2 ring-white"></div>
                                            <div class="flex justify-between items-start gap-2">
                                                <p class="text-sm font-medium text-gray-800">{{ $event['title'] }}</p>
                                                <span class="text-[11px] text-gray-400 whitespace-nowrap">{{ $event['formatted_date'] }}</span>
                                            </div>
                                            @if ($event['description'])
                                                <p class="text-xs text-gray-400 mt-0.5">{{ Str::limit($event['description'], 50) }}</p>
                                            @endif
                                            <div class="flex flex-wrap gap-1.5 text-xs text-gray-400 mt-1">
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
                            <h3 class="text-sm font-semibold text-gray-800">Queries</h3>
                        </div>
                        <dl class="p-5 space-y-2.5 text-sm">
                            <div class="flex justify-between items-baseline">
                                <dt class="text-gray-500">Students</dt>
                                <dd class="font-semibold text-gray-900 tabular-nums">{{ $studentQueries }}</dd>
                            </div>
                            <div class="flex justify-between items-baseline">
                                <dt class="text-gray-500">Teachers</dt>
                                <dd class="font-semibold text-gray-900 tabular-nums">{{ $teacherQueries }}</dd>
                            </div>
                            <div class="flex justify-between items-baseline">
                                <dt class="text-gray-500">Website</dt>
                                <dd class="font-semibold text-gray-900 tabular-nums">{{ $websiteQueries }}</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Quick links --}}
                    <div class="bg-white rounded-xl border border-gray-200">
                        <div class="px-5 py-3 border-b border-gray-100">
                            <h3 class="text-sm font-semibold text-gray-800">Quick Links</h3>
                        </div>
                        <div class="p-2">
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
                                    class="flex items-center justify-between px-3 py-2 hover:bg-gray-50 rounded-lg transition">
                                    <span class="text-sm text-gray-600">{{ $l[0] }}</span>
                                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- LMS rating --}}
                    <div class="bg-white rounded-xl border border-gray-200">
                        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-800">LMS Rating</h3>
                            <span class="text-sm font-semibold text-gray-900 tabular-nums">{{ number_format($averageRating, 1) }}<span class="text-gray-300 font-normal">/5</span></span>
                        </div>
                        @if ($latestRating)
                            <div class="p-5">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <svg class="w-3.5 h-3.5 {{ $i <= $latestRating->rating ? 'text-yellow-400' : 'text-gray-200' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                        @endfor
                                    </div>
                                    <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($latestRating->created_at)->format('d M, Y') }}</span>
                                </div>
                                <p class="text-sm text-gray-500 line-clamp-2">{{ $latestRating->feedback }}</p>
                                <a href="{{ route('admin.rate-lms', ['organization' => $organization]) }}"
                                    class="mt-4 w-full inline-flex justify-center items-center px-4 py-2 text-sm font-medium rounded-lg text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 transition">
                                    View Ratings
                                </a>
                            </div>
                        @else
                            <div class="p-6 text-center">
                                <p class="text-sm text-gray-400 mb-4">No ratings yet</p>
                                <a href="{{ route('admin.rate-lms', ['organization' => $organization]) }}"
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 transition">Rate LMS</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
