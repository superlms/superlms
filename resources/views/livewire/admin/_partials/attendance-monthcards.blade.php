{{-- Attendance as small month calendars, kept quiet in the Mark Attendance
     panel's look: a plain header (whose, the period, the figures in grey text),
     then a white card a month with soft status tints and its figures in short.
     Used by Teacher → By Teacher and Student → By Student alike.
     Expects $cards = ['counts' => [...], 'months' => [ym => ['label','lead','cells','counts','pct']]]
     plus $title (period label) and, optionally, $person (whose attendance this is). --}}
@php
    $person = $person ?? '';
    $c = $cards['counts'];
    // The Mark Attendance panel's status tints.
    $dayCell = [
        'present'    => 'bg-emerald-50 text-emerald-700',
        'absent'     => 'bg-red-50 text-red-600',
        'half_day'   => 'bg-amber-50 text-amber-700',
        'holiday'    => 'bg-indigo-50 text-indigo-700',
        'not_marked' => 'text-gray-400',
    ];
@endphp

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="px-5 py-3.5 border-b border-gray-100 flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
        <h3 class="text-sm font-semibold text-gray-800 min-w-0 truncate">
            {{ $person ?: 'Attendance' }}
            <span class="font-normal text-gray-400">· {{ $title }}</span>
        </h3>
        <p class="text-xs text-gray-400 tabular-nums">
            Working <span class="text-gray-700 font-medium">{{ $c['working'] }}</span><span class="text-gray-300"> · </span>
            Present <span class="text-gray-700 font-medium">{{ $c['present'] }}</span><span class="text-gray-300"> · </span>
            Absent <span class="text-gray-700 font-medium">{{ $c['absent'] }}</span><span class="text-gray-300"> · </span>
            @if ($c['half_day'] > 0)
                Half <span class="text-gray-700 font-medium">{{ $c['half_day'] }}</span><span class="text-gray-300"> · </span>
            @endif
            Holiday <span class="text-gray-700 font-medium">{{ $c['holiday'] }}</span><span class="text-gray-300"> · </span>
            <span class="text-gray-700 font-medium">{{ $c['percent'] }}%</span>
        </p>
    </div>

    <div class="p-4">
        {{-- Each month is its own small calendar carrying its own numbers, so a
             single month reads the same as a whole year laid out 3 to a row. --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @forelse ($cards['months'] as $ym => $m)
                @php $mc = $m['counts']; @endphp
                <div class="rounded-lg border border-gray-200 p-3" wire:key="attcal-{{ $ym }}">
                    {{-- Beside the month, in short: present, absent, a half day only
                         when there is one, holidays, and the month's %. --}}
                    <div class="flex items-baseline justify-between gap-2 mb-2.5">
                        <p class="text-xs font-medium text-gray-700 whitespace-nowrap">{{ $m['label'] }}</p>
                        <p class="text-[10px] text-gray-400 tabular-nums whitespace-nowrap">
                            <span title="Present">P <span class="text-gray-700 font-medium">{{ $mc['present'] }}</span></span><span class="text-gray-300"> · </span>
                            <span title="Absent">A <span class="text-gray-700 font-medium">{{ $mc['absent'] }}</span></span><span class="text-gray-300"> · </span>
                            @if ($mc['half_day'] > 0)
                                <span title="Half day">½ <span class="text-gray-700 font-medium">{{ $mc['half_day'] }}</span></span><span class="text-gray-300"> · </span>
                            @endif
                            <span title="Holiday">H <span class="text-gray-700 font-medium">{{ $mc['holiday'] }}</span></span><span class="text-gray-300"> · </span>
                            <span class="text-gray-700 font-medium">{{ $m['pct'] }}%</span>
                        </p>
                    </div>

                    {{-- Sunday-first weekday header --}}
                    <div class="grid grid-cols-7 gap-1 mb-1">
                        @foreach (['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $dow)
                            <div class="text-center text-[9px] text-gray-300">{{ $dow }}</div>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-7 gap-1">
                        {{-- Blanks before the 1st so the weekdays line up --}}
                        @for ($b = 0; $b < $m['lead']; $b++)
                            <div></div>
                        @endfor

                        @foreach ($m['cells'] as $d)
                            @if (!$d['in_period'])
                                <div class="py-1 text-center text-[10px] leading-none text-gray-200 tabular-nums">{{ $d['day'] }}</div>
                            @else
                                <div class="rounded py-1 text-center text-[10px] leading-none tabular-nums {{ $dayCell[$d['status']] ?? $dayCell['not_marked'] }}"
                                    title="{{ \Carbon\Carbon::parse($d['date'])->format('D, d M Y') }} · {{ ucfirst(str_replace('_', ' ', $d['status'])) }}">
                                    {{ $d['day'] }}
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="sm:col-span-2 lg:col-span-3 text-sm text-gray-400 text-center py-6">Nothing to show for this period.</p>
            @endforelse
        </div>

        <div class="flex flex-wrap gap-x-3 gap-y-1 text-[11px] text-gray-400 mt-3">
            <span><span class="inline-block w-2.5 h-2.5 rounded-sm bg-emerald-100 align-middle"></span> Present</span>
            <span><span class="inline-block w-2.5 h-2.5 rounded-sm bg-red-100 align-middle"></span> Absent</span>
            <span><span class="inline-block w-2.5 h-2.5 rounded-sm bg-amber-100 align-middle"></span> Half day</span>
            <span><span class="inline-block w-2.5 h-2.5 rounded-sm bg-indigo-100 align-middle"></span> Holiday</span>
            <span><span class="inline-block w-2.5 h-2.5 rounded-sm bg-white border border-gray-200 align-middle"></span> Not marked</span>
            <span class="text-gray-300">·</span>
            <span>Sundays are a standing holiday</span>
        </div>
    </div>
</div>
