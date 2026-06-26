<div class="min-h-screen bg-gray-50">

    {{-- ══════════ LIST VIEW ══════════ --}}
    @if ($activeView === 'list')

        {{-- HEADER (clean — analytics moved out into cards below) --}}
        <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4 sm:py-5 sticky top-0 z-50">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Fees</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Manage license fees charged to schools</p>
                </div>
            </div>
        </div>

        <div class="p-4 sm:p-6 space-y-5">

            {{-- OVERVIEW CARDS --}}
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
                @php
                    $overview = [
                        ['label' => 'Students',    'value' => number_format($totalStudentsAll),          'accent' => 'text-gray-900', 'icon' => 'bg-gray-100'],
                        ['label' => 'To Collect',  'value' => '₹' . number_format($totalFeeToCollect, 0),'accent' => 'text-blue-600', 'icon' => 'bg-blue-50'],
                        ['label' => 'Collected',   'value' => '₹' . number_format($totalFeeCollected, 0),'accent' => 'text-emerald-600', 'icon' => 'bg-emerald-50'],
                        ['label' => 'Remaining',   'value' => '₹' . number_format($totalFeeRemaining, 0),'accent' => 'text-red-500', 'icon' => 'bg-red-50'],
                        ['label' => 'Avg/Student', 'value' => '₹' . number_format($avgFeePerStudent, 0), 'accent' => 'text-gray-700', 'icon' => 'bg-gray-100'],
                    ];
                @endphp
                @foreach ($overview as $card)
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                        <p class="text-xs font-medium text-gray-400">{{ $card['label'] }}</p>
                        <p class="text-lg sm:text-xl font-bold mt-1 {{ $card['accent'] }}">{{ $card['value'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- SEARCH --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <div class="relative max-w-md">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input wire:model.live.debounce.300ms="search" type="text"
                        placeholder="Search schools by name or code..."
                        class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg
                               focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                </div>
            </div>

            {{-- SCHOOL CARDS --}}
            @if (count($schools))
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach ($schools as $school)
                        @php
                            $orgStructures = \App\Models\SuperAdmin\SuperAdminFeeStructure::where(
                                'organization_id',
                                $school->id,
                            )
                                ->where('academic_year', $academicYear)
                                ->active()
                                ->get();
                            $orgExpected = 0;
                            foreach ($orgStructures as $fs) {
                                if ($fs->fee_type === 'one_time') {
                                    $cnt = \App\Models\Student\StudentDetail::where('organization_id', $school->id)->count();
                                } else {
                                    $cnt = \App\Models\Student\StudentDetail::where('organization_id', $school->id)
                                        ->where('standard_id', $fs->standard_id)
                                        ->count();
                                }
                                $orgExpected += $fs->amount * $cnt;
                            }
                            $orgCollected = (float) \App\Models\SuperAdmin\SuperAdminFeePayment::forOrg($school->id)
                                ->forYear($academicYear)
                                ->sum('amount');
                            $orgPct = $orgExpected > 0 ? round(($orgCollected / $orgExpected) * 100) : 0;
                        @endphp

                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md
                                    transition-all duration-200 overflow-hidden flex flex-col cursor-pointer"
                            wire:click="selectSchool({{ $school->id }})">

                            <div
                                class="pt-5 pb-3 px-4 flex flex-col items-center text-center border-b border-gray-100 relative">
                                <span
                                    class="absolute top-3 right-3 w-2 h-2 rounded-full
                                    {{ $school->status ? 'bg-emerald-500' : 'bg-amber-400' }}"></span>
                                @if ($school->logo)
                                    <img src="{{ $school->logo }}"
                                        class="w-14 h-14 rounded-full object-cover border-2 border-gray-200 shadow-sm mb-2">
                                @else
                                    <div
                                        class="w-14 h-14 rounded-full bg-indigo-100 border-2 border-indigo-200
                                                flex items-center justify-center mb-2">
                                        <span class="text-lg font-bold text-indigo-600">
                                            {{ strtoupper(substr($school->name, 0, 1)) }}
                                        </span>
                                    </div>
                                @endif
                                <h3 class="text-sm font-bold text-gray-900">{{ $school->name }}</h3>
                                <p class="text-xs text-gray-400 mt-0.5">Code: {{ $school->school_code ?? '—' }}</p>
                            </div>

                            <div class="p-4 space-y-3 flex-1">
                                @if ($orgExpected > 0)
                                    <div>
                                        <div class="flex items-center justify-between text-xs mb-1">
                                            <span class="text-gray-500">₹{{ number_format($orgCollected, 0) }} /
                                                ₹{{ number_format($orgExpected, 0) }}</span>
                                            <span
                                                class="font-semibold {{ $orgPct >= 100 ? 'text-emerald-600' : 'text-gray-600' }}">{{ $orgPct }}%</span>
                                        </div>
                                        <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full transition-all
                                                {{ $orgPct >= 100 ? 'bg-emerald-500' : 'bg-blue-500' }}"
                                                style="width: {{ min(100, $orgPct) }}%"></div>
                                        </div>
                                    </div>
                                @else
                                    <p class="text-xs text-center text-gray-400">No fee structure set</p>
                                @endif
                            </div>

                            <div class="px-4 py-2.5 bg-blue-50 border-t border-blue-100 text-center">
                                <span class="text-xs font-semibold text-blue-600">Click to manage fees →</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5" />
                        </svg>
                    </div>
                    <p class="text-gray-500 text-sm">No schools found</p>
                </div>
            @endif
        </div>
    @endif

    {{-- ══════════ SCHOOL DETAIL VIEW ══════════ --}}
    @if ($activeView === 'school' && $selectedSchool)

        {{-- HEADER — includes school stats strip --}}
        <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4 sm:py-5 sticky top-0 z-50">
            <div class="flex items-center justify-between gap-4 mb-3">
                <div class="flex items-center gap-3">
                    <button wire:click="backToList"
                        class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-gray-200
                               text-gray-500 hover:text-gray-800 hover:bg-gray-50 text-sm transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span class="hidden sm:inline">Back</span>
                    </button>
                    <div class="flex items-center gap-3">
                        @if ($selectedSchool->logo)
                            <img src="{{ $selectedSchool->logo }}"
                                class="w-9 h-9 rounded-full object-cover border border-gray-200">
                        @else
                            <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center">
                                <span class="text-sm font-bold text-indigo-600">
                                    {{ strtoupper(substr($selectedSchool->name, 0, 1)) }}
                                </span>
                            </div>
                        @endif
                        <div>
                            <h1 class="text-base font-bold text-gray-900 leading-none">{{ $selectedSchool->name }}</h1>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $academicYear }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-4 sm:p-6 space-y-5">

            {{-- SCHOOL OVERVIEW CARDS (moved out of the header) --}}
            @if (!empty($schoolStats))
                <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
                    @php
                        $sStats = [
                            ['label' => 'Students',    'value' => number_format($schoolStats['total_students']),        'accent' => 'text-gray-900'],
                            ['label' => 'To Collect',  'value' => '₹' . number_format($schoolStats['total_to_collect'], 0), 'accent' => 'text-blue-600'],
                            ['label' => 'Collected',   'value' => '₹' . number_format($schoolStats['collected'], 0),    'accent' => 'text-emerald-600'],
                            ['label' => 'Remaining',   'value' => '₹' . number_format($schoolStats['remaining'], 0),    'accent' => 'text-red-500'],
                            ['label' => 'Avg/Student', 'value' => '₹' . number_format($schoolStats['avg_per_student'], 0), 'accent' => 'text-gray-700'],
                        ];
                    @endphp
                    @foreach ($sStats as $card)
                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                            <p class="text-xs font-medium text-gray-400">{{ $card['label'] }}</p>
                            <p class="text-lg font-bold mt-1 {{ $card['accent'] }}">{{ $card['value'] }}</p>
                        </div>
                    @endforeach
                </div>

                @if ($schoolStats['total_to_collect'] > 0)
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                        <div class="flex items-center justify-between text-xs text-gray-500 mb-1.5">
                            <span>₹{{ number_format($schoolStats['collected'], 0) }} collected of
                                ₹{{ number_format($schoolStats['total_to_collect'], 0) }}</span>
                            <span
                                class="font-semibold {{ $schoolStats['collection_pct'] >= 100 ? 'text-emerald-600' : 'text-blue-600' }}">
                                {{ $schoolStats['collection_pct'] }}%
                            </span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all
                                {{ $schoolStats['collection_pct'] >= 100 ? 'bg-emerald-500' : 'bg-blue-500' }}"
                                style="width: {{ min(100, $schoolStats['collection_pct']) }}%"></div>
                        </div>
                    </div>
                @endif
            @endif

            {{-- TABS --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="flex border-b border-gray-200 overflow-x-auto">
                    @foreach ([
        'add_fee' => 'Add Fee',
        'view_fee' => 'View Fee',
        'analytics' => 'Analytics',
        'update' => 'Update Payments',
    ] as $tab => $label)
                        <button wire:click="setTab('{{ $tab }}')"
                            class="px-5 py-3 text-sm font-medium whitespace-nowrap transition-colors border-b-2
                                {{ $activeTab === $tab
                                    ? 'border-blue-600 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- ════ ADD FEE TAB ════ --}}
                @if ($activeTab === 'add_fee')
                    <div class="p-5">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-700">Fee Structure</h3>
                                <p class="text-xs text-gray-400 mt-0.5">Academic year: {{ $academicYear }}</p>
                            </div>
                        </div>

                        {{-- Fee Type Switcher --}}
                        <div class="grid grid-cols-2 gap-3 mb-5">
                            <button wire:click="$set('feeType', 'class_wise')"
                                class="flex items-center gap-3 p-3 rounded-xl border-2 transition-all text-left
                                    {{ $feeType === 'class_wise'
                                        ? 'border-blue-500 bg-blue-50'
                                        : 'border-gray-200 bg-white hover:border-gray-300' }}">
                                <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0
                                    {{ $feeType === 'class_wise' ? 'bg-blue-500' : 'bg-gray-100' }}">
                                    <svg class="w-4 h-4 {{ $feeType === 'class_wise' ? 'text-white' : 'text-gray-500' }}"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 10h18M3 6h18M3 14h18M3 18h18" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold {{ $feeType === 'class_wise' ? 'text-blue-700' : 'text-gray-700' }}">
                                        Class Wise</p>
                                    <p class="text-xs {{ $feeType === 'class_wise' ? 'text-blue-500' : 'text-gray-400' }}">
                                        Different fee per class</p>
                                </div>
                            </button>

                            <button wire:click="$set('feeType', 'one_time')"
                                class="flex items-center gap-3 p-3 rounded-xl border-2 transition-all text-left
                                    {{ $feeType === 'one_time'
                                        ? 'border-indigo-500 bg-indigo-50'
                                        : 'border-gray-200 bg-white hover:border-gray-300' }}">
                                <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0
                                    {{ $feeType === 'one_time' ? 'bg-indigo-500' : 'bg-gray-100' }}">
                                    <svg class="w-4 h-4 {{ $feeType === 'one_time' ? 'text-white' : 'text-gray-500' }}"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold {{ $feeType === 'one_time' ? 'text-indigo-700' : 'text-gray-700' }}">
                                        One Time</p>
                                    <p class="text-xs {{ $feeType === 'one_time' ? 'text-indigo-500' : 'text-gray-400' }}">
                                        Same fee for all students</p>
                                </div>
                            </button>
                        </div>

                        {{-- ── CLASS WISE FORM ── --}}
                        @if ($feeType === 'class_wise')
                            @if (count($standards))
                                <div class="border border-gray-200 rounded-xl overflow-hidden">
                                    <table class="w-full">
                                        <thead class="bg-gray-50 border-b border-gray-200">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Class</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Students</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Fee Label</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Amount (₹)</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach ($standards as $standard)
                                                @php
                                                    $studentCount = \App\Models\Student\StudentDetail::where(
                                                        'organization_id', $selectedSchool->id)
                                                        ->where('standard_id', $standard->id)
                                                        ->count();
                                                @endphp
                                                <tr class="hover:bg-gray-50/50">
                                                    <td class="px-4 py-3">
                                                        <span class="text-sm font-semibold text-gray-800">{{ $standard->name }}</span>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <span class="text-xs px-2 py-0.5 bg-blue-50 text-blue-700 rounded-full font-medium">
                                                            {{ $studentCount }} students
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <input wire:model.defer="feeInputs.{{ $standard->id }}.label"
                                                            type="text" placeholder="Annual Platform Fee"
                                                            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg
                                                                   focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <input wire:model.defer="feeInputs.{{ $standard->id }}.amount"
                                                            type="number" placeholder="0" min="0"
                                                            class="w-32 px-3 py-1.5 text-sm border border-gray-300 rounded-lg
                                                                   focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-10 text-sm text-gray-400">
                                    No classes found for this school.
                                </div>
                            @endif
                        @endif

                        {{-- ── ONE TIME FORM ── --}}
                        @if ($feeType === 'one_time')
                            @php
                                $oneTimeTotalStudents = \App\Models\Student\StudentDetail::where('organization_id', $selectedSchool->id)->count();
                                $oneTimeTotalAmt  = (float)($oneTimeTotalAmount ?: 0);
                                $oneTimePerStudent = $oneTimeTotalStudents > 0 ? round($oneTimeTotalAmt / $oneTimeTotalStudents, 2) : 0;
                            @endphp
                            <div class="space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Fee Label *</label>
                                        <input wire:model.defer="oneTimeLabel" type="text"
                                            placeholder="Annual Platform Fee"
                                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                                                   focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Total School Fee (₹) *</label>
                                        <input wire:model.defer="oneTimeTotalAmount" type="number" placeholder="e.g. 10000" min="1"
                                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                                                   focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                                    </div>
                                </div>

                                {{-- Calculation preview --}}
                                <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4">
                                    <div class="flex items-center justify-between gap-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-xs text-indigo-600 font-semibold">Per Student Fee</p>
                                                <p class="text-xs text-indigo-400 mt-0.5">
                                                    ₹{{ number_format($oneTimeTotalAmt, 0) }} ÷ {{ $oneTimeTotalStudents }} students
                                                </p>
                                            </div>
                                        </div>
                                        <p class="text-xl font-bold text-indigo-700 flex-shrink-0">
                                            ₹{{ number_format($oneTimePerStudent, 2) }}
                                            <span class="text-xs font-normal text-indigo-400">/student</span>
                                        </p>
                                    </div>
                                    @if ($oneTimeTotalAmt > 0 && $oneTimeTotalStudents > 0)
                                        <div class="mt-3 pt-3 border-t border-indigo-100 flex items-center justify-between text-xs text-indigo-500">
                                            <span>Total to collect</span>
                                            <span class="font-bold text-indigo-700">₹{{ number_format($oneTimeTotalAmt, 0) }}</span>
                                        </div>
                                    @endif
                                </div>

                                <p class="text-xs text-amber-600 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
                                    <strong>Note:</strong> Switching to One Time will remove all class-wise fee structures for this school and year.
                                </p>
                            </div>
                        @endif

                        {{-- Save button --}}
                        @if ($feeType === 'class_wise' && count($standards) || $feeType === 'one_time')
                            <div class="mt-4 flex justify-end">
                                <button wire:click="saveFeeStructures"
                                    class="inline-flex items-center gap-1.5 px-5 py-2.5 text-white text-sm font-semibold
                                           rounded-lg transition-colors shadow-sm
                                           {{ $feeType === 'one_time'
                                               ? 'bg-indigo-600 hover:bg-indigo-700'
                                               : 'bg-blue-600 hover:bg-blue-700' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Save Fee Structure
                                </button>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- ════ VIEW FEE TAB ════ --}}
                @if ($activeTab === 'view_fee')
                    <div class="p-5">
                        @if ($feeStructures->count())
                            <div class="border border-gray-200 rounded-xl overflow-hidden">
                                <table class="w-full">
                                    <thead class="bg-gray-50 border-b border-gray-200">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 w-10">#</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Type</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Class</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Label</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Amount</th>
                                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($feeStructures as $i => $fs)
                                            <tr class="hover:bg-gray-50/50">
                                                <td class="px-4 py-3 text-xs text-gray-400">{{ $i + 1 }}</td>
                                                <td class="px-4 py-3">
                                                    @if ($fs->fee_type === 'one_time')
                                                        <span class="text-xs px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-full font-medium border border-indigo-100">
                                                            One Time
                                                        </span>
                                                    @else
                                                        <span class="text-xs px-2 py-0.5 bg-blue-50 text-blue-700 rounded-full font-medium border border-blue-100">
                                                            Class Wise
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3">
                                                    @if ($fs->fee_type === 'one_time')
                                                        <span class="text-xs text-gray-400">All Students</span>
                                                    @else
                                                        <span class="text-xs px-2 py-0.5 bg-gray-50 text-gray-700 rounded font-medium border border-gray-200">
                                                            {{ $fs->standard?->name ?? '—' }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-600">{{ $fs->fee_label ?? '—' }}</td>
                                                <td class="px-4 py-3 text-sm font-bold text-emerald-700">
                                                    ₹{{ number_format($fs->amount, 0) }}
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center justify-center gap-1">
                                                        <button wire:click="openEditFee({{ $fs->id }})"
                                                            class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"
                                                            title="Edit">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                            </svg>
                                                        </button>
                                                        <button wire:click="deleteFee({{ $fs->id }})"
                                                            class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                            title="Delete">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-12 text-sm text-gray-400">
                                No fee structure set yet. Go to <strong>Add Fee</strong> tab to create one.
                            </div>
                        @endif
                    </div>
                @endif

                {{-- ════ ANALYTICS TAB ════ --}}
                @if ($activeTab === 'analytics')
                    <div class="p-5 space-y-6">

                        {{-- FY Monthly Chart --}}
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Monthly
                                Collections</p>
                            <p class="text-[10px] text-gray-400 mb-3">FY Apr 2026 – Mar 2027</p>

                            @php
                                $fyMonths = [
                                    ['label' => 'Apr', 'month' => 4, 'year' => 2026],
                                    ['label' => 'May', 'month' => 5, 'year' => 2026],
                                    ['label' => 'Jun', 'month' => 6, 'year' => 2026],
                                    ['label' => 'Jul', 'month' => 7, 'year' => 2026],
                                    ['label' => 'Aug', 'month' => 8, 'year' => 2026],
                                    ['label' => 'Sep', 'month' => 9, 'year' => 2026],
                                    ['label' => 'Oct', 'month' => 10, 'year' => 2026],
                                    ['label' => 'Nov', 'month' => 11, 'year' => 2026],
                                    ['label' => 'Dec', 'month' => 12, 'year' => 2026],
                                    ['label' => 'Jan', 'month' => 1, 'year' => 2027],
                                    ['label' => 'Feb', 'month' => 2, 'year' => 2027],
                                    ['label' => 'Mar', 'month' => 3, 'year' => 2027],
                                ];
                                $fyChart = $schoolStats['fy_monthly_chart'] ?? [];
                                $maxVal = !empty($fyChart) ? max(array_values($fyChart)) : 1;
                                $maxVal = max($maxVal, 1);
                                $nowMonth = now()->month;
                                $nowYear = now()->year;
                            @endphp

                            <div class="flex items-end gap-1">
                                @foreach ($fyMonths as $m)
                                    @php
                                        $key = "{$m['year']}-{$m['month']}";
                                        $val = $fyChart[$key] ?? 0;
                                        $pct = ($val / $maxVal) * 100;
                                        $barH = max(4, round($pct * 0.9));
                                        $isCurrent = $m['month'] == $nowMonth && $m['year'] == $nowYear;
                                        $color = $isCurrent ? '#3b82f6' : ($val > 0 ? '#bfdbfe' : '#f3f4f6');
                                    @endphp
                                    <div class="flex-1 flex flex-col items-center gap-0.5">
                                        <div class="w-full rounded-sm transition-all"
                                            style="height: {{ $barH }}px; background-color: {{ $color }};">
                                        </div>
                                        <span class="text-[8px] text-gray-400 leading-none">{{ $m['label'] }}</span>
                                        <span class="text-[7px] text-gray-500 font-medium leading-none mt-0.5">
                                            @if ($val > 0)
                                                ₹{{ number_format($val / 1000, 0) }}k
                                            @else
                                                —
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Per Class Breakdown --}}
                        @if ($feeStructures->count())
                            @php $hasOneTime = $feeStructures->where('fee_type', 'one_time')->count() > 0; @endphp
                            <div>
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">
                                    {{ $hasOneTime ? 'Fee Breakdown' : 'Per Class Breakdown' }}
                                </p>
                                <div class="divide-y divide-gray-100 border border-gray-200 rounded-xl overflow-hidden">
                                    @foreach ($feeStructures as $fs)
                                        @php
                                            if ($fs->fee_type === 'one_time') {
                                                $classStudents = \App\Models\Student\StudentDetail::where('organization_id', $selectedSchool->id)->count();
                                            } else {
                                                $classStudents = \App\Models\Student\StudentDetail::where('organization_id', $selectedSchool->id)
                                                    ->where('standard_id', $fs->standard_id)->count();
                                            }
                                            $classExpected  = $fs->amount * $classStudents;
                                            $classCollected = (float) \App\Models\SuperAdmin\SuperAdminFeePayment::where('super_admin_fee_structure_id', $fs->id)->sum('amount');
                                            $classPct       = $classExpected > 0 ? round(($classCollected / $classExpected) * 100) : 0;
                                        @endphp
                                        <div class="px-4 py-3 hover:bg-gray-50/50 transition-colors">
                                            <div class="flex items-center justify-between gap-4">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    @if ($fs->fee_type === 'one_time')
                                                        <span class="text-xs px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded font-medium border border-indigo-100 flex-shrink-0">
                                                            One Time
                                                        </span>
                                                    @else
                                                        <span class="text-xs px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded font-medium border border-indigo-100 flex-shrink-0">
                                                            {{ $fs->standard?->name }}
                                                        </span>
                                                    @endif
                                                    <span class="text-xs text-gray-400 flex-shrink-0">{{ $classStudents }} students</span>
                                                </div>
                                                <div class="flex items-center gap-3 text-xs flex-shrink-0">
                                                    <span class="text-gray-400">₹/student: <strong class="text-gray-700">{{ number_format($fs->amount, 0) }}</strong></span>
                                                    <span class="text-emerald-600 font-semibold">₹{{ number_format($classCollected, 0) }}</span>
                                                    <span class="text-gray-300">/</span>
                                                    <span class="text-gray-600 font-medium">₹{{ number_format($classExpected, 0) }}</span>
                                                    <span class="w-10 text-right font-bold {{ $classPct >= 100 ? 'text-emerald-600' : 'text-gray-600' }}">
                                                        {{ $classPct }}%
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="mt-2 h-1 bg-gray-100 rounded-full overflow-hidden">
                                                <div class="h-full rounded-full {{ $classPct >= 100 ? 'bg-emerald-500' : 'bg-blue-500' }}"
                                                    style="width: {{ min(100, $classPct) }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="text-center py-8 text-sm text-gray-400">No fee structure set yet.</div>
                        @endif
                    </div>
                @endif

                {{-- ════ UPDATE PAYMENTS TAB ════ --}}
                @if ($activeTab === 'update')
                    <div class="p-5 space-y-4">

                        {{-- Filters: class_wise shows selectors, one_time shows Load All button --}}
                        @if ($feeType === 'one_time')
                            <div class="flex items-center gap-3">
                                <div class="flex-1 bg-indigo-50 border border-indigo-100 rounded-xl px-4 py-3">
                                    <p class="text-sm font-medium text-indigo-700">One Time Fee Mode</p>
                                    <p class="text-xs text-indigo-500 mt-0.5">Fee applies to all students in this school</p>
                                </div>
                                <button wire:click="loadStudentFeeList"
                                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold
                                           rounded-lg transition-colors whitespace-nowrap">
                                    Load All Students
                                </button>
                            </div>
                        @else
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <select wire:model.live="updateStandardId"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                                           focus:ring-2 focus:ring-blue-500 bg-white">
                                    <option value="">Select Class</option>
                                    @foreach ($standards as $standard)
                                        <option value="{{ $standard->id }}">{{ $standard->name }}</option>
                                    @endforeach
                                </select>

                                <select wire:model.live="updateSectionId" @disabled(!$updateStandardId)
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                                           focus:ring-2 focus:ring-blue-500 bg-white disabled:opacity-50 disabled:cursor-not-allowed">
                                    <option value="">All Sections</option>
                                    @foreach ($updateSections as $section)
                                        <option value="{{ $section->id }}">{{ $section->name }}</option>
                                    @endforeach
                                </select>

                                <button wire:click="loadStudentFeeList" @disabled(!$updateStandardId)
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold
                                           rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                    Load Students
                                </button>
                            </div>
                        @endif

                        {{-- Student Fee Table --}}
                        @if (count($studentFeeList))
                            <div class="border border-gray-200 rounded-xl overflow-hidden">
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead class="bg-gray-50 border-b border-gray-200">
                                            <tr>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500">#</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500">Name</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500">Admission No</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500">Mobile</th>
                                                @if ($feeType === 'one_time')
                                                    <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500">Class</th>
                                                @endif
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500">Section</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500">Fee</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500">Collected</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500">Remaining</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500">Status</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500">Date</th>
                                                <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach ($studentFeeList as $row)
                                                @php
                                                    $statusMeta = [
                                                        'paid'    => ['label' => 'Paid',    'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'rowBg' => 'bg-emerald-50/30'],
                                                        'partial' => ['label' => 'Partial', 'badge' => 'bg-amber-50 text-amber-700 border-amber-200',       'rowBg' => 'bg-amber-50/30'],
                                                        'pending' => ['label' => 'Pending', 'badge' => 'bg-gray-100 text-gray-500 border-gray-200',          'rowBg' => ''],
                                                    ][$row['status']] ?? ['label' => 'Pending', 'badge' => 'bg-gray-100 text-gray-500 border-gray-200', 'rowBg' => ''];
                                                @endphp
                                                <tr class="hover:bg-gray-50/50 {{ $statusMeta['rowBg'] }}">
                                                    <td class="px-3 py-3 text-xs text-gray-400">{{ $row['serial'] }}</td>
                                                    <td class="px-3 py-3">
                                                        <p class="text-sm font-medium text-gray-800">{{ $row['name'] }}</p>
                                                        <p class="text-xs text-gray-400">{{ $row['email'] }}</p>
                                                    </td>
                                                    <td class="px-3 py-3 text-xs font-mono text-gray-700">{{ $row['admission_no'] }}</td>
                                                    <td class="px-3 py-3 text-xs text-gray-600 font-mono">{{ $row['mobile'] }}</td>
                                                    @if ($feeType === 'one_time')
                                                        <td class="px-3 py-3 text-xs text-gray-600">{{ $row['standard'] }}</td>
                                                    @endif
                                                    <td class="px-3 py-3 text-xs text-gray-600">{{ $row['section'] }}</td>
                                                    <td class="px-3 py-3 text-sm font-semibold text-gray-800">
                                                        ₹{{ number_format($row['total_fee'], 0) }}
                                                    </td>
                                                    <td class="px-3 py-3 text-sm font-semibold text-emerald-700">
                                                        ₹{{ number_format($row['collected'], 0) }}
                                                    </td>
                                                    <td class="px-3 py-3 text-sm font-semibold
                                                        {{ $row['remaining'] > 0 ? 'text-red-500' : 'text-gray-400' }}">
                                                        ₹{{ number_format($row['remaining'], 0) }}
                                                    </td>
                                                    <td class="px-3 py-3">
                                                        <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full font-medium border {{ $statusMeta['badge'] }}">
                                                            {{ $statusMeta['label'] }}
                                                            @if ($row['status'] === 'partial')
                                                                <span class="font-semibold">· ₹{{ number_format($row['remaining'], 0) }} left</span>
                                                            @endif
                                                        </span>
                                                    </td>
                                                    <td class="px-3 py-3 text-xs text-gray-500 whitespace-nowrap">
                                                        {{ $row['payment_date'] }}
                                                        @if ($row['status'] !== 'pending' && $row['payment_mode'] !== '—')
                                                            <span class="block text-gray-400 capitalize">{{ $row['payment_mode'] }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-3">
                                                        @if ($row['structure_id'])
                                                            <div class="flex items-center justify-center gap-1">
                                                                {{-- Record / update payment (handles partial + full) --}}
                                                                <button
                                                                    wire:click="openPayModal({{ $row['id'] }}, {{ $row['structure_id'] }}, {{ $row['total_fee'] }}, {{ $row['collected'] }}, {{ $row['payment_id'] ?? 'null' }})"
                                                                    title="{{ $row['status'] === 'pending' ? 'Record payment' : 'Update payment' }}"
                                                                    class="inline-flex items-center justify-center w-7 h-7 rounded-full transition-colors
                                                                        {{ $row['status'] === 'pending'
                                                                            ? 'bg-gray-200 text-gray-500 hover:bg-blue-100 hover:text-blue-600'
                                                                            : 'bg-amber-50 text-amber-600 border border-amber-200 hover:bg-amber-100' }}">
                                                                    @if ($row['status'] === 'pending')
                                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                                        </svg>
                                                                    @else
                                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                        </svg>
                                                                    @endif
                                                                </button>

                                                                {{-- Quick "mark fully paid" when not already paid --}}
                                                                @if ($row['status'] !== 'paid')
                                                                    <button wire:click="markFullyPaid({{ $row['id'] }}, {{ $row['structure_id'] }})"
                                                                        title="Mark fully paid"
                                                                        class="inline-flex items-center justify-center w-7 h-7 bg-emerald-50 text-emerald-600
                                                                               border border-emerald-200 rounded-full hover:bg-emerald-500 hover:text-white transition-colors">
                                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                                        </svg>
                                                                    </button>
                                                                @else
                                                                    <span title="Fully paid"
                                                                        class="inline-flex items-center justify-center w-7 h-7 bg-emerald-500 text-white rounded-full">
                                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                                        </svg>
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        @else
                                                            <span class="block text-center text-xs text-gray-300">No fee set</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="bg-gray-50 border-t border-gray-200">
                                            <tr>
                                                <td colspan="{{ $feeType === 'one_time' ? 6 : 5 }}"
                                                    class="px-3 py-2.5 text-xs font-semibold text-gray-600">
                                                    Total ({{ count($studentFeeList) }} students)
                                                </td>
                                                <td class="px-3 py-2.5 text-sm font-bold text-gray-800">
                                                    ₹{{ number_format(collect($studentFeeList)->sum('total_fee'), 0) }}
                                                </td>
                                                <td class="px-3 py-2.5 text-sm font-bold text-emerald-700">
                                                    ₹{{ number_format(collect($studentFeeList)->sum('collected'), 0) }}
                                                </td>
                                                <td class="px-3 py-2.5 text-sm font-bold text-red-500">
                                                    ₹{{ number_format(collect($studentFeeList)->sum('remaining'), 0) }}
                                                </td>
                                                @php
                                                    $cnt = collect($studentFeeList);
                                                    $paidCnt    = $cnt->where('status', 'paid')->count();
                                                    $partialCnt = $cnt->where('status', 'partial')->count();
                                                    $pendingCnt = $cnt->where('status', 'pending')->count();
                                                @endphp
                                                <td class="px-3 py-2.5 text-xs whitespace-nowrap">
                                                    <span class="text-emerald-600 font-semibold">{{ $paidCnt }} paid</span>
                                                    <span class="text-gray-300">·</span>
                                                    <span class="text-amber-600 font-semibold">{{ $partialCnt }} partial</span>
                                                    <span class="text-gray-300">·</span>
                                                    <span class="text-gray-500 font-semibold">{{ $pendingCnt }} pending</span>
                                                </td>
                                                <td class="px-3 py-2.5 text-xs text-gray-500">—</td>
                                                <td class="px-3 py-2.5"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        @elseif ($feeType === 'class_wise' && $updateStandardId)
                            <div class="text-center py-8 text-sm text-gray-400">
                                No students found. Click "Load Students" to fetch.
                            </div>
                        @else
                            <div class="text-center py-8 text-sm text-gray-400">
                                @if ($feeType === 'one_time')
                                    Click "Load All Students" to view fee status.
                                @else
                                    Select a class and click "Load Students".
                                @endif
                            </div>
                        @endif
                    </div>
                @endif

            </div>
        </div>
    @endif

    {{-- ══════════ EDIT FEE STRUCTURE MODAL ══════════ --}}
    <x-modal-form :show="$showEditModal" title="Edit Fee" submitAction="saveEditFee" submitButton="Update"
        closeAction="closeEditModal">
        <div class="space-y-4">
            <x-input wire:model.defer="editLabel" label="Fee Label *" placeholder="Annual Platform Fee" />
            <x-input wire:model.defer="editAmount" label="Amount (₹) *" placeholder="0" type="number" />
        </div>
    </x-modal-form>

    {{-- ══════════ RECORD / EDIT PAYMENT MODAL ══════════ --}}
    @if ($showPayModal)
        <div class="fixed inset-0 flex items-center justify-center bg-black/30 backdrop-blur-sm z-[9999] px-4">
            <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full">
                <div class="flex items-center gap-3 mb-5">
                    <div
                        class="w-9 h-9 rounded-lg flex items-center justify-center
                        {{ $isEditPayment ? 'bg-amber-50' : 'bg-emerald-50' }}">
                        @if ($isEditPayment)
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        @else
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">
                            {{ $isEditPayment ? 'Update Payment' : 'Record Payment' }}
                        </h3>
                        <p class="text-xs text-gray-400">{{ $payStudentName }}</p>
                    </div>
                </div>

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

                <div class="flex items-center gap-2 mt-5">
                    <button wire:click="savePayment"
                        class="flex-1 py-2 text-white text-sm font-medium rounded-lg transition-colors
                            {{ $isEditPayment ? 'bg-amber-500 hover:bg-amber-600' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                        {{ $isEditPayment ? 'Update Payment' : 'Save Payment' }}
                    </button>
                    <button wire:click="closePayModal"
                        class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium
                               rounded-lg transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
