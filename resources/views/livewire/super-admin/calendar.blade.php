{{-- Super Admin → Calendar: the signed-in person's own meetings, tasks and
     events (App\Livewire\SuperAdmin\Calendar). Nobody else's are ever shown. --}}
<div class="min-h-screen bg-gray-50">
    @include('livewire.super-admin.partials.dash-styles')
    @php
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
        $isTodayPicked = $pickedItems === null;
    @endphp

    {{-- ══════════════════════════════ HEADER ══════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="px-4 sm:px-6 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:min-h-9">
            <div class="min-w-0 sm:flex sm:items-baseline sm:gap-2">
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">Calendar</h1>
                <p class="text-xs text-gray-400 mt-0.5 sm:mt-0 truncate">Your own meetings, tasks and events</p>
            </div>
            <div class="flex items-center justify-between sm:justify-end gap-4">
                <div class="flex items-center gap-3 sm:gap-4 text-xs sm:text-sm text-gray-500 sm:divide-x sm:divide-gray-200">
                    <span class="sm:pr-4">Today: <strong class="text-blue-600">{{ $stats['meetings'] }}</strong> {{ $stats['meetings'] === 1 ? 'meeting' : 'meetings' }}</span>
                    <span class="sm:px-4"><strong class="text-amber-700">{{ $stats['tasksDone'] }}/{{ $stats['tasks'] }}</strong> tasks done</span>
                    <span class="hidden md:inline sm:pl-4"><strong class="text-violet-600">{{ $stats['events'] }}</strong> {{ $stats['events'] === 1 ? 'event' : 'events' }}</span>
                </div>
                <button type="button" wire:click="openCreate('{{ $day }}', 'meeting')"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" /></svg>
                    <span>Add</span>
                </button>
            </div>
        </div>

        {{-- Month + what to show --}}
        <div class="border-t border-gray-100 px-4 sm:px-6 py-2.5 flex flex-wrap items-center gap-2">
            <div class="flex items-center gap-1">
                <button type="button" wire:click="previousMonth" title="Previous month"
                    class="w-8 h-8 flex items-center justify-center rounded-md border border-gray-200 bg-white text-gray-600 hover:bg-gray-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                </button>
                <button type="button" wire:click="nextMonth" title="Next month"
                    class="w-8 h-8 flex items-center justify-center rounded-md border border-gray-200 bg-white text-gray-600 hover:bg-gray-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </button>
            </div>
            <h2 class="text-sm font-semibold text-gray-900 min-w-[7.5rem]">{{ $monthStart->format('F Y') }}</h2>
            <button type="button" wire:click="goToday"
                class="px-3 py-1.5 text-xs font-medium rounded-md border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">Today</button>

            <div class="flex flex-wrap items-center gap-1.5 sm:ml-auto">
                @foreach (['' => 'All'] + array_map(fn($t) => $t . 's', $types) as $value => $label)
                    <button type="button" wire:click="setType('{{ $value }}')"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full border transition-colors {{ $typeFilter === $value ? 'bg-gray-900 border-gray-900 text-white' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                        @if ($value !== '')
                            <span class="w-1.5 h-1.5 rounded-full {{ $dotCls[$value] }}"></span>
                        @endif
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <div class="px-4 sm:px-6 py-4 grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_23rem] gap-4 items-start">

        {{-- ══════════════════════════════ MONTH ══════════════════════════════ --}}
        <div class="sa-card overflow-hidden">
            <div class="grid grid-cols-7 border-b border-gray-100 bg-gray-50/60">
                @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $wd)
                    <div class="py-2 text-center text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                        <span class="sm:hidden">{{ mb_substr($wd, 0, 1) }}</span><span class="hidden sm:inline">{{ $wd }}</span>
                    </div>
                @endforeach
            </div>

            @foreach ($weeks as $w => $week)
                <div class="grid grid-cols-7 {{ !$loop->last ? 'border-b border-gray-100' : '' }}" wire:key="cal-week-{{ $week[0]['date'] }}">
                    @foreach ($week as $cell)
                        @php $items = $cell['items']; @endphp
                        <div wire:key="cal-day-{{ $cell['date'] }}" wire:click="selectDay('{{ $cell['date'] }}')"
                            role="button" tabindex="0" x-on:keydown.enter="$wire.selectDay('{{ $cell['date'] }}')"
                            class="group relative min-h-[4.25rem] sm:min-h-[6.75rem] p-1 sm:p-1.5 cursor-pointer transition-colors outline-none
                                   {{ !$loop->last ? 'border-r border-gray-100' : '' }}
                                   {{ $cell['isPicked'] ? 'bg-blue-50/60' : ($cell['inMonth'] ? 'bg-white hover:bg-gray-50' : 'bg-gray-50/70 hover:bg-gray-50') }}
                                   focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500">
                            <div class="flex items-center justify-between">
                                <span class="w-6 h-6 sm:w-7 sm:h-7 flex items-center justify-center rounded-full text-xs sm:text-[13px]
                                             {{ $cell['isToday'] ? 'bg-gray-900 text-white font-semibold' : ($cell['isPicked'] ? 'text-blue-700 font-semibold ring-1 ring-blue-300 bg-white' : ($cell['inMonth'] ? 'text-gray-800' : 'text-gray-300')) }}">{{ $cell['num'] }}</span>
                                <span wire:click.stop="openCreate('{{ $cell['date'] }}', 'meeting')" title="Add on this day"
                                    class="hidden sm:group-hover:flex w-6 h-6 items-center justify-center rounded-md text-gray-400 hover:text-gray-800 hover:bg-gray-200/70">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" /></svg>
                                </span>
                            </div>

                            {{-- sm+: up to three, each opens itself --}}
                            <div class="hidden sm:block mt-1 space-y-0.5">
                                @foreach ($items->take(3) as $e)
                                    <div wire:key="cal-chip-{{ $e->id }}" wire:click.stop="openEdit({{ $e->id }})"
                                        title="{{ $e->title }}{{ $e->timeLabel() ? ' · ' . $e->timeLabel() : '' }}{{ $e->with_whom ? ' · With ' . $e->with_whom : '' }}"
                                        class="truncate text-[11px] leading-4 px-1.5 py-0.5 rounded {{ $e->type === 'task' && $e->is_done ? 'bg-gray-100 text-gray-400 line-through' : $chipCls[$e->type] ?? 'bg-gray-100 text-gray-700' }}">
                                        @if ($e->start_time)<span class="font-semibold">{{ \Carbon\Carbon::createFromFormat('H:i', \App\Models\SuperAdmin\SuperAdminCalendarEvent::hm($e->start_time))->format('g:i') }}</span> @endif{{ $e->title }}
                                    </div>
                                @endforeach
                                @if ($items->count() > 3)
                                    <p class="text-[11px] leading-4 px-1.5 text-gray-500 font-medium">+{{ $items->count() - 3 }} more</p>
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

        {{-- ══════════════════════════════ LISTS ══════════════════════════════ --}}
        <div class="space-y-4">

            {{-- TODAY --}}
            <div class="sa-card overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between gap-3">
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
                        <div class="px-4 py-8 text-center">
                            <p class="text-sm text-gray-500">{{ $typeFilter ? 'No ' . strtolower($types[$typeFilter]) . 's today.' : 'Nothing planned for today.' }}</p>
                            <p class="text-xs text-gray-400 mt-1">Add a meeting or a task to see it here.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- THE DAY PICKED ON THE MONTH --}}
            @unless ($isTodayPicked)
                <div class="sa-card overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between gap-3">
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
                            <div class="px-4 py-8 text-center">
                                <p class="text-sm text-gray-500">Nothing on this day.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endunless

            {{-- TASKS STILL OPEN FROM EARLIER DAYS --}}
            @if ($overdue->isNotEmpty())
                <div class="sa-card overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between gap-3">
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
                        <input wire:model="fTitle" type="text" maxlength="191" placeholder="{{ $placeholders[$fType] ?? '' }}"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @error('fTitle')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Date <span class="text-red-500">*</span></label>
                        <input wire:model="fDate" type="date"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @error('fDate')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $fType === 'task' ? 'Time' : 'From' }}</label>
                            <input wire:model="fStart" type="time"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('fStart')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $fType === 'task' ? 'Until' : 'To' }}</label>
                            <input wire:model="fEnd" type="time"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('fEnd')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    @if ($fType === 'meeting')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">With whom</label>
                            <input wire:model="fWith" type="text" maxlength="191" placeholder="Names, a school, a phone number"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('fWith')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Place or link</label>
                        <input wire:model="fLocation" type="text" maxlength="255" placeholder="Office, a school's address, or a Meet / Zoom link"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @error('fLocation')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes</label>
                        <textarea wire:model="fNotes" rows="4" maxlength="2000" placeholder="Agenda, what to bring, what to follow up"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm resize-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
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

    {{-- ══════════════════════════ DELETE CONFIRMATION ══════════════════════════ --}}
    @if ($deleting)
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-[9999] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDelete"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Delete this {{ strtolower($types[$deleting->type] ?? 'item') }}?</h3>
                        <p class="text-sm text-gray-500 break-words">“{{ $deleting->title }}” on {{ $deleting->date->format('d M Y') }} is deleted for good.</p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" wire:click="cancelDelete" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button type="button" wire:click="delete" wire:loading.attr="disabled" wire:target="delete"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md disabled:opacity-60">Delete</button>
                </div>
            </div>
        </div>
    @endif
</div>
