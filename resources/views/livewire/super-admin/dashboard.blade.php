<div class="min-h-screen bg-gray-50">

    @php
        // ─── Derived figures, as the school dashboard works them out ───
        $studentsPresentToday = $todayAttendance['student_present'] ?? 0;
        $teachersPresentToday = $todayAttendance['teacher_present'] ?? 0;
        $studentAttPct = $totalStudents > 0 ? round(($studentsPresentToday / $totalStudents) * 100, 1) : 0;
        $teacherAttPct = $totalTeachers > 0 ? round(($teachersPresentToday / $totalTeachers) * 100, 1) : 0;
        $markedPct     = $activeSchools > 0 ? round(($schoolsMarkedToday / $activeSchools) * 100, 1) : 0;

        // Thirty-day series across every school, students and teachers apart.
        $trendLabels  = array_column($attendanceTrend, 'label');
        $trendStuPres = array_column($attendanceTrend, 'student_present');
        $trendStuAbs  = array_column($attendanceTrend, 'student_absent');
        $trendTchPres = array_column($attendanceTrend, 'teacher_present');
        $trendTchAbs  = array_column($attendanceTrend, 'teacher_absent');

        $pendingEnquiries = $enquiryStats['demo_pending'] + $enquiryStats['contact_pending'];
    @endphp

    {{-- ══════════════════════════════ HEADER ══════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">Dashboard</h1>
                <p class="text-xs text-gray-400 mt-0.5">{{ now()->format('l, d M Y') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="refreshData"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" wire:loading.class="animate-spin" wire:target="refreshData" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh
                </button>
                <a href="{{ route('super-admin.schools') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add School
                </a>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 space-y-8">

        {{-- ══════════════════════════════════════════════════════════════════
             OVERVIEW — one flat row of counts, then today's rates.
        ══════════════════════════════════════════════════════════════════ --}}
        <section>
            <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Overview</h2>

            <div class="bg-white rounded-xl border border-gray-200 divide-y sm:divide-y-0 sm:divide-x divide-gray-100 grid grid-cols-2 sm:grid-cols-4">
                @php
                    $counts = [
                        ['Schools',  number_format($totalSchools),  $activeSchools . ' active · ' . $newSchoolsThisMonth . ' new this month', 'super-admin.schools'],
                        ['Students', number_format($totalStudents), number_format($activeStudents) . ' active', 'super-admin.students'],
                        ['Teachers', number_format($totalTeachers), number_format($activeTeachers) . ' active', 'super-admin.teachers'],
                        ['Avg / School', number_format($avgStudentsPerSchool), 'students in a school', 'super-admin.analytics'],
                    ];
                @endphp
                @foreach ($counts as [$label, $value, $sub, $route])
                    <a href="{{ route($route) }}"
                        class="px-5 py-4 hover:bg-gray-50/70 transition-colors first:rounded-l-xl last:rounded-r-xl">
                        <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">{{ $label }}</p>
                        <p class="text-[26px] leading-none font-semibold text-gray-900 mt-2 tabular-nums">{{ $value }}</p>
                        <p class="text-xs text-gray-400 mt-1.5">{{ $sub }}</p>
                    </a>
                @endforeach
            </div>

            {{-- Today, across every school, with a thin progress rule underneath. --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
                @php
                    $todayCards = [
                        ['Student Attendance', $studentAttPct . '%', number_format($studentsPresentToday) . ' of ' . number_format($totalStudents) . ' present today', $studentAttPct],
                        ['Teacher Attendance', $teacherAttPct . '%', number_format($teachersPresentToday) . ' of ' . number_format($totalTeachers) . ' present today', $teacherAttPct],
                        ['Attendance Marked', $schoolsMarkedToday . ' / ' . $activeSchools, 'active schools have marked students today', $markedPct],
                    ];
                @endphp
                @foreach ($todayCards as [$label, $value, $sub, $pct])
                    <div class="bg-white rounded-xl border border-gray-200 px-5 py-4">
                        <div class="flex items-baseline justify-between gap-2">
                            <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">{{ $label }}</p>
                            <p class="text-lg font-semibold text-gray-900 tabular-nums">{{ $value }}</p>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1 mt-3">
                            <div class="bg-gray-800 h-1 rounded-full" style="width: {{ min(100, $pct) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">{{ $sub }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ══════════════════════════════ QUICK ACCESS ══════════════════════ --}}
        @if (count($quickTiles) > 0)
            <section>
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Quick Access</h2>
                <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 gap-3">
                    @foreach ($quickTiles as $q)
                        <a href="{{ route($q['route']) }}"
                            class="group bg-white rounded-xl border border-gray-200 px-3 py-4 flex flex-col items-center text-center gap-2.5 hover:border-gray-300 transition-colors">
                            <span class="w-10 h-10 rounded-lg bg-gray-100 text-gray-600 flex items-center justify-center transition-colors group-hover:bg-gray-900 group-hover:text-white">
                                <x-icon name="{{ $q['icon'] }}" class="w-5 h-5" />
                            </span>
                            <span class="text-xs font-medium text-gray-600 group-hover:text-gray-900">{{ $q['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ══════════════════════════════════════════════════════════════════
             ATTENDANCE TREND — every school's students and teachers, a chart
             each over the last 30 days, today's figures underneath.
        ══════════════════════════════════════════════════════════════════ --}}
        <section>
            <div class="mb-3">
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Attendance Trend</h2>
                <p class="text-xs text-gray-400 mt-0.5">All schools · last 30 days</p>
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
                    <div class="h-52" wire:ignore wire:key="sa-home-stu-trend">
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
                        <p class="text-[11px] text-gray-400 mb-2">{{ now()->format('D, d M Y') }}</p>
                        <div class="grid grid-cols-4 gap-2 text-center">
                            <div><p class="text-base font-semibold text-emerald-600 tabular-nums">{{ number_format($todayAttendance['student_present'] ?? 0) }}</p><p class="text-[10px] text-gray-400 uppercase">Present</p></div>
                            <div><p class="text-base font-semibold text-red-500 tabular-nums">{{ number_format($todayAttendance['student_absent'] ?? 0) }}</p><p class="text-[10px] text-gray-400 uppercase">Absent</p></div>
                            <div><p class="text-base font-semibold text-amber-500 tabular-nums">{{ number_format($todayAttendance['student_half'] ?? 0) }}</p><p class="text-[10px] text-gray-400 uppercase">Half</p></div>
                            <div><p class="text-base font-semibold text-gray-800 tabular-nums">{{ $todayAttendance['student_pct'] ?? 0 }}%</p><p class="text-[10px] text-gray-400 uppercase">Rate</p></div>
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
                    <div class="h-52" wire:ignore wire:key="sa-home-tch-trend">
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
                        <p class="text-[11px] text-gray-400 mb-2">{{ now()->format('D, d M Y') }}</p>
                        <div class="grid grid-cols-4 gap-2 text-center">
                            <div><p class="text-base font-semibold text-blue-600 tabular-nums">{{ number_format($todayAttendance['teacher_present'] ?? 0) }}</p><p class="text-[10px] text-gray-400 uppercase">Present</p></div>
                            <div><p class="text-base font-semibold text-red-500 tabular-nums">{{ number_format($todayAttendance['teacher_absent'] ?? 0) }}</p><p class="text-[10px] text-gray-400 uppercase">Absent</p></div>
                            <div><p class="text-base font-semibold text-amber-500 tabular-nums">{{ number_format($todayAttendance['teacher_half'] ?? 0) }}</p><p class="text-[10px] text-gray-400 uppercase">Half</p></div>
                            <div><p class="text-base font-semibold text-gray-800 tabular-nums">{{ $todayAttendance['teacher_pct'] ?? 0 }}%</p><p class="text-[10px] text-gray-400 uppercase">Rate</p></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ══════════════════════════════════════════════════════════════════
             FEE COLLECTION — what the schools took, one bar a day over the last
             30 days, and the Super Admin's own fee from the schools.
        ══════════════════════════════════════════════════════════════════ --}}
        <section>
            <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Fee Collection</h2>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5">
                    <div class="mb-4">
                        <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">Collected by schools · Last 30 days</p>
                        <p class="text-2xl font-semibold text-gray-900 mt-1 tabular-nums">₹{{ number_format($feeSummary['range_total'], 0) }}</p>
                    </div>
                    <div class="h-56" wire:ignore wire:key="sa-home-fee">
                        <canvas x-data="{
                            init() {
                                new Chart(this.$el.getContext('2d'), {
                                    type: 'bar',
                                    data: {
                                        labels: @js($feeSummary['labels']),
                                        datasets: [{ label: 'Collected', data: @js($feeSummary['data']), backgroundColor: 'rgba(99,102,241,0.7)', borderRadius: 3, borderSkipped: false, maxBarThickness: 34 }]
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

                <div class="space-y-5">
                    {{-- Schools' fee, academic + transport --}}
                    <div class="bg-white rounded-xl border border-gray-200 p-5">
                        <h3 class="text-sm font-semibold text-gray-800 mb-4">Schools' Fee</h3>
                        <dl class="space-y-3 text-sm">
                            <div class="flex items-baseline justify-between">
                                <dt class="text-gray-500">Collected today</dt>
                                <dd class="font-semibold text-gray-900 tabular-nums">₹{{ number_format($feeSummary['today'], 0) }}</dd>
                            </div>
                            <div class="flex items-baseline justify-between">
                                <dt class="text-gray-500">This month</dt>
                                <dd class="font-semibold text-gray-900 tabular-nums">₹{{ number_format($feeSummary['month'], 0) }}</dd>
                            </div>
                            <div class="flex items-baseline justify-between">
                                <dt class="text-gray-500">This session</dt>
                                <dd class="font-semibold text-gray-900 tabular-nums">₹{{ number_format($feeSummary['session'], 0) }}</dd>
                            </div>
                            <div class="flex items-baseline justify-between pt-3 border-t border-gray-100">
                                <dt class="text-gray-500">Schools collected today</dt>
                                <dd class="font-semibold text-gray-900 tabular-nums">{{ $feeSummary['schools_today'] }}</dd>
                            </div>
                        </dl>
                        <p class="text-[11px] text-gray-400 mt-3">Session from {{ $feeSummary['session_from'] }}</p>
                    </div>

                    {{-- Super Admin fee from the schools --}}
                    <div class="bg-white rounded-xl border border-gray-200 p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-semibold text-gray-800">Super Admin Fee</h3>
                            <a href="{{ route('super-admin.fees') }}" class="text-xs font-medium text-blue-600 hover:text-blue-800">Manage →</a>
                        </div>
                        <dl class="space-y-3 text-sm">
                            <div class="flex items-baseline justify-between">
                                <dt class="text-gray-500">Collected</dt>
                                <dd class="font-semibold text-gray-900 tabular-nums">₹{{ number_format($superAdminFee['collected'], 0) }}</dd>
                            </div>
                            <div class="flex items-baseline justify-between">
                                <dt class="text-gray-500">Pending</dt>
                                <dd class="font-semibold text-gray-900 tabular-nums">₹{{ number_format($superAdminFee['pending'], 0) }}</dd>
                            </div>
                            <div class="flex items-baseline justify-between">
                                <dt class="text-gray-500">This month</dt>
                                <dd class="font-semibold text-gray-900 tabular-nums">₹{{ number_format($superAdminFee['this_month'], 0) }}</dd>
                            </div>
                        </dl>
                        <div class="pt-4">
                            <div class="flex justify-between text-xs mb-1.5"><span class="text-gray-400">of ₹{{ number_format($superAdminFee['total'], 0) }}</span><span class="font-semibold text-gray-700">{{ $superAdminFee['pct'] }}%</span></div>
                            <div class="w-full bg-gray-100 rounded-full h-1.5"><div class="bg-gray-800 h-1.5 rounded-full" style="width: {{ min(100, $superAdminFee['pct']) }}%"></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ══════════════════════════════════════════════════════════════════
             SCHOOLS — every school today, registrations and new schools, plus
             the side column.
        ══════════════════════════════════════════════════════════════════ --}}
        <section>
            <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Schools</h2>

            {{-- Every school, A–Z, with today's figures --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-5">
                <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Schools Today</h3>
                        <p class="text-[11px] text-gray-400 mt-0.5">{{ count($schoolsToday) }} {{ count($schoolsToday) === 1 ? 'school' : 'schools' }} · attendance and fee for {{ now()->format('d M') }}</p>
                    </div>
                    <a href="{{ route('super-admin.schools') }}" class="text-xs font-medium text-blue-600 hover:text-blue-800">Open →</a>
                </div>
                <div class="overflow-x-auto max-h-[28rem] overflow-y-auto">
                    <table class="w-full text-sm min-w-[720px]">
                        <thead class="bg-gray-50 text-gray-400 text-[11px] uppercase tracking-wide sticky top-0 z-10">
                            <tr>
                                <th class="px-5 py-2.5 text-left font-medium">School</th>
                                <th class="px-3 py-2.5 text-right font-medium">Students</th>
                                <th class="px-3 py-2.5 text-right font-medium">Teachers</th>
                                <th class="px-3 py-2.5 text-right font-medium border-l border-gray-200">Students Present</th>
                                <th class="px-3 py-2.5 text-right font-medium">Teachers Present</th>
                                <th class="px-3 py-2.5 text-right font-medium border-l border-gray-200">Fee Today</th>
                                <th class="px-5 py-2.5 text-right font-medium">This Session</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($schoolsToday as $school)
                                <tr class="hover:bg-gray-50/70" wire:key="sa-school-today-{{ $school['id'] }}">
                                    <td class="px-5 py-2.5">
                                        <div class="flex items-center gap-3 min-w-0">
                                            @if ($school['logo'])
                                                <img src="{{ $school['logo'] }}" alt="" class="w-8 h-8 rounded-lg object-cover border border-gray-100 flex-shrink-0">
                                            @else
                                                <span class="w-8 h-8 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center text-xs font-semibold flex-shrink-0">{{ strtoupper(mb_substr($school['name'], 0, 1)) }}</span>
                                            @endif
                                            <div class="min-w-0">
                                                <p class="font-medium text-gray-800 truncate max-w-[220px]">{{ $school['name'] }}</p>
                                                @unless ($school['status'])
                                                    <p class="text-[11px] text-gray-400">Inactive</p>
                                                @endunless
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2.5 text-right text-gray-700 tabular-nums">{{ number_format($school['students']) }}</td>
                                    <td class="px-3 py-2.5 text-right text-gray-700 tabular-nums">{{ number_format($school['teachers']) }}</td>
                                    <td class="px-3 py-2.5 text-right tabular-nums border-l border-gray-200">
                                        @if ($school['student_marked'])
                                            <p class="font-medium text-gray-800">{{ $school['student_pct'] }}%</p>
                                            <p class="text-[11px] text-gray-400">{{ number_format($school['student_present']) }} of {{ number_format($school['students']) }}</p>
                                        @else
                                            <span class="text-xs text-gray-400">Not marked</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5 text-right tabular-nums">
                                        @if ($school['teacher_marked'])
                                            <p class="font-medium text-gray-800">{{ $school['teacher_pct'] }}%</p>
                                            <p class="text-[11px] text-gray-400">{{ number_format($school['teacher_present']) }} of {{ number_format($school['teachers']) }}</p>
                                        @else
                                            <span class="text-xs text-gray-400">Not marked</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5 text-right text-gray-700 tabular-nums border-l border-gray-200">₹{{ number_format($school['fee_today'], 0) }}</td>
                                    <td class="px-5 py-2.5 text-right text-gray-700 tabular-nums">₹{{ number_format($school['fee_session'], 0) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-10 text-center text-sm text-gray-400">No schools registered yet</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                {{-- LEFT --}}
                <div class="lg:col-span-2 space-y-5 min-w-0">

                    {{-- Monthly registrations --}}
                    <div class="bg-white rounded-xl border border-gray-200 p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-800">Monthly Registrations</h3>
                                <p class="text-[11px] text-gray-400 mt-0.5">Students and teachers added · last 12 months</p>
                            </div>
                            <div class="flex items-center gap-3 text-[11px] text-gray-400">
                                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-blue-500 inline-block"></span> Students</span>
                                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-violet-500 inline-block"></span> Teachers</span>
                            </div>
                        </div>
                        <div class="h-56" wire:ignore wire:key="sa-home-registrations">
                            <canvas x-data="{
                                init() {
                                    new Chart(this.$el.getContext('2d'), {
                                        type: 'bar',
                                        data: {
                                            labels: @js($monthlyRegistrations['labels']),
                                            datasets: [
                                                { label: 'Students', data: @js($monthlyRegistrations['students']), backgroundColor: 'rgba(59,130,246,0.7)', borderRadius: 3, borderSkipped: false, maxBarThickness: 22 },
                                                { label: 'Teachers', data: @js($monthlyRegistrations['teachers']), backgroundColor: 'rgba(139,92,246,0.7)', borderRadius: 3, borderSkipped: false, maxBarThickness: 22 }
                                            ]
                                        },
                                        options: {
                                            responsive: true, maintainAspectRatio: false,
                                            plugins: { legend: { display: false } },
                                            scales: {
                                                x: { grid: { display: false }, ticks: { font: { size: 9 }, maxRotation: 0, autoSkipPadding: 8 } },
                                                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, precision: 0 } }
                                            }
                                        }
                                    });
                                }
                            }"></canvas>
                        </div>
                    </div>

                    {{-- Recently registered schools --}}
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-800">Recently Registered Schools</h3>
                            <a href="{{ route('super-admin.schools') }}" class="text-xs font-medium text-blue-600 hover:text-blue-800">View all →</a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm min-w-[520px]">
                                <thead class="bg-gray-50/70 text-gray-400 text-[11px] uppercase tracking-wide">
                                    <tr>
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
                                            <td class="px-5 py-2.5">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    @if ($school['logo'])
                                                        <img src="{{ $school['logo'] }}" alt="" class="w-8 h-8 rounded-lg object-cover border border-gray-100 flex-shrink-0">
                                                    @else
                                                        <span class="w-8 h-8 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center text-xs font-semibold flex-shrink-0">{{ strtoupper(mb_substr($school['name'], 0, 1)) }}</span>
                                                    @endif
                                                    <p class="font-medium text-gray-800 truncate max-w-[220px]">{{ $school['name'] }}</p>
                                                </div>
                                            </td>
                                            <td class="px-3 py-2.5 text-right text-gray-700 tabular-nums">{{ number_format($school['students_count']) }}</td>
                                            <td class="px-3 py-2.5 text-right text-gray-700 tabular-nums">{{ number_format($school['teachers_count']) }}</td>
                                            <td class="px-3 py-2.5 text-gray-500 whitespace-nowrap">{{ $school['created_at'] }}</td>
                                            <td class="px-5 py-2.5 {{ $school['status'] ? 'text-gray-700' : 'text-red-500' }}">{{ $school['status'] ? 'Active' : 'Inactive' }}</td>
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
                </div>

                {{-- RIGHT --}}
                <div class="space-y-5 min-w-0">

                    {{-- Recent activity --}}
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100">
                            <h3 class="text-sm font-semibold text-gray-800">Recent Activity</h3>
                        </div>
                        <div class="divide-y divide-gray-100 overflow-y-auto max-h-[22rem]">
                            @forelse ($activityFeed as $activity)
                                <div class="px-5 py-3 flex items-start gap-3">
                                    <span class="w-8 h-8 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center flex-shrink-0">
                                        <x-icon name="{{ $activity['icon'] }}" class="w-4 h-4" />
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-800">{{ $activity['title'] }}</p>
                                        <p class="text-xs text-gray-400 truncate mt-0.5">{{ $activity['description'] }}</p>
                                        <p class="text-[11px] text-gray-400 mt-0.5">{{ $activity['time'] }}</p>
                                    </div>
                                </div>
                            @empty
                                <p class="px-5 py-8 text-center text-sm text-gray-400">No recent activity</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Support --}}
                    <div class="bg-white rounded-xl border border-gray-200">
                        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-800">Support</h3>
                            <a href="{{ route('super-admin.support') }}" class="text-xs font-medium text-blue-600 hover:text-blue-800">Open →</a>
                        </div>
                        <dl class="p-5 space-y-2.5 text-sm">
                            <div class="flex justify-between items-baseline">
                                <dt class="text-gray-500">Total tickets</dt>
                                <dd class="font-semibold text-gray-900 tabular-nums">{{ $supportStats['total'] }}</dd>
                            </div>
                            <div class="flex justify-between items-baseline">
                                <dt class="text-gray-500">Pending reply</dt>
                                <dd class="font-semibold tabular-nums {{ $supportStats['pending'] > 0 ? 'text-red-500' : 'text-gray-900' }}">{{ $supportStats['pending'] }}</dd>
                            </div>
                            <div class="flex justify-between items-baseline">
                                <dt class="text-gray-500">Replied</dt>
                                <dd class="font-semibold text-gray-900 tabular-nums">{{ $supportStats['replied'] }}</dd>
                            </div>
                            <div class="flex justify-between items-baseline">
                                <dt class="text-gray-500">This month</dt>
                                <dd class="font-semibold text-gray-900 tabular-nums">{{ $supportStats['this_month'] }}</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Enquiries --}}
                    <div class="bg-white rounded-xl border border-gray-200">
                        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-800">Enquiries</h3>
                            <a href="{{ route('super-admin.enquiries') }}" class="text-xs font-medium text-blue-600 hover:text-blue-800">Open →</a>
                        </div>
                        <dl class="p-5 space-y-2.5 text-sm">
                            <div class="flex justify-between items-baseline">
                                <dt class="text-gray-500">Demo requests</dt>
                                <dd class="font-semibold text-gray-900 tabular-nums">{{ $enquiryStats['demo_total'] }}</dd>
                            </div>
                            <div class="flex justify-between items-baseline">
                                <dt class="text-gray-500">Contact messages</dt>
                                <dd class="font-semibold text-gray-900 tabular-nums">{{ $enquiryStats['contact_total'] }}</dd>
                            </div>
                            <div class="flex justify-between items-baseline">
                                <dt class="text-gray-500">Pending</dt>
                                <dd class="font-semibold tabular-nums {{ $pendingEnquiries > 0 ? 'text-red-500' : 'text-gray-900' }}">{{ $pendingEnquiries }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </section>

        {{-- ══════════════════════════════ TOP SCHOOLS ═══════════════════════ --}}
        <section>
            <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Top Schools &amp; Rating</h2>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                @php
                    $boards = [
                        ['By Students', $topSchoolsByStudents, 'students_count', 'students'],
                        ['By Teachers', $topSchoolsByTeachers, 'teachers_count', 'teachers'],
                    ];
                @endphp
                @foreach ($boards as [$title, $rows, $key, $unit])
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-800">{{ $title }}</h3>
                            <span class="text-[11px] text-gray-400">Top 5</span>
                        </div>
                        <div class="divide-y divide-gray-100">
                            @forelse ($rows as $school)
                                <div class="px-5 py-2.5 flex items-center gap-3">
                                    <span class="w-5 text-sm font-semibold text-gray-400 tabular-nums">{{ $school['rank'] }}</span>
                                    @if ($school['logo'])
                                        <img src="{{ $school['logo'] }}" alt="" class="w-8 h-8 rounded-lg object-cover border border-gray-100 flex-shrink-0">
                                    @else
                                        <span class="w-8 h-8 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center text-xs font-semibold flex-shrink-0">{{ strtoupper(mb_substr($school['name'], 0, 1)) }}</span>
                                    @endif
                                    <p class="flex-1 min-w-0 text-sm font-medium text-gray-800 truncate">{{ $school['name'] }}</p>
                                    <p class="text-sm text-gray-700 tabular-nums">{{ number_format($school[$key]) }} <span class="text-xs text-gray-400">{{ $unit }}</span></p>
                                </div>
                            @empty
                                <p class="px-5 py-8 text-center text-sm text-gray-400">No schools yet</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach

                {{-- LMS rating --}}
                <div class="bg-white rounded-xl border border-gray-200">
                    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-800">LMS Rating</h3>
                        <span class="text-sm font-semibold text-gray-900 tabular-nums">{{ number_format($ratingStats['avg_rating'], 1) }}<span class="text-gray-300 font-normal">/5</span></span>
                    </div>
                    <div class="p-5">
                        <div class="flex mb-3">
                            @for ($i = 1; $i <= 5; $i++)
                                <svg class="w-4 h-4 {{ $i <= round($ratingStats['avg_rating']) ? 'text-yellow-400' : 'text-gray-200' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                            @endfor
                        </div>
                        <dl class="space-y-2.5 text-sm">
                            <div class="flex justify-between items-baseline">
                                <dt class="text-gray-500">Schools rated</dt>
                                <dd class="font-semibold text-gray-900 tabular-nums">{{ $ratingStats['schools_rated'] }}</dd>
                            </div>
                            <div class="flex justify-between items-baseline">
                                <dt class="text-gray-500">Not rated yet</dt>
                                <dd class="font-semibold text-gray-900 tabular-nums">{{ $ratingStats['remaining'] }}</dd>
                            </div>
                        </dl>
                        <a href="{{ route('super-admin.rating') }}"
                            class="mt-4 w-full inline-flex justify-center items-center px-4 py-2 text-sm font-medium rounded-lg text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 transition">
                            View Ratings
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
