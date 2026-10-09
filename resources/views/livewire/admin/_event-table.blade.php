{{-- Upcoming / Completed events in the Students list's style: a table (cards
     on a phone) — S.No, the event (a round tile in its colour, the title and
     the description under it), the date, the time, the class (and the place),
     and the actions (View, Edit — not once completed — and Delete). A row
     opens the event's View, as the cards did.
     Expects: $events, $completed (bool), $readOnly (bool). --}}
@php $isCompleted = $completed ?? false; @endphp

{{-- ══ DESKTOP TABLE (hidden on mobile) ══ --}}
<div class="hidden md:block bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">S.No</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Event</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Time</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($events as $index => $event)
                    <tr wire:click="onEventClick({{ $event['id'] }})"
                        class="hover:bg-gray-50/70 transition-colors cursor-pointer {{ $isCompleted ? 'bg-gray-50/40' : '' }}">

                        {{-- S.No --}}
                        <td class="px-4 py-3">
                            <span class="text-sm text-gray-500 font-medium">{{ $index + 1 }}</span>
                        </td>

                        {{-- Event (its colour + the title, the description under it) --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0"
                                    style="background-color: {{ $isCompleted ? '#9ca3af' : $event['color'] }}">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold truncate max-w-[280px] {{ $isCompleted ? 'text-gray-500' : 'text-gray-900' }}" title="{{ $event['title'] }}">
                                        {{ $event['title'] }}
                                    </p>
                                    <p class="text-xs text-gray-400 truncate max-w-[280px]">{{ $event['description'] ?: '' }}</p>
                                </div>
                            </div>
                        </td>

                        {{-- Date --}}
                        <td class="px-4 py-3">
                            <span class="text-sm whitespace-nowrap {{ $isCompleted ? 'text-gray-500' : 'text-gray-700' }}">
                                {{ \Carbon\Carbon::parse($event['date'])->format('D, d M') }}
                            </span>
                        </td>

                        {{-- Time --}}
                        <td class="px-4 py-3">
                            @if ($event['is_all_day'])
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium border whitespace-nowrap
                                    {{ $isCompleted ? 'bg-gray-100 text-gray-500 border-gray-200' : 'bg-blue-50 text-blue-700 border-blue-100' }}">All Day</span>
                            @elseif ($event['start_time'])
                                <span class="text-sm whitespace-nowrap {{ $isCompleted ? 'text-gray-500' : 'text-gray-700' }}">{{ $event['start_time'] }}</span>
                            @else
                                <span class="text-sm text-gray-400">—</span>
                            @endif
                        </td>

                        {{-- Class (and the place, under it) --}}
                        <td class="px-4 py-3">
                            <p class="text-sm whitespace-nowrap {{ $isCompleted ? 'text-gray-500' : 'text-gray-700' }}">{{ $event['class'] ?: '—' }}</p>
                            @if (!empty($event['location']))
                                <p class="text-xs text-gray-400 truncate max-w-[180px]">{{ $event['location'] }}</p>
                            @endif
                        </td>

                        {{-- Actions (a completed event shows Done, and is not edited) --}}
                        <td class="px-4 py-3" onclick="event.stopPropagation()">
                            <div class="flex items-center justify-center gap-1">
                                @if ($isCompleted)
                                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wide px-1.5 py-0.5 mr-1 rounded-full bg-gray-200 text-gray-600">
                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Done
                                    </span>
                                @else
                                    <span class="w-2 h-2 rounded-full flex-shrink-0 mr-1" style="background-color: {{ $event['color'] }}"></span>
                                @endif
                                <button wire:click="onEventClick({{ $event['id'] }})" title="View"
                                    class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                                @if (!$readOnly && !$isCompleted)
                                    <button wire:click="onEditEvent({{ $event['id'] }})" title="Edit"
                                        class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                @endif
                                @if (!$readOnly)
                                    <button wire:click="onDeleteEvent({{ $event['id'] }})" title="Delete"
                                        class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ══ MOBILE CARDS (shown only on mobile) ══ --}}
<div class="md:hidden space-y-3">
    @foreach ($events as $index => $event)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 p-4 border-b border-gray-100" wire:click="onEventClick({{ $event['id'] }})">
                <span class="text-xs font-bold text-gray-400 w-6 text-center">{{ $index + 1 }}</span>
                <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                    style="background-color: {{ $isCompleted ? '#9ca3af' : $event['color'] }}">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold truncate {{ $isCompleted ? 'text-gray-500' : 'text-gray-900' }}">{{ $event['title'] }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ $event['description'] ?: '' }}</p>
                </div>
                @if ($isCompleted)
                    <span class="text-[10px] font-semibold uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-gray-200 text-gray-600">Done</span>
                @endif
            </div>

            <div class="px-4 py-3 grid grid-cols-2 gap-2 text-sm">
                <div>
                    <p class="text-xs text-gray-400">Date</p>
                    <p class="text-gray-700 font-medium">{{ \Carbon\Carbon::parse($event['date'])->format('D, d M') }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Time</p>
                    <p class="text-gray-700 font-medium">{{ $event['is_all_day'] ? 'All Day' : ($event['start_time'] ?: '—') }}</p>
                </div>
                @if (!empty($event['class']) || !empty($event['location']))
                    <div class="col-span-2">
                        <p class="text-xs text-gray-400">Class</p>
                        <p class="text-gray-700 font-medium">{{ $event['class'] ?: '—' }}{{ !empty($event['location']) ? ' · ' . $event['location'] : '' }}</p>
                    </div>
                @endif
            </div>

            <div class="flex items-center border-t border-gray-100 divide-x divide-gray-100">
                <button wire:click="onEventClick({{ $event['id'] }})"
                    class="flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-medium text-blue-600 hover:bg-blue-50 transition-colors">
                    View
                </button>
                @if (!$readOnly && !$isCompleted)
                    <button wire:click="onEditEvent({{ $event['id'] }})"
                        class="flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-medium text-amber-600 hover:bg-amber-50 transition-colors">
                        Edit
                    </button>
                @endif
                @if (!$readOnly)
                    <button wire:click="onDeleteEvent({{ $event['id'] }})"
                        class="flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-medium text-red-600 hover:bg-red-50 transition-colors">
                        Delete
                    </button>
                @endif
            </div>
        </div>
    @endforeach
</div>
