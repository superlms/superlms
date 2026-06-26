<div class="min-h-screen bg-gray-50">

    {{-- Sticky Header --}}
    <div class="sticky top-0 z-30 bg-white border-b border-gray-200 shadow-sm">
        <div class="px-6 pt-4 pb-3">
            {{-- Title row --}}
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h1 class="text-xl font-bold text-gray-900 leading-tight">Payments</h1>
                    <p class="text-xs text-gray-500 mt-0.5">View all fee payment records</p>
                </div>
                <button wire:click="resetFilters"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 text-xs font-medium text-gray-600 bg-white hover:bg-gray-50 transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    Reset Filters
                </button>
            </div>

            {{-- Analytics Strip --}}
            <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-7 gap-2">
                {{-- Total Fee --}}
                <div class="bg-gray-50 rounded-xl border border-gray-200 px-3 py-2.5">
                    <p class="text-[10px] font-medium text-gray-500 uppercase tracking-wider leading-none mb-1">Total Fee</p>
                    <p class="text-sm font-bold text-gray-800 leading-tight truncate">
                        ₹{{ number_format($headerStats['total_fee'] ?? 0, 0) }}
                    </p>
                    <p class="text-[10px] text-gray-400 mt-0.5">Acad + Transport</p>
                </div>

                {{-- Academic Fee --}}
                <div class="bg-emerald-50 rounded-xl border border-emerald-200 px-3 py-2.5">
                    <p class="text-[10px] font-medium text-emerald-600 uppercase tracking-wider leading-none mb-1">Academic</p>
                    <p class="text-sm font-bold text-emerald-700 leading-tight truncate">
                        ₹{{ number_format($headerStats['total_academic_fee'] ?? 0, 0) }}
                    </p>
                    <p class="text-[10px] text-emerald-500 mt-0.5">Scheduled</p>
                </div>

                {{-- Transport Fee --}}
                <div class="bg-blue-50 rounded-xl border border-blue-200 px-3 py-2.5">
                    <p class="text-[10px] font-medium text-blue-600 uppercase tracking-wider leading-none mb-1">Transport</p>
                    <p class="text-sm font-bold text-blue-700 leading-tight truncate">
                        ₹{{ number_format($headerStats['total_transport_fee'] ?? 0, 0) }}
                    </p>
                    <p class="text-[10px] text-blue-500 mt-0.5">Scheduled</p>
                </div>

                {{-- Academic Collected --}}
                <div class="bg-emerald-50 rounded-xl border border-emerald-200 px-3 py-2.5">
                    <p class="text-[10px] font-medium text-emerald-600 uppercase tracking-wider leading-none mb-1">Acad. Collected</p>
                    <p class="text-sm font-bold text-emerald-700 leading-tight truncate">
                        ₹{{ number_format($headerStats['academic_collected'] ?? 0, 0) }}
                    </p>
                    <p class="text-[10px] text-emerald-500 mt-0.5">Received</p>
                </div>

                {{-- Transport Collected --}}
                <div class="bg-blue-50 rounded-xl border border-blue-200 px-3 py-2.5">
                    <p class="text-[10px] font-medium text-blue-600 uppercase tracking-wider leading-none mb-1">Trans. Collected</p>
                    <p class="text-sm font-bold text-blue-700 leading-tight truncate">
                        ₹{{ number_format($headerStats['transport_collected'] ?? 0, 0) }}
                    </p>
                    <p class="text-[10px] text-blue-500 mt-0.5">Received</p>
                </div>

                {{-- Total Collected (accent) --}}
                <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl px-3 py-2.5">
                    <p class="text-[10px] font-medium text-emerald-100 uppercase tracking-wider leading-none mb-1">Total Collected</p>
                    <p class="text-sm font-bold text-white leading-tight truncate">
                        ₹{{ number_format($headerStats['total_collected'] ?? 0, 0) }}
                    </p>
                    <p class="text-[10px] text-emerald-200 mt-0.5">All received</p>
                </div>

                {{-- Remaining --}}
                <div class="rounded-xl border px-3 py-2.5 {{ ($headerStats['remaining_fee'] ?? 0) > 0 ? 'bg-red-50 border-red-200' : 'bg-gray-50 border-gray-200' }}">
                    <p class="text-[10px] font-medium uppercase tracking-wider leading-none mb-1 {{ ($headerStats['remaining_fee'] ?? 0) > 0 ? 'text-red-500' : 'text-gray-500' }}">Remaining</p>
                    <p class="text-sm font-bold leading-tight truncate {{ ($headerStats['remaining_fee'] ?? 0) > 0 ? 'text-red-600' : 'text-gray-700' }}">
                        ₹{{ number_format($headerStats['remaining_fee'] ?? 0, 0) }}
                    </p>
                    <p class="text-[10px] mt-0.5 {{ ($headerStats['remaining_fee'] ?? 0) > 0 ? 'text-red-400' : 'text-gray-400' }}">
                        {{ ($headerStats['remaining_fee'] ?? 0) > 0 ? 'Outstanding' : 'Fully settled' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Page body --}}
    <div class="px-6 py-5 space-y-4">

        {{-- Filter Bar --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4 space-y-3">
            <div class="flex items-center gap-1.5 mb-1">
                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                </svg>
                <span class="text-xs font-semibold text-gray-600 uppercase tracking-wider">Filters</span>
            </div>

            {{-- Row 1: Date, Class, Section --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Date From</label>
                    <input type="date" wire:model.live="dateFrom"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Date To</label>
                    <input type="date" wire:model.live="dateTo"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Class</label>
                    <select wire:model.live="paymentStandardId"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">All Classes</option>
                        @foreach($standards as $std)
                            <option value="{{ $std->id }}">{{ $std->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Section</label>
                    <select wire:model.live="paymentSectionId"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">All Sections</option>
                        @foreach($sections as $sec)
                            <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Row 2: Search, Mode, Type --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Search Student</label>
                    <div class="relative">
                        <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <input type="text" wire:model.live.debounce.300ms="paymentStudentSearch"
                            placeholder="Name or Adm No"
                            class="w-full pl-8 rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Payment Mode</label>
                    <select wire:model.live="paymentModeFilter"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">All Modes</option>
                        <option value="cash">Cash</option>
                        <option value="online">Online</option>
                        <option value="cheque">Cheque</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="waiver">Waiver</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Fee Type</label>
                    <select wire:model.live="feeTypeFilter"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">All Types</option>
                        <option value="academic">Academic</option>
                        <option value="transport">Transport</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Payments Table --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-800">Payment Records</h2>
                <span class="text-xs text-gray-500">
                    @if($payments->total() > 0)
                        {{ $payments->total() }} {{ Str::plural('record', $payments->total()) }}
                    @endif
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-emerald-50 border-b border-emerald-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-emerald-700 whitespace-nowrap">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-emerald-700 whitespace-nowrap">Student Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-emerald-700 whitespace-nowrap">Adm No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-emerald-700 whitespace-nowrap">Class – Section</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-emerald-700 whitespace-nowrap">Fee Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-emerald-700 whitespace-nowrap">Mode</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-emerald-700 whitespace-nowrap">Amount</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-emerald-700 whitespace-nowrap">Penalty</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-emerald-700 whitespace-nowrap">Waiver</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-emerald-700 whitespace-nowrap">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-emerald-700 whitespace-nowrap">Collected By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($payments as $index => $payment)
                            <tr class="hover:bg-emerald-50/40 transition-colors">
                                <td class="px-4 py-3 text-xs text-gray-400 tabular-nums">
                                    {{ $payments->firstItem() + $index }}
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-xs font-semibold text-gray-800">
                                        {{ $payment->studentDetail?->user?->name ?? $payment->studentDetail?->full_name ?? '—' }}
                                    </p>
                                </td>
                                <td class="px-4 py-3 text-xs font-mono text-emerald-700 font-medium">
                                    {{ $payment->studentDetail?->admission_no ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-700">
                                    {{ $payment->standard?->name ?? '—' }}
                                    @if($payment->section?->name)
                                        <span class="text-gray-400">/ {{ $payment->section->name }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium
                                        {{ $payment->fee_type === 'academic' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700' }}">
                                        {{ ucfirst($payment->fee_type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $modeColors = [
                                            'cash'          => 'bg-green-100 text-green-700',
                                            'online'        => 'bg-purple-100 text-purple-700',
                                            'cheque'        => 'bg-orange-100 text-orange-700',
                                            'bank_transfer' => 'bg-cyan-100 text-cyan-700',
                                            'waiver'        => 'bg-amber-100 text-amber-700',
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium
                                        {{ $modeColors[$payment->payment_mode] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ ucfirst(str_replace('_', ' ', $payment->payment_mode)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right text-xs font-semibold text-gray-800 tabular-nums">
                                    ₹{{ number_format($payment->amount, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right text-xs tabular-nums
                                    {{ ($payment->penalty_amount ?? 0) > 0 ? 'text-red-600 font-medium' : 'text-gray-300' }}">
                                    {{ ($payment->penalty_amount ?? 0) > 0 ? '₹' . number_format($payment->penalty_amount, 2) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right text-xs tabular-nums
                                    {{ ($payment->waiver_amount ?? 0) > 0 ? 'text-amber-600 font-medium' : 'text-gray-300' }}">
                                    @if(($payment->waiver_amount ?? 0) > 0)
                                        <span title="{{ $payment->waiver_reason ?? '' }}">₹{{ number_format($payment->waiver_amount, 2) }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600 whitespace-nowrap">
                                    {{ $payment->payment_date?->format('d M Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500">
                                    {{ $payment->submitted_by ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-4 py-16 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center">
                                            <svg class="w-7 h-7 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">No payments found</p>
                                            <p class="text-xs text-gray-400 mt-0.5">Try adjusting your filters or date range</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($payments->hasPages())
                <div class="px-5 py-3 border-t border-gray-100 bg-gray-50 flex flex-col sm:flex-row items-center justify-between gap-2">
                    <p class="text-xs text-gray-500">
                        Showing <span class="font-medium text-gray-700">{{ $payments->firstItem() }}</span>
                        to <span class="font-medium text-gray-700">{{ $payments->lastItem() }}</span>
                        of <span class="font-medium text-gray-700">{{ $payments->total() }}</span> payments
                    </p>
                    <div class="text-xs">
                        {{ $payments->links() }}
                    </div>
                </div>
            @else
                @if($payments->count() > 0)
                    <div class="px-5 py-3 border-t border-gray-100 bg-gray-50">
                        <p class="text-xs text-gray-500">
                            Showing all <span class="font-medium text-gray-700">{{ $payments->total() }}</span> {{ Str::plural('payment', $payments->total()) }}
                        </p>
                    </div>
                @endif
            @endif
        </div>

    </div>
</div>
