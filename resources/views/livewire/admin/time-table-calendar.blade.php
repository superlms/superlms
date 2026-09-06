<div>

    {{-- ══════════════════════════════════════════════════
         HEADER
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3 sticky top-0 z-50">
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

                <button wire:click="onAddEvent"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700
                           text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Event
                </button>
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

    <div class="p-6 space-y-6">

        {{-- ══════════════════════════════════════════════════
             FULL CALENDAR
        ══════════════════════════════════════════════════ --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

            {{-- Calendar Header --}}
            <div
                class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">

                {{-- View Toggle --}}
                <div class="flex gap-2">
                    <button wire:click="switchToMonthlyView"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-lg transition-all
                               {{ $view === 'month' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Monthly
                    </button>
                    <button wire:click="switchToYearlyView"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-lg transition-all
                               {{ $view === 'year' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        Yearly
                    </button>
                </div>

                {{-- Navigation --}}
                <div class="flex items-center gap-3">
                    @if ($view === 'month')
                        <button wire:click="goToPreviousMonth"
                            class="p-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <h2 class="text-base font-bold text-gray-800 min-w-40 text-center">
                            {{ $startsAt->format('F Y') }}
                        </h2>
                        <button wire:click="goToNextMonth"
                            class="p-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    @else
                        <button wire:click="goToPreviousYear"
                            class="p-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <h2 class="text-base font-bold text-gray-800 min-w-24 text-center">
                            {{ $startsAt->format('Y') }}
                        </h2>
                        <button wire:click="goToNextYear"
                            class="p-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Calendar Body --}}
            <div class="p-4">

                {{-- Monthly View --}}
                @if ($view === 'month')
                    <div class="rounded-xl overflow-hidden border border-gray-200">
                        <div class="grid grid-cols-7 bg-gray-50 border-b border-gray-200">
                            @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                                <div
                                    class="py-3 text-center text-sm font-semibold text-gray-600 border-r last:border-r-0 border-gray-200">
                                    {{ $day }}
                                </div>
                            @endforeach
                        </div>
                        <div class="grid grid-cols-7">
                            @foreach ($monthGrid as $week)
                                @foreach ($week as $day)
                                    @php
                                        $isCurrentMonth = $day->month == $startsAt->month;
                                        $isToday = $day->isToday();
                                        // Only the active month's cells get events — adjacent-month
                                        // fillers stay empty.
                                        $dayEvents = $isCurrentMonth ? $getEventsForDay($day) : [];
                                        $eventsCount = count($dayEvents);
                                    @endphp
                                    <div class="min-h-32 p-2 border border-gray-100 cursor-pointer transition-all duration-200
                                        {{ !$isCurrentMonth ? 'bg-gray-50/60' : 'bg-white hover:bg-blue-50/30' }}"
                                        wire:click="onDayClick('{{ $day->year }}', '{{ $day->month }}', '{{ $day->day }}')">

                                        <div class="flex justify-between items-center mb-1.5">
                                            <span
                                                class="text-sm font-medium leading-none
                                                {{ $isToday ? 'w-7 h-7 bg-blue-600 text-white rounded-full flex items-center justify-center' : ($isCurrentMonth ? 'text-gray-800' : 'text-gray-400') }}">
                                                {{ $day->day }}
                                            </span>
                                            @if ($eventsCount > 0)
                                                <span
                                                    class="text-xs px-1.5 py-0.5 rounded-full bg-blue-100 text-blue-700 font-medium">
                                                    {{ $eventsCount }}
                                                </span>
                                            @endif
                                        </div>

                                        <div class="space-y-1 max-h-24 overflow-y-auto">
                                            @foreach ($dayEvents as $event)
                                                <div class="text-xs px-2 py-1 rounded-lg border-l-4 truncate cursor-pointer hover:opacity-90 transition-opacity"
                                                    style="border-left-color: {{ $event['color'] }}; background-color: {{ $event['color'] }}18;"
                                                    wire:click.stop="onEventClick('{{ $event['id'] }}')"
                                                    title="{{ $event['title'] }}">
                                                    <div class="font-medium text-gray-800 truncate">
                                                        {{ $event['title'] }}</div>
                                                    @if ($event['start_time'] && !$event['is_all_day'])
                                                        <div class="text-gray-500 text-xs">{{ $event['start_time'] }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Yearly View — month thumbnails with event indicators + total count --}}
                @if ($view === 'year')
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($yearlyCalendar as $month)
                            <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md hover:border-blue-200
                                transition-all duration-200 cursor-pointer group"
                                wire:click="switchToMonthlyView('{{ $month['year'] }}', '{{ $month['month'] }}')">
                                <div class="flex justify-between items-center mb-3">
                                    <h3 class="text-sm font-semibold text-gray-800 group-hover:text-blue-600 transition-colors">
                                        {{ $month['name'] }}
                                    </h3>
                                    <div class="flex items-center gap-2">
                                        @if ($month['totalEvents'] > 0)
                                            <span class="text-xs px-2 py-0.5 bg-blue-50 text-blue-700 rounded-full font-medium">
                                                {{ $month['totalEvents'] }} {{ $month['totalEvents'] === 1 ? 'event' : 'events' }}
                                            </span>
                                        @else
                                            <span class="text-xs px-2 py-0.5 bg-gray-50 text-gray-400 rounded-full">0 events</span>
                                        @endif
                                        <svg class="w-4 h-4 text-gray-300 group-hover:text-blue-500 transition-colors"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="grid grid-cols-7 gap-0.5 text-center text-xs">
                                    @foreach (['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $day)
                                        <div class="font-medium text-gray-400 py-1">{{ $day }}</div>
                                    @endforeach
                                    @foreach ($month['days'] as $day)
                                        <div class="py-1.5 rounded-lg relative transition-colors
                                            {{ $day['isCurrentMonth'] ? 'text-gray-700 hover:bg-blue-50' : 'text-gray-300' }}
                                            {{ $day['isToday'] ? 'bg-blue-100 text-blue-700 font-bold' : '' }}">
                                            {{ $day['day'] }}
                                            @if (($day['eventsCount'] ?? 0) > 0)
                                                <div class="absolute bottom-0.5 left-1/2 -translate-x-1/2 w-1 h-1 bg-blue-500 rounded-full"></div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════
             EVENTS — Upcoming + Completed (monthly view only)
        ══════════════════════════════════════════════════ --}}
        @if ($view === 'month')
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

            {{-- UPCOMING --}}
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900">
                    Upcoming Events
                    <span class="ml-1 text-xs text-gray-400 font-normal">— {{ $startsAt->format('F Y') }}</span>
                </h3>
                <span class="text-xs text-gray-400">{{ count($upcomingEvents) }} {{ count($upcomingEvents) === 1 ? 'event' : 'events' }}</span>
            </div>

            @if (count($upcomingEvents) > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-6">
                    @foreach ($upcomingEvents as $event)
                        @include('livewire.admin._event-card', ['event' => $event, 'completed' => false])
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <p class="text-gray-500 text-sm mb-3">No upcoming events scheduled</p>
                    <button wire:click="onAddEvent"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700
                               text-white text-sm font-semibold rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Upcoming Event
                    </button>
                </div>
            @endif

            {{-- DIVIDER + COMPLETED --}}
            @if (count($completedEvents) > 0)
                <div class="px-6 pt-4 pb-3 border-t border-gray-200 bg-gray-50/60">
                    <div class="flex items-center gap-3">
                        <span class="h-px bg-gray-200 flex-1"></span>
                        <div class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            Completed
                            <span class="text-gray-400 font-normal">· {{ count($completedEvents) }}</span>
                        </div>
                        <span class="h-px bg-gray-200 flex-1"></span>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-6 pt-3">
                    @foreach ($completedEvents as $event)
                        @include('livewire.admin._event-card', ['event' => $event, 'completed' => true])
                    @endforeach
                </div>
            @endif
        </div>
        @endif {{-- end view === 'month' --}}

    </div>


    {{-- ══════════════════════════════════════════════════
         RIGHT-SIDE SLIDE-IN PANEL (Add / Edit / View Event)
    ══════════════════════════════════════════════════ --}}
    @if ($showSlider)
        <div class="fixed inset-0 z-[9999] overflow-hidden">
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeSlider"></div>

            {{-- Panel (anchored right, full height) --}}
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- ✅ Fixed header (always visible at top of panel) --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0 bg-white">
                    <div class="flex items-center gap-2.5 min-w-0">
                        @if (isset($sliderData['mode']) && $sliderData['mode'] === 'view')
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
                                    <span class="col-span-2 text-gray-800 font-medium whitespace-pre-line leading-relaxed">
                                        {{ $ev['description'] }}
                                    </span>
                                </div>
                            @endif

                            @if (!empty($ev['attachment']))
                                <div class="grid grid-cols-3 gap-3 text-sm">
                                    <span class="text-xs text-gray-400 uppercase tracking-wider">Attachment</span>
                                    <span class="col-span-2">
                                        <a href="{{ $ev['attachment'] }}" target="_blank" rel="noopener"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border border-blue-200 bg-blue-50 text-xs font-medium text-blue-700 hover:bg-blue-100">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                            </svg>
                                            Open attachment
                                        </a>
                                    </span>
                                </div>
                            @endif
                        </div>
                    @else
                        {{-- ══ ADD / EDIT MODE — Livewire child form ══ --}}
                        <livewire:admin.event-form
                            :date="$sliderData['date'] ?? null"
                            :event="$sliderData['event'] ?? null"
                            :mode="$sliderData['mode'] ?? 'create'" />
                    @endif

                </div>

                {{-- ✅ Fixed footer (always at bottom of panel) --}}
                <div class="flex items-center justify-between px-6 py-3.5 border-t border-gray-200 flex-shrink-0 bg-white">
                    @if (isset($sliderData['mode']) && $sliderData['mode'] === 'view')
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
                        </div>
                    @else
                        <button wire:click="closeSlider"
                            class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md transition-colors">
                            Cancel
                        </button>
                        {{-- Save button rendered by child event-form via emit --}}
                    @endif
                </div>

            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         DELETE EVENT CONFIRMATION OVERLAY
    ══════════════════════════════════════════════════ --}}
    @if ($showDeleteEventConfirm)
        <div class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
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
