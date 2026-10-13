<div class="min-h-screen bg-gray-50">

    @php
        // ─── The Super Admin's own figures, laid out in the school panel's look ───
        $inr = fn ($v) => '₹' . number_format((float) $v, 0);
        $supportPct = ($supportStats['total'] ?? 0) > 0
            ? round(($supportStats['replied'] / $supportStats['total']) * 100, 1)
            : 0;
        $staffNotMarked = max(0, $staff['active'] - $staff['today']['marked']);
    @endphp

    {{-- ══════════════════════════════ HEADER ══════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:min-h-9">
            <div class="min-w-0 sm:flex sm:items-baseline sm:gap-2">
                <h1 class="min-w-0 text-lg sm:text-xl font-bold text-gray-900 truncate">Dashboard</h1>
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
             OVERVIEW — the platform's counts in one flat row, then where the
             Super Admin's own work stands.
        ══════════════════════════════════════════════════════════════════ --}}
        <section>
            <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Overview</h2>

            <div class="bg-white rounded-xl border border-gray-200 divide-y sm:divide-y-0 sm:divide-x divide-gray-100 grid grid-cols-2 sm:grid-cols-4">
                @php
                    $counts = [
                        ['Schools',  number_format($totalSchools),  $activeSchools . ' active · ' . $newSchoolsThisMonth . ' new this month', 'super-admin.schools'],
                        ['Students', number_format($totalStudents), number_format($activeStudents) . ' active', 'super-admin.students'],
                        ['Teachers', number_format($totalTeachers), number_format($activeTeachers) . ' active', 'super-admin.teachers'],
                        ['Staff',    number_format($staff['employees']), number_format($staff['active']) . ' active', 'super-admin.payroll'],
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

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mt-4">
                @php
                    $statusCards = [
                        ['Super Admin Fee', $superAdminFee['pct'] . '%', $inr($superAdminFee['collected']) . ' of ' . $inr($superAdminFee['total']) . ' collected', $superAdminFee['pct']],
                        ['Salary · ' . $staff['month'], $staff['paid_count'] . ' / ' . $staff['active'], $inr($staff['paid_amount']) . ' paid · ' . $staff['unpaid_count'] . ' to pay', $staff['paid_pct']],
                        ['Staff Today', $staff['today']['marked'] > 0 ? $staff['today']['pct'] . '%' : '—', $staff['today']['marked'] > 0 ? $staff['today']['present'] . ' of ' . $staff['active'] . ' present' : 'Not marked yet today', $staff['today']['pct']],
                        ['Support Replied', $supportPct . '%', $supportStats['pending'] . ' of ' . $supportStats['total'] . ' tickets waiting', $supportPct],
                    ];
                @endphp
                @foreach ($statusCards as [$label, $value, $sub, $pct])
                    <div class="bg-white rounded-xl border border-gray-200 px-5 py-4">
                        <div class="flex items-baseline justify-between gap-2">
                            <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide truncate">{{ $label }}</p>
                            <p class="text-lg font-semibold text-gray-900 tabular-nums whitespace-nowrap">{{ $value }}</p>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1 mt-3">
                            <div class="bg-gray-800 h-1 rounded-full" style="width: {{ min(100, $pct) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2 truncate">{{ $sub }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ══════════════════════════ NEEDS ATTENTION ═══════════════════════
             What is waiting on the Super Admin, each figure opening its screen.
        ══════════════════════════════════════════════════════════════════ --}}
        @if (count($waiting) > 0)
            <section>
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Needs Attention</h2>
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6">
                    @foreach ($waiting as $w)
                        <a href="{{ route($w['route']) }}"
                            class="px-5 py-4 -mr-px -mb-px border-r border-b border-gray-100 hover:bg-gray-50/70 transition-colors min-w-0">
                            <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide truncate">{{ $w['label'] }}</p>
                            <p class="text-[26px] leading-none font-semibold mt-2 tabular-nums {{ $w['count'] > 0 ? 'text-red-500' : 'text-gray-900' }}">{{ number_format($w['count']) }}</p>
                            <p class="text-xs text-gray-400 mt-1.5">{{ $w['sub'] }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ══════════════════════════════════════════════════════════════════
             FEE — the Super Admin's fee from the schools, month by month.
        ══════════════════════════════════════════════════════════════════ --}}
        <section>
            <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Super Admin Fee</h2>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5">
                    <div class="mb-4">
                        <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">Collected · Last 12 months</p>
                        <p class="text-2xl font-semibold text-gray-900 mt-1 tabular-nums">{{ $inr($superAdminFee['year_total']) }}</p>
                    </div>
                    <div class="h-56" wire:ignore wire:key="sa-home-fee">
                        <canvas x-data="{
                            init() {
                                new Chart(this.$el.getContext('2d'), {
                                    type: 'bar',
                                    data: {
                                        labels: @js($superAdminFee['labels']),
                                        datasets: [{ label: 'Collected', data: @js($superAdminFee['data']), backgroundColor: 'rgba(99,102,241,0.7)', borderRadius: 3, borderSkipped: false, maxBarThickness: 34 }]
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

                <div class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-800">Overall</h3>
                        <a href="{{ route('super-admin.fees') }}" class="text-xs font-medium text-blue-600 hover:text-blue-800">Manage →</a>
                    </div>
                    <dl class="space-y-3 text-sm">
                        <div class="flex items-baseline justify-between">
                            <dt class="text-gray-500">Collected</dt>
                            <dd class="font-semibold text-gray-900 tabular-nums">{{ $inr($superAdminFee['collected']) }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between">
                            <dt class="text-gray-500">Pending</dt>
                            <dd class="font-semibold text-gray-900 tabular-nums">{{ $inr($superAdminFee['pending']) }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between pt-3 border-t border-gray-100">
                            <dt class="text-gray-500">This month</dt>
                            <dd class="font-semibold text-gray-900 tabular-nums">{{ $inr($superAdminFee['this_month']) }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between">
                            <dt class="text-gray-500">Last month</dt>
                            <dd class="font-semibold text-gray-900 tabular-nums">{{ $inr($superAdminFee['last_month']) }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between">
                            <dt class="text-gray-500">Schools with fee</dt>
                            <dd class="font-semibold text-gray-900 tabular-nums">{{ number_format($superAdminFee['schools']) }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between">
                            <dt class="text-gray-500">Schools with dues</dt>
                            <dd class="font-semibold tabular-nums {{ $superAdminFee['schools_due'] > 0 ? 'text-red-500' : 'text-gray-900' }}">{{ number_format($superAdminFee['schools_due']) }}</dd>
                        </div>
                    </dl>
                    <div class="mt-auto pt-5">
                        <div class="flex justify-between text-xs mb-1.5"><span class="text-gray-400">of {{ $inr($superAdminFee['total']) }}</span><span class="font-semibold text-gray-700">{{ $superAdminFee['pct'] }}%</span></div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5"><div class="bg-gray-800 h-1.5 rounded-full" style="width: {{ min(100, $superAdminFee['pct']) }}%"></div></div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ══════════════════════════════════════════════════════════════════
             SCHOOLS — registrations and new schools, the biggest schools.
        ══════════════════════════════════════════════════════════════════ --}}
        <section>
            <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Schools</h2>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
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

                {{-- Top schools --}}
                <div class="space-y-5 min-w-0">
                    @foreach ([['Top Schools by Students', $topSchoolsByStudents, 'students_count', 'students'], ['Top Schools by Teachers', $topSchoolsByTeachers, 'teachers_count', 'teachers']] as [$title, $rows, $key, $unit])
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
                                        <p class="text-sm text-gray-700 tabular-nums whitespace-nowrap">{{ number_format($school[$key]) }} <span class="text-xs text-gray-400">{{ $unit }}</span></p>
                                    </div>
                                @empty
                                    <p class="px-5 py-8 text-center text-sm text-gray-400">No schools yet</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ══════════════════════════════════════════════════════════════════
             REQUESTS — schools' tickets and website enquiries, the newest
             first; the platform's own activity and website beside them.
        ══════════════════════════════════════════════════════════════════ --}}
        <section>
            <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Requests</h2>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="lg:col-span-2 space-y-5 min-w-0">
                    {{-- Support tickets --}}
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-800">Latest Support Tickets</h3>
                                <p class="text-[11px] text-gray-400 mt-0.5">{{ $supportStats['this_month'] }} this month · {{ $supportStats['total'] }} in all</p>
                            </div>
                            <a href="{{ route('super-admin.support') }}" class="text-xs font-medium text-blue-600 hover:text-blue-800">Open →</a>
                        </div>
                        <div class="divide-y divide-gray-100">
                            @forelse ($latestTickets as $t)
                                <div class="px-5 py-3 flex items-center gap-3">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate">{{ $t['topic'] }}</p>
                                        <p class="text-xs text-gray-400 truncate">{{ $t['school'] }} · {{ $t['time'] }}</p>
                                    </div>
                                    <span class="text-xs flex-shrink-0 {{ $t['replied'] ? 'text-gray-500' : 'text-red-500' }}">{{ $t['replied'] ? 'Replied' : 'Pending' }}</span>
                                </div>
                            @empty
                                <p class="px-5 py-8 text-center text-sm text-gray-400">No support tickets yet</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Enquiries --}}
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-800">Latest Enquiries</h3>
                                <p class="text-[11px] text-gray-400 mt-0.5">{{ $enquiryStats['demo_total'] }} demo requests · {{ $enquiryStats['contact_total'] }} contact messages</p>
                            </div>
                            <a href="{{ route('super-admin.enquiries') }}" class="text-xs font-medium text-blue-600 hover:text-blue-800">Open →</a>
                        </div>
                        <div class="divide-y divide-gray-100">
                            @forelse ($latestEnquiries as $e)
                                <div class="px-5 py-3 flex items-center gap-3">
                                    <span class="w-14 text-xs text-gray-400 flex-shrink-0">{{ $e['kind'] }}</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate">{{ $e['name'] ?: '—' }}</p>
                                        <p class="text-xs text-gray-400 truncate">{{ $e['school'] ?: '—' }} · {{ $e['time'] }}</p>
                                    </div>
                                    <span class="text-xs flex-shrink-0 {{ $e['answered'] ? 'text-gray-500' : 'text-red-500' }}">{{ $e['answered'] ? 'Answered' : 'Pending' }}</span>
                                </div>
                            @empty
                                <p class="px-5 py-8 text-center text-sm text-gray-400">No enquiries yet</p>
                            @endforelse
                        </div>
                    </div>
                </div>

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

                    {{-- Website, last 7 days --}}
                    <div class="bg-white rounded-xl border border-gray-200">
                        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-800">Website · Last 7 Days</h3>
                            <a href="{{ route('super-admin.analytics') }}" class="text-xs font-medium text-blue-600 hover:text-blue-800">Analytics →</a>
                        </div>
                        <dl class="p-5 space-y-2.5 text-sm">
                            <div class="flex justify-between items-baseline">
                                <dt class="text-gray-500">Visits</dt>
                                <dd class="font-semibold text-gray-900 tabular-nums">{{ number_format($websiteWeek['visits']) }}</dd>
                            </div>
                            <div class="flex justify-between items-baseline">
                                <dt class="text-gray-500">Unique visitors</dt>
                                <dd class="font-semibold text-gray-900 tabular-nums">{{ number_format($websiteWeek['unique']) }}</dd>
                            </div>
                            <div class="flex justify-between items-baseline">
                                <dt class="text-gray-500">Today</dt>
                                <dd class="font-semibold text-gray-900 tabular-nums">{{ number_format($websiteWeek['today']) }}</dd>
                            </div>
                            <div class="flex justify-between items-baseline gap-3">
                                <dt class="text-gray-500 flex-shrink-0">Most visited</dt>
                                <dd class="font-semibold text-gray-900 truncate">{{ $websiteWeek['top'] }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </section>

        {{-- ══════════════════════════════════════════════════════════════════
             TEAM & PLATFORM — the Super Admin's staff, notifications and the
             schools' rating of the LMS.
        ══════════════════════════════════════════════════════════════════ --}}
        <section>
            <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Team &amp; Platform</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
                {{-- Salary --}}
                <div class="bg-white rounded-xl border border-gray-200 flex flex-col">
                    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-800">Salary · {{ $staff['month'] }}</h3>
                        <a href="{{ route('super-admin.payroll') }}" class="text-xs font-medium text-blue-600 hover:text-blue-800">Payroll →</a>
                    </div>
                    <dl class="px-5 pt-4 space-y-2.5 text-sm">
                        <div class="flex justify-between items-baseline">
                            <dt class="text-gray-500">Monthly bill</dt>
                            <dd class="font-semibold text-gray-900 tabular-nums">{{ $inr($staff['bill']) }}</dd>
                        </div>
                        <div class="flex justify-between items-baseline">
                            <dt class="text-gray-500">Paid</dt>
                            <dd class="font-semibold text-gray-900 tabular-nums">{{ $inr($staff['paid_amount']) }}</dd>
                        </div>
                    </dl>
                    <div class="px-5 pt-4 pb-5">
                        <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide mb-2">To pay · {{ $staff['unpaid_count'] }}</p>
                        @forelse ($staff['unpaid'] as $e)
                            <div class="flex items-baseline justify-between gap-3 py-1">
                                <p class="text-sm text-gray-700 truncate">{{ $e['name'] }} <span class="text-xs text-gray-400">{{ $e['role'] }}</span></p>
                                <p class="text-sm text-gray-700 tabular-nums flex-shrink-0">{{ $inr($e['salary']) }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400">Everyone has been paid</p>
                        @endforelse
                    </div>
                </div>

                {{-- Staff attendance today --}}
                <div class="bg-white rounded-xl border border-gray-200">
                    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-800">Staff Today</h3>
                        <span class="text-[11px] text-gray-400">{{ now()->format('d M') }}</span>
                    </div>
                    <dl class="p-5 space-y-2.5 text-sm">
                        @foreach ([['Present', 'present', 'text-gray-900'], ['Absent', 'absent', 'text-red-500'], ['Half day', 'half_day', 'text-gray-900'], ['Leave', 'leave', 'text-gray-900'], ['Holiday', 'holiday', 'text-gray-900']] as [$label, $key, $tone])
                            <div class="flex justify-between items-baseline">
                                <dt class="text-gray-500">{{ $label }}</dt>
                                <dd class="font-semibold tabular-nums {{ $staff['today'][$key] > 0 ? $tone : 'text-gray-900' }}">{{ $staff['today'][$key] }}</dd>
                            </div>
                        @endforeach
                        <div class="flex justify-between items-baseline pt-2.5 border-t border-gray-100">
                            <dt class="text-gray-500">Not marked</dt>
                            <dd class="font-semibold text-gray-900 tabular-nums">{{ $staffNotMarked }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Push notifications --}}
                <div class="bg-white rounded-xl border border-gray-200">
                    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-800">Push Notifications</h3>
                        <a href="{{ route('super-admin.push-notification') }}" class="text-xs font-medium text-blue-600 hover:text-blue-800">Open →</a>
                    </div>
                    <div class="p-5">
                        <dl class="space-y-2.5 text-sm">
                            <div class="flex justify-between items-baseline">
                                <dt class="text-gray-500">Sent this month</dt>
                                <dd class="font-semibold text-gray-900 tabular-nums">{{ $pushSummary['this_month'] }}</dd>
                            </div>
                            <div class="flex justify-between items-baseline">
                                <dt class="text-gray-500">Sent in all</dt>
                                <dd class="font-semibold text-gray-900 tabular-nums">{{ $pushSummary['total'] }}</dd>
                            </div>
                        </dl>
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            @if ($pushSummary['latest'])
                                <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">Latest</p>
                                <p class="text-sm font-medium text-gray-800 mt-1 truncate">{{ $pushSummary['latest']['title'] }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ number_format($pushSummary['latest']['recipients']) }} people · {{ $pushSummary['latest']['time'] }}</p>
                            @else
                                <p class="text-sm text-gray-400">Nothing sent yet</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- LMS rating --}}
                <div class="bg-white rounded-xl border border-gray-200">
                    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-800">LMS Rating</h3>
                        <span class="text-sm font-semibold text-gray-900 tabular-nums">{{ number_format($ratingStats['avg_rating'], 1) }}<span class="text-gray-300 font-normal">/5</span></span>
                    </div>
                    <div class="p-5">
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
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            @if ($latestReview)
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <svg class="w-3.5 h-3.5 {{ $i <= $latestReview['rating'] ? 'text-yellow-400' : 'text-gray-200' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                        @endfor
                                    </div>
                                    <span class="text-xs text-gray-400">{{ $latestReview['time'] }}</span>
                                </div>
                                <p class="text-sm font-medium text-gray-800 mt-1.5 truncate">{{ $latestReview['school'] }}</p>
                                @if ($latestReview['feedback'])
                                    <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ $latestReview['feedback'] }}</p>
                                @endif
                            @else
                                <p class="text-sm text-gray-400">No reviews yet</p>
                            @endif
                            <a href="{{ route('super-admin.rating') }}"
                                class="mt-4 w-full inline-flex justify-center items-center px-4 py-2 text-sm font-medium rounded-lg text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 transition">
                                View Ratings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
