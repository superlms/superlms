{{-- ══════════════════════════════════════════════════════════════════
     PAYMENTS — the analytics strip. Everything on one line: the overall
     pair, then academic, then transport. Recomputed from the same filters
     as the listing below, so it always matches what's on screen.
     Shared by Accounts\Payments and the Admin Fee "Payments" tab.
══════════════════════════════════════════════════════════════════ --}}

@php
    $s = $headerStats ?? [];
    // [label, value, is-a-due-figure, starts-a-new-group]
    $cells = [
        ['Total Fee',           $s['total_fee'] ?? 0,            false, false],
        ['Collected',           $s['total_collected'] ?? 0,      false, false],
        ['Academic Fee',        $s['total_academic_fee'] ?? 0,   false, true],
        ['Academic Collected',  $s['academic_collected'] ?? 0,   false, false],
        ['Academic Remaining',  $s['academic_remaining'] ?? 0,   true,  false],
        ['Transport Fee',       $s['total_transport_fee'] ?? 0,  false, true],
        ['Transport Collected', $s['transport_collected'] ?? 0,  false, false],
        ['Transport Remaining', $s['transport_remaining'] ?? 0,  true,  false],
    ];
@endphp

<div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
    <div class="flex items-stretch min-w-max">
        @foreach ($cells as $i => [$label, $value, $isDue, $newGroup])
            <div class="px-4 py-3 {{ $i === 0 ? '' : ($newGroup ? 'border-l border-gray-200' : 'border-l border-gray-100') }}">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 whitespace-nowrap">{{ $label }}</p>
                <p class="mt-1 text-sm font-bold tabular-nums whitespace-nowrap {{ $isDue && $value > 0 ? 'text-red-600' : 'text-gray-900' }}">
                    ₹{{ number_format($value, 0) }}
                </p>
            </div>
        @endforeach
    </div>
</div>
