{{-- Attendance as small month calendars, kept quiet in the Mark Attendance
     panel's look: a plain header (whose, the period, the figures in grey text),
     then a white card a month with soft status tints and its figures in short.
     Used by Teacher → By Teacher and Student → By Student alike.
     Expects $cards = ['counts' => [...], 'months' => [ym => ['label','lead','cells','counts','pct']]]
     plus $title (period label) and, optionally, $person (whose attendance this is). --}}
@php
    $person = $person ?? '';
    $c = $cards['counts'];
    // Each status fills its whole square (user's ask of 2026-11-25).
    $dayCell = [
        'present'    => 'is-present',
        'absent'     => 'is-absent',
        'half_day'   => 'is-half',
        'holiday'    => 'is-holiday',
        'not_marked' => 'is-unmarked',
    ];
@endphp

@once
<style>
    /* A month as a table (user's ask of 2026-11-25): square cells with no
       rounding, touching, with thin lines between them; the date in the
       website's figure font; a day's status fills its whole cell. */
    .att-cal { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); border-top: 1px solid #e5eae8; border-left: 1px solid #e5eae8; }
    .att-cal > * { border-right: 1px solid #e5eae8; border-bottom: 1px solid #e5eae8; border-radius: 0; }
    .att-cal .att-dow { padding: 5px 0; text-align: center; font-size: 10px; font-weight: 600; letter-spacing: .04em; color: #95a39e; background: #fafbfb; }
    .att-cal .att-day { aspect-ratio: 1 / 1; display: flex; align-items: center; justify-content: center; background: #fff;
        font-family: 'Plus Jakarta Sans', 'Geist', system-ui, -apple-system, 'Segoe UI', sans-serif; font-size: 13px; font-weight: 600;
        font-variant-numeric: tabular-nums; line-height: 1; color: #6b7c76; }
    .att-cal .att-day.is-present  { background: #d1fae5; color: #047857; }
    .att-cal .att-day.is-absent   { background: #fee2e2; color: #b91c1c; }
    .att-cal .att-day.is-half     { background: #fef3c7; color: #b45309; }
    .att-cal .att-day.is-holiday  { background: #e0e7ff; color: #4338ca; }
    .att-cal .att-day.is-unmarked { color: #95a39e; }
    .att-cal .att-day.is-out      { color: #d6dedb; font-weight: 500; }
    .att-cal .att-day.is-blank    { background: #fafbfb; }
    .att-key { display: inline-block; width: 10px; height: 10px; vertical-align: middle; border-radius: 0; }
</style>
@endonce

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

                    {{-- Sunday-first, as a table: the weekdays, blank cells before the
                         1st and after the last day so every week is a full row. --}}
                    @php $trail = (7 - (($m['lead'] + count($m['cells'])) % 7)) % 7; @endphp
                    <div class="att-cal">
                        @foreach (['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $dow)
                            <div class="att-dow">{{ $dow }}</div>
                        @endforeach

                        @for ($b = 0; $b < $m['lead']; $b++)
                            <div class="att-day is-blank"></div>
                        @endfor

                        @foreach ($m['cells'] as $d)
                            @if (!$d['in_period'])
                                <div class="att-day is-out">{{ $d['day'] }}</div>
                            @else
                                <div class="att-day {{ $dayCell[$d['status']] ?? $dayCell['not_marked'] }}"
                                    title="{{ \Carbon\Carbon::parse($d['date'])->format('D, d M Y') }} · {{ ucfirst(str_replace('_', ' ', $d['status'])) }}">{{ $d['day'] }}</div>
                            @endif
                        @endforeach

                        @for ($b = 0; $b < $trail; $b++)
                            <div class="att-day is-blank"></div>
                        @endfor
                    </div>
                </div>
            @empty
                <p class="sm:col-span-2 lg:col-span-3 text-sm text-gray-400 text-center py-6">Nothing to show for this period.</p>
            @endforelse
        </div>

        <div class="flex flex-wrap gap-x-3 gap-y-1 text-[11px] text-gray-400 mt-3">
            <span><span class="att-key" style="background:#d1fae5"></span> Present</span>
            <span><span class="att-key" style="background:#fee2e2"></span> Absent</span>
            <span><span class="att-key" style="background:#fef3c7"></span> Half day</span>
            <span><span class="att-key" style="background:#e0e7ff"></span> Holiday</span>
            <span><span class="att-key" style="background:#fff; box-shadow: inset 0 0 0 1px #e5eae8"></span> Not marked</span>
            <span class="text-gray-300">·</span>
            <span>Sundays are a standing holiday</span>
        </div>
    </div>
</div>
