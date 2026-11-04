{{-- Reports: the schools a month's figure came from, biggest first, each
     with its share as a bar (the top one in full colour).

     $title, $sub, $list ([['name', 'value', 'count'], …]), $money (₹ or a
     count), $unit ('receipt' | 'payment' | null — what count counts),
     $color (the bar's), $empty (text with no rows), $more (a link, or null) --}}
@php
    $max   = max(1.0, (float) max(array_column($list, 'value') ?: [0]));
    $total = array_sum(array_column($list, 'value'));
@endphp
<div class="sa-card p-5 min-w-0 flex flex-col">
    <div class="flex items-start justify-between gap-3 mb-4">
        <div class="min-w-0">
            <h3 class="sa-card-title">{{ $title }}</h3>
            <p class="sa-card-sub">{{ $sub }}</p>
        </div>
        @if (!empty($more))
            <a href="{{ $more }}" class="text-xs font-medium text-violet-600 hover:text-violet-800 whitespace-nowrap">View all →</a>
        @endif
    </div>
    <div class="space-y-3.5">
        @forelse ($list as $i => $row)
            <div class="min-w-0">
                <div class="flex items-center justify-between gap-3 text-sm">
                    <p class="font-medium text-gray-800 truncate min-w-0" title="{{ $row['name'] }}">
                        <span class="inline-flex w-5 text-gray-400 tabular-nums">{{ $i + 1 }}</span>{{ $row['name'] }}
                    </p>
                    <p class="whitespace-nowrap tabular-nums">
                        <span class="font-semibold text-gray-900">{{ $money ? \App\Support\DashboardChart::inrShort($row['value']) : number_format($row['value']) }}</span>
                        @if ($unit)
                            <span class="text-[11px] text-gray-400 ml-1">{{ $row['count'] }} {{ \Illuminate\Support\Str::plural($unit, $row['count']) }}</span>
                        @endif
                    </p>
                </div>
                <div class="mt-1.5 h-1.5 rounded-full bg-gray-100">
                    <div class="h-1.5 rounded-full" style="width: {{ max(3, round($row['value'] / $max * 100)) }}%; background: {{ $color }}; opacity: {{ $i === 0 ? 1 : .45 }}"></div>
                </div>
            </div>
        @empty
            <p class="py-10 text-center text-sm text-gray-400">{{ $empty }}</p>
        @endforelse
    </div>
    @if (count($list) > 0)
        <p class="mt-auto pt-4 text-[11px] text-gray-400">Top {{ count($list) }} · {{ $money ? \App\Support\DashboardChart::inrShort($total) : number_format($total) }} between them</p>
    @endif
</div>
