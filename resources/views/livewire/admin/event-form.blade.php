{{-- Event form — styled to match the Exams template slide-in panel pattern.
     The parent slide-in renders its own header (title + close) and uses
     this body section; the Save / Update button lives inside the form so
     it can submit via wire:submit, and Cancel comes from the parent footer. --}}
<div>
    <form wire:submit.prevent="save" class="space-y-4">

        {{-- Title --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                Event Title <span class="text-red-500">*</span>
            </label>
            <input wire:model.defer="title" type="text" placeholder="e.g. Annual Sports Day"
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
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Description</label>
            <textarea wire:model.defer="description" rows="3" placeholder="Optional notes..."
                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm resize-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
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
