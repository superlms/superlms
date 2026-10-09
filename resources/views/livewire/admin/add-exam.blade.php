<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════
         HEADER (full-width, sticky, analytics + tabs + dynamic Add button)
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900">Exams</h1>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                        <span class="pr-4">Total: <strong class="text-gray-800">{{ $totalExams }}</strong></span>
                        <span class="px-4">Published: <strong class="text-emerald-600">{{ $publishedExams }}</strong></span>
                        <span class="px-4">Upcoming: <strong class="text-amber-500">{{ $upcomingExams }}</strong></span>
                        <span class="px-4">Active: <strong class="text-blue-600">{{ $activeExams }}</strong></span>
                        <span class="px-4">Completed: <strong class="text-violet-600">{{ $completedExams }}</strong></span>
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
                <span>Active: <strong class="text-blue-600">{{ $activeExams }}</strong></span>
                <span>Completed: <strong class="text-violet-600">{{ $completedExams }}</strong></span>
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

                    <select wire:model.live="filterPaperStandard" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="">All Classes</option>
                        @foreach ($allStandards as $s)
                            <option value="{{ $s['id'] }}">{{ $s['name'] }}</option>
                        @endforeach
                    </select>

                    <select data-sole-section wire:model.live="filterPaperSection" @disabled(!$filterPaperStandard)
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                        <option value="">All Sections</option>
                        @foreach ($paperFilterSections as $sec)
                            <option value="{{ $sec['id'] }}">{{ $sec['name'] }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filterPaperSubject"
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="">All Subjects</option>
                        @foreach ($paperFilterSubjects as $sub)
                            <option value="{{ $sub['id'] }}">{{ $sub['name'] }}</option>
                        @endforeach
                        <option value="other">Other</option>
                    </select>

                    @if ($filterPaperExam || $filterPaperStandard || $filterPaperSection || $filterPaperSubject)
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

                    <select wire:model.live="syllabusFilterStandard" @disabled(!$syllabusFilterExam)
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                        <option value="">Select Class</option>
                        @foreach ($allStandards as $s)
                            <option value="{{ $s['id'] }}">{{ $s['name'] }}</option>
                        @endforeach
                    </select>

                    <select data-sole-section wire:model.live="syllabusFilterSection" @disabled(!$syllabusFilterStandard)
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                        <option value="">Select Section</option>
                        @foreach ($filterSections as $sec)
                            <option value="{{ $sec['id'] }}">{{ $sec['name'] }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="syllabusFilterSubject" @disabled(!$syllabusFilterStandard)
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
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Dates</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Total / Passing</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($exams as $exam)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-semibold text-gray-900">{{ $exam->exam_name }}</p>
                                        @if ($exam->term)
                                            <p class="text-xs text-gray-400 mt-0.5">{{ $exam->term }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $examTypes[$exam->exam_type] ?? $exam->exam_type }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-600 whitespace-nowrap">
                                        @if ($exam->start_date || $exam->end_date)
                                            {{ $exam->start_date?->format('d M Y') ?? '—' }} → {{ $exam->end_date?->format('d M Y') ?? '—' }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm font-semibold text-gray-800 whitespace-nowrap">
                                        @if ($exam->uses_grading_system ?? false)
                                            —
                                        @else
                                            {{ $exam->total_marks ?? '—' }} / {{ $exam->passing_marks ?? '—' }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        {{-- Status follows the dates on every render: ended → Completed,
                                             running → Active, starting within 10 days → Upcoming. --}}
                                        @php $status = $exam->currentStatus(); @endphp
                                        {{-- Plain text (no chip); a click still publishes / unpublishes. --}}
                                        <button wire:click="onTogglePublish({{ $exam->id }})"
                                            title="{{ $exam->is_published ? 'Click to unpublish' : 'Click to publish' }}"
                                            class="text-sm text-gray-700 hover:text-gray-900">
                                            {{ ucfirst($status) }}
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
                                    <td colspan="6" class="px-4 py-16 text-center">
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

        @elseif ($activeTab === 'papers' && $paperBoard !== null)
            {{-- ═════ EXAM PAPERS — exam and class picked: every subject of the
                 class is a row; its paper with View / Download / Edit / Delete,
                 or Add when none is added. Deleting a paper keeps the subject. ═════ --}}
            {{-- Add Paper / Edit open the file picker straight away (no panel);
                 the PDF picked is saved as soon as it is uploaded. --}}
            <div x-data="{ pick(target) { this.$wire.quickPaperFor = target; this.$refs.quickPaper.value = ''; this.$refs.quickPaper.click(); } }"
                class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <input type="file" x-ref="quickPaper" wire:model="quickPaperFile" accept="application/pdf,.pdf" class="hidden">
                <div wire:loading.flex wire:target="quickPaperFile" class="items-center gap-2 px-4 py-2 text-xs text-blue-600 border-b border-gray-100">
                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    Uploading the PDF…
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">S.No</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Subject</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Paper</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Uploaded</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($paperBoard as $i => $row)
                                @if ($row['papers']->isEmpty())
                                    <tr wire:key="pb-{{ $row['key'] }}" class="hover:bg-gray-50/70">
                                        <td class="px-4 py-3 text-sm text-gray-500 font-medium">{{ $i + 1 }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                <x-subject-icon :name="$row['name']" size="w-9 h-9" />
                                                <span class="text-sm font-semibold text-gray-900">{{ $row['name'] }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-400">Not added yet</td>
                                        <td class="px-4 py-3 text-sm text-gray-400">—</td>
                                        <td class="px-4 py-3 text-center">
                                            <button x-on:click="pick('add:{{ $row['key'] }}')" type="button" title="Choose the PDF (up to 2 MB)"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-blue-600 bg-white border border-blue-200 rounded-md hover:bg-blue-50">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                                Add Paper
                                            </button>
                                        </td>
                                    </tr>
                                @else
                                    @foreach ($row['papers'] as $k => $paper)
                                        <tr wire:key="pb-{{ $row['key'] }}-{{ $paper->id }}" class="hover:bg-gray-50/70">
                                            @if ($k === 0)
                                                <td class="px-4 py-3 text-sm text-gray-500 font-medium align-top" rowspan="{{ $row['papers']->count() }}">{{ $i + 1 }}</td>
                                                <td class="px-4 py-3 align-top" rowspan="{{ $row['papers']->count() }}">
                                                    <div class="flex items-center gap-3">
                                                        <x-subject-icon :name="$row['name']" size="w-9 h-9" />
                                                        <span class="text-sm font-semibold text-gray-900">{{ $row['name'] }}</span>
                                                    </div>
                                                </td>
                                            @endif
                                            <td class="px-4 py-3">
                                                <p class="text-sm font-medium text-gray-900">{{ $paper->title }}</p>
                                                @if (!$filterPaperSection && $paper->section)
                                                    <p class="text-xs text-gray-400">Section {{ $paper->section->name }}</p>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">{{ $paper->created_at->format('d M Y, g:i A') }}</td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center justify-center gap-1">
                                                    <button wire:click="viewPaper({{ $paper->id }})" title="View"
                                                        class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                    </button>
                                                    <button wire:click="downloadPaper({{ $paper->id }})" title="Download"
                                                        class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                        </svg>
                                                    </button>
                                                    <button x-on:click="pick('edit:{{ $paper->id }}')" type="button" title="Edit — choose a new PDF"
                                                        class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                    </button>
                                                    <button wire:click="onDeletePaper({{ $paper->id }})" title="Delete"
                                                        class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-16 text-center">
                                        <p class="text-sm font-semibold text-gray-800">No subjects for this class</p>
                                        <p class="text-xs text-gray-400 mt-1">Add subjects to the class first, or use Upload Paper.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
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
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Subject</th>
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
                                            <div class="min-w-0">
                                                <span class="block text-sm font-semibold text-gray-900">{{ $paper->title }}</span>
                                                @if ($paper->description)
                                                    <span class="block text-xs text-gray-400 truncate max-w-xs" title="{{ $paper->description }}">
                                                        {{ \Illuminate\Support\Str::limit($paper->description, 70) }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $paper->exam->exam_name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $paper->standard->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $paper->section->name ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="text-xs font-medium px-2 py-0.5 rounded
                                            {{ $paper->subject ? 'bg-blue-50 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $paper->subjectLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">{{ $paper->created_at->format('d M Y, g:i A') }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <button wire:click="viewPaper({{ $paper->id }})" title="View"
                                                class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
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
                                    <td colspan="7" class="px-4 py-16 text-center">
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

        @elseif ($syllabusBoard !== null)
            {{-- ═════ EXAM SYLLABUS — exam and class picked: every subject of the
                 class with its chapters for the exam, one after another with
                 dots; Edit, or Add when none are picked. ═════ --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">S.No</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Subject</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Chapters</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($syllabusBoard as $i => $row)
                                <tr wire:key="sb-{{ $row['subject_id'] }}" class="hover:bg-gray-50/70">
                                    <td class="px-4 py-3 text-sm text-gray-500 font-medium align-top">{{ $i + 1 }}</td>
                                    <td class="px-4 py-3 align-top">
                                        <div class="flex items-center gap-3">
                                            <x-subject-icon :name="$row['name']" size="w-9 h-9" />
                                            <span class="text-sm font-semibold text-gray-900 whitespace-nowrap">{{ $row['name'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm {{ $row['chapters'] ? 'text-gray-700' : 'text-gray-400' }}">
                                        {{ $row['chapters'] ? implode(' · ', $row['chapters']) : 'Not added yet' }}
                                    </td>
                                    <td class="px-4 py-3 text-center align-top">
                                        @if ($row['chapters'])
                                            <button wire:click="onEditSyllabus({{ $syllabusFilterExam }}, {{ $syllabusFilterStandard }}, {{ $row['subject_id'] }}, {{ $row['section_id'] ?? 'null' }})" title="Edit"
                                                class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                        @else
                                            <button wire:click="openSyllabusFor({{ $row['subject_id'] }})" type="button"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-blue-600 bg-white border border-blue-200 rounded-md hover:bg-blue-50">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                                Add
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-16 text-center">
                                        <p class="text-sm font-semibold text-gray-800">No subjects for this class</p>
                                        <p class="text-xs text-gray-400 mt-1">Add subjects to the class first.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            @if ($syllabus['mode'] === 'detail')
                {{-- Filtered syllabus — chapters with their topics on one line. --}}
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-start justify-between gap-3 flex-wrap">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-1.5 text-xs text-gray-400">
                                <span class="text-gray-700 font-medium">{{ $syllabus['exam_name'] ?? '—' }}</span>
                                <span>/</span>
                                <span>{{ $syllabus['standard_name'] ?? '—' }}</span>
                                @if ($syllabus['section_name'] ?? null)
                                    <span>/</span>
                                    <span>{{ $syllabus['section_name'] }}</span>
                                @endif
                                <span>/</span>
                                <span class="text-blue-600 font-medium">{{ $syllabus['subject_name'] ?? '—' }}</span>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ count($syllabus['chapters']) }} chapter{{ count($syllabus['chapters']) === 1 ? '' : 's' }} in this syllabus
                            </p>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button wire:click="clearSyllabusFilters"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                                Back
                            </button>
                            <button wire:click="onEditSyllabus({{ $syllabus['exam_id'] }}, {{ $syllabus['standard_id'] }}, {{ $syllabus['subject_id'] }}, {{ $syllabus['section_id'] ?? 'null' }})"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Edit
                            </button>
                        </div>
                    </div>

                    @if (empty($syllabus['chapters']))
                        <div class="text-center py-14">
                            <p class="text-sm text-gray-500">No syllabus configured for this combination.</p>
                            <p class="text-xs text-gray-400 mt-1">Use "Add Syllabus" to pick its chapters.</p>
                        </div>
                    @else
                        <div class="divide-y divide-gray-100">
                            @foreach ($syllabus['chapters'] as $index => $chapter)
                                <div class="px-5 py-3.5 flex items-baseline gap-3">
                                    <span class="text-xs font-semibold text-gray-300 tabular-nums w-5 flex-shrink-0">
                                        {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-900">{{ $chapter['name'] }}</p>
                                        @if (!empty($chapter['topics']))
                                            <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">
                                                {{ implode(' · ', array_column($chapter['topics'], 'topic_name')) }}
                                            </p>
                                        @endif
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
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
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
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Start Date</label>
                            <input wire:model.defer="startDate" type="date" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                            @error('startDate')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">End Date</label>
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

    {{-- ADD / EDIT SYLLABUS SLIDE-IN PANEL — the Mark Attendance panel's look:
         exam, class (section only when the class has several) and subject in
         one row, then the subject's chapters to tick; a chapter already in a
         syllabus says so in plain text with the exam's name. --}}
    @if ($openSyllabusModal)
        @php
            $sylTicked = count($sylModalChapterIds);
            $sylTotal  = count($sylModalChapters);
        @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeSyllabusModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900">{{ $sylModalIsEdit ? 'Edit Exam Syllabus' : 'Add Exam Syllabus' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Pick exam, class &amp; subject — then tick its chapters and save.</p>
                    </div>
                    <button wire:click="closeSyllabusModal" type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- Toolbar: exam → class → (section) → subject --}}
                <div class="px-6 py-3 border-b border-gray-100 flex flex-wrap items-center gap-2 flex-shrink-0">
                    <select wire:model.live="sylModalExamId"
                        class="text-sm border border-gray-300 rounded-md px-3 py-1.5 bg-white focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <option value="">Select exam…</option>
                        @foreach ($allExams as $e)<option value="{{ $e['id'] }}">{{ $e['exam_name'] }} ({{ $e['academic_year'] }})</option>@endforeach
                    </select>
                    <select wire:model.live="sylModalStandardId"
                        class="text-sm border border-gray-300 rounded-md px-3 py-1.5 bg-white focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <option value="">Select class…</option>
                        @foreach ($allStandards as $s)<option value="{{ $s['id'] }}">{{ $s['name'] }}</option>@endforeach
                    </select>
                    @if (count($sylModalSections) > 1)
                        <select wire:model.live="sylModalSectionId"
                            class="text-sm border border-gray-300 rounded-md px-3 py-1.5 bg-white focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                            <option value="">All sections</option>
                            @foreach ($sylModalSections as $sec)<option value="{{ $sec['id'] }}">{{ $sec['name'] }}</option>@endforeach
                        </select>
                    @endif
                    <select wire:model.live="sylModalSubjectId" @disabled(!$sylModalStandardId)
                        class="text-sm border border-gray-300 rounded-md px-3 py-1.5 bg-white disabled:opacity-50 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <option value="">Select subject…</option>
                        @foreach ($sylModalSubjects as $sub)<option value="{{ $sub['id'] }}">{{ $sub['name'] }}</option>@endforeach
                    </select>
                    @if ($sylModalSubjectId && $sylTotal > 0)
                        <span class="ml-auto text-xs text-gray-400 tabular-nums">{{ $sylTicked }} of {{ $sylTotal }} ticked</span>
                    @endif
                </div>

                @if ($errors->hasAny(['sylModalExamId', 'sylModalStandardId', 'sylModalSubjectId', 'sylModalChapterIds']))
                    <p class="px-6 py-2 text-xs text-red-500 border-b border-gray-100 flex-shrink-0">
                        {{ $errors->first('sylModalExamId') ?: ($errors->first('sylModalStandardId') ?: ($errors->first('sylModalSubjectId') ?: $errors->first('sylModalChapterIds'))) }}
                    </p>
                @endif

                {{-- One quiet line, with Select all / Clear --}}
                @if ($sylModalSubjectId && $sylTotal > 0)
                    <div class="px-6 py-2 text-xs text-gray-500 border-b border-gray-100 flex items-center justify-between gap-3 flex-shrink-0">
                        <span>{{ $sylModalIsEdit ? 'A chapter added to another exam moves here when ticked; untick every chapter to remove this syllabus.' : 'Tick the chapters this exam covers.' }}</span>
                        <span class="flex items-center gap-1 flex-shrink-0">
                            <button type="button" wire:click="toggleAllChapters(true)" class="px-2 py-1 rounded text-blue-600 hover:bg-blue-50 font-medium">Select all</button>
                            <button type="button" wire:click="toggleAllChapters(false)" class="px-2 py-1 rounded text-gray-600 hover:bg-gray-100 font-medium">Clear</button>
                        </span>
                    </div>
                @endif

                {{-- Rows --}}
                <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
                    @if ($sylModalSubjectId && $sylTotal > 0)
                        @foreach ($sylModalChapters as $i => $ch)
                            @php
                                $ownedByOther = !empty($ch['owning_exam_id'])
                                    && (int) $ch['owning_exam_id'] !== (int) $sylModalExamId;
                                // Add: a chapter in another exam's syllabus cannot be picked.
                                // Edit: it can — saving moves it here.
                                $isLocked = !$sylModalIsEdit && $ownedByOther;
                            @endphp
                            <label wire:key="sylch-{{ $ch['id'] }}"
                                class="flex items-center gap-x-3 px-6 py-2.5 {{ $isLocked ? 'cursor-not-allowed' : 'hover:bg-gray-50 cursor-pointer' }}">
                                <span class="w-4 text-[11px] text-gray-300 tabular-nums flex-shrink-0">{{ $i + 1 }}</span>
                                <input type="checkbox" wire:model.live="sylModalChapterIds" value="{{ $ch['id'] }}" @disabled($isLocked)
                                    class="rounded text-gray-900 focus:ring-gray-400 disabled:opacity-40 disabled:cursor-not-allowed flex-shrink-0">
                                <span class="flex-1 min-w-0 text-sm {{ $isLocked ? 'text-gray-400' : 'text-gray-900' }}">{{ $ch['name'] }}</span>
                                @if (!empty($ch['owning_exam_id']))
                                    <span class="text-xs text-gray-400 flex-shrink-0">Added · {{ $ch['owning_exam_name'] ?: 'an exam' }}</span>
                                @endif
                            </label>
                        @endforeach
                    @elseif ($sylModalSubjectId)
                        <div class="py-16 text-center text-sm text-gray-400">
                            No chapters for this subject yet.
                            <p class="text-xs mt-1">Add them from Syllabus first.</p>
                        </div>
                    @else
                        <p class="py-16 text-center text-sm text-gray-400">Select an exam, class &amp; subject to start.</p>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeSyllabusModal" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveSyllabus" wire:loading.attr="disabled" wire:target="saveSyllabus" type="button"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveSyllabus">{{ $sylModalIsEdit ? 'Update Syllabus' : 'Save Syllabus' }}</span>
                        <span wire:loading wire:target="saveSyllabus">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- VIEW EXAM SLIDE-IN PANEL --}}
    @if ($showViewModal && !empty($viewData))
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeViewModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $viewModalTitle }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $viewData['exam']->statusLabel() }} ·
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
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDelete"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Delete exam?</h3>
                        <p class="text-sm text-gray-500">
                            This will permanently delete the exam and remove all its syllabus mappings.
                        </p>
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

    {{-- UPLOAD / EDIT EXAM PAPER SLIDE-IN PANEL --}}
    @if ($showPaperModal)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePaperModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $paperIsEdit ? 'Edit Exam Paper' : 'Upload Exam Paper' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Pick the exam, class, section &amp; subject, then upload the PDF (max 2 MB).</p>
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
                            <select wire:model.live="paperSection" @disabled(!$paperStandard)
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm disabled:opacity-50">
                                <option value="">All / None</option>
                                @foreach ($paperModalSections as $sec)
                                    <option value="{{ $sec['id'] }}">{{ $sec['name'] }}</option>
                                @endforeach
                            </select>
                            @error('paperSection')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- Subject - the class's own subjects, plus an "Other" bucket for
                         papers that don't belong to any one subject. --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Subject <span class="text-red-500">*</span></label>
                            <select wire:model.defer="paperSubject" @disabled(!$paperStandard)
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm disabled:opacity-50">
                                <option value="">Select Subject</option>
                                @foreach ($paperModalSubjects as $sub)
                                    <option value="{{ $sub['id'] }}">{{ $sub['name'] }}</option>
                                @endforeach
                                <option value="other">Other</option>
                            </select>
                            <p class="text-xs text-gray-400 mt-1">
                                @if (!$paperStandard)
                                    Choose a class first.
                                @else
                                    Pick <strong>Other</strong> for a paper that isn't tied to one of these subjects.
                                @endif
                            </p>
                            @error('paperSubject')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Title <span class="text-red-500">*</span></label>
                            <input wire:model.defer="paperTitle" type="text" placeholder="e.g. Mathematics Question Paper"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('paperTitle')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div x-data>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            PDF File @if (!$paperIsEdit)<span class="text-red-500">*</span>@endif
                            <span class="text-xs font-normal text-gray-400">(max 2 MB)</span>
                        </label>

                        {{-- Click-anywhere drop-zone wrapping a hidden file input. --}}
                        <label for="paperFileInput"
                            class="flex items-center gap-3 w-full px-4 py-4 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-colors">
                            <span class="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-medium text-gray-700">Click to choose a PDF</span>
                                <span class="block text-xs text-gray-400">
                                    PDF only, up to 2 MB{{ $paperIsEdit ? ' - leave empty to keep the current file' : '' }}
                                </span>
                            </span>
                        </label>
                        <input id="paperFileInput" x-ref="paperFileInput" wire:model="paperFile" type="file"
                            accept="application/pdf,.pdf" class="hidden">

                        <div wire:loading wire:target="paperFile" class="flex items-center gap-2 mt-2 text-xs text-blue-600">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            Uploading...
                        </div>

                        @if ($paperFile)
                            <div wire:loading.remove wire:target="paperFile"
                                class="mt-2 flex items-center justify-between gap-3 px-3 py-2 rounded-lg border border-emerald-200 bg-emerald-50">
                                <div class="flex items-center gap-2 min-w-0">
                                    <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-xs text-emerald-800 truncate">{{ $paperFile->getClientOriginalName() }}</span>
                                    <span class="text-[11px] text-emerald-600 flex-shrink-0">
                                        ({{ number_format($paperFile->getSize() / 1024, 1) }} KB)
                                    </span>
                                </div>
                                <button type="button" title="Remove"
                                    x-on:click="$refs.paperFileInput.value = ''; $wire.set('paperFile', null)"
                                    class="p-1 rounded text-emerald-700 hover:bg-emerald-100 flex-shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        @endif
                        @error('paperFile')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Description (optional, 3000 characters) --}}
                    <div x-data="{ len: @js(mb_strlen($paperDescription ?? '')) }">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <span class="text-xs text-gray-400"><span x-text="len">0</span>/3000</span>
                        </div>
                        <textarea wire:model.defer="paperDescription" rows="4" maxlength="3000"
                            x-on:input="len = $event.target.value.length"
                            placeholder="Optional notes about this paper - instructions, sections covered, marking scheme..."
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm resize-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        @error('paperDescription')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
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
    {{-- VIEW PAPER — the PDF in a slide-in, as a report card is viewed --}}
    @if ($viewPaperId)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePaperView"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col" wire:click.stop>
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $viewPaperTitle }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Exam paper</p>
                    </div>
                    <button wire:click="closePaperView" type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-hidden bg-gray-100">
                    <iframe src="{{ $viewPaperUrl }}#toolbar=0&amp;navpanes=0&amp;view=FitH" class="w-full h-full border-0" title="{{ $viewPaperTitle }}"></iframe>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-between flex-shrink-0">
                    <button wire:click="downloadPaper({{ $viewPaperId }})" type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        Download PDF
                    </button>
                    <button type="button" wire:click="closePaperView"
                        class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
                </div>
            </div>
        </div>
    @endif

    @if ($showPaperDeleteConfirm)
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-50 flex items-center justify-center p-4">
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
