{{-- ══════════════════════════════════════════════════════════════════
     PAYMENTS — the listing. Every fee payment regardless of which table it
     lives in (fee_payments + transport_fee_payments), styled like the
     payments list on the Fee Submission / View Fee screens: borderless
     rows, light-weight headers, one slip button each.
     Needs $payments (paginator of normalized rows) and $paymentsOrgId.
     With $paymentsDeletable (admin panel, Super Admin's Delete Fee on) a
     payment taken at the school has a Delete beside its slip, asked first.
══════════════════════════════════════════════════════════════════ --}}

@php
    $pageAmount  = collect($payments->items())->sum('amount');
    $canDelete   = $paymentsDeletable ?? false;
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
                    <th class="px-4 py-2 text-right font-normal">Amount</th>
                    <th class="px-4 py-2 text-center font-normal {{ $canDelete ? 'w-28' : 'w-20' }}">Slip</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $index => $p)
                    <tr wire:key="pay-{{ $p->receipt_route }}-{{ $p->id }}" class="hover:bg-gray-50/70">
                        <td class="px-4 py-2.5 text-[11px] text-gray-300 tabular-nums">{{ $payments->firstItem() + $index }}</td>
                        {{-- The student over their father, as the student lists read (2026-10-14) --}}
                        <td class="px-4 py-2.5">
                            <p class="text-gray-800 leading-tight">{{ $p->student_name }}</p>
                            @if (!empty($p->father_name))
                                <p class="text-[11px] text-gray-400 leading-tight mt-0.5">{{ $p->father_name }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 font-mono text-xs text-gray-500">{{ $p->admission_no ?? '—' }}</td>
                        {{-- The class over its section, in small type --}}
                        <td class="px-4 py-2.5 whitespace-nowrap">
                            <p class="text-gray-600 leading-tight">{{ $p->standard_name ?? '—' }}</p>
                            @if ($p->section_name)
                                <p class="text-[11px] text-gray-400 leading-tight mt-0.5">{{ $p->section_name }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-gray-600 whitespace-nowrap">{{ $p->payment_date?->format('d M Y') ?? '—' }}</td>
                        <td class="px-4 py-2.5 capitalize {{ $p->fee_type === 'penalty' ? 'text-amber-600' : 'text-gray-600' }}">{{ !empty($p->is_dues) ? 'Dues' : $p->fee_type }}</td>
                        <td class="px-4 py-2.5 capitalize {{ $p->waiver_amount > 0 ? 'text-emerald-600' : 'text-gray-600' }}">{{ str_replace('_', ' ', $p->payment_mode) }}</td>
                        {{-- Who took it, by kind only — Admin, Sub Admin… (HandlesPayments::paymentCollectorTypes) --}}
                        <td class="px-4 py-2.5 text-gray-500 whitespace-nowrap">{{ $p->collected_by_type ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-right font-semibold text-gray-900 tabular-nums">₹{{ number_format($p->amount, 2) }}</td>
                        <td class="px-4 py-2.5 text-center whitespace-nowrap">
                            <a href="{{ route($p->receipt_route, ['organization' => $paymentsOrgId, 'id' => $p->id]) }}" target="_blank" title="Open slip"
                                class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-emerald-200 text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                            </a>
                            @if ($canDelete)
                                @if ($p->deletable ?? false)
                                    <button type="button" wire:click="askDeleteFeePayment('{{ $p->kind }}', {{ $p->id }})" title="Delete payment"
                                        class="ml-1 inline-flex items-center justify-center w-8 h-8 rounded-md border border-red-200 text-red-500 hover:bg-red-50 hover:text-red-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                @else
                                    {{-- Paid online in the app: the gateway's record stays. --}}
                                    <span title="Paid online in the app — cannot be deleted"
                                        class="ml-1 inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-100 text-gray-300 cursor-not-allowed">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </span>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-4 py-12 text-center">
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

    {{-- Delete asks first: whose payment, how much, which receipt. --}}
    @if ($canDelete && !empty($feePaymentToDelete))
        <div class="fixed inset-0 z-[9999] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="fee-del-title"
            x-data x-on:keydown.escape.window="$wire.keepFeePayment()">
            <div class="absolute inset-0 bg-black/30" wire:click="keepFeePayment"></div>
            <div class="relative w-full max-w-sm bg-white rounded-xl shadow-2xl p-5">
                <h3 id="fee-del-title" class="text-base font-semibold text-gray-900">Delete this payment?</h3>
                <dl class="mt-3 text-sm divide-y divide-gray-100 border-y border-gray-100">
                    <div class="flex justify-between gap-3 py-2"><dt class="text-gray-500">Student</dt><dd class="text-gray-900 text-right">{{ $feePaymentToDelete['student'] }}</dd></div>
                    <div class="flex justify-between gap-3 py-2"><dt class="text-gray-500">Amount</dt><dd class="font-semibold text-gray-900 tabular-nums">₹{{ number_format($feePaymentToDelete['amount'], 2) }}</dd></div>
                    @if ($feePaymentToDelete['receipt'] !== '')
                        <div class="flex justify-between gap-3 py-2"><dt class="text-gray-500">Receipt</dt><dd class="font-mono text-xs text-gray-700">{{ $feePaymentToDelete['receipt'] }}</dd></div>
                    @endif
                    @if ($feePaymentToDelete['date'] !== '')
                        <div class="flex justify-between gap-3 py-2"><dt class="text-gray-500">Date</dt><dd class="text-gray-700">{{ $feePaymentToDelete['date'] }}</dd></div>
                    @endif
                </dl>
                <p class="mt-3 text-xs text-gray-500">The amount goes back to the student's dues and its receipt stops opening. This cannot be undone.</p>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" wire:click="keepFeePayment" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button type="button" wire:click="deleteFeePayment" wire:loading.attr="disabled" wire:target="deleteFeePayment"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md disabled:opacity-60">Delete</button>
                </div>
            </div>
        </div>
    @endif
</div>
