<div class="min-h-screen bg-gray-50">

    {{-- ══════════ HEADER (analytics + filter) ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:min-h-9">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900">Students</h1>
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

                <div class="relative flex-[1_1_8rem] min-w-0 max-w-56">
                    <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" /></svg>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search name, email, admission, roll…"
                        class="text-xs bg-white border border-gray-200 rounded-md pl-8 pr-3 py-1.5 text-gray-700 w-full focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <select wire:model.live="filterOrganization"
                    class="text-xs min-w-0 max-w-[13rem] bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Schools</option>
                    @foreach ($organizations as $org)
                        <option value="{{ $org->id }}">{{ $org->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filterClass" @disabled(!$filterOrganization)
                    class="text-xs min-w-0 max-w-[13rem] bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                    <option value="">All Classes</option>
                    @foreach ($standards as $standard)
                        <option value="{{ $standard->id }}">{{ $standard->name }}</option>
                    @endforeach
                </select>

                <select data-sole-section wire:model.live="filterSection" @disabled(!$filterClass)
                    class="text-xs min-w-0 max-w-[13rem] bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                    <option value="">All Sections</option>
                    @foreach ($filterSections as $section)
                        <option value="{{ $section->id }}">{{ $section->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filterGender"
                    class="text-xs min-w-0 max-w-[13rem] bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Genders</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>

                <select wire:model.live="filterStatus"
                    class="text-xs min-w-0 max-w-[13rem] bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>

                @if ($search || $filterOrganization || $filterClass || $filterSection || $filterGender || $filterStatus !== '')
                    <button wire:click="clearFilters"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
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
                                                class="w-9 h-9 rounded-full object-cover object-top border border-gray-200 flex-shrink-0">
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

                                {{-- Class / Section: the class, then the section's last letter (Nursery-A) --}}
                                <td class="px-4 py-3">
                                    @if ($student->standard)
                                        @php $secLetter = mb_strtoupper(mb_substr(trim((string) $student->section?->name), -1)); @endphp
                                        <span class="text-sm text-gray-700 whitespace-nowrap">{{ $student->standard->name }}{{ $secLetter !== '' ? '-' . $secLetter : '' }}</span>
                                    @else
                                        <span class="text-sm text-gray-400">—</span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <span
                                            class="w-2 h-2 rounded-full flex-shrink-0 mr-1 {{ $student->user?->is_active ? 'bg-green-500' : 'bg-red-500' }}"
                                            title="{{ $student->user?->is_active ? 'Active' : 'Inactive' }}"></span>
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
                                class="w-10 h-10 rounded-full object-cover object-top border border-gray-200 flex-shrink-0">
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
                            @php $mSecLetter = mb_strtoupper(mb_substr(trim((string) $student->section?->name), -1)); @endphp
                            <p class="text-sm text-gray-700">{{ $student->standard ? $student->standard->name . ($mSecLetter !== '' ? '-' . $mSecLetter : '') : '—' }}</p>
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

    {{-- ══════════════════════════════════════════════════
         ADD / EDIT — the school panel's student slide-in (flat 2-col grid,
         header + footer fixed). Same fields and saves as before; the school
         picker stays first since the Super Admin adds for any school.
    ══════════════════════════════════════════════════ --}}
    @php
        $saIn = 'w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500';
        $saLb = 'block text-sm font-medium text-gray-700 mb-1.5';
    @endphp
    @foreach ([
        ['open' => $showAddPanel, 'p' => 'add', 'close' => 'closeAddPanel', 'save' => 'saveNewStudent'],
        ['open' => $showEditPanel, 'p' => 'edit', 'close' => 'closeEditPanel', 'save' => 'saveEditStudent'],
    ] as $form)
        @if ($form['open'])
            @php
                $p        = $form['p'];
                $isEdit   = $p === 'edit';
                $image    = $isEdit ? $editImage : $addImage;
                $imageUrl = $isEdit ? $editImageUrl : null;
                $orgId    = $isEdit ? $editOrgId : $addOrgId;
                $stdId    = $isEdit ? $editStandardId : $addStandardId;
                $stds     = $isEdit ? $editStandards : $addStandards;
                $secs     = $isEdit ? $editSections : $addSections;
                $board    = $isEdit ? $editBoard : $addBoard;
                $states   = $isEdit ? $editStates : $addStates;
                $cities   = $isEdit ? $editCities : $addCities;
                $state    = $isEdit ? $editState : $addState;
                $needsBus = $isEdit ? $editTransportation : $addTransportation;
                $routes   = $isEdit ? $editRouteOptions : $addRouteOptions;
                $routeId  = $isEdit ? $editRoute : $addRoute;
                $regModel = $isEdit ? 'editRegNo' : 'addRegNo';
            @endphp
            <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden" wire:key="sa-student-{{ $p }}-panel">
                <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="{{ $form['close'] }}"></div>
                <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col">

                    {{-- Header --}}
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                        <div class="min-w-0">
                            <h2 class="text-lg font-semibold text-gray-900">{{ $isEdit ? 'Edit Student' : 'New Student' }}</h2>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ $isEdit ? 'Update the student details below' : 'A password is generated and emailed to the student on save' }}
                            </p>
                        </div>
                        <button wire:click="{{ $form['close'] }}" type="button"
                            class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Body --}}
                    <div class="flex-1 overflow-y-auto">
                        <div class="px-6 py-6 space-y-5">

                            {{-- School --}}
                            <div>
                                <label class="{{ $saLb }}">School <span class="text-red-500">*</span></label>
                                <select wire:model.live="{{ $p }}OrgId"
                                    class="{{ $saIn }} @error($p . 'OrgId') border-red-400 @enderror">
                                    <option value="">Select School</option>
                                    @foreach ($organizations as $org)
                                        <option value="{{ $org->id }}">{{ $org->name }}</option>
                                    @endforeach
                                </select>
                                @error($p . 'OrgId')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>

                            {{-- Profile photo --}}
                            <div>
                                <label class="{{ $saLb }}">
                                    Student Profile Photo <span class="text-gray-400 font-normal">(Optional, max 1 MB)</span>
                                </label>
                                <div class="flex items-center gap-3">
                                    @if ($image)
                                        <img src="{{ $image->temporaryUrl() }}" class="w-12 h-12 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                    @elseif ($imageUrl)
                                        <img src="{{ $imageUrl }}" class="w-12 h-12 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                    @else
                                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                    @endif
                                    <input type="file" wire:model="{{ $p }}Image" accept="image/*" class="flex-1 text-sm">
                                </div>
                                <div wire:loading wire:target="{{ $p }}Image" class="text-xs text-blue-600 mt-1">Uploading…</div>
                                @error($p . 'Image')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>

                            {{-- Personal, parents, academic and address fields in one flat 2-col grid --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="{{ $saLb }}">Full Name <span class="text-red-500">*</span></label>
                                    <input wire:model.defer="{{ $p }}Name" type="text" maxlength="50" placeholder="Enter full name"
                                        class="{{ $saIn }} @error($p . 'Name') border-red-400 @enderror">
                                    @error($p . 'Name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="{{ $saLb }}">Email <span class="text-red-500">*</span></label>
                                    <input wire:model.defer="{{ $p }}Email" type="email" placeholder="student@example.com"
                                        class="{{ $saIn }} @error($p . 'Email') border-red-400 @enderror">
                                    @error($p . 'Email')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="{{ $saLb }}">Mobile <span class="text-red-500">*</span></label>
                                    <input wire:model.defer="{{ $p }}Mobile" type="tel" maxlength="10" inputmode="numeric"
                                        oninput="this.value=this.value.replace(/\D/g,'').slice(0,10)" placeholder="10-digit mobile"
                                        class="{{ $saIn }} @error($p . 'Mobile') border-red-400 @enderror">
                                    @error($p . 'Mobile')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="{{ $saLb }}">Gender <span class="text-red-500">*</span></label>
                                    <select wire:model.defer="{{ $p }}Gender"
                                        class="{{ $saIn }} @error($p . 'Gender') border-red-400 @enderror">
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                    @error($p . 'Gender')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="{{ $saLb }}">Class <span class="text-red-500">*</span></label>
                                    <select wire:model.live="{{ $p }}StandardId" @disabled(!$orgId)
                                        class="{{ $saIn }} disabled:opacity-50 @error($p . 'StandardId') border-red-400 @enderror">
                                        <option value="">Select Class</option>
                                        @foreach ($stds as $std)
                                            <option value="{{ $std->id }}">{{ $std->name }}</option>
                                        @endforeach
                                    </select>
                                    @error($p . 'StandardId')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                    <p class="mt-1 text-[11px] text-gray-400">Board: {{ $board ?: '—' }} (auto-fetched from the class)</p>
                                </div>
                                <div>
                                    <label class="{{ $saLb }}">Section <span class="text-red-500">*</span></label>
                                    <select wire:model.defer="{{ $p }}SectionId" @disabled(!$stdId)
                                        class="{{ $saIn }} disabled:opacity-50 @error($p . 'SectionId') border-red-400 @enderror">
                                        <option value="">Select Section</option>
                                        @foreach ($secs as $sec)
                                            <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                                        @endforeach
                                    </select>
                                    @error($p . 'SectionId')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="{{ $saLb }}">Date of Birth <span class="text-red-500">*</span></label>
                                    <input wire:model.defer="{{ $p }}Dob" type="date" max="{{ now()->subDay()->format('Y-m-d') }}"
                                        class="{{ $saIn }} @error($p . 'Dob') border-red-400 @enderror">
                                    @error($p . 'Dob')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="{{ $saLb }}">Date of Admission <span class="text-gray-400 font-normal text-xs">(optional)</span></label>
                                    <input wire:model.defer="{{ $p }}DateOfAdmission" type="date" max="{{ now()->format('Y-m-d') }}"
                                        class="{{ $saIn }} @error($p . 'DateOfAdmission') border-red-400 @enderror">
                                    @error($p . 'DateOfAdmission')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="{{ $saLb }}">Religion</label>
                                    <input wire:model.defer="{{ $p }}Religion" type="text" maxlength="20" placeholder="e.g. Hindu, Muslim"
                                        class="{{ $saIn }}">
                                </div>
                                <div>
                                    <label class="{{ $saLb }}">Aadhar Number</label>
                                    <input wire:model.defer="{{ $p }}AadharNo" type="text" maxlength="12" inputmode="numeric"
                                        oninput="this.value=this.value.replace(/\D/g,'').slice(0,12)" placeholder="12-digit Aadhar no."
                                        class="{{ $saIn }} @error($p . 'AadharNo') border-red-400 @enderror">
                                    @error($p . 'AadharNo')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="{{ $saLb }}">Father's Name <span class="text-red-500">*</span></label>
                                    <input wire:model.defer="{{ $p }}FatherName" type="text" maxlength="50" placeholder="Father's full name"
                                        class="{{ $saIn }} @error($p . 'FatherName') border-red-400 @enderror">
                                    @error($p . 'FatherName')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="{{ $saLb }}">Mother's Name <span class="text-gray-400 font-normal text-xs">(optional)</span></label>
                                    <input wire:model.defer="{{ $p }}MotherName" type="text" maxlength="50" placeholder="Mother's full name"
                                        class="{{ $saIn }} @error($p . 'MotherName') border-red-400 @enderror">
                                    @error($p . 'MotherName')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="{{ $saLb }}">Apaar ID</label>
                                    <input wire:model.defer="{{ $p }}ApparId" type="text" maxlength="25" placeholder="Apaar ID"
                                        class="{{ $saIn }}">
                                </div>
                                <div>
                                    <label class="{{ $saLb }}">Registration Number</label>
                                    <input wire:model.defer="{{ $regModel }}" type="text" maxlength="25" placeholder="Registration number"
                                        class="{{ $saIn }}">
                                </div>
                                <div>
                                    <label class="{{ $saLb }}">State</label>
                                    <select wire:model.live="{{ $p }}State" class="{{ $saIn }}">
                                        <option value="">Select State</option>
                                        @foreach ($states as $st)
                                            <option value="{{ $st }}">{{ $st }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="{{ $saLb }}">City</label>
                                    <select wire:model.defer="{{ $p }}City" @disabled(!$state) class="{{ $saIn }} disabled:opacity-50">
                                        <option value="">Select City</option>
                                        @foreach ($cities as $city)
                                            <option value="{{ $city }}">{{ $city }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="{{ $saLb }}">Pincode</label>
                                    <input wire:model.defer="{{ $p }}Pincode" type="text" maxlength="6" inputmode="numeric"
                                        oninput="this.value=this.value.replace(/\D/g,'').slice(0,6)" placeholder="6-digit pincode"
                                        class="{{ $saIn }} @error($p . 'Pincode') border-red-400 @enderror">
                                    @error($p . 'Pincode')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="{{ $saLb }}">Transport Required? <span class="text-red-500">*</span></label>
                                    <select wire:model.live="{{ $p }}Transportation" class="{{ $saIn }}">
                                        <option value="0">No</option>
                                        <option value="1">Yes</option>
                                    </select>
                                </div>
                                @if ($needsBus)
                                    <div>
                                        <label class="{{ $saLb }}">Select Route <span class="text-red-500">*</span></label>
                                        <select wire:model.live="{{ $p }}Route"
                                            class="{{ $saIn }} @error($p . 'Route') border-red-400 @enderror">
                                            <option value="">Select Route</option>
                                            @foreach ($routes as $route)
                                                <option value="{{ $route->id }}">{{ $route->route_name }} — ₹{{ number_format($route->monthly_fee, 0) }}/mo</option>
                                            @endforeach
                                        </select>
                                        @error($p . 'Route')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                        @if (count($routes) === 0)
                                            <p class="text-amber-600 text-xs mt-1">No active routes for this school.</p>
                                        @endif

                                        @php $chosenRoute = collect($routes)->firstWhere('id', (int) $routeId); @endphp
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
                                <label class="{{ $saLb }}">Local Address</label>
                                <textarea wire:model.defer="{{ $p }}LocalAddress" rows="2" maxlength="250" placeholder="Current/local address"
                                    class="{{ $saIn }} resize-none"></textarea>
                            </div>
                            <div>
                                <label class="{{ $saLb }}">Permanent Address</label>
                                <textarea wire:model.defer="{{ $p }}PermanentAddress" rows="2" maxlength="250" placeholder="Permanent address (if different)"
                                    class="{{ $saIn }} resize-none"></textarea>
                            </div>

                            {{-- Active toggle --}}
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model.defer="{{ $p }}Active" class="rounded">
                                <span class="text-sm text-gray-700">Active (can log in)</span>
                            </label>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                        <button type="button" wire:click="{{ $form['close'] }}"
                            class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">
                            Cancel
                        </button>
                        <button type="button" wire:click="{{ $form['save'] }}" wire:loading.attr="disabled" wire:target="{{ $form['save'] }}"
                            class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                            <span wire:loading.remove wire:target="{{ $form['save'] }}">{{ $isEdit ? 'Update Student' : 'Create Student' }}</span>
                            <span wire:loading wire:target="{{ $form['save'] }}">Saving...</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    {{-- ══════════════════════════════════════════════════
         VIEW — the school panel's view: one plain label / value list
    ══════════════════════════════════════════════════ --}}
    @if ($showViewModal && !empty($viewData))
        @php
            $stuUser  = $viewData['user'] ?? null;
            $stuDet   = $viewData['detail'] ?? null;
            $svRoute  = $stuDet?->transportations?->first();
        @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeViewModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $stuUser->name ?? 'Student Details' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5 truncate">
                            {{ ($stuUser->email ?? '') !== '' ? $stuUser->email . ' · ' : '' }}{{ ($stuUser->is_active ?? false) ? 'Active' : 'Inactive' }}
                        </p>
                    </div>
                    <button wire:click="closeViewModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div class="flex justify-center pb-2">
                        @if ($studentImageUrl)
                            <img src="{{ $studentImageUrl }}" alt="{{ $stuUser->name ?? '' }}"
                                class="w-24 h-24 rounded-full object-cover object-top border border-gray-200">
                        @else
                            <div class="w-24 h-24 rounded-full bg-indigo-100 flex items-center justify-center">
                                <span class="text-3xl font-semibold text-indigo-600">{{ strtoupper(substr($stuUser->name ?? 'S', 0, 1)) }}</span>
                            </div>
                        @endif
                    </div>

                    @foreach ([
                        'School'            => $stuUser?->organization?->name ?? 'N/A',
                        'Admission No'      => $stuDet->admission_no ?? 'N/A',
                        'Roll No'           => $stuDet->roll_no ?? 'N/A',
                        'Class'             => $viewData['standard']?->name ?? 'N/A',
                        'Section'           => $viewData['section']?->name ?? 'N/A',
                        'Board'             => $stuDet->board ?? 'N/A',
                        'Admission Date'    => $stuDet?->date_of_admission?->format('d M Y') ?? 'N/A',
                        'Mobile'            => $stuUser->mobile_number ?? 'N/A',
                        'Gender'            => $stuDet?->gender ? ucfirst($stuDet->gender) : 'N/A',
                        'Date of Birth'     => $stuDet?->dob?->format('d M Y') ?? 'N/A',
                        "Father's Name"     => $stuDet->father_name ?? 'N/A',
                        "Mother's Name"     => $stuDet->mother_name ?? 'N/A',
                        'Religion'          => $stuDet->religion ?? 'N/A',
                        'Aadhar No'         => $stuDet->aadhar_no ?? 'N/A',
                        'Apaar ID'          => $stuDet->appar_id ?? 'N/A',
                        'Registration No'   => $stuDet->registration_number ?? 'N/A',
                        'Local Address'     => $stuDet->local_address ?? 'N/A',
                        'Permanent Address' => $stuDet->permanent_address ?? 'N/A',
                        'City'              => $stuDet->city ?? 'N/A',
                        'State'             => $stuDet->state ?? 'N/A',
                        'Pincode'           => $stuDet->pincode ?? 'N/A',
                        'Transport Required'=> $stuDet?->transportation_required ? 'Yes' : 'No',
                    ] as $label => $value)
                        <div class="grid grid-cols-3 gap-3 text-sm">
                            <span class="text-xs text-gray-400 uppercase tracking-wider">{{ $label }}</span>
                            <span class="col-span-2 text-gray-800 font-medium break-words">{{ ($value === null || trim((string) $value) === '') ? 'N/A' : $value }}</span>
                        </div>
                    @endforeach

                    @if ($svRoute)
                        <div class="grid grid-cols-3 gap-3 text-sm">
                            <span class="text-xs text-gray-400 uppercase tracking-wider">Transport Route</span>
                            <span class="col-span-2 text-gray-800 font-medium">{{ $svRoute->route_name }}</span>
                        </div>
                        <div class="grid grid-cols-3 gap-3 text-sm">
                            <span class="text-xs text-gray-400 uppercase tracking-wider">Transport Fee</span>
                            <span class="col-span-2 text-gray-800 font-medium">₹{{ number_format($svRoute->monthly_fee, 0) }}/mo · ₹{{ number_format($svRoute->monthly_fee * 11, 0) }}/yr</span>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-between flex-shrink-0">
                    <button type="button" wire:click="editFromView({{ $stuDet->id ?? 0 }})"
                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Edit</button>
                    <div class="flex items-center gap-2">
                        {{-- Only when the super admin has Send Credentials on for the school (WhatsApp tab). --}}
                        @if (\App\Services\SchoolWhatsApp::credentialsOn((int) ($viewData['user']->organization_id ?? 0)))
                        {{-- Send Credentials: the WhatsApp welcome again — admission no. / username and password (2026-10-14). --}}
                        <button type="button" wire:click="sendCredentials" wire:confirm="Send the login details again on WhatsApp?"
                            wire:loading.attr="disabled" wire:target="sendCredentials"
                            class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 text-sm font-medium text-emerald-700 border border-emerald-200 hover:bg-emerald-50 rounded-md whitespace-nowrap disabled:opacity-60">
                            <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.75-.86-2.02-.96-.27-.1-.47-.15-.67.15-.2.3-.77.96-.94 1.16-.17.2-.35.22-.64.07-.3-.15-1.25-.46-2.38-1.47-.88-.79-1.47-1.76-1.65-2.06-.17-.3-.02-.46.13-.6.13-.14.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.6-.92-2.2-.24-.58-.49-.5-.67-.5h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48 0 1.46 1.07 2.88 1.22 3.08.15.2 2.1 3.2 5.08 4.48.71.31 1.26.49 1.7.63.71.23 1.36.2 1.87.12.57-.08 1.75-.72 2-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35M12.05 21.5h-.01a9.4 9.4 0 01-4.8-1.31l-.34-.2-3.57.94.95-3.48-.22-.36a9.39 9.39 0 01-1.44-5.01c0-5.2 4.23-9.43 9.44-9.43 2.52 0 4.89.98 6.67 2.77a9.37 9.37 0 012.76 6.67c0 5.2-4.23 9.43-9.44 9.43m8.03-17.47A11.28 11.28 0 0012.05.7C5.79.7.7 5.79.7 12.04c0 2 .52 3.95 1.52 5.67L.6 23.6l6.03-1.58a11.33 11.33 0 005.42 1.38h.01c6.25 0 11.34-5.09 11.34-11.34 0-3.03-1.18-5.88-3.32-8.02" /></svg>
                            Send Credentials
                        </button>
                        @endif
                        <button type="button" wire:click="closeViewModal"
                            class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         DELETE CONFIRM — the school panel's popup
    ══════════════════════════════════════════════════ --}}
    @if ($showDeleteConfirm)
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-[9999] flex items-center justify-center p-4">
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
                        <p class="text-sm text-gray-500">This will permanently delete the student and their account. This action cannot be undone.</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button wire:click="cancelDelete" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md transition-colors">Cancel</button>
                    <button wire:click="doDeleteStudent({{ $deleteTargetId }})" wire:loading.attr="disabled" wire:target="doDeleteStudent"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md disabled:opacity-60 flex items-center gap-1.5">
                        <span wire:loading.remove wire:target="doDeleteStudent">Delete Student</span>
                        <span wire:loading wire:target="doDeleteStudent">Deleting...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
