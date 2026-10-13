<div class="min-h-screen bg-gray-50">
    @php
        // "CLASS 5 (B) · Adm: 26SSIC120328" — the class, its section by the
        // last letter of the name, then the admission number.
        $studentWhere = function (array $row): string {
            $class   = trim((string) ($row['class'] ?? ''));
            $section = ($row['section'] ?? '—') !== '—' ? trim((string) $row['section']) : '';
            $letter  = $section !== '' ? mb_strtoupper(mb_substr($section, -1)) : '';
            $where   = $class !== '' ? $class . ($letter !== '' ? ' (' . $letter . ')' : '') : ($section !== '' ? 'Sec ' . $section : '');

            return ($where !== '' ? $where . ' · ' : '') . 'Adm: ' . ($row['admission_no'] ?? '—');
        };
    @endphp

    {{-- ══════════ LIST VIEW ══════════ --}}
    @if ($activeView === 'list')

        {{-- HEADER — simple inline analytics (Support/Enquiries style) --}}
        <div class="bg-white border-b border-gray-200 sticky top-0 z-50">
            <div class="px-4 sm:px-6 py-3">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:min-h-9">
                    <div>
                        <h1 class="text-lg sm:text-xl font-bold text-gray-900">Fees</h1>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200">
                            <span class="pr-4">Students: <strong class="text-gray-800">{{ number_format($totalStudentsAll) }}</strong></span>
                            <span class="px-4">To Collect: <strong class="text-blue-600">₹{{ number_format($totalFeeToCollect, 0) }}</strong></span>
                            <span class="px-4">Collected: <strong class="text-emerald-600">₹{{ number_format($totalFeeCollected, 0) }}</strong></span>
                            <span class="px-4">Remaining: <strong class="text-red-500">₹{{ number_format($totalFeeRemaining, 0) }}</strong></span>
                            <span class="pl-4">Avg/Student: <strong class="text-gray-800">₹{{ number_format($avgFeePerStudent, 0) }}</strong></span>
                        </div>
                        <button wire:click="openUpdatePanel"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            <span>Update Fee</span>
                        </button>
                    </div>
                </div>

                {{-- Mobile/Tablet stats --}}
                <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                    <span>Students: <strong class="text-gray-800">{{ number_format($totalStudentsAll) }}</strong></span>
                    <span>To Collect: <strong class="text-blue-600">₹{{ number_format($totalFeeToCollect, 0) }}</strong></span>
                    <span>Collected: <strong class="text-emerald-600">₹{{ number_format($totalFeeCollected, 0) }}</strong></span>
                    <span>Remaining: <strong class="text-red-500">₹{{ number_format($totalFeeRemaining, 0) }}</strong></span>
                    <span>Avg/Student: <strong class="text-gray-800">₹{{ number_format($avgFeePerStudent, 0) }}</strong></span>
                </div>
            </div>

            {{-- FILTER BAR --}}
            <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filter by:
                    </div>

                    <div class="relative flex-[1_1_8rem] min-w-0 max-w-56">
                        <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" /></svg>
                        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search schools by name or code..."
                            class="text-xs bg-white border border-gray-200 rounded-md pl-8 pr-3 py-1.5 text-gray-700 w-full focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <select wire:model.live="filterOrganization"
                        class="text-xs min-w-0 max-w-[13rem] bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Schools</option>
                        @foreach ($organizations as $org)
                            <option value="{{ $org->id }}">{{ $org->name }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filterFeeType"
                        class="text-xs min-w-0 max-w-[13rem] bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Fee Types</option>
                        <option value="per_student">Per Student</option>
                        <option value="one_time">One Time</option>
                    </select>

                    <select wire:model.live="filterBoard"
                        class="text-xs min-w-0 max-w-[13rem] bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Boards</option>
                        @foreach ($boards as $board)
                            <option value="{{ $board }}">{{ $board }}</option>
                        @endforeach
                    </select>

                    @if ($search || $filterOrganization || $filterFeeType || $filterBoard)
                        <button wire:click="clearFilters"
                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            Clear
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="p-4 sm:p-6 space-y-4">

            {{-- ══════════ DESKTOP TABLE ══════════ --}}
            <div class="hidden md:block bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="overflow-x-auto rounded-xl">
                    <table class="w-full min-w-[860px]">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">School</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Board</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Fee Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Students</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Collection</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($schools as $school)
                                @php
                                    $orgStructure = \App\Models\SuperAdmin\SuperAdminFeeStructure::where('organization_id', $school->id)
                                        ->where('academic_year', $academicYear)
                                        ->whereIn('fee_type', ['one_time', 'per_student'])
                                        ->active()
                                        ->first();

                                    if ($orgStructure && $orgStructure->fee_type === 'one_time') {
                                        $orgExpected = !empty($orgStructure->period_amounts)
                                            ? (float) array_sum($orgStructure->period_amounts)
                                            : (float) ($orgStructure->total_amount ?? ($orgStructure->amount * $school->total_students));
                                    } elseif ($orgStructure) {
                                        $orgExpected = (float) $orgStructure->amount * $school->total_students;
                                    } else {
                                        $orgExpected = 0;
                                    }

                                    $orgCollected = (float) \App\Models\SuperAdmin\SuperAdminFeePayment::forOrg($school->id)
                                        ->forYear($academicYear)
                                        ->sum('amount');
                                    $orgPct = $orgExpected > 0 ? round(($orgCollected / $orgExpected) * 100) : 0;
                                @endphp
                                <tr class="hover:bg-gray-50/70 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            @if ($school->logo)
                                                <img src="{{ $school->logo }}" class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                            @else
                                                <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                                    <span class="text-xs font-semibold text-indigo-600">{{ strtoupper(substr($school->name, 0, 1)) }}</span>
                                                </div>
                                            @endif
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-gray-900 truncate">{{ $school->name }}</p>
                                                <p class="text-xs text-gray-400 truncate">Code: {{ $school->school_code ?? '—' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">{{ \App\Support\BoardName::short($school->education_board) ?: '—' }}</td>
                                    {{-- Fee Type as plain text: the plan as it was set (Monthly / One Time / Per Student). --}}
                                    <td class="px-4 py-3 text-sm whitespace-nowrap {{ $orgStructure ? 'text-gray-700' : 'text-gray-400' }}">
                                        {{ \App\Support\SuperAdminFeeTotals::planName($orgStructure) }}
                                    </td>
                                    {{-- Amount on one line: the figure, then what it is for. --}}
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if ($orgStructure?->fee_type === 'one_time')
                                            @php $rowAmt = !empty($orgStructure->period_amounts) ? array_sum($orgStructure->period_amounts) : (float) ($orgStructure->total_amount ?? $orgStructure->amount); @endphp
                                            <span class="text-sm font-semibold text-gray-800">₹{{ number_format((float) $rowAmt, 0) }}</span>
                                            <span class="text-xs text-gray-400">/ year</span>
                                        @elseif ($orgStructure?->fee_type === 'per_student')
                                            <span class="text-sm font-semibold text-gray-800">₹{{ number_format((float) $orgStructure->amount, 0) }}</span>
                                            <span class="text-xs text-gray-400">/ student</span>
                                        @else
                                            <span class="text-sm text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $school->total_students }}</td>
                                    <td class="px-4 py-3 min-w-[160px]">
                                        @if ($orgExpected > 0)
                                            <div class="flex items-center justify-between text-xs mb-1">
                                                <span class="text-gray-500">₹{{ number_format($orgCollected, 0) }} / ₹{{ number_format($orgExpected, 0) }}</span>
                                                <span class="font-semibold {{ $orgPct >= 100 ? 'text-emerald-600' : 'text-gray-600' }}">{{ $orgPct }}%</span>
                                            </div>
                                            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                                <div class="h-full rounded-full transition-all {{ $orgPct >= 100 ? 'bg-emerald-500' : 'bg-blue-500' }}"
                                                    style="width: {{ min(100, $orgPct) }}%"></div>
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-400">No fee structure set</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button wire:click="selectSchool({{ $school->id }})"
                                            title="{{ $orgStructure ? 'Edit fee & manage payments' : 'Set up fee' }}"
                                            class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 text-blue-600 hover:bg-blue-50 hover:border-blue-200 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-16 text-center">
                                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5" />
                                            </svg>
                                        </div>
                                        <p class="text-gray-500 text-sm">No schools found</p>
                                        @if ($search || $filterOrganization || $filterFeeType || $filterBoard)
                                            <button wire:click="clearFilters" class="mt-3 text-sm text-blue-600 hover:text-blue-800 font-medium">Clear filters</button>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($schools->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <p class="text-sm text-gray-500">
                            Showing <strong class="text-gray-700">{{ $schools->firstItem() }}</strong>
                            to <strong class="text-gray-700">{{ $schools->lastItem() }}</strong>
                            of <strong class="text-gray-700">{{ $schools->total() }}</strong> schools
                        </p>
                        <div class="flex items-center gap-1">
                            @if ($schools->onFirstPage())
                                <span class="px-3 py-1.5 text-sm text-gray-300 border border-gray-200 rounded-lg cursor-not-allowed">&laquo; Prev</span>
                            @else
                                <button wire:click="previousPage" class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">&laquo; Prev</button>
                            @endif

                            @foreach ($schools->getUrlRange(max(1, $schools->currentPage() - 2), min($schools->lastPage(), $schools->currentPage() + 2)) as $page => $url)
                                <button wire:click="gotoPage({{ $page }})"
                                    class="px-3 py-1.5 text-sm rounded-lg transition-colors
                                        {{ $page == $schools->currentPage() ? 'bg-blue-600 text-white border border-blue-600' : 'text-gray-600 border border-gray-300 hover:bg-gray-50' }}">
                                    {{ $page }}
                                </button>
                            @endforeach

                            @if ($schools->hasMorePages())
                                <button wire:click="nextPage" class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Next &raquo;</button>
                            @else
                                <span class="px-3 py-1.5 text-sm text-gray-300 border border-gray-200 rounded-lg cursor-not-allowed">Next &raquo;</span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            {{-- ══════════ MOBILE CARDS ══════════ --}}
            <div class="md:hidden space-y-3">
                @forelse ($schools as $school)
                    @php
                        $orgStructure = \App\Models\SuperAdmin\SuperAdminFeeStructure::where('organization_id', $school->id)
                            ->where('academic_year', $academicYear)
                            ->whereIn('fee_type', ['one_time', 'per_student'])
                            ->active()
                            ->first();

                        if ($orgStructure && $orgStructure->fee_type === 'one_time') {
                            $orgExpected = !empty($orgStructure->period_amounts)
                                ? (float) array_sum($orgStructure->period_amounts)
                                : (float) ($orgStructure->total_amount ?? ($orgStructure->amount * $school->total_students));
                        } elseif ($orgStructure) {
                            $orgExpected = (float) $orgStructure->amount * $school->total_students;
                        } else {
                            $orgExpected = 0;
                        }

                        $orgCollected = (float) \App\Models\SuperAdmin\SuperAdminFeePayment::forOrg($school->id)->forYear($academicYear)->sum('amount');
                        $orgPct = $orgExpected > 0 ? round(($orgCollected / $orgExpected) * 100) : 0;
                    @endphp
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden" wire:click="selectSchool({{ $school->id }})">
                        <div class="flex items-center gap-3 p-4 border-b border-gray-100">
                            @if ($school->logo)
                                <img src="{{ $school->logo }}" class="w-10 h-10 rounded-full object-cover border border-gray-200 flex-shrink-0">
                            @else
                                <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-sm font-semibold text-indigo-600">{{ strtoupper(substr($school->name, 0, 1)) }}</span>
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900 truncate">{{ $school->name }}</p>
                                <p class="text-xs text-gray-400 truncate">{{ \App\Support\BoardName::short($school->education_board) ?: 'Code: ' . ($school->school_code ?? '—') }}</p>
                            </div>
                        </div>
                        <div class="px-4 py-3 space-y-2">
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div>
                                    <p class="text-xs text-gray-400">Fee Type</p>
                                    <p class="{{ $orgStructure ? 'text-gray-700' : 'text-gray-400' }}">{{ \App\Support\SuperAdminFeeTotals::planName($orgStructure) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">Amount</p>
                                    @if ($orgStructure?->fee_type === 'one_time')
                                        @php $mAmt = !empty($orgStructure->period_amounts) ? array_sum($orgStructure->period_amounts) : (float) ($orgStructure->total_amount ?? $orgStructure->amount); @endphp
                                        <p class="text-gray-700 font-medium whitespace-nowrap">₹{{ number_format((float) $mAmt, 0) }} <span class="text-xs font-normal text-gray-400">/ year</span></p>
                                    @elseif ($orgStructure?->fee_type === 'per_student')
                                        <p class="text-gray-700 font-medium whitespace-nowrap">₹{{ number_format((float) $orgStructure->amount, 0) }} <span class="text-xs font-normal text-gray-400">/ student</span></p>
                                    @else
                                        <p class="text-gray-400">—</p>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">Students</p>
                                    <p class="text-gray-700 font-medium">{{ $school->total_students }}</p>
                                </div>
                            </div>
                            @if ($orgExpected > 0)
                                <div>
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <span class="text-gray-500">₹{{ number_format($orgCollected, 0) }} / ₹{{ number_format($orgExpected, 0) }}</span>
                                        <span class="font-semibold {{ $orgPct >= 100 ? 'text-emerald-600' : 'text-gray-600' }}">{{ $orgPct }}%</span>
                                    </div>
                                    <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full {{ $orgPct >= 100 ? 'bg-emerald-500' : 'bg-blue-500' }}" style="width: {{ min(100, $orgPct) }}%"></div>
                                    </div>
                                </div>
                            @else
                                <p class="text-xs text-center text-gray-400 py-1">No fee structure set</p>
                            @endif
                        </div>
                        <div class="border-t border-gray-100">
                            <button class="w-full py-2.5 text-xs font-semibold text-blue-600 hover:bg-blue-50 transition-colors flex items-center justify-center gap-1">
                                {{ $orgStructure ? 'Edit fee & payments' : 'Set up fee' }}
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center">
                        <p class="text-gray-500 text-sm">No schools found</p>
                    </div>
                @endforelse

                @if ($schools->hasPages())
                    <div class="px-2">{{ $schools->links() }}</div>
                @endif
            </div>
        </div>
    @endif

    {{-- ══════════ SCHOOL DETAIL VIEW ══════════
         In the Super Admin home's look: one page of sections (Overview,
         Fee, Students / Months) with the school's own fee — the same
         figures, lists and actions the Analytics / View Fee tabs had. --}}
    @if ($activeView === 'school' && $selectedSchool)
        @php
            $inr        = fn ($v) => '₹' . number_format((float) $v, 0);
            $dStructure = $this->detailStructure();
            $dPeriods   = $this->detailPeriods($dStructure);
            $dCounts    = $this->detailCounts($dStructure, $dPeriods);
            $dPlan      = \App\Support\SuperAdminFeeTotals::planName($dStructure);
            $dUnit      = $dCounts['unit'];
            $dPaidPct   = $dCounts['total'] > 0 ? round($dCounts['paid'] / $dCounts['total'] * 100) : 0;
            $dStats     = $schoolStats + ['total_students' => 0, 'total_to_collect' => 0, 'collected' => 0, 'remaining' => 0, 'avg_per_student' => 0, 'collection_pct' => 0, 'fy_monthly_chart' => []];

            // April → March of the year shown.
            $fyStart  = (int) explode('-', $academicYear)[0];
            $fyLabels = $fyData = [];
            for ($i = 0; $i < 12; $i++) {
                $m          = \Illuminate\Support\Carbon::create($fyStart, 4, 1)->addMonthsNoOverflow($i);
                $fyLabels[] = $m->format('M y');
                $fyData[]   = (float) ($dStats['fy_monthly_chart'][$m->year . '-' . $m->month] ?? 0);
            }
            $fyTotal = array_sum($fyData);

            $statusTone = ['paid' => 'text-emerald-600', 'partial' => 'text-amber-600', 'pending' => 'text-gray-400'];
            $statusName = ['paid' => 'Paid', 'partial' => 'Partial', 'pending' => 'Pending'];
        @endphp

        {{-- ══════════════════════════════ HEADER ══════════════════════════════ --}}
        <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:min-h-9">
                <div class="flex items-center gap-3 min-w-0">
                    <button wire:click="backToList"
                        class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-gray-200 text-gray-500 hover:text-gray-800 hover:bg-gray-50 text-sm transition-colors flex-shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span class="hidden sm:inline">Back</span>
                    </button>
                    @if ($selectedSchool->logo)
                        <img src="{{ $selectedSchool->logo }}" class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0">
                    @else
                        <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                            <span class="text-sm font-semibold text-indigo-600">{{ strtoupper(substr($selectedSchool->name, 0, 1)) }}</span>
                        </div>
                    @endif
                    <div class="min-w-0 sm:flex sm:items-baseline sm:gap-2">
                        <h1 class="min-w-0 text-lg sm:text-xl font-bold text-gray-900 truncate">{{ $selectedSchool->name }}</h1>
                        <p class="text-xs text-gray-400 mt-0.5 sm:mt-0 truncate">
                            {{ $dStructure ? $dPlan . ($dStructure->fee_label ? ' · ' . $dStructure->fee_label : '') . ' · ' : '' }}{{ $academicYear }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0">
                    @if ($currentFeeType)
                        {{-- Record / edit payments (the Update Fee panel) … --}}
                        <button wire:click="openUpdatePanelForCurrent"
                            class="inline-flex items-center gap-1.5 px-3 py-[7px] text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            Update Fee
                        </button>
                        {{-- … and the fee structure itself. --}}
                        <button wire:click="openUpdateFeePanel"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit Fee
                        </button>
                    @else
                        <button wire:click="openAddFeePanel"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            Add Fee
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="p-4 sm:p-6 space-y-8">

            {{-- ══════════════════════════════ OVERVIEW ══════════════════════════════ --}}
            <section>
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Overview</h2>

                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5">
                    @php
                        $dTiles = [
                            ['Students',      number_format($dStats['total_students']), 'in the school'],
                            ['To Collect',    $inr($dStats['total_to_collect']),        $dStructure ? $dPlan . ' · ' . $academicYear : 'No fee set'],
                            ['Collected',     $inr($dStats['collected']),               $dStats['collection_pct'] . '% of the fee'],
                            ['Remaining',     $inr($dStats['remaining']),               $dCounts['total'] > 0 ? number_format($dCounts['partial'] + $dCounts['pending']) . ' ' . $dUnit . (($dCounts['partial'] + $dCounts['pending']) === 1 ? '' : 's') . ' due' : '—'],
                            ['Avg / Student', $inr($dStats['avg_per_student']),         'fee to collect per student'],
                        ];
                    @endphp
                    @foreach ($dTiles as [$label, $value, $sub])
                        <div class="px-5 py-4 -mr-px -mb-px border-r border-b border-gray-100 min-w-0">
                            <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide truncate">{{ $label }}</p>
                            <p class="text-[26px] leading-none font-semibold text-gray-900 mt-2 tabular-nums truncate">{{ $value }}</p>
                            <p class="text-xs text-gray-400 mt-1.5 truncate">{{ $sub }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    @php
                        $dCards = [
                            ['Collection', $dStats['collection_pct'] . '%', $inr($dStats['collected']) . ' of ' . $inr($dStats['total_to_collect']) . ' collected', $dStats['collection_pct']],
                            [$dUnit === 'student' ? 'Students Paid' : ($dUnit === 'month' ? 'Months Paid' : 'Fee Paid'),
                             $dCounts['paid'] . ' / ' . $dCounts['total'],
                             $dCounts['partial'] . ' part paid · ' . $dCounts['pending'] . ' pending',
                             $dPaidPct],
                        ];
                    @endphp
                    @foreach ($dCards as [$label, $value, $sub, $pct])
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

            {{-- ═══════════════════════════════ FEE ════════════════════════════════
                 Money received month by month over the year, and the fee set.
            ═══════════════════════════════════════════════════════════════════ --}}
            <section>
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Fee</h2>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5">
                        <div class="mb-4">
                            <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">Collected · {{ $fyLabels[0] }} – {{ end($fyLabels) }}</p>
                            <p class="text-2xl font-semibold text-gray-900 mt-1 tabular-nums">{{ $inr($fyTotal) }}</p>
                        </div>
                        {{-- Keyed on its figures: a new payment redraws it. --}}
                        <div class="h-56" wire:key="sa-fee-school-chart-{{ $selectedSchool->id }}-{{ md5(json_encode($fyData)) }}">
                            <canvas x-data="{
                                chart: null,
                                init() {
                                    if (typeof Chart === 'undefined') return;
                                    this.chart = new Chart(this.$el.getContext('2d'), {
                                        type: 'bar',
                                        data: {
                                            labels: @js($fyLabels),
                                            datasets: [{ label: 'Collected', data: @js($fyData), backgroundColor: 'rgba(99,102,241,0.7)', borderRadius: 3, borderSkipped: false, maxBarThickness: 34 }]
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
                                },
                                destroy() { this.chart?.destroy(); }
                            }"></canvas>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-semibold text-gray-800">Fee Structure</h3>
                            @if ($currentFeeType)
                                <button wire:click="openUpdateFeePanel" class="text-xs font-medium text-blue-600 hover:text-blue-800">Edit →</button>
                            @endif
                        </div>

                        @if ($feeStructures->count())
                            <div class="space-y-5">
                                @foreach ($feeStructures as $fs)
                                    @php
                                        $schoolKids = $dStats['total_students'];
                                        if ($fs->fee_type === 'class_wise') {
                                            $fsStudents = \App\Models\Student\StudentDetail::where('organization_id', $selectedSchool->id)->where('standard_id', $fs->standard_id)->count();
                                        } else {
                                            $fsStudents = $schoolKids;
                                        }
                                        $fsExpected  = \App\Support\SuperAdminFeeTotals::expected($fs, $schoolKids, $fsStudents);
                                        $fsCollected = (float) \App\Models\SuperAdmin\SuperAdminFeePayment::where('super_admin_fee_structure_id', $fs->id)->sum('amount');
                                        $fsPct       = $fsExpected > 0 ? round($fsCollected / $fsExpected * 100) : 0;
                                        $fsAmount    = match (true) {
                                            $fs->fee_type === 'per_student' => $inr($fs->amount) . ' / student',
                                            $fs->fee_type === 'one_time'    => $inr($fsExpected) . ' / year',
                                            default                         => $inr($fs->amount) . ' / student',
                                        };
                                    @endphp
                                    <dl class="space-y-3 text-sm">
                                        <div class="flex items-baseline justify-between gap-3">
                                            <dt class="text-gray-500">Fee Type</dt>
                                            <dd class="font-semibold text-gray-900 text-right">{{ $fs->fee_type === 'class_wise' ? ($fs->standard?->name ?? 'Class') : \App\Support\SuperAdminFeeTotals::planName($fs) }}</dd>
                                        </div>
                                        @if ($fs->fee_label)
                                            <div class="flex items-baseline justify-between gap-3">
                                                <dt class="text-gray-500">Label</dt>
                                                <dd class="font-semibold text-gray-900 text-right truncate">{{ $fs->fee_label }}</dd>
                                            </div>
                                        @endif
                                        <div class="flex items-baseline justify-between gap-3">
                                            <dt class="text-gray-500">Amount</dt>
                                            <dd class="font-semibold text-gray-900 tabular-nums whitespace-nowrap">{{ $fsAmount }}</dd>
                                        </div>
                                        <div class="flex items-baseline justify-between gap-3">
                                            <dt class="text-gray-500">Students</dt>
                                            <dd class="font-semibold text-gray-900 tabular-nums">{{ number_format($fsStudents) }}</dd>
                                        </div>
                                        <div class="flex items-baseline justify-between gap-3 pt-3 border-t border-gray-100">
                                            <dt class="text-gray-500">Collected</dt>
                                            <dd class="font-semibold text-gray-900 tabular-nums">{{ $inr($fsCollected) }}</dd>
                                        </div>
                                        <div class="flex items-baseline justify-between gap-3">
                                            <dt class="text-gray-500">Remaining</dt>
                                            <dd class="font-semibold tabular-nums {{ $fsExpected - $fsCollected > 0.5 ? 'text-red-500' : 'text-gray-900' }}">{{ $inr(max(0, $fsExpected - $fsCollected)) }}</dd>
                                        </div>
                                    </dl>
                                    <div>
                                        <div class="flex justify-between text-xs mb-1.5"><span class="text-gray-400">of {{ $inr($fsExpected) }}</span><span class="font-semibold text-gray-700">{{ $fsPct }}%</span></div>
                                        <div class="w-full bg-gray-100 rounded-full h-1.5"><div class="bg-gray-800 h-1.5 rounded-full" style="width: {{ min(100, $fsPct) }}%"></div></div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="flex-1 flex flex-col items-center justify-center text-center py-8">
                                <p class="text-sm text-gray-400">No fee structure set yet.</p>
                                <button wire:click="openAddFeePanel" class="mt-3 text-sm font-medium text-blue-600 hover:text-blue-800">Add Fee →</button>
                            </div>
                        @endif
                    </div>
                </div>
            </section>

            {{-- ═══════════════════ STUDENTS (per student) / MONTHS (monthly, one time) ═══════════════════ --}}
            @if ($dStructure?->fee_type === 'per_student')
                @php
                    $vfRows    = $this->viewFeeStudents();
                    $vfPaid    = collect($vfRows)->where('status', 'paid')->count();
                    $vfPartial = collect($vfRows)->where('status', 'partial')->count();
                    $vfPending = collect($vfRows)->where('status', 'pending')->count();
                @endphp
                <section>
                    <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Students</h2>

                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        {{-- Filter: class / section / status --}}
                        <div class="px-5 py-3 border-b border-gray-100 flex flex-wrap items-center gap-2 sm:gap-3">
                            <select wire:model.live="viewFeeStandardId"
                                class="text-xs min-w-0 max-w-[13rem] bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Classes</option>
                                @foreach ($viewStandards as $std)
                                    <option value="{{ $std->id }}">{{ $std->name }}</option>
                                @endforeach
                            </select>
                            <select data-sole-section wire:model.live="viewFeeSectionId" @if (!$viewFeeStandardId) disabled @endif
                                class="text-xs min-w-0 max-w-[13rem] bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-50 disabled:text-gray-400">
                                <option value="">All Sections</option>
                                @foreach ($viewFeeSections as $sec)
                                    <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                                @endforeach
                            </select>
                            <select wire:model.live="viewFeeStatus"
                                class="text-xs min-w-0 max-w-[13rem] bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Status</option>
                                <option value="paid">Paid</option>
                                <option value="partial">Partial</option>
                                <option value="pending">Pending</option>
                            </select>
                            {{-- Sort: class wise (the order the fee marks students paid in) or A to Z --}}
                            <select wire:model.live="viewFeeSort" title="Sort"
                                class="text-xs min-w-0 max-w-[13rem] bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="class">Sort: Class wise</option>
                                <option value="name">Sort: A to Z</option>
                            </select>
                            @if ($viewFeeStandardId || $viewFeeSectionId || $viewFeeStatus !== '' || $viewFeeSort !== 'class')
                                <button wire:click="clearViewFeeFilters"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    Clear
                                </button>
                            @endif
                            <span class="ml-auto text-xs text-gray-400">
                                <span class="text-emerald-600 font-medium">{{ $vfPaid }} paid</span> ·
                                <span class="text-amber-600 font-medium">{{ $vfPartial }} partial</span> ·
                                {{ $vfPending }} pending
                            </span>
                        </div>

                        @if (empty($vfRows))
                            <p class="text-center py-12 text-sm text-gray-400">No students match the selected filters.</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm min-w-[620px]">
                                    <thead class="bg-gray-50/70 text-gray-400 text-[11px] uppercase tracking-wide">
                                        <tr>
                                            <th class="px-5 py-2.5 text-left font-medium w-12">S.No</th>
                                            <th class="px-3 py-2.5 text-left font-medium">Student</th>
                                            <th class="px-3 py-2.5 text-right font-medium">Fee</th>
                                            <th class="px-3 py-2.5 text-right font-medium">Collected</th>
                                            <th class="px-3 py-2.5 text-left font-medium">Status</th>
                                            <th class="px-5 py-2.5 text-center font-medium">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($vfRows as $row)
                                            <tr class="hover:bg-gray-50/70">
                                                <td class="px-5 py-2.5 text-gray-400 tabular-nums">{{ $row['serial'] }}</td>
                                                <td class="px-3 py-2.5">
                                                    <p class="font-medium text-gray-800 leading-tight">{{ $row['name'] }}</p>
                                                    <p class="text-xs text-gray-400">{{ $studentWhere($row) }}</p>
                                                </td>
                                                <td class="px-3 py-2.5 text-right text-gray-700 tabular-nums">{{ $inr($row['total_fee']) }}</td>
                                                <td class="px-3 py-2.5 text-right tabular-nums">
                                                    <p class="font-semibold text-gray-900">{{ $inr($row['collected']) }}</p>
                                                    @if ($row['status'] === 'partial')
                                                        <p class="text-xs text-red-500">{{ $inr($row['remaining']) }} due</p>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2.5 font-medium {{ $statusTone[$row['status']] }}">{{ $statusName[$row['status']] }}</td>
                                                <td class="px-5 py-2.5">
                                                    <div class="flex items-center justify-center gap-1">
                                                        <button
                                                            wire:click="openPayModal({{ $row['id'] }}, {{ $row['structure_id'] }}, {{ $row['total_fee'] }}, {{ $row['collected'] }}, {{ $row['payment_id'] ?? 'null' }})"
                                                            class="px-2 py-1 text-[11px] font-semibold rounded-md transition-colors
                                                                {{ $row['status'] === 'pending' ? 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100' : 'text-amber-700 bg-amber-50 hover:bg-amber-100' }}">
                                                            {{ $row['status'] === 'pending' ? 'Record' : 'Edit' }}
                                                        </button>
                                                        @if (!$row['is_paid'])
                                                            <button
                                                                wire:click="openMarkPaidModal({{ $row['id'] }}, {{ $row['structure_id'] }}, {{ $row['total_fee'] }}, {{ $row['collected'] }}, {{ $row['payment_id'] ?? 'null' }})"
                                                                class="px-2 py-1 text-[11px] font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-md transition-colors">
                                                                Mark Paid
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </section>
            @elseif ($dStructure?->fee_type === 'one_time')
                <section>
                    <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">{{ $dUnit === 'month' ? 'Months' : 'Fee' }}</h2>

                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-gray-800">{{ $dPlan }} · {{ $academicYear }}</h3>
                            <span class="text-xs text-gray-400">
                                <span class="text-emerald-600 font-medium">{{ $dCounts['paid'] }} paid</span> ·
                                <span class="text-amber-600 font-medium">{{ $dCounts['partial'] }} partial</span> ·
                                {{ $dCounts['pending'] }} pending
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm min-w-[560px]">
                                <thead class="bg-gray-50/70 text-gray-400 text-[11px] uppercase tracking-wide">
                                    <tr>
                                        <th class="px-5 py-2.5 text-left font-medium">Period</th>
                                        <th class="px-3 py-2.5 text-right font-medium">Amount</th>
                                        <th class="px-3 py-2.5 text-right font-medium">Collected</th>
                                        <th class="px-3 py-2.5 text-left font-medium">Status</th>
                                        <th class="px-5 py-2.5 text-center font-medium">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($dPeriods as $inst)
                                        <tr class="hover:bg-gray-50/70">
                                            <td class="px-5 py-2.5">
                                                <p class="font-medium text-gray-800">{{ $inst['label'] }}</p>
                                                @if ($inst['payment_date'] !== '—')
                                                    <p class="text-xs text-gray-400">{{ $inst['payment_date'] }} · {{ ucfirst($inst['payment_mode']) }}</p>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2.5 text-right text-gray-700 tabular-nums">{{ $inst['amount'] > 0 ? $inr($inst['amount']) : '—' }}</td>
                                            <td class="px-3 py-2.5 text-right tabular-nums">
                                                <p class="font-semibold text-gray-900">{{ $inr($inst['collected']) }}</p>
                                                @if ($inst['status'] === 'partial')
                                                    <p class="text-xs text-red-500">{{ $inr($inst['remaining']) }} due</p>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2.5 font-medium {{ $inst['amount'] > 0 ? $statusTone[$inst['status']] : 'text-gray-300' }}">{{ $inst['amount'] > 0 ? $statusName[$inst['status']] : 'No fee' }}</td>
                                            <td class="px-5 py-2.5">
                                                @if ($inst['amount'] > 0)
                                                    <div class="flex items-center justify-center gap-1">
                                                        <button
                                                            wire:click="openInstallmentPayModal('{{ $inst['key'] }}', '{{ $inst['label'] }}', {{ $inst['structure_id'] }}, {{ $inst['amount'] }}, {{ $inst['collected'] }}, {{ $inst['payment_id'] ?? 'null' }})"
                                                            class="px-2 py-1 text-[11px] font-semibold rounded-md transition-colors
                                                                {{ $inst['status'] === 'pending' ? 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100' : 'text-amber-700 bg-amber-50 hover:bg-amber-100' }}">
                                                            {{ $inst['status'] === 'pending' ? 'Record' : 'Edit' }}
                                                        </button>
                                                        @if ($inst['status'] !== 'paid')
                                                            <button
                                                                wire:click="openMarkInstallmentPaidModal('{{ $inst['key'] }}', '{{ $inst['label'] }}', {{ $inst['structure_id'] }}, {{ $inst['amount'] }}, {{ $inst['collected'] }}, {{ $inst['payment_id'] ?? 'null' }})"
                                                                class="px-2 py-1 text-[11px] font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-md transition-colors">
                                                                Mark Paid
                                                            </button>
                                                        @endif
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            @endif
        </div>
    @endif

    {{-- ══════════ ADD/UPDATE FEE SLIDE-IN PANEL ══════════
         In the Update Fee panel's look (the Students form's): a wide panel,
         plain labels over bordered boxes, the totals as a plain bordered row,
         Cancel and a dark Save in the footer. Same fields, bindings and save. --}}
    @if ($showFeePanel)
        @php
            $fpIn  = 'w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm bg-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500';
            $fpLb  = 'block text-sm font-medium text-gray-700 mb-1.5';
            $fpErr = 'mt-1 text-xs text-red-500';
        @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeFeePanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900">Fee Structure</h2>
                        <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $selectedSchool?->name }}</p>
                    </div>
                    <button wire:click="closeFeePanel" type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- Scrollable Body --}}
                <div class="flex-1 overflow-y-auto">
                    <div class="px-6 py-6 space-y-5">

                    {{-- Step 1: Fee Type --}}
                    <div>
                        <label class="{{ $fpLb }}">Fee Type <span class="text-red-500">*</span></label>
                        <select wire:model.live="feePlan" class="{{ $fpIn }}">
                            <option value="monthly">Monthly</option>
                            <option value="one_time">One Time</option>
                            <option value="per_student">Per Student</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-400">
                            @if ($feePlan === 'monthly') Enter a fee for each month (April → March).
                            @elseif ($feePlan === 'one_time') Enter one fee for the complete year.
                            @else Enter one student's fee — it applies to every student.
                            @endif
                        </p>
                    </div>

                    {{-- ── MONTHLY FIELDS (April → March) ── --}}
                    @if ($feePlan === 'monthly')
                        <div>
                            <label class="{{ $fpLb }}">Fee Label <span class="text-red-500">*</span></label>
                            <input wire:model="oneTimeLabel" type="text" placeholder="Annual Platform Fee"
                                class="{{ $fpIn }} @error('oneTimeLabel') border-red-400 @enderror" />
                            @error('oneTimeLabel') <p class="{{ $fpErr }}">{{ $message }}</p> @enderror
                        </div>

                        {{-- Per-month amounts (April → March) --}}
                        <div class="space-y-3" x-data="{
                                amounts: @js($periodAmounts),
                                get total() { return Object.values(this.amounts).reduce((sum, v) => sum + (parseFloat(v) || 0), 0); },
                             }">
                            <label class="{{ $fpLb }}">Amount per Month (₹)</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                @foreach ($this->currentPeriodsList() as $period)
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">{{ $period['label'] }}</label>
                                        <input type="number" min="0" step="0.01" placeholder="0"
                                            wire:model="periodAmounts.{{ $period['key'] }}"
                                            x-on:input="amounts['{{ $period['key'] }}'] = $event.target.value"
                                            class="{{ $fpIn }} @error('periodAmounts.' . $period['key']) border-red-400 @enderror" />
                                        @error('periodAmounts.' . $period['key']) <p class="{{ $fpErr }}">{{ $message }}</p> @enderror
                                    </div>
                                @endforeach
                            </div>

                            <div class="border border-gray-200 rounded-md px-4 py-3 flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-800">Total · {{ count($this->currentPeriodsList()) }} months</p>
                                    <p class="text-xs text-gray-400 mt-0.5">Sum of every month entered above</p>
                                </div>
                                <p class="text-lg font-semibold text-gray-900 tabular-nums flex-shrink-0" x-text="'₹' + total.toLocaleString('en-IN')"></p>
                            </div>
                        </div>
                    @endif

                    {{-- ── ONE TIME FIELDS (single full-year amount) ── --}}
                    @if ($feePlan === 'one_time')
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="{{ $fpLb }}">Fee Label <span class="text-red-500">*</span></label>
                                <input wire:model="oneTimeLabel" type="text" placeholder="Annual Platform Fee"
                                    class="{{ $fpIn }} @error('oneTimeLabel') border-red-400 @enderror" />
                                @error('oneTimeLabel') <p class="{{ $fpErr }}">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="{{ $fpLb }}">Complete Year Fee (₹) <span class="text-red-500">*</span></label>
                                <input wire:model.live="oneTimeAmount" type="number" placeholder="e.g. 6000" min="0.01" step="0.01"
                                    class="{{ $fpIn }} @error('oneTimeAmount') border-red-400 @enderror" />
                                @error('oneTimeAmount') <p class="{{ $fpErr }}">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="border border-gray-200 rounded-md px-4 py-3 flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-800">Full-Year Fee</p>
                                <p class="text-xs text-gray-400 mt-0.5">Charged once for the whole academic year</p>
                            </div>
                            <p class="text-lg font-semibold text-gray-900 tabular-nums flex-shrink-0">₹{{ number_format((float) ($oneTimeAmount ?: 0), 0) }}</p>
                        </div>
                    @endif

                    {{-- ── PER STUDENT FIELDS ── --}}
                    @if ($feePlan === 'per_student')
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="{{ $fpLb }}">Fee Label <span class="text-red-500">*</span></label>
                                <input wire:model="perStudentLabel" type="text" placeholder="Annual Platform Fee"
                                    class="{{ $fpIn }} @error('perStudentLabel') border-red-400 @enderror" />
                                @error('perStudentLabel') <p class="{{ $fpErr }}">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="{{ $fpLb }}">Amount per Student (₹) <span class="text-red-500">*</span></label>
                                <input wire:model.live="perStudentAmount" type="number" placeholder="e.g. 500" min="0.01" step="0.01"
                                    class="{{ $fpIn }} @error('perStudentAmount') border-red-400 @enderror" />
                                @error('perStudentAmount') <p class="{{ $fpErr }}">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        @php
                            // The school's students: the list carries the count; the
                            // school's own page does not, so it is counted here.
                            $perStudentTotalStudents = $selectedSchool
                                ? (int) ($selectedSchool->total_students
                                    ?? \App\Models\Student\StudentDetail::where('organization_id', $selectedSchool->id)->count())
                                : 0;
                            $perStudentPreviewTotal  = (float) ($perStudentAmount ?: 0) * $perStudentTotalStudents;
                        @endphp
                        <div class="border border-gray-200 rounded-md px-4 py-3 flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-800">Total to Collect</p>
                                <p class="text-xs text-gray-400 mt-0.5">₹{{ number_format((float) ($perStudentAmount ?: 0), 2) }} × {{ number_format($perStudentTotalStudents) }} students</p>
                            </div>
                            <p class="text-lg font-semibold text-gray-900 tabular-nums flex-shrink-0">₹{{ number_format($perStudentPreviewTotal, 0) }}</p>
                        </div>
                    @endif

                    <p class="text-xs text-amber-600">Saving replaces any earlier fee setup for this school.</p>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeFeePanel" type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveFeeStructures" type="button" wire:loading.attr="disabled" wire:target="saveFeeStructures"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveFeeStructures">Save Fee Structure</span>
                        <span wire:loading wire:target="saveFeeStructures">Saving...</span>
                    </button>
                </div>

            </div>
        </div>
    @endif

    {{-- ══════════ UPDATE FEE SLIDE-IN PANEL (header — choose org → update payments) ══════════
         In the Students form's look: a wide panel, plain labels over bordered
         boxes, the plan as plain text (no chips), statuses as plain coloured
         words. Amount Received folds away behind its own row — click it to
         open the form. Same fields, bindings, previews and saves. --}}
    @if ($showUpdatePanel)
        @php
            $upIn  = 'w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm bg-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-50 disabled:text-gray-400';
            $upLb  = 'block text-sm font-medium text-gray-700 mb-1.5';
            $upErr = 'mt-1 text-xs text-red-500';
            $upStatusTone = ['paid' => 'text-emerald-600', 'partial' => 'text-amber-600', 'pending' => 'text-gray-500'];
            $upStatusName = ['paid' => 'Paid', 'partial' => 'Partial', 'pending' => 'Pending'];
        @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden" x-data @keydown.escape.window="$wire.closeUpdatePanel()">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeUpdatePanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900">Update Fee</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Choose a school, then record or edit its fee collections</p>
                    </div>
                    <button wire:click="closeUpdatePanel" type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- Scrollable Body --}}
                <div class="flex-1 overflow-y-auto">
                    <div class="px-6 py-6 space-y-5">

                    {{-- Step 1: Choose Organization --}}
                    <div>
                        <label class="{{ $upLb }}">School / Organization <span class="text-red-500">*</span></label>
                        <select wire:model.live="updateOrgId" class="{{ $upIn }}">
                            <option value="">— Select a school —</option>
                            @foreach ($organizations as $org)
                                <option value="{{ $org->id }}">{{ $org->name }}</option>
                            @endforeach
                        </select>
                        {{-- The school's plan, as plain text --}}
                        @if ($updateOrgId !== '' && $updateOrgFeeType)
                            <p class="mt-1.5 text-xs text-gray-500">
                                @if ($updateOrgFeeType === 'one_time')
                                    One Time · {{ ucfirst($updateOrgFrequency ?: 'yearly') }}
                                @else
                                    Per Student
                                @endif
                                @if ($updateOrgFeeLabel)
                                    · {{ $updateOrgFeeLabel }} · {{ $academicYear }}
                                @endif
                            </p>
                        @endif
                    </div>

                    @if ($updateOrgId === '')
                        <div class="text-center py-14 text-sm text-gray-400">
                            Select a school above to see its fee structure.
                        </div>
                    @elseif (!$updateOrgFeeType)
                        <div class="text-center py-14">
                            <p class="text-sm text-gray-500 font-medium">No fee structure set</p>
                            <p class="text-xs text-gray-400 mt-1">
                                {{ $updateOrgName }} has no active fee structure for {{ $academicYear }}.
                                Open the school and click <strong>Add Fee</strong> first.
                            </p>
                        </div>
                    @else

                        {{-- ── AMOUNT RECEIVED: one sum from the school, put against what is due in
                             order (per student: first class, then the next…; monthly: April, then May…).
                             A row that opens like a dropdown; the form shows under it. ── --}}
                        @php $receive = $this->receivePreview(); @endphp
                        <div class="border border-gray-300 rounded-md" x-data="{ open: false }" wire:key="receive-box-{{ $updateOrgId }}">
                            <button type="button" x-on:click="open = ! open"
                                class="w-full flex items-center justify-between gap-3 px-4 py-3 text-left hover:bg-gray-50 rounded-md">
                                <span class="min-w-0">
                                    <span class="block text-sm font-medium text-gray-800">Amount Received</span>
                                    <span class="block text-xs text-gray-400 mt-0.5">
                                        @if ($receive['per_student'])
                                            Goes to the first class's students first, then the next class, and so on.
                                        @elseif ($updateOrgFrequency === 'monthly')
                                            Goes to April first, then May, and so on.
                                        @else
                                            Goes to the year's fee.
                                        @endif
                                    </span>
                                </span>
                                <span class="flex items-center gap-3 flex-shrink-0">
                                    <span class="text-xs text-gray-500 whitespace-nowrap">Total due
                                        <strong class="{{ $receive['due'] > 0 ? 'text-red-500' : 'text-emerald-600' }}">₹{{ number_format($receive['due'], 0) }}</strong>
                                    </span>
                                    <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                </span>
                            </button>

                            <div x-show="open" style="display: none" class="border-t border-gray-200 px-4 py-4 space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="{{ $upLb }}">Amount (₹) <span class="text-red-500">*</span></label>
                                        <input wire:model.live.debounce.400ms="receiveAmount" type="number" min="0" step="0.01" placeholder="e.g. 25000"
                                            class="{{ $upIn }} @error('receiveAmount') border-red-400 @enderror" />
                                        @error('receiveAmount') <p class="{{ $upErr }}">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="{{ $upLb }}">Payment Mode</label>
                                        <select wire:model="receiveMode" class="{{ $upIn }}">
                                            <option value="cash">Cash</option>
                                            <option value="online">Online</option>
                                            <option value="cheque">Cheque</option>
                                            <option value="upi">UPI</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="{{ $upLb }}">Payment Date</label>
                                        <input wire:model="receiveDate" type="date" class="{{ $upIn }} @error('receiveDate') border-red-400 @enderror" />
                                        @error('receiveDate') <p class="{{ $upErr }}">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="{{ $upLb }}">Remark <span class="text-gray-400 font-normal">(Optional)</span></label>
                                        <input wire:model="receiveRemark" type="text" placeholder="Optional remark" class="{{ $upIn }} @error('receiveRemark') border-red-400 @enderror" />
                                        @error('receiveRemark') <p class="{{ $upErr }}">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                {{-- What the amount will cover, before it is saved. --}}
                                @if ($receive['amount'] > 0)
                                    @if ($receive['over'])
                                        <p class="text-xs text-red-500">More than the total due (₹{{ number_format($receive['due'], 0) }}).</p>
                                    @elseif (count($receive['lines']))
                                        <div class="border border-gray-200 rounded-md divide-y divide-gray-100 max-h-44 overflow-y-auto">
                                            @foreach ($receive['lines'] as $line)
                                                <div class="flex items-baseline justify-between gap-3 px-3 py-1.5 text-xs">
                                                    <span class="font-medium text-gray-700 whitespace-nowrap">{{ $line['label'] }}</span>
                                                    <span class="text-right {{ $line['full'] ? 'text-emerald-700' : 'text-amber-600' }}">{{ $line['text'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                @endif

                                {{-- Asks once before saving: one amount can mark many students / months. --}}
                                <div x-data="{ ask: false }" class="flex items-center justify-end gap-2">
                                    <button type="button" x-show="!ask" x-on:click="ask = true"
                                        @disabled($receive['due'] <= 0)
                                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-medium rounded-md">
                                        Save Amount
                                    </button>
                                    <span x-show="ask" x-cloak class="text-xs text-gray-500 mr-auto">Save this amount against what is due?</span>
                                    <button type="button" x-show="ask" x-cloak x-on:click="ask = false"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">
                                        Cancel
                                    </button>
                                    <button type="button" x-show="ask" x-cloak wire:click="applyReceivedAmount" x-on:click="ask = false"
                                        wire:loading.attr="disabled" wire:target="applyReceivedAmount"
                                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60">
                                        Yes, save{{ $receive['amount'] > 0 ? ' ₹' . number_format($receive['amount'], 0) : '' }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- ── PER STUDENT: class + section → students list ── --}}
                        @if ($updateOrgFeeType === 'per_student')
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="{{ $upLb }}">Class <span class="text-red-500">*</span></label>
                                    <select wire:model.live="updateStandardId" class="{{ $upIn }}">
                                        <option value="">— Select class —</option>
                                        @foreach ($standards as $std)
                                            <option value="{{ $std->id }}">{{ $std->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="{{ $upLb }}">Section</label>
                                    <select wire:model.live="updateSectionId" @if(!$updateStandardId) disabled @endif class="{{ $upIn }}">
                                        <option value="">All Sections</option>
                                        @foreach ($updateSections as $sec)
                                            <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            @if (!$updateStandardId)
                                <div class="text-center py-10 text-sm text-gray-400">Select a class to load its students.</div>
                            @elseif (empty($studentFeeList))
                                <div class="text-center py-10 text-sm text-gray-400">No students found in this class/section.</div>
                            @else
                                <div class="border border-gray-200 rounded-md overflow-hidden">
                                    <div class="overflow-x-auto">
                                        <table class="w-full min-w-[560px]">
                                            <thead class="bg-gray-50 border-b border-gray-200">
                                                <tr>
                                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 w-10">#</th>
                                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500">Student</th>
                                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500">Fee</th>
                                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500">Collected</th>
                                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500">Status</th>
                                                    <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-500">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                                @foreach ($studentFeeList as $row)
                                                    <tr class="hover:bg-gray-50/60">
                                                        <td class="px-3 py-2.5 text-xs text-gray-400">{{ $row['serial'] }}</td>
                                                        <td class="px-3 py-2.5">
                                                            <p class="text-sm font-medium text-gray-800 leading-tight">{{ $row['name'] }}</p>
                                                            <p class="text-[11px] text-gray-400">{{ $studentWhere($row) }}</p>
                                                        </td>
                                                        <td class="px-3 py-2.5 text-sm text-gray-700">₹{{ number_format($row['total_fee'], 0) }}</td>
                                                        <td class="px-3 py-2.5">
                                                            <p class="text-sm font-semibold text-gray-900">₹{{ number_format($row['collected'], 0) }}</p>
                                                            @if ($row['status'] === 'partial')
                                                                <p class="text-[11px] text-red-500">₹{{ number_format($row['remaining'], 0) }} due</p>
                                                            @endif
                                                        </td>
                                                        <td class="px-3 py-2.5 text-sm font-medium {{ $upStatusTone[$row['status']] ?? 'text-gray-500' }}">{{ $upStatusName[$row['status']] ?? ucfirst($row['status']) }}</td>
                                                        <td class="px-3 py-2.5">
                                                            <div class="flex items-center justify-center gap-1">
                                                                <button
                                                                    wire:click="openPayModal({{ $row['id'] }}, {{ $row['structure_id'] }}, {{ $row['total_fee'] }}, {{ $row['collected'] }}, {{ $row['payment_id'] ?? 'null' }})"
                                                                    class="px-2 py-1 text-[11px] font-semibold rounded-md transition-colors
                                                                        {{ $row['status'] === 'pending' ? 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100' : 'text-amber-700 bg-amber-50 hover:bg-amber-100' }}">
                                                                    {{ $row['status'] === 'pending' ? 'Record' : 'Edit' }}
                                                                </button>
                                                                @if (!$row['is_paid'])
                                                                    <button
                                                                        wire:click="openMarkPaidModal({{ $row['id'] }}, {{ $row['structure_id'] }}, {{ $row['total_fee'] }}, {{ $row['collected'] }}, {{ $row['payment_id'] ?? 'null' }})"
                                                                        class="px-2 py-1 text-[11px] font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-md transition-colors">
                                                                        Mark Paid
                                                                    </button>
                                                                @endif
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

                        {{-- ── ONE TIME: installment periods (monthly Apr–Mar / quarterly / yearly) ── --}}
                        @if ($updateOrgFeeType === 'one_time')
                            @if (empty($installments))
                                <div class="text-center py-10 text-sm text-gray-400">No installment periods found.</div>
                            @else
                                <div class="border border-gray-200 rounded-md overflow-hidden">
                                    <div class="overflow-x-auto">
                                        <table class="w-full min-w-[560px]">
                                            <thead class="bg-gray-50 border-b border-gray-200">
                                                <tr>
                                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500">Period</th>
                                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500">Amount</th>
                                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500">Collected</th>
                                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500">Status</th>
                                                    <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-500">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                                @foreach ($installments as $inst)
                                                    <tr class="hover:bg-gray-50/60">
                                                        <td class="px-3 py-2.5">
                                                            <p class="text-sm font-medium text-gray-800">{{ $inst['label'] }}</p>
                                                            @if ($inst['payment_date'] !== '—')
                                                                <p class="text-[11px] text-gray-400">{{ $inst['payment_date'] }} · {{ ucfirst($inst['payment_mode']) }}</p>
                                                            @endif
                                                        </td>
                                                        <td class="px-3 py-2.5 text-sm text-gray-700">₹{{ number_format($inst['amount'], 0) }}</td>
                                                        <td class="px-3 py-2.5">
                                                            <p class="text-sm font-semibold text-gray-900">₹{{ number_format($inst['collected'], 0) }}</p>
                                                            @if ($inst['status'] === 'partial')
                                                                <p class="text-[11px] text-red-500">₹{{ number_format($inst['remaining'], 0) }} due</p>
                                                            @endif
                                                        </td>
                                                        <td class="px-3 py-2.5 text-sm font-medium {{ $upStatusTone[$inst['status']] ?? 'text-gray-500' }}">{{ $upStatusName[$inst['status']] ?? ucfirst($inst['status']) }}</td>
                                                        <td class="px-3 py-2.5">
                                                            <div class="flex items-center justify-center gap-1">
                                                                <button
                                                                    wire:click="openInstallmentPayModal('{{ $inst['key'] }}', '{{ $inst['label'] }}', {{ $inst['structure_id'] }}, {{ $inst['amount'] }}, {{ $inst['collected'] }}, {{ $inst['payment_id'] ?? 'null' }})"
                                                                    class="px-2 py-1 text-[11px] font-semibold rounded-md transition-colors
                                                                        {{ $inst['status'] === 'pending' ? 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100' : 'text-amber-700 bg-amber-50 hover:bg-amber-100' }}">
                                                                    {{ $inst['status'] === 'pending' ? 'Record' : 'Edit' }}
                                                                </button>
                                                                @if ($inst['status'] !== 'paid')
                                                                    <button
                                                                        wire:click="openMarkInstallmentPaidModal('{{ $inst['key'] }}', '{{ $inst['label'] }}', {{ $inst['structure_id'] }}, {{ $inst['amount'] }}, {{ $inst['collected'] }}, {{ $inst['payment_id'] ?? 'null' }})"
                                                                        class="px-2 py-1 text-[11px] font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-md transition-colors">
                                                                        Mark Paid
                                                                    </button>
                                                                @endif
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                @php
                                    $instTotal     = collect($installments)->sum('amount');
                                    $instCollected = collect($installments)->sum('collected');
                                @endphp
                                <div class="border border-gray-200 rounded-md px-4 py-3 flex items-center justify-between text-sm">
                                    <span class="text-gray-500">Total collected</span>
                                    <span>
                                        <strong class="text-emerald-700">₹{{ number_format($instCollected, 0) }}</strong>
                                        <span class="text-gray-400"> / ₹{{ number_format($instTotal, 0) }}</span>
                                    </span>
                                </div>
                            @endif
                        @endif

                    @endif
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeUpdatePanel" type="button"
                        class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">
                        Close
                    </button>
                </div>

            </div>
        </div>
    @endif

    {{-- ══════════ LEGACY PER-CLASS FEE EDIT MODAL ══════════ --}}
    <x-modal-form :show="$showEditModal" title="Edit Fee" submitAction="saveEditFee" submitButton="Update"
        closeAction="closeEditModal">
        <div class="space-y-4">
            <x-input wire:model.defer="editLabel" label="Fee Label *" placeholder="Annual Platform Fee" />
            <x-input wire:model.defer="editAmount" label="Amount (₹) *" placeholder="0" type="number" />
        </div>
    </x-modal-form>

    {{-- ══════════ RECORD / EDIT PAYMENT MODAL ══════════ --}}
    @if ($showPayModal)
        <div class="fixed inset-x-0 bottom-0 top-16 z-[60] overflow-hidden" x-data @keydown.escape.window="$wire.closePayModal()">
            <div class="absolute inset-0 bg-black/[0.06] backdrop-blur-[1.5px]" wire:click="closePayModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-md bg-white shadow-2xl flex flex-col">

                {{-- Header --}}
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center {{ $isEditPayment ? 'bg-amber-50' : 'bg-emerald-50' }}">
                            @if ($isEditPayment)
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            @else
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                            @endif
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">{{ $isEditPayment ? 'Update Payment' : 'Record Payment' }}</h3>
                            <p class="text-xs text-gray-400">{{ $payContextLabel }}</p>
                        </div>
                    </div>
                    <button wire:click="closePayModal" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- Scrollable body --}}
                <div class="flex-1 overflow-y-auto p-5">
                <div x-data="{
                        amt: @js((float) ($payAmount ?: 0)),
                        total: @js((float) $payTotalFee),
                        fmt(n) { return '₹' + Math.round(n).toLocaleString('en-IN'); },
                        get remaining() { return Math.max(0, this.total - (parseFloat(this.amt) || 0)); },
                     }"
                     class="space-y-3">

                    {{-- Fee context --}}
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                            <p class="text-[11px] text-gray-400">Total fee</p>
                            <p class="text-sm font-bold text-gray-800">₹{{ number_format($payTotalFee, 0) }}</p>
                        </div>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                            <p class="text-[11px] text-gray-400">Already collected</p>
                            <p class="text-sm font-bold text-emerald-700">₹{{ number_format($payCollected, 0) }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-medium text-gray-600">Total collected (₹)</label>
                                <div class="flex items-center gap-2">
                                    <button type="button"
                                        x-on:click="amt = total; $refs.amtInput.value = total; $wire.set('payAmount', total, false)"
                                        class="text-[11px] font-semibold text-emerald-600 hover:underline">Pay full</button>
                                    <button type="button"
                                        x-on:click="amt = 0; $refs.amtInput.value = ''; $wire.set('payAmount', 0, false)"
                                        class="text-[11px] font-semibold text-gray-400 hover:underline">Clear</button>
                                </div>
                            </div>
                            <input wire:model.defer="payAmount" type="number" min="0" step="0.01" x-ref="amtInput"
                                x-on:input="amt = parseFloat($event.target.value) || 0"
                                placeholder="0"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                                       focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />

                            {{-- Live status hint --}}
                            <div class="mt-1.5 text-xs">
                                <template x-if="(parseFloat(amt) || 0) <= 0">
                                    <span class="text-gray-400">No amount entered — will be marked <strong>Pending</strong>.</span>
                                </template>
                                <template x-if="(parseFloat(amt) || 0) > 0 && total > 0 && (parseFloat(amt) || 0) + 0.01 >= total">
                                    <span class="text-emerald-600 font-medium">Fee fully paid ✓</span>
                                </template>
                                <template x-if="(parseFloat(amt) || 0) > 0 && (total <= 0 || (parseFloat(amt) || 0) + 0.01 < total)">
                                    <span class="text-amber-600 font-medium">
                                        Partial — <span x-text="fmt(remaining)"></span> will remain due.
                                    </span>
                                </template>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Payment Mode</label>
                            <select wire:model.defer="payMode"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                                       focus:ring-2 focus:ring-blue-500 bg-white">
                                <option value="cash">Cash</option>
                                <option value="online">Online</option>
                                <option value="cheque">Cheque</option>
                                <option value="upi">UPI</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Payment Date</label>
                            <input wire:model.defer="payDate" type="date"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                                       focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Remark (Optional)</label>
                            <input wire:model.defer="payRemark" type="text" placeholder="Optional remark"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                                       focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                        </div>
                    </div>
                </div>

                </div>{{-- /scrollable body --}}

                {{-- Footer --}}
                <div class="flex-shrink-0 px-5 py-4 border-t border-gray-100 flex items-center gap-2 bg-gray-50/50">
                    <button wire:click="savePayment"
                        class="flex-1 py-2.5 text-white text-sm font-semibold rounded-lg transition-colors
                            {{ $isEditPayment ? 'bg-amber-500 hover:bg-amber-600' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                        {{ $isEditPayment ? 'Update Payment' : 'Save Payment' }}
                    </button>
                    <button wire:click="closePayModal"
                        class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
