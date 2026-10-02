{{--
    Shared class → student picker for the Certificate / TC issue panels.

    Two boxes in one row: the class, then that class's students A to Z. The
    student box is a list of the page's own rather than a browser <select>, so
    each student reads as the name with the father's name small beside it (a
    <select> cannot make part of an option small) — and nothing else: no
    admission number. While editing, the student is shown as a card instead
    and cannot be changed, because an issued certificate always stays with the
    student it was issued to.

    Params:
      $classProp, $studentProp               property names (class is wire:model'd,
                                             the student is $set on a pick)
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
                <p class="text-sm font-medium text-gray-900 truncate">{{ $selected->full_name }} <span class="text-xs font-normal text-gray-400">{{ $selected->father_name }}</span></p>
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

            {{-- The student box. Keyed on the class, so another class starts closed
                 with nobody picked. --}}
            <div class="relative" wire:key="pick-{{ $errorKey }}-{{ $classValue ?: 'none' }}"
                x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape="open = false">
                <button type="button" x-on:click="open = !open" @disabled(!$classValue)
                    class="w-full flex items-center justify-between gap-2 px-3.5 py-2.5 border rounded-md text-sm text-left bg-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-50 disabled:opacity-60 @error($errorKey) border-red-400 @else border-gray-300 @enderror">
                    <span class="min-w-0 truncate">
                        @if ($selected && $classValue)
                            <span class="text-gray-900">{{ $selected->full_name }}</span>
                            <span class="text-xs text-gray-400">{{ $selected->father_name }}</span>
                        @else
                            <span class="text-gray-900">{{ $classValue ? 'Select Student' : 'Select a class first' }}</span>
                        @endif
                    </span>
                    <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                </button>

                <div x-show="open" style="display: none"
                    class="absolute z-20 left-0 right-0 mt-1 max-h-64 overflow-y-auto bg-white border border-gray-200 rounded-md shadow-lg py-1">
                    @forelse ($students as $stu)
                        <button type="button" wire:key="pick-{{ $errorKey }}-opt-{{ $stu->id }}"
                            wire:click="$set('{{ $studentProp }}', {{ $stu->id }})" x-on:click="open = false"
                            class="w-full px-3.5 py-2 text-left truncate hover:bg-gray-50 {{ $selected && $selected->id === $stu->id ? 'bg-blue-50' : '' }}">
                            <span class="text-sm text-gray-800">{{ $stu->full_name }}</span>
                            <span class="text-xs text-gray-400">{{ $stu->father_name }}</span>
                        </button>
                    @empty
                        <p class="px-3.5 py-6 text-center text-sm text-gray-400">No students found.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    @error($errorKey)<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
</div>
