{{-- Stat tiles in the dashboard look (2026-11-03): each figure on its own
     soft card — the label, the figure, a line under it — with a thin coloured
     mark that follows the figure's tone. $items = [[label, value, sub|null,
     tone], …]; $cols = the grid's column classes; $big = larger figures.

     Nothing is cut: a figure that is wider than its tile wraps, a value that
     is words rather than a number (a page's address) is set smaller over up
     to two lines, and the line under it wraps. --}}
@php
    $mark = fn (string $tone) => match (true) {
        str_contains($tone, 'emerald') => '#10b981',
        str_contains($tone, 'red'), str_contains($tone, 'rose') => '#f43f5e',
        str_contains($tone, 'violet') => '#7c3aed',
        default => '#c4b5fd',
    };
@endphp
<div class="grid gap-4 {{ $cols ?? 'grid-cols-2 xl:grid-cols-4' }}">
    @foreach ($items as [$label, $value, $sub, $tone])
        @php $isWords = !preg_match('/^[\s₹\d,.%★+\-–—\/]*$/u', (string) $value) || mb_strlen((string) $value) > 16; @endphp
        <div class="sa-card px-5 py-4 min-w-0 relative overflow-hidden">
            <span class="absolute left-0 top-4 bottom-4 w-[3px] rounded-r-full" style="background: {{ $mark($tone) }}"></span>
            <p class="text-xs font-medium text-gray-500 truncate" title="{{ $label }}">{{ $label }}</p>
            @if ($isWords)
                <p class="mt-2 text-sm sm:text-[15px] leading-snug font-semibold {{ $tone }} break-all line-clamp-2" title="{{ $value }}">{{ $value }}</p>
            @else
                <p class="{{ ($big ?? false) ? 'text-[22px] sm:text-[26px] leading-none mt-2' : 'text-lg sm:text-xl leading-tight mt-1.5' }} font-semibold tracking-tight {{ $tone }} tabular-nums break-words">{{ $value }}</p>
            @endif
            @if ($sub)
                <p class="mt-1.5 text-xs text-gray-400 leading-snug break-words">{{ $sub }}</p>
            @endif
        </div>
    @endforeach
</div>
