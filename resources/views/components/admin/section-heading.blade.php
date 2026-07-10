@props([
    'title' => '',
    'subtitle' => null,
    'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
    'color' => 'indigo',
])

@php
    $palette = [
        'indigo'  => ['bg' => 'bg-indigo-50',  'ic' => 'text-indigo-600'],
        'emerald' => ['bg' => 'bg-emerald-50', 'ic' => 'text-emerald-600'],
        'blue'    => ['bg' => 'bg-blue-50',    'ic' => 'text-blue-600'],
        'amber'   => ['bg' => 'bg-amber-50',   'ic' => 'text-amber-600'],
        'violet'  => ['bg' => 'bg-violet-50',  'ic' => 'text-violet-600'],
        'rose'    => ['bg' => 'bg-rose-50',    'ic' => 'text-rose-600'],
    ];
    $c = $palette[$color] ?? $palette['indigo'];
@endphp

<div class="flex items-center gap-3 pt-1">
    <span class="w-9 h-9 rounded-xl {{ $c['bg'] }} flex items-center justify-center flex-shrink-0">
        <svg class="w-5 h-5 {{ $c['ic'] }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
        </svg>
    </span>
    <div class="flex-shrink-0">
        <h2 class="text-base font-bold text-slate-800 leading-tight">{{ $title }}</h2>
        @if ($subtitle)
            <p class="text-xs text-slate-400">{{ $subtitle }}</p>
        @endif
    </div>
    <div class="flex-1 h-px bg-gradient-to-r from-slate-200 to-transparent"></div>
</div>
