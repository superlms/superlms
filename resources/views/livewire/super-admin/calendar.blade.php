{{-- Super Admin → Calendar (App\Livewire\SuperAdmin\Calendar), two tabs:
     Calendar — the signed-in person's own meetings, tasks and events (nobody
     else's are ever shown); Queries — the website's demo requests with when they
     came and their status (to whoever may open Website Enquiries). --}}
@php
    $isCal = $tab === 'calendar';
    $chipCls = [
        'meeting' => 'bg-blue-50 text-blue-700 hover:bg-blue-100',
        'task'    => 'bg-amber-50 text-amber-800 hover:bg-amber-100',
        'event'   => 'bg-violet-50 text-violet-700 hover:bg-violet-100',
    ];
    $dotCls = [
        'meeting' => 'bg-blue-500',
        'task'    => 'bg-amber-500',
        'event'   => 'bg-violet-500',
    ];
    $placeholders = [
        'meeting' => 'e.g. Fee review with Doon School',
        'task'    => 'e.g. Send the fee reminders',
        'event'   => 'e.g. Annual function',
    ];
    $statusPill = [
        'pending'   => 'bg-amber-50 text-amber-700 ring-amber-200',
        'booked'    => 'bg-blue-50 text-blue-700 ring-blue-200',
        'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'cancelled' => 'bg-gray-100 text-gray-500 ring-gray-200',
    ];
    $gear = 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z';
    $sel = 'text-xs min-w-0 max-w-[11rem] bg-white border border-gray-200 rounded-md px-2 py-1 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500';
    $input = 'w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500';
@endphp

{{-- On a wide screen (xl+) the Calendar tab fits the window: the page is the
     window's height, the header takes what it needs and the month and the lists
     share the rest — the month's weeks split its height, the lists scroll on
     their own. Below that, or on a very short window, the page scrolls. --}}
<div class="min-h-screen bg-gray-50 {{ $isCal ? 'xl:h-dvh xl:min-h-[36rem] xl:flex xl:flex-col' : '' }}"
    x-data x-on:copy-text.window="navigator.clipboard && navigator.clipboard.writeText($event.detail.text)">
    @include('livewire.super-admin.partials.dash-styles')

    {{-- Each day of the month shows as many lines as its height holds — two on a
         short day, three or four on a taller one — the last of them "+N more" when
         the rest do not fit (a container query on the day's own height; the chips
         past those lines are hidden, not dropped). --}}
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
        .cal-lists::-webkit-scrollbar { width: 6px; }
        .cal-lists::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 3px; }
        .cal-lists::-webkit-scrollbar-track { background: transparent; }
    </style>

    {{-- ══════════ HEADER in the Fees look: the title, the figures in one line and
         the blue button; the tabs; then the filter bar ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-40 flex-none">
        @if ($isCal)
            @include('livewire.super-admin.partials.list-header', [
                'title' => 'Calendar',
                'stats' => [
                    ['Meetings today', $stats['meetings'], 'text-blue-600', null],
                    ['Tasks done', $stats['tasksDone'] . '/' . $stats['tasks'], 'text-amber-700', null],
                    ['Events today', $stats['events'], 'text-violet-600', null],
                    ['This month', $stats['month'], 'text-gray-800', null],
                ],
                'addLabel' => 'Add New', 'addAction' => "openCreate('{$day}', 'meeting')",
            ])
        @else
            @include('livewire.super-admin.partials.list-header', [
                'title' => 'Calendar',
                'stats' => [
                    ['Queries', number_format($queryCounts['total']), 'text-gray-800', null],
                    ['Pending', number_format($queryCounts['pending']), 'text-amber-600', null],
                    ['Upcoming demos', number_format($queryCounts['upcoming']), 'text-blue-600', null],
                    ['Completed', number_format($queryCounts['completed']), 'text-emerald-600', null],
                    ['This month', number_format($queryCounts['month']), 'text-gray-800', null],
                ],
                'addLabel' => 'Booking Settings', 'addAction' => 'openSettings', 'addIcon' => $gear,
            ])
        @endif

        {{-- TABS --}}
        @if ($canSeeQueries)
            <div class="border-t border-gray-200 px-4 sm:px-6 flex items-center gap-1" role="tablist">
                @foreach (['calendar' => 'Calendar', 'queries' => 'Queries'] as $key => $label)
                    <button type="button" wire:click="switchTab('{{ $key }}')" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                        class="px-3 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors {{ $tab === $key ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        <span class="inline-flex items-center gap-1.5">
                            @if ($key === 'calendar')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                            @endif
                            {{ $label }}
                            @if ($key === 'queries' && $queryCounts['pending'] > 0)
                                <span class="ml-0.5 text-[11px] font-semibold px-1.5 py-px rounded-full {{ $tab === 'queries' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }}" title="Pending">{{ $queryCounts['pending'] }}</span>
                            @endif
                        </span>
                    </button>
                @endforeach
            </div>
        @endif

        {{-- FILTER BAR — as on Fees --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-2" data-filter-bar>
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-1 text-xs font-semibold text-gray-600">
                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter by:
                </div>

                @if ($isCal)
                    <div class="relative flex-[1_1_8rem] min-w-0 max-w-52">
                        <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" /></svg>
                        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search title, people, place..."
                            class="text-xs bg-white border border-gray-200 rounded-md pl-7 pr-2.5 py-1 text-gray-700 w-full focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <select wire:model.live="typeFilter" class="{{ $sel }}" aria-label="Type">
                        <option value="">All Types</option>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}">{{ $label }}s</option>
                        @endforeach
                    </select>
                    <select wire:model.live="statusFilter" class="{{ $sel }}" aria-label="Status">
                        <option value="">All Status</option>
                        <option value="open">Open</option>
                        <option value="done">Done tasks</option>
                    </select>
                @else
                    <div class="relative flex-[1_1_8rem] min-w-0 max-w-56">
                        <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" /></svg>
                        <input wire:model.live.debounce.300ms="qSearch" type="text" placeholder="Search name, school, phone, email..."
                            class="text-xs bg-white border border-gray-200 rounded-md pl-7 pr-2.5 py-1 text-gray-700 w-full focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <select wire:model.live="qStatus" class="{{ $sel }}" aria-label="Status">
                        <option value="">All Status</option>
                        @foreach (\App\Models\WebsiteDemo::STATUSES as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="qDays" class="{{ $sel }}" aria-label="Received">
                        <option value="">All Time</option>
                        <option value="7">Last 7 days</option>
                        <option value="30">Last 30 days</option>
                        <option value="90">Last 90 days</option>
                    </select>
                @endif

                @if ($filtered)
                    <button wire:click="clearFilters"
                        class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>
    </div>

    @if ($isCal)
        @php $isTodayPicked = $pickedItems === null; @endphp
        <div class="px-4 sm:px-6 py-3 grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_21rem] gap-4 items-start xl:items-stretch xl:flex-1 xl:min-h-0 xl:grid-rows-[minmax(0,1fr)]">

            {{-- ═══════════ MONTH — previous / next / Today on the card itself ═══════════ --}}
            <div class="sa-card overflow-hidden flex flex-col xl:h-full xl:min-h-0" data-cal-month>
                @php $navBtn = 'inline-flex items-center justify-center w-7 h-7 rounded-md border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 transition-colors'; @endphp
                <div class="flex-none flex items-center justify-between gap-2 px-3 py-2 border-b border-gray-100" data-cal-nav>
                    <div class="flex items-center gap-1.5">
                        <button type="button" wire:click="previousMonth" title="Previous month" aria-label="Previous month" class="{{ $navBtn }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                        </button>
                        <h2 class="text-sm font-semibold text-gray-900 min-w-[7.5rem] text-center" wire:loading.class="opacity-60" wire:target="previousMonth,nextMonth,goToday">{{ $monthStart->format('F Y') }}</h2>
                        <button type="button" wire:click="nextMonth" title="Next month" aria-label="Next month" class="{{ $navBtn }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                        </button>
                        @unless ($monthStart->isSameMonth($today) && $isTodayPicked)
                            <button type="button" wire:click="goToday" class="ml-1 h-7 px-2.5 text-xs font-medium rounded-md border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">Today</button>
                        @endunless
                    </div>
                    <div class="hidden sm:flex items-center gap-3 text-[11px] text-gray-500">
                        @foreach ($types as $value => $label)
                            <span class="inline-flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full {{ $dotCls[$value] }}"></span>{{ $label }}s</span>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-7 border-b border-gray-100 bg-gray-50/60 flex-none">
                    @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $wd)
                        <div class="py-1 text-center text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                            <span class="sm:hidden">{{ mb_substr($wd, 0, 1) }}</span><span class="hidden sm:inline">{{ $wd }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="flex flex-col xl:flex-1 xl:min-h-0">
                    @foreach ($weeks as $w => $week)
                        <div class="grid grid-cols-7 xl:flex-1 xl:min-h-0 {{ !$loop->last ? 'border-b border-gray-100' : '' }}" wire:key="cal-week-{{ $week[0]['date'] }}">
                            @foreach ($week as $cell)
                                @php $items = $cell['items']; $n = $items->count(); @endphp
                                <div wire:key="cal-day-{{ $cell['date'] }}" wire:click="selectDay('{{ $cell['date'] }}')"
                                    role="button" tabindex="0" x-on:keydown.enter="$wire.selectDay('{{ $cell['date'] }}')"
                                    class="cal-day group relative min-h-[3.75rem] sm:min-h-[5.25rem] xl:min-h-0 p-1 sm:p-1.5 overflow-hidden cursor-pointer transition-colors outline-none
                                           {{ !$loop->last ? 'border-r border-gray-100' : '' }}
                                           {{ $cell['isPicked'] ? 'bg-blue-50/60' : ($cell['inMonth'] ? 'bg-white hover:bg-gray-50' : 'bg-gray-50/70 hover:bg-gray-50') }}
                                           focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500">
                                    <div class="flex items-center justify-between">
                                        <span class="w-6 h-6 flex items-center justify-center rounded-full text-xs
                                                     {{ $cell['isToday'] ? 'bg-gray-900 text-white font-semibold' : ($cell['isPicked'] ? 'text-blue-700 font-semibold ring-1 ring-blue-300 bg-white' : ($cell['inMonth'] ? 'text-gray-800' : 'text-gray-300')) }}">{{ $cell['num'] }}</span>
                                        <span wire:click.stop="openCreate('{{ $cell['date'] }}', 'meeting')" title="Add on this day"
                                            class="hidden sm:group-hover:flex w-6 h-6 items-center justify-center rounded-md text-gray-400 hover:text-gray-800 hover:bg-gray-200/70">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" /></svg>
                                        </span>
                                    </div>

                                    {{-- sm+: up to three, each opens itself; how many show depends on the
                                         day's height (the style at the top): 2 lines, else 3, else 4. --}}
                                    <div class="hidden sm:block mt-0.5 space-y-px">
                                        @foreach ($items->take(3) as $i => $e)
                                            @php $fit = $i === 0 ? '' : ($i === 1 ? ($n <= 2 ? '' : 'cal-ge3') : ($n <= 3 ? 'cal-ge3' : 'cal-ge4')); @endphp
                                            <div wire:key="cal-chip-{{ $e->id }}" wire:click.stop="openEdit({{ $e->id }})"
                                                title="{{ $e->title }}{{ $e->timeLabel() ? ' · ' . $e->timeLabel() : '' }}{{ $e->with_whom ? ' · With ' . $e->with_whom : '' }}"
                                                class="{{ $fit }} truncate text-[11px] leading-[15px] px-1.5 py-px rounded {{ $e->type === 'task' && $e->is_done ? 'bg-gray-100 text-gray-400 line-through' : $chipCls[$e->type] ?? 'bg-gray-100 text-gray-700' }}">
                                                @if ($e->start_time)<span class="font-semibold">{{ \Carbon\Carbon::createFromFormat('H:i', \App\Models\SuperAdmin\SuperAdminCalendarEvent::hm($e->start_time))->format('g:i') }}</span> @endif{{ $e->title }}
                                            </div>
                                        @endforeach
                                        @if ($n > 2)
                                            <p class="cal-l2 text-[11px] leading-[15px] px-1.5 text-gray-500 font-medium">+{{ $n - 1 }} more</p>
                                        @endif
                                        @if ($n > 3)
                                            <p class="cal-l3 text-[11px] leading-[15px] px-1.5 text-gray-500 font-medium">+{{ $n - 2 }} more</p>
                                            <p class="cal-ge4 text-[11px] leading-[15px] px-1.5 text-gray-500 font-medium">+{{ $n - 3 }} more</p>
                                        @endif
                                    </div>

                                    {{-- phones: a dot for each (up to four) --}}
                                    @if ($items->isNotEmpty())
                                        <div class="sm:hidden mt-1 flex flex-wrap justify-center gap-0.5">
                                            @foreach ($items->take(4) as $e)
                                                <span class="w-1.5 h-1.5 rounded-full {{ $e->type === 'task' && $e->is_done ? 'bg-gray-300' : $dotCls[$e->type] ?? 'bg-gray-400' }}"></span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ═══════════ LISTS — scroll on their own beside the month ═══════════ --}}
            <div class="cal-lists space-y-4 xl:h-full xl:min-h-0 xl:overflow-y-auto xl:pr-1">

                {{-- TODAY --}}
                <div class="sa-card overflow-hidden">
                    <div class="px-4 py-2.5 border-b border-gray-100 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="sa-card-title">Today</p>
                            <p class="sa-card-sub">{{ $today->format('l, d M Y') }}</p>
                        </div>
                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            <button type="button" wire:click="openCreate('{{ $today->toDateString() }}', 'meeting')"
                                class="px-2.5 py-1 text-xs font-medium rounded-md border border-gray-200 text-gray-700 hover:bg-gray-50">+ Meeting</button>
                            <button type="button" wire:click="openCreate('{{ $today->toDateString() }}', 'task')"
                                class="px-2.5 py-1 text-xs font-medium rounded-md border border-gray-200 text-gray-700 hover:bg-gray-50">+ Task</button>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($todayItems as $e)
                            @include('livewire.super-admin.partials.calendar-item', ['e' => $e, 'showDate' => false])
                        @empty
                            <div class="px-4 py-6 text-center">
                                <p class="text-sm text-gray-500">{{ $filtered ? 'Nothing today matches the filters.' : 'Nothing planned for today.' }}</p>
                                <p class="text-xs text-gray-400 mt-1">Add a meeting or a task to see it here.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- THE DAY PICKED ON THE MONTH --}}
                @unless ($isTodayPicked)
                    <div class="sa-card overflow-hidden">
                        <div class="px-4 py-2.5 border-b border-gray-100 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="sa-card-title">{{ $pickedDate->format('l') }}</p>
                                <p class="sa-card-sub">{{ $pickedDate->format('d M Y') }}{{ $pickedDate->isTomorrow() ? ' · Tomorrow' : ($pickedDate->isYesterday() ? ' · Yesterday' : '') }}</p>
                            </div>
                            <button type="button" wire:click="openCreate('{{ $pickedDate->toDateString() }}', 'meeting')"
                                class="px-2.5 py-1 text-xs font-medium rounded-md border border-gray-200 text-gray-700 hover:bg-gray-50 flex-shrink-0">+ Add</button>
                        </div>
                        <div class="divide-y divide-gray-100">
                            @forelse ($pickedItems as $e)
                                @include('livewire.super-admin.partials.calendar-item', ['e' => $e, 'showDate' => false])
                            @empty
                                <div class="px-4 py-6 text-center">
                                    <p class="text-sm text-gray-500">Nothing on this day.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endunless

                {{-- TASKS STILL OPEN FROM EARLIER DAYS --}}
                @if ($overdue->isNotEmpty())
                    <div class="sa-card overflow-hidden">
                        <div class="px-4 py-2.5 border-b border-gray-100 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="sa-card-title">Not done yet</p>
                                <p class="sa-card-sub">Tasks from earlier days still open</p>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-600">{{ $overdue->count() }}{{ $overdue->count() >= 20 ? '+' : '' }}</span>
                        </div>
                        <div class="divide-y divide-gray-100">
                            @foreach ($overdue as $e)
                                @include('livewire.super-admin.partials.calendar-item', ['e' => $e, 'showDate' => true])
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @else
        @include('livewire.super-admin.partials.calendar-queries')
    @endif

    {{-- ══════════════════════════ ADD / EDIT SLIDE-IN ══════════════════════════ --}}
    @if ($showPanel)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden" x-data x-on:keydown.escape.window="$wire.closePanel()">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-md bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editingId ? 'Edit' : 'Add' }} {{ $types[$fType] ?? 'Meeting' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Only you see your calendar</p>
                    </div>
                    <button type="button" wire:click="closePanel" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form wire:submit="save" class="flex-1 overflow-y-auto px-6 py-6 space-y-4" id="cal-form">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">What is it? <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach ($types as $value => $label)
                                <button type="button" wire:click="chooseType('{{ $value }}')"
                                    class="flex items-center justify-center gap-1.5 px-2 py-2 text-sm font-semibold border-2 rounded-md transition-all {{ $fType === $value ? 'border-gray-900 bg-gray-50 text-gray-900' : 'border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                                    <span class="w-2 h-2 rounded-full {{ $dotCls[$value] }}"></span>{{ $label }}
                                </button>
                            @endforeach
                        </div>
                        @error('fType')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Title <span class="text-red-500">*</span></label>
                        <input wire:model="fTitle" type="text" maxlength="191" placeholder="{{ $placeholders[$fType] ?? '' }}" class="{{ $input }}">
                        @error('fTitle')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Date <span class="text-red-500">*</span></label>
                        <input wire:model="fDate" type="date" class="{{ $input }}">
                        @error('fDate')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $fType === 'task' ? 'Time' : 'From' }}</label>
                            <input wire:model="fStart" type="time" class="{{ $input }}">
                            @error('fStart')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $fType === 'task' ? 'Until' : 'To' }}</label>
                            <input wire:model="fEnd" type="time" class="{{ $input }}">
                            @error('fEnd')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    @if ($fType === 'meeting')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">With whom</label>
                            <input wire:model="fWith" type="text" maxlength="191" placeholder="Names, a school, a phone number" class="{{ $input }}">
                            @error('fWith')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Place or link</label>
                        <input wire:model="fLocation" type="text" maxlength="255" placeholder="Office, a school's address, or a Meet / Zoom link" class="{{ $input }}">
                        @error('fLocation')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes</label>
                        <textarea wire:model="fNotes" rows="4" maxlength="2000" placeholder="Agenda, what to bring, what to follow up"
                            class="{{ $input }} resize-none"></textarea>
                        @error('fNotes')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </form>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center gap-2 flex-shrink-0">
                    @if ($editingId)
                        <button type="button" wire:click="confirmDelete({{ $editingId }})"
                            class="px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-md">Delete</button>
                    @endif
                    <div class="ml-auto flex items-center gap-2">
                        <button type="button" wire:click="closePanel" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                        <button type="submit" form="cal-form" wire:loading.attr="disabled" wire:target="save"
                            class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60">
                            <span wire:loading.remove wire:target="save">{{ $editingId ? 'Update' : 'Save' }}</span>
                            <span wire:loading wire:target="save">Saving…</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════ DELETE CONFIRMATIONS ══════════════════════════ --}}
    @if ($deleting || $deletingQuery)
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-[9999] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDelete"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        @if ($deleting)
                            <h3 class="text-base font-semibold text-gray-900 mb-1">Delete this {{ strtolower($types[$deleting->type] ?? 'item') }}?</h3>
                            <p class="text-sm text-gray-500 break-words">“{{ $deleting->title }}” on {{ $deleting->date->format('d M Y') }} is deleted for good.</p>
                        @else
                            <h3 class="text-base font-semibold text-gray-900 mb-1">Delete this query?</h3>
                            <p class="text-sm text-gray-500 break-words">
                                {{ $deletingQuery->full_name }}@if ($deletingQuery->school_name), {{ $deletingQuery->school_name }}@endif is deleted for good{{ $deletingQuery->statusKey() === 'booked' ? ', and the booked demo is cancelled' : '' }}.
                            </p>
                        @endif
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" wire:click="cancelDelete" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button type="button" wire:click="{{ $deleting ? 'delete' : 'deleteQuery' }}" wire:loading.attr="disabled"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md disabled:opacity-60">Delete</button>
                </div>
            </div>
        </div>
    @endif
</div>
