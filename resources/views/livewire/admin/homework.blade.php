<div class="{{ ($embedded ?? false) ? '' : 'min-h-screen bg-gray-50' }}">

    {{-- ══════════════════════════════════════════════════
         HEADER (full-width, sticky, stats + Add button + filter bar)
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 {{ ($embedded ?? false) ? '' : 'sticky top-0 z-30' }}">
        <div class="px-4 sm:px-6 py-4 sm:py-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Homework</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Manage homework assignments for classes and subjects</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                        <span class="pr-4">Total: <strong class="text-gray-800">{{ $statistics['total'] ?? 0 }}</strong></span>
                        <span class="px-4">This Week: <strong class="text-emerald-600">{{ $statistics['this_week'] ?? 0 }}</strong></span>
                        <span class="px-4">Teachers: <strong class="text-purple-600">{{ $statistics['by_teacher'] ?? 0 }}</strong></span>
                        <span class="pl-4">Classes: <strong class="text-amber-500">{{ $statistics['by_class'] ?? 0 }}</strong></span>
                    </div>

                    <button wire:click="onAddHomework"
                        class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span class="hidden sm:inline">Add Homework</span>
                        <span class="sm:hidden">New</span>
                    </button>
                </div>
            </div>

            {{-- Mobile/Tablet stats --}}
            <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Total: <strong class="text-gray-800">{{ $statistics['total'] ?? 0 }}</strong></span>
                <span>This Week: <strong class="text-emerald-600">{{ $statistics['this_week'] ?? 0 }}</strong></span>
                <span>Teachers: <strong class="text-purple-600">{{ $statistics['by_teacher'] ?? 0 }}</strong></span>
                <span>Classes: <strong class="text-amber-500">{{ $statistics['by_class'] ?? 0 }}</strong></span>
            </div>
        </div>

        {{-- Filter bar --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter by:
                </div>

                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search title, description, teacher..."
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-56 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />

                <select wire:model.live="filterTeacher" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">All Teachers</option>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filterStandard" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">All Standards</option>
                    @foreach ($standards as $standard)
                        <option value="{{ $standard->id }}">{{ $standard->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filterSection" @disabled(!$filterStandard)
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                    <option value="">All Sections</option>
                    @foreach ($filterSections as $section)
                        <option value="{{ $section->id }}">{{ $section->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filterSubject" @disabled(!$filterStandard)
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                    <option value="">All Subjects</option>
                    @foreach ($filterSubjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                    @endforeach
                </select>

                @if ($search || $filterTeacher || $filterStandard || $filterSection || $filterSubject)
                    <button wire:click="clearFilters"
                        class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         BODY / TABLE
    ══════════════════════════════════════════════════ --}}
    <div class="p-4 sm:p-6">
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Homework</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Teacher</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Subject</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Assigned</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($homeworks as $homework)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-start gap-2">
                                    <p class="text-sm font-semibold text-gray-900">{{ $homework->title ?? 'No Title' }}</p>
                                    @if ($homework->file)
                                        <svg class="w-3.5 h-3.5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" title="Has attachment">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                        </svg>
                                    @endif
                                </div>
                                @if ($homework->description)
                                    <p class="text-xs text-gray-400 line-clamp-1 mt-0.5">{{ Str::limit($homework->description, 80) }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $homework->user->name ?? 'Unknown' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ $homework->standard->name ?? 'Unknown' }}
                                @if ($homework->section)
                                    <span class="text-gray-400">- {{ $homework->section->name }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($homework->subject)
                                    <span class="text-xs font-medium px-2 py-0.5 rounded bg-gray-100 text-gray-700">{{ $homework->subject->name }}</span>
                                @else
                                    <span class="text-xs font-medium px-2 py-0.5 rounded bg-blue-50 text-blue-700">All Subjects</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600">{{ $homework->created_at?->format('d M Y, h:i A') ?? 'Unknown' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <button wire:click="onViewHomework({{ $homework->id }})" title="View"
                                        class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <button wire:click="onEditHomework({{ $homework->id }})" title="Edit"
                                        class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-amber-50 hover:text-amber-600 hover:border-amber-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button wire:click="onDeleteHomework({{ $homework->id }})" title="Delete"
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
                                <p class="text-sm font-semibold text-gray-800">No homework found</p>
                                <p class="text-xs text-gray-400 mt-1">
                                    @if ($search || $filterTeacher || $filterStandard || $filterSection || $filterSubject)
                                        Try adjusting your search or filters.
                                    @else
                                        Create your first homework using the button above.
                                    @endif
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($homeworks->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $homeworks->links() }}
            </div>
        @endif
    </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         ADD / EDIT SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($open)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editId ? 'Edit Homework' : 'New Homework' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $editId ? 'Update homework details' : 'Create a new homework assignment' }}</p>
                    </div>
                    <button wire:click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Homework Title <span class="text-red-500">*</span></label>
                        <input wire:model.defer="title" type="text" placeholder="e.g. Chapter 3 exercises"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @error('title')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Standard <span class="text-red-500">*</span></label>
                            <select wire:model.live="standard_id" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                                <option value="">Select Standard</option>
                                @foreach ($standards as $standard)
                                    <option value="{{ $standard->id }}">{{ $standard->name }}</option>
                                @endforeach
                            </select>
                            @error('standard_id')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Section <span class="text-gray-400 font-normal">(Optional)</span></label>
                            <select wire:model.live="section_id" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                                <option value="">Select Section</option>
                                @foreach ($sections as $section)
                                    <option value="{{ $section->id }}">{{ $section->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Subject Selection</label>
                        <div class="flex gap-3">
                            <label class="flex-1 flex items-center gap-2 px-3 py-2 border rounded-md cursor-pointer text-sm transition-colors {{ $subject_selection === 'single' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-300 text-gray-700' }}">
                                <input type="radio" wire:model.live="subject_selection" value="single" class="text-blue-600 focus:ring-blue-500">
                                Single Subject
                            </label>
                            <label class="flex-1 flex items-center gap-2 px-3 py-2 border rounded-md cursor-pointer text-sm transition-colors {{ $subject_selection === 'all' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-300 text-gray-700' }}">
                                <input type="radio" wire:model.live="subject_selection" value="all" class="text-blue-600 focus:ring-blue-500">
                                All Subjects
                            </label>
                        </div>
                    </div>

                    @if ($subject_selection === 'single')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Subject <span class="text-red-500">*</span></label>
                            <select wire:model.defer="subject_id" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                                <option value="">Select Subject</option>
                                @foreach ($subjects as $subject)
                                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                @endforeach
                            </select>
                            @error('subject_id')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    @else
                        <div class="p-3 bg-blue-50 rounded-md">
                            <p class="text-sm text-blue-700">
                                <strong>Note:</strong> This homework will be assigned to <strong>ALL SUBJECTS</strong>
                                for the selected class{{ $section_id ? ' and section' : '' }}.
                            </p>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Description <span class="text-red-500">*</span></label>
                        <textarea wire:model.defer="description" rows="4" placeholder="Enter homework description..."
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm resize-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        @error('description')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Attachment <span class="text-gray-400 font-normal">(Optional)</span></label>

                        @if ($editId && !$homework_file)
                            @php $homeworkModel = \App\Models\Admin\HomeWork::find($editId) @endphp
                            @if ($homeworkModel && $homeworkModel->file)
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-sm text-blue-600">{{ basename($homeworkModel->file) }}</span>
                                    <button wire:click="$set('homework_file', null)" type="button" class="text-red-600 hover:text-red-800 text-xs">Remove</button>
                                </div>
                            @endif
                        @endif

                        @if ($tempFileUrl)
                            <span class="text-sm text-gray-600">{{ $tempFileUrl }}</span>
                        @endif

                        <input type="file" wire:model="homework_file"
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png"
                            class="block w-full text-sm text-gray-500
                               file:mr-4 file:py-2 file:px-4
                               file:rounded-md file:border-0
                               file:text-sm file:font-semibold
                               file:bg-blue-50 file:text-blue-700
                               hover:file:bg-blue-100">
                        @error('homework_file')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        <p class="text-xs text-gray-500 mt-1">Allowed: PDF, Word, Excel, PowerPoint, Text, Images (Max: 10MB)</p>
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="onSave" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="onSave">{{ $editId ? 'Update Homework' : 'Create Homework' }}</span>
                        <span wire:loading wire:target="onSave">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         VIEW SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($showViewModal && $viewHomework)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeViewModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $viewHomework->title }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $viewHomework->subject ? $viewHomework->subject->name : 'All Subjects' }} ·
                            {{ $viewHomework->created_at?->format('d M Y') }}
                        </p>
                    </div>
                    <button wire:click="closeViewModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Description</p>
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $viewHomework->description }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Teacher</p>
                            <p class="text-gray-800 font-medium">{{ $viewHomework->user->name ?? 'Unknown' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Class</p>
                            <p class="text-gray-800 font-medium">
                                {{ $viewHomework->standard->name ?? 'Unknown' }}
                                @if ($viewHomework->section)- {{ $viewHomework->section->name }}@endif
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Subject</p>
                            <p class="text-gray-800 font-medium">{{ $viewHomework->subject ? $viewHomework->subject->name : 'All Subjects' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Assigned</p>
                            <p class="text-gray-800 font-medium">{{ $viewHomework->created_at?->format('M d, Y h:i A') ?? 'Unknown' }}</p>
                        </div>
                    </div>

                    @if ($viewHomework->file)
                        <div class="pt-4 border-t border-gray-100">
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">Attachment</p>
                            <a href="{{ $viewHomework->file }}" target="_blank"
                                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                View Attachment
                            </a>
                        </div>
                    @endif
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeViewModal" class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
                </div>
            </div>
        </div>
    @endif
</div>
