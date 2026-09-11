{{-- ══════════════════════════════════════════════════════════════════
     PAYMENTS — the listing. Every fee payment regardless of which table it
     lives in (fee_payments + transport_fee_payments), styled like the
     payments list on the Fee Submission / View Fee screens: borderless
     rows, light-weight headers, one slip button each.
     Needs $payments (paginator of normalized rows) and $paymentsOrgId.
══════════════════════════════════════════════════════════════════ --}}

@php
    $pageAmount  = collect($payments->items())->sum('amount');
    $pagePenalty = collect($payments->items())->sum('penalty_amount');
@endphp

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between gap-2">
        <h3 class="text-sm font-semibold text-gray-900">Payments</h3>
        <span class="text-[11px] text-gray-400">
            {{ $payments->total() }} {{ Str::plural('entry', $payments->total()) }}
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-gray-100">
                <tr class="text-[11px] uppercase tracking-wider text-gray-400">
                    <th class="px-4 py-2 text-left font-normal w-10">#</th>
                    <th class="px-4 py-2 text-left font-normal">Student</th>
                    <th class="px-4 py-2 text-left font-normal">Adm No</th>
                    <th class="px-4 py-2 text-left font-normal">Class</th>
                    <th class="px-4 py-2 text-left font-normal">Date</th>
                    <th class="px-4 py-2 text-left font-normal">Type</th>
                    <th class="px-4 py-2 text-left font-normal">Mode</th>
                    <th class="px-4 py-2 text-left font-normal">Collected By</th>
                    <th class="px-4 py-2 text-right font-normal">Penalty</th>
                    <th class="px-4 py-2 text-right font-normal">Amount</th>
                    <th class="px-4 py-2 text-center font-normal w-20">Slip</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $index => $p)
                    <tr wire:key="pay-{{ $p->receipt_route }}-{{ $p->id }}" class="hover:bg-gray-50/70">
                        <td class="px-4 py-2.5 text-[11px] text-gray-300 tabular-nums">{{ $payments->firstItem() + $index }}</td>
                        <td class="px-4 py-2.5 text-gray-800">{{ $p->student_name }}</td>
                        <td class="px-4 py-2.5 font-mono text-xs text-gray-500">{{ $p->admission_no ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-gray-600 whitespace-nowrap">
                            {{ $p->standard_name ?? '—' }}@if ($p->section_name)<span class="text-gray-400"> · {{ $p->section_name }}</span>@endif
                        </td>
                        <td class="px-4 py-2.5 text-gray-600 whitespace-nowrap">{{ $p->payment_date?->format('d M Y') ?? '—' }}</td>
                        <td class="px-4 py-2.5 capitalize {{ $p->fee_type === 'penalty' ? 'text-amber-600' : 'text-gray-600' }}">{{ $p->fee_type }}</td>
                        <td class="px-4 py-2.5 capitalize {{ $p->waiver_amount > 0 ? 'text-emerald-600' : 'text-gray-600' }}">{{ str_replace('_', ' ', $p->payment_mode) }}</td>
                        <td class="px-4 py-2.5 text-gray-500">{{ $p->submitted_by ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-right tabular-nums {{ $p->penalty_amount > 0 ? 'text-amber-600' : 'text-gray-300' }}">
                            ₹{{ number_format($p->penalty_amount, 2) }}
                        </td>
                        <td class="px-4 py-2.5 text-right font-semibold text-gray-900 tabular-nums">₹{{ number_format($p->amount, 2) }}</td>
                        <td class="px-4 py-2.5 text-center">
                            <a href="{{ route($p->receipt_route, ['organization' => $paymentsOrgId, 'id' => $p->id]) }}" target="_blank" title="Open slip"
                                class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-200 text-gray-400 hover:bg-gray-50 hover:text-gray-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="px-4 py-12 text-center">
                            <p class="text-sm font-semibold text-gray-800">No payments found</p>
                            <p class="text-xs text-gray-400 mt-1">Adjust the date range or filters above.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if ($payments->count())
                <tfoot>
                    <tr class="border-t border-gray-200">
                        <td colspan="8" class="px-4 py-2.5 text-[11px] font-semibold uppercase tracking-wider text-gray-400">This page</td>
                        <td class="px-4 py-2.5 text-right text-gray-500 tabular-nums">₹{{ number_format($pagePenalty, 2) }}</td>
                        <td class="px-4 py-2.5 text-right font-bold text-gray-900 tabular-nums">₹{{ number_format($pageAmount, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    @if ($payments->hasPages())
        <div class="flex items-center justify-between gap-3 px-4 py-2.5 border-t border-gray-100">
            <span class="text-[11px] text-gray-400">
                Showing {{ $payments->firstItem() }}–{{ $payments->lastItem() }} of {{ $payments->total() }}
            </span>
            <div class="text-xs">{{ $payments->links() }}</div>
        </div>
    @endif
</div>
