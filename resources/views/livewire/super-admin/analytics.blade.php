<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════════════
         STICKY HEADER + TABS
    ══════════════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="px-4 sm:px-6 pt-4 pb-0">
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Analytics</h1>
            <p class="text-sm text-gray-500 mt-0.5 mb-3">Platform-wide statistics and trends</p>
        </div>
        <div class="px-4 sm:px-6 flex items-center gap-1 overflow-x-auto">
            @foreach ([
                'overview'  => 'Overview',
                'credit'    => 'Credit',
                'fee'       => 'Fee',
                'payroll'   => 'Payroll',
                'enquiries' => 'Enquiries',
                'support'   => 'Support',
            ] as $tab => $label)
                <button wire:click="setTab('{{ $tab }}')"
                    class="py-3 px-4 text-sm font-semibold border-b-2 whitespace-nowrap transition-colors
                           {{ $activeTab === $tab
                               ? 'border-emerald-600 text-emerald-700'
                               : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="p-4 sm:p-6">

        {{-- ══════════════════════════════════════════════════════════
             OVERVIEW TAB
        ══════════════════════════════════════════════════════════ --}}
        @if ($activeTab === 'overview')

            {{-- 4 Core stats --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                @foreach ([
                    ['label' => 'Total Schools',        'value' => $overviewStats['totalSchools'] ?? 0,         'color' => 'emerald', 'suffix' => ''],
                    ['label' => 'Total Students',        'value' => $overviewStats['totalStudents'] ?? 0,        'color' => 'blue',    'suffix' => ''],
                    ['label' => 'Total Teachers',        'value' => $overviewStats['totalTeachers'] ?? 0,        'color' => 'violet',  'suffix' => ''],
                    ['label' => 'Avg Students / School', 'value' => $overviewStats['avgStudentsPerSchool'] ?? 0, 'color' => 'amber',   'suffix' => ''],
                ] as $stat)
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                        <p class="text-xs text-gray-500 mb-2">{{ $stat['label'] }}</p>
                        <p class="text-3xl font-bold text-{{ $stat['color'] }}-600">{{ number_format($stat['value']) }}{{ $stat['suffix'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- School Size Buckets --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-6">
                <h2 class="text-sm font-bold text-gray-900 mb-4">Schools by Size</h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    @foreach ($schoolBuckets as $label => $count)
                        <div class="text-center p-4 bg-gray-50 rounded-xl">
                            <p class="text-2xl font-bold text-gray-800">{{ $count }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ $label }} students</p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Top Schools Tables --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                {{-- By Students --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="text-sm font-bold text-gray-900">Top 5 by Students</h2>
                    </div>
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">School</th>
                                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">Students</th>
                                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">Teachers</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($topByStudents as $s)
                                <tr class="hover:bg-gray-50/60">
                                    <td class="px-4 py-3 text-xs font-bold text-gray-400">{{ $s['rank'] }}</td>
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-800 max-w-[160px] truncate">{{ $s['name'] }}</td>
                                    <td class="px-4 py-3 text-right text-sm font-bold text-blue-600">{{ number_format($s['students']) }}</td>
                                    <td class="px-4 py-3 text-right text-sm font-semibold text-violet-500">{{ number_format($s['teachers']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400">No data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- By Teachers --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="text-sm font-bold text-gray-900">Top 5 by Teachers</h2>
                    </div>
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">School</th>
                                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">Teachers</th>
                                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">Students</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($topByTeachers as $s)
                                <tr class="hover:bg-gray-50/60">
                                    <td class="px-4 py-3 text-xs font-bold text-gray-400">{{ $s['rank'] }}</td>
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-800 max-w-[160px] truncate">{{ $s['name'] }}</td>
                                    <td class="px-4 py-3 text-right text-sm font-bold text-violet-600">{{ number_format($s['teachers']) }}</td>
                                    <td class="px-4 py-3 text-right text-sm font-semibold text-blue-500">{{ number_format($s['students']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400">No data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Monthly registrations chart --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-bold text-gray-900">Monthly Registrations (Last 12 Months)</h2>
                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-blue-500 inline-block"></span> Students</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-violet-500 inline-block"></span> Teachers</span>
                    </div>
                </div>
                <div style="height: 260px; position: relative;">
                    <canvas id="overviewMonthlyChart"></canvas>
                </div>
            </div>
        @endif

        {{-- ══════════════════════════════════════════════════════════
             CREDIT TAB
        ══════════════════════════════════════════════════════════ --}}
        @if ($activeTab === 'credit')

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                @foreach ([
                    ['label' => 'Total Applications', 'value' => $creditStats['total'] ?? 0,             'color' => 'gray'],
                    ['label' => 'Pending',             'value' => $creditStats['pending'] ?? 0,           'color' => 'amber'],
                    ['label' => 'Approved',            'value' => $creditStats['approved'] ?? 0,          'color' => 'emerald'],
                    ['label' => 'Denied',              'value' => $creditStats['denied'] ?? 0,            'color' => 'red'],
                ] as $s)
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                        <p class="text-xs text-gray-500 mb-2">{{ $s['label'] }}</p>
                        <p class="text-3xl font-bold text-{{ $s['color'] }}-600">{{ number_format($s['value']) }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs text-gray-500 mb-2">Total Amount Leased (Approved)</p>
                    <p class="text-2xl font-bold text-emerald-600">₹{{ number_format($creditStats['totalAmountLeased'] ?? 0, 0) }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs text-gray-500 mb-2">Amount Pending Approval</p>
                    <p class="text-2xl font-bold text-amber-600">₹{{ number_format($creditStats['totalPending'] ?? 0, 0) }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs text-gray-500 mb-2">Active Credits</p>
                    <p class="text-2xl font-bold text-blue-600">{{ number_format($creditStats['activeCredits'] ?? 0) }}</p>
                </div>
            </div>

            {{-- Credit Monthly Chart --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-bold text-gray-900">Monthly Credit Applications (Last 12 Months)</h2>
                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-blue-500 inline-block"></span> Applied</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span> Approved</span>
                    </div>
                </div>
                <div style="height: 260px; position: relative;">
                    <canvas id="creditMonthlyChart"></canvas>
                </div>
            </div>
        @endif

        {{-- ══════════════════════════════════════════════════════════
             FEE TAB
        ══════════════════════════════════════════════════════════ --}}
        @if ($activeTab === 'fee')

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                @foreach ([
                    ['label' => 'Schools with Fee',   'value' => number_format($feeStats['totalSchools'] ?? 0),                      'color' => 'gray',    'prefix' => ''],
                    ['label' => 'Total Fee Records',   'value' => number_format($feeStats['totalStudents'] ?? 0),                     'color' => 'blue',    'prefix' => ''],
                    ['label' => 'Total to Collect',    'value' => '₹' . number_format($feeStats['totalFeeToCollect'] ?? 0, 0),       'color' => 'gray',    'prefix' => ''],
                    ['label' => 'Total Collected',     'value' => '₹' . number_format($feeStats['totalCollected'] ?? 0, 0),          'color' => 'emerald', 'prefix' => ''],
                    ['label' => 'Remaining',           'value' => '₹' . number_format(max(0, $feeStats['totalRemaining'] ?? 0), 0),  'color' => 'red',     'prefix' => ''],
                    ['label' => 'Avg / Student',       'value' => '₹' . number_format($feeStats['avgFeePerStudent'] ?? 0, 0),        'color' => 'amber',   'prefix' => ''],
                ] as $s)
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                        <p class="text-xs text-gray-500 mb-1.5">{{ $s['label'] }}</p>
                        <p class="text-xl font-bold text-{{ $s['color'] }}-600">{{ $s['value'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Monthly Collection Chart --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-6">
                <h2 class="text-sm font-bold text-gray-900 mb-4">Monthly Fee Collection (Last 12 Months)</h2>
                <div style="height: 240px; position: relative;">
                    <canvas id="feeMonthlyChart"></canvas>
                </div>
            </div>

            {{-- School Fee List --}}
            @if (count($schoolFeeList) > 0)
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="text-sm font-bold text-gray-900">Schools Fee Breakdown (Top 10)</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-100">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">School</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">To Collect</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Collected</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Remaining</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">%</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach ($schoolFeeList as $row)
                                    <tr class="hover:bg-gray-50/60">
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-800 max-w-[200px] truncate">{{ $row['name'] }}</td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-700">₹{{ number_format($row['toCollect'], 0) }}</td>
                                        <td class="px-4 py-3 text-right text-sm font-semibold text-emerald-600">₹{{ number_format($row['collected'], 0) }}</td>
                                        <td class="px-4 py-3 text-right text-sm font-semibold text-red-500">₹{{ number_format($row['remaining'], 0) }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <div class="w-16 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                                    <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $row['pct'] }}%"></div>
                                                </div>
                                                <span class="text-xs font-semibold text-gray-700">{{ $row['pct'] }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endif

        {{-- ══════════════════════════════════════════════════════════
             PAYROLL TAB
        ══════════════════════════════════════════════════════════ --}}
        @if ($activeTab === 'payroll')

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs text-gray-500 mb-2">Total Employees</p>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($payrollStats['totalEmployees'] ?? 0) }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs text-gray-500 mb-2">Monthly Salary Bill</p>
                    <p class="text-2xl font-bold text-violet-600">₹{{ number_format($payrollStats['totalMonthlySalaryBill'] ?? 0, 0) }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs text-gray-500 mb-2">Paid This Month</p>
                    <p class="text-2xl font-bold text-emerald-600">₹{{ number_format($payrollStats['totalPaidAmount'] ?? 0, 0) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                @foreach ([
                    ['label' => 'Paid (This Month)',    'value' => $payrollStats['paidThisMonth'] ?? 0,    'color' => 'emerald'],
                    ['label' => 'Pending (This Month)', 'value' => $payrollStats['pendingThisMonth'] ?? 0, 'color' => 'amber'],
                    ['label' => 'Teachers',             'value' => $payrollStats['byType']['teacher'] ?? 0,'color' => 'blue'],
                    ['label' => 'Management',           'value' => $payrollStats['byType']['management'] ?? 0, 'color' => 'violet'],
                ] as $s)
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                        <p class="text-xs text-gray-500 mb-2">{{ $s['label'] }}</p>
                        <p class="text-2xl font-bold text-{{ $s['color'] }}-600">{{ number_format($s['value']) }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Employee type breakdown --}}
            @if (!empty($payrollStats['byType']))
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <h2 class="text-sm font-bold text-gray-900 mb-4">Employees by Type</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        @foreach ($payrollStats['byType'] as $type => $count)
                            <div class="flex flex-col items-center p-4 bg-gray-50 rounded-xl">
                                <p class="text-2xl font-bold text-gray-800">{{ $count }}</p>
                                <p class="text-xs text-gray-500 mt-1 capitalize">{{ $type }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif

        {{-- ══════════════════════════════════════════════════════════
             ENQUIRIES TAB
        ══════════════════════════════════════════════════════════ --}}
        @if ($activeTab === 'enquiries')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                {{-- Demo Enquiries --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-9 h-9 bg-blue-100 rounded-xl flex items-center justify-center">
                            <span class="text-base">🎬</span>
                        </div>
                        <h2 class="text-sm font-bold text-gray-900">Demo Enquiries</h2>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach ([
                            ['label' => 'Total',      'value' => $enquiryStats['demo']['total'] ?? 0,     'color' => 'gray'],
                            ['label' => 'Pending',    'value' => $enquiryStats['demo']['pending'] ?? 0,   'color' => 'amber'],
                            ['label' => 'Replied',    'value' => $enquiryStats['demo']['replied'] ?? 0,   'color' => 'emerald'],
                            ['label' => 'This Month', 'value' => $enquiryStats['demo']['thisMonth'] ?? 0, 'color' => 'blue'],
                        ] as $s)
                            <div class="p-3 bg-gray-50 rounded-xl">
                                <p class="text-xs text-gray-400 mb-1">{{ $s['label'] }}</p>
                                <p class="text-xl font-bold text-{{ $s['color'] }}-600">{{ $s['value'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Contact Enquiries --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-9 h-9 bg-violet-100 rounded-xl flex items-center justify-center">
                            <span class="text-base">✉️</span>
                        </div>
                        <h2 class="text-sm font-bold text-gray-900">Contact Enquiries</h2>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach ([
                            ['label' => 'Total',      'value' => $enquiryStats['contact']['total'] ?? 0,     'color' => 'gray'],
                            ['label' => 'Pending',    'value' => $enquiryStats['contact']['pending'] ?? 0,   'color' => 'amber'],
                            ['label' => 'Replied',    'value' => $enquiryStats['contact']['replied'] ?? 0,   'color' => 'emerald'],
                            ['label' => 'This Month', 'value' => $enquiryStats['contact']['thisMonth'] ?? 0, 'color' => 'violet'],
                        ] as $s)
                            <div class="p-3 bg-gray-50 rounded-xl">
                                <p class="text-xs text-gray-400 mb-1">{{ $s['label'] }}</p>
                                <p class="text-xl font-bold text-{{ $s['color'] }}-600">{{ $s['value'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Combined Totals --}}
            <div class="grid grid-cols-3 gap-4 mb-6">
                @foreach ([
                    ['label' => 'Combined Total', 'value' => $enquiryStats['combined']['total'] ?? 0,   'color' => 'gray'],
                    ['label' => 'Total Pending',  'value' => $enquiryStats['combined']['pending'] ?? 0, 'color' => 'amber'],
                    ['label' => 'Total Replied',  'value' => $enquiryStats['combined']['replied'] ?? 0, 'color' => 'emerald'],
                ] as $s)
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                        <p class="text-xs text-gray-500 mb-2">{{ $s['label'] }}</p>
                        <p class="text-3xl font-bold text-{{ $s['color'] }}-600">{{ $s['value'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Monthly Enquiry Chart --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-bold text-gray-900">Monthly Enquiries (Last 12 Months)</h2>
                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-blue-500 inline-block"></span> Demo</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-violet-500 inline-block"></span> Contact</span>
                    </div>
                </div>
                <div style="height: 260px; position: relative;">
                    <canvas id="enquiryMonthlyChart"></canvas>
                </div>
            </div>
        @endif

        {{-- ══════════════════════════════════════════════════════════
             SUPPORT TAB
        ══════════════════════════════════════════════════════════ --}}
        @if ($activeTab === 'support')

            <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
                @foreach ([
                    ['label' => 'Total Tickets', 'value' => $supportStats['total'] ?? 0,     'color' => 'gray'],
                    ['label' => 'Pending',        'value' => $supportStats['pending'] ?? 0,   'color' => 'amber'],
                    ['label' => 'Replied',        'value' => $supportStats['replied'] ?? 0,   'color' => 'emerald'],
                    ['label' => 'This Month',     'value' => $supportStats['thisMonth'] ?? 0, 'color' => 'blue'],
                    ['label' => 'This Week',      'value' => $supportStats['thisWeek'] ?? 0,  'color' => 'violet'],
                ] as $s)
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                        <p class="text-xs text-gray-500 mb-2">{{ $s['label'] }}</p>
                        <p class="text-2xl font-bold text-{{ $s['color'] }}-600">{{ $s['value'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Reply Rate --}}
            @php
                $replyRate = ($supportStats['total'] ?? 0) > 0
                    ? round((($supportStats['replied'] ?? 0) / ($supportStats['total'] ?? 1)) * 100, 1)
                    : 0;
            @endphp
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-6">
                <h2 class="text-sm font-bold text-gray-900 mb-3">Reply Rate</h2>
                <div class="flex items-center gap-4">
                    <div class="flex-1 h-3 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-500 rounded-full transition-all duration-500"
                            style="width: {{ $replyRate }}%"></div>
                    </div>
                    <span class="text-lg font-bold text-emerald-600 flex-shrink-0">{{ $replyRate }}%</span>
                </div>
                <p class="text-xs text-gray-400 mt-2">{{ $supportStats['replied'] ?? 0 }} of {{ $supportStats['total'] ?? 0 }} tickets have been replied</p>
            </div>

            {{-- Monthly Support Chart --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-bold text-gray-900">Monthly Support Tickets (Last 12 Months)</h2>
                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-blue-500 inline-block"></span> Total</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span> Replied</span>
                    </div>
                </div>
                <div style="height: 260px; position: relative;">
                    <canvas id="supportMonthlyChart"></canvas>
                </div>
            </div>
        @endif

    </div>

    {{-- ══════════════════════════════════════════════════════════
         CHART SCRIPTS
    ══════════════════════════════════════════════════════════ --}}
    <script>
        document.addEventListener('livewire:navigated', initAnalyticsCharts);
        document.addEventListener('DOMContentLoaded', initAnalyticsCharts);

        function initAnalyticsCharts() {
            const commonOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        padding: 10,
                        cornerRadius: 8,
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#9ca3af' } },
                    y: { grid: { color: '#f3f4f6' }, ticks: { font: { size: 10 }, color: '#9ca3af' }, beginAtZero: true }
                }
            };

            // Overview
            const overviewCtx = document.getElementById('overviewMonthlyChart');
            if (overviewCtx && !overviewCtx._chartInstance) {
                overviewCtx._chartInstance = new Chart(overviewCtx, {
                    type: 'bar',
                    data: {
                        labels: @json($monthlyRegistrations['labels'] ?? []),
                        datasets: [
                            { label: 'Students', data: @json($monthlyRegistrations['students'] ?? []), backgroundColor: 'rgba(59,130,246,0.7)', borderRadius: 5 },
                            { label: 'Teachers', data: @json($monthlyRegistrations['teachers'] ?? []), backgroundColor: 'rgba(139,92,246,0.7)', borderRadius: 5 },
                        ]
                    },
                    options: commonOptions
                });
            }

            // Credit
            const creditCtx = document.getElementById('creditMonthlyChart');
            if (creditCtx && !creditCtx._chartInstance) {
                creditCtx._chartInstance = new Chart(creditCtx, {
                    type: 'line',
                    data: {
                        labels: @json($creditMonthly['labels'] ?? []),
                        datasets: [
                            { label: 'Applied',  data: @json($creditMonthly['applications'] ?? []), borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.1)', fill: true, tension: 0.4, pointRadius: 3 },
                            { label: 'Approved', data: @json($creditMonthly['approved'] ?? []),     borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)',   fill: true, tension: 0.4, pointRadius: 3 },
                        ]
                    },
                    options: commonOptions
                });
            }

            // Fee
            const feeCtx = document.getElementById('feeMonthlyChart');
            if (feeCtx && !feeCtx._chartInstance) {
                feeCtx._chartInstance = new Chart(feeCtx, {
                    type: 'bar',
                    data: {
                        labels: @json($feeMonthly['labels'] ?? []),
                        datasets: [
                            { label: 'Collected', data: @json($feeMonthly['collected'] ?? []), backgroundColor: 'rgba(16,185,129,0.7)', borderRadius: 5 },
                        ]
                    },
                    options: commonOptions
                });
            }

            // Enquiries
            const enquiryCtx = document.getElementById('enquiryMonthlyChart');
            if (enquiryCtx && !enquiryCtx._chartInstance) {
                enquiryCtx._chartInstance = new Chart(enquiryCtx, {
                    type: 'line',
                    data: {
                        labels: @json($enquiryMonthly['labels'] ?? []),
                        datasets: [
                            { label: 'Demo',    data: @json($enquiryMonthly['demo'] ?? []),    borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.1)',  fill: true, tension: 0.4, pointRadius: 3 },
                            { label: 'Contact', data: @json($enquiryMonthly['contact'] ?? []), borderColor: '#8b5cf6', backgroundColor: 'rgba(139,92,246,0.1)', fill: true, tension: 0.4, pointRadius: 3 },
                        ]
                    },
                    options: commonOptions
                });
            }

            // Support
            const supportCtx = document.getElementById('supportMonthlyChart');
            if (supportCtx && !supportCtx._chartInstance) {
                supportCtx._chartInstance = new Chart(supportCtx, {
                    type: 'bar',
                    data: {
                        labels: @json($supportMonthly['labels'] ?? []),
                        datasets: [
                            { label: 'Total',   data: @json($supportMonthly['total'] ?? []),   backgroundColor: 'rgba(59,130,246,0.7)', borderRadius: 5 },
                            { label: 'Replied', data: @json($supportMonthly['replied'] ?? []), backgroundColor: 'rgba(16,185,129,0.7)', borderRadius: 5 },
                        ]
                    },
                    options: commonOptions
                });
            }
        }
    </script>

</div>
