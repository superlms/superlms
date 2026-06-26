<div>

    {{-- ══════════════════════════════════════════════════
         HEADER
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 px-6 py-5 sticky top-0 z-50">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Calendar</h1>
                <p class="text-sm text-gray-500 mt-0.5">View your academic schedule and events</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">

                {{-- Analytics inline --}}
                <div class="hidden sm:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                    <span class="pr-4">
                        Today: <strong class="text-gray-800">{{ $this->eventsCount['today'] }}</strong>
                    </span>
                    <span class="px-4">
                        This Week: <strong class="text-gray-800">{{ $this->eventsCount['this_week'] }}</strong>
                    </span>
                    <span class="px-4">
                        This Month: <strong class="text-gray-800">{{ $this->eventsCount['this_month'] }}</strong>
                    </span>
                    <span class="pl-4">
                        This Year: <strong class="text-gray-800">{{ $this->eventsCount['this_year'] }}</strong>
                    </span>
                </div>

                <button wire:click="goToCurrentMonth"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-50 hover:bg-emerald-100
                           text-emerald-700 text-sm font-semibold rounded-lg transition-colors border border-emerald-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Today
                </button>
            </div>
        </div>

        {{-- Mobile analytics --}}
        <div class="flex sm:hidden items-center gap-4 text-xs text-gray-500 mt-3 flex-wrap">
            <span>Today: <strong class="text-gray-800">{{ $this->eventsCount['today'] }}</strong></span>
            <span>Week: <strong class="text-gray-800">{{ $this->eventsCount['this_week'] }}</strong></span>
            <span>Month: <strong class="text-gray-800">{{ $this->eventsCount['this_month'] }}</strong></span>
            <span>Year: <strong class="text-gray-800">{{ $this->eventsCount['this_year'] }}</strong></span>
        </div>
    </div>

    <div class="p-6 space-y-6">

        {{-- ══════════════════════════════════════════════════
             CALENDAR GRID
        ══════════════════════════════════════════════════ --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

            {{-- Calendar Navigation Header --}}
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <button wire:click="goToPreviousMonth"
                        class="p-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <h2 class="text-base font-bold text-gray-800 min-w-40 text-center">
                        {{ $this->monthLabel }}
                    </h2>
                    <button wire:click="goToNextMonth"
                        class="p-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                {{-- Event type legend --}}
                <div class="hidden md:flex items-center gap-4 text-xs text-gray-500">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Class
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> Exam
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Meeting
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Event
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-violet-500"></span> Holiday
                    </span>
                </div>
            </div>

            {{-- Calendar Grid --}}
            <div class="p-4">
                <div class="rounded-xl overflow-hidden border border-gray-200">
                    {{-- Day Headers --}}
                    <div class="grid grid-cols-7 bg-gray-50 border-b border-gray-200">
                        @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                            <div class="py-3 text-center text-sm font-semibold text-gray-600 border-r last:border-r-0 border-gray-200">
                                {{ $day }}
                            </div>
                        @endforeach
                    </div>

                    {{-- Week Rows --}}
                    <div class="grid grid-cols-7">
                        @foreach ($this->calendarGrid as $week)
                            @foreach ($week as $day)
                                <div
                                    class="min-h-32 p-2 border border-gray-100 transition-all duration-200
                                        {{ !$day['isCurrentMonth'] ? 'bg-gray-50/60' : 'bg-white' }}
                                        {{ $day['eventCount'] > 0 ? 'cursor-pointer hover:bg-emerald-50/30' : '' }}"
                                    @if ($day['eventCount'] > 0)
                                        wire:click="viewDayEvents('{{ $day['date'] }}')"
                                    @endif
                                >
                                    <div class="flex justify-between items-center mb-1.5">
                                        <span class="text-sm font-medium leading-none
                                            {{ $day['isToday']
                                                ? 'w-7 h-7 bg-emerald-600 text-white rounded-full flex items-center justify-center'
                                                : ($day['isCurrentMonth'] ? 'text-gray-800' : 'text-gray-400') }}">
                                            {{ $day['day'] }}
                                        </span>
                                        @if ($day['eventCount'] > 0)
                                            <span class="text-xs px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-medium">
                                                {{ $day['eventCount'] }}
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Event indicators --}}
                                    <div class="space-y-1 max-h-24 overflow-y-auto">
                                        @foreach ($day['events'] as $event)
                                            <div
                                                class="text-xs px-2 py-1 rounded-lg border-l-4 truncate cursor-pointer hover:opacity-90 transition-opacity"
                                                style="border-left-color: {{ $event['color'] }}; background-color: {{ $event['color'] }}18;"
                                                wire:click.stop="viewEvent({{ $event['id'] }})"
                                                title="{{ $event['title'] }}"
                                            >
                                                <div class="font-medium text-gray-800 truncate">{{ $event['title'] }}</div>
                                                @if ($event['start_time'] && !$event['is_all_day'])
                                                    <div class="text-gray-500 text-xs">{{ $event['start_time'] }}</div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════
             UPCOMING EVENTS
        ══════════════════════════════════════════════════ --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Upcoming Events</h3>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $this->monthLabel }}</p>
                </div>
                <span class="text-xs text-gray-400">{{ count($this->upcomingEvents) }} events</span>
            </div>

            @if (count($this->upcomingEvents) > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-6">
                    @foreach ($this->upcomingEvents as $event)
                        <div
                            class="group relative bg-gray-50 border border-gray-200 rounded-xl p-4
                                   hover:border-emerald-300 hover:shadow-md transition-all duration-200 cursor-pointer"
                            wire:click="viewEvent({{ $event['id'] }})"
                        >
                            {{-- Color bar --}}
                            <div class="absolute top-0 left-0 w-1 h-full rounded-l-xl"
                                style="background-color: {{ $event['color'] }}"></div>

                            <div class="pl-3">
                                {{-- Title + dot --}}
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <h4 class="font-semibold text-gray-900 text-sm group-hover:text-emerald-600
                                               transition-colors leading-snug line-clamp-1">
                                        {{ $event['title'] }}
                                    </h4>
                                    <div class="w-2.5 h-2.5 rounded-full flex-shrink-0 mt-0.5"
                                        style="background-color: {{ $event['color'] }}"></div>
                                </div>

                                {{-- Description --}}
                                @if (!empty($event['description']))
                                    <p class="text-xs text-gray-500 leading-relaxed line-clamp-2 mb-2">
                                        {{ $event['description'] }}
                                    </p>
                                @endif

                                {{-- Date --}}
                                <div class="flex items-center gap-1.5 text-xs text-gray-500 mb-1">
                                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ $event['date_formatted'] }}

                                    @if ($event['start_time'] && !$event['is_all_day'])
                                        <span class="text-gray-300 mx-1">&bull;</span>
                                        <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ $event['start_time'] }}
                                    @elseif($event['is_all_day'])
                                        <span class="text-gray-300 mx-1">&bull;</span>
                                        <span class="text-xs px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full">All Day</span>
                                    @endif
                                </div>

                                {{-- Badges --}}
                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    @if (!empty($event['location']))
                                        <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            </svg>
                                            {{ $event['location'] }}
                                        </span>
                                    @endif
                                    @if (!empty($event['class']))
                                        <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            {{ $event['class'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-16">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <p class="text-gray-500 text-sm">No upcoming events in {{ $this->monthLabel }}</p>
                </div>
            @endif
        </div>

        {{-- ══════════════════════════════════════════════════
             COMPLETED EVENTS
        ══════════════════════════════════════════════════ --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Completed Events</h3>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $this->monthLabel }}</p>
                </div>
                <span class="text-xs text-gray-400">{{ count($this->completedEvents) }} events</span>
            </div>

            @if (count($this->completedEvents) > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-6">
                    @foreach ($this->completedEvents as $event)
                        <div
                            class="group relative bg-gray-50 border border-gray-200 rounded-xl p-4 opacity-80
                                   hover:opacity-100 hover:border-gray-300 hover:shadow-md transition-all duration-200 cursor-pointer"
                            wire:click="viewEvent({{ $event['id'] }})"
                        >
                            {{-- Color bar --}}
                            <div class="absolute top-0 left-0 w-1 h-full rounded-l-xl"
                                style="background-color: {{ $event['color'] }}"></div>

                            <div class="pl-3">
                                {{-- Title + completed tag --}}
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <h4 class="font-semibold text-gray-700 text-sm group-hover:text-gray-900
                                               transition-colors leading-snug line-clamp-1">
                                        {{ $event['title'] }}
                                    </h4>
                                    <span class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 bg-gray-200 text-gray-600 rounded-full flex-shrink-0">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Done
                                    </span>
                                </div>

                                {{-- Description --}}
                                @if (!empty($event['description']))
                                    <p class="text-xs text-gray-500 leading-relaxed line-clamp-2 mb-2">
                                        {{ $event['description'] }}
                                    </p>
                                @endif

                                {{-- Date --}}
                                <div class="flex items-center gap-1.5 text-xs text-gray-500 mb-1">
                                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ $event['date_formatted'] }}

                                    @if ($event['start_time'] && !$event['is_all_day'])
                                        <span class="text-gray-300 mx-1">&bull;</span>
                                        <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ $event['start_time'] }}
                                    @elseif($event['is_all_day'])
                                        <span class="text-gray-300 mx-1">&bull;</span>
                                        <span class="text-xs px-2 py-0.5 bg-gray-200 text-gray-600 rounded-full">All Day</span>
                                    @endif
                                </div>

                                {{-- Badges --}}
                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    @if (!empty($event['location']))
                                        <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            </svg>
                                            {{ $event['location'] }}
                                        </span>
                                    @endif
                                    @if (!empty($event['class']))
                                        <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            {{ $event['class'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-16">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="text-gray-500 text-sm">No completed events in {{ $this->monthLabel }}</p>
                </div>
            @endif
        </div>

    </div>

    {{-- ══════════════════════════════════════════════════
         EVENT DETAIL — SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($showEventModal && $selectedEvent)
        <div class="fixed inset-0 z-[9999] overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeEventModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div><h2 class="text-lg font-semibold text-gray-900">Event Details</h2></div>
                    <button wire:click="closeEventModal"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body (admissions template) --}}
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">

                    {{-- Title + chips --}}
                    <div class="flex items-start gap-3 pr-8">
                        <span class="w-3 h-3 rounded-full mt-2 flex-shrink-0"
                            style="background-color: {{ $selectedEvent['color'] ?? '#10b981' }}"></span>
                        <div class="min-w-0">
                            <h2 class="text-lg font-semibold text-gray-900 leading-snug">{{ $selectedEvent['title'] }}</h2>
                            <div class="flex flex-wrap gap-2 mt-2">
                                <span class="inline-flex items-center text-xs px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-full border border-emerald-100">
                                    {{ ucfirst($selectedEvent['event_type'] ?? 'Event') }}
                                </span>
                                @if ($selectedEvent['is_all_day'] ?? false)
                                    <span class="inline-flex items-center text-xs px-2.5 py-1 bg-green-50 text-green-700 rounded-full border border-green-100">All Day</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Date & Time --}}
                    <div class="grid grid-cols-2 gap-4 border-t border-gray-100 pt-5">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Date</p>
                            <p class="text-sm font-medium text-gray-800">{{ \Carbon\Carbon::parse($selectedEvent['date'])->format('l, d M Y') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Time</p>
                            <p class="text-sm font-medium text-gray-800">
                                @if ($selectedEvent['is_all_day'] ?? false)
                                    All Day
                                @elseif (!empty($selectedEvent['start_time']))
                                    {{ $selectedEvent['start_time'] }}@if (!empty($selectedEvent['end_time'])) &ndash; {{ $selectedEvent['end_time'] }}@endif
                                @else
                                    &mdash;
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- Description --}}
                    @if (!empty($selectedEvent['description']))
                        <div class="border-t border-gray-100 pt-5">
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">Description</p>
                            <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $selectedEvent['description'] }}</p>
                        </div>
                    @endif

                    {{-- Location --}}
                    @if (!empty($selectedEvent['location']))
                        <div class="border-t border-gray-100 pt-5">
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">Location</p>
                            <p class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-800">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                {{ $selectedEvent['location'] }}
                            </p>
                        </div>
                    @endif

                    {{-- Class Info --}}
                    @if (!empty($selectedEvent['standard']) || !empty($selectedEvent['subject']) || !empty($selectedEvent['teacher']))
                        <div class="border-t border-gray-100 pt-5">
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-3">Class Information</p>
                            <div class="space-y-3">
                                @if (!empty($selectedEvent['standard']))
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-400">Class</span>
                                        <span class="text-sm font-medium text-gray-800">{{ $selectedEvent['standard'] }}@if (!empty($selectedEvent['section'])) &ndash; {{ $selectedEvent['section'] }}@endif</span>
                                    </div>
                                @endif
                                @if (!empty($selectedEvent['subject']))
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-400">Subject</span>
                                        <span class="text-sm font-medium text-gray-800">{{ $selectedEvent['subject'] }}</span>
                                    </div>
                                @endif
                                @if (!empty($selectedEvent['teacher']))
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-400">Teacher</span>
                                        <span class="text-sm font-medium text-gray-800">{{ $selectedEvent['teacher'] }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-between flex-shrink-0">
                    <p class="text-xs text-gray-400">Event #{{ $selectedEvent['id'] }}</p>
                    <button wire:click="closeEventModal" class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md">Close</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         DAY EVENTS MODAL
    ══════════════════════════════════════════════════ --}}
    @if ($showDayEventsModal && count($selectedDayEvents) > 0)
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[9999] flex items-center justify-center p-4 sm:p-6"
            wire:click.self="closeDayEventsModal">

            <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg flex flex-col max-h-[90vh]">

                {{-- Modal Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Events</h3>
                        <p class="text-sm text-gray-500">{{ $selectedDayDate }}</p>
                    </div>
                    <button wire:click="closeDayEventsModal"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400
                               hover:text-gray-600 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Events List --}}
                <div class="overflow-y-auto flex-1 p-4 space-y-3">
                    @foreach ($selectedDayEvents as $event)
                        <div
                            class="group relative bg-gray-50 border border-gray-200 rounded-xl p-4
                                   hover:border-emerald-300 hover:shadow-sm transition-all duration-200 cursor-pointer"
                            wire:click="viewEvent({{ $event['id'] }})"
                        >
                            <div class="absolute top-0 left-0 w-1 h-full rounded-l-xl"
                                style="background-color: {{ $event['color'] }}"></div>

                            <div class="pl-3">
                                <div class="flex items-start justify-between gap-2 mb-1">
                                    <h4 class="font-semibold text-gray-900 text-sm group-hover:text-emerald-600 transition-colors">
                                        {{ $event['title'] }}
                                    </h4>
                                    <span class="text-xs px-2 py-0.5 rounded-full border whitespace-nowrap"
                                        style="background-color: {{ $event['color'] }}18; border-color: {{ $event['color'] }}40; color: {{ $event['color'] }};">
                                        {{ ucfirst($event['event_type'] ?? 'event') }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-3 text-xs text-gray-500 mt-1.5">
                                    @if ($event['is_all_day'])
                                        <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full">All Day</span>
                                    @elseif (!empty($event['start_time']))
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            {{ $event['start_time'] }}
                                            @if (!empty($event['end_time']))
                                                &ndash; {{ $event['end_time'] }}
                                            @endif
                                        </span>
                                    @endif

                                    @if (!empty($event['location']))
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            </svg>
                                            {{ $event['location'] }}
                                        </span>
                                    @endif

                                    @if (!empty($event['class']))
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            {{ $event['class'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Modal Footer --}}
                <div class="flex items-center justify-end px-6 py-4 border-t border-gray-200 flex-shrink-0 bg-white rounded-b-xl">
                    <button wire:click="closeDayEventsModal"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition-colors">
                        Close
                    </button>
                </div>

            </div>
        </div>
    @endif

</div>
