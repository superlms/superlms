{{-- A donut with its figure in the middle and a legend with each part's
     share: beside the donut on a wide card, under it on a narrow one (the
     card's own width decides, not the window's), so a label is never
     squeezed down to nothing. A long label wraps onto a second line.

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
<div class="{{ $legend ? 'sa-donut' : 'inline-flex' }} min-w-0">
    <div class="{{ $legend ? 'sa-donut-row' : '' }}">
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
                <span class="font-semibold tabular-nums leading-tight {{ $size < 120 ? 'text-base' : 'text-lg' }} {{ $dark ? 'text-white' : 'text-gray-900' }}">{{ $center }}</span>
                @if (!empty($centerLabel))
                    <span class="text-[10px] uppercase tracking-wide mt-0.5 {{ $dark ? 'text-white/60' : 'text-gray-400' }}">{{ $centerLabel }}</span>
                @endif
            </div>
        </div>
        @if ($legend)
            <ul class="space-y-2.5">
                @foreach ($segments as $i => $s)
                    <li class="flex items-start gap-2 text-sm min-w-0">
                        <span class="sa-dot mt-[0.4rem]" style="background: {{ $s['color'] }}"></span>
                        <span class="flex-1 min-w-0 leading-snug break-words {{ $dark ? 'text-white/70' : 'text-gray-600' }}" title="{{ $s['label'] }}">{{ $s['label'] }}</span>
                        <span class="pl-2 font-semibold tabular-nums whitespace-nowrap leading-snug {{ $dark ? 'text-white' : 'text-gray-900' }}">
                            @if (!empty($format)){{ $format($s['value']) }} <span class="font-normal {{ $dark ? 'text-white/50' : 'text-gray-400' }}">·</span> @endif{{ $rings[$i]['pct'] }}%
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
