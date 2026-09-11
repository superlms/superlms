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
                        @foreach ($analyticsSections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                    </select>
                    @if ($analyticsStandardId || $analyticsSectionId)
                        <button wire:click="resetAnalyticsFilters" class="text-xs font-medium text-gray-500 hover:text-gray-800">Clear</button>
                    @endif
                    <span class="ml-auto hidden sm:inline-flex items-center gap-3 text-xs text-gray-500">
                        <span>Collected: <strong class="text-emerald-600">₹{{ number_format($analyticsSummary['total_collected'] ?? 0, 0) }}</strong></span>
                        <span>Due: <strong class="text-rose-600">₹{{ number_format($analyticsSummary['total_due'] ?? 0, 0) }}</strong></span>
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
        @include('livewire.partials.fee-analytics-panel')
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
