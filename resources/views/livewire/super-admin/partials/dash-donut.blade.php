{{-- A donut with its figure in the middle and, beside it, a legend with
     each part's share.

     $segments = [['label' => …, 'value' => number, 'color' => '#hex'], …]
     $center (the figure in the middle), $centerLabel, $size (px, default 148),
     $dark (on a dark card), $legend (default true), $format (fn for a value
     in the legend, or null for the share only) --}}
@php
    $rings  = \App\Support\DashboardChart::donut(array_column($segments, 'value'));
    $size   = $size ?? 148;
    $dark   = $dark ?? false;
    $legend = $legend ?? true;
@endphp
<div class="flex items-center gap-5 min-w-0">
    <div class="relative flex-shrink-0" style="width: {{ $size }}px; height: {{ $size }}px">
        <svg viewBox="0 0 42 42" class="w-full h-full" aria-hidden="true">
            <circle cx="21" cy="21" r="15.9155" fill="none" stroke="{{ $dark ? 'rgba(255,255,255,.12)' : '#f1f5f9' }}" stroke-width="5" />
            @foreach ($rings as $i => $ring)
                @if ($ring['pct'] > 0)
                    <circle cx="21" cy="21" r="15.9155" fill="none" stroke="{{ $segments[$i]['color'] }}" stroke-width="5"
                        stroke-dasharray="{{ $ring['dash'] }}" stroke-dashoffset="{{ $ring['offset'] }}" />
                @endif
            @endforeach
        </svg>
        <div class="absolute inset-0 flex flex-col items-center justify-center text-center px-3">
            <span class="text-lg font-semibold tabular-nums leading-tight {{ $dark ? 'text-white' : 'text-gray-900' }}">{{ $center }}</span>
            @if (!empty($centerLabel))
                <span class="text-[10px] uppercase tracking-wide mt-0.5 {{ $dark ? 'text-white/60' : 'text-gray-400' }}">{{ $centerLabel }}</span>
            @endif
        </div>
    </div>
    @if ($legend)
        <ul class="flex-1 min-w-0 space-y-2.5">
            @foreach ($segments as $i => $s)
                <li class="flex items-center gap-2 text-sm min-w-0">
                    <span class="sa-dot" style="background: {{ $s['color'] }}"></span>
                    <span class="truncate {{ $dark ? 'text-white/70' : 'text-gray-600' }}" title="{{ $s['label'] }}">{{ $s['label'] }}</span>
                    <span class="ml-auto pl-2 font-semibold tabular-nums whitespace-nowrap {{ $dark ? 'text-white' : 'text-gray-900' }}">
                        @if (!empty($format)){{ $format($s['value']) }} · @endif{{ $rings[$i]['pct'] }}%
                    </span>
                </li>
            @endforeach
        </ul>
    @endif
</div>
