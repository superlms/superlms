<div class="min-h-screen bg-gray-50">
    {{-- Local cloak rule so Alpine-collapsed panels stay hidden before init
         (independent of any global x-cloak style). --}}
    <style>[x-cloak]{display:none !important;}</style>

    {{-- Tab catalog (label, icon, description, color) — defined up top so the
         sticky header can show the active tab's own name. --}}
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
            'account_users'  => ['Accounts user',   'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'Cashier / account logins', 'orange'],
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
        $headerTitle = $activeTab === '' ? 'Fees' : ($feeTabs[$activeTab][0] ?? 'Fees');
        $headerDesc  = $activeTab === '' ? 'Manage fee structures, submissions, cycles and analytics' : ($feeTabs[$activeTab][2] ?? '');
    @endphp

    {{-- ══════════════════════════════════════════════════
         HEADER (sticky: dynamic per-tab title + stats + Add + filter bar)
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3">
                    @if ($activeTab !== '')
                        <button wire:click="backToMenu" title="Back to fee sections"
                            class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                        </button>
                    @endif
                    <div>
                        <h1 class="text-lg sm:text-xl font-bold text-gray-900">{{ $headerTitle }}</h1>
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
                            <span>Collected: <strong class="text-emerald-600">₹{{ number_format($headerStats['total_collected'] ?? 0, 0) }}</strong></span>
                        </div>
                    @elseif ($activeTab === 'penalties' && $penaltySubTab === 'by_student' && $penaltyViewStudentId && !empty($penaltyStudentView))
                        @php
                            $penHdrCycles  = collect($penaltyStudentView['cycles'] ?? []);
                            $penHdrAccrued = $penHdrCycles->sum('penalty_total');
                            $penHdrNet     = $penHdrCycles->sum('penalty_net');
                        @endphp
                        <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-1 divide-x divide-gray-200">
                            <span class="pr-4">Accrued: <strong class="text-red-600">₹{{ number_format($penHdrAccrued, 0) }}</strong></span>
                            <span class="pl-4">Still Due: <strong class="text-gray-800">₹{{ number_format($penHdrNet, 0) }}</strong></span>
                        </div>
                    @elseif ($activeTab === 'account_users')
                        <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-1 divide-x divide-gray-200">
                            <span class="pr-4">Total: <strong class="text-gray-800">{{ $acctTotal ?? 0 }}</strong></span>
                            <span class="px-4">Active: <strong class="text-emerald-600">{{ $acctActive ?? 0 }}</strong></span>
                            <span class="pl-4">Inactive: <strong class="text-rose-500">{{ $acctInactive ?? 0 }}</strong></span>
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
                    @elseif ($activeTab === 'fee_structure')
                        {{-- The embedded fee-structure component owns the panel; the button lives here. --}}
                        <button wire:click="$dispatchTo('admin.fee-structure', 'fee-structure-add')"
                            class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                            <span class="hidden sm:inline">Add Fee Structure</span>
                            <span class="sm:hidden">Add</span>
                        </button>
                    @elseif ($activeTab === 'cycle')
                        @include('livewire.partials.fee-cycle-actions')
                    @elseif ($activeTab === 'concession')
                        <button wire:click="openConcessionModal()"
                            class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                            <span class="hidden sm:inline">Add Concession</span>
                            <span class="sm:hidden">Add</span>
                        </button>
                    @elseif ($activeTab === 'account_users')
                        <button wire:click="acctAdd"
                            class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                            <span class="hidden sm:inline">Add User</span>
                            <span class="sm:hidden">New</span>
                        </button>
                    @elseif ($activeTab === 'payments')
                        <button wire:click="resetPaymentFilters"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-300 text-xs font-medium text-gray-600 bg-white hover:bg-gray-50 transition">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                            Reset Filters
                        </button>
                    @elseif ($activeTab === 'penalties')
                        <button wire:click="openPenaltyWaiver" @disabled(!$penaltyViewStudentId)
                            class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-amber-600 hover:bg-amber-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
                            <span>Waiver</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Tabs as cards (Lists-style) — $feeTabs/$feeColorMap defined at top --}}
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
        @if ($activeTab === 'account_users')
            <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                        Filter by:
                    </div>
                    <input wire:model.live.debounce.300ms="acctSearch" type="text" placeholder="Search name, email, mobile..."
                        class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-56 focus:ring-2 focus:ring-purple-500 focus:border-purple-500" />
                    <select wire:model.live="acctStatus"
                        class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                        <option value="">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                    @if ($acctSearch || $acctStatus !== '')
                        <button wire:click="acctClearFilters" class="text-xs text-purple-600 hover:text-purple-800 font-medium">Clear</button>
                    @endif
                </div>
            </div>
        @endif

        @if ($activeTab === 'cycle')
            @include('livewire.partials.fee-cycle-header')
        @endif

        @if ($activeTab === 'concession')
            @include('livewire.partials.fee-concession-header')
        @endif

        @if ($activeTab === 'fee_submission')
            @include('livewire.partials.fee-submission-header')
        @elseif ($activeTab === 'view_fee')
            @include('livewire.partials.view-fee-header')
        @elseif ($activeTab === 'analytics')
            <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                        Filter by:
                    </div>
                    <select wire:model.live="analyticsStandardId" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="">All Classes</option>
                        @foreach ($standards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
                    </select>
                    <select wire:model.live="analyticsSectionId" @disabled(!$analyticsStandardId) class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                        <option value="">All Sections</option>
                        @foreach ($sections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                    </select>
                    <button wire:click="loadAnalytics"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-white border border-gray-200 rounded-md text-gray-700 hover:bg-gray-50">
                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        Refresh
                    </button>
                    <span class="ml-auto hidden sm:inline-flex items-center gap-1.5 text-xs text-gray-500">
                        Collected: <strong class="text-emerald-600">₹{{ number_format($analyticsData['collected'] ?? 0, 0) }}</strong>
                    </span>
                </div>
            </div>
        @elseif ($activeTab === 'penalties')
            @include('livewire.partials.penalties-header')
        @endif
    </div>

    <div class="p-4 sm:p-6 space-y-5">

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- TAB 1: FEE STRUCTURE  (embeds the standalone admin.fee-structure  --}}
    {{--          component so the two stay 1:1 in sync.)                  --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    @if ($activeTab === 'fee_structure')
        {{-- Break out of the parent content padding so the embedded component's
             header (sub-tabs + filter) sits flush & full-width under the Fee
             header, exactly like a standalone functionality page. --}}
        <div class="-mx-4 sm:-mx-6 -mt-4 sm:-mt-6">
            <livewire:admin.fee-structure :embedded="true" />
        </div>
    @endif


    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- TAB 2: FEE SUBMISSION                                           --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    @if ($activeTab === 'fee_submission')
        @include('livewire.partials.fee-submission-panel', [
            'feePrefix' => 'admin',
            'feeOrg'    => auth()->user()->organization_id,
        ])
    @endif

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- TAB 3: VIEW FEE                                                 --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    @if ($activeTab === 'view_fee')
        <div class="space-y-4">
            @include('livewire.partials.view-fee-panel', [
                'feePrefix' => 'admin',
                'feeOrg'    => auth()->user()->organization_id,
            ])
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- TAB 4: ANALYTICS                                                --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    @if ($activeTab === 'analytics')
        @php
            $aCollectPct = ($analyticsData['totalFee'] ?? 0) > 0
                ? min(100, round((($analyticsData['collected'] ?? 0) / $analyticsData['totalFee']) * 100, 1)) : 0;
            $modeMax = !empty($analyticsModeBreakdown) ? max(array_map('floatval', $analyticsModeBreakdown)) : 0;
            $modeMeta = [
                'cash'          => ['label' => 'Cash',          'color' => 'bg-emerald-500'],
                'online'        => ['label' => 'Online',        'color' => 'bg-blue-500'],
                'cheque'        => ['label' => 'Cheque',        'color' => 'bg-amber-500'],
                'bank_transfer' => ['label' => 'Bank Transfer', 'color' => 'bg-violet-500'],
            ];
        @endphp

        {{-- ── Period stat cards (daily / week / month) ── --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-gray-400 uppercase tracking-wide">Collected Today</p>
                    <span class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </span>
                </div>
                <p class="text-2xl font-bold text-gray-900 mt-2">₹{{ number_format($analyticsPeriodStats['today_amt'] ?? 0, 0) }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ $analyticsPeriodStats['today_cnt'] ?? 0 }} payment(s)</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-gray-400 uppercase tracking-wide">This Week</p>
                    <span class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </span>
                </div>
                <p class="text-2xl font-bold text-gray-900 mt-2">₹{{ number_format($analyticsPeriodStats['week_amt'] ?? 0, 0) }}</p>
                <p class="text-xs text-gray-400 mt-1">Mon–Sun</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-gray-400 uppercase tracking-wide">This Month</p>
                    <span class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13h2l1 5h12l1-5h2M5 13L4 4h16l-1 9" /></svg>
                    </span>
                </div>
                <p class="text-2xl font-bold text-gray-900 mt-2">₹{{ number_format($analyticsPeriodStats['month_amt'] ?? 0, 0) }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ $analyticsPeriodStats['month_cnt'] ?? 0 }} payment(s)</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-gray-400 uppercase tracking-wide">Avg / Payment</p>
                    <span class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                    </span>
                </div>
                <p class="text-2xl font-bold text-gray-900 mt-2">₹{{ number_format($analyticsPeriodStats['avg_txn'] ?? 0, 0) }}</p>
                <p class="text-xs text-gray-400 mt-1">Across all filtered</p>
            </div>
        </div>

        {{-- ── Collection totals + progress ── --}}
        @if (!empty($analyticsData))
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-5">
                <div class="lg:col-span-1 grid grid-cols-2 lg:grid-cols-1 gap-4">
                    <div class="bg-white rounded-xl border border-gray-200 p-4">
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Total Fee</p>
                        <p class="text-lg font-bold text-gray-900 mt-1">₹{{ number_format($analyticsData['totalFee'], 0) }}</p>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-200 p-4">
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Transport</p>
                        <p class="text-lg font-bold text-cyan-600 mt-1">₹{{ number_format($analyticsData['transportTotal'], 0) }}</p>
                    </div>
                </div>
                <div class="lg:col-span-3 bg-white rounded-xl border border-gray-200 p-5">
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-4">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Collected</p>
                            <p class="text-xl font-bold text-emerald-600 mt-1">₹{{ number_format($analyticsData['collected'], 0) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Remaining</p>
                            <p class="text-xl font-bold text-red-500 mt-1">₹{{ number_format($analyticsData['remaining'], 0) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Collection Rate</p>
                            <p class="text-xl font-bold text-blue-600 mt-1">{{ $aCollectPct }}%</p>
                        </div>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2.5">
                        <div class="h-2.5 rounded-full bg-gradient-to-r from-emerald-500 to-emerald-600" style="width: {{ $aCollectPct }}%"></div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ── Daily payments chart + payment mode breakdown ── --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
            <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="font-semibold text-gray-800">Daily Collections</h3>
                        <p class="text-xs text-gray-400">Last 14 days</p>
                    </div>
                </div>
                <div class="h-60" wire:key="analytics-daily-{{ $analyticsStandardId }}-{{ $analyticsSectionId }}">
                    <canvas x-data="{
                        chart: null,
                        init() {
                            this.chart = new Chart(this.$el.getContext('2d'), {
                                type: 'bar',
                                data: {
                                    labels: @js($analyticsDaily['labels'] ?? []),
                                    datasets: [{ label: 'Collected', data: @js($analyticsDaily['amounts'] ?? []), backgroundColor: 'rgba(16,185,129,0.75)', borderRadius: 4, borderSkipped: false }]
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

            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="font-semibold text-gray-800 mb-4">Payment Modes</h3>
                <div class="space-y-4">
                    @foreach ($modeMeta as $key => $meta)
                        @php $val = $analyticsModeBreakdown[$key] ?? 0; $pct = $modeMax > 0 ? ($val / $modeMax) * 100 : 0; @endphp
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-gray-500">{{ $meta['label'] }}</span>
                                <span class="font-semibold text-gray-700">₹{{ number_format($val, 0) }}</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2">
                                <div class="h-2 rounded-full {{ $meta['color'] }}" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ── Recent payments feed ── --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-5">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700">Recent Payments</h3>
                <button wire:click="showTab('payments')" class="text-xs font-medium text-blue-600 hover:text-blue-800">View all →</button>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-2.5 text-left">Student</th>
                        <th class="px-4 py-2.5 text-left">Class/Sec</th>
                        <th class="px-4 py-2.5 text-left">Receipt</th>
                        <th class="px-4 py-2.5 text-left">Mode</th>
                        <th class="px-4 py-2.5 text-left">Date</th>
                        <th class="px-4 py-2.5 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($analyticsRecentPayments as $rp)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2.5 font-medium text-gray-800">{{ $rp['name'] }}</td>
                            <td class="px-4 py-2.5 text-gray-500">{{ $rp['class'] }}</td>
                            <td class="px-4 py-2.5 font-mono text-xs text-blue-700">{{ $rp['receipt'] }}</td>
                            <td class="px-4 py-2.5 capitalize text-gray-600">{{ $rp['mode'] }}</td>
                            <td class="px-4 py-2.5 text-gray-600">{{ $rp['date'] }}</td>
                            <td class="px-4 py-2.5 text-right font-semibold text-emerald-600">₹{{ number_format($rp['amount'], 0) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">No payments recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

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
        {{-- Identical to the accounts Payments page — same three partials. --}}
        <div class="space-y-4">
            @include('livewire.partials.payments-filters')
            @include('livewire.partials.payments-analytics')
            @include('livewire.partials.payments-table')
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- TAB 6: PENALTIES (per-student)                                  --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    @if ($activeTab === 'penalties')
        @include('livewire.partials.penalties-panel')
    @endif

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- TAB 6B: FEE CYCLE (installments)                                --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    @if ($activeTab === 'cycle')
        @include('livewire.partials.fee-cycle-panel')
    @endif

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: CONCESSION (per-student fee discount)                      --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    @if ($activeTab === 'concession')
        @include('livewire.partials.fee-concession-panel')
    @endif

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- TAB 7: ACCOUNT USERS                                            --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    @if ($activeTab === 'account_users')
        @livewire('admin.account-users')
    @endif

    </div>
</div>
