{{-- Payroll's Add: "Who are you adding?" — the four kinds, the picked one
     outlined. It sits on top of every add form: Payroll's own panel
     (management, driver, employee) and the teacher form, which is another
     component — there the buttons call Payroll through `$parent`.

     $selected  the kind picked ('' = none yet)
     $call      the action a button calls: 'chooseEmpType' or '$parent.chooseEmpType' --}}
<div>
    <label class="block text-sm font-medium text-gray-700 mb-2">
        Who are you adding? <span class="text-red-500">*</span>
    </label>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
        @foreach (['management' => 'Management', 'teacher' => 'Teacher', 'driver' => 'Driver', 'employee' => 'Employee'] as $value => $label)
            <button type="button" wire:click="{{ $call }}('{{ $value }}')"
                class="px-3.5 py-3 text-left border-2 rounded-md transition-all
                    {{ $selected === $value ? 'border-gray-900 bg-gray-50' : 'border-gray-200 hover:bg-gray-50' }}">
                <span class="block text-sm font-semibold text-gray-900">{{ $label }}</span>
            </button>
        @endforeach
    </div>
</div>
