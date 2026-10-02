{{--
    Shared class → student picker for the Certificate / TC issue panels.

    Two selects in one row: the class, then that class's students A to Z, each
    as "Name (Father's name) · admission no". While editing, the student is
    shown as a card instead and cannot be changed, because an issued
    certificate always stays with the student it was issued to.

    Params:
      $classProp, $studentProp               property names to wire:model
      $classValue, $students                 current class + its students
      $standards                             class options
      $selected                              StudentDetail|null (chosen student)
      $errorKey                              validation key for the student id
      $locked                                true in edit mode (the card)
      (The panels still pass $sectionProp, $sectionsList, $searchProp,
       $selectMethod and $clearMethod from the list this picker used to be;
       it no longer uses them.)
--}}
<div>
    <label class="block text-sm font-medium text-gray-700 mb-1.5">
        Student <span class="text-red-500">*</span>
    </label>

    @if (($locked ?? false) && $selected)
        <div class="flex items-center gap-3 px-3.5 py-2.5 border border-gray-300 rounded-md bg-gray-50">
            <div class="w-9 h-9 rounded-full bg-blue-600 flex items-center justify-center text-white text-xs font-semibold flex-shrink-0">
                {{ strtoupper(substr($selected->full_name ?? 'S', 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-gray-900 truncate">{{ $selected->full_name . ($selected->father_name ? ' (' . $selected->father_name . ')' : '') }}</p>
                <p class="text-xs text-gray-500 truncate">
                    Adm {{ $selected->admission_no ?: '—' }}
                    @if ($selected->standard?->name) · {{ $selected->standard->name }}@endif
                    @if ($selected->section?->name) · {{ $selected->section->name }}@endif
                </p>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <select wire:model.live="{{ $classProp }}"
                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                <option value="">Select Class</option>
                @foreach ($standards as $std)
                    <option value="{{ $std->id }}">{{ $std->name }}</option>
                @endforeach
            </select>
            {{-- Keyed on the class, so another class starts with nobody picked. --}}
            <select wire:model.live="{{ $studentProp }}" wire:key="pick-{{ $errorKey }}-{{ $classValue ?: 'none' }}" @disabled(!$classValue)
                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-50 disabled:opacity-60 @error($errorKey) border-red-400 @enderror">
                <option value="">{{ $classValue ? 'Select Student' : 'Select a class first' }}</option>
                @foreach ($students as $stu)
                    {{-- One expression, no @if: Livewire leaves a marker beside each @if, and an <option> is no place for one. --}}
                    <option value="{{ $stu->id }}">{{ $stu->full_name . ($stu->father_name ? ' (' . $stu->father_name . ')' : '') . ($stu->admission_no ? ' · ' . $stu->admission_no : '') }}</option>
                @endforeach
            </select>
        </div>
    @endif

    @error($errorKey)<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
</div>
