{{-- Attendance as small month calendars — the payroll screen's card layout.
     Expects $cards = ['counts' => [...], 'months' => [ym => ['label','lead','cells','counts','pct']]]
     plus $title (period label) and, optionally, $person (whose attendance this is). --}}
@php
    $person = $person ?? '';
    $c = $cards['counts'];
    $dayCell = [
        'present'    => 'bg-emerald-500 text-white',
        'absent'     => 'bg-red-500 text-white',
        'half_day'   => 'bg-yellow-300 text-yellow-900',
        'holiday'    => 'bg-indigo-100 text-indigo-700',
        'not_marked' => 'bg-white text-gray-400 border border-gray-200',
    ];
@endphp

<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 bg-gradient-to-r from-purple-50 to-indigo-50 flex flex-wrap items-center justify-between gap-2">
        <div>
            <h3 class="text-sm font-semibold text-gray-700">
                {{ $person ?: 'Attendance' }}
                <span class="font-normal text-gray-400">· {{ $title }}</span>
            </h3>
            <p class="text-[11px] text-gray-400">Sundays are a standing holiday.</p>
        </div>
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px]">
            <span class="text-emerald-700">Present <strong>{{ $c['present'] }}</strong></span>
            <span class="text-red-700">Absent <strong>{{ $c['absent'] }}</strong></span>
            <span class="text-yellow-700">Half <strong>{{ $c['half_day'] }}</strong></span>
            <span class="text-indigo-700">Holiday <strong>{{ $c['holiday'] }}</strong></span>
            <span class="text-gray-500">Working <strong>{{ $c['working'] }}</strong></span>
            <span class="px-2 py-0.5 rounded-full bg-white border border-indigo-200 font-semibold text-indigo-600">{{ $c['percent'] }}%</span>
        </div>
    </div>

    <div class="p-4">
        {{-- Each month is its own small calendar carrying its own numbers, so a
             single month reads the same as a whole year laid out 3 to a row. --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @forelse ($cards['months'] as $ym => $m)
                @php $mc = $m['counts']; @endphp
                <div class="rounded-lg border border-gray-200 p-3" wire:key="attcal-{{ $ym }}">
                    <div class="flex items-baseline justify-between mb-2">
                        <p class="text-xs font-semibold text-gray-800">{{ $m['label'] }}</p>
                        <span class="text-[11px] font-semibold text-indigo-600">{{ $m['pct'] }}%</span>
                    </div>

                    {{-- Sunday-first weekday header --}}
                    <div class="grid grid-cols-7 gap-1 mb-1">
                        @foreach (['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $dow)
                            <div class="text-center text-[9px] font-semibold text-gray-400">{{ $dow }}</div>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-7 gap-1">
                        {{-- Blanks before the 1st so the weekdays line up --}}
                        @for ($b = 0; $b < $m['lead']; $b++)
                            <div></div>
                        @endfor

                        @foreach ($m['cells'] as $d)
                            @if (!$d['in_period'])
                                <div class="rounded py-1 text-center text-[10px] text-gray-300">{{ $d['day'] }}</div>
                            @else
                                <div class="rounded py-1 text-center text-[10px] font-semibold leading-none {{ $dayCell[$d['status']] ?? $dayCell['not_marked'] }}"
                                    title="{{ \Carbon\Carbon::parse($d['date'])->format('D, d M Y') }} · {{ ucfirst(str_replace('_', ' ', $d['status'])) }}">
                                    {{ $d['day'] }}
                                </div>
                            @endif
                        @endforeach
                    </div>

                    {{-- That month's analytics --}}
                    <div class="flex flex-wrap gap-x-2 gap-y-0.5 mt-2.5 pt-2 border-t border-gray-100 text-[10px]">
                        <span class="text-emerald-700">P <strong>{{ $mc['present'] }}</strong></span>
                        <span class="text-red-700">A <strong>{{ $mc['absent'] }}</strong></span>
                        <span class="text-yellow-700">H <strong>{{ $mc['half_day'] }}</strong></span>
                        <span class="text-indigo-700">Hol <strong>{{ $mc['holiday'] }}</strong></span>
                        <span class="text-gray-400">NM <strong>{{ $mc['not_marked'] }}</strong></span>
                    </div>
                </div>
            @empty
                <p class="sm:col-span-2 lg:col-span-3 text-sm text-gray-400 text-center py-6">Nothing to show for this period.</p>
            @endforelse
        </div>

        <div class="flex flex-wrap gap-x-3 gap-y-1 text-[11px] text-gray-500 mt-3">
            <span><span class="inline-block w-2.5 h-2.5 rounded-sm bg-emerald-500 align-middle"></span> Present</span>
            <span><span class="inline-block w-2.5 h-2.5 rounded-sm bg-red-500 align-middle"></span> Absent</span>
            <span><span class="inline-block w-2.5 h-2.5 rounded-sm bg-yellow-300 align-middle"></span> Half day</span>
            <span><span class="inline-block w-2.5 h-2.5 rounded-sm bg-indigo-100 border border-indigo-200 align-middle"></span> Holiday</span>
            <span><span class="inline-block w-2.5 h-2.5 rounded-sm bg-white border border-gray-300 align-middle"></span> Not marked</span>
        </div>
    </div>
</div>
