<div class="min-h-screen bg-gray-50">

    {{-- All chart data lives in this attribute. It is part of the Livewire DOM,
         so every morph (tab switch / month-window change) refreshes it and the
         script below re-reads it — charts always render with current data. --}}
    <div id="analytics-data" data-payload="{{ json_encode($chartPayload) }}" class="hidden"></div>

    {{-- ══════════════════════════════════════════════════════════
         STICKY HEADER + TABS
    ══════════════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="px-4 sm:px-6 pt-4 pb-0 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Analytics</h1>
                <p class="text-sm text-gray-500 mt-0.5 mb-3">Platform-wide statistics and trends</p>
            </div>
            <div class="flex items-center gap-2">
                <label class="text-xs text-gray-500">Trend window</label>
                <select wire:model.live="months"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="6">Last 6 months</option>
                    <option value="12">Last 12 months</option>
                    <option value="24">Last 24 months</option>
                </select>
            </div>
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
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                @foreach ([
                    ['label' => 'Total Schools',        'value' => number_format($overviewStats['totalSchools'] ?? 0),        'color' => 'emerald', 'sub' => ($overviewStats['activeSchools'] ?? 0) . ' active · ' . ($overviewStats['inactiveSchools'] ?? 0) . ' pending'],
                    ['label' => 'Total Students',        'value' => number_format($overviewStats['totalStudents'] ?? 0),       'color' => 'blue',    'sub' => number_format($overviewStats['activeStudents'] ?? 0) . ' active'],
                    ['label' => 'Total Teachers',        'value' => number_format($overviewStats['totalTeachers'] ?? 0),       'color' => 'violet',  'sub' => number_format($overviewStats['activeTeachers'] ?? 0) . ' active'],
                    ['label' => 'Avg Students / School', 'value' => number_format($overviewStats['avgStudentsPerSchool'] ?? 0, 1), 'color' => 'amber', 'sub' => 'across all schools'],
                ] as $stat)
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                        <p class="text-xs text-gray-500 mb-2">{{ $stat['label'] }}</p>
                        <p class="text-3xl font-bold text-{{ $stat['color'] }}-600">{{ $stat['value'] }}</p>
                        <p class="text-[11px] text-gray-400 mt-1">{{ $stat['sub'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Growth + rating row --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs text-gray-500 mb-2">New Schools (This Month)</p>
                    <p class="text-2xl font-bold text-emerald-600">{{ number_format($overviewStats['newSchoolsThisMonth'] ?? 0) }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs text-gray-500 mb-2">New Students (This Month)</p>
                    <div class="flex items-end gap-2">
                        <p class="text-2xl font-bold text-blue-600">{{ number_format($overviewStats['studentsThisMonth'] ?? 0) }}</p>
                        @php $g = $overviewStats['studentGrowthPct'] ?? 0; @endphp
                        <span class="text-xs font-semibold mb-1 {{ $g >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                            {{ $g >= 0 ? '▲' : '▼' }} {{ abs($g) }}%
                        </span>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-1">vs {{ number_format($overviewStats['studentsLastMonth'] ?? 0) }} last month</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs text-gray-500 mb-2">Platform Rating</p>
                    <p class="text-2xl font-bold text-amber-500">★ {{ $ratingStats['avg'] ?? 0 }}</p>
                    <p class="text-[11px] text-gray-400 mt-1">{{ number_format($ratingStats['total'] ?? 0) }} reviews</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs text-gray-500 mb-2">5-Star Reviews</p>
                    <p class="text-2xl font-bold text-emerald-600">{{ number_format($ratingStats['fiveStar'] ?? 0) }}</p>
                    <p class="text-[11px] text-gray-400 mt-1">{{ $ratingStats['distribution'][5]['pct'] ?? 0 }}% of all reviews</p>
                </div>
            </div>

            {{-- School Size Buckets --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-6">
                <h2 class="text-sm font-bold text-gray-900 mb-4">Schools by Size</h2>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
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

            {{-- Monthly registrations + schools onboarded --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-bold text-gray-900">Monthly Registrations</h2>
                        <div class="flex items-center gap-3 text-xs text-gray-500">
                            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-blue-500 inline-block"></span> Students</span>
                            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-violet-500 inline-block"></span> Teachers</span>
                        </div>
                    </div>
                    <div style="height: 240px; position: relative;">
                        <canvas id="overviewMonthlyChart"></canvas>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-bold text-gray-900">Schools Onboarded</h2>
                        <span class="flex items-center gap-1.5 text-xs text-gray-500"><span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span> New schools</span>
                    </div>
                    <div style="height: 240px; position: relative;">
                        <canvas id="schoolsMonthlyChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- Gender split + rating distribution --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <h2 class="text-sm font-bold text-gray-900 mb-4">Students by Gender</h2>
                    <div class="flex items-center gap-6">
                        <div style="height: 190px; width: 190px; position: relative;" class="flex-shrink-0 mx-auto sm:mx-0">
                            <canvas id="genderSplitChart"></canvas>
                        </div>
                        <div class="hidden sm:block space-y-2 text-sm">
                            @foreach ([
                                ['label' => 'Male',        'key' => 'male',    'color' => 'bg-blue-500'],
                                ['label' => 'Female',      'key' => 'female',  'color' => 'bg-pink-500'],
                                ['label' => 'Other',       'key' => 'other',   'color' => 'bg-amber-400'],
                                ['label' => 'Not set',     'key' => 'unknown', 'color' => 'bg-gray-300'],
                            ] as $row)
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full {{ $row['color'] }} inline-block"></span>
                                    <span class="text-gray-600">{{ $row['label'] }}</span>
                                    <span class="font-bold text-gray-800 ml-auto pl-6">{{ number_format($genderSplit[$row['key']] ?? 0) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <h2 class="text-sm font-bold text-gray-900 mb-4">Rating Distribution</h2>
                    @if (($ratingStats['total'] ?? 0) > 0)
                        <div class="space-y-2.5">
                            @foreach ($ratingStats['distribution'] ?? [] as $star => $row)
                                <div class="flex items-center gap-3">
                                    <span class="text-xs font-semibold text-gray-600 w-8">{{ $star }} ★</span>
                                    <div class="flex-1 h-2.5 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full {{ $star >= 4 ? 'bg-emerald-500' : ($star === 3 ? 'bg-amber-400' : 'bg-red-400') }}"
                                            style="width: {{ $row['pct'] }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-500 w-16 text-right">{{ $row['count'] }} ({{ $row['pct'] }}%)</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 py-8 text-center">No reviews yet</p>
                    @endif
                </div>
            </div>
        @endif

        {{-- ══════════════════════════════════════════════════════════
             CREDIT TAB
        ══════════════════════════════════════════════════════════ --}}
        @if ($activeTab === 'credit')

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
                @foreach ([
                    ['label' => 'Total Applications', 'value' => $creditStats['total'] ?? 0,    'color' => 'gray'],
                    ['label' => 'Pending',             'value' => $creditStats['pending'] ?? 0,  'color' => 'amber'],
                    ['label' => 'Approved',            'value' => $creditStats['approved'] ?? 0, 'color' => 'emerald'],
                    ['label' => 'Denied',              'value' => $creditStats['denied'] ?? 0,   'color' => 'red'],
                ] as $s)
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                        <p class="text-xs text-gray-500 mb-2">{{ $s['label'] }}</p>
                        <p class="text-3xl font-bold text-{{ $s['color'] }}-600">{{ number_format($s['value']) }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
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

            {{-- Deeper credit detail --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs text-gray-500 mb-2">Approval Rate</p>
                    <p class="text-2xl font-bold text-emerald-600">{{ $creditStats['approvalRate'] ?? 0 }}%</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs text-gray-500 mb-2">Avg Approved Amount</p>
                    <p class="text-2xl font-bold text-blue-600">₹{{ number_format($creditStats['avgApproved'] ?? 0, 0) }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs text-gray-500 mb-2">Recovered</p>
                    <p class="text-2xl font-bold text-teal-600">₹{{ number_format($creditStats['amountCollected'] ?? 0, 0) }}</p>
                    <p class="text-[11px] text-gray-400 mt-1">{{ $creditStats['collectedCount'] ?? 0 }} credits collected</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs text-gray-500 mb-2">Outstanding</p>
                    <p class="text-2xl font-bold text-red-500">₹{{ number_format($creditStats['amountOutstanding'] ?? 0, 0) }}</p>
                    <p class="text-[11px] text-gray-400 mt-1">yet to be recovered</p>
                </div>
            </div>

            {{-- Credit charts --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-bold text-gray-900">Monthly Applications</h2>
                        <div class="flex items-center gap-3 text-xs text-gray-500">
                            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-blue-500 inline-block"></span> Applied</span>
                            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span> Approved</span>
                        </div>
                    </div>
                    <div style="height: 240px; position: relative;">
                        <canvas id="creditMonthlyChart"></canvas>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-bold text-gray-900">Amount Leased per Month (₹)</h2>
                        <span class="flex items-center gap-1.5 text-xs text-gray-500"><span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span> Approved amount</span>
                    </div>
                    <div style="height: 240px; position: relative;">
                        <canvas id="creditAmountChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- Top schools by credit --}}
            @if (count($topCreditSchools) > 0)
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="text-sm font-bold text-gray-900">Top Schools by Approved Credit</h2>
                    </div>
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">School</th>
                                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">Credits</th>
                                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($topCreditSchools as $row)
                                <tr class="hover:bg-gray-50/60">
                                    <td class="px-4 py-3 text-xs font-bold text-gray-400">{{ $row['rank'] }}</td>
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-800 max-w-[220px] truncate">{{ $row['name'] }}</td>
                                    <td class="px-4 py-3 text-right text-sm font-semibold text-blue-600">{{ $row['queries'] }}</td>
                                    <td class="px-4 py-3 text-right text-sm font-bold text-emerald-600">₹{{ number_format($row['amount'], 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif

        {{-- ══════════════════════════════════════════════════════════
             FEE TAB
        ══════════════════════════════════════════════════════════ --}}
        @if ($activeTab === 'fee')

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-4">
                @foreach ([
                    ['label' => 'Schools with Fee',   'value' => number_format($feeStats['totalSchools'] ?? 0),                     'color' => 'gray'],
                    ['label' => 'Total Fee Records',   'value' => number_format($feeStats['totalStudents'] ?? 0),                    'color' => 'blue'],
                    ['label' => 'Total to Collect',    'value' => '₹' . number_format($feeStats['totalFeeToCollect'] ?? 0, 0),      'color' => 'gray'],
                    ['label' => 'Total Collected',     'value' => '₹' . number_format($feeStats['totalCollected'] ?? 0, 0),         'color' => 'emerald'],
                    ['label' => 'Remaining',           'value' => '₹' . number_format(max(0, $feeStats['totalRemaining'] ?? 0), 0), 'color' => 'red'],
                    ['label' => 'Avg / Student',       'value' => '₹' . number_format($feeStats['avgFeePerStudent'] ?? 0, 0),       'color' => 'amber'],
                ] as $s)
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                        <p class="text-xs text-gray-500 mb-1.5">{{ $s['label'] }}</p>
                        <p class="text-xl font-bold text-{{ $s['color'] }}-600">{{ $s['value'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Collection health row --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs text-gray-500">Collection Rate</p>
                        <span class="text-lg font-bold text-emerald-600">{{ $feeStats['collectionRate'] ?? 0 }}%</span>
                    </div>
                    <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-500 rounded-full transition-all duration-500" style="width: {{ min(100, $feeStats['collectionRate'] ?? 0) }}%"></div>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-2">{{ number_format($feeStats['paidRecords'] ?? 0) }} paid · {{ number_format($feeStats['unpaidRecords'] ?? 0) }} unpaid records</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs text-gray-500 mb-2">Collected This Month</p>
                    <p class="text-2xl font-bold text-emerald-600">₹{{ number_format($feeStats['collectedThisMonth'] ?? 0, 0) }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs text-gray-500 mb-2">Collected Last Month</p>
                    <p class="text-2xl font-bold text-blue-600">₹{{ number_format($feeStats['collectedLastMonth'] ?? 0, 0) }}</p>
                </div>
            </div>

            {{-- Fee charts --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-bold text-gray-900">Monthly Fee Collection (₹)</h2>
                        <span class="flex items-center gap-1.5 text-xs text-gray-500"><span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span> Collected</span>
                    </div>
                    <div style="height: 240px; position: relative;">
                        <canvas id="feeMonthlyChart"></canvas>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <h2 class="text-sm font-bold text-gray-900 mb-4">Collection by Payment Mode</h2>
                    @if (count($feeModes['data'] ?? []) > 0)
                        <div style="height: 210px; position: relative;">
                            <canvas id="feeModeChart"></canvas>
                        </div>
                    @else
                        <p class="text-sm text-gray-400 py-8 text-center">No paid records yet</p>
                    @endif
                </div>
            </div>

            {{-- School Fee List --}}
            @if (count($schoolFeeList) > 0)
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="text-sm font-bold text-gray-900">Schools Fee Breakdown (Top 10 by amount)</h2>
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

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-4">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs text-gray-500 mb-2">Total Employees</p>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($payrollStats['totalEmployees'] ?? 0) }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs text-gray-500 mb-2">Monthly Salary Bill</p>
                    <p class="text-2xl font-bold text-violet-600">₹{{ number_format($payrollStats['totalMonthlySalaryBill'] ?? 0, 0) }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs text-gray-500 mb-2">Paid for {{ $payrollStats['payableMonth'] ?? '—' }}</p>
                    <p class="text-2xl font-bold text-emerald-600">₹{{ number_format($payrollStats['totalPaidAmount'] ?? 0, 0) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
                @foreach ([
                    ['label' => 'Paid (' . ($payrollStats['payableMonth'] ?? '—') . ')',    'value' => number_format($payrollStats['paidThisMonth'] ?? 0),    'color' => 'emerald'],
                    ['label' => 'Pending (' . ($payrollStats['payableMonth'] ?? '—') . ')', 'value' => number_format($payrollStats['pendingThisMonth'] ?? 0), 'color' => 'amber'],
                    ['label' => 'Avg Salary',    'value' => '₹' . number_format($payrollStats['avgSalary'] ?? 0, 0),     'color' => 'blue'],
                    ['label' => 'Highest Salary','value' => '₹' . number_format($payrollStats['highestSalary'] ?? 0, 0), 'color' => 'violet'],
                    ['label' => 'Paid This Year','value' => '₹' . number_format($payrollStats['paidThisYear'] ?? 0, 0),  'color' => 'teal'],
                ] as $s)
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                        <p class="text-xs text-gray-500 mb-2">{{ $s['label'] }}</p>
                        <p class="text-xl font-bold text-{{ $s['color'] }}-600">{{ $s['value'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Salary paid per month chart --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-bold text-gray-900">Salary Paid per Month (₹)</h2>
                    <span class="flex items-center gap-1.5 text-xs text-gray-500"><span class="w-3 h-3 rounded-full bg-violet-500 inline-block"></span> Paid out</span>
                </div>
                <div style="height: 240px; position: relative;">
                    <canvas id="payrollMonthlyChart"></canvas>
                </div>
            </div>

            {{-- Employee type breakdown (dynamic — all payroll types) --}}
            @if (!empty($payrollStats['byType']))
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <h2 class="text-sm font-bold text-gray-900 mb-4">Employees by Type</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
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
                        <span class="ml-auto text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">{{ $enquiryStats['demo']['replyRate'] ?? 0 }}% replied</span>
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
                    <div class="mt-3">
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $enquiryStats['demo']['replyRate'] ?? 0 }}%"></div>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1.5">{{ $enquiryStats['demo']['thisWeek'] ?? 0 }} new this week</p>
                    </div>
                </div>

                {{-- Contact Enquiries --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-9 h-9 bg-violet-100 rounded-xl flex items-center justify-center">
                            <span class="text-base">✉️</span>
                        </div>
                        <h2 class="text-sm font-bold text-gray-900">Contact Enquiries</h2>
                        <span class="ml-auto text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">{{ $enquiryStats['contact']['replyRate'] ?? 0 }}% replied</span>
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
                    <div class="mt-3">
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $enquiryStats['contact']['replyRate'] ?? 0 }}%"></div>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1.5">{{ $enquiryStats['contact']['thisWeek'] ?? 0 }} new this week</p>
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
                    <h2 class="text-sm font-bold text-gray-900">Monthly Enquiries</h2>
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

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                @foreach ([
                    ['label' => 'Total Tickets', 'value' => $supportStats['total'] ?? 0,       'color' => 'gray'],
                    ['label' => 'Pending',        'value' => $supportStats['pending'] ?? 0,     'color' => 'amber'],
                    ['label' => 'Replied',        'value' => $supportStats['replied'] ?? 0,     'color' => 'emerald'],
                    ['label' => 'This Month',     'value' => $supportStats['thisMonth'] ?? 0,   'color' => 'blue'],
                    ['label' => 'This Week',      'value' => $supportStats['thisWeek'] ?? 0,    'color' => 'violet'],
                    ['label' => 'Avg / Month',    'value' => $supportStats['avgPerMonth'] ?? 0, 'color' => 'teal'],
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
                    <h2 class="text-sm font-bold text-gray-900">Monthly Support Tickets</h2>
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
         Charts are rebuilt from #analytics-data after every refresh
         event, so tab switches and month-window changes always render.
    ══════════════════════════════════════════════════════════ --}}
    <script>
        (function () {
            const registry = {};

            function payload() {
                const el = document.getElementById('analytics-data');
                if (!el || !el.dataset.payload) return null;
                try { return JSON.parse(el.dataset.payload); } catch (e) { return null; }
            }

            function makeChart(id, config) {
                const el = document.getElementById(id);
                if (registry[id]) { try { registry[id].destroy(); } catch (e) {} delete registry[id]; }
                if (!el) return;
                registry[id] = new Chart(el, config);
            }

            const inr = v => '₹' + Number(v).toLocaleString('en-IN');

            function options(money = false) {
                return {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1f2937',
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: money ? {
                                label: ctx => (ctx.dataset.label ? ctx.dataset.label + ': ' : '') + inr(ctx.parsed.y ?? ctx.parsed)
                            } : {}
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#9ca3af' } },
                        y: {
                            grid: { color: '#f3f4f6' },
                            ticks: {
                                font: { size: 10 }, color: '#9ca3af',
                                callback: money ? (v => '₹' + Number(v).toLocaleString('en-IN')) : (v => v)
                            },
                            beginAtZero: true
                        }
                    }
                };
            }

            function initAnalyticsCharts() {
                if (typeof Chart === 'undefined') return;
                const p = payload();
                if (!p) return;
                const L = p.labels || [];

                // ── Overview ──
                makeChart('overviewMonthlyChart', {
                    type: 'bar',
                    data: {
                        labels: L,
                        datasets: [
                            { label: 'Students', data: p.overview.students, backgroundColor: 'rgba(59,130,246,0.7)', borderRadius: 5 },
                            { label: 'Teachers', data: p.overview.teachers, backgroundColor: 'rgba(139,92,246,0.7)', borderRadius: 5 },
                        ]
                    },
                    options: options()
                });

                makeChart('schoolsMonthlyChart', {
                    type: 'line',
                    data: {
                        labels: L,
                        datasets: [
                            { label: 'Schools', data: p.overview.schools, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.4, pointRadius: 3 },
                        ]
                    },
                    options: options()
                });

                makeChart('genderSplitChart', {
                    type: 'doughnut',
                    data: {
                        labels: ['Male', 'Female', 'Other', 'Not set'],
                        datasets: [{
                            data: [p.gender.male, p.gender.female, p.gender.other, p.gender.unknown],
                            backgroundColor: ['#3b82f6', '#ec4899', '#fbbf24', '#d1d5db'],
                            borderWidth: 2,
                            borderColor: '#fff',
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '62%',
                        plugins: {
                            legend: { display: false },
                            tooltip: { backgroundColor: '#1f2937', padding: 10, cornerRadius: 8 }
                        }
                    }
                });

                // ── Credit ──
                makeChart('creditMonthlyChart', {
                    type: 'line',
                    data: {
                        labels: L,
                        datasets: [
                            { label: 'Applied',  data: p.credit.applications, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.1)', fill: true, tension: 0.4, pointRadius: 3 },
                            { label: 'Approved', data: p.credit.approved,     borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.4, pointRadius: 3 },
                        ]
                    },
                    options: options()
                });

                makeChart('creditAmountChart', {
                    type: 'bar',
                    data: {
                        labels: L,
                        datasets: [
                            { label: 'Amount leased', data: p.credit.amount, backgroundColor: 'rgba(16,185,129,0.7)', borderRadius: 5 },
                        ]
                    },
                    options: options(true)
                });

                // ── Fee ──
                makeChart('feeMonthlyChart', {
                    type: 'bar',
                    data: {
                        labels: L,
                        datasets: [
                            { label: 'Collected', data: p.fee.collected, backgroundColor: 'rgba(16,185,129,0.7)', borderRadius: 5 },
                        ]
                    },
                    options: options(true)
                });

                makeChart('feeModeChart', {
                    type: 'doughnut',
                    data: {
                        labels: p.fee.modes.labels || [],
                        datasets: [{
                            data: p.fee.modes.data || [],
                            backgroundColor: ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#ef4444', '#14b8a6', '#6b7280'],
                            borderWidth: 2,
                            borderColor: '#fff',
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '58%',
                        plugins: {
                            legend: { position: 'bottom', labels: { font: { size: 10 }, color: '#6b7280', boxWidth: 10 } },
                            tooltip: {
                                backgroundColor: '#1f2937', padding: 10, cornerRadius: 8,
                                callbacks: { label: ctx => ctx.label + ': ' + inr(ctx.parsed) }
                            }
                        }
                    }
                });

                // ── Payroll ──
                makeChart('payrollMonthlyChart', {
                    type: 'bar',
                    data: {
                        labels: L,
                        datasets: [
                            { label: 'Salary paid', data: p.payroll.paid, backgroundColor: 'rgba(139,92,246,0.7)', borderRadius: 5 },
                        ]
                    },
                    options: options(true)
                });

                // ── Enquiries ──
                makeChart('enquiryMonthlyChart', {
                    type: 'line',
                    data: {
                        labels: L,
                        datasets: [
                            { label: 'Demo',    data: p.enquiry.demo,    borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.1)',  fill: true, tension: 0.4, pointRadius: 3 },
                            { label: 'Contact', data: p.enquiry.contact, borderColor: '#8b5cf6', backgroundColor: 'rgba(139,92,246,0.1)', fill: true, tension: 0.4, pointRadius: 3 },
                        ]
                    },
                    options: options()
                });

                // ── Support ──
                makeChart('supportMonthlyChart', {
                    type: 'bar',
                    data: {
                        labels: L,
                        datasets: [
                            { label: 'Total',   data: p.support.total,   backgroundColor: 'rgba(59,130,246,0.7)', borderRadius: 5 },
                            { label: 'Replied', data: p.support.replied, backgroundColor: 'rgba(16,185,129,0.7)', borderRadius: 5 },
                        ]
                    },
                    options: options()
                });
            }

            document.addEventListener('DOMContentLoaded', initAnalyticsCharts);
            document.addEventListener('livewire:navigated', initAnalyticsCharts);
            // Fired by the component after tab / month-window changes; the small
            // delay lets Livewire finish morphing the new canvases in first.
            window.addEventListener('analytics-refresh', () => setTimeout(initAnalyticsCharts, 60));

            initAnalyticsCharts();
        })();
    </script>

</div>
