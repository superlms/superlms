{{-- One of the Ledger header's figures that opens, on hover or tap, what it is
     made of: Credit and Expense by source (a line lists just those entries, the
     same line again shows everything), Closing from the opening balance.
     Takes: $label, $value, $valueClass, $class (the strip's padding), $title and
     $lines — each ['label', 'pill' (badge classes or null), 'amount',
     'amountClass', 'type' and 'source' (both null when the line is not a filter),
     'on' (it is the filter in use)]. --}}
{{-- On a phone or tablet the card opens under the whole stats row (its parent is
     the positioned box there), so it never runs off the screen. --}}
<div class="lg:relative {{ $class }}" x-data="{ open: false }"
    @mouseenter="open = true" @mouseleave="open = false" @keydown.escape="open = false">
    <button type="button" @click="open = ! open" :aria-expanded="open"
        class="inline-flex items-center gap-1 hover:text-gray-700">
        <span>{{ $label }}: <strong class="{{ $valueClass }}">{{ $value }}</strong></span>
        <svg class="w-3 h-3 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
    </button>

    {{-- pt-2 keeps the pointer inside on its way down to the card. --}}
    <div x-cloak x-show="open" x-transition.opacity.duration.100ms @click.outside="open = false"
        class="absolute inset-x-0 lg:left-auto lg:right-0 top-full pt-2 z-40">
        <div class="w-full sm:w-72 lg:w-64 bg-white border border-gray-200 rounded-xl shadow-lg py-1.5 text-left whitespace-nowrap">
            <p class="px-3 pt-1 pb-1.5 text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ $title }}</p>
            @foreach ($lines as $line)
                @php
                    $inner = ($line['pill']
                            ? '<span class="text-[11px] font-semibold px-1.5 py-0.5 rounded ' . e($line['pill']) . '">' . e($line['label']) . '</span>'
                            : '<span class="text-gray-600">' . e($line['label']) . '</span>')
                        . '<strong class="tabular-nums ' . e($line['amountClass']) . '">' . e($line['amount']) . '</strong>';
                @endphp
                @if ($line['source'])
                    <button type="button" wire:click="pickSource('{{ $line['type'] }}', '{{ $line['source'] }}')" @click="open = false"
                        class="w-full flex items-center justify-between gap-3 px-3 py-1.5 text-sm transition-colors {{ $line['on'] ? 'bg-blue-50' : 'hover:bg-gray-50' }}">{!! $inner !!}</button>
                @else
                    <div class="flex items-center justify-between gap-3 px-3 py-1.5 text-sm {{ $loop->last ? 'border-t border-gray-100 mt-1 pt-2' : '' }}">{!! $inner !!}</div>
                @endif
            @endforeach
        </div>
    </div>
</div>
