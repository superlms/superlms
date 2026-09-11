{{-- ══════════════════════════════════════════════════════════════════
     FEE CONCESSION — the grey "Filter by:" band, meant to be @included
     directly inside the host's own sticky header (Accounts\FeeConcessions's
     whole header, or Admin\Fee's shared header under the 'concession' tab)
     — student-page style. The "Add Concession" button lives in the host's
     header row, next to the title.

     Both hosts feed this via App\Livewire\Concerns\HandlesFeeConcessions,
     so editing this one file keeps the two pages in sync.
══════════════════════════════════════════════════════════════════ --}}
<div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
    <div class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
            Filter by:
        </div>

        <select wire:model.live="filterConcStandardId" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
            <option value="">All Classes</option>
            @foreach ($standards as $std)
                <option value="{{ $std->id }}">{{ $std->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterConcSectionId" @disabled(!$filterConcStandardId) class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
            <option value="">All Sections</option>
            @foreach ($filterConcSections as $sec)
                <option value="{{ $sec->id }}">{{ $sec->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterConcStudentId" @disabled(!$filterConcStandardId) class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed min-w-[180px]">
            <option value="">All Students</option>
            @foreach ($filterConcStudents as $stu)
                <option value="{{ $stu->id }}">{{ $stu->full_name ?? ($stu->user->name ?? 'Unknown') }}</option>
            @endforeach
        </select>

        <input type="date" wire:model.live="filterConcDate" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">

        @if ($filterConcStandardId || $filterConcSectionId || $filterConcStudentId || $filterConcDate)
            <button wire:click="clearConcFilters"
                class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                Clear
            </button>
        @endif
    </div>
</div>
