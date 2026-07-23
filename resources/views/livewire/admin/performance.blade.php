<div class="min-h-screen bg-gray-50">

@php
    $gradeBadge = function ($grade) {
        return match (true) {
            in_array($grade, ['A+', 'A']) => 'bg-green-100 text-green-700',
            in_array($grade, ['B+', 'B']) => 'bg-blue-100 text-blue-700',
            in_array($grade, ['C+', 'C']) => 'bg-yellow-100 text-yellow-700',
            $grade === 'D'                => 'bg-orange-100 text-orange-700',
            default                       => 'bg-red-100 text-red-700',
        };
    };
    $perfRemark = function ($pct) {
        if ($pct >= 90) return ['label' => 'Outstanding', 'cls' => 'bg-emerald-100 text-emerald-700'];
        if ($pct >= 80) return ['label' => 'Excellent',   'cls' => 'bg-green-100 text-green-700'];
        if ($pct >= 70) return ['label' => 'Very Good',   'cls' => 'bg-blue-100 text-blue-700'];
        if ($pct >= 60) return ['label' => 'Good',        'cls' => 'bg-indigo-100 text-indigo-700'];
        if ($pct >= 50) return ['label' => 'Average',     'cls' => 'bg-yellow-100 text-yellow-700'];
        if ($pct >= 40) return ['label' => 'Below Avg',   'cls' => 'bg-orange-100 text-orange-700'];
        if ($pct >= 33) return ['label' => 'Pass',        'cls' => 'bg-gray-100 text-gray-600'];
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
                'subject'    => 'View by Subject',
                'student'    => 'View by Student',
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
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search student / exam / subject..."
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-56 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                <select wire:model.live="filterStandard"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[120px]">
                    <option value="">Select Class</option>
                    @foreach ($standards as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                </select>
                <select wire:model.live="filterSection" @disabled(!$filterStandard)
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed min-w-[120px]">
                    <option value="">Select Section</option>
                    @foreach ($sections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                </select>
                <select wire:model.live="filterExam"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[120px]">
                    <option value="">All Exams</option>
                    @foreach ($exams as $e)<option value="{{ $e->id }}">{{ $e->exam_name }}</option>@endforeach
                </select>
                <select wire:model.live="filterSubject" @disabled(!$filterStandard)
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed min-w-[120px]">
                    <option value="">All Subjects</option>
                    @foreach ($subjects as $sub)<option value="{{ $sub->id }}">{{ $sub->name }}</option>@endforeach
                </select>
                @if ($search || $filterExam || $filterStandard || $filterSection || $filterSubject)
                    <button wire:click="clearFilters"
                        class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>
    @endif

    {{-- ────────── STUDENT TAB FILTER BAR (exams-style) ────────── --}}
    @if ($activeTab === 'student')
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Filter by:
                </div>
                <select wire:model.live="selectedExam"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[140px]">
                    <option value="">Select Exam</option>
                    @foreach ($exams as $e)<option value="{{ $e->id }}">{{ $e->exam_name }}</option>@endforeach
                </select>
                <select wire:model.live="selectedStandard"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[120px]">
                    <option value="">Select Class</option>
                    @foreach ($standards as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                </select>
                <select wire:model.live="selectedSection" @disabled(!$selectedStandard)
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed min-w-[120px]">
                    <option value="">Select Section</option>
                    @foreach ($sections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                </select>
                <select wire:model.live="selectedSubject" @disabled(!$selectedSection)
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed min-w-[120px]">
                    <option value="">All Subjects</option>
                    @foreach ($subjects as $sub)<option value="{{ $sub->id }}">{{ $sub->name }}</option>@endforeach
                </select>
                <select wire:model.live="selectedStudent" @disabled(!$selectedSection)
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed min-w-[160px]">
                    <option value="">Select Student</option>
                    @foreach ($students as $st)<option value="{{ $st->id }}">{{ $st->user?->name ?? $st->full_name ?? 'N/A' }}</option>@endforeach
                </select>
                @if ($selectedExam || $selectedStandard || $selectedSection || $selectedSubject || $selectedStudent)
                    <button wire:click="clearStudentFilters"
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
                <select wire:model.live="perfExam"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[140px]">
                    <option value="">All Exams</option>
                    @foreach ($exams as $e)<option value="{{ $e->id }}">{{ $e->exam_name }}</option>@endforeach
                </select>
                <select wire:model.live="perfStandard"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[120px]">
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

    @if (!$filterStandard || !$filterSection)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center">
            <div class="w-14 h-14 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-3-3v6m9 5a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h10l5 5v11z"/></svg>
            </div>
            <p class="text-sm text-gray-600 font-medium">Select a class and section to view performance records.</p>
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
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">S.No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Adm No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Exam · Subject</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Obtained</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Grade</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($examCopies as $i => $ec)
                            <tr class="hover:bg-gray-50/70 transition-colors">
                                <td class="px-4 py-3 text-sm text-gray-500 font-medium">{{ $examCopies->firstItem() + $i }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2.5">
                                        @if ($ec->studentDetail?->image)
                                            <img src="{{ $ec->studentDetail->image }}" class="w-8 h-8 rounded-full object-cover border border-gray-100">
                                        @else
                                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                                <span class="text-xs font-semibold text-indigo-600">{{ strtoupper(substr($ec->studentDetail?->user?->name ?? 'S', 0, 1)) }}</span>
                                            </div>
                                        @endif
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800">{{ $ec->studentDetail?->user?->name ?? 'N/A' }}</p>
                                            <p class="text-xs text-gray-400">Roll: {{ $ec->studentDetail?->roll_no ?? '—' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500">{{ $ec->studentDetail?->admission_no ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <div class="font-medium">{{ $ec->exam?->exam_name ?? '—' }}</div>
                                    <div class="text-xs text-gray-500">{{ $ec->subject?->name ?? '—' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-700">{{ $ec->max_marks }}</td>
                                <td class="px-4 py-3">
                                    @if ($ec->is_absent)
                                        <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-red-100 text-red-700">Absent</span>
                                    @else
                                        <span class="font-semibold text-gray-800">{{ $ec->marks_obtained }}</span>
                                        <div class="text-xs text-gray-400">{{ $ec->percentage }}%</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($ec->is_absent)
                                        <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-red-100 text-red-700">AB</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full {{ $gradeBadge($ec->grade) }}">{{ $ec->grade }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <button wire:click="onView({{ $ec->id }})" title="View"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                        <button wire:click="onEdit({{ $ec->id }})" title="Edit"
                                            class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button wire:click="onDelete({{ $ec->id }})" title="Delete"
                                            class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($examCopies->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">{{ $examCopies->links() }}</div>
            @endif
        </div>
    @endif

{{-- ═══════════════════════════════════════════════
     STUDENT TAB
═══════════════════════════════════════════════ --}}
@elseif ($activeTab === 'student')

    @if (!$selectedExam || !$selectedStandard || !$selectedSection || !$selectedStudent)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center">
            <div class="w-14 h-14 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
            <p class="text-sm text-gray-600 font-medium">Pick Exam → Class → Section → Student to view performance.</p>
            <p class="text-xs text-gray-400 mt-1">Subject filter is optional.</p>
        </div>
    @elseif (count($studentPerformance) > 0)
        @php
            $student    = collect($students)->firstWhere('id', $selectedStudent);
            $totalObt   = collect($studentPerformance)->sum('marks_obtained');
            $totalMax   = collect($studentPerformance)->sum('max_marks');
            $overallPct = $totalMax > 0 ? round(($totalObt / $totalMax) * 100, 2) : 0;
            $examName   = $studentPerformance[0]['exam']['exam_name'] ?? collect($exams)->firstWhere('id', (int) $selectedExam)?->exam_name ?? '—';
        @endphp

        {{-- Merged student card with subject table inside --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            {{-- Header --}}
            <div class="flex flex-col sm:flex-row sm:items-center gap-4 px-5 sm:px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-blue-50/40 to-transparent">
                @if ($student?->image)
                    <img src="{{ $student->image }}" class="w-16 h-16 rounded-full object-cover border-2 border-white shadow-sm">
                @else
                    <div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center shadow-sm">
                        <span class="text-xl font-bold text-indigo-600">{{ strtoupper(substr($student?->user?->name ?? 'S', 0, 1)) }}</span>
                    </div>
                @endif
                <div class="flex-1 min-w-0">
                    <h2 class="text-lg font-bold text-gray-900 truncate">{{ $student?->user?->name ?? 'N/A' }}</h2>
                    <div class="flex flex-wrap gap-x-3 gap-y-0.5 mt-0.5">
                        <span class="text-xs text-gray-500">Roll: <strong class="text-gray-700">{{ $student?->roll_no ?? '—' }}</strong></span>
                        <span class="text-xs text-gray-500">Adm: <strong class="text-gray-700">{{ $student?->admission_no ?? '—' }}</strong></span>
                        <span class="text-xs text-gray-500">Exam: <strong class="text-blue-700">{{ $examName }}</strong></span>
                    </div>
                </div>
                <div class="flex items-center gap-4 sm:border-l sm:border-gray-200 sm:pl-5">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-blue-600">{{ $overallPct }}%</p>
                        <p class="text-[10px] uppercase tracking-wider text-gray-400 mt-0.5">Overall</p>
                    </div>
                    <div class="text-center hidden sm:block">
                        <p class="text-base font-semibold text-gray-700">{{ $totalObt }} / {{ $totalMax }}</p>
                        <p class="text-[10px] uppercase tracking-wider text-gray-400 mt-0.5">Marks</p>
                    </div>
                </div>
            </div>

            {{-- Subject-wise table (Total before Obtained) --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide w-12">#</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Subject</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Total</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Obtained</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">%</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Grade</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($studentPerformance as $i => $perf)
                            <tr class="hover:bg-gray-50/70">
                                <td class="px-5 py-3 text-gray-400 text-xs font-medium">{{ $i + 1 }}</td>
                                <td class="px-5 py-3 font-semibold text-gray-800">{{ $perf['subject']['name'] ?? '—' }}</td>
                                <td class="px-5 py-3 text-sm text-gray-700">{{ $perf['max_marks'] ?? '—' }}</td>
                                <td class="px-5 py-3 font-bold text-blue-600">{{ $perf['marks_obtained'] ?? '—' }}</td>
                                <td class="px-5 py-3 text-sm font-medium text-gray-600">{{ $perf['percentage'] ?? 0 }}%</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full {{ $gradeBadge($perf['grade'] ?? 'F') }}">{{ $perf['grade'] ?? '—' }}</span>
                                </td>
                                <td class="px-5 py-3 text-gray-500 text-xs">{{ $perf['remarks'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-blue-50 font-semibold">
                            <td class="px-5 py-3" colspan="2"><span class="text-sm text-blue-700">Total</span></td>
                            <td class="px-5 py-3 text-blue-600">{{ $totalMax }}</td>
                            <td class="px-5 py-3 text-blue-700">{{ $totalObt }}</td>
                            <td class="px-5 py-3 text-blue-700 font-bold">{{ $overallPct }}%</td>
                            <td colspan="2"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-10 text-center">
            <p class="text-sm font-medium text-gray-500">No records found for the selected criteria.</p>
        </div>
    @endif

{{-- ═══════════════════════════════════════════════
     PERFORMERS TAB
═══════════════════════════════════════════════ --}}
@elseif ($activeTab === 'performers')

    @if (!$perfStandard || !$perfSection)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center">
            <div class="w-14 h-14 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872"/></svg>
            </div>
            <p class="text-sm text-gray-600 font-medium">Pick a class and section to view performers.</p>
            <p class="text-xs text-gray-400 mt-1">Exam / Subject / Student filters narrow the list further.</p>
        </div>
    @elseif (count($performers) === 0)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-10 text-center">
            <p class="text-sm font-medium text-gray-500">No performance data found for these filters.</p>
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-3">
                <h3 class="font-semibold text-gray-900 text-sm">Ranked by Obtained Marks (desc)</h3>
                <span class="ml-auto text-xs font-medium bg-indigo-100 text-indigo-700 px-2.5 py-1 rounded-full">{{ count($performers) }} students</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide w-16">Rank</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Adm No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Class</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Total</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Obtained</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">%</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Grade</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Remark</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($performers as $p)
                            @php
                                $remark = $perfRemark($p['percentage']);
                                $rankBg = match($p['rank']) {
                                    1 => 'bg-amber-400 text-white',
                                    2 => 'bg-slate-400 text-white',
                                    3 => 'bg-orange-400 text-white',
                                    default => 'bg-gray-100 text-gray-600',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50 {{ $p['rank'] <= 3 ? 'bg-amber-50/30' : '' }}">
                                <td class="px-4 py-3"><span class="inline-flex w-7 h-7 items-center justify-center rounded-full text-xs font-bold {{ $rankBg }}">{{ $p['rank'] }}</span></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2.5">
                                        @if ($p['student']?->image)
                                            <img src="{{ $p['student']->image }}" class="w-8 h-8 rounded-full object-cover border border-gray-100">
                                        @else
                                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                                <span class="text-xs font-semibold text-indigo-600">{{ strtoupper(substr($p['student']?->user?->name ?? 'S', 0, 1)) }}</span>
                                            </div>
                                        @endif
                                        <div>
                                            <p class="font-semibold text-gray-800 text-sm">{{ $p['student']?->user?->name ?? 'N/A' }}</p>
                                            <p class="text-xs text-gray-400">Roll: {{ $p['student']?->roll_no ?? '—' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-500 text-xs">{{ $p['student']?->admission_no ?? '—' }}</td>
                                <td class="px-4 py-3 text-xs text-gray-600">{{ $p['student']?->standard?->name ?? '—' }} {{ $p['student']?->section?->name ?? '' }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-700">{{ $p['total_max'] }}</td>
                                <td class="px-4 py-3 font-semibold text-gray-800">{{ $p['total_obtained'] }}</td>
                                <td class="px-4 py-3 font-bold text-blue-600">{{ $p['percentage'] }}%</td>
                                <td class="px-4 py-3"><span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full {{ $gradeBadge($p['grade']) }}">{{ $p['grade'] }}</span></td>
                                <td class="px-4 py-3"><span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full {{ $remark['cls'] }}">{{ $remark['label'] }}</span></td>
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
<div class="fixed inset-0 z-50 overflow-hidden">
    <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeUploadModal"></div>
    <div class="absolute top-0 right-0 bottom-0 w-full max-w-5xl bg-white shadow-2xl flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Upload Marks</h2>
                <p class="text-xs text-gray-500 mt-0.5">Select exam, class, section &amp; subject to enter marks</p>
            </div>
            <button wire:click="closeUploadModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex-shrink-0">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Exam <span class="text-red-500">*</span></label>
                    <select wire:model.live="uploadExam"
                        class="w-full px-3 py-2 border border-gray-200 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                        <option value="">Select Exam</option>
                        @foreach ($exams as $e)<option value="{{ $e->id }}">{{ $e->exam_name }}</option>@endforeach
                    </select>
                    @error('uploadExam')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Class <span class="text-red-500">*</span></label>
                    <select wire:model.live="uploadStandard"
                        class="w-full px-3 py-2 border border-gray-200 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                        <option value="">Select Class</option>
                        @foreach ($standards as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                    </select>
                    @error('uploadStandard')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Section <span class="text-red-500">*</span></label>
                    <select wire:model.live="uploadSection" @disabled(!$uploadStandard)
                        class="w-full px-3 py-2 border border-gray-200 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white disabled:opacity-50">
                        <option value="">Select Section</option>
                        @foreach ($sections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                    </select>
                    @error('uploadSection')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Subject <span class="text-red-500">*</span></label>
                    <select wire:model.live="uploadSubject" @disabled(!$uploadSection)
                        class="w-full px-3 py-2 border border-gray-200 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white disabled:opacity-50">
                        <option value="">Select Subject</option>
                        @foreach ($subjects as $sub)<option value="{{ $sub->id }}">{{ $sub->name }}</option>@endforeach
                    </select>
                    @error('uploadSubject')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
            </div>
            @if ($uploadExam)
                <p class="mt-3 text-[11px] text-blue-700">Exam total marks: <strong>{{ $uploadTotalMarks }}</strong> — auto-applied to every student row below.</p>
            @endif
        </div>

        <div class="flex-1 overflow-y-auto">
            @if (!$uploadExam || !$uploadStandard || !$uploadSection || !$uploadSubject)
                <div class="flex flex-col items-center justify-center py-20 text-center px-8">
                    <div class="w-14 h-14 bg-blue-50 rounded-full flex items-center justify-center mb-3">
                        <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5"/></svg>
                    </div>
                    <p class="text-sm font-medium text-gray-700">Pick Exam, Class, Section &amp; Subject</p>
                    <p class="text-xs text-gray-400 mt-1">Student list will appear here</p>
                </div>
            @elseif (count($studentMarks) === 0)
                <div class="flex flex-col items-center justify-center py-20 text-center">
                    <p class="text-sm font-medium text-gray-600">No students found for this section.</p>
                </div>
            @else
                @php
                    $uploadStd = collect($standards)->firstWhere('id', $uploadStandard);
                    $uploadSec = collect($sections)->firstWhere('id', $uploadSection);
                    $uploadSub = collect($subjects)->firstWhere('id', $uploadSubject);
                @endphp
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 z-10 bg-gray-50">
                            <tr class="border-b border-gray-200">
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide w-10">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide min-w-[160px]">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Adm No</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Class</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Subject</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide w-24">Total</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide w-28">Marks</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide min-w-[120px]">Remark</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($studentMarks as $studentId => $marks)
                                @php $isSaved = $marks['saved'] ?? false; $isAbsent = $marks['is_absent'] ?? false; @endphp
                                <tr class="{{ $isAbsent ? 'bg-red-50/40' : ($isSaved ? 'bg-emerald-50/40' : 'bg-white') }} hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-400 text-xs">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            @if (!empty($marks['image']))
                                                <img src="{{ $marks['image'] }}" class="w-8 h-8 rounded-full object-cover border border-gray-100">
                                            @else
                                                <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                                    <span class="text-xs font-semibold text-indigo-600">{{ strtoupper(substr($marks['student_name'], 0, 1)) }}</span>
                                                </div>
                                            @endif
                                            <div>
                                                <p class="font-semibold text-gray-800 text-sm">{{ $marks['student_name'] }}</p>
                                                <p class="text-xs text-gray-400">Roll: {{ $marks['roll_no'] ?? '—' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">{{ $marks['admission_no'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-600">{{ $uploadStd?->name ?? '' }} <span class="text-gray-400">·</span> {{ $uploadSec?->name ?? '' }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-600">{{ $uploadSub?->name ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="text-sm font-semibold text-gray-700">{{ $uploadTotalMarks }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($isAbsent)
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700">Absent</span>
                                                <button type="button" wire:click="toggleAbsent({{ $studentId }})"
                                                    class="text-[11px] font-medium text-blue-600 hover:text-blue-800">Undo</button>
                                            </div>
                                        @else
                                            {{-- Deferred wire:model so values sync on Save click (no debounce race). --}}
                                            <div class="flex items-center gap-2">
                                                <input type="number"
                                                    wire:model="studentMarks.{{ $studentId }}.marks_obtained"
                                                    class="w-24 px-2 py-1.5 border border-gray-200 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white"
                                                    min="0" max="{{ $uploadTotalMarks }}" step="0.01" placeholder="Obtained">
                                                <button type="button" wire:click="toggleAbsent({{ $studentId }})"
                                                    class="text-[11px] font-medium text-red-500 hover:text-red-700 whitespace-nowrap">Absent</button>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="text"
                                            wire:model="studentMarks.{{ $studentId }}.remarks"
                                            class="w-full px-2 py-1.5 border border-gray-200 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white"
                                            placeholder="Remark…">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="flex items-center justify-between gap-3 px-6 py-3.5 border-t border-gray-200 flex-shrink-0 bg-gray-50/50">
            <p class="text-xs text-gray-400">
                @if (count($studentMarks) > 0)
                    {{ count($studentMarks) }} students · {{ collect($studentMarks)->where('saved', true)->count() }} saved
                    <span class="text-amber-600">· students left blank will be marked <strong>Absent</strong> on save</span>
                @else
                    Total marks: <strong class="text-gray-700">{{ $uploadTotalMarks }}</strong>
                @endif
            </p>
            <div class="flex gap-2">
                <button wire:click="closeUploadModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                <button wire:click="uploadMarks" wire:loading.attr="disabled"
                    class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                    <span wire:loading.remove wire:target="uploadMarks">Save All Marks</span>
                    <span wire:loading wire:target="uploadMarks">Saving…</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════
     VIEW SLIDE-IN
═══════════════════════════════════════════════ --}}
@if ($showSlider && isset($sliderData['exam_copy']))
@php $ec = $sliderData['exam_copy']; @endphp
<div class="fixed inset-0 z-50 overflow-hidden">
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
        <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
            @foreach ([
                ['Student',  $ec->studentDetail?->user?->name ?? 'N/A'],
                ['Adm No',   $ec->studentDetail?->admission_no ?? '—'],
                ['Class',    trim(($ec->standard?->name ?? '') . ' · ' . ($ec->section?->name ?? ''))],
                ['Exam',     $ec->exam?->exam_name ?? '—'],
                ['Subject',  $ec->subject?->name ?? '—'],
            ] as [$label, $value])
                <div class="grid grid-cols-3 gap-3 text-sm">
                    <span class="text-xs text-gray-400 uppercase tracking-wider">{{ $label }}</span>
                    <span class="col-span-2 text-gray-800 font-medium">{{ $value }}</span>
                </div>
            @endforeach
            @if ($ec->is_absent)
                <div class="mt-2 p-3 bg-red-50 rounded-md border border-red-100 text-center">
                    <p class="text-sm font-bold text-red-700">Student was Absent for this exam</p>
                </div>
            @endif
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-2">
                <div class="p-3 bg-emerald-50 rounded-md border border-emerald-100 text-center">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-emerald-600 mb-1">Total</p>
                    <p class="text-2xl font-bold text-emerald-700">{{ $ec->max_marks }}</p>
                </div>
                <div class="p-3 bg-blue-50 rounded-md border border-blue-100 text-center">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-blue-600 mb-1">Obtained</p>
                    <p class="text-2xl font-bold text-blue-700">{{ $ec->is_absent ? 'AB' : $ec->marks_obtained }}</p>
                </div>
                <div class="p-3 bg-purple-50 rounded-md border border-purple-100 text-center">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-purple-600 mb-1">%</p>
                    <p class="text-2xl font-bold text-purple-700">{{ $ec->percentage }}</p>
                </div>
                <div class="p-3 bg-orange-50 rounded-md border border-orange-100 text-center">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-orange-600 mb-1">Grade</p>
                    <p class="text-2xl font-bold text-orange-700">{{ $ec->grade }}</p>
                </div>
            </div>
            @if ($ec->remarks)
                <div class="p-3 bg-gray-50 rounded-md border border-gray-200">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 mb-1">Remarks</p>
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
<div class="fixed inset-0 z-50 overflow-hidden">
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
            <div class="bg-gray-50 border border-gray-200 rounded-md p-3 text-sm space-y-1">
                <div><strong>{{ $editMarkData['student_name'] ?? '—' }}</strong> <span class="text-gray-500">· Adm {{ $editMarkData['admission_no'] ?? '—' }}</span></div>
                <div class="text-xs text-gray-500">{{ $editMarkData['class_label'] ?? '' }}</div>
                <div class="text-xs text-blue-700 mt-1">Exam: <strong>{{ $editMarkData['exam_name'] ?? '—' }}</strong></div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Max Marks <span class="text-red-500">*</span></label>
                    <input type="number" wire:model="editMarkData.max_marks" min="1"
                        class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500">
                    @error('editMarkData.max_marks')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Marks Obtained <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" min="0" wire:model="editMarkData.marks_obtained"
                        class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500">
                    @error('editMarkData.marks_obtained')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Remarks</label>
                <input type="text" wire:model="editMarkData.remarks"
                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500"
                    placeholder="Optional remark…">
            </div>
        </div>
        <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
            <button wire:click="closeEditSlider" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
            <button wire:click="saveEditMark" wire:loading.attr="disabled"
                class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                <span wire:loading.remove wire:target="saveEditMark">Update Marks</span>
                <span wire:loading wire:target="saveEditMark">Saving…</span>
            </button>
        </div>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════
     DELETE CONFIRM OVERLAY (custom, no WireUI dialog)
═══════════════════════════════════════════════ --}}
@if ($showDeleteConfirm)
<div class="fixed inset-0 z-50 flex items-center justify-center p-4">
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
                <span wire:loading wire:target="confirmDelete">Deleting…</span>
            </button>
        </div>
    </div>
</div>
@endif

</div>
