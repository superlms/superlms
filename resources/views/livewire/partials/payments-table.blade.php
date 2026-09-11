{{-- ══════════════════════════════════════════════════════════════════
     PAYMENTS — the listing. Every fee payment regardless of which table it
     lives in (fee_payments + transport_fee_payments), with a receipt link
     on each row. Shared by Accounts\Payments and the Admin Fee tab.
     Needs $payments (paginator of normalized rows) and $paymentsOrgId.
══════════════════════════════════════════════════════════════════ --}}

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">

    <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between gap-2">
        <h3 class="text-sm font-semibold text-gray-900">Payments</h3>
        <p class="text-[11px] text-gray-400">
            {{ $payments->total() }} {{ Str::plural('record', $payments->total()) }}
        </p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                    <th class="px-4 py-2.5 text-left w-10">#</th>
                    <th class="px-4 py-2.5 text-left">Student</th>
                    <th class="px-4 py-2.5 text-left">Adm No</th>
                    <th class="px-4 py-2.5 text-left">Class</th>
                    <th class="px-4 py-2.5 text-left">Type</th>
                    <th class="px-4 py-2.5 text-left">Mode</th>
                    <th class="px-4 py-2.5 text-right">Amount</th>
                    <th class="px-4 py-2.5 text-right">Penalty</th>
                    <th class="px-4 py-2.5 text-right">Waiver</th>
                    <th class="px-4 py-2.5 text-left">Date</th>
                    <th class="px-4 py-2.5 text-left">Collected By</th>
                    <th class="px-4 py-2.5 text-center w-16">Receipt</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($payments as $index => $p)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2.5 text-[11px] text-gray-300 tabular-nums">{{ $payments->firstItem() + $index }}</td>
                        <td class="px-4 py-2.5 text-gray-800 font-medium">{{ $p->student_name }}</td>
                        <td class="px-4 py-2.5 text-xs text-gray-500 tabular-nums">{{ $p->admission_no ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-gray-600">
                            {{ $p->standard_name ?? '—' }}@if ($p->section_name)<span class="text-gray-400"> · {{ $p->section_name }}</span>@endif
                        </td>
                        <td class="px-4 py-2.5 capitalize {{ $p->fee_type === 'penalty' ? 'text-red-600' : 'text-gray-600' }}">{{ $p->fee_type }}</td>
                        <td class="px-4 py-2.5 capitalize text-gray-600">{{ str_replace('_', ' ', $p->payment_mode) }}</td>
                        <td class="px-4 py-2.5 text-right text-gray-900 tabular-nums">₹{{ number_format($p->amount, 2) }}</td>
                        <td class="px-4 py-2.5 text-right tabular-nums {{ $p->penalty_amount > 0 ? 'text-gray-700' : 'text-gray-300' }}">
                            {{ $p->penalty_amount > 0 ? '₹' . number_format($p->penalty_amount, 2) : '—' }}
                        </td>
                        <td class="px-4 py-2.5 text-right tabular-nums {{ $p->waiver_amount > 0 ? 'text-gray-700' : 'text-gray-300' }}">
                            @if ($p->waiver_amount > 0)
                                <span title="{{ $p->waiver_reason ?? '' }}">₹{{ number_format($p->waiver_amount, 2) }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-xs text-gray-600 whitespace-nowrap">{{ $p->payment_date?->format('d M Y') ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-xs text-gray-500">{{ $p->submitted_by ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-center">
                            <a href="{{ route($p->receipt_route, ['organization' => $paymentsOrgId, 'id' => $p->id]) }}" target="_blank" title="Receipt"
                                class="inline-flex p-1.5 rounded-md text-gray-400 hover:text-blue-600 hover:bg-blue-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="px-4 py-16 text-center">
                            <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-gray-100 flex items-center justify-center">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" /></svg>
                            </div>
                            <p class="text-sm font-semibold text-gray-800">No payments found</p>
                            <p class="text-xs text-gray-400 mt-1">Adjust the date range or filters above.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($payments->total() > 0)
        <div class="flex items-center justify-between gap-3 px-4 py-2.5 border-t border-gray-100">
            <span class="text-[11px] text-gray-400">
                Showing {{ $payments->firstItem() }}–{{ $payments->lastItem() }} of {{ $payments->total() }}
            </span>
            @if ($payments->hasPages())
                <div class="text-xs">{{ $payments->links() }}</div>
            @endif
        </div>
    @endif
</div>
