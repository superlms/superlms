{{-- Single-day attendance analytics as the card's header strip, matching the
     month-card header. Expects $stats = tally array and $title = the day.
     $subtitle (optional) replaces the default second line. --}}
@php
    $subtitle = $subtitle ?? '';
    $marked = $stats['present'] + $stats['absent'] + $stats['half_day'];
    $percent = $marked > 0 ? round(($stats['present'] + 0.5 * $stats['half_day']) / $marked * 100, 1) : 0;
@endphp

<div class="px-4 py-3 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-indigo-50 flex flex-wrap items-center justify-between gap-2">
    <div>
        <h3 class="text-sm font-semibold text-gray-700">{{ $title }}</h3>
        <p class="text-[11px] text-gray-400">{{ $subtitle ?: 'Sundays are a standing holiday.' }}</p>
    </div>
    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px]">
        <span class="text-gray-600">Total <strong>{{ $stats['total'] }}</strong></span>
        <span class="text-emerald-700">Present <strong>{{ $stats['present'] }}</strong></span>
        <span class="text-red-700">Absent <strong>{{ $stats['absent'] }}</strong></span>
        <span class="text-amber-700">Half <strong>{{ $stats['half_day'] }}</strong></span>
        <span class="text-indigo-700">Holiday <strong>{{ $stats['holiday'] }}</strong></span>
        <span class="text-gray-400">Not marked <strong>{{ $stats['not_marked'] }}</strong></span>
        <span class="px-2 py-0.5 rounded-full bg-white border border-indigo-200 font-semibold text-indigo-600">{{ $percent }}%</span>
    </div>
</div>
