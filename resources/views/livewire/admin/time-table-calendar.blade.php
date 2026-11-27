<div class="min-h-screen bg-gray-50 {{ $view === 'month' ? 'xl:h-dvh xl:min-h-[36rem] xl:flex xl:flex-col' : '' }}">

    {{-- ══════════════════════════════════════════════════
         HEADER — stays whole at the top while the page scrolls
         (data-lms-pin: the layout does not slide it away; there is no
         filter bar under it to pin instead)
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3 sticky top-0 z-50 flex-none" data-lms-pin>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">School Calendar</h1>
            </div>
            <div class="flex flex-wrap items-center gap-2">

                {{-- Analytics inline text --}}
                <div class="hidden sm:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                    <span class="pr-4">
                        Today: <strong class="text-gray-800">{{ $eventsCount['today'] }}</strong>
                    </span>
                    <span class="px-4">
                        This Week: <strong class="text-gray-800">{{ $eventsCount['this_week'] }}</strong>
                    </span>
                    <span class="px-4">
                        This Month: <strong class="text-gray-800">{{ $eventsCount['current_month'] }}</strong>
                    </span>
                    <span class="pl-4">
                        This Year: <strong class="text-gray-800">{{ $eventsCount['this_year'] }}</strong>
                    </span>
                </div>

                @if (!$readOnly)
                    <button wire:click="onAddEvent"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700
                               text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Event
                    </button>
                @endif
            </div>
        </div>

        {{-- Mobile analytics --}}
        <div class="flex sm:hidden items-center gap-4 text-xs text-gray-500 mt-3 flex-wrap">
            <span>Today: <strong class="text-gray-800">{{ $eventsCount['today'] }}</strong></span>
            <span>Week: <strong class="text-gray-800">{{ $eventsCount['this_week'] }}</strong></span>
            <span>Month: <strong class="text-gray-800">{{ $eventsCount['current_month'] }}</strong></span>
            <span>Year: <strong class="text-gray-800">{{ $eventsCount['this_year'] }}</strong></span>
        </div>
    </div>

    @include('livewire.super-admin.partials.dash-styles')

    {{-- The Super Admin's Calendar look (user's ask of 2026-11-27): the month on
         the left, every event of that month in a list on the right. A day shows
         its events as plain lines — a small dot in the event's colour, the time,
         the title — with no background behind them; as many as the day's height
         holds (a container query on the day), the last line "+N more". On a
         wide screen (xl+) the page is the window's height: the weeks share the
         month's height and the list scrolls on its own. --}}
    <style>
        .cal-day { container: calday / size; }
        .cal-ge3, .cal-ge4, .cal-l3 { display: none; }
        @container calday (min-height: 81px) {
            .cal-ge3, .cal-l3 { display: block; }
            .cal-l2 { display: none; }
        }
        @container calday (min-height: 99px) {
            .cal-ge4 { display: block; }
            .cal-l3 { display: none; }
        }
        .cal-list::-webkit-scrollbar { width: 6px; }
        .cal-list::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 3px; }
        .cal-list::-webkit-scrollbar-track { background: transparent; }
    </style>

    @php
        $isMonth = $view === 'month';
        $navBtn  = 'inline-flex items-center justify-center w-7 h-7 rounded-md border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 transition-colors';
        $hm      = fn ($t) => $t ? \Carbon\Carbon::createFromFormat('H:i', $t)->format('g:i') : '';
        $hmA     = fn ($t) => $t ? \Carbon\Carbon::createFromFormat('H:i', $t)->format('g:i A') : '';
        $sortKey = fn ($e) => \Carbon\Carbon::parse($e['date'])->format('Y-m-d') . ' ' . (($e['is_all_day'] ?? false) ? '00:00' : ($e['start_time'] ?? '99:99'));
        $monthList = $isMonth ? collect($events)->sortBy($sortKey)->values() : collect();
    @endphp

    <div class="px-4 sm:px-6 py-3 grid grid-cols-1 gap-4 items-start {{ $isMonth ? 'xl:grid-cols-[minmax(0,1fr)_21rem] xl:items-stretch xl:flex-1 xl:min-h-0 xl:grid-rows-[minmax(0,1fr)]' : '' }}">

        {{-- ═══════════ THE MONTH (or the year) — previous / next and Monthly / Yearly on the card ═══════════ --}}
        <div class="sa-card overflow-hidden flex flex-col {{ $isMonth ? 'xl:h-full xl:min-h-0' : '' }}" data-cal-month>
            <div class="flex-none flex items-center justify-between gap-2 px-3 py-2 border-b border-gray-100" data-cal-nav>
                <div class="flex items-center gap-1.5">
                    <button type="button" wire:click="{{ $isMonth ? 'goToPreviousMonth' : 'goToPreviousYear' }}"
                        title="{{ $isMonth ? 'Previous month' : 'Previous year' }}" aria-label="{{ $isMonth ? 'Previous month' : 'Previous year' }}" class="{{ $navBtn }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                    </button>
                    <h2 class="text-sm font-semibold text-gray-900 min-w-[7.5rem] text-center" wire:loading.class="opacity-60"
                        wire:target="goToPreviousMonth,goToNextMonth,goToPreviousYear,goToNextYear,goToCurrentMonth">{{ $isMonth ? $startsAt->format('F Y') : $startsAt->format('Y') }}</h2>
                    <button type="button" wire:click="{{ $isMonth ? 'goToNextMonth' : 'goToNextYear' }}"
                        title="{{ $isMonth ? 'Next month' : 'Next year' }}" aria-label="{{ $isMonth ? 'Next month' : 'Next year' }}" class="{{ $navBtn }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </button>
                    @if ($isMonth && !$startsAt->isSameMonth(now()))
                        <button type="button" wire:click="goToCurrentMonth" class="ml-1 h-7 px-2.5 text-xs font-medium rounded-md border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">Today</button>
                    @endif
                </div>
                <div class="flex items-center gap-1">
                    <button type="button" wire:click="switchToMonthlyView"
                        class="px-2.5 py-1 text-xs font-semibold rounded-md border transition-colors {{ $isMonth ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">Monthly</button>
                    <button type="button" wire:click="switchToYearlyView"
                        class="px-2.5 py-1 text-xs font-semibold rounded-md border transition-colors {{ !$isMonth ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">Yearly</button>
                </div>
            </div>

            @if ($isMonth)
                <div class="grid grid-cols-7 border-b border-gray-100 bg-gray-50/60 flex-none">
                    @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $wd)
                        <div class="py-1 text-center text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                            <span class="sm:hidden">{{ mb_substr($wd, 0, 1) }}</span><span class="hidden sm:inline">{{ $wd }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="flex flex-col xl:flex-1 xl:min-h-0">
                    @foreach ($monthGrid as $week)
                        <div class="grid grid-cols-7 xl:flex-1 xl:min-h-0 {{ !$loop->last ? 'border-b border-gray-100' : '' }}" wire:key="cal-week-{{ $week->first()->format('Y-m-d') }}">
                            @foreach ($week as $day)
                                @php
                                    $inMonth = $day->month == $startsAt->month;
                                    // Only the month's own days get events; the days of the months
                                    // either side stay empty.
                                    $items = $inMonth ? $getEventsForDay($day)->sortBy($sortKey)->values() : collect();
                                    $n     = $items->count();
                                @endphp
                                {{-- Read-only (accounts): a day does not open "add event", so it is not clickable. --}}
                                <div wire:key="cal-day-{{ $day->format('Y-m-d') }}"
                                    @if (!$readOnly) wire:click="onDayClick('{{ $day->year }}', '{{ $day->month }}', '{{ $day->day }}')" title="Add an event on {{ $day->format('d M') }}" @endif
                                    class="cal-day group relative min-h-[3.75rem] sm:min-h-[5.25rem] xl:min-h-0 p-1 sm:p-1.5 overflow-hidden transition-colors
                                           {{ !$loop->last ? 'border-r border-gray-100' : '' }}
                                           {{ $readOnly ? '' : 'cursor-pointer' }}
                                           {{ $inMonth ? ($readOnly ? 'bg-white' : 'bg-white hover:bg-gray-50') : 'bg-gray-50/70' }}">
                                    <div class="flex items-center justify-between">
                                        <span class="w-6 h-6 flex items-center justify-center rounded-full text-xs
                                                     {{ $day->isToday() ? 'bg-gray-900 text-white font-semibold' : ($inMonth ? 'text-gray-800' : 'text-gray-300') }}">{{ $day->day }}</span>
                                        @if (!$readOnly && $inMonth)
                                            <span class="hidden sm:group-hover:flex w-6 h-6 items-center justify-center rounded-md text-gray-400" aria-hidden="true">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" /></svg>
                                            </span>
                                        @endif
                                    </div>

                                    {{-- sm+: the events as plain lines, no background; how many show depends on
                                         the day's height (the style at the top): 2 lines, else 3, else 4. --}}
                                    <div class="hidden sm:block mt-0.5 space-y-px">
                                        @foreach ($items->take(3) as $i => $event)
                                            @php $fit = $i === 0 ? '' : ($i === 1 ? ($n <= 2 ? '' : 'cal-ge3') : ($n <= 3 ? 'cal-ge3' : 'cal-ge4')); @endphp
                                            <div wire:key="cal-ev-{{ $event['id'] }}" wire:click.stop="onEventClick('{{ $event['id'] }}')"
                                                title="{{ $event['title'] }}{{ ($event['start_time'] && !$event['is_all_day']) ? ' · ' . $hmA($event['start_time']) . ($event['end_time'] ? ' – ' . $hmA($event['end_time']) : '') : '' }}"
                                                class="cal-ev {{ $fit }} truncate text-[11px] leading-[15px] px-0.5 cursor-pointer {{ $event['is_completed'] ? 'text-gray-400' : 'text-gray-700 hover:text-gray-900' }}">
                                                <span class="inline-block w-1.5 h-1.5 rounded-full mr-1 align-middle -mt-px" style="background-color: {{ $event['color'] }}"></span>@if ($event['start_time'] && !$event['is_all_day'])<span class="font-semibold">{{ $hm($event['start_time']) }}</span> @endif{{ $event['title'] }}
                                            </div>
                                        @endforeach
                                        @if ($n > 2)
                                            <p class="cal-l2 text-[11px] leading-[15px] px-0.5 text-gray-500 font-medium">+{{ $n - 1 }} more</p>
                                        @endif
                                        @if ($n > 3)
                                            <p class="cal-l3 text-[11px] leading-[15px] px-0.5 text-gray-500 font-medium">+{{ $n - 2 }} more</p>
                                            <p class="cal-ge4 text-[11px] leading-[15px] px-0.5 text-gray-500 font-medium">+{{ $n - 3 }} more</p>
                                        @endif
                                    </div>

                                    {{-- phones: a dot for each (up to four) --}}
                                    @if ($n)
                                        <div class="sm:hidden mt-1 flex flex-wrap justify-center gap-0.5">
                                            @foreach ($items->take(4) as $event)
                                                <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $event['color'] }}"></span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Yearly View — month thumbnails with event indicators + total count --}}
                <div class="p-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($yearlyCalendar as $month)
                        <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md hover:border-gray-300 transition-all duration-200 cursor-pointer group"
                            wire:click="switchToMonthlyView('{{ $month['year'] }}', '{{ $month['month'] }}')">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="text-sm font-semibold text-gray-800">{{ $month['name'] }}</h3>
                                <span class="text-xs {{ $month['totalEvents'] > 0 ? 'text-gray-600 font-medium' : 'text-gray-400' }}">
                                    {{ $month['totalEvents'] }} {{ $month['totalEvents'] === 1 ? 'event' : 'events' }}
                                </span>
                            </div>
                            <div class="grid grid-cols-7 gap-0.5 text-center text-xs">
                                @foreach (['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $wd)
                                    <div class="font-medium text-gray-400 py-1">{{ $wd }}</div>
                                @endforeach
                                @foreach ($month['days'] as $d)
                                    <div class="py-1.5 rounded-lg relative {{ $d['isCurrentMonth'] ? 'text-gray-700' : 'text-gray-300' }} {{ $d['isToday'] ? 'bg-gray-900 text-white font-bold' : '' }}">
                                        {{ $d['day'] }}
                                        @if (($d['eventsCount'] ?? 0) > 0)
                                            <div class="absolute bottom-0.5 left-1/2 -translate-x-1/2 w-1 h-1 rounded-full {{ $d['isToday'] ? 'bg-white' : 'bg-gray-500' }}"></div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ═══════════ THE MONTH'S EVENTS — every one, in date order; scrolls on its own beside the month ═══════════ --}}
        @if ($isMonth)
            <div class="sa-card overflow-hidden flex flex-col xl:h-full xl:min-h-0" data-cal-list>
                <div class="flex-none px-4 py-2.5 border-b border-gray-100 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="sa-card-title">Events</p>
                        <p class="sa-card-sub">{{ $startsAt->format('F Y') }} · {{ $monthList->count() }} {{ $monthList->count() === 1 ? 'event' : 'events' }}</p>
                    </div>
                    @if (!$readOnly)
                        <button type="button" wire:click="onAddEvent"
                            class="px-2.5 py-1 text-xs font-medium rounded-md border border-gray-200 text-gray-700 hover:bg-gray-50 flex-shrink-0">+ Add</button>
                    @endif
                </div>
                <div class="cal-list divide-y divide-gray-100 xl:flex-1 xl:min-h-0 xl:overflow-y-auto">
                    @forelse ($monthList as $event)
                        @php
                            $date = \Carbon\Carbon::parse($event['date']);
                            $when = $event['is_all_day'] ? 'All day'
                                : ($event['start_time'] ? $hmA($event['start_time']) . ($event['end_time'] ? ' – ' . $hmA($event['end_time']) : '') : '');
                            $class = trim(($event['standard'] ?? '') . (($event['section'] ?? '') ? ' · ' . $event['section'] : ''));
                            $meta  = collect([$when, ucfirst($event['event_type'] ?? ''), $class, $event['location'] ?? ''])->filter()->implode(' · ');
                        @endphp
                        <button type="button" wire:key="cal-list-{{ $event['id'] }}" wire:click="onEventClick('{{ $event['id'] }}')"
                            class="w-full flex items-start gap-3 px-4 py-2.5 text-left hover:bg-gray-50 transition-colors">
                            <span class="w-10 flex-shrink-0 text-center leading-tight {{ $date->isToday() ? 'text-gray-900' : 'text-gray-500' }}">
                                <span class="block text-[10px] font-semibold uppercase tracking-wide">{{ $date->format('D') }}</span>
                                <span class="block text-lg font-semibold {{ $event['is_completed'] ? 'text-gray-400' : 'text-gray-900' }}">{{ $date->format('j') }}</span>
                            </span>
                            <span class="min-w-0 flex-1 pt-0.5">
                                <span class="flex items-center gap-1.5 min-w-0">
                                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background-color: {{ $event['color'] }}"></span>
                                    <span class="truncate text-sm font-medium {{ $event['is_completed'] ? 'text-gray-400' : 'text-gray-900' }}">{{ $event['title'] }}</span>
                                    @if ($event['is_completed'])
                                        <span class="flex-shrink-0 text-[10px] font-semibold uppercase tracking-wide text-gray-400">Done</span>
                                    @endif
                                </span>
                                @if ($meta !== '')
                                    <span class="block mt-0.5 text-xs text-gray-500 truncate">{{ $meta }}</span>
                                @endif
                            </span>
                        </button>
                    @empty
                        <div class="px-4 py-10 text-center">
                            <p class="text-sm text-gray-500">No events in {{ $startsAt->format('F') }}.</p>
                            @if (!$readOnly)
                                <button type="button" wire:click="onAddEvent" class="mt-2 text-sm font-medium text-blue-600 hover:text-blue-800">Add an event →</button>
                            @endif
                        </div>
                    @endforelse
                </div>
            </div>
        @endif
    </div>


    {{-- ══════════════════════════════════════════════════
         RIGHT-SIDE SLIDE-IN PANEL (Add / Edit / View Event)
    ══════════════════════════════════════════════════ --}}
    @if ($showSlider)
        <div class="fixed inset-x-0 bottom-0 top-16 z-[9999] overflow-hidden">
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeSlider"></div>

            {{-- Panel (anchored right, full height) --}}
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- ✅ Fixed header (always visible at top of panel) --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2.5 min-w-0">
                            @if (isset($sliderData['mode']) && $sliderData['mode'] === 'view')
                                <span class="block w-2 h-2 rounded-full flex-shrink-0"
                                    style="background-color: {{ $sliderData['event']['color'] ?? '#4363D8' }}"></span>
                            @endif
                            <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $sliderTitle }}</h2>
                        </div>
                        @if (!empty($sliderData['date']))
                            <p class="text-xs text-gray-500 mt-0.5">{{ \Carbon\Carbon::parse($sliderData['date'])->format('l, d M Y') }}</p>
                        @elseif (!empty($sliderData['event']['date']))
                            <p class="text-xs text-gray-500 mt-0.5">{{ \Carbon\Carbon::parse($sliderData['event']['date'])->format('l, d M Y') }}</p>
                        @endif
                    </div>
                    <button wire:click="closeSlider"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                @if (isset($sliderData['mode']) && $sliderData['mode'] === 'view')
                {{-- ✅ Scrollable body (only this section scrolls — header & footer stay fixed) --}}
                <div class="flex-1 overflow-y-auto px-6 py-6">

                        {{-- ══ VIEW MODE — same label/value layout as the Exams view panel ══ --}}
                        @php
                            $ev = $sliderData['event'];
                            $rows = [];

                            $rows['Title'] = $ev['title'] ?? '—';
                            $rows['Type']  = ucfirst($ev['event_type'] ?? 'event');
                            $rows['Date']  = \Carbon\Carbon::parse($ev['date'] ?? now())->format('l, d M Y');
                            $rows['Time']  = ($ev['is_all_day'] ?? false)
                                ? 'All Day'
                                : (($ev['start_time'] ?? null)
                                    ? $ev['start_time'] . ' – ' . ($ev['end_time'] ?? '')
                                    : '—');

                            if (!empty($ev['location']))  $rows['Location'] = $ev['location'];
                            if (!empty($ev['standard']))  $rows['Class']    = $ev['standard'];
                            if (!empty($ev['section']))   $rows['Section']  = $ev['section'];
                            if (!empty($ev['subject']))   $rows['Subject']  = $ev['subject'];
                            if (!empty($ev['teacher']))   $rows['Teacher']  = $ev['teacher'];
                        @endphp

                        {{-- The Students view's look: one column, a label / value row each. --}}
                        <div class="space-y-4">
                            @foreach ($rows as $label => $value)
                                <div class="grid grid-cols-3 gap-3 text-sm">
                                    <span class="text-xs text-gray-400 uppercase tracking-wider">{{ $label }}</span>
                                    <span class="col-span-2 text-gray-800 font-medium">{{ $value }}</span>
                                </div>
                            @endforeach

                            @if (!empty($ev['description']))
                                <div class="grid grid-cols-3 gap-3 text-sm">
                                    <span class="text-xs text-gray-400 uppercase tracking-wider">Description</span>
                                    <span class="col-span-2 text-gray-800 whitespace-pre-line leading-relaxed">{{ $ev['description'] }}</span>
                                </div>
                            @endif

                            @if (!empty($ev['attachment']))
                                <div class="grid grid-cols-3 gap-3 text-sm">
                                    <span class="text-xs text-gray-400 uppercase tracking-wider">Attachment</span>
                                    <span class="col-span-2">
                                        <a href="{{ $ev['attachment'] }}" target="_blank" rel="noopener"
                                            class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 hover:text-blue-800">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                            </svg>
                                            Open attachment
                                        </a>
                                    </span>
                                </div>
                            @endif
                        </div>
                </div>

                {{-- ✅ Fixed footer (always at bottom of panel) --}}
                <div class="flex items-center justify-between px-6 py-3.5 border-t border-gray-200 flex-shrink-0">
                        <p class="text-xs text-gray-400">
                            #{{ $sliderData['event']['id'] }}
                            @if ($sliderData['event']['is_completed'] ?? false)
                                <span class="ml-2 inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-600 uppercase tracking-wide text-[10px] font-semibold">
                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Completed
                                </span>
                            @endif
                        </p>
                        <div class="flex items-center gap-2">
                            @if (!$readOnly)
                            <button wire:click="onDeleteEvent({{ $sliderData['event']['id'] ?? 0 }})"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-red-200 hover:bg-red-50 text-red-600 text-sm font-medium rounded-md transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Delete
                            </button>
                            @if (!($sliderData['event']['is_completed'] ?? false))
                                <button wire:click="onEditEvent({{ $sliderData['event']['id'] ?? 0 }})"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit Event
                                </button>
                            @endif
                            @endif
                        </div>
                </div>
                @else
                    {{-- ══ ADD / EDIT MODE — the Livewire child form brings its own
                         scrolling body and the panel's footer: Cancel and
                         Create / Update Event side by side at the bottom ══ --}}
                    <livewire:admin.event-form
                        :date="$sliderData['date'] ?? null"
                        :event="$sliderData['event'] ?? null"
                        :mode="$sliderData['mode'] ?? 'create'" />
                @endif

            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         DELETE EVENT CONFIRMATION OVERLAY
    ══════════════════════════════════════════════════ --}}
    @if ($showDeleteEventConfirm)
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-[10000] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDeleteEvent"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Delete event?</h3>
                        <p class="text-sm text-gray-500">
                            This will permanently remove the event from the calendar. This action cannot be undone.
                        </p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button wire:click="cancelDeleteEvent"
                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="confirmDeleteEvent" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md disabled:opacity-60 flex items-center gap-1.5">
                        <span wire:loading.remove wire:target="confirmDeleteEvent">Delete</span>
                        <span wire:loading wire:target="confirmDeleteEvent">Deleting...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
