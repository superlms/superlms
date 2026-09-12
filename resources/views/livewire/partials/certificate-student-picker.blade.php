{{--
    Shared class → section → student picker for the Certificate / TC issue panels.

    Collapses to a single selected-student card once a student is chosen, so the
    panel stays short. Locked (no "Change") while editing, because an issued
    certificate always stays with the student it was issued to.

    Params:
      $classProp, $sectionProp, $searchProp  property names to wire:model
      $classValue, $sectionsList, $students  current class + option/row data
      $standards                             class options
      $selected                              StudentDetail|null (chosen student)
      $selectMethod, $clearMethod            component methods
      $errorKey                              validation key for the student id
      $locked                                true in edit mode (hide "Change")
--}}
<div>
    <label class="block text-sm font-medium text-gray-700 mb-1.5">
        Student <span class="text-red-500">*</span>
    </label>

    @if ($selected)
        <div class="flex items-center gap-3 px-3.5 py-2.5 border border-gray-300 rounded-md bg-gray-50">
            <div class="w-9 h-9 rounded-full bg-blue-600 flex items-center justify-center text-white text-xs font-semibold flex-shrink-0">
                {{ strtoupper(substr($selected->full_name ?? 'S', 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-gray-900 truncate">{{ $selected->full_name }}</p>
                <p class="text-xs text-gray-500 truncate">
                    Adm {{ $selected->admission_no ?: '—' }}
                    @if ($selected->standard?->name) · {{ $selected->standard->name }}@endif
                    @if ($selected->section?->name) · {{ $selected->section->name }}@endif
                </p>
            </div>
            @unless ($locked ?? false)
                <button type="button" wire:click="{{ $clearMethod }}"
                    class="text-xs font-medium text-blue-600 hover:text-blue-700 flex-shrink-0">Change</button>
            @endunless
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
            <select wire:model.live="{{ $sectionProp }}" @disabled($sectionsList->isEmpty())
                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50">
                <option value="">All Sections</option>
                @foreach ($sectionsList as $sec)
                    <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                @endforeach
            </select>
        </div>

        <input wire:model.live.debounce.300ms="{{ $searchProp }}" type="text" @disabled(!$classValue)
            placeholder="Search by name or admission no."
            class="mt-3 w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-50 disabled:opacity-60">

        <div class="mt-3 border border-gray-300 rounded-md overflow-hidden">
            <div class="max-h-56 overflow-y-auto divide-y divide-gray-100">
                @if (!$classValue)
                    <p class="px-3.5 py-8 text-center text-sm text-gray-400">Select a class to see students.</p>
                @else
                    @forelse ($students as $stu)
                        <button type="button" wire:key="pick-{{ $errorKey }}-{{ $stu->id }}"
                            wire:click="{{ $selectMethod }}({{ $stu->id }})"
                            class="w-full flex items-center gap-3 px-3.5 py-2.5 text-left hover:bg-gray-50">
                            <div class="w-7 h-7 rounded-full bg-gray-100 flex items-center justify-center text-[11px] font-semibold text-gray-600 flex-shrink-0">
                                {{ strtoupper(substr($stu->full_name ?? 'S', 0, 1)) }}
                            </div>
                            <span class="flex-1 min-w-0 text-sm text-gray-800 truncate">{{ $stu->full_name }}</span>
                            <span class="text-xs text-gray-400 flex-shrink-0">{{ $stu->admission_no }}</span>
                        </button>
                    @empty
                        <p class="px-3.5 py-8 text-center text-sm text-gray-400">No students found.</p>
                    @endforelse
                @endif
            </div>
        </div>
    @endif

    @error($errorKey)<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
</div>
