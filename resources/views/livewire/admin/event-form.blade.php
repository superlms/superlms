{{-- Event form — styled to match the Exams template slide-in panel pattern.
     The parent slide-in renders its own header (title + close) and uses
     this body section; the Save / Update button lives inside the form so
     it can submit via wire:submit, and Cancel comes from the parent footer. --}}
<div>
    <form wire:submit.prevent="save" class="space-y-4">

        {{-- Title --}}
        <div x-data="{ len: @js(mb_strlen($title ?? '')) }">
            <div class="flex items-center justify-between mb-1.5">
                <label class="block text-sm font-medium text-gray-700">
                    Event Title <span class="text-red-500">*</span>
                </label>
                <span class="text-xs text-gray-400"><span x-text="len">0</span>/1000</span>
            </div>
            <input wire:model.defer="title" type="text" maxlength="1000" placeholder="e.g. Annual Sports Day"
                x-on:input="len = $event.target.value.length"
                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
            @error('title')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        {{-- Event Type + Color --}}
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Event Type</label>
                <select wire:model.defer="event_type"
                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    <option value="class">Class</option>
                    <option value="exam">Exam</option>
                    <option value="meeting">Meeting</option>
                    <option value="event">Event</option>
                    <option value="holiday">Holiday</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Color</label>
                <input wire:model.defer="color" type="color"
                    class="w-full h-[42px] px-1 py-1 border border-gray-300 rounded-md cursor-pointer">
            </div>
        </div>

        {{-- Description --}}
        <div x-data="{ len: @js(mb_strlen($description ?? '')) }">
            <div class="flex items-center justify-between mb-1.5">
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <span class="text-xs text-gray-400"><span x-text="len">0</span>/3000</span>
            </div>
            <textarea wire:model.defer="description" rows="3" maxlength="3000" placeholder="Optional notes..."
                x-on:input="len = $event.target.value.length"
                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm resize-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
            @error('description')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        {{-- Attachment (image or PDF, ≤ 1 MB) --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                Attachment <span class="font-normal text-gray-400">(Optional · Image or PDF, max 1 MB)</span>
            </label>

            {{-- Existing attachment when editing --}}
            @if ($mode !== 'create' && $event && $event->attachment && !$attachment)
                <div class="mb-2">
                    <a href="{{ $event->attachment }}" target="_blank" rel="noopener"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border border-blue-200 bg-blue-50 text-xs font-medium text-blue-700 hover:bg-blue-100">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                        </svg>
                        View current attachment
                    </a>
                </div>
            @endif

            <input type="file" wire:model="attachment" accept="image/*,application/pdf"
                class="block w-full text-sm text-gray-500 cursor-pointer file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            <p class="text-xs text-gray-400 mt-1">Image (JPG/PNG/GIF/WebP) or PDF · max 1 MB</p>
            <div wire:loading wire:target="attachment" class="text-xs text-blue-600 mt-1">Uploading...</div>
            @if ($attachment)
                <p wire:loading.remove wire:target="attachment" class="mt-1 text-xs text-gray-600 truncate">
                    Selected: {{ $attachment->getClientOriginalName() }}
                </p>
            @endif
            @error('attachment')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        {{-- Date + All Day --}}
        <div class="grid grid-cols-2 gap-3 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Date <span class="text-red-500">*</span>
                </label>
                <input wire:model.defer="event_date" type="date"
                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                @error('event_date')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700 px-3.5 py-2.5 border border-gray-200 rounded-md bg-gray-50 cursor-pointer">
                <input type="checkbox" wire:model.live="is_all_day" class="rounded">
                All Day Event
            </label>
        </div>

        {{-- Start / End time --}}
        @if (!$is_all_day)
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        Start Time <span class="text-red-500">*</span>
                    </label>
                    <input wire:model.defer="start_time" type="time"
                        class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    @error('start_time')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        End Time <span class="text-red-500">*</span>
                    </label>
                    <input wire:model.defer="end_time" type="time"
                        class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    @error('end_time')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
            </div>
        @endif

        {{-- Action row (matches Exams footer styling, embedded inside form so it submits) --}}
        <div class="flex items-center justify-end gap-2 pt-2">
            <button type="submit" wire:loading.attr="disabled"
                class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                <span wire:loading.remove wire:target="save">
                    {{ $mode === 'create' ? 'Create Event' : 'Update Event' }}
                </span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </div>
    </form>
</div>
