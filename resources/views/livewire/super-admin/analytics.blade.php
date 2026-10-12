<div class="min-h-screen bg-gray-50">

    {{-- All chart data lives in this attribute. It is part of the Livewire DOM,
         so every morph (month-window or website-range change) refreshes it and
         the script below re-reads it — charts always render with current data. --}}
    <div id="analytics-data" data-payload="{{ json_encode($chartPayload) }}" class="hidden"></div>

    @php
        $inr = fn ($v) => '₹' . number_format((float) $v, 0);
        $supportReplyRate = ($supportStats['total'] ?? 0) > 0
            ? round((($supportStats['replied'] ?? 0) / $supportStats['total']) * 100, 1)
            : 0;
        // The tabs are one page now: what each tab showed is a section below.
        $windowLabel = 'Last ' . $months . ' months';
    @endphp

    {{-- ══════════════════════════════════════════════════════════
         HEADER — title, date, the platform's headline figures and the
         window the monthly charts cover.
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
                        ['Schools', number_format($overviewStats['totalSchools'] ?? 0)],
                        ['Students', number_format($overviewStats['totalStudents'] ?? 0)],
                        ['Rating', ($ratingStats['avg'] ?? 0) . ' / 5'],
                    ];
                @endphp
                @foreach ($headline as [$label, $value])
                    <div>
                        <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wide">{{ $label }}</p>
                        <p class="text-base font-semibold text-gray-900 tabular-nums">{{ $value }}</p>
                    </div>
                @endforeach
                <select wire:model.live="months" title="Months on the monthly charts"
                    class="text-xs bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-indigo-400">
                    <option value="6">Last 6 months</option>
                    <option value="12">Last 12 months</option>
                    <option value="24">Last 24 months</option>
                </select>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 space-y-8">

        {{-- ═══════════════════════ KEY METRICS ═══════════════════════ --}}
        <section class="space-y-4">
            <x-admin.section-heading title="Key Metrics" />

            @php
                $growth = $overviewStats['studentGrowthPct'] ?? 0;
            @endphp
            @include('livewire.super-admin.partials.analytics-tiles', ['big' => true, 'cols' => 'grid-cols-2 xl:grid-cols-4', 'items' => [
                ['Schools', number_format($overviewStats['totalSchools'] ?? 0), number_format($overviewStats['activeSchools'] ?? 0) . ' active · ' . number_format($overviewStats['inactiveSchools'] ?? 0) . ' inactive', 'text-gray-900'],
                ['Students', number_format($overviewStats['totalStudents'] ?? 0), number_format($overviewStats['activeStudents'] ?? 0) . ' active', 'text-gray-900'],
                ['Teachers', number_format($overviewStats['totalTeachers'] ?? 0), number_format($overviewStats['activeTeachers'] ?? 0) . ' active', 'text-gray-900'],
                ['Avg / School', number_format($overviewStats['avgStudentsPerSchool'] ?? 0, 1), 'students in a school', 'text-gray-900'],
                ['New Schools', number_format($overviewStats['newSchoolsThisMonth'] ?? 0), 'this month', 'text-gray-900'],
                ['New Students', number_format($overviewStats['studentsThisMonth'] ?? 0), ($growth > 0 ? '+' : '') . $growth . '% vs last month (' . number_format($overviewStats['studentsLastMonth'] ?? 0) . ')', 'text-violet-600'],
                ['Rating', ($ratingStats['avg'] ?? 0) . ' ★', number_format($ratingStats['total'] ?? 0) . ' reviews', 'text-gray-900'],
                ['5-Star Reviews', number_format($ratingStats['fiveStar'] ?? 0), 'of ' . number_format($ratingStats['total'] ?? 0), 'text-emerald-600'],
            ]])
        </section>

        {{-- ═══════════════════════ SCHOOLS ═══════════════════════ --}}
        <section class="space-y-4">
            <x-admin.section-heading title="Schools" />

            {{-- Registrations + schools onboarded --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">Monthly Registrations</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Students and teachers added · {{ $windowLabel }}</p>
                        </div>
                        <div class="flex items-center gap-3 text-[11px] text-gray-400">
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-blue-500 inline-block"></span> Students</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-violet-500 inline-block"></span> Teachers</span>
                        </div>
                    </div>
                    <div class="h-56 relative">
                        <canvas id="overviewMonthlyChart"></canvas>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">Schools Onboarded</h3>
                            <p class="text-xs text-gray-400 mt-0.5">New schools · {{ $windowLabel }}</p>
                        </div>
                        <span class="flex items-center gap-1 text-[11px] text-gray-400"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-500 inline-block"></span> New schools</span>
                    </div>
                    <div class="h-56 relative">
                        <canvas id="schoolsMonthlyChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- School size buckets --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-800">Schools by Size</h3>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-5">
                    @foreach ($schoolBuckets as $label => $count)
                        <div class="px-5 py-4 -mr-px -mb-px border-r border-b border-gray-100">
                            <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">{{ $label }} students</p>
                            <p class="text-xl font-semibold text-gray-900 mt-1.5 tabular-nums">{{ number_format($count) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Top schools --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                @foreach ([['Top 5 by Students', $topByStudents, 'students', 'teachers'], ['Top 5 by Teachers', $topByTeachers, 'teachers', 'students']] as [$heading, $list, $main, $other])
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100">
                            <h3 class="text-sm font-semibold text-gray-800">{{ $heading }}</h3>
                        </div>
                        @if (count($list))
                            <div class="divide-y divide-gray-100">
                                @foreach ($list as $s)
                                    <div class="flex items-center gap-3 px-5 py-3">
                                        <span class="w-7 text-xs text-gray-300 tabular-nums flex-shrink-0">{{ $s['rank'] }}</span>
                                        @if ($s['logo'])
                                            <img src="{{ $s['logo'] }}" alt="" class="w-9 h-9 rounded-lg object-cover border border-gray-100 flex-shrink-0">
                                        @else
                                            <span class="w-9 h-9 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center text-xs font-bold flex-shrink-0">{{ strtoupper(mb_substr($s['name'], 0, 1)) }}</span>
                                        @endif
                                        <p class="flex-1 min-w-0 text-sm font-medium text-gray-800 truncate">{{ $s['name'] }}</p>
                                        <div class="text-right flex-shrink-0">
                                            <p class="text-sm font-semibold text-gray-900 tabular-nums">{{ number_format($s[$main]) }} <span class="text-xs font-normal text-gray-400">{{ $main }}</span></p>
                                            <p class="text-[10px] text-gray-400 tabular-nums">{{ number_format($s[$other]) }} {{ $other }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-center py-10 text-gray-400 text-sm">No schools yet</p>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Gender split + rating distribution --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h3 class="text-sm font-semibold text-gray-800 mb-4">Students by Gender</h3>
                    <div class="flex items-center gap-6">
                        <div class="h-44 w-44 relative flex-shrink-0 mx-auto sm:mx-0">
                            <canvas id="genderSplitChart"></canvas>
                        </div>
                        <div class="hidden sm:block flex-1 space-y-2.5 text-sm">
                            @foreach ([
                                ['label' => 'Male',    'key' => 'male',    'color' => 'bg-blue-500'],
                                ['label' => 'Female',  'key' => 'female',  'color' => 'bg-pink-500'],
                                ['label' => 'Other',   'key' => 'other',   'color' => 'bg-amber-400'],
                                ['label' => 'Not set', 'key' => 'unknown', 'color' => 'bg-gray-300'],
                            ] as $row)
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-sm {{ $row['color'] }} inline-block"></span>
                                    <span class="text-gray-500">{{ $row['label'] }}</span>
                                    <span class="font-semibold text-gray-900 ml-auto tabular-nums">{{ number_format($genderSplit[$row['key']] ?? 0) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-800">Rating Distribution</h3>
                        <span class="text-xs text-gray-400">{{ number_format($ratingStats['total'] ?? 0) }} reviews</span>
                    </div>
                    @if (($ratingStats['total'] ?? 0) > 0)
                        <div class="space-y-2.5">
                            @foreach ($ratingStats['distribution'] ?? [] as $star => $row)
                                <div class="flex items-center gap-3">
                                    <span class="w-8 text-xs text-gray-500 flex-shrink-0">{{ $star }} ★</span>
                                    <div class="flex-1 bg-gray-100 rounded-full h-1.5 min-w-0">
                                        <div class="h-1.5 rounded-full {{ $star >= 4 ? 'bg-emerald-500' : ($star === 3 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ $row['pct'] }}%"></div>
                                    </div>
                                    <span class="w-20 text-right text-xs text-gray-500 flex-shrink-0 tabular-nums">{{ $row['count'] }} · {{ $row['pct'] }}%</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 py-8 text-center">No reviews yet</p>
                    @endif
                </div>
            </div>
        </section>

        {{-- ═══════════════════════ WEBSITE ═══════════════════════ --}}
        <section class="space-y-4">
            <x-admin.section-heading title="Website" />

            {{-- Range: any dates within the last 90 days --}}
            <div class="flex flex-wrap items-center justify-end gap-2">
                {{-- diffInDays() of start-of-day to end-of-day is a fraction one
                     short of a whole day (29.9999… + 1 for 30 days), so the
                     count is the whole days in it. --}}
                @php $visitDays = (int) floor($websiteStats['days'] ?? 0); @endphp
                <span class="text-xs text-gray-400 mr-auto">
                    {{ $websiteStats['from'] ?? '' }} – {{ $websiteStats['to'] ?? '' }}
                    · {{ $visitDays }} {{ $visitDays === 1 ? 'day' : 'days' }}
                </span>
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wide mr-1">Range</span>
                <input type="date" wire:model.live="visitFrom" min="{{ $this->visitMinDate }}" max="{{ $this->visitMaxDate }}" aria-label="From"
                    class="text-xs bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-indigo-400">
                <span class="text-xs text-gray-400">to</span>
                <input type="date" wire:model.live="visitTo" min="{{ $this->visitMinDate }}" max="{{ $this->visitMaxDate }}" aria-label="To"
                    class="text-xs bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-indigo-400">
                @foreach ([7 => '7 days', 30 => '30 days', 90 => '90 days'] as $days => $lbl)
                    <button wire:click="setVisitRange({{ $days }})"
                        class="px-2.5 py-1.5 text-xs font-medium rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 transition-colors">
                        {{ $lbl }}
                    </button>
                @endforeach
            </div>

            @include('livewire.super-admin.partials.analytics-tiles', ['cols' => 'grid-cols-2 lg:grid-cols-5', 'items' => [
                ['Total Visits', number_format($websiteStats['totalVisits'] ?? 0), null, 'text-gray-900'],
                ['Unique Visitors', number_format($websiteStats['uniqueVisitors'] ?? 0), null, 'text-gray-900'],
                ['Avg / Day', number_format($websiteStats['avgPerDay'] ?? 0), null, 'text-gray-900'],
                ['Pages Tracked', number_format($websiteStats['pagesTracked'] ?? 0), null, 'text-gray-900'],
                ['Most Visited', $websiteStats['topPage'] ?? '—', null, 'text-gray-900'],
            ]])

            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-800">Visits Over Time</h3>
                    <div class="flex items-center gap-3 text-[11px] text-gray-400">
                        <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-blue-500 inline-block"></span> Visits</span>
                        <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-500 inline-block"></span> Unique</span>
                    </div>
                </div>
                <div class="h-64 relative">
                    <canvas id="websiteVisitsChart"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-800">Pages — Visits &amp; Users</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[560px]">
                        <thead class="bg-gray-50/70 text-gray-400 text-[11px] uppercase tracking-wide">
                            <tr>
                                <th class="px-5 py-2.5 text-left font-medium">Page</th>
                                <th class="px-4 py-2.5 text-right font-medium">Visits</th>
                                <th class="px-4 py-2.5 text-right font-medium">Unique Users</th>
                                <th class="px-5 py-2.5 text-left font-medium w-1/3">Share</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($websitePages as $row)
                                <tr class="hover:bg-gray-50/70">
                                    <td class="px-5 py-2.5 font-medium text-gray-800">{{ $row['page'] }}</td>
                                    <td class="px-4 py-2.5 text-right text-gray-700 tabular-nums">{{ number_format($row['visits']) }}</td>
                                    <td class="px-4 py-2.5 text-right text-gray-700 tabular-nums">{{ number_format($row['uniques']) }}</td>
                                    <td class="px-5 py-2.5">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-1 bg-gray-100 rounded-full h-1.5 min-w-0">
                                                <div class="h-1.5 rounded-full bg-blue-500" style="width: {{ $row['pct'] }}%"></div>
                                            </div>
                                            <span class="w-12 text-right text-xs text-gray-500 tabular-nums">{{ $row['pct'] }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-10 text-center">
                                        <p class="text-sm text-gray-400">No visits recorded for this range yet.</p>
                                        <p class="text-xs text-gray-400 mt-1">Traffic is tracked from the moment this feature goes live.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        {{-- ═══════════════════════ FEE ═══════════════════════ --}}
        <section class="space-y-4">
            <x-admin.section-heading title="Fee" />

            @include('livewire.super-admin.partials.analytics-tiles', ['items' => [
                ['To Collect', $inr($feeStats['totalFeeToCollect'] ?? 0), 'fee structures' . (!empty($feeStats['year']) ? ' · ' . $feeStats['year'] : ''), 'text-gray-900'],
                ['Collected', $inr($feeStats['totalCollected'] ?? 0), number_format($feeStats['payments'] ?? $feeStats['paidRecords'] ?? 0) . ' payments', 'text-emerald-600'],
                ['Remaining', $inr(max(0, $feeStats['totalRemaining'] ?? 0)), number_format($feeStats['schoolsDue'] ?? $feeStats['unpaidRecords'] ?? 0) . ' schools with dues', 'text-red-500'],
                ['Collection Rate', ($feeStats['collectionRate'] ?? 0) . '%', 'of the fee to collect', 'text-gray-900'],
                ['This Month', $inr($feeStats['collectedThisMonth'] ?? 0), 'collected', 'text-gray-900'],
                ['Last Month', $inr($feeStats['collectedLastMonth'] ?? 0), 'collected', 'text-gray-900'],
                ['Schools with Fee', number_format($feeStats['totalSchools'] ?? 0), 'have a fee structure', 'text-gray-900'],
                ['Avg / Student', $inr($feeStats['avgFeePerStudent'] ?? 0), 'fee to collect per student', 'text-gray-900'],
            ]])

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">Monthly Fee Collection</h3>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $windowLabel }}</p>
                        </div>
                        <span class="flex items-center gap-1 text-[11px] text-gray-400"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-500 inline-block"></span> Collected</span>
                    </div>
                    <div class="h-56 relative">
                        <canvas id="feeMonthlyChart"></canvas>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h3 class="text-sm font-semibold text-gray-800 mb-4">Collection by Payment Mode</h3>
                    @if (count($feeModes['data'] ?? []) > 0)
                        <div class="h-56 relative">
                            <canvas id="feeModeChart"></canvas>
                        </div>
                    @else
                        <p class="text-sm text-gray-400 py-8 text-center">No paid records yet</p>
                    @endif
                </div>
            </div>

            @if (count($schoolFeeList) > 0)
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-800">Schools Fee Breakdown</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Top 10 by fee{{ !empty($feeStats['year']) ? ' · ' . $feeStats['year'] : '' }}</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm min-w-[600px]">
                            <thead class="bg-gray-50/70 text-gray-400 text-[11px] uppercase tracking-wide">
                                <tr>
                                    <th class="px-5 py-2.5 text-left font-medium">School</th>
                                    <th class="px-4 py-2.5 text-right font-medium">To Collect</th>
                                    <th class="px-4 py-2.5 text-right font-medium">Collected</th>
                                    <th class="px-4 py-2.5 text-right font-medium">Remaining</th>
                                    <th class="px-5 py-2.5 text-right font-medium">Collected %</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($schoolFeeList as $row)
                                    <tr class="hover:bg-gray-50/70">
                                        <td class="px-5 py-2.5 font-medium text-gray-800 max-w-[240px] truncate">{{ $row['name'] }}</td>
                                        <td class="px-4 py-2.5 text-right text-gray-700 tabular-nums">{{ $inr($row['toCollect']) }}</td>
                                        <td class="px-4 py-2.5 text-right text-gray-700 tabular-nums">{{ $inr($row['collected']) }}</td>
                                        <td class="px-4 py-2.5 text-right tabular-nums {{ $row['remaining'] > 0 ? 'text-red-500' : 'text-gray-700' }}">{{ $inr($row['remaining']) }}</td>
                                        <td class="px-5 py-2.5">
                                            <div class="flex items-center justify-end gap-3">
                                                <div class="w-20 bg-gray-100 rounded-full h-1.5">
                                                    <div class="h-1.5 rounded-full bg-emerald-500" style="width: {{ min(100, $row['pct']) }}%"></div>
                                                </div>
                                                <span class="w-12 text-right text-xs font-semibold text-gray-700 tabular-nums">{{ $row['pct'] }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </section>

        {{-- ═══════════════════════ PAYROLL ═══════════════════════ --}}
        <section class="space-y-4">
            <x-admin.section-heading title="Payroll" />

            @include('livewire.super-admin.partials.analytics-tiles', ['items' => [
                ['Employees', number_format($payrollStats['totalEmployees'] ?? 0), null, 'text-gray-900'],
                ['Monthly Bill', $inr($payrollStats['totalMonthlySalaryBill'] ?? 0), 'active employees', 'text-gray-900'],
                ['Paid · ' . ($payrollStats['payableMonth'] ?? '—'), $inr($payrollStats['totalPaidAmount'] ?? 0), number_format($payrollStats['paidThisMonth'] ?? 0) . ' employees paid', 'text-emerald-600'],
                ['Unpaid · ' . ($payrollStats['payableMonth'] ?? '—'), number_format($payrollStats['pendingThisMonth'] ?? 0), 'employees', 'text-red-500'],
                ['Paid This Year', $inr($payrollStats['paidThisYear'] ?? 0), now()->format('Y'), 'text-gray-900'],
                ['Avg Salary', $inr($payrollStats['avgSalary'] ?? 0), null, 'text-gray-900'],
                ['Lowest Salary', $inr($payrollStats['lowestSalary'] ?? 0), 'active employees', 'text-gray-900'],
                ['Highest Salary', $inr($payrollStats['highestSalary'] ?? 0), null, 'text-gray-900'],
            ]])

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">Salary Paid per Month</h3>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $windowLabel }}</p>
                        </div>
                        <span class="flex items-center gap-1 text-[11px] text-gray-400"><span class="w-2.5 h-2.5 rounded-sm bg-violet-500 inline-block"></span> Paid out</span>
                    </div>
                    <div class="h-56 relative">
                        <canvas id="payrollMonthlyChart"></canvas>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h3 class="text-sm font-semibold text-gray-800 mb-4">Payouts by Mode</h3>
                    @if (count($payrollModes['data'] ?? []) > 0)
                        <div class="h-56 relative">
                            <canvas id="payrollModeChart"></canvas>
                        </div>
                    @else
                        <p class="text-sm text-gray-400 py-8 text-center">No paid records yet</p>
                    @endif
                </div>
            </div>

            @if (!empty($payrollStats['byType']))
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-800">Employees by Type</h3>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-5">
                        @foreach ($payrollStats['byType'] as $type => $row)
                            <div class="px-5 py-4 -mr-px -mb-px border-r border-b border-gray-100">
                                <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">{{ $type }}</p>
                                <p class="text-xl font-semibold text-gray-900 mt-1.5 tabular-nums">{{ $row['count'] }}</p>
                                <p class="mt-1.5 text-xs text-gray-400">{{ $inr($row['salary']) }} bill</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-800">Unpaid for {{ $payrollStats['payableMonth'] ?? '—' }}</h3>
                        <span class="text-xs text-gray-400 tabular-nums">{{ count($payrollPending) }}</span>
                    </div>
                    @if (count($payrollPending) > 0)
                        <div class="max-h-80 overflow-y-auto divide-y divide-gray-100">
                            @foreach ($payrollPending as $row)
                                <div class="px-5 py-3 flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate">{{ $row['name'] }}</p>
                                        <p class="text-xs text-gray-400 capitalize">{{ $row['designation'] ?: $row['type'] }}</p>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-700 flex-shrink-0 tabular-nums">{{ $inr($row['salary']) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 py-10 text-center">Everyone has been paid</p>
                    @endif
                </div>

                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-800">Recent Payouts</h3>
                    </div>
                    @if (count($payrollRecent) > 0)
                        <div class="max-h-80 overflow-y-auto divide-y divide-gray-100">
                            @foreach ($payrollRecent as $row)
                                <div class="px-5 py-3 flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate">{{ $row['name'] }}</p>
                                        <p class="text-xs text-gray-400">{{ $row['month'] }} · {{ $row['mode'] }} · {{ $row['date'] }}</p>
                                    </div>
                                    <span class="text-sm font-semibold text-emerald-600 flex-shrink-0 tabular-nums">{{ $inr($row['amount']) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 py-10 text-center">No payouts yet</p>
                    @endif
                </div>
            </div>
        </section>

        {{-- ═══════════════════════ ENQUIRIES ═══════════════════════ --}}
        <section class="space-y-4">
            <x-admin.section-heading title="Enquiries" />

            @include('livewire.super-admin.partials.analytics-tiles', ['items' => [
                ['Demo Requests', number_format($enquiryStats['demo']['total'] ?? 0), number_format($enquiryStats['demo']['thisMonth'] ?? 0) . ' this month · ' . number_format($enquiryStats['demo']['thisWeek'] ?? 0) . ' this week', 'text-gray-900'],
                ['Demo Pending', number_format($enquiryStats['demo']['pending'] ?? 0), 'not replied', 'text-red-500'],
                ['Demo Replied', number_format($enquiryStats['demo']['replied'] ?? 0), ($enquiryStats['demo']['replyRate'] ?? 0) . '% reply rate', 'text-emerald-600'],
                ['Total Enquiries', number_format($enquiryStats['combined']['total'] ?? 0), 'demo + contact', 'text-gray-900'],
                ['Contact Messages', number_format($enquiryStats['contact']['total'] ?? 0), number_format($enquiryStats['contact']['thisMonth'] ?? 0) . ' this month · ' . number_format($enquiryStats['contact']['thisWeek'] ?? 0) . ' this week', 'text-gray-900'],
                ['Contact Pending', number_format($enquiryStats['contact']['pending'] ?? 0), 'not replied', 'text-red-500'],
                ['Contact Replied', number_format($enquiryStats['contact']['replied'] ?? 0), ($enquiryStats['contact']['replyRate'] ?? 0) . '% reply rate', 'text-emerald-600'],
                ['Total Pending', number_format($enquiryStats['combined']['pending'] ?? 0), number_format($enquiryStats['combined']['replied'] ?? 0) . ' replied', 'text-red-500'],
            ]])

            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Monthly Enquiries</h3>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $windowLabel }}</p>
                    </div>
                    <div class="flex items-center gap-3 text-[11px] text-gray-400">
                        <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-blue-500 inline-block"></span> Demo</span>
                        <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-violet-500 inline-block"></span> Contact</span>
                    </div>
                </div>
                <div class="h-56 relative">
                    <canvas id="enquiryMonthlyChart"></canvas>
                </div>
            </div>
        </section>

        {{-- ═══════════════════════ SUPPORT ═══════════════════════ --}}
        <section class="space-y-4">
            <x-admin.section-heading title="Support" />

            @include('livewire.super-admin.partials.analytics-tiles', ['items' => [
                ['Total Tickets', number_format($supportStats['total'] ?? 0), ($supportStats['avgPerMonth'] ?? 0) . ' a month on average', 'text-gray-900'],
                ['Pending', number_format($supportStats['pending'] ?? 0), 'not replied', 'text-red-500'],
                ['Replied', number_format($supportStats['replied'] ?? 0), $supportReplyRate . '% reply rate', 'text-emerald-600'],
                ['This Month', number_format($supportStats['thisMonth'] ?? 0), number_format($supportStats['thisWeek'] ?? 0) . ' this week', 'text-gray-900'],
            ]])

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">Monthly Support Tickets</h3>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $windowLabel }}</p>
                        </div>
                        <div class="flex items-center gap-3 text-[11px] text-gray-400">
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-blue-500 inline-block"></span> Total</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-500 inline-block"></span> Replied</span>
                        </div>
                    </div>
                    <div class="h-56 relative">
                        <canvas id="supportMonthlyChart"></canvas>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col">
                    <h3 class="text-sm font-semibold text-gray-800 mb-4">Reply Rate</h3>
                    <p class="text-[26px] leading-none font-semibold text-gray-900 tabular-nums">{{ $supportReplyRate }}%</p>
                    <div class="w-full bg-gray-100 rounded-full h-1.5 mt-4">
                        <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ min(100, $supportReplyRate) }}%"></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">{{ number_format($supportStats['replied'] ?? 0) }} of {{ number_format($supportStats['total'] ?? 0) }} tickets replied</p>
                </div>
            </div>
        </section>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         CHART SCRIPTS
         Charts are rebuilt from #analytics-data after every refresh
         event, so month-window and website-range changes always render.
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

            // The school Analytics' chart look: no legend, quiet grid, small ticks.
            function options(money = false) {
                return {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: money ? {
                                label: ctx => (ctx.dataset.label ? ctx.dataset.label + ': ' : '') + inr(ctx.parsed.y ?? ctx.parsed)
                            } : {}
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 9 }, maxRotation: 0, autoSkipPadding: 8 } },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0,0,0,0.04)' },
                            ticks: {
                                font: { size: 10 }, precision: 0,
                                callback: money ? (v => inr(v)) : (v => v)
                            }
                        }
                    }
                };
            }

            function doughnut(labels, data, colors, money) {
                return {
                    type: 'doughnut',
                    data: { labels: labels, datasets: [{ data: data, backgroundColor: colors, borderWidth: 0, hoverOffset: 6 }] },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: money ? { position: 'bottom', labels: { font: { size: 11 }, padding: 12, boxWidth: 10, usePointStyle: true } } : { display: false },
                            tooltip: { callbacks: { label: ctx => ctx.label + ': ' + (money ? inr(ctx.parsed) : Number(ctx.parsed).toLocaleString('en-IN')) } }
                        }
                    }
                };
            }

            const bar = (label, data, color) => ({ label: label, data: data, backgroundColor: color, borderRadius: 3, borderSkipped: false, maxBarThickness: 34 });

            function initAnalyticsCharts() {
                if (typeof Chart === 'undefined') return;
                const p = payload();
                if (!p) return;
                const L = p.labels || [];

                // ── Schools ──
                makeChart('overviewMonthlyChart', {
                    type: 'bar',
                    data: { labels: L, datasets: [
                        bar('Students', p.overview.students, 'rgba(59,130,246,0.7)'),
                        bar('Teachers', p.overview.teachers, 'rgba(139,92,246,0.7)'),
                    ] },
                    options: options()
                });

                makeChart('schoolsMonthlyChart', {
                    type: 'line',
                    data: { labels: L, datasets: [
                        { label: 'Schools', data: p.overview.schools, borderColor: 'rgb(16,185,129)', backgroundColor: 'rgba(16,185,129,0.10)', fill: true, tension: 0.35, borderWidth: 2, pointRadius: 2 },
                    ] },
                    options: options()
                });

                makeChart('genderSplitChart', doughnut(
                    ['Male', 'Female', 'Other', 'Not set'],
                    [p.gender.male, p.gender.female, p.gender.other, p.gender.unknown],
                    ['#3b82f6', '#ec4899', '#fbbf24', '#d1d5db'],
                    false
                ));

                // ── Website ──
                if (p.website) {
                    makeChart('websiteVisitsChart', {
                        type: 'line',
                        data: { labels: p.website.labels || [], datasets: [
                            { label: 'Visits', data: p.website.visits || [], borderColor: 'rgb(59,130,246)', backgroundColor: 'rgba(59,130,246,0.10)', fill: true, tension: 0.35, borderWidth: 2, pointRadius: 2 },
                            { label: 'Unique', data: p.website.uniques || [], borderColor: 'rgb(16,185,129)', fill: false, tension: 0.35, borderWidth: 2, pointRadius: 2 },
                        ] },
                        options: Object.assign(options(), { interaction: { mode: 'index', intersect: false } })
                    });
                }

                // ── Fee ──
                makeChart('feeMonthlyChart', {
                    type: 'bar',
                    data: { labels: L, datasets: [bar('Collected', p.fee.collected, 'rgba(16,185,129,0.7)')] },
                    options: options(true)
                });
                makeChart('feeModeChart', doughnut(
                    p.fee.modes.labels || [], p.fee.modes.data || [],
                    ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#ef4444', '#14b8a6', '#6b7280'],
                    true
                ));

                // ── Payroll ──
                makeChart('payrollMonthlyChart', {
                    type: 'bar',
                    data: { labels: L, datasets: [bar('Salary paid', p.payroll.paid, 'rgba(139,92,246,0.7)')] },
                    options: options(true)
                });
                makeChart('payrollModeChart', doughnut(
                    p.payroll.modes.labels || [], p.payroll.modes.data || [],
                    ['#8b5cf6', '#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#14b8a6', '#6b7280'],
                    true
                ));

                // ── Enquiries ──
                makeChart('enquiryMonthlyChart', {
                    type: 'line',
                    data: { labels: L, datasets: [
                        { label: 'Demo', data: p.enquiry.demo, borderColor: 'rgb(59,130,246)', backgroundColor: 'rgba(59,130,246,0.10)', fill: true, tension: 0.35, borderWidth: 2, pointRadius: 2 },
                        { label: 'Contact', data: p.enquiry.contact, borderColor: 'rgb(139,92,246)', fill: false, tension: 0.35, borderWidth: 2, pointRadius: 2 },
                    ] },
                    options: Object.assign(options(), { interaction: { mode: 'index', intersect: false } })
                });

                // ── Support ──
                makeChart('supportMonthlyChart', {
                    type: 'bar',
                    data: { labels: L, datasets: [
                        bar('Total', p.support.total, 'rgba(59,130,246,0.7)'),
                        bar('Replied', p.support.replied, 'rgba(16,185,129,0.7)'),
                    ] },
                    options: options()
                });
            }

            document.addEventListener('DOMContentLoaded', initAnalyticsCharts);
            document.addEventListener('livewire:navigated', initAnalyticsCharts);
            // Fired by the component after month-window / website-range changes;
            // the small delay lets Livewire finish morphing the canvases first.
            window.addEventListener('analytics-refresh', () => setTimeout(initAnalyticsCharts, 60));

            initAnalyticsCharts();
        })();
    </script>

</div>
