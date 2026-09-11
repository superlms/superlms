<div class="min-h-screen bg-gray-50">

    {{-- ══════════ STICKY HEADER — title, then the grey filter band ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900">Dashboard</h1>
            <span class="inline-flex items-center gap-1.5 text-xs text-gray-500">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                {{ now()->format('l, d M Y') }}
            </span>
        </div>

        {{-- Grey filter band — scopes every fee figure below to a class/section --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-2.5">
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700 shrink-0">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    <span class="hidden sm:inline">Filter by:</span>
                </div>

                <select wire:model.live="analyticsStandardId"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">All Classes</option>
                    @foreach ($standards as $std)
                        <option value="{{ $std->id }}">{{ $std->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="analyticsSectionId" @disabled(!$analyticsStandardId)
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <option value="">All Sections</option>
                    @foreach ($analyticsSections as $sec)
                        <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                    @endforeach
                </select>

                @if ($analyticsStandardId || $analyticsSectionId)
                    <button wire:click="resetAnalyticsFilters"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Clear
                    </button>
                @endif

                <span class="ml-auto hidden md:inline-flex items-center gap-3 text-xs text-gray-500">
                    <span>Collected: <strong class="text-emerald-600">₹{{ number_format($analyticsSummary['total_collected'] ?? 0, 0) }}</strong></span>
                    <span>Due: <strong class="text-red-600">₹{{ number_format($analyticsSummary['total_due'] ?? 0, 0) }}</strong></span>
                </span>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 space-y-5">

        {{-- ══════════ SCHOOL & OFFICE AT A GLANCE ══════════ --}}
        @php
            $o = $officeStats;
            $glance = [
                ['Students',        number_format($o['students']),             $o['classes'] . ' ' . Str::plural('class', $o['classes'])],
                ['New This Month',  number_format($o['new_admissions']),       'admissions'],
                ['On Transport',    number_format($o['riders']),               $o['students'] > 0 ? round($o['riders'] / $o['students'] * 100) . '% of roll' : '—'],
                ['Employees',       number_format($o['employees']),            'on payroll'],
                ['Salary Paid',     '₹' . number_format($o['salary_month'], 0), now()->format('F')],
                ['Salary Pending',  '₹' . number_format($o['salary_pending'], 0), 'this month'],
                ['Enquiries',       number_format($o['enquiries']),            $o['enquiries_pending'] . ' open'],
                ['Fee Entries',     number_format($analyticsDaily['count'] ?? 0),  'last 14 days'],
            ];
        @endphp
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-8 divide-x divide-y sm:divide-y-0 divide-gray-100">
                @foreach ($glance as $i => [$label, $value, $sub])
                    <div class="px-3 py-2.5 {{ $i >= 4 ? 'sm:border-t sm:border-gray-100 xl:border-t-0' : '' }}">
                        <p class="text-[10px] uppercase tracking-wider text-gray-400 leading-tight">{{ $label }}</p>
                        <p class="mt-0.5 text-sm font-semibold text-gray-900 tabular-nums leading-tight">{{ $value }}</p>
                        <p class="text-[10px] text-gray-400 leading-tight mt-0.5">{{ $sub }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ══════════ FEE ANALYTICS — the admin Fee > Analytics screen ══════════ --}}
        @include('livewire.partials.fee-analytics-panel')

        {{-- ══════════ RECENT RECEIPTS + QUICK ACCESS ══════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between gap-2">
                    <h3 class="text-sm font-semibold text-gray-900">Recent receipts</h3>
                    <a href="{{ route('accounts.payments', ['organization' => $orgId]) }}"
                        class="text-xs font-medium text-gray-500 hover:text-gray-900 underline underline-offset-2 decoration-gray-300">All payments</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-gray-100">
                            <tr class="text-[11px] uppercase tracking-wider text-gray-400">
                                <th class="px-4 py-2 text-left font-normal">Student</th>
                                <th class="px-4 py-2 text-left font-normal">Receipt</th>
                                <th class="px-4 py-2 text-left font-normal">Type</th>
                                <th class="px-4 py-2 text-left font-normal">Mode</th>
                                <th class="px-4 py-2 text-left font-normal">Date</th>
                                <th class="px-4 py-2 text-right font-normal">Amount</th>
                                <th class="px-4 py-2 text-center font-normal w-16">Slip</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentPayments as $p)
                                <tr class="hover:bg-gray-50/70">
                                    <td class="px-4 py-2.5">
                                        <span class="text-gray-800">{{ $p['student'] }}</span>
                                        @if ($p['admno'])
                                            <span class="text-xs text-gray-400 font-mono"> · {{ $p['admno'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 font-mono text-xs text-gray-500">{{ $p['receipt'] }}</td>
                                    <td class="px-4 py-2.5 capitalize {{ $p['type'] === 'penalty' ? 'text-amber-600' : 'text-gray-600' }}">{{ $p['type'] }}</td>
                                    <td class="px-4 py-2.5 capitalize text-gray-600">{{ str_replace('_', ' ', $p['mode']) }}</td>
                                    <td class="px-4 py-2.5 text-gray-600 whitespace-nowrap">{{ $p['date'] }}</td>
                                    <td class="px-4 py-2.5 text-right font-semibold text-gray-900 tabular-nums">₹{{ number_format($p['amount'], 2) }}</td>
                                    <td class="px-4 py-2.5 text-center">
                                        <a href="{{ route($p['route'], ['organization' => $orgId, 'id' => $p['id']]) }}" target="_blank" title="Open slip"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-200 text-gray-400 hover:bg-gray-50 hover:text-gray-700">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-12 text-center">
                                    <p class="text-sm font-semibold text-gray-800">No payments yet</p>
                                    <p class="text-xs text-gray-400 mt-1">Collected fees will appear here with their slips.</p>
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-900">Quick access</h3>
                </div>
                <div class="grid grid-cols-3 gap-2 p-4">
                    @foreach ($menu as $item)
                        <a href="{{ route($item['link'], ['organization' => $orgId]) }}"
                            class="group flex flex-col items-center text-center gap-2 p-2.5 rounded-lg hover:bg-gray-50 transition-colors">
                            <span class="w-10 h-10 rounded-lg bg-gray-50 group-hover:bg-white group-hover:border-gray-200 border border-transparent flex items-center justify-center transition-colors">
                                <x-icon name="{{ $item['icon'] ?? 'squares-2x2' }}" class="w-5 h-5 text-gray-400 group-hover:text-gray-700" />
                            </span>
                            <span class="text-[11px] text-gray-500 group-hover:text-gray-900 leading-tight">{{ $item['title'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
