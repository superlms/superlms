<div class="min-h-screen bg-gray-50">

    {{-- ══════════ HEADER ══════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3 sticky top-0 z-30">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">Accounts Dashboard</h1>
            </div>
            <div class="flex items-center gap-2">
                <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 border border-emerald-100 text-sm text-emerald-700 font-medium">
                    {{ $stats['collection_rate'] }}% collected
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gray-50 border border-gray-200 text-sm text-gray-600">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    {{ now()->format('l, d M Y') }}
                </span>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 space-y-6">

        {{-- ══════════ KPI CARDS ══════════ --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Total Collected --}}
            <div class="relative overflow-hidden bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl p-5 text-white shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium text-emerald-50/90 uppercase tracking-wide">Total Collected</p>
                        <p class="text-2xl sm:text-3xl font-bold mt-2">₹{{ number_format($stats['collected'], 0) }}</p>
                        <p class="text-[11px] text-emerald-50/80 mt-1">{{ $stats['txn_count'] }} payments · avg ₹{{ number_format($stats['avg_txn'], 0) }}</p>
                    </div>
                    <span class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </span>
                </div>
                <svg class="absolute -bottom-6 -right-4 w-28 h-28 text-white/10" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
            </div>

            {{-- Pending --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Pending Dues</p>
                        <p class="text-2xl sm:text-3xl font-bold text-red-600 mt-2">₹{{ number_format($stats['pending'], 0) }}</p>
                        <p class="text-[11px] text-gray-400 mt-1">of expected fee</p>
                    </div>
                    <span class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </span>
                </div>
            </div>

            {{-- This Month --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide">This Month</p>
                        <p class="text-2xl sm:text-3xl font-bold text-gray-900 mt-2">₹{{ number_format($stats['month'], 0) }}</p>
                        <p class="text-[11px] text-gray-400 mt-1">{{ now()->format('F Y') }}</p>
                    </div>
                    <span class="w-9 h-9 rounded-xl bg-violet-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13h2l1 5h12l1-5h2M5 13L4 4h16l-1 9" /></svg>
                    </span>
                </div>
            </div>

            {{-- Today --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Collected Today</p>
                        <p class="text-2xl sm:text-3xl font-bold text-gray-900 mt-2">₹{{ number_format($stats['today'], 0) }}</p>
                        <p class="text-[11px] text-gray-400 mt-1">{{ $stats['today_count'] }} payment(s)</p>
                    </div>
                    <span class="w-9 h-9 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </span>
                </div>
            </div>
        </div>

        {{-- ══════════ CHARTS: DAILY + MODES ══════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            {{-- Daily collections bar --}}
            <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-800">Daily Collections</h2>
                        <p class="text-xs text-gray-400">Last 14 days</p>
                    </div>
                    <span class="text-xs font-medium text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg">₹{{ number_format(array_sum($chartDaily['amounts']), 0) }}</span>
                </div>
                <div class="h-64" wire:ignore>
                    <canvas x-data="{}" x-init="
                        new Chart($el.getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: @js($chartDaily['labels']),
                                datasets: [{ label: 'Collected', data: @js($chartDaily['amounts']), backgroundColor: 'rgba(16,185,129,0.8)', hoverBackgroundColor: 'rgba(5,150,105,1)', borderRadius: 6, borderSkipped: false, maxBarThickness: 26 }]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: false,
                                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => '₹' + c.parsed.y.toLocaleString('en-IN') } } },
                                scales: {
                                    x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#9ca3af' } },
                                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 10 }, color: '#9ca3af', callback: (v) => '₹' + v.toLocaleString('en-IN') } }
                                }
                            }
                        })
                    "></canvas>
                </div>
            </div>

            {{-- Payment modes doughnut --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-gray-800 mb-1">Payment Modes</h2>
                <p class="text-xs text-gray-400 mb-4">Share of total collection</p>
                @if (count($chartModes['labels']))
                    <div class="h-48" wire:ignore>
                        <canvas x-data="{}" x-init="
                            new Chart($el.getContext('2d'), {
                                type: 'doughnut',
                                data: {
                                    labels: @js($chartModes['labels']),
                                    datasets: [{ data: @js($chartModes['amounts']), backgroundColor: ['#10b981','#3b82f6','#f59e0b','#8b5cf6'], borderWidth: 0, hoverOffset: 6 }]
                                },
                                options: {
                                    responsive: true, maintainAspectRatio: false, cutout: '68%',
                                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, padding: 12, font: { size: 11 }, color: '#6b7280', usePointStyle: true } },
                                        tooltip: { callbacks: { label: (c) => c.label + ': ₹' + c.parsed.toLocaleString('en-IN') } } }
                                }
                            })
                        "></canvas>
                    </div>
                @else
                    <div class="h-48 flex flex-col items-center justify-center text-center">
                        <svg class="w-10 h-10 text-gray-200 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                        <p class="text-xs text-gray-400">No payments yet</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- ══════════ CHARTS: MONTHLY TREND + COLLECTION RING ══════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            {{-- Monthly trend line --}}
            <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-800">Monthly Trend</h2>
                        <p class="text-xs text-gray-400">Collections over the last 6 months</p>
                    </div>
                </div>
                <div class="h-60" wire:ignore>
                    <canvas x-data="{}" x-init="
                        (() => {
                            const ctx = $el.getContext('2d');
                            const grad = ctx.createLinearGradient(0, 0, 0, 240);
                            grad.addColorStop(0, 'rgba(16,185,129,0.28)');
                            grad.addColorStop(1, 'rgba(16,185,129,0)');
                            new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: @js($chartMonthly['labels']),
                                    datasets: [{ label: 'Collected', data: @js($chartMonthly['amounts']), borderColor: '#059669', backgroundColor: grad, fill: true, tension: 0.4, borderWidth: 2.5, pointBackgroundColor: '#059669', pointRadius: 3, pointHoverRadius: 5 }]
                                },
                                options: {
                                    responsive: true, maintainAspectRatio: false,
                                    plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => '₹' + c.parsed.y.toLocaleString('en-IN') } } },
                                    scales: {
                                        x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#9ca3af' } },
                                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 10 }, color: '#9ca3af', callback: (v) => '₹' + v.toLocaleString('en-IN') } }
                                    }
                                }
                            });
                        })()
                    "></canvas>
                </div>
            </div>

            {{-- Collection progress ring --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm flex flex-col">
                <h2 class="text-sm font-semibold text-gray-800 mb-1">Collection Progress</h2>
                <p class="text-xs text-gray-400 mb-2">Collected vs expected fee</p>
                <div class="relative flex-1 flex items-center justify-center min-h-[176px]" wire:ignore>
                    <canvas x-data="{}" x-init="
                        new Chart($el.getContext('2d'), {
                            type: 'doughnut',
                            data: {
                                labels: ['Collected', 'Pending'],
                                datasets: [{ data: [{{ (float) $stats['collected'] }}, {{ (float) $stats['pending'] }}], backgroundColor: ['#10b981','#f1f5f9'], borderWidth: 0 }]
                            },
                            options: { responsive: true, maintainAspectRatio: false, cutout: '78%', plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => c.label + ': ₹' + c.parsed.toLocaleString('en-IN') } } } }
                        })
                    "></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <span class="text-3xl font-bold text-gray-900">{{ $stats['collection_rate'] }}%</span>
                        <span class="text-[11px] text-gray-400">collected</span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 mt-3 text-center">
                    <div class="rounded-lg bg-emerald-50 py-2">
                        <p class="text-sm font-bold text-emerald-700">₹{{ number_format($stats['collected'], 0) }}</p>
                        <p class="text-[10px] uppercase tracking-wide text-emerald-500">Collected</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 py-2">
                        <p class="text-sm font-bold text-gray-700">₹{{ number_format($stats['pending'], 0) }}</p>
                        <p class="text-[10px] uppercase tracking-wide text-gray-400">Pending</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════ SECONDARY STATS ══════════ --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            @php
                $tiles = [
                    ['Transport Fees', '₹' . number_format($stats['transport_collected'], 0), 'text-cyan-600', 'bg-cyan-50', 'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1'],
                    ['Students', number_format($stats['students']), 'text-gray-800', 'bg-gray-100', 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197'],
                    ['Employees', number_format($stats['employees']), 'text-indigo-600', 'bg-indigo-50', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                    ['Salary (Month)', '₹' . number_format($stats['salary_month'], 0), 'text-amber-600', 'bg-amber-50', 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
                    ['Admissions', $stats['admissions'] . ' / ' . $stats['admissions_pending'] . ' pend', 'text-rose-600', 'bg-rose-50', 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z'],
                ];
            @endphp
            @foreach ($tiles as [$label, $value, $tc, $bg, $icon])
                <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3 shadow-sm">
                    <span class="w-10 h-10 rounded-lg {{ $bg }} flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 {{ $tc }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-base font-bold {{ $tc }} truncate">{{ $value }}</p>
                        <p class="text-[11px] text-gray-400">{{ $label }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ══════════ RECENT PAYMENTS + QUICK ACCESS ══════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            {{-- Recent payments --}}
            <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm">
                <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-800">Recent Fee Payments</h2>
                    <a href="{{ route('accounts.payments', ['organization' => auth()->user()->organization_id]) }}" class="text-xs font-medium text-emerald-600 hover:text-emerald-800">View all →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                            <tr>
                                <th class="px-4 py-2.5 text-left">Student</th>
                                <th class="px-4 py-2.5 text-left">Receipt</th>
                                <th class="px-4 py-2.5 text-left">Mode</th>
                                <th class="px-4 py-2.5 text-left">Date</th>
                                <th class="px-4 py-2.5 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($recentPayments as $p)
                                @php
                                    $modeClass = match($p['mode']) {
                                        'cash'          => 'bg-emerald-50 text-emerald-700',
                                        'online'        => 'bg-blue-50 text-blue-700',
                                        'cheque'        => 'bg-amber-50 text-amber-700',
                                        'bank_transfer' => 'bg-violet-50 text-violet-700',
                                        default         => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gray-800">{{ $p['student'] }}</p>
                                        <p class="text-xs text-gray-400">{{ $p['admno'] }}</p>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $p['receipt'] }}</td>
                                    <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-[11px] font-medium capitalize {{ $modeClass }}">{{ str_replace('_', ' ', $p['mode']) }}</span></td>
                                    <td class="px-4 py-3 text-gray-600">{{ $p['date'] }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-emerald-600">₹{{ number_format($p['amount'], 0) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-12 text-center text-gray-400">No payments recorded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Quick access --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-gray-800 mb-4">Quick Access</h2>
                <div class="grid grid-cols-3 gap-3">
                    @foreach ($menu as $item)
                        <a href="{{ route($item['link'], ['organization' => auth()->user()->organization_id]) }}"
                            class="group flex flex-col items-center text-center gap-2 p-2.5 rounded-xl hover:bg-emerald-50 transition-colors">
                            <span class="w-11 h-11 rounded-xl bg-gray-50 group-hover:bg-emerald-100 flex items-center justify-center transition-colors">
                                <x-icon name="{{ $item['icon'] ?? 'squares-2x2' }}" class="w-5 h-5 text-gray-500 group-hover:text-emerald-600" />
                            </span>
                            <span class="text-[11px] font-medium text-gray-600 group-hover:text-emerald-700 leading-tight">{{ $item['title'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
