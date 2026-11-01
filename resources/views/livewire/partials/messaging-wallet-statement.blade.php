{{-- A school's messaging wallet as one list, newest first (MessagingWallet::statement):
     money put in and taken out — a school's own Add funds pending until approved,
     or rejected with the reason — and each month's messages as one line.
       $rows  MessagingWallet::statement() --}}
@php $wsInr = fn ($v) => '₹' . number_format(abs((float) $v), 2); @endphp
<div class="border border-gray-200 rounded-md overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-[11px] font-semibold uppercase tracking-wider text-gray-400">
            <tr>
                <th class="px-4 py-2 text-left">Date</th>
                <th class="px-4 py-2 text-left">What</th>
                <th class="px-4 py-2 text-left">Status</th>
                <th class="px-4 py-2 text-right">Amount</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($rows as $wsRow)
                @php
                    $wsTone = match ($wsRow['status']) {
                        'pending'  => 'text-amber-700 bg-amber-50',
                        'rejected' => 'text-red-700 bg-red-50',
                        default    => 'text-emerald-700 bg-emerald-50',
                    };
                    $wsWord = match ($wsRow['status']) {
                        'pending'  => 'To approve',
                        'rejected' => 'Rejected',
                        default    => $wsRow['kind'] === 'usage' ? 'Used' : 'Done',
                    };
                @endphp
                <tr wire:key="ws-{{ $wsRow['id'] }}">
                    <td class="px-4 py-2.5 whitespace-nowrap text-gray-500 tabular-nums">{{ \Illuminate\Support\Carbon::parse($wsRow['date'])->format('d M Y') }}</td>
                    <td class="px-4 py-2.5">
                        <p class="text-gray-800">{{ $wsRow['label'] }}</p>
                        @if ($wsRow['detail'])
                            <p class="text-xs text-gray-400 break-words">{{ $wsRow['detail'] }}</p>
                        @endif
                        @if ($wsRow['by'])
                            <p class="text-xs text-gray-400">by {{ $wsRow['by'] }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-2.5">
                        <span class="text-[11px] font-semibold px-1.5 py-0.5 rounded {{ $wsTone }}">{{ $wsWord }}</span>
                        @if ($wsRow['status'] === 'rejected' && $wsRow['reason'])
                            <span class="block text-xs text-gray-400 mt-0.5 break-words">{{ $wsRow['reason'] }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-right font-semibold tabular-nums whitespace-nowrap
                        {{ $wsRow['status'] !== 'approved' ? 'text-gray-400 line-through decoration-gray-300' : ($wsRow['amount'] < 0 ? 'text-red-600' : 'text-emerald-600') }}">
                        {{ $wsRow['amount'] < 0 ? '−' : '+' }}{{ $wsInr($wsRow['amount']) }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-10 text-center text-sm text-gray-400">Nothing in this wallet yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
