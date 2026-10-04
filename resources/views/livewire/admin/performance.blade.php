<div class="min-h-screen bg-gray-50">

@php
    // Grades come from config/grading.php: O > 90, A+ 81-90, A 71-80,
    // B 61-70, C 51-60, D 41-50, P 35-40, F below 35.
    $gradeBadge = function ($grade) {
        return match (true) {
            $grade === 'O'  => 'bg-emerald-100 text-emerald-700',
            $grade === 'A+' => 'bg-green-100 text-green-700',
            $grade === 'A'  => 'bg-green-100 text-green-700',
            $grade === 'B'  => 'bg-blue-100 text-blue-700',
            $grade === 'C'  => 'bg-yellow-100 text-yellow-700',
            $grade === 'D'  => 'bg-orange-100 text-orange-700',
            $grade === 'P'  => 'bg-gray-100 text-gray-600',
            default         => 'bg-red-100 text-red-700',
        };
    };
    $perfRemark = function ($pct) {
        if ($pct > 90)  return ['label' => 'Outstanding', 'cls' => 'bg-emerald-100 text-emerald-700'];
        if ($pct >= 81) return ['label' => 'Excellent',   'cls' => 'bg-green-100 text-green-700'];
        if ($pct >= 71) return ['label' => 'Very Good',   'cls' => 'bg-blue-100 text-blue-700'];
        if ($pct >= 61) return ['label' => 'Good',        'cls' => 'bg-indigo-100 text-indigo-700'];
        if ($pct >= 51) return ['label' => 'Fair',        'cls' => 'bg-yellow-100 text-yellow-700'];
        if ($pct >= 41) return ['label' => 'Average',     'cls' => 'bg-orange-100 text-orange-700'];
        if ($pct >= 35) return ['label' => 'Pass',        'cls' => 'bg-gray-100 text-gray-600'];
        return ['label' => 'Fail', 'cls' => 'bg-red-100 text-red-700'];
    };
@endphp

{{-- ══════════════════════════════════════════════════
     HEADER + TABS + FILTER BAR (exams-style)
══════════════════════════════════════════════════ --}}
<div class="bg-white border-b border-gray-200 sticky top-0 z-30">
    <div class="px-4 sm:px-6 py-3">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">Performance</h1>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                    <span class="pr-4">Records: <strong class="text-gray-800">{{ $totalRecords }}</strong></span>
                    <span class="px-4">Exams: <strong class="text-emerald-600">{{ $totalExamsCount }}</strong></span>
                    <span class="px-4">Students: <strong class="text-blue-600">{{ $totalStudentsCount }}</strong></span>
                    <span class="pl-4">Avg %: <strong class="text-purple-600">{{ number_format($avgPercentage, 1) }}</strong></span>
                </div>
                <button wire:click="openUploadMarks"
                    class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span class="hidden sm:inline">Upload Marks</span>
                    <span class="sm:hidden">Marks</span>
                </button>
            </div>
        </div>
        <div class="flex lg:hidden items-center gap-3 text-xs text-gray-500 mt-3 flex-wrap">
            <span>Records: <strong class="text-gray-800">{{ $totalRecords }}</strong></span>
            <span>Exams: <strong class="text-emerald-600">{{ $totalExamsCount }}</strong></span>
            <span>Students: <strong class="text-blue-600">{{ $totalStudentsCount }}</strong></span>
            <span>Avg %: <strong class="text-purple-600">{{ number_format($avgPercentage, 1) }}</strong></span>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-t border-gray-200 px-4 sm:px-6">
        <div class="flex gap-1">
            @foreach ([
                'subject'    => 'View Marks',
                'performers' => 'Performers',
            ] as $tab => $label)
                <button wire:click="showTab('{{ $tab }}')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors
                        {{ $activeTab === $tab ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- ────────── SUBJECT TAB FILTER BAR ────────── --}}
    @if ($activeTab === 'subject')
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Filter by:
                </div>
                {{-- Exam → Class → Section → Subject. Changing the exam keeps
                     the rest of the filtering exactly as it is. --}}
                <select wire:model.live="filterExam"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[140px]">
                    <option value="">Select Exam</option>
                    @foreach ($exams as $e)<option value="{{ $e->id }}">{{ $e->exam_name }}</option>@endforeach
                </select>
                <select wire:model.live="filterStandard" @disabled(!$filterExam)
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed min-w-[120px]">
                    <option value="">Select Class</option>
                    @foreach ($standards as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                </select>
                <select wire:model.live="filterSection" @disabled(!$filterStandard)
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed min-w-[120px]">
                    <option value="">Select Section</option>
                    @foreach ($sections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                </select>
                <select wire:model.live="filterSubject" @disabled(!$filterSection)
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed min-w-[120px]">
                    <option value="">Select Subject</option>
                    @foreach ($subjects as $sub)<option value="{{ $sub->id }}">{{ $sub->name }}</option>@endforeach
                </select>
                {{-- Last filter: narrow the list to one child. --}}
                <select wire:model.live="filterStudent" @disabled(!$filterSubject)
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed min-w-[160px]">
                    <option value="">All Students</option>
                    @foreach ($students as $st)<option value="{{ $st->id }}">{{ $st->user?->name ?? $st->full_name ?? 'N/A' }}</option>@endforeach
                </select>
                @if ($filterExam || $filterStandard || $filterSection || $filterSubject || $filterStudent)
                    <button wire:click="clearFilters"
                        class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>
    @endif

    {{-- ────────── PERFORMERS TAB FILTER BAR (exams-style) ────────── --}}
    @if ($activeTab === 'performers')
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Filter by:
                </div>
                {{-- Exam → Class → Section, then an optional subject. --}}
                <select wire:model.live="perfExam"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[140px]">
                    <option value="">Select Exam</option>
                    @foreach ($exams as $e)<option value="{{ $e->id }}">{{ $e->exam_name }}</option>@endforeach
                </select>
                <select wire:model.live="perfStandard" @disabled(!$perfExam)
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed min-w-[120px]">
                    <option value="">Select Class</option>
                    @foreach ($standards as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                </select>
                <select wire:model.live="perfSection" @disabled(!$perfStandard)
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed min-w-[120px]">
                    <option value="">Select Section</option>
                    @foreach ($perfSections as $sec)<option value="{{ $sec['id'] }}">{{ $sec['name'] }}</option>@endforeach
                </select>
                <select wire:model.live="perfSubject" @disabled(!$perfSection)
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed min-w-[120px]">
                    <option value="">All Subjects</option>
                    @foreach ($perfSubjects as $sub)<option value="{{ $sub['id'] }}">{{ $sub['name'] }}</option>@endforeach
                </select>
                <select wire:model.live="perfStudent" @disabled(!$perfSection)
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed min-w-[160px]">
                    <option value="">All Students</option>
                    @foreach ($perfStudents as $st)<option value="{{ $st['id'] }}">{{ $st['user']['name'] ?? $st['full_name'] ?? 'N/A' }}</option>@endforeach
                </select>
                @if ($perfExam || $perfStandard || $perfSection || $perfSubject || $perfStudent)
                    <button wire:click="clearPerfFilters"
                        class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>
    @endif
</div>

<div class="p-4 sm:p-6 space-y-4 sm:space-y-5">

{{-- ═══════════════════════════════════════════════
     SUBJECT TAB
═══════════════════════════════════════════════ --}}
@if ($activeTab === 'subject')

    @if (!$filterExam || !$filterStandard || !$filterSection || !$filterSubject)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center">
            <div class="w-14 h-14 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-3-3v6m9 5a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h10l5 5v11z"/></svg>
            </div>
            <p class="text-sm text-gray-600 font-medium">Pick Exam → Class → Section → Subject to see the marks.</p>
            <p class="text-xs text-gray-400 mt-1">Then pick a student to narrow it to one child.</p>
        </div>
    @elseif ($examCopies->total() === 0)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center">
            <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-3-3v6m9 5a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h10l5 5v11z"/></svg>
            </div>
            <p class="text-sm text-gray-600 font-medium">No performance records found.</p>
            <button wire:click="openUploadMarks" class="mt-3 text-sm font-medium text-blue-600 hover:text-blue-800">Upload marks →</button>
        </div>
    @else
        {{-- The Students list's table: S.No, the student (photo, name, admission
             number under it), class, the paper, marks, grade, and the same
             View / Edit buttons. --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">S.No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Admission No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Subject</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Marks</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Grade</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($examCopies as $i => $ec)
                            @php
                                $sd    = $ec->studentDetail;
                                $sName = $sd?->full_name ?: ($sd?->user?->name ?? 'N/A');
                                $sImg  = $sd?->user?->image ?: $sd?->image;
                                $secL  = mb_strtoupper(mb_substr(trim((string) $ec->section?->name), -1));
                            @endphp
                            <tr class="hover:bg-gray-50/70 transition-colors" wire:key="ec-{{ $ec->id }}">
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-500 font-medium">{{ $examCopies->firstItem() + $i }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($sImg)
                                            <img src="{{ $sImg }}" class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                        @else
                                            <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                                <span class="text-xs font-semibold text-indigo-600">{{ strtoupper(substr($sName, 0, 1)) }}</span>
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $sName }}</p>
                                            <p class="text-xs text-gray-400 truncate">{{ $sd?->father_name ?? '' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm font-mono text-gray-700">{{ $sd?->admission_no ?? '—' }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-700 whitespace-nowrap">{{ $ec->standard?->name ?? '—' }}{{ $secL !== '' ? '-' . $secL : '' }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-sm text-gray-700">{{ $ec->subject?->name ?? '—' }}</p>
                                    <p class="text-xs text-gray-400">{{ $ec->exam?->exam_name ?? '' }}</p>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if ($ec->is_absent)
                                        <span class="text-sm text-red-600">Absent</span>
                                    @else
                                        <span class="text-sm font-semibold text-gray-900 tabular-nums">{{ $ec->marks_obtained }}</span><span class="text-sm text-gray-400 tabular-nums"> / {{ $ec->max_marks }}</span>
                                        <p class="text-xs text-gray-400 tabular-nums">{{ $ec->percentage }}%</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($ec->is_absent)
                                        <span class="text-sm text-red-600">AB</span>
                                    @else
                                        <span class="text-sm text-gray-700">{{ $ec->grade_letter }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <button wire:click="onView({{ $ec->id }})" title="View"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                        {{-- View and edit only: marks are corrected, never dropped. --}}
                                        <button wire:click="onEdit({{ $ec->id }})" title="Edit"
                                            class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination, as the Students list's --}}
            @if ($examCopies->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p class="text-sm text-gray-500">
                        Showing <span class="font-medium text-gray-700">{{ $examCopies->firstItem() }}</span>
                        to <span class="font-medium text-gray-700">{{ $examCopies->lastItem() }}</span>
                        of <span class="font-medium text-gray-700">{{ $examCopies->total() }}</span> students
                    </p>
                    <div class="flex items-center gap-1">
                        @if ($examCopies->onFirstPage())
                            <span class="px-3 py-1.5 text-sm text-gray-300 border border-gray-200 rounded-lg cursor-not-allowed">&laquo; Prev</span>
                        @else
                            <button wire:click="previousPage" class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">&laquo; Prev</button>
                        @endif
                        @foreach ($examCopies->getUrlRange(max(1, $examCopies->currentPage() - 2), min($examCopies->lastPage(), $examCopies->currentPage() + 2)) as $page => $url)
                            <button wire:click="gotoPage({{ $page }})"
                                class="px-3 py-1.5 text-sm rounded-lg transition-colors {{ $page == $examCopies->currentPage() ? 'bg-blue-600 text-white border border-blue-600' : 'text-gray-600 border border-gray-300 hover:bg-gray-50' }}">{{ $page }}</button>
                        @endforeach
                        @if ($examCopies->hasMorePages())
                            <button wire:click="nextPage" class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Next &raquo;</button>
                        @else
                            <span class="px-3 py-1.5 text-sm text-gray-300 border border-gray-200 rounded-lg cursor-not-allowed">Next &raquo;</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @endif

{{-- ═══════════════════════════════════════════════
     PERFORMERS TAB
═══════════════════════════════════════════════ --}}
@elseif ($activeTab === 'performers')

    @if (!$perfExam || !$perfStandard || !$perfSection)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center">
            <div class="w-14 h-14 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872"/></svg>
            </div>
            <p class="text-sm text-gray-600 font-medium">Pick Exam → Class → Section to rank the students.</p>
            <p class="text-xs text-gray-400 mt-1">Without a subject they are ranked on their overall marks in that exam; pick one and the ranking is on that subject alone.</p>
        </div>
    @elseif (count($performers) === 0)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-10 text-center">
            <p class="text-sm font-medium text-gray-500">No performance data found for these filters.</p>
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-3">
                <h3 class="font-semibold text-gray-900 text-sm">
                    {{ $perfSubject ? 'Ranked by this subject\'s marks' : 'Ranked by overall marks in this exam' }}
                </h3>
                <span class="ml-auto text-xs text-gray-500">{{ count($performers) }} students</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">Rank</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Admission No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Marks</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Grade</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Remark</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($performers as $p)
                            @php
                                $remark = $perfRemark($p['percentage']);
                                $st     = $p['student'];
                                $pName  = $st?->full_name ?: ($st?->user?->name ?? 'N/A');
                                $pImg   = $st?->user?->image ?: $st?->image;
                                $pSec   = mb_strtoupper(mb_substr(trim((string) $st?->section?->name), -1));
                            @endphp
                            <tr class="hover:bg-gray-50/70 transition-colors">
                                <td class="px-4 py-3"><span class="text-sm text-gray-500 font-medium">{{ $p['rank'] }}</span></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($pImg)
                                            <img src="{{ $pImg }}" class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                        @else
                                            <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                                <span class="text-xs font-semibold text-indigo-600">{{ strtoupper(substr($pName, 0, 1)) }}</span>
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $pName }}</p>
                                            <p class="text-xs text-gray-400 truncate">{{ $st?->father_name ?? '' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3"><span class="text-sm font-mono text-gray-700">{{ $st?->admission_no ?? '—' }}</span></td>
                                <td class="px-4 py-3"><span class="text-sm text-gray-700 whitespace-nowrap">{{ $st?->standard?->name ?? '—' }}{{ $pSec !== '' ? '-' . $pSec : '' }}</span></td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-sm font-semibold text-gray-900 tabular-nums">{{ $p['total_obtained'] }}</span><span class="text-sm text-gray-400 tabular-nums"> / {{ $p['total_max'] }}</span>
                                    <p class="text-xs text-gray-400 tabular-nums">{{ $p['percentage'] }}%</p>
                                </td>
                                <td class="px-4 py-3"><span class="text-sm text-gray-700">{{ $p['grade'] }}</span></td>
                                <td class="px-4 py-3"><span class="text-sm {{ $remark['label'] === 'Fail' ? 'text-red-600' : 'text-gray-700' }}">{{ $remark['label'] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

@endif

</div>

{{-- ═══════════════════════════════════════════════
     UPLOAD MARKS SLIDE-IN (exams-style)
═══════════════════════════════════════════════ --}}
@if ($showUploadModal)
@php
    $markedCount = collect($studentMarks)->filter(fn ($m) => ($m['is_absent'] ?? false) || (($m['marks_obtained'] ?? '') !== '' && $m['marks_obtained'] !== null))->count();
    $uploadExamName = collect($exams)->firstWhere('id', $uploadExam)?->exam_name;
@endphp
{{-- The Mark Attendance panel's look: a toolbar of pickers, one plain row a
     student (number, photo, name over admission number, remark, marks out of
     the exam's total, Absent), and the save in the footer. --}}
<div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
    <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeUploadModal"></div>
    <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col">
        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
            <div class="min-w-0">
                <h2 class="text-lg font-semibold text-gray-900">Upload Marks</h2>
                <p class="text-xs text-gray-500 mt-0.5 truncate">
                    @if ($uploadExam)
                        {{ $uploadExamName }} · total marks {{ $uploadTotalMarks }}
                    @else
                        Select exam, class, section &amp; subject to enter marks
                    @endif
                </p>
            </div>
            <button wire:click="closeUploadModal" type="button"
                class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Toolbar: exam → class → section → subject --}}
        <div class="px-6 py-3 border-b border-gray-100 flex flex-wrap items-center gap-2 flex-shrink-0">
            <select wire:model.live="uploadExam"
                class="text-sm border border-gray-300 rounded-md px-3 py-1.5 bg-white focus:ring-1 focus:ring-gray-400 focus:border-gray-400 @error('uploadExam') border-red-400 @enderror">
                <option value="">Select exam…</option>
                @foreach ($exams as $e)<option value="{{ $e->id }}">{{ $e->exam_name }}</option>@endforeach
            </select>
            <select wire:model.live="uploadStandard"
                class="text-sm border border-gray-300 rounded-md px-3 py-1.5 bg-white focus:ring-1 focus:ring-gray-400 focus:border-gray-400 @error('uploadStandard') border-red-400 @enderror">
                <option value="">Select class…</option>
                @foreach ($standards as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
            </select>
            <select wire:model.live="uploadSection" @disabled(!$uploadStandard)
                class="text-sm border border-gray-300 rounded-md px-3 py-1.5 bg-white disabled:opacity-50 focus:ring-1 focus:ring-gray-400 focus:border-gray-400 @error('uploadSection') border-red-400 @enderror">
                <option value="">Select section…</option>
                @foreach ($sections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
            </select>
            <select wire:model.live="uploadSubject" @disabled(!$uploadSection)
                class="text-sm border border-gray-300 rounded-md px-3 py-1.5 bg-white disabled:opacity-50 focus:ring-1 focus:ring-gray-400 focus:border-gray-400 @error('uploadSubject') border-red-400 @enderror">
                <option value="">Select subject…</option>
                @foreach ($subjects as $sub)<option value="{{ $sub->id }}">{{ $sub->name }}</option>@endforeach
            </select>
            @if (count($studentMarks) > 0)
                <span class="ml-auto text-xs text-gray-400 tabular-nums">{{ $markedCount }} of {{ count($studentMarks) }} entered</span>
            @endif
        </div>

        @if ($errors->hasAny(['uploadExam', 'uploadStandard', 'uploadSection', 'uploadSubject']))
            <p class="px-6 py-2 text-xs text-red-500 border-b border-gray-100 flex-shrink-0">
                {{ $errors->first('uploadExam') ?: ($errors->first('uploadStandard') ?: ($errors->first('uploadSection') ?: $errors->first('uploadSubject'))) }}
            </p>
        @endif

        @if (count($studentMarks) > 0)
            <p class="px-6 py-2 text-xs text-gray-500 border-b border-gray-100 flex-shrink-0">
                Out of {{ $uploadTotalMarks }} for everyone · {{ collect($studentMarks)->where('saved', true)->count() }} already saved ·
                <span class="text-amber-700">a student left blank is saved Absent</span>
            </p>
        @endif

        {{-- Rows --}}
        <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
            @if (!$uploadExam || !$uploadStandard || !$uploadSection || !$uploadSubject)
                <p class="py-16 text-center text-sm text-gray-400">Select an exam, class, section &amp; subject to start entering marks.</p>
            @else
                @forelse ($studentMarks as $studentId => $marks)
                    @php $isAbsent = $marks['is_absent'] ?? false; @endphp
                    <div wire:key="mark-u-{{ $studentId }}" class="flex flex-wrap items-center gap-x-3 gap-y-2 px-6 py-2.5 {{ $isAbsent ? 'bg-red-50/40' : '' }}">
                        <span class="w-4 text-[11px] text-gray-300 tabular-nums flex-shrink-0">{{ $loop->iteration }}</span>
                        @if (!empty($marks['image']))
                            <img src="{{ $marks['image'] }}" class="w-8 h-8 rounded-full object-cover border border-gray-200 flex-shrink-0">
                        @else
                            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 text-[11px] font-medium flex-shrink-0">{{ strtoupper(substr($marks['student_name'], 0, 1)) }}</div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-gray-800 truncate">{{ $marks['student_name'] }}</p>
                            <p class="text-[11px] text-gray-400 truncate">{{ $marks['admission_no'] ?: '—' }}</p>
                        </div>
                        <input type="text" wire:model="studentMarks.{{ $studentId }}.remarks" placeholder="Remark"
                            class="w-28 sm:w-36 text-xs border border-gray-200 rounded-md px-2.5 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        {{-- Marks out of the total, or Absent; deferred wire:model so values sync on Save. --}}
                        <div class="inline-flex items-center rounded-md border border-gray-200 overflow-hidden text-[11px] flex-shrink-0">
                            @if ($isAbsent)
                                <span class="w-[5.5rem] px-2.5 py-1.5 text-center text-gray-300">—</span>
                            @else
                                <input type="number" wire:model="studentMarks.{{ $studentId }}.marks_obtained"
                                    min="0" max="{{ $uploadTotalMarks }}" step="0.01" placeholder="Marks"
                                    class="w-14 px-2 py-1.5 text-xs border-0 focus:ring-0 tabular-nums">
                                <span class="pr-2.5 py-1.5 text-gray-400 tabular-nums">/ {{ $uploadTotalMarks }}</span>
                            @endif
                            <button type="button" wire:click="toggleAbsent({{ $studentId }})" title="{{ $isAbsent ? 'Undo absent' : 'Absent' }}"
                                class="px-2.5 py-1.5 border-l border-gray-200 {{ $isAbsent ? 'bg-red-50 text-red-600 font-medium' : 'text-gray-500 hover:bg-gray-50' }}">Absent</button>
                        </div>
                    </div>
                @empty
                    <p class="py-16 text-center text-sm text-gray-400">No students in this class/section.</p>
                @endforelse
            @endif
        </div>

        {{-- Footer --}}
        <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
            <button wire:click="closeUploadModal" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
            {{-- Target the save itself: without it any other in-flight
                 request (a filter change, the search) disables this. --}}
            <button wire:click="uploadMarks" type="button" wire:loading.attr="disabled" wire:target="uploadMarks"
                class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                <span wire:loading.remove wire:target="uploadMarks">Save All Marks</span>
                <span wire:loading wire:target="uploadMarks">Saving...</span>
            </button>
        </div>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════
     VIEW SLIDE-IN
═══════════════════════════════════════════════ --}}
@if ($showSlider && isset($sliderData['exam_copy']))
@php $ec = $sliderData['exam_copy']; @endphp
<div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
    <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeSlider"></div>
    <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
            <div class="min-w-0">
                <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $sliderTitle }}</h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    <strong class="text-gray-700">{{ $ec->exam?->exam_name ?? '—' }}</strong>
                    · {{ $ec->subject?->name ?? '—' }}
                </p>
            </div>
            <button wire:click="closeSlider" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5 text-sm text-gray-700">
            @if ($ec->is_absent)
                <div class="p-3 bg-red-50 border border-red-200 rounded-md">
                    <p class="text-sm font-medium text-red-800">Student was absent for this exam</p>
                </div>
            @endif

            <div>
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Record</h4>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div><dt class="text-xs text-gray-400">Student</dt><dd class="font-medium">{{ $ec->studentDetail?->user?->name ?? 'N/A' }}</dd></div>
                    <div><dt class="text-xs text-gray-400">Admission No</dt><dd class="font-medium font-mono">{{ $ec->studentDetail?->admission_no ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-400">Class</dt><dd class="font-medium">{{ trim(($ec->standard?->name ?? '') . ' · ' . ($ec->section?->name ?? '')) ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-400">Exam</dt><dd class="font-medium">{{ $ec->exam?->exam_name ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-400">Subject</dt><dd class="font-medium">{{ $ec->subject?->name ?? '—' }}</dd></div>
                </dl>
            </div>

            <div>
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Result</h4>
                <dl class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div><dt class="text-xs text-gray-400">Total</dt><dd class="text-lg font-semibold text-gray-900 tabular-nums">{{ $ec->max_marks }}</dd></div>
                    <div><dt class="text-xs text-gray-400">Obtained</dt><dd class="text-lg font-semibold text-gray-900 tabular-nums">{{ $ec->is_absent ? 'AB' : $ec->marks_obtained }}</dd></div>
                    <div><dt class="text-xs text-gray-400">Percentage</dt><dd class="text-lg font-semibold text-gray-900 tabular-nums">{{ $ec->percentage }}</dd></div>
                    <div><dt class="text-xs text-gray-400">Grade</dt><dd class="text-lg font-semibold text-gray-900">{{ $ec->grade_letter }}</dd></div>
                </dl>
            </div>

            @if ($ec->remarks)
                <div>
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Remarks</h4>
                    <p class="text-sm text-gray-700">{{ $ec->remarks }}</p>
                </div>
            @endif
        </div>
        <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
            <button wire:click="closeSlider" class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
        </div>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════
     EDIT SLIDE-IN
═══════════════════════════════════════════════ --}}
@if ($showEditSlider)
<div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
    <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeEditSlider"></div>
    <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Edit Marks</h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    <strong class="text-gray-700">Exam:</strong> {{ $editMarkData['exam_name'] ?? '—' }}
                    · <strong class="text-gray-700">Subject:</strong> {{ $editMarkData['subject_name'] ?? '—' }}
                </p>
            </div>
            <button wire:click="closeEditSlider" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
            <div class="pb-4 border-b border-gray-100">
                <p class="text-sm font-medium text-gray-900">{{ $editMarkData['student_name'] ?? '—' }}</p>
                <p class="text-xs text-gray-500 mt-0.5">
                    Adm {{ $editMarkData['admission_no'] ?? '—' }}
                    @if (!empty($editMarkData['class_label'])) · {{ $editMarkData['class_label'] }} @endif
                    · {{ $editMarkData['exam_name'] ?? '—' }}
                </p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Max Marks <span class="text-red-500">*</span></label>
                    <input type="number" wire:model="editMarkData.max_marks" min="1"
                        class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    @error('editMarkData.max_marks')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Marks Obtained <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" min="0" wire:model="editMarkData.marks_obtained"
                        class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    @error('editMarkData.marks_obtained')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Remarks</label>
                <input type="text" wire:model="editMarkData.remarks"
                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Optional remark">
            </div>
        </div>
        <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
            <button wire:click="closeEditSlider" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
            <button wire:click="saveEditMark" wire:loading.attr="disabled"
                class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                <span wire:loading.remove wire:target="saveEditMark">Update Marks</span>
                <span wire:loading wire:target="saveEditMark">Saving...</span>
            </button>
        </div>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════
     DELETE CONFIRM OVERLAY (custom, no WireUI dialog)
═══════════════════════════════════════════════ --}}
@if ($showDeleteConfirm)
<div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDelete"></div>
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div class="flex-1">
                <h3 class="text-base font-semibold text-gray-900 mb-1">Delete this record?</h3>
                <p class="text-sm text-gray-500">This will permanently delete the exam record for this student/subject.</p>
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 mt-5">
            <button wire:click="cancelDelete" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
            <button wire:click="confirmDelete" wire:loading.attr="disabled"
                class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md disabled:opacity-60 flex items-center gap-1.5">
                <span wire:loading.remove wire:target="confirmDelete">Delete</span>
                <span wire:loading wire:target="confirmDelete">Deleting...</span>
            </button>
        </div>
    </div>
</div>
@endif

</div>
