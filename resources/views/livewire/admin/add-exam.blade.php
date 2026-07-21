<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════
         HEADER (full-width, sticky, analytics + tabs + dynamic Add button)
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3 sm:py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-lg sm:text-2xl font-bold text-gray-900">Exams</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Manage exams and their subject-wise syllabus</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                        <span class="pr-4">Total: <strong class="text-gray-800">{{ $totalExams }}</strong></span>
                        <span class="px-4">Published: <strong class="text-emerald-600">{{ $publishedExams }}</strong></span>
                        <span class="px-4">Upcoming: <strong class="text-amber-500">{{ $upcomingExams }}</strong></span>
                        <span class="pl-4">Syllabus: <strong class="text-blue-600">{{ $totalSyllabusRows }}</strong></span>
                    </div>

                    @if ($activeTab === 'exams')
                        <button wire:click="onAddExam"
                            class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="hidden sm:inline">Add Exam</span>
                            <span class="sm:hidden">New</span>
                        </button>
                    @elseif ($activeTab === 'syllabus')
                        <button wire:click="onAddSyllabus"
                            class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="hidden sm:inline">Add Syllabus</span>
                            <span class="sm:hidden">New</span>
                        </button>
                    @elseif ($activeTab === 'papers')
                        <button wire:click="openPaperModal"
                            class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            <span class="hidden sm:inline">Upload Paper</span>
                            <span class="sm:hidden">Upload</span>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Mobile/Tablet stats --}}
            <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Total: <strong class="text-gray-800">{{ $totalExams }}</strong></span>
                <span>Published: <strong class="text-emerald-600">{{ $publishedExams }}</strong></span>
                <span>Upcoming: <strong class="text-amber-500">{{ $upcomingExams }}</strong></span>
                <span>Syllabus: <strong class="text-blue-600">{{ $totalSyllabusRows }}</strong></span>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="border-t border-gray-200 px-4 sm:px-6">
            <div class="flex gap-1">
                <button wire:click="setTab('exams')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors
                           {{ $activeTab === 'exams' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        Exams
                    </span>
                </button>
                <button wire:click="setTab('syllabus')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors
                           {{ $activeTab === 'syllabus' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        Exam Syllabus
                    </span>
                </button>
                <button wire:click="setTab('papers')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors
                           {{ $activeTab === 'papers' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Exam Papers
                    </span>
                </button>
            </div>
        </div>

        {{-- Filter bar (changes by tab) --}}
        @if ($activeTab === 'exams')
            <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filter by:
                    </div>

                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search exam name..."
                        class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-48 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />

                    <select wire:model.live="filterAcademicYear" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="">All Years</option>
                        @foreach ($academicYearOptions as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filterExamType" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="">All Types</option>
                        @foreach ($examTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filterTerm" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="">All Terms</option>
                        @foreach ($termOptions as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filterStatus" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="">All Status</option>
                        <option value="published">Published</option>
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="upcoming">Upcoming</option>
                        <option value="completed">Completed</option>
                    </select>

                    @if ($search || $filterAcademicYear || $filterExamType || $filterTerm || $filterStatus)
                        <button wire:click="clearExamFilters"
                            class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            Clear
                        </button>
                    @endif
                </div>
            </div>
        @elseif ($activeTab === 'papers')
            {{-- Exam Papers tab — Exam → Class → Section --}}
            <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filter by:
                    </div>

                    <select wire:model.live="filterPaperExam" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="">All Exams</option>
                        @foreach ($allExams as $e)
                            <option value="{{ $e['id'] }}">{{ $e['exam_name'] }} ({{ $e['academic_year'] }})</option>
                        @endforeach
                    </select>

                    <span class="text-gray-300">→</span>

                    <select wire:model.live="filterPaperStandard" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="">All Classes</option>
                        @foreach ($allStandards as $s)
                            <option value="{{ $s['id'] }}">{{ $s['name'] }}</option>
                        @endforeach
                    </select>

                    <span class="text-gray-300">→</span>

                    <select wire:model.live="filterPaperSection" @disabled(!$filterPaperStandard)
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                        <option value="">All Sections</option>
                        @foreach ($paperFilterSections as $sec)
                            <option value="{{ $sec['id'] }}">{{ $sec['name'] }}</option>
                        @endforeach
                    </select>

                    @if ($filterPaperExam || $filterPaperStandard || $filterPaperSection)
                        <button wire:click="clearPaperFilters"
                            class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            Clear
                        </button>
                    @endif
                </div>
            </div>
        @else
            {{-- Syllabus tab — Exam → Class → Section → Subject --}}
            <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        View:
                    </div>

                    <select wire:model.live="syllabusFilterExam" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="">Select Exam</option>
                        @foreach ($allExams as $e)
                            <option value="{{ $e['id'] }}">{{ $e['exam_name'] }} ({{ $e['academic_year'] }})</option>
                        @endforeach
                    </select>

                    <span class="text-gray-300">→</span>

                    <select wire:model.live="syllabusFilterStandard" @disabled(!$syllabusFilterExam)
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                        <option value="">Select Class</option>
                        @foreach ($allStandards as $s)
                            <option value="{{ $s['id'] }}">{{ $s['name'] }}</option>
                        @endforeach
                    </select>

                    <span class="text-gray-300">→</span>

                    <select wire:model.live="syllabusFilterSection" @disabled(!$syllabusFilterStandard)
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                        <option value="">Select Section</option>
                        @foreach ($filterSections as $sec)
                            <option value="{{ $sec['id'] }}">{{ $sec['name'] }}</option>
                        @endforeach
                    </select>

                    <span class="text-gray-300">→</span>

                    <select wire:model.live="syllabusFilterSubject" @disabled(!$syllabusFilterSection)
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                        <option value="">Select Subject</option>
                        @foreach ($filterSubjects as $sub)
                            <option value="{{ $sub['id'] }}">{{ $sub['name'] }}</option>
                        @endforeach
                    </select>

                    @if ($syllabusFilterExam || $syllabusFilterStandard || $syllabusFilterSection || $syllabusFilterSubject)
                        <button wire:click="clearSyllabusFilters"
                            class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            Clear
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════
         BODY
    ══════════════════════════════════════════════════ --}}
    <div class="p-4 sm:p-6">

        @if ($activeTab === 'exams')
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Exam</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Year</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Dates</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Marks</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Passing Marks</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($exams as $exam)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-semibold text-gray-900">{{ $exam->exam_name }}</p>
                                            @if ($exam->term)
                                                <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-600">{{ $exam->term }}</span>
                                            @endif
                                        </div>
                                        @if ($exam->description)
                                            <p class="text-xs text-gray-400 line-clamp-1 mt-0.5">{{ $exam->description }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-xs font-medium px-2 py-0.5 rounded bg-gray-100 text-gray-700">
                                            {{ $examTypes[$exam->exam_type] ?? $exam->exam_type }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $exam->academic_year }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-600">
                                        {{ $exam->start_date?->format('d M Y') }} →<br>
                                        {{ $exam->end_date?->format('d M Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm font-semibold text-gray-800">
                                        {{ ($exam->uses_grading_system ?? false) ? '—' : ($exam->total_marks ?? '—') }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm font-semibold text-gray-800">
                                        {{ ($exam->uses_grading_system ?? false) ? '—' : ($exam->passing_marks ?? '—') }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button wire:click="onTogglePublish({{ $exam->id }})"
                                            class="text-[11px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide
                                                {{ $exam->is_published ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                            {{ $exam->is_published ? 'Published' : 'Draft' }}
                                        </button>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <button wire:click="onViewExam({{ $exam->id }})" title="View"
                                                class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                            <button wire:click="onEditExam({{ $exam->id }})" title="Edit"
                                                class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-amber-50 hover:text-amber-600 hover:border-amber-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button wire:click="onDeleteExam({{ $exam->id }})" title="Delete"
                                                class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-red-50 hover:text-red-600 hover:border-red-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-16 text-center">
                                        <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-gray-800">No exams found</p>
                                        <p class="text-xs text-gray-400 mt-1">Create your first exam using the button above.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($exams->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">
                        {{ $exams->links() }}
                    </div>
                @endif
            </div>

        @elseif ($activeTab === 'papers')
            {{-- ═════ EXAM PAPERS TAB ═════ --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Exam</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Section</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Uploaded</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($examPapers as $paper)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-lg bg-red-50 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <span class="text-sm font-semibold text-gray-900">{{ $paper->title }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $paper->exam->exam_name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $paper->standard->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $paper->section->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-500">{{ $paper->created_at->format('d M Y, g:i A') }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <button wire:click="downloadPaper({{ $paper->id }})" title="Download"
                                                class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                </svg>
                                            </button>
                                            <button wire:click="openEditPaperModal({{ $paper->id }})" title="Edit"
                                                class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-amber-50 hover:text-amber-600 hover:border-amber-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button wire:click="onDeletePaper({{ $paper->id }})" title="Delete"
                                                class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-red-50 hover:text-red-600 hover:border-red-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-16 text-center">
                                        <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-gray-800">No exam papers uploaded</p>
                                        <p class="text-xs text-gray-400 mt-1">Click "Upload Paper" to add a question paper for an exam.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($examPapers->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">{{ $examPapers->links() }}</div>
                @endif
            </div>

        @else
            @if ($syllabus['mode'] === 'detail')
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-start justify-between gap-3 flex-wrap">
                        <div class="min-w-0">
                            {{-- Breadcrumb summary: Exam › Class › Section › Subject --}}
                            <div class="flex flex-wrap items-center gap-1.5 text-xs text-gray-500 mb-1">
                                <span class="font-semibold text-gray-700">{{ $syllabus['exam_name'] ?? '—' }}</span>
                                <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                <span>{{ $syllabus['standard_name'] ?? '—' }}</span>
                                @if ($syllabus['section_name'] ?? null)
                                    <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                    <span>{{ $syllabus['section_name'] }}</span>
                                @endif
                                <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                <span class="font-semibold text-blue-700">{{ $syllabus['subject_name'] ?? '—' }}</span>
                            </div>
                            <h3 class="text-base font-semibold text-gray-900">Selected Syllabus</h3>
                            <p class="text-xs text-gray-500 mt-0.5">{{ count($syllabus['chapters']) }} chapter(s) included</p>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button wire:click="clearSyllabusFilters"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-200 rounded-md hover:bg-gray-50">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                                Back to list
                            </button>
                            <button wire:click="onEditSyllabus({{ $syllabus['exam_id'] }}, {{ $syllabus['standard_id'] }}, {{ $syllabus['subject_id'] }}, {{ $syllabus['section_id'] ?? 'null' }})"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-md hover:bg-amber-100">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Edit Syllabus
                            </button>
                            <button wire:click="onDeleteSyllabusGroup({{ $syllabus['exam_id'] }}, {{ $syllabus['standard_id'] }}, {{ $syllabus['subject_id'] }})"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 border border-red-200 rounded-md hover:bg-red-100">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Remove
                            </button>
                        </div>
                    </div>

                    @if (empty($syllabus['chapters']))
                        <div class="text-center py-12">
                            <p class="text-sm text-gray-500">No syllabus configured for this combination.</p>
                        </div>
                    @else
                        <div class="divide-y divide-gray-100">
                            @foreach ($syllabus['chapters'] as $index => $chapter)
                                <div class="p-5">
                                    <div class="flex items-start gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm font-bold flex-shrink-0">
                                            {{ $index + 1 }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-base font-semibold text-gray-900">{{ $chapter['name'] }}</h4>
                                            @if (!empty($chapter['description']))
                                                <p class="text-sm text-gray-600 mt-1">{{ $chapter['description'] }}</p>
                                            @endif

                                            @if (!empty($chapter['topics']))
                                                <div class="mt-3 pl-2 border-l-2 border-blue-100 space-y-1.5">
                                                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Topics ({{ count($chapter['topics']) }})</p>
                                                    @foreach ($chapter['topics'] as $topic)
                                                        <div class="text-sm text-gray-700 flex items-start gap-2">
                                                            <svg class="w-3.5 h-3.5 text-blue-400 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="2" /></svg>
                                                            {{ $topic['topic_name'] }}
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="text-xs text-gray-400 italic mt-2">No topics added to this chapter yet.</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    @if (empty($syllabus['groups']))
                        <div class="text-center py-20 px-4">
                            <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </div>
                            <p class="text-base font-semibold text-gray-800">No syllabus configured yet</p>
                            <p class="text-sm text-gray-400 mt-1">Click "Add Syllabus" to choose chapters for an exam, class, section, and subject.</p>
                            <p class="text-xs text-gray-400 mt-3">Or use the filters above (Exam → Class → Section → Subject) to view a specific syllabus.</p>
                        </div>
                    @else
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Exam</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Section</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Subject</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Chapters</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($syllabus['groups'] as $g)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ $g['exam_name'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $g['standard_name'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $g['section_name'] ?? '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $g['subject_name'] }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">
                                                {{ $g['chapter_count'] }} chapter{{ $g['chapter_count'] > 1 ? 's' : '' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-center gap-1">
                                                <button wire:click="onViewSyllabus({{ $g['exam_id'] }}, {{ $g['standard_id'] }}, {{ $g['section_id'] ?? 'null' }}, {{ $g['subject_id'] }})" title="View syllabus"
                                                    class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </button>
                                                <button wire:click="onEditSyllabus({{ $g['exam_id'] }}, {{ $g['standard_id'] }}, {{ $g['subject_id'] }}, {{ $g['section_id'] ?? 'null' }})" title="Edit"
                                                    class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-amber-50 hover:text-amber-600 hover:border-amber-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </button>
                                                <button wire:click="onDeleteSyllabusGroup({{ $g['exam_id'] }}, {{ $g['standard_id'] }}, {{ $g['subject_id'] }})" title="Remove"
                                                    class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-red-50 hover:text-red-600 hover:border-red-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @endif
        @endif
    </div>

    {{-- ADD / EDIT EXAM SLIDE-IN PANEL --}}
    @if ($open)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editId ? 'Edit Exam' : 'New Exam' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $editId ? 'Update exam details' : 'Create a new exam' }}</p>
                    </div>
                    <button wire:click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Term <span class="text-red-500">*</span></label>
                        <select wire:model.defer="term" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select Term</option>
                            @foreach ($termOptions as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                        </select>
                        @error('term')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Exam Name <span class="text-red-500">*</span></label>
                        <input wire:model.defer="examName" type="text" placeholder="e.g. Annual Examination 2026"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @error('examName')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Academic Year <span class="text-red-500">*</span></label>
                            <select wire:model.defer="academicYear" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                                @foreach ($academicYearOptions as $year)<option value="{{ $year }}">{{ $year }}</option>@endforeach
                            </select>
                            @error('academicYear')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Exam Type <span class="text-red-500">*</span></label>
                            <select wire:model.defer="examType" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                                <option value="">Select Type</option>
                                @foreach ($examTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                            </select>
                            @error('examType')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Start Date <span class="text-red-500">*</span></label>
                            <input wire:model.defer="startDate" type="date" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                            @error('startDate')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">End Date <span class="text-red-500">*</span></label>
                            <input wire:model.defer="endDate" type="date" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                            @error('endDate')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model.live="usesGradingSystem" class="rounded">
                        Use Grading System (no marks)
                    </label>

                    @if (!$usesGradingSystem)
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Total Marks <span class="text-red-500">*</span></label>
                                <input wire:model.defer="totalMarks" type="number" min="1" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                                @error('totalMarks')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Passing Marks <span class="text-red-500">*</span></label>
                                <input wire:model.defer="passingMarks" type="number" min="1" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                                @error('passingMarks')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Description</label>
                        <textarea wire:model.defer="description" rows="3" placeholder="Optional notes..."
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm resize-none"></textarea>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model.defer="isPublished" class="rounded">
                        Publish immediately
                    </label>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="onSave" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="onSave">{{ $editId ? 'Update Exam' : 'Create Exam' }}</span>
                        <span wire:loading wire:target="onSave">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ADD / EDIT SYLLABUS SLIDE-IN PANEL --}}
    @if ($openSyllabusModal)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeSyllabusModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-2xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ $sylModalIsEdit ? 'Edit Exam Syllabus' : 'Add Exam Syllabus' }}
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Pick exam, class, section &amp; subject, then choose chapters
                        </p>
                    </div>
                    <button wire:click="closeSyllabusModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">1. Select Exam <span class="text-red-500">*</span></label>
                        <select wire:model.live="sylModalExamId" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                            <option value="">— Choose exam —</option>
                            @foreach ($allExams as $e)<option value="{{ $e['id'] }}">{{ $e['exam_name'] }} ({{ $e['academic_year'] }})</option>@endforeach
                        </select>
                        @error('sylModalExamId')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">2. Select Class <span class="text-red-500">*</span></label>
                        <select wire:model.live="sylModalStandardId" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                            <option value="">— Choose class —</option>
                            @foreach ($allStandards as $s)<option value="{{ $s['id'] }}">{{ $s['name'] }}</option>@endforeach
                        </select>
                        @error('sylModalStandardId')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">3. Select Section <span class="text-red-500">*</span></label>
                        <select wire:model.live="sylModalSectionId" @disabled(!$sylModalStandardId)
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm disabled:opacity-50">
                            <option value="">— Choose section —</option>
                            @foreach ($sylModalSections as $sec)<option value="{{ $sec['id'] }}">{{ $sec['name'] }}</option>@endforeach
                        </select>
                        @if (!$sylModalStandardId)<p class="mt-1 text-xs text-gray-400">Select a class first to load sections.</p>@endif
                        @error('sylModalSectionId')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">4. Select Subject <span class="text-red-500">*</span></label>
                        <select wire:model.live="sylModalSubjectId" @disabled(!$sylModalSectionId)
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm disabled:opacity-50">
                            <option value="">— Choose subject —</option>
                            @foreach ($sylModalSubjects as $sub)<option value="{{ $sub['id'] }}">{{ $sub['name'] }}</option>@endforeach
                        </select>
                        @if (!$sylModalSectionId)<p class="mt-1 text-xs text-gray-400">Select a section first to filter subjects.</p>@endif
                        @error('sylModalSubjectId')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    @if ($sylModalSubjectId && !empty($sylModalChapters))
                        <div class="border-t border-gray-100 pt-5">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700">5. Select Chapters <span class="text-red-500">*</span></label>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ count($sylModalChapterIds) }} of {{ count($sylModalChapters) }} selected</p>
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" wire:click="toggleAllChapters(true)" class="text-xs font-medium text-blue-600 hover:text-blue-700 px-2 py-1 rounded hover:bg-blue-50">Select All</button>
                                    <button type="button" wire:click="toggleAllChapters(false)" class="text-xs font-medium text-gray-600 hover:text-gray-700 px-2 py-1 rounded hover:bg-gray-100">Clear</button>
                                </div>
                            </div>

                            @if ($sylModalIsEdit)
                                <p class="text-xs text-amber-700 bg-amber-50 border border-amber-100 rounded-md px-3 py-2 mb-3">
                                    Edit mode — chapters owned by other exams remain selectable and will be
                                    <strong>transferred</strong> here when you save.
                                </p>
                            @endif

                            <div class="border border-gray-200 rounded-lg divide-y divide-gray-100 max-h-96 overflow-y-auto">
                                @foreach ($sylModalChapters as $ch)
                                    @php
                                        $ownedByOther = !empty($ch['owning_exam_id'])
                                            && (int) $ch['owning_exam_id'] !== (int) $sylModalExamId;
                                        // Add-mode: a chapter belonging to ANY other exam is locked.
                                        // Edit-mode: nothing is locked — taken chapters can be transferred.
                                        $isLocked = !$sylModalIsEdit && $ownedByOther;
                                    @endphp
                                    <label class="flex items-start gap-3 p-3 transition-colors
                                                  {{ $isLocked ? 'bg-gray-50 cursor-not-allowed opacity-70' : 'hover:bg-gray-50 cursor-pointer' }}">
                                        <input type="checkbox" wire:model.live="sylModalChapterIds" value="{{ $ch['id'] }}"
                                            @disabled($isLocked)
                                            class="mt-0.5 rounded text-blue-600 focus:ring-blue-500 disabled:cursor-not-allowed">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="text-sm font-medium {{ $isLocked ? 'text-gray-500' : 'text-gray-900' }}">
                                                    {{ $ch['name'] }}
                                                </p>
                                                @if ($ownedByOther)
                                                    <span class="text-[10px] font-semibold uppercase tracking-wide
                                                                 {{ $sylModalIsEdit ? 'bg-amber-100 text-amber-700' : 'bg-gray-200 text-gray-600' }}
                                                                 px-1.5 py-0.5 rounded">
                                                        {{ $sylModalIsEdit ? 'Will transfer from' : 'In' }}
                                                        {{ $ch['owning_exam_name'] }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if (!empty($ch['description']))
                                                <p class="text-xs text-gray-500 mt-0.5 line-clamp-1">{{ $ch['description'] }}</p>
                                            @endif
                                            @if (!empty($ch['topics']))
                                                <p class="text-xs text-gray-400 mt-1">{{ count($ch['topics']) }} topic(s)</p>
                                            @endif
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            @error('sylModalChapterIds')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    @elseif ($sylModalSubjectId)
                        <div class="border-t border-gray-100 pt-5 text-center">
                            <p class="text-sm text-gray-500">No chapters found for this class + section + subject.</p>
                            <p class="text-xs text-gray-400 mt-1">Add chapters from the Content section first.</p>
                        </div>
                    @endif
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeSyllabusModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveSyllabus" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveSyllabus">
                            {{ $sylModalIsEdit ? 'Update Syllabus' : 'Save Syllabus' }}
                        </span>
                        <span wire:loading wire:target="saveSyllabus">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- VIEW EXAM SLIDE-IN PANEL --}}
    @if ($showViewModal && !empty($viewData))
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeViewModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $viewModalTitle }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $viewData['exam']->is_published ? 'Published' : 'Draft' }} ·
                            {{ $viewData['exam']->academic_year }}
                        </p>
                    </div>
                    <button wire:click="closeViewModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    @foreach ($viewData['details'] as $label => $value)
                        <div class="grid grid-cols-3 gap-3 text-sm">
                            <span class="text-xs text-gray-400 uppercase tracking-wider">{{ $label }}</span>
                            <span class="col-span-2 text-gray-800 font-medium">{{ $value }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeViewModal" class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
                </div>
            </div>
        </div>
    @endif

    {{-- DELETE CONFIRM OVERLAY --}}
    @if ($showDeleteConfirm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDelete"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">
                            {{ $deleteTargetType === 'exam' ? 'Delete exam?' : 'Remove syllabus?' }}
                        </h3>
                        <p class="text-sm text-gray-500">
                            {{ $deleteTargetType === 'exam'
                                ? 'This will permanently delete the exam and remove all its syllabus mappings.'
                                : 'This will remove all chapters from this exam-class-subject syllabus.' }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button wire:click="cancelDelete" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="confirmDelete" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md disabled:opacity-60 flex items-center gap-1.5">
                        <span wire:loading.remove wire:target="confirmDelete">{{ $deleteTargetType === 'exam' ? 'Delete' : 'Remove' }}</span>
                        <span wire:loading wire:target="confirmDelete">Deleting...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- UPLOAD / EDIT EXAM PAPER SLIDE-IN PANEL --}}
    @if ($showPaperModal)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePaperModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $paperIsEdit ? 'Edit Exam Paper' : 'Upload Exam Paper' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Select the exam, class &amp; section, then upload the PDF (max 5 MB).</p>
                    </div>
                    <button wire:click="closePaperModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Exam <span class="text-red-500">*</span></label>
                        <select wire:model.defer="paperExam" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                            <option value="">Select Exam</option>
                            @foreach ($allExams as $e)
                                <option value="{{ $e['id'] }}">{{ $e['exam_name'] }} ({{ $e['academic_year'] }})</option>
                            @endforeach
                        </select>
                        @error('paperExam')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Class <span class="text-red-500">*</span></label>
                            <select wire:model.live="paperStandard" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                                <option value="">Select Class</option>
                                @foreach ($allStandards as $s)
                                    <option value="{{ $s['id'] }}">{{ $s['name'] }}</option>
                                @endforeach
                            </select>
                            @error('paperStandard')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Section</label>
                            <select wire:model.defer="paperSection" @disabled(!$paperStandard)
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm disabled:opacity-50">
                                <option value="">All / None</option>
                                @foreach ($paperModalSections as $sec)
                                    <option value="{{ $sec['id'] }}">{{ $sec['name'] }}</option>
                                @endforeach
                            </select>
                            @error('paperSection')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Title <span class="text-red-500">*</span></label>
                        <input wire:model.defer="paperTitle" type="text" placeholder="e.g. Mathematics Question Paper"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @error('paperTitle')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            PDF File @if (!$paperIsEdit)<span class="text-red-500">*</span>@endif
                            <span class="text-xs font-normal text-gray-400">(max 5 MB)</span>
                        </label>
                        <input wire:model="paperFile" type="file" accept=".pdf" class="w-full text-sm">
                        @if ($paperIsEdit)<p class="text-xs text-gray-400 mt-1">Leave empty to keep the existing PDF.</p>@endif
                        <div wire:loading wire:target="paperFile" class="text-xs text-blue-600 mt-1">Uploading...</div>
                        @if ($paperFile)
                            <div class="mt-2 text-xs text-emerald-600">
                                Selected: {{ $paperFile->getClientOriginalName() }}
                                ({{ number_format($paperFile->getSize() / 1024, 2) }} KB)
                            </div>
                        @endif
                        @error('paperFile')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closePaperModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="savePaper" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="savePaper">{{ $paperIsEdit ? 'Update Paper' : 'Upload Paper' }}</span>
                        <span wire:loading wire:target="savePaper">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- DELETE EXAM PAPER CONFIRM OVERLAY --}}
    @if ($showPaperDeleteConfirm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDeletePaper"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Delete exam paper?</h3>
                        <p class="text-sm text-gray-500">The PDF will be permanently removed from storage.</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button wire:click="cancelDeletePaper" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="confirmDeletePaper" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">Delete</button>
                </div>
            </div>
        </div>
    @endif

</div>
