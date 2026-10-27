<div class="min-h-screen bg-gray-50">

    {{-- ══════════ LIST VIEW ══════════ --}}
    @if ($activeView === 'list')

        {{-- ── HEADER (analytics + filter) ── --}}
        <div class="bg-white border-b border-gray-200 sticky top-0 z-50">
            <div class="px-4 sm:px-6 py-3">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:min-h-9">
                    <div>
                        <h1 class="text-lg sm:text-xl font-bold text-gray-900">Schools</h1>
                    </div>
                    <div class="flex items-center gap-4">
                        {{-- Analytics (enquiries-style) --}}
                        <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200">
                            <span class="pr-4">Total: <strong class="text-gray-800">{{ number_format($totalSchools) }}</strong></span>
                            <span class="px-4">Active: <strong class="text-emerald-600">{{ number_format($activeSchools) }}</strong></span>
                            <span class="px-4">Inactive: <strong class="text-amber-500">{{ number_format($inactiveSchools) }}</strong></span>
                            <span class="px-4">Students: <strong class="text-gray-800">{{ number_format($totalStudents) }}</strong></span>
                            <span class="pl-4">Teachers: <strong class="text-gray-800">{{ number_format($totalTeachers) }}</strong></span>
                        </div>
                        <button wire:click="openModal"
                            class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700
                                   text-white text-sm font-semibold rounded-lg shadow-sm transition-colors flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="hidden sm:inline">Add School</span>
                        </button>
                    </div>
                </div>

                {{-- Mobile/Tablet stats --}}
                <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                    <span>Total: <strong class="text-gray-800">{{ number_format($totalSchools) }}</strong></span>
                    <span>Active: <strong class="text-emerald-600">{{ number_format($activeSchools) }}</strong></span>
                    <span>Inactive: <strong class="text-amber-500">{{ number_format($inactiveSchools) }}</strong></span>
                    <span>Students: <strong class="text-gray-800">{{ number_format($totalStudents) }}</strong></span>
                    <span>Teachers: <strong class="text-gray-800">{{ number_format($totalTeachers) }}</strong></span>
                </div>
            </div>

            {{-- Filter bar (enquiry-style sub-header) --}}
            <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filter by:
                    </div>

                    <div class="relative flex-[1_1_8rem] min-w-0 max-w-56">
                        <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" /></svg>
                        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search name, email, code…"
                            class="text-xs bg-white border border-gray-200 rounded-md pl-8 pr-3 py-1.5 text-gray-700 w-full focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <select wire:model.live="statusFilter"
                        class="text-xs min-w-0 max-w-[13rem] bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>

                    <select wire:model.live="mediumFilter"
                        class="text-xs min-w-0 max-w-[13rem] bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All mediums</option>
                        <option value="english">English Medium</option>
                        <option value="hindi">Hindi Medium</option>
                        <option value="both">Hindi &amp; English</option>
                    </select>

                    <select wire:model.live="boardFilter"
                        class="text-xs min-w-0 max-w-[13rem] bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All boards</option>
                        @foreach ($boards as $board)
                            <option value="{{ $board }}">{{ $board }}</option>
                        @endforeach
                    </select>

                    {{-- Sorting --}}
                    <div class="flex items-center gap-1.5 ml-1">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7h12M3 12h9m-9 5h6m6 0l3 3m0 0l3-3m-3 3V8"/></svg>
                        <select wire:model.live="sortBy"
                            class="text-xs min-w-0 max-w-[13rem] bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="name">Name (A–Z)</option>
                            <option value="created_at">Registered Date</option>
                            <option value="students">No. of Students</option>
                        </select>
                        <select wire:model.live="sortDir"
                            class="text-xs min-w-0 max-w-[13rem] bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="asc">Ascending</option>
                            <option value="desc">Descending</option>
                        </select>
                    </div>

                    @if ($search || $statusFilter || $mediumFilter || $boardFilter || $sortBy !== 'name' || $sortDir !== 'asc')
                        <button wire:click="clearFilters"
                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            Clear
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="p-4 sm:p-6 space-y-5">

            {{-- ══════════ DESKTOP TABLE (student-style) ══════════ --}}
            <div class="hidden md:block bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="overflow-x-auto rounded-xl">
                    <table class="w-full table-fixed">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">S.No</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">School</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-36">Mobile</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-40">Board</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">School Code</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">Students</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">Teachers</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($schools as $index => $school)
                                <tr class="hover:bg-gray-50/70 transition-colors">
                                    {{-- S.No --}}
                                    <td class="px-4 py-3">
                                        <span class="text-sm text-gray-500 font-medium">{{ $schools->firstItem() + $index }}</span>
                                    </td>
                                    {{-- School --}}
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            @if ($school->logo)
                                                <img src="{{ $school->logo }}" class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                            @else
                                                <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                                    <span class="text-xs font-semibold text-indigo-600">{{ strtoupper(substr($school->name, 0, 1)) }}</span>
                                                </div>
                                            @endif
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-gray-900 truncate max-w-[220px]">{{ $school->name }}</p>
                                                <p class="text-xs text-gray-400 truncate max-w-[220px]">{{ $school->email ?? '—' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    {{-- Mobile --}}
                                    <td class="px-4 py-3">
                                        <span class="text-sm text-gray-700 whitespace-nowrap">{{ $school->mobile_number ?? '—' }}</span>
                                    </td>
                                    {{-- Board: the board alone (no medium), cut with … when it is too long. --}}
                                    <td class="px-4 py-3">
                                        <div class="min-w-0">
                                            <p class="text-sm text-gray-700 truncate" title="{{ \App\Support\BoardName::short($school->education_board) }}">{{ \App\Support\BoardName::short($school->education_board) ?: '—' }}</p>
                                        </div>
                                    </td>
                                    {{-- School Code: every school has its own --}}
                                    <td class="px-4 py-3">
                                        <p class="text-sm text-gray-700 truncate" title="{{ $school->school_code }}">{{ $school->school_code ?: '—' }}</p>
                                    </td>
                                    {{-- Students --}}
                                    <td class="px-4 py-3 text-right">
                                        <span class="text-sm font-semibold text-gray-800">{{ number_format($school->total_students) }}</span>
                                    </td>
                                    {{-- Teachers --}}
                                    <td class="px-4 py-3 text-right">
                                        <span class="text-sm font-semibold text-gray-800">{{ number_format($school->total_teachers) }}</span>
                                    </td>
                                    {{-- Actions --}}
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <button wire:click="viewSchoolDetail({{ $school->id }})" title="View"
                                                class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </button>
                                            <button type="button" x-on:click="lmsLoginAsSchool({{ $school->id }})" title="Login as school"
                                                class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                                            </button>
                                            <button wire:click="onEdit({{ $school->id }})" title="Edit"
                                                class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                            <button wire:click="onDelete({{ $school->id }})" title="Delete"
                                                class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-16 text-center">
                                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        </div>
                                        <p class="text-gray-500 text-sm">No schools found</p>
                                        @if ($search || $statusFilter || $mediumFilter || $boardFilter)
                                            <button wire:click="clearFilters" class="mt-3 text-sm text-blue-600 hover:text-blue-800 font-medium">Clear filters</button>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($schools->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100">{{ $schools->links() }}</div>
                @endif
            </div>

            {{-- ══════════ MOBILE CARDS ══════════ --}}
            <div class="md:hidden space-y-3">
                @forelse ($schools as $index => $school)
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                        <div class="flex items-start gap-3">
                            @if ($school->logo)
                                <img src="{{ $school->logo }}" class="w-10 h-10 rounded-full object-cover border border-gray-200 flex-shrink-0">
                            @else
                                <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-sm font-bold text-indigo-600">{{ strtoupper(substr($school->name, 0, 1)) }}</span>
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-gray-900 truncate">{{ $school->name }}</p>
                                <p class="text-xs text-gray-400 truncate">{{ $school->email ?? '—' }}</p>
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1.5 text-xs text-gray-500">
                                    <span>{{ $school->mobile_number ?? '—' }}</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 text-[11px] font-medium">{{ number_format($school->total_students) }} students</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-teal-50 text-teal-600 text-[11px] font-medium">{{ number_format($school->total_teachers) }} teachers</span>
                                    @if ($school->education_board)<span class="truncate max-w-[120px]" title="{{ \App\Support\BoardName::short($school->education_board) }}">{{ \App\Support\BoardName::short($school->education_board) }}</span>@endif
                                    @if ($school->school_code)<span>Code {{ $school->school_code }}</span>@endif
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center border-t border-gray-100 divide-x divide-gray-100 mt-3 pt-1 -mb-1">
                            <button wire:click="viewSchoolDetail({{ $school->id }})" class="flex-1 py-2 text-xs font-medium text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">View</button>
                            <button type="button" x-on:click="lmsLoginAsSchool({{ $school->id }})" class="flex-1 py-2 text-xs font-medium text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors">Login</button>
                            <button wire:click="onEdit({{ $school->id }})" class="flex-1 py-2 text-xs font-medium text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">Edit</button>
                            <button wire:click="onDelete({{ $school->id }})" class="flex-1 py-2 text-xs font-medium text-red-600 hover:bg-red-50 rounded-lg transition-colors">Delete</button>
                        </div>
                    </div>
                @empty
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center text-gray-400">
                        No schools found
                    </div>
                @endforelse
                @if ($schools->hasPages())
                    <div class="mt-4">{{ $schools->links() }}</div>
                @endif
            </div>
        </div>
    @endif

    {{-- ══════════ DETAIL VIEW ══════════ --}}
    @if ($activeView === 'detail' && $detailSchool)

        {{-- ── DETAIL HEADER ── --}}
        <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3 sticky top-0 z-50">
            <div class="flex items-center justify-between gap-4 sm:min-h-9">
                <div class="flex items-center gap-3">
                    <button wire:click="backToList"
                        class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-gray-200
                               text-gray-500 hover:text-gray-800 hover:bg-gray-50 text-sm transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 19l-7-7 7-7" />
                        </svg>
                        <span class="hidden sm:inline">Back</span>
                    </button>
                    <div class="flex items-center gap-3">
                        @if ($detailSchool->logo)
                            <img src="{{ $detailSchool->logo }}"
                                class="w-9 h-9 rounded-xl object-cover border border-gray-200">
                        @else
                            <div class="w-9 h-9 rounded-xl bg-indigo-100 flex items-center justify-center">
                                <span class="text-sm font-bold text-indigo-600">
                                    {{ strtoupper(substr($detailSchool->name, 0, 1)) }}
                                </span>
                            </div>
                        @endif
                        <div class="min-w-0 sm:flex sm:items-baseline sm:gap-2">
                            <h1 class="min-w-0 text-lg font-bold text-gray-900 leading-none truncate">{{ $detailSchool->name }}</h1>
                            <p class="text-xs text-gray-400 mt-0.5 sm:mt-0 truncate">{{ $detailSchool->email }}</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span
                        class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-medium
                        {{ $detailSchool->status
                            ? 'bg-green-50 text-green-700 border border-green-100'
                            : 'bg-amber-50 text-amber-700 border border-amber-100' }}">
                        <span
                            class="w-1.5 h-1.5 rounded-full {{ $detailSchool->status ? 'bg-green-500' : 'bg-amber-400' }}"></span>
                        {{ $detailSchool->status ? 'Active' : 'Pending' }}
                    </span>
                    <button wire:click="onEdit({{ $detailSchool->id }})"
                        class="px-3 py-1.5 text-sm font-medium text-amber-600 border border-amber-200
                               rounded-lg hover:bg-amber-50 transition-colors">
                        Edit
                    </button>
                    <button wire:click="onDelete({{ $detailSchool->id }})"
                        class="px-3 py-1.5 text-sm font-medium text-red-600 border border-red-200
                               rounded-lg hover:bg-red-50 transition-colors">
                        Delete
                    </button>
                </div>
            </div>
        </div>

        <div class="p-4 sm:p-6 space-y-5">

            {{-- ── TABS ── --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">

                {{-- Tab Bar --}}
                <div class="flex border-b border-gray-200 overflow-x-auto">
                    @foreach ([
        'overview' => 'Overview',
        'modules' => 'Modules',
        'whatsapp' => 'WhatsApp',
        'sms' => 'SMS',
        'custom_fee' => 'Custom Fee',
        'biometric' => 'Biometric',
        'bank' => 'Bank Details',
        'fees' => 'Fees Analytics',
        'students' => 'Students',
        'teachers' => 'Teachers',
    ] as $tab => $label)
                        <button wire:click="setDetailTab('{{ $tab }}')"
                            class="px-5 py-3 text-sm font-medium whitespace-nowrap transition-colors border-b-2
                                {{ $detailTab === $tab
                                    ? 'border-blue-600 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- ════════ OVERVIEW TAB ════════ --}}
                @if ($detailTab === 'overview')
                    <div class="p-5">
                        {{-- Two-section field list --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-10 gap-y-2">

                            {{-- School Information --}}
                            <div>
                                <p
                                    class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 pb-2 border-b border-gray-100">
                                    School Information
                                </p>
                                <div class="divide-y divide-gray-100">
                                    @foreach ([
        'name' => 'School Name',
        'email' => 'Email',
        'mobile_number' => 'Mobile',
        'state' => 'State',
        'address' => 'Address',
    ] as $field => $label)
                                        <div class="flex items-start gap-4 py-2.5">
                                            <span
                                                class="text-xs text-gray-400 w-28 flex-shrink-0 pt-0.5 leading-relaxed">
                                                {{ $label }}
                                            </span>
                                            <span class="text-sm text-gray-800 font-medium leading-relaxed">
                                                {{ $detailSchool->$field ?? '—' }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Registration Details --}}
                            <div>
                                <p
                                    class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 pb-2 border-b border-gray-100">
                                    Registration Details
                                </p>
                                <div class="divide-y divide-gray-100">
                                    @foreach ([
        'education_board' => 'Education Board',
        'school_code' => 'School Code',
        'affiliation_no' => 'Affiliation No',
        'udise_number' => 'UDISE Number',
        'serial_number' => 'Serial Number',
    ] as $field => $label)
                                        <div class="flex items-start gap-4 py-2.5">
                                            <span
                                                class="text-xs text-gray-400 w-28 flex-shrink-0 pt-0.5 leading-relaxed">
                                                {{ $label }}
                                            </span>
                                            <span class="text-sm text-gray-800 font-medium leading-relaxed">
                                                {{ $detailSchool->$field ?? '—' }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                        </div>
                    </div>
                @endif

                {{-- ════════ MODULES TAB ════════
                     The Mark Attendance panel's look: one quiet toolbar (what is on,
                     Enable all / Disable all), then a flat list of small rows with
                     hairlines. Credit has left the school panel and is not offered. --}}
                @if ($detailTab === 'modules')
                    @php
                        $shownModules = $this->shownModules();
                        $modulesOn    = collect($shownModules)->keys()->filter(fn ($k) => !empty($moduleStates[$k]))->count();
                    @endphp
                    <div class="p-5 space-y-3">
                        <p class="text-xs text-gray-500">
                            Turn features on or off for this school. A feature turned off is hidden from the school's menu and
                            blocked from direct access. Home, Students, Teachers, Users and Analytics are always on.
                        </p>

                        <div class="border border-gray-200 rounded-md">
                            {{-- Toolbar --}}
                            <div class="px-4 py-2.5 border-b border-gray-100 flex flex-wrap items-center gap-2">
                                <div class="inline-flex items-center rounded-md border border-gray-200 overflow-hidden text-xs">
                                    <button type="button" wire:click="enableAllModules" class="px-2.5 py-1.5 text-gray-600 hover:bg-gray-50">Enable all</button>
                                    <button type="button" wire:click="disableAllModules" class="px-2.5 py-1.5 text-gray-600 hover:bg-gray-50 border-l border-gray-200">Disable all</button>
                                </div>
                                <span class="ml-auto text-xs text-gray-400 tabular-nums">{{ $modulesOn }} of {{ count($shownModules) }} on</span>
                            </div>

                            {{-- Rows --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($shownModules as $key => $def)
                                    <label wire:key="module-{{ $key }}"
                                        class="flex items-center gap-2.5 px-4 py-2 border-b border-gray-100 cursor-pointer hover:bg-gray-50">
                                        <input type="checkbox" wire:model="moduleStates.{{ $key }}"
                                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                        <span class="text-sm text-gray-700 truncate">{{ $def['label'] ?? $key }}</span>
                                    </label>
                                @endforeach
                            </div>

                            {{-- Footer --}}
                            <div class="px-4 py-2.5 flex justify-end">
                                <button type="button" wire:click="saveModules" wire:loading.attr="disabled" wire:target="saveModules"
                                    class="px-4 py-1.5 text-sm font-medium rounded-md bg-gray-900 hover:bg-gray-800 text-white disabled:opacity-60">
                                    <span wire:loading.remove wire:target="saveModules">Save Module Access</span>
                                    <span wire:loading wire:target="saveModules">Saving…</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ════════ WHATSAPP TAB ════════
                     The school's paid messages (Absent alert, Fee received) on /
                     off, and its analytics: the last 30 days or a month — each
                     kind day by day, what it came to, delivery, the messages. --}}
                @if ($detailTab === 'whatsapp')
                    <div class="p-5">
                        @if ($wa = $this->whatsAppAnalytics())
                            @include('livewire.partials.whatsapp-analytics', ['wa' => $wa, 'waEditable' => true, 'waKey' => 'sa-school-' . $detailSchool->id])
                        @else
                            <p class="text-center py-12 text-sm text-gray-400">WhatsApp messages are not set up yet.</p>
                        @endif
                    </div>
                @endif

                {{-- ════════ SMS TAB ════════
                     Welcome by SMS (Bhash) on / off: on, a new student or teacher
                     gets their login by SMS only, in place of the welcome email and
                     WhatsApp (App\Services\WelcomeSms). And the SMS that went. --}}
                @if ($detailTab === 'sms')
                    <div class="p-5 space-y-4">
                        @if ($sms = $this->smsSummary())
                            <div class="border border-gray-200 rounded-md">
                                <div class="px-4 py-3 flex flex-wrap items-center gap-3">
                                    <button type="button" role="switch" aria-checked="{{ $sms['enabled'] ? 'true' : 'false' }}"
                                        wire:click="toggleSms" wire:loading.attr="disabled" wire:target="toggleSms"
                                        title="{{ $sms['enabled'] ? 'Turn off' : 'Turn on' }}"
                                        class="relative inline-flex h-5 w-9 flex-shrink-0 items-center rounded-full transition-colors disabled:opacity-60 {{ $sms['enabled'] ? 'bg-emerald-500' : 'bg-gray-300' }}">
                                        <span class="inline-block h-4 w-4 rounded-full bg-white shadow transition-transform {{ $sms['enabled'] ? 'translate-x-4.5' : 'translate-x-0.5' }}"></span>
                                    </button>
                                    <span class="text-sm font-medium text-gray-800">Welcome by SMS</span>
                                    <span class="text-xs {{ $sms['enabled'] ? 'text-emerald-600' : 'text-gray-400' }}">{{ $sms['enabled'] ? 'On' : 'Off' }}</span>
                                </div>
                                <div class="px-4 py-3 border-t border-gray-100 space-y-2">
                                    <p class="text-xs text-gray-500 leading-relaxed">
                                        When on, a new student gets an SMS with the school, class, section, admission number,
                                        password and the app link, and a new teacher one with their username, password and the
                                        app link — and <strong class="text-gray-700">no welcome email and no WhatsApp</strong>.
                                        A changed mobile number and Send Credentials go by SMS too. When off, the email and
                                        WhatsApp go as before.
                                    </p>
                                    @unless ($sms['configured'])
                                        <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded px-2.5 py-1.5">
                                            Bhash SMS is not set up on the server yet (BHASH_SMS_USER, BHASH_SMS_PASS, BHASH_SMS_SENDER),
                                            so this school still gets the email and WhatsApp.
                                        </p>
                                    @endunless
                                </div>
                            </div>

                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                                @foreach ([
                                    ['Sent this month', number_format($sms['month']['sent']), 'text-gray-900'],
                                    ['Failed this month', number_format($sms['month']['failed']), $sms['month']['failed'] ? 'text-red-600' : 'text-gray-900'],
                                    ['SMS parts this month', number_format($sms['month']['parts']), 'text-gray-900'],
                                    ['Sent in all', number_format($sms['total']), 'text-gray-900'],
                                ] as [$smsLabel, $smsValue, $smsTone])
                                    <div class="border border-gray-200 rounded-md px-4 py-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ $smsLabel }}</p>
                                        <p class="mt-1 text-sm font-semibold tabular-nums {{ $smsTone }}">{{ $smsValue }}</p>
                                    </div>
                                @endforeach
                            </div>

                            <div class="border border-gray-200 rounded-md overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50 text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                                        <tr>
                                            <th class="px-4 py-2 text-left">When</th>
                                            <th class="px-4 py-2 text-left">To</th>
                                            <th class="px-4 py-2 text-left">Kind</th>
                                            <th class="px-4 py-2 text-left">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @forelse ($sms['messages'] as $smsRow)
                                            <tr wire:key="sms-row-{{ $smsRow['id'] }}">
                                                <td class="px-4 py-2 whitespace-nowrap text-gray-500 tabular-nums">{{ optional($smsRow['at'])->format('d M Y, h:i A') }}</td>
                                                <td class="px-4 py-2">
                                                    <span class="text-gray-800">{{ $smsRow['name'] }}</span>
                                                    <span class="text-xs text-gray-400"> · {{ $smsRow['who'] }} · {{ $smsRow['to'] }}</span>
                                                </td>
                                                <td class="px-4 py-2 text-gray-600">{{ $smsRow['kind'] }}</td>
                                                <td class="px-4 py-2">
                                                    <span class="text-xs font-medium {{ $smsRow['status'] === 'sent' ? 'text-emerald-600' : ($smsRow['status'] === 'failed' ? 'text-red-600' : 'text-gray-500') }}">
                                                        {{ ucfirst($smsRow['status']) }}
                                                    </span>
                                                    @if ($smsRow['status'] === 'failed' && $smsRow['error'])
                                                        <span class="block text-xs text-gray-400 break-words max-w-xs">{{ $smsRow['error'] }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400">No SMS sent for this school yet.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-center py-12 text-sm text-gray-400">SMS is not set up yet.</p>
                        @endif
                    </div>
                @endif

                {{-- ════════ CUSTOM FEE TAB ════════
                     On: the school's Fee page has Add Fee, Submit Fee and View Fee
                     (each student's own particulars) in place of its nine sections,
                     and the accounts desk one Fees page for the same. Off: the
                     ordinary fee screens, as they were. Nothing is deleted either way. --}}
                @if ($detailTab === 'custom_fee')
                    <div class="p-5 space-y-4">
                        @if ($cf = $this->customFeeSummary())
                            <div class="border border-gray-200 rounded-md">
                                <div class="px-4 py-3 flex flex-wrap items-center gap-3">
                                    <button type="button" role="switch" aria-checked="{{ $cf['enabled'] ? 'true' : 'false' }}"
                                        wire:click="toggleCustomFee" wire:loading.attr="disabled" wire:target="toggleCustomFee"
                                        title="{{ $cf['enabled'] ? 'Turn off — a code is emailed to you to confirm' : 'Turn on — a code is emailed to you to confirm' }}"
                                        class="relative inline-flex h-5 w-9 flex-shrink-0 items-center rounded-full transition-colors disabled:opacity-60 {{ $cf['enabled'] ? 'bg-emerald-500' : 'bg-gray-300' }}">
                                        <span class="inline-block h-4 w-4 rounded-full bg-white shadow transition-transform {{ $cf['enabled'] ? 'translate-x-4.5' : 'translate-x-0.5' }}"></span>
                                    </button>
                                    <span class="text-sm font-medium text-gray-800">Custom Fee</span>
                                    <span class="text-xs {{ $cf['enabled'] ? 'text-emerald-600' : 'text-gray-400' }}">{{ $cf['enabled'] ? 'On' : 'Off' }}</span>
                                </div>
                                <div class="px-4 py-3 border-t border-gray-100">
                                    <p class="text-xs text-gray-500 leading-relaxed">
                                        When on, the school sets each student's fee by hand — any particulars and amounts, on that
                                        student alone — so a parent's children can all be put on one of them. Its Fee page shows
                                        <strong class="text-gray-700">Add Fee</strong>, <strong class="text-gray-700">Submit Fee</strong> and
                                        <strong class="text-gray-700">View Fee</strong> in place of Fee Structure, Fee Cycle, Concession,
                                        Penalties and the rest; the accounts desk gets one Fees page for the same; Home and Analytics count
                                        the fee from it. When off, the ordinary fee screens come back as they were. Nothing is deleted either way.
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                                @foreach ([
                                    ['Students with a fee', number_format($cf['students']), 'text-gray-900'],
                                    ['Total fee', '₹' . number_format($cf['total'], 0), 'text-gray-900'],
                                    ['Collected', '₹' . number_format($cf['collected'], 0) . ' · ' . number_format($cf['payments']) . ' payments', 'text-emerald-600'],
                                    ['Balance', '₹' . number_format($cf['balance'], 0), $cf['balance'] > 0 ? 'text-red-600' : 'text-gray-900'],
                                ] as [$cfLabel, $cfValue, $cfTone])
                                    <div class="border border-gray-200 rounded-md px-4 py-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ $cfLabel }}</p>
                                        <p class="mt-1 text-sm font-semibold tabular-nums {{ $cfTone }}">{{ $cfValue }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-center py-12 text-sm text-gray-400">Custom Fee is not set up yet.</p>
                        @endif
                    </div>
                @endif

                {{-- ════════ BIOMETRIC TAB ════════
                     On: the school's fingerprint / 125 kHz card machines mark
                     attendance as people punch (App\Support\BiometricPunches) —
                     its times on the school's Setting page. The machines are
                     added here by serial number; each is then sent every student
                     and teacher (with their card) and keeps calling us. --}}
                @if ($detailTab === 'biometric')
                    <div class="p-5 space-y-4">
                        @if ($bio = $this->biometricSummary())
                            <div class="border border-gray-200 rounded-md">
                                <div class="px-4 py-3 flex flex-wrap items-center gap-3">
                                    <button type="button" role="switch" aria-checked="{{ $bio['enabled'] ? 'true' : 'false' }}"
                                        wire:click="toggleBiometric" wire:loading.attr="disabled" wire:target="toggleBiometric"
                                        title="{{ $bio['enabled'] ? 'Turn off — a code is emailed to you to confirm' : 'Turn on — a code is emailed to you to confirm' }}"
                                        class="relative inline-flex h-5 w-9 flex-shrink-0 items-center rounded-full transition-colors disabled:opacity-60 {{ $bio['enabled'] ? 'bg-emerald-500' : 'bg-gray-300' }}">
                                        <span class="inline-block h-4 w-4 rounded-full bg-white shadow transition-transform {{ $bio['enabled'] ? 'translate-x-4.5' : 'translate-x-0.5' }}"></span>
                                    </button>
                                    <span class="text-sm font-medium text-gray-800">Biometric attendance</span>
                                    <span class="text-xs {{ $bio['enabled'] ? 'text-emerald-600' : 'text-gray-400' }}">{{ $bio['enabled'] ? 'On' : 'Off' }}</span>
                                </div>
                                <div class="px-4 py-3 border-t border-gray-100">
                                    <p class="text-xs text-gray-500 leading-relaxed">
                                        When on, a punch on the school's machines — fingerprint or 125 kHz card — marks the day as it happens.
                                        <strong class="text-gray-700">Students:</strong> a punch by the school's last time ({{ $bio['times']['student_last_time'] }}) is Present, a later one Absent.
                                        <strong class="text-gray-700">Teachers:</strong> the first punch is the punch-in, the last the punch-out.
                                        The student or teacher hears it on the app at once, and the web's Attendance page shows it.
                                        The school sets its times on its Setting page, and assigns cards from View Student / View Teacher with a USB card reader.
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                                @foreach ([
                                    ['Machines', number_format(count($bio['devices'])) . ' · ' . $bio['devices']->filter->online()->count() . ' online', 'text-gray-900'],
                                    ['Cards — students', number_format($bio['cards']['student']), 'text-gray-900'],
                                    ['Cards — teachers', number_format($bio['cards']['teacher']), 'text-gray-900'],
                                    ['Punches today', number_format($bio['punches']) . ' · ' . number_format($bio['people']) . ' people', 'text-emerald-600'],
                                ] as [$bLabel, $bValue, $bTone])
                                    <div class="border border-gray-200 rounded-md px-4 py-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ $bLabel }}</p>
                                        <p class="mt-1 text-sm font-semibold tabular-nums {{ $bTone }}">{{ $bValue }}</p>
                                    </div>
                                @endforeach
                            </div>

                            {{-- The machines --}}
                            <div class="border border-gray-200 rounded-md">
                                <div class="px-4 py-2.5 border-b border-gray-100 flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-medium text-gray-800">Machines</span>
                                    <span class="ml-auto text-xs text-gray-400">In the machine: Comm. → Cloud Server (ADMS) — Server <strong class="text-gray-600">{{ $bio['host'] }}</strong>, Port <strong class="text-gray-600">80</strong>, Domain name on, Proxy off</span>
                                </div>
                                <div class="divide-y divide-gray-100">
                                    @forelse ($bio['devices'] as $dev)
                                        <div wire:key="bio-dev-{{ $dev->id }}" class="flex flex-wrap items-center gap-3 px-4 py-2.5">
                                            <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $dev->online() ? 'bg-emerald-500' : 'bg-gray-300' }}"
                                                title="{{ $dev->online() ? 'Online' : 'Not heard from' }}"></span>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm text-gray-800 truncate">{{ $dev->name ?: 'Machine' }} <span class="text-xs text-gray-400 font-mono">{{ $dev->serial_number }}</span></p>
                                                <p class="text-[11px] text-gray-400">
                                                    {{ $dev->last_seen_at ? 'Last heard ' . $dev->last_seen_at->diffForHumans() . ($dev->last_ip ? ' from ' . $dev->last_ip : '') : 'Not heard from yet — set the server in the machine' }}
                                                    @if ($dev->waiting) · {{ number_format($dev->waiting) }} still to send @endif
                                                </p>
                                            </div>
                                            <div class="inline-flex items-center rounded-md border border-gray-200 overflow-hidden text-xs flex-shrink-0">
                                                <button type="button" wire:click="syncBiometricDevice({{ $dev->id }})" wire:loading.attr="disabled"
                                                    title="Send every student and teacher (with cards and fingerprints) to this machine again"
                                                    class="px-2.5 py-1.5 text-gray-600 hover:bg-gray-50">Send everyone</button>
                                                <button type="button" wire:click="removeBiometricDevice({{ $dev->id }})"
                                                    wire:confirm="Remove this machine? Its punches stop counting until it is added again."
                                                    class="px-2.5 py-1.5 text-red-600 hover:bg-red-50 border-l border-gray-200">Remove</button>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="px-4 py-6 text-center text-xs text-gray-400">No machine added yet.</p>
                                    @endforelse
                                </div>
                                <div class="px-4 py-3 border-t border-gray-100 flex flex-wrap items-start gap-2">
                                    <div class="flex-1 min-w-[10rem]">
                                        <input type="text" wire:model="bioSerial" placeholder="Serial number (Menu → System Info)"
                                            class="w-full text-sm border border-gray-300 rounded-md px-3 py-1.5 font-mono focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                        @error('bioSerial') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <input type="text" wire:model="bioName" placeholder="Name, e.g. Main gate"
                                        class="flex-1 min-w-[10rem] text-sm border border-gray-300 rounded-md px-3 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                    <button type="button" wire:click="addBiometricDevice" wire:loading.attr="disabled" wire:target="addBiometricDevice"
                                        class="px-4 py-1.5 text-sm font-medium rounded-md bg-gray-900 hover:bg-gray-800 text-white disabled:opacity-60">Add Machine</button>
                                </div>
                                @if ($bio['unknown'])
                                    {{-- Machines that called here but are no school's yet --}}
                                    <div class="px-4 py-2.5 border-t border-gray-100 bg-amber-50/60">
                                        <p class="text-xs font-medium text-amber-800 mb-1.5">Machines calling that are not added to any school</p>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach ($bio['unknown'] as $sn => $seen)
                                                <button type="button" wire:click="addBiometricDevice('{{ $sn }}')" wire:key="bio-unk-{{ $sn }}"
                                                    title="Add to {{ $detailSchool->name }}"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs bg-white border border-amber-200 rounded-full hover:bg-amber-100">
                                                    <span class="font-mono">{{ $sn }}</span>
                                                    <span class="text-amber-700">· {{ \Illuminate\Support\Carbon::createFromTimestamp($seen['at'])->diffForHumans() }} · Add here</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @else
                            <p class="text-center py-12 text-sm text-gray-400">Biometric attendance is not set up yet.</p>
                        @endif
                    </div>
                @endif

                {{-- ════════ BANK DETAILS TAB ════════ --}}
                @if ($detailTab === 'bank')
                    <div class="p-5">
                        <div class="flex items-center justify-between mb-4">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                Bank Account Details
                            </p>
                            <button wire:click="openBankModal"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg transition-colors
                                    {{ $detailSchool->bank_name
                                        ? 'text-amber-600 border border-amber-200 hover:bg-amber-50'
                                        : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="{{ $detailSchool->bank_name
                                            ? 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'
                                            : 'M12 4v16m8-8H4' }}" />
                                </svg>
                                {{ $detailSchool->bank_name ? 'Edit' : 'Add Bank Details' }}
                            </button>
                        </div>

                        @if ($detailSchool->bank_name)
                            <div class="divide-y divide-gray-100">
                                @foreach ([
        'Bank Name' => $detailSchool->bank_name,
        'Account Holder' => $detailSchool->bank_holder_name,
        'Account Number' => $detailSchool->bank_account_no,
        'IFSC Code' => $detailSchool->bank_ifsc,
        'Branch' => $detailSchool->bank_branch,
    ] as $label => $value)
                                    <div class="flex items-start gap-4 py-2.5">
                                        <span class="text-xs text-gray-400 w-28 flex-shrink-0 pt-0.5 leading-relaxed">{{ $label }}</span>
                                        <span class="text-sm text-gray-800 font-medium leading-relaxed">{{ $value ?? '—' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="py-12 text-center text-sm text-gray-400">
                                No bank details added yet.
                            </div>
                        @endif
                    </div>
                @endif

                {{-- ════════ ONLINE PAYMENT TAB ════════ --}}
                @if ($detailTab === 'payment')
                    @php $pg = $this->pgSetting; @endphp
                    <div class="p-5">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                    PhonePe Merchant (fee collection)
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Set this school's own PhonePe credentials so student fees settle into
                                    <span class="font-medium">its own account</span>. Leave blank to use the platform account.
                                </p>
                            </div>
                            <button wire:click="openPaymentModal"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg transition-colors
                                    {{ $pg
                                        ? 'text-amber-600 border border-amber-200 hover:bg-amber-50'
                                        : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="{{ $pg ? 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' : 'M12 4v16m8-8H4' }}" />
                                </svg>
                                {{ $pg ? 'Edit' : 'Connect PhonePe' }}
                            </button>
                        </div>

                        @if ($pg)
                            <div class="mb-4">
                                @if ($pg->collectionReady())
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-50 text-emerald-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active — collecting to own account
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-500">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Not active (using platform account)
                                    </span>
                                @endif
                            </div>
                            <div class="divide-y divide-gray-100">
                                @foreach ([
        'Client ID' => $pg->client_id,
        'Client Secret' => $pg->client_secret ? '•••••••• (saved)' : '—',
        'Client Version' => $pg->client_version,
        'Environment' => strtoupper($pg->env ?? 'sandbox'),
        'Webhook Username' => $pg->webhook_username ?: '—',
        'Webhook Password' => $pg->webhook_password ? '•••••••• (saved)' : '—',
    ] as $label => $value)
                                    <div class="flex items-center gap-6 py-3">
                                        <span class="text-xs text-gray-400 w-36 flex-shrink-0">{{ $label }}</span>
                                        <span class="text-sm font-semibold text-gray-800 font-mono break-all">{{ $value ?? '—' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="py-12 text-center text-sm text-gray-400">
                                Not connected. Student fees currently settle into the platform account.
                            </div>
                        @endif
                    </div>
                @endif

                {{-- ════════ FEES ANALYTICS TAB ════════
                     In the home's look: sections with a quiet heading — the
                     school's collection at a glance, the year month by month
                     beside the academic / transport split, and the money that
                     came in today and yesterday. Same figures as before. --}}
                @if ($detailTab === 'fees')
                    @php
                        $faInr = fn ($v) => '₹' . number_format((float) $v, 0);
                        $faToCollect = (float) ($feeStats['total_to_collect'] ?? 0);
                        $faCollected = (float) ($feeStats['total_collected'] ?? 0);
                        $faPct = $faToCollect > 0 ? min(100, round($faCollected / $faToCollect * 100)) : 0;
                        $faTiles = [
                            ['Total to Collect', $faInr($faToCollect), 'Academic + Transport'],
                            ['Collected',        $faInr($faCollected), $faPct . '% of the fee'],
                            ['Remaining',        $faInr($feeStats['total_remaining'] ?? 0), 'still to come'],
                            ['This Month',       $faInr($feeStats['this_month'] ?? 0), now()->format('F Y')],
                            ['Last Month',       $faInr($feeStats['last_month'] ?? 0), now()->subMonthNoOverflow()->format('F Y')],
                        ];
                        $faAcademic   = (float) ($feeStats['academic_total'] ?? 0);
                        $faTransport  = (float) ($feeStats['transport_total'] ?? 0);
                        $faAcTo       = (float) ($feeStats['academic_to_collect'] ?? 0);
                        $faTrTo       = (float) ($feeStats['transport_to_collect'] ?? 0);
                        $faCards = [
                            ['Academic',  $faAcademic,  $faAcTo, $faAcTo > 0 ? min(100, round($faAcademic / $faAcTo * 100)) : 0],
                            ['Transport', $faTransport, $faTrTo, $faTrTo > 0 ? min(100, round($faTransport / $faTrTo * 100)) : 0],
                        ];

                        // The financial year, April to March, as before.
                        $faMonths = [[4, 2026], [5, 2026], [6, 2026], [7, 2026], [8, 2026], [9, 2026], [10, 2026], [11, 2026], [12, 2026], [1, 2027], [2, 2027], [3, 2027]];
                        $faChart  = $feeStats['fy_monthly_chart'] ?? [];
                        $faLabels = array_map(fn ($m) => \Carbon\Carbon::create($m[1], $m[0], 1)->format('M y'), $faMonths);
                        $faData   = array_map(fn ($m) => (float) ($faChart[$m[1] . '-' . $m[0]] ?? 0), $faMonths);

                        $faSplitTotal = $faAcademic + $faTransport;
                        $faTwoDays    = $feeStats['two_days'] ?? [];
                        $faToday      = now()->toDateString();
                    @endphp
                    <div class="p-5 space-y-8">

                        {{-- ── Overview ── --}}
                        <section>
                            <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Overview</h2>
                            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5">
                                @foreach ($faTiles as [$label, $value, $sub])
                                    <div class="px-5 py-4 -mr-px -mb-px border-r border-b border-gray-100 min-w-0">
                                        <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide truncate">{{ $label }}</p>
                                        <p class="text-[26px] leading-none font-semibold text-gray-900 mt-2 tabular-nums truncate">{{ $value }}</p>
                                        <p class="text-xs text-gray-400 mt-1.5 truncate">{{ $sub }}</p>
                                    </div>
                                @endforeach
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                                @foreach ($faCards as [$label, $paid, $to, $pct])
                                    <div class="bg-white rounded-xl border border-gray-200 px-5 py-4">
                                        <div class="flex items-baseline justify-between gap-2">
                                            <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide truncate">{{ $label }}</p>
                                            <p class="text-lg font-semibold text-gray-900 tabular-nums whitespace-nowrap">{{ $pct }}%</p>
                                        </div>
                                        <div class="w-full bg-gray-100 rounded-full h-1 mt-3">
                                            <div class="bg-gray-800 h-1 rounded-full" style="width: {{ $pct }}%"></div>
                                        </div>
                                        <p class="text-xs text-gray-400 mt-2 truncate">{{ $faInr($paid) }} of {{ $faInr($to) }} collected</p>
                                    </div>
                                @endforeach
                            </div>
                        </section>

                        {{-- ── Collection ── --}}
                        <section>
                            <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Collection</h2>
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                                <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5">
                                    <div class="mb-4">
                                        <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">Monthly · {{ $faLabels[0] }} – {{ end($faLabels) }}</p>
                                        <p class="text-2xl font-semibold text-gray-900 mt-1 tabular-nums">{{ $faInr(array_sum($faData)) }}</p>
                                    </div>
                                    {{-- Keyed on its figures: a new payment redraws it. --}}
                                    <div class="h-56" wire:key="sa-school-fee-chart-{{ $detailSchool->id }}-{{ md5(json_encode($faData)) }}">
                                        <canvas x-data="{
                                            chart: null,
                                            init() {
                                                if (typeof Chart === 'undefined') return;
                                                this.chart = new Chart(this.$el.getContext('2d'), {
                                                    type: 'bar',
                                                    data: {
                                                        labels: @js($faLabels),
                                                        datasets: [{ label: 'Collected', data: @js($faData), backgroundColor: 'rgba(99,102,241,0.7)', borderRadius: 3, borderSkipped: false, maxBarThickness: 34 }]
                                                    },
                                                    options: {
                                                        responsive: true, maintainAspectRatio: false,
                                                        plugins: {
                                                            legend: { display: false },
                                                            tooltip: { callbacks: { label: (c) => '₹' + Number(c.raw).toLocaleString('en-IN') } }
                                                        },
                                                        scales: {
                                                            x: { grid: { display: false }, ticks: { font: { size: 9 }, maxRotation: 0, autoSkipPadding: 8 } },
                                                            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, callback: (v) => '₹' + Number(v).toLocaleString('en-IN') } }
                                                        }
                                                    }
                                                });
                                            },
                                            destroy() { this.chart?.destroy(); }
                                        }"></canvas>
                                    </div>
                                </div>

                                <div class="bg-white rounded-xl border border-gray-200 p-5">
                                    <h3 class="text-sm font-semibold text-gray-800 mb-4">By Fee Type</h3>
                                    <dl class="space-y-4 text-sm">
                                        @foreach ([['Academic', $faAcademic], ['Transport', $faTransport]] as [$label, $value])
                                            @php $share = $faSplitTotal > 0 ? round($value / $faSplitTotal * 100) : 0; @endphp
                                            <div>
                                                <div class="flex items-baseline justify-between gap-3">
                                                    <dt class="text-gray-500">{{ $label }}</dt>
                                                    <dd class="font-semibold text-gray-900 tabular-nums">{{ $faInr($value) }} <span class="text-xs font-normal text-gray-400">({{ $share }}%)</span></dd>
                                                </div>
                                                <div class="w-full bg-gray-100 rounded-full h-1.5 mt-2"><div class="bg-gray-800 h-1.5 rounded-full" style="width: {{ $share }}%"></div></div>
                                            </div>
                                        @endforeach
                                        <div class="flex items-baseline justify-between gap-3 pt-4 border-t border-gray-100">
                                            <dt class="text-gray-500">This month's payments</dt>
                                            <dd class="font-semibold text-gray-900 tabular-nums">{{ number_format($feeStats['this_month_count'] ?? 0) }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </section>

                        {{-- ── Recent Transactions: today and yesterday only ── --}}
                        <section>
                            <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 mb-3">
                                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Recent Transactions</h2>
                                <p class="text-xs text-gray-400 tabular-nums">
                                    Today <span class="text-gray-700 font-medium">{{ $faInr($feeStats['today_total'] ?? 0) }}</span><span class="text-gray-300"> · </span>
                                    Yesterday <span class="text-gray-700 font-medium">{{ $faInr($feeStats['yesterday_total'] ?? 0) }}</span>
                                </p>
                            </div>
                            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                                @if (empty($faTwoDays))
                                    <p class="px-5 py-10 text-center text-sm text-gray-400">No fee came in today or yesterday.</p>
                                @else
                                    <div class="overflow-x-auto">
                                        <table class="w-full min-w-[640px]">
                                            <thead class="bg-gray-50 border-b border-gray-200">
                                                <tr>
                                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500">Student</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Receipt</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Type</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Mode</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Day</th>
                                                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                                @foreach ($faTwoDays as $i => $tx)
                                                    <tr class="hover:bg-gray-50/60" wire:key="fa-tx-{{ $i }}">
                                                        <td class="px-5 py-3">
                                                            <p class="text-sm font-medium text-gray-800">{{ $tx['name'] ?: '—' }}</p>
                                                            <p class="text-xs text-gray-400">{{ trim(($tx['class'] ?? '') . ($tx['section'] ? ' / ' . $tx['section'] : '')) ?: '—' }}</p>
                                                        </td>
                                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $tx['receipt'] ?: '—' }}</td>
                                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $tx['type'] }}</td>
                                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $tx['mode'] ? ucwords(str_replace('_', ' ', $tx['mode'])) : '—' }}</td>
                                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $tx['date'] === $faToday ? 'Today' : 'Yesterday' }}</td>
                                                        <td class="px-5 py-3 text-right text-sm font-semibold text-gray-900 tabular-nums">{{ $faInr($tx['amount']) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </section>
                    </div>
                @endif

                {{-- ════════ STUDENTS TAB ════════ --}}
                @if ($detailTab === 'students')
                    {{-- Analytics Strip --}}
                    <div class="px-5 py-3 border-b border-gray-100 bg-gray-50 min-h-[44px]">
                        @if ($selectedStudentId && isset($studentStats['selected']) && $studentStats['selected'])
                            @php $sel = $studentStats['selected']; @endphp
                            <div class="flex flex-wrap items-center gap-1 text-sm text-gray-600">
                                <button wire:click="selectStudent({{ $selectedStudentId }})"
                                    class="flex items-center gap-1 text-xs text-blue-500 hover:text-blue-700 pr-3 mr-2 border-r border-gray-300 transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 19l-7-7 7-7" />
                                    </svg>
                                    All Students
                                </button>
                                <div
                                    class="flex flex-wrap items-center gap-1 text-sm text-gray-500 divide-x divide-gray-300">
                                    <span class="pr-3">
                                        <strong class="text-gray-800">{{ $sel->full_name ?? '—' }}</strong>
                                    </span>
                                    <span class="px-3">
                                        Class: <strong class="text-blue-600">
                                            {{ $sel->standard?->name ?? '—' }}{{ $sel->section ? ' / ' . $sel->section->name : '' }}
                                        </strong>
                                    </span>
                                    <span class="px-3">
                                        Mobile: <strong
                                            class="text-gray-800 font-mono">{{ $sel->phone ?? '—' }}</strong>
                                    </span>
                                    <span class="px-3">
                                        Adm No: <strong
                                            class="text-gray-800 font-mono text-xs">{{ $sel->admission_no ?? '—' }}</strong>
                                    </span>
                                    <span class="px-3">
                                        Gender: <strong
                                            class="text-gray-800">{{ ucfirst($sel->gender ?? '—') }}</strong>
                                    </span>
                                    <span class="pl-3">
                                        Status:
                                        @if ($sel->user?->is_active)
                                            <strong class="text-emerald-600">Active</strong>
                                        @else
                                            <strong class="text-red-500">Inactive</strong>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @else
                            <div
                                class="flex flex-wrap items-center gap-1 text-sm text-gray-500 divide-x divide-gray-300">
                                <span class="pr-4">
                                    Total: <strong class="text-gray-800">{{ $studentStats['total'] ?? 0 }}</strong>
                                </span>
                                <span class="px-4">
                                    Active: <strong
                                        class="text-emerald-600">{{ $studentStats['active'] ?? 0 }}</strong>
                                </span>
                                <span class="px-4">
                                    Inactive: <strong
                                        class="text-red-500">{{ $studentStats['inactive'] ?? 0 }}</strong>
                                </span>
                                <span class="px-4">
                                    Male: <strong class="text-blue-600">{{ $studentStats['male'] ?? 0 }}</strong>
                                </span>
                                <span class="pl-4">
                                    Female: <strong class="text-pink-600">{{ $studentStats['female'] ?? 0 }}</strong>
                                </span>
                            </div>
                        @endif
                    </div>

                    {{-- Student Table --}}
                    @php
                        $schoolStudents = \App\Models\Student\StudentDetail::with(['user', 'standard', 'section'])
                            ->where('organization_id', $detailSchool->id)
                            ->latest()
                            ->take(20)
                            ->get();
                        $schoolStudents = \App\Support\NameOrder::sort($schoolStudents, fn ($s) => $s->full_name ?: ($s->user->name ?? ''));
                    @endphp
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-xs text-gray-400">
                                Showing latest <strong class="text-gray-600">{{ $schoolStudents->count() }}</strong>
                                of <strong class="text-gray-600">{{ $detailSchool->total_students }}</strong> students
                                @if ($selectedStudentId)
                                    — <span class="text-blue-500">1 selected</span>
                                @endif
                            </p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-y border-gray-200">
                                    <tr>
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500">#</th>
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500">Name</th>
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500">Mobile
                                        </th>
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500">Class
                                        </th>
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500">Admission
                                            No</th>
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500">Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse ($schoolStudents as $i => $s)
                                        <tr wire:click="selectStudent({{ $s->id }})"
                                            class="cursor-pointer transition-colors
                                                {{ $selectedStudentId == $s->id ? 'bg-blue-50 hover:bg-blue-50' : 'hover:bg-gray-50/70' }}">
                                            <td class="px-3 py-2.5 text-xs text-gray-400">{{ $i + 1 }}</td>
                                            <td class="px-3 py-2.5">
                                                <div class="flex items-center gap-2">
                                                    @if ($selectedStudentId == $s->id)
                                                        <span
                                                            class="w-1.5 h-1.5 rounded-full bg-blue-500 flex-shrink-0"></span>
                                                    @endif
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-800">
                                                            {{ $s->full_name ?? '—' }}</p>
                                                        <p class="text-xs text-gray-400">{{ $s->user?->email ?? '' }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-3 py-2.5 text-sm text-gray-600">{{ $s->phone ?? '—' }}</td>
                                            <td class="px-3 py-2.5 text-sm text-gray-600">{{ $s->standard?->name ?? '—' }}{{ $s->section ? ' / ' . $s->section->name : '' }}</td>
                                            <td class="px-3 py-2.5 text-sm text-gray-600">{{ $s->admission_no ?? '—' }}</td>
                                            <td class="px-3 py-2.5 text-sm font-medium {{ $s->user?->is_active ? 'text-emerald-600' : 'text-red-500' }}">{{ $s->user?->is_active ? 'Active' : 'Inactive' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-3 py-8 text-center text-sm text-gray-400">
                                                No students found
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- ════════ TEACHERS TAB ════════ --}}
                @if ($detailTab === 'teachers')
                    {{-- Analytics Strip --}}
                    <div class="px-5 py-3 border-b border-gray-100 bg-gray-50 min-h-[44px]">
                        @if ($selectedTeacherId && isset($teacherStats['selected']) && $teacherStats['selected'])
                            @php $tsel = $teacherStats['selected']; @endphp
                            <div class="flex flex-wrap items-center gap-1 text-sm text-gray-600">
                                <button wire:click="selectTeacher({{ $selectedTeacherId }})"
                                    class="flex items-center gap-1 text-xs text-blue-500 hover:text-blue-700 pr-3 mr-2 border-r border-gray-300 transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 19l-7-7 7-7" />
                                    </svg>
                                    All Teachers
                                </button>
                                <div
                                    class="flex flex-wrap items-center gap-1 text-sm text-gray-500 divide-x divide-gray-300">
                                    <span class="pr-3">
                                        <strong class="text-gray-800">{{ $tsel->user?->name ?? '—' }}</strong>
                                    </span>
                                    <span class="px-3">
                                        Emp ID: <strong
                                            class="text-gray-800 font-mono text-xs">{{ $tsel->employee_id ?? '—' }}</strong>
                                    </span>
                                    <span class="px-3">
                                        Mobile: <strong
                                            class="text-gray-800 font-mono">{{ $tsel->phone ?? '—' }}</strong>
                                    </span>
                                    <span class="px-3">
                                        Qualification: <strong
                                            class="text-gray-800">{{ $tsel->qualification ?? '—' }}</strong>
                                    </span>
                                    <span class="pl-3">
                                        Status:
                                        @if ($tsel->user?->is_active)
                                            <strong class="text-emerald-600">Active</strong>
                                        @else
                                            <strong class="text-red-500">Inactive</strong>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @else
                            <div
                                class="flex flex-wrap items-center gap-1 text-sm text-gray-500 divide-x divide-gray-300">
                                <span class="pr-4">
                                    Total: <strong class="text-gray-800">{{ $teacherStats['total'] ?? 0 }}</strong>
                                </span>
                                <span class="px-4">
                                    Active: <strong
                                        class="text-emerald-600">{{ $teacherStats['active'] ?? 0 }}</strong>
                                </span>
                                <span class="pl-4">
                                    Inactive: <strong
                                        class="text-red-500">{{ $teacherStats['inactive'] ?? 0 }}</strong>
                                </span>
                            </div>
                        @endif
                    </div>

                    {{-- Teachers Table --}}
                    @php
                        $schoolTeachers = \App\Models\Teacher\TeacherDetail::with(['user'])
                            ->where('organization_id', $detailSchool->id)
                            ->latest()
                            ->take(20)
                            ->get();
                        $schoolTeachers = \App\Support\NameOrder::sort($schoolTeachers, fn ($t) => $t->user->name ?? '');
                    @endphp
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-xs text-gray-400">
                                Showing latest <strong class="text-gray-600">{{ $schoolTeachers->count() }}</strong>
                                of <strong class="text-gray-600">{{ $detailSchool->total_teachers }}</strong>
                                teachers
                                @if ($selectedTeacherId)
                                    — <span class="text-blue-500">1 selected</span>
                                @endif
                            </p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-y border-gray-200">
                                    <tr>
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500">#</th>
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500">Name</th>
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500">Employee ID</th>
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500">Mobile</th>
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500">Qualification</th>
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse ($schoolTeachers as $i => $t)
                                        <tr wire:click="selectTeacher({{ $t->id }})"
                                            class="cursor-pointer transition-colors
                                                {{ $selectedTeacherId == $t->id ? 'bg-blue-50 hover:bg-blue-50' : 'hover:bg-gray-50/70' }}">
                                            <td class="px-3 py-2.5 text-xs text-gray-400">{{ $i + 1 }}</td>
                                            <td class="px-3 py-2.5">
                                                <div class="flex items-center gap-2">
                                                    @if ($selectedTeacherId == $t->id)
                                                        <span
                                                            class="w-1.5 h-1.5 rounded-full bg-blue-500 flex-shrink-0"></span>
                                                    @endif
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-800">
                                                            {{ $t->user?->name ?? '—' }}</p>
                                                        <p class="text-xs text-gray-400">{{ $t->user?->email ?? '' }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-3 py-2.5 text-sm text-gray-600">{{ $t->employee_id ?? '—' }}</td>
                                            <td class="px-3 py-2.5 text-sm text-gray-600">{{ $t->phone ?? '—' }}</td>
                                            <td class="px-3 py-2.5 text-sm text-gray-600">{{ $t->qualification ?? '—' }}</td>
                                            <td class="px-3 py-2.5 text-sm font-medium {{ $t->user?->is_active ? 'text-emerald-600' : 'text-red-500' }}">{{ $t->user?->is_active ? 'Active' : 'Inactive' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-3 py-8 text-center text-sm text-gray-400">
                                                No teachers found
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    @endif

    {{-- ══════════ ADD / EDIT SCHOOL SLIDE-IN PANEL ══════════
         In the school panel's Add Student look (flat 2-col grid, header and
         footer fixed). Same fields, the same two steps and the same saves. --}}
    @if ($showModal)
        @php
            $scIn = 'w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500';
            $scLb = 'block text-sm font-medium text-gray-700 mb-1.5';
            try {
                $logoPreview = $logo ? $logo->temporaryUrl() : null;
            } catch (\Throwable $e) {
                $logoPreview = null;
            }
        @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeModal"></div>
            <div class="absolute top-0 right-0 bottom-0 z-10 w-full max-w-3xl bg-white shadow-2xl flex flex-col">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editId ? 'Edit School' : 'New School' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            @if ($editId)
                                Update the school details below
                            @elseif ($modalStep === 2)
                                Step 2 of 2 · choose the modules this school can use
                            @else
                                Step 1 of 2 · a login password is generated and emailed to the school on save
                            @endif
                        </p>
                    </div>
                    <button wire:click="closeModal" type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto">
                    <div class="px-6 py-6 space-y-5">

                        {{-- ===== STEP 1: School details ===== --}}
                        @if ($editId || $modalStep === 1)

                            {{-- Logo --}}
                            <div>
                                <label class="{{ $scLb }}">
                                    School Logo <span class="text-gray-400 font-normal">(Optional, max 2 MB)</span>
                                </label>
                                <div class="flex items-center gap-3">
                                    @if ($logoPreview)
                                        <img src="{{ $logoPreview }}" class="w-12 h-12 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                    @elseif ($editId && $existingLogo)
                                        <img src="{{ $existingLogo }}" class="w-12 h-12 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                    @else
                                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                        </div>
                                    @endif
                                    <input type="file" wire:model="logo" accept="image/*" class="flex-1 text-sm">
                                </div>
                                <div wire:loading wire:target="logo" class="text-xs text-blue-600 mt-1">Uploading…</div>
                                @error('logo')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>

                            {{-- Every field in one flat 2-col grid --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="{{ $scLb }}">School Name <span class="text-red-500">*</span></label>
                                    <input wire:model.defer="schoolName" type="text" placeholder="Enter school name"
                                        class="{{ $scIn }} @error('schoolName') border-red-400 @enderror">
                                    @error('schoolName')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="{{ $scLb }}">School Code <span class="text-red-500">*</span></label>
                                    <input wire:model.defer="schoolCode" type="text" placeholder="Enter school code"
                                        class="{{ $scIn }} @error('schoolCode') border-red-400 @enderror">
                                    @error('schoolCode')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="{{ $scLb }}">Email <span class="text-red-500">*</span></label>
                                    <input wire:model.defer="email" type="email" placeholder="school@example.com"
                                        class="{{ $scIn }} @error('email') border-red-400 @enderror">
                                    @error('email')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="{{ $scLb }}">Mobile <span class="text-red-500">*</span></label>
                                    <input wire:model.defer="mobileNumber" type="text" placeholder="Enter mobile number"
                                        class="{{ $scIn }} @error('mobileNumber') border-red-400 @enderror">
                                    @error('mobileNumber')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="{{ $scLb }}">Education Board <span class="text-red-500">*</span></label>
                                    <select wire:model.defer="educationBoard"
                                        class="{{ $scIn }} @error('educationBoard') border-red-400 @enderror">
                                        <option value="">Select Board</option>
                                        <option value="CBSE">CBSE</option>
                                        <option value="UP BOARD">UP BOARD</option>
                                        <option value="UP BOARD (ENGLISH MEDIUM)">UP BOARD (ENGLISH MEDIUM)</option>
                                        <option value="UP BOARD (HINDI MEDIUM)">UP BOARD (HINDI MEDIUM)</option>
                                        <option value="UP BOARD (HINDI &amp; ENGLISH MEDIUM)">UP BOARD (HINDI &amp; ENGLISH MEDIUM)</option>
                                    </select>
                                    @error('educationBoard')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="{{ $scLb }}">Medium</label>
                                    <select wire:model.defer="medium"
                                        class="{{ $scIn }} @error('medium') border-red-400 @enderror">
                                        <option value="">Select Medium</option>
                                        <option value="english">English Medium</option>
                                        <option value="hindi">Hindi Medium</option>
                                        <option value="both">Hindi &amp; English Medium</option>
                                    </select>
                                    @error('medium')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="{{ $scLb }}">State <span class="text-red-500">*</span></label>
                                    <input wire:model.defer="state" type="text" placeholder="Enter state"
                                        class="{{ $scIn }} @error('state') border-red-400 @enderror">
                                    @error('state')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="{{ $scLb }}">Serial Number <span class="text-red-500">*</span></label>
                                    <input wire:model.defer="serialNumber" type="text" placeholder="Enter serial number"
                                        class="{{ $scIn }} @error('serialNumber') border-red-400 @enderror">
                                    @error('serialNumber')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="{{ $scLb }}">Affiliation No <span class="text-gray-400 font-normal text-xs">(optional)</span></label>
                                    <input wire:model.defer="affiliationNo" type="text" placeholder="Enter affiliation number"
                                        class="{{ $scIn }} @error('affiliationNo') border-red-400 @enderror">
                                    @error('affiliationNo')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="{{ $scLb }}">UDISE Number <span class="text-gray-400 font-normal text-xs">(optional)</span></label>
                                    <input wire:model.defer="udiseNumber" type="text" placeholder="Enter UDISE number"
                                        class="{{ $scIn }} @error('udiseNumber') border-red-400 @enderror">
                                    @error('udiseNumber')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            {{-- Full-width address --}}
                            <div>
                                <label class="{{ $scLb }}">Address</label>
                                <textarea wire:model.defer="address" rows="2" placeholder="Enter address"
                                    class="{{ $scIn }} resize-none @error('address') border-red-400 @enderror"></textarea>
                                @error('address')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>

                        @endif {{-- ===== end STEP 1 ===== --}}

                        {{-- ===== STEP 2: Module selection (create only) ===== --}}
                        @if (!$editId && $modalStep === 2)
                            <div>
                                <div class="flex items-center justify-between mb-3 gap-3">
                                    <label class="{{ $scLb }} !mb-0">Module Access</label>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <button type="button" wire:click="enableAllNewModules"
                                            class="px-3 py-1.5 text-xs font-medium rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">Enable all</button>
                                        <button type="button" wire:click="disableAllNewModules"
                                            class="px-3 py-1.5 text-xs font-medium rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">Disable all</button>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-400 mb-3">You can change these later from the school's Modules tab.</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    @foreach ($this->shownModules() as $key => $def)
                                        <label class="flex items-center justify-between gap-3 px-3.5 py-2.5 rounded-md border border-gray-300 cursor-pointer hover:bg-gray-50">
                                            <span class="text-sm text-gray-700">{{ $def['label'] ?? $key }}</span>
                                            <input type="checkbox" wire:model="selectedModules.{{ $key }}"
                                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    @if (!$editId && $modalStep === 2)
                        <button wire:click="backToDetailsStep" type="button"
                            class="mr-auto px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">
                            ← Back
                        </button>
                    @endif
                    <button wire:click="closeModal" type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">
                        Cancel
                    </button>

                    @if (!$editId && $modalStep === 1)
                        <button wire:click="goToModuleStep" type="button"
                            class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5">
                            Next: Select Modules →
                        </button>
                    @else
                        <button wire:click="saveSchool" type="button"
                            wire:loading.attr="disabled" wire:target="saveSchool,logo"
                            class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                            <span wire:loading.remove wire:target="saveSchool,logo">{{ $editId ? 'Update School' : 'Create School' }}</span>
                            <span wire:loading wire:target="saveSchool,logo">Saving...</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ BANK DETAILS SLIDE-IN PANEL ══════════ --}}
    @if ($showBankModal)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeBankModal"></div>
            <div class="absolute top-0 right-0 bottom-0 z-10 w-full max-w-xl bg-white shadow-2xl flex flex-col overflow-hidden">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-white flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">{{ $editBankMode ? 'Edit Bank Details' : 'Add Bank Details' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">School's bank account information for fee collection</p>
                    </div>
                    <button wire:click="closeBankModal" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Account Holder Name <span class="text-red-500">*</span></label>
                        <input wire:model.defer="bankHolderName" type="text" placeholder="Enter holder name"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                        @error('bankHolderName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name <span class="text-red-500">*</span></label>
                        <input wire:model.defer="bankName" type="text" placeholder="Enter bank name"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                        @error('bankName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Account Number <span class="text-red-500">*</span></label>
                        <input wire:model.defer="bankAccountNo" type="text" placeholder="Enter account number"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono" />
                        @error('bankAccountNo') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">IFSC Code <span class="text-red-500">*</span></label>
                            <input wire:model.defer="bankIfsc" type="text" placeholder="e.g. SBIN0001234"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono uppercase" />
                            @error('bankIfsc') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Branch <span class="text-red-500">*</span></label>
                            <input wire:model.defer="bankBranch" type="text" placeholder="Enter branch name"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            @error('bankBranch') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50 flex-shrink-0">
                    <button wire:click="closeBankModal" type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button wire:click="saveBankDetails" type="button"
                        wire:loading.attr="disabled" wire:target="saveBankDetails"
                        class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold
                               bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors shadow-sm
                               disabled:opacity-60 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="saveBankDetails">{{ $editBankMode ? 'Update' : 'Save Details' }}</span>
                        <span wire:loading wire:target="saveBankDetails" class="flex items-center gap-2">
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

    {{-- ══════════ ONLINE PAYMENT MODAL ══════════ --}}
    @if ($showPaymentModal)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePaymentModal"></div>
            <div class="absolute top-0 right-0 bottom-0 z-10 w-full max-w-xl bg-white shadow-2xl flex flex-col overflow-hidden">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-white flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">{{ $editPaymentMode ? 'Edit Online Payment' : 'Connect PhonePe' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Student fees will settle into this school's own PhonePe account</p>
                    </div>
                    <button wire:click="closePaymentModal" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Client ID <span class="text-red-500">*</span></label>
                        <input wire:model.defer="pgClientId" type="text" placeholder="e.g. M23K3CIXXM08M_2601292258"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono" />
                        @error('pgClientId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Client Secret @if (!$editPaymentMode)<span class="text-red-500">*</span>@endif
                        </label>
                        <input wire:model.defer="pgClientSecret" type="password" autocomplete="new-password"
                            placeholder="{{ $editPaymentMode ? 'Leave blank to keep existing' : 'Enter client secret' }}"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Client Version <span class="text-red-500">*</span></label>
                            <input wire:model.defer="pgClientVersion" type="text" placeholder="1"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono" />
                            @error('pgClientVersion') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Environment <span class="text-red-500">*</span></label>
                            <select wire:model.defer="pgEnv"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="sandbox">Sandbox (Test)</option>
                                <option value="production">Production (Live)</option>
                            </select>
                            @error('pgEnv') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Webhook Username</label>
                            <input wire:model.defer="pgWebhookUsername" type="text" placeholder="alphanumeric only"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono" />
                            @error('pgWebhookUsername') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Webhook Password</label>
                            <input wire:model.defer="pgWebhookPassword" type="password" autocomplete="new-password"
                                placeholder="{{ $editPaymentMode ? 'Leave blank to keep' : 'letters + numbers' }}"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono" />
                        </div>
                    </div>
                    <label class="flex items-center gap-2 pt-1 cursor-pointer">
                        <input wire:model.defer="pgIsActive" type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                        <span class="text-sm font-medium text-gray-700">Activate online collection for this school</span>
                    </label>
                    @error('pgIsActive') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    <p class="text-xs text-gray-400">Set the same Webhook URL <span class="font-mono">/api/v1/phonepe/webhook</span> + username/password in this school's PhonePe dashboard.</p>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50 flex-shrink-0">
                    <button wire:click="closePaymentModal" type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button wire:click="savePaymentSettings" type="button"
                        wire:loading.attr="disabled" wire:target="savePaymentSettings"
                        class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold
                               bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors shadow-sm
                               disabled:opacity-60 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="savePaymentSettings">{{ $editPaymentMode ? 'Update' : 'Save' }}</span>
                        <span wire:loading wire:target="savePaymentSettings" class="flex items-center gap-2">
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

    {{-- ══════════ DELETE CONFIRMATION (OTP-verified) ══════════
         Minimal: a plain title, a line or two of text, Cancel and the red
         action. Same steps — send a code to the super admin's email, then
         enter it to delete. --}}
    @if ($showDeleteConfirm)
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" wire:click="cancelDelete"></div>
            <div class="relative z-10 bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">

                @if ($deleteStep === 'confirm')
                    {{-- Step 1: what goes, then send the code --}}
                    <h3 class="text-base font-semibold text-gray-900">Delete this school?</h3>
                    <p class="text-sm text-gray-500 mt-2">Everything in it goes too — students, teachers, staff, logins, fees, exams and records. This cannot be undone.</p>
                    <p class="text-sm text-gray-500 mt-2">A code will be emailed to you to confirm.</p>
                    <div class="flex items-center justify-end gap-2 mt-5">
                        <button wire:click="cancelDelete" type="button"
                            class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                        <button wire:click="sendDeleteOtp" type="button" wire:loading.attr="disabled" wire:target="sendDeleteOtp"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md disabled:opacity-60">
                            <span wire:loading.remove wire:target="sendDeleteOtp">Send Code</span>
                            <span wire:loading wire:target="sendDeleteOtp">Sending…</span>
                        </button>
                    </div>
                @else
                    {{-- Step 2: enter the emailed code --}}
                    <h3 class="text-base font-semibold text-gray-900">Enter the code</h3>
                    <p class="text-sm text-gray-500 mt-2">Sent to {{ $deleteOtpEmailMasked }}. Enter it to delete the school for good.</p>
                    <input type="text" inputmode="numeric" maxlength="6" wire:model="deleteOtpInput"
                        wire:keydown.enter="verifyAndDelete" placeholder="6-digit code"
                        class="mt-4 w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-md tracking-widest focus:ring-1 focus:ring-red-400 focus:border-red-400">
                    @error('deleteOtpInput') <p class="text-xs text-red-500 mt-1.5">{{ $message }}</p> @enderror
                    <button wire:click="sendDeleteOtp" type="button" wire:loading.attr="disabled" wire:target="sendDeleteOtp"
                        class="text-xs text-gray-500 hover:text-gray-800 underline mt-2">
                        <span wire:loading.remove wire:target="sendDeleteOtp">Resend code</span>
                        <span wire:loading wire:target="sendDeleteOtp">Sending…</span>
                    </button>
                    <div class="flex items-center justify-end gap-2 mt-5">
                        <button wire:click="cancelDelete" type="button"
                            class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                        <button wire:click="verifyAndDelete" type="button" wire:loading.attr="disabled" wire:target="verifyAndDelete"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md disabled:opacity-60">
                            <span wire:loading.remove wire:target="verifyAndDelete">Delete School</span>
                            <span wire:loading wire:target="verifyAndDelete">Deleting…</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ══════════ CUSTOM FEE ON / OFF (OTP-verified) ══════════
         As the delete above: what changes, a code emailed to the super admin,
         then the code turns it on or off. --}}
    @if ($showCustomFeeConfirm && $detailSchool)
        @php $cfTurnOn = !\App\Support\CustomFee::enabled((int) $detailSchool->id); @endphp
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" wire:click="cancelCustomFee"></div>
            <div class="relative z-10 bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">

                @if ($customFeeStep === 'confirm')
                    {{-- Step 1: what changes, then send the code --}}
                    <h3 class="text-base font-semibold text-gray-900">Turn Custom Fee {{ $cfTurnOn ? 'on' : 'off' }}?</h3>
                    <p class="text-sm text-gray-500 mt-2">
                        @if ($cfTurnOn)
                            {{ $detailSchool->name }}'s nine fee sections are hidden and replaced by Add Fee, Submit Fee and View Fee. Nothing is deleted.
                        @else
                            {{ $detailSchool->name }}'s ordinary fee screens come back as they were. The custom fees and payments are kept.
                        @endif
                    </p>
                    <p class="text-sm text-gray-500 mt-2">A code will be emailed to you to confirm.</p>
                    <div class="flex items-center justify-end gap-2 mt-5">
                        <button wire:click="cancelCustomFee" type="button"
                            class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                        <button wire:click="sendCustomFeeOtp" type="button" wire:loading.attr="disabled" wire:target="sendCustomFeeOtp"
                            class="px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md disabled:opacity-60">
                            <span wire:loading.remove wire:target="sendCustomFeeOtp">Send Code</span>
                            <span wire:loading wire:target="sendCustomFeeOtp">Sending…</span>
                        </button>
                    </div>
                @else
                    {{-- Step 2: enter the emailed code --}}
                    <h3 class="text-base font-semibold text-gray-900">Enter the code</h3>
                    <p class="text-sm text-gray-500 mt-2">Sent to {{ $customFeeOtpEmailMasked }}. Enter it to turn Custom Fee {{ $cfTurnOn ? 'on' : 'off' }} for {{ $detailSchool->name }}.</p>
                    <input type="text" inputmode="numeric" maxlength="6" wire:model="customFeeOtpInput"
                        wire:keydown.enter="verifyCustomFeeOtp" placeholder="6-digit code"
                        class="mt-4 w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-md tracking-widest focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    @error('customFeeOtpInput') <p class="text-xs text-red-500 mt-1.5">{{ $message }}</p> @enderror
                    <button wire:click="sendCustomFeeOtp" type="button" wire:loading.attr="disabled" wire:target="sendCustomFeeOtp"
                        class="text-xs text-gray-500 hover:text-gray-800 underline mt-2">
                        <span wire:loading.remove wire:target="sendCustomFeeOtp">Resend code</span>
                        <span wire:loading wire:target="sendCustomFeeOtp">Sending…</span>
                    </button>
                    <div class="flex items-center justify-end gap-2 mt-5">
                        <button wire:click="cancelCustomFee" type="button"
                            class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                        <button wire:click="verifyCustomFeeOtp" type="button" wire:loading.attr="disabled" wire:target="verifyCustomFeeOtp"
                            class="px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md disabled:opacity-60">
                            <span wire:loading.remove wire:target="verifyCustomFeeOtp">Verify &amp; Turn {{ $cfTurnOn ? 'On' : 'Off' }}</span>
                            <span wire:loading wire:target="verifyCustomFeeOtp">Verifying…</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ══════════ BIOMETRIC ON / OFF (OTP-verified) ══════════
         As Custom Fee above: what changes, a code emailed to the super admin,
         then the code turns it on or off. --}}
    @if ($showBiometricConfirm && $detailSchool)
        @php $bioTurnOn = !\App\Support\Biometric::enabled((int) $detailSchool->id); @endphp
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" wire:click="cancelBiometric"></div>
            <div class="relative z-10 bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">

                @if ($biometricStep === 'confirm')
                    <h3 class="text-base font-semibold text-gray-900">Turn biometric attendance {{ $bioTurnOn ? 'on' : 'off' }}?</h3>
                    <p class="text-sm text-gray-500 mt-2">
                        @if ($bioTurnOn)
                            {{ $detailSchool->name }}'s fingerprint and card machines will mark students' and teachers' attendance as they punch.
                        @else
                            Punches from {{ $detailSchool->name }}'s machines will no longer mark attendance. What was marked stays.
                        @endif
                    </p>
                    <p class="text-sm text-gray-500 mt-2">A code will be emailed to you to confirm.</p>
                    <div class="flex items-center justify-end gap-2 mt-5">
                        <button wire:click="cancelBiometric" type="button"
                            class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                        <button wire:click="sendBiometricOtp" type="button" wire:loading.attr="disabled" wire:target="sendBiometricOtp"
                            class="px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md disabled:opacity-60">
                            <span wire:loading.remove wire:target="sendBiometricOtp">Send Code</span>
                            <span wire:loading wire:target="sendBiometricOtp">Sending…</span>
                        </button>
                    </div>
                @else
                    <h3 class="text-base font-semibold text-gray-900">Enter the code</h3>
                    <p class="text-sm text-gray-500 mt-2">Sent to {{ $biometricOtpEmailMasked }}. Enter it to turn biometric attendance {{ $bioTurnOn ? 'on' : 'off' }} for {{ $detailSchool->name }}.</p>
                    <input type="text" inputmode="numeric" maxlength="6" wire:model="biometricOtpInput"
                        wire:keydown.enter="verifyBiometricOtp" placeholder="6-digit code"
                        class="mt-4 w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-md tracking-widest focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    @error('biometricOtpInput') <p class="text-xs text-red-500 mt-1.5">{{ $message }}</p> @enderror
                    <button wire:click="sendBiometricOtp" type="button" wire:loading.attr="disabled" wire:target="sendBiometricOtp"
                        class="text-xs text-gray-500 hover:text-gray-800 underline mt-2">
                        <span wire:loading.remove wire:target="sendBiometricOtp">Resend code</span>
                        <span wire:loading wire:target="sendBiometricOtp">Sending…</span>
                    </button>
                    <div class="flex items-center justify-end gap-2 mt-5">
                        <button wire:click="cancelBiometric" type="button"
                            class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                        <button wire:click="verifyBiometricOtp" type="button" wire:loading.attr="disabled" wire:target="verifyBiometricOtp"
                            class="px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md disabled:opacity-60">
                            <span wire:loading.remove wire:target="verifyBiometricOtp">Verify &amp; Turn {{ $bioTurnOn ? 'On' : 'Off' }}</span>
                            <span wire:loading wire:target="verifyBiometricOtp">Verifying…</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Login as school: the school opens in its own tab and this super-admin
         tab stays where it is. Every login goes to the same named tab, so that
         tab follows whichever school was logged into last; an admin tab still
         open on an earlier school follows too (partials.school-login-sync). --}}
    {{-- The Login buttons post this into the school tab from the click itself,
         so the tab opens on the school's login at once — it no longer opens
         empty and waits for a Livewire answer (which, when it never came, left
         the tab on about:blank). SchoolLoginController signs in and lands on
         the school's home; the other admin tabs follow from there. --}}
    <form id="lms-school-login" method="POST" target="superlms-school" class="hidden" wire:ignore
          data-action="{{ route('super-admin.school-login', ['organization' => '999999999']) }}">
        @csrf
    </form>

    @script
    <script>
        window.lmsLoginAsSchool = function (orgId) {
            const form = document.getElementById('lms-school-login');
            if (!form) return;
            form.action = form.dataset.action.replace('999999999', String(parseInt(orgId, 10)));
            form.submit();
        };
    </script>
    @endscript

</div>
