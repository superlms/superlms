@props(['state' => 'sent', 'tone' => 'light'])

{{-- Delivery receipt for a message I sent:
     one tick = sent, two ticks = delivered to them, two green ticks = read. --}}
@php
    $muted = $tone === 'light' ? 'text-blue-200' : 'text-gray-400';
    $color = $state === 'read' ? 'text-emerald-400' : $muted;
    $label = ['sent' => 'Sent', 'delivered' => 'Delivered', 'read' => 'Read'][$state] ?? 'Sent';
@endphp

<svg class="w-4 h-4 flex-shrink-0 {{ $color }}" viewBox="0 0 20 16" fill="none"
     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
     aria-label="{{ $label }}">
    <title>{{ $label }}</title>
    @if ($state === 'sent')
        <path d="M4 8.5 L7.5 12 L15 4" />
    @else
        <path d="M1 8.5 L4.5 12 L12 4" />
        <path d="M7.5 12 L15 4" />
    @endif
</svg>
