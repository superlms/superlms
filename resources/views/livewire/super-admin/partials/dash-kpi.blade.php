{{-- A KPI card: label and icon, the figure beside a sparkline of the months
     behind it, its change against last month (green up / red down) and a
     line at its foot.

     The figure always keeps its full width: the sparkline beside it is what
     gives way on a narrow card, and the change has a row of its own, so the
     three never run into each other or cut the figure short.

     $label, $value, $icon (heroicon), $series (numbers, oldest first),
     $trend (DashboardChart::trend() or null — null with $flat (default true)
     reads "0%", with $flat false shows no change row), $compare (what the
     change is against, default "vs last month"), $sub (the foot line, or
     null), $color ('violet' | 'sky' | 'emerald' | 'amber' | 'rose'),
     $href (or null), $click (a Livewire action the card runs instead, e.g.
     "openMonthDetail('fees')" — Reports).

     Also used by the school admin's Home and Analytics (2026-11-04). --}}
@php
    $tones = [
        'violet'  => ['#f5f3ff', '#7c3aed'],
        'sky'     => ['#f0f9ff', '#0284c7'],
        'emerald' => ['#ecfdf5', '#059669'],
        'amber'   => ['#fffbeb', '#d97706'],
        'rose'    => ['#fff1f2', '#e11d48'],
    ];
    [$tint, $ink] = $tones[$color ?? 'violet'] ?? $tones['violet'];
    $spark = \App\Support\DashboardChart::sparkline($series ?? [], 120, 40);
    $gid   = 'spk' . substr(md5($label . json_encode($series ?? [])), 0, 10);
    $tag   = !empty($href) ? 'a' : (!empty($click) ? 'button' : 'div');
    $flat  = $flat ?? true;
@endphp
<{{ $tag }} @if (!empty($href)) href="{{ $href }}" @elseif (!empty($click)) type="button" wire:click="{{ $click }}" @endif class="sa-card sa-kpi p-5 flex flex-col min-w-0 {{ $tag === 'button' ? 'w-full text-left' : '' }}">
    <div class="flex items-center justify-between gap-2">
        <p class="text-sm font-medium text-gray-500 truncate" title="{{ $label }}">{{ $label }}</p>
        <span class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background: {{ $tint }}; color: {{ $ink }}">
            <x-icon name="{{ $icon }}" class="w-4 h-4" />
        </span>
    </div>

    <div class="mt-3 flex items-end justify-between gap-3">
        <p class="sa-kpi-value font-semibold tracking-tight text-gray-900 tabular-nums whitespace-nowrap">{{ $value }}</p>
        <div class="flex-1 min-w-0 flex justify-end">
            <svg viewBox="0 0 120 40" class="block overflow-visible" style="width: 100%; max-width: 120px; height: auto; aspect-ratio: 3 / 1" aria-hidden="true">
                <defs>
                    <linearGradient id="{{ $gid }}" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="{{ $ink }}" stop-opacity=".22" />
                        <stop offset="100%" stop-color="{{ $ink }}" stop-opacity="0" />
                    </linearGradient>
                </defs>
                <path d="{{ $spark['area'] }}" fill="url(#{{ $gid }})" />
                <path d="{{ $spark['line'] }}" fill="none" stroke="{{ $ink }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                <circle cx="{{ $spark['last'][0] }}" cy="{{ $spark['last'][1] }}" r="3" fill="#fff" stroke="{{ $ink }}" stroke-width="2" />
            </svg>
        </div>
    </div>

    @if (!empty($trend) || $flat)
        <div class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
            @if (!empty($trend))
                <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md font-semibold tabular-nums {{ $trend['up'] ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $trend['up'] ? 'M12 19V5m-6 6l6-6 6 6' : 'M12 5v14m6-6l-6 6-6-6' }}" />
                    </svg>
                    {{ rtrim(rtrim(number_format($trend['pct'], 1), '0'), '.') }}%
                </span>
            @else
                <span class="inline-flex items-center px-1.5 py-0.5 rounded-md font-semibold bg-gray-100 text-gray-500 tabular-nums">0%</span>
            @endif
            <span class="text-gray-400">{{ $compare ?? 'vs last month' }}</span>
        </div>
    @endif

    @if (!empty($sub))
        {{-- At the card's foot, its full width; a long line wraps instead of being cut. --}}
        <div class="mt-auto pt-4">
            <p class="pt-3 border-t border-gray-100 text-xs text-gray-400 leading-snug break-words">{{ $sub }}</p>
        </div>
    @endif
</{{ $tag }}>
