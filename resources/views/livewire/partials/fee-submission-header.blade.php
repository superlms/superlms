{{-- ══════════════════════════════════════════════════════════════════
     FEE SUBMISSION — the grey "Filter by:" band, meant to be @included
     inside the host's own sticky header (Admin\Fee's Fee Submission tab or
     Accounts\FeeSubmission). Both hosts feed it via
     App\Livewire\Concerns\HandlesFeeSubmission, so editing this one file
     keeps the two screens in sync.
══════════════════════════════════════════════════════════════════ --}}

<div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
    <div class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
            Filter by:
        </div>
        <select wire:model.live="submissionStandardId" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
            <option value="">Select Class</option>
            @foreach ($fsStandards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
        </select>
        <select wire:model.live="submissionSectionId" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
            <option value="">All Sections</option>
            @foreach ($fsSections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
        </select>
        <select wire:model.live="selectedStudentId" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[200px]">
            <option value="">Select Student</option>
            @foreach ($fsStudents as $stu)
                <option value="{{ $stu->id }}">{{ $stu->full_name ?? ($stu->user->name ?? 'Unknown') }}@if ($stu->father_name) — {{ $stu->father_name }}@endif</option>
            @endforeach
        </select>
        <input type="text" wire:model="submissionSearch" wire:keydown.enter="searchSubmissionStudents" placeholder="Search student / father name…"
            class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-56 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
        <button wire:click="searchSubmissionStudents"
            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            Search
        </button>
    </div>
</div>
