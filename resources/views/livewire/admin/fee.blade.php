<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════
         HEADER (sticky: title + contextual stats + dynamic Add + tabs + filter bar)
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200">
        <div class="px-4 sm:px-6 py-4 sm:py-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3">
                    @if ($activeTab !== '')
                        <button wire:click="backToMenu" title="Back to fee sections"
                            class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                        </button>
                    @endif
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Fees</h1>
                        <p class="text-sm text-gray-500 mt-0.5">Manage fee structures, submissions, cycles and analytics</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    {{-- Contextual stat (inline, exam-style) --}}
                    @if ($activeTab === 'fee_submission' && $selectedStudentId)
                        <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-1">
                            <span>Net Payable: <strong class="text-blue-600">₹{{ number_format($netPayable, 0) }}</strong></span>
                        </div>
                    @elseif ($activeTab === 'payments')
                        <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-1">
                            <span>Filtered Total: <strong class="text-emerald-600">₹{{ number_format($paymentFilteredTotal ?? 0, 0) }}</strong></span>
                        </div>
                    @elseif ($activeTab === 'penalties' && $penaltyStudentId)
                        <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-1 divide-x divide-gray-200">
                            <span class="pr-4">Penalty: <strong class="text-red-600">₹{{ number_format($penaltyGross, 0) }}</strong></span>
                            <span class="pl-4">Net Due: <strong class="text-gray-800">₹{{ number_format($penaltyNet, 0) }}</strong></span>
                        </div>
                    @elseif ($activeTab === 'concession')
                        <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-1">
                            <span>Concessions: <strong class="text-emerald-600">{{ \App\Models\Admin\Fee\FeeConcession::where('organization_id', auth()->user()->organization_id)->count() }}</strong></span>
                        </div>
                    @endif

                    {{-- Per-tab primary button --}}
                    @if ($activeTab === 'fee_submission')
                        <button wire:click="openSubmitPanel" @disabled(!$selectedStudentId)
                            class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                            <span class="hidden sm:inline">Update Fee</span>
                            <span class="sm:hidden">Update</span>
                        </button>
                    @elseif ($activeTab === 'concession')
                        <button wire:click="openConcessionModal()"
                            class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                            <span class="hidden sm:inline">Add Concession</span>
                            <span class="sm:hidden">New</span>
                        </button>
                    @elseif ($activeTab === 'cycle')
                        <button wire:click="openCycleModal()"
                            class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                            <span class="hidden sm:inline">Add Installment</span>
                            <span class="sm:hidden">New</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Tabs as cards (Lists-style) --}}
        @php
            $feeTabs = [
                'fee_structure'  => ['Fee Structure',  'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', 'Class-wise fee heads & amounts', 'blue'],
                'fee_submission' => ['Fee Submission', 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', 'Collect & record student fees', 'emerald'],
                'view_fee'       => ['View Fee',        'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', "A student's full fee ledger", 'indigo'],
                'analytics'      => ['Analytics',       'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'Collections & dues overview', 'rose'],
                'payments'       => ['Payments',        'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z', 'All recorded fee payments', 'cyan'],
                'penalties'      => ['Penalties',       'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', 'Late-fee penalties', 'amber'],
                'cycle'          => ['Fee Cycle',       'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', 'Installments & due dates', 'purple'],
                'concession'     => ['Concession',      'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z', 'Discounts & waivers', 'teal'],
                'account_users'  => ['Account Users',   'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'Cashier / account logins', 'orange'],
            ];
            $feeColorMap = [
                'blue'    => ['bg' => 'bg-blue-50',    'text' => 'text-blue-600'],
                'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600'],
                'indigo'  => ['bg' => 'bg-indigo-50',  'text' => 'text-indigo-600'],
                'rose'    => ['bg' => 'bg-rose-50',    'text' => 'text-rose-600'],
                'cyan'    => ['bg' => 'bg-cyan-50',    'text' => 'text-cyan-600'],
                'amber'   => ['bg' => 'bg-amber-50',   'text' => 'text-amber-600'],
                'purple'  => ['bg' => 'bg-purple-50',  'text' => 'text-purple-600'],
                'teal'    => ['bg' => 'bg-teal-50',    'text' => 'text-teal-600'],
                'orange'  => ['bg' => 'bg-orange-50',  'text' => 'text-orange-600'],
            ];
        @endphp
        @if ($activeTab === '')
        <div class="border-t border-gray-200 px-4 sm:px-6 py-6">
            <p class="text-sm text-gray-500 mb-4">Choose what you want to manage:</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
                @foreach ($feeTabs as $tab => [$label, $icon, $desc, $color])
                    @php $c = $feeColorMap[$color] ?? $feeColorMap['blue']; @endphp
                    <button wire:click="showTab('{{ $tab }}')"
                        class="text-left bg-white rounded-xl border border-gray-200 p-3.5 flex items-start gap-3 transition-all hover:border-gray-300 hover:shadow-md">
                        <div class="w-10 h-10 rounded-lg {{ $c['bg'] }} flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 {{ $c['text'] }}" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" /></svg>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-gray-900">{{ $label }}</h3>
                            <p class="text-xs text-gray-500 mt-0.5 leading-snug">{{ $desc }}</p>
                        </div>
                    </button>
                @endforeach
            </div>
        </div>
        @endif

        {{-- ══════════ PER-TAB FILTER BAR (gray-50, exam-style) ══════════ --}}
        @if ($activeTab === 'fee_submission')
            <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                        Filter by:
                    </div>
                    <select wire:model.live="submissionStandardId" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="">Select Class</option>
                        @foreach ($standards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
                    </select>
                    <select wire:model.live="submissionSectionId" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="">All Sections</option>
                        @foreach ($sections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                    </select>
                    <select wire:model.live="selectedStudentId" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[200px]">
                        <option value="">Select Student</option>
                        @foreach ($students as $stu)
                            <option value="{{ $stu->id }}">{{ $stu->full_name ?? ($stu->user->name ?? 'Unknown') }}@if ($stu->father_name) — {{ $stu->father_name }}@endif</option>
                        @endforeach
                    </select>
                    <input type="text" wire:model="submissionSearch" wire:keydown.enter="searchSubmissionStudents" placeholder="Search student / father name…"
                        class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-56 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    <button wire:click="searchSubmissionStudents"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Search
                    </button>
                </div>
            </div>
        @elseif ($activeTab === 'payments')
            <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                        Filter by:
                    </div>
                    <select wire:model.live="paymentStandardId" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="">All Classes</option>
                        @foreach ($standards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
                    </select>
                    <select wire:model.live="paymentSectionId" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="">All Sections</option>
                        @foreach ($sections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                    </select>
                    <select wire:model.live="paymentStudentId" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[160px]">
                        <option value="">All Students</option>
                        @foreach ($paymentStudents as $stu)<option value="{{ $stu->id }}">{{ $stu->full_name ?? ($stu->user->name ?? 'Unknown') }}</option>@endforeach
                    </select>
                    <input type="date" wire:model.live="paymentDateFrom" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700" title="From date" />
                    <span class="text-gray-300">→</span>
                    <input type="date" wire:model.live="paymentDateTo" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700" title="To date" />
                    <select wire:model.live="paymentModeFilter" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="">All Modes</option>
                        <option value="cash">Cash</option>
                        <option value="online">Online</option>
                        <option value="cheque">Cheque</option>
                        <option value="bank_transfer">Bank Transfer</option>
                    </select>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search student name…"
                        class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-44 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    @if ($paymentStandardId || $paymentSectionId || $paymentStudentId || $paymentModeFilter || $paymentDateFrom || $paymentDateTo || $search)
                        <button wire:click="clearPaymentFilters"
                            class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            Clear
                        </button>
                    @endif
                </div>
            </div>
        @elseif ($activeTab === 'penalties')
            <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                        Filter by:
                    </div>
                    <select wire:model.live="penaltyFilterStandard" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="">Select Class</option>
                        @foreach ($standards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
                    </select>
                    <span class="text-gray-300">→</span>
                    <select wire:model.live="penaltyFilterSection" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="">All Sections</option>
                        @foreach ($sections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                    </select>
                    <span class="text-gray-300">→</span>
                    <select wire:model.live="penaltyStudentId" @disabled(!$penaltyFilterStandard)
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 min-w-[200px]">
                        <option value="">Select Student</option>
                        @foreach ($penaltyStudents as $stu)
                            <option value="{{ $stu->id }}">{{ $stu->full_name ?? ($stu->user->name ?? 'Unknown') }}@if ($stu->father_name) — {{ $stu->father_name }}@endif</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @elseif ($activeTab === 'concession')
            <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                        Filter by:
                    </div>
                    <select wire:model.live="concFilterStandard" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="">All Classes</option>
                        @foreach ($standards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
                    </select>
                    <select wire:model.live="concFilterSection" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="">All Sections</option>
                        @foreach ($sections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                    </select>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search student / father…"
                        class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-56 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                </div>
            </div>
        @endif
    </div>

    <div class="p-4 sm:p-6 space-y-5">

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- TAB 1: FEE STRUCTURE  (embeds the standalone admin.fee-structure  --}}
    {{--          component so the two stay 1:1 in sync.)                  --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    @if ($activeTab === 'fee_structure')
        <livewire:admin.fee-structure :embedded="true" />
    @endif


    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- TAB 2: FEE SUBMISSION                                           --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    @if ($activeTab === 'fee_submission')
        @if (!$selectedStudentId || empty($selectedStudentInfo))
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="text-center py-16 px-4">
                    <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    </div>
                    <p class="text-sm font-semibold text-gray-800">Select a student</p>
                    <p class="text-xs text-gray-400 mt-1">Use the filters above (Class → Section → Student) or search by name to view fee details.</p>
                </div>
            </div>
        @endif

        @if ($selectedStudentId && !empty($selectedStudentInfo))
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                {{-- Student details --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-800 mb-3 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center font-bold">{{ strtoupper(mb_substr($selectedStudentInfo['name'], 0, 1)) }}</span>
                        Student Details
                    </h3>
                    <dl class="text-sm divide-y divide-gray-100">
                        <div class="flex justify-between py-1.5"><dt class="text-gray-500">Name</dt><dd class="font-medium text-gray-800">{{ $selectedStudentInfo['name'] }}</dd></div>
                        <div class="flex justify-between py-1.5"><dt class="text-gray-500">Father</dt><dd class="font-medium text-gray-800">{{ $selectedStudentInfo['father_name'] }}</dd></div>
                        <div class="flex justify-between py-1.5"><dt class="text-gray-500">Admission No.</dt><dd class="font-medium text-gray-800">{{ $selectedStudentInfo['admission_no'] }}</dd></div>
                        <div class="flex justify-between py-1.5"><dt class="text-gray-500">Roll No.</dt><dd class="font-medium text-gray-800">{{ $selectedStudentInfo['roll_no'] }}</dd></div>
                        <div class="flex justify-between py-1.5"><dt class="text-gray-500">Class / Section</dt><dd class="font-medium text-gray-800">{{ $selectedStudentInfo['class'] }} / {{ $selectedStudentInfo['section'] }}</dd></div>
                        <div class="flex justify-between py-1.5"><dt class="text-gray-500">Phone</dt><dd class="font-medium text-gray-800">{{ $selectedStudentInfo['phone'] }}</dd></div>
                    </dl>
                </div>

                {{-- Fee structure + concession --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-800 mb-3">Fee Structure</h3>
                    @if (count($classStructures))
                        <div class="space-y-1 text-sm">
                            @foreach ($classStructures as $cs)
                                <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                    <span class="text-gray-700">{{ $cs['fee_name'] }}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded text-[11px] {{ $cs['fee_type'] === 'academic' ? 'bg-blue-100 text-blue-600' : 'bg-green-100 text-green-600' }}">{{ ucfirst($cs['fee_type']) }}</span>
                                        <span class="font-semibold text-gray-800">₹{{ number_format($cs['amount'], 2) }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400">No fee structure set for this class.</p>
                    @endif

                    @if (count($studentConcessions))
                        <div class="mt-3 pt-3 border-t border-dashed border-gray-200">
                            <p class="text-[11px] font-semibold text-emerald-600 uppercase mb-1">Concessions</p>
                            @foreach ($studentConcessions as $c)
                                <div class="flex justify-between text-sm py-0.5">
                                    <span class="text-gray-600">{{ $c['reason'] ?: ucfirst($c['fee_type']) . ' concession' }}</span>
                                    <span class="font-semibold text-emerald-600">{{ $c['concession_type'] === 'percent' ? $c['value'] . '%' : '₹' . number_format($c['value'], 0) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-3 pt-3 border-t border-gray-200 flex justify-between items-center">
                        <span class="text-sm font-semibold text-gray-700">Net Payable</span>
                        <span class="text-lg font-bold text-blue-700">₹{{ number_format($netPayable, 2) }}</span>
                    </div>
                </div>

                {{-- Quick action --}}
                <div class="bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl shadow-sm p-5 flex flex-col justify-center text-white">
                    <p class="text-sm text-white/80">Record a new payment for</p>
                    <p class="text-lg font-bold">{{ $selectedStudentInfo['name'] }}</p>
                    <button wire:click="openSubmitPanel"
                        class="mt-4 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-white text-blue-700 text-sm font-semibold rounded-lg hover:bg-blue-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Update / Collect Fee
                    </button>
                </div>
            </div>
        @endif

        {{-- Payments (admin / accounts / app) --}}
        @if ($selectedStudentId && !empty($selectedStudentInfo))
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <h3 class="text-base font-semibold text-gray-800 mb-4">Payment History</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-3 py-2 text-left text-[11px] text-gray-500 uppercase">#</th>
                            <th class="px-3 py-2 text-left text-[11px] text-gray-500 uppercase">Receipt</th>
                            <th class="px-3 py-2 text-right text-[11px] text-gray-500 uppercase">Amount</th>
                            <th class="px-3 py-2 text-left text-[11px] text-gray-500 uppercase">Fee Type</th>
                            <th class="px-3 py-2 text-left text-[11px] text-gray-500 uppercase">Mode</th>
                            <th class="px-3 py-2 text-left text-[11px] text-gray-500 uppercase">Source</th>
                            <th class="px-3 py-2 text-left text-[11px] text-gray-500 uppercase">Date</th>
                            <th class="px-3 py-2 text-left text-[11px] text-gray-500 uppercase">By</th>
                            <th class="px-3 py-2 text-center text-[11px] text-gray-500 uppercase">Receipt</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($studentTransactions as $i => $txn)
                            @php
                                $by = strtolower((string) ($txn['submitted_by'] ?? ''));
                                $isApp = $txn['payment_mode'] === 'online' && (str_contains($by, 'self') || str_contains($by, 'app') || str_contains($by, 'student') || $by === '');
                            @endphp
                            <tr class="hover:bg-gray-50/50">
                                <td class="px-3 py-2 text-gray-500">{{ $i + 1 }}</td>
                                <td class="px-3 py-2 font-mono text-xs text-blue-700">{{ $txn['receipt_number'] }}</td>
                                <td class="px-3 py-2 text-right font-semibold">₹{{ number_format($txn['amount'], 2) }}</td>
                                <td class="px-3 py-2"><span class="px-2 py-0.5 rounded text-[11px] {{ $txn['fee_type'] === 'academic' ? 'bg-blue-100 text-blue-600' : 'bg-green-100 text-green-600' }}">{{ ucfirst($txn['fee_type']) }}</span></td>
                                <td class="px-3 py-2 capitalize text-gray-600">{{ str_replace('_', ' ', $txn['payment_mode']) }}</td>
                                <td class="px-3 py-2">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $isApp ? 'bg-purple-50 text-purple-600' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $isApp ? 'Mobile App' : 'Counter' }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-gray-600">{{ \Carbon\Carbon::parse($txn['payment_date'])->format('d M Y') }}</td>
                                <td class="px-3 py-2 text-xs text-gray-600">{{ $txn['submitted_by'] }}</td>
                                <td class="px-3 py-2 text-center">
                                    <a href="{{ route('admin.fee.receipt', ['organization' => auth()->user()->organization_id, 'id' => $txn['id']]) }}" target="_blank"
                                        class="text-xs px-2.5 py-1 border border-gray-300 rounded hover:bg-gray-100 text-gray-600 inline-flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2z"/></svg>
                                        Receipt
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-3 py-8 text-center text-gray-400 text-sm">No payments yet for this student.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- ── Update / Collect Fee slide-in panel ── --}}
        @if ($showSubmitPanel)
            <div class="fixed inset-0 z-50 overflow-hidden">
                <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeSubmitPanel"></div>
                <div class="absolute top-0 right-0 bottom-0 w-full max-w-md bg-white shadow-2xl flex flex-col">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Collect Fee</h2>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $selectedStudentInfo['name'] ?? '' }} · Net ₹{{ number_format($netPayable, 0) }}</p>
                        </div>
                        <button wire:click="closeSubmitPanel" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Amount (₹) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" min="1" wire:model="submitAmount" placeholder="Enter amount" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('submitAmount')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Fee Type</label>
                                <select wire:model="submitFeeType" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="academic">Academic</option>
                                    <option value="transport">Transport</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Payment Mode</label>
                                <select wire:model="submitPaymentMode" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="cash">Cash</option>
                                    <option value="online">Online</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Date <span class="text-red-500">*</span></label>
                            <input type="date" wire:model="submitDate" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('submitDate')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Collected By <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="submittedBy" placeholder="Staff name" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('submittedBy')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Remark</label>
                            <input type="text" wire:model="submitRemark" placeholder="Optional" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                        <button wire:click="closeSubmitPanel" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                        <button wire:click="submitFeePayment" wire:loading.attr="disabled" wire:target="submitFeePayment"
                            class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                            <span wire:loading.remove wire:target="submitFeePayment">Submit Payment</span>
                            <span wire:loading wire:target="submitFeePayment">Saving…</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- TAB 3: VIEW FEE                                                 --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    @if ($activeTab === 'view_fee')
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-6">
                @foreach (['by_student' => 'By Student', 'by_class' => 'By Class'] as $key => $label)
                    <button wire:click="setViewSubTab('{{ $key }}')"
                        class="relative whitespace-nowrap py-3 px-1 font-medium text-sm {{ $viewSubTab === $key ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                        {{ $label }}
                        @if ($viewSubTab === $key)
                            <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600"></div>
                        @endif
                    </button>
                @endforeach
            </nav>
        </div>

        @if ($viewSubTab === 'by_student')
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Class</label>
                        <select wire:model.live="viewStudentStandardId"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="">Select Class</option>
                            @foreach ($standards as $std)
                                <option value="{{ $std->id }}">{{ $std->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Section (optional)</label>
                        <select wire:model.live="viewStudentSectionId"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="">All Sections</option>
                            @foreach ($sections as $sec)
                                <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Student</label>
                        <select wire:model.live="viewStudentId"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="">Select Student</option>
                            @foreach ($students as $stu)
                                <option value="{{ $stu->id }}">{{ $stu->user->name ?? 'Unknown' }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @if ($viewStudentId)
                    <div class="mt-4 flex justify-end">
                        <button wire:click="loadStudentFeeView"
                            class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition-colors">
                            View Fee Details
                        </button>
                    </div>
                @endif
            </div>

            @if (!empty($studentFeeView))
                @php $sv = $studentFeeView; @endphp
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200">
                        <p class="text-xs text-blue-600 font-medium uppercase">Total Fee</p>
                        <p class="text-2xl font-bold text-blue-800 mt-1">₹{{ number_format($sv['totalFee'], 2) }}</p>
                    </div>
                    <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-lg p-4 border border-emerald-200">
                        <p class="text-xs text-emerald-600 font-medium uppercase">Paid</p>
                        <p class="text-2xl font-bold text-emerald-800 mt-1">₹{{ number_format($sv['totalPaid'], 2) }}</p>
                    </div>
                    <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg p-4 border border-red-200">
                        <p class="text-xs text-red-600 font-medium uppercase">Remaining</p>
                        <p class="text-2xl font-bold text-red-800 mt-1">₹{{ number_format($sv['remaining'], 2) }}</p>
                    </div>
                    <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-lg p-4 border border-indigo-200">
                        <p class="text-xs text-indigo-600 font-medium uppercase">Academic Fee</p>
                        <p class="text-2xl font-bold text-indigo-800 mt-1">₹{{ number_format($sv['academicTotal'], 2) }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                        <h4 class="text-sm font-semibold text-gray-800 mb-3">Fee Structure</h4>
                        <div class="space-y-2">
                            @foreach ($sv['structures'] as $fs)
                                @if ($fs->fee_type === 'transport' && !$sv['hasTransport']) @continue @endif
                                <div class="flex justify-between items-center py-2 border-b border-gray-100 text-sm">
                                    <div>
                                        <span class="font-medium text-gray-800">{{ $fs->fee_name }}</span>
                                        <span class="ml-2 px-2 py-0.5 rounded text-xs {{ $fs->fee_type === 'academic' ? 'bg-blue-100 text-blue-600' : 'bg-green-100 text-green-600' }}">
                                            {{ ucfirst($fs->fee_type) }}
                                        </span>
                                    </div>
                                    <span class="font-semibold">₹{{ number_format($fs->amount, 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                        @if ($sv['hasTransport'])
                            <div class="mt-3 p-3 bg-green-50 rounded-lg border border-green-200">
                                <div class="flex justify-between text-sm">
                                    <span class="text-green-700 font-medium">Transport Fee Total</span>
                                    <span class="font-bold text-green-800">₹{{ number_format($sv['transportTotal'], 2) }}</span>
                                </div>
                                <div class="flex justify-between text-sm mt-1">
                                    <span class="text-green-600">Transport Paid</span>
                                    <span class="font-medium text-green-700">₹{{ number_format($sv['transportPaid'], 2) }}</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                        <h4 class="text-sm font-semibold text-gray-800 mb-3">Payment History</h4>
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            @forelse ($sv['payments'] as $p)
                                <div class="flex justify-between items-center py-2 border-b border-gray-100 text-sm">
                                    <div>
                                        <p class="font-medium text-gray-800">₹{{ number_format($p->amount, 2) }}
                                            <span class="text-xs px-1.5 py-0.5 rounded {{ $p->fee_type === 'academic' ? 'bg-blue-100 text-blue-600' : 'bg-green-100 text-green-600' }}">
                                                {{ ucfirst($p->fee_type) }}
                                            </span>
                                        </p>
                                        <p class="text-xs text-gray-500">{{ $p->receipt_number }} &bull; {{ ucfirst(str_replace('_', ' ', $p->payment_mode)) }}</p>
                                    </div>
                                    <span class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($p->payment_date)->format('d M Y') }}</span>
                                </div>
                            @empty
                                <p class="text-center text-gray-400 text-sm py-4">No payments recorded.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif
        @endif

        @if ($viewSubTab === 'by_class')
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Class <span class="text-red-500">*</span></label>
                        <select wire:model.live="viewClassStandardId"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="">Select Class</option>
                            @foreach ($standards as $std)
                                <option value="{{ $std->id }}">{{ $std->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Section (optional)</label>
                        <select wire:model.live="viewClassSectionId"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="">All Sections</option>
                            @foreach ($sections as $sec)
                                <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button wire:click="loadClassFeeView"
                            class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition-colors">
                            Load Students
                        </button>
                    </div>
                </div>
            </div>

            @if (!empty($classFeeList))
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs text-gray-500 uppercase">#</th>
                                    <th class="px-4 py-3 text-left text-xs text-gray-500 uppercase">Name</th>
                                    <th class="px-4 py-3 text-left text-xs text-gray-500 uppercase">Adm No.</th>
                                    <th class="px-4 py-3 text-left text-xs text-gray-500 uppercase">Class</th>
                                    <th class="px-4 py-3 text-left text-xs text-gray-500 uppercase">Section</th>
                                    <th class="px-4 py-3 text-right text-xs text-gray-500 uppercase">Academic</th>
                                    <th class="px-4 py-3 text-right text-xs text-gray-500 uppercase">Transport</th>
                                    <th class="px-4 py-3 text-right text-xs text-gray-500 uppercase">Total Fee</th>
                                    <th class="px-4 py-3 text-right text-xs text-gray-500 uppercase">Collected</th>
                                    <th class="px-4 py-3 text-center text-xs text-gray-500 uppercase">View</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($classFeeList as $i => $row)
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-4 py-3">{{ $i + 1 }}</td>
                                        <td class="px-4 py-3 font-medium text-gray-800">{{ $row['name'] }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $row['admission_no'] ?? '-' }}</td>
                                        <td class="px-4 py-3">{{ $row['class'] }}</td>
                                        <td class="px-4 py-3">{{ $row['section'] }}</td>
                                        <td class="px-4 py-3 text-right">₹{{ number_format($row['academicFee'], 2) }}</td>
                                        <td class="px-4 py-3 text-right">₹{{ number_format($row['transportFee'], 2) }}</td>
                                        <td class="px-4 py-3 text-right font-semibold">₹{{ number_format($row['totalFee'], 2) }}</td>
                                        <td class="px-4 py-3 text-right text-emerald-700 font-semibold">₹{{ number_format($row['collected'], 2) }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <button wire:click="$set('viewSubTab', 'by_student'); $set('viewStudentId', '{{ $row['id'] }}')"
                                                class="text-xs px-3 py-1 border border-blue-300 text-blue-600 rounded hover:bg-blue-50 transition-all">
                                                View
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endif
    @endif

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- TAB 4: ANALYTICS                                                --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    @if ($activeTab === 'analytics')
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Filter by Class</label>
                    <select wire:model.live="analyticsStandardId"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <option value="">All Classes</option>
                        @foreach ($standards as $std)
                            <option value="{{ $std->id }}">{{ $std->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Filter by Section</label>
                    <select wire:model.live="analyticsSectionId"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <option value="">All Sections</option>
                        @foreach ($sections as $sec)
                            <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button wire:click="loadAnalytics"
                        class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition-colors">
                        Refresh Analytics
                    </button>
                </div>
            </div>
        </div>

        @if (!empty($analyticsData))
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-xl p-5 border border-indigo-200">
                    <p class="text-xs text-indigo-600 font-medium uppercase">Total Fee</p>
                    <p class="text-xl font-bold text-indigo-800 mt-1">₹{{ number_format($analyticsData['totalFee'], 0) }}</p>
                </div>
                <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-xl p-5 border border-emerald-200">
                    <p class="text-xs text-emerald-600 font-medium uppercase">Collected</p>
                    <p class="text-xl font-bold text-emerald-800 mt-1">₹{{ number_format($analyticsData['collected'], 0) }}</p>
                </div>
                <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-5 border border-red-200">
                    <p class="text-xs text-red-600 font-medium uppercase">Remaining</p>
                    <p class="text-xl font-bold text-red-800 mt-1">₹{{ number_format($analyticsData['remaining'], 0) }}</p>
                </div>
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-5 border border-green-200">
                    <p class="text-xs text-green-600 font-medium uppercase">Transport Total</p>
                    <p class="text-xl font-bold text-green-800 mt-1">₹{{ number_format($analyticsData['transportTotal'], 0) }}</p>
                </div>
            </div>
        @endif

        @if (!empty($analyticsStudentList))
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h3 class="text-sm font-semibold text-gray-800 mb-3">Student Fee Overview</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">#</th>
                                <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Adm No.</th>
                                <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Class</th>
                                <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Section</th>
                                <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Fee Come / Total</th>
                                <th class="px-4 py-2 text-center text-xs text-gray-500 uppercase">View</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($analyticsStudentList as $i => $row)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-4 py-3">{{ $i + 1 }}</td>
                                    <td class="px-4 py-3 font-medium">{{ $row['name'] }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $row['admission_no'] ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ $row['class'] }}</td>
                                    <td class="px-4 py-3">{{ $row['section'] }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="text-emerald-700 font-semibold">₹{{ number_format($row['collected'], 0) }}</span>
                                        <span class="text-gray-400 mx-1">/</span>
                                        <span class="text-gray-700">₹{{ number_format($row['totalFee'], 0) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button wire:click="showTab('view_fee'); $set('viewStudentId', '{{ $row['id'] }}')"
                                            class="text-xs px-3 py-1 border border-blue-300 text-blue-600 rounded hover:bg-blue-50">View</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- TAB 5: PAYMENTS                                                 --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    @if ($activeTab === 'payments')
        {{-- Summary (plain text, not chips) --}}
        @if (!empty($paymentPeriodStats))
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-5 py-4 mb-4">
                <h3 class="text-sm font-semibold text-gray-800 mb-2">Collection Summary</h3>
                <p class="text-sm text-gray-600 leading-relaxed">
                    <span class="text-gray-500">Today:</span> <span class="font-semibold text-gray-800">₹{{ number_format($paymentPeriodStats['today'] ?? 0, 0) }}</span>
                    <span class="text-gray-300 mx-2">|</span>
                    <span class="text-gray-500">Yesterday:</span> <span class="font-semibold text-gray-800">₹{{ number_format($paymentPeriodStats['yesterday'] ?? 0, 0) }}</span>
                    <span class="text-gray-300 mx-2">|</span>
                    <span class="text-gray-500">This Week:</span> <span class="font-semibold text-gray-800">₹{{ number_format($paymentPeriodStats['this_week'] ?? 0, 0) }}</span>
                    <span class="text-gray-300 mx-2">|</span>
                    <span class="text-gray-500">This Month:</span> <span class="font-semibold text-gray-800">₹{{ number_format($paymentPeriodStats['this_month'] ?? 0, 0) }}</span>
                    <span class="text-gray-300 mx-2">|</span>
                    <span class="text-gray-500">Last Month:</span> <span class="font-semibold text-gray-800">₹{{ number_format($paymentPeriodStats['last_month'] ?? 0, 0) }}</span>
                </p>
                <p class="text-sm mt-2 pt-2 border-t border-gray-100">
                    <span class="text-gray-500">Total for current filter:</span>
                    <span class="font-bold text-blue-700">₹{{ number_format($paymentFilteredTotal ?? 0, 2) }}</span>
                </p>
            </div>
        @endif

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Receipt</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class/Sec</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Fee Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Mode</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Submitted By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($payments as $p)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $loop->iteration }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-blue-700">{{ $p->receipt_number }}</td>
                                <td class="px-4 py-3">
                                    <p class="text-sm font-semibold text-gray-900">{{ $p->studentDetail->user->name ?? '-' }}</p>
                                    <p class="text-xs text-gray-400">{{ $p->studentDetail->admission_no ?? '-' }}</p>
                                </td>
                                <td class="px-4 py-3 text-gray-600 text-xs">
                                    {{ $p->standard->name ?? '-' }} {{ $p->section ? '/ ' . $p->section->name : '' }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-gray-800">₹{{ number_format($p->amount, 2) }}</td>
                                <td class="px-4 py-3">
                                    <span class="text-xs font-medium px-2 py-0.5 rounded {{ $p->fee_type === 'academic' ? 'bg-blue-100 text-blue-600' : 'bg-green-100 text-green-600' }}">
                                        {{ ucfirst($p->fee_type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 capitalize text-xs text-gray-600">{{ str_replace('_', ' ', $p->payment_mode) }}</td>
                                <td class="px-4 py-3 text-xs text-gray-600">{{ $p->payment_date->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-xs text-gray-600">{{ $p->submitted_by }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-16 text-center">
                                    <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" /></svg>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-800">No payments found</p>
                                    <p class="text-xs text-gray-400 mt-1">Adjust the filters above to see results.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($payments->hasPages())
                <div class="px-4 py-3 border-t border-gray-100">{{ $payments->links() }}</div>
            @endif
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- TAB 6: PENALTIES (per-student)                                  --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    @if ($activeTab === 'penalties')
        {{-- Compact penalty-rate settings --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-4">
            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-[11px] font-semibold text-gray-500 mb-1">Penalty per Day (₹)</label>
                    <input type="number" wire:model="penaltyPerDay" step="0.01" min="0" placeholder="0"
                        class="w-32 border border-gray-300 rounded-md px-3 py-2 text-sm">
                    @error('penaltyPerDay') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-gray-500 mb-1">Cycle</label>
                    <select wire:model="cycleType" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-gray-500 mb-1">Due Day of Month</label>
                    <input type="number" wire:model="dueDayOfMonth" min="1" max="31" placeholder="10"
                        class="w-28 border border-gray-300 rounded-md px-3 py-2 text-sm">
                    @error('dueDayOfMonth') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <button wire:click="saveSettings"
                    class="px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-md text-sm font-semibold">
                    Save
                </button>
                <p class="text-xs text-gray-400 flex-1 min-w-[200px]">Penalty = overdue days × per-day rate when no payment is made in the current cycle.</p>
            </div>
        </div>

        @if ($penaltyStudentId && !empty($penaltyStudentInfo))
            {{-- Penalty summary line --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-5 py-4 mb-4">
                <h3 class="text-sm font-semibold text-gray-800 mb-1">{{ $penaltyStudentInfo['name'] }}
                    <span class="text-gray-400 font-normal">· {{ $penaltyStudentInfo['class'] }} / {{ $penaltyStudentInfo['section'] }} · Adm {{ $penaltyStudentInfo['admission_no'] }}</span>
                </h3>
                <p class="text-sm text-gray-600">
                    <span class="text-gray-500">Days Overdue:</span> <span class="font-semibold text-gray-800">{{ $penaltyDaysOverdue }}</span>
                    <span class="text-gray-300 mx-2">|</span>
                    <span class="text-gray-500">Penalty:</span> <span class="font-semibold text-red-600">₹{{ number_format($penaltyGross, 2) }}</span>
                    <span class="text-gray-300 mx-2">|</span>
                    <span class="text-gray-500">Waived:</span> <span class="font-semibold text-emerald-600">₹{{ number_format($penaltyWaivedTotal, 2) }}</span>
                    <span class="text-gray-300 mx-2">|</span>
                    <span class="text-gray-500">Net Penalty Due:</span> <span class="font-bold text-gray-900">₹{{ number_format($penaltyNet, 2) }}</span>
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                {{-- Fee structure --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h4 class="text-sm font-semibold text-gray-800 mb-3">Fee Structure</h4>
                    @if (count($penaltyStructures))
                        <div class="space-y-1 text-sm">
                            @foreach ($penaltyStructures as $cs)
                                <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                    <span class="text-gray-700">{{ $cs['fee_name'] }}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded text-[11px] {{ $cs['fee_type'] === 'academic' ? 'bg-blue-100 text-blue-600' : 'bg-green-100 text-green-600' }}">{{ ucfirst($cs['fee_type']) }}</span>
                                        <span class="font-semibold text-gray-800">₹{{ number_format($cs['amount'], 2) }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400">No fee structure set for this class.</p>
                    @endif
                </div>

                {{-- Waive penalty --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h4 class="text-sm font-semibold text-gray-800 mb-3">Waive / Concession on Penalty</h4>
                    <div class="flex items-end gap-2 mb-4">
                        <div class="flex-1">
                            <label class="block text-[11px] font-semibold text-gray-500 mb-1">Waiver Amount (₹) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" min="0.01" wire:model="waiveValue" placeholder="e.g. {{ number_format($penaltyGross, 0) }}"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            @error('waiveValue') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex-1">
                            <label class="block text-[11px] font-semibold text-gray-500 mb-1">Reason</label>
                            <input type="text" wire:model="waiveReason" placeholder="Optional"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        </div>
                        <button wire:click="waivePenalty"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md text-sm font-semibold whitespace-nowrap">
                            Waive
                        </button>
                    </div>

                    @if (count($penaltyWaivers))
                        <p class="text-[11px] font-semibold text-gray-500 uppercase mb-1">Applied Waivers</p>
                        <div class="space-y-1">
                            @foreach ($penaltyWaivers as $w)
                                <div class="flex justify-between items-center text-sm py-1 border-b border-gray-100">
                                    <span class="text-gray-600">{{ $w['reason'] ?: 'Penalty waiver' }}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-emerald-600">₹{{ number_format($w['value'], 2) }}</span>
                                        <button wire:click="removeWaiver({{ $w['id'] }})" class="text-red-500 hover:text-red-700" title="Remove">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400">No penalty waivers applied yet.</p>
                    @endif
                </div>
            </div>

            {{-- Payments --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mt-4">
                <h4 class="text-sm font-semibold text-gray-800 mb-3">Payment History</h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-3 py-2 text-left text-[11px] text-gray-500 uppercase">Receipt</th>
                                <th class="px-3 py-2 text-right text-[11px] text-gray-500 uppercase">Amount</th>
                                <th class="px-3 py-2 text-left text-[11px] text-gray-500 uppercase">Fee Type</th>
                                <th class="px-3 py-2 text-left text-[11px] text-gray-500 uppercase">Mode</th>
                                <th class="px-3 py-2 text-left text-[11px] text-gray-500 uppercase">Date</th>
                                <th class="px-3 py-2 text-left text-[11px] text-gray-500 uppercase">By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($penaltyPayments as $txn)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-3 py-2 font-mono text-xs text-blue-700">{{ $txn['receipt_number'] }}</td>
                                    <td class="px-3 py-2 text-right font-semibold">₹{{ number_format($txn['amount'], 2) }}</td>
                                    <td class="px-3 py-2"><span class="px-2 py-0.5 rounded text-[11px] {{ $txn['fee_type'] === 'academic' ? 'bg-blue-100 text-blue-600' : 'bg-green-100 text-green-600' }}">{{ ucfirst($txn['fee_type']) }}</span></td>
                                    <td class="px-3 py-2 capitalize text-gray-600">{{ str_replace('_', ' ', $txn['payment_mode']) }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ \Carbon\Carbon::parse($txn['payment_date'])->format('d M Y') }}</td>
                                    <td class="px-3 py-2 text-xs text-gray-600">{{ $txn['submitted_by'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-3 py-8 text-center text-gray-400 text-sm">No payments yet for this student.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="text-center py-16 px-4">
                    <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <p class="text-sm font-semibold text-gray-800">Select a student</p>
                    <p class="text-xs text-gray-400 mt-1">Use the filters above (Class → Section → Student) to view their fee structure, penalties and payments.</p>
                </div>
            </div>
        @endif
    @endif

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- TAB 6B: FEE CYCLE (installments)                                --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    @if ($activeTab === 'cycle')
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-semibold text-gray-800">Fee Cycle — Installments</h3>
                <p class="text-sm text-gray-500">Define each installment’s dates and the % of the fee to collect. The amount is computed automatically.</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Installment</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Fee Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Start Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">End Date</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Fee %</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Year</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($cycles as $cy)
                        <tr wire:key="cycle-{{ $cy->id }}" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-semibold text-gray-800">#{{ $cy->payment_serial }}</td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-[11px] {{ $cy->fee_type === 'academic' ? 'bg-blue-100 text-blue-600' : 'bg-green-100 text-green-600' }} capitalize">{{ $cy->fee_type }}</span></td>
                            <td class="px-4 py-3 text-gray-600">{{ optional($cy->start_date)->format('d M Y') ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ optional($cy->end_date)->format('d M Y') ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">{{ rtrim(rtrim(number_format($cy->fee_percent, 2), '0'), '.') }}%</td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-800">₹{{ number_format($cy->amount, 2) }}</td>
                            <td class="px-4 py-3 text-center text-gray-500">{{ $cy->academic_year }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button wire:click="openCycleModal({{ $cy->id }})" class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-amber-50 hover:text-amber-600" title="Edit">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </button>
                                    <button wire:click="deleteCycle({{ $cy->id }})" class="p-1.5 rounded-md border border-red-200 text-red-500 hover:bg-red-50" title="Delete">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-16 text-center">
                                <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                </div>
                                <p class="text-sm font-semibold text-gray-800">No installments yet</p>
                                <p class="text-xs text-gray-400 mt-1">Click “Add Installment” to define the fee cycle.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Add / Edit installment slide-in --}}
        @if ($cycleModalOpen)
            <div class="fixed inset-0 z-50 overflow-hidden">
                <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeCycleModal"></div>
                <div class="absolute top-0 right-0 bottom-0 w-full max-w-md bg-white shadow-2xl flex flex-col">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">{{ $editCycleId ? 'Edit Installment' : 'Add Installment' }}</h2>
                            <p class="text-xs text-gray-500 mt-0.5">Set dates and % of fee — amount auto-computes</p>
                        </div>
                        <button wire:click="closeCycleModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Installment No. <span class="text-red-500">*</span></label>
                                <select wire:model="cycleSerial" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                    @for ($i = 1; $i <= 8; $i++)<option value="{{ $i }}">Installment {{ $i }}</option>@endfor
                                </select>
                                @error('cycleSerial')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Fee Type</label>
                                <select wire:model="cycleFeeType" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="academic">Academic</option>
                                    <option value="transport">Transport</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Start Date <span class="text-red-500">*</span></label>
                                <input type="date" wire:model="cycleStartDate" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                @error('cycleStartDate')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">End Date <span class="text-red-500">*</span></label>
                                <input type="date" wire:model="cycleEndDate" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                @error('cycleEndDate')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Due Date</label>
                                <input type="date" wire:model="cycleDueDate" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                <p class="mt-1 text-[11px] text-gray-400">Defaults to end date if blank.</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Penalty / Day (₹)</label>
                                <input type="number" step="0.01" min="0" wire:model="cyclePenaltyPerDay" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        <div class="border-t border-gray-100 pt-4">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Total / Annual Fee (₹) <span class="text-red-500">*</span></label>
                                    <input type="number" step="0.01" min="0" wire:model.live.debounce.400ms="cycleBaseAmount" placeholder="e.g. 50000" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                    @error('cycleBaseAmount')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Fee % to Collect <span class="text-red-500">*</span></label>
                                    <input type="number" step="0.01" min="0" max="100" wire:model.live.debounce.400ms="cycleFeePercent" placeholder="e.g. 25" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                    @error('cycleFeePercent')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div class="mt-3 flex justify-between items-center bg-blue-50 border border-blue-100 rounded-lg px-4 py-3">
                                <span class="text-sm font-medium text-blue-700">Installment Amount</span>
                                <span class="text-lg font-bold text-blue-800">₹{{ number_format((float) $cycleAmount, 2) }}</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Academic Year</label>
                            <input type="text" wire:model="cycleYear" placeholder="2026-27" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                        <button wire:click="closeCycleModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                        <button wire:click="saveCycle" wire:loading.attr="disabled"
                            class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                            <span wire:loading.remove wire:target="saveCycle">{{ $editCycleId ? 'Update Installment' : 'Add Installment' }}</span>
                            <span wire:loading wire:target="saveCycle">Saving…</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Delete confirm --}}
        @if ($pendingDeleteCycleId !== null)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDeleteCycle"></div>
                <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-base font-semibold text-gray-900 mb-1">Delete installment?</h3>
                            <p class="text-sm text-gray-500">This removes the installment from the fee cycle. This cannot be undone.</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2 mt-5">
                        <button wire:click="cancelDeleteCycle" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                        <button wire:click="doDeleteCycle" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">Delete</button>
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: CONCESSION (per-student fee discount)                      --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    @if ($activeTab === 'concession')
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class / Section</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Applies To</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Concession</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Reason</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Year</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($concessions as $c)
                        <tr wire:key="conc-{{ $c->id }}" class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800">{{ $c->studentDetail->full_name ?? ($c->studentDetail->user->name ?? '—') }}</p>
                                <p class="text-xs text-gray-400">{{ $c->studentDetail->father_name ?? '' }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $c->standard->name ?? '—' }}{{ $c->section ? ' / ' . $c->section->name : '' }}</td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-[11px] bg-gray-100 text-gray-600 capitalize">{{ $c->fee_type }}</span></td>
                            <td class="px-4 py-3 text-right font-semibold text-emerald-600">{{ $c->concession_type === 'percent' ? $c->value . '%' : '₹' . number_format($c->value, 2) }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $c->reason ?: '—' }}</td>
                            <td class="px-4 py-3 text-center text-gray-500">{{ $c->academic_year }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button wire:click="openConcessionModal({{ $c->id }})" class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-amber-50 hover:text-amber-600" title="Edit">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </button>
                                    <button wire:click="deleteConcession({{ $c->id }})" class="p-1.5 rounded-md border border-red-200 text-red-500 hover:bg-red-50" title="Delete">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center">
                                <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                                </div>
                                <p class="text-sm font-semibold text-gray-800">No concessions yet</p>
                                <p class="text-xs text-gray-400 mt-1">Click “Add Concession” to grant a student a fee discount.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if ($concessions->hasPages())
                <div class="px-4 py-3 border-t border-gray-100">{{ $concessions->links() }}</div>
            @endif
        </div>

        {{-- Add / Edit concession slide-in --}}
        @if ($concModalOpen)
            <div class="fixed inset-0 z-50 overflow-hidden">
                <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeConcessionModal"></div>
                <div class="absolute top-0 right-0 bottom-0 w-full max-w-md bg-white shadow-2xl flex flex-col">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">{{ $editConcessionId ? 'Edit Concession' : 'Add Concession' }}</h2>
                            <p class="text-xs text-gray-500 mt-0.5">Grant a student a fee discount</p>
                        </div>
                        <button wire:click="closeConcessionModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Class <span class="text-red-500">*</span></label>
                                <select wire:model.live="concFilterStandard" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select…</option>
                                    @foreach ($standards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Section</label>
                                <select wire:model.live="concFilterSection" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">All</option>
                                    @foreach ($sections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Student <span class="text-red-500">*</span></label>
                            <select wire:model="concStudentId" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select student…</option>
                                @foreach ($concStudents as $stu)
                                    <option value="{{ $stu->id }}">{{ $stu->full_name ?? ($stu->user->name ?? 'Unknown') }}@if ($stu->father_name) — {{ $stu->father_name }}@endif</option>
                                @endforeach
                            </select>
                            @error('concStudentId')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Type</label>
                                <select wire:model.live="concType" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="amount">Flat Amount (₹)</option>
                                    <option value="percent">Percentage (%)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Value <span class="text-red-500">*</span></label>
                                <input type="number" step="0.01" min="0" wire:model="concValue" placeholder="{{ $concType === 'percent' ? 'e.g. 25' : 'e.g. 2000' }}" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                @error('concValue')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Applies To</label>
                                <select wire:model="concFeeType" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="all">All Fees</option>
                                    <option value="academic">Academic</option>
                                    <option value="transport">Transport</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Academic Year</label>
                                <input type="text" wire:model="concYear" placeholder="2026-27" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Reason</label>
                            <input type="text" wire:model="concReason" placeholder="e.g. Staff ward, Sibling, Merit" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                        <button wire:click="closeConcessionModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                        <button wire:click="saveConcession" class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md">{{ $editConcessionId ? 'Update Concession' : 'Add Concession' }}</button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Delete confirm --}}
        @if ($pendingDeleteConcessionId !== null)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDeleteConcession"></div>
                <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-base font-semibold text-gray-900 mb-1">Delete concession?</h3>
                            <p class="text-sm text-gray-500">This removes the student's fee discount. This cannot be undone.</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2 mt-5">
                        <button wire:click="cancelDeleteConcession" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                        <button wire:click="doDeleteConcession" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">Delete</button>
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- TAB 7: ACCOUNT USERS                                            --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    @if ($activeTab === 'account_users')
        @livewire('admin.account-users')
    @endif

    </div>
</div>
