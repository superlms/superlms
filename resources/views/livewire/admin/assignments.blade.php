<div class="min-h-screen bg-gray-50">
@php
    // Palette for the four submission statuses + the "never attempted" row.
    $statusChip = [
        'pending'   => 'bg-gray-100 text-gray-600 border-gray-200',
        'submitted' => 'bg-blue-50 text-blue-700 border-blue-200',
        'reviewed'  => 'bg-amber-50 text-amber-700 border-amber-200',
        'approved'  => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'rejected'  => 'bg-red-50 text-red-700 border-red-200',
    ];
    $statusLabel = [
        'pending'   => 'Not attempted',
        'submitted' => 'Submitted',
        'reviewed'  => 'Reviewed',
        'approved'  => 'Approved',
        'rejected'  => 'Rejected',
    ];
    $windowChip = [
        'open'     => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'upcoming' => 'bg-blue-50 text-blue-700 border-blue-200',
        'closed'   => 'bg-gray-100 text-gray-500 border-gray-200',
    ];
@endphp

    {{-- ══════════════════════════════════════════════════
         HEADER · stats · tabs · filter bar
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900">Assignments</h1>
                    <p class="text-xs text-gray-400 mt-0.5">Hand out written work or MCQs to a class, then mark what comes back.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                        <span class="pr-4">Total: <strong class="text-gray-800">{{ $statistics['total'] }}</strong></span>
                        <span class="px-4">Open: <strong class="text-emerald-600">{{ $statistics['open'] }}</strong></span>
                        <span class="px-4">MCQ: <strong class="text-purple-600">{{ $statistics['mcq'] }}</strong></span>
                        <span class="pl-4">Attempts: <strong class="text-blue-600">{{ $statistics['attempts'] }}</strong></span>
                    </div>

                    @if ($activeTab === 'assignments')
                        <button wire:click="onAdd"
                            class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            <span class="hidden sm:inline">Add Assignment</span>
                            <span class="sm:hidden">New</span>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Mobile / tablet stats --}}
            <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Total: <strong class="text-gray-800">{{ $statistics['total'] }}</strong></span>
                <span>Open: <strong class="text-emerald-600">{{ $statistics['open'] }}</strong></span>
                <span>MCQ: <strong class="text-purple-600">{{ $statistics['mcq'] }}</strong></span>
                <span>Attempts: <strong class="text-blue-600">{{ $statistics['attempts'] }}</strong></span>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="border-t border-gray-200 px-4 sm:px-6">
            <div class="flex gap-1">
                <button wire:click="switchTab('assignments')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'assignments' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">Assignments</button>
                <button wire:click="switchTab('responses')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'responses' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">Responses</button>
            </div>
        </div>

        {{-- Filter bar (tab-aware) --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    Filter by:
                </div>

                @if ($activeTab === 'assignments')
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search title or description…"
                        class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-56 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />

                    <select wire:model.live="filterStandard" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[120px]">
                        <option value="">All Classes</option>
                        @foreach ($standards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
                    </select>

                    <select wire:model.live="filterSection" @disabled(!$filterStandard)
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                        <option value="">All Sections</option>
                        @foreach ($filterSections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                    </select>

                    <select wire:model.live="filterSubject" @disabled(!$filterStandard)
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                        <option value="">All Subjects</option>
                        @foreach ($filterSubjects as $sub)<option value="{{ $sub->id }}">{{ $sub->name }}</option>@endforeach
                    </select>

                    <select wire:model.live="filterType" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="">All Types</option>
                        <option value="written">Text / File</option>
                        <option value="mcq">MCQ</option>
                    </select>

                    <select wire:model.live="filterWindow" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="">Any Date</option>
                        <option value="open">Open now</option>
                        <option value="upcoming">Upcoming</option>
                        <option value="closed">Closed</option>
                    </select>

                    @if ($search || $filterStandard || $filterSection || $filterSubject || $filterType || $filterWindow)
                        <button wire:click="clearFilters"
                            class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            Clear
                        </button>
                    @endif
                @else
                    <select wire:model.live="resStandard" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[120px]">
                        <option value="">Select class…</option>
                        @foreach ($standards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
                    </select>
                    <span class="text-gray-300">→</span>
                    <select wire:model.live="resSection" @disabled(!$resStandard)
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                        <option value="">All sections</option>
                        @foreach ($resSections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                    </select>
                    <span class="text-gray-300">→</span>
                    <select wire:model.live="resSubject" @disabled(!$resStandard)
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                        <option value="">All subjects</option>
                        @foreach ($resSubjects as $sub)<option value="{{ $sub->id }}">{{ $sub->name }}</option>@endforeach
                    </select>
                    <span class="text-gray-300">→</span>
                    <select wire:model.live="resAssignment" @disabled(!$resStandard)
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 min-w-[200px]">
                        <option value="">Select assignment…</option>
                        @foreach ($resAssignments as $a)
                            <option value="{{ $a->id }}">{{ $a->title }} ({{ $a->type === 'mcq' ? 'MCQ' : 'Text/File' }})</option>
                        @endforeach
                    </select>

                    <select wire:model.live="resStatus" @disabled(!$resAssignment)
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                        <option value="">All statuses</option>
                        <option value="pending">Not attempted</option>
                        <option value="submitted">Submitted</option>
                        <option value="reviewed">Reviewed</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         BODY
    ══════════════════════════════════════════════════ --}}
    <div class="p-4 sm:p-6">

    {{-- ───────────────────────── ASSIGNMENTS TAB ───────────────────────── --}}
    @if ($activeTab === 'assignments')
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Assignment</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Subject</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Window</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Responses</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($assignments as $assignment)
                            @php $win = $assignment->windowStatus(); @endphp
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="flex items-start gap-2">
                                        <p class="text-sm font-semibold text-gray-900">{{ $assignment->title }}</p>
                                        @if ($assignment->file)
                                            <svg class="w-3.5 h-3.5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                                        @endif
                                        @unless ($assignment->is_active)
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-500 border border-gray-200">Draft</span>
                                        @endunless
                                    </div>
                                    @if ($assignment->description)
                                        <p class="text-xs text-gray-400 line-clamp-1 mt-0.5">{{ Str::limit($assignment->description, 80) }}</p>
                                    @endif
                                    <p class="text-[11px] text-gray-400 mt-0.5">By {{ $assignment->user->name ?? 'Unknown' }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($assignment->type === 'mcq')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">MCQ · {{ $assignment->questions_count }} Q</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-sky-50 text-sky-700 border border-sky-200">
                                            {{ ['text' => 'Text', 'file' => 'File', 'both' => 'Text + File'][$assignment->submission_mode] ?? 'Text + File' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ $assignment->standard->name ?? '—' }}@if ($assignment->section)<span class="text-gray-400"> · {{ $assignment->section->name }}</span>@endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $assignment->subject->name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold border {{ $windowChip[$win] }}">{{ ucfirst($win) }}</span>
                                    <p class="text-[11px] text-gray-400 mt-1">
                                        {{ $assignment->start_date?->format('d M Y, h:i A') ?? '—' }}<br>
                                        <span class="text-gray-300">to</span> {{ $assignment->end_date?->format('d M Y, h:i A') ?? '—' }}
                                    </p>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-md text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">{{ $assignment->submissions_count }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <button wire:click="onView({{ $assignment->id }})" title="View"
                                            class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-blue-600 hover:bg-blue-50">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        </button>
                                        <button wire:click="onEdit({{ $assignment->id }})" title="Edit"
                                            class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-amber-600 hover:bg-amber-50">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </button>
                                        <button wire:click="confirmDelete({{ $assignment->id }})" title="Delete"
                                            class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center">
                                    <div class="w-12 h-12 mx-auto mb-3 bg-blue-50 rounded-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-800">No assignments yet</p>
                                    <p class="text-xs text-gray-400 mt-1">Click “Add Assignment” to hand one out to a class.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($assignments instanceof \Illuminate\Contracts\Pagination\Paginator && $assignments->hasPages())
                <div class="px-4 py-3 border-t border-gray-200">{{ $assignments->links() }}</div>
            @endif
        </div>
    @endif

    {{-- ───────────────────────── RESPONSES TAB ───────────────────────── --}}
    @if ($activeTab === 'responses')
        @if (!$selectedAssignment)
            <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
                <div class="w-12 h-12 mx-auto mb-3 bg-blue-50 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                </div>
                <p class="text-sm font-semibold text-gray-800">Pick an assignment to see its responses</p>
                <p class="text-xs text-gray-400 mt-1">Choose a class (and optionally section / subject) above, then the assignment.</p>
            </div>
        @else
            {{-- Selected assignment summary --}}
            <div class="bg-white rounded-xl border border-gray-200 p-4 mb-4">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-bold text-gray-900">{{ $selectedAssignment->title }}</h2>
                            @if ($selectedAssignment->isMcq())
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">MCQ</span>
                            @else
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-sky-50 text-sky-700 border border-sky-200">Text / File</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ $selectedAssignment->standard->name ?? '—' }}
                            @if ($selectedAssignment->section) · {{ $selectedAssignment->section->name }} @endif
                            · {{ $selectedAssignment->subject->name ?? '—' }}
                            · {{ $selectedAssignment->start_date?->format('d M Y, h:i A') }} → {{ $selectedAssignment->end_date?->format('d M Y, h:i A') }}
                            @if ($selectedAssignment->maxMarks() > 0) · Out of <strong>{{ $selectedAssignment->maxMarks() }}</strong> @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-gray-500">
                        <span>Students: <strong class="text-gray-800">{{ $responseSummary['total'] }}</strong></span>
                        <span>Attempted: <strong class="text-blue-600">{{ $responseSummary['attempted'] }}</strong></span>
                        <span>Pending: <strong class="text-amber-600">{{ $responseSummary['pending'] }}</strong></span>
                        <span>Marked: <strong class="text-emerald-600">{{ $responseSummary['reviewed'] }}</strong></span>
                        @if ($selectedAssignment->isMcq())
                            <button wire:click="applyMcqScores"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-purple-700 bg-purple-50 border border-purple-200 rounded-md hover:bg-purple-100">
                                Auto-fill MCQ marks
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Submitted</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Answer</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Marks</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Remarks</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Save</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($responseRows as $row)
                                @php
                                    $student = $row['student'];
                                    $sub     = $row['submission'];
                                    $sid     = $sub?->id;
                                @endphp
                                <tr class="hover:bg-gray-50 transition-colors align-top">
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-semibold text-gray-900">{{ $student->full_name }}</p>
                                        @if ($student->roll_no)
                                            <p class="text-[11px] text-gray-400">Roll {{ $student->roll_no }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600">
                                        {{ $sub?->submitted_at?->format('d M Y, h:i A') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if (!$sub)
                                            <span class="text-xs text-gray-400">Nothing submitted</span>
                                        @else
                                            <div class="flex flex-wrap items-center gap-2">
                                                @if ($sub->file)
                                                    <a href="{{ $sub->file }}" target="_blank" rel="noopener"
                                                        class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-semibold text-blue-700 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                                                        {{ Str::limit($sub->file_name ?: 'File', 22) }}
                                                    </a>
                                                @endif
                                                @if ($sub->mcq_score !== null)
                                                    <span class="px-2 py-1 text-[11px] font-semibold text-purple-700 bg-purple-50 border border-purple-200 rounded-md">MCQ score {{ $sub->mcq_score }}</span>
                                                @endif
                                                <button wire:click="onViewResponse({{ $sid }})"
                                                    class="px-2 py-1 text-[11px] font-semibold text-gray-600 bg-gray-50 border border-gray-200 rounded-md hover:bg-gray-100">Open</button>
                                            </div>
                                            @if ($sub->answer_text)
                                                <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ Str::limit($sub->answer_text, 120) }}</p>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if (!$sub)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold border {{ $statusChip['pending'] }}">{{ $statusLabel['pending'] }}</span>
                                        @else
                                            <select wire:model="marking.{{ $sid }}.status"
                                                class="text-xs bg-white border border-gray-200 rounded-md px-2 py-1.5 text-gray-700">
                                                <option value="submitted">Submitted</option>
                                                <option value="reviewed">Reviewed</option>
                                                <option value="approved">Approved</option>
                                                <option value="rejected">Rejected</option>
                                            </select>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($sub)
                                            <div class="flex items-center gap-1">
                                                <input type="number" step="0.5" min="0" wire:model="marking.{{ $sid }}.marks"
                                                    class="w-20 text-xs bg-white border border-gray-200 rounded-md px-2 py-1.5 text-gray-700" placeholder="—" />
                                                @if ($selectedAssignment->maxMarks() > 0)
                                                    <span class="text-[11px] text-gray-400">/ {{ $selectedAssignment->maxMarks() }}</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-300">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($sub)
                                            <input type="text" wire:model="marking.{{ $sid }}.remarks" placeholder="Optional feedback"
                                                class="w-44 text-xs bg-white border border-gray-200 rounded-md px-2 py-1.5 text-gray-700" />
                                        @else
                                            <span class="text-xs text-gray-300">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($sub)
                                            <button wire:click="saveMarking({{ $sid }})" wire:loading.attr="disabled"
                                                class="px-3 py-1.5 text-xs font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50">Save</button>
                                        @else
                                            <span class="text-xs text-gray-300">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-12 text-center">
                                        <p class="text-sm font-semibold text-gray-800">No students match this filter</p>
                                        <p class="text-xs text-gray-400 mt-1">Try “All statuses”, or check that students are assigned to this class and section.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif

    </div>

    {{-- ══════════════════════════════════════════════════
         ADD / EDIT SLIDE-OVER
         Teleported to <body>: inside #main-scroll a fixed overlay paints under
         the navbar and sidebar and its buttons stop responding.
    ══════════════════════════════════════════════════ --}}
    @if ($openForm)
        @teleport('body')
        <div class="fixed inset-0 z-[70] overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeForm"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-2xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editId ? 'Edit Assignment' : 'New Assignment' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Class, section, subject and the window it stays open for.</p>
                    </div>
                    <button wire:click="closeForm" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">

                    {{-- Class → Section → Subject --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Class <span class="text-red-500">*</span></label>
                            <select wire:model.live="standard_id" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select class</option>
                                @foreach ($standards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
                            </select>
                            @error('standard_id')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Section <span class="text-red-500">*</span></label>
                            <select wire:model.live="section_id" @disabled(!$standard_id) class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-50">
                                <option value="">Select section</option>
                                @foreach ($formSections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                            </select>
                            @error('section_id')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Subject <span class="text-red-500">*</span></label>
                            <select wire:model="subject_id" @disabled(!$standard_id) class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-50">
                                <option value="">Select subject</option>
                                @foreach ($formSubjects as $sub)<option value="{{ $sub->id }}">{{ $sub->name }}</option>@endforeach
                            </select>
                            @error('subject_id')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- Title --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Title <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="title" placeholder="e.g. Chapter 4 — Practice Set"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
                        @error('title')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Start / End --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Start date <span class="text-red-500">*</span></label>
                            <input type="datetime-local" wire:model="start_date" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
                            @error('start_date')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">End date <span class="text-red-500">*</span></label>
                            <input type="datetime-local" wire:model="end_date" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
                            @error('end_date')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Total marks</label>
                            <input type="number" min="0" wire:model="total_marks" placeholder="{{ $type === 'mcq' ? 'Auto from questions' : 'Optional' }}"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
                            @error('total_marks')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- ─── Type: the two options ─── --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">What goes in this assignment? <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <button type="button" wire:click="$set('type', 'written')"
                                class="text-left p-3 rounded-lg border-2 transition-colors {{ $type === 'written' ? 'border-blue-600 bg-blue-50' : 'border-gray-200 hover:border-gray-300' }}">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 {{ $type === 'written' ? 'text-blue-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    <span class="text-sm font-semibold {{ $type === 'written' ? 'text-blue-700' : 'text-gray-700' }}">Text / File</span>
                                </div>
                                <p class="text-[11px] text-gray-500 mt-1">Write instructions, attach a file, and let students answer in text and/or upload their work.</p>
                            </button>

                            <button type="button" wire:click="$set('type', 'mcq')"
                                class="text-left p-3 rounded-lg border-2 transition-colors {{ $type === 'mcq' ? 'border-purple-600 bg-purple-50' : 'border-gray-200 hover:border-gray-300' }}">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 {{ $type === 'mcq' ? 'text-purple-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <span class="text-sm font-semibold {{ $type === 'mcq' ? 'text-purple-700' : 'text-gray-700' }}">MCQs</span>
                                </div>
                                <p class="text-[11px] text-gray-500 mt-1">Add questions with options; the score is worked out automatically when a student submits.</p>
                            </button>
                        </div>
                    </div>

                    @if ($type === 'written')
                        {{-- Description --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Instructions / question text</label>
                            <textarea wire:model="description" rows="5" placeholder="Type the assignment here…"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>

                        {{-- Attachment --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Attachment (optional)</label>
                            @if ($existingFile && !$removeFile)
                                <div class="flex items-center justify-between gap-2 px-3 py-2 mb-2 bg-gray-50 border border-gray-200 rounded-md">
                                    <a href="{{ $existingFile }}" target="_blank" rel="noopener" class="text-xs font-semibold text-blue-600 hover:underline truncate">Current attachment</a>
                                    <button type="button" wire:click="$set('removeFile', true)" class="text-xs font-semibold text-red-600 hover:underline flex-shrink-0">Remove</button>
                                </div>
                            @endif
                            <input type="file" wire:model="attachment"
                                class="w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                            <p class="text-[11px] text-gray-400 mt-1">PDF, Word, Excel, PowerPoint, text or image. Up to 5 MB.</p>
                            <div wire:loading wire:target="attachment" class="text-[11px] text-blue-600 mt-1">Uploading…</div>
                            @error('attachment')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        {{-- What the student submits --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Student submits</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach (['text' => 'Text answer', 'file' => 'File upload', 'both' => 'Text and/or file'] as $mode => $label)
                                    <button type="button" wire:click="$set('submission_mode', '{{ $mode }}')"
                                        class="px-3 py-1.5 rounded-md text-xs font-semibold border transition-colors {{ $submission_mode === $mode ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">{{ $label }}</button>
                                @endforeach
                            </div>
                        </div>
                    @else
                        {{-- Description still useful as MCQ instructions --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Instructions (optional)</label>
                            <textarea wire:model="description" rows="2" placeholder="Anything students should know before starting…"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>

                        {{-- MCQ builder --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium text-gray-700">Questions ({{ count($questions) }})</label>
                                <button type="button" wire:click="addQuestion"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-purple-700 bg-purple-50 border border-purple-200 rounded-md hover:bg-purple-100">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                    Add question
                                </button>
                            </div>

                            <div class="space-y-3">
                                @foreach ($questions as $qi => $q)
                                    <div wire:key="q-{{ $qi }}" class="border border-gray-200 rounded-lg p-3 bg-gray-50/60">
                                        <div class="flex items-start gap-2 mb-2">
                                            <span class="mt-2 text-xs font-bold text-gray-400 flex-shrink-0">Q{{ $qi + 1 }}</span>
                                            <textarea wire:model="questions.{{ $qi }}.question_text" rows="2" placeholder="Question text"
                                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm bg-white"></textarea>
                                            <div class="flex-shrink-0">
                                                <input type="number" min="1" wire:model="questions.{{ $qi }}.marks" title="Marks"
                                                    class="w-16 px-2 py-2 border border-gray-300 rounded-md text-sm bg-white" />
                                            </div>
                                            <button type="button" wire:click="removeQuestion({{ $qi }})" title="Remove question"
                                                class="mt-1 w-7 h-7 flex items-center justify-center rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 flex-shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pl-6">
                                            @foreach ($q['options'] as $oi => $opt)
                                                <div wire:key="q-{{ $qi }}-o-{{ $oi }}" class="flex items-center gap-2">
                                                    <button type="button" wire:click="setCorrectOption({{ $qi }}, {{ $oi }})" title="Mark as the correct answer"
                                                        class="w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0 {{ !empty($opt['is_correct']) ? 'border-emerald-500 bg-emerald-500' : 'border-gray-300 bg-white' }}">
                                                        @if (!empty($opt['is_correct']))
                                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                                        @endif
                                                    </button>
                                                    <input type="text" wire:model="questions.{{ $qi }}.options.{{ $oi }}.text"
                                                        placeholder="Option {{ chr(65 + $oi) }}"
                                                        class="flex-1 px-2.5 py-1.5 border border-gray-300 rounded-md text-sm bg-white" />
                                                </div>
                                            @endforeach
                                        </div>
                                        <p class="text-[11px] text-gray-400 mt-2 pl-6">Tick the circle next to the correct option. Leave an option blank to skip it.</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Active --}}
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="is_active" class="w-4 h-4 rounded border-gray-300 text-blue-600" />
                        <span class="text-sm text-gray-700">Publish to students (uncheck to keep it as a draft)</span>
                    </label>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeForm" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="save" wire:loading.attr="disabled" wire:target="save"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="save">{{ $editId ? 'Update Assignment' : 'Create Assignment' }}</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
        @endteleport
    @endif

    {{-- ══════════════════════════════════════════════════
         VIEW ASSIGNMENT
    ══════════════════════════════════════════════════ --}}
    @if ($openView && $viewAssignment)
        @teleport('body')
        <div class="fixed inset-0 z-[70] overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeView"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $viewAssignment->title }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $viewAssignment->standard->name ?? '—' }}
                            @if ($viewAssignment->section) · {{ $viewAssignment->section->name }} @endif
                            · {{ $viewAssignment->subject->name ?? '—' }}
                        </p>
                    </div>
                    <button wire:click="closeView" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    {{-- Turned in, against the class it actually went to. --}}
                    @php
                        $vsTotal = $viewStats['total'] ?? 0;
                        $vsDone  = $viewStats['attempted'] ?? 0;
                        $vsPct   = $vsTotal > 0 ? (int) round($vsDone / $vsTotal * 100) : 0;
                        $submissionModeLabels = ['text' => 'Text only', 'file' => 'File only', 'both' => 'Text or file'];
                    @endphp
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <div class="grid grid-cols-3 divide-x divide-gray-200">
                            <div class="px-4 py-3 text-center">
                                <p class="text-xl font-bold text-emerald-600 tabular-nums">{{ $vsDone }}</p>
                                <p class="text-[11px] text-gray-400 uppercase tracking-wide mt-0.5">Attempted</p>
                            </div>
                            <div class="px-4 py-3 text-center">
                                <p class="text-xl font-bold text-amber-600 tabular-nums">{{ $viewStats['pending'] ?? 0 }}</p>
                                <p class="text-[11px] text-gray-400 uppercase tracking-wide mt-0.5">Not yet</p>
                            </div>
                            <div class="px-4 py-3 text-center">
                                <p class="text-xl font-bold text-gray-800 tabular-nums">{{ $vsTotal }}</p>
                                <p class="text-[11px] text-gray-400 uppercase tracking-wide mt-0.5">In class</p>
                            </div>
                        </div>
                        <div class="px-4 pb-3">
                            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $vsPct }}%"></div>
                            </div>
                            <p class="text-[11px] text-gray-400 mt-1.5 text-center">
                                {{ $vsPct }}% turned in &middot; {{ $viewStats['marked'] ?? 0 }} marked
                            </p>
                        </div>
                    </div>

                    {{-- Everything the form was filled in with. --}}
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wide">Class</p>
                            <p class="text-gray-800 font-medium">{{ $viewAssignment->standard->name ?? '—' }}</p>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wide">Section</p>
                            <p class="text-gray-800 font-medium">{{ $viewAssignment->section->name ?? '—' }}</p>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wide">Subject</p>
                            <p class="text-gray-800 font-medium">{{ $viewAssignment->subject->name ?? '—' }}</p>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wide">Type</p>
                            <p class="text-gray-800 font-medium">{{ $viewAssignment->isMcq() ? 'MCQ' : 'Written' }}</p>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wide">Starts</p>
                            <p class="text-gray-800 font-medium">{{ $viewAssignment->start_date?->format('d M Y, h:i A') ?? '—' }}</p>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wide">Ends</p>
                            <p class="text-gray-800 font-medium">{{ $viewAssignment->end_date?->format('d M Y, h:i A') ?? '—' }}</p>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wide">Out of</p>
                            <p class="text-gray-800 font-medium">{{ $viewAssignment->maxMarks() > 0 ? $viewAssignment->maxMarks() . ' marks' : 'Not set' }}</p>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wide">Window</p>
                            <p class="text-gray-800 font-medium capitalize">{{ $viewAssignment->windowStatus() }}</p>
                        </div>
                        @unless ($viewAssignment->isMcq())
                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                <p class="text-[11px] text-gray-400 uppercase tracking-wide">Answer with</p>
                                <p class="text-gray-800 font-medium">{{ $submissionModeLabels[$viewAssignment->submission_mode] ?? '—' }}</p>
                            </div>
                        @endunless
                        <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wide">Visibility</p>
                            <p class="font-medium {{ $viewAssignment->is_active ? 'text-emerald-700' : 'text-gray-500' }}">{{ $viewAssignment->is_active ? 'Published' : 'Draft' }}</p>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 col-span-2">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wide">Set by</p>
                            <p class="text-gray-800 font-medium">{{ $viewAssignment->user->name ?? '—' }}</p>
                        </div>
                    </div>

                    @if ($viewAssignment->description)
                        <div>
                            <p class="text-[11px] text-gray-400 uppercase tracking-wide mb-1">Instructions</p>
                            <p class="text-sm text-gray-700 whitespace-pre-line">{{ $viewAssignment->description }}</p>
                        </div>
                    @endif

                    @if ($viewAssignment->file)
                        <a href="{{ $viewAssignment->file }}" target="_blank" rel="noopener"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                            Open attachment
                        </a>
                    @endif

                    @if (!empty($viewQuestions))
                        <div>
                            <p class="text-[11px] text-gray-400 uppercase tracking-wide mb-2">Questions ({{ count($viewQuestions) }})</p>
                            <div class="space-y-3">
                                @foreach ($viewQuestions as $qi => $q)
                                    <div class="border border-gray-200 rounded-lg p-3">
                                        <p class="text-sm font-semibold text-gray-800">Q{{ $qi + 1 }}. {{ $q['question_text'] }}
                                            <span class="text-[11px] font-normal text-gray-400">({{ $q['marks'] }} mark{{ $q['marks'] > 1 ? 's' : '' }})</span>
                                        </p>
                                        <ul class="mt-2 space-y-1">
                                            @foreach ($q['options'] as $oi => $opt)
                                                <li class="flex items-center gap-2 text-sm {{ $opt['is_correct'] ? 'text-emerald-700 font-semibold' : 'text-gray-600' }}">
                                                    <span class="w-5 h-5 rounded-full border flex items-center justify-center text-[10px] flex-shrink-0 {{ $opt['is_correct'] ? 'border-emerald-500 bg-emerald-50' : 'border-gray-300' }}">{{ chr(65 + $oi) }}</span>
                                                    {{ $opt['text'] }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endteleport
    @endif

    {{-- ══════════════════════════════════════════════════
         VIEW ONE RESPONSE
    ══════════════════════════════════════════════════ --}}
    @if ($openResponse && $responseRow)
        @teleport('body')
        <div class="fixed inset-0 z-[70] overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeResponse"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $responseRow->student->full_name ?? $responseRow->user->name ?? 'Student' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $responseRow->assignment->title ?? '' }} ·
                            Submitted {{ $responseRow->submitted_at?->format('d M Y, h:i A') ?? '—' }}
                        </p>
                    </div>
                    <button wire:click="closeResponse" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold border {{ $statusChip[$responseRow->status] ?? $statusChip['pending'] }}">
                            {{ $statusLabel[$responseRow->status] ?? ucfirst($responseRow->status) }}
                        </span>
                        @if ($responseRow->marks !== null)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">Marks: {{ 0 + $responseRow->marks }}</span>
                        @endif
                        @if ($responseRow->mcq_score !== null)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">MCQ score: {{ $responseRow->mcq_score }}</span>
                        @endif
                    </div>

                    @if ($responseRow->file)
                        <a href="{{ $responseRow->file }}" target="_blank" rel="noopener"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                            {{ $responseRow->file_name ?: 'Open submitted file' }}
                        </a>
                    @endif

                    @if ($responseRow->answer_text)
                        <div>
                            <p class="text-[11px] text-gray-400 uppercase tracking-wide mb-1">Written answer</p>
                            <p class="text-sm text-gray-700 whitespace-pre-line bg-gray-50 border border-gray-200 rounded-lg p-3">{{ $responseRow->answer_text }}</p>
                        </div>
                    @endif

                    @if ($responseRow->remarks)
                        <div>
                            <p class="text-[11px] text-gray-400 uppercase tracking-wide mb-1">Your remarks</p>
                            <p class="text-sm text-gray-700">{{ $responseRow->remarks }}</p>
                        </div>
                    @endif

                    @if (!empty($responseAnswers))
                        <div>
                            <p class="text-[11px] text-gray-400 uppercase tracking-wide mb-2">Answers</p>
                            <div class="space-y-3">
                                @foreach ($responseAnswers as $qi => $q)
                                    <div class="border border-gray-200 rounded-lg p-3">
                                        <div class="flex items-start justify-between gap-2">
                                            <p class="text-sm font-semibold text-gray-800">Q{{ $qi + 1 }}. {{ $q['question_text'] }}</p>
                                            <span class="flex-shrink-0 px-2 py-0.5 rounded-md text-[11px] font-semibold border {{ $q['is_correct'] ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200' }}">
                                                {{ $q['is_correct'] ? '+' . $q['marks'] : '0' }}
                                            </span>
                                        </div>
                                        <ul class="mt-2 space-y-1">
                                            @foreach ($q['options'] as $oi => $opt)
                                                @php $picked = $q['chosen_id'] === $opt['id']; @endphp
                                                <li class="flex items-center gap-2 text-sm {{ $opt['is_correct'] ? 'text-emerald-700 font-semibold' : ($picked ? 'text-red-600 font-semibold' : 'text-gray-600') }}">
                                                    <span class="w-5 h-5 rounded-full border flex items-center justify-center text-[10px] flex-shrink-0 {{ $opt['is_correct'] ? 'border-emerald-500 bg-emerald-50' : ($picked ? 'border-red-400 bg-red-50' : 'border-gray-300') }}">{{ chr(65 + $oi) }}</span>
                                                    {{ $opt['text'] }}
                                                    @if ($picked)<span class="text-[10px] uppercase tracking-wide text-gray-400">chosen</span>@endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @unless ($responseRow->file || $responseRow->answer_text || !empty($responseAnswers))
                        <p class="text-sm text-gray-400">This attempt has no content attached to it.</p>
                    @endunless
                </div>
            </div>
        </div>
        @endteleport
    @endif

    {{-- ══════════════════════════════════════════════════
         DELETE CONFIRM
    ══════════════════════════════════════════════════ --}}
    @if ($openDelete)
        @teleport('body')
        <div class="fixed inset-0 z-[80] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/30 backdrop-blur-[1.5px]" wire:click="cancelDelete"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
                <div class="w-12 h-12 mb-4 bg-red-50 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Delete this assignment?</h3>
                <p class="text-sm text-gray-500 mt-1">“{{ $deleteTitle }}” and every response, mark and question on it will be removed. This can't be undone.</p>
                <div class="flex items-center justify-end gap-2 mt-6">
                    <button wire:click="cancelDelete" class="px-4 py-2 text-sm font-semibold text-gray-600 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
                    <button wire:click="destroyAssignment" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm font-semibold text-white bg-red-600 rounded-md hover:bg-red-700 disabled:opacity-50">Delete</button>
                </div>
            </div>
        </div>
        @endteleport
    @endif
</div>
