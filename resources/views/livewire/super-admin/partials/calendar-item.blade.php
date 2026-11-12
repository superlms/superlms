{{-- One meeting, task or event in the Calendar's lists (Today, the day picked,
     tasks still open). Params: $e (SuperAdminCalendarEvent), $showDate (bool). --}}
@php
    $badgeCls = [
        'meeting' => 'bg-blue-50 text-blue-700 ring-blue-200',
        'task'    => 'bg-amber-50 text-amber-800 ring-amber-200',
        'event'   => 'bg-violet-50 text-violet-700 ring-violet-200',
    ][$e->type] ?? 'bg-gray-50 text-gray-700 ring-gray-200';
    $barCls = [
        'meeting' => 'bg-blue-500',
        'task'    => 'bg-amber-500',
        'event'   => 'bg-violet-500',
    ][$e->type] ?? 'bg-gray-400';
    $isTask   = $e->type === 'task';
    $done     = $isTask && $e->is_done;
    $time     = $e->timeLabel();
    $isLink   = $e->location && preg_match('~^https?://~i', $e->location);
    $typeName = \App\Models\SuperAdmin\SuperAdminCalendarEvent::TYPES[$e->type] ?? ucfirst($e->type);
@endphp
<div class="group flex gap-3 px-4 py-3" wire:key="cal-item-{{ $e->id }}-{{ $showDate ? 'd' : 'n' }}">
    @if ($isTask)
        <button type="button" wire:click="toggleDone({{ $e->id }})" title="{{ $done ? 'Mark as not done' : 'Mark as done' }}"
            class="mt-0.5 w-[18px] h-[18px] flex-shrink-0 rounded-[5px] border flex items-center justify-center transition-colors {{ $done ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-gray-300 bg-white hover:border-gray-500' }}">
            @if ($done)
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
            @endif
        </button>
    @else
        <span class="mt-1 w-1 h-9 rounded-full flex-shrink-0 {{ $barCls }}"></span>
    @endif

    <div class="min-w-0 flex-1">
        <p class="text-sm font-medium leading-snug break-words {{ $done ? 'line-through text-gray-400' : 'text-gray-900' }}">{{ $e->title }}</p>
        <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-500">
            <span class="inline-flex items-center px-1.5 py-px rounded ring-1 ring-inset text-[11px] font-medium {{ $badgeCls }}">{{ $typeName }}</span>
            @if ($showDate)
                <span class="{{ $isTask && !$done ? 'text-red-600 font-medium' : '' }}">{{ $e->date->format('D, d M') }}</span>
            @endif
            <span>{{ $time ?? ($isTask ? 'Any time' : 'All day') }}</span>
            @if ($done && $e->done_at)
                <span class="text-emerald-600">Done {{ $e->done_at->format('g:i A') }}</span>
            @endif
        </div>
        @if ($e->with_whom)
            <p class="mt-1 text-xs text-gray-600 flex items-start gap-1.5">
                <svg class="w-3.5 h-3.5 mt-px text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                <span class="break-words min-w-0">With {{ $e->with_whom }}</span>
            </p>
        @endif
        @if ($e->location)
            <p class="mt-1 text-xs text-gray-600 flex items-start gap-1.5">
                <svg class="w-3.5 h-3.5 mt-px text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                @if ($isLink)
                    <a href="{{ $e->location }}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline break-all min-w-0">{{ $e->location }}</a>
                @else
                    <span class="break-words min-w-0">{{ $e->location }}</span>
                @endif
            </p>
        @endif
        @if ($e->notes)
            <p class="mt-1 text-xs text-gray-500 whitespace-pre-line break-words line-clamp-3">{{ $e->notes }}</p>
        @endif
    </div>

    <div class="flex items-start gap-0.5 flex-shrink-0 sm:opacity-0 sm:group-hover:opacity-100 sm:focus-within:opacity-100 transition-opacity">
        <button type="button" wire:click="openEdit({{ $e->id }})" title="Edit"
            class="w-7 h-7 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
        </button>
        <button type="button" wire:click="confirmDelete({{ $e->id }})" title="Delete"
            class="w-7 h-7 flex items-center justify-center rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
        </button>
    </div>
</div>
