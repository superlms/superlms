{{-- KPI tiles in the school Analytics' look: one white box, the figures in
     cells split by hairlines. $items = [[label, value, sub|null, tone], …];
     $cols = the grid's column classes; $big = the large 26px figures (Key
     Metrics) instead of the smaller ones the other sections use. --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden grid {{ $cols ?? 'grid-cols-2 xl:grid-cols-4' }}">
    @foreach ($items as [$label, $value, $sub, $tone])
        <div class="px-5 py-4 -mr-px -mb-px border-r border-b border-gray-100 min-w-0">
            <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide truncate">{{ $label }}</p>
            <p class="{{ ($big ?? false) ? 'text-[26px] leading-none mt-2' : 'text-xl mt-1.5' }} font-semibold {{ $tone }} tabular-nums truncate" title="{{ $value }}">{{ $value }}</p>
            @if ($sub)
                <p class="mt-1.5 text-xs text-gray-400 truncate">{{ $sub }}</p>
            @endif
        </div>
    @endforeach
</div>
