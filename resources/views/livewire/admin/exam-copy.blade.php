<div class="min-h-screen bg-gray-50"
    x-data
    x-on:open-in-new-tab.window="window.open($event.detail.url, '_blank')">

    {{-- ══════════════════════════════════════════════════
         HEADER (full-width, sticky, analytics + Upload Copies button)
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900">Exam Copies</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Upload, view, and manage student exam answer copies</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                        <span class="pr-4">Total: <strong class="text-gray-800">{{ $totalExamCopies }}</strong></span>
                        <span class="px-4">Students: <strong class="text-gray-800">{{ $totalStudents }}</strong></span>
                        <span class="px-4">Uploaded: <strong class="text-emerald-600">{{ $uploadedCopies }}</strong></span>
                        <span class="pl-4">Pending: <strong class="text-amber-500">{{ $pendingUploads }}</strong></span>
                    </div>
                    <button wire:click="openUploadModal"
                        class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        <span class="hidden sm:inline">Upload Copies</span>
                        <span class="sm:hidden">Upload</span>
                    </button>
                </div>
            </div>

            <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Total: <strong class="text-gray-800">{{ $totalExamCopies }}</strong></span>
                <span>Students: <strong class="text-gray-800">{{ $totalStudents }}</strong></span>
                <span>Uploaded: <strong class="text-emerald-600">{{ $uploadedCopies }}</strong></span>
                <span>Pending: <strong class="text-amber-500">{{ $pendingUploads }}</strong></span>
            </div>
        </div>

        {{-- Tabs (Rules & Regulations style) --}}
        <div class="border-t border-gray-200 px-4 sm:px-6">
            <nav class="flex gap-1">
                <button wire:click="showTab('by-subject')"
                    class="py-3.5 px-5 text-sm font-semibold border-b-2 transition-colors
                           {{ $activeTab === 'by-subject' ? 'border-blue-500 text-blue-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    View by Subject
                </button>
                <button wire:click="showTab('by-student')"
                    class="py-3.5 px-5 text-sm font-semibold border-b-2 transition-colors
                           {{ $activeTab === 'by-student' ? 'border-purple-500 text-purple-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    View by Student
                </button>
            </nav>
        </div>

        {{-- Filter bar --}}
        @if ($activeTab === 'by-subject')
            <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">Filter:</div>

                    <input wire:model.live.debounce.400ms="search" type="text" placeholder="Search name, admission no, subject..."
                        class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-56 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />

                    <select wire:model.live="filterExam" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="">All Exams</option>
                        @foreach ($exams as $exam)
                            <option value="{{ $exam->id }}">{{ $exam->exam_name }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filterStandard" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="">All Classes</option>
                        @foreach ($standards as $std)
                            <option value="{{ $std->id }}">{{ $std->name }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filterSection" @disabled(!$filterStandard)
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                        <option value="">All Sections</option>
                        @foreach ($sections as $sec)
                            <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filterSubject" @disabled(!$filterStandard)
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                        <option value="">All Subjects</option>
                        @foreach ($subjects as $sub)
                            <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                        @endforeach
                    </select>

                    @if ($search || $filterExam || $filterStandard || $filterSection || $filterSubject)
                        <button wire:click="clearSubjectFilters"
                            class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            Clear
                        </button>
                    @endif
                </div>
            </div>
        @else
            <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                        Filter by:
                    </div>

                    <select wire:model.live="byStudentExam"
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[140px]">
                        <option value="">Select Exam</option>
                        @foreach ($exams as $exam)<option value="{{ $exam->id }}">{{ $exam->exam_name }}</option>@endforeach
                    </select>

                    <select wire:model.live="byStudentStandard" @disabled(!$byStudentExam)
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed min-w-[120px]">
                        <option value="">Select Class</option>
                        @foreach ($standards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
                    </select>

                    <select wire:model.live="byStudentSection" @disabled(!$byStudentStandard)
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed min-w-[120px]">
                        <option value="">Select Section</option>
                        @foreach ($sections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                    </select>

                    <select wire:model.live="byStudentStudent" @disabled(!$byStudentSection)
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed min-w-[160px]">
                        <option value="">Select Student</option>
                        @foreach ($students as $st)<option value="{{ $st->id }}">{{ $st->user->name ?? $st->full_name ?? '—' }}</option>@endforeach
                    </select>

                    @if ($byStudentExam || $byStudentStandard || $byStudentSection || $byStudentStudent)
                        <button wire:click="clearStudentFilters"
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

        @if ($activeTab === 'by-subject')
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Admission No</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Section</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Subject</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Remark</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($examCopies as $i => $copy)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $examCopies->firstItem() + $i }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            @if ($copy->studentDetail?->image)
                                                <img src="{{ $copy->studentDetail->image }}" class="w-8 h-8 rounded-full object-cover border border-gray-100">
                                            @else
                                                <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                                    <span class="text-xs font-semibold text-indigo-600">{{ strtoupper(substr($copy->studentDetail?->user?->name ?? $copy->studentDetail?->full_name ?? 'S', 0, 1)) }}</span>
                                                </div>
                                            @endif
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $copy->studentDetail?->user?->name ?? $copy->studentDetail?->full_name ?? '—' }}</p>
                                                <p class="text-xs text-gray-400">Roll: {{ $copy->studentDetail?->roll_no ?? '—' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $copy->studentDetail->admission_no ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $copy->standard->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $copy->section->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $copy->subject->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($copy->pdf_path)
                                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide bg-emerald-100 text-emerald-700">Uploaded</span>
                                        @else
                                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide bg-amber-100 text-amber-700">Pending</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600">{{ Str::limit($copy->remarks ?? '—', 30) }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            @if ($copy->pdf_path)
                                                <a href="{{ $this->getPdfUrl($copy->pdf_path) }}" target="_blank" rel="noopener" title="View PDF"
                                                    class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </a>
                                            @endif
                                            <button wire:click="openEditModal({{ $copy->id }})" title="Edit"
                                                class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-amber-50 hover:text-amber-600 hover:border-amber-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </button>
                                            <button wire:click="onDelete({{ $copy->id }})" title="Delete"
                                                class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-red-50 hover:text-red-600 hover:border-red-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="px-4 py-16 text-center">
                                    <p class="text-sm font-semibold text-gray-800">No exam copies found</p>
                                    <p class="text-xs text-gray-400 mt-1">Click "Upload Copies" to add the first record.</p>
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($examCopies instanceof \Illuminate\Pagination\LengthAwarePaginator && $examCopies->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">{{ $examCopies->links() }}</div>
                @endif
            </div>
        @else
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                @if (empty($studentResults))
                    <div class="text-center py-20 px-4">
                        <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        </div>
                        <p class="text-sm font-semibold text-gray-800">No student copies loaded</p>
                        <p class="text-xs text-gray-400 mt-1">Pick exam → class → section → student, then click Search.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Admission No</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Section</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Subject</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Remark</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($studentResults as $i => $r)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $i + 1 }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2.5">
                                                @if (!empty($r['student_detail']['image']))
                                                    <img src="{{ $r['student_detail']['image'] }}" class="w-8 h-8 rounded-full object-cover border border-gray-100">
                                                @else
                                                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                                        <span class="text-xs font-semibold text-indigo-600">{{ strtoupper(substr($r['student_detail']['user']['name'] ?? $r['student_detail']['full_name'] ?? 'S', 0, 1)) }}</span>
                                                    </div>
                                                @endif
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900">{{ $r['student_detail']['user']['name'] ?? $r['student_detail']['full_name'] ?? '—' }}</p>
                                                    <p class="text-xs text-gray-400">Roll: {{ $r['student_detail']['roll_no'] ?? '—' }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $r['student_detail']['admission_no'] ?? '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $r['standard']['name'] ?? '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $r['section']['name'] ?? '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $r['subject']['name'] ?? '—' }}</td>
                                        <td class="px-4 py-3 text-center">
                                            @if (!empty($r['pdf_path']))
                                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide bg-emerald-100 text-emerald-700">Uploaded</span>
                                            @else
                                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide bg-amber-100 text-amber-700">Pending</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-600">{{ Str::limit($r['remarks'] ?? '—', 30) }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-center gap-1">
                                                @if (!empty($r['pdf_path']))
                                                    <a href="{{ $this->getPdfUrl($r['pdf_path']) }}" target="_blank" rel="noopener" title="View PDF"
                                                        class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                    </a>
                                                @endif
                                                <button wire:click="openEditModal({{ $r['id'] }})" title="Edit"
                                                    class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-amber-50 hover:text-amber-600 hover:border-amber-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                </button>
                                                <button wire:click="onDelete({{ $r['id'] }})" title="Delete"
                                                    class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-red-50 hover:text-red-600 hover:border-red-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════
         UPLOAD COPIES SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($showUploadModal)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeUploadModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-5xl bg-white shadow-2xl flex flex-col">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Upload Exam Copies</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Pick exam, class, section & subject, then upload PDF per student</p>
                    </div>
                    <button wire:click="closeUploadModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Exam <span class="text-red-500">*</span></label>
                            <select wire:model.live="uploadExam" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                                <option value="">Select Exam</option>
                                @foreach ($exams as $exam)
                                    <option value="{{ $exam->id }}">{{ $exam->exam_name }}</option>
                                @endforeach
                            </select>
                            @error('uploadExam')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Class <span class="text-red-500">*</span></label>
                            <select wire:model.live="uploadStandard" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                                <option value="">Select Class</option>
                                @foreach ($standards as $std)
                                    <option value="{{ $std->id }}">{{ $std->name }}</option>
                                @endforeach
                            </select>
                            @error('uploadStandard')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Section <span class="text-red-500">*</span></label>
                            <select wire:model.live="uploadSection" @disabled(!$uploadStandard) class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm disabled:opacity-50">
                                <option value="">Select Section</option>
                                @foreach ($sections as $sec)
                                    <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                                @endforeach
                            </select>
                            @error('uploadSection')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Subject <span class="text-red-500">*</span></label>
                            <select wire:model.live="uploadSubject" @disabled(!$uploadSection || $subjects->isEmpty()) class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm disabled:opacity-50">
                                <option value="">Select Subject</option>
                                @foreach ($subjects as $sub)
                                    <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                                @endforeach
                            </select>
                            @if ($uploadExam && $uploadStandard && $uploadSection && $subjects->isEmpty())
                                <p class="text-[11px] text-amber-600 mt-1">Marks not uploaded yet for this exam &amp; class+section. Upload marks via Performance first to enable subject selection.</p>
                            @endif
                            @error('uploadSubject')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    @if (!empty($studentPdfs))
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50 border-b border-gray-200">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-10">#</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Adm No</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Section</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Subject</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">PDF</th>
                                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Remark</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($studentPdfs as $sid => $sp)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-3 py-2 text-xs text-gray-500">{{ $loop->iteration }}</td>
                                                <td class="px-3 py-2">
                                                    <div class="flex items-center gap-2">
                                                        @if (!empty($sp['student_image']))
                                                            <img src="{{ $sp['student_image'] }}" class="w-7 h-7 rounded-full object-cover border border-gray-100">
                                                        @else
                                                            <div class="w-7 h-7 rounded-full bg-indigo-100 flex items-center justify-center">
                                                                <span class="text-[10px] font-semibold text-indigo-600">{{ strtoupper(substr($sp['student_name'], 0, 1)) }}</span>
                                                            </div>
                                                        @endif
                                                        <span class="text-xs font-medium text-gray-900">{{ $sp['student_name'] }}</span>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2 text-xs text-gray-700">{{ $sp['admission_no'] }}</td>
                                                <td class="px-3 py-2 text-xs text-gray-700">{{ $sp['standard_name'] }}</td>
                                                <td class="px-3 py-2 text-xs text-gray-700">{{ $sp['section_name'] }}</td>
                                                <td class="px-3 py-2 text-xs text-gray-700">{{ $sp['subject_name'] }}</td>
                                                <td class="px-3 py-2">
                                                    @if ($sp['has_pdf'])
                                                        <div class="flex items-center gap-1">
                                                            <button wire:click="viewPdfInUpload({{ $sid }})" type="button" title="View PDF"
                                                                class="p-1 rounded border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                            </button>
                                                            <label class="cursor-pointer p-1 rounded border border-gray-200 text-gray-500 hover:bg-amber-50 hover:text-amber-600" title="Replace PDF">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                                <input type="file" wire:model="uploadedFiles.{{ $sid }}" accept=".pdf" class="hidden">
                                                            </label>
                                                            <button wire:click="deletePdfInUpload({{ $sid }})" type="button" title="Delete PDF"
                                                                class="p-1 rounded border border-gray-200 text-gray-500 hover:bg-red-50 hover:text-red-600">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                            </button>
                                                        </div>
                                                    @else
                                                        <label class="cursor-pointer inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded hover:bg-blue-100">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                                                            {{ isset($uploadedFiles[$sid]) ? 'Change' : 'Upload PDF' }}
                                                            <input type="file" wire:model="uploadedFiles.{{ $sid }}" accept=".pdf" class="hidden">
                                                        </label>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2 text-center">
                                                    @if ($sp['has_pdf'])
                                                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Uploaded</span>
                                                    @elseif (isset($uploadedFiles[$sid]) && $uploadedFiles[$sid])
                                                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-blue-100 text-blue-700">Ready</span>
                                                    @else
                                                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700">Pending</span>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input wire:model.defer="studentPdfs.{{ $sid }}.remarks" type="text" placeholder="Remark"
                                                        class="w-full px-2 py-1 text-xs border border-gray-200 rounded">
                                                </td>
                                            </tr>
                                            @error("uploadedFiles.$sid")
                                                <tr><td colspan="9" class="px-3 py-1 text-xs text-red-500">{{ $sp['student_name'] }}: {{ $message }}</td></tr>
                                            @enderror
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @elseif ($uploadExam && $uploadStandard && $uploadSection && $uploadSubject)
                        <div class="text-center py-8 text-sm text-gray-500">No students found for this class + section.</div>
                    @else
                        <div class="text-center py-8 text-sm text-gray-500">Select exam, class, section & subject to load student list.</div>
                    @endif
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeUploadModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="uploadPdfs" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60">
                        <span wire:loading.remove wire:target="uploadPdfs">Save Uploads</span>
                        <span wire:loading wire:target="uploadPdfs">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         EDIT SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($showEditModal && !empty($editCopyMeta))
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeEditModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Edit Exam Copy</h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $editCopyMeta['student_name'] }} · {{ $editCopyMeta['subject_name'] }}</p>
                    </div>
                    <button wire:click="closeEditModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div><p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Exam</p><p class="text-gray-800">{{ $editCopyMeta['exam_name'] }}</p></div>
                        <div><p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Admission</p><p class="text-gray-800">{{ $editCopyMeta['admission_no'] }}</p></div>
                        <div><p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Class</p><p class="text-gray-800">{{ $editCopyMeta['standard_name'] }}</p></div>
                        <div><p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Section</p><p class="text-gray-800">{{ $editCopyMeta['section_name'] }}</p></div>
                    </div>

                    @if ($editCopyMeta['pdf_url'])
                        <div class="border-t border-gray-100 pt-5">
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">Current PDF</p>
                            <a href="{{ $editCopyMeta['pdf_url'] }}" target="_blank" rel="noopener"
                                class="inline-flex items-center gap-1.5 text-sm text-blue-600 hover:underline">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                View Current PDF (new tab)
                            </a>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Replace PDF <span class="text-gray-400 font-normal">(Optional, max 5 MB)</span></label>
                        <input wire:model="editPdf" type="file" accept=".pdf" class="w-full text-sm">
                        <div wire:loading wire:target="editPdf" class="text-xs text-blue-600 mt-1">Uploading...</div>
                        @error('editPdf')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Remark</label>
                        <textarea wire:model.defer="editRemarks" rows="4"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm resize-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        @error('editRemarks')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeEditModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveEdit" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveEdit">Update</span>
                        <span wire:loading wire:target="saveEdit">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         DELETE CONFIRM
    ══════════════════════════════════════════════════ --}}
    @if ($showDeleteConfirm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDelete"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Delete exam copy?</h3>
                        <p class="text-sm text-gray-500">This will permanently delete the record and the uploaded PDF.</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button wire:click="cancelDelete" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="confirmDelete" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">Delete</button>
                </div>
            </div>
        </div>
    @endif

</div>
