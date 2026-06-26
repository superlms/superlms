<div class="min-h-screen bg-gray-50">

    {{-- ══════════ HEADER ══════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4 sm:py-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Accounts Dashboard</h1>
                <p class="text-sm text-gray-500 mt-0.5">Welcome, {{ auth()->user()->name }} · {{ auth()->user()->organization->name ?? '' }}</p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gray-50 border border-gray-200 text-sm text-gray-600">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                {{ now()->format('l, d M Y') }}
            </span>
        </div>
    </div>

    <div class="p-4 sm:p-6 space-y-6">

        {{-- ══════════ KEY STATS ══════════ --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-gray-400 uppercase tracking-wide">Total Collected</p>
                    <span class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </span>
                </div>
                <p class="text-2xl font-bold text-gray-900 mt-2">₹{{ number_format($stats['collected'], 0) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-gray-400 uppercase tracking-wide">Pending</p>
                    <span class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </span>
                </div>
                <p class="text-2xl font-bold text-red-600 mt-2">₹{{ number_format($stats['pending'], 0) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-gray-400 uppercase tracking-wide">Today</p>
                    <span class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </span>
                </div>
                <p class="text-2xl font-bold text-blue-600 mt-2">₹{{ number_format($stats['today'], 0) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-gray-400 uppercase tracking-wide">This Month</p>
                    <span class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13h2l1 5h12l1-5h2M5 13L4 4h16l-1 9" /></svg>
                    </span>
                </div>
                <p class="text-2xl font-bold text-purple-600 mt-2">₹{{ number_format($stats['month'], 0) }}</p>
            </div>
        </div>

        {{-- Secondary stats --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <p class="text-xl font-bold text-cyan-600">₹{{ number_format($stats['transport_collected'], 0) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Transport Fees</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <p class="text-xl font-bold text-gray-800">{{ $stats['students'] }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Students</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <p class="text-xl font-bold text-gray-800">{{ $stats['employees'] }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Employees</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <p class="text-xl font-bold text-amber-600">₹{{ number_format($stats['salary_month'], 0) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Salary (Month)</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <p class="text-xl font-bold text-indigo-600">{{ $stats['admissions'] }}<span class="text-sm text-amber-500"> / {{ $stats['admissions_pending'] }} pend</span></p>
                <p class="text-xs text-gray-400 mt-0.5">Admissions</p>
            </div>
        </div>

        {{-- ══════════ QUICK ACCESS ══════════ --}}
        <div>
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Quick Access</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3">
                @foreach ($menu as $item)
                    <a href="{{ route($item['link'], ['organization' => auth()->user()->organization_id]) }}"
                        class="group bg-white rounded-xl border border-gray-200 p-4 flex flex-col items-center text-center gap-2 hover:border-blue-300 hover:shadow-md transition-all">
                        <span class="w-11 h-11 rounded-xl bg-blue-50 group-hover:bg-blue-100 flex items-center justify-center transition-colors">
                            <x-icon name="{{ $item['icon'] ?? 'squares-2x2' }}" class="w-5 h-5 text-blue-600" />
                        </span>
                        <span class="text-sm font-medium text-gray-700 group-hover:text-blue-700">{{ $item['title'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- ══════════ RECENT PAYMENTS ══════════ --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-700">Recent Fee Payments</h2>
                <a href="{{ route('accounts.payments', ['organization' => auth()->user()->organization_id]) }}" class="text-xs font-medium text-blue-600 hover:text-blue-800">View all →</a>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Student</th>
                        <th class="px-4 py-3 text-left">Receipt</th>
                        <th class="px-4 py-3 text-left">Mode</th>
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($recentPayments as $p)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800">{{ $p['student'] }}</p>
                                <p class="text-xs text-gray-400">{{ $p['admno'] }}</p>
                            </td>
                            <td class="px-4 py-3 font-mono text-gray-600">{{ $p['receipt'] }}</td>
                            <td class="px-4 py-3 capitalize text-gray-600">{{ $p['mode'] }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $p['date'] }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-emerald-600">₹{{ number_format($p['amount'], 0) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400">No payments recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
