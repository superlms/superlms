{{-- Column bars for School Data's analytics (2026-11-21): one bar per month
     (or day), the last one in teal and the rest in a soft teal, its label
     under it and its figure over it under the pointer. Plain HTML, so it
     stays drawn through every Livewire re-render.

     $values (numbers, oldest first; null = nothing that day), $labels,
     $format (fn for a figure), $empty (the line shown when all are 0),
     $height (px of the bars, default 132), $max (the top of the scale, or
     the biggest value). --}}
@php
    $vals   = array_map(fn ($v) => $v === null ? null : (float) $v, $values ?? []);
    $top    = (float) ($max ?? max(array_map(fn ($v) => (float) $v, $vals) ?: [0]));
    $format = $format ?? fn ($v) => number_format($v);
@endphp
@if ($top <= 0)
    <p class="text-sm text-gray-400 py-10 text-center">{{ $empty ?? 'Nothing yet' }}</p>
@else
    <div class="sd-bars {{ count($vals) > 8 ? 'sd-12' : '' }}" style="--h: {{ $height ?? 132 }}px">
        @foreach ($vals as $i => $v)
            @php $pct = $v > 0 ? max(3, min(100, round($v / $top * 100, 1))) : 0; @endphp
            <div class="sd-bar {{ $loop->last ? 'is-now' : '' }}" title="{{ $labels[$i] ?? '' }} · {{ $v === null ? 'Not marked' : $format($v) }}">
                <div class="sd-bar-t" style="--p: {{ $pct }}%">
                    <i style="height: {{ $pct }}%"></i>
                    <span class="sd-bar-tip">{{ $v === null ? '—' : $format($v) }}</span>
                </div>
                <b>{{ $labels[$i] ?? '' }}</b>
            </div>
        @endforeach
    </div>
@endif
