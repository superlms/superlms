{{-- ══════════════════════════════════════════════════════════════════
     VIEW FEE — the two sub-tabs and the grey "Filter by:" band, meant to be
     @included inside the host's own sticky header (Admin\Fee's View Fee tab
     or Accounts\ViewFee). Both hosts feed it via
     App\Livewire\Concerns\HandlesViewFee, so editing this one file keeps the
     two screens in sync.
══════════════════════════════════════════════════════════════════ --}}

{{-- Sub-tabs: By Student | By Class --}}
<div class="border-t border-gray-200 px-4 sm:px-6">
    <div class="flex gap-1">
        @foreach (['by_student' => 'By Student', 'by_class' => 'By Class'] as $tab => $label)
            <button wire:click="setViewSubTab('{{ $tab }}')"
                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $viewSubTab === $tab ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">{{ $label }}</button>
        @endforeach
    </div>
</div>

{{-- Grey filter band — student-page style, attached under the tabs --}}
<div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
    <div class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
            Filter by:
        </div>

        @if ($viewSubTab === 'by_student')
            <select wire:model.live="viewStudentStandardId" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                <option value="">Select Class</option>
                @foreach ($vfStandards as $std)
                    <option value="{{ $std->id }}">{{ $std->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="viewStudentSectionId" @disabled(!$viewStudentStandardId) class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
                <option value="">All Sections</option>
                @foreach ($vfSections as $sec)
                    <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="viewStudentId" @disabled(!$viewStudentStandardId) class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed min-w-[200px]">
                <option value="">{{ $viewStudentStandardId ? 'Select Student' : 'Pick a class first' }}</option>
                @foreach ($vfStudents as $stu)
                    <option value="{{ $stu->id }}">{{ $stu->full_name ?? ($stu->user->name ?? 'Unknown') }}@if ($stu->admission_no) · {{ $stu->admission_no }}@endif</option>
                @endforeach
            </select>

            @if ($viewStudentStandardId || $viewStudentSectionId || $viewStudentId)
                <button wire:click="clearViewStudentFilters"
                    class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    Clear
                </button>
            @endif
        @else
            <select wire:model.live="viewClassStandardId" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                <option value="">Select Class</option>
                @foreach ($vfStandards as $std)
                    <option value="{{ $std->id }}">{{ $std->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="viewClassSectionId" @disabled(!$viewClassStandardId) class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
                <option value="">All Sections</option>
                @foreach ($vfSections as $sec)
                    <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                @endforeach
            </select>

            <button wire:click="loadClassFeeView" @disabled(!$viewClassStandardId)
                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-white bg-gray-900 hover:bg-gray-800 rounded-md disabled:opacity-50 disabled:cursor-not-allowed">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Load Students
            </button>

            @if ($viewClassStandardId || $viewClassSectionId)
                <button wire:click="clearViewClassFilters"
                    class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    Clear
                </button>
            @endif
        @endif
    </div>
</div>
