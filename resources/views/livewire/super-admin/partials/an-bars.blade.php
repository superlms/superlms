{{-- A ranked list of bars (Analytics' extra detail, 2026-11-19): each row's
     name, its count and its share, with a bar against the first.
     $rows = [['label' => …, 'count' => …], …]; $empty = the line shown when
     there are none; optional $unit (word after the count), $top (bars in teal
     for the first, a soft teal for the rest). --}}
@php
    $rows  = $rows ?? [];
    $sum   = max(1, (int) array_sum(array_column($rows, 'count')));
    $first = max(1, (int) max(array_column($rows, 'count') ?: [0]));
@endphp
@if (count($rows))
    <div class="space-y-3.5">
        @foreach ($rows as $row)
            <div class="min-w-0">
                <div class="flex items-baseline justify-between gap-3 text-sm">
                    <p class="font-medium text-gray-800 truncate" title="{{ $row['label'] }}">{{ $row['label'] }}</p>
                    <p class="text-gray-700 tabular-nums whitespace-nowrap"><span class="font-semibold">{{ number_format($row['count']) }}</span>@if (!empty($unit)) <span class="text-xs text-gray-400">{{ $unit }}</span>@endif <span class="text-xs text-gray-400">· {{ round($row['count'] / $sum * 100) }}%</span></p>
                </div>
                <div class="mt-1.5 h-1.5 rounded-full bg-gray-100">
                    <div class="h-1.5 rounded-full" style="width: {{ round($row['count'] / $first * 100) }}%; background: {{ $loop->first ? '#0d9488' : '#99e0d3' }}"></div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <p class="text-sm text-gray-400 py-8 text-center">{{ $empty ?? 'Nothing yet' }}</p>
@endif
