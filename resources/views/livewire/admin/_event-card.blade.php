{{-- Upcoming / Completed event card --}}
@php
    $isCompleted = $completed ?? false;
@endphp
<div class="group relative {{ $isCompleted ? 'bg-gray-50 border-gray-200 opacity-80' : 'bg-gray-50 border-gray-200 hover:border-blue-300 hover:shadow-md' }}
        border rounded-xl p-4 transition-all duration-200 cursor-pointer"
    wire:click="onEventClick({{ $event['id'] }})">

    {{-- Color bar --}}
    <div class="absolute top-0 left-0 w-1 h-full rounded-l-xl"
        style="background-color: {{ $isCompleted ? '#9ca3af' : $event['color'] }}"></div>

    <div class="pl-3">
        <div class="flex items-start justify-between gap-2 mb-2">
            <h4 class="font-semibold text-sm leading-snug line-clamp-1 transition-colors
                       {{ $isCompleted ? 'text-gray-600' : 'text-gray-900 group-hover:text-blue-600' }}">
                {{ $event['title'] }}
            </h4>
            <div class="flex items-center gap-1.5 flex-shrink-0 mt-0.5">
                @if ($isCompleted)
                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wide
                                 px-1.5 py-0.5 rounded-full bg-gray-200 text-gray-600">
                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Done
                    </span>
                @else
                    <div class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $event['color'] }}"></div>
                @endif
            </div>
        </div>

        @if (!empty($event['description']))
            <p class="text-xs leading-relaxed line-clamp-2 mb-2 {{ $isCompleted ? 'text-gray-400' : 'text-gray-500' }}">
                {{ $event['description'] }}
            </p>
        @endif

        <div class="flex items-center gap-1.5 text-xs mb-1 {{ $isCompleted ? 'text-gray-400' : 'text-gray-500' }}">
            <svg class="w-3.5 h-3.5 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            {{ Carbon\Carbon::parse($event['date'])->format('D, M d') }}

            @if ($event['start_time'] && !$event['is_all_day'])
                <span class="text-gray-300 mx-1">•</span>
                <svg class="w-3.5 h-3.5 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ $event['start_time'] }}
            @elseif ($event['is_all_day'])
                <span class="text-gray-300 mx-1">•</span>
                <span class="text-xs px-2 py-0.5 rounded-full
                             {{ $isCompleted ? 'bg-gray-200 text-gray-600' : 'bg-blue-100 text-blue-700' }}">
                    All Day
                </span>
            @endif
        </div>

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
                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5
                             {{ $isCompleted ? 'bg-gray-200 text-gray-600' : 'bg-purple-100 text-purple-700' }} rounded-full">
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
