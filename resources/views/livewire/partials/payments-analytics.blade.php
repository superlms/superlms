{{-- ══════════════════════════════════════════════════════════════════
     PAYMENTS — the analytics strip, attached under the filter band inside
     the host's sticky header. A wrapping grid rather than a scrolling row,
     so every figure is always on screen: eight across on a wide window,
     four then two as it narrows. Recomputed from the same filters as the
     listing, so it always matches what's below.
══════════════════════════════════════════════════════════════════ --}}

@php
    $s = $headerStats ?? [];
    // [label, value, is-a-due-figure]
    $paymentStatCells = [
        ['Total Fee',           $s['total_fee'] ?? 0,           false],
        ['Collected',           $s['total_collected'] ?? 0,     false],
        ['Academic Fee',        $s['total_academic_fee'] ?? 0,  false],
        ['Academic Collected',  $s['academic_collected'] ?? 0,  false],
        ['Academic Remaining',  $s['academic_remaining'] ?? 0,  true],
        ['Transport Fee',       $s['total_transport_fee'] ?? 0, false],
        ['Transport Collected', $s['transport_collected'] ?? 0, false],
        ['Transport Remaining', $s['transport_remaining'] ?? 0, true],
    ];
@endphp

<div class="border-t border-gray-200 bg-white">
    <div class="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-8 divide-x divide-y sm:divide-y-0 xl:divide-y-0 divide-gray-100">
        @foreach ($paymentStatCells as $i => [$label, $value, $isDue])
            <div class="px-3 py-2 {{ $i >= 4 ? 'sm:border-t sm:border-gray-100 xl:border-t-0' : '' }}">
                <p class="text-[10px] uppercase tracking-wider text-gray-400 leading-tight">{{ $label }}</p>
                <p class="mt-0.5 text-sm font-semibold tabular-nums leading-tight {{ $isDue && $value > 0 ? 'text-red-600' : 'text-gray-900' }}">
                    ₹{{ number_format($value, 0) }}
                </p>
            </div>
        @endforeach
    </div>
</div>
