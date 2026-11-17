{{-- Class → Section → Student, for a form whose component uses
     App\Livewire\Concerns\PicksStudent. Takes $standards, $sections, $students. --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Class <span class="text-red-500">*</span></label>
        <select wire:model.live="standardId"
            class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <option value="">Select class</option>
            @foreach ($standards as $std)
                <option value="{{ $std->id }}">{{ $std->name }}</option>
            @endforeach
        </select>
        @error('standardId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Section <span class="text-red-500">*</span></label>
        <select data-sole-section wire:model.live="sectionId" @disabled($standardId === '')
            class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 text-gray-800 disabled:bg-gray-50 disabled:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <option value="">Select section</option>
            @foreach ($sections as $sec)
                <option value="{{ $sec->id }}">{{ $sec->name }}</option>
            @endforeach
        </select>
        @error('sectionId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>
    <div class="sm:col-span-2">
        <label class="block text-xs font-semibold text-gray-600 mb-1">Student <span class="text-red-500">*</span></label>
        <select wire:model.live="studentId" @disabled($sectionId === '')
            class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 text-gray-800 disabled:bg-gray-50 disabled:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <option value="">{{ $sectionId !== '' && $students->isEmpty() ? 'No students in this section' : 'Select student' }}</option>
            @foreach ($students as $st)
                <option value="{{ $st->id }}">{{ $st->full_name ?: 'N/A' }}{{ $st->admission_no ? ' · ' . $st->admission_no : '' }}{{ $st->roll_no ? ' · Roll ' . $st->roll_no : '' }}</option>
            @endforeach
        </select>
        @error('studentId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>
</div>
