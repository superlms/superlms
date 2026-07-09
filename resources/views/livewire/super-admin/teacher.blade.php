<div class="min-h-screen bg-gray-50">

    {{-- ══════════ HEADER (analytics + filter) ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="px-4 sm:px-6 py-4 sm:py-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Teachers</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Manage all teacher records across schools</p>
                </div>
                <div class="flex items-center gap-4">
                    {{-- Analytics (Users-style) --}}
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200">
                        <span class="pr-4">Schools: <strong class="text-gray-800">{{ $totalSchools }}</strong></span>
                        <span class="px-4">Total: <strong class="text-gray-800">{{ $totalTeachers }}</strong></span>
                        <span class="px-4">Active: <strong class="text-emerald-600">{{ $activeTeachers }}</strong></span>
                        <span class="pl-4">Inactive: <strong class="text-rose-500">{{ $inactiveTeachers }}</strong></span>
                    </div>

                    <div class="flex items-center gap-2 flex-shrink-0">
                        {{-- Export Button (first) --}}
                        <button wire:click="exportTeachers" @disabled(!$filterOrganization)
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

                        {{-- Add Teacher Button (after Export) --}}
                        <button wire:click="openAddPanel"
                            class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 text-sm font-semibold
                                   bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="hidden sm:inline">Add Teacher</span>
                            <span class="sm:hidden">Add</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Mobile stats --}}
            <div class="flex lg:hidden items-center gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Schools: <strong class="text-gray-800">{{ $totalSchools }}</strong></span>
                <span>Total: <strong class="text-gray-800">{{ $totalTeachers }}</strong></span>
                <span>Active: <strong class="text-emerald-600">{{ $activeTeachers }}</strong></span>
                <span>Inactive: <strong class="text-rose-500">{{ $inactiveTeachers }}</strong></span>
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
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search name, email, mobile, employee ID…"
                        class="text-xs bg-white border border-gray-200 rounded-md pl-8 pr-3 py-1.5 text-gray-700 w-56 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <select wire:model.live="filterOrganization"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Schools</option>
                    @foreach ($organizations as $org)
                        <option value="{{ $org->id }}">{{ $org->name }}</option>
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

                @if ($search || $filterOrganization || $filterGender || $filterStatus !== '')
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
                                Teacher</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                School</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Mobile</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Employee ID</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Qualification</th>
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($teachers as $index => $teacher)
                            <tr class="hover:bg-gray-50/70 transition-colors">

                                {{-- S.No --}}
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-500 font-medium">
                                        {{ $teachers->firstItem() + $index }}
                                    </span>
                                </td>

                                {{-- Teacher --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($teacher->user?->image)
                                            <img src="{{ $teacher->user->image }}"
                                                class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                        @else
                                            <div
                                                class="w-9 h-9 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0">
                                                <span class="text-xs font-semibold text-purple-600">
                                                    {{ strtoupper(substr($teacher->user?->name ?? 'T', 0, 1)) }}
                                                </span>
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 truncate">
                                                {{ $teacher->user?->name ?? '—' }}</p>
                                            <p class="text-xs text-gray-400 truncate">
                                                {{ $teacher->user?->email ?? '—' }}</p>
                                        </div>
                                    </div>
                                </td>

                                {{-- School --}}
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-700 truncate block max-w-[160px]">
                                        {{ $teacher->user?->organization?->name ?? '—' }}
                                    </span>
                                </td>

                                {{-- Mobile --}}
                                <td class="px-4 py-3">
                                    <span
                                        class="text-sm text-gray-700">{{ $teacher->user?->mobile_number ?? '—' }}</span>
                                </td>

                                {{-- Employee ID --}}
                                <td class="px-4 py-3">
                                    <span
                                        class="text-sm font-mono text-gray-700">{{ $teacher->employee_id ?? '—' }}</span>
                                </td>

                                 <td class="px-4 py-3">
                                    <span
                                        class="text-sm font-mono text-gray-700">{{ $teacher->qualification ?? '—' }}</span>
                                </td>


                                {{-- Actions (status dot first) --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <span class="w-2 h-2 rounded-full flex-shrink-0 mr-1 {{ $teacher->user?->is_active ? 'bg-green-500' : 'bg-red-500' }}"
                                            title="{{ $teacher->user?->is_active ? 'Active' : 'Inactive' }}"></span>
                                        <button wire:click="viewTeacher({{ $teacher->user_id }})"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                            title="View">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <button wire:click="openEditPanel({{ $teacher->id }})"
                                            class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"
                                            title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button wire:click="onDeleteTeacherSuperAdmin({{ $teacher->id }})"
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
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <div
                                        class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 text-sm">No teachers found</p>
                                    @if ($search || $filterOrganization || $filterGender || $filterStatus !== '')
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
            @if ($teachers->hasPages())
                <div
                    class="px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p class="text-sm text-gray-500">
                        Showing <strong class="text-gray-700">{{ $teachers->firstItem() }}</strong>
                        to <strong class="text-gray-700">{{ $teachers->lastItem() }}</strong>
                        of <strong class="text-gray-700">{{ $teachers->total() }}</strong> teachers
                    </p>
                    <div class="flex items-center gap-1">
                        @if ($teachers->onFirstPage())
                            <span
                                class="px-3 py-1.5 text-sm text-gray-300 border border-gray-200 rounded-lg cursor-not-allowed">&laquo;
                                Prev</span>
                        @else
                            <button wire:click="previousPage"
                                class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                &laquo; Prev
                            </button>
                        @endif
                        @foreach ($teachers->getUrlRange(max(1, $teachers->currentPage() - 2), min($teachers->lastPage(), $teachers->currentPage() + 2)) as $page => $url)
                            <button wire:click="gotoPage({{ $page }})"
                                class="px-3 py-1.5 text-sm rounded-lg transition-colors
                                    {{ $page == $teachers->currentPage()
                                        ? 'bg-blue-600 text-white border border-blue-600'
                                        : 'text-gray-600 border border-gray-300 hover:bg-gray-50' }}">
                                {{ $page }}
                            </button>
                        @endforeach
                        @if ($teachers->hasMorePages())
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
            @forelse ($teachers as $index => $teacher)
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="flex items-center gap-3 p-4 border-b border-gray-100">
                        <span class="text-xs font-bold text-gray-400 w-6 text-center">
                            {{ $teachers->firstItem() + $index }}
                        </span>
                        @if ($teacher->user?->image)
                            <img src="{{ $teacher->user->image }}"
                                class="w-10 h-10 rounded-full object-cover border border-gray-200 flex-shrink-0">
                        @else
                            <div
                                class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0">
                                <span class="text-sm font-semibold text-purple-600">
                                    {{ strtoupper(substr($teacher->user?->name ?? 'T', 0, 1)) }}
                                </span>
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $teacher->user?->name ?? '—' }}
                            </p>
                            <p class="text-xs text-gray-400 truncate">
                                {{ $teacher->user?->organization?->name ?? '—' }}</p>
                        </div>
                        @if ($teacher->user?->is_active)
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
                                <p class="text-gray-700 font-medium">{{ $teacher->user?->mobile_number ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">Employee ID</p>
                                <p class="text-gray-700 font-mono text-xs font-medium">
                                    {{ $teacher->employee_id ?? '—' }}</p>
                            </div>
                        </div>
                        @if ($teacher->assignedSubjects->count())
                            <div class="flex flex-wrap gap-1">
                                @foreach ($teacher->assignedSubjects->take(3) as $as)
                                    <span
                                        class="text-xs px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-full font-medium border border-indigo-100">
                                        {{ $as->subject?->name ?? '—' }}
                                    </span>
                                @endforeach
                                @if ($teacher->assignedSubjects->count() > 3)
                                    <span class="text-xs px-2 py-0.5 bg-gray-100 text-gray-500 rounded-full">
                                        +{{ $teacher->assignedSubjects->count() - 3 }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center border-t border-gray-100 divide-x divide-gray-100">
                        <button wire:click="viewTeacher({{ $teacher->user_id }})"
                            class="flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-medium
                                   text-blue-600 hover:bg-blue-50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            View
                        </button>
                        <button wire:click="openEditPanel({{ $teacher->id }})"
                            class="flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-medium
                                   text-amber-600 hover:bg-amber-50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit
                        </button>
                        <button wire:click="onDeleteTeacherSuperAdmin({{ $teacher->id }})"
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
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <p class="text-gray-500 text-sm">No teachers found</p>
                    @if ($search || $filterOrganization || $filterGender || $filterStatus !== '')
                        <button wire:click="clearFilters"
                            class="mt-3 text-sm text-blue-600 hover:text-blue-800 font-medium">Clear filters</button>
                    @endif
                </div>
            @endforelse

            {{-- Pagination Mobile --}}
            @if ($teachers->hasPages())
                <div
                    class="flex items-center justify-between bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3">
                    <p class="text-xs text-gray-500">
                        {{ $teachers->firstItem() }}–{{ $teachers->lastItem() }} of {{ $teachers->total() }}
                    </p>
                    <div class="flex items-center gap-1">
                        @if (!$teachers->onFirstPage())
                            <button wire:click="previousPage"
                                class="px-2.5 py-1 text-xs text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Prev</button>
                        @endif
                        <span class="px-2.5 py-1 text-xs bg-blue-600 text-white rounded-lg">
                            {{ $teachers->currentPage() }}
                        </span>
                        @if ($teachers->hasMorePages())
                            <button wire:click="nextPage"
                                class="px-2.5 py-1 text-xs text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Next</button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════ VIEW MODAL ══════════ --}}
    {{-- ══════════ ADD TEACHER SLIDE-IN PANEL ══════════ --}}
    @if ($showAddPanel)
        <div class="fixed inset-0 z-50 overflow-hidden">
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]"
                wire:click="closeAddPanel"></div>

            {{-- Panel --}}
            <div class="absolute top-0 right-0 bottom-0 z-10 w-full max-w-xl bg-white shadow-2xl
                        flex flex-col overflow-hidden">

                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-white flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Add New Teacher</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Fill in all required fields to create a teacher account</p>
                    </div>
                    <button wire:click="closeAddPanel"
                        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Panel Body --}}
                <div class="flex-1 overflow-y-auto px-6 py-5 space-y-6">

                    {{-- School --}}
                    <div>
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">School</h3>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Organization <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="addOrgId"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                                       focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-white">
                                <option value="">— Select School —</option>
                                @foreach ($organizations as $org)
                                    <option value="{{ $org->id }}">{{ $org->name }}</option>
                                @endforeach
                            </select>
                            @error('addOrgId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Basic Info --}}
                    <div>
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Basic Information</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Full Name <span class="text-red-500">*</span>
                                </label>
                                <input wire:model="addName" type="text" placeholder="Enter full name"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                                           focus:ring-2 focus:ring-purple-500 focus:border-purple-500" />
                                @error('addName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Email <span class="text-red-500">*</span>
                                </label>
                                <input wire:model="addEmail" type="email" placeholder="teacher@school.com"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                                           focus:ring-2 focus:ring-purple-500 focus:border-purple-500" />
                                @error('addEmail') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Mobile <span class="text-red-500">*</span>
                                </label>
                                <input wire:model="addMobile" type="text" placeholder="10-digit number"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                                           focus:ring-2 focus:ring-purple-500 focus:border-purple-500" />
                                @error('addMobile') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Date of Birth <span class="text-red-500">*</span>
                                </label>
                                <input wire:model="addDob" type="date"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                                           focus:ring-2 focus:ring-purple-500 focus:border-purple-500" />
                                @error('addDob') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Gender <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="addGender"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                                           focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-white">
                                    <option value="">— Select —</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                                @error('addGender') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Professional --}}
                    <div>
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Professional Details</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Employee ID <span class="text-red-500">*</span>
                                </label>
                                <input wire:model="addEmployeeId" type="text" placeholder="e.g. EMP001"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                                           focus:ring-2 focus:ring-purple-500 focus:border-purple-500 font-mono" />
                                @error('addEmployeeId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Date of Joining <span class="text-red-500">*</span>
                                </label>
                                <input wire:model="addDateOfJoining" type="date"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                                           focus:ring-2 focus:ring-purple-500 focus:border-purple-500" />
                                @error('addDateOfJoining') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Qualification <span class="text-red-500">*</span>
                                </label>
                                <input wire:model="addQualification" type="text" placeholder="e.g. B.Ed, M.Sc"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                                           focus:ring-2 focus:ring-purple-500 focus:border-purple-500" />
                                @error('addQualification') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Address --}}
                    <div>
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Address</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Address <span class="text-red-500">*</span>
                                </label>
                                <textarea wire:model="addAddress" rows="2" placeholder="House no., Street, Area"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                                           focus:ring-2 focus:ring-purple-500 focus:border-purple-500 resize-none"></textarea>
                                @error('addAddress') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                                <select wire:model.live="addState"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                                           focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-white">
                                    <option value="">— Select State —</option>
                                    @foreach ($addStates as $state)
                                        <option value="{{ $state }}">{{ $state }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                                <select wire:model="addCity"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                                           focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-white
                                           {{ empty($addCities) ? 'opacity-50' : '' }}">
                                    <option value="">— Select City —</option>
                                    @foreach ($addCities as $city)
                                        <option value="{{ $city }}">{{ $city }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Pincode <span class="text-red-500">*</span>
                                </label>
                                <input wire:model="addPincode" type="text" placeholder="6-digit pincode"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                                           focus:ring-2 focus:ring-purple-500 focus:border-purple-500" />
                                @error('addPincode') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Emergency --}}
                    <div>
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Emergency Contact</h3>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Emergency Contact Number <span class="text-red-500">*</span>
                            </label>
                            <input wire:model="addEmergencyContact" type="text" placeholder="10-digit mobile number"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                                       focus:ring-2 focus:ring-purple-500 focus:border-purple-500" />
                            @error('addEmergencyContact') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Password note --}}
                    <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                        <p class="font-semibold mb-0.5">Auto-generated password</p>
                        <p class="text-xs text-amber-700">A secure password will be generated and emailed to the teacher upon account creation.</p>
                    </div>

                </div>

                {{-- Panel Footer --}}
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50 flex-shrink-0">
                    <button wire:click="closeAddPanel" type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300
                               rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button wire:click="saveNewTeacher" type="button"
                        wire:loading.attr="disabled" wire:target="saveNewTeacher"
                        class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold
                               bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors shadow-sm
                               disabled:opacity-60 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="saveNewTeacher">Save Teacher</span>
                        <span wire:loading wire:target="saveNewTeacher" class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            Saving...
                        </span>
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
                    <h2 class="text-base font-bold text-gray-900">Teacher Details</h2>
                    <button wire:click="closeViewModal" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                {{-- Body --}}
                <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5 text-sm text-gray-700">
                    <div class="flex items-center gap-4 pb-4 border-b border-gray-200">
                        @if ($teacherImageUrl)
                            <img src="{{ $teacherImageUrl }}" class="w-16 h-16 rounded-full object-cover border border-gray-200">
                        @else
                            <div class="w-16 h-16 rounded-full bg-purple-100 flex items-center justify-center">
                                <span class="text-xl font-bold text-purple-600">{{ strtoupper(substr($viewData['user']?->name ?? 'T', 0, 1)) }}</span>
                            </div>
                        @endif
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $viewData['user']?->name ?? '—' }}</h3>
                            <p class="text-sm text-gray-500">{{ $viewData['detail']?->employee_id ?? '—' }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $viewData['school_name'] }}</p>
                            @if ($viewData['user']?->is_active)
                                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 mt-1 bg-green-50 text-green-700 rounded-full font-medium border border-green-100"><span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Active</span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 mt-1 bg-red-50 text-red-600 rounded-full font-medium border border-red-100"><span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span> Inactive</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Personal Information</h4>
                        <dl class="grid grid-cols-2 gap-3">
                            <div><dt class="text-xs text-gray-400">Email</dt><dd class="font-medium text-xs break-all">{{ $viewData['user']?->email ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Mobile</dt><dd class="font-medium">{{ $viewData['user']?->mobile_number ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Gender</dt><dd class="font-medium capitalize">{{ $viewData['user']?->gender ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Date of Birth</dt><dd class="font-medium">{{ $viewData['user']?->dob ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Qualification</dt><dd class="font-medium">{{ $viewData['detail']?->qualification ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Emergency Contact</dt><dd class="font-medium">{{ $viewData['detail']?->emergency_contact ?? '—' }}</dd></div>
                        </dl>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Professional</h4>
                        <dl class="grid grid-cols-2 gap-3">
                            <div><dt class="text-xs text-gray-400">Employee ID</dt><dd class="font-medium font-mono">{{ $viewData['detail']?->employee_id ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-400">Date of Joining</dt><dd class="font-medium">{{ $viewData['detail']?->date_of_joining ?? '—' }}</dd></div>
                            @if (!empty($viewData['subjects']) && $viewData['subjects'] !== '—')
                                <div class="col-span-2">
                                    <dt class="text-xs text-gray-400 mb-1">Subjects</dt>
                                    <dd class="flex flex-wrap gap-1.5">
                                        @foreach (explode(', ', $viewData['subjects']) as $subject)
                                            <span class="text-xs px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-full font-medium border border-indigo-100">{{ $subject }}</span>
                                        @endforeach
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Address</h4>
                        <dl class="grid grid-cols-1 gap-2">
                            <div><dt class="text-xs text-gray-400">Address</dt><dd class="font-medium">{{ $viewData['detail']?->address ?? '—' }}</dd></div>
                            <div class="grid grid-cols-3 gap-3">
                                <div><dt class="text-xs text-gray-400">City</dt><dd class="font-medium">{{ $viewData['detail']?->city ?? '—' }}</dd></div>
                                <div><dt class="text-xs text-gray-400">State</dt><dd class="font-medium">{{ $viewData['detail']?->state ?? '—' }}</dd></div>
                                <div><dt class="text-xs text-gray-400">Pincode</dt><dd class="font-medium">{{ $viewData['detail']?->pincode ?? '—' }}</dd></div>
                            </div>
                        </dl>
                    </div>
                </div>
                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50 flex-shrink-0">
                    <button wire:click="closeViewModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Close</button>
                    <button wire:click="openEditPanel({{ $viewData['detail']?->id }}); closeViewModal()"
                        class="px-4 py-2 text-sm font-semibold text-white bg-amber-500 hover:bg-amber-600 rounded-lg transition-colors">
                        Edit Teacher
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ EDIT TEACHER SLIDE-IN PANEL ══════════ --}}
    @if ($showEditPanel)
        <div class="fixed inset-0 z-[9999]">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeEditPanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col z-10">
                {{-- Header --}}
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-amber-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-gray-900">Edit Teacher</h2>
                            <p class="text-xs text-gray-400">Update teacher information</p>
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
                        <select wire:model.defer="editOrgId" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-white">
                            <option value="">— Select School —</option>
                            @foreach ($organizations as $org)
                                <option value="{{ $org->id }}">{{ $org->name }}</option>
                            @endforeach
                        </select>
                        @error('editOrgId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Basic Info --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Basic Info</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Full Name <span class="text-red-500">*</span></label>
                                <input wire:model.defer="editName" type="text" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"/>
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
                        </div>
                    </div>

                    {{-- Professional --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Professional Details</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Employee ID <span class="text-red-500">*</span></label>
                                <input wire:model.defer="editEmployeeId" type="text" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 font-mono"/>
                                @error('editEmployeeId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Date of Joining <span class="text-red-500">*</span></label>
                                <input wire:model.defer="editDateOfJoining" type="date" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"/>
                                @error('editDateOfJoining') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Qualification <span class="text-red-500">*</span></label>
                                <input wire:model.defer="editQualification" type="text" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"/>
                                @error('editQualification') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Emergency Contact <span class="text-red-500">*</span></label>
                                <input wire:model.defer="editEmergencyContact" type="text" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"/>
                                @error('editEmergencyContact') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Address --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Address</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Address <span class="text-red-500">*</span></label>
                                <textarea wire:model.defer="editAddress" rows="2" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 resize-none"></textarea>
                                @error('editAddress') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
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
                                <label class="block text-xs font-medium text-gray-600 mb-1">Pincode <span class="text-red-500">*</span></label>
                                <input wire:model.defer="editPincode" type="text" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 font-mono"/>
                                @error('editPincode') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 px-5 py-4 border-t border-gray-100 bg-gray-50 flex-shrink-0">
                    <button wire:click="closeEditPanel" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
                    <button wire:click="saveEditTeacher" type="button"
                        wire:loading.attr="disabled" wire:target="saveEditTeacher"
                        class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition-colors disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveEditTeacher">Save Changes</span>
                        <span wire:loading wire:target="saveEditTeacher" class="flex items-center gap-2">
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
                        <h3 class="text-base font-bold text-gray-900">Delete Teacher?</h3>
                        <p class="text-sm text-gray-500 mt-1">This will permanently delete the teacher and their account. This action cannot be undone.</p>
                    </div>
                    <div class="flex items-center gap-3 w-full mt-1">
                        <button wire:click="cancelDelete" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">Cancel</button>
                        <button wire:click="doDeleteTeacher({{ $deleteTargetId }})"
                            wire:loading.attr="disabled" wire:target="doDeleteTeacher"
                            class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-colors disabled:opacity-60">
                            <span wire:loading.remove wire:target="doDeleteTeacher">Yes, Delete</span>
                            <span wire:loading wire:target="doDeleteTeacher">Deleting...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
