{{-- Messages a school sent to many (More → Messaging → Send) and how far each got.
       $campaigns   a paginator of MessagingCampaign
       $showSchool  the school's name on each (the Super Admin's every-school list) --}}
@php
    $mcInr     = fn ($v) => '₹' . number_format((float) $v, 2);
    $mcSending = collect($campaigns->items())->contains(fn ($c) => $c->status !== 'done');
    $mcSchool  = (bool) ($showSchool ?? false);
@endphp
<div class="bg-white rounded-xl border border-gray-200 overflow-x-auto" @if ($mcSending) wire:poll.5s @endif>
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200 text-[11px] font-semibold uppercase tracking-wider text-gray-400">
            <tr>
                <th class="px-4 py-2.5 text-left">When</th>
                @if ($mcSchool)<th class="px-4 py-2.5 text-left">School</th>@endif
                <th class="px-4 py-2.5 text-left">Message</th>
                <th class="px-4 py-2.5 text-left">To</th>
                <th class="px-4 py-2.5 text-right">Sent</th>
                <th class="px-4 py-2.5 text-right">Not sent</th>
                <th class="px-4 py-2.5 text-right">Cost</th>
                <th class="px-4 py-2.5 text-left">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($campaigns as $mc)
                <tr wire:key="mc-{{ $mc->id }}" class="align-top">
                    <td class="px-4 py-3 whitespace-nowrap text-gray-500 tabular-nums">
                        {{ $mc->created_at?->format('d M Y') }}
                        <span class="block text-[11px] text-gray-400">{{ $mc->created_at?->format('h:i A') }}</span>
                    </td>
                    @if ($mcSchool)
                        <td class="px-4 py-3 text-gray-800">{{ $mc->organization?->name ?? '—' }}</td>
                    @endif
                    <td class="px-4 py-3 max-w-md">
                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded {{ $mc->channel === 'whatsapp' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700' }}">{{ $mc->channel === 'whatsapp' ? 'WhatsApp' : 'Email' }}</span>
                        @if ($mc->subject)
                            <p class="mt-1 text-sm font-medium text-gray-900 break-words">{{ $mc->subject }}</p>
                        @endif
                        <p class="mt-0.5 text-xs text-gray-500 break-words line-clamp-2">{{ $mc->body }}</p>
                        @if ($mc->creator)
                            <p class="text-[11px] text-gray-400 mt-0.5">by {{ $mc->creator->name }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-600">
                        {{ $mc->audience_label ?: ucfirst($mc->audience) }}
                        <span class="block text-[11px] text-gray-400">{{ number_format($mc->recipients) }} {{ (int) $mc->recipients === 1 ? 'person' : 'people' }}</span>
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums text-emerald-600 font-semibold">{{ number_format($mc->sent) }}</td>
                    <td class="px-4 py-3 text-right tabular-nums text-gray-500">
                        {{ number_format($mc->failed + $mc->skipped) }}
                        @if ($mc->failed || $mc->skipped)
                            <span class="block text-[11px] text-gray-400">{{ trim(($mc->failed ? $mc->failed . ' failed' : '') . ($mc->failed && $mc->skipped ? ' · ' : '') . ($mc->skipped ? $mc->skipped . ' skipped' : '')) }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums text-gray-700">{{ $mcInr($mc->sent * (float) $mc->rate) }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if ($mc->status === 'done')
                            <span class="text-xs font-medium text-emerald-600">Done</span>
                        @else
                            <span class="text-xs font-medium text-blue-600">{{ $mc->status === 'queued' ? 'Waiting' : 'Sending' }} · {{ number_format($mc->done) }}/{{ number_format($mc->recipients) }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ $mcSchool ? 8 : 7 }}" class="px-4 py-12 text-center text-sm text-gray-400">No messages sent yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if ($campaigns->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">{{ $campaigns->links('livewire::tailwind') }}</div>
    @endif
</div>
