{{-- Messages a school sent to many (More → Messaging → Send) and how far each
     got, as a list in a card (the caller's sa-card) in the Home's look.
       $campaigns   a paginator of MessagingCampaign
       $showSchool  the school's name on each (the Super Admin's every-school list) --}}
@php
    $mcSending = collect($campaigns->items())->contains(fn ($c) => $c->status !== 'done');
    $mcSchool  = (bool) ($showSchool ?? false);
    $mcLabel   = ['whatsapp' => 'WhatsApp', 'sms' => 'SMS', 'email' => 'Email'];
@endphp
<div class="overflow-x-auto" @if ($mcSending) wire:poll.5s @endif data-campaigns>
    <table class="sk-table">
        <thead>
            <tr>
                <th>When</th>
                @if ($mcSchool)<th>School</th>@endif
                <th>Message</th>
                <th>To</th>
                <th class="num">Sent</th>
                <th class="num">Not sent</th>
                <th class="num">Messages used</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($campaigns as $mc)
                <tr wire:key="mc-{{ $mc->id }}" style="vertical-align: top">
                    <td class="whitespace-nowrap text-gray-500 tabular-nums">
                        {{ $mc->created_at?->format('d M Y') }}
                        <span class="block text-[11px] text-gray-400">{{ $mc->created_at?->format('h:i A') }}</span>
                    </td>
                    @if ($mcSchool)
                        <td class="font-medium text-gray-900">{{ $mc->organization?->name ?? '—' }}</td>
                    @endif
                    <td style="max-width: 28rem">
                        <span class="an-tag {{ $mc->channel }}">{{ $mcLabel[$mc->channel] ?? ucfirst($mc->channel) }}</span>
                        @if ($mc->subject)
                            <p class="mt-1 text-sm font-semibold text-gray-900 break-words">{{ $mc->subject }}</p>
                        @endif
                        <p class="mt-0.5 text-xs text-gray-500 break-words line-clamp-2">{{ $mc->body }}</p>
                        @if ($mc->creator)
                            <p class="text-[11px] text-gray-400 mt-0.5">by {{ $mc->creator->name }}</p>
                        @endif
                    </td>
                    <td class="text-gray-600">
                        {{ $mc->audience_label ?: ucfirst($mc->audience) }}
                        <span class="block text-[11px] text-gray-400">{{ number_format($mc->recipients) }} {{ (int) $mc->recipients === 1 ? 'person' : 'people' }}</span>
                    </td>
                    <td class="num font-semibold text-emerald-700">{{ number_format($mc->sent) }}</td>
                    <td class="num text-gray-500">
                        {{ number_format($mc->failed + $mc->skipped) }}
                        @if ($mc->failed || $mc->skipped)
                            <span class="block text-[11px] text-gray-400">{{ trim(($mc->failed ? $mc->failed . ' failed' : '') . ($mc->failed && $mc->skipped ? ' · ' : '') . ($mc->skipped ? $mc->skipped . ' skipped' : '')) }}</span>
                        @endif
                    </td>
                    <td class="num text-gray-700">{{ number_format($mc->sent) }}<span class="block text-[11px] text-gray-400">₹{{ number_format($mc->sent * (float) $mc->rate, 2) }}</span></td>
                    <td class="whitespace-nowrap">
                        @if ($mc->status === 'done')
                            <span class="an-tag green">Done</span>
                        @else
                            <span class="an-tag blue">{{ $mc->status === 'queued' ? 'Waiting' : 'Sending' }} · {{ number_format($mc->done) }}/{{ number_format($mc->recipients) }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ $mcSchool ? 8 : 7 }}" class="text-center text-sm text-gray-400" style="padding: 48px 12px">No messages sent yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if ($campaigns->hasPages())
    <div class="mg-pages">{{ $campaigns->links('livewire::tailwind') }}</div>
@endif
