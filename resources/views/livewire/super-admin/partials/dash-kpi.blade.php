{{-- A KPI card: label and icon, the figure, its change against last month
     (green up / red down) and a sparkline of the months behind it.

     $label, $value, $icon (heroicon), $series (numbers, oldest first),
     $trend (DashboardChart::trend() or null), $sub (a line under it, or null),
     $color ('violet' | 'sky' | 'emerald' | 'amber' | 'rose'), $href (or null) --}}
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
    $tag   = !empty($href) ? 'a' : 'div';
@endphp
<{{ $tag }} @if (!empty($href)) href="{{ $href }}" @endif class="sa-card p-5 flex flex-col gap-4 min-w-0">
    <div class="flex items-center justify-between gap-2">
        <p class="text-sm font-medium text-gray-500 truncate">{{ $label }}</p>
        <span class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background: {{ $tint }}; color: {{ $ink }}">
            <x-icon name="{{ $icon }}" class="w-4 h-4" />
        </span>
    </div>
    <div class="flex items-end justify-between gap-3">
        <div class="min-w-0">
            <p class="text-[28px] leading-none font-semibold tracking-tight text-gray-900 tabular-nums truncate" title="{{ $value }}">{{ $value }}</p>
            @if (!empty($trend))
                <p class="mt-2.5 text-xs flex items-center gap-1.5 whitespace-nowrap">
                    <span class="inline-flex items-center gap-0.5 font-semibold {{ $trend['up'] ? 'text-emerald-600' : 'text-rose-500' }}">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $trend['up'] ? 'M12 19V5m-6 6l6-6 6 6' : 'M12 5v14m6-6l-6 6-6-6' }}" />
                        </svg>
                        {{ rtrim(rtrim(number_format($trend['pct'], 1), '0'), '.') }}%
                    </span>
                    <span class="text-gray-400">vs last month</span>
                </p>
            @endif
        </div>
        <svg viewBox="0 0 120 40" width="120" height="40" class="flex-shrink-0 overflow-visible" aria-hidden="true">
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
    @if (!empty($sub))
        {{-- Under the figure and the sparkline both, so it has the card's width. --}}
        <p class="-mt-1 pt-3 border-t border-gray-100 text-xs text-gray-400 truncate" title="{{ $sub }}">{{ $sub }}</p>
    @endif
</{{ $tag }}>
