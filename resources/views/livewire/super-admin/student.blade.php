<div class="min-h-screen bg-gray-50">

    {{-- ══════════ HEADER (analytics + filter) ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="px-4 sm:px-6 py-4 sm:py-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Students</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Manage all student records across schools</p>
                </div>
                <div class="flex items-center gap-4">
                    {{-- Analytics (Users-style) --}}
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200">
                        <span class="pr-4">Schools: <strong class="text-gray-800">{{ $totalSchools }}</strong></span>
                        <span class="px-4">Total: <strong class="text-gray-800">{{ $totalStudents }}</strong></span>
                        <span class="px-4">Active: <strong class="text-emerald-600">{{ $activeStudents }}</strong></span>
                        <span class="pl-4">Inactive: <strong class="text-rose-500">{{ $inactiveStudents }}</strong></span>
                    </div>

                    <div class="flex items-center gap-2 flex-shrink-0">
                        {{-- Export Button (first) --}}
                        <button wire:click="exportStudents" @disabled(!$filterOrganization)
                            class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 text-sm font-semibold
                                   rounded-lg transition-colors
                                   {{ $filterOrganization
                                       ? 'bg-gray-100 hover:bg-gray-200 text-gray-700 cursor-pointer'
                                       : 'bg-gray-50 text-gray-300 cursor-not-allowed border border-gray-200' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <span class="hidden sm:inline">{{ $filterOrganization ? 'Export' : 'Select School to Export' }}</span>
                            <span class="sm:hidden">Export</span>
                        </button>

                        {{-- Add Student Button (after Export) --}}
                        <button wire:click="openAddPanel"
                            class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 text-sm font-semibold text-white
                                   bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span class="hidden sm:inline">Add Student</span>
                            <span class="sm:hidden">Add</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Mobile stats --}}
            <div class="flex lg:hidden items-center gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Schools: <strong class="text-gray-800">{{ $totalSchools }}</strong></span>
                <span>Total: <strong class="text-gray-800">{{ $totalStudents }}</strong></span>
                <span>Active: <strong class="text-emerald-600">{{ $activeStudents }}</strong></span>
                <span>Inactive: <strong class="text-rose-500">{{ $inactiveStudents }}</strong></span>
            </div>
        </div>

        {{-- Filter bar (enquiry-style sub-header) --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter by:
                </div>

                <div class="relative">
                    <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" /></svg>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search name, email, admission, roll…"
                        class="text-xs bg-white border border-gray-200 rounded-md pl-8 pr-3 py-1.5 text-gray-700 w-56 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <select wire:model.live="filterOrganization"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Schools</option>
                    @foreach ($organizations as $org)
                        <option value="{{ $org->id }}">{{ $org->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filterClass" @disabled(!$filterOrganization)
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                    <option value="">All Classes</option>
                    @foreach ($standards as $standard)
                        <option value="{{ $standard->id }}">{{ $standard->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filterSection" @disabled(!$filterClass)
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                    <option value="">All Sections</option>
                    @foreach ($filterSections as $section)
                        <option value="{{ $section->id }}">{{ $section->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filterGender"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Genders</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>

                <select wire:model.live="filterStatus"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>

                @if ($search || $filterOrganization || $filterClass || $filterSection || $filterGender || $filterStatus !== '')
                    <button wire:click="clearFilters"
                        class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 space-y-4 sm:space-y-5">

        {{-- ══════════ DESKTOP TABLE ══════════ --}}
        <div class="hidden md:block bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="overflow-x-auto rounded-xl">
                <table class="w-full min-w-[860px]">
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
                                School</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Mobile</th>
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

                                {{-- Student --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($student->user?->image)
                                            <img src="{{ $student->user->image }}"
                                                class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                        @else
                                            <div
                                                class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                                <span class="text-xs font-semibold text-indigo-600">
                                                    {{ strtoupper(substr($student->full_name ?? 'S', 0, 1)) }}
                                                </span>
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 truncate">
                                                {{ $student->full_name ?? '—' }}</p>
                                            <p class="text-xs text-gray-400 truncate">
                                                {{ $student->user?->email ?? '—' }}</p>
                                        </div>
                                    </div>
                                </td>

                                {{-- School --}}
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-700 truncate block max-w-[160px]">
                                        {{ $student->user?->organization?->name ?? '—' }}
                                    </span>
                                </td>

                                {{-- Mobile --}}
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-700">{{ $student->phone ?? '—' }}</span>
                                </td>

                                {{-- Admission No --}}
                                <td class="px-4 py-3">
                                    <span
                                        class="text-sm font-mono text-gray-700">{{ $student->admission_no ?? '—' }}</span>
                                </td>

                                {{-- Class / Section --}}
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center gap-1">
                                        @if ($student->standard)
                                            <span
                                                class="text-xs px-2 py-0.5 bg-blue-50 text-blue-700 rounded-full font-medium border border-blue-100 whitespace-nowrap">
                                                {{ $student->standard->name }}
                                            </span>
                                        @endif
                                        @if (!$student->standard && !$student->section)
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Actions --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <button wire:click="onViewStudentSuperAdmin({{ $student->user_id }})"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                            title="View">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <button wire:click="openEditPanel({{ $student->id }})"
                                            class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"
                                            title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button wire:click="onDeleteStudentSuperAdmin({{ $student->id }})"
                                            class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-16 text-center">
                                    <div
                                        class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 text-sm">No students found</p>
                                    @if ($search || $filterOrganization || $filterClass || $filterGender || $filterStatus !== '')
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

            {{-- Pagination Desktop --}}
            @if ($students->hasPages())
                <div
                    class="px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p class="text-sm text-gray-500">
                        Showing <strong class="text-gray-700">{{ $students->firstItem() }}</strong>
                        to <strong class="text-gray-700">{{ $students->lastItem() }}</strong>
                        of <strong class="text-gray-700">{{ $students->total() }}</strong> students
                    </p>
                    <div class="flex items-center gap-1">
                        @if ($students->onFirstPage())
                            <span
                                class="px-3 py-1.5 text-sm text-gray-300 border border-gray-200 rounded-lg cursor-not-allowed">&laquo;
                                Prev</span>
                        @else
                            <button wire:click="previousPage"
                                class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                &laquo; Prev
                            </button>
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
                                class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                Next &raquo;
                            </button>
                        @else
                            <span
                                class="px-3 py-1.5 text-sm text-gray-300 border border-gray-200 rounded-lg cursor-not-allowed">Next
                                &raquo;</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- ══════════ MOBILE CARDS ══════════ --}}
        <div class="md:hidden space-y-3">
            @forelse ($students as $index => $student)
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="flex items-center gap-3 p-4 border-b border-gray-100">
                        <span class="text-xs font-bold text-gray-400 w-6 text-center">
                            {{ $students->firstItem() + $index }}
                        </span>
                        @if ($student->user?->image)
                            <img src="{{ $student->user->image }}"
                                class="w-10 h-10 rounded-full object-cover border border-gray-200 flex-shrink-0">
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
                            <p class="text-xs text-gray-400 truncate">
                                {{ $student->user?->organization?->name ?? '—' }}</p>
                        </div>
                        @if ($student->user?->is_active)
                            <span
                                class="inline-flex items-center gap-1 text-xs px-2 py-0.5
                                bg-green-50 text-green-700 rounded-full font-medium border border-green-100">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Active
                            </span>
                        @else
                            <span
                                class="inline-flex items-center gap-1 text-xs px-2 py-0.5
                                bg-red-50 text-red-600 rounded-full font-medium border border-red-100">
                                <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span> Inactive
                            </span>
                        @endif
                    </div>

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
                                        class="text-xs px-2 py-0.5 bg-blue-50 text-blue-700 rounded-full font-medium border border-blue-100">
                                        {{ $student->standard->name }}
                                    </span>
                                @endif
                                @if ($student->section)
                                    <span
                                        class="text-xs px-2 py-0.5 bg-purple-50 text-purple-700 rounded-full font-medium border border-purple-100">
                                        {{ $student->section->name }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center border-t border-gray-100 divide-x divide-gray-100">
                        <button wire:click="onViewStudentSuperAdmin({{ $student->user_id }})"
                            class="flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-medium
                                   text-blue-600 hover:bg-blue-50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            View
                        </button>
                        <button wire:click="openEditPanel({{ $student->id }})"
                            class="flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-medium
                                   text-amber-600 hover:bg-amber-50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit
                        </button>
                        <button wire:click="onDeleteStudentSuperAdmin({{ $student->id }})"
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
                    @if ($search || $filterOrganization || $filterClass || $filterGender || $filterStatus !== '')
                        <button wire:click="clearFilters"
                            class="mt-3 text-sm text-blue-600 hover:text-blue-800 font-medium">Clear filters</button>
                    @endif
                </div>
            @endforelse

            {{-- Pagination Mobile --}}
            @if ($students->hasPages())
                <div
                    class="flex items-center justify-between bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3">
                    <p class="text-xs text-gray-500">
                        {{ $students->firstItem() }}–{{ $students->lastItem() }} of {{ $students->total() }}
                    </p>
                    <div class="flex items-center gap-1">
                        @if (!$students->onFirstPage())
                            <button wire:click="previousPage"
                                class="px-2.5 py-1 text-xs text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Prev</button>
                        @endif
                        <span class="px-2.5 py-1 text-xs bg-blue-600 text-white rounded-lg">
                            {{ $students->currentPage() }}
                        </span>
                        @if ($students->hasMorePages())
                            <button wire:click="nextPage"
                                class="px-2.5 py-1 text-xs text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Next</button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════ ADD STUDENT SLIDE-IN PANEL ══════════ --}}
    @if ($showAddPanel)
        <div class="fixed inset-0 z-[9999]">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeAddPanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-lg bg-white shadow-2xl flex flex-col z-10">

                {{-- Header --}}
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-900">Add Student</p>
                            <p class="text-xs text-gray-400">Select a school and fill in the details</p>
                        </div>
                    </div>
                    <button wire:click="closeAddPanel"
                        class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Scrollable Body --}}
                <div class="flex-1 overflow-y-auto p-5 space-y-4">

                    {{-- Organisation --}}
                    <div class="bg-indigo-50 rounded-xl p-4 space-y-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">School</p>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Select School <span class="text-red-500">*</span></label>
                            <select wire:model.live="addOrgId"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white @error('addOrgId') border-red-400 @enderror">
                                <option value="">-- Select School --</option>
                                @foreach ($organizations as $org)
                                    <option value="{{ $org->id }}">{{ $org->name }}</option>
                                @endforeach
                            </select>
                            @error('addOrgId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Profile photo (admin-style) --}}
                    <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Profile Photo <span class="normal-case font-normal text-gray-400">(optional, max 1 MB)</span></p>
                        <div class="flex items-center gap-3">
                            @if ($addImage)
                                <img src="{{ $addImage->temporaryUrl() }}" class="w-12 h-12 rounded-full object-cover border border-gray-200 flex-shrink-0">
                            @else
                                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                            @endif
                            <input type="file" wire:model="addImage" accept="image/*" class="flex-1 text-sm">
                        </div>
                        <div wire:loading wire:target="addImage" class="text-xs text-blue-600">Uploading…</div>
                        @error('addImage')<p class="text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Basic Info --}}
                    <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Basic Information</p>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Full Name <span class="text-red-500">*</span></label>
                            <input wire:model.defer="addName" type="text" maxlength="50" placeholder="Student's full name"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('addName') border-red-400 @enderror" />
                            @error('addName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Email <span class="text-red-500">*</span></label>
                                <input wire:model.defer="addEmail" type="email" placeholder="student@email.com"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('addEmail') border-red-400 @enderror" />
                                @error('addEmail') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Mobile <span class="text-red-500">*</span></label>
                                <input wire:model.defer="addMobile" type="tel" maxlength="10" inputmode="numeric"
                                    oninput="this.value=this.value.replace(/\D/g,'').slice(0,10)" placeholder="10-digit"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('addMobile') border-red-400 @enderror" />
                                @error('addMobile') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Gender <span class="text-red-500">*</span></label>
                                <select wire:model.defer="addGender"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white @error('addGender') border-red-400 @enderror">
                                    <option value="">Select</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                                @error('addGender') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Date of Birth <span class="text-red-500">*</span></label>
                                <input wire:model.defer="addDob" type="date"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('addDob') border-red-400 @enderror" />
                                @error('addDob') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Religion</label>
                            <input wire:model.defer="addReligion" type="text" placeholder="e.g. Hindu"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
                        </div>
                    </div>

                    {{-- Family --}}
                    <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Family Details</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Father's Name <span class="text-red-500">*</span></label>
                                <input wire:model.defer="addFatherName" type="text" placeholder="Father's name"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('addFatherName') border-red-400 @enderror" />
                                @error('addFatherName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Mother's Name <span class="text-gray-400 font-normal">(optional)</span></label>
                                <input wire:model.defer="addMotherName" type="text" maxlength="50" placeholder="Mother's name"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('addMotherName') border-red-400 @enderror" />
                                @error('addMotherName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Academic --}}
                    <div class="bg-blue-50 rounded-xl p-4 space-y-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Academic Details</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Class <span class="text-red-500">*</span></label>
                                <select wire:model.live="addStandardId" @disabled(!$addOrgId)
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white disabled:opacity-50 @error('addStandardId') border-red-400 @enderror">
                                    <option value="">Select class</option>
                                    @foreach ($addStandards as $std)
                                        <option value="{{ $std->id }}">{{ $std->name }}</option>
                                    @endforeach
                                </select>
                                @error('addStandardId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Section <span class="text-red-500">*</span></label>
                                <select wire:model.defer="addSectionId" @disabled(!$addStandardId)
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white disabled:opacity-50 @error('addSectionId') border-red-400 @enderror">
                                    <option value="">Select section</option>
                                    @foreach ($addSections as $sec)
                                        <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                                    @endforeach
                                </select>
                                @error('addSectionId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Board <span class="text-gray-400 font-normal">(auto from class)</span></label>
                                <input value="{{ $addBoard ?: '—' }}" type="text" readonly
                                    class="w-full px-3 py-2 text-sm border border-gray-200 bg-gray-100 text-gray-500 rounded-lg cursor-not-allowed" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Date of Admission <span class="text-gray-400 font-normal">(optional)</span></label>
                                <input wire:model.defer="addDateOfAdmission" type="date" max="{{ now()->format('Y-m-d') }}"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('addDateOfAdmission') border-red-400 @enderror" />
                                @error('addDateOfAdmission') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Aadhar No</label>
                                <input wire:model.defer="addAadharNo" type="text" maxlength="12" inputmode="numeric"
                                    oninput="this.value=this.value.replace(/\D/g,'').slice(0,12)" placeholder="12-digit"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('addAadharNo') border-red-400 @enderror" />
                                @error('addAadharNo') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Appar ID</label>
                                <input wire:model.defer="addApparId" type="text" placeholder="Optional"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Registration Number</label>
                            <input wire:model.defer="addRegNo" type="text" placeholder="Optional"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Transport Required? <span class="text-red-500">*</span></label>
                                <select wire:model.live="addTransportation"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                            @if ($addTransportation)
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Select Route <span class="text-red-500">*</span></label>
                                    <select wire:model.live="addRoute"
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white @error('addRoute') border-red-400 @enderror">
                                        <option value="">Select route</option>
                                        @foreach ($addRouteOptions as $route)
                                            <option value="{{ $route->id }}">{{ $route->route_name }} — ₹{{ number_format($route->monthly_fee, 0) }}/mo</option>
                                        @endforeach
                                    </select>
                                    @error('addRoute') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                    @if (count($addRouteOptions) === 0)
                                        <p class="text-amber-600 text-xs mt-1">No active routes for this school.</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                        @if ($addTransportation)
                            @php $addChosenRoute = collect($addRouteOptions)->firstWhere('id', (int) $addRoute); @endphp
                            @if ($addChosenRoute)
                                <div class="bg-blue-50 border border-blue-100 rounded-lg px-3 py-2 flex items-center justify-between">
                                    <span class="text-xs text-gray-600">Transport Fee</span>
                                    <span class="text-sm font-semibold text-blue-700">
                                        ₹{{ number_format($addChosenRoute->monthly_fee, 0) }}/mo · ₹{{ number_format($addChosenRoute->monthly_fee * 11, 0) }}/yr
                                    </span>
                                </div>
                            @endif
                        @endif
                    </div>

                    {{-- Address --}}
                    <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Address</p>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Local Address</label>
                            <textarea wire:model.defer="addLocalAddress" rows="2" placeholder="Local address"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Permanent Address</label>
                            <textarea wire:model.defer="addPermanentAddress" rows="2" placeholder="Permanent address"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">State</label>
                                <select wire:model.live="addState"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white">
                                    <option value="">Select state</option>
                                    @foreach ($addStates as $state)
                                        <option value="{{ $state }}">{{ $state }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">City</label>
                                <select wire:model.defer="addCity" @disabled(!$addState)
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white disabled:opacity-50">
                                    <option value="">Select city</option>
                                    @foreach ($addCities as $city)
                                        <option value="{{ $city }}">{{ $city }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Pincode</label>
                            <input wire:model.defer="addPincode" type="text" maxlength="6" inputmode="numeric"
                                oninput="this.value=this.value.replace(/\D/g,'').slice(0,6)" placeholder="6-digit pincode"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('addPincode') border-red-400 @enderror" />
                            @error('addPincode') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Active toggle (admin-style) --}}
                    <label class="inline-flex items-center gap-2 cursor-pointer px-1">
                        <input type="checkbox" wire:model.defer="addActive" class="rounded">
                        <span class="text-sm text-gray-700">Active (can log in)</span>
                    </label>

                    {{-- Email note --}}
                    <div class="flex items-start gap-2 bg-amber-50 border border-amber-100 rounded-xl px-4 py-3">
                        <svg class="w-4 h-4 text-amber-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-xs text-amber-700">A random password will be auto-generated and sent to the student's email.</p>
                    </div>

                </div>

                {{-- Footer --}}
                <div class="flex-shrink-0 px-5 py-4 border-t border-gray-100 flex items-center gap-2 bg-gray-50/50">
                    <button wire:click="saveNewStudent"
                        class="flex-1 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold
                               rounded-lg transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Student
                    </button>
                    <button wire:click="closeAddPanel"
                        class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg transition-colors">
                        Cancel
                    </button>
                </div>

            </div>
        </div>
    @endif

    {{-- ══════════ VIEW SLIDE-IN PANEL ══════════ --}}
    @if ($showViewModal && !empty($viewData))
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeViewModal"></div>
            <div class="absolute top-0 right-0 bottom-0 z-10 w-full max-w-lg bg-white shadow-2xl flex flex-col overflow-hidden">
                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <h2 class="text-base font-bold text-gray-900">Student Details</h2>
                    <button wire:click="closeViewModal" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                {{-- Body --}}
                <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5 text-sm text-gray-700">
                    {{-- Profile Header --}}
                    <div class="flex items-center gap-4 pb-4 border-b border-gray-200">
                        @if ($studentImageUrl)
                            <img src="{{ $studentImageUrl }}" class="w-16 h-16 rounded-full object-cover border border-gray-200">
                        @else
                            <div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center">
                                <span class="text-xl font-bold text-indigo-600">{{ strtoupper(substr($viewData['user']?->name ?? 'S', 0, 1)) }}</span>
                            </div>
                        @endif
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $viewData['user']?->name ?? '—' }}</h3>
                            <p class="text-sm text-gray-500">{{ $viewData['detail']?->admission_no ?? '—' }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $viewData['user']?->organization?->name ?? '—' }}</p>
                            @if ($viewData['user']?->is_active)
                                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 mt-1 bg-green-50 text-green-700 rounded-full font-medium border border-green-100">
                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 mt-1 bg-red-50 text-red-600 rounded-full font-medium border border-red-100">
                                    <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span> Inactive
                                </span>
                            @endif
                        </div>
                    </div>
                    {{-- Personal --}}
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Personal Information</h4>
                        <dl class="grid grid-cols-2 gap-3">
                            <div><dt class="text-xs text-gray-400">Email</dt><dd class="font-medium text-xs break-all">{{ $viewData['user']?->email ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Mobile</dt><dd class="font-medium">{{ $viewData['user']?->mobile_number ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Gender</dt><dd class="font-medium capitalize">{{ $viewData['detail']?->gender ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Date of Birth</dt><dd class="font-medium">{{ $viewData['detail']?->dob?->format('d M Y') ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Father's Name</dt><dd class="font-medium">{{ $viewData['detail']?->father_name ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Mother's Name</dt><dd class="font-medium">{{ $viewData['detail']?->mother_name ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Religion</dt><dd class="font-medium">{{ $viewData['detail']?->religion ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Aadhar No</dt><dd class="font-medium font-mono">{{ $viewData['detail']?->aadhar_no ?? '—' }}</dd></div>
                        </dl>
                    </div>
                    {{-- Academic --}}
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Academic Information</h4>
                        <dl class="grid grid-cols-2 gap-3">
                            <div><dt class="text-xs text-gray-400">Class</dt><dd class="font-medium">{{ $viewData['standard']?->name ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Section</dt><dd class="font-medium">{{ $viewData['section']?->name ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Roll No</dt><dd class="font-medium font-mono">{{ $viewData['detail']?->roll_no ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Admission Date</dt><dd class="font-medium">{{ $viewData['detail']?->date_of_admission?->format('d M Y') ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Board</dt><dd class="font-medium">{{ $viewData['detail']?->board ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Transportation</dt><dd class="font-medium {{ $viewData['detail']?->transportation_required ? 'text-green-700' : 'text-gray-500' }}">{{ $viewData['detail']?->transportation_required ? 'Required' : 'Not Required' }}</dd></div>
                            @php $svRoute = $viewData['detail']?->transportations?->first(); @endphp
                            @if ($svRoute)
                                <div><dt class="text-xs text-gray-400">Transport Route</dt><dd class="font-medium">{{ $svRoute->route_name }}</dd></div>
                                <div><dt class="text-xs text-gray-400">Transport Fee</dt><dd class="font-medium text-blue-700">₹{{ number_format($svRoute->monthly_fee, 0) }}/mo · ₹{{ number_format($svRoute->monthly_fee * 11, 0) }}/yr</dd></div>
                            @endif
                        </dl>
                    </div>
                    {{-- Address --}}
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Address</h4>
                        <dl class="grid grid-cols-1 gap-2">
                            <div><dt class="text-xs text-gray-400">Local Address</dt><dd class="font-medium">{{ $viewData['detail']?->local_address ?? '—' }}</dd></div>
                            @if ($viewData['detail']?->permanent_address)
                                <div><dt class="text-xs text-gray-400">Permanent Address</dt><dd class="font-medium">{{ $viewData['detail']->permanent_address }}</dd></div>
                            @endif
                            <div class="grid grid-cols-3 gap-3">
                                <div><dt class="text-xs text-gray-400">City</dt><dd class="font-medium">{{ $viewData['detail']?->city ?? '—' }}</dd></div>
                                <div><dt class="text-xs text-gray-400">State</dt><dd class="font-medium">{{ $viewData['detail']?->state ?? '—' }}</dd></div>
                                <div><dt class="text-xs text-gray-400">Pincode</dt><dd class="font-medium">{{ $viewData['detail']?->pincode ?? '—' }}</dd></div>
                            </div>
                        </dl>
                    </div>
                    @if ($viewData['detail']?->appar_id || $viewData['detail']?->registration_number)
                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Additional</h4>
                            <dl class="grid grid-cols-2 gap-3">
                                @if ($viewData['detail']?->appar_id)
                                    <div><dt class="text-xs text-gray-400">Appar ID</dt><dd class="font-medium">{{ $viewData['detail']->appar_id }}</dd></div>
                                @endif
                                @if ($viewData['detail']?->registration_number)
                                    <div><dt class="text-xs text-gray-400">Registration No</dt><dd class="font-medium">{{ $viewData['detail']->registration_number }}</dd></div>
                                @endif
                            </dl>
                        </div>
                    @endif
                </div>
                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50 flex-shrink-0">
                    <button wire:click="closeViewModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Close</button>
                    <button wire:click="openEditPanel({{ $viewData['detail']?->id }}); closeViewModal()"
                        class="px-4 py-2 text-sm font-semibold text-white bg-amber-500 hover:bg-amber-600 rounded-lg transition-colors">
                        Edit Student
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ EDIT STUDENT SLIDE-IN PANEL ══════════ --}}
    @if ($showEditPanel)
        <div class="fixed inset-0 z-[9999]">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeEditPanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-lg bg-white shadow-2xl flex flex-col z-10">
                {{-- Header --}}
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-amber-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-gray-900">Edit Student</h2>
                            <p class="text-xs text-gray-400">Update student information</p>
                        </div>
                    </div>
                    <button wire:click="closeEditPanel" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                {{-- Body --}}
                <div class="flex-1 overflow-y-auto px-5 py-4 space-y-5">

                    {{-- School --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">School</p>
                        <select wire:model.live="editOrgId" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-white">
                            <option value="">— Select School —</option>
                            @foreach ($organizations as $org)
                                <option value="{{ $org->id }}">{{ $org->name }}</option>
                            @endforeach
                        </select>
                        @error('editOrgId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Profile photo (admin-style) --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Profile Photo <span class="normal-case font-normal text-gray-400">(optional, max 1 MB)</span></p>
                        <div class="flex items-center gap-3">
                            @if ($editImage)
                                <img src="{{ $editImage->temporaryUrl() }}" class="w-12 h-12 rounded-full object-cover border border-gray-200 flex-shrink-0">
                            @elseif ($editImageUrl)
                                <img src="{{ $editImageUrl }}" class="w-12 h-12 rounded-full object-cover border border-gray-200 flex-shrink-0">
                            @else
                                <div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                            @endif
                            <input type="file" wire:model="editImage" accept="image/*" class="flex-1 text-sm">
                        </div>
                        <div wire:loading wire:target="editImage" class="text-xs text-amber-600 mt-1">Uploading…</div>
                        @error('editImage')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Basic Info --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Basic Info</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Full Name <span class="text-red-500">*</span></label>
                                <input wire:model.defer="editName" type="text" maxlength="50" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"/>
                                @error('editName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Email <span class="text-red-500">*</span></label>
                                <input wire:model.defer="editEmail" type="email" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"/>
                                @error('editEmail') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Mobile <span class="text-red-500">*</span></label>
                                <input wire:model.defer="editMobile" type="text" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"/>
                                @error('editMobile') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Date of Birth <span class="text-red-500">*</span></label>
                                <input wire:model.defer="editDob" type="date" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"/>
                                @error('editDob') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Gender <span class="text-red-500">*</span></label>
                                <select wire:model.defer="editGender" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 bg-white">
                                    <option value="">— Select —</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                                @error('editGender') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Father's Name <span class="text-red-500">*</span></label>
                                <input wire:model.defer="editFatherName" type="text" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"/>
                                @error('editFatherName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Mother's Name <span class="text-gray-400 font-normal">(optional)</span></label>
                                <input wire:model.defer="editMotherName" type="text" maxlength="50" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"/>
                                @error('editMotherName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Religion</label>
                                <input wire:model.defer="editReligion" type="text" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"/>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Aadhar No</label>
                                <input wire:model.defer="editAadharNo" type="text" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 font-mono"/>
                                @error('editAadharNo') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Academic --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Academic Details</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Board <span class="text-gray-400 font-normal">(auto from class)</span></label>
                                <input value="{{ $editBoard ?: '—' }}" type="text" readonly
                                    class="w-full px-3 py-2 text-sm border border-gray-200 bg-gray-100 text-gray-500 rounded-lg cursor-not-allowed"/>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Date of Admission <span class="text-gray-400 font-normal">(optional)</span></label>
                                <input wire:model.defer="editDateOfAdmission" type="date" max="{{ now()->format('Y-m-d') }}" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"/>
                                @error('editDateOfAdmission') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Class <span class="text-red-500">*</span></label>
                                <select wire:model.live="editStandardId" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 bg-white {{ empty($editStandards) ? 'opacity-50' : '' }} @error('editStandardId') border-red-400 @enderror">
                                    <option value="">— Select Class —</option>
                                    @foreach ($editStandards as $std)
                                        <option value="{{ $std->id }}">{{ $std->name }}</option>
                                    @endforeach
                                </select>
                                @error('editStandardId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Section <span class="text-red-500">*</span></label>
                                <select wire:model.defer="editSectionId" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 bg-white {{ empty($editSections) ? 'opacity-50' : '' }} @error('editSectionId') border-red-400 @enderror">
                                    <option value="">— Select Section —</option>
                                    @foreach ($editSections as $sec)
                                        <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                                    @endforeach
                                </select>
                                @error('editSectionId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Appar ID</label>
                                <input wire:model.defer="editApparId" type="text" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 font-mono"/>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Registration No</label>
                                <input wire:model.defer="editRegNo" type="text" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 font-mono"/>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Transport Required? <span class="text-red-500">*</span></label>
                                <select wire:model.live="editTransportation" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 bg-white">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                            @if ($editTransportation)
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Select Route <span class="text-red-500">*</span></label>
                                    <select wire:model.live="editRoute" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 bg-white @error('editRoute') border-red-400 @enderror">
                                        <option value="">Select route</option>
                                        @foreach ($editRouteOptions as $route)
                                            <option value="{{ $route->id }}">{{ $route->route_name }} — ₹{{ number_format($route->monthly_fee, 0) }}/mo</option>
                                        @endforeach
                                    </select>
                                    @error('editRoute') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    @if (count($editRouteOptions) === 0)
                                        <p class="text-amber-600 text-xs mt-1">No active routes for this school.</p>
                                    @endif
                                </div>
                            @endif
                            @if ($editTransportation)
                                @php $editChosenRoute = collect($editRouteOptions)->firstWhere('id', (int) $editRoute); @endphp
                                @if ($editChosenRoute)
                                    <div class="col-span-2 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2 flex items-center justify-between">
                                        <span class="text-xs text-gray-600">Transport Fee</span>
                                        <span class="text-sm font-semibold text-amber-700">
                                            ₹{{ number_format($editChosenRoute->monthly_fee, 0) }}/mo · ₹{{ number_format($editChosenRoute->monthly_fee * 11, 0) }}/yr
                                        </span>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>

                    {{-- Address --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Address</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Local Address</label>
                                <textarea wire:model.defer="editLocalAddress" rows="2" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 resize-none"></textarea>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Permanent Address</label>
                                <textarea wire:model.defer="editPermanentAddress" rows="2" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 resize-none"></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">State</label>
                                <select wire:model.live="editState" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 bg-white">
                                    <option value="">— State —</option>
                                    @foreach ($editStates as $st)
                                        <option value="{{ $st }}">{{ $st }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">City</label>
                                <select wire:model.defer="editCity" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 bg-white {{ empty($editCities) ? 'opacity-50' : '' }}">
                                    <option value="">— City —</option>
                                    @foreach ($editCities as $city)
                                        <option value="{{ $city }}">{{ $city }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Pincode</label>
                                <input wire:model.defer="editPincode" type="text" maxlength="6" inputmode="numeric"
                                    oninput="this.value=this.value.replace(/\D/g,'').slice(0,6)"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 font-mono"/>
                                @error('editPincode') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Active toggle (admin-style) --}}
                    <label class="inline-flex items-center gap-2 cursor-pointer px-1">
                        <input type="checkbox" wire:model.defer="editActive" class="rounded">
                        <span class="text-sm text-gray-700">Active (can log in)</span>
                    </label>
                </div>
                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 px-5 py-4 border-t border-gray-100 bg-gray-50 flex-shrink-0">
                    <button wire:click="closeEditPanel" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
                    <button wire:click="saveEditStudent" type="button"
                        wire:loading.attr="disabled" wire:target="saveEditStudent"
                        class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition-colors disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveEditStudent">Save Changes</span>
                        <span wire:loading wire:target="saveEditStudent" class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            Saving...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ DELETE CONFIRMATION ══════════ --}}
    @if ($showDeleteConfirm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" wire:click="cancelDelete"></div>
            <div class="relative z-10 bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex flex-col items-center text-center gap-3">
                    <div class="w-14 h-14 bg-red-50 rounded-full flex items-center justify-center">
                        <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Delete Student?</h3>
                        <p class="text-sm text-gray-500 mt-1">This will permanently delete the student and their account. This action cannot be undone.</p>
                    </div>
                    <div class="flex items-center gap-3 w-full mt-1">
                        <button wire:click="cancelDelete" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">Cancel</button>
                        <button wire:click="doDeleteStudent({{ $deleteTargetId }})"
                            wire:loading.attr="disabled" wire:target="doDeleteStudent"
                            class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-colors disabled:opacity-60">
                            <span wire:loading.remove wire:target="doDeleteStudent">Yes, Delete</span>
                            <span wire:loading wire:target="doDeleteStudent">Deleting...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
