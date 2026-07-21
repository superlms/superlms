<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════
         HEADER (sticky) + INLINE FILTER STRIP
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="px-4 sm:px-6 py-3 sm:py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-lg sm:text-2xl font-bold text-gray-900">Teachers</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Manage teacher records and assignments</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                        <span class="pr-4">Total: <strong class="text-gray-800">{{ $totalTeachers }}</strong></span>
                        <span class="px-4">Active: <strong class="text-emerald-600">{{ $activeTeachers }}</strong></span>
                        <span class="px-4">Inactive: <strong class="text-red-500">{{ $inactiveTeachers }}</strong></span>
                        <span class="pl-4">This Year: <strong class="text-blue-600">{{ $thisYearJoining }}</strong></span>
                    </div>
                    <button wire:click="exportTeachers"
                        class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-gray-100 hover:bg-gray-200
                               text-gray-700 text-sm font-semibold rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span class="hidden sm:inline">Export</span>
                    </button>
                    <button wire:click="onAddTeacher"
                        class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700
                               text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span class="hidden sm:inline">Add Teacher</span>
                    </button>
                </div>
            </div>

            {{-- Mobile stats --}}
            <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Total: <strong class="text-gray-800">{{ $totalTeachers }}</strong></span>
                <span>Active: <strong class="text-emerald-600">{{ $activeTeachers }}</strong></span>
                <span>Inactive: <strong class="text-red-500">{{ $inactiveTeachers }}</strong></span>
                <span>This Year: <strong class="text-blue-600">{{ $thisYearJoining }}</strong></span>
            </div>
        </div>

        {{-- Thin attached filter strip (student / exams style) --}}
        <div class="bg-gray-50 border-t border-gray-200 px-4 sm:px-6 py-2.5">
            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                <div class="flex items-center gap-1.5 text-xs font-medium text-gray-500 mr-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter by:
                </div>

                <div class="relative flex-1 min-w-[200px] max-w-xs">
                    <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input wire:model.live.debounce.300ms="search" type="text"
                        placeholder="Search name, email, ID, phone..."
                        class="w-full pl-8 pr-3 py-1.5 text-xs border border-gray-300 rounded-md
                               focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white" />
                </div>

                <select wire:model.live="filterClass"
                    class="px-2.5 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 bg-white">
                    <option value="">All Classes</option>
                    @foreach ($standards as $standard)
                        <option value="{{ $standard->id }}">{{ $standard->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filterSection"
                    class="px-2.5 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 bg-white disabled:opacity-50 disabled:cursor-not-allowed"
                    @disabled(!$filterClass)>
                    <option value="">All Sections</option>
                    @foreach ($filterSections as $section)
                        <option value="{{ $section->id }}">{{ $section->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filterGender"
                    class="px-2.5 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 bg-white">
                    <option value="">All Genders</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>

                <select wire:model.live="filterStatus"
                    class="px-2.5 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 bg-white">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>

                @if ($search || $filterGender || $filterStatus !== '' || $filterClass || $filterSection)
                    <button wire:click="clearFilters" title="Clear all filters"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs text-gray-500 border border-gray-300
                               rounded-md hover:bg-white transition-colors bg-white">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 space-y-4 sm:space-y-5">

        {{-- ══════════════════════════════════════════════════
             DESKTOP TABLE
        ══════════════════════════════════════════════════ --}}
        <div class="hidden md:block bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full table-fixed">
                    <colgroup>
                        <col class="w-12">
                        <col class="w-[24%]">
                        <col class="w-[12%]">
                        <col class="w-[22%]">
                        <col class="w-[16%]">
                        <col class="w-[12%]">
                        <col class="w-[8%]">
                        <col class="w-[6%]">
                    </colgroup>
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                S.No</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Teacher</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Mobile</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Email</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Joining Date</th>
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($teachers as $index => $teacher)
                            <tr class="hover:bg-gray-50/70 transition-colors">
                                <td class="px-4 py-3">
                                    <span
                                        class="text-sm text-gray-500 font-medium">{{ $teachers->firstItem() + $index }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        @if ($teacher->user?->image)
                                            <img src="{{ $teacher->user->image }}"
                                                class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0 cursor-pointer"
                                                wire:click="onImageClick({{ $teacher->user->id }})">
                                        @else
                                            <div
                                                class="w-9 h-9 rounded-full bg-teal-100 flex items-center justify-center flex-shrink-0">
                                                <span
                                                    class="text-xs font-semibold text-teal-600">{{ strtoupper(substr($teacher->user?->name ?? 'T', 0, 1)) }}</span>
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 truncate"
                                                title="{{ $teacher->user?->name ?? '' }}">
                                                {{ $teacher->user?->name ?? '—' }}</p>
                                            <p class="text-xs text-gray-400 capitalize truncate">
                                                {{ $teacher->user?->gender ?? '' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-700 truncate block"
                                        title="{{ $teacher->phone ?? '' }}">{{ $teacher->phone ?? '—' }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-600 truncate block"
                                        title="{{ $teacher->user?->email ?? '' }}">{{ $teacher->user?->email ?? '—' }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span
                                        class="text-sm text-gray-700">{{ $teacher->date_of_joining ? \Carbon\Carbon::parse($teacher->date_of_joining)->format('d M Y') : '—' }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <span
                                            class="w-2 h-2 rounded-full flex-shrink-0 mr-1 {{ $teacher->user?->is_active ? 'bg-green-500' : 'bg-red-500' }}"
                                            title="{{ $teacher->user?->is_active ? 'Active' : 'Inactive' }}"></span>
                                        <button wire:click="onViewTeacherAdmin({{ $teacher->id }})"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                            title="View">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <button wire:click="onEditTeacher({{ $teacher->id }})"
                                            class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"
                                            title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button wire:click="onDeleteTeacher({{ $teacher->id }})"
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
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div
                                        class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 text-sm">No teachers found</p>
                                    @if ($search || $filterGender || $filterStatus !== '' || $filterClass || $filterSection)
                                        <button wire:click="clearFilters"
                                            class="mt-3 text-sm text-blue-600 hover:text-blue-800 font-medium">Clear
                                            filters</button>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($teachers->hasPages())
                <div
                    class="px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p class="text-sm text-gray-500">
                        Showing <span class="font-medium text-gray-700">{{ $teachers->firstItem() }}</span>
                        to <span class="font-medium text-gray-700">{{ $teachers->lastItem() }}</span>
                        of <span class="font-medium text-gray-700">{{ $teachers->total() }}</span>
                    </p>
                    <div class="flex items-center gap-1">
                        @if ($teachers->onFirstPage())
                            <span
                                class="px-3 py-1.5 text-sm text-gray-300 border border-gray-200 rounded-lg cursor-not-allowed">&laquo;
                                Prev</span>
                        @else
                            <button wire:click="previousPage"
                                class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">&laquo;
                                Prev</button>
                        @endif
                        @foreach ($teachers->getUrlRange(max(1, $teachers->currentPage() - 2), min($teachers->lastPage(), $teachers->currentPage() + 2)) as $page => $url)
                            <button wire:click="gotoPage({{ $page }})"
                                class="px-3 py-1.5 text-sm rounded-lg {{ $page == $teachers->currentPage() ? 'bg-blue-600 text-white border border-blue-600' : 'text-gray-600 border border-gray-300 hover:bg-gray-50' }}">{{ $page }}</button>
                        @endforeach
                        @if ($teachers->hasMorePages())
                            <button wire:click="nextPage"
                                class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Next
                                &raquo;</button>
                        @else
                            <span
                                class="px-3 py-1.5 text-sm text-gray-300 border border-gray-200 rounded-lg cursor-not-allowed">Next
                                &raquo;</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- ══════════════════════════════════════════════════
             MOBILE CARDS
        ══════════════════════════════════════════════════ --}}
        <div class="md:hidden space-y-3">
            @forelse ($teachers as $index => $teacher)
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="flex items-center gap-3 p-4 border-b border-gray-100">
                        <span
                            class="text-xs font-bold text-gray-400 w-6 text-center">{{ $teachers->firstItem() + $index }}</span>
                        @if ($teacher->user?->image)
                            <img src="{{ $teacher->user->image }}"
                                class="w-10 h-10 rounded-full object-cover border border-gray-200 flex-shrink-0">
                        @else
                            <div
                                class="w-10 h-10 rounded-full bg-teal-100 flex items-center justify-center flex-shrink-0">
                                <span
                                    class="text-sm font-semibold text-teal-600">{{ strtoupper(substr($teacher->user?->name ?? 'T', 0, 1)) }}</span>
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $teacher->user?->name ?? '—' }}
                            </p>
                            <p class="text-xs text-gray-400">{{ $teacher->user?->email ?? '—' }}</p>
                        </div>
                        @if ($teacher->user?->is_active)
                            <span
                                class="inline-flex items-center gap-1 text-xs px-2 py-0.5 bg-green-50 text-green-700 rounded-full font-medium border border-green-100">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Active
                            </span>
                        @else
                            <span
                                class="inline-flex items-center gap-1 text-xs px-2 py-0.5 bg-red-50 text-red-600 rounded-full font-medium border border-red-100">
                                <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span> Inactive
                            </span>
                        @endif
                    </div>
                    <div class="px-4 py-3 space-y-2">
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div class="min-w-0">
                                <p class="text-xs text-gray-400">Mobile</p>
                                <p class="text-gray-700 font-medium truncate">{{ $teacher->phone ?? '—' }}</p>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs text-gray-400">Joining Date</p>
                                <p class="text-gray-700 font-medium truncate">
                                    {{ $teacher->date_of_joining ? \Carbon\Carbon::parse($teacher->date_of_joining)->format('d M Y') : '—' }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center border-t border-gray-100 divide-x divide-gray-100">
                        <button wire:click="onViewTeacherAdmin({{ $teacher->id }})"
                            class="flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-medium text-blue-600 hover:bg-blue-50">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg> View
                        </button>
                        <button wire:click="onEditTeacher({{ $teacher->id }})"
                            class="flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-medium text-amber-600 hover:bg-amber-50">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg> Edit
                        </button>
                        <button wire:click="onDeleteTeacher({{ $teacher->id }})"
                            class="flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-medium text-red-600 hover:bg-red-50">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg> Delete
                        </button>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 text-center">
                    <p class="text-gray-500 text-sm">No teachers found</p>
                    @if ($search || $filterGender || $filterStatus !== '' || $filterClass || $filterSection)
                        <button wire:click="clearFilters"
                            class="mt-3 text-sm text-blue-600 hover:text-blue-800 font-medium">Clear filters</button>
                    @endif
                </div>
            @endforelse

            @if ($teachers->hasPages())
                <div
                    class="flex items-center justify-between bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3">
                    <p class="text-xs text-gray-500">{{ $teachers->firstItem() }}–{{ $teachers->lastItem() }} of
                        {{ $teachers->total() }}</p>
                    <div class="flex items-center gap-1">
                        @if (!$teachers->onFirstPage())
                            <button wire:click="previousPage"
                                class="px-2.5 py-1 text-xs text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Prev</button>
                        @endif
                        <span
                            class="px-2.5 py-1 text-xs bg-blue-600 text-white rounded-lg">{{ $teachers->currentPage() }}</span>
                        @if ($teachers->hasMorePages())
                            <button wire:click="nextPage"
                                class="px-2.5 py-1 text-xs text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Next</button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         ADD / EDIT TEACHER SLIDE-IN PANEL (Exams style)
    ══════════════════════════════════════════════════ --}}
    @if ($open)
        <div class="fixed inset-0 z-[9999] overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-2xl bg-white shadow-2xl flex flex-col">

                {{-- Fixed header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editId ? 'Edit Teacher' : 'New Teacher' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $editId ? 'Update teacher details' : 'Welcome email with login credentials will be sent on save' }}</p>
                    </div>
                    <button wire:click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- Scrollable body --}}
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">

                    {{-- Profile image --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Teacher Profile Image <span class="text-gray-400 font-normal">(Optional, max 1 MB)</span></label>
                        @if ($editId && !$teacherImage)
                            @php $user = \App\Models\User::find($editId) @endphp
                            @if ($user?->image)
                                <div class="flex items-center gap-3 mb-2 border border-gray-200 rounded-md p-3">
                                    <img src="{{ $user->image }}" class="h-14 w-14 rounded-full object-cover border border-gray-200">
                                    <div class="flex-1">
                                        <p class="text-sm text-gray-700">Current photo</p>
                                        <button wire:click="$set('teacherImage', null)" type="button" class="text-xs text-red-600 hover:text-red-700">Remove</button>
                                    </div>
                                </div>
                            @endif
                        @endif
                        <input type="file" wire:model="teacherImage" accept="image/*" class="w-full text-sm">
                        <div wire:loading wire:target="teacherImage" class="text-xs text-blue-600 mt-1">Uploading...</div>
                        @error('teacherImage')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Personal info grid --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name <span class="text-red-500">*</span></label>
                            <input wire:model.defer="teacherName" type="text" maxlength="50"
                                oninput="this.value=this.value.replace(/[^A-Za-z ]/g,'')"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('teacherName')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Email <span class="text-red-500">*</span></label>
                            <input wire:model.defer="teacherEmail" type="email" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('teacherEmail')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Mobile <span class="text-red-500">*</span></label>
                            <input wire:model.defer="teacherMobile" type="tel" maxlength="10" inputmode="numeric"
                                oninput="this.value=this.value.replace(/\D/g,'').slice(0,10)"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                            @error('teacherMobile')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Employee ID <span class="text-red-500">*</span></label>
                            <input wire:model.defer="employeeId" type="text" maxlength="20" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                            @error('employeeId')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Date of Birth <span class="text-red-500">*</span></label>
                            <input wire:model.defer="dob" type="date" min="1940-01-01" max="{{ now()->subYear()->format('Y-m-d') }}" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                            @error('dob')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Date of Joining <span class="text-red-500">*</span></label>
                            <input wire:model.defer="dateOfJoining" type="date" min="1970-01-01" max="{{ now()->format('Y-m-d') }}" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                            @error('dateOfJoining')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Gender <span class="text-red-500">*</span></label>
                            <select wire:model.defer="teacherGender" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                            @error('teacherGender')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Qualification <span class="text-red-500">*</span></label>
                            <input wire:model.defer="qualification" type="text" maxlength="50" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                            @error('qualification')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Emergency Contact <span class="text-red-500">*</span></label>
                            <input wire:model.defer="emergencyContact" type="tel" maxlength="10" inputmode="numeric"
                                oninput="this.value=this.value.replace(/\D/g,'').slice(0,10)"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                            @error('emergencyContact')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Pincode <span class="text-red-500">*</span></label>
                            <input wire:model.defer="pincode" type="text" maxlength="6" inputmode="numeric"
                                oninput="this.value=this.value.replace(/\D/g,'').slice(0,6)"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                            @error('pincode')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">State</label>
                            <select wire:model.live="selectedState" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                                <option value="">Select State</option>
                                @foreach ($states as $state)
                                    <option value="{{ $state }}">{{ $state }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">City</label>
                            <select wire:model.live="selectedCity" @disabled(empty($cities))
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm disabled:opacity-50">
                                <option value="">Select City</option>
                                @foreach ($cities as $city)
                                    @php $cityName = is_array($city) ? ($city['name'] ?? '') : $city; @endphp
                                    @if ($cityName !== '')
                                        <option value="{{ $cityName }}">{{ $cityName }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Address --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Address <span class="text-red-500">*</span></label>
                        <textarea wire:model.defer="address" rows="3" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm resize-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        @error('address')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Active toggle --}}
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model.defer="teacherActive" class="rounded">
                        <span class="text-sm text-gray-700">Active (can log in)</span>
                    </label>
                </div>

                {{-- Fixed footer --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeModal" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md order-3 sm:order-1">Cancel</button>
                    <button wire:click="onSave" wire:loading.attr="disabled" wire:target="onSave"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60 flex items-center justify-center gap-1.5 order-1 sm:order-3">
                        <span wire:loading.remove wire:target="onSave">{{ $editId ? 'Update Teacher' : 'Create Teacher' }}</span>
                        <span wire:loading wire:target="onSave">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         VIEW TEACHER SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($showViewModal && !empty($viewData))
        <div class="fixed inset-0 z-[9999] overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeViewModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-2xl bg-white shadow-2xl flex flex-col">

                {{-- Fixed header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $viewModalTitle ?: 'Teacher Details' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $viewData['user']->email ?? '' }}</p>
                    </div>
                    <button wire:click="closeViewModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- Scrollable body --}}
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5 text-left">

                    {{-- Profile Header --}}
                    <div class="flex items-center gap-4 pb-4 border-b border-gray-200">
                        @if ($teacherImageUrl)
                            <img src="{{ $teacherImageUrl }}"
                                class="w-16 h-16 rounded-full object-cover border-2 border-gray-200">
                        @else
                            <div class="w-16 h-16 rounded-full bg-teal-100 flex items-center justify-center">
                                <span class="text-xl font-bold text-teal-600">{{ strtoupper(substr($viewData['user']->name ?? 'T', 0, 1)) }}</span>
                            </div>
                        @endif
                        <div class="min-w-0">
                            <h3 class="text-lg font-bold text-gray-900 truncate">{{ $viewData['user']->name ?? '—' }}</h3>
                            <p class="text-sm text-gray-500 font-mono">{{ $viewData['detail']->employee_id ?? '—' }}</p>
                            @if ($viewData['user']->is_active ?? false)
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
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Personal Information</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 min-w-0">
                                <p class="text-xs text-gray-400 mb-0.5">Email</p>
                                <p class="text-sm font-medium text-gray-800 break-all">{{ $viewData['user']->email ?? '—' }}</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 min-w-0">
                                <p class="text-xs text-gray-400 mb-0.5">Mobile</p>
                                <p class="text-sm font-medium text-gray-800">{{ $viewData['user']->mobile_number ?? '—' }}</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 min-w-0">
                                <p class="text-xs text-gray-400 mb-0.5">Gender</p>
                                <p class="text-sm font-medium text-gray-800 capitalize">{{ $viewData['user']->gender ?? '—' }}</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 min-w-0">
                                <p class="text-xs text-gray-400 mb-0.5">Date of Birth</p>
                                <p class="text-sm font-medium text-gray-800">{{ $viewData['user']->dob ?? '—' }}</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 min-w-0">
                                <p class="text-xs text-gray-400 mb-0.5">Emergency Contact</p>
                                <p class="text-sm font-medium text-gray-800">{{ $viewData['detail']->emergency_contact ?? '—' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Professional --}}
                    <div>
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Professional Information</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <div class="bg-teal-50 p-3 rounded-lg border border-teal-100 min-w-0">
                                <p class="text-xs text-teal-400 mb-0.5">Employee ID</p>
                                <p class="text-sm font-bold text-teal-800 font-mono truncate">{{ $viewData['detail']->employee_id ?? '—' }}</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 min-w-0">
                                <p class="text-xs text-gray-400 mb-0.5">Date of Joining</p>
                                <p class="text-sm font-bold text-gray-800">{{ $viewData['detail']->date_of_joining ?? '—' }}</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 min-w-0">
                                <p class="text-xs text-gray-400 mb-0.5">Qualification</p>
                                <p class="text-sm font-bold text-gray-800 truncate" title="{{ $viewData['detail']->qualification ?? '' }}">{{ $viewData['detail']->qualification ?? '—' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Assigned Classes --}}
                    @if (!empty($viewData['assignments']) && count($viewData['assignments']) > 0)
                        <div>
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Assigned Classes</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($viewData['assignments'] as $asgn)
                                    <span class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg font-medium border border-blue-100">
                                        {{ $asgn->standard?->name ?? '—' }}
                                        @if ($asgn->section)
                                            <span class="text-blue-400">•</span>
                                            {{ $asgn->section->name }}
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Address --}}
                    <div>
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Address</h4>
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 space-y-3">
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Address</p>
                                <p class="text-sm text-gray-800">{{ $viewData['detail']->address ?? '—' }}</p>
                            </div>
                            <div class="grid grid-cols-3 gap-4 pt-2 border-t border-gray-200">
                                <div class="min-w-0">
                                    <p class="text-xs text-gray-400">City</p>
                                    <p class="text-sm font-medium text-gray-700 truncate">{{ $viewData['detail']->city ?? '—' }}</p>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs text-gray-400">State</p>
                                    <p class="text-sm font-medium text-gray-700 truncate">{{ $viewData['detail']->state ?? '—' }}</p>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs text-gray-400">Pincode</p>
                                    <p class="text-sm font-medium text-gray-700">{{ $viewData['detail']->pincode ?? '—' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Fixed footer --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeViewModal" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Close</button>
                    @if (!empty($viewData['detail']?->id))
                        <button wire:click="onEditTeacher({{ $viewData['detail']->id }})" type="button"
                            class="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-md flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            Edit Teacher
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         DELETE CONFIRM OVERLAY (replaces broken WireUI dialog)
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
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Delete teacher?</h3>
                        <p class="text-sm text-gray-500">This will permanently delete the teacher account, profile, class assignments, and uploaded photo. This action cannot be undone.</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button wire:click="cancelDelete" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md transition-colors">Cancel</button>
                    <button wire:click="confirmDelete" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md disabled:opacity-60 flex items-center gap-1.5">
                        <span wire:loading.remove wire:target="confirmDelete">Delete Teacher</span>
                        <span wire:loading wire:target="confirmDelete">Deleting...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
