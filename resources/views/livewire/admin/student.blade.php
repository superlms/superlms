<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════
         HEADER
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 pt-4 sm:pt-5 sticky top-0 z-50">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Students</h1>
                <p class="text-sm text-gray-500 mt-0.5">Manage student records and admissions</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                    <span class="pr-4">Total: <strong class="text-gray-800">{{ $totalStudents }}</strong></span>
                    <span class="px-4">Active: <strong class="text-emerald-600">{{ $activeStudents }}</strong></span>
                    <span class="px-4">Last Year: <strong
                            class="text-gray-800">{{ $lastYearStudents }}</strong></span>
                    <span class="pl-4">This Year: <strong
                            class="text-blue-600">{{ $thisYearStudents }}</strong></span>
                </div>
                <button wire:click="exportStudents"
                    class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-gray-100 hover:bg-gray-200
                           text-gray-700 text-sm font-semibold rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span class="hidden sm:inline">Export</span>
                </button>
                <button wire:click="onAddStudent"
                    class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700
                           text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="hidden sm:inline">Add Student</span>
                </button>
            </div>
        </div>

        {{-- Mobile / Tablet stats --}}
        <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
            <span>Total: <strong class="text-gray-800">{{ $totalStudents }}</strong></span>
            <span>Active: <strong class="text-emerald-600">{{ $activeStudents }}</strong></span>
            <span>Last Year: <strong class="text-gray-800">{{ $lastYearStudents }}</strong></span>
            <span>This Year: <strong class="text-blue-600">{{ $thisYearStudents }}</strong></span>
        </div>

        {{-- ══════════════════════════════════════════════════
             FILTER BAR — exam-style sub-header (attached, gray)
        ══════════════════════════════════════════════════ --}}
        <div class="border-t border-gray-200 bg-gray-50 -mx-4 sm:-mx-6 mt-3 sm:mt-4 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter by:
                </div>

                <input wire:model.live.debounce.300ms="search" type="text"
                    placeholder="Search name, admission, roll, phone, email…"
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-64
                           focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />

                <select wire:model.live="filterClass"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">All Classes</option>
                    @foreach ($standards as $standard)
                        <option value="{{ $standard->id }}">{{ $standard->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filterSection" @disabled(!$filterClass)
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <option value="">All Sections</option>
                    @foreach ($filterSections as $section)
                        <option value="{{ $section->id }}">{{ $section->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filterGender"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">All Genders</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>

                <select wire:model.live="filterStatus"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>

                <span class="text-gray-300">·</span>

                {{-- Sort control --}}
                <div class="flex items-center gap-1.5 text-xs font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" />
                    </svg>
                    Sort:
                </div>
                <select wire:model.live="sortBy"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="name_asc">Name (A → Z)</option>
                    <option value="admission_no">Admission No (asc)</option>
                    <option value="roll_no">Roll No (asc)</option>
                </select>

                @if ($search || $filterClass || $filterSection || $filterGender || $filterStatus !== '' || $sortBy !== 'name_asc')
                    <button wire:click="clearFilters"
                        class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 space-y-4 sm:space-y-5">

        {{-- ══════════════════════════════════════════════════
             DESKTOP TABLE (hidden on mobile)
        ══════════════════════════════════════════════════ --}}
        <div class="hidden md:block bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">
                                S.No</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Student</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Mobile</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Email</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Admission No</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Class</th>
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($students as $index => $student)
                            <tr class="hover:bg-gray-50/70 transition-colors">

                                {{-- S.No --}}
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-500 font-medium">
                                        {{ $students->firstItem() + $index }}
                                    </span>
                                </td>

                                {{-- Student (Image + Name) --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($student->user?->image)
                                            <img src="{{ $student->user->image }}"
                                                class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0 cursor-pointer"
                                                wire:click="onImageClick({{ $student->user->id }})">
                                        @else
                                            <div
                                                class="w-9 h-9 rounded-full bg-indigo-100 flex items-center
                                                        justify-center flex-shrink-0">
                                                <span class="text-xs font-semibold text-indigo-600">
                                                    {{ strtoupper(substr($student->full_name ?? 'S', 0, 1)) }}
                                                </span>
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 truncate">
                                                {{ $student->full_name ?? '—' }}</p>
                                            <p class="text-xs text-gray-400 capitalize">{{ $student->gender ?? '' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Mobile --}}
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-700">{{ $student->phone ?? '—' }}</span>
                                </td>

                                {{-- Email --}}
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-600 truncate block max-w-[200px]">
                                        {{ $student->user?->email ?? '—' }}
                                    </span>
                                </td>

                                {{-- Admission No --}}
                                <td class="px-4 py-3">
                                    <span
                                        class="text-sm font-mono text-gray-700">{{ $student->admission_no ?? '—' }}</span>
                                </td>

                                {{-- Class / Section --}}
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1.5">
                                        @if ($student->standard)
                                            <span
                                                class="text-xs px-2 py-0.5 bg-blue-50 text-blue-700
                                                rounded-full font-medium border border-blue-100">
                                                {{ $student->standard->name }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Actions (status dot shown inline) --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <span
                                            class="w-2 h-2 rounded-full flex-shrink-0 mr-1 {{ $student->user?->is_active ? 'bg-green-500' : 'bg-red-500' }}"
                                            title="{{ $student->user?->is_active ? 'Active' : 'Inactive' }}"></span>
                                        <button wire:click="onViewStudentAdmin({{ $student->id }})"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                            title="View">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <button wire:click="onEditStudent({{ $student->id }})"
                                            class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"
                                            title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button wire:click="onDeleteStudent({{ $student->id }})"
                                            class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <div
                                        class="w-16 h-16 bg-gray-100 rounded-full flex items-center
                                                justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 text-sm">No students found</p>
                                    @if ($search || $filterClass || $filterGender || $filterStatus !== '')
                                        <button wire:click="clearFilters"
                                            class="mt-3 text-sm text-blue-600 hover:text-blue-800 font-medium">
                                            Clear filters
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination (Desktop) --}}
            @if ($students->hasPages())
                <div
                    class="px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row
                            items-center justify-between gap-3">
                    <p class="text-sm text-gray-500">
                        Showing <span class="font-medium text-gray-700">{{ $students->firstItem() }}</span>
                        to <span class="font-medium text-gray-700">{{ $students->lastItem() }}</span>
                        of <span class="font-medium text-gray-700">{{ $students->total() }}</span> students
                    </p>
                    <div class="flex items-center gap-1">
                        @if ($students->onFirstPage())
                            <span
                                class="px-3 py-1.5 text-sm text-gray-300 border border-gray-200 rounded-lg cursor-not-allowed">
                                &laquo; Prev</span>
                        @else
                            <button wire:click="previousPage"
                                class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300
                                       rounded-lg hover:bg-gray-50 transition-colors">&laquo;
                                Prev</button>
                        @endif

                        @foreach ($students->getUrlRange(max(1, $students->currentPage() - 2), min($students->lastPage(), $students->currentPage() + 2)) as $page => $url)
                            <button wire:click="gotoPage({{ $page }})"
                                class="px-3 py-1.5 text-sm rounded-lg transition-colors
                                    {{ $page == $students->currentPage()
                                        ? 'bg-blue-600 text-white border border-blue-600'
                                        : 'text-gray-600 border border-gray-300 hover:bg-gray-50' }}">
                                {{ $page }}
                            </button>
                        @endforeach

                        @if ($students->hasMorePages())
                            <button wire:click="nextPage"
                                class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300
                                       rounded-lg hover:bg-gray-50 transition-colors">Next
                                &raquo;</button>
                        @else
                            <span
                                class="px-3 py-1.5 text-sm text-gray-300 border border-gray-200 rounded-lg cursor-not-allowed">
                                Next &raquo;</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- ══════════════════════════════════════════════════
             MOBILE CARDS (shown only on mobile)
        ══════════════════════════════════════════════════ --}}
        <div class="md:hidden space-y-3">
            @forelse ($students as $index => $student)
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    {{-- Card Header --}}
                    <div class="flex items-center gap-3 p-4 border-b border-gray-100">
                        <span class="text-xs font-bold text-gray-400 w-6 text-center">
                            {{ $students->firstItem() + $index }}
                        </span>
                        @if ($student->user?->image)
                            <img src="{{ $student->user->image }}"
                                class="w-10 h-10 rounded-full object-cover border border-gray-200 flex-shrink-0"
                                wire:click="onImageClick({{ $student->user->id }})">
                        @else
                            <div
                                class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                <span class="text-sm font-semibold text-indigo-600">
                                    {{ strtoupper(substr($student->full_name ?? 'S', 0, 1)) }}
                                </span>
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $student->full_name ?? '—' }}
                            </p>
                            <p class="text-xs text-gray-400">{{ $student->user?->email ?? '—' }}</p>
                        </div>
                        @if ($student->user?->is_active)
                            <span
                                class="inline-flex items-center gap-1 text-xs px-2 py-0.5
                                bg-green-50 text-green-700 rounded-full font-medium border border-green-100">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                Active
                            </span>
                        @else
                            <span
                                class="inline-flex items-center gap-1 text-xs px-2 py-0.5
                                bg-red-50 text-red-600 rounded-full font-medium border border-red-100">
                                <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                                Inactive
                            </span>
                        @endif
                    </div>

                    {{-- Card Body --}}
                    <div class="px-4 py-3 space-y-2">
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div>
                                <p class="text-xs text-gray-400">Mobile</p>
                                <p class="text-gray-700 font-medium">{{ $student->phone ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">Admission No</p>
                                <p class="text-gray-700 font-mono text-xs font-medium">
                                    {{ $student->admission_no ?? '—' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <p class="text-xs text-gray-400">Class/Section:</p>
                            <div class="flex gap-1.5">
                                @if ($student->standard)
                                    <span
                                        class="text-xs px-2 py-0.5 bg-blue-50 text-blue-700
                                        rounded-full font-medium border border-blue-100">
                                        {{ $student->standard->name }}
                                    </span>
                                @endif
                                @if ($student->section)
                                    <span
                                        class="text-xs px-2 py-0.5 bg-purple-50 text-purple-700
                                        rounded-full font-medium border border-purple-100">
                                        {{ $student->section->name }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Card Actions --}}
                    <div class="flex items-center border-t border-gray-100 divide-x divide-gray-100">
                        <button wire:click="onViewStudentAdmin({{ $student->id }})"
                            class="flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-medium
                                   text-blue-600 hover:bg-blue-50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            View
                        </button>
                        <button wire:click="onEditStudent({{ $student->id }})"
                            class="flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-medium
                                   text-amber-600 hover:bg-amber-50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit
                        </button>
                        <button wire:click="onDeleteStudent({{ $student->id }})"
                            class="flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-medium
                                   text-red-600 hover:bg-red-50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Delete
                        </button>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <p class="text-gray-500 text-sm">No students found</p>
                    @if ($search || $filterClass || $filterGender || $filterStatus !== '')
                        <button wire:click="clearFilters"
                            class="mt-3 text-sm text-blue-600 hover:text-blue-800 font-medium">
                            Clear filters
                        </button>
                    @endif
                </div>
            @endforelse

            {{-- Pagination (Mobile) --}}
            @if ($students->hasPages())
                <div
                    class="flex items-center justify-between bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3">
                    <p class="text-xs text-gray-500">
                        {{ $students->firstItem() }}–{{ $students->lastItem() }} of {{ $students->total() }}
                    </p>
                    <div class="flex items-center gap-1">
                        @if (!$students->onFirstPage())
                            <button wire:click="previousPage"
                                class="px-2.5 py-1 text-xs text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                                Prev
                            </button>
                        @endif
                        <span class="px-2.5 py-1 text-xs bg-blue-600 text-white rounded-lg">
                            {{ $students->currentPage() }}
                        </span>
                        @if ($students->hasMorePages())
                            <button wire:click="nextPage"
                                class="px-2.5 py-1 text-xs text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                                Next
                            </button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         ADD / EDIT — teacher-style slide-in panel
         (flat 2-col grid, sticky header + footer)
    ══════════════════════════════════════════════════ --}}
    @if ($open)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-2xl bg-white shadow-2xl flex flex-col">

                {{-- Fixed header (teacher template) --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ $editId ? 'Edit Student' : 'New Student' }}
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $editId ? 'Update the student details below' : 'Welcome email with login credentials will be sent on save' }}
                        </p>
                    </div>
                    <button wire:click="closeModal" type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Scrollable form body (flat teacher grid) --}}
                <form id="student-form" wire:submit.prevent="onSave" class="flex-1 overflow-y-auto">
                    <div class="px-6 py-6 space-y-5">

                        {{-- Inline save-error banner. The WireUI toast at top-right is
                             easy to miss with the slide-in modal taking up most of the
                             screen, so we surface the same error here too. --}}
                        @if ($saveError)
                            <div class="flex items-start gap-2 p-3 bg-red-50 border border-red-200 rounded-md">
                                <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A8.25 8.25 0 1112 20.25a8.25 8.25 0 010-16.536zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-red-800">Could not save this student</p>
                                    <p class="text-xs text-red-700 mt-0.5 break-words">{{ $saveError }}</p>
                                </div>
                            </div>
                        @endif

                        {{-- Profile image (single inline row, teacher style) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                Student Profile Photo <span class="text-gray-400 font-normal">(Optional, max 1 MB)</span>
                            </label>
                            <div class="flex items-center gap-3">
                                @if ($studentImage)
                                    <img src="{{ $studentImage->temporaryUrl() }}"
                                        class="w-12 h-12 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                @elseif ($studentImageUrl)
                                    <img src="{{ $studentImageUrl }}"
                                        class="w-12 h-12 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                @else
                                    <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                @endif
                                <input type="file" wire:model="studentImage" accept="image/*" class="flex-1 text-sm">
                            </div>
                            <div wire:loading wire:target="studentImage" class="text-xs text-blue-600 mt-1">Uploading…</div>
                            @error('studentImage')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        {{-- Personal + parents + academic + address fields, all in one flat 2-col grid --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name <span class="text-red-500">*</span></label>
                                <input wire:model.defer="studentsName" type="text" maxlength="50" placeholder="Enter full name"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                           @error('studentsName') border-red-400 @enderror">
                                @error('studentsName')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Email <span class="text-red-500">*</span></label>
                                <input wire:model.defer="studentsEmail" type="email" placeholder="student@example.com"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                           @error('studentsEmail') border-red-400 @enderror">
                                @error('studentsEmail')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Mobile <span class="text-red-500">*</span></label>
                                <input wire:model.defer="studentsMobile" type="tel" maxlength="10" inputmode="numeric"
                                    oninput="this.value=this.value.replace(/\D/g,'').slice(0,10)" placeholder="10-digit mobile"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                           @error('studentsMobile') border-red-400 @enderror">
                                @error('studentsMobile')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Gender <span class="text-red-500">*</span></label>
                                <select wire:model.defer="studentsGender"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                           @error('studentsGender') border-red-400 @enderror">
                                    <option value="">Select Gender</option>
                                    @foreach (App\Helpers\Constants::GENDER as $gender)
                                        <option value="{{ $gender }}">{{ ucfirst($gender) }}</option>
                                    @endforeach
                                </select>
                                @error('studentsGender')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            {{-- Class & Section: moved up here so users don't miss them. Save was
                                 silently "failing" because validation flagged these required fields,
                                 but the error messages rendered below the visible viewport. --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Class <span class="text-red-500">*</span></label>
                                <select wire:model.live="studentsClass"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                           @error('studentsClass') border-red-400 @enderror">
                                    <option value="">Select Class</option>
                                    @foreach ($standards as $standard)
                                        <option value="{{ $standard->id }}">{{ $standard->name }}{{ $standard->board ? ' · ' . $standard->board : '' }}</option>
                                    @endforeach
                                </select>
                                @error('studentsClass')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                <p class="mt-1 text-[11px] text-gray-400">Board is auto-fetched from the class.</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Section <span class="text-red-500">*</span></label>
                                <select wire:model.defer="studentsSection" @disabled(!$studentsClass)
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50
                                           @error('studentsSection') border-red-400 @enderror">
                                    <option value="">Select Section</option>
                                    @foreach ($sections as $section)
                                        <option value="{{ $section->id }}">{{ $section->name }}</option>
                                    @endforeach
                                </select>
                                @error('studentsSection')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Date of Birth <span class="text-red-500">*</span></label>
                                <input wire:model.defer="dob" type="date" max="{{ now()->subDay()->format('Y-m-d') }}"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                           @error('dob') border-red-400 @enderror">
                                @error('dob')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                    Date of Admission <span class="text-gray-400 font-normal text-xs">(optional)</span>
                                </label>
                                <input wire:model.defer="dateOfAdmission" type="date" max="{{ now()->format('Y-m-d') }}"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                           @error('dateOfAdmission') border-red-400 @enderror">
                                @error('dateOfAdmission')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Religion</label>
                                <input wire:model.defer="religion" type="text" maxlength="20" placeholder="e.g. Hindu, Muslim"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                           @error('religion') border-red-400 @enderror">
                                @error('religion')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Aadhar Number</label>
                                <input wire:model.defer="aadharNo" type="text" maxlength="12" inputmode="numeric"
                                    oninput="this.value=this.value.replace(/\D/g,'').slice(0,12)" placeholder="12-digit Aadhar no."
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                           @error('aadharNo') border-red-400 @enderror">
                                @error('aadharNo')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Father's Name <span class="text-red-500">*</span></label>
                                <input wire:model.defer="fatherName" type="text" maxlength="50" placeholder="Father's full name"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                           @error('fatherName') border-red-400 @enderror">
                                @error('fatherName')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                    Mother's Name <span class="text-gray-400 font-normal text-xs">(optional)</span>
                                </label>
                                <input wire:model.defer="motherName" type="text" maxlength="50" placeholder="Mother's full name"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                           @error('motherName') border-red-400 @enderror">
                                @error('motherName')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Apaar ID</label>
                                <input wire:model.defer="apparId" type="text" maxlength="25" placeholder="Apaar ID"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                           @error('apparId') border-red-400 @enderror">
                                @error('apparId')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Registration Number</label>
                                <input wire:model.defer="registrationNumber" type="text" maxlength="25" placeholder="Registration number"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                           @error('registrationNumber') border-red-400 @enderror">
                                @error('registrationNumber')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">State</label>
                                <select wire:model.live="selectedState"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select State</option>
                                    @foreach ($states as $state)
                                        <option value="{{ $state }}">{{ $state }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">City</label>
                                <select wire:model.defer="selectedCity" @disabled(!$selectedState)
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50">
                                    <option value="">Select City</option>
                                    @foreach ($cities as $city)
                                        @php $cityName = is_array($city) ? ($city['name'] ?? '') : (string) $city; @endphp
                                        @if ($cityName !== '')
                                            <option value="{{ $cityName }}">{{ $cityName }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Pincode</label>
                                <input wire:model.defer="pincode" type="text" maxlength="6" inputmode="numeric"
                                    oninput="this.value=this.value.replace(/\D/g,'').slice(0,6)" placeholder="6-digit pincode"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                           @error('pincode') border-red-400 @enderror">
                                @error('pincode')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Transport Required? <span class="text-red-500">*</span></label>
                                <select wire:model.live="transportationRequired"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                            @if ($transportationRequired)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Select Route <span class="text-red-500">*</span></label>
                                    <select wire:model.live="selectedRoute"
                                        class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                               @error('selectedRoute') border-red-400 @enderror">
                                        <option value="">Select Route</option>
                                        @foreach ($routeOptions as $route)
                                            <option value="{{ $route->id }}">{{ $route->route_name }} — ₹{{ number_format($route->monthly_fee, 0) }}/mo</option>
                                        @endforeach
                                    </select>
                                    @error('selectedRoute')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                    @if (count($routeOptions) === 0)
                                        <p class="text-amber-600 text-xs mt-1">No active routes available. Add routes in the Transport module first.</p>
                                    @endif

                                    @php $chosenRoute = collect($routeOptions)->firstWhere('id', (int) $selectedRoute); @endphp
                                    @if ($chosenRoute)
                                        <div class="mt-2 bg-blue-50 border border-blue-100 rounded-md px-3 py-2 space-y-1">
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs text-gray-600">Monthly Fee</span>
                                                <span class="text-sm font-semibold text-blue-700">₹{{ number_format($chosenRoute->monthly_fee, 2) }}</span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs text-gray-600">Annual (× 11)</span>
                                                <span class="text-sm font-semibold text-blue-700">₹{{ number_format($chosenRoute->monthly_fee * 11, 2) }}</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Full-width address textareas --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Local Address</label>
                            <textarea wire:model.defer="localAddress" rows="2" maxlength="250" placeholder="Current/local address"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 resize-none
                                       @error('localAddress') border-red-400 @enderror"></textarea>
                            @error('localAddress')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Permanent Address</label>
                            <textarea wire:model.defer="permanentAddress" rows="2" maxlength="250" placeholder="Permanent address (if different)"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 resize-none
                                       @error('permanentAddress') border-red-400 @enderror"></textarea>
                            @error('permanentAddress')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        @if ($this->isOrphaned)
                            <div class="px-3 py-2 bg-amber-50 border border-amber-200 rounded-md text-xs text-amber-700">
                                This student's previous class was deleted. Assign a class &amp; section to re-activate.
                            </div>
                        @endif

                        {{-- Active toggle --}}
                        <label class="inline-flex items-center gap-2 cursor-pointer {{ $this->isOrphaned ? 'opacity-60 cursor-not-allowed' : '' }}">
                            <input type="checkbox" wire:model.defer="studentsActive" class="rounded" @disabled($this->isOrphaned)>
                            <span class="text-sm text-gray-700">Active (can log in)</span>
                        </label>
                        @error('studentsActive')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </form>

                {{-- Footer — Cancel + single Save / Update button --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button type="button" wire:click="closeModal"
                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">
                        Cancel
                    </button>

                    {{-- Use wire:click instead of the HTML5 form="…" + type=submit trick.
                         The external-button trick depends on the browser firing the form's
                         submit event for a button outside the form — which is fragile across
                         Livewire morph cycles and some browser/extension combinations. A
                         plain wire:click is bulletproof. The form's wire:submit.prevent
                         still handles Enter-key submission from any input. --}}
                    <button type="button" wire:click="onSave" wire:loading.attr="disabled" wire:target="onSave"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md
                               flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="onSave">
                            {{ $editId ? ($this->isOrphaned ? 'Update (Reassign Class)' : 'Update Student') : 'Create Student' }}
                        </span>
                        <span wire:loading wire:target="onSave">Saving...</span>
                    </button>
                </div>

            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         VIEW — exam-style slide-in panel
    ══════════════════════════════════════════════════ --}}
    @if ($showViewModal && !empty($viewData))
        @php
            $stuUser  = $viewData['user']    ?? null;
            $stuDet   = $viewData['detail']  ?? null;
            $stuOrg   = $viewData['organization'] ?? null;
            $boardLbl = $stuDet->board ?? optional($stuDet?->standard)->board ?? '—';
            $route    = $stuDet?->transportations?->first();
        @endphp
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeViewModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-2xl bg-white shadow-2xl flex flex-col">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Student Details</h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $stuDet->admission_no ?? 'Profile, academic & contact info' }}</p>
                    </div>
                    <button wire:click="closeViewModal"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5 text-sm text-gray-700">

                    {{-- Profile strip --}}
                    <div class="flex items-center gap-4 pb-4 border-b border-gray-100">
                        @if ($studentImageUrl)
                            <img src="{{ $studentImageUrl }}" class="w-16 h-16 rounded-full object-cover border border-gray-200">
                        @else
                            <div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center">
                                <span class="text-xl font-bold text-indigo-600">{{ strtoupper(substr($stuUser->name ?? 'S', 0, 1)) }}</span>
                            </div>
                        @endif
                        <div class="min-w-0">
                            <h3 class="text-lg font-semibold text-gray-900 truncate">{{ $stuUser->name ?? '—' }}</h3>
                            @if ($stuOrg)
                                <p class="text-xs text-gray-500 mt-0.5">
                                    <svg class="w-3 h-3 inline-block text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2m-2 0h-3m-1-9V7m0 5l-3-3m3 3l3-3" />
                                    </svg>
                                    {{ $stuOrg->name }}
                                </p>
                            @endif
                            <div class="flex items-center gap-2 mt-1">
                                @if ($stuUser->is_active ?? false)
                                    <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 bg-green-50 text-green-700 rounded-full font-medium border border-green-100">
                                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 bg-red-50 text-red-600 rounded-full font-medium border border-red-100">
                                        <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span> Inactive
                                    </span>
                                @endif
                                <span class="text-xs text-gray-400 font-mono">Adm: {{ $stuDet->admission_no ?? '—' }}</span>
                                <span class="text-xs text-gray-400 font-mono">Roll: {{ $stuDet->roll_no ?? '—' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Personal Information --}}
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Personal Information</h4>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div><dt class="text-xs text-gray-400">Email</dt><dd class="font-medium">{{ $stuUser->email ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Mobile</dt><dd class="font-medium">{{ $stuUser->mobile_number ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Gender</dt><dd class="font-medium capitalize">{{ $stuDet->gender ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Date of Birth</dt><dd class="font-medium">{{ $stuDet->dob?->format('d M Y') ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Father's Name</dt><dd class="font-medium">{{ $stuDet->father_name ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Mother's Name</dt><dd class="font-medium">{{ $stuDet->mother_name ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Religion</dt><dd class="font-medium">{{ $stuDet->religion ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Aadhar No</dt><dd class="font-medium font-mono">{{ $stuDet->aadhar_no ?? '—' }}</dd></div>
                        </dl>
                    </div>

                    {{-- Academic Information --}}
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Academic Information</h4>
                        <dl class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <div><dt class="text-xs text-gray-400">Class</dt><dd class="font-medium">{{ $stuDet->standard->name ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Section</dt><dd class="font-medium">{{ $stuDet->section->name ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Board <span class="text-[10px] text-gray-300">(auto)</span></dt><dd class="font-medium">{{ $boardLbl }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Roll No</dt><dd class="font-medium font-mono">{{ $stuDet->roll_no ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Admission No</dt><dd class="font-medium font-mono">{{ $stuDet->admission_no ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Admission Date</dt><dd class="font-medium">{{ $stuDet->date_of_admission?->format('d M Y') ?? '—' }}</dd></div>
                            @if ($stuDet->appar_id)
                                <div><dt class="text-xs text-gray-400">Apaar ID</dt><dd class="font-medium">{{ $stuDet->appar_id }}</dd></div>
                            @endif
                            @if ($stuDet->registration_number)
                                <div><dt class="text-xs text-gray-400">Registration No</dt><dd class="font-medium">{{ $stuDet->registration_number }}</dd></div>
                            @endif
                        </dl>
                    </div>

                    {{-- Address --}}
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Address</h4>
                        <dl class="space-y-3">
                            <div><dt class="text-xs text-gray-400">Local Address</dt><dd class="font-medium">{{ $stuDet->local_address ?? '—' }}</dd></div>
                            @if ($stuDet->permanent_address)
                                <div><dt class="text-xs text-gray-400">Permanent Address</dt><dd class="font-medium">{{ $stuDet->permanent_address }}</dd></div>
                            @endif
                            <div class="grid grid-cols-3 gap-3">
                                <div><dt class="text-xs text-gray-400">City</dt><dd class="font-medium">{{ $stuDet->city ?? '—' }}</dd></div>
                                <div><dt class="text-xs text-gray-400">State</dt><dd class="font-medium">{{ $stuDet->state ?? '—' }}</dd></div>
                                <div><dt class="text-xs text-gray-400">Pincode</dt><dd class="font-medium">{{ $stuDet->pincode ?? '—' }}</dd></div>
                            </div>
                        </dl>
                    </div>

                    {{-- Transport --}}
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Transport</h4>
                        <dl class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <dt class="text-xs text-gray-400">Required</dt>
                                <dd class="font-medium {{ $stuDet->transportation_required ? 'text-green-700' : 'text-gray-500' }}">
                                    {{ $stuDet->transportation_required ? 'Yes' : 'No' }}
                                </dd>
                            </div>
                            @if ($route)
                                <div><dt class="text-xs text-gray-400">Route</dt><dd class="font-medium">{{ $route->route_name }}</dd></div>
                                <div><dt class="text-xs text-gray-400">Fee</dt>
                                    <dd class="font-medium text-blue-700">
                                        ₹{{ number_format($route->monthly_fee, 0) }}/mo
                                        · ₹{{ number_format($route->monthly_fee * 11, 0) }}/yr
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-between flex-shrink-0">
                    <button type="button" wire:click="onEditStudent({{ $stuDet->id ?? 0 }})"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit
                    </button>
                    <button type="button" wire:click="closeViewModal"
                        class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         DELETE CONFIRM OVERLAY
         (replaces broken WireUI dialog() — same pattern as
          other admin pages: Enquiries, Exams, etc.)
    ══════════════════════════════════════════════════ --}}
    @if ($showDeleteConfirm)
        <div class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDelete"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Delete student?</h3>
                        <p class="text-sm text-gray-500">This will permanently delete the student account, profile, and uploaded photo. This action cannot be undone.</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button wire:click="cancelDelete" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md transition-colors">Cancel</button>
                    <button wire:click="confirmDelete" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md disabled:opacity-60 flex items-center gap-1.5">
                        <span wire:loading.remove wire:target="confirmDelete">Delete Student</span>
                        <span wire:loading wire:target="confirmDelete">Deleting...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
