{{--
    Merged payments listing — every fee payment regardless of which table it
    lives in. Needs: $payments (paginator of normalized rows) and
    $paymentsOrgId (for the receipt links' {organization} segment).
--}}
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
                    <th class="px-4 py-3 text-center text-xs font-semibold text-emerald-700 whitespace-nowrap">Receipt</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($payments as $index => $payment)
                    <tr class="hover:bg-emerald-50/40 transition-colors">
                        <td class="px-4 py-3 text-xs text-gray-400 tabular-nums">
                            {{ $payments->firstItem() + $index }}
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-xs font-semibold text-gray-800">{{ $payment->student_name }}</p>
                        </td>
                        <td class="px-4 py-3 text-xs font-mono text-emerald-700 font-medium">
                            {{ $payment->admission_no ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-700">
                            {{ $payment->standard_name ?? '—' }}
                            @if($payment->section_name)
                                <span class="text-gray-400">/ {{ $payment->section_name }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $typeColors = [
                                    'academic' => 'bg-emerald-100 text-emerald-700',
                                    'transport' => 'bg-blue-100 text-blue-700',
                                    'penalty' => 'bg-red-100 text-red-700',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium
                                {{ $typeColors[$payment->fee_type] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ ucfirst($payment->fee_type) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $modeColors = [
                                    'cash'          => 'bg-green-100 text-green-700',
                                    'online'        => 'bg-purple-100 text-purple-700',
                                    'upi'           => 'bg-indigo-100 text-indigo-700',
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
                            {{ $payment->penalty_amount > 0 ? 'text-red-600 font-medium' : 'text-gray-300' }}">
                            {{ $payment->penalty_amount > 0 ? '₹' . number_format($payment->penalty_amount, 2) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right text-xs tabular-nums
                            {{ $payment->waiver_amount > 0 ? 'text-amber-600 font-medium' : 'text-gray-300' }}">
                            @if($payment->waiver_amount > 0)
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
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route($payment->receipt_route, ['organization' => $paymentsOrgId, 'id' => $payment->id]) }}"
                                target="_blank"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-gray-100 hover:bg-emerald-100 hover:text-emerald-700 text-gray-600 text-[11px] font-medium transition whitespace-nowrap">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
                                </svg>
                                Receipt
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="px-4 py-16 text-center">
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
